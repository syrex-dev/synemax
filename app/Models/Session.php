<?php

namespace App\Models;

use App\Services\CinemaSettings;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable([
    'session_date',
    'start_time',
    'end_time',
    'film_id',
    'capacity',
    'is_private',
    'status',
    'confirmation_method',
    'confirmed_at',
    'resolution_notes',
])]
class Session extends Model
{
    public const MAX_CAPACITY = 6;

    protected $table = 'screening_sessions';

    protected function casts(): array
    {
        return [
            'film_id' => 'integer',
            'capacity' => 'integer',
            'is_private' => 'boolean',
            'confirmed_at' => 'datetime',
        ];
    }

    public function film(): BelongsTo
    {
        return $this->belongsTo(Film::class)->withTrashed();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function occupiedBookings(): HasMany
    {
        return $this->bookings()
            ->whereNotIn('status', ['dibatalkan', 'kedaluwarsa'])
            ->where(function ($query) {
                $query->where('status', '!=', 'menunggu_pembayaran')
                    ->orWhere(function ($q) {
                        $q->where('status', 'menunggu_pembayaran')
                            ->where(function ($sub) {
                                $sub->whereNull('payment_expires_at')
                                    ->orWhere('payment_expires_at', '>', now());
                            });
                    });
            });
    }

    public function hasActivePrivateBooking(): bool
    {
        return $this->occupiedBookings()
            ->where('booking_type', 'private')
            ->exists();
    }

    public function hasActivePerPersonBookings(): bool
    {
        return $this->occupiedBookings()
            ->where('booking_type', 'per_orang')
            ->exists();
    }

    public function isPrivateActive(): bool
    {
        return (bool) $this->is_private || $this->hasActivePrivateBooking();
    }

    public function syncPrivateStatus(): bool
    {
        $hasPrivate = $this->hasActivePrivateBooking();
        $hasAnyOccupied = $this->occupiedBookings()->exists();

        $dirty = false;
        if ((bool) $this->is_private !== $hasPrivate) {
            $this->is_private = $hasPrivate;
            $dirty = true;
        }

        // If no active bookings remain, release film lock so session is free for new choice
        if (! $hasAnyOccupied && $this->film_id !== null) {
            $this->film_id = null;
            $this->confirmation_method = null;
            $this->confirmed_at = null;
            $this->resolution_notes = null;
            $dirty = true;
        }

        if ($dirty) {
            $this->save();
        }

        return $hasPrivate;
    }

    public function blockedDates(): HasMany
    {
        return $this->hasMany(BlockedDate::class);
    }

    public function startsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse(
            $this->session_date.' '.$this->start_time,
            config('app.timezone'),
        );
    }

    public function remainingSeats(): int
    {
        if ($this->isPrivateActive()) {
            return 0;
        }

        $occupiedSeats = array_key_exists('occupied_seats', $this->attributes)
            ? (int) $this->occupied_seats
            : (int) $this->occupiedBookings()->sum('seats_reserved');

        return max(0, $this->seatCapacity() - $occupiedSeats);
    }

    public function seatCapacity(): int
    {
        return min(self::MAX_CAPACITY, $this->capacity);
    }

    public function quorum(): int
    {
        return app(CinemaSettings::class)->minimumPublicViewers();
    }

    public function isConfirmed(): bool
    {
        return $this->film_id !== null && ($this->confirmed_at !== null || in_array($this->confirmation_method, ['quorum', 'couple', 'private', 'admin'], true));
    }

    public function confirmAndLock(int $filmId, string $method, ?string $notes = null): void
    {
        $this->film_id = $filmId;
        $this->confirmation_method = $method;
        $this->confirmed_at = now();
        if ($notes !== null) {
            $this->resolution_notes = $notes;
        }
        if ($method === 'private') {
            $this->is_private = true;
        }
        $this->save();
    }

    /**
     * Hitung akumulasi kursi per film dari pemesan aktif di sesi ini.
     *
     * @return Collection<int, array{film_id: int, film: ?Film, title: string, seats: int, count: int, is_quorum_reached: bool, needed_seats: int}>
     */
    public function candidateFilmTallies(): Collection
    {
        $bookings = $this->occupiedBookings()->with('film')->get();
        $grouped = $bookings->groupBy('film_id');
        $quorum = $this->quorum();

        return $grouped->map(function ($items, $filmId) use ($quorum) {
            $first = $items->first();
            $film = $first?->film;
            $seats = (int) $items->sum('seats_reserved');

            return [
                'film_id' => (int) $filmId,
                'film' => $film,
                'title' => $film?->title ?? 'Film Belum Ditentukan',
                'seats' => $seats,
                'count' => $items->count(),
                'bookings_count' => $items->count(),
                'is_quorum_reached' => $seats >= $quorum,
                'needed_seats' => max(0, $quorum - $seats),
            ];
        })->sortByDesc('seats')->values();
    }

