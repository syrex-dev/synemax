<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nobar_events', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->string('kompetisi')->nullable();
            $table->string('tim_a');
            $table->string('tim_b')->nullable();
            $table->dateTime('kickoff_at'); // datetime WIB
            $table->unsignedSmallInteger('durasi_menit')->default(150);
            $table->string('lokasi_nama');
            $table->text('alamat_patokan');
            $table->string('maps_url');
            $table->boolean('is_free')->default(true);
            $table->string('harga_info')->nullable();
            $table->text('catatan')->nullable();
            $table->string('poster_path')->nullable();
            $table->string('status_publikasi')->default('draft'); // draft, terbit
            $table->boolean('is_pinned')->default(false);
            $table->string('status_acara')->default('aktif'); // aktif, ditunda, dibatalkan
            $table->string('alasan_status')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status_publikasi', 'kickoff_at']);
            $table->index(['status_acara']);
            $table->index(['is_pinned']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nobar_events');
    }
};
