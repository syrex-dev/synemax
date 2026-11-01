<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('age_ratings')) {
            Schema::create('age_ratings', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 8)->unique();
                $table->string('label', 100);
                $table->unsignedTinyInteger('min_age')->default(0);
                $table->text('description')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Seed standard ratings SU, 13+, 17+, 21+ and any other existing distinct film ratings
            $seedData = [
                ['code' => 'SU', 'label' => 'Semua Umur', 'min_age' => 0, 'description' => 'Dapat ditonton oleh seluruh kelompok usia.', 'sort_order' => 1, 'is_active' => true],
                ['code' => '13+', 'label' => 'Remaja 13 Tahun ke Atas', 'min_age' => 13, 'description' => 'Khusus untuk penonton berusia 13 tahun ke atas.', 'sort_order' => 2, 'is_active' => true],
                ['code' => '17+', 'label' => 'Dewasa 17 Tahun ke Atas', 'min_age' => 17, 'description' => 'Khusus untuk penonton dewasa berusia 17 tahun ke atas.', 'sort_order' => 3, 'is_active' => true],
                ['code' => '21+', 'label' => 'Dewasa 21 Tahun ke Atas', 'min_age' => 21, 'description' => 'Khusus penonton dewasa berusia 21 tahun ke atas.', 'sort_order' => 4, 'is_active' => true],
            ];

            foreach ($seedData as $row) {
                DB::table('age_ratings')->updateOrInsert(['code' => $row['code']], array_merge($row, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            // Also ensure any ratings already used in films are present
            if (Schema::hasTable('films')) {
                $distinctRatings = DB::table('films')
                    ->whereNotNull('age_rating')
                    ->where('age_rating', '!=', '')
                    ->distinct()
                    ->pluck('age_rating');

                $order = 5;
                foreach ($distinctRatings as $rating) {
                    $rating = trim((string) $rating);
                    if ($rating === '') {
                        continue;
                    }
                    DB::table('age_ratings')->insertOrIgnore([
                        'code' => $rating,
                        'label' => 'Klasifikasi '.$rating,
                        'min_age' => (int) preg_replace('/\D/', '', $rating) ?: 0,
                        'description' => null,
                        'sort_order' => $order++,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('age_ratings');
    }
};
