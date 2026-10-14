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
    'total_price',
    'ticket_total',
    'snack_total',
    'payment_method',
    'payment_proof_path',
    'status',
    'notes',
    'confirmed_at',
    'cancelled_at',
    'attended_at',
])]
class Booking extends Model
{
    protected function casts(): array
    {
        return [
            'pax' => 'integer',
            'seats_reserved' => 'integer',
            'total_price' => 'integer',
            'ticket_total' => 'integer',
            'snack_total' => 'integer',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'attended_at' => 'datetime',
        ];
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
