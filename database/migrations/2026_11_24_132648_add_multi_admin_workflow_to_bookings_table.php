<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('claimed_by_name')->nullable()->after('proof_rejection_note');
            $table->string('claimed_by_id')->nullable()->after('claimed_by_name');
            $table->timestamp('claimed_at')->nullable()->after('claimed_by_id');
            $table->string('confirmed_by_name')->nullable()->after('claimed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['claimed_by_name', 'claimed_by_id', 'claimed_at', 'confirmed_by_name']);
        });
    }
};
