<?php

namespace Tests\Feature;

use App\Models\ScheduleTemplate;
use App\Models\Session;
use App\Models\User;
use App\Services\SessionGenerationService;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleStandardSessionTimesTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_initializes_standard_session_templates(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Senin–Kamis: 4 sesi (12:00, 14:00, 16:00, 18:00)
        $weekdayTimes = ScheduleTemplate::query()
            ->where('day_type', 'weekday')
            ->where('is_active', true)
            ->orderBy('start_time')
            ->pluck('start_time')
            ->map(fn ($t) => substr((string) $t, 0, 5))
            ->all();

        $this->assertSame(['12:00', '14:00', '16:00', '18:00'], $weekdayTimes);

        // Jumat: 5 sesi (09:00, 11:00, 13:00, 15:00, 18:00)
        $jumatTimes = ScheduleTemplate::query()
            ->where('day_type', 'jumat')
            ->where('is_active', true)
            ->orderBy('start_time')
            ->pluck('start_time')
            ->map(fn ($t) => substr((string) $t, 0, 5))
            ->all();

        $this->assertSame(['09:00', '11:00', '13:00', '15:00', '18:00'], $jumatTimes);

        // Weekend (Sabtu–Minggu): 6 sesi (07:00, 09:00, 11:00, 13:00, 15:00, 18:00)
        $weekendTimes = ScheduleTemplate::query()
            ->where('day_type', 'weekend')
            ->where('is_active', true)
            ->orderBy('start_time')
            ->pluck('start_time')
            ->map(fn ($t) => substr((string) $t, 0, 5))
            ->all();

        $this->assertSame(['07:00', '09:00', '11:00', '13:00', '15:00', '18:00'], $weekendTimes);

        // Libur: 6 sesi (07:00, 09:00, 11:00, 13:00, 15:00, 18:00)
        $liburTimes = ScheduleTemplate::query()
            ->where('day_type', 'libur')
            ->where('is_active', true)
            ->orderBy('start_time')
            ->pluck('start_time')
            ->map(fn ($t) => substr((string) $t, 0, 5))
            ->all();

        $this->assertSame(['07:00', '09:00', '11:00', '13:00', '15:00', '18:00'], $liburTimes);

        // Tidak boleh ada template jam 19:30 sama sekali
        $this->assertDatabaseMissing('schedule_templates', ['start_time' => '19:30']);
        $this->assertDatabaseMissing('schedule_templates', ['start_time' => '19:30:00']);
    }

    public function test_generation_produces_configured_standard_sessions(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Uji untuk pekan mendatang: Senin hingga Minggu
        $monday = CarbonImmutable::now('Asia/Jakarta')->next(CarbonImmutable::MONDAY)->startOfDay();
        app(SessionGenerationService::class)->generateDays(7, $monday);

        // Senin (Weekday): 4 sesi
        $mondaySessions = Session::query()
            ->where('session_date', $monday->toDateString())
            ->where('status', 'tersedia')
            ->orderBy('start_time')
            ->pluck('start_time')
            ->map(fn ($t) => substr((string) $t, 0, 5))
            ->all();
        $this->assertSame(['12:00', '14:00', '16:00', '18:00'], $mondaySessions);

        // Kamis (Weekday): 4 sesi
        $thursdaySessions = Session::query()
            ->where('session_date', $monday->addDays(3)->toDateString())
            ->where('status', 'tersedia')
            ->orderBy('start_time')
            ->pluck('start_time')
            ->map(fn ($t) => substr((string) $t, 0, 5))
            ->all();
        $this->assertSame(['12:00', '14:00', '16:00', '18:00'], $thursdaySessions);

        // Jumat: 5 sesi
        $fridaySessions = Session::query()
            ->where('session_date', $monday->addDays(4)->toDateString())
            ->where('status', 'tersedia')
            ->orderBy('start_time')
            ->pluck('start_time')
            ->map(fn ($t) => substr((string) $t, 0, 5))
            ->all();
        $this->assertSame(['09:00', '11:00', '13:00', '15:00', '18:00'], $fridaySessions);

        // Sabtu: 6 sesi
        $saturdaySessions = Session::query()
            ->where('session_date', $monday->addDays(5)->toDateString())
            ->where('status', 'tersedia')
            ->orderBy('start_time')
            ->pluck('start_time')
            ->map(fn ($t) => substr((string) $t, 0, 5))
            ->all();
        $this->assertSame(['07:00', '09:00', '11:00', '13:00', '15:00', '18:00'], $saturdaySessions);

        // Minggu: 6 sesi
        $sundaySessions = Session::query()
            ->where('session_date', $monday->addDays(6)->toDateString())
            ->where('status', 'tersedia')
            ->orderBy('start_time')
            ->pluck('start_time')
            ->map(fn ($t) => substr((string) $t, 0, 5))
            ->all();
        $this->assertSame(['07:00', '09:00', '11:00', '13:00', '15:00', '18:00'], $sundaySessions);
    }

    public function test_admin_schedule_page_displays_18_00_wib_and_does_not_contain_19_30(): void
    {
        $this->seed(DatabaseSeeder::class);
        app(SessionGenerationService::class)->generateDays(14);

        $admin = User::query()->where('is_admin', true)->firstOrFail();
        $response = $this->actingAs($admin)->get(route('admin.schedule.index'));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('18.00 WIB', $content);
        $this->assertStringNotContainsString('19.30 WIB', $content);
    }
}
