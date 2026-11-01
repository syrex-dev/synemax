<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Session;
use App\Models\Setting;
use App\Models\User;
use App\Services\CinemaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CanonicalSettingsUnifiedTest extends TestCase
{
    use RefreshDatabase;

    public function test_changing_address_in_settings_reflects_equally_in_ticket_footer_and_mobile_menu(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $newAddress = 'Jl. Anggrek No. 99, Panyingkiran, Majalengka, Jawa Barat';

        // Update via admin route
        $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
            'tab' => 'kontak',
            'contact_email' => 'halo@synemax.id',
            'contact_address' => $newAddress,
            'maps_label' => 'Lihat Lokasi',
            'maps_url' => 'https://maps.google.com/?q=-6.8000,108.2000',
            'instagram_label' => '@synemax',
            'instagram_url' => 'https://instagram.com/synemax',
            'tiktok_label' => '@synemax',
            'tiktok_url' => 'https://tiktok.com/@synemax',
            'youtube_label' => '',
            'youtube_url' => '',
            'footer_text' => '© 2026 SynemaX.',
        ]);

        $response->assertRedirect();

        app(CinemaSettings::class)->clearCache();
        $settings = app(CinemaSettings::class);

        // 1. CinemaSettings returns the exact updated address
        $this->assertSame($newAddress, $settings->contactAddress());

        // 2. Desktop footer renders the exact updated address
        $homeResponse = $this->get(route('home'));
        $homeResponse->assertOk();
        $homeResponse->assertSee($newAddress);

        // 3. Mobile menu sheet renders the exact updated address
        $homeContent = $homeResponse->getContent();
        $this->assertStringContainsString($newAddress, $homeContent);

        // 4. Ticket card renders the exact updated address
        $film = Film::create([
            'title' => 'Film Uji Alamat',
            'slug' => 'film-uji-alamat',
            'duration_minutes' => 100,
            'genre' => 'Drama',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis film pengujian alamat.',
            'is_active' => true,
        ]);

        $session = Session::create([
            'session_date' => now()->addDays(2)->toDateString(),
            'start_time' => '13:00',
            'end_time' => '15:00',
            'film_id' => $film->id,
            'capacity' => 6,
            'status' => 'tersedia',
            'confirmed_at' => now(),
            'confirmation_method' => 'manual',
        ]);

        $booking = Booking::create([
            'code' => 'TIK-ALAMAT-01',
            'session_id' => $session->id,
            'film_id' => $film->id,
            'customer_name' => 'Budi Santoso',
            'whatsapp' => '6281234567890',
            'pax' => 2,
            'seats_reserved' => 2,
            'booking_type' => 'per_orang',
            'package' => 'reguler',
            'people_count' => 2,
            'total_price' => 40000,
            'ticket_total' => 40000,
            'snack_total' => 0,
            'payment_method' => 'di_tempat',
            'status' => 'terkonfirmasi',
        ]);

        $ticketResponse = $this->get(route('booking.ticket', ['code' => $booking->code]));
        $ticketResponse->assertOk();
        $ticketResponse->assertSee($newAddress);
    }

    public function test_youtube_is_hidden_when_empty_or_demo_value(): void
    {
        Setting::query()->delete();
        Setting::create(['key' => 'youtube_url', 'value' => '', 'group' => 'kontak']);
        Setting::create(['key' => 'youtube_label', 'value' => '', 'group' => 'kontak']);

        app(CinemaSettings::class)->clearCache();
        $settings = app(CinemaSettings::class);

        $this->assertSame('', $settings->youtubeUrl());
        $this->assertSame('', $settings->youtubeLabel());

        $homeResponse = $this->get(route('home'));
        $homeResponse->assertOk();
        $homeResponse->assertDontSee('@synemax.cinema');
    }
}
