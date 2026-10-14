<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('snack_order_edits', function (Blueprint $table): void {
            $table->text('summary')->nullable()->after('actor_id');
        });
    }

    public function down(): void
    {
        Schema::table('snack_order_edits', function (Blueprint $table): void {
            $table->dropColumn('summary');
        });
    }
};
