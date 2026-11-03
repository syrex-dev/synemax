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
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'checked_in_at')) {
                $table->timestamp('checked_in_at')->nullable()->after('attended_at');
            }
            if (! Schema::hasColumn('bookings', 'checked_in_by')) {
                $table->foreignId('checked_in_by')->nullable()->after('checked_in_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('bookings', 'check_in_method')) {
                $table->string('check_in_method', 30)->nullable()->after('checked_in_by');
            }
        });

        if (! Schema::hasTable('booking_checkins')) {
            Schema::create('booking_checkins', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('method', 30)->comment('qr, kode, daftar, admin_manual, legacy');
                $table->string('result', 30)->comment('ok, duplicate, rejected, override, undo');
                $table->text('reason')->nullable();
                $table->string('ip', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamp('created_at')->nullable()->index();

                $table->index('booking_id');
            });
        }

        // Backfill legacy check-in method for existing attended bookings without modifying their status
        DB::table('bookings')
            ->where('status', 'hadir')
            ->whereNull('checked_in_at')
            ->update([
                'checked_in_at' => DB::raw('COALESCE(attended_at, updated_at, created_at)'),
                'check_in_method' => 'legacy',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_checkins');

        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'checked_in_by')) {
                $table->dropForeign(['checked_in_by']);
                $table->dropColumn('checked_in_by');
            }
            if (Schema::hasColumn('bookings', 'checked_in_at')) {
                $table->dropColumn('checked_in_at');
            }
            if (Schema::hasColumn('bookings', 'check_in_method')) {
                $table->dropColumn('check_in_method');
            }
        });
    }
};
