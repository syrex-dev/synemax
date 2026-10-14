<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->unsignedBigInteger('ticket_total')->default(0)->after('total_price');
            $table->unsignedBigInteger('snack_total')->default(0)->after('ticket_total');
        });

        DB::table('bookings')->update([
            'ticket_total' => DB::raw('total_price'),
            'snack_total' => 0,
        ]);

        Schema::create('menu_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('menu_categories')->restrictOnDelete();
            $table->string('name', 120);
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('cost_price')->nullable();
            $table->string('photo_path')->nullable();
            $table->unsignedSmallInteger('prep_minutes')->default(0);
            $table->string('stock_mode', 10)->default('toggle');
            $table->unsignedInteger('stock_qty')->nullable();
            $table->unsignedInteger('stock_alert_threshold')->default(3);
            $table->boolean('is_available')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['category_id', 'is_active', 'is_available']);
        });

        Schema::create('booking_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_item_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $table->string('name_snapshot', 120);
            $table->unsignedBigInteger('price_snapshot');
            $table->unsignedBigInteger('cost_snapshot')->nullable();
            $table->unsignedSmallInteger('qty');
            $table->string('note', 250)->nullable();
            $table->string('status', 20)->default('baru')->index();
            $table->unsignedInteger('stock_deducted_qty')->default(0);
            $table->unsignedInteger('stock_restored_qty')->default(0);
            $table->timestamp('stock_deducted_at')->nullable();
            $table->timestamp('stock_restored_at')->nullable();
            $table->timestamps();
            $table->index(['booking_id', 'status']);
        });

        DB::table('settings')->insertOrIgnore([
            [
                'key' => 'snacks_enabled',
                'value' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'snack_prep_buffer_minutes',
                'value' => '15',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'snack_max_qty_per_item',
                'value' => '10',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menu_categories');

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn(['ticket_total', 'snack_total']);
        });
    }
};
