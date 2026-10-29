<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
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
])]
class Film extends Model
{
    use SoftDeletes;

    public const CANONICAL_GENRES = [
        'Aksi',
        'Animasi',
        'Drama',
        'Fantasi',
        'Horor',
        'Keluarga',
        'Komedi',
        'Misteri',
        'Petualangan',
        'Romantis',
        'Sci-Fi',
        'Thriller',
    ];

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
     * Check if film has a given genre.
     */
    public function hasGenre(string $targetGenre): bool
    {
        return in_array(strtolower(trim($targetGenre)), array_map('strtolower', $this->genresList()), true);
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
