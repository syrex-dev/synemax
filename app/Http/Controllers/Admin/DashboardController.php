<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Session;
use App\Services\CinemaSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(CinemaSettings $settings): View
    {
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $activeBookings = fn ($query) => $query->where('status', '!=', 'dibatalkan');
        $todaySessions = Session::query()
            ->where('session_date', $today->toDateString())
            ->with('film')
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

        $upcomingSessions = Session::query()
            ->whereBetween('session_date', [$today->toDateString(), $today->addDays(13)->toDateString()])
            ->where('status', 'tersedia')
            ->where('is_private', false)
            ->with('film')
            ->withSum(['occupiedBookings as viewer_count' => $activeBookings], 'seats_reserved')
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get()
            ->filter(fn (Session $session): bool => $session->startsAt()->greaterThan(now()));

        $minimumViewers = $settings->integer('minimum_public_viewers', 3);

        // Trend Pendapatan 7 Hari & Ringkasan Okupansi
        $sevenDaysAgo = $today->subDays(6);
        $paidStatuses = ['terkonfirmasi', 'hadir', 'tidak_hadir'];

        $dailyRevenueData = DB::table('bookings')
            ->join('screening_sessions', 'bookings.session_id', '=', 'screening_sessions.id')
            ->whereIn('bookings.status', $paidStatuses)
            ->whereBetween('screening_sessions.session_date', [$sevenDaysAgo->toDateString(), $today->toDateString()])
            ->selectRaw('screening_sessions.session_date as date, SUM(bookings.total_price) as revenue')
            ->groupBy('screening_sessions.session_date')
            ->pluck('revenue', 'date');

        $revenueTrend = [];
        $weeklyRevenue = 0;
        for ($i = 6; $i >= 0; $i--) {
            $dateObj = $today->subDays($i);
            $dStr = $dateObj->toDateString();
            $rev = (int) ($dailyRevenueData[$dStr] ?? 0);
            $weeklyRevenue += $rev;
            $revenueTrend[] = [
                'date' => $dStr,
                'dayName' => $dateObj->locale('id')->isoFormat('ddd'),
                'dateLabel' => $dateObj->format('d/m'),
                'revenue' => $rev,
                'isToday' => $i === 0,
            ];
        }

        $maxRev = max(array_column($revenueTrend, 'revenue'));
        $maxRev = $maxRev > 0 ? $maxRev : 1;
        foreach ($revenueTrend as &$item) {
            $item['heightPercent'] = max(8, (int) round(($item['revenue'] / $maxRev) * 100));
        }
        unset($item);

        $todayViewers = (int) $todaySessions->sum('viewer_count');
        $todayRevenue = (int) ($dailyRevenueData[$today->toDateString()] ?? 0);
        $todayCapacity = (int) $todaySessions->sum(fn ($s) => $s->seatCapacity());
        $todayOccupancyPercent = $todayCapacity > 0 ? (int) round(($todayViewers / $todayCapacity) * 100) : 0;

        return view('admin.dashboard', [
            'todaySessions' => $todaySessions,
            'todayViewers' => $todayViewers,
            'todayRevenue' => $todayRevenue,
            'weeklyRevenue' => $weeklyRevenue,
            'todayCapacity' => $todayCapacity,
            'todayOccupancyPercent' => $todayOccupancyPercent,
            'revenueTrend' => $revenueTrend,
            'waitingBookings' => Booking::query()->where('status', 'menunggu_konfirmasi')->count(),
            'awaitingPaymentCount' => Booking::query()->where('status', 'menunggu_pembayaran')->count(),
            'needProofVerificationCount' => Booking::query()->where('status', 'menunggu_konfirmasi')->whereNotNull('payment_proof_path')->count(),
            'lowAttendanceSessions' => $upcomingSessions
                ->filter(fn (Session $session): bool => (int) $session->viewer_count < $minimumViewers)
                ->values(),
            'minimumViewers' => $minimumViewers,
            'qrisWarning' => in_array('qris', $settings->rawPaymentMethods(), true) && ! $settings->hasValidQrisImage(),
            'transferWarning' => in_array('transfer', $settings->rawPaymentMethods(), true) && ! $settings->hasValidTransferAccounts(),
        ]);
    }
}
