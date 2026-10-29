<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Booking;
use App\Models\Film;
use App\Models\Promo;
use App\Models\Session;
use App\Services\CinemaSettings;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(CinemaSettings $settings): View
    {
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $tomorrow = $today->addDay();
        $activeBookings = fn ($query) => $query->where('status', '!=', 'dibatalkan');

        $todaySessions = Session::query()
            ->where('session_date', $today->toDateString())
            ->with(['film', 'bookings.film'])
            ->with([
                'occupiedBookings.items' => fn ($query) => $query
                    ->where('status', '!=', 'dibatalkan')
                    ->orderBy('name_snapshot'),
            ])
            ->withCount([
                'occupiedBookings',
                'bookings as pending_booking_count' => fn ($query) => $query
                    ->where('status', 'menunggu_konfirmasi'),
            ])
            ->withSum(['occupiedBookings as viewer_count' => $activeBookings], 'seats_reserved')
            ->orderBy('start_time')
            ->get();

        $minimumViewers = $settings->minimumPublicViewers(); // default 4
        $warningWindowHours = $settings->lowAttendanceWarningWindowHours(); // default 72
        $warningWindowCutoff = now()->addHours($warningWindowHours);

        $upcomingSessions = Session::query()
            ->whereBetween('session_date', [$today->toDateString(), $today->addDays(13)->toDateString()])
            ->where('status', 'tersedia')
            ->where('is_private', false)
            ->with(['film', 'bookings.film'])
            ->withSum(['occupiedBookings as viewer_count' => $activeBookings], 'seats_reserved')
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get()
            ->filter(fn (Session $session): bool => $session->startsAt()->greaterThan(now()));

        $waitingQuorumSessions = $upcomingSessions
            ->filter(fn (Session $session): bool => ! $session->isConfirmed())
            ->values();

        $todayStr = $today->toDateString();
        $tomorrowStr = $tomorrow->toDateString();

        // 1. Hari ini
        $lowToday = $waitingQuorumSessions
            ->filter(fn (Session $s) => ((string) (is_string($s->session_date) ? $s->session_date : $s->session_date->toDateString())) === $todayStr)
            ->values();

        // 2. Besok
        $lowTomorrow = $waitingQuorumSessions
            ->filter(fn (Session $s) => ((string) (is_string($s->session_date) ? $s->session_date : $s->session_date->toDateString())) === $tomorrowStr)
            ->values();

        // 3. Mendatang
        $lowUpcoming = $waitingQuorumSessions
            ->filter(fn (Session $s) => ((string) (is_string($s->session_date) ? $s->session_date : $s->session_date->toDateString())) > $tomorrowStr && $s->startsAt()->lessThanOrEqualTo($warningWindowCutoff))
            ->values();

        // 4. Distant (beyond warning window)
        $distantCount = $waitingQuorumSessions
            ->filter(fn (Session $s) => $s->startsAt()->greaterThan($warningWindowCutoff))
            ->count();

        // "Hubungi pemesan" button visibility: ONLY if any session has bookings (> 0 viewers)
        $hasAnyBookedLowAttendance = $lowToday->contains(fn ($s) => (int) $s->viewer_count > 0 || $s->bookings->isNotEmpty())
            || $lowTomorrow->contains(fn ($s) => (int) $s->viewer_count > 0 || $s->bookings->isNotEmpty())
            || $lowUpcoming->contains(fn ($s) => (int) $s->viewer_count > 0 || $s->bookings->isNotEmpty());

        // Action Items (Perlu Tindakan) - ordered by urgency
        $waitingBookings = Booking::query()->where('status', 'menunggu_konfirmasi')->count();
        $expiringPromosCount = Promo::query()->where('is_active', true)->whereNotNull('end_date')->whereBetween('end_date', [now()->toDateString(), now()->addDays(3)->toDateString()])->count();
        $filmsWithoutPosterCount = Film::query()->where('is_active', true)->where(fn ($q) => $q->whereNull('poster_path')->orWhere('poster_path', ''))->count();
        $slidesWithoutTargetCount = Banner::query()->where('is_active', true)->where('target_type', 'none')->count();

        $rawMethods = $settings->rawPaymentMethods();
        $qrisSelected = in_array('qris', $rawMethods, true);
        $qrisWarning = $qrisSelected && (! $settings->hasValidQrisImage() || ! $settings->boolean('qris_enabled', true));
        $transferWarning = in_array('transfer', $rawMethods, true) && ! $settings->hasValidTransferAccounts();

        $actionItems = collect();
        if ($waitingBookings > 0) {
            $actionItems->push([
                'type' => 'booking',
                'level' => 'critical',
                'urgency_score' => 1,
                'title' => $waitingBookings.' booking menunggu konfirmasi',
                'url' => route('admin.bookings.index', ['status' => 'menunggu_konfirmasi']),
            ]);
        }
        if ($qrisWarning) {
            $actionItems->push([
                'type' => 'qris',
                'level' => 'critical',
                'urgency_score' => 2,
                'title' => 'QRIS nonaktif: Metode bayar QRIS dinonaktifkan sementara (unggah gambar QRIS)',
                'url' => route('admin.settings.edit').'#qris-section',
            ]);
        }
        if ($transferWarning) {
            $actionItems->push([
                'type' => 'transfer',
                'level' => 'critical',
                'urgency_score' => 3,
                'title' => 'Metode Transfer belum memiliki rekening aktif',
                'url' => route('admin.settings.edit').'#transfer-section',
            ]);
        }
        if ($expiringPromosCount > 0) {
            $actionItems->push([
                'type' => 'promo',
                'level' => 'warning',
                'urgency_score' => 4,
                'title' => $expiringPromosCount.' promo berakhir dalam 3 hari ke depan',
                'url' => route('admin.promos.index', ['status' => 'aktif']),
            ]);
        }
        if ($filmsWithoutPosterCount > 0) {
            $actionItems->push([
                'type' => 'film',
                'level' => 'info',
                'urgency_score' => 5,
                'title' => $filmsWithoutPosterCount.' film belum memiliki poster',
                'url' => route('admin.films.index'),
            ]);
        }
        if ($slidesWithoutTargetCount > 0) {
            $actionItems->push([
                'type' => 'banner',
                'level' => 'info',
                'urgency_score' => 6,
                'title' => $slidesWithoutTargetCount.' slide hero tanpa link tujuan',
                'url' => route('admin.banners.index'),
            ]);
        }

        $actionItems = $actionItems->sortBy('urgency_score')->values();

        // Stats calculation
        $todayViewers = (int) $todaySessions->sum('viewer_count');
        $todayCapacity = (int) $todaySessions->sum(fn ($s) => $s->seatCapacity());
        $todayOccupancyPercent = $todayCapacity > 0 ? (int) round(($todayViewers / $todayCapacity) * 100) : 0;

        return view('admin.dashboard', [
            'todaySessions' => $todaySessions,
            'todayViewers' => $todayViewers,
            'todayCapacity' => $todayCapacity,
            'todayOccupancyPercent' => $todayOccupancyPercent,
            'waitingBookings' => $waitingBookings,
            'totalActionsCount' => $actionItems->count(),
            'actionItems' => $actionItems,
            'minimumViewers' => $minimumViewers,
            'warningWindowHours' => $warningWindowHours,
            'lowAttendanceSessions' => $waitingQuorumSessions,
            'waitingQuorumSessions' => $waitingQuorumSessions,
            'lowToday' => $lowToday,
            'lowTomorrow' => $lowTomorrow,
            'lowUpcoming' => $lowUpcoming,
            'distantCount' => $distantCount,
            'hasAnyBookedLowAttendance' => $hasAnyBookedLowAttendance,
            'settings' => $settings,
        ]);
    }

    public function updateDensity(Request $request): JsonResponse
    {
        $data = $request->validate([
            'density' => ['required', 'in:nyaman,ringkas'],
        ]);

        $user = $request->user();
        if ($user) {
            $user->update(['display_density' => $data['density']]);
        }

        return response()->json(['status' => 'ok', 'density' => $data['density']]);
    }

    public function pollStatus(): JsonResponse
    {
        $waitingBookings = Booking::query()->where('status', 'menunggu_konfirmasi')->count();
        $latestWaitingId = Booking::query()->where('status', 'menunggu_konfirmasi')->latest('id')->value('id');

        return response()->json([
            'waiting_count' => $waitingBookings,
            'latest_id' => $latestWaitingId,
        ]);
    }
}
