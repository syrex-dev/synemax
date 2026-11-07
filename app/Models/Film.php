<?php

namespace App\Models;

use App\Services\CinemaSettings;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'title',
    'slug',
    'duration_minutes',
    'release_year',
    'genre',
    'age_rating',
    'synopsis',
    'trailer_url',
    'poster_path',
    'banner_path',
    'license_notes',
    'is_active',
    'release_date',
    'is_coming_soon',
    'is_sample',
    'import_batch_id',
    'director',
    'casts',
    'language',
    'custom_badge',
])]
class Film extends Model
{
    use SoftDeletes;

    /**
     * Check whether film has a custom promotional/informative badge.
     */
    public function hasCustomBadge(): bool
    {
        return filled($this->custom_badge);
    }

    /**
     * Dynamic canonical genres from database.
     *
     * @return array<int, string>
     */
    public static function canonicalGenres(): array
    {
        return Genre::allActiveNames();
    }

    /**
     * Return array of clean individual genres.
     *
     * @return array<int, string>
     */
    public function genresList(): array
    {
        if (empty($this->genre)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $this->genre))));
    }

    /**
     * Formatted genre string for card display: max N genres + remainder count.
     */
    public function formattedGenresText(int $max = 3): string
    {
        $list = $this->genresList();
        if (empty($list)) {
            return '-';
        }
        if (count($list) <= $max) {
            return implode(', ', $list);
        }

        $shown = array_slice($list, 0, $max);
        $remaining = count($list) - $max;

        return implode(', ', $shown).' +'.$remaining;
    }

    /**
     * Check if film has a given genre.
     */
    public function hasGenre(string $targetGenre): bool
    {
        return in_array(strtolower(trim($targetGenre)), array_map('strtolower', $this->genresList()), true);
    }

    /**
     * Scope query to apply catalog / home sorting based on settings or mode.
     */
    public function scopeApplyCustomSort($query, ?string $sort = null)
    {
        $sort = $sort ?: app(CinemaSettings::class)->filmsSortOrder();

        return match ($sort) {
            'year_asc' => $query->orderByRaw('release_year IS NULL, release_year ASC')->orderBy('title', 'asc'),
            'title_asc' => $query->orderBy('title', 'asc'),
            'title_desc' => $query->orderBy('title', 'desc'),
            'latest_added' => $query->orderBy('id', 'desc'),
            default => $query->orderByRaw('release_year IS NULL, release_year DESC')->orderBy('title', 'asc'),
        };
    }

    protected static function booted(): void
    {
        static::creating(function (Film $film) {
            if (empty($film->slug)) {
                $baseSlug = Str::slug($film->title);
                $slug = $baseSlug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.(++$counter);
                }
                $film->slug = $slug;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'release_year' => 'integer',
            'is_active' => 'boolean',
            'is_coming_soon' => 'boolean',
            'release_date' => 'date',
            'is_sample' => 'boolean',
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function ageRatingDefinition(): BelongsTo
    {
        return $this->belongsTo(AgeRating::class, 'age_rating', 'code');
    }

    public function getPosterUrlAttribute(): ?string
    {
        return $this->posterUrl();
    }

    public function getBannerUrlAttribute(): ?string
    {
        return $this->bannerUrl();
    }

    public function posterUrl(): ?string
    {
        if (empty($this->poster_path)) {
            return null;
        }

        if (str_starts_with($this->poster_path, 'http://') || str_starts_with($this->poster_path, 'https://') || str_starts_with($this->poster_path, '/')) {
            return $this->poster_path;
        }

        return Storage::url($this->poster_path);
    }

    public function bannerUrl(): ?string
    {
        if (empty($this->banner_path)) {
            return null;
        }

        if (str_starts_with($this->banner_path, 'http://') || str_starts_with($this->banner_path, 'https://') || str_starts_with($this->banner_path, '/')) {
            return $this->banner_path;
        }

        return Storage::url($this->banner_path);
    }
}
