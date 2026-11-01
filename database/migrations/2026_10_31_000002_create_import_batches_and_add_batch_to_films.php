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
        if (! Schema::hasTable('import_batches')) {
            Schema::create('import_batches', function (Blueprint $table) {
                $table->string('id', 36)->primary();
                $table->string('filename')->nullable();
                $table->unsignedInteger('total_rows')->default(0);
                $table->unsignedInteger('imported_count')->default(0);
                $table->unsignedInteger('skipped_count')->default(0);
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('rolled_back_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('films', 'import_batch_id')) {
            Schema::table('films', function (Blueprint $table) {
                $table->string('import_batch_id', 36)->nullable()->index()->after('is_sample');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('films', 'import_batch_id')) {
            Schema::table('films', function (Blueprint $table) {
                $table->dropColumn('import_batch_id');
            });
        }

        Schema::dropIfExists('import_batches');
    }
};
