<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Session;
use App\Services\CinemaSettings;
use App\Support\BookingStatusPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingStatusAndTicketActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite']);
    }

    private function createFilm(array $attributes = []): Film
    {
        return Film::create(array_merge([
            'title' => 'Film Uji',
            'slug' => 'film-uji-'.Str::random(6),
            'duration_minutes' => 120,
            'age_rating' => '13+',
            'genre' => 'Aksi',
            'synopsis' => 'Sinopsis film uji',
            'is_active' => true,
        ], $attributes));
    }

    private function createSession(array $attributes = []): Session
    {
        static $counter = 1;
        $counter++;

        return Session::create(array_merge([
            'session_date' => now()->addDays($counter)->toDateString(),
            'start_time' => sprintf('%02d:00:00', 8 + ($counter % 12)),
            'end_time' => sprintf('%02d:00:00', 10 + ($counter % 12)),
            'capacity' => 6,
            'status' => 'tersedia',
        ], $attributes));
    }

    private function createBooking(array $attributes = []): Booking
    {
        return Booking::create(array_merge([
            'code' => 'HC-'.strtoupper(Str::random(10)),
            'customer_name' => 'Budi Santoso',
            'whatsapp' => '081234567890',
            'pax' => 2,
            'people_count' => 2,
            'seats_reserved' => 2,
            'booking_type' => 'per_orang',
            'package' => 'reguler',
            'total_price' => 50000,
            'ticket_total' => 50000,
            'snack_total' => 0,
            'payment_method' => 'di_tempat',
            'status' => 'menunggu_konfirmasi',
        ], $attributes));
    }

    public function test_payment_status_matrix_for_all_payment_methods(): void
    {
        $session = $this->createSession([
            'status' => 'open',
            'film_id' => null,
        ]);

        $film = $this->createFilm(['title' => 'Film Uji']);

        // 1. "Bayar di tempat" - Unconfirmed must display "Bayar di tempat" (NOT "Lunas")
        $cashUnconfirmed = $this->createBooking([
            'session_id' => $session->id,
            'film_id' => $film->id,
            'payment_method' => 'di_tempat',
            'status' => 'menunggu_konfirmasi',
        ]);
        $this->assertEquals('Bayar di tempat', BookingStatusPresenter::paymentStatusLabel($cashUnconfirmed));
        $this->assertEquals('Bayar di tempat', $cashUnconfirmed->paymentStatusLabel());

        // 2. "Bayar di tempat" - Active/Terkonfirmasi session still displays "Bayar di tempat" until attended at venue
        $cashTerkonfirmasi = $this->createBooking([
            'session_id' => $session->id,
            'film_id' => $film->id,
            'payment_method' => 'di_tempat',
            'status' => 'terkonfirmasi',
        ]);
        $this->assertEquals('Bayar di tempat', $cashTerkonfirmasi->paymentStatusLabel());

        // 3. "Bayar di tempat" - Attended / Completed at venue displays "Lunas"
        $cashHadir = $this->createBooking([
            'session_id' => $session->id,
            'film_id' => $film->id,
            'payment_method' => 'di_tempat',
            'status' => 'hadir',
        ]);
        $this->assertEquals('Lunas', $cashHadir->paymentStatusLabel());

        $cashSelesai = $this->createBooking([
            'session_id' => $session->id,
            'film_id' => $film->id,
            'payment_method' => 'di_tempat',
            'status' => 'selesai',
        ]);
        $this->assertEquals('Lunas', $cashSelesai->paymentStatusLabel());

        // 4. Online Payment (QRIS / Transfer) - Confirmed displays "Lunas"
        $onlinePaid = $this->createBooking([
            'session_id' => $session->id,
            'film_id' => $film->id,
            'payment_method' => 'qris',
            'status' => 'terkonfirmasi',
        ]);
        $this->assertEquals('Lunas', $onlinePaid->paymentStatusLabel());

        // 5. Online Payment - Pending confirmation
        $onlinePending = $this->createBooking([
            'session_id' => $session->id,
            'film_id' => $film->id,
            'payment_method' => 'transfer',
            'status' => 'menunggu_konfirmasi',
        ]);
        $this->assertEquals('Menunggu konfirmasi', $onlinePending->paymentStatusLabel());

        // 6. Online Payment - Awaiting payment
        $onlineUnpaid = $this->createBooking([
            'session_id' => $session->id,
            'film_id' => $film->id,
            'payment_method' => 'transfer',
            'status' => 'menunggu_pembayaran',
        ]);
        $this->assertEquals('Belum dibayar', $onlineUnpaid->paymentStatusLabel());

        // 7. Cancelled bookings
        $cancelledWithoutRefund = $this->createBooking([
            'session_id' => $session->id,
            'film_id' => $film->id,
            'status' => 'dibatalkan',
            'refund_needed' => false,
        ]);
        $this->assertEquals('Dibatalkan', $cancelledWithoutRefund->paymentStatusLabel());

        $cancelledWithRefund = $this->createBooking([
            'session_id' => $session->id,
            'film_id' => $film->id,
            'status' => 'dibatalkan',
            'refund_needed' => true,
        ]);
        $this->assertEquals('Dibatalkan (Perlu Refund)', $cancelledWithRefund->paymentStatusLabel());
    }

    public function test_ticket_guidance_text_matrix(): void
    {
        $unconfirmedSession = $this->createSession([
            'status' => 'open',
            'film_id' => null,
        ]);

        $film = $this->createFilm(['title' => 'Film Sesi']);

        // A. Session NOT yet confirmed to air
        $bookingWaitingSession = $this->createBooking([
            'session_id' => $unconfirmedSession->id,
            'film_id' => $film->id,
            'status' => 'menunggu_konfirmasi',
        ]);
        $this->assertEquals(
            'E-tiket aktif dan dapat disimpan setelah sesi dipastikan tayang.',
            $bookingWaitingSession->ticketGuidanceText()
        );

        // B. Session IS confirmed to air
        $confirmedSession = $this->createSession([
            'status' => 'confirmed',
            'film_id' => $film->id,
            'confirmed_at' => now(),
            'confirmation_method' => 'admin',
        ]);

        $activeBooking = $this->createBooking([
            'session_id' => $confirmedSession->id,
            'film_id' => $film->id,
            'payment_method' => 'di_tempat',
            'status' => 'terkonfirmasi',
        ]);
        $this->assertEquals(
            'Tunjukkan kode ini ke petugas saat tiba.',
            $activeBooking->ticketGuidanceText()
        );

        // C. Completed booking
        $completedBooking = $this->createBooking([
            'session_id' => $confirmedSession->id,
            'film_id' => $film->id,
            'status' => 'selesai',
        ]);
        $this->assertEquals(
            'Tiket sudah digunakan.',
            $completedBooking->ticketGuidanceText()
        );

        // D. Cancelled booking
        $cancelledBooking = $this->createBooking([
            'session_id' => $confirmedSession->id,
            'film_id' => $film->id,
            'status' => 'dibatalkan',
        ]);
        $this->assertEquals(
            'Booking ini telah dibatalkan dan tiket tidak dapat digunakan.',
            $cancelledBooking->ticketGuidanceText()
        );
    }

    public function test_whatsapp_button_component_rendering_and_url(): void
    {
        $settings = app(CinemaSettings::class);
        $settings->set('whatsapp_number', '081234567890');
        $settings->set('admin_whatsapp', '081234567890');

        $film = $this->createFilm(['title' => 'Wildwood']);
        $session = $this->createSession([
            'status' => 'confirmed',
            'film_id' => $film->id,
            'session_date' => '2026-11-01',
            'start_time' => '19:00:00',
        ]);

        $booking = $this->createBooking([
            'code' => 'HC-261101-TEST',
            'session_id' => $session->id,
            'film_id' => $film->id,
            'customer_name' => 'Budi Santoso',
            'whatsapp' => '081299887766',
        ]);

        // Render blade component inline
        $rendered = (string) $this->blade('<x-ui.whatsapp-button variant="inline" :booking="$booking" />', ['booking' => $booking]);

        $this->assertStringContainsString('https://wa.me/6281234567890', $rendered);
        $this->assertStringContainsString('HC-261101-TEST', $rendered);
        $this->assertStringContainsString('Wildwood', $rendered);
        $this->assertStringContainsString('Chat admin via WhatsApp', $rendered);
        $this->assertStringContainsString('bg-[#15803D]', $rendered);
        $this->assertStringContainsString('target="_blank"', $rendered);
    }

    public function test_whatsapp_button_hidden_when_number_is_empty(): void
    {
        $settings = app(CinemaSettings::class);
        $settings->set('whatsapp_number', '');
        $settings->set('admin_whatsapp', '');
        $settings->set('cinema_phone', '');

        $rendered = (string) $this->blade('<x-ui.whatsapp-button variant="inline" />');
        $this->assertEmpty(trim($rendered));
    }
}
