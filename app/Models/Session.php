<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'session_date',
    'start_time',
    'end_time',
    'film_id',
    'capacity',
    'is_private',
    'status',
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
        if ((bool) $this->is_private !== $hasPrivate) {
            $this->is_private = $hasPrivate;
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

    public function availabilityLabel(int $bookingCutoffMinutes = 30): string
    {
        if ($this->status !== 'tersedia') {
            return 'Tutup';
        }

        $startsAt = $this->startsAt();

        if ($startsAt->lessThanOrEqualTo(now())) {
            return 'Sudah lewat';
        }

        if ($startsAt->subMinutes($bookingCutoffMinutes)->lessThanOrEqualTo(now())) {
            return 'Tutup';
        }

        if ($this->remainingSeats() === 0) {
            return 'Penuh';
        }

        return $this->remainingSeats() < $this->seatCapacity()
            ? 'Sisa '.$this->remainingSeats().' kursi'
            : 'Tersedia';
    }
}
