<?php

namespace App\Http\Controllers;

use App\Models\BlockedDate;
use App\Models\Film;
use App\Models\Holiday;
use App\Models\Session;
use App\Services\CinemaSettings;
use App\Services\ScheduleResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PublicCinemaController extends Controller
{
    public function index(): View
    {
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $settings = app(CinemaSettings::class);
        $dates = collect(range(0, 6))->map(fn (int $offset) => $today->addDays($offset));
        $sessions = Session::query()
            ->whereBetween('session_date', [$today->toDateString(), $today->addDays(6)->toDateString()])
            ->with('film')
            ->withSum('occupiedBookings as occupied_seats', 'seats_reserved')
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get()
            ->groupBy('session_date');
        $holidayDates = Holiday::query()
            ->whereBetween('holiday_date', [$today->toDateString(), $today->addDays(6)->toDateString()])
            ->pluck('name', 'holiday_date');
        $nextAvailableSession = $sessions->flatten()->first(
            fn (Session $session): bool => ! $session->is_private
                && ! in_array($session->availabilityLabel($settings->integer('booking_cutoff_minutes', 30)), ['Tutup', 'Sudah lewat', 'Penuh'], true),
        );
        $adminWhatsApp = preg_replace('/\D+/', '', $settings->string('admin_whatsapp'));

        return view('public.home', [
            'films' => Film::query()->where('is_active', true)->orderBy('title')->get(),
            'dates' => $dates,
            'sessionsByDate' => $sessions,
            'holidayDates' => $holidayDates,
            'nextAvailableSession' => $nextAvailableSession,
            'adminWhatsAppUrl' => $adminWhatsApp !== '' ? 'https://wa.me/'.$adminWhatsApp : null,
            'weekdayPrice' => $settings->integer('weekday_price', 5000),
            'weekendPrice' => $settings->integer('weekend_price', 6000),
            'holidayPrice' => $settings->integer('holiday_price', 6000),
        ]);
    }

    public function schedule(
        string $date,
        ScheduleResolver $resolver,
        CinemaSettings $settings,
    ): View|RedirectResponse {
        if (! CarbonImmutable::hasFormat($date, 'Y-m-d')) {
            abort(404);
        }

        $selectedDate = CarbonImmutable::createFromFormat('!Y-m-d', $date, config('app.timezone'));

        if ($selectedDate === false || $selectedDate->toDateString() !== $date) {
            abort(404);
        }

        $dayType = $resolver->dayTypeFor($selectedDate);
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $dates = collect(range(0, 6))->map(fn (int $offset) => $today->addDays($offset));
        $holidayDates = Holiday::query()
            ->whereBetween('holiday_date', [$today->toDateString(), $today->addDays(6)->toDateString()])
            ->pluck('name', 'holiday_date');

        if ($dayType === null) {
            return view('public.schedule', [
                'date' => $selectedDate,
                'sessionPrice' => $settings->integer('weekday_price', 5000),
                'dates' => $dates,
                'today' => $today->toDateString(),
                'holidayDates' => $holidayDates,
                'sessions' => collect(),
                'bookingCutoffMinutes' => $settings->integer('booking_cutoff_minutes', 30),
                'closedReason' => BlockedDate::query()
                    ->where('blocked_date', $date)
                    ->where('scope_key', 'ALL')
                    ->value('reason'),
            ]);
        }

        $sessionPrice = match ($dayType) {
            'libur' => $settings->integer('holiday_price', 6000),
            'weekend' => $settings->integer('weekend_price', 6000),
            default => $settings->integer('weekday_price', 5000),
        };

        return view('public.schedule', [
            'date' => $selectedDate,
            'sessionPrice' => $sessionPrice,
            'dates' => $dates,
            'today' => $today->toDateString(),
            'holidayDates' => $holidayDates,
            'bookingCutoffMinutes' => $settings->integer('booking_cutoff_minutes', 30),
            'sessions' => Session::query()
                ->where('session_date', $date)
                ->with('film')
                ->withSum('occupiedBookings as occupied_seats', 'seats_reserved')
                ->orderBy('start_time')
                ->get(),
            'closedReason' => null,
        ]);
    }
}
