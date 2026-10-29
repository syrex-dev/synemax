<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Session;
use App\Services\CinemaSettings;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, CinemaSettings $settings): View
    {
        $filters = $request->validate([
            'period' => ['nullable', 'in:harian,mingguan,bulanan'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $period = $filters['period'] ?? 'harian';
        $referenceDate = CarbonImmutable::parse(
            $filters['date'] ?? now(config('app.timezone'))->toDateString(),
            config('app.timezone'),
        );
        [$startDate, $endDate] = $this->range($period, $referenceDate);
        $paidStatuses = ['terkonfirmasi', 'hadir', 'tidak_hadir'];
        $bookings = Booking::query()
            ->whereIn('status', $paidStatuses)
            ->whereHas('session', fn ($query) => $query->whereBetween('session_date', [$startDate->toDateString(), $endDate->toDateString()]));
        $sessions = Session::query()
            ->whereBetween('session_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->withSum('occupiedBookings as occupied_seats', 'seats_reserved')
            ->orderBy('start_time')
            ->get();
        $revenue = (int) (clone $bookings)->sum('total_price');
        $ticketRevenue = (int) (clone $bookings)->sum('ticket_total');
        $snackRevenue = (int) (clone $bookings)->sum('snack_total');
        $viewers = (int) (clone $bookings)->sum('seats_reserved');
        $bookingCount = (int) (clone $bookings)->count();
        $snackBookingCount = (int) (clone $bookings)->where('snack_total', '>', 0)->count();
        $snackProfit = (int) DB::table('booking_items')
            ->join('bookings', 'booking_items.booking_id', '=', 'bookings.id')
            ->join('screening_sessions', 'bookings.session_id', '=', 'screening_sessions.id')
            ->whereIn('bookings.status', $paidStatuses)
            ->whereBetween('screening_sessions.session_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereNotNull('booking_items.cost_snapshot')
            ->where('booking_items.status', '!=', 'dibatalkan')
            ->selectRaw('COALESCE(SUM((booking_items.price_snapshot - booking_items.cost_snapshot) * booking_items.qty), 0) as profit')
            ->value('profit');
        $paidSessionCount = Session::query()
            ->whereBetween('session_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereHas('bookings', fn ($query) => $query->whereIn('status', $paidStatuses))
            ->count();
        $electricityCost = $settings->integer('electricity_cost_per_session', 0) * $paidSessionCount;
        $grossProfit = $ticketRevenue + $snackProfit - $electricityCost;
        $filmPopularity = Booking::query()
            ->select('film_id')
            ->selectRaw('COUNT(*) as booking_count')
            ->selectRaw('SUM(seats_reserved) as viewer_count')
            ->whereIn('status', $paidStatuses)
            ->whereHas('session', fn ($query) => $query->whereBetween('session_date', [$startDate->toDateString(), $endDate->toDateString()]))
            ->whereNotNull('film_id')
            ->groupBy('film_id')
            ->orderByDesc('booking_count')
            ->orderByDesc('viewer_count')
            ->with('film')
            ->limit(10)
            ->get();
        $snackPopularity = DB::table('booking_items')
            ->join('bookings', 'booking_items.booking_id', '=', 'bookings.id')
            ->join('screening_sessions', 'bookings.session_id', '=', 'screening_sessions.id')
            ->whereIn('bookings.status', $paidStatuses)
            ->whereBetween('screening_sessions.session_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('booking_items.status', '!=', 'dibatalkan')
            ->selectRaw('booking_items.name_snapshot, SUM(booking_items.qty) as quantity')
            ->groupBy('booking_items.name_snapshot')
            ->orderByDesc('quantity')
            ->limit(10)
            ->get();
        $dailySummary = DB::table('bookings')
            ->join('screening_sessions', 'bookings.session_id', '=', 'screening_sessions.id')
            ->whereIn('bookings.status', $paidStatuses)
            ->whereBetween('screening_sessions.session_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('screening_sessions.session_date as report_date, SUM(bookings.total_price) as revenue, SUM(bookings.ticket_total) as ticket_revenue, SUM(bookings.snack_total) as snack_revenue, SUM(bookings.seats_reserved) as viewers')
            ->groupBy('screening_sessions.session_date')
            ->orderBy('screening_sessions.session_date')
            ->get();

        $cancelledByQuorumCount = $sessions->filter(function (Session $session): bool {
            if (str_contains((string) $session->resolution_notes, 'kuorum')) {
                return true;
            }

            return $session->isCancelledByQuorum();
        })->count();

        $filmDemandPopularity = Booking::query()
            ->select('film_id')
            ->selectRaw('COUNT(*) as booking_count')
            ->selectRaw('SUM(COALESCE(people_count, pax, seats_reserved)) as interested_viewers')
            ->whereHas('session', fn ($query) => $query->whereBetween('session_date', [$startDate->toDateString(), $endDate->toDateString()]))
            ->whereNotNull('film_id')
            ->groupBy('film_id')
            ->orderByDesc('interested_viewers')
            ->orderByDesc('booking_count')
            ->with('film')
            ->limit(10)
            ->get();

        return view('admin.reports.index', [
            'period' => $period,
            'referenceDate' => $referenceDate,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'revenue' => $revenue,
            'ticketRevenue' => $ticketRevenue,
            'snackRevenue' => $snackRevenue,
            'snackProfit' => $snackProfit,
            'snackBookingCount' => $snackBookingCount,
            'averageSnackValue' => $snackBookingCount > 0 ? (int) round($snackRevenue / $snackBookingCount) : 0,
            'snackBookingPercent' => $bookingCount > 0 ? (int) round(($snackBookingCount / $bookingCount) * 100) : 0,
            'viewers' => $viewers,
            'bookingCount' => $bookingCount,
            'sessionCount' => $sessions->count(),
            'electricityCost' => $electricityCost,
            'grossProfit' => $grossProfit,
            'occupancyByTime' => $sessions
                ->groupBy(fn (Session $session): string => substr((string) $session->start_time, 0, 5))
                ->map(function ($sessions): array {
                    $capacity = (int) $sessions->sum(fn (Session $session): int => $session->seatCapacity());
                    $occupied = (int) $sessions->sum('occupied_seats');

                    return [
                        'occupied' => $occupied,
                        'capacity' => $capacity,
                        'percent' => $capacity > 0 ? (int) round(($occupied / $capacity) * 100) : 0,
                    ];
                }),
            'filmPopularity' => $filmPopularity,
            'filmDemandPopularity' => $filmDemandPopularity,
            'cancelledByQuorumCount' => $cancelledByQuorumCount,
            'snackPopularity' => $snackPopularity,
            'dailySummary' => $dailySummary,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'period' => ['nullable', 'in:harian,mingguan,bulanan'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $period = $filters['period'] ?? 'harian';
        $referenceDate = CarbonImmutable::parse(
            $filters['date'] ?? now(config('app.timezone'))->toDateString(),
            config('app.timezone'),
        );
        [$startDate, $endDate] = $this->range($period, $referenceDate);
        $filename = 'laporan-home-cinema-'.$startDate->toDateString().'-'.$endDate->toDateString().'.csv';

        return response()->streamDownload(function () use ($startDate, $endDate): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Kode booking', 'Tanggal sesi', 'Jam', 'Film', 'Nama', 'WhatsApp', 'Jumlah / tipe booking', 'Tipe', 'Metode bayar', 'Pendapatan tiket (Rp)', 'Pendapatan jajan (Rp)', 'Total (Rp)', 'Rincian jajan', 'Status']);

            Booking::query()
                ->with([
                    'session',
                    'film',
                    'items' => fn ($query) => $query->where('status', '!=', 'dibatalkan'),
                ])
                ->whereHas('session', fn ($query) => $query->whereBetween('session_date', [$startDate->toDateString(), $endDate->toDateString()]))
                ->orderBy('id')
                ->chunkById(200, function ($bookings) use ($output): void {
                    foreach ($bookings as $booking) {
                        $details = $booking->items
                            ->map(fn ($item): string => $item->name_snapshot.' x'.$item->qty.($item->note ? ' ('.$item->note.')' : ''))
                            ->implode('; ');
                        fputcsv($output, array_map($this->safeCsvValue(...), [
                            $booking->code,
                            $booking->session->session_date,
                            substr((string) $booking->session->start_time, 0, 5),
                            $booking->film?->title ?? '',
                            $booking->customer_name,
                            $booking->whatsapp,
                            $booking->booking_type === 'private'
                                ? 'Sewa Private (6 kursi)'
                                : $booking->pax.' orang',
                            $booking->booking_type,
                            $booking->payment_method,
                            $booking->ticket_total,
                            $booking->snack_total,
                            $booking->total_price,
                            $details,
                            $booking->status,
                        ]));
                    }
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    private function range(string $period, CarbonImmutable $referenceDate): array
    {
        return match ($period) {
            'mingguan' => [$referenceDate->startOfWeek(), $referenceDate->endOfWeek()],
            'bulanan' => [$referenceDate->startOfMonth(), $referenceDate->endOfMonth()],
            default => [$referenceDate->startOfDay(), $referenceDate->endOfDay()],
        };
    }

    private function safeCsvValue(mixed $value): string|int
    {
        if (is_int($value)) {
            return $value;
        }

        $value = (string) $value;

        return preg_match('/^\s*[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}
