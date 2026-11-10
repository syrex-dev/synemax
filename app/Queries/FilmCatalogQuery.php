<?php

namespace App\Queries;

use App\Models\Film;
use App\Models\Genre;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class FilmCatalogQuery
{
    /**
     * Build base query for active public films with relationships eager-loaded.
     *
     * @return Builder<Film>
     */
    public function baseQuery(): Builder
    {
        return Film::query()
            ->where('is_active', true)
            ->with(['genres' => fn ($q) => $q->orderByPivot('position')]);
    }

    /**
     * Get paginated catalog results with live filter support.
     *
     * @param  array{genre?: ?string, tahun?: ?string|int, cari?: ?string, q?: ?string}  $filters
     * @return LengthAwarePaginator<Film>
     */
    public function paginateCatalog(array $filters = [], int $perPage = 30): LengthAwarePaginator
    {
        $query = $this->baseQuery();

        $genreInput = trim((string) ($filters['genre'] ?? ''));
        $yearInput = trim((string) ($filters['tahun'] ?? ''));
        $searchInput = trim((string) ($filters['cari'] ?? ($filters['q'] ?? '')));

        if ($genreInput !== '') {
            $genreModel = Genre::where('name', $genreInput)
                ->orWhere('normalized_name', Genre::normalizeName($genreInput))
                ->first();

            if ($genreModel) {
                $familyIds = $genreModel->getFamilyIds();
                $query->where(function ($sub) use ($familyIds, $genreInput) {
                    $sub->whereHas('genres', function ($q) use ($familyIds) {
                        $q->whereIn('genres.id', $familyIds);
                    })->orWhere('genre', 'like', '%'.$genreInput.'%');
                });
            } else {
                // Fallback text matching on denormalized string
                $query->where('genre', 'like', '%'.$genreInput.'%');
            }
        }

        if ($yearInput !== '' && is_numeric($yearInput)) {
            $yearInt = (int) $yearInput;
            $query->where(function (Builder $sub) use ($yearInt): void {
                $sub->where('release_year', $yearInt)
                    ->orWhere(function (Builder $dateSub) use ($yearInt): void {
                        $dateSub->whereNull('release_year')
                            ->whereNotNull('release_date')
                            ->whereYear('release_date', $yearInt);
                    });
            });
        }

        if ($searchInput !== '') {
            $query->where('title', 'like', '%'.$searchInput.'%');
        }

        return $query->applyCustomSort()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get sorted unique release years from all active films.
     *
     * @return SupportCollection<int, int>
     */
    public function availableYears(): SupportCollection
    {
        return Film::query()
            ->where('is_active', true)
            ->get(['release_year', 'release_date'])
            ->map(function (Film $film): ?int {
                if ($film->release_year) {
                    return (int) $film->release_year;
                }

                if ($film->release_date) {
                    return (int) $film->release_date->format('Y');
                }

                return null;
            })
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();
    }

    /**
     * Get similar films based on genre pivot priority.
     * Primary genre (position = 0) matches are prioritized over secondary genres.
     * Purely unrelated genres are NEVER returned.
     *
     * @return Collection<int, Film>
     */
    public function similarFilms(Film $film, int $limit = 4): Collection
    {
        $filmGenres = $film->relationLoaded('genres')
            ? $film->genres
            : $film->genres()->orderByPivot('position')->get();

        if ($filmGenres->isEmpty()) {
            $rawGenres = array_filter(array_map('trim', explode(',', (string) $film->genre)));
            if (empty($rawGenres)) {
                return new Collection;
            }

            return Film::query()
                ->where('is_active', true)
                ->where('id', '!=', $film->id)
                ->where(function ($q) use ($rawGenres) {
                    foreach ($rawGenres as $g) {
                        $q->orWhere('genre', 'like', '%'.$g.'%');
                    }
                })
                ->applyCustomSort()
                ->take($limit)
                ->get();
        }

        $primaryGenre = $filmGenres->first();
        $primaryId = $primaryGenre ? $primaryGenre->id : null;
        $primaryFamilyIds = $primaryGenre ? $primaryGenre->getFamilyIds() : [];

        $secondaryGenres = $filmGenres->slice(1);
        $secondaryFamilyIds = [];
        foreach ($secondaryGenres as $sg) {
            $secondaryFamilyIds = array_merge($secondaryFamilyIds, $sg->getFamilyIds());
        }
        $secondaryFamilyIds = array_values(array_unique(array_diff($secondaryFamilyIds, $primaryFamilyIds)));

        $allFamilyIds = array_values(array_unique(array_merge($primaryFamilyIds, $secondaryFamilyIds)));

        if (empty($allFamilyIds)) {
            return new Collection;
        }

        // Subquery or joined aggregate to compute priority score
        // Primary genre matching position 0: score 10
        // Primary genre matching any position: score 6
        // Secondary genre matching: score 3
        $primaryFamilySql = implode(',', array_map('intval', $primaryFamilyIds));
        $secondaryFamilySql = ! empty($secondaryFamilyIds)
            ? implode(',', array_map('intval', $secondaryFamilyIds))
            : '0';

        $candidateIdsWithScores = DB::table('films')
            ->join('film_genre', 'films.id', '=', 'film_genre.film_id')
            ->where('films.is_active', true)
            ->whereNull('films.deleted_at')
            ->where('films.id', '!=', $film->id)
            ->whereIn('film_genre.genre_id', $allFamilyIds)
            ->groupBy('films.id', 'films.release_year')
            ->select('films.id', 'films.release_year')
            ->selectRaw("
                SUM(CASE
                    WHEN film_genre.genre_id IN ({$primaryFamilySql}) AND film_genre.position = 0 THEN 10
                    WHEN film_genre.genre_id IN ({$primaryFamilySql}) THEN 6
                    WHEN film_genre.genre_id IN ({$secondaryFamilySql}) THEN 3
                    ELSE 1
                END) as similarity_score
            ")
            ->orderByDesc('similarity_score')
            ->orderByDesc('films.release_year')
            ->orderByDesc('films.id')
            ->limit($limit)
            ->get();

        if ($candidateIdsWithScores->isEmpty()) {
            return new Collection;
        }

        $orderedIds = $candidateIdsWithScores->pluck('id')->all();

        // Load models preserving exact priority order
        $loadedFilms = Film::query()
            ->whereIn('id', $orderedIds)
            ->with(['genres' => fn ($q) => $q->orderByPivot('position')])
            ->get()
            ->keyBy('id');

        $result = new Collection;
        foreach ($orderedIds as $id) {
            if (isset($loadedFilms[$id])) {
                $result->push($loadedFilms[$id]);
            }
        }

        return $result;
    }
}
