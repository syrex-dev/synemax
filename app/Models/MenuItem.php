<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'category_id',
    'name',
    'price',
    'cost_price',
    'photo_path',
    'prep_minutes',
    'stock_mode',
    'stock_qty',
    'stock_alert_threshold',
    'is_available',
    'is_active',
    'sort_order',
])]
class MenuItem extends Model
{
    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'price' => 'integer',
            'cost_price' => 'integer',
            'prep_minutes' => 'integer',
            'stock_qty' => 'integer',
            'stock_alert_threshold' => 'integer',
            'is_available' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function canBeOrdered(): bool
    {
        return $this->is_active && $this->is_available;
    }
}
