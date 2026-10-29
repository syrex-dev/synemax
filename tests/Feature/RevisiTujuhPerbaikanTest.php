<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Promo;
use App\Models\Session;
use App\Models\User;
use App\Services\CinemaSettings;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RevisiTujuhPerbaikanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-28 10:00:00');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-28 10:00:00', 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_film_multi_genre_canonical_and_filtering(): void
    {
        $film = Film::query()->create([
            'title' => 'Film Petualangan Seru',
            'duration_minutes' => 110,
            'genre' => 'Animasi, Keluarga',
            'age_rating' => 'SU',
            'synopsis' => 'Petualangan animasi keluarga.',
            'release_year' => 2025,
            'is_active' => true,
        ]);

        $this->assertEquals(['Animasi', 'Keluarga'], $film->genresList());
        $this->assertTrue($film->hasGenre('Animasi'));
        $this->assertTrue($film->hasGenre('Keluarga'));
        $this->assertFalse($film->hasGenre('Horor'));

        // Filter by single canonical genre
        $response = $this->get(route('films.index', ['genre' => 'Keluarga']));
        $response->assertOk();
        $response->assertSee('Film Petualangan Seru');

        $responseNotMatched = $this->get(route('films.index', ['genre' => 'Horor']));
        $responseNotMatched->assertOk();
        $responseNotMatched->assertDontSee('Film Petualangan Seru');
    }

    public function test_filter_genre_and_year_combination(): void
    {
        $film2025 = Film::query()->create([
            'title' => 'Komedi Modern 2025',
            'duration_minutes' => 95,
            'genre' => 'Komedi',
            'age_rating' => '13+',
            'synopsis' => 'Lucu sekali.',
            'release_year' => 2025,
            'is_active' => true,
        ]);

        $film2024 = Film::query()->create([
            'title' => 'Komedi Lawas 2024',
            'duration_minutes' => 95,
            'genre' => 'Komedi',
            'age_rating' => '13+',
            'synopsis' => 'Lucu juga.',
            'release_year' => 2024,
            'is_active' => true,
        ]);

        $response = $this->get(route('films.index', ['genre' => 'Komedi', 'tahun' => 2025]));
        $response->assertOk();
        $response->assertSee('Komedi Modern 2025');
        $response->assertDontSee('Komedi Lawas 2024');
    }

    public function test_expired_promo_does_not_display_on_home_or_promos(): void
    {
        $activePromo = Promo::query()->create([
            'title' => 'Promo Diskon Pelajar Aktif',
            'slug' => 'promo-diskon-pelajar-aktif',
            'description' => 'Diskon khusus.',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'is_active' => true,
        ]);

        $expiredPromo = Promo::query()->create([
            'title' => 'Promo Lebaran Telah Lewat',
            'slug' => 'promo-lebaran-telah-lewat',
            'description' => 'Sudah hangus.',
            'start_date' => now()->subDays(20)->toDateString(),
            'end_date' => now()->subDays(1)->toDateString(),
            'is_active' => true,
        ]);

        $home = $this->get(route('home'));
        $home->assertOk();
        $home->assertSee('Promo Diskon Pelajar Aktif');
        $home->assertDontSee('Promo Lebaran Telah Lewat');

        $promosPage = $this->get(route('promos.index'));
        $promosPage->assertOk();
        $promosPage->assertSee('Promo Diskon Pelajar Aktif');
        $promosPage->assertSee('Promo Telah Berakhir');
    }

    public function test_dashboard_reading_settings_and_low_attendance_tabs_urgency(): void
    {
        $settings = app(CinemaSettings::class);
        $settings->setMany([
            'minimum_public_viewers' => 3,
            'low_attendance_warning_window' => 72,
        ]);

        $this->assertEquals(3, $settings->minimumPublicViewers());
        $this->assertEquals(72, $settings->lowAttendanceWarningWindowHours());

        $admin = User::factory()->create();
        $film = Film::query()->create([
            'title' => 'Film Sesi Uji',
            'duration_minutes' => 120,
            'genre' => 'Aksi',
            'age_rating' => '13+',
            'synopsis' => 'Uji sesi.',
            'is_active' => true,
        ]);

        // Session 1: Today, 0 viewers (urgent)
        $sessionToday = Session::query()->create([
            'session_date' => '2026-10-28',
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'film_id' => $film->id,
            'capacity' => 6,
            'is_private' => false,
            'status' => 'tersedia',
        ]);

        // Session 2: Tomorrow, 0 viewers
        $sessionTomorrow = Session::query()->create([
            'session_date' => '2026-10-29',
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'film_id' => $film->id,
            'capacity' => 6,
            'is_private' => false,
            'status' => 'tersedia',
        ]);

        // Session 3: Distant session (in 5 days > 72 hours), 0 viewers
        $sessionDistant = Session::query()->create([
            'session_date' => '2026-11-03',
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'film_id' => $film->id,
            'capacity' => 6,
            'is_private' => false,
            'status' => 'tersedia',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertSee('Minimal 3 penonton per sesi');
        // Distant session should be counted as distant, not shown as urgent card
        $response->assertSee('sesi di luar jendela 72 jam belum mencapai kuota');
    }

    public function test_hubungi_pemesan_button_only_visible_when_bookings_exist(): void
    {
        $admin = User::factory()->create();
        $film = Film::query()->create([
            'title' => 'Film Drama Uji Coba',
            'duration_minutes' => 90,
            'genre' => 'Drama',
            'age_rating' => '13+',
            'synopsis' => 'Uji hubungi pemesan.',
            'is_active' => true,
        ]);

        $session = Session::query()->create([
            'session_date' => '2026-10-28',
            'start_time' => '20:00:00',
            'end_time' => '21:30:00',
            'film_id' => $film->id,
            'capacity' => 6,
            'is_private' => false,
            'status' => 'tersedia',
        ]);

        // Without any booking, Hubungi Pemesan button should NOT be rendered
        $responseNoBooking = $this->actingAs($admin)->get(route('admin.dashboard'));
        $responseNoBooking->assertOk();
        $responseNoBooking->assertDontSee('Hubungi Pemesan');

        // With a booking (1 viewer, still < 3 min viewers)
        Booking::query()->create([
            'code' => 'BOOK123',
            'session_id' => $session->id,
            'film_id' => $film->id,
            'customer_name' => 'Budi Santoso',
            'whatsapp' => '08123456789',
            'pax' => 1,
            'seats_reserved' => 1,
            'total_price' => 20000,
            'booking_type' => 'per_orang',
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $responseWithBooking = $this->actingAs($admin)->get(route('admin.dashboard'));
        $responseWithBooking->assertOk();
        $responseWithBooking->assertSee('Hubungi Pemesan');
    }

    public function test_admin_uses_indonesian_date_and_time_format(): void
    {
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();

        // Verify Indonesian day and month names are present, not ISO format
        $response->assertSee('Rabu');
        $response->assertSee('Oktober');
    }
}
