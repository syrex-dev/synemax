<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Session;
use App\Models\Setting;
use App\Services\CinemaSettings;
use App\Support\DeviceBookingTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cookie;
use Tests\TestCase;

class UnpaidBookingRulesAndBannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['key' => 'seats_capacity'], ['value' => '6']);
        Setting::updateOrCreate(['key' => 'minimum_public_viewers'], ['value' => '4']);
        Setting::updateOrCreate(['key' => 'weekday_price'], ['value' => '20000']);
        Setting::updateOrCreate(['key' => 'weekend_price'], ['value' => '24000']);
        Setting::updateOrCreate(['key' => 'enabled_payment_methods'], ['value' => json_encode(['di_tempat', 'transfer', 'qris'])]);
        Setting::updateOrCreate(['key' => 'transfer_accounts'], ['value' => json_encode([
            ['bank_name' => 'BCA', 'account_number' => '1234567890', 'account_holder' => 'SynemaX'],
        ])]);
        Setting::updateOrCreate(['key' => 'payment_time_limit_minutes'], ['value' => '30']);
        app(CinemaSettings::class)->clearCache();
    }

    /**
     * Test max 1 unpaid booking per device rule.
     */
    public function test_max_one_unpaid_booking_per_device_blocked(): void
    {
        $film = Film::query()->create([
            'title' => 'Film QA Unpaid Device',
            'slug' => 'film-qa-unpaid-device-'.uniqid(),
            'duration_minutes' => 90,
            'genre' => 'Aksi',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis',
            'is_active' => true,
        ]);

        $session = Session::query()->create([
            'film_id' => $film->id,
            'session_date' => now()->addDays(2)->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'status' => 'tersedia',
        ]);

        $existingBooking = Booking::query()->create([
            'session_id' => $session->id,
            'code' => 'UP'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'Device User 1',
            'whatsapp' => '6281111111111',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'status' => 'menunggu_pembayaran',
            'total_price' => 40000,
            'payment_expires_at' => now()->addMinutes(30),
        ]);

        // Simulate device cookie with existing booking
        $cookiePayload = json_encode([
            ['code' => $existingBooking->code, 'token' => 'token123', 'created_at' => time()],
        ]);

        $newPayload = [
            'customer_name' => 'Device User 2',
            'whatsapp' => '082222222222', // Different WhatsApp, but same device
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'people_count' => 2,
            'payment_method' => 'di_tempat',
            'film_id' => $film->id,
        ];

        $response = $this->withCookie(DeviceBookingTracker::COOKIE_NAME, $cookiePayload)
            ->post(route('booking.store', ['session' => $session->id]), $newPayload);

        $response->assertSessionHasErrors(['session']);
        $this->assertStringContainsString('belum diselesaikan pembayarannya', session('errors')->first('session'));
    }

    /**
     * Test max 1 unpaid booking per WhatsApp rule.
     */
    public function test_max_one_unpaid_booking_per_whatsapp_blocked(): void
    {
        $film = Film::query()->create([
            'title' => 'Film QA Unpaid WA',
            'slug' => 'film-qa-unpaid-wa-'.uniqid(),
            'duration_minutes' => 90,
            'genre' => 'Aksi',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis',
            'is_active' => true,
        ]);

        $session = Session::query()->create([
            'film_id' => $film->id,
            'session_date' => now()->addDays(2)->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'status' => 'tersedia',
        ]);

        // Existing booking for 6281234567890
        Booking::query()->create([
            'session_id' => $session->id,
            'code' => 'WA'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'WA User',
            'whatsapp' => '6281234567890',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'status' => 'menunggu_pembayaran',
            'total_price' => 40000,
            'payment_expires_at' => now()->addMinutes(30),
        ]);

        // Attempt new booking from a clean device (no cookie) with equivalent WA 081234567890
        $payload = [
            'customer_name' => 'WA User Again',
            'whatsapp' => '081234567890',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'people_count' => 2,
            'payment_method' => 'di_tempat',
            'film_id' => $film->id,
        ];

        $response = $this->post(route('booking.store', ['session' => $session->id]), $payload);

        $response->assertSessionHasErrors(['whatsapp']);
        $this->assertStringContainsString('Nomor WhatsApp ini masih memiliki booking', session('errors')->first('whatsapp'));
    }

    /**
     * Test unpaid booking banner displays on public pages when unpaid cookie is present.
     */
    public function test_unpaid_booking_banner_renders_on_public_pages(): void
    {
        $film = Film::query()->create([
            'title' => 'Film QA Banner',
            'slug' => 'film-qa-banner-'.uniqid(),
            'duration_minutes' => 100,
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
            'code' => 'BN'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'Banner Tester',
            'whatsapp' => '6281234567899',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'payment_method' => 'qris',
            'status' => 'menunggu_pembayaran',
            'total_price' => 40000,
            'payment_expires_at' => now()->addMinutes(25),
        ]);

        $cookiePayload = json_encode([
            ['code' => $booking->code, 'token' => 'plain_token', 'created_at' => time()],
        ]);

        $response = $this->withCookie(DeviceBookingTracker::COOKIE_NAME, $cookiePayload)
            ->get(route('home'));

        $response->assertOk();
        $response->assertSee('unpaid-booking-banner', false);
        $response->assertSee($booking->code);
        $this->assertSame(1, substr_count($response->getContent(), 'id="unpaid-booking-banner"'));
        $response->assertSee('Lanjutkan pembayaran');
    }

    /**
     * Test in-app cancellation releases seats and cancels booking immediately.
     */
    public function test_in_app_cancellation_releases_seats(): void
    {
        $film = Film::query()->create([
            'title' => 'Film QA Cancel',
            'slug' => 'film-qa-cancel-'.uniqid(),
            'duration_minutes' => 100,
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
            'status' => 'tersedia',
        ]);

        $booking = Booking::query()->create([
            'session_id' => $session->id,
            'code' => 'CN'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'Cancel Tester',
            'whatsapp' => '6281234567888',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'seats_reserved' => 2,
            'payment_method' => 'qris',
            'status' => 'menunggu_pembayaran',
            'total_price' => 40000,
            'payment_expires_at' => now()->addMinutes(25),
        ]);

        $response = $this->withSession(['verified_booking_'.$booking->code => true])
            ->post(route('booking.cancel', ['code' => $booking->code]), [
                'reason' => 'Ingin ganti jadwal',
            ]);

        $response->assertRedirect();
        $booking->refresh();
        $session->refresh();

        $this->assertSame('dibatalkan', $booking->status);
        $this->assertSame(6, $session->remainingSeats());
        $this->assertSame(0, $session->occupiedBookings()->count());
    }

    /**
     * Test payment time limit setting changes apply only to new bookings while existing retain snapshot.
     */
    public function test_payment_time_limit_setting_applies_only_to_new_bookings(): void
    {
        // 1. Initial setting: 30 minutes
        Setting::updateOrCreate(['key' => 'payment_time_limit_minutes'], ['value' => '30']);
        app(CinemaSettings::class)->clearCache();

        $film = Film::query()->create([
            'title' => 'Film QA Snapshot',
            'slug' => 'film-qa-snapshot-'.uniqid(),
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
            'status' => 'tersedia',
            'confirmed_at' => now(),
            'confirmation_method' => 'admin',
        ]);

        // Create booking 1 with 30 min limit
        $payload1 = [
            'customer_name' => 'User Thirty',
            'whatsapp' => '081111111110',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'people_count' => 2,
            'payment_method' => 'transfer',
            'film_id' => $film->id,
        ];

        $resp1 = $this->post(route('booking.store', ['session' => $session->id]), $payload1);
        $resp1->assertSessionHasNoErrors();
        $resp1->assertRedirect();

        $booking1 = Booking::where('customer_name', 'User Thirty')->first();
        $this->assertNotNull($booking1);
        $this->assertNotNull($booking1->payment_expires_at);

        $initialExpiry1 = $booking1->payment_expires_at->toIso8601String();
        // Difference should be roughly 30 minutes
        $diff1 = now()->diffInMinutes($booking1->payment_expires_at, false);
        $this->assertGreaterThanOrEqual(28, $diff1);
        $this->assertLessThanOrEqual(31, $diff1);

        // 2. Change setting to 15 minutes
        Setting::updateOrCreate(['key' => 'payment_time_limit_minutes'], ['value' => '15']);
        app(CinemaSettings::class)->clearCache();

        // Cancel or clear booking1 from device so new booking can be made
        $booking1->update(['status' => 'terkonfirmasi']); // Mark confirmed so it's not unpaid

        // Create booking 2 with 15 min limit
        $payload2 = [
            'customer_name' => 'User Fifteen',
            'whatsapp' => '082222222220',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'people_count' => 2,
            'payment_method' => 'transfer',
            'film_id' => $film->id,
        ];

        $resp2 = $this->post(route('booking.store', ['session' => $session->id]), $payload2);
        $resp2->assertRedirect();

        $booking2 = Booking::where('customer_name', 'User Fifteen')->first();
        $this->assertNotNull($booking2);
        $this->assertNotNull($booking2->payment_expires_at);

        $diff2 = now()->diffInMinutes($booking2->payment_expires_at, false);
        $this->assertGreaterThanOrEqual(13, $diff2);
        $this->assertLessThanOrEqual(16, $diff2);

        // Verify booking 1 expiry was not modified
        $booking1->refresh();
        $this->assertSame($initialExpiry1, $booking1->payment_expires_at->toIso8601String());
    }
}
