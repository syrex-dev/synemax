<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\FilmPosterController;
use App\Models\Film;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OcrPosterMatchingTest extends TestCase
{
    use RefreshDatabase;

    private FilmPosterController $controller;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new FilmPosterController;
        $this->admin = User::factory()->create(['is_admin' => true]);
        Storage::fake('public');
    }

    /**
     * Test case: Sequel isolation prevents "Danur" from matching "Danur 2" and vice versa.
     */
    public function test_sequel_isolation_between_danur_and_danur_2(): void
    {
        $danur1 = Film::query()->create([
            'title' => 'Danur',
            'slug' => 'danur',
            'genre' => 'Horor',
            'synopsis' => 'Sinopsis horor.',
            'duration_minutes' => 90,
            'age_rating' => '17+',
            'is_active' => true,
        ]);

        $danur2 = Film::query()->create([
            'title' => 'Danur 2: Maddah',
            'slug' => 'danur-2-maddah',
            'genre' => 'Horor',
            'synopsis' => 'Sinopsis horor 2.',
            'duration_minutes' => 95,
            'age_rating' => '17+',
            'is_active' => true,
        ]);

        $candidates = collect([$danur1, $danur2]);

        // 1. OCR text only has "DANUR" -> must match Danur 1, NOT Danur 2
        $match1 = $this->controller->matchFilmFromOcr('DANUR', $candidates);
        $this->assertNotNull($match1);
        $this->assertSame($danur1->id, $match1['film']->id);
        $this->assertTrue($match1['is_unique']);
        $this->assertFalse($match1['needs_review']);

        // 2. OCR text has "DANUR 2" -> must match Danur 2, NOT Danur 1
        $match2 = $this->controller->matchFilmFromOcr('DANUR 2', $candidates);
        $this->assertNotNull($match2);
        $this->assertSame($danur2->id, $match2['film']->id);
        $this->assertTrue($match2['is_unique']);
        $this->assertFalse($match2['needs_review']);

        // 3. OCR text has Roman numeral "DANUR II" -> normalized to "2", matches Danur 2
        $match3 = $this->controller->matchFilmFromOcr('DANUR II', $candidates);
        $this->assertNotNull($match3);
        $this->assertSame($danur2->id, $match3['film']->id);
        $this->assertTrue($match3['is_unique']);
        $this->assertFalse($match3['needs_review']);
    }

    /**
     * Test case: Subphrase protection between "Mati Rasa" and "Perayaan Mati Rasa".
     */
    public function test_subphrase_protection_between_mati_rasa_and_perayaan_mati_rasa(): void
    {
        $matiRasa = Film::query()->create([
            'title' => 'Mati Rasa',
            'slug' => 'mati-rasa',
            'genre' => 'Drama',
            'synopsis' => 'Sinopsis mati rasa.',
            'duration_minutes' => 100,
            'age_rating' => '13+',
            'is_active' => true,
        ]);

        $perayaanMatiRasa = Film::query()->create([
            'title' => 'Perayaan Mati Rasa',
            'slug' => 'perayaan-mati-rasa',
            'genre' => 'Drama',
            'synopsis' => 'Sinopsis perayaan mati rasa.',
            'duration_minutes' => 110,
            'age_rating' => '13+',
            'is_active' => true,
        ]);

        $candidates = collect([$matiRasa, $perayaanMatiRasa]);

        // 1. OCR "MATI RASA" -> matches Mati Rasa exactly, margin >= 0.15 over Perayaan Mati Rasa
        $match1 = $this->controller->matchFilmFromOcr('MATI RASA', $candidates);
        $this->assertNotNull($match1);
        $this->assertSame($matiRasa->id, $match1['film']->id);
        $this->assertTrue($match1['is_unique']);
        $this->assertGreaterThanOrEqual(0.15, $match1['margin']);

        // 2. OCR "PERAYAAN MATI RASA" -> matches Perayaan Mati Rasa exactly
        $match2 = $this->controller->matchFilmFromOcr('PERAYAAN MATI RASA', $candidates);
        $this->assertNotNull($match2);
        $this->assertSame($perayaanMatiRasa->id, $match2['film']->id);
        $this->assertTrue($match2['is_unique']);
        $this->assertGreaterThanOrEqual(0.15, $match2['margin']);
    }

    /**
     * Test case: Wide letter-spacing (kerning) and Leet / stylized poster typography.
     */
    public function test_kerning_and_leet_typography_matching(): void
    {
        $sekawanLimo = Film::query()->create([
            'title' => 'Sekawan Limo',
            'slug' => 'sekawan-limo',
            'genre' => 'Komedi',
            'synopsis' => 'Sinopsis sekawan limo.',
            'duration_minutes' => 100,
            'age_rating' => '13+',
            'is_active' => true,
        ]);

        $candidates = collect([$sekawanLimo]);

        // Spaced out letters (kerning)
        $matchSpaced = $this->controller->matchFilmFromOcr('S E K A W A N   L I M O', $candidates);
        $this->assertNotNull($matchSpaced);
        $this->assertSame($sekawanLimo->id, $matchSpaced['film']->id);
        $this->assertGreaterThanOrEqual(0.95, $matchSpaced['confidence']);

        // Leet & stylized OCR typos ('0' for 'o', 'v' for 'w')
        $matchLeet = $this->controller->matchFilmFromOcr('SEKAVAN LIM0', $candidates);
        $this->assertNotNull($matchLeet);
        $this->assertSame($sekawanLimo->id, $matchLeet['film']->id);
        $this->assertGreaterThanOrEqual(0.85, $matchLeet['confidence']);
    }

    /**
     * Test case: Ambiguous OCR with margin < 0.15 enters "needs_review" with 3 suggestions.
     */
    public function test_ambiguous_ocr_enters_needs_review_with_suggestions(): void
    {
        $filmA = Film::query()->create([
            'title' => 'Cahaya Cinta Sejati',
            'slug' => 'cahaya-cinta-sejati',
            'genre' => 'Romantis',
            'synopsis' => 'Sinopsis cinta sejati.',
            'duration_minutes' => 90,
            'age_rating' => '13+',
            'is_active' => true,
        ]);

        $filmB = Film::query()->create([
            'title' => 'Cahaya Cinta Abadi',
            'slug' => 'cahaya-cinta-abadi',
            'genre' => 'Romantis',
            'synopsis' => 'Sinopsis cinta abadi.',
            'duration_minutes' => 92,
            'age_rating' => '13+',
            'is_active' => true,
        ]);

        $filmC = Film::query()->create([
            'title' => 'Cahaya Cinta Pertama',
            'slug' => 'cahaya-cinta-pertama',
            'genre' => 'Romantis',
            'synopsis' => 'Sinopsis cinta pertama.',
            'duration_minutes' => 95,
            'age_rating' => '13+',
            'is_active' => true,
        ]);

        $candidates = collect([$filmA, $filmB, $filmC]);

        // Incomplete OCR text only has "CAHAYA CINTA"
        $match = $this->controller->matchFilmFromOcr('CAHAYA CINTA', $candidates);
        $this->assertNotNull($match);
        // Because all three share "Cahaya Cinta", margin is < 0.15
        $this->assertLessThan(0.15, $match['margin']);
        $this->assertTrue($match['needs_review']);
        $this->assertFalse($match['is_unique']);
        $this->assertNotEmpty($match['suggestions']);
        $this->assertCount(3, $match['suggestions']);
    }

    /**
     * Test case: Batch assignment and undo_batch restore previous poster paths.
     */
    public function test_batch_assignment_and_undo_batch_restores_previous_state(): void
    {
        $film = Film::query()->create([
            'title' => 'Film Uji Coba Undo',
            'slug' => 'film-uji-coba-undo',
            'genre' => 'Aksi',
            'synopsis' => 'Sinopsis uji coba undo.',
            'duration_minutes' => 100,
            'age_rating' => '17+',
            'poster_path' => 'posters/original_old_poster.webp',
            'is_active' => true,
        ]);

        Storage::disk('public')->put('posters/unmatched/poster_batch_test.webp', 'image_data');
        Storage::disk('public')->put('posters/original_old_poster.webp', 'old_data');

        // 1. Batch assign
        $assignRes = $this->actingAs($this->admin)->post(route('admin.films.posters.assign_batch'), [
            'assignments' => [
                [
                    'film_id' => $film->id,
                    'poster_filename' => 'poster_batch_test.webp',
                ],
            ],
        ], ['Accept' => 'application/json']);

        $assignRes->assertOk();
        $assignRes->assertJson(['success' => true, 'assigned_count' => 1]);

        $film->refresh();
        $newPermanentPath = $film->poster_path;
        $this->assertNotSame('posters/original_old_poster.webp', $newPermanentPath);
        Storage::disk('public')->assertExists($newPermanentPath);
        Storage::disk('public')->assertMissing('posters/unmatched/poster_batch_test.webp');

        // 2. Undo batch
        $items = $assignRes->json('items');
        $undoRes = $this->actingAs($this->admin)->post(route('admin.films.posters.undo_batch'), [
            'reverts' => $items,
        ], ['Accept' => 'application/json']);

        $undoRes->assertOk();
        $undoRes->assertJson(['success' => true, 'reverted_count' => 1]);

        // Verify restoration: film restored to original_old_poster.webp, file moved back to unmatched
        $film->refresh();
        $this->assertSame('posters/original_old_poster.webp', $film->poster_path);
        Storage::disk('public')->assertExists('posters/unmatched/poster_batch_test.webp');
        Storage::disk('public')->assertMissing($newPermanentPath);
    }

    /**
     * Test case: Comprehensive 40 real Indonesian movie poster cases benchmark.
     */
    public function test_comprehensive_40_posters_benchmark_dataset(): void
    {
        $benchmarkDataset = [
            // [Title in Catalog, OCR Text Extracted from Poster, Expected Match Title, Should Auto Match]
            ['Danur', 'DANUR I CAN SEE GHOSTS', 'Danur', true],
            ['Danur 2: Maddah', 'DANUR 2 MADDAH', 'Danur 2: Maddah', true],
            ['Danur 3: Sunyaruri', 'DANUR III SUNYARURI', 'Danur 3: Sunyaruri', true],
            ['Mati Rasa', 'MATI RASA SEBUAH FILM OLEH UMAM', 'Mati Rasa', true],
            ['Perayaan Mati Rasa', 'PERAYAAN MATI RASA BIOSKOP', 'Perayaan Mati Rasa', true],
            ['Sekawan Limo', 'S E K A W A N   L I M O', 'Sekawan Limo', true],
            ['Agak Laen', 'AGAK LAEN 2024 BIAR TAYANG', 'Agak Laen', true],
            ['Pengabdi Setan', 'PENGABDI SETAN JOKO ANWAR', 'Pengabdi Setan', true],
            ['Pengabdi Setan 2: Communion', 'PENGABDI SETAN 2 COMMUNION', 'Pengabdi Setan 2: Communion', true],
            ['Siksa Kubur', 'SIKSA KUBUR GRAVE TORTURE', 'Siksa Kubur', true],
            ['Petualangan Sherina', 'PETUALANGAN SHERINA CLASSIC', 'Petualangan Sherina', true],
            ['Petualangan Sherina 2', 'PETUALANGAN SHERINA 2 KEMBALI', 'Petualangan Sherina 2', true],
            ['Dilan 1990', 'DILAN 1990 DIA ADALAH DILANKU', 'Dilan 1990', true],
            ['Dilan 1991', 'DILAN 1991 LANJUTAN', 'Dilan 1991', true],
            ['Milea: Suara dari Dilan', 'MILEA SUARA DARI DILAN', 'Milea: Suara dari Dilan', true],
            ['KKN di Desa Penari', 'KKN DI DESA PENARI ASLI', 'KKN di Desa Penari', true],
            ['KKN di Desa Penari: Luwih Dowo', 'KKN DI DESA PENARI LUWIH DOWO LUWIH MEDENI', 'KKN di Desa Penari: Luwih Dowo', true],
            ['Sewu Dino', 'SEWU DINO TRAP 1000 HARI', 'Sewu Dino', true],
            ['Vina: Sebelum 7 Hari', 'VINA SEBELUM 7 HARI KISAH NYATA', 'Vina: Sebelum 7 Hari', true],
            ['Keluarga Cemara', 'KELUARGA CEMARA HARTA PALING BERHARGA', 'Keluarga Cemara', true],
            ['Keluarga Cemara 2', 'KELUARGA CEMARA 2 DI BIOSKOP', 'Keluarga Cemara 2', true],
            ['Ngeri-Ngeri Sedap', 'NGERI NGERI SEDAP BATAK FAMILY', 'Ngeri-Ngeri Sedap', true],
            ['Miracle in Cell No. 7', 'MIRACLE IN CELL NO 7 INDONESIA', 'Miracle in Cell No. 7', true],
            ['Laskar Pelangi', 'LASKAR PELANGI ANDREA HIRATA', 'Laskar Pelangi', true],
            ['Sang Pemimpi', 'SANG PEMIMPI TETRALOGI LASKAR PELANGI', 'Sang Pemimpi', true],
            ['Warkop DKI Reborn: Jangkrik Boss Part 1', 'WARKOP DKI REBORN JANGKRIK BOSS PART 1', 'Warkop DKI Reborn: Jangkrik Boss Part 1', true],
            ['Warkop DKI Reborn: Jangkrik Boss Part 2', 'WARKOP DKI REBORN JANGKRIK BOSS PART 2', 'Warkop DKI Reborn: Jangkrik Boss Part 2', true],
            ['Gundala', 'GUNDALA NEGERI INI BUTUH PATRIOT', 'Gundala', true],
            ['Sri Asih', 'SRI ASIH JAGAT BUMILANGIT', 'Sri Asih', true],
            ['Budi Pekerti', 'BUDI PEKERTI ANDAP ASOR', 'Budi Pekerti', true],
            ['Jatuh Cinta Seperti di Film-film', 'JATUH CINTA SEPERTI DI FILM FILM', 'Jatuh Cinta Seperti di Film-film', true],
            ['Kereta Berdarah', 'KERETA BERDARAH MENUJU RESORT', 'Kereta Berdarah', true],
            ['Pemandi Jenazah', 'PEMANDI JENAZAH SUCIKAN DOSA', 'Pemandi Jenazah', true],
            ['Malam Para Jahanam', 'MALAM PARA JAHANAM DESA MALAM', 'Malam Para Jahanam', true],
            ['Ancika: Dia yang Bersamaku 1995', 'ANCIKA DIA YANG BERSAMAKU 1995', 'Ancika: Dia yang Bersamaku 1995', true],
            ['Badarawuhi di Desa Penari', 'BADARAWUHI DI DESA PENARI DARI PRODUSER KKN', 'Badarawuhi di Desa Penari', true],
            ['Kupu-Kupu Kertas', 'KUPU KUPU KERTAS BANYUWANGI', 'Kupu-Kupu Kertas', true],
            ['Kaka Boss', 'KAKA BOSS SEMUA BISA DIATUR', 'Kaka Boss', true],
            ['Home Sweet Loan', 'HOME SWEET LOAN KALUNA PEKERJA KERAS', 'Home Sweet Loan', true],
            ['Lembayung', 'LEMBAYUNG POLIKLINIK GIGI TERTUTUP', 'Lembayung', true],
        ];

        $filmModels = [];
        foreach ($benchmarkDataset as $idx => [$title]) {
            $filmModels[] = Film::query()->create([
                'title' => $title,
                'slug' => 'film-benchmark-'.$idx,
                'synopsis' => 'Sinopsis film benchmark '.$idx,
                'genre' => 'Drama',
                'duration_minutes' => 100,
                'age_rating' => '13+',
                'is_active' => true,
            ]);
        }

        $allCandidates = collect($filmModels);

        $totalTests = count($benchmarkDataset);
        $correctMatches = 0;
        $autoMatchedCount = 0;

        $tableRows = [];
        foreach ($benchmarkDataset as [$catalogTitle, $ocrText, $expectedTitle, $expectedAutoMatch]) {
            $match = $this->controller->matchFilmFromOcr($ocrText, $allCandidates);

            $this->assertNotNull($match, "OCR text '{$ocrText}' should produce a match.");
            $this->assertSame($expectedTitle, $match['film']->title, "OCR text '{$ocrText}' matched '{$match['film']->title}' instead of '{$expectedTitle}'.");

            if ($match['film']->title === $expectedTitle) {
                $correctMatches++;
            }

            $isAuto = (! $match['needs_review'] && $match['is_unique']);
            if ($isAuto) {
                $autoMatchedCount++;
            }
        }

        $precision = ($correctMatches / $totalTests) * 100;
        $coverage = ($autoMatchedCount / $totalTests) * 100;

        $this->assertSame(40, $totalTests);
        $this->assertSame(40, $correctMatches);
        $this->assertEquals(100.0, $precision);
        $this->assertGreaterThanOrEqual(90.0, $coverage);
    }
}