    /**
     * Film calon dengan peminat terbanyak saat ini.
     */
    public function leadingFilmTally(): ?array
    {
        return $this->candidateFilmTallies()->first();
    }

    public function leadingFilm(): ?Film
    {
        if ($this->film_id !== null) {
            return $this->relationLoaded('film') ? $this->film : Film::query()->find($this->film_id);
        }

        return $this->leadingFilmTally()['film'] ?? null;
    }

    public function activeFilm(): ?Film
    {
        if ($this->relationLoaded('film') && $this->film !== null) {
            return $this->film;
        }

        if ($this->film_id !== null) {
            return $this->film;
        }

        return $this->leadingFilm();
    }

    /**
     * Pagar Anti-Buntu:
     * Pada sesi yang belum terkonfirmasi, tolak booking reguler jika setelah booking itu:
     * (total kursi aktif) - (kursi film terpopuler) > (kapasitas - kuorum).
     *
     * @return array{allowed: bool, reason: ?string, is_deadlock: bool}
     */
    public function canAcceptRegularBooking(int $filmId, int $pax): array
    {
        $capacity = $this->seatCapacity();
        $quorum = $this->quorum();
        $deadlockLimit = max(0, $capacity - $quorum); // 6 - 4 = 2

        if ($this->isConfirmed()) {
            if ($this->film_id !== $filmId) {
                return [
                    'allowed' => false,
                    'reason' => 'Sesi ini sudah terkunci untuk film yang berbeda.',
                    'is_deadlock' => false,
                ];
            }

            if ($pax > $this->remainingSeats()) {
                return [
                    'allowed' => false,
                    'reason' => 'Jumlah kursi tersisa tidak cukup. Silakan pilih jumlah orang lebih sedikit.',
                    'is_deadlock' => false,
                ];
            }

            return ['allowed' => true, 'reason' => null, 'is_deadlock' => false];
        }

        // Kumpulkan kursi per film saat ini
        $tallies = $this->candidateFilmTallies();
        $seatsByFilm = [];
        foreach ($tallies as $t) {
            $seatsByFilm[$t['film_id']] = $t['seats'];
        }

        // Simulasikan penambahan $pax ke $filmId
        $seatsByFilm[$filmId] = ($seatsByFilm[$filmId] ?? 0) + $pax;
        $totalSeats = array_sum($seatsByFilm);
        $maxSeats = ! empty($seatsByFilm) ? max($seatsByFilm) : 0;

        if ($totalSeats > $capacity) {
            return [
                'allowed' => false,
                'reason' => 'Jumlah kursi tersisa tidak cukup. Silakan pilih jumlah orang lebih sedikit.',
                'is_deadlock' => false,
            ];
        }

        // Pagar anti-buntu
        if (($totalSeats - $maxSeats) > $deadlockLimit) {
            $summaryParts = $tallies->map(fn ($t) => "{$t['title']} {$t['seats']}")->implode(', ');
            $candidateSummary = $summaryParts !== '' ? " ($summaryParts)" : '';

            return [
                'allowed' => false,
                'reason' => "Pilihan ini membuat sesi sulit tayang. Pilih film yang sudah punya peminat{$candidateSummary}, pilih slot lain, atau pesan Private/Couple.",
                'is_deadlock' => true,
            ];
        }

        return ['allowed' => true, 'reason' => null, 'is_deadlock' => false];
    }

