<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleFlashAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_index_renders_flash_status_exactly_once_without_layout_duplicate(): void
    {
        $message = 'Booking berhasil dibatalkan dan tempat telah dilepaskan.';

        $response = $this->withSession(['status' => $message])
            ->get(route('booking.ticket.index'));

        $response->assertOk();

        // Must contain the message
        $response->assertSee($message);

        // Count occurrences of the exact status message string in rendered HTML
        $content = $response->getContent();
        $count = substr_count($content, $message);

        // It must appear EXACTLY ONCE on the page (no double banner)
        $this->assertSame(1, $count, "The flash status message '{$message}' should appear exactly once, but appeared {$count} times.");

        // The old layout amber warning banner container must NOT be present
        $this->assertStringNotContainsString('border border-warning/30 bg-warning-surface/80', $content);
    }

    public function test_ticket_show_with_diproses_does_not_render_duplicate_flash_banner(): void
    {
        $film = Film::create([
            'title' => 'Film Uji',
            'duration_minutes' => 90,
            'genre' => 'Drama',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis',
            'is_active' => true,
        ]);
        $session = Session::create([
            'film_id' => $film->id,
            'session_date' => now()->toDateString(),
            'start_time' => '14:00',
            'end_time' => '15:30',
            'price' => 25000,
            'capacity' => 10,
            'is_active' => true,
        ]);
        $booking = Booking::create([
            'session_id' => $session->id,
            'code' => 'HC-261104-TEST1',
            'status' => 'menunggu_konfirmasi',
            'customer_name' => 'John Doe',
            'whatsapp' => '081234567890',
            'ticket_count' => 1,
            'total_price' => 25000,
            'pax' => 1,
            'payment_method' => 'transfer',
            'payment_proof_path' => 'proofs/test.jpg',
        ]);

        $message = 'Bukti bayar terkirim. Admin akan memeriksa dan mengonfirmasi tiketmu.';

        $response = $this->withSession([
            'status' => $message,
            'verified_booking_'.$booking->code => true,
        ])->get(route('booking.ticket', ['code' => $booking->code]));

        $response->assertOk();

        $content = $response->getContent();

        // The status card for diproses MUST be visible
        $this->assertStringContainsString('Bukti pembayaran diterima, menunggu konfirmasi admin', $content);

        // The old layout amber warning banner must NOT be present
        $this->assertStringNotContainsString('border border-warning/30 bg-warning-surface/80', $content);

        // The green banner with identical text must NOT be duplicated above the stepper
        $count = substr_count($content, $message);
        $this->assertLessThanOrEqual(1, $count, "Message '{$message}' appeared {$count} times, should not be duplicated.");
    }

    public function test_payment_page_renders_flash_status_without_layout_duplicate(): void
    {
        $film = Film::create([
            'title' => 'Film Uji 2',
            'duration_minutes' => 90,
            'genre' => 'Aksi',
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis 2',
            'is_active' => true,
        ]);
        $session = Session::create([
            'film_id' => $film->id,
            'session_date' => now()->toDateString(),
            'start_time' => '17:00',
            'end_time' => '18:30',
            'price' => 25000,
            'capacity' => 10,
            'is_active' => true,
        ]);
        $booking = Booking::create([
            'session_id' => $session->id,
            'code' => 'HC-261104-TEST2',
            'status' => 'menunggu_pembayaran',
            'customer_name' => 'Jane Doe',
            'whatsapp' => '081234567891',
            'ticket_count' => 1,
            'total_price' => 25000,
            'pax' => 1,
            'payment_method' => 'transfer',
            'payment_expires_at' => now()->addMinutes(15),
        ]);

        $message = 'Pilihan film berhasil diubah.';

        $response = $this->withSession([
            'status' => $message,
            'verified_booking_'.$booking->code => true,
        ])->get(route('booking.payment', ['code' => $booking->code]));

        $response->assertOk();

        $content = $response->getContent();
        $count = substr_count($content, $message);

        $this->assertSame(1, $count, "The flash status message '{$message}' should appear exactly once on payment page, but appeared {$count} times.");
        $this->assertStringNotContainsString('border border-warning/30 bg-warning-surface/80', $content);
    }

    public function test_ticket_index_renders_flash_error_without_layout_duplicate(): void
    {
        $message = 'Kode booking tidak ditemukan.';

        $response = $this->withSession(['error' => $message])
            ->get(route('booking.ticket.index'));

        $response->assertOk();

        $content = $response->getContent();
        $count = substr_count($content, $message);

        $this->assertSame(1, $count, "The flash error message '{$message}' should appear exactly once on ticket index page, but appeared {$count} times.");
        $this->assertStringNotContainsString('border border-warning/30 bg-warning-surface/80', $content);
    }
}
