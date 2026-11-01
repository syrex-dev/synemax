<?php

namespace Tests\Feature;

use App\Models\AgeRating;
use App\Models\Film;
use App\Models\User;
use App\Services\FilmBulkImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgeRatingCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);

        // Ensure default seed exists in test DB
        $seedData = [
            ['code' => 'SU', 'label' => 'Semua Umur', 'min_age' => 0, 'sort_order' => 1, 'is_active' => true],
            ['code' => '13+', 'label' => 'Remaja 13 Tahun ke Atas', 'min_age' => 13, 'sort_order' => 2, 'is_active' => true],
            ['code' => '17+', 'label' => 'Dewasa 17 Tahun ke Atas', 'min_age' => 17, 'sort_order' => 3, 'is_active' => true],
            ['code' => '21+', 'label' => 'Dewasa 21 Tahun ke Atas', 'min_age' => 21, 'sort_order' => 4, 'is_active' => true],
        ];

        foreach ($seedData as $row) {
            AgeRating::updateOrCreate(['code' => $row['code']], $row);
        }
    }

    public function test_can_list_age_ratings_in_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.age_ratings.index'));

        $response->assertOk();
        $response->assertSee('Klasifikasi Usia');
        $response->assertSee('SU');
        $response->assertSee('13+');
        $response->assertSee('17+');
        $response->assertSee('21+');
    }

    public function test_can_create_new_age_rating(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.age_ratings.store'), [
            'code' => 'BO',
            'label' => 'Bimbingan Orang Tua',
            'min_age' => 7,
            'description' => 'Perlu didampingi orang tua untuk anak-anak.',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.age_ratings.index'));
        $this->assertDatabaseHas('age_ratings', [
            'code' => 'BO',
            'label' => 'Bimbingan Orang Tua',
            'min_age' => 7,
            'is_active' => true,
        ]);
    }

    public function test_can_update_age_rating_label(): void
    {
        $rating = AgeRating::where('code', 'SU')->firstOrFail();

        $response = $this->actingAs($this->admin)->put(route('admin.age_ratings.update', $rating), [
            'label' => 'Semua Umur (Bebas Semua Kalangan)',
            'min_age' => 0,
            'description' => 'Dapat ditonton seluruh keluarga.',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.age_ratings.index'));
        $this->assertDatabaseHas('age_ratings', [
            'id' => $rating->id,
            'label' => 'Semua Umur (Bebas Semua Kalangan)',
        ]);
    }

    public function test_code_cannot_be_changed_if_used_by_films(): void
    {
        Film::create([
            'title' => 'Film Edukasi Anak',
            'slug' => 'film-edukasi-anak',
            'duration_minutes' => 90,
            'genre' => 'Animasi',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis film edukasi anak yang sangat menarik untuk semua umur.',
            'is_active' => true,
        ]);

        $rating = AgeRating::where('code', 'SU')->firstOrFail();

        $response = $this->actingAs($this->admin)->put(route('admin.age_ratings.update', $rating), [
            'code' => 'ALL',
            'label' => 'Semua Umur Global',
            'min_age' => 0,
            'is_active' => 1,
        ]);

        // Code remains SU because it is used by films
        $this->assertEquals('SU', $rating->fresh()->code);
        $this->assertEquals('Semua Umur Global', $rating->fresh()->label);
    }

    public function test_can_deactivate_and_reactivate_age_rating(): void
    {
        $rating = AgeRating::where('code', '21+')->firstOrFail();

        // Toggle off
        $response = $this->actingAs($this->admin)->patch(route('admin.age_ratings.toggle', $rating));
        $response->assertRedirect();
        $this->assertFalse((bool) $rating->fresh()->is_active);
        $this->assertNotContains('21+', AgeRating::activeCodes());

        // Toggle on
        $response = $this->actingAs($this->admin)->patch(route('admin.age_ratings.toggle', $rating));
        $response->assertRedirect();
        $this->assertTrue((bool) $rating->fresh()->is_active);
        $this->assertContains('21+', AgeRating::activeCodes());
    }

    public function test_cannot_delete_or_deactivate_last_active_rating(): void
    {
        // Deactivate all except one
        AgeRating::where('code', '!=', 'SU')->update(['is_active' => false]);
        $su = AgeRating::where('code', 'SU')->firstOrFail();

        // Attempting to deactivate the last active rating
        $response = $this->actingAs($this->admin)->patch(route('admin.age_ratings.toggle', $su));
        $response->assertSessionHas('error');
        $this->assertTrue((bool) $su->fresh()->is_active);

        // Attempting to delete the last active rating
        $delResponse = $this->actingAs($this->admin)->delete(route('admin.age_ratings.destroy', $su));
        $delResponse->assertSessionHas('error');
        $this->assertDatabaseHas('age_ratings', ['code' => 'SU']);
    }

    public function test_can_delete_with_film_reassignment(): void
    {
        $film = Film::create([
            'title' => 'Film Horor Dewasa',
            'slug' => 'film-horor-dewasa',
            'duration_minutes' => 110,
            'genre' => 'Horor',
            'age_rating' => '21+',
            'synopsis' => 'Sinopsis horor yang sangat menegangkan.',
            'is_active' => true,
        ]);

        $rating21 = AgeRating::where('code', '21+')->firstOrFail();

        $response = $this->actingAs($this->admin)->delete(route('admin.age_ratings.destroy', $rating21), [
            'action' => 'reassign',
            'target_code' => '17+',
        ]);

        $response->assertRedirect(route('admin.age_ratings.index'));
        $this->assertDatabaseMissing('age_ratings', ['code' => '21+']);
        $this->assertEquals('17+', $film->fresh()->age_rating);
    }

    public function test_csv_import_with_new_and_unknown_classification(): void
    {
        $importer = app(FilmBulkImporter::class);

        // 1. Registered rating 21+ should be recognized directly
        $rows = [
            [
                'title' => 'Film Khusus 21',
                'duration_minutes' => 100,
                'genre' => 'Thriller',
                'age_rating' => '21+',
                'synopsis' => 'Sinopsis cerita lengkap.',
                'is_active' => '1',
            ],
            [
                'title' => 'Film Klasifikasi Tak Dikenal',
                'duration_minutes' => 95,
                'genre' => 'Drama',
                'age_rating' => 'XX-UNKNOWN',
                'synopsis' => 'Sinopsis cerita lengkap lainnya.',
                'is_active' => '1',
            ],
        ];

        $analyzed = $importer->analyzeRows($rows);

        // Row 1: 21+ recognized cleanly
        $this->assertEquals('21+', $analyzed[0]['age_rating']);
        $this->assertEquals('baru', $analyzed[0]['status']);

        // Row 2: XX-UNKNOWN not recognized, marked as masalah, preserved for admin
        $this->assertEquals('masalah', $analyzed[1]['status']);
        $this->assertArrayHasKey('age_rating', $analyzed[1]['cell_errors']);
        $this->assertEquals('XX-UNKNOWN', $analyzed[1]['age_rating']);
    }

    public function test_age_rating_displayed_in_public_film_badge(): void
    {
        $film = Film::create([
            'title' => 'Petualangan Rimba',
            'slug' => 'petualangan-rimba',
            'duration_minutes' => 105,
            'genre' => 'Petualangan',
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis petualangan rimba yang memukau.',
            'is_active' => true,
        ]);

        $response = $this->get(route('films.show', $film->slug));

        $response->assertOk();
        $response->assertSee('13+');
    }
}
