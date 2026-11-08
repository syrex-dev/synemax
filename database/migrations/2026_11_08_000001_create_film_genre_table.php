<?php

use App\Models\Genre;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('film_genre')) {
            Schema::create('film_genre', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('film_id')->constrained('films')->cascadeOnDelete();
                $table->foreignId('genre_id')->constrained('genres')->cascadeOnDelete();
                $table->unsignedSmallInteger('position')->default(0);
                $table->timestamps();

                $table->unique(['film_id', 'genre_id']);
                $table->index(['genre_id', 'position']);
                $table->index(['film_id', 'position']);
            });
        }

        // Idempotent backfill from films.genre text column
        if (Schema::hasTable('films') && Schema::hasTable('genres')) {
            $films = DB::table('films')
                ->whereNotNull('genre')
                ->where('genre', '!=', '')
                ->select(['id', 'genre'])
                ->get();

            foreach ($films as $film) {
                $tokens = array_filter(array_map('trim', preg_split('/[,;]+/', (string) $film->genre)));
                $position = 0;
                $seenGenreIds = [];

                foreach ($tokens as $token) {
                    if ($token === '') {
                        continue;
                    }

                    $genreModel = Genre::findOrCreateByName($token);
                    if ($genreModel && ! isset($seenGenreIds[$genreModel->id])) {
                        $seenGenreIds[$genreModel->id] = true;

                        DB::table('film_genre')->updateOrInsert(
                            [
                                'film_id' => $film->id,
                                'genre_id' => $genreModel->id,
                            ],
                            [
                                'position' => $position++,
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );
                    }
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('film_genre');
    }
};
