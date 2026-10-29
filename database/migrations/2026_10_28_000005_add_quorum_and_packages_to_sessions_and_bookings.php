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
        // 1. Update screening_sessions table
        if (Schema::hasTable('screening_sessions')) {
            Schema::table('screening_sessions', function (Blueprint $table) {
                if (! Schema::hasColumn('screening_sessions', 'confirmation_method')) {
                    $table->string('confirmation_method', 30)->nullable()->after('is_private');
                }
                if (! Schema::hasColumn('screening_sessions', 'confirmed_at')) {
                    $table->timestamp('confirmed_at')->nullable()->after('confirmation_method');
                }
                if (! Schema::hasColumn('screening_sessions', 'resolution_notes')) {
                    $table->text('resolution_notes')->nullable()->after('confirmed_at');
                }
            });
        }

        // 2. Update bookings table
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (! Schema::hasColumn('bookings', 'package')) {
                    $table->string('package', 20)->default('reguler')->after('seats_reserved');
                }
                if (! Schema::hasColumn('bookings', 'people_count')) {
                    $table->unsignedTinyInteger('people_count')->default(1)->after('package');
                }
                if (! Schema::hasColumn('bookings', 'reselect_needed')) {
                    $table->boolean('reselect_needed')->default(false)->after('people_count');
                }
                if (! Schema::hasColumn('bookings', 'refund_needed')) {
                    $table->boolean('refund_needed')->default(false)->after('reselect_needed');
                }
            });

            // Backfill legacy bookings:
            // if booking_type is 'private', package = 'private', else 'reguler'
            DB::table('bookings')
                ->where('booking_type', 'private')
                ->update([
                    'package' => 'private',
                    'people_count' => DB::raw('COALESCE(pax, 6)'),
                ]);

            DB::table('bookings')
                ->where('booking_type', '!=', 'private')
                ->orWhereNull('booking_type')
                ->update([
                    'package' => 'reguler',
                    'people_count' => DB::raw('COALESCE(pax, 1)'),
                ]);
        }

        // Backfill legacy sessions that already have film_id locked:
        // mark confirmed_at = created_at, confirmation_method = 'admin' or 'quorum'
        if (Schema::hasTable('screening_sessions')) {
            DB::table('screening_sessions')
                ->whereNotNull('film_id')
                ->whereNull('confirmed_at')
                ->update([
                    'confirmation_method' => 'admin',
                    'confirmed_at' => DB::raw('created_at'),
                ]);
        }

        // 3. Ensure minimum_public_viewers is set to 4 (default quorum in Bagian 2)
        if (Schema::hasTable('settings')) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'minimum_public_viewers'],
                ['value' => '4', 'group' => 'operational', 'updated_at' => now()]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('screening_sessions')) {
            Schema::table('screening_sessions', function (Blueprint $table) {
                $columns = ['confirmation_method', 'confirmed_at', 'resolution_notes'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('screening_sessions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                $columns = ['package', 'people_count', 'reselect_needed', 'refund_needed'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('bookings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
