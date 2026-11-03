<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Session;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentThemeSyncTest extends TestCase
{
    use RefreshDatabase;

    private function createTransferBooking(): Booking
    {
        Setting::updateOrCreate(['key' => 'enabled_payment_methods'], ['value' => json_encode(['transfer', 'qris'])]);
        Setting::updateOrCreate(['key' => 'transfer_accounts'], ['value' => json_encode([
            ['bank_name' => 'BCA', 'account_number' => '1234567890', 'account_holder' => 'SYNEMAX OFFICIAL'],
            ['bank_name' => 'DANA', 'account_number' => '08123456789', 'account_holder' => 'SYNEMAX DANA'],
        ])]);

        $film = Film::create([
            'title' => 'Film Uji Coba Pembayaran',
            'slug' => 'film-uji-coba-pembayaran',
            'genre' => 'Aksi',
            'synopsis' => 'Sinopsis film uji coba.',
            'status' => 'now_playing',
            'duration_minutes' => 100,
            'age_rating' => '13+',
            'is_active' => true,
        ]);

        $session = Session::create([
            'film_id' => $film->id,
            'session_date' => now()->toDateString(),
            'start_time' => '19:00',
            'end_time' => '21:00',
            'price' => 15000,
            'status' => 'confirmed',
        ]);

        return Booking::create([
            'code' => 'TEST-THEME-TRF01',
            'session_id' => $session->id,
            'film_id' => $film->id,
            'customer_name' => 'Penonton Uji',
            'whatsapp' => '081299998888',
            'pax' => 1,
            'seat_price' => 15000,
            'ticket_total' => 15000,
            'snack_total' => 0,
            'total_price' => 15000,
            'payment_method' => 'transfer',
            'status' => 'menunggu_pembayaran',
            'payment_expires_at' => now()->addMinutes(20),
        ]);
    }

    public function test_payment_page_transfer_has_synchronized_light_and_dark_mode_tokens(): void
    {
        $booking = $this->createTransferBooking();

        $response = $this->withSession(['verified_booking_'.$booking->code => true])
            ->get(route('booking.payment', ['code' => $booking->code]));
        $response->assertOk();

        // 1. Rekening Tujuan Transfer heading has dark/light parity
        $response->assertSee('text-sm font-black text-text dark:text-white', false);
        $response->assertSee('Rekening Tujuan Transfer', false);

        // 2. Account numbers and holders have high contrast in light mode
        $response->assertSee('1234567890', false);
        $response->assertSee('08123456789', false);
        $response->assertSee('text-text dark:text-white tracking-wider', false);
        $response->assertSee('text-text dark:text-slate-200 font-bold', false);

        // 3. Petunjuk Bayar has dark/light parity
        $response->assertSee('Petunjuk Bayar via Transfer', false);
        $response->assertSee('text-text dark:text-white flex items-center gap-1.5', false);

        // 4. Kirim Bukti Pembayaran form and file input have dark/light parity
        $response->assertSee('Kirim Bukti Pembayaran', false);
        $response->assertSee('Pilih Berkas Bukti Transfer', false);
        $response->assertSee('text-amber-700 dark:file:text-amber-400', false);

        // 5. Ensure broken hardcoded classes on white background are absent
        $response->assertDontSee('text-xl font-black text-white', false);
        $response->assertDontSee('border border-white/10 bg-white/5 p-4 text-xs text-slate-300', false);
    }

    public function test_payment_page_qris_has_synchronized_light_and_dark_mode_tokens(): void
    {
        $booking = $this->createTransferBooking();
        $booking->update(['payment_method' => 'qris']);

        $response = $this->withSession(['verified_booking_'.$booking->code => true])
            ->get(route('booking.payment', ['code' => $booking->code]));
        $response->assertOk();

        // QRIS instructions heading
        $response->assertSee('Petunjuk Bayar via QRIS', false);
        $response->assertSee('text-text dark:text-white flex items-center gap-1.5', false);
    }
}
