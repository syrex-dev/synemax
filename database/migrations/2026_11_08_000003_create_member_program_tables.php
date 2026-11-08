<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('members')) {
            Schema::create('members', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 20)->unique();
                $table->string('member_code', 20)->nullable()->unique();
                $table->string('name', 80);
                $table->string('nickname', 50)->nullable();
                $table->string('photo_path')->nullable();
                $table->string('notes', 300)->nullable();
                $table->string('status', 20)->default('aktif');
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('opening_visits')->default(0);
                $table->unsignedInteger('link_version')->default(1);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index('status');
                $table->index('is_active');
                $table->index('name');
                $table->index('deleted_at');
            });
        }

        if (! Schema::hasTable('member_visits')) {
            Schema::create('member_visits', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
                $table->foreignId('session_id')->constrained('screening_sessions')->cascadeOnDelete();
                $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
                $table->unsignedInteger('position')->default(1); // 1..siklus
                $table->unsignedInteger('visit_number')->default(1);
                $table->unsignedInteger('cycle_number')->default(1);
                $table->string('reward_type', 20)->default('none'); // none, discount, free
                $table->unsignedInteger('reward_amount')->default(0);
                $table->string('reward_applied', 50)->nullable();
                $table->tinyInteger('active')->nullable()->default(1); // 1 bila sah, NULL bila dibatalkan
                $table->string('method', 20)->default('qr'); // qr, kode, cari
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('void_reason')->nullable();
                $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('voided_at')->nullable();
                $table->timestamps();

                $table->unique(['member_id', 'session_id', 'active']);
                $table->index(['member_id', 'active', 'created_at']);
                $table->index(['member_id', 'cycle_number', 'visit_number']);
            });
        }

        if (! Schema::hasTable('member_rewards')) {
            Schema::create('member_rewards', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('member_visit_id')->nullable()->unique()->constrained('member_visits')->cascadeOnDelete();
                $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
                $table->string('type', 20)->default('discount'); // discount, free
                $table->string('reward_type', 50)->nullable();
                $table->unsignedInteger('amount')->default(0);
                $table->string('settlement', 30)->default('counter_discount'); // deducted_bill, refund_cash, counter_discount
                $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
                $table->unsignedInteger('cycle_number')->default(1);
                $table->string('status', 30)->default('pending'); // pending, given, skipped
                $table->foreignId('given_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('given_at')->nullable();
                $table->string('note')->nullable();
                $table->timestamp('granted_at')->nullable();
                $table->timestamp('redeemed_at')->nullable();
                $table->foreignId('redeemed_session_id')->nullable()->constrained('screening_sessions')->nullOnDelete();
                $table->timestamps();

                $table->index(['status', 'created_at']);
                $table->index(['member_id', 'status']);
            });
        }

        if (! Schema::hasTable('member_events')) {
            Schema::create('member_events', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('event', 50); // created, updated, status_changed, link_regenerated, visit_added, visit_voided, reward_given, reward_skipped, opening_changed
                $table->string('event_type', 50)->nullable();
                $table->json('meta')->nullable();
                $table->json('payload')->nullable();
                $table->string('ip', 45)->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index(['member_id', 'event']);
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('member_events');
        Schema::dropIfExists('member_rewards');
        Schema::dropIfExists('member_visits');
        Schema::dropIfExists('members');
    }
};
