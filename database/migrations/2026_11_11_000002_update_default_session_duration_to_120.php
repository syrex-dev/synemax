<?php

use App\Models\Session;
use Carbon\Carbon;
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
        // 1. Ubah default column duration_minutes di schedule_templates jika tabel ada
        if (Schema::hasTable('schedule_templates') && Schema::hasColumn('schedule_templates', 'duration_minutes')) {
            Schema::table('schedule_templates', function (Blueprint $table): void {
                $table->unsignedSmallInteger('duration_minutes')->default(120)->change();
            });

            // Update semua template eksisting yang masih 150 menit menjadi 120 menit
            DB::table('schedule_templates')
                ->where('duration_minutes', 150)
                ->update(['duration_minutes' => 120]);
        }

        // 2. Perbarui sesi mendatang (hari ini ke depan) yang masih berdurasi 150 menit & belum ada booking aktif
        $today = Carbon::today(config('app.timezone'))->toDateString();

        $candidateSessions = Session::query()
            ->where('session_date', '>=', $today)
            ->where('status', 'tersedia')
            ->whereDoesntHave('occupiedBookings')
            ->get();

        foreach ($candidateSessions as $session) {
            $start = Carbon::parse($session->start_time);
            $end = Carbon::parse($session->end_time);

            if ($start->diffInMinutes($end) === 150) {
                $session->update([
                    'end_time' => $start->addMinutes(120)->format('H:i:s'),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('schedule_templates') && Schema::hasColumn('schedule_templates', 'duration_minutes')) {
            Schema::table('schedule_templates', function (Blueprint $table): void {
                $table->unsignedSmallInteger('duration_minutes')->default(150)->change();
            });
        }
    }
};
