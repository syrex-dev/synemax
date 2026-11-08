<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Member;
use App\Models\MemberVisit;
use App\Models\Session;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleSessionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($this->admin);
    }

    public function test_admin_can_add_new_session_for_specific_date(): void
    {
        $film = Film::query()->create([
            'title' => 'Film Sesi Baru',
            'duration_minutes' => 110,
            'genre' => 'Drama',
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis',
            'is_active' => true,
        ]);

        $targetDate = Carbon::now()->addDays(5)->toDateString();

        $response = $this->post(route('admin.schedule.sessions.store'), [
            'session_date' => $targetDate,
            'start_time' => '14:00',
            'duration_minutes' => 150,
            'film_id' => $film->id,
            'is_private' => 1,
            'status' => 'tersedia',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('screening_sessions', [
            'session_date' => $targetDate,
            'start_time' => '14:00:00',
            'end_time' => '16:30:00',
            'film_id' => $film->id,
            'is_private' => 1,
            'status' => 'tersedia',
        ]);
    }

    public function test_adding_session_with_duplicate_time_on_same_date_is_rejected(): void
    {
        $targetDate = Carbon::now()->addDays(4)->toDateString();

        Session::query()->create([
            'session_date' => $targetDate,
            'start_time' => '19:00:00',
            'end_time' => '21:30:00',
            'status' => 'tersedia',
        ]);

        $response = $this->post(route('admin.schedule.sessions.store'), [
            'session_date' => $targetDate,
            'start_time' => '19:00',
            'duration_minutes' => 150,
            'status' => 'tersedia',
        ]);

        $response->assertSessionHasErrors('start_time');
    }

    public function test_admin_can_update_session_details_and_duration(): void
    {
        $film = Film::query()->create([
            'title' => 'Film Terpilih',
            'duration_minutes' => 90,
            'genre' => 'Komedi',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis',
            'is_active' => true,
        ]);

        $session = Session::query()->create([
            'session_date' => Carbon::now()->addDays(3)->toDateString(),
            'start_time' => '16:00:00',
            'end_time' => '18:30:00',
            'status' => 'tersedia',
            'is_private' => false,
        ]);

        $response = $this->patch(route('admin.schedule.sessions.update', $session), [
            'film_id' => $film->id,
            'status' => 'ditutup',
            'duration_minutes' => 120,
            'is_private' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $fresh = $session->fresh();
        $this->assertSame($film->id, $fresh->film_id);
        $this->assertSame('ditutup', $fresh->status);
        $this->assertTrue($fresh->is_private);
        $this->assertSame('18:00:00', $fresh->end_time);
    }

    public function test_admin_can_safely_delete_empty_session(): void
    {
        $session = Session::query()->create([
            'session_date' => Carbon::now()->addDays(2)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '12:30:00',
            'status' => 'tersedia',
        ]);

        $response = $this->delete(route('admin.schedule.sessions.destroy', $session));

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseMissing('screening_sessions', [
            'id' => $session->id,
        ]);
    }

    public function test_admin_cannot_delete_session_with_active_bookings(): void
    {
        $film = Film::query()->create([
            'title' => 'Film Ramai',
            'duration_minutes' => 100,
            'genre' => 'Horor',
            'age_rating' => '17+',
            'synopsis' => 'Sinopsis',
            'is_active' => true,
        ]);

        $session = Session::query()->create([
            'session_date' => Carbon::now()->addDays(2)->toDateString(),
            'start_time' => '19:00:00',
            'end_time' => '21:30:00',
            'status' => 'tersedia',
        ]);

        Booking::query()->create([
            'session_id' => $session->id,
            'film_id' => $film->id,
            'code' => 'SYX-TEST-DELETE',
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '081234567890',
            'whatsapp' => '081234567890',
            'pax' => 2,
            'seats_reserved' => 2,
            'status' => 'dikonfirmasi',
            'package' => 'reguler',
            'payment_method' => 'di_tempat',
            'total_price' => 50000,
        ]);

        $response = $this->delete(route('admin.schedule.sessions.destroy', $session));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('screening_sessions', [
            'id' => $session->id,
        ]);
    }

    public function test_admin_cannot_delete_session_with_member_visits(): void
    {
        $member = Member::query()->create([
            'member_code' => 'MBR-9999-DELETE',
            'name' => 'Member Setia',
            'status' => 'aktif',
            'cycle_number' => 1,
            'cycle_visit_count' => 1,
            'total_visits_all_time' => 1,
        ]);

        $session = Session::query()->create([
            'session_date' => Carbon::now()->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '15:30:00',
            'status' => 'tersedia',
        ]);

        MemberVisit::query()->create([
            'member_id' => $member->id,
            'session_id' => $session->id,
            'cycle_number' => 1,
            'visit_number_in_cycle' => 1,
            'visited_date' => Carbon::now()->toDateString(),
            'visited_time' => '13:05:00',
            'active' => 1,
        ]);

        $response = $this->delete(route('admin.schedule.sessions.destroy', $session));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('screening_sessions', [
            'id' => $session->id,
        ]);
    }

    public function test_member_scan_view_and_alias_route_work(): void
    {
        $response = $this->get(route('admin.members.scan'));
        $response->assertOk();
        $response->assertSee('Scan Member');
        $response->assertSee('Stempel Loyalitas');

        $aliasResponse = $this->get('/scan-member');
        $aliasResponse->assertRedirect(route('admin.members.scan'));
    }
}
