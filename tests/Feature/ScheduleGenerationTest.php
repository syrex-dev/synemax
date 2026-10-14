<?php

namespace Tests\Feature;

use App\Models\BlockedDate;
use App\Models\Holiday;
use App\Models\ScheduleTemplate;
use App\Models\Session;
use App\Services\ScheduleResolver;
use App\Services\SessionGenerationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_holiday_template_overrides_the_weekday_template(): void
    {
        $date = CarbonImmutable::create(2026, 10, 13, 0, 0, 0, 'Asia/Jakarta');

        Holiday::query()->create(['holiday_date' => $date->toDateString(), 'name' => 'Hari Desa']);
        ScheduleTemplate::query()->create([
            'day_type' => 'libur',
            'start_time' => '09:00',
            'duration_minutes' => 150,
            'is_active' => true,
        ]);
        ScheduleTemplate::query()->create([
            'day_type' => 'weekday',
            'start_time' => '16:00',
            'duration_minutes' => 150,
            'is_active' => true,
        ]);

        $created = app(SessionGenerationService::class)->generateDays(1, $date);

        $this->assertSame(1, $created);
        $this->assertDatabaseHas('screening_sessions', [
            'session_date' => $date->toDateString(),
            'start_time' => '09:00',
        ]);
        $this->assertDatabaseMissing('screening_sessions', [
            'session_date' => $date->toDateString(),
            'start_time' => '16:00',
        ]);
    }

    public function test_a_full_day_block_takes_priority_over_a_holiday(): void
    {
        $date = CarbonImmutable::create(2026, 10, 13, 0, 0, 0, 'Asia/Jakarta');

        Holiday::query()->create(['holiday_date' => $date->toDateString(), 'name' => 'Hari Desa']);
        BlockedDate::query()->create([
            'blocked_date' => $date->toDateString(),
            'scope_key' => 'ALL',
            'reason' => 'Perawatan studio',
        ]);
        ScheduleTemplate::query()->create([
            'day_type' => 'libur',
            'start_time' => '09:00',
            'duration_minutes' => 150,
            'is_active' => true,
        ]);

        $created = app(SessionGenerationService::class)->generateDays(1, $date);

        $this->assertSame(0, $created);
        $this->assertDatabaseCount('screening_sessions', 0);
        $this->assertNull(app(ScheduleResolver::class)->dayTypeFor($date));
    }

    public function test_friday_and_weekend_use_their_own_schedule_templates(): void
    {
        $friday = CarbonImmutable::create(2026, 10, 16, 0, 0, 0, 'Asia/Jakarta');
        ScheduleTemplate::query()->create([
            'day_type' => 'jumat',
            'start_time' => '13:00',
            'duration_minutes' => 150,
            'is_active' => true,
        ]);
        ScheduleTemplate::query()->create([
            'day_type' => 'weekend',
            'start_time' => '09:00',
            'duration_minutes' => 150,
            'is_active' => true,
        ]);

        app(SessionGenerationService::class)->generateDays(3, $friday);

        $this->assertDatabaseHas('screening_sessions', [
            'session_date' => '2026-10-16',
            'start_time' => '13:00',
        ]);
        $this->assertDatabaseHas('screening_sessions', [
            'session_date' => '2026-10-17',
            'start_time' => '09:00',
        ]);
        $this->assertDatabaseHas('screening_sessions', [
            'session_date' => '2026-10-18',
            'start_time' => '09:00',
        ]);
        $this->assertSame('jumat', app(ScheduleResolver::class)->dayTypeFor($friday));
        $this->assertSame('weekend', app(ScheduleResolver::class)->dayTypeFor($friday->addDay()));
    }

    public function test_slot_generation_is_idempotent_and_respects_blocked_slots(): void
    {
        $date = CarbonImmutable::create(2026, 10, 13, 0, 0, 0, 'Asia/Jakarta');

        ScheduleTemplate::query()->create([
            'day_type' => 'weekday',
            'start_time' => '16:00',
            'duration_minutes' => 180,
            'is_active' => true,
        ]);
        BlockedDate::query()->create([
            'blocked_date' => $date->toDateString(),
            'scope_key' => '16:00',
            'reason' => 'Mati listrik',
        ]);

        $generator = app(SessionGenerationService::class);

        $this->assertSame(0, $generator->generateDays(1, $date));
        $this->assertSame(0, $generator->generateDays(1, $date));
        $this->assertDatabaseCount('screening_sessions', 0);
    }

    public function test_existing_session_is_not_overwritten_during_regeneration(): void
    {
        $date = CarbonImmutable::create(2026, 10, 13, 0, 0, 0, 'Asia/Jakarta');

        ScheduleTemplate::query()->create([
            'day_type' => 'weekday',
            'start_time' => '16:00',
            'duration_minutes' => 150,
            'is_active' => true,
        ]);
        Session::query()->create([
            'session_date' => $date->toDateString(),
            'start_time' => '16:00',
            'end_time' => '18:00',
            'capacity' => 4,
            'status' => 'ditutup',
        ]);

        $created = app(SessionGenerationService::class)->generateDays(1, $date);

        $this->assertSame(0, $created);
        $this->assertDatabaseHas('screening_sessions', [
            'session_date' => $date->toDateString(),
            'start_time' => '16:00',
            'end_time' => '18:00',
            'capacity' => 4,
            'status' => 'ditutup',
        ]);
    }

    public function test_regeneration_applies_duration_changes_and_closes_removed_slots(): void
    {
        $date = CarbonImmutable::create(2026, 10, 13, 0, 0, 0, 'Asia/Jakarta');
        $template = ScheduleTemplate::query()->create([
            'day_type' => 'weekday',
            'start_time' => '16:00',
            'duration_minutes' => 150,
            'is_active' => true,
        ]);
        $generator = app(SessionGenerationService::class);

        $this->assertSame(1, $generator->generateDays(1, $date));

        $template->update(['duration_minutes' => 180]);
        $this->assertSame(0, $generator->generateDays(1, $date));
        $this->assertDatabaseHas('screening_sessions', [
            'session_date' => $date->toDateString(),
            'start_time' => '16:00',
            'end_time' => '19:00:00',
            'status' => 'tersedia',
        ]);

        $template->update(['start_time' => '17:00']);
        $this->assertSame(1, $generator->generateDays(1, $date));
        $this->assertDatabaseHas('screening_sessions', [
            'session_date' => $date->toDateString(),
            'start_time' => '16:00',
            'status' => 'ditutup',
        ]);
        $this->assertDatabaseHas('screening_sessions', [
            'session_date' => $date->toDateString(),
            'start_time' => '17:00',
            'status' => 'tersedia',
        ]);
    }
}
