<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Session;
use App\Models\Setting;
use App\Services\CinemaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevisiPenutupTest extends TestCase
{
    use RefreshDatabase;

    private function createSampleBooking(string $code = 'HC-261014-A7K2'): Booking
    {
        if (! Setting::where('key', 'admin_whatsapp')->exists()) {
            Setting::updateOrCreate(['key' => 'admin_whatsapp'], ['value' => '628123456789']);
        }

        $film = Film::create([
            'title' => 'Film Uji Penutup',
            'slug' => 'film-uji-penutup',
            'duration_minutes' => 95,
            'age_rating' => '13+',
            'genre' => 'Petualangan',
            'synopsis' => 'Sinopsis film uji penutup',
            'is_active' => true,
        ]);

        $session = Session::create([
            'film_id' => $film->id,
            'session_date' => '2026-10-31',
            'start_time' => '19:00:00',
            'end_time' => '20:35:00',
            'capacity' => 6,
            'status' => 'tersedia',
            'confirmed_at' => now(),
            'confirmation_method' => 'admin',
        ]);

        return Booking::create([
            'session_id' => $session->id,
            'film_id' => $film->id,
            'code' => $code,
            'customer_name' => 'Ahmad Penutup',
            'customer_phone' => '085169730960',
            'whatsapp' => '6285169730960',
            'seats_reserved' => 2,
            'seat_price' => 5000,
            'ticket_total' => 10000,
            'total_price' => 10000,
            'status' => 'terkonfirmasi',
            'payment_method' => 'qris',
            'package_type' => 'reguler',
            'pax' => 2,
        ]);
    }

    public function test_phone_format_helper_formats_indonesian_phone_numbers_correctly(): void
    {
        $this->assertSame('+62 851-6973-0960', format_phone_id('6285169730960'));
        $this->assertSame('+62 851-6973-0960', format_phone_id('085169730960'));
        $this->assertSame('+62 812-3456-7890', format_phone_id('+62 812-3456-7890'));
        $this->assertSame('-', format_phone_id(null));
        $this->assertSame('-', format_phone_id(''));
    }

    public function test_cinema_facts_helpers_are_synchronized(): void
    {
        Setting::updateOrCreate(['key' => 'capacity_per_session'], ['value' => '6']);
        Setting::updateOrCreate(['key' => 'seat_price_regular'], ['value' => '5000']);

        $this->assertSame('6 kursi per sesi', cinema_capacity_text());
        $this->assertSame('Mulai Rp 5.000', cinema_starting_price_text());

        $facts = cinema_facts();
        $this->assertSame('6 kursi per sesi', $facts['capacity']);
        $this->assertSame('Mulai Rp 5.000', $facts['price']);
        $this->assertSame('6 kursi per sesi • Mulai Rp 5.000', $facts['combined']);
        $this->assertSame('6 kursi per sesi &bull; Mulai Rp 5.000', $facts['combined_html']);
    }

    public function test_formatted_footer_text_renders_single_line_without_duplicate_copyright(): void
    {
        $settings = app(CinemaSettings::class);

        // When custom text already has ©
        $settings->set('footer_text', '© 2026 SynemaX Cinema. All rights reserved.');
        $rendered = $settings->formattedFooterText();
        $this->assertSame('© 2026 SynemaX Cinema. All rights reserved.', $rendered);
        $this->assertSame(1, substr_count($rendered, '©'));

        // When custom text uses {tahun} token
        $settings->set('footer_text', '© {tahun} SynemaX Cinema. Hak cipta dilindungi.');
        $rendered2 = $settings->formattedFooterText();
        $this->assertSame('© '.date('Y').' SynemaX Cinema. Hak cipta dilindungi.', $rendered2);

        // When setting is empty fallback
        $settings->set('footer_text', '');
        $rendered3 = $settings->formattedFooterText();
        $this->assertSame('© '.date('Y').' SynemaX. Hak cipta dilindungi.', $rendered3);
    }

    public function test_qr_code_endpoint_returns_svg_for_valid_registered_booking(): void
    {
        $booking = $this->createSampleBooking('HC-261014-A7K2');

        $response = $this->get(route('qr.code', ['data' => $booking->code, 'size' => 150]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<svg', $response->getContent());
    }

    public function test_qr_code_endpoint_rejects_free_text_arbitrary_urls(): void
    {
        $response = $this->get(route('qr.code', ['data' => 'https://evil-site.test/phishing']));
        $response->assertStatus(422);

        $response2 = $this->get(route('qr.code', ['data' => 'Halo ini teks sembarang']));
        $response2->assertStatus(422);
    }

    public function test_qr_code_endpoint_rejects_nonexistent_booking_code(): void
    {
        $response = $this->get(route('qr.code', ['data' => 'HC-999999-XXXX']));
        $response->assertStatus(404);
    }

    public function test_qr_code_endpoint_rejects_data_exceeding_32_characters(): void
    {
        $response = $this->get(route('qr.code', ['data' => str_repeat('A', 33)]));
        $response->assertStatus(422);
    }

    public function test_public_pages_contain_meta_color_scheme_and_theme_color(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('name="color-scheme"', false);
        $response->assertSee('name="theme-color"', false);
        $response->assertSee('id="theme-color-meta"', false);
    }

    public function test_canonical_contact_email_is_used_consistently_across_views(): void
    {
        $settings = app(CinemaSettings::class);
        $settings->set('contact_email', 'resmi@synemax.test', 'kontak');

        $this->assertSame('resmi@synemax.test', $settings->contactEmail());

        $homeResponse = $this->get('/');
        $homeResponse->assertOk();
        $homeResponse->assertSee('resmi@synemax.test');
        $homeResponse->assertSee('mailto:resmi@synemax.test');

        $booking = $this->createSampleBooking('HC-261014-EML1');
        $ticketResponse = $this->followingRedirects()->get(route('booking.ticket', ['code' => $booking->code, 'wa' => $booking->whatsapp]));
        $ticketResponse->assertOk();
        $ticketResponse->assertSee('resmi@synemax.test');
    }

    public function test_views_format_whatsapp_using_helper_and_wa_me_uses_raw_digits(): void
    {
        Setting::updateOrCreate(['key' => 'admin_whatsapp'], ['value' => '6285169730960']);
        $booking = $this->createSampleBooking('HC-261014-WA01');

        $ticketResponse = $this->followingRedirects()->get(route('booking.ticket', ['code' => $booking->code, 'wa' => $booking->whatsapp]));
        $ticketResponse->assertOk();

        // Displayed text must use Indonesian format helper
        $ticketResponse->assertSee('+62 851-6973-0960');

        // External wa.me links must use raw digit string
        $ticketResponse->assertSee('wa.me/6285169730960');
    }

    public function test_additive_migration_copies_cinema_email_if_contact_email_empty(): void
    {
        Setting::where('key', 'contact_email')->delete();
        Setting::updateOrCreate(['key' => 'cinema_email'], ['value' => 'warisan@synemax.test', 'group' => 'kontak']);

        $migration = require database_path('migrations/2026_10_31_000001_standardize_contact_email_canonical_key.php');
        $migration->up();

        $copied = Setting::where('key', 'contact_email')->value('value');
        $this->assertSame('warisan@synemax.test', $copied);
    }

    public function test_theme_script_reacts_without_inline_hex_styles(): void
    {
        $response = $this->get('/');
        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringNotContainsString('docEl.style.backgroundColor', $content);
        $this->assertStringNotContainsString('document.body.style.backgroundColor', $content);
        $this->assertStringContainsString('color-scheme-meta', $content);
        $this->assertStringContainsString('theme-color-meta', $content);
        $this->assertStringContainsString('prefers-color-scheme', $content);
    }
}
