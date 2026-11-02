<?php

namespace Tests\Feature;

use App\Models\Film;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilmCatalogPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_paginates_30_films_per_page(): void
    {
        Genre::firstOrCreate(['name' => 'Aksi'], ['normalized_name' => 'aksi', 'is_active' => true]);

        for ($i = 1; $i <= 35; $i++) {
            Film::query()->create([
                'title' => sprintf('Film Aksi %02d', $i),
                'slug' => 'film-aksi-'.$i,
                'synopsis' => 'Sinopsis film '.$i,
                'genre' => 'Aksi',
                'duration_minutes' => 90,
                'age_rating' => '13+',
                'is_active' => true,
            ]);
        }

        $res = $this->get(route('films.index'));
        $res->assertOk();
        $res->assertViewHas('films', function ($paginator) {
            return $paginator->perPage() === 30
                && $paginator->count() === 30
                && $paginator->total() === 35
                && $paginator->lastPage() === 2;
        });

        // Page 2 should have remaining 5 films
        $resPage2 = $this->get(route('films.index', ['page' => 2]));
        $resPage2->assertOk();
        $resPage2->assertViewHas('films', function ($paginator) {
            return $paginator->perPage() === 30
                && $paginator->count() === 5;
        });
    }

    public function test_pagination_preserves_query_string_parameters(): void
    {
        Genre::firstOrCreate(['name' => 'Horor'], ['normalized_name' => 'horor', 'is_active' => true]);

        for ($i = 1; $i <= 35; $i++) {
            Film::query()->create([
                'title' => sprintf('Film Horor %02d', $i),
                'slug' => 'film-horor-'.$i,
                'synopsis' => 'Sinopsis horor '.$i,
                'genre' => 'Horor',
                'release_year' => 2024,
                'duration_minutes' => 90,
                'age_rating' => '17+',
                'is_active' => true,
            ]);
        }

        $response = $this->get(route('films.index', ['genre' => 'Horor', 'tahun' => 2024]));
        $response->assertOk();
        $response->assertViewHas('films', function ($paginator) {
            $nextUrl = $paginator->nextPageUrl();

            return str_contains($nextUrl, 'genre=Horor')
                && str_contains($nextUrl, 'tahun=2024')
                && str_contains($nextUrl, 'page=2');
        });
    }
}
