<?php

namespace Tests\Feature;

use App\Models\Film;
use App\Models\Session;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-13 10:00:00', 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_homepage_lists_movies_and_the_next_seven_days(): void
    {
        Film::query()->create([
            'title' => 'Malam di Bukit Bintang',
            'duration_minutes' => 105,
            'genre' => 'Drama keluarga',
            'age_rating' => 'SU',
            'synopsis' => 'Cerita keluarga yang pulang ke desa.',
            'is_active' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Film tersedia')
            ->assertSee('Malam di Bukit Bintang')
            ->assertSee('Jadwal 7 hari ke depan');
    }

    public function test_schedule_and_booking_pages_render_availability_and_locked_film(): void
    {
        $film = Film::query()->create([
            'title' => 'Misi Terakhir Pak Pos',
            'duration_minutes' => 98,
            'genre' => 'Komedi petualangan',
            'age_rating' => 'SU',
            'synopsis' => 'Petualangan surat terakhir.',
            'is_active' => true,
        ]);
        $session = Session::query()->create([
            'session_date' => '2026-10-14',
            'start_time' => '16:00',
            'end_time' => '18:30',
            'film_id' => $film->id,
            'capacity' => 6,
            'status' => 'tersedia',
        ]);

        $this->get(route('schedule.date', ['date' => '2026-10-14']))
            ->assertOk()
            ->assertSee('Tersedia')
            ->assertSee('Misi Terakhir Pak Pos');

        $this->get(route('booking.create', ['session' => $session->id]))
            ->assertOk()
            ->assertSee('Amankan kursimu')
            ->assertSee('Misi Terakhir Pak Pos')
            ->assertSee('Sewa Private');
    }

    public function test_booking_success_page_renders_the_wa_confirmation_link(): void
    {
        Setting::query()->create(['key' => 'admin_whatsapp', 'value' => '6280000000000']);

        $film = Film::query()->create([
            'title' => 'Malam di Bukit Bintang',
            'duration_minutes' => 105,
            'genre' => 'Drama keluarga',
            'age_rating' => 'SU',
            'synopsis' => 'Cerita keluarga yang pulang ke desa.',
            'is_active' => true,
        ]);
        $session = Session::query()->create([
            'session_date' => '2026-10-14',
            'start_time' => '16:00',
            'end_time' => '18:30',
            'capacity' => 6,
            'status' => 'tersedia',
        ]);

        $response = $this->post(route('booking.store', ['session' => $session->id]), [
            'customer_name' => 'Dewi',
            'whatsapp' => '081234567890',
            'pax' => 2,
            'booking_type' => 'per_orang',
            'film_id' => $film->id,
            'payment_method' => 'di_tempat',
        ]);

        $response->assertRedirect();
        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('Konfirmasi via WhatsApp')
            ->assertSee('wa.me/6280000000000', false)
            ->assertSee('Malam di Bukit Bintang');
    }
}
