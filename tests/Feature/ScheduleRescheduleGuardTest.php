<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Session;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleRescheduleGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_updating_session_without_changing_time_succeeds_normally(): void
    {
        $session = Session::query()->create([
            'session_date' => Carbon::now()->addDays(2)->toDateString(),
            'start_time' => '13:00',
            'end_time' => '15:30',
            'status' => 'tersedia',
        ]);

        $response = $this->patch(route('admin.schedule.sessions.update', $session), [
            'status' => 'ditutup',
            'film_id' => null,
        ]);

        $response->assertRedirect();
        $this->assertSame('ditutup', $session->fresh()->status);
    }

    public function test_rescheduling_session_with_active_bookings_requires_in_app_confirmation(): void
    {
        $film = Film::query()->create([
            'title' => 'Film Aksi Uji',
            'duration_minutes' => 120,
            'genre' => 'Aksi',
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis film aksi uji',
            'is_active' => true,
        ]);

        $session = Session::query()->create([
            'session_date' => Carbon::now()->addDays(3)->toDateString(),
            'start_time' => '19:30',
            'end_time' => '22:00',
            'status' => 'tersedia',
        ]);

        Booking::query()->create([
            'session_id' => $session->id,
            'film_id' => $film->id,
            'code' => 'BK-TEST-001',
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '081234567890',
            'whatsapp' => '081234567890',
            'pax' => 2,
            'status' => 'terkonfirmasi',
            'seats_reserved' => 2,
            'package' => 'reguler',
            'payment_method' => 'di_tempat',
            'total_price' => 50000,
        ]);

        // Attempt to change start_time from 19:30 to 19:00 without confirmation
        $response = $this->json('PATCH', route('admin.schedule.sessions.update', $session), [
            'status' => 'tersedia',
            'film_id' => null,
            'start_time' => '19:00',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'requires_confirmation' => true,
            'reason' => 'reschedule_with_active_bookings',
            'old_time' => '19:30',
            'new_time' => '19:00',
            'affected_count' => 1,
        ]);
        $response->assertJsonPath('affected_bookings.0.customer_name', 'Budi Santoso');
        $this->assertStringContainsString('wa.me', $response->json('affected_bookings.0.wa_link'));

        // Verify session time was NOT changed yet
        $this->assertSame('19:30', substr((string) $session->fresh()->start_time, 0, 5));

        // Now provide explicit confirm_reschedule
        $confirmResponse = $this->json('PATCH', route('admin.schedule.sessions.update', $session), [
            'status' => 'tersedia',
            'film_id' => null,
            'start_time' => '19:00',
            'confirm_reschedule' => true,
        ]);

        $confirmResponse->assertOk();
        $this->assertSame('19:00', substr((string) $session->fresh()->start_time, 0, 5));
    }

    public function test_rescheduling_session_checks_unique_date_and_time_constraint(): void
    {
        $targetDate = Carbon::now()->addDays(4)->toDateString();

        Session::query()->create([
            'session_date' => $targetDate,
            'start_time' => '19:00',
            'end_time' => '21:30',
            'status' => 'tersedia',
        ]);

        $session2 = Session::query()->create([
            'session_date' => $targetDate,
            'start_time' => '16:00',
            'end_time' => '18:30',
            'status' => 'tersedia',
        ]);

        // Attempt to move session2 to 19:00 on the same date
        $response = $this->json('PATCH', route('admin.schedule.sessions.update', $session2), [
            'status' => 'tersedia',
            'start_time' => '19:00',
            'confirm_reschedule' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_time']);
    }
}
