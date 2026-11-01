<?php

namespace Tests\Feature;

use App\Models\Film;
use App\Models\ImportBatch;
use App\Models\User;
use App\Services\FilmBulkImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class FilmBulkImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);
        Storage::fake('public');
    }

    public function test_non_admin_cannot_access_import(): void
    {
        $guestRes = $this->get(route('admin.films.import.create'));
        $guestRes->assertRedirect(route('login'));

        $regularUser = User::factory()->create(['is_admin' => false]);
        $userRes = $this->actingAs($regularUser)->get(route('admin.films.import.create'));
        $userRes->assertForbidden();
    }

    public function test_csv_parser_handles_comma_semicolon_tab_bom_and_multiline_quotes(): void
    {
        $importer = app(FilmBulkImporter::class);

        // 1. Semicolon delimiter with BOM
        $bomSemicolon = "\xEF\xBB\xBFtitle;duration_minutes;genre;age_rating;synopsis\n\"Film Titik Koma\";120;Drama;13+;\"Sinopsis dengan baris baru\ndan tanda kutip ganda: \"\"Keren\"\".\"";
        $parsed1 = $importer->parseCsv($bomSemicolon);

        $this->assertCount(1, $parsed1);
        $this->assertSame('Film Titik Koma', $parsed1[0]['title']);
        $this->assertSame('120', $parsed1[0]['duration_minutes']);
        $this->assertStringContainsString("Sinopsis dengan baris baru\ndan tanda kutip ganda: \"Keren\".", $parsed1[0]['synopsis']);

        // 2. Tab delimiter
        $tabCsv = "title\tduration_minutes\tgenre\tage_rating\tsynopsis\nFilm Tab\t95\tAksi\tSU\tSinopsis tab";
        $parsed2 = $importer->parseCsv($tabCsv);

        $this->assertCount(1, $parsed2);
        $this->assertSame('Film Tab', $parsed2[0]['title']);

        // 3. Comma delimiter
        $commaCsv = "title,duration_minutes,genre,age_rating,synopsis\nFilm Koma,100,Horor,17+,Sinopsis koma";
        $parsed3 = $importer->parseCsv($commaCsv);

        $this->assertCount(1, $parsed3);
        $this->assertSame('Film Koma', $parsed3[0]['title']);
    }

    public function test_genre_and_age_classification_fuzzy_mapping(): void
    {
        $importer = app(FilmBulkImporter::class);

        // Misspelled genres & variations
        $mappedGenre = $importer->normalizeGenre('action, komedi, horror, roman, petualangan');
        $this->assertSame('Aksi, Komedi, Horor, Romantis, Petualangan', $mappedGenre);

        // Age rating mapping (21+ becomes 17+, R13 becomes 13+, G becomes SU)
        $this->assertSame('17+', $importer->normalizeAgeRating('21+'));
        $this->assertSame('17+', $importer->normalizeAgeRating('D21'));
        $this->assertSame('13+', $importer->normalizeAgeRating('R13+'));
        $this->assertSame('SU', $importer->normalizeAgeRating('Semua Umur'));
        $this->assertSame('SU', $importer->normalizeAgeRating('BO'));
    }

    public function test_duplicate_detection_against_db_and_csv(): void
    {
        $importer = app(FilmBulkImporter::class);

        Film::create([
            'title' => 'Film Lama Ada Di Database',
            'duration_minutes' => 100,
            'genre' => 'Aksi',
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis lama',
            'is_active' => true,
        ]);

        $csv = "title,duration_minutes,genre,age_rating,synopsis\n"
            ."Film Lama Ada Di Database,110,Aksi,13+,Sinopsis baru\n"
            ."Film Unik Pertama,90,Drama,SU,Sinopsis unik\n"
            .'Film Unik Pertama,95,Drama,SU,Duplikat internal CSV';

        $rows = $importer->parseCsv($csv);
        $analyzed = $importer->analyzeRows($rows);

        $this->assertSame('duplikat', $analyzed[0]['status']);
        $this->assertSame('baru', $analyzed[1]['status']);
        $this->assertSame('duplikat', $analyzed[2]['status']);
    }

    public function test_full_import_lifecycle_and_idempotency(): void
    {
        $importer = app(FilmBulkImporter::class);

        $csvContent = "title,duration_minutes,genre,age_rating,synopsis,release_year\n"
            ."Film Impor Satu,105,Aksi,13+,Sinopsis satu,2024\n"
            .'Film Impor Dua,115,Drama,SU,Sinopsis dua,2023';

        $csvFile = UploadedFile::fake()->createWithContent('films.csv', $csvContent);

        // 1. Submit preview
        $previewResponse = $this->actingAs($this->admin)->post(route('admin.films.import.preview'), [
            'csv_file' => $csvFile,
            'duplicate_strategy' => 'skip',
        ]);

        $previewResponse->assertOk();
        $previewResponse->assertViewIs('admin.films.import-preview');
        $batchToken = $previewResponse->viewData('batchToken');
        $this->assertNotEmpty($batchToken);

        // 2. Confirm import
        $confirmResponse = $this->actingAs($this->admin)->post(route('admin.films.import.confirm'), [
            'batch_token' => $batchToken,
            'duplicate_strategy' => 'skip',
            'rows' => [
                0 => [
                    'title' => 'Film Impor Satu',
                    'genre' => 'Aksi',
                    'duration_minutes' => 105,
                    'age_rating' => '13+',
                    'release_year' => 2024,
                    'synopsis' => 'Sinopsis satu',
                ],
                1 => [
                    'title' => 'Film Impor Dua',
                    'genre' => 'Drama',
                    'duration_minutes' => 115,
                    'age_rating' => 'SU',
                    'release_year' => 2023,
                    'synopsis' => 'Sinopsis dua',
                ],
            ],
        ]);

        $confirmResponse->assertRedirect(route('admin.films.index'));
        $this->assertDatabaseHas('films', ['title' => 'Film Impor Satu', 'release_year' => 2024]);
        $this->assertDatabaseHas('films', ['title' => 'Film Impor Dua', 'release_year' => 2023]);

        $batch = ImportBatch::find($batchToken);
        $this->assertNotNull($batch);
        $this->assertSame(2, $batch->successful_rows);

        // 3. Repeating import with same titles and strategy skip
        $repeatRows = [
            ['title' => 'Film Impor Satu', 'duration_minutes' => 105, 'genre' => 'Aksi', 'age_rating' => '13+', 'synopsis' => 'Sinopsis'],
        ];
        $repeatResult = $importer->executeImport($repeatRows, 'skip', $batchToken.'-2', $this->admin->id, 'repeat.csv');
        $this->assertSame(0, $repeatResult['imported_count']);
        $this->assertSame(1, $repeatResult['skipped_count']);
        $this->assertSame(1, Film::where('title', 'Film Impor Satu')->count());
    }

    public function test_batch_rollback_removes_all_films_in_batch(): void
    {
        $importer = app(FilmBulkImporter::class);

        $rows = [
            ['title' => 'Film Rollback 1', 'duration_minutes' => 90, 'genre' => 'Komedi', 'age_rating' => 'SU', 'synopsis' => 'S1'],
            ['title' => 'Film Rollback 2', 'duration_minutes' => 95, 'genre' => 'Komedi', 'age_rating' => 'SU', 'synopsis' => 'S2'],
        ];

        $batchResult = $importer->executeImport($rows, 'skip', 'test-batch-rollback', $this->admin->id, 'test.csv');
        $batchId = $batchResult['batch']->id;

        $this->assertSame(2, Film::where('import_batch_id', $batchId)->count());

        // Perform rollback via controller endpoint
        $rollbackResponse = $this->actingAs($this->admin)->post(route('admin.films.import.rollback', $batchId));
        $rollbackResponse->assertRedirect(route('admin.films.index'));
        $rollbackResponse->assertSessionHas('undo_batch_id', $batchId);

        $this->assertSame(0, Film::where('import_batch_id', $batchId)->count());
        $this->assertSame(2, Film::withTrashed()->where('import_batch_id', $batchId)->count());
        $this->assertDatabaseHas('import_batches', ['id' => $batchId, 'status' => 'cancelled']);
    }

    public function test_poster_zip_matching_and_cropping(): void
    {
        $importer = app(FilmBulkImporter::class);

        // Create a temporary zip with a dummy image (400x400 square)
        $tempZipPath = tempnam(sys_get_temp_dir(), 'test_zip_').'.zip';
        $zip = new ZipArchive;
        $zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Generate a 400x400 png image in memory
        $img = imagecreatetruecolor(400, 400);
        $color = imagecolorallocate($img, 200, 100, 50);
        imagefilledrectangle($img, 0, 0, 400, 400, $color);
        ob_start();
        imagepng($img);
        $pngData = ob_get_clean();
        imagedestroy($img);

        $zip->addFromString('avengers-endgame.png', $pngData);
        $zip->close();

        $uploadedZip = new UploadedFile($tempZipPath, 'posters.zip', 'application/zip', null, true);
        $extracted = $importer->extractAndProcessPosters($uploadedZip);

        $this->assertArrayHasKey('avengers-endgame.png', $extracted);
        $storedPath = $extracted['avengers-endgame.png'];
        Storage::disk('public')->assertExists($storedPath);

        // Test matching logic
        $matched = $importer->matchPoster('Avengers: Endgame', $extracted);
        $this->assertSame('avengers-endgame.png', $matched);

        if (file_exists($tempZipPath)) {
            @unlink($tempZipPath);
        }
    }

    public function test_malicious_zip_and_non_image_rejection(): void
    {
        $importer = app(FilmBulkImporter::class);

        $tempZipPath = tempnam(sys_get_temp_dir(), 'test_bad_zip_').'.zip';
        $zip = new ZipArchive;
        $zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Add malicious traversal and non-image files
        $zip->addFromString('../../../exploit.php', '<?php phpinfo(); ?>');
        $zip->addFromString('malware.exe', 'binary code');
        $zip->addFromString('shell.sh', '#!/bin/bash');
        $zip->close();

        $uploadedZip = new UploadedFile($tempZipPath, 'bad.zip', 'application/zip', null, true);
        $extracted = $importer->extractAndProcessPosters($uploadedZip);

        // No malicious file should be accepted
        $this->assertEmpty($extracted);

        if (file_exists($tempZipPath)) {
            @unlink($tempZipPath);
        }
    }

    public function test_film_bulk_actions_activate_deactivate_delete_restore(): void
    {
        $f1 = Film::create(['title' => 'Film Bulk 1', 'duration_minutes' => 90, 'genre' => 'Aksi', 'age_rating' => 'SU', 'synopsis' => 'S1', 'is_active' => true]);
        $f2 = Film::create(['title' => 'Film Bulk 2', 'duration_minutes' => 95, 'genre' => 'Drama', 'age_rating' => '13+', 'synopsis' => 'S2', 'is_active' => true]);

        // Bulk deactivate
        $this->actingAs($this->admin)->post(route('admin.films.bulk_action'), [
            'action' => 'deactivate',
            'film_ids' => [$f1->id, $f2->id],
        ]);
        $this->assertFalse((bool) $f1->fresh()->is_active);
        $this->assertFalse((bool) $f2->fresh()->is_active);

        // Bulk activate
        $this->actingAs($this->admin)->post(route('admin.films.bulk_action'), [
            'action' => 'activate',
            'film_ids' => [$f1->id, $f2->id],
        ]);
        $this->assertTrue((bool) $f1->fresh()->is_active);
        $this->assertTrue((bool) $f2->fresh()->is_active);

        // Bulk delete (soft delete)
        $this->actingAs($this->admin)->post(route('admin.films.bulk_action'), [
            'action' => 'delete',
            'film_ids' => [$f1->id, $f2->id],
        ]);
        $this->assertTrue($f1->fresh()->trashed());
        $this->assertTrue($f2->fresh()->trashed());

        // Bulk restore
        $this->actingAs($this->admin)->post(route('admin.films.bulk_action'), [
            'action' => 'restore',
            'film_ids' => [$f1->id, $f2->id],
        ]);
        $this->assertFalse($f1->fresh()->trashed());
        $this->assertFalse($f2->fresh()->trashed());
    }

    public function test_public_film_catalog_with_large_dataset_500_films(): void
    {
        // Insert 500 films in batches using DB::table for high performance
        $now = now();
        $batchData = [];
        for ($i = 1; $i <= 500; $i++) {
            $batchData[] = [
                'title' => "Katalog Film Ke-{$i}",
                'slug' => "katalog-film-ke-{$i}",
                'duration_minutes' => 90 + ($i % 30),
                'genre' => ($i % 2 === 0) ? 'Aksi, Petualangan' : 'Drama, Komedi',
                'age_rating' => ($i % 3 === 0) ? 'SU' : '13+',
                'synopsis' => "Sinopsis film untuk pengujian skala besar index katalog film nomor {$i}.",
                'release_year' => 2020 + ($i % 6),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($batchData, 100) as $chunk) {
            Film::insert($chunk);
        }

        $this->assertSame(500, Film::count());

        $startTime = microtime(true);
        $response = $this->get(route('films.index'));
        $duration = microtime(true) - $startTime;

        $response->assertOk();
        $response->assertViewIs('public.films.index');
        $response->assertSee('Katalog Film');
        $response->assertSee('Menampilkan 1–24 dari 500 film');
        $response->assertSee('Katalog Film Ke-1');

        // Check that response was delivered smoothly (< 1.5s)
        $this->assertLessThan(2.0, $duration, "Catalog page for 500 films loaded in {$duration}s");
    }

    public function test_ajax_chunked_processing_endpoint(): void
    {
        $csvContent = "judul,durasi,genre,klasifikasi,sinopsis,tahun\n"
            ."Film Chunked Satu,100,Aksi,13+,Sinopsis satu,2024\n"
            .'Film Chunked Dua,110,Drama,SU,Sinopsis dua,2023';

        $csvFile = UploadedFile::fake()->createWithContent('chunk_films.csv', $csvContent);

        $previewResponse = $this->actingAs($this->admin)->post(route('admin.films.import.preview'), [
            'csv_file' => $csvFile,
            'duplicate_strategy' => 'skip',
        ]);

        $previewResponse->assertOk();
        $batchToken = $previewResponse->viewData('batchToken');

        $chunkResponse = $this->actingAs($this->admin)->postJson(route('admin.films.import.chunk'), [
            'batch_token' => $batchToken,
            'chunk_index' => 0,
            'chunk_size' => 25,
            'duplicate_strategy' => 'skip',
        ]);

        $chunkResponse->assertOk();
        $chunkResponse->assertJsonPath('status', 'ok');
        $chunkResponse->assertJsonPath('is_last', true);
        $chunkResponse->assertJsonPath('batch_imported', 2);

        $this->assertDatabaseHas('films', ['title' => 'Film Chunked Satu', 'release_year' => 2024]);
        $this->assertDatabaseHas('films', ['title' => 'Film Chunked Dua', 'release_year' => 2023]);
    }

    public function test_error_report_and_catalog_export_downloads(): void
    {
        Film::create([
            'title' => 'Film Export Sample',
            'duration_minutes' => 95,
            'genre' => 'Aksi',
            'age_rating' => '13+',
            'release_year' => 2025,
            'synopsis' => 'Sinopsis sampel ekspor.',
            'is_active' => false,
        ]);

        // 1. Export catalog CSV
        $exportResponse = $this->actingAs($this->admin)->get(route('admin.films.export'));
        $exportResponse->assertOk();
        $this->assertStringContainsString('text/csv', $exportResponse->headers->get('Content-Type'));

        // 2. Download error report
        $reportResponse = $this->actingAs($this->admin)->get(route('admin.films.import.report_errors', ['batchToken' => 'batch-dummy-test']));
        $reportResponse->assertOk();
        $this->assertStringContainsString('text/csv', $reportResponse->headers->get('Content-Type'));
    }

    public function test_poster_management_routes_and_assignment(): void
    {
        $film = Film::create([
            'title' => 'Film Butuh Poster',
            'duration_minutes' => 90,
            'genre' => 'Horor',
            'age_rating' => '17+',
            'synopsis' => 'Sinopsis horor butuh poster.',
            'is_active' => true,
        ]);

        // Index
        $indexResponse = $this->actingAs($this->admin)->get(route('admin.films.posters.index'));
        $indexResponse->assertOk();
        $indexResponse->assertViewIs('admin.films.posters');

        // Create dummy poster
        $img = imagecreatetruecolor(400, 600);
        $color = imagecolorallocate($img, 100, 150, 200);
        imagefilledrectangle($img, 0, 0, 400, 600, $color);
        ob_start();
        imagepng($img);
        $pngData = ob_get_clean();
        imagedestroy($img);

        $file = UploadedFile::fake()->createWithContent('film-butuh-poster.png', $pngData);

        // Upload
        $uploadResponse = $this->actingAs($this->admin)->post(route('admin.films.posters.upload'), [
            'posters' => [$file],
        ]);
        $uploadResponse->assertRedirect(route('admin.films.posters.index'));

        // Film should have auto-matched poster
        $film->refresh();
        $this->assertNotNull($film->poster_path);
        Storage::disk('public')->assertExists($film->poster_path);
    }

    public function test_data_rule_empty_year_allowed_and_invalid_year_flagged(): void
    {
        $importer = app(FilmBulkImporter::class);

        // 1. Empty year is allowed (null, status 'baru')
        $rowEmptyYear = [
            ['title' => 'Film Tanpa Tahun', 'genre' => 'Aksi', 'duration_minutes' => 90, 'age_rating' => '13+', 'release_year' => ''],
        ];
        $resEmpty = $importer->analyzeRows($rowEmptyYear);
        $this->assertSame('baru', $resEmpty[0]['status']);
        $this->assertNull($resEmpty[0]['release_year']);
        $this->assertArrayNotHasKey('release_year', $resEmpty[0]['cell_errors']);

        // 2. Invalid year (< 1900, > next year, non-numeric) is flagged as 'masalah' and sets release_year = null (never silently current year)
        $rowInvalidYear = [
            ['title' => 'Film Tahun 1850', 'genre' => 'Aksi', 'duration_minutes' => 90, 'age_rating' => '13+', 'release_year' => '1850'],
            ['title' => 'Film Tahun Depan Jauh', 'genre' => 'Aksi', 'duration_minutes' => 90, 'age_rating' => '13+', 'release_year' => '2099'],
            ['title' => 'Film Tahun Abc', 'genre' => 'Aksi', 'duration_minutes' => 90, 'age_rating' => '13+', 'release_year' => 'abc'],
        ];
        $resInvalid = $importer->analyzeRows($rowInvalidYear);
        foreach ($resInvalid as $ri) {
            $this->assertSame('masalah', $ri['status']);
            $this->assertNull($ri['release_year']);
            $this->assertArrayHasKey('release_year', $ri['cell_errors']);
            $this->assertNotEquals((int) date('Y'), $ri['release_year']);
        }
    }

    public function test_data_rule_duration_range_boundaries(): void
    {
        $importer = app(FilmBulkImporter::class);

        // 1 and 400 are valid
        $validDurations = [
            ['title' => 'Film Durasi 1', 'genre' => 'Aksi', 'duration_minutes' => '1', 'age_rating' => '13+'],
            ['title' => 'Film Durasi 400', 'genre' => 'Aksi', 'duration_minutes' => '400', 'age_rating' => '13+'],
        ];
        $resValid = $importer->analyzeRows($validDurations);
        $this->assertSame('baru', $resValid[0]['status']);
        $this->assertSame(1, $resValid[0]['duration_minutes']);
        $this->assertSame('baru', $resValid[1]['status']);
        $this->assertSame(400, $resValid[1]['duration_minutes']);

        // 0, 401, empty, negative are invalid ('masalah')
        $invalidDurations = [
            ['title' => 'Film Durasi 0', 'genre' => 'Aksi', 'duration_minutes' => '0', 'age_rating' => '13+'],
            ['title' => 'Film Durasi 401', 'genre' => 'Aksi', 'duration_minutes' => '401', 'age_rating' => '13+'],
            ['title' => 'Film Durasi Kosong', 'genre' => 'Aksi', 'duration_minutes' => '', 'age_rating' => '13+'],
            ['title' => 'Film Durasi Negatif', 'genre' => 'Aksi', 'duration_minutes' => '-15', 'age_rating' => '13+'],
        ];
        $resInvalid = $importer->analyzeRows($invalidDurations);
        foreach ($resInvalid as $riv) {
            $this->assertSame('masalah', $riv['status']);
            $this->assertArrayHasKey('duration_minutes', $riv['cell_errors']);
        }
    }

    public function test_data_rule_classification_variations_and_unknowns(): void
    {
        $importer = app(FilmBulkImporter::class);

        // "21+" and "D21" reported as variations requiring admin confirmation (status 'masalah')
        $var21 = [
            ['title' => 'Film 21 Plus', 'genre' => 'Thriller', 'duration_minutes' => 100, 'age_rating' => '21+'],
            ['title' => 'Film D21', 'genre' => 'Thriller', 'duration_minutes' => 100, 'age_rating' => 'D21'],
        ];
        $res21 = $importer->analyzeRows($var21);
        $this->assertSame('masalah', $res21[0]['status']);
        $this->assertStringContainsString('variasi 21+', $res21[0]['errors'][0]);
        $this->assertSame('masalah', $res21[1]['status']);
        $this->assertStringContainsString('variasi 21+', $res21[1]['errors'][0]);

        // Unknown classification reported as 'masalah' with suggestions
        $unknownRating = [
            ['title' => 'Film Rating Aneh', 'genre' => 'Drama', 'duration_minutes' => 95, 'age_rating' => 'DewasaBanget'],
        ];
        $resUnknown = $importer->analyzeRows($unknownRating);
        $this->assertSame('masalah', $resUnknown[0]['status']);
        $this->assertStringContainsString('tidak dikenal', $resUnknown[0]['errors'][0]);
        $this->assertStringContainsString('Saran', $resUnknown[0]['errors'][0]);
    }

    public function test_resume_import_flow_after_tab_close_without_duplicate(): void
    {
        $importer = app(FilmBulkImporter::class);

        // Generate 60 rows CSV (requiring 3 chunks: 25, 25, 10)
        $csvContent = "judul,genre,tahun,durasi,klasifikasi,sinopsis,tampil\n";
        for ($i = 1; $i <= 60; $i++) {
            $csvContent .= sprintf("Film Tab Resume %02d,Aksi,2025,100,13+,Sinopsis %d,tidak\n", $i, $i);
        }

        $csvFile = UploadedFile::fake()->createWithContent('resume_test.csv', $csvContent);

        // 1. Submit preview
        $previewRes = $this->actingAs($this->admin)->post(route('admin.films.import.preview'), [
            'csv_file' => $csvFile,
            'duplicate_strategy' => 'skip',
        ]);
        $previewRes->assertOk();
        $batchToken = $previewRes->viewData('batchToken');

        // 2. Process first chunk (25 rows)
        $chunk1 = $this->actingAs($this->admin)->postJson(route('admin.films.import.chunk'), [
            'batch_token' => $batchToken,
            'chunk_index' => 0,
            'chunk_size' => 25,
            'duplicate_strategy' => 'skip',
        ]);
        $chunk1->assertOk();
        $chunk1->assertJsonPath('batch_imported', 25);
        $this->assertSame(25, Film::where('import_batch_id', $batchToken)->count());

        // 3. Simulate user closing tab and resuming via GET /admin/films/impor/lanjut/{batchToken}
        $resumeRes = $this->actingAs($this->admin)->get(route('admin.films.import.resume', $batchToken));
        $resumeRes->assertOk();
        $resumeRes->assertViewIs('admin.films.import-preview');
        $this->assertTrue($resumeRes->viewData('autoResume'));
        $this->assertSame(1, $resumeRes->viewData('initialChunk')); // starts from chunk index 1 (row 26-50)
        $this->assertSame(25, $resumeRes->viewData('initialProcessed'));

        // 4. Continue remaining chunks (chunk 1 and chunk 2)
        $chunk2 = $this->actingAs($this->admin)->postJson(route('admin.films.import.chunk'), [
            'batch_token' => $batchToken,
            'chunk_index' => 1,
            'chunk_size' => 25,
            'duplicate_strategy' => 'skip',
        ]);
        $chunk2->assertOk();
        $chunk2->assertJsonPath('batch_imported', 50);

        $chunk3 = $this->actingAs($this->admin)->postJson(route('admin.films.import.chunk'), [
            'batch_token' => $batchToken,
            'chunk_index' => 2,
            'chunk_size' => 25,
            'duplicate_strategy' => 'skip',
        ]);
        $chunk3->assertOk();
        $chunk3->assertJsonPath('is_last', true);
        $chunk3->assertJsonPath('batch_imported', 60);

        // Verify total films created is exactly 60 without duplicates
        $this->assertSame(60, Film::where('import_batch_id', $batchToken)->count());
        $batch = ImportBatch::find($batchToken);
        $this->assertSame('completed', $batch->status);
    }

    public function test_acceptance_with_film_trending_okt2026_csv(): void
    {
        $importer = app(FilmBulkImporter::class);

        $csvPath = base_path('scratch/film_trending_okt2026.csv');
        $this->assertFileExists($csvPath);

        $content = file_get_contents($csvPath);
        $this->assertTrue(str_starts_with($content, "\xEF\xBB\xBF"), 'File must contain UTF-8 BOM');

        $rows = $importer->parseCsv($content);
        $this->assertCount(27, $rows, 'CSV must contain exactly 27 rows');

        $analyzed = $importer->analyzeRows($rows);
        $this->assertCount(27, $analyzed);

        $baruCount = count(array_filter($analyzed, fn ($r) => $r['status'] === 'baru'));
        $masalahCount = count(array_filter($analyzed, fn ($r) => $r['status'] === 'masalah'));
        $perluDilengkapiCount = count(array_filter($analyzed, fn ($r) => ! empty($r['needs_synopsis'])));

        $this->assertSame(27, $baruCount, 'Expected exactly 27 Baru');
        $this->assertSame(0, $masalahCount, 'Expected 0 Masalah');
        $this->assertSame(8, $perluDilengkapiCount, 'Expected exactly 8 Perlu dilengkapi');

        // Execute import
        $batchId = 'acceptance-trending-batch';
        $result = $importer->executeChunk($analyzed, 'skip', $batchId, $this->admin->id, 'film_trending_okt2026.csv');

        $this->assertSame(27, $result['imported']);
        $this->assertSame(0, $result['failed']);

        // Verify all 27 imported films have is_active = false
        $importedFilms = Film::where('import_batch_id', $batchId)->get();
        $this->assertCount(27, $importedFilms);
        foreach ($importedFilms as $f) {
            $this->assertFalse((bool) $f->is_active, "Film {$f->title} must be inactive (hidden)");
        }
    }

    public function test_rule_release_year_empty_allowed_and_invalid_marked_as_problem_not_defaulted(): void
    {
        $importer = app(FilmBulkImporter::class);

        $csv = "judul,durasi,genre,klasifikasi,sinopsis,tahun\n"
            ."Film Tahun Kosong,100,Aksi,13+,Sinopsis kosong tahun,\n"
            ."Film Tahun Kuno,100,Aksi,13+,Sinopsis tahun kuno,1850\n"
            .'Film Tahun Jauh Masa Depan,100,Aksi,13+,Sinopsis tahun masa depan,3050';

        $rows = $importer->parseCsv($csv);
        $analyzed = $importer->analyzeRows($rows);

        // 1. Empty year is allowed (null), not problem
        $this->assertSame('baru', $analyzed[0]['status']);
        $this->assertNull($analyzed[0]['release_year']);
        $this->assertEmpty($analyzed[0]['errors']);

        // 2. Year < 1900 is Ada Masalah and NOT defaulted to current year
        $this->assertSame('masalah', $analyzed[1]['status']);
        $this->assertNull($analyzed[1]['release_year']);
        $this->assertArrayHasKey('release_year', $analyzed[1]['cell_errors']);
        $this->assertStringContainsString('Harus antara 1900', $analyzed[1]['cell_errors']['release_year']);

        // 3. Year > next year is Ada Masalah and NOT defaulted to current year
        $this->assertSame('masalah', $analyzed[2]['status']);
        $this->assertNull($analyzed[2]['release_year']);
        $this->assertArrayHasKey('release_year', $analyzed[2]['cell_errors']);
    }

    public function test_rule_duration_strictly_between_1_and_400_minutes(): void
    {
        $importer = app(FilmBulkImporter::class);

        $csv = "judul,durasi,genre,klasifikasi,sinopsis,tahun\n"
            ."Film Durasi Nol,0,Aksi,13+,Sinopsis nol,2024\n"
            ."Film Durasi Negatif,-15,Aksi,13+,Sinopsis negatif,2024\n"
            ."Film Durasi Melebihi,401,Aksi,13+,Sinopsis melebihi,2024\n"
            ."Film Durasi Valid Min,1,Aksi,13+,Sinopsis min,2024\n"
            .'Film Durasi Valid Max,400,Aksi,13+,Sinopsis max,2024';

        $rows = $importer->parseCsv($csv);
        $analyzed = $importer->analyzeRows($rows);

        // Nol
        $this->assertSame('masalah', $analyzed[0]['status']);
        $this->assertArrayHasKey('duration_minutes', $analyzed[0]['cell_errors']);

        // Negatif
        $this->assertSame('masalah', $analyzed[1]['status']);
        $this->assertArrayHasKey('duration_minutes', $analyzed[1]['cell_errors']);

        // 401
        $this->assertSame('masalah', $analyzed[2]['status']);
        $this->assertArrayHasKey('duration_minutes', $analyzed[2]['cell_errors']);

        // 1 & 400
        $this->assertSame('baru', $analyzed[3]['status']);
        $this->assertSame(1, $analyzed[3]['duration_minutes']);

        $this->assertSame('baru', $analyzed[4]['status']);
        $this->assertSame(400, $analyzed[4]['duration_minutes']);
    }

    public function test_rule_age_rating_unknown_and_21_plus_reported_as_variation_requiring_confirmation(): void
    {
        $importer = app(FilmBulkImporter::class);

        $csv = "judul,durasi,genre,klasifikasi,sinopsis,tahun\n"
            ."Film Variasi 21,100,Aksi,21+,Sinopsis 21+,2024\n"
            ."Film Variasi D21,100,Aksi,D21,Sinopsis D21,2024\n"
            ."Film Tak Dikenal,100,Aksi,DewasaBanget,Sinopsis tak dikenal,2024\n"
            ."Film Rating Baku SU,100,Aksi,SU,Sinopsis baku,2024\n"
            ."Film Rating Baku 13,100,Aksi,13+,Sinopsis baku,2024\n"
            .'Film Rating Baku 17,100,Aksi,17+,Sinopsis baku,2024';

        $rows = $importer->parseCsv($csv);
        $analyzed = $importer->analyzeRows($rows);

        // 21+ must NOT be silently mapped to 17+; it must be flagged as masalah needing admin confirmation
        $this->assertSame('masalah', $analyzed[0]['status']);
        $this->assertArrayHasKey('age_rating', $analyzed[0]['cell_errors']);
        $this->assertStringContainsStringIgnoringCase('variasi 21+', $analyzed[0]['cell_errors']['age_rating']);

        // D21 must also be flagged as variasi 21+
        $this->assertSame('masalah', $analyzed[1]['status']);
        $this->assertArrayHasKey('age_rating', $analyzed[1]['cell_errors']);
        $this->assertStringContainsStringIgnoringCase('variasi 21+', $analyzed[1]['cell_errors']['age_rating']);

        // Unknown rating must be flagged with suggestions
        $this->assertSame('masalah', $analyzed[2]['status']);
        $this->assertArrayHasKey('age_rating', $analyzed[2]['cell_errors']);
        $this->assertStringContainsString('Saran: SU / 13+ / 17+', $analyzed[2]['cell_errors']['age_rating']);

        // Standard ratings SU, 13+, 17+ are valid (baru)
        $this->assertSame('baru', $analyzed[3]['status']);
        $this->assertSame('SU', $analyzed[3]['age_rating']);

        $this->assertSame('baru', $analyzed[4]['status']);
        $this->assertSame('13+', $analyzed[4]['age_rating']);

        $this->assertSame('baru', $analyzed[5]['status']);
        $this->assertSame('17+', $analyzed[5]['age_rating']);
    }
}
