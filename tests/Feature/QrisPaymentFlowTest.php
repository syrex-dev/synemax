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

class QrisPaymentFlowTest extends TestCase
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
            'title' => 'Film QRIS Test',
            'duration_minutes' => 90,
            'genre' => 'Petualangan',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis film untuk pengujian QRIS.',
            'is_active' => true,
        ]);
        $this->session = Session::query()->create([
            'session_date' => '2026-10-14',
            'start_time' => '14:00',
            'end_time' => '16:00',
            'film_id' => $this->film->id,
            'capacity' => 6,
            'status' => 'tersedia',
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_admin_can_upload_qris_image_and_save_merchant_details(): void
    {
        $this->actingAs($this->admin);

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
            'payment_methods' => ['di_tempat', 'qris'],
            'qris_image' => UploadedFile::fake()->image('qris.png', 400, 400),
            'qris_merchant_name' => 'SynemaX Studio Official',
            'qris_payment_notes' => 'Harap bayar sesuai nominal tepat.',
        ]);

        $response->assertRedirect();
        $settings = app(CinemaSettings::class);
        $this->assertTrue($settings->hasValidQrisImage());
        $this->assertSame('SynemaX Studio Official', $settings->qrisMerchantName());
        $this->assertSame('Harap bayar sesuai nominal tepat.', $settings->qrisPaymentNotes());
        $this->assertContains('qris', $settings->enabledPaymentMethods());
    }

    public function test_admin_cannot_activate_qris_without_valid_image(): void
    {
        $this->actingAs($this->admin);

        $response = $this->from(route('admin.settings.edit'))
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
                'payment_methods' => ['di_tempat', 'qris'],
            ]);

        $response->assertSessionHasErrors('payment_methods');
    }

    public function test_admin_rejects_svg_or_small_or_oversized_qris_images(): void
    {
        $this->actingAs($this->admin);

        // SVG rejection
        $svgFile = UploadedFile::fake()->create('qris.svg', 50, 'image/svg+xml');
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
                'payment_methods' => ['di_tempat'],
                'qris_image' => $svgFile,
            ])
            ->assertSessionHasErrors('qris_image');

        // Smaller than 300x300 rejection
        $smallFile = UploadedFile::fake()->image('small.png', 200, 200);
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
                'payment_methods' => ['di_tempat'],
                'qris_image' => $smallFile,
            ])
            ->assertSessionHasErrors('qris_image');
    }

    public function test_admin_can_delete_qris_image(): void
    {
        $this->actingAs($this->admin);

        // Upload first
        $this->put(route('admin.settings.update'), [
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
            'payment_methods' => ['di_tempat', 'qris'],
            'qris_image' => UploadedFile::fake()->image('qris.png', 400, 400),
        ]);

        $settings = app(CinemaSettings::class);
        $path = $settings->string('qris_image_path');
        $this->assertTrue(Storage::disk('local')->exists($path));

        // Delete
        $response = $this->delete(route('admin.settings.qris.destroy'));
        $response->assertRedirect();

        $this->assertFalse(Storage::disk('local')->exists($path));
        $this->assertFalse(app(CinemaSettings::class)->hasValidQrisImage());
        $this->assertNotContains('qris', app(CinemaSettings::class)->enabledPaymentMethods());
    }

    public function test_qris_warning_shown_when_active_in_raw_methods_but_file_missing(): void
    {
        $this->actingAs($this->admin);

        Setting::query()->updateOrCreate(['key' => 'enabled_payment_methods'], ['value' => json_encode(['di_tempat', 'qris'])]);
        Setting::query()->updateOrCreate(['key' => 'qris_image_path'], ['value' => 'qris/missing.png']);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Metode bayar QRIS dinonaktifkan sementara');

        $this->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Peringatan: Gambar QRIS belum diunggah atau tidak ditemukan');

        // Customer does not see QRIS when file is missing
        $this->get(route('booking.create', $this->session))
            ->assertOk()
            ->assertDontSee('value="qris"', false);
    }

    public function test_qris_served_inline_and_downloadable(): void
    {
        $this->actingAs($this->admin);

        $this->put(route('admin.settings.update'), [
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
            'payment_methods' => ['di_tempat', 'qris'],
            'qris_image' => UploadedFile::fake()->image('qris.png', 400, 400),
        ]);

        // Inline stream
        $response = $this->get(route('payment.qris'));
        $response->assertOk();
        $this->assertStringContainsString('image/', $response->headers->get('content-type'));
        $this->assertStringContainsString('public', $response->headers->get('cache-control'));

        // Download attachment
        $downloadResponse = $this->get(route('payment.qris', ['download' => 1]));
        $downloadResponse->assertOk();
        $this->assertStringContainsString('attachment;', $downloadResponse->headers->get('content-disposition'));
    }

    public function test_customer_success_and_status_pages_display_qris_card(): void
    {
        $this->actingAs($this->admin);
        $this->put(route('admin.settings.update'), [
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
            'payment_methods' => ['di_tempat', 'qris'],
            'qris_image' => UploadedFile::fake()->image('qris.png', 400, 400),
            'qris_merchant_name' => 'Studio SynemaX',
            'qris_payment_notes' => 'Kirim bukti ke sistem atau WhatsApp.',
        ]);

        $booking = Booking::query()->create([
            'code' => 'HC-QRIS-TEST',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Randi',
            'whatsapp' => '6281122334455',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'ticket_unit_price' => 5000,
            'ticket_total' => 10000,
            'snack_total' => 0,
            'total_price' => 10000,
            'payment_method' => 'qris',
            'status' => 'menunggu_pembayaran',
            'payment_expires_at' => now()->addMinutes(15),
        ]);

        // Success Page
        $this->followingRedirects()
            ->get(route('booking.success', ['code' => $booking->code]))
            ->assertOk()
            ->assertSee('Studio SynemaX')
            ->assertSee('Salin nominal')
            ->assertSee('Simpan gambar QRIS')
            ->assertSee('Kirim bukti lewat WhatsApp');

        // Status Page
        $this->followingRedirects()
            ->post(route('booking.lookup'), [
                'code' => $booking->code,
                'whatsapp' => '081122334455',
            ])
            ->assertOk()
            ->assertSee('Studio SynemaX')
            ->assertSee('Pindai Kode QRIS')
            ->assertSee('Kirim Bukti Pembayaran');
    }

    public function test_customer_cash_booking_does_not_display_qris_card(): void
    {
        $booking = Booking::query()->create([
            'code' => 'HC-CASH-TEST',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Bambang',
            'whatsapp' => '6281122334499',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'ticket_unit_price' => 5000,
            'ticket_total' => 5000,
            'snack_total' => 0,
            'total_price' => 5000,
            'payment_method' => 'di_tempat',
            'status' => 'menunggu_konfirmasi',
        ]);

        $this->followingRedirects()
            ->get(route('booking.success', ['code' => $booking->code]))
            ->assertOk()
            ->assertDontSee('Pindai Kode QRIS');
    }

    public function test_customer_can_upload_proof_and_access_is_secured(): void
    {
        $booking = Booking::query()->create([
            'code' => 'HC-PROOF-TST',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Dinda',
            'whatsapp' => '6281987654321',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'ticket_unit_price' => 5000,
            'ticket_total' => 10000,
            'snack_total' => 0,
            'total_price' => 10000,
            'payment_method' => 'qris',
            'status' => 'menunggu_konfirmasi',
        ]);

        // Wrong WhatsApp verification fails
        $this->post(route('booking.proof.upload', ['code' => $booking->code]), [
            'whatsapp' => '081234567899',
            'payment_proof' => UploadedFile::fake()->image('proof.jpg', 600, 800),
        ])->assertSessionHasErrors('whatsapp');

        // Correct WhatsApp uploads successfully
        $response = $this->post(route('booking.proof.upload', ['code' => $booking->code]), [
            'whatsapp' => '081987654321',
            'payment_proof' => UploadedFile::fake()->image('proof.jpg', 600, 800),
        ]);
        $response->assertRedirect();
        $booking->refresh();
        $this->assertNotNull($booking->payment_proof_path);
        $this->assertTrue(Storage::disk('local')->exists($booking->payment_proof_path));

        // Unauthenticated guest without WhatsApp or session cannot view proof
        $this->flushSession();
        $this->get(route('booking.proof.show', ['code' => $booking->code]))
            ->assertForbidden();

        // Customer with verified WhatsApp query can view proof
        $this->get(route('booking.proof.show', ['code' => $booking->code, 'wa' => '081987654321']))
            ->assertOk();

        // Admin can view proof via admin route
        $this->actingAs($this->admin);
        $this->get(route('admin.bookings.proof', $booking))
            ->assertOk();

        // Admin can confirm booking and payment
        $this->patch(route('admin.bookings.status', $booking), [
            'action' => 'konfirmasi',
        ])->assertRedirect();

        $this->assertSame('terkonfirmasi', $booking->fresh()->status);
    }
}
