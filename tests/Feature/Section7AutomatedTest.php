<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Session;
use App\Models\Setting;
use App\Services\CinemaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Section7AutomatedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 7c.1 to 7c.5: Comprehensive Section 7 verification.
     */
    public function test_section7_visual_footer_theme_and_contact_consistency(): void
    {
        // 1. Setup canonical settings
        Setting::updateOrCreate(['key' => 'site_name'], ['value' => 'SynemaX']);
        Setting::updateOrCreate(['key' => 'contact_email'], ['value' => 'kontak@synemax.test']);
        Setting::updateOrCreate(['key' => 'admin_whatsapp'], ['value' => '081234567890']);

        // 2. WhatsApp formatting verification
        require_once app_path('helpers.php');
        $formatted1 = format_phone_id('081234567890');
        $this->assertEquals('+62 812-3456-7890', $formatted1);
        $this->assertStringNotContainsString('!', $formatted1);

        $formatted2 = format_phone_id('6285169730960');
        $this->assertEquals('+62 851-6973-0960', $formatted2);
        $this->assertStringNotContainsString('!', $formatted2);

        // 3. Email consistency with CinemaSettings
        $settings = app(CinemaSettings::class);
        $this->assertEquals('kontak@synemax.test', $settings->contactEmail());

        // 4. Request Homepage
        $response = $this->get('/');
        $response->assertStatus(200);
        $html = $response->getContent();

        // Footer is scoped to desktop (hidden md:block)
        $this->assertMatchesRegularExpression('/<footer[^>]*class="[^"]*hidden\s+md:block[^"]*"/', $html);
        $response->assertSee('Jelajahi', false);
        $response->assertSee('Bantuan', false);
        $response->assertSee('Hubungi & Ikuti', false);

        // Mobile bottom bar is rendered with 5 navigation items
        $this->assertStringContainsString('id="mobile-bottom-bar"', $html);
        $this->assertStringContainsString('Beranda', $html);
        $this->assertStringContainsString('Jadwal', $html);
        $this->assertStringContainsString('Film', $html);
        $this->assertStringContainsString('Cek booking', $html);
        $this->assertStringContainsString('Menu', $html);

        // Email rendered in footer
        $response->assertSee('mailto:kontak@synemax.test', false);
        $response->assertSee('kontak@synemax.test', false);

        // Theme switch meta and zero-flicker script
        $this->assertStringContainsString('<meta name="theme-color" id="theme-color-meta"', $html);
        $this->assertStringContainsString('window.synemaXTheme', $html);
        $this->assertStringContainsString('applyMode', $html);
        $this->assertStringContainsString('#07090E', $html);
        $this->assertStringContainsString('#FFFFFF', $html);

        // 5. Ticket Card Email Consistency
        $film = Film::create([
            'title' => 'Film Pengujian Email',
            'slug' => 'film-pengujian-email',
            'synopsis' => 'Sinopsis film pengujian email.',
            'duration_minutes' => 100,
            'age_rating' => '13+',
            'genre' => 'Aksi',
            'is_active' => true,
        ]);

        $session = Session::create([
            'film_id' => $film->id,
            'session_date' => now()->addDays(2)->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'price' => 35000,
            'is_active' => true,
        ]);

        $booking = Booking::create([
            'code' => 'SYN-EMAIL-TEST',
            'session_id' => $session->id,
            'customer_name' => 'Budi Santoso',
            'whatsapp' => '081234567890',
            'pax' => 2,
            'total_price' => 70000,
            'status' => 'lunas',
            'payment_method' => 'qris',
            'booking_type' => 'regular',
        ]);

        $ticketView = view('components.ticket.card', [
            'booking' => $booking,
            'film' => $film,
            'session' => $session,
        ])->render();

        $this->assertStringContainsString('• Email: kontak@synemax.test', $ticketView);
        $this->assertStringContainsString('• Bantuan WhatsApp: +62 812-3456-7890', $ticketView);
    }
}
