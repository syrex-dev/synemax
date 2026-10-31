<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = ['films', 'promos', 'banners', 'nobar_events', 'news_articles'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'is_sample')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->boolean('is_sample')->default(false)->index();
                });
            }
        }

        // Mark existing records with [Contoh] prefix as sample data
        if (Schema::hasTable('films')) {
            DB::table('films')->where('title', 'like', '[Contoh]%')->update(['is_sample' => true]);
        }
        if (Schema::hasTable('promos')) {
            DB::table('promos')->where('title', 'like', '[Contoh]%')->update(['is_sample' => true]);
        }
        if (Schema::hasTable('banners')) {
            DB::table('banners')->where('title', 'like', '[Contoh]%')->update(['is_sample' => true]);
        }
        if (Schema::hasTable('nobar_events')) {
            DB::table('nobar_events')->where('judul', 'like', '[Contoh]%')->update(['is_sample' => true]);
        }
        if (Schema::hasTable('news_articles')) {
            DB::table('news_articles')->where('judul', 'like', '[Contoh]%')->update(['is_sample' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['films', 'promos', 'banners', 'nobar_events', 'news_articles'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'is_sample')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('is_sample');
                });
            }
        }
    }
};
