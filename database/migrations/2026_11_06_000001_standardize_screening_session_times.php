<?php

use App\Services\SessionGenerationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ubah seluruh jam mulai sesi 19:30 menjadi 19:00 di tabel screening_sessions
        DB::table('screening_sessions')
            ->whereIn('start_time', ['19:30', '19:30:00'])
            ->update([
                'start_time' => '19:00',
                'end_time' => '21:30:00',
                'updated_at' => now(),
            ]);

        // 2. Ubah scope_key pada tanggal/sesi yang diblokir jika merujuk ke 19:30
        DB::table('blocked_dates')
            ->where('scope_key', '19:30')
            ->update([
                'scope_key' => '19:00',
                'updated_at' => now(),
            ]);

        // 3. Bersihkan template jadwal lama yang berada pada pukul 19:30
        DB::table('schedule_templates')
            ->whereIn('start_time', ['19:30', '19:30:00'])
            ->delete();

        // 4. Standarisasi template jadwal pada lingkungan live/non-testing:
        // - Senin–Kamis (weekday): 3 sesi => 13:00, 16:00, 19:00
        // - Jumat (jumat): 4 sesi => 09:00, 13:00, 16:00, 19:00
        // - Sabtu–Minggu (weekend): 4 sesi => 09:00, 13:00, 16:00, 19:00
        // - Hari libur (libur): 4 sesi => 09:00, 13:00, 16:00, 19:00
        if (! app()->runningUnitTests()) {
            $standardTemplates = [
                'weekday' => ['13:00', '16:00', '19:00'],
                'jumat' => ['09:00', '13:00', '16:00', '19:00'],
                'weekend' => ['09:00', '13:00', '16:00', '19:00'],
                'libur' => ['09:00', '13:00', '16:00', '19:00'],
            ];

            foreach ($standardTemplates as $dayType => $times) {
                foreach ($times as $startTime) {
                    DB::table('schedule_templates')->updateOrInsert(
                        [
                            'day_type' => $dayType,
                            'start_time' => $startTime,
                        ],
                        [
                            'duration_minutes' => 150,
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

            // 5. Generate ulang sesi 14 hari ke depan agar slot baru otomatis terbit
            try {
                $generator = app(SessionGenerationService::class);
                $generator->generateDays(14);
            } catch (Throwable) {
                // Abaikan jika service tidak dapat dipanggil saat migrasi terisolasi
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('screening_sessions')
            ->whereIn('start_time', ['19:00', '19:00:00'])
            ->update([
                'start_time' => '19:30',
                'end_time' => '22:00:00',
                'updated_at' => now(),
            ]);

        DB::table('blocked_dates')
            ->where('scope_key', '19:00')
            ->update([
                'scope_key' => '19:30',
                'updated_at' => now(),
            ]);
    }
};
