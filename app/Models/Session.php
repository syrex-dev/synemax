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
        return $this->bookings()->where('status', '!=', 'dibatalkan');
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
