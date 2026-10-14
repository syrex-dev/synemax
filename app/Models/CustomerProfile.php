<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['whatsapp', 'no_show_count', 'is_blocked', 'notes'])]
class CustomerProfile extends Model
{
    protected function casts(): array
    {
        return [
            'no_show_count' => 'integer',
            'is_blocked' => 'boolean',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
