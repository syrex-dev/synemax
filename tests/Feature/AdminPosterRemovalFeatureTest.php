<?php

namespace Tests\Feature;

use App\Models\Film;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPosterRemovalFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_remove_matched_poster_and_delete_file_from_storage(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $filePath = 'posters/test-film-poster.webp';
        Storage::disk('public')->put($filePath, 'fake image content');

        $film = Film::query()->create([
            'title' => 'Film Uji Coba Hapus Poster',
            'slug' => 'film-uji-coba-hapus-poster',
            'genre' => 'Horor',
            'release_year' => 2026,
            'duration_minutes' => 90,
            'age_rating' => '17+',
            'synopsis' => 'Sinopsis uji coba.',
            'poster_path' => $filePath,
            'is_sample' => false,
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $response = $this->postJson(route('admin.films.posters.remove_matched'), [
            'film_id' => $film->id,
            'stored_path' => $filePath,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'film_id' => $film->id,
            ]);

        $film->refresh();
        $this->assertNull($film->poster_path);
        Storage::disk('public')->assertMissing($filePath);
    }

    public function test_admin_can_destroy_unmatched_poster(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $tempFileName = 'screenshot-poster_abc123.webp';
        $unmatchedPath = 'posters/unmatched/'.$tempFileName;
        Storage::disk('public')->put($unmatchedPath, 'fake unmatched image');

        $this->actingAs($admin);

        $response = $this->deleteJson(route('admin.films.posters.destroy', $tempFileName));

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        Storage::disk('public')->assertMissing($unmatchedPath);
    }

    public function test_admin_can_destroy_unmatched_poster_with_spaces_in_filename(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $tempFileName = 'Screenshot 2026-11-06 075651.webp';
        $unmatchedPath = 'posters/unmatched/'.$tempFileName;
        Storage::disk('public')->put($unmatchedPath, 'fake unmatched image');

        $this->actingAs($admin);

        $response = $this->deleteJson(route('admin.films.posters.destroy', rawurlencode($tempFileName)));

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        Storage::disk('public')->assertMissing($unmatchedPath);
    }

    public function test_admin_can_assign_single_unmatched_poster_to_film(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $tempFileName = 'poster_manual_assign.webp';
        $unmatchedPath = 'posters/unmatched/'.$tempFileName;
        Storage::disk('public')->put($unmatchedPath, 'fake unmatched image bytes');

        $film = Film::query()->create([
            'title' => 'Film Uji Coba Assign',
            'slug' => 'film-uji-coba-assign',
            'genre' => 'Horor',
            'release_year' => 2026,
            'duration_minutes' => 100,
            'age_rating' => '17+',
            'synopsis' => 'Sinopsis uji coba assign.',
            'poster_path' => null,
            'is_sample' => false,
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $response = $this->postJson(route('admin.films.posters.assign'), [
            'poster_filename' => $tempFileName,
            'film_id' => $film->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'film_id' => $film->id,
            ]);

        $film->refresh();
        $this->assertNotNull($film->poster_path);
        Storage::disk('public')->assertMissing($unmatchedPath);
        Storage::disk('public')->assertExists($film->poster_path);
    }

    public function test_film_create_form_renders_genre_picker_and_stores_multiple_genres(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->actingAs($admin);

        $viewResponse = $this->get(route('admin.films.create'));
        $viewResponse->assertOk()
            ->assertSee('Genre Film')
            ->assertSee('Genre Utama Bioskop')
            ->assertSee('Cari genre cepat');

        $createResponse = $this->post(route('admin.films.store'), [
            'title' => 'Film Horor Komedi Super',
            'slug' => 'film-horor-komedi-super',
            'genres' => ['Horor', 'Komedi'],
            'new_genres' => ['Horor Komedi Fantastis'],
            'release_year' => 2026,
            'duration_minutes' => 105,
            'age_rating' => '17+',
            'synopsis' => 'Sinopsis film horor komedi yang seru dan menyenangkan.',
            'director' => 'Sutradara Hebat',
            'casts' => 'Aktor 1, Aktris 2',
            'language' => 'Indonesia',
            'is_active' => 1,
        ]);

        $createResponse->assertRedirect(route('admin.films.index'));

        $film = Film::where('slug', 'film-horor-komedi-super')->first();
        $this->assertNotNull($film);
        $this->assertStringContainsString('Horor', $film->genre);
        $this->assertStringContainsString('Komedi', $film->genre);
    }
}
