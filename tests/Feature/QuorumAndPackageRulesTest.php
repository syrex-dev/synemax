<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Session;
use App\Models\Setting;
use App\Models\User;
use App\Services\BookingService;
use App\Services\CinemaSettings;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QuorumAndPackageRulesTest extends TestCase
{
    use RefreshDatabase;

    private Film $filmA;

    private Film $filmB;

    private Film $filmC;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-28 09:00:00', 'Asia/Jakarta'));

        Setting::updateOrCreate(['key' => 'seats_capacity'], ['value' => '6']);
        Setting::updateOrCreate(['key' => 'minimum_public_viewers'], ['value' => '4']);
        Setting::updateOrCreate(['key' => 'weekday_price'], ['value' => '20000']);
        Setting::updateOrCreate(['key' => 'weekend_price'], ['value' => '24000']);
        Setting::updateOrCreate(['key' => 'admin_whatsapp'], ['value' => '6285169730960']);
        app(CinemaSettings::class)->clearCache();

        $this->filmA = $this->createFilm('Film Alpha');
        $this->filmB = $this->createFilm('Film Beta');
        $this->filmC = $this->createFilm('Film Gamma');

        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    private function createFilm(string $title): Film
    {
        return Film::query()->create([
            'title' => $title,
            'slug' => Str::slug($title).'-'.uniqid(),
            'duration_minutes' => 100,
            'genre' => 'Drama',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis '.$title,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function createSession(string $date = '2026-10-28', string $time = '16:00'): Session
    {
        return Session::query()->create([
            'session_date' => $date,
            'start_time' => $time,
            'end_time' => '18:15',
            'capacity' => 6,
            'film_id' => null,
            'is_private' => false,
            'status' => 'tersedia',
        ]);
    }

    private function createBooking(Session $session, int $filmId, int $pax, string $name, string $whatsapp, string $package = 'reguler', string $paymentMethod = 'di_tempat'): Booking
    {
        return app(BookingService::class)->create(
            $session,
            [
                'customer_name' => $name,
                'whatsapp' => $whatsapp,
                'pax' => $pax,
                'people_count' => $pax,
                'film_id' => $filmId,
                'package' => $package,
                'booking_type' => $package === 'private' ? 'private' : ($package === 'couple' ? 'couple' : 'per_orang'),
                'payment_method' => $paymentMethod,
            ]
        );
    }

    public function test_deadlock_guard_truth_table(): void
    {
        $session = $this->createSession();

        // 1. Initial state: Film A 2 seats -> allowed
        $checkA2 = $session->canAcceptRegularBooking($this->filmA->id, 2);
        $this->assertTrue($checkA2['allowed']);

        // Book A:2
        $this->post(route('booking.store', $session), [
            'package' => 'reguler',
            'film_id' => $this->filmA->id,
            'pax' => 2,
            'customer_name' => 'Pemesan A',
            'whatsapp' => '08123456781',
            'payment_method' => 'di_tempat',
        ])->assertSessionHasNoErrors();

        $session->refresh();
        $this->assertFalse($session->isConfirmed());

        // 2. Book B:2 -> allowed (total=4, max=2, total-max=2 <= 2)
        $checkB2 = $session->canAcceptRegularBooking($this->filmB->id, 2);
        $this->assertTrue($checkB2['allowed']);

        $this->post(route('booking.store', $session), [
            'package' => 'reguler',
            'film_id' => $this->filmB->id,
            'pax' => 2,
            'customer_name' => 'Pemesan B',
            'whatsapp' => '08123456782',
            'payment_method' => 'di_tempat',
        ])->assertSessionHasNoErrors();

        $session->refresh();
        $this->assertFalse($session->isConfirmed());

        // 3. Adding Film C:1 -> REJECTED by deadlock guard (total=5, max=2, total-max=3 > 2)
        $checkC1 = $session->canAcceptRegularBooking($this->filmC->id, 1);
        $this->assertFalse($checkC1['allowed']);
        $this->assertTrue($checkC1['is_deadlock']);
        $this->assertStringContainsString('Pilihan ini membuat sesi sulit tayang', $checkC1['reason']);

        $this->post(route('booking.store', $session), [
            'package' => 'reguler',
            'film_id' => $this->filmC->id,
            'pax' => 1,
            'customer_name' => 'Pemesan C',
            'whatsapp' => '08123456783',
            'payment_method' => 'di_tempat',
        ])->assertSessionHasErrors(['film_id']);

        // 4. Adding Film A:1 -> ALLOWED (total=5, max=3, total-max=2 <= 2)
        $checkA1 = $session->canAcceptRegularBooking($this->filmA->id, 1);
        $this->assertTrue($checkA1['allowed']);

        $this->post(route('booking.store', $session), [
            'package' => 'reguler',
            'film_id' => $this->filmA->id,
            'pax' => 1,
            'customer_name' => 'Pemesan A2',
            'whatsapp' => '08123456784',
            'payment_method' => 'di_tempat',
        ])->assertSessionHasNoErrors();
    }

    public function test_quorum_reaching_4_locks_film_and_flags_others_for_reselect(): void
    {
        $session = $this->createSession();

        // Pemesan B: 1 kursi
        $bookingB = $this->createBooking($session, $this->filmB->id, 1, 'User B', '0811111111');

        // Pemesan A1: 2 kursi
        $this->createBooking($session, $this->filmA->id, 2, 'User A1', '0811111112');

        $session->refresh();
        $this->assertFalse($session->isConfirmed());
        $this->assertNull($session->film_id);

        // Pemesan A2: 2 kursi -> Total A = 4 >= Quorum (4)
        $this->createBooking($session, $this->filmA->id, 2, 'User A2', '0811111113');

        $session->refresh();
        $this->assertTrue($session->isConfirmed());
        $this->assertSame($this->filmA->id, $session->film_id);
        $this->assertSame('quorum', $session->confirmation_method);
        $this->assertNotNull($session->confirmed_at);

        // Booker B flagged for reselect
        $bookingB->refresh();
        $this->assertTrue($bookingB->reselect_needed);

        // Booking regular for another film should be rejected
        $checkB = $session->canAcceptRegularBooking($this->filmB->id, 1);
        $this->assertFalse($checkB['allowed']);
    }

    public function test_couple_package_rules_and_conditions(): void
    {
        $session = $this->createSession();

        // Couple package on empty session -> allowed
        $checkCouple = $session->canAcceptCoupleBooking($this->filmA->id);
        $this->assertTrue($checkCouple['allowed']);

        // Book couple
        $booking = $this->createBooking($session, $this->filmA->id, 2, 'Pasangan', '08123456789', 'couple');

        $this->assertTrue($booking->isCouple());
        $this->assertSame(2, $booking->seats_reserved);
        $this->assertSame(2, $booking->people_count);

        $session->refresh();
        $this->assertTrue($session->isConfirmed());
        $this->assertSame('couple', $session->confirmation_method);
        $this->assertSame($this->filmA->id, $session->film_id);

        // Second couple booking rejected because session already confirmed
        $checkSecondCouple = $session->canAcceptCoupleBooking($this->filmA->id);
        $this->assertFalse($checkSecondCouple['allowed']);

        // Regular booking can join the same film
        $checkRegularJoin = $session->canAcceptRegularBooking($this->filmA->id, 2);
        $this->assertTrue($checkRegularJoin['allowed']);

        // Regular booking for different film rejected
        $checkRegularDiff = $session->canAcceptRegularBooking($this->filmB->id, 1);
        $this->assertFalse($checkRegularDiff['allowed']);
    }

    public function test_private_package_rules_and_conditions(): void
    {
        $session = $this->createSession();

        // Empty session -> private allowed
        $checkPrivate = $session->canAcceptPrivateBooking();
        $this->assertTrue($checkPrivate['allowed']);

        // If session has even 1 regular booking -> private rejected
        $this->createBooking($session, $this->filmA->id, 1, 'User 1', '08123456781');

        $session->refresh();
        $checkPrivateBlocked = $session->canAcceptPrivateBooking();
        $this->assertFalse($checkPrivateBlocked['allowed']);

        // Fresh session for private booking
        $privateSession = $this->createSession('2026-10-28', '19:30');
        $privateBooking = $this->createBooking($privateSession, $this->filmB->id, 4, 'Keluarga', '08123456782', 'private');

        $this->assertTrue($privateBooking->isPrivate());
        $this->assertSame(6, $privateBooking->seats_reserved);
        $this->assertSame(4, $privateBooking->people_count);

        $privateSession->refresh();
        $this->assertTrue($privateSession->isConfirmed());
        $this->assertSame('private', $privateSession->confirmation_method);
        $this->assertTrue($privateSession->is_private);
        $this->assertSame(0, $privateSession->remainingSeats());
    }

    public function test_customer_reselect_reschedule_and_upgrade_actions(): void
    {
        $session = $this->createSession();

        // Quorum reached for Film A, Film B has 1 booker needing reselect
        $bookingB = $this->createBooking($session, $this->filmB->id, 1, 'User B', '08123456780');
        $this->createBooking($session, $this->filmA->id, 4, 'User A', '08123456781');

        $bookingB->refresh();
        $this->assertTrue($bookingB->reselect_needed);

        // 1. Reselect / Ubah Film to Film A
        $this->withSession(['verified_booking_'.$bookingB->code => true])
            ->post(route('booking.change-film', $bookingB->code), [
                'film_id' => $this->filmA->id,
            ])->assertSessionHasNoErrors();

        $bookingB->refresh();
        $this->assertSame($this->filmA->id, $bookingB->film_id);
        $this->assertFalse($bookingB->reselect_needed);

        // 2. Reschedule to another session
        $targetSession = $this->createSession('2026-10-29', '10:00');
        $this->withSession(['verified_booking_'.$bookingB->code => true])
            ->post(route('booking.reschedule', $bookingB->code), [
                'target_session_id' => $targetSession->id,
                'film_id' => $this->filmC->id,
            ])->assertSessionHasNoErrors();

        $bookingB->refresh();
        $this->assertSame($targetSession->id, $bookingB->session_id);
        $this->assertSame($this->filmC->id, $bookingB->film_id);

        // 3. Upgrade to Couple
        $this->withSession(['verified_booking_'.$bookingB->code => true])
            ->post(route('booking.upgrade', $bookingB->code), [
                'target_package' => 'couple',
            ])->assertSessionHasNoErrors();

        $bookingB->refresh();
        $this->assertSame('couple', $bookingB->package);
        $this->assertSame(2, $bookingB->seats_reserved);
        $targetSession->refresh();
        $this->assertTrue($targetSession->isConfirmed());
        $this->assertSame('couple', $targetSession->confirmation_method);
    }

    public function test_quorum_resolution_scheduler_command(): void
    {
        // Session at 16:00
        $session = $this->createSession('2026-10-28', '16:00');
        $booking = $this->createBooking($session, $this->filmA->id, 2, 'Tester', '08123456780');

        // Test Now at 10:00 (H-6h): Untouched
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-28 10:00:00', 'Asia/Jakarta'));
        $this->artisan('sessions:resolve-quorum')->assertSuccessful();
        $session->refresh();
        $this->assertSame('tersedia', $session->status);
        $this->assertFalse($session->isInAgreementWindow());

        // Test Now at 14:00 (H-2h, within agreement window 180m s/d 60m):
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-28 14:00:00', 'Asia/Jakarta'));
        $this->assertTrue($session->isInAgreementWindow());
        $this->artisan('sessions:resolve-quorum')->assertSuccessful();
        $session->refresh();
        $this->assertSame('tersedia', $session->status); // Still open for agreement

        // Test Now at 15:10 (H-50m, past deadline 60m): Cancelled automatically
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-28 15:10:00', 'Asia/Jakarta'));
        $this->assertTrue($session->isPastConfirmationDeadline());

        $this->artisan('sessions:resolve-quorum')->assertSuccessful();
        $session->refresh();
        $this->assertSame('ditutup', $session->status);
        $this->assertStringContainsString('Dibatalkan otomatis: kuorum tidak terpenuhi', $session->resolution_notes);

        $booking->refresh();
        $this->assertSame('dibatalkan', $booking->status);

        // Idempotency: run again without changes or errors
        $this->artisan('sessions:resolve-quorum')->assertSuccessful();
        $session->refresh();
        $this->assertSame('ditutup', $session->status);
    }

    public function test_admin_resolve_session_actions(): void
    {
        $session = $this->createSession('2026-10-28', '16:00');
        $this->createBooking($session, $this->filmA->id, 2, 'User A', '08123456781');
        $bookingB = $this->createBooking($session, $this->filmB->id, 1, 'User B', '08123456782');

        // 1. Action: Tayangkan Tetap
        $this->actingAs($this->admin)
            ->post(route('admin.schedule.sessions.resolve', $session), [
                'action' => 'tayangkan_tetap',
                'film_id' => $this->filmA->id,
                'reason' => 'Admin memutuskan tayang',
            ])->assertSessionHasNoErrors();

        $session->refresh();
        $this->assertTrue($session->isConfirmed());
        $this->assertSame('admin', $session->confirmation_method);
        $this->assertSame($this->filmA->id, $session->film_id);

        $bookingB->refresh();
        $this->assertTrue($bookingB->reselect_needed);

        // 2. Action: Batalkan Sesi
        $session2 = $this->createSession('2026-10-28', '19:30');
        $booking2 = $this->createBooking($session2, $this->filmC->id, 2, 'User C', '08123456783');

        $this->actingAs($this->admin)
            ->post(route('admin.schedule.sessions.resolve', $session2), [
                'action' => 'batalkan_sesi',
                'reason' => 'Kendala teknis',
            ])->assertSessionHasNoErrors();

        $session2->refresh();
        $this->assertSame('ditutup', $session2->status);
        $booking2->refresh();
        $this->assertSame('dibatalkan', $booking2->status);
    }

    public function test_package_pricing_formula_and_settings_override(): void
    {
        $settings = app(CinemaSettings::class);

        // Weekday default formula: Couple = 4 * 20.000 = 80.000, Private = 6 * 20.000 = 120.000
        $this->assertSame(80000, $settings->couplePrice('weekday'));
        $this->assertSame(120000, $settings->privatePriceForDayType('weekday'));

        // Weekend default formula: Couple = 4 * 24.000 = 96.000, Private = 6 * 24.000 = 144.000
        $this->assertSame(96000, $settings->couplePrice('weekend'));
        $this->assertSame(144000, $settings->privatePriceForDayType('weekend'));

        // Admin override couple price
        Setting::updateOrCreate(['key' => 'couple_price_weekday'], ['value' => '75000']);
        $settings->clearCache();
        $this->assertSame(75000, $settings->couplePrice('weekday'));

        // Admin override private price
        Setting::updateOrCreate(['key' => 'private_price_weekday'], ['value' => '150000']);
        $settings->clearCache();
        $this->assertSame(150000, $settings->privatePriceForDayType('weekday'));
    }

    public function test_admin_report_metrics_include_quorum_cancellations_and_film_demand(): void
    {
        // Cancelled session due to quorum
        $session = $this->createSession('2026-10-28', '13:00');
        $session->update([
            'status' => 'ditutup',
            'resolution_notes' => 'Dibatalkan otomatis: kuorum tidak terpenuhi hingga batas konfirmasi.',
        ]);

        Booking::query()->create([
            'code' => 'HC-TEST-REPORT',
            'session_id' => $session->id,
            'film_id' => $this->filmC->id,
            'package' => 'reguler',
            'customer_name' => 'Report Tester',
            'whatsapp' => '08123456789',
            'pax' => 2,
            'people_count' => 2,
            'seats_reserved' => 2,
            'booking_type' => 'per_orang',
            'total_price' => 40000,
            'ticket_total' => 40000,
            'snack_total' => 0,
            'payment_method' => 'di_tempat',
            'status' => 'dibatalkan',
            'refund_needed' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.index', ['period' => 'harian', 'date' => '2026-10-28']));

        $response->assertOk();
        $response->assertSee('Sesi batal karena kuorum');
        $response->assertSee('Film paling sering diminati');
        $response->assertSee('Film Gamma');
    }
}
