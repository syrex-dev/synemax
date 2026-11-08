<?php

namespace App\Models;

use App\Services\LoyaltyRules;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'member_id',
    'session_id',
    'booking_id',
    'position',
    'visit_number',
    'cycle_number',
    'reward_type',
    'reward_amount',
    'reward_applied',
    'active',
    'method',
    'recorded_by',
    'void_reason',
    'voided_by',
    'voided_at',
])]
class MemberVisit extends Model
{
    protected $casts = [
        'position' => 'integer',
        'visit_number' => 'integer',
        'cycle_number' => 'integer',
        'reward_amount' => 'integer',
        'active' => 'integer',
        'voided_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function reward(): HasOne
    {
        return $this->hasOne(MemberReward::class, 'member_visit_id');
    }

    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function voidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isValid(): bool
    {
        return $this->active === 1;
    }

    public function isVoided(): bool
    {
        return $this->active === null || $this->voided_at !== null;
    }

    public function rewardLabel(): ?string
    {
        $type = $this->reward_type !== 'none' ? $this->reward_type : $this->reward_applied;

        return $type ? LoyaltyRules::rewardLabel($type) : null;
    }
}
