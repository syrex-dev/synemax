<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            if (! Schema::hasColumn('members', 'code')) {
                $table->string('code', 20)->nullable()->after('id');
            }
            if (! Schema::hasColumn('members', 'nickname')) {
                $table->string('nickname', 50)->nullable()->after('name');
            }
            if (! Schema::hasColumn('members', 'notes')) {
                $table->string('notes', 300)->nullable()->after('photo_path');
            }
            if (! Schema::hasColumn('members', 'status')) {
                $table->string('status', 20)->default('aktif')->after('notes');
            }
            if (! Schema::hasColumn('members', 'opening_visits')) {
                $table->unsignedInteger('opening_visits')->default(0)->after('status');
            }
            if (! Schema::hasColumn('members', 'link_version')) {
                $table->unsignedInteger('link_version')->default(1)->after('opening_visits');
            }
            if (! Schema::hasColumn('members', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('link_version');
            }
        });

        Schema::table('member_visits', function (Blueprint $table): void {
            if (! Schema::hasColumn('member_visits', 'booking_id')) {
                $table->foreignId('booking_id')->nullable()->after('session_id');
            }
            if (! Schema::hasColumn('member_visits', 'position')) {
                $table->unsignedInteger('position')->default(1)->after('booking_id');
            }
            if (! Schema::hasColumn('member_visits', 'reward_type')) {
                $table->string('reward_type', 20)->default('none')->after('position');
            }
            if (! Schema::hasColumn('member_visits', 'reward_amount')) {
                $table->unsignedInteger('reward_amount')->default(0)->after('reward_type');
            }
            if (! Schema::hasColumn('member_visits', 'active')) {
                $table->tinyInteger('active')->nullable()->default(1)->after('reward_amount');
            }
            if (! Schema::hasColumn('member_visits', 'method')) {
                $table->string('method', 20)->default('qr')->after('active');
            }
            if (! Schema::hasColumn('member_visits', 'void_reason')) {
                $table->string('void_reason')->nullable()->after('recorded_by');
            }
            if (! Schema::hasColumn('member_visits', 'voided_by')) {
                $table->foreignId('voided_by')->nullable()->after('void_reason');
            }
            if (! Schema::hasColumn('member_visits', 'voided_at')) {
                $table->timestamp('voided_at')->nullable()->after('voided_by');
            }
        });

        Schema::table('member_rewards', function (Blueprint $table): void {
            if (! Schema::hasColumn('member_rewards', 'member_visit_id')) {
                $table->foreignId('member_visit_id')->nullable()->after('id');
            }
            if (! Schema::hasColumn('member_rewards', 'type')) {
                $table->string('type', 20)->default('discount')->after('member_id');
            }
            if (! Schema::hasColumn('member_rewards', 'amount')) {
                $table->unsignedInteger('amount')->default(0)->after('type');
            }
            if (! Schema::hasColumn('member_rewards', 'settlement')) {
                $table->string('settlement', 30)->default('counter_discount')->after('amount');
            }
            if (! Schema::hasColumn('member_rewards', 'booking_id')) {
                $table->foreignId('booking_id')->nullable()->after('settlement');
            }
            if (! Schema::hasColumn('member_rewards', 'given_by')) {
                $table->foreignId('given_by')->nullable()->after('status');
            }
            if (! Schema::hasColumn('member_rewards', 'given_at')) {
                $table->timestamp('given_at')->nullable()->after('given_by');
            }
            if (! Schema::hasColumn('member_rewards', 'note')) {
                $table->string('note')->nullable()->after('given_at');
            }
        });

        Schema::table('member_events', function (Blueprint $table): void {
            if (! Schema::hasColumn('member_events', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('member_id');
            }
            if (! Schema::hasColumn('member_events', 'event')) {
                $table->string('event', 50)->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('member_events', 'meta')) {
                $table->json('meta')->nullable()->after('event');
            }
            if (! Schema::hasColumn('member_events', 'ip')) {
                $table->string('ip', 45)->nullable()->after('meta');
            }
        });
    }

    public function down(): void
    {
        // Additive only
    }
};
