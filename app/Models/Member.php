<?php

namespace App\Models;

use App\Services\CinemaSettings;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

#[Fillable([
    'code',
    'member_code',
    'name',
    'nickname',
    'photo_path',
    'notes',
    'status',
    'is_active',
    'opening_visits',
    'link_version',
    'created_by',
])]
class Member extends Model
{
    use SoftDeletes;

    protected $casts = [
        'is_active' => 'boolean',
        'opening_visits' => 'integer',
        'link_version' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Member $member): void {
            if (empty($member->code) && ! empty($member->member_code)) {
                $member->code = $member->member_code;
            } elseif (! empty($member->code) && empty($member->member_code)) {
                $member->member_code = $member->code;
            }

            if ($member->isDirty('status')) {
                $member->is_active = ($member->status === 'aktif');
            } elseif ($member->isDirty('is_active')) {
                $member->status = $member->is_active ? 'aktif' : 'nonaktif';
            }
        });
    }

    public function visits(): HasMany
    {
        return $this->hasMany(MemberVisit::class);
    }

    public function validVisits(): HasMany
    {
        return $this->hasMany(MemberVisit::class)->where('active', 1);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(MemberReward::class)->orderByDesc('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(MemberEvent::class)->orderByDesc('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lastValidVisit(): ?MemberVisit
    {
        return $this->hasMany(MemberVisit::class)->where('active', 1)->latest('id')->first();
    }

    public function totalValidVisits(): int
    {
        return $this->validVisits()->count();
    }

    public function totalVisits(): int
    {
        $validCount = isset($this->valid_visits_count)
            ? (int) $this->valid_visits_count
            : $this->totalValidVisits();

        return (int) $this->opening_visits + $validCount;
    }

    public function currentCycle(int $cycleMax = 10): int
    {
        $total = $this->totalVisits();

        return intdiv($total, $cycleMax) + 1;
    }

    public function nextVisitPosition(int $cycleMax = 10): int
    {
        $total = $this->totalVisits();

        return ($total % $cycleMax) + 1;
    }

    public function currentCycleVisitCount(int $cycleMax = 10): int
    {
        $pos = $this->nextVisitPosition($cycleMax) - 1;

        return $pos <= 0 ? 0 : $pos;
    }

    public function progressDescription(?CinemaSettings $settings = null): string
    {
        $settings ??= app(CinemaSettings::class);
        $cycleMax = $settings->memberFreeVisit();
        $discountVisit = $settings->memberDiscountVisit();
        $discountAmount = number_format($settings->memberDiscountAmount(), 0, ',', '.');

        $nextPos = $this->nextVisitPosition($cycleMax);
        $currentInCycle = $nextPos - 1;

        if ($nextPos <= $discountVisit) {
            $rem = $discountVisit - $currentInCycle;

            return "Kunjungan {$currentInCycle} dari {$cycleMax}. {$rem} kunjungan lagi untuk potongan Rp {$discountAmount}";
        }

        $rem = $cycleMax - $currentInCycle;

        return "Kunjungan {$currentInCycle} dari {$cycleMax}. {$rem} kunjungan lagi untuk gratis tiket nonton";
    }

    public function activeRewards(): Collection
    {
        return $this->rewards()->where('status', 'earned')->orWhere('status', 'pending')->get();
    }

    public function photoUrl(): ?string
    {
        if (empty($this->photo_path)) {
            return null;
        }

        if (str_starts_with($this->photo_path, 'http://') || str_starts_with($this->photo_path, 'https://') || str_starts_with($this->photo_path, '/')) {
            return $this->photo_path;
        }

        return Storage::url($this->photo_path);
    }

    public function digitalCardSignedUrl(): string
    {
        return URL::signedRoute('public.member.card', [
            'code' => $this->code ?: $this->member_code,
            'v' => $this->link_version,
        ]);
    }

    public function isActive(): bool
    {
        return $this->status === 'aktif';
    }

    public function isBlocked(): bool
    {
        return $this->status === 'diblokir';
    }

    public function formattedCode(): string
    {
        return $this->code ?: (string) $this->member_code;
    }
}
