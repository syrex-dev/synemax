<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('films', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('genre', 100);
            $table->string('age_rating', 5);
            $table->text('synopsis');
            $table->string('trailer_url')->nullable();
            $table->string('poster_path')->nullable();
            $table->text('license_notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('schedule_templates', function (Blueprint $table) {
            $table->id();
            $table->string('day_type', 20);
            $table->time('start_time');
            $table->unsignedSmallInteger('duration_minutes')->default(150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['day_type', 'start_time']);
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('holiday_date')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('screening_sessions', function (Blueprint $table) {
            $table->id();
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->foreignId('film_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('capacity')->default(6);
            $table->boolean('is_private')->default(false);
            $table->string('status', 30)->default('tersedia');
            $table->timestamps();
            $table->unique(['session_date', 'start_time']);
            $table->index(['session_date', 'status']);
        });

        Schema::create('blocked_dates', function (Blueprint $table) {
            $table->id();
            $table->date('blocked_date');
            $table->char('scope_key', 5)->default('ALL');
            $table->foreignId('session_id')->nullable()->constrained('screening_sessions')->cascadeOnDelete();
            $table->string('reason');
            $table->timestamps();
            $table->unique(['blocked_date', 'scope_key']);
        });

        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('whatsapp', 15)->unique();
            $table->unsignedSmallInteger('no_show_count')->default(0);
            $table->boolean('is_blocked')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('session_id')->constrained('screening_sessions')->restrictOnDelete();
            $table->foreignId('film_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('whatsapp', 15)->index();
            $table->unsignedTinyInteger('pax');
            $table->unsignedTinyInteger('seats_reserved')->default(1);
            $table->string('booking_type', 20)->default('per_orang');
            $table->unsignedBigInteger('total_price');
            $table->string('payment_method', 20)->default('di_tempat');
            $table->string('payment_proof_path')->nullable();
            $table->string('status', 30)->default('menunggu_konfirmasi')->index();
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('attended_at')->nullable();
            $table->timestamps();
            $table->index(['session_id', 'status']);
            $table->index(['created_at', 'status']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('customer_profiles');
        Schema::dropIfExists('blocked_dates');
        Schema::dropIfExists('screening_sessions');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('schedule_templates');
        Schema::dropIfExists('films');
    }
};
