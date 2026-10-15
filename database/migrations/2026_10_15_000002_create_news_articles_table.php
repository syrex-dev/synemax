<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_articles', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->string('kategori'); // Berita, Promo, Pengumuman
            $table->text('ringkasan');
            $table->longText('konten');
            $table->string('cover_path')->nullable();
            $table->string('status_publikasi')->default('draft'); // draft, terbit
            $table->dateTime('tanggal_terbit')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status_publikasi', 'tanggal_terbit']);
            $table->index(['kategori']);
            $table->index(['is_pinned']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_articles');
    }
};
