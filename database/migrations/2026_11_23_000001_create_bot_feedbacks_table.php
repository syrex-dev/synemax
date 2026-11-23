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
        Schema::create('bot_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->string('telegram_user_id')->nullable()->index();
            $table->string('telegram_username')->nullable()->index();
            $table->string('sender_name')->nullable();
            $table->string('category', 40)->default('GENERAL')->index();
            $table->string('booking_code', 60)->nullable()->index();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->text('message');
            $table->text('bot_response')->nullable();
            $table->string('status', 30)->default('AUTO_RESOLVED')->index();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_feedbacks');
    }
};
