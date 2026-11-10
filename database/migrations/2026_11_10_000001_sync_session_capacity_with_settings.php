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
        $settingVal = DB::table('settings')->where('key', 'capacity_per_session')->value('value')
            ?: DB::table('settings')->where('key', 'seats_capacity')->value('value');

        if ($settingVal && (int) $settingVal > 6) {
            $capacity = (int) $settingVal;
            DB::table('screening_sessions')
                ->where('capacity', 6)
                ->update(['capacity' => $capacity]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
