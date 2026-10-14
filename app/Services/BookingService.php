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
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

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
        return DB::transaction(function () use ($requestedSession, $data, $paymentProof): Booking {
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

            if ($session->is_private) {
                $this->reject('booking_type', 'Sesi ini sudah disewa secara private.');
            }

            if ($isPrivate) {
                if ($occupiedSeats > 0 || $seatCapacity < Session::MAX_CAPACITY) {
                    $this->reject('booking_type', 'Sewa private hanya bisa untuk sesi yang masih kosong.');
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

            if (! in_array($paymentMethod, $this->settings->enabledPaymentMethods(), true)) {
                $this->reject('payment_method', 'Metode pembayaran ini sedang tidak tersedia.');
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
                'status' => 'menunggu_konfirmasi',
            ]);

            $booking = $this->snackOrders->replaceItems($booking, $data['snacks'] ?? []);
            $proofPath = $paymentProof?->store('payment-proofs', 'local');

            if ($proofPath === false) {
                throw new RuntimeException('Bukti pembayaran gagal disimpan. Silakan coba lagi.');
            }

            if ($proofPath !== null) {
                $booking->update(['payment_proof_path' => $proofPath]);
            }

            return $booking;
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

            if (! in_array($booking->status, ['menunggu_konfirmasi', 'terkonfirmasi'], true)) {
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

            if (
                $session->is_private
                && (int) $session->occupiedBookings()->sum('seats_reserved') === 0
            ) {
                $session->update(['is_private' => false]);
            }

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
