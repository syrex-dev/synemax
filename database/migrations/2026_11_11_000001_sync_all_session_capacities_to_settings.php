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

        $capacity = (int) ($settingVal ?: 10);
        if ($capacity > 0) {
            DB::table('screening_sessions')
                ->where('session_date', '>=', now(config('app.timezone'))->toDateString())
                ->update(['capacity' => $capacity]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
