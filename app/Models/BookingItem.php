<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'booking_id',
    'menu_item_id',
    'name_snapshot',
    'price_snapshot',
    'cost_snapshot',
    'qty',
    'note',
    'status',
    'stock_deducted_qty',
    'stock_restored_qty',
    'stock_deducted_at',
    'stock_restored_at',
])]
class BookingItem extends Model
{
    protected function casts(): array
    {
        return [
            'booking_id' => 'integer',
            'menu_item_id' => 'integer',
            'price_snapshot' => 'integer',
            'cost_snapshot' => 'integer',
            'qty' => 'integer',
            'stock_deducted_qty' => 'integer',
            'stock_restored_qty' => 'integer',
            'stock_deducted_at' => 'datetime',
            'stock_restored_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }
}
