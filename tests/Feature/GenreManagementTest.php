<?php

namespace Tests\Feature;

use App\Models\Film;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreManagementTest extends TestCase
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

    public function test_admin_can_view_genre_list(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.genres.index'));

        $response->assertOk();
        $response->assertSee('Horor');
        $response->assertSee('Aksi');
        $response->assertSee('Drama');
    }

    public function test_admin_can_create_new_genre_with_formatted_name(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.genres.store'), [
            'name' => 'cyberpunk noir',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.genres.index'));
        $this->assertDatabaseHas('genres', [
            'name' => 'Cyberpunk Noir',
            'normalized_name' => 'cyberpunk noir',
            'is_active' => 1,
        ]);
    }

    public function test_cannot_create_duplicate_normalized_genre(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.genres.store'), [
            'name' => '  drama  ',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(1, Genre::where('normalized_name', 'drama')->count());
    }

    public function test_renaming_genre_updates_associated_films(): void
    {
        $genre = Genre::where('name', 'Sci-Fi')->firstOrFail();

        $film1 = Film::create([
            'title' => 'Film Nebula',
            'synopsis' => 'Sinopsis petualangan nebula antariksa.',
            'genre' => 'Sci-Fi, Petualangan',
            'duration_minutes' => 120,
            'age_rating' => '13+',
            'is_active' => true,
        ]);

        $film2 = Film::create([
            'title' => 'Film Alien',
            'synopsis' => 'Sinopsis film invasi alien.',
            'genre' => 'Sci-Fi',
            'duration_minutes' => 90,
            'age_rating' => '17+',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.genres.update', $genre), [
            'name' => 'Fiksi Ilmiah',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.genres.index'));

        $this->assertEquals('Fiksi Ilmiah, Petualangan', $film1->fresh()->genre);
        $this->assertEquals('Fiksi Ilmiah', $film2->fresh()->genre);
    }

    public function test_merging_genre_migrates_film_genres_and_deletes_source(): void
    {
        $source = Genre::create([
            'name' => 'Laga Keras',
            'normalized_name' => 'laga keras',
            'sort_order' => 99,
            'is_active' => true,
        ]);
        $target = Genre::where('name', 'Aksi')->firstOrFail();

        $film = Film::create([
            'title' => 'Pembalap Liar',
            'synopsis' => 'Sinopsis pembalap malam kota.',
            'genre' => 'Laga Keras, Drama',
            'duration_minutes' => 110,
            'age_rating' => '13+',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.genres.merge', $source), [
            'target_id' => $target->id,
        ]);

        $response->assertRedirect(route('admin.genres.index'));

        $this->assertDatabaseMissing('genres', ['id' => $source->id]);
        $this->assertEquals('Aksi, Drama', $film->fresh()->genre);
    }

    public function test_cannot_delete_genre_in_use_without_action(): void
    {
        $genre = Genre::where('name', 'Komedi')->firstOrFail();

        Film::create([
            'title' => 'Warkop Baru',
            'synopsis' => 'Sinopsis film komedi segar.',
            'genre' => 'Komedi',
            'duration_minutes' => 95,
            'age_rating' => 'SU',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.genres.destroy', $genre));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
    }

    public function test_can_delete_unused_genre(): void
    {
        $genre = Genre::create([
            'name' => 'Genre Sementara',
            'normalized_name' => 'genre sementara',
            'sort_order' => 100,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.genres.destroy', $genre));

        $response->assertRedirect(route('admin.genres.index'));
        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
    }

    public function test_reordering_genres(): void
    {
        $g1 = Genre::where('name', 'Aksi')->firstOrFail();
        $g2 = Genre::where('name', 'Animasi')->firstOrFail();

        $originalOrder1 = $g1->sort_order;
        $originalOrder2 = $g2->sort_order;

        $response = $this->actingAs($this->admin)->post(route('admin.genres.reorder'), [
            'id' => $g1->id,
            'direction' => 'down',
        ]);

        $response->assertSessionHas('status');
        $this->assertEquals($originalOrder2, $g1->fresh()->sort_order);
    }

    public function test_public_catalog_uses_database_genres(): void
    {
        Genre::create([
            'name' => 'Thriller Unik',
            'normalized_name' => 'thriller unik',
            'sort_order' => 999,
            'is_active' => true,
        ]);

        $response = $this->get(route('films.index'));

        $response->assertOk();
        $response->assertSee('Thriller Unik');
    }
}
