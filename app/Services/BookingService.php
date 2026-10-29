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

            $package = $data['package'] ?? ($data['booking_type'] === 'private' ? 'private' : 'reguler');
            if (! in_array($package, ['reguler', 'couple', 'private'], true)) {
                $package = 'reguler';
            }

            $isPrivate = $package === 'private';
            $isCouple = $package === 'couple';
            $isReguler = $package === 'reguler';

            if ($isReguler && empty($data['people_count']) && empty($data['pax'])) {
                $this->reject('pax', 'Pilih jumlah orang.');
            }

            $pax = match (true) {
                $isPrivate => Session::MAX_CAPACITY,
                $isCouple => 2,
                default => (int) ($data['people_count'] ?? $data['pax'] ?? 1),
            };

            $peopleCount = match (true) {
                $isPrivate => (int) ($data['people_count'] ?? $data['pax'] ?? Session::MAX_CAPACITY),
                $isCouple => 2,
                default => $pax,
            };

            $requestedFilmId = ! empty($data['film_id']) ? (int) $data['film_id'] : null;
            if ($session->isConfirmed() && $requestedFilmId !== null && $requestedFilmId !== (int) $session->film_id) {
                $this->reject('film_id', 'Sesi ini sudah memiliki film yang terkonfirmasi.');
            }

            $filmId = (int) ($requestedFilmId ?: $session->film_id);
            $film = Film::query()->where('is_active', true)->find($filmId);

            if ($film === null) {
                $this->reject('film_id', 'Pilih film yang masih tersedia.');
            }

            // 1. Validasi Paket & Pagar Anti-Buntu
            if ($isPrivate) {
                $check = $session->canAcceptPrivateBooking();
                if (! $check['allowed']) {
                    $this->reject('booking_type', $check['reason'] ?? 'Sewa Private tidak tersedia.');
                }
            } elseif ($isCouple) {
                if ($session->isPrivateActive()) {
                    $this->reject('booking_type', 'Sesi ini sudah disewa private.');
                }
                $check = $session->canAcceptCoupleBooking($film->id);
                if (! $check['allowed']) {
                    $this->reject('package', $check['reason'] ?? 'Paket Couple tidak tersedia.');
                }
            } else {
                if ($session->isPrivateActive()) {
                    $this->reject('booking_type', 'Sesi ini sudah disewa private.');
                }
                if ($pax < 1 || $pax > Session::MAX_CAPACITY) {
                    $this->reject('pax', 'Jumlah orang harus antara 1 sampai 6.');
                }
                if ($pax > $session->remainingSeats()) {
                    $this->reject('pax', 'Jumlah kursi tersisa tidak cukup. Silakan pilih jumlah orang lebih sedikit.');
                }
                $check = $session->canAcceptRegularBooking($film->id, $pax);
                if (! $check['allowed']) {
                    $this->reject('film_id', $check['reason'] ?? 'Pilihan ini membuat sesi sulit tayang.');
                }
            }

            $paymentMethod = $data['payment_method'];
            $availableMethods = $this->settings->availablePaymentMethodsForSession($session);

            if (! in_array($paymentMethod, $availableMethods, true)) {
                $this->reject('payment_method', 'Metode pembayaran ini sedang tidak tersedia untuk sesi ini.');
            }

            // 2. Hitung Harga Sesuai Paket & Hari
            $price = match (true) {
                $isPrivate => $this->settings->privatePriceForDayType($dayType),
                $isCouple => $this->settings->couplePrice($dayType),
                default => $this->settings->ticketPriceForDayType($dayType) * $pax,
            };

            $profile ??= CustomerProfile::query()->firstOrCreate(['whatsapp' => $whatsapp]);

            // 3. Evaluasi Konfirmasi & Kunci Sesi
            $isOnline = in_array($paymentMethod, ['qris', 'transfer'], true);
            $paymentLimitMinutes = $this->settings->paymentTimeLimitMinutes();
            $onlineBufferMinutes = $this->settings->onlinePaymentCutoffMinutes();
            $maxTimeByLimit = now()->addMinutes($paymentLimitMinutes);
            $maxTimeBySession = $startsAt->subMinutes($onlineBufferMinutes);
            $onlineExpiresAt = $maxTimeByLimit->lessThan($maxTimeBySession) ? $maxTimeByLimit : $maxTimeBySession;

            $seatsReserved = match (true) {
                $isPrivate => Session::MAX_CAPACITY,
                $isCouple => 2,
                default => $pax,
            };

            $status = $isOnline ? 'menunggu_pembayaran' : 'menunggu_konfirmasi';
            $paymentExpiresAt = null;

            if ($isPrivate) {
                $session->confirmAndLock($film->id, 'private');
                if ($isOnline) {
                    $paymentExpiresAt = $onlineExpiresAt;
                }
            } elseif ($isCouple) {
                $session->confirmAndLock($film->id, 'couple');
                if ($isOnline) {
                    $paymentExpiresAt = $onlineExpiresAt;
                }
            } else {
                // Booking Reguler
                $existingSeats = (int) $session->occupiedBookings()->where('film_id', $film->id)->sum('seats_reserved');
                $newTotalForFilm = $existingSeats + $seatsReserved;

                if (! $session->isConfirmed() && $newTotalForFilm >= $session->quorum()) {
                    // Capai kuorum! Sesi menjadi Pasti Tayang dan film terkunci
                    $session->confirmAndLock($film->id, 'quorum');

                    // Pemesan film lain di sesi ini perlu pilih ulang
                    Booking::query()
                        ->where('session_id', $session->id)
                        ->where('film_id', '!=', $film->id)
                        ->whereNotIn('status', ['dibatalkan', 'kedaluwarsa'])
                        ->update(['reselect_needed' => true]);

                    // Aktifkan timer pembayaran untuk booking online yang tadinya menunggu kuorum
                    Booking::query()
                        ->where('session_id', $session->id)
                        ->where('film_id', $film->id)
                        ->whereIn('payment_method', ['qris', 'transfer'])
                        ->where('status', 'menunggu_pembayaran')
                        ->whereNull('payment_expires_at')
                        ->update(['payment_expires_at' => $onlineExpiresAt]);
                }

                if ($session->isConfirmed()) {
                    if ($isOnline) {
                        $paymentExpiresAt = $onlineExpiresAt;
                    }
                } else {
                    // Sesi belum Pasti Tayang: instruksi bayar online ditahan sampai kuorum tercapai
                    if ($isOnline) {
                        $paymentExpiresAt = null;
                    }
                }
            }

            $booking = Booking::query()->create([
                'code' => $this->newBookingCode($startsAt),
                'session_id' => $session->getKey(),
                'film_id' => $film->getKey(),
                'customer_profile_id' => $profile->getKey(),
                'customer_name' => trim($data['customer_name']),
                'whatsapp' => $whatsapp,
                'pax' => $pax,
                'seats_reserved' => $seatsReserved,
                'booking_type' => $isPrivate ? 'private' : 'per_orang',
                'package' => $package,
                'people_count' => $peopleCount,
                'reselect_needed' => false,
                'refund_needed' => false,
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

    public function changeFilm(Booking $booking, int $newFilmId): Booking
    {
        return DB::transaction(function () use ($booking, $newFilmId): Booking {
            $session = Session::query()->lockForUpdate()->findOrFail($booking->session_id);
            $lockedBooking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if ($session->isConfirmed()) {
                if ($newFilmId !== (int) $session->film_id) {
                    $this->reject('film_id', 'Sesi ini sudah memiliki film yang terkonfirmasi, Anda hanya dapat memilih film yang tayang atau pindah sesi.');
                }

                $lockedBooking->update([
                    'film_id' => $newFilmId,
                    'reselect_needed' => false,
                ]);

                return $lockedBooking;
            }

            $newFilm = Film::query()->where('is_active', true)->findOrFail($newFilmId);

            // Simulasikan kursi tanpa booking ini untuk film lama, lalu tambahkan ke film baru
            $tallies = $session->candidateFilmTallies();
            $seatsByFilm = [];
            foreach ($tallies as $t) {
                $seatsByFilm[$t['film_id']] = $t['seats'];
            }
            $seatsByFilm[$lockedBooking->film_id] = max(0, ($seatsByFilm[$lockedBooking->film_id] ?? 0) - $lockedBooking->seats_reserved);
            if ($seatsByFilm[$lockedBooking->film_id] === 0) {
                unset($seatsByFilm[$lockedBooking->film_id]);
            }
            $seatsByFilm[$newFilmId] = ($seatsByFilm[$newFilmId] ?? 0) + $lockedBooking->seats_reserved;

            $capacity = $session->seatCapacity();
            $quorum = $session->quorum();
            $deadlockLimit = max(0, $capacity - $quorum);
            $totalSeats = array_sum($seatsByFilm);
            $maxSeats = ! empty($seatsByFilm) ? max($seatsByFilm) : 0;

            if (($totalSeats - $maxSeats) > $deadlockLimit) {
                $this->reject('film_id', 'Pilihan film ini membuat sesi sulit mencapai kuorum. Pilih film yang sudah memiliki peminat atau slot sesi lain.');
            }

            $lockedBooking->update([
                'film_id' => $newFilmId,
                'reselect_needed' => false,
            ]);

            // Cek apakah film baru ini kini mencapai kuorum
            if ($seatsByFilm[$newFilmId] >= $quorum) {
                $session->confirmAndLock($newFilmId, 'quorum');

                Booking::query()
                    ->where('session_id', $session->id)
                    ->where('film_id', '!=', $newFilmId)
                    ->whereNotIn('status', ['dibatalkan', 'kedaluwarsa'])
                    ->update(['reselect_needed' => true]);

                $startsAt = $session->startsAt();
                $paymentLimitMinutes = $this->settings->paymentTimeLimitMinutes();
                $onlineBufferMinutes = $this->settings->onlinePaymentCutoffMinutes();
                $maxTimeByLimit = now()->addMinutes($paymentLimitMinutes);
                $maxTimeBySession = $startsAt->subMinutes($onlineBufferMinutes);
                $onlineExpiresAt = $maxTimeByLimit->lessThan($maxTimeBySession) ? $maxTimeByLimit : $maxTimeBySession;

                Booking::query()
                    ->where('session_id', $session->id)
                    ->where('film_id', $newFilmId)
                    ->whereIn('payment_method', ['qris', 'transfer'])
                    ->where('status', 'menunggu_pembayaran')
                    ->whereNull('payment_expires_at')
                    ->update(['payment_expires_at' => $onlineExpiresAt]);
            }

            return $lockedBooking->fresh(['session', 'film']);
        }, attempts: 3);
    }

    public function reschedule(Booking $booking, Session $targetSession, ?int $targetFilmId = null): Booking
    {
        return DB::transaction(function () use ($booking, $targetSession, $targetFilmId): Booking {
            $oldSession = Session::query()->lockForUpdate()->findOrFail($booking->session_id);
            $newSession = Session::query()->lockForUpdate()->findOrFail($targetSession->id);
            $lockedBooking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            $startsAt = $newSession->startsAt();
            $cutoffMinutes = $this->settings->integer('booking_cutoff_minutes', 30);
            if ($newSession->status !== 'tersedia' || now()->greaterThanOrEqualTo($startsAt->subMinutes($cutoffMinutes))) {
                $this->reject('session', 'Sesi tujuan sudah tidak menerima pemesanan.');
            }

            $filmId = $targetFilmId ?: $lockedBooking->film_id;
            $film = Film::query()->where('is_active', true)->findOrFail($filmId);

            // Validasi di sesi tujuan
            if ($lockedBooking->isPrivate()) {
                $check = $newSession->canAcceptPrivateBooking();
                if (! $check['allowed']) {
                    $this->reject('session', $check['reason'] ?? 'Sewa Private tidak tersedia di sesi tujuan.');
                }
            } elseif ($lockedBooking->isCouple()) {
                $check = $newSession->canAcceptCoupleBooking($film->id);
                if (! $check['allowed']) {
                    $this->reject('session', $check['reason'] ?? 'Paket Couple tidak tersedia di sesi tujuan.');
                }
            } else {
                $check = $newSession->canAcceptRegularBooking($film->id, $lockedBooking->seats_reserved);
                if (! $check['allowed']) {
                    $this->reject('session', $check['reason'] ?? 'Kursi tidak cukup atau pilihan film membuat sesi tujuan sulit tayang.');
                }
            }

            // Pindahkan booking
            $lockedBooking->update([
                'session_id' => $newSession->id,
                'film_id' => $film->id,
                'reselect_needed' => false,
            ]);

            // Sinkronisasi status sesi lama
            $oldSession->syncPrivateStatus();

            // Evaluasi konfirmasi di sesi tujuan
            if ($lockedBooking->isPrivate()) {
                $newSession->confirmAndLock($film->id, 'private');
            } elseif ($lockedBooking->isCouple()) {
                $newSession->confirmAndLock($film->id, 'couple');
            } else {
                $seatsForFilm = (int) $newSession->occupiedBookings()->where('film_id', $film->id)->sum('seats_reserved');
                if (! $newSession->isConfirmed() && $seatsForFilm >= $newSession->quorum()) {
                    $newSession->confirmAndLock($film->id, 'quorum');
                }
            }

            return $lockedBooking->fresh(['session', 'film']);
        }, attempts: 3);
    }

    public function upgrade(Booking $booking, string $targetPackage): Booking
    {
        return DB::transaction(function () use ($booking, $targetPackage): Booking {
            $session = Session::query()->lockForUpdate()->findOrFail($booking->session_id);
            $lockedBooking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            $startsAt = $session->startsAt();
            $dayType = $this->scheduleResolver->dayTypeFor($startsAt);

            if (! in_array($targetPackage, ['couple', 'private'], true)) {
                $this->reject('package', 'Paket upgrade yang dipilih tidak valid.');
            }

            if ($targetPackage === 'couple') {
                if ($lockedBooking->seats_reserved > 2) {
                    $this->reject('package', 'Upgrade ke Paket Couple hanya untuk pemesanan maksimal 2 kursi.');
                }
                // Couple checks
                $check = $session->canAcceptCoupleBooking($lockedBooking->film_id);
                if (! $check['allowed']) {
                    $this->reject('package', $check['reason'] ?? 'Paket Couple tidak tersedia pada sesi ini.');
                }

                $newPrice = $this->settings->couplePrice($dayType);
                $diff = max(0, $newPrice - $lockedBooking->ticket_total);

                $lockedBooking->update([
                    'package' => 'couple',
                    'booking_type' => 'couple',
                    'pax' => 2,
                    'seats_reserved' => 2,
                    'people_count' => 2,
                    'ticket_total' => $newPrice,
                    'total_price' => $lockedBooking->total_price + $diff,
                    'reselect_needed' => false,
                ]);

                $session->confirmAndLock($lockedBooking->film_id, 'couple');
            } else {
                // Private upgrade
                $otherBookings = $session->occupiedBookings()->where('id', '!=', $lockedBooking->id)->count();
                if ($otherBookings > 0) {
                    $this->reject('package', 'Sudah ada pemesan lain di sesi ini, sehingga Sewa Private tidak dapat diambil.');
                }

                $newPrice = $this->settings->privatePriceForDayType($dayType);
                $diff = max(0, $newPrice - $lockedBooking->ticket_total);

                $lockedBooking->update([
                    'package' => 'private',
                    'booking_type' => 'private',
                    'pax' => Session::MAX_CAPACITY,
                    'seats_reserved' => Session::MAX_CAPACITY,
                    'people_count' => Session::MAX_CAPACITY,
                    'ticket_total' => $newPrice,
                    'total_price' => $lockedBooking->total_price + $diff,
                    'reselect_needed' => false,
                ]);

                $session->confirmAndLock($lockedBooking->film_id, 'private');
            }

            // Jika pembayaran online dan sesi kini pasti tayang, aktifkan timer pembayaran
            if ($lockedBooking->isOnlinePayment() && $lockedBooking->status === 'menunggu_pembayaran' && $lockedBooking->payment_expires_at === null) {
                $paymentLimitMinutes = $this->settings->paymentTimeLimitMinutes();
                $onlineBufferMinutes = $this->settings->onlinePaymentCutoffMinutes();
                $maxTimeByLimit = now()->addMinutes($paymentLimitMinutes);
                $maxTimeBySession = $startsAt->subMinutes($onlineBufferMinutes);
                $onlineExpiresAt = $maxTimeByLimit->lessThan($maxTimeBySession) ? $maxTimeByLimit : $maxTimeBySession;
                $lockedBooking->update(['payment_expires_at' => $onlineExpiresAt]);
            }

            return $lockedBooking->fresh(['session', 'film']);
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
