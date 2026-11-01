<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Booking;
use App\Models\Film;
use App\Models\Promo;
use App\Models\Session;
use App\Models\User;
use App\Services\CinemaSettings;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevisiBagianTigaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Film $film;

    private Session $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->film = Film::query()->create([
            'title' => 'Dune Part Three',
            'slug' => 'dune-part-three',
            'duration_minutes' => 120,
            'genre' => 'Sci-Fi',
            'release_year' => 2026,
            'age_rating' => '13+',
            'synopsis' => 'Kelanjutan epik padang pasir Arrakis.',
            'is_active' => true,
            'is_sample' => true,
        ]);

        $this->session = Session::query()->create([
            'session_date' => CarbonImmutable::now('Asia/Jakarta')->addDay()->toDateString(),
            'start_time' => '19:00:00',
            'end_time' => '21:00:00',
            'film_id' => $this->film->id,
            'capacity' => 6,
            'is_private' => false,
            'status' => 'tersedia',
            'confirmed_at' => CarbonImmutable::now('Asia/Jakarta'),
            'confirmation_method' => 'admin',
        ]);
    }

    /**
     * Bagian 7: Sample data management (is_sample)
     */
    public function test_sample_data_column_and_deletion_route(): void
    {
        $this->assertTrue($this->film->is_sample);

        $samplePromo = Promo::query()->create([
            'title' => '[Contoh] Promo Pembukaan',
            'is_active' => true,
            'is_sample' => true,
        ]);

        $sampleBanner = Banner::query()->create([
            'title' => '[Contoh] Banner Hero',
            'is_active' => true,
            'is_sample' => true,
        ]);

        $this->assertEquals(1, Film::where('is_sample', true)->count());
        $this->assertEquals(1, Promo::where('is_sample', true)->count());
        $this->assertEquals(1, Banner::where('is_sample', true)->count());

        // Hit delete sample data route
        $response = $this->actingAs($this->admin)->post(route('admin.settings.delete_sample'));
        $response->assertRedirect(route('admin.settings.edit', ['tab' => 'seo']));

        $this->assertEquals(0, Film::where('is_sample', true)->count());
        $this->assertEquals(0, Promo::where('is_sample', true)->count());
        $this->assertEquals(0, Banner::where('is_sample', true)->count());
    }

    /**
     * Bagian 8: Frictionless Admin UX - Live search endpoint
     */
    public function test_admin_live_booking_search_endpoint_and_normalization(): void
    {
        $booking = Booking::query()->create([
            'code' => 'SX-202610-ABCD',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Budi Santoso',
            'whatsapp' => '6281234567890',
            'pax' => 2,
            'seats_reserved' => 2,
            'ticket_total' => 10000,
            'total_price' => 10000,
            'status' => 'terkonfirmasi',
            'payment_method' => 'di_tempat',
        ]);

        // Search by last 4 chars
        $res1 = $this->actingAs($this->admin)->getJson(route('admin.bookings.live_search', ['q' => 'ABCD']));
        $res1->assertOk()->assertJsonPath('count', 1);
        $this->assertEquals($booking->code, $res1->json()['results'][0]['code']);

        // Search by phone with prefix
        $res2 = $this->actingAs($this->admin)->getJson(route('admin.bookings.live_search', ['q' => '+6281234567890']));
        $res2->assertOk()->assertJsonPath('count', 1);
        $this->assertEquals($booking->customer_name, $res2->json()['results'][0]['customer_name']);

        // Search by name
        $res3 = $this->actingAs($this->admin)->getJson(route('admin.bookings.live_search', ['q' => 'Budi']));
        $res3->assertOk()->assertJsonPath('count', 1);
    }

    /**
     * Bagian 8: Soft delete and 30-day restore for content models
     */
    public function test_soft_deletes_and_restore_workflow(): void
    {
        $film = Film::query()->create([
            'title' => 'Film Temporer',
            'synopsis' => 'Sinopsis film temporer.',
            'duration_minutes' => 90,
            'genre' => 'Drama',
            'release_year' => 2026,
            'age_rating' => 'SU',
            'is_active' => true,
        ]);

        // Delete film -> soft deletes and flashes undo route
        $delResponse = $this->actingAs($this->admin)->delete(route('admin.films.destroy', $film));
        $delResponse->assertRedirect(route('admin.films.index'));
        $delResponse->assertSessionHas('undo_route', route('admin.films.restore', $film->id));

        $this->assertSoftDeleted('films', ['id' => $film->id]);

        // Restore film
        $restoreResponse = $this->actingAs($this->admin)->post(route('admin.films.restore', $film->id));
        $restoreResponse->assertRedirect(route('admin.films.index'));

        $this->assertDatabaseHas('films', [
            'id' => $film->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Bagian 10: Printable E-Ticket layout, QR code, and status rules
     */
    public function test_printable_e_ticket_layout_and_qr_content(): void
    {
        $booking = Booking::query()->create([
            'code' => 'SX-TICKET-TEST',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Siti Rahma',
            'whatsapp' => '6281298765432',
            'pax' => 2,
            'seats_reserved' => 2,
            'ticket_total' => 20000,
            'snack_total' => 15000,
            'total_price' => 35000,
            'status' => 'terkonfirmasi',
            'payment_method' => 'qris',
            'payment_proof_path' => 'proofs/test.png',
        ]);

        $booking->items()->create([
            'name_snapshot' => 'Popcorn Karamel',
            'price_snapshot' => 15000,
            'cost_snapshot' => 8000,
            'qty' => 1,
            'status' => 'siap',
        ]);

        $res = $this->withSession(['verified_booking_'.$booking->code => true])
            ->get(route('booking.ticket', ['code' => $booking->code]));
        $res->assertOk();

        // 1. Light theme card styling on both screen & print
        $res->assertSee('print-ticket', false);
        $res->assertSee('bg-white', false);

        // 2. Schedule 2-column grid info
        $res->assertSee('Tanggal & Jam', false);
        $res->assertSee('Studio & Layar', false);
        $res->assertSee('Nama Pemesan', false);
        $res->assertSee('Paket & Kursi', false);
        $res->assertSee('Siti Rahma');

        // 3. Tear notches
        $res->assertSee('tear-notch', false);

        // 4. QR Code contains strictly the booking code in inline SVG
        $res->assertSee('<svg', false);
        $res->assertSee($booking->code);

        // 5. Monospace booking code
        $res->assertSee('font-mono', false);
        $res->assertSee($booking->code);

        // 6. Payment breakdown snapshot
        $res->assertSee('Rp 20.000');
        $res->assertSee('Rp 15.000');
        $res->assertSee('Rp 35.000');

        // 7. CinemaSettings ticket getters
        $res->assertSee(app(CinemaSettings::class)->ticketStudioName());
        $res->assertSee(app(CinemaSettings::class)->ticketArriveMinutes().' menit');
    }

    /**
     * Bagian 10: Status transitions (Cancelled booking has no QR code)
     */
    public function test_cancelled_ticket_shows_cancelled_badge_and_no_active_qr(): void
    {
        $cancelledBooking = Booking::query()->create([
            'code' => 'SX-CANCELLED-01',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Doni Batal',
            'whatsapp' => '6281299990000',
            'pax' => 1,
            'seats_reserved' => 1,
            'ticket_total' => 10000,
            'total_price' => 10000,
            'status' => 'dibatalkan',
            'cancellation_reason' => 'Dibatalkan oleh pemesan',
            'payment_method' => 'di_tempat',
        ]);

        $res = $this->withSession(['verified_booking_'.$cancelledBooking->code => true])
            ->get(route('booking.ticket', ['code' => $cancelledBooking->code]));
        $res->assertOk();

        // Cancelled status displayed
        $res->assertSee('Dibatalkan');
        $res->assertSee('Tiket Dibatalkan');
        $res->assertSee('QR Code tidak lagi aktif untuk masuk studio.');

        // Active QR server URL must not be present
        $res->assertDontSee('api.qrserver.com');
    }
}
