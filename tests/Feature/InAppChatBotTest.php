<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Session;
use App\Services\CinemaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InAppChatBotTest extends TestCase
{
    use RefreshDatabase;

    protected CinemaSettings $settings;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $this->settings = app(CinemaSettings::class);
        $this->settings->set('telegram_notifications_enabled', '1', 'notifikasi');
        $this->settings->set('telegram_admin_bot_token', Crypt::encryptString('9245078183:TEST_ADMIN_TOKEN'), 'notifikasi');
        $this->settings->set('telegram_admin_bot_username', 'SynemaXAdmin_bot', 'notifikasi');
        $this->settings->set('telegram_cs_bot_token', Crypt::encryptString('9235362155:TEST_CS_TOKEN'), 'notifikasi');
        $this->settings->set('telegram_cs_bot_username', 'SynemaXChat_bot', 'notifikasi');
        $this->settings->set('telegram_chat_ids', '99887766', 'notifikasi');
    }

    public function test_in_app_chat_endpoint_returns_main_menu(): void
    {
        $response = $this->postJson('/api/chat-bot/message', [
            'action' => 'menu_main',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'ok' => true,
        ]);
        $json = $response->json();
        $this->assertStringContainsString('Layanan Bantuan', $json['reply']);
        $this->assertNotEmpty($json['quick_replies']);
    }

    public function test_in_app_chat_endpoint_checks_booking_by_code(): void
    {
        $film = Film::query()->create([
            'title' => 'Pengabdi Setan 2',
            'duration_minutes' => 110,
            'genre' => 'Horor',
            'age_rating' => '17+',
            'synopsis' => 'Sinopsis horor film bioskop.',
            'is_active' => true,
        ]);

        $session = Session::query()->create([
            'film_id' => $film->id,
            'session_date' => now()->toDateString(),
            'start_time' => '15:00:00',
            'end_time' => '17:00:00',
            'capacity' => 6,
        ]);

        $booking = Booking::query()->create([
            'session_id' => $session->id,
            'film_id' => $film->id,
            'code' => 'HC-261123-INAPP1',
            'customer_name' => 'Budi Santoso',
            'whatsapp' => '081234567890',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'payment_method' => 'qris',
            'status' => 'menunggu_konfirmasi',
            'total_price' => 20000,
        ]);

        $response = $this->postJson('/api/chat-bot/message', [
            'message' => 'HC-261123-INAPP1',
            'session_id' => 'SESSION-TEST-123',
            'sender_name' => 'Budi Santoso',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        $json = $response->json();
        $this->assertStringContainsString('HC-261123-INAPP1', $json['reply']);
        $this->assertStringContainsString('Pengabdi Setan 2', $json['reply']);

        $this->assertDatabaseHas('bot_feedbacks', [
            'booking_code' => 'HC-261123-INAPP1',
            'category' => 'ORDER_INQUIRY',
            'booking_id' => $booking->id,
        ]);
    }

    public function test_in_app_chat_endpoint_creates_ticket_and_notifies_admin_on_issue(): void
    {
        $response = $this->postJson('/api/chat-bot/message', [
            'message' => 'KELUHAN Bukti pembayaran transfer QRIS saya belum di-ACC min tolong dibantu',
            'session_id' => 'SESSION-TEST-999',
            'sender_name' => 'Siti Aminah',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        $json = $response->json();
        $this->assertStringContainsString('Laporan Keluhan Anda Berhasil Dicatat', $json['reply']);
        $this->assertArrayHasKey('ticket', $json);

        $this->assertDatabaseHas('bot_feedbacks', [
            'category' => 'ISSUE_REPORT',
            'status' => 'AUTO_RESOLVED',
        ]);

        // Verify alert sent to Admin Telegram Chat ID
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && (string) $request['chat_id'] === '99887766'
                && str_contains($request['text'], 'LAPORAN PENONTON MASUK');
        });
    }

    public function test_in_app_chat_endpoint_shows_snacks_menu(): void
    {
        $category = MenuCategory::query()->create([
            'name' => 'Makanan Ringan',
        ]);

        MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Popcorn Karamel XL',
            'price' => 15000,
            'is_available' => true,
        ]);

        $response = $this->postJson('/api/chat-bot/message', [
            'action' => 'menu_check_snacks',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        $json = $response->json();
        $this->assertStringContainsString('Popcorn Karamel XL', $json['reply']);
        $this->assertStringContainsString('15.000', $json['reply']);
    }

    public function test_in_app_chat_endpoint_records_general_feedback(): void
    {
        $response = $this->postJson('/api/chat-bot/message', [
            'message' => 'Mohon tambahkan pemutaran film Oppenheimer minggu depan min',
            'session_id' => 'SESSION-TEST-777',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        $this->assertDatabaseHas('bot_feedbacks', [
            'category' => 'FEEDBACK',
            'status' => 'AUTO_RESOLVED',
        ]);

        // Verify alert sent to Admin Telegram Bot
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && (string) $request['chat_id'] === '99887766'
                && str_contains($request['text'], 'MASUKAN / SARAN');
        });
    }
}
