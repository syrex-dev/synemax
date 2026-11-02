<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\NotificationOutbox;
use App\Models\Session;
use App\Models\Setting;
use App\Models\User;
use App\Services\CinemaSettings;
use App\Services\TelegramNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramOutboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['key' => 'seats_capacity'], ['value' => '6']);
        Setting::updateOrCreate(['key' => 'telegram_notifications_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'telegram_bot_token'], ['value' => Crypt::encryptString('123456:AAHabcde_sample_token_xyz')]);
        Setting::updateOrCreate(['key' => 'telegram_chat_ids'], ['value' => '-100123456789']);
        Setting::updateOrCreate(['key' => 'telegram_notify_booking_created'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'telegram_notify_payment_confirmed'], ['value' => '1']);
        app(CinemaSettings::class)->clearCache();
    }

    /**
     * Test queueing adds record to notification_outbox table.
     */
    public function test_queueing_creates_outbox_record(): void
    {
        $service = app(TelegramNotificationService::class);

        $film = Film::query()->create([
            'title' => 'Film QA Outbox',
            'slug' => 'film-qa-outbox-'.uniqid(),
            'duration_minutes' => 90,
            'genre' => 'Drama',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis',
            'is_active' => true,
        ]);

        $session = Session::query()->create([
            'film_id' => $film->id,
            'session_date' => now()->addDays(2)->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'status' => 'tersedia',
        ]);

        $booking = Booking::query()->create([
            'session_id' => $session->id,
            'code' => 'TG'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'Telegram Customer',
            'whatsapp' => '6281234567800',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'payment_method' => 'di_tempat',
            'status' => 'menunggu_konfirmasi',
            'total_price' => 40000,
        ]);

        $outbox = $service->queue('booking_created', $booking);

        $this->assertNotNull($outbox);
        $this->assertDatabaseHas('notification_outbox', [
            'id' => $outbox->id,
            'channel' => 'telegram',
            'event' => 'booking_created',
            'booking_id' => $booking->id,
            'status' => 'pending',
            'attempts' => 0,
        ]);
    }

    /**
     * Test dispatchItem sends message via Http::fake() with HTML parse mode and NO emojis.
     */
    public function test_dispatch_item_sends_html_without_emojis(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 999]], 200),
        ]);

        $service = app(TelegramNotificationService::class);

        $film = Film::query()->create([
            'title' => 'Film QA Dispatch',
            'slug' => 'film-qa-dispatch-'.uniqid(),
            'duration_minutes' => 90,
            'genre' => 'Drama',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis',
            'is_active' => true,
        ]);

        $session = Session::query()->create([
            'film_id' => $film->id,
            'session_date' => now()->addDays(2)->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'status' => 'tersedia',
        ]);

        $booking = Booking::query()->create([
            'session_id' => $session->id,
            'code' => 'DS'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'Dispatch Tester',
            'whatsapp' => '6281234567801',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'payment_method' => 'di_tempat',
            'status' => 'menunggu_konfirmasi',
            'total_price' => 40000,
        ]);

        $item = NotificationOutbox::query()->create([
            'channel' => 'telegram',
            'event' => 'booking_created',
            'booking_id' => $booking->id,
            'payload' => [],
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => now(),
        ]);

        $success = $service->dispatchItem($item);
        $this->assertTrue($success);

        $item->refresh();
        $this->assertSame('sent', $item->status);
        $this->assertNotNull($item->sent_at);

        // Strict emoji pattern check: no emojis or pictographs allowed
        $emojiPattern = '/[\p{Extended_Pictographic}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u';

        Http::assertSent(function ($request) use ($emojiPattern) {
            $data = $request->data();
            $text = $data['text'] ?? '';
            $parseMode = $data['parse_mode'] ?? '';

            $hasNoEmojis = preg_match($emojiPattern, $text) === 0;
            $isHtml = $parseMode === 'HTML';

            return $request->url() === 'https://api.telegram.org/bot123456:AAHabcde_sample_token_xyz/sendMessage'
                && $isHtml
                && $hasNoEmojis;
        });
    }

    /**
     * Test retry backoff on delivery failure.
     */
    public function test_retry_backoff_on_failure(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Internal Server Error'], 500),
        ]);

        $service = app(TelegramNotificationService::class);

        $item = NotificationOutbox::query()->create([
            'channel' => 'telegram',
            'event' => 'booking_created',
            'booking_id' => null,
            'payload' => ['message' => 'Uji coba server error'],
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => now(),
        ]);

        $success = $service->dispatchItem($item);
        $this->assertFalse($success);

        $item->refresh();
        $this->assertSame(1, $item->attempts);
        $this->assertSame('pending', $item->status);
        // Delay for attempt 1 is +1 minute
        $this->assertNotNull($item->available_at);
        $this->assertGreaterThan(now()->addSeconds(30), $item->available_at);
    }

    /**
     * Test 429 retry_after sets available_at accordingly.
     */
    public function test_rate_limit_429_sets_retry_after(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response([
                'ok' => false,
                'description' => 'Too Many Requests',
                'parameters' => ['retry_after' => 75],
            ], 429),
        ]);

        $service = app(TelegramNotificationService::class);

        $item = NotificationOutbox::query()->create([
            'channel' => 'telegram',
            'event' => 'booking_created',
            'booking_id' => null,
            'payload' => ['message' => 'Uji coba 429'],
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => now(),
        ]);

        $success = $service->dispatchItem($item);
        $this->assertFalse($success);

        $item->refresh();
        $this->assertStringContainsString('Rate limited (429)', (string) $item->last_error);
        $diff = now()->diffInSeconds($item->available_at, false);
        $this->assertGreaterThanOrEqual(70, $diff);
        $this->assertLessThanOrEqual(80, $diff);
    }

    /**
     * Test token masking and encryption.
     */
    public function test_token_masking_and_encryption(): void
    {
        $service = app(TelegramNotificationService::class);

        $masked = $service->getMaskedToken();
        // Expect ••••••:AAH...xyz
        $this->assertSame('••••••:AAH...xyz', $masked);

        $decrypted = $service->getDecryptedToken();
        $this->assertSame('123456:AAHabcde_sample_token_xyz', $decrypted);
    }

    /**
     * Test admin endpoints for test message and chat detection.
     */
    public function test_admin_telegram_endpoints(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        Http::fake([
            'api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true, 'result' => ['message_id' => 101]], 200),
            'api.telegram.org/bot*/getUpdates' => Http::response([
                'ok' => true,
                'result' => [
                    [
                        'update_id' => 12345,
                        'message' => [
                            'chat' => [
                                'id' => -100987654321,
                                'type' => 'group',
                                'title' => 'Grup SynemaX Admin',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        // 1. Test message endpoint
        $testResponse = $this->actingAs($admin)
            ->post(route('admin.settings.telegram.test'));

        $testResponse->assertOk();
        $testResponse->assertJson(['success' => true]);

        // 2. Chat ID detect endpoint
        $detectResponse = $this->actingAs($admin)
            ->post(route('admin.settings.telegram.detect'));

        $detectResponse->assertOk();
        $detectResponse->assertJson(['success' => true]);
        $chats = $detectResponse->json('chats');
        $this->assertIsArray($chats);
        $this->assertNotEmpty($chats);
        $this->assertSame('-100987654321', (string) $chats[0]['id']);
    }
}
