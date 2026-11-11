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
        Schema::table('members', function (Blueprint $table): void {
            if (! Schema::hasColumn('members', 'has_physical_card')) {
                $table->boolean('has_physical_card')->default(false)->after('status')->index();
            }

            if (! Schema::hasColumn('members', 'physical_card_printed_at')) {
                $table->timestamp('physical_card_printed_at')->nullable()->after('has_physical_card');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            if (Schema::hasColumn('members', 'physical_card_printed_at')) {
                $table->dropColumn('physical_card_printed_at');
            }

            if (Schema::hasColumn('members', 'has_physical_card')) {
                $table->dropIndex(['has_physical_card']);
                $table->dropColumn('has_physical_card');
            }
        });
    }
};
