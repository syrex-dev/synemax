<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Session;
use App\Services\CinemaSettings;
use Carbon\CarbonImmutable;
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

        return view('admin.dashboard', [
            'todaySessions' => $todaySessions,
            'todayViewers' => (int) $todaySessions->sum('viewer_count'),
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
