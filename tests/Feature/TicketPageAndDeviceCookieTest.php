<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Session;
use App\Models\Setting;
use App\Services\CinemaSettings;
use App\Support\DeviceBookingTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketPageAndDeviceCookieTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['key' => 'seats_capacity'], ['value' => '6']);
        Setting::updateOrCreate(['key' => 'minimum_public_viewers'], ['value' => '4']);
        Setting::updateOrCreate(['key' => 'weekday_price'], ['value' => '20000']);
        Setting::updateOrCreate(['key' => 'weekend_price'], ['value' => '24000']);
        Setting::updateOrCreate(['key' => 'payment_methods'], ['value' => 'di_tempat,transfer,qris']);
        app(CinemaSettings::class)->clearCache();
    }

    /**
     * Test /cek-booking 301 redirect to /tiket.
     */
    public function test_cek_booking_redirects_301_to_tiket(): void
    {
        $response = $this->get('/cek-booking');
        $response->assertStatus(301);
        $response->assertRedirect('/tiket');
    }

    /**
     * Test /tiket renders 4 tabs with correct counts and data.
     */
    public function test_tiket_page_renders_four_tabs_with_accurate_counts(): void
    {
        $film = Film::query()->create([
            'title' => 'Film QA Tabs',
            'slug' => 'film-qa-tabs-'.uniqid(),
            'duration_minutes' => 90,
            'genre' => 'Drama',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis',
            'is_active' => true,
        ]);

        $session = Session::query()->create([
            'film_id' => $film->id,
            'session_date' => now()->addDays(2)->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'status' => 'pasti_tayang',
        ]);

        // 1. Belum bayar
        $bBelumBayar = Booking::query()->create([
            'session_id' => $session->id,
            'code' => 'BB'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'User Belum Bayar',
            'whatsapp' => '6281200000001',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'payment_method' => 'qris',
            'status' => 'menunggu_pembayaran',
            'total_price' => 40000,
            'payment_expires_at' => now()->addMinutes(25),
        ]);

        // 2. Diproses
        $bDiproses = Booking::query()->create([
            'session_id' => $session->id,
            'code' => 'DP'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'User Diproses',
            'whatsapp' => '6281200000002',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'payment_method' => 'qris',
            'status' => 'menunggu_konfirmasi',
            'total_price' => 40000,
        ]);

        // 3. Aktif
        $bAktif = Booking::query()->create([
            'session_id' => $session->id,
            'code' => 'AK'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'User Aktif',
            'whatsapp' => '6281200000003',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'payment_method' => 'qris',
            'status' => 'terkonfirmasi',
            'total_price' => 40000,
        ]);

        // 4. Riwayat (cancelled or finished)
        $bRiwayat = Booking::query()->create([
            'session_id' => $session->id,
            'code' => 'RW'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'User Riwayat',
            'whatsapp' => '6281200000004',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'payment_method' => 'qris',
            'status' => 'dibatalkan',
            'total_price' => 40000,
        ]);

        $cookiePayload = json_encode([
            ['code' => $bBelumBayar->code, 'token' => 't1', 'created_at' => time()],
            ['code' => $bDiproses->code, 'token' => 't2', 'created_at' => time()],
            ['code' => $bAktif->code, 'token' => 't3', 'created_at' => time()],
            ['code' => $bRiwayat->code, 'token' => 't4', 'created_at' => time()],
        ]);

        $response = $this->withCookie(DeviceBookingTracker::COOKIE_NAME, $cookiePayload)
            ->get(route('booking.ticket.index'));

        $response->assertOk();
        $response->assertSee('Belum bayar (1)');
        $response->assertSee('Diproses (1)');
        $response->assertSee('Tiket aktif (1)');
        $response->assertSee('Riwayat (1)');

        // First tab with data is belum_bayar
        $response->assertSee('activeTab: \'belum_bayar\'', false);
    }

    /**
     * Test privacy redirect: accessing with ?wa=... verifies access, sets device cookie, and redirects to clean URL.
     */
    public function test_privacy_redirect_strips_whatsapp_query_and_sets_cookie(): void
    {
        $film = Film::query()->create([
            'title' => 'Film QA Privacy',
            'slug' => 'film-qa-privacy-'.uniqid(),
            'duration_minutes' => 90,
            'genre' => 'Drama',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis',
            'is_active' => true,
        ]);

        $session = Session::query()->create([
            'film_id' => $film->id,
            'session_date' => now()->addDays(2)->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'status' => 'pasti_tayang',
        ]);

        $booking = Booking::query()->create([
            'session_id' => $session->id,
            'code' => 'PR'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'Privacy User',
            'whatsapp' => '6281299999999',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'payment_method' => 'qris',
            'status' => 'terkonfirmasi',
            'total_price' => 40000,
        ]);

        // Request with ?wa=081299999999
        $response = $this->get(route('booking.ticket', ['code' => $booking->code, 'wa' => '081299999999']));

        $response->assertStatus(302);
        $response->assertRedirect(route('booking.ticket', ['code' => $booking->code]));
        $this->assertTrue(session('verified_booking_'.$booking->code));
    }

    /**
     * Test signed token access ?t=... verifies access, sets cookie, and redirects to clean URL.
     */
    public function test_signed_token_access_verifies_and_redirects_to_clean_url(): void
    {
        $film = Film::query()->create([
            'title' => 'Film QA Token',
            'slug' => 'film-qa-token-'.uniqid(),
            'duration_minutes' => 90,
            'genre' => 'Drama',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis',
            'is_active' => true,
        ]);

        $session = Session::query()->create([
            'film_id' => $film->id,
            'session_date' => now()->addDays(2)->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'status' => 'pasti_tayang',
        ]);

        $plainToken = Str::random(40);
        $booking = Booking::query()->create([
            'session_id' => $session->id,
            'code' => 'TK'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'Token User',
            'whatsapp' => '6281288888888',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'payment_method' => 'qris',
            'status' => 'terkonfirmasi',
            'total_price' => 40000,
            'access_token_hash' => hash('sha256', $plainToken),
        ]);

        // Request with ?t=...
        $response = $this->get(route('booking.ticket', ['code' => $booking->code, 't' => $plainToken]));

        $response->assertStatus(302);
        $response->assertRedirect(route('booking.ticket', ['code' => $booking->code]));
        $this->assertTrue(session('verified_booking_'.$booking->code));
    }

    /**
     * Test Referrer-Policy: strict-origin-when-cross-origin on ticket and payment views.
     */
    public function test_referrer_policy_header_enforced_on_ticket_and_payment(): void
    {
        $film = Film::query()->create([
            'title' => 'Film QA Referrer',
            'slug' => 'film-qa-referrer-'.uniqid(),
            'duration_minutes' => 90,
            'genre' => 'Drama',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis',
            'is_active' => true,
        ]);

        $session = Session::query()->create([
            'film_id' => $film->id,
            'session_date' => now()->addDays(2)->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'status' => 'pasti_tayang',
        ]);

        $booking = Booking::query()->create([
            'session_id' => $session->id,
            'code' => 'RF'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'Referrer User',
            'whatsapp' => '6281277777777',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'payment_method' => 'qris',
            'status' => 'menunggu_pembayaran',
            'total_price' => 40000,
            'payment_expires_at' => now()->addMinutes(25),
        ]);

        // Payment view
        $pResponse = $this->withSession(['verified_booking_'.$booking->code => true])
            ->get(route('booking.payment', ['code' => $booking->code]));
        $pResponse->assertOk();
        $pResponse->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Ticket view (mark confirmed)
        $booking->update(['status' => 'terkonfirmasi']);
        $tResponse = $this->withSession(['verified_booking_'.$booking->code => true])
            ->get(route('booking.ticket', ['code' => $booking->code]));
        $tResponse->assertOk();
        $tResponse->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_ticket_index_cards_render_linked_poster_image_and_urls_across_tabs(): void
    {
        $film = Film::query()->create([
            'title' => 'Film Poster Linked Test',
            'slug' => 'film-poster-linked-test',
            'duration_minutes' => 90,
            'genre' => 'Drama',
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis',
            'poster_path' => 'https://img.synemax.test/posters/test-poster.jpg',
            'is_active' => true,
        ]);

        $session = Session::query()->create([
            'film_id' => $film->id,
            'session_date' => now()->addDays(2)->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'status' => 'pasti_tayang',
        ]);

        $booking = Booking::query()->create([
            'session_id' => $session->id,
            'code' => 'HC-TEST-POSTER-LINK',
            'customer_name' => 'User Poster Link',
            'whatsapp' => '6281299998888',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'payment_method' => 'qris',
            'status' => 'terkonfirmasi',
            'total_price' => 40000,
        ]);

        $cookiePayload = json_encode([
            ['code' => $booking->code, 'token' => 'tok-test', 'created_at' => time()],
        ]);

        $response = $this->withCookie(DeviceBookingTracker::COOKIE_NAME, $cookiePayload)
            ->get('/tiket');

        $response->assertOk();
        // The image src should link to the posterUrl
        $response->assertSee('https://img.synemax.test/posters/test-poster.jpg');
        // The poster container and title must link to the ticket view
        $expectedTicketUrl = route('booking.ticket', ['code' => $booking->code]);
        $response->assertSee('href="'.$expectedTicketUrl.'"', false);
    }

    public function test_all_links_in_four_ticket_tabs_and_bayar_redirect_resolve_without_404(): void
    {
        $film = Film::query()->create([
            'title' => 'Film Tabs Test',
            'slug' => 'film-tabs-test',
            'duration_minutes' => 90,
            'genre' => 'Action',
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis',
            'poster_path' => 'https://img.synemax.test/posters/test.jpg',
            'is_active' => true,
        ]);

        $session = Session::query()->create([
            'film_id' => $film->id,
            'session_date' => now()->addDays(2)->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'status' => 'pasti_tayang',
        ]);

        $bBelumBayar = Booking::query()->create([
            'session_id' => $session->id,
            'code' => 'HC-TAB-UNPAID',
            'customer_name' => 'User Unpaid',
            'whatsapp' => '6281200000001',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'payment_method' => 'qris',
            'status' => 'menunggu_pembayaran',
            'total_price' => 20000,
            'payment_expires_at' => now()->addMinutes(15),
        ]);

        $bDiproses = Booking::query()->create([
            'session_id' => $session->id,
            'code' => 'HC-TAB-PROCESS',
            'customer_name' => 'User Process',
            'whatsapp' => '6281200000002',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'payment_method' => 'qris',
            'status' => 'menunggu_konfirmasi',
            'total_price' => 20000,
        ]);

        $bAktif = Booking::query()->create([
            'session_id' => $session->id,
            'code' => 'HC-TAB-ACTIVE',
            'customer_name' => 'User Active',
            'whatsapp' => '6281200000003',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'payment_method' => 'qris',
            'status' => 'terkonfirmasi',
            'total_price' => 20000,
        ]);

        $bRiwayat = Booking::query()->create([
            'session_id' => $session->id,
            'code' => 'HC-TAB-HISTORY',
            'customer_name' => 'User History',
            'whatsapp' => '6281200000004',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'payment_method' => 'qris',
            'status' => 'selesai',
            'total_price' => 20000,
        ]);

        $cookiePayload = json_encode([
            ['code' => $bBelumBayar->code, 'token' => 'tok-1', 'created_at' => time()],
            ['code' => $bDiproses->code, 'token' => 'tok-2', 'created_at' => time()],
            ['code' => $bAktif->code, 'token' => 'tok-3', 'created_at' => time()],
            ['code' => $bRiwayat->code, 'token' => 'tok-4', 'created_at' => time()],
        ]);

        $response = $this->withCookie(DeviceBookingTracker::COOKIE_NAME, $cookiePayload)->get('/tiket');
        $response->assertOk();

        // Test 301 redirect for legacy /bayar/{code}
        $legacyRedirect = $this->get('/bayar/'.$bBelumBayar->code);
        $legacyRedirect->assertStatus(301);
        $legacyRedirect->assertRedirect(route('booking.payment', ['code' => $bBelumBayar->code]));

        // Test all tab links on /tiket
        $paymentUrl = route('booking.payment', ['code' => $bBelumBayar->code]);
        $this->get($paymentUrl)->assertOk();

        $ticketProcessUrl = route('booking.ticket', ['code' => $bDiproses->code]);
        $this->get($ticketProcessUrl)->assertOk();

        $ticketActiveUrl = route('booking.ticket', ['code' => $bAktif->code]);
        $this->get($ticketActiveUrl)->assertOk();

        $ticketHistoryUrl = route('booking.ticket', ['code' => $bRiwayat->code]);
        $this->get($ticketHistoryUrl)->assertOk();
    }
}
