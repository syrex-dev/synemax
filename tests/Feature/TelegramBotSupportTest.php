<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BotFeedback;
use App\Models\Film;
use App\Models\Session;
use App\Models\User;
use App\Services\CinemaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramBotSupportTest extends TestCase
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
        $this->settings->set('telegram_bot_token', Crypt::encryptString('123456:TEST_TOKEN'), 'notifikasi');
        $this->settings->set('telegram_bot_username', 'SynemaXTestBot', 'notifikasi');
        $this->settings->set('telegram_chat_ids', '11223344', 'notifikasi');
    }

    public function test_webhook_handles_empty_or_ping_payload(): void
    {
        $response = $this->postJson('/telegram/webhook', []);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    public function test_webhook_handles_start_command(): void
    {
        $payload = [
            'update_id' => 10001,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => 999888,
                    'first_name' => 'Budi',
                    'username' => 'budicinema',
                ],
                'chat' => [
                    'id' => 999888,
                    'type' => 'private',
                ],
                'text' => '/start',
                'date' => time(),
            ],
        ];

        $response = $this->postJson('/telegram/webhook', $payload);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && str_contains($request['text'], 'Layanan Bantuan Otomatis');
        });
    }

    public function test_webhook_handles_booking_inquiry_with_code(): void
    {
        $film = Film::query()->create([
            'title' => 'Pengabdi Setan',
            'duration_minutes' => 100,
            'genre' => 'Horor',
            'age_rating' => '17+',
            'synopsis' => 'Sinopsis horor film.',
            'is_active' => true,
        ]);

        $session = Session::query()->create([
            'film_id' => $film->id,
            'session_date' => now()->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'capacity' => 6,
        ]);

        $booking = Booking::query()->create([
            'session_id' => $session->id,
            'film_id' => $film->id,
            'code' => 'HC-261123-2D60FU',
            'customer_name' => 'Budi Santoso',
            'whatsapp' => '081234567890',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'status' => 'menunggu_konfirmasi',
            'total_price' => 25000,
        ]);

        $payload = [
            'update_id' => 10002,
            'message' => [
                'message_id' => 2,
                'from' => [
                    'id' => 999888,
                    'first_name' => 'Budi',
                    'username' => 'budicinema',
                ],
                'chat' => [
                    'id' => 999888,
                    'type' => 'private',
                ],
                'text' => 'HC-261123-2D60FU',
                'date' => time(),
            ],
        ];

        $response = $this->postJson('/telegram/webhook', $payload);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && str_contains($request['text'], 'HC-261123-2D60FU')
                && str_contains($request['text'], 'Pengabdi Setan');
        });

        $this->assertDatabaseHas('bot_feedbacks', [
            'category' => 'ORDER_INQUIRY',
            'booking_code' => 'HC-261123-2D60FU',
        ]);
    }

    public function test_webhook_handles_issue_report_and_notifies_admin(): void
    {
        $payload = [
            'update_id' => 10003,
            'message' => [
                'message_id' => 3,
                'from' => [
                    'id' => 999888,
                    'first_name' => 'Budi',
                    'username' => 'budicinema',
                ],
                'chat' => [
                    'id' => 999888,
                    'type' => 'private',
                ],
                'text' => 'KELUHAN HC-261123-2D60FU Bukti transfer belum di-acc sudah 20 menit',
                'date' => time(),
            ],
        ];

        $response = $this->postJson('/telegram/webhook', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('bot_feedbacks', [
            'category' => 'ISSUE_REPORT',
            'booking_code' => 'HC-261123-2D60FU',
            'telegram_user_id' => '999888',
        ]);

        // Customer receives confirmation & Admin receives alert
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && str_contains($request['text'], 'Laporan Keluhan Anda Berhasil Dicatat');
        });

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && str_contains($request['text'], 'LAPORAN PENONTON MASUK')
                && (string) $request['chat_id'] === '11223344';
        });
    }

    public function test_admin_can_view_and_update_bot_feedbacks(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $feedback = BotFeedback::create([
            'telegram_user_id' => '12345',
            'sender_name' => 'Pelanggan Bioskop',
            'category' => 'ISSUE_REPORT',
            'message' => 'Layar proyektor agak buram',
            'status' => 'AUTO_RESOLVED',
        ]);

        $response = $this->actingAs($admin)->get('/admin/bot-feedbacks');
        $response->assertStatus(200);

        $updateResponse = $this->actingAs($admin)
            ->patch("/admin/bot-feedbacks/{$feedback->id}/status", [
                'status' => 'RESOLVED',
                'admin_note' => 'Sudah dicek dan lensa proyektor dibersihkan.',
            ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('bot_feedbacks', [
            'id' => $feedback->id,
            'status' => 'RESOLVED',
            'admin_note' => 'Sudah dicek dan lensa proyektor dibersihkan.',
        ]);
    }

    public function test_setting_test_telegram_redirects_back_for_inertia(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)
            ->from('/admin/pengaturan?tab=notifikasi')
            ->post('/admin/pengaturan/telegram/test', [], [
                'X-Inertia' => 'true',
            ]);

        $response->assertRedirect('/admin/pengaturan?tab=notifikasi');
        $response->assertSessionHas('status');
    }
}
