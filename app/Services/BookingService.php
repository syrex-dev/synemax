<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CustomerProfile;
use App\Models\Film;
use App\Models\Session;
use App\Support\WhatsAppNumber;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(
        private readonly CinemaSettings $settings,
        private readonly ScheduleResolver $scheduleResolver,
        private readonly SnackOrderService $snackOrders,
    ) {}

    /**
     * @param array{
     *     customer_name: string,
     *     whatsapp: string,
     *     pax?: int|string,
     *     booking_type: string,
     *     film_id?: int|string|null,
     *     payment_method: string
     * } $data
     */
    public function create(Session $requestedSession, array $data, ?UploadedFile $paymentProof = null): Booking
    {
        return DB::transaction(function () use ($requestedSession, $data): Booking {
            $session = Session::query()->lockForUpdate()->findOrFail($requestedSession->getKey());
            $startsAt = $session->startsAt();
            $dayType = $this->scheduleResolver->dayTypeFor($startsAt);
            $startTime = substr((string) $session->start_time, 0, 5);

            if (
                $session->status !== 'tersedia'
                || $dayType === null
                || $this->scheduleResolver->isSlotBlocked($startsAt, $startTime)
            ) {
                $this->reject('session', 'Sesi ini sedang ditutup. Silakan pilih jadwal lain.');
            }

            $bookingDeadline = $startsAt->subMinutes(
                $this->settings->integer('booking_cutoff_minutes', 30),
            );

            if (now()->greaterThanOrEqualTo($bookingDeadline)) {
                $this->reject('session', 'Booking untuk sesi ini sudah ditutup.');
            }

            $whatsapp = WhatsAppNumber::normalize($data['whatsapp']);
            $profile = CustomerProfile::query()->where('whatsapp', $whatsapp)->lockForUpdate()->first();

            if ($profile?->is_blocked) {
                $this->reject('whatsapp', 'Nomor ini belum bisa dipakai untuk booking. Silakan hubungi admin.');
            }

            $isPrivate = $data['booking_type'] === 'private';
            $pax = $isPrivate ? Session::MAX_CAPACITY : (int) ($data['pax'] ?? 0);
            $occupiedSeats = (int) $session->occupiedBookings()->sum('seats_reserved');
            $seatCapacity = $session->seatCapacity();

            if ($session->isPrivateActive()) {
                $this->reject('booking_type', 'Sesi ini sudah disewa private.');
            }

            if ($isPrivate) {
                if ($session->hasActivePerPersonBookings() || $occupiedSeats > 0 || $seatCapacity < Session::MAX_CAPACITY) {
                    $this->reject('booking_type', 'Sudah ada pemesan per orang di sesi ini, jadi Sewa Private tidak tersedia.');
                }
            } else {
                if ($pax < 1 || $pax > Session::MAX_CAPACITY) {
                    $this->reject('pax', 'Jumlah orang harus antara 1 sampai 6.');
                }

                if ($occupiedSeats + $pax > $seatCapacity) {
                    $this->reject('pax', 'Jumlah kursi tersisa tidak cukup. Silakan pilih jumlah orang lebih sedikit.');
                }
            }

            $filmId = $session->film_id ?: ($data['film_id'] ?? null);

            if ($session->film_id !== null && isset($data['film_id']) && (int) $data['film_id'] !== $session->film_id) {
                $this->reject('film_id', 'Sesi ini sudah terkunci untuk film yang berbeda.');
            }

            $film = Film::query()
                ->where('is_active', true)
                ->find($filmId);

            if ($film === null) {
                $this->reject('film_id', 'Pilih film yang masih tersedia.');
            }

            $paymentMethod = $data['payment_method'];
            $availableMethods = $this->settings->availablePaymentMethodsForSession($session);

            if (! in_array($paymentMethod, $availableMethods, true)) {
                $this->reject('payment_method', 'Metode pembayaran ini sedang tidak tersedia untuk sesi ini.');
            }

            $price = match (true) {
                $isPrivate => $this->settings->integer('private_price', 36000),
                $dayType === 'libur' => $this->settings->integer('holiday_price', 6000) * $pax,
                $dayType === 'weekend' => $this->settings->integer('weekend_price', 6000) * $pax,
                default => $this->settings->integer('weekday_price', 5000) * $pax,
            };

            $profile ??= CustomerProfile::query()->firstOrCreate(['whatsapp' => $whatsapp]);
            $session->film_id = $film->getKey();
            $session->is_private = $isPrivate;
            $session->save();

            $isOnline = in_array($paymentMethod, ['qris', 'transfer'], true);
            $status = $isOnline ? 'menunggu_pembayaran' : 'menunggu_konfirmasi';
            $paymentExpiresAt = null;

            if ($isOnline) {
                $paymentLimitMinutes = $this->settings->paymentTimeLimitMinutes();
                $onlineBufferMinutes = $this->settings->onlinePaymentCutoffMinutes();
                $maxTimeByLimit = now()->addMinutes($paymentLimitMinutes);
                $maxTimeBySession = $startsAt->subMinutes($onlineBufferMinutes);
                $paymentExpiresAt = $maxTimeByLimit->lessThan($maxTimeBySession) ? $maxTimeByLimit : $maxTimeBySession;
            }

            $booking = Booking::query()->create([
                'code' => $this->newBookingCode($startsAt),
                'session_id' => $session->getKey(),
                'film_id' => $film->getKey(),
                'customer_profile_id' => $profile->getKey(),
                'customer_name' => trim($data['customer_name']),
                'whatsapp' => $whatsapp,
                'pax' => $pax,
                'seats_reserved' => $isPrivate ? $seatCapacity : $pax,
                'booking_type' => $isPrivate ? 'private' : 'per_orang',
                'total_price' => $price,
                'ticket_total' => $price,
                'snack_total' => 0,
                'payment_method' => $paymentMethod,
                'payment_proof_path' => null,
                'payment_expires_at' => $paymentExpiresAt,
                'status' => $status,
            ]);

            $booking = $this->snackOrders->replaceItems($booking, $data['snacks'] ?? []);

            return $booking;
        }, attempts: 3);
    }

    public function expireUnpaidBookings(): int
    {
        $overdueBookings = Booking::query()
            ->where('status', 'menunggu_pembayaran')
            ->where('payment_expires_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($overdueBookings as $booking) {
            DB::transaction(function () use ($booking): void {
                $lockedBooking = Booking::query()->lockForUpdate()->find($booking->id);
                if ($lockedBooking === null || $lockedBooking->status !== 'menunggu_pembayaran') {
                    return;
                }

                $session = Session::query()->lockForUpdate()->findOrFail($lockedBooking->session_id);
                $lockedBooking->update(['status' => 'kedaluwarsa']);
                $this->snackOrders->cancelItems($lockedBooking);
                $session->syncPrivateStatus();
            }, attempts: 3);
            $count++;
        }

        return $count;
    }

    public function expireIfOverdue(Booking $booking): Booking
    {
        if (
            $booking->status === 'menunggu_pembayaran'
            && $booking->payment_expires_at !== null
            && $booking->payment_expires_at->isPast()
        ) {
            DB::transaction(function () use ($booking): void {
                $lockedBooking = Booking::query()->lockForUpdate()->find($booking->id);
                if ($lockedBooking === null || $lockedBooking->status !== 'menunggu_pembayaran') {
                    return;
                }

                $session = Session::query()->lockForUpdate()->findOrFail($lockedBooking->session_id);
                $lockedBooking->update(['status' => 'kedaluwarsa']);
                $this->snackOrders->cancelItems($lockedBooking);
                $session->syncPrivateStatus();
            }, attempts: 3);

            return $booking->fresh();
        }

        return $booking;
    }

    public function restoreExpired(Booking $booking): Booking
    {
        if ($booking->status !== 'kedaluwarsa') {
            $this->reject('status', 'Hanya booking yang kedaluwarsa yang dapat dipulihkan.');
        }

        return DB::transaction(function () use ($booking): Booking {
            $session = Session::query()->lockForUpdate()->findOrFail($booking->session_id);
            $lockedBooking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            $startsAt = $session->startsAt();
            $onlineBufferMinutes = $this->settings->onlinePaymentCutoffMinutes();
            $sessionCutoff = $startsAt->subMinutes($onlineBufferMinutes);

            if (now()->greaterThanOrEqualTo($sessionCutoff)) {
                $this->reject('session', 'Tidak dapat memulihkan booking: sesi sudah terlalu dekat dengan waktu mulai.');
            }

            if ($session->status !== 'tersedia') {
                $this->reject('session', 'Tidak dapat memulihkan booking: sesi sedang tidak tersedia.');
            }

            $isPrivate = $lockedBooking->booking_type === 'private';
            $seatCapacity = $session->seatCapacity();
            $occupiedSeats = (int) $session->occupiedBookings()->sum('seats_reserved');

            if ($isPrivate) {
                if ($session->hasActivePerPersonBookings() || $occupiedSeats > 0) {
                    $this->reject('capacity', 'Sudah ada pemesan per orang di sesi ini, jadi Sewa Private tidak tersedia.');
                }
                if ($session->isPrivateActive()) {
                    $this->reject('capacity', 'Sesi ini sudah disewa private.');
                }
                $session->update(['is_private' => true]);
            } else {
                if ($session->isPrivateActive()) {
                    $this->reject('capacity', 'Sesi ini sudah disewa private.');
                }
                if ($occupiedSeats + $lockedBooking->pax > $seatCapacity) {
                    $this->reject('capacity', 'Tidak dapat memulihkan: kursi pada sesi ini sudah tidak mencukupi.');
                }
            }

            $paymentLimitMinutes = $this->settings->paymentTimeLimitMinutes();
            $maxTimeByLimit = now()->addMinutes($paymentLimitMinutes);
            $expiresAt = $maxTimeByLimit->lessThan($sessionCutoff) ? $maxTimeByLimit : $sessionCutoff;

            $lockedBooking->update([
                'status' => 'menunggu_pembayaran',
                'payment_expires_at' => $expiresAt,
                'proof_rejection_note' => null,
            ]);

            $session->syncPrivateStatus();

            return $lockedBooking->fresh(['session', 'film']);
        }, attempts: 3);
    }

    public function rejectProof(Booking $booking, string $reason): Booking
    {
        return DB::transaction(function () use ($booking, $reason): Booking {
            $session = Session::query()->lockForUpdate()->findOrFail($booking->session_id);
            $lockedBooking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            $startsAt = $session->startsAt();
            $onlineBufferMinutes = $this->settings->onlinePaymentCutoffMinutes();
            $sessionCutoff = $startsAt->subMinutes($onlineBufferMinutes);

            if (now()->greaterThanOrEqualTo($sessionCutoff)) {
                $this->reject('session', 'Waktu pembayaran online untuk sesi ini sudah lewat batas.');
            }

            $seatCapacity = $session->seatCapacity();
            $occupiedSeats = (int) $session->occupiedBookings()->where('id', '!=', $lockedBooking->id)->sum('seats_reserved');
            $isPrivate = $lockedBooking->booking_type === 'private';

            if ($isPrivate) {
                if ($session->hasActivePerPersonBookings() || $occupiedSeats > 0) {
                    $this->reject('capacity', 'Sudah ada pemesan per orang di sesi ini, jadi Sewa Private tidak tersedia.');
                }
            } else {
                if ($session->isPrivateActive()) {
                    $this->reject('capacity', 'Sesi ini sudah disewa private.');
                }
                if ($occupiedSeats + $lockedBooking->pax > $seatCapacity) {
                    $this->reject('capacity', 'Kursi pada sesi ini sudah tidak mencukupi.');
                }
            }

            $paymentLimitMinutes = $this->settings->paymentTimeLimitMinutes();
            $maxTimeByLimit = now()->addMinutes($paymentLimitMinutes);
            $expiresAt = $maxTimeByLimit->lessThan($sessionCutoff) ? $maxTimeByLimit : $sessionCutoff;

            if ($lockedBooking->payment_proof_path && Storage::disk('local')->exists($lockedBooking->payment_proof_path)) {
                Storage::disk('local')->delete($lockedBooking->payment_proof_path);
            }

            $lockedBooking->update([
                'status' => 'menunggu_pembayaran',
                'payment_expires_at' => $expiresAt,
                'payment_proof_path' => null,
                'proof_rejection_note' => $reason,
            ]);

            $session->syncPrivateStatus();

            return $lockedBooking->fresh(['session', 'film']);
        }, attempts: 3);
    }

    public function cancel(string $code, string $whatsapp): Booking
    {
        $normalizedWhatsApp = WhatsAppNumber::normalize($whatsapp);
        $candidate = Booking::query()
            ->where('code', strtoupper(trim($code)))
            ->where('whatsapp', $normalizedWhatsApp)
            ->first();

        if ($candidate === null) {
            $this->reject('code', 'Kode booking atau nomor WhatsApp tidak cocok.');
        }

        return DB::transaction(function () use ($candidate, $normalizedWhatsApp): Booking {
            $session = Session::query()->lockForUpdate()->findOrFail($candidate->session_id);
            $booking = Booking::query()->lockForUpdate()->findOrFail($candidate->getKey());

            if ($booking->whatsapp !== $normalizedWhatsApp) {
                $this->reject('code', 'Kode booking atau nomor WhatsApp tidak cocok.');
            }

            if (! in_array($booking->status, ['menunggu_pembayaran', 'menunggu_konfirmasi', 'terkonfirmasi'], true)) {
                $this->reject('code', 'Booking ini tidak bisa dibatalkan.');
            }

            $deadline = $session->startsAt()->subHours(
                $this->settings->integer('cancellation_cutoff_hours', 6),
            );

            if (now()->greaterThanOrEqualTo($deadline)) {
                $this->reject('code', 'Batas waktu pembatalan sudah lewat. Silakan hubungi admin.');
            }

            $booking->update([
                'status' => 'dibatalkan',
                'cancelled_at' => now(),
            ]);
            $this->snackOrders->cancelItems($booking);

            $session->syncPrivateStatus();

            return $booking->fresh(['session', 'film']);
        }, attempts: 3);
    }

    private function newBookingCode(CarbonImmutable $startsAt): string
    {
        do {
            $code = 'HC-'.$startsAt->format('ymd').'-'.Str::upper(Str::random(6));
        } while (Booking::query()->where('code', $code)->exists());

        return $code;
    }

    private function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
