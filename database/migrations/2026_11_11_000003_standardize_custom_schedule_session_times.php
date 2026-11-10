<?php

use App\Models\Session;
use App\Services\ScheduleResolver;
use App\Services\SessionGenerationService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $standardTemplates = [
            'weekday' => ['12:00', '14:00', '16:00', '18:00'],
            'jumat' => ['09:00', '11:00', '13:00', '15:00', '18:00'],
            'weekend' => ['07:00', '09:00', '11:00', '13:00', '15:00', '18:00'],
            'libur' => ['07:00', '09:00', '11:00', '13:00', '15:00', '18:00'],
        ];

        // 1. Perbarui template standar di tabel schedule_templates
        if (Schema::hasTable('schedule_templates')) {
            foreach ($standardTemplates as $dayType => $times) {
                foreach ($times as $startTime) {
                    DB::table('schedule_templates')->updateOrInsert(
                        [
                            'day_type' => $dayType,
                            'start_time' => $startTime,
                        ],
                        [
                            'duration_minutes' => 120,
                            'is_active' => true,
                            'updated_at' => now(),
                        ]
                    );
                }

                // Hapus template yang tidak sesuai daftar untuk tipe hari ini
                DB::table('schedule_templates')
                    ->where('day_type', $dayType)
                    ->whereNotIn('start_time', $times)
                    ->delete();
            }
        }

        // 2. Sesuaikan jadwal yang ada sekarang untuk 14 hari ke depan
        $scheduleResolver = app(ScheduleResolver::class);
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();

        for ($offset = 0; $offset < 14; $offset++) {
            $date = $today->addDays($offset)->startOfDay();
            $dateString = $date->toDateString();
            $dayType = $scheduleResolver->dayTypeFor($date);

            if ($dayType === null || ! isset($standardTemplates[$dayType])) {
                continue;
            }

            $targetTimes = $standardTemplates[$dayType];
            $targetTimesWithSeconds = array_map(fn ($t) => strlen($t) === 5 ? $t.':00' : $t, $targetTimes);
            $allTargetFormats = array_unique([...$targetTimes, ...$targetTimesWithSeconds]);

            // Hapus sesi kosong di luar jam standar baru
            $sessionsToClean = Session::query()
                ->where('session_date', $dateString)
                ->whereNotIn('start_time', $allTargetFormats)
                ->get();

            foreach ($sessionsToClean as $session) {
                if ($session->occupiedBookings()->doesntExist() && $session->memberVisits()->doesntExist()) {
                    $session->delete();
                }
            }

            // Pastikan sesi yang cocok dengan jam standar memiliki durasi 120 menit
            $matchingSessions = Session::query()
                ->where('session_date', $dateString)
                ->whereIn('start_time', $allTargetFormats)
                ->where('status', 'tersedia')
                ->get();

            foreach ($matchingSessions as $session) {
                $start = Carbon::parse($session->start_time);
                $expectedEndTime = $start->copy()->addMinutes(120)->format('H:i:s');

                if ($session->occupiedBookings()->doesntExist() && substr((string) $session->end_time, 0, 8) !== $expectedEndTime) {
                    $session->update(['end_time' => $expectedEndTime]);
                }
            }
        }

        // Generate ulang slot 14 hari ke depan agar semua sesi standar baru terbit
        try {
            $generator = app(SessionGenerationService::class);
            $generator->generateDays(14, $today);
        } catch (Throwable) {
            // Abaikan jika service tidak dapat dipanggil saat migrasi terisolasi
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $oldTemplates = [
            'weekday' => ['13:00', '16:00', '19:00'],
            'jumat' => ['09:00', '13:00', '16:00', '19:00'],
            'weekend' => ['09:00', '13:00', '16:00', '19:00'],
            'libur' => ['09:00', '13:00', '16:00', '19:00'],
        ];

        if (Schema::hasTable('schedule_templates')) {
            foreach ($oldTemplates as $dayType => $times) {
                foreach ($times as $startTime) {
                    DB::table('schedule_templates')->updateOrInsert(
                        [
                            'day_type' => $dayType,
                            'start_time' => $startTime,
                        ],
                        [
                            'duration_minutes' => 120,
                            'is_active' => true,
                            'updated_at' => now(),
                        ]
                    );
                }

                DB::table('schedule_templates')
                    ->where('day_type', $dayType)
                    ->whereNotIn('start_time', $times)
                    ->delete();
            }
        }
    }
};
