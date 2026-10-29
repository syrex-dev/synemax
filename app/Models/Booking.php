<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'session_id',
    'film_id',
    'customer_profile_id',
    'customer_name',
    'whatsapp',
    'pax',
    'seats_reserved',
    'booking_type',
    'package',
    'people_count',
    'reselect_needed',
    'refund_needed',
    'total_price',
    'ticket_total',
    'snack_total',
    'payment_method',
    'payment_proof_path',
    'status',
    'notes',
    'payment_expires_at',
    'payment_proof_uploaded_at',
    'proof_rejection_note',
    'confirmed_at',
    'cancelled_at',
    'attended_at',
])]
class Booking extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Booking $booking): void {
            if ($booking->package === null) {
                $booking->package = $booking->booking_type === 'private' ? 'private' : 'reguler';
            }
            if ($booking->people_count === null) {
                $booking->people_count = (int) ($booking->pax ?: 1);
            }
            if ($booking->seats_reserved === null) {
                $booking->seats_reserved = match ($booking->package) {
                    'private' => 6,
                    'couple' => 2,
                    default => (int) ($booking->pax ?: 1),
                };
            }
        });
    }

    protected function casts(): array
    {
        return [
            'pax' => 'integer',
            'seats_reserved' => 'integer',
            'people_count' => 'integer',
            'reselect_needed' => 'boolean',
            'refund_needed' => 'boolean',
            'total_price' => 'integer',
            'ticket_total' => 'integer',
            'snack_total' => 'integer',
            'payment_expires_at' => 'datetime',
            'payment_proof_uploaded_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'attended_at' => 'datetime',
        ];
    }

    public function isCouple(): bool
    {
        return $this->package === 'couple';
    }

    public function isPrivate(): bool
    {
        return $this->package === 'private' || $this->booking_type === 'private';
    }

    public function isReguler(): bool
    {
        return ! $this->isCouple() && ! $this->isPrivate();
    }

    public function needsReselect(): bool
    {
        return (bool) $this->reselect_needed;
    }

    public function isAwaitingPayment(): bool
    {
        return $this->status === 'menunggu_pembayaran';
    }

    public function isExpired(): bool
    {
        return $this->status === 'kedaluwarsa'
            || ($this->isAwaitingPayment() && $this->payment_expires_at !== null && $this->payment_expires_at->isPast());
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'terkonfirmasi';
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['terkonfirmasi', 'menunggu_konfirmasi'], true)
            || ! empty($this->payment_proof_path);
    }

    public function isOnlinePayment(): bool
    {
        return in_array($this->payment_method, ['qris', 'transfer'], true);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function film(): BelongsTo
    {
        return $this->belongsTo(Film::class)->withTrashed();
    }

    public function customerProfile(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function snackChangeLogs(): HasMany
    {
        return $this->hasMany(SnackOrderEdit::class)
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
