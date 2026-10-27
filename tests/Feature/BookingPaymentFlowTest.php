<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Session;
use App\Models\Setting;
use App\Models\User;
use App\Services\CinemaSettings;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Film $film;

    private Session $session;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-14 10:00:00', 'Asia/Jakarta'));

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->film = Film::query()->create([
            'title' => 'Film Pembayaran Test',
            'duration_minutes' => 90,
            'genre' => 'Drama',
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis drama pengujian alur pembayaran.',
            'is_active' => true,
        ]);
        $this->session = Session::query()->create([
            'session_date' => '2026-10-14',
            'start_time' => '15:00',
            'end_time' => '17:00',
            'film_id' => $this->film->id,
            'capacity' => 6,
            'status' => 'tersedia',
        ]);

        // Seed default settings
        Setting::query()->updateOrCreate(['key' => 'weekday_price'], ['value' => '5000']);
        Setting::query()->updateOrCreate(['key' => 'private_price'], ['value' => '36000']);
        Setting::query()->updateOrCreate(['key' => 'booking_cutoff_minutes'], ['value' => '30']);
        Setting::query()->updateOrCreate(['key' => 'cancellation_cutoff_hours'], ['value' => '3']);
        Setting::query()->updateOrCreate(['key' => 'payment_time_limit_minutes'], ['value' => '15']);
        Setting::query()->updateOrCreate(['key' => 'online_payment_cutoff_minutes'], ['value' => '20']);
        Setting::query()->updateOrCreate(['key' => 'max_payment_proof_kb'], ['value' => '4096']);
        Setting::query()->updateOrCreate(['key' => 'admin_whatsapp'], ['value' => '6281234567890']);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_booking_creation_prohibits_uploading_payment_proof(): void
    {
        $response = $this->post(route('booking.store', $this->session), [
            'film_id' => $this->film->id,
            'booking_type' => 'per_orang',
            'pax' => 2,
            'customer_name' => 'Budi Santoso',
            'whatsapp' => '081234567890',
            'payment_method' => 'di_tempat',
            'payment_proof' => UploadedFile::fake()->image('proof.png', 400, 400),
        ]);

        $response->assertSessionHasErrors('payment_proof');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_online_booking_creation_starts_as_menunggu_pembayaran_with_expiry(): void
    {
        // Setup QRIS image and enable methods
        $qrisPath = 'qris/test-qris.png';
        Storage::disk('local')->put($qrisPath, 'fake-qris-data');
        Setting::query()->updateOrCreate(['key' => 'qris_image_path'], ['value' => $qrisPath]);
        Setting::query()->updateOrCreate(['key' => 'enabled_payment_methods'], ['value' => json_encode(['di_tempat', 'qris'])]);

        $response = $this->post(route('booking.store', $this->session), [
            'film_id' => $this->film->id,
            'booking_type' => 'per_orang',
            'pax' => 2,
            'customer_name' => 'Andi Wijaya',
            'whatsapp' => '081234567891',
            'payment_method' => 'qris',
        ]);

        $response->assertRedirect();
        $booking = Booking::query()->first();
        $this->assertNotNull($booking);
        $this->assertSame('menunggu_pembayaran', $booking->status);
        $this->assertNotNull($booking->payment_expires_at);
        $this->assertEquals(CarbonImmutable::parse('2026-10-14 10:15:00', 'Asia/Jakarta'), $booking->payment_expires_at);
        $this->assertTrue($booking->isAwaitingPayment());
        $this->assertFalse($booking->isExpired());
    }

    public function test_cash_booking_creation_starts_as_menunggu_konfirmasi_without_expiry(): void
    {
        $response = $this->post(route('booking.store', $this->session), [
            'film_id' => $this->film->id,
            'booking_type' => 'per_orang',
            'pax' => 1,
            'customer_name' => 'Citra Lestari',
            'whatsapp' => '081234567892',
            'payment_method' => 'di_tempat',
        ]);

        $response->assertRedirect();
        $booking = Booking::query()->first();
        $this->assertNotNull($booking);
        $this->assertSame('menunggu_konfirmasi', $booking->status);
        $this->assertNull($booking->payment_expires_at);
    }

    public function test_cutoff_excludes_online_payment_when_near_session_start(): void
    {
        $qrisPath = 'qris/test-qris.png';
        Storage::disk('local')->put($qrisPath, 'fake-qris-data');
        Setting::query()->updateOrCreate(['key' => 'qris_image_path'], ['value' => $qrisPath]);
        Setting::query()->updateOrCreate(['key' => 'enabled_payment_methods'], ['value' => json_encode(['di_tempat', 'qris', 'transfer'])]);
        Setting::query()->updateOrCreate(['key' => 'transfer_accounts'], ['value' => json_encode([['bank_name' => 'BCA', 'account_number' => '123', 'account_holder' => 'Studio']])]);

        // Session starts at 15:00. Cutoff is 20 minutes (14:40).
        // At 10:00, online methods are available.
        $settings = app(CinemaSettings::class);
        $availableAt10 = $settings->availablePaymentMethodsForSession($this->session);
        $this->assertContains('qris', $availableAt10);
        $this->assertContains('transfer', $availableAt10);

        // At 14:45 (15 mins before session starts, inside cutoff), only di_tempat is available.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-14 14:45:00', 'Asia/Jakarta'));
        $availableAtCutoff = $settings->availablePaymentMethodsForSession($this->session);
        $this->assertSame(['di_tempat'], $availableAtCutoff);
    }

    public function test_expired_payment_reservation_is_excluded_from_occupied_seats_and_lazy_expired(): void
    {
        $booking = Booking::query()->create([
            'code' => 'HC-EXPIRE-01',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Eko Prasetyo',
            'whatsapp' => '6281234567893',
            'booking_type' => 'per_orang',
            'pax' => 4,
            'ticket_unit_price' => 5000,
            'ticket_total' => 20000,
            'snack_total' => 0,
            'total_price' => 20000,
            'payment_method' => 'qris',
            'status' => 'menunggu_pembayaran',
            'payment_expires_at' => CarbonImmutable::parse('2026-10-14 10:15:00', 'Asia/Jakarta'),
        ]);

        // While not expired, seats are reserved
        $this->assertSame(2, $this->session->remainingSeats());

        // Fast-forward past expiry time
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-14 10:16:00', 'Asia/Jakarta'));

        // Occupied query immediately excludes the overdue booking even before status change
        $this->assertSame(6, $this->session->remainingSeats());
        $this->assertTrue($booking->isExpired());

        // Visiting success page lazily updates status to kedaluwarsa and redirects to ticket
        $this->get(route('booking.success', ['code' => $booking->code]))
            ->assertRedirect(route('booking.ticket', ['code' => $booking->code]));

        $booking->refresh();
        $this->assertSame('kedaluwarsa', $booking->status);
    }

    public function test_artisan_expire_unpaid_command_expires_overdue_bookings(): void
    {
        $booking = Booking::query()->create([
            'code' => 'HC-EXPIRE-CMD',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Fajar',
            'whatsapp' => '6281234567894',
            'booking_type' => 'per_orang',
            'pax' => 3,
            'ticket_unit_price' => 5000,
            'ticket_total' => 15000,
            'snack_total' => 0,
            'total_price' => 15000,
            'payment_method' => 'qris',
            'status' => 'menunggu_pembayaran',
            'payment_expires_at' => CarbonImmutable::parse('2026-10-14 10:15:00', 'Asia/Jakarta'),
        ]);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-14 10:16:00', 'Asia/Jakarta'));

        $this->artisan('bookings:expire-unpaid')
            ->expectsOutputToContain('1 booking telah kedaluwarsa')
            ->assertSuccessful();

        $this->assertSame('kedaluwarsa', $booking->fresh()->status);
    }

    public function test_customer_can_upload_proof_transitioning_to_menunggu_konfirmasi(): void
    {
        $booking = Booking::query()->create([
            'code' => 'HC-PROOF-01',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Gita',
            'whatsapp' => '6281234567895',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'ticket_unit_price' => 5000,
            'ticket_total' => 10000,
            'snack_total' => 0,
            'total_price' => 10000,
            'payment_method' => 'qris',
            'status' => 'menunggu_pembayaran',
            'payment_expires_at' => CarbonImmutable::parse('2026-10-14 10:15:00', 'Asia/Jakarta'),
        ]);

        $response = $this->post(route('booking.proof.upload', ['code' => $booking->code]), [
            'whatsapp' => '081234567895',
            'payment_proof' => UploadedFile::fake()->image('bukti.jpg', 600, 800),
        ]);

        $response->assertRedirect(route('booking.ticket', ['code' => $booking->code]));
        $booking->refresh();
        $this->assertSame('menunggu_konfirmasi', $booking->status);
        $this->assertNotNull($booking->payment_proof_path);
        $this->assertNotNull($booking->payment_proof_uploaded_at);
        $this->assertTrue(Storage::disk('local')->exists($booking->payment_proof_path));
    }

    public function test_uploading_proof_is_rejected_for_cash_and_expired_bookings(): void
    {
        $cashBooking = Booking::query()->create([
            'code' => 'HC-CASH-PROOF',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Hadi',
            'whatsapp' => '6281234567896',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'ticket_unit_price' => 5000,
            'ticket_total' => 5000,
            'snack_total' => 0,
            'total_price' => 5000,
            'payment_method' => 'di_tempat',
            'status' => 'menunggu_konfirmasi',
        ]);

        $this->post(route('booking.proof.upload', ['code' => $cashBooking->code]), [
            'whatsapp' => '081234567896',
            'payment_proof' => UploadedFile::fake()->image('bukti.jpg', 600, 800),
        ])->assertSessionHasErrors('payment_proof');

        $expiredBooking = Booking::query()->create([
            'code' => 'HC-EXPIRED-UP',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Indra',
            'whatsapp' => '6281234567897',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'ticket_unit_price' => 5000,
            'ticket_total' => 5000,
            'snack_total' => 0,
            'total_price' => 5000,
            'payment_method' => 'qris',
            'status' => 'kedaluwarsa',
        ]);

        $this->post(route('booking.proof.upload', ['code' => $expiredBooking->code]), [
            'whatsapp' => '081234567897',
            'payment_proof' => UploadedFile::fake()->image('bukti.jpg', 600, 800),
        ])->assertSessionHasErrors('payment_proof');
    }

    public function test_admin_can_reject_proof_with_reason_resetting_to_menunggu_pembayaran(): void
    {
        $this->actingAs($this->admin);

        $proofPath = 'payment-proofs/test-proof.jpg';
        Storage::disk('local')->put($proofPath, 'dummy-content');

        $booking = Booking::query()->create([
            'code' => 'HC-REJECT-01',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Joko',
            'whatsapp' => '6281234567898',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'ticket_unit_price' => 5000,
            'ticket_total' => 10000,
            'snack_total' => 0,
            'total_price' => 10000,
            'payment_method' => 'transfer',
            'status' => 'menunggu_konfirmasi',
            'payment_proof_path' => $proofPath,
            'payment_proof_uploaded_at' => now(),
            'payment_expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->patch(route('admin.bookings.status', $booking), [
            'action' => 'tolak_bukti',
            'rejection_note' => 'Nominal transfer kurang Rp 2.000.',
        ]);

        $response->assertRedirect();
        $booking->refresh();
        $this->assertSame('menunggu_pembayaran', $booking->status);
        $this->assertNull($booking->payment_proof_path);
        $this->assertSame('Nominal transfer kurang Rp 2.000.', $booking->proof_rejection_note);
        $this->assertFalse(Storage::disk('local')->exists($proofPath));
        $this->assertTrue($booking->payment_expires_at->greaterThan(now()));
    }

    public function test_admin_can_restore_expired_booking_when_capacity_permits(): void
    {
        $this->actingAs($this->admin);

        $booking = Booking::query()->create([
            'code' => 'HC-RESTORE-01',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Kartika',
            'whatsapp' => '6281234567899',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'ticket_unit_price' => 5000,
            'ticket_total' => 10000,
            'snack_total' => 0,
            'total_price' => 10000,
            'payment_method' => 'qris',
            'status' => 'kedaluwarsa',
            'payment_expires_at' => now()->subMinutes(10),
        ]);

        $response = $this->patch(route('admin.bookings.status', $booking), [
            'action' => 'pulihkan',
        ]);

        $response->assertRedirect();
        $booking->refresh();
        $this->assertSame('menunggu_pembayaran', $booking->status);
        $this->assertTrue($booking->payment_expires_at->greaterThan(now()));
        $this->assertSame(4, $this->session->remainingSeats());
    }

    public function test_admin_configures_transfer_accounts_and_enforces_rules(): void
    {
        $this->actingAs($this->admin);

        // Cannot enable transfer without any account
        $this->from(route('admin.settings.edit'))
            ->put(route('admin.settings.update'), [
                'weekday_price' => 5000,
                'weekend_price' => 6000,
                'holiday_price' => 6000,
                'private_price' => 36000,
                'minimum_public_viewers' => 3,
                'booking_cutoff_minutes' => 30,
                'cancellation_cutoff_hours' => 6,
                'admin_whatsapp' => '081234567890',
                'electricity_cost_per_session' => 0,
                'snacks_enabled' => 1,
                'snack_prep_buffer_minutes' => 15,
                'snack_max_qty_per_item' => 10,
                'payment_methods' => ['di_tempat', 'transfer'],
                'transfer_accounts' => [],
                'payment_time_limit_minutes' => 15,
                'online_payment_cutoff_minutes' => 20,
                'max_payment_proof_kb' => 4096,
            ])
            ->assertSessionHasErrors('payment_methods');

        // Can enable transfer with valid accounts
        $response = $this->put(route('admin.settings.update'), [
            'weekday_price' => 5000,
            'weekend_price' => 6000,
            'holiday_price' => 6000,
            'private_price' => 36000,
            'minimum_public_viewers' => 3,
            'booking_cutoff_minutes' => 30,
            'cancellation_cutoff_hours' => 6,
            'admin_whatsapp' => '081234567890',
            'electricity_cost_per_session' => 0,
            'snacks_enabled' => 1,
            'snack_prep_buffer_minutes' => 15,
            'snack_max_qty_per_item' => 10,
            'payment_methods' => ['di_tempat', 'transfer'],
            'transfer_accounts' => [
                ['bank_name' => 'BCA', 'account_number' => '8881234567', 'account_holder' => 'PT SynemaX Studio'],
                ['bank_name' => 'GoPay', 'account_number' => '081234567890', 'account_holder' => 'SynemaX Official'],
            ],
            'transfer_payment_notes' => 'Tulis kode booking pada berita transfer.',
            'payment_time_limit_minutes' => 20,
            'online_payment_cutoff_minutes' => 25,
            'max_payment_proof_kb' => 2048,
        ]);

        $response->assertRedirect();
        $settings = app(CinemaSettings::class);
        $this->assertTrue($settings->hasValidTransferAccounts());
        $this->assertCount(2, $settings->transferAccounts());
        $this->assertSame(20, $settings->paymentTimeLimitMinutes());
        $this->assertSame(25, $settings->onlinePaymentCutoffMinutes());
        $this->assertSame(2048, $settings->maxPaymentProofKb());
    }

    public function test_cash_booking_redirects_directly_to_ticket_and_bypasses_payment_page(): void
    {
        $response = $this->post(route('booking.store', $this->session), [
            'film_id' => $this->film->id,
            'booking_type' => 'per_orang',
            'pax' => 2,
            'customer_name' => 'Budi Cash',
            'whatsapp' => '081234567888',
            'payment_method' => 'di_tempat',
        ]);

        $booking = Booking::query()->where('customer_name', 'Budi Cash')->firstOrFail();
        $response->assertRedirect(route('booking.ticket', ['code' => $booking->code]));

        // Visiting payment page for cash booking redirects to ticket
        $this->get(route('booking.payment', ['code' => $booking->code]))
            ->assertRedirect(route('booking.ticket', ['code' => $booking->code]));

        // Ticket page displays cash instructions and 2-step stepper
        $ticketResponse = $this->get(route('booking.ticket', ['code' => $booking->code]));
        $ticketResponse->assertOk()
            ->assertSee('Tiket masuk studio')
            ->assertSee('Bayar di tempat')
            ->assertSee('Booking diterima. Bayar tunai di lokasi sebelum film mulai.')
            ->assertSee('1. Booking')
            ->assertSee('2. Selesai')
            ->assertDontSee('2. Bayar')
            ->assertDontSee('⏱️')
            ->assertDontSee('⌛');
    }

    public function test_online_booking_flow_booking_to_payment_to_ticket(): void
    {
        $qrisPath = 'qris/sample.png';
        Storage::disk('local')->put($qrisPath, 'dummy-qris');
        Setting::query()->updateOrCreate(['key' => 'qris_image_path'], ['value' => $qrisPath]);
        Setting::query()->updateOrCreate(['key' => 'enabled_payment_methods'], ['value' => json_encode(['di_tempat', 'qris'])]);

        // Step 1: Submit Booking
        $response = $this->post(route('booking.store', $this->session), [
            'film_id' => $this->film->id,
            'booking_type' => 'per_orang',
            'pax' => 2,
            'customer_name' => 'Siti Online',
            'whatsapp' => '081234567777',
            'payment_method' => 'qris',
        ]);

        $booking = Booking::query()->where('customer_name', 'Siti Online')->firstOrFail();
        $response->assertRedirect(route('booking.payment', ['code' => $booking->code]));

        // Step 2: Payment Page displays countdown, total, QRIS, and disabled submit button until file chosen (stepper removed per C2)
        $paymentResponse = $this->get(route('booking.payment', ['code' => $booking->code]));
        $paymentResponse->assertOk()
            ->assertDontSee('Tahapan Pesanan')
            ->assertDontSee('1. Booking')
            ->assertSee('Sisa waktu pembayaran')
            ->assertSee('Salin nominal')
            ->assertSee('Pindai kode QRIS')
            ->assertSee('Kirim bukti bayar')
            ->assertDontSee('Konfirmasi via WhatsApp')
            ->assertSee('Kirim bukti lewat WhatsApp');

        // Accessing ticket page while awaiting payment redirects to payment page
        $this->get(route('booking.ticket', ['code' => $booking->code]))
            ->assertRedirect(route('booking.payment', ['code' => $booking->code]));

        // Step 3: Upload Proof -> redirects to Ticket page
        $uploadResponse = $this->post(route('booking.proof.upload', ['code' => $booking->code]), [
            'whatsapp' => '081234567777',
            'payment_proof' => UploadedFile::fake()->image('bukti.png', 500, 500),
        ]);
        $uploadResponse->assertRedirect(route('booking.ticket', ['code' => $booking->code]));

        // Visiting ticket page now succeeds and shows menunggu_konfirmasi banner
        $ticketResponse = $this->get(route('booking.ticket', ['code' => $booking->code]));
        $ticketResponse->assertOk()
            ->assertSee('Menunggu konfirmasi admin')
            ->assertSee('Bukti bayar diterima. Admin akan memeriksa dan mengonfirmasi tiketmu.')
            ->assertSee('Simpan tiket')
            ->assertSee('Hubungi admin')
            ->assertDontSee('⏱️');
    }

    public function test_unauthorized_guests_are_forbidden_from_viewing_ticket_and_status_json(): void
    {
        $booking = Booking::query()->create([
            'code' => 'HC-SECURE-01',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Secret Customer',
            'whatsapp' => '6281234567000',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'ticket_unit_price' => 5000,
            'ticket_total' => 5000,
            'snack_total' => 0,
            'total_price' => 5000,
            'payment_method' => 'di_tempat',
            'status' => 'menunggu_konfirmasi',
        ]);

        // Unauthenticated guest without verified session gets 403
        $this->flushSession();
        $this->get(route('booking.ticket', ['code' => $booking->code]))
            ->assertForbidden();

        $this->get(route('booking.status.json', ['code' => $booking->code]))
            ->assertForbidden();

        // Guest with wrong WhatsApp query gets 403
        $this->get(route('booking.ticket', ['code' => $booking->code, 'wa' => '08999999999']))
            ->assertForbidden();

        // Customer providing matching WhatsApp query gets redirected to clean URL (no WA in URL)
        $verifyResponse = $this->get(route('booking.ticket', ['code' => $booking->code, 'wa' => '081234567000']));
        $verifyResponse->assertRedirect(route('booking.ticket', ['code' => $booking->code]));

        // Subsequent request using established session succeeds on clean URL with same-origin referrer policy
        $ticketResponse = $this->get(route('booking.ticket', ['code' => $booking->code]));
        $ticketResponse->assertOk();
        $this->assertSame('same-origin', $ticketResponse->headers->get('Referrer-Policy'));

        // Online payment page also redirects WA query to clean URL and attaches Referrer-Policy
        $onlineBooking = Booking::query()->create([
            'code' => 'HC-ONLINE-CLEAN',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Online Clean URL',
            'whatsapp' => '6281234567888',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'seat_numbers' => [2],
            'total_price' => 10000,
            'payment_method' => 'qris',
            'status' => 'menunggu_pembayaran',
            'payment_due_at' => now()->addMinutes(30),
        ]);

        $this->flushSession();
        $this->get(route('booking.payment', ['code' => $onlineBooking->code]))
            ->assertForbidden();

        $payRedirect = $this->get(route('booking.payment', ['code' => $onlineBooking->code, 'wa' => '081234567888']));
        $payRedirect->assertRedirect(route('booking.payment', ['code' => $onlineBooking->code]));

        $paymentPage = $this->get(route('booking.payment', ['code' => $onlineBooking->code]));
        $paymentPage->assertOk();
        $this->assertSame('same-origin', $paymentPage->headers->get('Referrer-Policy'));
    }

    public function test_status_json_endpoint_returns_clean_payload_for_polling(): void
    {
        $booking = Booking::query()->create([
            'code' => 'HC-POLL-01',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Polling Test',
            'whatsapp' => '6281234567111',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'ticket_unit_price' => 5000,
            'ticket_total' => 5000,
            'snack_total' => 0,
            'total_price' => 5000,
            'payment_method' => 'di_tempat',
            'status' => 'menunggu_konfirmasi',
        ]);

        $response = $this->get(route('booking.status.json', ['code' => $booking->code, 'wa' => '081234567111']));
        $response->assertOk()
            ->assertJson([
                'code' => 'HC-POLL-01',
                'status' => 'menunggu_konfirmasi',
                'status_label' => 'Menunggu konfirmasi',
                'is_final' => false,
            ]);
    }

    public function test_lookup_redirects_correctly_based_on_payment_status(): void
    {
        $qrisBooking = Booking::query()->create([
            'code' => 'HC-LOOK-QRIS',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Lookup QRIS',
            'whatsapp' => '6281234567222',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'ticket_unit_price' => 5000,
            'ticket_total' => 5000,
            'snack_total' => 0,
            'total_price' => 5000,
            'payment_method' => 'qris',
            'status' => 'menunggu_pembayaran',
            'payment_expires_at' => now()->addMinutes(15),
        ]);

        $confirmedBooking = Booking::query()->create([
            'code' => 'HC-LOOK-CONF',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Lookup Confirmed',
            'whatsapp' => '6281234567333',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'ticket_unit_price' => 5000,
            'ticket_total' => 5000,
            'snack_total' => 0,
            'total_price' => 5000,
            'payment_method' => 'di_tempat',
            'status' => 'terkonfirmasi',
        ]);

        // Awaiting payment online redirects to payment page
        $this->post(route('booking.lookup'), [
            'code' => $qrisBooking->code,
            'whatsapp' => '081234567222',
        ])->assertRedirect(route('booking.payment', ['code' => $qrisBooking->code]));

        // Confirmed booking redirects to ticket page
        $this->post(route('booking.lookup'), [
            'code' => $confirmedBooking->code,
            'whatsapp' => '081234567333',
        ])->assertRedirect(route('booking.ticket', ['code' => $confirmedBooking->code]));
    }
}
