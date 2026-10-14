<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Session;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivateBookingRulesTest extends TestCase
{
    use RefreshDatabase;

    private Film $film;

    private Session $session;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-14 10:00:00', 'Asia/Jakarta'));

        $this->admin = User::factory()->create(['is_admin' => true]);

        $this->film = Film::query()->create([
            'title' => 'Film Pengujian C1',
            'duration_minutes' => 100,
            'genre' => 'Drama',
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis film pengujian.',
            'is_active' => true,
        ]);

        $this->session = Session::query()->create([
            'session_date' => '2026-10-14',
            'start_time' => '15:00',
            'end_time' => '17:00',
            'film_id' => $this->film->id,
            'capacity' => 6,
            'is_private' => false,
            'status' => 'tersedia',
        ]);

        Setting::query()->updateOrCreate(['key' => 'weekday_price'], ['value' => '5000']);
        Setting::query()->updateOrCreate(['key' => 'private_price'], ['value' => '36000']);
        Setting::query()->updateOrCreate(['key' => 'booking_cutoff_minutes'], ['value' => '30']);
        Setting::query()->updateOrCreate(['key' => 'cancellation_cutoff_hours'], ['value' => '3']);
        Setting::query()->updateOrCreate(['key' => 'payment_time_limit_minutes'], ['value' => '15']);
        Setting::query()->updateOrCreate(['key' => 'online_payment_cutoff_minutes'], ['value' => '20']);
        Setting::query()->updateOrCreate(['key' => 'enabled_payment_methods'], ['value' => json_encode(['di_tempat'])]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_server_rejects_private_booking_when_active_per_person_booking_exists(): void
    {
        Booking::query()->create([
            'code' => 'HC-PER-01',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Budi Santoso',
            'whatsapp' => '6281234567801',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'seats_reserved' => 2,
            'ticket_unit_price' => 5000,
            'ticket_total' => 10000,
            'total_price' => 10000,
            'payment_method' => 'di_tempat',
            'status' => 'menunggu_konfirmasi',
        ]);

        $response = $this->post(route('booking.store', $this->session), [
            'film_id' => $this->film->id,
            'booking_type' => 'private',
            'customer_name' => 'Pemesan Private',
            'whatsapp' => '081234567802',
            'payment_method' => 'di_tempat',
        ]);

        $response->assertSessionHasErrors([
            'booking_type' => 'Sudah ada pemesan per orang di sesi ini, jadi Sewa Private tidak tersedia.',
        ]);

        $this->assertFalse($this->session->fresh()->is_private);
    }

    public function test_unpaid_per_person_booking_before_expiry_blocks_private_booking(): void
    {
        Booking::query()->create([
            'code' => 'HC-UNPAID-01',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Andi Online',
            'whatsapp' => '6281234567803',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'seats_reserved' => 1,
            'ticket_unit_price' => 5000,
            'ticket_total' => 5000,
            'total_price' => 5000,
            'payment_method' => 'qris',
            'status' => 'menunggu_pembayaran',
            'payment_expires_at' => CarbonImmutable::parse('2026-10-14 10:15:00', 'Asia/Jakarta'),
        ]);

        $response = $this->post(route('booking.store', $this->session), [
            'film_id' => $this->film->id,
            'booking_type' => 'private',
            'customer_name' => 'Private Challenger',
            'whatsapp' => '081234567804',
            'payment_method' => 'di_tempat',
        ]);

        $response->assertSessionHasErrors([
            'booking_type' => 'Sudah ada pemesan per orang di sesi ini, jadi Sewa Private tidak tersedia.',
        ]);
    }

    public function test_private_becomes_available_again_after_per_person_booking_is_cancelled(): void
    {
        $booking = Booking::query()->create([
            'code' => 'HC-CANCEL-01',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Candra Batal',
            'whatsapp' => '6281234567805',
            'booking_type' => 'per_orang',
            'pax' => 3,
            'seats_reserved' => 3,
            'ticket_unit_price' => 5000,
            'ticket_total' => 15000,
            'total_price' => 15000,
            'payment_method' => 'di_tempat',
            'status' => 'menunggu_konfirmasi',
        ]);

        // Cancel the per-person booking
        $this->post(route('booking.cancel', ['code' => $booking->code]), [
            'whatsapp' => '081234567805',
        ])->assertRedirect();

        $this->assertSame('dibatalkan', $booking->fresh()->status);
        $this->assertSame(6, $this->session->remainingSeats());

        // Now private booking must succeed
        $response = $this->post(route('booking.store', $this->session), [
            'film_id' => $this->film->id,
            'booking_type' => 'private',
            'customer_name' => 'Sultan Private',
            'whatsapp' => '081234567806',
            'payment_method' => 'di_tempat',
        ]);

        $response->assertRedirect();
        $this->assertTrue($this->session->fresh()->is_private);
    }

    public function test_private_becomes_available_again_after_per_person_booking_expires(): void
    {
        $booking = Booking::query()->create([
            'code' => 'HC-EXP-01',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Dodi Expire',
            'whatsapp' => '6281234567807',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'seats_reserved' => 2,
            'ticket_unit_price' => 5000,
            'ticket_total' => 10000,
            'total_price' => 10000,
            'payment_method' => 'qris',
            'status' => 'menunggu_pembayaran',
            'payment_expires_at' => CarbonImmutable::parse('2026-10-14 10:15:00', 'Asia/Jakarta'),
        ]);

        // Fast-forward past expiry time
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-14 10:16:00', 'Asia/Jakarta'));

        // Run expire command
        $this->artisan('bookings:expire-unpaid')->assertSuccessful();

        $this->assertSame('kedaluwarsa', $booking->fresh()->status);
        $this->assertSame(6, $this->session->remainingSeats());

        // Private booking succeeds
        $response = $this->post(route('booking.store', $this->session), [
            'film_id' => $this->film->id,
            'booking_type' => 'private',
            'customer_name' => 'Eka Private',
            'whatsapp' => '081234567808',
            'payment_method' => 'di_tempat',
        ]);

        $response->assertRedirect();
        $this->assertTrue($this->session->fresh()->is_private);
    }

    public function test_server_rejects_booking_when_session_already_private(): void
    {
        // Create an active private booking
        $this->post(route('booking.store', $this->session), [
            'film_id' => $this->film->id,
            'booking_type' => 'private',
            'customer_name' => 'Fani Private',
            'whatsapp' => '081234567809',
            'payment_method' => 'di_tempat',
        ])->assertRedirect();

        $this->assertTrue($this->session->fresh()->is_private);

        // Attempting to book per_orang must be rejected with exact message
        $responsePerPerson = $this->post(route('booking.store', $this->session), [
            'film_id' => $this->film->id,
            'booking_type' => 'per_orang',
            'pax' => 1,
            'customer_name' => 'Gerry Per Orang',
            'whatsapp' => '081234567810',
            'payment_method' => 'di_tempat',
        ]);

        $responsePerPerson->assertSessionHasErrors([
            'booking_type' => 'Sesi ini sudah disewa private.',
        ]);

        // Attempting another private booking must also be rejected
        $responsePrivate = $this->post(route('booking.store', $this->session), [
            'film_id' => $this->film->id,
            'booking_type' => 'private',
            'customer_name' => 'Hendra Private',
            'whatsapp' => '081234567811',
            'payment_method' => 'di_tempat',
        ]);

        $responsePrivate->assertSessionHasErrors([
            'booking_type' => 'Sesi ini sudah disewa private.',
        ]);
    }

    public function test_session_is_private_flag_returns_to_false_when_private_booking_is_cancelled_or_expires(): void
    {
        $response = $this->post(route('booking.store', $this->session), [
            'film_id' => $this->film->id,
            'booking_type' => 'private',
            'customer_name' => 'Imam Private',
            'whatsapp' => '081234567812',
            'payment_method' => 'di_tempat',
        ]);

        $response->assertRedirect();
        $booking = Booking::query()->where('customer_name', 'Imam Private')->firstOrFail();
        $this->assertTrue($this->session->fresh()->is_private);

        // Cancel the private booking
        $this->post(route('booking.cancel', ['code' => $booking->code]), [
            'whatsapp' => '081234567812',
        ])->assertRedirect();

        $this->assertSame('dibatalkan', $booking->fresh()->status);
        $this->assertFalse($this->session->fresh()->is_private);
        $this->assertSame(6, $this->session->fresh()->remainingSeats());
    }

    public function test_admin_cancel_resets_is_private_flag(): void
    {
        $this->actingAs($this->admin);

        $booking = Booking::query()->create([
            'code' => 'HC-ADM-PRIV',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Jafar Private',
            'whatsapp' => '6281234567813',
            'booking_type' => 'private',
            'pax' => 6,
            'seats_reserved' => 6,
            'ticket_unit_price' => 36000,
            'ticket_total' => 36000,
            'total_price' => 36000,
            'payment_method' => 'di_tempat',
            'status' => 'menunggu_konfirmasi',
        ]);
        $this->session->update(['is_private' => true]);

        $this->patch(route('admin.bookings.status', $booking), [
            'action' => 'batal',
        ])->assertRedirect();

        $this->assertSame('dibatalkan', $booking->fresh()->status);
        $this->assertFalse($this->session->fresh()->is_private);
    }

    public function test_availability_json_endpoint_returns_accurate_state_and_no_store_headers(): void
    {
        // Case 1: Empty session
        $response = $this->get(route('booking.availability', $this->session));
        $response->assertOk();
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', (string) $response->headers->get('Pragma'));
        $response->assertJson([
                'session_id' => $this->session->id,
                'status' => 'tersedia',
                'is_available' => true,
                'seat_capacity' => 6,
                'occupied_seats' => 0,
                'remaining_seats' => 6,
                'can_private' => true,
                'can_per_orang' => true,
                'private_disabled_reason' => null,
            ])
            ->assertJsonMissing(['whatsapp', 'customer_name', 'code']);

        // Case 2: Session with per_orang booking
        Booking::query()->create([
            'code' => 'HC-JSON-PER',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Kiki',
            'whatsapp' => '6281234567814',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'seats_reserved' => 2,
            'ticket_unit_price' => 5000,
            'ticket_total' => 10000,
            'total_price' => 10000,
            'payment_method' => 'di_tempat',
            'status' => 'menunggu_konfirmasi',
        ]);

        $responsePerPerson = $this->get(route('booking.availability', $this->session));
        $responsePerPerson->assertOk()
            ->assertJson([
                'can_private' => false,
                'can_per_orang' => true,
                'remaining_seats' => 4,
                'occupied_seats' => 2,
                'has_active_per_person' => true,
                'private_disabled_reason' => 'Sudah ada pemesan per orang di sesi ini, jadi Sewa Private tidak tersedia.',
            ]);

        // Case 3: Past session
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-14 16:00:00', 'Asia/Jakarta'));
        $responsePast = $this->get(route('booking.availability', $this->session));
        $responsePast->assertOk()
            ->assertJson([
                'is_available' => false,
                'can_private' => false,
                'can_per_orang' => false,
            ]);
    }

    public function test_wizard_booking_form_renders_disabled_private_card_with_aria_and_exact_reason(): void
    {
        // Add 1 per-person booking
        Booking::query()->create([
            'code' => 'HC-UI-PER',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Lina',
            'whatsapp' => '6281234567815',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'seats_reserved' => 1,
            'ticket_unit_price' => 5000,
            'ticket_total' => 5000,
            'total_price' => 5000,
            'payment_method' => 'di_tempat',
            'status' => 'menunggu_konfirmasi',
        ]);

        $response = $this->get(route('booking.create', $this->session));
        $response->assertOk()
            ->assertSee('Sudah ada pemesan per orang di sesi ini, jadi Sewa Private tidak tersedia.')
            ->assertSee('aria-describedby="private-desc"', false)
            ->assertSee('disabled', false);
    }
}
