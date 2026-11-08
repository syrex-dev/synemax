<?php

namespace App\Models;

use App\Services\LoyaltyRules;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'member_visit_id',
    'member_id',
    'type',
    'reward_type',
    'amount',
    'settlement',
    'booking_id',
    'cycle_number',
    'status',
    'given_by',
    'given_at',
    'note',
    'granted_at',
    'redeemed_at',
    'redeemed_session_id',
])]
class MemberReward extends Model
{
    protected $casts = [
        'amount' => 'integer',
        'cycle_number' => 'integer',
        'given_at' => 'datetime',
        'granted_at' => 'datetime',
        'redeemed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (MemberReward $reward): void {
            if (empty($reward->type) && ! empty($reward->reward_type)) {
                $reward->type = $reward->reward_type;
            } elseif (! empty($reward->type) && empty($reward->reward_type)) {
                $reward->reward_type = $reward->type;
            }
        });
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(MemberVisit::class, 'member_visit_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function givenByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'given_by');
    }

    public function label(): string
    {
        return LoyaltyRules::rewardLabel($this->type ?: $this->reward_type);
    }

    public function settlementLabel(): string
    {
        return match ($this->settlement) {
            'deducted_bill' => 'Dikurangi dari tagihan',
            'refund_cash' => 'Pengembalian tunai',
            'counter_discount' => 'Potongan langsung di kasir',
            default => $this->settlement ?: '-',
        };
    }
}
