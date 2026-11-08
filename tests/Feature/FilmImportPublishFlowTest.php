<?php

namespace Tests\Feature;

use App\Models\Film;
use App\Models\User;
use App\Services\FilmBulkImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FilmImportPublishFlowTest extends TestCase
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

    public function test_template_download_contains_ya_in_sample_rows(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.films.import.template'));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('Inception;"Sci-Fi; Aksi";2010;148;13+;', $content);
        $this->assertStringContainsString(';ya', $content);
        $this->assertStringNotContainsString(';tidak', $content);
    }

    public function test_import_with_switch_on_publishes_films_immediately_to_catalog_and_home(): void
    {
        $csvContent = "judul;genre;tahun;durasi;klasifikasi;sinopsis\n"
            ."Film Langsung Tampil 1;Aksi;2024;100;13+;Sinopsis film 1\n"
            ."Film Langsung Tampil 2;Drama;2024;110;SU;Sinopsis film 2\n"
            ."Film Langsung Tampil 3;Komedi;2024;95;13+;Sinopsis film 3\n"
            ."Film Langsung Tampil 4;Horor;2024;90;17+;Sinopsis film 4\n"
            ."Film Langsung Tampil 5;Animasi;2024;85;SU;Sinopsis film 5\n";

        $file = UploadedFile::fake()->createWithContent('films.csv', $csvContent);

        // Step 1: Preview with publish_on_import = 1
        $previewRes = $this->actingAs($this->admin)->post(route('admin.films.import.preview'), [
            'csv_file' => $file,
            'publish_on_import' => '1',
            'duplicate_strategy' => 'skip',
        ]);

        $previewRes->assertOk();
        $batchToken = $previewRes->viewData('batchToken');
        $this->assertNotEmpty($batchToken);

        // Step 2: Confirm import
        $confirmRes = $this->actingAs($this->admin)->post(route('admin.films.import.confirm'), [
            'batch_token' => $batchToken,
            'duplicate_strategy' => 'skip',
            'publish_on_import' => '1',
        ]);

        $confirmRes->assertRedirect(route('admin.films.index'));

        // Verify in database: All 5 films must have is_active = true
        $this->assertSame(5, Film::where('is_active', true)->count());

        // Verify public catalog and home
        $catalogRes = $this->get('/film');
        $catalogRes->assertOk();
        $catalogRes->assertSee('Film Langsung Tampil 1');
        $catalogRes->assertSee('Film Langsung Tampil 5');

        $homeRes = $this->get('/');
        $homeRes->assertOk();
        $homeRes->assertSee('Film Langsung Tampil 1');
    }

    public function test_import_with_switch_off_keeps_films_hidden_and_shows_admin_banner(): void
    {
        $csvContent = "judul;genre;tahun;durasi;klasifikasi;sinopsis\n"
            ."Film Draf 1;Aksi;2024;100;13+;Sinopsis draf 1\n"
            ."Film Draf 2;Drama;2024;110;SU;Sinopsis draf 2\n";

        $file = UploadedFile::fake()->createWithContent('films_draft.csv', $csvContent);

        // Preview with publish_on_import = 0
        $previewRes = $this->actingAs($this->admin)->post(route('admin.films.import.preview'), [
            'csv_file' => $file,
            'publish_on_import' => '0',
            'duplicate_strategy' => 'skip',
        ]);

        $previewRes->assertOk();
        $batchToken = $previewRes->viewData('batchToken');

        // Confirm
        $confirmRes = $this->actingAs($this->admin)->post(route('admin.films.import.confirm'), [
            'batch_token' => $batchToken,
            'duplicate_strategy' => 'skip',
            'publish_on_import' => '0',
        ]);

        $confirmRes->assertRedirect(route('admin.films.index'));

        // All 2 films are hidden
        $this->assertSame(2, Film::where('is_active', false)->count());
        $this->assertSame(0, Film::where('is_active', true)->count());

        // Check admin index displays the warning banner
        $adminRes = $this->actingAs($this->admin)->get(route('admin.films.index'));
        $adminRes->assertOk();
        $adminRes->assertSee('2 film belum tampil di katalog bioskop');
        $adminRes->assertSee('Tampilkan Semua');

        // Execute bulk activation
        $bulkRes = $this->actingAs($this->admin)->post(route('admin.films.bulk_action'), [
            'action' => 'activate',
            'select_all' => '1',
            'filter' => 'belum_tampil',
        ]);

        $bulkRes->assertSessionHas('status', '2 film berhasil ditampilkan.');
        $bulkRes->assertSessionHas('undo_activated_ids');

        // All 2 films are now active
        $this->assertSame(2, Film::where('is_active', true)->count());

        // Now visible in catalog
        $catalogRes = $this->get('/film');
        $catalogRes->assertOk();
        $catalogRes->assertSee('Film Draf 1');
    }

    public function test_rollback_import_clears_cache_and_removes_films_from_catalog(): void
    {
        $importer = app(FilmBulkImporter::class);
        $batchId = 'batch-rollback-test-'.time();

        $rows = [
            [
                'title' => 'Film Rollback Test',
                'duration_minutes' => 120,
                'genre' => 'Aksi',
                'age_rating' => '13+',
                'release_year' => 2024,
                'synopsis' => 'Sinopsis',
                'is_active' => true,
            ],
        ];

        $importer->executeImport($rows, 'skip', $batchId, $this->admin->id, 'test.csv');

        // Confirm visible in catalog
        $this->get('/film')->assertSee('Film Rollback Test');

        // Perform rollback
        $rollbackRes = $this->actingAs($this->admin)->post(route('admin.films.import.rollback', $batchId));
        $rollbackRes->assertRedirect(route('admin.films.index'));

        $film = Film::withTrashed()->where('title', 'Film Rollback Test')->first();
        $this->assertNotNull($film);
        $this->assertTrue($film->trashed());

        // No longer visible in catalog
        $this->get('/film')->assertDontSee('Film Rollback Test');
    }
}
