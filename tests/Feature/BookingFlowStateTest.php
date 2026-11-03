<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Session;
use App\Models\Setting;
use App\Models\User;
use App\Services\CinemaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingFlowStateTest extends TestCase
{
    use RefreshDatabase;

    protected Film $film;

    protected Session $session;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['key' => 'seats_capacity'], ['value' => '6']);
        Setting::updateOrCreate(['key' => 'quorum_min_seats'], ['value' => '3']);
        Setting::updateOrCreate(['key' => 'minimum_public_viewers'], ['value' => '3']);
        Setting::updateOrCreate(['key' => 'ticket_unit_price'], ['value' => '25000']);
        Setting::updateOrCreate(['key' => 'couple_package_price'], ['value' => '65000']);
        Setting::updateOrCreate(['key' => 'private_package_price_weekday'], ['value' => '150000']);
        Setting::updateOrCreate(['key' => 'private_package_price_weekend'], ['value' => '175000']);
        Setting::updateOrCreate(['key' => 'payment_time_limit_minutes'], ['value' => '30']);
        app(CinemaSettings::class)->clearCache();

        $this->film = Film::query()->create([
            'title' => 'Film QA Eksklusif',
            'slug' => 'film-qa-eksklusif-'.uniqid(),
            'duration_minutes' => 100,
            'genre' => 'Aksi',
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis film eksklusif QA',
            'is_active' => true,
        ]);

        $this->session = Session::query()->create([
            'film_id' => $this->film->id,
            'session_date' => now()->addDays(2)->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'status' => 'tersedia',
        ]);
    }

    /**
     * Helper to authenticate booking session for public view.
     */
    protected function withBookingSession(Booking $booking): self
    {
        return $this->withSession(['verified_booking_'.$booking->code => true]);
    }

    public function test_menunggu_bayar_state_renders_payment_and_redirects_ticket(): void
    {
        $booking = Booking::query()->create([
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'code' => 'PAY'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'Budi Santoso',
            'whatsapp' => '6281234567890',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'seats_reserved' => 1,
            'payment_method' => 'transfer',
            'status' => 'menunggu_pembayaran',
            'total_price' => 25000,
            'ticket_total' => 25000,
            'payment_expires_at' => now()->addMinutes(25),
        ]);

        $this->assertSame('menunggu_bayar', $booking->flowState());

        // /tiket/{kode} dialihkan ke /pembayaran/{kode}
        $ticketRes = $this->withBookingSession($booking)->get(route('booking.ticket', ['code' => $booking->code]));
        $ticketRes->assertRedirect(route('booking.payment', ['code' => $booking->code]));

        // /pembayaran/{kode} merender instruksi bayar + hitung mundur
        $paymentRes = $this->withBookingSession($booking)->get(route('booking.payment', ['code' => $booking->code]));
        $paymentRes->assertOk();
        $paymentRes->assertSee('Sisa waktu pembayaran');
        $paymentRes->assertSee('Booking');
        $paymentRes->assertSee('Bayar');
        $paymentRes->assertDontSee('Butuh 0');
        $paymentRes->assertDontSee('kuorum', false);
        $paymentRes->assertDontSee('Kursi', false);
    }

    public function test_diproses_state_renders_verification_card_and_redirects_payment(): void
    {
        $booking = Booking::query()->create([
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'code' => 'PRO'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'Dewi Lestari',
            'whatsapp' => '6281234567891',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'seats_reserved' => 1,
            'payment_method' => 'transfer',
            'payment_proof_path' => 'payment-proofs/test-proof.jpg',
            'status' => 'menunggu_konfirmasi',
            'total_price' => 25000,
            'ticket_total' => 25000,
            'payment_expires_at' => now()->addMinutes(20),
        ]);

        $this->assertSame('diproses', $booking->flowState());

        // /pembayaran/{kode} dialihkan ke /tiket/{kode}
        $paymentRes = $this->withBookingSession($booking)->get(route('booking.payment', ['code' => $booking->code]));
        $paymentRes->assertRedirect(route('booking.ticket', ['code' => $booking->code]));

        // /tiket/{kode} menampilkan kartu menunggu konfirmasi admin tanpa QR
        $ticketRes = $this->withBookingSession($booking)->get(route('booking.ticket', ['code' => $booking->code]));
        $ticketRes->assertOk();
        $ticketRes->assertSee('Bukti pembayaran diterima, menunggu konfirmasi admin');
        $ticketRes->assertDontSee('data-ticket-qr', false);
        $ticketRes->assertDontSee('Butuh 0');
        $ticketRes->assertDontSee('kuorum', false);
        $ticketRes->assertDontSee('Kursi', false);
    }

    public function test_menunggu_penonton_state_renders_waiting_box_and_no_qr(): void
    {
        // Reguler bayar di tempat, sesi belum pasti tayang, masih butuh >= 1 orang
        $booking = Booking::query()->create([
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'code' => 'WAI'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'Rian Hidayat',
            'whatsapp' => '6281234567892',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'seats_reserved' => 1,
            'payment_method' => 'di_tempat',
            'status' => 'menunggu_pembayaran',
            'total_price' => 25000,
            'ticket_total' => 25000,
        ]);

        $this->assertSame('menunggu_penonton', $booking->flowState());

        $ticketRes = $this->withBookingSession($booking)->get(route('booking.ticket', ['code' => $booking->code]));
        $ticketRes->assertOk();
        $ticketRes->assertSee('Menunggu penonton');
        $ticketRes->assertSee('Perbarui');
        $ticketRes->assertSee('Butuh 2 orang lagi untuk film Film QA Eksklusif');
        $ticketRes->assertSee('1 dari 3 orang');
        $ticketRes->assertSee('Ajak teman via WhatsApp');
        $ticketRes->assertDontSee('data-ticket-qr', false);
        $ticketRes->assertDontSee('Butuh 0');
        $ticketRes->assertDontSee('kuorum', false);
        $ticketRes->assertDontSee('Kursi', false);
        $ticketRes->assertDontSee('100% Terpenuhi');
    }

    public function test_aktif_state_renders_qr_and_no_waiting_box(): void
    {
        // Sesi sudah dikonfirmasi pasti tayang
        $this->session->confirmAndLock($this->film->id, 'manual');

        $booking = Booking::query()->create([
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'code' => 'AKT'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'Siti Nurhaliza',
            'whatsapp' => '6281234567893',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'seats_reserved' => 2,
            'payment_method' => 'transfer',
            'status' => 'terkonfirmasi',
            'total_price' => 50000,
            'ticket_total' => 50000,
            'confirmed_at' => now(),
        ]);

        $this->assertSame('aktif', $booking->flowState());

        $ticketRes = $this->withBookingSession($booking)->get(route('booking.ticket', ['code' => $booking->code]));
        $ticketRes->assertOk();
        $ticketRes->assertSee('Tiket aktif');
        $ticketRes->assertSee('Simpan tiket (PDF)');
        $ticketRes->assertSee('data-ticket-qr', false);
        $ticketRes->assertDontSee('Butuh ');
        $ticketRes->assertDontSee('Perbarui');
        $ticketRes->assertDontSee('kuorum', false);
        $ticketRes->assertDontSee('Kursi', false);
    }

    public function test_private_and_couple_never_enter_menunggu_penonton(): void
    {
        $privateBooking = Booking::query()->create([
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'code' => 'PRV'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'Keluarga Wijaya',
            'whatsapp' => '6281234567894',
            'package' => 'private',
            'booking_type' => 'private',
            'pax' => 6,
            'seats_reserved' => 6,
            'payment_method' => 'transfer',
            'status' => 'terkonfirmasi',
            'total_price' => 150000,
            'ticket_total' => 150000,
            'confirmed_at' => now(),
        ]);

        $this->assertSame('aktif', $privateBooking->flowState());
        $this->assertNotSame('menunggu_penonton', $privateBooking->flowState());

        $coupleBooking = Booking::query()->create([
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'code' => 'CPL'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'Pasangan Romantis',
            'whatsapp' => '6281234567895',
            'package' => 'couple',
            'booking_type' => 'couple',
            'pax' => 2,
            'seats_reserved' => 2,
            'payment_method' => 'di_tempat',
            'status' => 'terkonfirmasi',
            'total_price' => 65000,
            'ticket_total' => 65000,
        ]);

        $this->assertSame('aktif', $coupleBooking->flowState());
        $this->assertNotSame('menunggu_penonton', $coupleBooking->flowState());
    }

    public function test_shortage_zero_automatically_activates_booking_and_confirms_session(): void
    {
        // Quorum minimal = 3 orang. Buat booking dengan 3 pax.
        $booking = Booking::query()->create([
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'code' => 'QRM'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'Rombongan Komunitas',
            'whatsapp' => '6281234567896',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 3,
            'seats_reserved' => 3,
            'payment_method' => 'di_tempat',
            'status' => 'menunggu_pembayaran',
            'total_price' => 75000,
            'ticket_total' => 75000,
        ]);

        $this->assertFalse($this->session->isConfirmed());

        // Evaluasi flowState harus otomatis mengonfirmasi sesi dan menjadikan booking aktif
        $state = $booking->flowState();

        $this->assertSame('aktif', $state);
        $this->session->refresh();
        $this->assertTrue($this->session->isConfirmed());
    }

    public function test_expired_booking_cannot_be_waiting_penonton_or_aktif(): void
    {
        $booking = Booking::query()->create([
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'code' => 'EXP'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'Pemesan Terlambat',
            'whatsapp' => '6281234567897',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'seats_reserved' => 2,
            'payment_method' => 'transfer',
            'status' => 'kedaluwarsa',
            'total_price' => 50000,
            'ticket_total' => 50000,
            'payment_expires_at' => now()->subMinutes(10),
        ]);

        $this->assertSame('kedaluwarsa', $booking->flowState());
        $this->assertNotSame('menunggu_penonton', $booking->flowState());
        $this->assertNotSame('aktif', $booking->flowState());

        $ticketRes = $this->withBookingSession($booking)->get(route('booking.ticket', ['code' => $booking->code]));
        $ticketRes->assertOk();
        $ticketRes->assertSee('Batas waktu pembayaran habis dan tempat pesanan telah dilepas.');
        $ticketRes->assertSee('Pesan sesi lain');
        $ticketRes->assertDontSee('data-ticket-qr', false);
    }

    public function test_admin_confirming_expired_booking_checks_session_capacity(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $expiredBooking = Booking::query()->create([
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'code' => 'EXA'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'Pemesan Reaktivasi',
            'whatsapp' => '6281234567898',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'seats_reserved' => 2,
            'payment_method' => 'transfer',
            'status' => 'kedaluwarsa',
            'total_price' => 50000,
            'ticket_total' => 50000,
            'payment_expires_at' => now()->subMinutes(10),
        ]);

        // Kasus 1: Sesi sudah penuh oleh pemesan lain (kapasitas 6)
        Booking::query()->create([
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'code' => 'FUL'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'Pemesan Penuh',
            'whatsapp' => '6281234567899',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 6,
            'seats_reserved' => 6,
            'payment_method' => 'transfer',
            'status' => 'terkonfirmasi',
            'total_price' => 150000,
            'ticket_total' => 150000,
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('admin.bookings.status', $expiredBooking), ['action' => 'konfirmasi']);

        // Harus ditolak dengan pesan kesalahan kapasitas dan tidak lunas
        $response->assertSessionHasErrors();
        $expiredBooking->refresh();
        $this->assertSame('kedaluwarsa', $expiredBooking->status);
    }
}
