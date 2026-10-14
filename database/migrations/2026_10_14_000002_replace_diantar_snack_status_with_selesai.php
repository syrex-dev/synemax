<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('booking_items')
            ->where('status', 'diantar')
            ->update(['status' => 'selesai']);
    }

    public function down(): void
    {
        DB::table('booking_items')
            ->where('status', 'selesai')
            ->update(['status' => 'diantar']);
    }
};
