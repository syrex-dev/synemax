<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('genres') && ! Schema::hasColumn('genres', 'parent_id')) {
            Schema::table('genres', function (Blueprint $table): void {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('genres')
                    ->nullOnDelete();
                $table->index('parent_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('genres') && Schema::hasColumn('genres', 'parent_id')) {
            Schema::table('genres', function (Blueprint $table): void {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            });
        }
    }
};
