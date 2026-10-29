<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add display_density preference to users table
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'display_density')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('display_density', 20)->default('nyaman')->after('is_admin');
            });
        }

        // 2. Ensure all legacy bookings have film_id populated from their session (DB-agnostic)
        if (Schema::hasTable('bookings') && Schema::hasTable('screening_sessions')) {
            $sessions = DB::table('screening_sessions')
                ->whereNotNull('film_id')
                ->pluck('film_id', 'id');

            foreach ($sessions as $sessionId => $filmId) {
                DB::table('bookings')
                    ->where('session_id', $sessionId)
                    ->whereNull('film_id')
                    ->update(['film_id' => $filmId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'display_density')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('display_density');
            });
        }
    }
};
