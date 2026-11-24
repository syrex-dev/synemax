<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BotFeedback;
use App\Models\Film;
use App\Models\Session;
use App\Models\User;
use App\Services\CinemaSettings;
use App\Services\Telegram\TelegramBotService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MultiAdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);
    }

    private function createFilmAndSession(): array
    {
        $film = Film::query()->create([
            'title' => 'Film Horor Uji Coba',
            'slug' => 'film-horor-uji-coba-'.uniqid(),
            'genre' => 'Horor',
            'age_rating' => '17+',
            'synopsis' => 'Sinopsis film uji coba bioskop mini.',
            'duration_minutes' => 100,
            'is_active' => true,
        ]);

        $session = Session::query()->create([
            'film_id' => $film->id,
            'session_date' => now()->addDays(2)->toDateString(),
            'start_time' => '14:00',
            'end_time' => '16:00',
            'status' => 'tersedia',
            'capacity' => 6,
        ]);

        return [$film, $session];
    }

    private function createBooking(string $status = 'menunggu_pembayaran', string $code = 'HC-TEST-1'): Booking
    {
        [$film, $session] = $this->createFilmAndSession();

        return Booking::query()->create([
            'session_id' => $session->id,
            'film_id' => $film->id,
            'status' => $status,
            'code' => $code,
            'customer_name' => 'Penonton Uji',
            'whatsapp' => '081234567890',
            'booking_type' => 'reguler',
            'package' => 'reguler',
            'pax' => 2,
            'seats_reserved' => 2,
            'total_price' => 50000,
            'ticket_total' => 50000,
            'snack_total' => 0,
            'payment_method' => 'qris',
        ]);
    }

    public function test_telegram_admin_claim_and_anti_collision_workflow(): void
    {
        $booking = $this->createBooking('menunggu_pembayaran', 'HC-TEST-CLAIM1');

        $botService = app(TelegramBotService::class);

        // Admin 1 claims
        $botService->handleAdminCallback([
            'id' => 'q1',
            'data' => 'admin_claim:HC-TEST-CLAIM1',
            'from' => [
                'id' => 111111,
                'first_name' => 'Budi',
                'last_name' => 'Admin 1',
                'username' => 'budiadmin',
            ],
            'message' => [
                'chat' => ['id' => 111111],
            ],
        ]);

        $booking->refresh();
        $this->assertEquals('Budi Admin 1', $booking->claimed_by_name);
        $this->assertEquals('tg:111111', $booking->claimed_by_id);
        $this->assertNotNull($booking->claimed_at);

        // Admin 2 attempts to claim same booking -> must be rejected without changing claim
        $botService->handleAdminCallback([
            'id' => 'q2',
            'data' => 'admin_claim:HC-TEST-CLAIM1',
            'from' => [
                'id' => 222222,
                'first_name' => 'Siti',
                'last_name' => 'Admin 2',
                'username' => 'sitiadmin',
            ],
            'message' => [
                'chat' => ['id' => 222222],
            ],
        ]);

        $booking->refresh();
        $this->assertEquals('Budi Admin 1', $booking->claimed_by_name);
        $this->assertEquals('tg:111111', $booking->claimed_by_id);
    }

    public function test_telegram_admin_confirm_sets_confirmed_by_name(): void
    {
        $booking = $this->createBooking('menunggu_konfirmasi', 'HC-TEST-CONFIRM1');

        $botService = app(TelegramBotService::class);

        $botService->handleAdminCallback([
            'id' => 'q3',
            'data' => 'admin_confirm:HC-TEST-CONFIRM1',
            'from' => [
                'id' => 111111,
                'first_name' => 'Budi',
                'last_name' => 'Admin 1',
                'username' => 'budiadmin',
            ],
            'message' => [
                'chat' => ['id' => 111111],
            ],
        ]);

        $booking->refresh();
        $this->assertEquals('terkonfirmasi', $booking->status);
        $this->assertEquals('Budi Admin 1', $booking->confirmed_by_name);
    }

    public function test_web_admin_booking_claim_and_release_actions(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Admin Web Utama']);
        $booking = $this->createBooking('menunggu_pembayaran', 'HC-TEST-WEB1');

        // Claim via web
        $this->actingAs($admin)
            ->patch("/admin/booking/{$booking->id}/status", ['action' => 'ambil'])
            ->assertRedirect();

        $booking->refresh();
        $this->assertEquals('Admin Web Utama', $booking->claimed_by_name);
        $this->assertEquals("web:{$admin->id}", $booking->claimed_by_id);

        // Release claim via web
        $this->actingAs($admin)
            ->patch("/admin/booking/{$booking->id}/status", ['action' => 'lepas_klaim'])
            ->assertRedirect();

        $booking->refresh();
        $this->assertNull($booking->claimed_by_name);
        $this->assertNull($booking->claimed_by_id);
    }

    public function test_feedback_cleanup_command_deletes_records_older_than_retention_days(): void
    {
        app(CinemaSettings::class)->set('feedback_retention_days', '14', 'notifikasi');

        // Old feedback (> 14 days)
        $old = BotFeedback::create([
            'category' => 'ISSUE_REPORT',
            'message' => 'Laporan lama AC kurang dingin',
        ]);
        DB::table('bot_feedbacks')->where('id', $old->id)->update([
            'created_at' => Carbon::now()->subDays(20),
            'updated_at' => Carbon::now()->subDays(20),
        ]);

        // Recent feedback (3 days)
        $recent = BotFeedback::create([
            'category' => 'FEEDBACK',
            'message' => 'Laporan baru audio bagus',
        ]);
        DB::table('bot_feedbacks')->where('id', $recent->id)->update([
            'created_at' => Carbon::now()->subDays(3),
            'updated_at' => Carbon::now()->subDays(3),
        ]);

        $this->artisan('feedback:cleanup')
            ->expectsOutputToContain('Berhasil membersihkan')
            ->assertSuccessful();

        $this->assertDatabaseMissing('bot_feedbacks', ['message' => 'Laporan lama AC kurang dingin']);
        $this->assertDatabaseHas('bot_feedbacks', ['id' => $recent->id]);
    }

    public function test_web_admin_can_trigger_manual_feedback_cleanup(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $old = BotFeedback::create([
            'category' => 'ISSUE_REPORT',
            'message' => 'Laporan sangat lama',
        ]);
        DB::table('bot_feedbacks')->where('id', $old->id)->update([
            'created_at' => Carbon::now()->subDays(40),
            'updated_at' => Carbon::now()->subDays(40),
        ]);

        $this->actingAs($admin)
            ->post('/admin/bot-feedbacks/cleanup', ['days' => 30])
            ->assertRedirect();

        $this->assertDatabaseMissing('bot_feedbacks', ['message' => 'Laporan sangat lama']);
    }
}
