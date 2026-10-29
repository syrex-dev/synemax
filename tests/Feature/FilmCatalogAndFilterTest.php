<?php

namespace Tests\Feature;

use App\Models\Film;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilmCatalogAndFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_film_catalog_page_renders_active_films(): void
    {
        $film1 = Film::query()->create([
            'title' => 'Malam di Bukit Bintang',
            'slug' => 'malam-di-bukit-bintang',
            'genre' => 'Drama',
            'release_year' => 2024,
            'duration_minutes' => 105,
            'age_rating' => 'SU',
            'synopsis' => 'Kisah keluarga di desa.',
            'is_active' => true,
        ]);

        $film2 = Film::query()->create([
            'title' => 'Misi Terakhir Pak Pos',
            'slug' => 'misi-terakhir-pak-pos',
            'genre' => 'Komedi',
            'release_year' => 2023,
            'duration_minutes' => 98,
            'age_rating' => '13+',
            'synopsis' => 'Petualangan lucu pak pos.',
            'is_active' => true,
        ]);

        $inactiveFilm = Film::query()->create([
            'title' => 'Film Rahasia Draft',
            'slug' => 'film-rahasia-draft',
            'genre' => 'Misteri',
            'release_year' => 2025,
            'duration_minutes' => 100,
            'age_rating' => '17+',
            'synopsis' => 'Sinopsis misteri.',
            'is_active' => false,
        ]);

        $response = $this->get(route('films.index'));

        $response->assertOk();
        $response->assertSee('Katalog Film');
        $response->assertSee('Malam di Bukit Bintang');
        $response->assertSee('Misi Terakhir Pak Pos');
        $response->assertDontSee('Film Rahasia Draft');
    }

    public function test_film_catalog_filters_by_genre_and_year_via_json_api(): void
    {
        Film::query()->create([
            'title' => 'Aksi di Kota Tua',
            'slug' => 'aksi-di-kota-tua',
            'genre' => 'Aksi',
            'release_year' => 2025,
            'duration_minutes' => 110,
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis aksi seru.',
            'is_active' => true,
        ]);

        Film::query()->create([
            'title' => 'Drama Musim Gugur',
            'slug' => 'drama-musim-gugur',
            'genre' => 'Drama',
            'release_year' => 2024,
            'duration_minutes' => 95,
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis drama hangat.',
            'is_active' => true,
        ]);

        // Filter by Genre
        $resGenre = $this->getJson(route('films.index', ['genre' => 'Aksi']));
        $resGenre->assertOk();
        $resGenre->assertJsonPath('count', 1);
        $resGenre->assertJsonFragment(['title' => 'Aksi di Kota Tua']);
        $resGenre->assertJsonMissing(['title' => 'Drama Musim Gugur']);

        // Filter by Year
        $resYear = $this->getJson(route('films.index', ['tahun' => 2024]));
        $resYear->assertOk();
        $resYear->assertJsonPath('count', 1);
        $resYear->assertJsonFragment(['title' => 'Drama Musim Gugur']);
    }

    public function test_film_detail_page_renders_metadata_and_upcoming_showtimes(): void
    {
        $film = Film::query()->create([
            'title' => 'Malam di Bukit Bintang',
            'slug' => 'malam-di-bukit-bintang',
            'genre' => 'Drama',
            'release_year' => 2024,
            'duration_minutes' => 105,
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis lengkap film keluarga.',
            'is_active' => true,
        ]);

        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();

        Session::query()->create([
            'session_date' => $today->addDay()->toDateString(),
            'start_time' => '16:00',
            'end_time' => '18:15',
            'film_id' => $film->id,
            'capacity' => 6,
            'status' => 'tersedia',
        ]);

        $response = $this->get(route('films.show', $film->slug));

        $response->assertOk();
        $response->assertSee('Malam di Bukit Bintang');
        $response->assertSee('Sinopsis lengkap film keluarga.');
        $response->assertSee('Pilih Film Ini');
        $response->assertSee('Gabung sesi yang sudah memilih film ini');
    }

    public function test_film_detail_displays_friendly_message_when_no_sessions_scheduled(): void
    {
        $film = Film::query()->create([
            'title' => 'Film Tanpa Sesi',
            'slug' => 'film-tanpa-sesi',
            'genre' => 'Dokumenter',
            'release_year' => 2025,
            'duration_minutes' => 90,
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis film dokumenter.',
            'is_active' => true,
        ]);

        $response = $this->get(route('films.show', $film->slug));

        $response->assertOk();
        $response->assertSee('Pilih Film Ini');
        $response->assertDontSee('Gabung sesi yang sudah memilih film ini');
    }
}