    /**
     * Syarat Paket Couple:
     * Hanya boleh bila sesi belum terkonfirmasi dan tidak ada kursi aktif film lain.
     *
     * @return array{allowed: bool, reason: ?string}
     */
    public function canAcceptCoupleBooking(?int $filmId = null): array
    {
        if ($this->isPrivateActive()) {
            return [
                'allowed' => false,
                'reason' => 'Sesi ini sudah disewa private.',
            ];
        }

        if ($this->isConfirmed()) {
            return [
                'allowed' => false,
                'reason' => 'Sesi ini sudah memiliki film yang terkonfirmasi.',
            ];
        }

        if ($this->remainingSeats() < 2) {
            return [
                'allowed' => false,
                'reason' => 'Kursi tersisa kurang dari 2 untuk paket Couple.',
            ];
        }

        $tallies = $this->candidateFilmTallies();

        if ($filmId !== null) {
            $otherFilms = $tallies->filter(fn ($t) => $t['film_id'] !== $filmId);

            if ($otherFilms->isNotEmpty()) {
                $leadingOther = $otherFilms->first();

                return [
                    'allowed' => false,
                    'reason' => "Sudah ada pemesan film \"{$leadingOther['title']}\" di sesi ini. Anda dapat bergabung ke film tersebut atau memilih slot sesi lain.",
                ];
            }
        } else {
            if ($tallies->count() > 1) {
                return [
                    'allowed' => false,
                    'reason' => 'Sudah ada pemesan untuk film berbeda di sesi ini. Paket Couple hanya bisa dipilih jika sesi kosong atau semua kursi aktif memilih film yang sama.',
                ];
            }
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Syarat Paket Private:
     * Hanya boleh bila sesi tidak punya booking aktif sama sekali.
     *
     * @return array{allowed: bool, reason: ?string}
     */
    public function canAcceptPrivateBooking(): array
    {
        if ($this->isPrivateActive()) {
            return [
                'allowed' => false,
                'reason' => 'Sesi ini sudah disewa private.',
            ];
        }

        if ($this->isConfirmed()) {
            return [
                'allowed' => false,
                'reason' => 'Sesi ini sudah terkonfirmasi.',
            ];
        }

        $occupiedSeats = (int) $this->occupiedBookings()->sum('seats_reserved');
        if ($occupiedSeats > 0 || $this->hasActivePerPersonBookings()) {
            return [
                'allowed' => false,
                'reason' => 'Sudah ada pemesan per orang di sesi ini, jadi Sewa Private tidak tersedia.',
            ];
        }

        if ($this->seatCapacity() < self::MAX_CAPACITY) {
            return [
                'allowed' => false,
                'reason' => 'Kapasitas sesi tidak mencukupi untuk sewa privat.',
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    public function isOpen(): bool
    {
        return $this->status === 'tersedia';
    }

    public function endsAt(): CarbonImmutable
    {
        $endTime = $this->end_time;
        if (blank($endTime)) {
            return $this->startsAt()->addHours(2);
        }

        return CarbonImmutable::parse(
            $this->session_date.' '.$endTime,
            config('app.timezone'),
        );
    }

    public function isCompleted(): bool
    {
        return now()->greaterThanOrEqualTo($this->endsAt());
    }

    public function isOngoing(): bool
    {
        return now()->greaterThanOrEqualTo($this->startsAt()) && now()->lessThan($this->endsAt());
    }

    public function isPast(): bool
    {
        return now()->greaterThanOrEqualTo($this->startsAt());
    }

    public function agreementWindowMinutes(): int
    {
        return app(CinemaSettings::class)->agreementWindowMinutes();
    }

    public function confirmationDeadlineMinutes(): int
    {
        return app(CinemaSettings::class)->confirmationDeadlineMinutes();
    }

    public function agreementWindowStartsAt(): CarbonImmutable
    {
        return $this->startsAt()->subMinutes($this->agreementWindowMinutes());
    }

    public function confirmationDeadline(): CarbonImmutable
    {
        return $this->startsAt()->subMinutes($this->confirmationDeadlineMinutes());
    }

    public function isInAgreementWindow(): bool
    {
        return ! $this->isConfirmed()
            && $this->occupiedBookings()->exists()
            && now()->greaterThanOrEqualTo($this->agreementWindowStartsAt())
            && now()->lessThan($this->confirmationDeadline());
    }

    public function isPastConfirmationDeadline(): bool
    {
        return now()->greaterThanOrEqualTo($this->confirmationDeadline());
    }

    public function isCancelledByQuorum(): bool
    {
        return ! $this->isConfirmed()
            && (
                str_contains((string) $this->resolution_notes, 'kuorum')
                || ($this->occupiedBookings()->exists() && $this->isPastConfirmationDeadline() && ! $this->isCompleted())
            );
    }

    /**
     * Hitung status waktu dan kuorum sesi secara akurat.
     *
     * @return array{key: string, label: string, can_book: bool, is_past: bool, badge_class: string}
     */
    public function statusWaktu(int $bookingCutoffMinutes = 30): array
    {
        // 1. Selesai
        if ($this->isCompleted()) {
            return [
                'key' => 'selesai',
                'label' => 'Selesai',
                'can_book' => false,
                'is_past' => true,
                'badge_class' => 'bg-surface-2 dark:bg-white/5 border-border dark:border-white/10 text-text-muted',
            ];
        }

        // 2. Berlangsung
        if ($this->isOngoing()) {
            return [
                'key' => 'berlangsung',
                'label' => 'Berlangsung',
                'can_book' => false,
                'is_past' => true,
                'badge_class' => 'bg-amber-500/15 border-amber-500/40 text-amber-600 dark:text-amber-400 font-bold',
            ];
        }

        // 3. Ditutup manual oleh admin
        if ($this->status !== 'tersedia') {
            return [
                'key' => 'ditutup',
                'label' => 'Ditutup admin',
                'can_book' => false,
                'is_past' => false,
                'badge_class' => 'bg-surface-2 dark:bg-white/5 border-border dark:border-white/10 text-text-muted font-semibold',
            ];
        }

        // 4. Batas konfirmasi lewat tanpa kuorum (Dibatalkan)
        if ($this->isCancelledByQuorum()) {
            return [
                'key' => 'dibatalkan_kuorum',
                'label' => 'Dibatalkan (kuorum tidak terpenuhi)',
                'can_book' => false,
                'is_past' => false,
                'badge_class' => 'bg-danger/10 border-danger/30 text-danger font-bold',
            ];
        }

        // 5. Pemesanan ditutup karena batas cutoff reguler
        $startsAt = $this->startsAt();
        if ($startsAt->subMinutes($bookingCutoffMinutes)->lessThanOrEqualTo(now())) {
            return [
                'key' => 'pemesanan_ditutup',
                'label' => 'Pemesanan ditutup',
                'can_book' => false,
                'is_past' => false,
                'badge_class' => 'bg-surface-2 dark:bg-white/5 border-border dark:border-white/10 text-text-muted font-semibold',
            ];
        }

        // 6. Sewa privat aktif
        if ($this->isPrivateActive()) {
            return [
                'key' => 'disewa_private',
                'label' => 'Sudah disewa private',
                'can_book' => false,
                'is_past' => false,
                'badge_class' => 'bg-purple-500/15 border-purple-500/30 text-purple-600 dark:text-purple-300 font-semibold',
            ];
        }

        // 7. Penuh
        if ($this->remainingSeats() === 0) {
            return [
                'key' => 'penuh',
                'label' => 'Penuh',
                'can_book' => false,
                'is_past' => false,
                'badge_class' => 'bg-danger/10 border-danger/30 text-danger font-bold',
            ];
        }

        // 8. Sesi Pasti Tayang (terkunci kuorum / couple / private / admin)
        if ($this->isConfirmed()) {
            $lockedTitle = $this->film?->title ?? 'Film Terkunci';

            return [
                'key' => 'pasti_tayang',
                'label' => 'Pasti tayang: '.$lockedTitle,
                'can_book' => true,
                'is_past' => false,
                'badge_class' => 'bg-emerald-500/15 border-emerald-500/40 text-emerald-600 dark:text-emerald-400 font-bold',
            ];
        }

        // 9. Jendela Kesepakatan (H-3 jam)
        if ($this->isInAgreementWindow()) {
            return [
                'key' => 'butuh_kesepakatan',
                'label' => 'Butuh kesepakatan',
                'can_book' => true,
                'is_past' => false,
                'badge_class' => 'bg-amber-500/20 border-amber-500/50 text-amber-700 dark:text-amber-400 font-black',
            ];
        }

        // 10. Menunggu Kuorum (ada peminat tapi belum kuorum)
        $leading = $this->leadingFilmTally();
        if ($leading) {
            $quorum = $this->quorum();

            return [
                'key' => 'menunggu_kuorum',
                'label' => "Film {$leading['title']} {$leading['seats']}/{$quorum} · butuh {$leading['needed_seats']} lagi",
                'can_book' => true,
                'is_past' => false,
                'badge_class' => 'bg-amber-500/10 border-amber-500/30 text-amber-600 dark:text-amber-400 font-bold',
            ];
        }

        // 11. Belum ada peminat (Kosong)
        return [
            'key' => 'tersedia',
            'label' => 'Tersedia: belum ada film, kamu yang menentukan',
            'can_book' => true,
            'is_past' => false,
            'badge_class' => 'bg-surface-2 border-border dark:border-white/10 text-text font-semibold',
        ];
    }

    public function computedStatus(): string
    {
        if ($this->status === 'ditutup') {
            return 'ditutup';
        }

        if ($this->isCompleted()) {
            return 'selesai';
        }

        if ($this->isOngoing()) {
            return 'berlangsung';
        }

        if ($this->isCancelledByQuorum()) {
            return 'dibatalkan';
        }

        if ($this->remainingSeats() === 0 || $this->isPrivateActive()) {
            return 'penuh';
        }

        if ($this->isConfirmed()) {
            return 'pasti_tayang';
        }

        if ($this->isInAgreementWindow()) {
            return 'butuh_kesepakatan';
        }

        if ($this->occupiedBookings()->exists()) {
            return 'menunggu_kuorum';
        }

        return 'tersedia';
    }

    public function availabilityLabel(int $bookingCutoffMinutes = 30): string
    {
        return $this->statusWaktu($bookingCutoffMinutes)['label'];
    }
}
