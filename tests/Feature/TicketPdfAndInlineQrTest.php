<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Session;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketPdfAndInlineQrTest extends TestCase
{
    use RefreshDatabase;

    private Film $film;

    private Session $session;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['key' => 'admin_whatsapp'], ['value' => '628123456789']);

        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->film = Film::create([
            'title' => 'Film Uji Tiket PDF',
            'slug' => 'film-uji-tiket-pdf',
            'duration_minutes' => 120,
            'age_rating' => '13+',
            'genre' => 'Aksi',
            'synopsis' => 'Sinopsis uji tiket',
            'is_active' => true,
            'poster_path' => null, // Test plain color poster
        ]);

        $this->session = Session::create([
            'film_id' => $this->film->id,
            'session_date' => now()->addDay()->toDateString(),
            'start_time' => '19:00:00',
            'end_time' => '21:00:00',
            'capacity' => 6,
            'status' => 'tersedia',
            'confirmed_at' => now(),
            'confirmation_method' => 'admin',
        ]);
    }

    public function test_generate_inline_qr_svg_returns_valid_svg_without_xml_declaration(): void
    {
        $code = 'HC-261101-TEST12';
        $svg = generate_inline_qr_svg($code, 150, 1);

        $this->assertNotEmpty($svg);
        $this->assertStringStartsWith('<svg', trim($svg));
        $this->assertStringContainsString('</svg>', $svg);
        $this->assertStringNotContainsString('<?xml', $svg);
    }

    public function test_ticket_renders_inline_svg_qr_directly_without_img_tag(): void
    {
        $booking = Booking::create([
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'code' => 'TKT-TEST-QR01',
            'customer_name' => 'Doni Salmanan',
            'whatsapp' => '6281234567891',
            'seats_reserved' => 2,
            'pax' => 2,
            'ticket_total' => 50000,
            'total_price' => 50000,
            'status' => 'terkonfirmasi',
            'payment_method' => 'qris',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.ticket', $booking));
        $response->assertOk();

        // Must contain inline SVG
        $response->assertSee('<svg xmlns="http://www.w3.org/2000/svg"', false);
        // Must NOT use external/local HTTP image route for QR
        $response->assertDontSee('/qr-code?', false);
    }

    public function test_payment_status_shows_bayar_di_tempat_before_confirmation_and_lunas_after(): void
    {
        // 1. Before admin confirmation (status: menunggu_konfirmasi)
        $booking = Booking::create([
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'code' => 'TKT-COD-001',
            'customer_name' => 'Rahmat Hidayat',
            'whatsapp' => '6281234567892',
            'seats_reserved' => 2,
            'pax' => 2,
            'ticket_total' => 50000,
            'total_price' => 50000,
            'status' => 'menunggu_konfirmasi',
            'payment_method' => 'di_tempat',
        ]);

        $responseBefore = $this->actingAs($this->admin)->get(route('admin.bookings.ticket', $booking));
        $responseBefore->assertOk();
        $responseBefore->assertSee('Bayar di tempat');
        $responseBefore->assertDontSee('Lunas');

        // 2. After admin confirmation (status: terkonfirmasi)
        $booking->update(['status' => 'terkonfirmasi']);

        $responseAfter = $this->actingAs($this->admin)->get(route('admin.bookings.ticket', $booking));
        $responseAfter->assertOk();
        $responseAfter->assertSee('Lunas');
    }

    public function test_snack_items_limited_to_max_3_rows_with_overflow_label(): void
    {
        $category = MenuCategory::create([
            'name' => 'Snacks',
            'slug' => 'snacks-test',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $booking = Booking::create([
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'code' => 'TKT-SNACK-6',
            'customer_name' => 'Bambang Soesatyo',
            'whatsapp' => '6281234567893',
            'seats_reserved' => 2,
            'pax' => 2,
            'ticket_total' => 50000,
            'total_price' => 110000,
            'status' => 'terkonfirmasi',
            'payment_method' => 'qris',
        ]);

        // Add 6 snack items
        for ($i = 1; $i <= 6; $i++) {
            $menu = MenuItem::create([
                'category_id' => $category->id,
                'name' => "Camilan {$i}",
                'slug' => "camilan-{$i}",
                'price' => 10000,
                'cost_price' => 5000,
                'stock_mode' => 'unlimited',
                'is_active' => true,
                'sort_order' => $i,
            ]);

            $booking->items()->create([
                'menu_item_id' => $menu->id,
                'name_snapshot' => "Camilan {$i}",
                'price_snapshot' => 10000,
                'cost_snapshot' => 5000,
                'qty' => 1,
                'status' => 'baru',
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.ticket', $booking));
        $response->assertOk();

        // Must see items 1, 2, 3
        $response->assertSee('Camilan 1');
        $response->assertSee('Camilan 2');
        $response->assertSee('Camilan 3');

        // Items 4, 5, 6 must NOT be printed as full rows
        $response->assertDontSee('Camilan 4 ×');
        $response->assertDontSee('Camilan 5 ×');
        $response->assertDontSee('Camilan 6 ×');

        // Overflow label "+3 item jajan lainnya" must be visible
        $response->assertSee('+3 item jajan lainnya');
    }

    public function test_mini_poster_has_no_placeholder_text_when_poster_is_null(): void
    {
        $this->assertNull($this->film->poster_path);

        $booking = Booking::create([
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'code' => 'TKT-NO-POSTER',
            'customer_name' => 'Clara Shinta',
            'whatsapp' => '6281234567894',
            'seats_reserved' => 1,
            'pax' => 1,
            'ticket_total' => 25000,
            'total_price' => 25000,
            'status' => 'terkonfirmasi',
            'payment_method' => 'qris',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.ticket', $booking));
        $response->assertOk();

        // Plain color div should be present
        $response->assertSee('bg-slate-800 shrink-0');
        // No placeholder text
        $response->assertDontSee('No Poster');
        $response->assertDontSee('Tidak ada poster');
    }
}
