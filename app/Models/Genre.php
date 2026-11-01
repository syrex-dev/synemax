<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Genre extends Model
{
    protected $fillable = [
        'name',
        'normalized_name',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function () {
            static::clearGenreCache();
        });

        static::deleted(function () {
            static::clearGenreCache();
        });
    }

    /**
     * Clear all genre caches.
     */
    public static function clearGenreCache(): void
    {
        Cache::forget('active_genres_names');
        Cache::forget('active_genres_collection');
        Cache::forget('all_genres_collection');
    }

    /**
     * Normalize string for unique normalized_name matching.
     */
    public static function normalizeName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name);
        $name = mb_strtolower($name, 'UTF-8');

        // Keep alphanumeric, spaces, and hyphens (for sci-fi)
        return (string) preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $name);
    }

    /**
     * Format a display name properly (Title Case, preserve Sci-Fi, max 30 chars).
     */
    public static function formatDisplayName(string $raw): ?string
    {
        $clean = trim(strip_tags($raw));
        $clean = (string) preg_replace('/\s+/', ' ', $clean);
        // Remove weird characters but preserve letters, numbers, spaces, and hyphens
        $clean = (string) preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $clean);

        if ($clean === '') {
            return null;
        }

        if (mb_strlen($clean, 'UTF-8') > 30) {
            $clean = mb_substr($clean, 0, 30, 'UTF-8');
        }

        if (strcasecmp($clean, 'sci-fi') === 0 || strcasecmp($clean, 'scifi') === 0) {
            return 'Sci-Fi';
        }

        return mb_convert_case($clean, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Map English alias or common variants to canonical Indonesian name.
     */
    public static function mapAlias(string $token): ?string
    {
        $normalized = self::normalizeName($token);

        $aliasMap = [
            'action' => 'Aksi',
            'aksi' => 'Aksi',
            'comedy' => 'Komedi',
            'komedi' => 'Komedi',
            'horror' => 'Horor',
            'horor' => 'Horor',
            'romance' => 'Romantis',
            'romantis' => 'Romantis',
            'roman' => 'Romantis',
            'romantik' => 'Romantis',
            'animation' => 'Animasi',
            'animasi' => 'Animasi',
            'fantasy' => 'Fantasi',
            'fantasi' => 'Fantasi',
            'adventure' => 'Petualangan',
            'petualangan' => 'Petualangan',
            'petualang' => 'Petualangan',
            'family' => 'Keluarga',
            'keluarga' => 'Keluarga',
            'mystery' => 'Misteri',
            'misteri' => 'Misteri',
            'science fiction' => 'Sci-Fi',
            'fiksi ilmiah' => 'Sci-Fi',
            'sci-fi' => 'Sci-Fi',
            'scifi' => 'Sci-Fi',
            'documentary' => 'Dokumenter',
            'dokumenter' => 'Dokumenter',
            'thriller' => 'Thriller',
            'triler' => 'Thriller',
            'tegang' => 'Thriller',
            'drama' => 'Drama',
        ];

        return $aliasMap[$normalized] ?? null;
    }

    /**
     * Get or create a genre by raw name safely.
     */
    public static function findOrCreateByName(string $rawName): ?self
    {
        $mapped = self::mapAlias($rawName);
        $displayName = $mapped ?: self::formatDisplayName($rawName);

        if (! $displayName) {
            return null;
        }

        $normalized = self::normalizeName($displayName);
        if ($normalized === '') {
            return null;
        }

        $existing = self::where('normalized_name', $normalized)->first();
        if ($existing) {
            return $existing;
        }

        $maxOrder = (int) self::max('sort_order');

        return self::create([
            'name' => $displayName,
            'normalized_name' => $normalized,
            'sort_order' => $maxOrder + 1,
            'is_active' => true,
        ]);
    }

    /**
     * Fallback standard 18 genres when table does not exist yet.
     *
     * @return array<int, string>
     */
    public static function defaultFallbackGenres(): array
    {
        return [
            'Aksi', 'Animasi', 'Biografi', 'Dokumenter', 'Drama',
            'Fantasi', 'Horor', 'Kejahatan', 'Keluarga', 'Komedi',
            'Misteri', 'Musikal', 'Perang', 'Petualangan', 'Romantis',
            'Sci-Fi', 'Sejarah', 'Thriller',
        ];
    }

    /**
     * Get all active genre names as cached array.
     *
     * @return array<int, string>
     */
    public static function allActiveNames(): array
    {
        return Cache::rememberForever('active_genres_names', function () {
            try {
                if (! Schema::hasTable('genres')) {
                    return self::defaultFallbackGenres();
                }

                $names = self::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->pluck('name')
                    ->all();

                return ! empty($names) ? $names : self::defaultFallbackGenres();
            } catch (\Throwable $e) {
                return self::defaultFallbackGenres();
            }
        });
    }

    /**
     * Get all active genres as cached Collection.
     *
     * @return Collection<int, self>
     */
    public static function cachedActive(): Collection
    {
        try {
            $cached = Cache::get('active_genres_collection');
            if ($cached instanceof Collection) {
                return $cached;
            }

            if (! Schema::hasTable('genres')) {
                return new Collection;
            }

            $collection = self::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            Cache::forever('active_genres_collection', $collection);

            return $collection;
        } catch (\Throwable $e) {
            return new Collection;
        }
    }

    /**
     * Count how many films currently use this genre.
     */
    public function countFilms(): int
    {
        if (empty($this->name)) {
            return 0;
        }

        return Film::withTrashed()
            ->whereNotNull('genre')
            ->where(function ($q) {
                $q->where('genre', $this->name)
                    ->orWhere('genre', 'LIKE', $this->name.',%')
                    ->orWhere('genre', 'LIKE', '%, '.$this->name.',%')
                    ->orWhere('genre', 'LIKE', '%, '.$this->name);
            })
            ->count();
    }

    /**
     * Rename this genre and update all occurrences in films table.
     */
    public function renameWithFilms(string $newName): void
    {
        $oldName = $this->name;
        $formatted = self::formatDisplayName($newName);
        if (! $formatted) {
            throw new \InvalidArgumentException('Nama genre tidak valid.');
        }

        $normalized = self::normalizeName($formatted);

        $exists = self::where('normalized_name', $normalized)->where('id', '!=', $this->id)->exists();
        if ($exists) {
            throw new \InvalidArgumentException("Genre dengan nama '{$formatted}' sudah ada. Gunakan fitur Gabung.");
        }

        DB::transaction(function () use ($formatted, $normalized, $oldName) {
            $this->update([
                'name' => $formatted,
                'normalized_name' => $normalized,
            ]);

            // Update films
            $films = Film::withTrashed()
                ->whereNotNull('genre')
                ->where('genre', '!=', '')
                ->get();

            foreach ($films as $film) {
                $tokens = array_map('trim', explode(',', (string) $film->genre));
                $modified = false;
                foreach ($tokens as $idx => $t) {
                    if (strcasecmp($t, $oldName) === 0) {
                        $tokens[$idx] = $formatted;
                        $modified = true;
                    }
                }
                if ($modified) {
                    $unique = array_values(array_unique(array_filter($tokens)));
                    $film->update(['genre' => implode(', ', $unique)]);
                }
            }
        });
    }

    /**
     * Merge this genre into target genre and delete this genre.
     */
    public function mergeInto(self $target): void
    {
        if ($this->id === $target->id) {
            return;
        }

        $oldName = $this->name;
        $targetName = $target->name;

        DB::transaction(function () use ($oldName, $targetName) {
            $films = Film::withTrashed()
                ->whereNotNull('genre')
                ->where('genre', '!=', '')
                ->get();

            foreach ($films as $film) {
                $tokens = array_map('trim', explode(',', (string) $film->genre));
                $modified = false;
                foreach ($tokens as $idx => $t) {
                    if (strcasecmp($t, $oldName) === 0) {
                        $tokens[$idx] = $targetName;
                        $modified = true;
                    }
                }
                if ($modified) {
                    $unique = array_values(array_unique(array_filter($tokens)));
                    $film->update(['genre' => implode(', ', $unique)]);
                }
            }

            $this->delete();
        });
    }
}
