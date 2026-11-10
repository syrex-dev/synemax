<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settingVal = DB::table('settings')->where('key', 'capacity_per_session')->value('value');
        $capacity = (int) ($settingVal ?: 10);

        if ($capacity > 0) {
            // Sinkronkan kapasitas sesi yang masih bernilai 6 ke kapasitas pengaturan
            DB::table('screening_sessions')
                ->where('capacity', 6)
                ->update(['capacity' => $capacity]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu revert paksa karena kapasitas adalah preferensi operasional
    }
};
