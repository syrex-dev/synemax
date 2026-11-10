<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\BlockedDate;
use App\Models\Film;
use App\Models\Genre;
use App\Models\Holiday;
use App\Models\NobarEvent;
use App\Models\Promo;
use App\Models\Session;
use App\Queries\FilmCatalogQuery;
use App\Services\CinemaSettings;
use App\Services\ScheduleResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicCinemaController extends Controller
{
    public function index(Request $request, FilmCatalogQuery $catalogQuery): View
    {
        $settings = app(CinemaSettings::class);

        // 1. Hero Carousel (Image only, admin managed)
        $heroBanners = Banner::query()
            ->active()
            ->ofType('hero')
            ->orderBy('sort_order')
            ->get();

        if ($heroBanners->isEmpty()) {
            $heroBanners = collect([
                new Banner([
                    'type' => 'hero',
                    'title' => 'Selamat Datang di SynemaX Home Cinema',
                    'image_path' => null,
                    'target_type' => 'none',
                    'sort_order' => 1,
                    'is_active' => true,
                ]),
            ]);
        }

        // 2. Info Chips from Settings
        $infoChips = $settings->homeInfoChips();

        // 3. Film Catalog
        $filmsLimit = $settings->homeFilmsCount();
        $filmsMode = $settings->homeFilmsMode();
        $featuredIds = $settings->homeFeaturedFilmIds();

        $selectedGenre = trim((string) $request->query('genre', ''));
        $selectedYear = trim((string) $request->query('tahun', ''));
        $hasActiveFilter = $selectedGenre !== '' || ($selectedYear !== '' && is_numeric($selectedYear));

        if ($hasActiveFilter) {
            $filteredPaginator = $catalogQuery->paginateCatalog([
                'genre' => $selectedGenre,
                'tahun' => $selectedYear,
            ], $filmsLimit);

            $films = collect($filteredPaginator->items());
            $totalFilteredCount = $filteredPaginator->total();
        } else {
            $films = collect();
            if ($filmsMode === 'custom' && ! empty($featuredIds)) {
                $films = Film::query()
                    ->where('is_active', true)
                    ->whereIn('id', $featuredIds)
                    ->get()
                    ->sortBy(fn ($f) => array_search($f->id, $featuredIds, true))
                    ->values();
            }

            if ($films->isEmpty()) {
                $films = Film::query()
                    ->where('is_active', true)
                    ->orderByRaw("CASE WHEN poster_path IS NOT NULL AND poster_path != '' THEN 0 ELSE 1 END")
                    ->applyCustomSort()
                    ->take($filmsLimit)
                    ->get();
            }

            $totalFilteredCount = $films->count();
        }

        // Single source of truth from genres table
        $genres = Genre::allActiveNames();

        // Collect unique release years across all active films
        $years = $catalogQuery->availableYears();

        // 4. Promo & Penawaran (pure image banners)
        $promos = Promo::query()->active()->current()->ordered()->take(6)->get();

        // 5. Nobar Mendatang
        $infoNobarEnabled = $settings->infoNobarEnabled();
        $upcomingNobar = $infoNobarEnabled
            ? NobarEvent::query()
                ->where('status_publikasi', 'terbit')
                ->where('status_acara', '!=', 'dibatalkan')
                ->where('kickoff_at', '>=', now()->subHours(2))
                ->orderBy('kickoff_at')
                ->limit(4)
                ->get()
            : collect();

        return view('public.home', [
            'heroBanners' => $heroBanners,
            'infoChips' => $infoChips,
            'films' => $films,
            'filmsData' => $films->map(fn (Film $f): array => $this->serializeCatalogCard($f))->values(),
            'selectedGenre' => $selectedGenre,
            'selectedYear' => $selectedYear,
            'totalFilteredCount' => $totalFilteredCount,
            'filmsLimit' => $filmsLimit,
            'genres' => $genres,
            'years' => $years,
            'promos' => $promos,
            'upcomingNobar' => $upcomingNobar,
            'infoNobarEnabled' => $infoNobarEnabled,
            'snackBanners' => $settings->homeSnackBanners(),
            'settings' => $settings,
        ]);
    }

    /**
     * Full Film Catalog Page with live filtering.
     */
    public function filmIndex(Request $request, FilmCatalogQuery $catalogQuery): View|JsonResponse
    {
        $genre = $request->query('genre');
        $tahun = $request->query('tahun');
        $search = $request->query('cari') ?: $request->query('q');
        $isJsonRequest = $request->wantsJson() || $request->ajax();
        $perPage = $isJsonRequest && $request->filled('limit')
            ? max(1, min(30, $request->integer('limit', 30)))
            : 30;

        $films = $catalogQuery->paginateCatalog([
            'genre' => $genre,
            'tahun' => $tahun,
            'cari' => $search,
        ], $perPage);

        $rootGenres = Genre::whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $genres = $rootGenres->pluck('name')->all();

        $selectedGenreModel = $genre ? Genre::where('name', $genre)->orWhere('normalized_name', Genre::normalizeName($genre))->first() : null;
        $childGenres = collect();
        if ($selectedGenreModel) {
            if ($selectedGenreModel->isParent()) {
                $childGenres = $selectedGenreModel->children()->where('is_active', true)->get();
            } elseif ($selectedGenreModel->parent_id && $selectedGenreModel->parent) {
                $childGenres = $selectedGenreModel->parent->children()->where('is_active', true)->get();
            }
        }

        $years = $catalogQuery->availableYears();

        if ($isJsonRequest) {
            return response()->json([
                'count' => $films->total(),
                'films' => collect($films->items())->map(fn (Film $f): array => $this->serializeCatalogCard($f))->values(),
            ]);
        }

        return view('public.films.index', [
            'films' => $films,
            'genres' => $genres,
            'childGenres' => $childGenres,
            'selectedGenreModel' => $selectedGenreModel,
            'years' => $years,
            'selectedGenre' => $genre,
            'selectedYear' => $tahun,
            'searchQuery' => $search,
        ]);
    }

    /**
     * Serialize a film model into the public catalog card payload.
     *
     * @return array{id: int, title: string, slug: ?string, genre: ?string, release_year: ?int, duration_minutes: ?int, age_rating: ?string, poster_url: ?string, custom_badge: ?string, url: string}
     */
    private function serializeCatalogCard(Film $film): array
    {
        return [
            'id' => $film->id,
            'title' => $film->title,
            'slug' => $film->slug,
            'genre' => $film->genre,
            'release_year' => $film->release_year ?: ($film->release_date ? (int) $film->release_date->format('Y') : null),
            'duration_minutes' => $film->duration_minutes,
            'age_rating' => $film->age_rating,
            'poster_url' => $film->posterUrl(),
            'custom_badge' => $film->custom_badge,
            'url' => route('films.show', $film->slug ?: $film->id),
        ];
    }

    /**
     * Film Detail Page with showtimes and booking CTA.
     */
    public function filmShow(string $slug, FilmCatalogQuery $catalogQuery): View
    {
        $film = Film::query()
            ->where('is_active', true)
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)->orWhere('id', $slug);
            })
            ->with(['genres' => fn ($q) => $q->orderByPivot('position')])
            ->firstOrFail();

        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();

        // Upcoming screening sessions that already chose this film (max 3)
        $sessions = Session::query()
            ->where(function ($q) use ($film) {
                $q->where('film_id', $film->id)
                    ->orWhereHas('bookings', function ($bq) use ($film) {
                        $bq->whereIn('status', ['menunggu_pembayaran', 'menunggu_konfirmasi', 'terkonfirmasi'])
                            ->where('film_id', $film->id);
                    });
            })
            ->where('session_date', '>=', $today->toDateString())
            ->where('status', 'tersedia')
            ->where('is_private', false)
            ->withSum('occupiedBookings as occupied_seats', 'seats_reserved')
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->take(3)
            ->get();

        $otherFilms = $catalogQuery->similarFilms($film, 4);
        $matchedGenreLabel = $film->primaryGenre()?->name ?: $film->genre;

        return view('public.films.show', [
            'film' => $film,
            'sessions' => $sessions,
            'otherFilms' => $otherFilms,
            'matchedGenreLabel' => $matchedGenreLabel,
        ]);
    }

    /**
     * Public Promos Listing Page.
     */
    public function promoIndex(): View
    {
        $activePromos = Promo::query()->active()->current()->ordered()->get();
        $expiredPromos = Promo::query()->active()
            ->whereNotNull('end_date')
            ->where('end_date', '<', now()->toDateString())
            ->ordered()
            ->get();

        return view('public.promos.index', [
            'activePromos' => $activePromos,
            'expiredPromos' => $expiredPromos,
        ]);
    }

    /**
     * Public Promo Detail Page (CGV inspired layout).
     */
    public function promoShow(string $slug): View
    {
        $promo = Promo::query()
            ->where('is_active', true)
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)->orWhere('id', $slug);
            })
            ->firstOrFail();

        $otherPromos = Promo::query()
            ->active()
            ->current()
            ->where('id', '!=', $promo->id)
            ->ordered()
            ->take(3)
            ->get();

        return view('public.promos.show', [
            'promo' => $promo,
            'otherPromos' => $otherPromos,
        ]);
    }

    /**
     * Existing public schedule view.
     */
    public function schedule(?string $date = null, ?ScheduleResolver $resolver = null): View|RedirectResponse
    {
        $resolver = $resolver ?? app(ScheduleResolver::class);
        $settings = app(CinemaSettings::class);
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();

        $bookingCutoffMinutes = $settings->integer('booking_cutoff_minutes', 30);
        $candidateDate = null;

        $selectedFilm = null;
        $filmParam = request('film');
        if (filled($filmParam)) {
            $selectedFilm = Film::query()
                ->where('is_active', true)
                ->where(function ($q) use ($filmParam) {
                    $q->where('id', $filmParam)->orWhere('slug', $filmParam);
                })
                ->first();

            if (! $selectedFilm) {
                session()->flash('warning', 'Film tidak tersedia');
            }
        }

        if ($date === null) {
            // Find the first date from today onwards that still has bookable sessions
            for ($offset = 0; $offset < 14; $offset++) {
                $checkDate = $today->addDays($offset);
                $daySessions = Session::query()
                    ->where('session_date', $checkDate->toDateString())
                    ->where('status', 'tersedia')
                    ->where('is_private', false)
                    ->with('film')
                    ->withSum('occupiedBookings as occupied_seats', 'seats_reserved')
                    ->get();

                $hasAvailable = $daySessions->contains(function ($s) use ($bookingCutoffMinutes, $selectedFilm) {
                    $canBook = $s->statusWaktu($bookingCutoffMinutes)['can_book'];
                    if (! $canBook) {
                        return false;
                    }

                    // If a film is selected, prefer sessions that are not locked to another film
                    if ($selectedFilm && $s->isConfirmed() && $s->film_id !== $selectedFilm->id) {
                        return false;
                    }

                    return true;
                });

                if ($hasAvailable) {
                    $candidateDate = $checkDate;
                    break;
                }
            }

            $targetDate = $candidateDate ?? $today;
        } else {
            $targetDate = CarbonImmutable::parse($date, config('app.timezone'))->startOfDay();
        }

        // Allow looking up to 14 days ahead
        if ($targetDate->lessThan($today) || $targetDate->greaterThan($today->addDays(13))) {
            return redirect()->route('schedule.date', array_filter(['date' => $today->toDateString(), 'film' => $selectedFilm?->id]));
        }

        $dayType = $resolver->dayTypeFor($targetDate);
        $dates = collect(range(0, 13))->map(fn (int $offset) => $today->addDays($offset));
        $holidayDates = Holiday::query()
            ->whereBetween('holiday_date', [$today->toDateString(), $today->addDays(13)->toDateString()])
            ->pluck('name', 'holiday_date');

        $sessionPrice = $settings->ticketPriceForDayType($dayType);
        $hasAnyAvailableDate = $candidateDate !== null;

        $closedReason = BlockedDate::query()
            ->where('blocked_date', $targetDate->toDateString())
            ->where('scope_key', 'ALL')
            ->value('reason');

        $sessions = Session::query()
            ->where('session_date', $targetDate->toDateString())
            ->with('film')
            ->withSum('occupiedBookings as occupied_seats', 'seats_reserved')
            ->orderBy('start_time')
            ->get();

        $holiday = Holiday::query()->where('holiday_date', $targetDate->toDateString())->first();
        $blockedDate = BlockedDate::query()->where('blocked_date', $targetDate->toDateString())->first();

        return view('public.schedule', [
            'date' => $targetDate,
            'targetDate' => $targetDate,
            'today' => $today->toDateString(),
            'dates' => $dates,
            'holidayDates' => $holidayDates,
            'sessionPrice' => $sessionPrice,
            'hasAnyAvailableDate' => $hasAnyAvailableDate,
            'sessions' => $sessions,
            'closedReason' => $closedReason,
            'holiday' => $holiday,
            'blockedDate' => $blockedDate,
            'bookingCutoffMinutes' => $settings->integer('booking_cutoff_minutes', 30),
            'weekdayPrice' => $settings->weekdayPrice(),
            'weekendPrice' => $settings->weekendPrice(),
            'holidayPrice' => $settings->holidayPrice(),
            'privatePrice' => $settings->privatePrice(),
            'selectedFilm' => $selectedFilm,
        ]);
    }
}
