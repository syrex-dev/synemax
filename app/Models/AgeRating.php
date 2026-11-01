<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

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
     * Standard fallback options if table does not exist yet.
     *
     * @return array<string, string>
     */
    public static function defaultFallbackOptions(): array
    {
        return [
            'SU' => 'SU · Semua Umur',
            '13+' => '13+ · Remaja 13 Tahun ke Atas',
            '17+' => '17+ · Dewasa 17 Tahun ke Atas',
            '21+' => '21+ · Dewasa 21 Tahun ke Atas',
        ];
    }

    /**
     * Get list of active codes.
     *
     * @return list<string>
     */
    public static function activeCodes(): array
    {
        try {
            if (! Schema::hasTable('age_ratings')) {
                return ['SU', '13+', '17+', '21+'];
            }

            return static::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->pluck('code')
                ->all();
        } catch (\Throwable $e) {
            return ['SU', '13+', '17+', '21+'];
        }
    }

    /**
     * Get all registered codes in database.
     *
     * @return list<string>
     */
    public static function allCodes(): array
    {
        try {
            if (! Schema::hasTable('age_ratings')) {
                return ['SU', '13+', '17+', '21+'];
            }

            return static::query()
                ->orderBy('sort_order')
                ->pluck('code')
                ->all();
        } catch (\Throwable $e) {
            return ['SU', '13+', '17+', '21+'];
        }
    }

    /**
     * Get formatted options array for select components.
     *
     * @return array<string, string>
     */
    public static function optionsArray(?string $includeCode = null): array
    {
        try {
            if (! Schema::hasTable('age_ratings')) {
                $options = self::defaultFallbackOptions();
                if ($includeCode !== null && trim($includeCode) !== '' && ! isset($options[$includeCode])) {
                    $cleanInclude = trim($includeCode);
                    $options[$cleanInclude] = "{$cleanInclude} (Belum terdaftar)";
                }

                return $options;
            }

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

            return ! empty($options) ? $options : self::defaultFallbackOptions();
        } catch (\Throwable $e) {
            $options = self::defaultFallbackOptions();
            if ($includeCode !== null && trim($includeCode) !== '' && ! isset($options[$includeCode])) {
                $cleanInclude = trim($includeCode);
                $options[$cleanInclude] = "{$cleanInclude} (Belum terdaftar)";
            }

            return $options;
        }
    }
}
