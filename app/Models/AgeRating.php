<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgeRating extends Model
{
    protected $fillable = [
        'code',
        'label',
        'min_age',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'min_age' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship to films using this age rating code.
     */
    public function films(): HasMany
    {
        return $this->hasMany(Film::class, 'age_rating', 'code');
    }

    /**
     * Scope for active age ratings ordered by sort_order.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('min_age');
    }

    /**
     * Get list of active codes.
     *
     * @return list<string>
     */
    public static function activeCodes(): array
    {
        return static::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('code')
            ->all();
    }

    /**
     * Get all registered codes in database.
     *
     * @return list<string>
     */
    public static function allCodes(): array
    {
        return static::query()
            ->orderBy('sort_order')
            ->pluck('code')
            ->all();
    }

    /**
     * Get formatted options array for select components.
     *
     * @return array<string, string>
     */
    public static function optionsArray(?string $includeCode = null): array
    {
        $ratings = static::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('min_age')
            ->get();

        $options = [];
        $found = false;
        foreach ($ratings as $r) {
            $options[$r->code] = "{$r->code} · {$r->label}";
            if ($includeCode !== null && $r->code === $includeCode) {
                $found = true;
            }
        }

        if ($includeCode !== null && trim($includeCode) !== '' && ! $found) {
            $cleanInclude = trim($includeCode);
            $options[$cleanInclude] = "{$cleanInclude} (Belum terdaftar)";
        }

        return $options;
    }
}
