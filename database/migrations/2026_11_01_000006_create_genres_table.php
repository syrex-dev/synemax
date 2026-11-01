<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('genres')) {
            Schema::create('genres', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 50);
                $table->string('normalized_name', 50)->unique();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Initial canonical genres from all codebases + historical wizard lists
            $initialGenres = [
                'Aksi',
                'Animasi',
                'Biografi',
                'Dokumenter',
                'Drama',
                'Fantasi',
                'Horor',
                'Kejahatan',
                'Keluarga',
                'Komedi',
                'Misteri',
                'Musikal',
                'Perang',
                'Petualangan',
                'Romantis',
                'Sci-Fi',
                'Sejarah',
                'Thriller',
            ];

            $order = 1;
            foreach ($initialGenres as $genreName) {
                $normalized = mb_strtolower(trim(preg_replace('/\s+/', ' ', $genreName)), 'UTF-8');
                $normalized = preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $normalized);

                DB::table('genres')->updateOrInsert(
                    ['normalized_name' => $normalized],
                    [
                        'name' => $genreName,
                        'sort_order' => $order++,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            // Also seed any distinct genres currently used in films table
            if (Schema::hasTable('films')) {
                $filmGenres = DB::table('films')
                    ->whereNotNull('genre')
                    ->where('genre', '!=', '')
                    ->pluck('genre');

                foreach ($filmGenres as $rawGenreString) {
                    $tokens = explode(',', (string) $rawGenreString);
                    foreach ($tokens as $token) {
                        $token = trim($token);
                        if ($token === '') {
                            continue;
                        }

                        $normalized = mb_strtolower(trim(preg_replace('/\s+/', ' ', $token)), 'UTF-8');
                        $normalized = preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $normalized);
                        if ($normalized === '') {
                            continue;
                        }

                        // Check if exists
                        $exists = DB::table('genres')->where('normalized_name', $normalized)->exists();
                        if (! $exists) {
                            // Format title case (preserving Sci-Fi)
                            $displayName = strcasecmp($token, 'sci-fi') === 0 || strcasecmp($token, 'scifi') === 0
                                ? 'Sci-Fi'
                                : mb_convert_case($token, MB_CASE_TITLE, 'UTF-8');

                            DB::table('genres')->insert([
                                'name' => $displayName,
                                'normalized_name' => $normalized,
                                'sort_order' => $order++,
                                'is_active' => true,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('genres');
    }
};
