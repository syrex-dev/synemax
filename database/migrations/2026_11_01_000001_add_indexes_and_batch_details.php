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
        Schema::table('films', function (Blueprint $table) {
            if (Schema::hasColumn('films', 'release_year')) {
                // Add index to release_year if not already indexed
                $table->index('release_year');
            }
            if (Schema::hasColumn('films', 'genre')) {
                $table->index('genre');
            }
        });

        Schema::table('import_batches', function (Blueprint $table) {
            if (! Schema::hasColumn('import_batches', 'status')) {
                $table->string('status', 20)->default('completed')->after('skipped_count');
            }
            if (! Schema::hasColumn('import_batches', 'failed_count')) {
                $table->unsignedInteger('failed_count')->default(0)->after('skipped_count');
            }
            if (! Schema::hasColumn('import_batches', 'failed_rows')) {
                $table->text('failed_rows')->nullable()->after('failed_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('films', function (Blueprint $table) {
            $table->dropIndex(['release_year']);
            $table->dropIndex(['genre']);
        });

        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropColumn(['status', 'failed_count', 'failed_rows']);
        });
    }
};
