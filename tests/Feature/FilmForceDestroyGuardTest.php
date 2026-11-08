<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FilmForceDestroyGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_force_destroy_rejected_when_film_has_bookings(): void
    {
        $film = Film::query()->create([
            'title' => 'Film Terikat Booking',
            'duration_minutes' => 90,
            'genre' => 'Drama',
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis uji coba.',
            'is_active' => false,
        ]);
        $film->delete(); // Soft delete into trash

        $session = Session::query()->create([
            'session_date' => '2026-11-20',
            'start_time' => '13:00',
            'end_time' => '15:30',
            'status' => 'tersedia',
        ]);

        Booking::query()->create([
            'session_id' => $session->id,
            'film_id' => $film->id,
            'code' => 'BK-REF-001',
            'customer_name' => 'Siti Aisyah',
            'customer_phone' => '081234567891',
            'whatsapp' => '081234567891',
            'pax' => 1,
            'seats_reserved' => 1,
            'status' => 'terkonfirmasi',
            'package' => 'reguler',
            'payment_method' => 'di_tempat',
            'total_price' => 25000,
        ]);

        $response = $this->delete(route('admin.films.force_destroy', $film->id));

        $response->assertRedirect(route('admin.films.index', ['trash' => 1]));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('1 pemesanan', session('error'));

        // Verify film is still preserved in DB (soft-deleted)
        $this->assertDatabaseHas('films', ['id' => $film->id]);
    }

    public function test_force_destroy_rejected_when_film_has_sessions(): void
    {
        $film = Film::query()->create([
            'title' => 'Film Terikat Sesi',
            'duration_minutes' => 100,
            'genre' => 'Aksi',
            'age_rating' => '17+',
            'synopsis' => 'Sinopsis uji coba sesi.',
            'is_active' => false,
        ]);
        $film->delete();

        Session::query()->create([
            'film_id' => $film->id,
            'session_date' => '2026-11-21',
            'start_time' => '16:00',
            'end_time' => '18:30',
            'status' => 'tersedia',
        ]);

        $response = $this->delete(route('admin.films.force_destroy', $film->id));

        $response->assertRedirect(route('admin.films.index', ['trash' => 1]));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('1 sesi tayang', session('error'));

        $this->assertDatabaseHas('films', ['id' => $film->id]);
    }

    public function test_force_destroy_succeeds_and_deletes_unshared_poster(): void
    {
        Storage::disk('public')->put('posters/unshared_poster.webp', 'image_data');

        $film = Film::query()->create([
            'title' => 'Film Tanpa Rujukan',
            'duration_minutes' => 85,
            'genre' => 'Komedi',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis film bebas rujukan.',
            'poster_path' => 'posters/unshared_poster.webp',
            'is_active' => false,
        ]);
        $film->delete();

        $response = $this->delete(route('admin.films.force_destroy', $film->id));

        $response->assertRedirect(route('admin.films.index', ['trash' => 1]));
        $response->assertSessionHas('status');

        // Film permanently deleted
        $this->assertDatabaseMissing('films', ['id' => $film->id]);

        // File permanently deleted
        Storage::disk('public')->assertMissing('posters/unshared_poster.webp');
    }

    public function test_force_destroy_preserves_poster_when_shared_by_another_film(): void
    {
        Storage::disk('public')->put('posters/shared_poster.webp', 'shared_image_data');

        $film1 = Film::query()->create([
            'title' => 'Film Pertama',
            'duration_minutes' => 90,
            'genre' => 'Drama',
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis 1.',
            'poster_path' => 'posters/shared_poster.webp',
            'is_active' => false,
        ]);
        $film1->delete();

        $film2 = Film::query()->create([
            'title' => 'Film Kedua (Pemakai Poster Sama)',
            'duration_minutes' => 95,
            'genre' => 'Drama',
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis 2.',
            'poster_path' => 'posters/shared_poster.webp',
            'is_active' => true,
        ]);

        $response = $this->delete(route('admin.films.force_destroy', $film1->id));

        $response->assertRedirect(route('admin.films.index', ['trash' => 1]));

        // Film 1 deleted, but Film 2 still has poster so file remains
        $this->assertDatabaseMissing('films', ['id' => $film1->id]);
        $this->assertDatabaseHas('films', ['id' => $film2->id]);
        Storage::disk('public')->assertExists('posters/shared_poster.webp');
    }
}
