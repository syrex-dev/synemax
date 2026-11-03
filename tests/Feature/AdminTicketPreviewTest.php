<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Session;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTicketPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function createConfirmedBooking(): Booking
    {
        Setting::updateOrCreate(['key' => 'admin_whatsapp'], ['value' => '628123456789']);

        $film = Film::create([
            'title' => 'Film Uji Admin',
            'slug' => 'film-uji-admin',
            'duration_minutes' => 110,
            'age_rating' => '13+',
            'genre' => 'Drama',
            'synopsis' => 'Sinopsis film uji',
            'is_active' => true,
        ]);

        $session = Session::create([
            'film_id' => $film->id,
            'session_date' => '2026-10-31',
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'capacity' => 6,
            'status' => 'tersedia',
            'confirmed_at' => now(),
            'confirmation_method' => 'admin',
        ]);

        return Booking::create([
            'session_id' => $session->id,
            'film_id' => $film->id,
            'code' => 'TKT-ADM-1234',
            'customer_name' => 'Budi Pratama',
            'customer_phone' => '081234567890',
            'whatsapp' => '6281234567890',
            'seats_reserved' => 2,
            'seat_price' => 5000,
            'ticket_total' => 10000,
            'total_price' => 10000,
            'status' => 'terkonfirmasi',
            'payment_method' => 'qris',
            'package_type' => 'reguler',
            'pax' => 2,
        ]);
    }

    public function test_admin_ticket_preview_route_rejects_non_admin(): void
    {
        $booking = $this->createConfirmedBooking();

        $response = $this->get(route('admin.bookings.ticket', $booking));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_ticket_preview_displays_clean_preview_without_customer_chrome(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $booking = $this->createConfirmedBooking();

        $response = $this->actingAs($admin)->get(route('admin.bookings.ticket', $booking));

        $response->assertOk();
        $response->assertSee('Pratinjau tiket');
        $response->assertSee('Ini tampilan tiket yang diterima pelanggan');
        $response->assertSee('Budi Pratama');
        $response->assertSee('TKT-ADM-1234');
        $response->assertSee('Cetak');
        $response->assertSee('Kembali ke detail booking');

        // Verify strictly prohibited customer chrome
        $response->assertDontSee('Booking Lagi');
        $response->assertDontSee('Booking lagi');
        $response->assertDontSee('via WhatsApp');
        $response->assertDontSee('Ubah Jajan');
        $response->assertDontSee('wa.me');
        $response->assertDontSee('Tahapan Pesanan');
        $response->assertDontSee('Salin tautan');
    }

    public function test_customer_ticket_page_still_contains_customer_actions(): void
    {
        $booking = $this->createConfirmedBooking();

        // Customer page with session cookie verified
        $response = $this->withSession(['verified_booking_'.$booking->code => true])
            ->get(route('booking.ticket', ['code' => $booking->code]));

        $response->assertOk();
        $response->assertSee('Simpan tiket (PDF)');
        $response->assertSee('Kembali ke Beranda');
    }
}
