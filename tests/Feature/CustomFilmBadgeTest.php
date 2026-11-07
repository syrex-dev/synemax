<?php

namespace Tests\Feature;

use App\Models\Film;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomFilmBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_admin_can_create_film_with_custom_badge(): void
    {
        $response = $this->post(route('admin.films.store'), [
            'title' => 'Tumbal Darah',
            'duration_minutes' => 105,
            'genre' => 'Horor',
            'age_rating' => '17+',
            'synopsis' => 'Sinopsis horor mencekam.',
            'is_active' => 1,
            'custom_badge' => 'Baru Ditambahkan',
        ]);

        $response->assertRedirect(route('admin.films.index'));

        $this->assertDatabaseHas('films', [
            'title' => 'Tumbal Darah',
            'custom_badge' => 'Baru Ditambahkan',
        ]);
    }

    public function test_admin_can_update_film_custom_badge(): void
    {
        $film = Film::query()->create([
            'title' => 'Film Aksi Keren',
            'duration_minutes' => 120,
            'genre' => 'Aksi',
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis film aksi.',
            'is_active' => true,
            'custom_badge' => 'Trending',
        ]);

        $response = $this->put(route('admin.films.update', $film), [
            'title' => 'Film Aksi Keren',
            'duration_minutes' => 120,
            'genre' => 'Aksi',
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis film aksi.',
            'is_active' => 1,
            'custom_badge' => 'Eksklusif',
        ]);

        $response->assertRedirect(route('admin.films.index'));

        $this->assertSame('Eksklusif', $film->fresh()->custom_badge);
    }

    public function test_admin_can_bulk_set_custom_badge_on_multiple_films(): void
    {
        $film1 = Film::query()->create([
            'title' => 'Film Satu',
            'duration_minutes' => 90,
            'genre' => 'Drama',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis satu.',
            'is_active' => true,
        ]);

        $film2 = Film::query()->create([
            'title' => 'Film Dua',
            'duration_minutes' => 95,
            'genre' => 'Komedi',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis dua.',
            'is_active' => true,
        ]);

        $response = $this->post(route('admin.films.bulk_action'), [
            'action' => 'set_badge',
            'film_ids' => [$film1->id, $film2->id],
            'custom_badge' => 'Baru Ditambahkan',
        ]);

        $response->assertSessionHas('status');
        $this->assertSame('Baru Ditambahkan', $film1->fresh()->custom_badge);
        $this->assertSame('Baru Ditambahkan', $film2->fresh()->custom_badge);
    }

    public function test_admin_can_bulk_clear_custom_badge_from_selected_films(): void
    {
        $film = Film::query()->create([
            'title' => 'Film Berbadge',
            'duration_minutes' => 100,
            'genre' => 'Horor',
            'age_rating' => '17+',
            'synopsis' => 'Sinopsis.',
            'is_active' => true,
            'custom_badge' => 'Baru Ditambahkan',
        ]);

        $this->post(route('admin.films.bulk_action'), [
            'action' => 'clear_badge',
            'film_ids' => [$film->id],
        ]);

        $this->assertNull($film->fresh()->custom_badge);
    }

    public function test_badge_renders_on_public_and_admin_views(): void
    {
        $film = Film::query()->create([
            'title' => 'Tumbal Darah',
            'slug' => 'tumbal-darah',
            'duration_minutes' => 105,
            'genre' => 'Horor',
            'age_rating' => '17+',
            'synopsis' => 'Sinopsis horor mencekam.',
            'is_active' => true,
            'custom_badge' => 'Baru Ditambahkan',
        ]);

        // Public Catalog
        $catalogResponse = $this->get(route('films.index'));
        $catalogResponse->assertOk();
        $catalogResponse->assertSee('Baru Ditambahkan');

        // Public Detail
        $detailResponse = $this->get(route('films.show', $film->slug));
        $detailResponse->assertOk();
        $detailResponse->assertSee('Baru Ditambahkan');

        // Admin Films List
        $adminResponse = $this->get(route('admin.films.index'));
        $adminResponse->assertOk();
        $adminResponse->assertSee('Baru Ditambahkan');
    }
}
