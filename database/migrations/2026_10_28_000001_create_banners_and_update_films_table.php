<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30)->default('hero'); // 'hero', 'promo', 'snack'
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('badge', 60)->nullable();
            $table->string('image_path')->nullable();
            $table->string('cta_text', 50)->nullable();
            $table->string('cta_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active', 'sort_order']);
        });

        Schema::table('films', function (Blueprint $table) {
            $table->string('banner_path')->nullable()->after('poster_path');
            $table->date('release_date')->nullable()->after('is_active');
            $table->boolean('is_coming_soon')->default(false)->after('release_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');

        Schema::table('films', function (Blueprint $table) {
            $table->dropColumn(['banner_path', 'release_date', 'is_coming_soon']);
        });
    }
};
