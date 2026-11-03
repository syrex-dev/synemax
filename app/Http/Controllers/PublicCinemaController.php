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
use App\Services\CinemaSettings;
use App\Services\ScheduleResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicCinemaController extends Controller
{
    public function index(): View
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

        if ($filmsMode === 'custom' && ! empty($featuredIds)) {
            $films = Film::query()
                ->where('is_active', true)
                ->whereIn('id', $featuredIds)
                ->get()
                ->sortBy(fn ($f) => array_search($f->id, $featuredIds, true))
                ->values();

            if ($films->isEmpty()) {
                $films = Film::query()->where('is_active', true)->orderBy('title')->take($filmsLimit)->get();
            }
        } else {
            $films = Film::query()->where('is_active', true)->orderBy('title')->take($filmsLimit)->get();
        }
        $allActiveFilms = Film::query()->where('is_active', true)->get(['id', 'genre', 'release_year']);

        // Single source of truth from genres table
        $genres = Genre::allActiveNames();

        // Collect unique release years
        $years = $allActiveFilms->pluck('release_year')
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

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
    public function filmIndex(Request $request): View|JsonResponse
    {
        $genre = $request->query('genre');
        $tahun = $request->query('tahun');
        $search = $request->query('cari') ?: $request->query('q');

        $query = Film::query()->where('is_active', true);

        if (filled($genre)) {
            $query->where('genre', 'like', '%'.$genre.'%');
        }

        if (filled($tahun)) {
            $query->where('release_year', (int) $tahun);
        }

        if (filled($search)) {
            $query->where('title', 'like', '%'.$search.'%');
        }

        $films = $query->orderBy('title')->paginate(30)->withQueryString();

        $genres = Genre::allActiveNames();

        $years = Film::query()
            ->where('is_active', true)
            ->whereNotNull('release_year')
            ->pluck('release_year')
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'count' => $films->total(),
                'films' => collect($films->items())->map(fn (Film $f) => [
                    'id' => $f->id,
                    'title' => $f->title,
                    'slug' => $f->slug,
                    'genre' => $f->genre,
                    'release_year' => $f->release_year,
                    'duration_minutes' => $f->duration_minutes,
                    'age_rating' => $f->age_rating,
                    'poster_url' => $f->posterUrl(),
                    'url' => route('films.show', $f->slug ?: $f->id),
                ]),
            ]);
        }

        return view('public.films.index', [
            'films' => $films,
            'genres' => $genres,
            'years' => $years,
            'selectedGenre' => $genre,
            'selectedYear' => $tahun,
            'searchQuery' => $search,
        ]);
    }

    /**
     * Film Detail Page with showtimes and booking CTA.
     */
    public function filmShow(string $slug): View
    {
        $film = Film::query()
            ->where('is_active', true)
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)->orWhere('id', $slug);
            })
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

        $otherFilms = Film::query()
            ->where('is_active', true)
            ->where('id', '!=', $film->id)
            ->inRandomOrder()
            ->take(4)
            ->get();

        return view('public.films.show', [
            'film' => $film,
            'sessions' => $sessions,
            'otherFilms' => $otherFilms,
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
