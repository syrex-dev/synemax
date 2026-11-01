<?php

namespace App\Support;

use App\Models\Booking;

class BookingStatusPresenter
{
    /**
     * Map payment status to standard customer-facing Indonesian text.
     *
     * Rules:
     * - "Lunas" ONLY if payment is actually confirmed.
     * - "Bayar di tempat" if payment method is at venue and not yet completed/attended.
     * - "Menunggu konfirmasi" if online payment submitted and awaiting verification.
     * - "Dibatalkan" or "Dibatalkan (Perlu Refund)" if booking is cancelled.
     * - "Belum dibayar" if awaiting online payment.
     * - "Kedaluwarsa" if expired.
     */
    public static function paymentStatusLabel(Booking $booking): string
    {
        if ($booking->status === 'dibatalkan') {
            return $booking->refund_needed ? 'Dibatalkan (Perlu Refund)' : 'Dibatalkan';
        }

        if ($booking->status === 'kedaluwarsa') {
            return 'Kedaluwarsa';
        }

        // Cash / Bayar di tempat: only "Lunas" once the customer attends or completes session at venue
        if ($booking->payment_method === 'di_tempat') {
            if (in_array($booking->status, ['hadir', 'selesai'], true)) {
                return 'Lunas';
            }

            return 'Bayar di tempat';
        }

        // Online payment (QRIS / Transfer):
        if (in_array($booking->status, ['terkonfirmasi', 'hadir', 'selesai'], true)) {
            return 'Lunas';
        }

        if ($booking->status === 'menunggu_konfirmasi') {
            return 'Menunggu konfirmasi';
        }

        return 'Belum dibayar';
    }

    /**
     * CSS color class for the payment status label.
     */
    public static function paymentStatusColor(Booking $booking): string
    {
        $label = self::paymentStatusLabel($booking);

        return match ($label) {
            'Lunas' => 'text-emerald-700 dark:text-emerald-400',
            'Bayar di tempat' => 'text-amber-800 dark:text-amber-300',
            'Menunggu konfirmasi' => 'text-amber-700 dark:text-amber-400',
            'Dibatalkan', 'Dibatalkan (Perlu Refund)', 'Kedaluwarsa' => 'text-red-700 dark:text-red-400',
            default => 'text-slate-600 dark:text-slate-400',
        };
    }

    /**
     * Guidance text for the ticket card according to session confirmation and booking status:
     * - If session is NOT confirmed to air: "E-tiket aktif dan dapat disimpan setelah sesi dipastikan tayang."
     * - If session IS confirmed to air: "Tunjukkan kode ini ke petugas saat tiba."
     * - If ticket is attended/completed: "Tiket sudah digunakan."
     * - If ticket is cancelled: "Booking ini telah dibatalkan dan tiket tidak dapat digunakan."
     */
    public static function ticketGuidanceText(Booking $booking): string
    {
        $session = $booking->session;
        $isSessionConfirmed = $session ? $session->isConfirmed() : false;
        $isCancelled = $booking->status === 'dibatalkan';
        $isCompleted = in_array($booking->status, ['selesai', 'terlewat', 'hadir'], true);

        if ($isCancelled) {
            return 'Booking ini telah dibatalkan dan tiket tidak dapat digunakan.';
        }

        if ($isCompleted) {
            return 'Tiket sudah digunakan.';
        }

        if (! $isSessionConfirmed) {
            return 'E-tiket aktif dan dapat disimpan setelah sesi dipastikan tayang.';
        }

        return 'Tunjukkan kode ini ke petugas saat tiba.';
    }
}
