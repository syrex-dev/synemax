<?php

namespace Tests\Feature;

use App\Models\Film;
use App\Models\User;
use App\Services\CinemaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilmSortingOrderSettingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);
    }

    public function test_default_film_sorting_orders_by_year_descending_then_title(): void
    {
        $film2024 = Film::query()->create([
            'title' => 'Film 2024 Beta',
            'slug' => 'film-2024-beta',
            'duration_minutes' => 100,
            'genre' => 'Drama',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis',
            'release_year' => 2024,
            'is_active' => true,
        ]);

        $film2026A = Film::query()->create([
            'title' => 'Film 2026 Alpha',
            'slug' => 'film-2026-alpha',
            'duration_minutes' => 100,
            'genre' => 'Aksi',
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis',
            'release_year' => 2026,
            'is_active' => true,
        ]);

        $film2026Z = Film::query()->create([
            'title' => 'Film 2026 Zebra',
            'slug' => 'film-2026-zebra',
            'duration_minutes' => 100,
            'genre' => 'Komedi',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis',
            'release_year' => 2026,
            'is_active' => true,
        ]);

        $film2020 = Film::query()->create([
            'title' => 'Film 2020 Klasik',
            'slug' => 'film-2020-klasik',
            'duration_minutes' => 100,
            'genre' => 'Horor',
            'age_rating' => '17+',
            'synopsis' => 'Sinopsis',
            'release_year' => 2020,
            'is_active' => true,
        ]);

        // Home page test
        $homeRes = $this->get(route('home'));
        $homeRes->assertOk();
        $homeFilms = $homeRes->viewData('films');
        $this->assertEquals(
            [$film2026A->id, $film2026Z->id, $film2024->id, $film2020->id],
            $homeFilms->pluck('id')->all()
        );

        // Catalog film page test
        $catalogRes = $this->get(route('films.index'));
        $catalogRes->assertOk();
        $catalogFilms = $catalogRes->viewData('films');
        $this->assertEquals(
            [$film2026A->id, $film2026Z->id, $film2024->id, $film2020->id],
            $catalogFilms->pluck('id')->all()
        );
    }

    public function test_admin_can_update_films_sort_order_setting(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'active_tab' => 'tampilan',
            'default_theme' => 'system',
            'home_films_count' => 8,
            'films_sort_order' => 'title_asc',
            'home_hero_interval' => 5,
        ]);

        $response->assertRedirect(route('admin.settings.edit', ['tab' => 'tampilan']));
        $this->assertSame('title_asc', app(CinemaSettings::class)->filmsSortOrder());

        // Create films out of alphabetical order
        $filmZ = Film::query()->create([
            'title' => 'Zebra Movie',
            'slug' => 'zebra-movie',
            'duration_minutes' => 100,
            'genre' => 'Aksi',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis',
            'release_year' => 2026,
            'is_active' => true,
        ]);

        $filmA = Film::query()->create([
            'title' => 'Alpha Movie',
            'slug' => 'alpha-movie',
            'duration_minutes' => 100,
            'genre' => 'Drama',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis',
            'release_year' => 2020,
            'is_active' => true,
        ]);

        $homeRes = $this->get(route('home'));
        $homeRes->assertOk();
        $this->assertSame($filmA->id, $homeRes->viewData('films')->first()->id);
        $this->assertSame($filmZ->id, $homeRes->viewData('films')->last()->id);
    }
}
