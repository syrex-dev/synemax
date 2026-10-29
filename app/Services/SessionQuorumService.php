<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class SessionQuorumService
{
    public function __construct(
        private readonly CinemaSettings $settings,
        private readonly SnackOrderService $snackOrders,
    ) {}

    /**
     * Resolves unconfirmed sessions past confirmation deadline,
     * cancelling bookings and marking refund needed if paid.
     * Idempotent and safe to run repeatedly.
     *
     * @return array{cancelled_sessions: int, cancelled_bookings: int, agreement_sessions: int}
     */
    public function resolveAll(): array
    {
        $now = now();
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();

        $candidates = Session::query()
            ->whereNull('confirmed_at')
            ->where('status', 'tersedia')
            ->whereDate('session_date', '>=', $today->subDay()->toDateString())
            ->whereDate('session_date', '<=', $today->addDays(14)->toDateString())
            ->with(['occupiedBookings'])
            ->get();

        $cancelledSessions = 0;
        $cancelledBookings = 0;
        $agreementSessions = 0;

        foreach ($candidates as $candidate) {
            if ($candidate->isConfirmed()) {
                continue;
            }

            // 1. Past Confirmation Deadline (H-60m) -> Batalkan Sesi & Booking
            if ($candidate->isPastConfirmationDeadline()) {
                $result = DB::transaction(function () use ($candidate): int {
                    $session = Session::query()->lockForUpdate()->find($candidate->id);
                    if ($session === null || $session->isConfirmed() || $session->status !== 'tersedia') {
                        return 0;
                    }

                    $activeBookings = $session->occupiedBookings()->get();
                    $bCount = 0;

                    foreach ($activeBookings as $booking) {
                        $lockedBooking = Booking::query()->lockForUpdate()->find($booking->id);
                        if ($lockedBooking === null || in_array($lockedBooking->status, ['dibatalkan', 'kedaluwarsa'], true)) {
                            continue;
                        }

                        $hasPaid = in_array($lockedBooking->status, ['terkonfirmasi', 'menunggu_konfirmasi'], true)
                            || ! empty($lockedBooking->payment_proof_path);

                        $lockedBooking->update([
                            'status' => 'dibatalkan',
                            'refund_needed' => $hasPaid,
                            'notes' => 'Dibatalkan: kuorum tidak terpenuhi',
                            'cancelled_at' => now(),
                        ]);

                        $this->snackOrders->cancelItems($lockedBooking);
                        $bCount++;
                    }

                    $session->update([
                        'status' => 'ditutup',
                        'resolution_notes' => 'Dibatalkan otomatis: kuorum tidak terpenuhi hingga batas konfirmasi.',
                    ]);

                    return $bCount;
                }, attempts: 3);

                if ($result >= 0) {
                    $cancelledSessions++;
                    $cancelledBookings += $result;
                }
            } elseif ($candidate->isInAgreementWindow()) {
                $agreementSessions++;
            }
        }

        return [
            'cancelled_sessions' => $cancelledSessions,
            'cancelled_bookings' => $cancelledBookings,
            'agreement_sessions' => $agreementSessions,
        ];
    }
}
