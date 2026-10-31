<?php

namespace App\Services;

use App\Models\Film;
use App\Models\ImportBatch;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class FilmBulkImporter
{
    public const TARGET_WIDTH = 600;

    public const TARGET_HEIGHT = 900;

    public const TARGET_RATIO = 2 / 3;

    /**
     * Parse raw CSV/text content with auto delimiter detection and multiline handling.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseCsv(string $content): array
    {
        // 1. Remove UTF-8 BOM if present
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        // 2. Normalize line breaks to \n
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // 3. Detect delimiter by sampling first few non-empty lines
        $delimiter = $this->detectDelimiter($content);

        // 4. Parse using a memory stream to support multiline quoted fields cleanly
        $stream = fopen('php://temp', 'r+');
        if (! $stream) {
            throw new RuntimeException('Gagal membuka alur memori untuk membaca CSV.');
        }

        fwrite($stream, $content);
        rewind($stream);

        $header = null;
        $rows = [];

        while (($row = fgetcsv($stream, 0, $delimiter, '"', '\\')) !== false) {
            // Skip empty rows
            if ($row === null || (count($row) === 1 && trim((string) $row[0]) === '')) {
                continue;
            }

            if ($header === null) {
                $header = array_map(fn ($h) => $this->normalizeHeader((string) $h), $row);

                continue;
            }

            // Combine header and row values with padding if needed
            $paddedRow = array_pad($row, count($header), '');
            $assoc = [];
            foreach ($header as $idx => $key) {
                if ($key !== '') {
                    $assoc[$key] = isset($paddedRow[$idx]) ? trim((string) $paddedRow[$idx]) : '';
                }
            }
            $rows[] = $assoc;
        }

        fclose($stream);

        return $rows;
    }

    /**
     * Auto detect delimiter (, ; \t |).
     */
    private function detectDelimiter(string $content): string
    {
        $delimiters = [',', ';', "\t", '|'];
        $firstLines = array_filter(array_slice(explode("\n", $content), 0, 5), fn ($l) => trim($l) !== '');

        if (empty($firstLines)) {
            return ',';
        }

        $scores = [];
        foreach ($delimiters as $delim) {
            $counts = [];
            foreach ($firstLines as $line) {
                $counts[] = count(str_getcsv($line, $delim, '"', '\\'));
            }
            // Check consistency (all sampled lines have same number of columns > 1)
            $first = $counts[0] ?? 0;
            if ($first > 1 && count(array_unique($counts)) === 1) {
                $scores[$delim] = $first * 10;
            } else {
                $scores[$delim] = array_sum($counts);
            }
        }

        arsort($scores);

        return key($scores) ?: ',';
    }

    /**
     * Normalize header to standard internal key.
     */
    public function normalizeHeader(string $raw): string
    {
        $clean = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace([' ', '-'], '_', $raw))));

        return match ($clean) {
            'judul', 'title', 'nama_film', 'nama', 'film' => 'title',
            'durasi', 'duration', 'duration_minutes', 'durasi_menit', 'menit' => 'duration_minutes',
            'genre', 'genres', 'kategori' => 'genre',
            'klasifikasi', 'usia', 'age_rating', 'rating', 'rating_usia', 'kategori_usia' => 'age_rating',
            'tahun', 'release_year', 'year', 'tahun_rilis' => 'release_year',
            'sinopsis', 'synopsis', 'deskripsi', 'cerita', 'ringkasan' => 'synopsis',
            'tampil', 'status', 'is_active', 'aktif', 'tayang', 'visibilitas' => 'is_active',
            'poster', 'poster_filename', 'poster_file', 'gambar', 'file_poster' => 'poster_filename',
            'trailer', 'trailer_url', 'youtube_url', 'tautan_trailer' => 'trailer_url',
            default => $clean,
        };
    }

    /**
     * Normalize duplicate key using lowercase letters, numbers, spaces, and release year.
     */
    public function normalizeDuplicateKey(string $title, mixed $year = null): string
    {
        $cleanTitle = mb_strtolower(trim($title), 'UTF-8');
        // Remove punctuation and extra whitespace
        $cleanTitle = preg_replace('/[^\p{L}\p{N}\s]/u', '', $cleanTitle);
        $cleanTitle = preg_replace('/\s+/', ' ', (string) $cleanTitle);

        $cleanYear = $year ? (string) (int) $year : '';

        return trim((string) $cleanTitle).'___'.$cleanYear;
    }

    /**
     * Parse genres string and fuzzy match against canonical genres, detecting unknown tokens.
     *
     * @return array{raw: string, normalized: string, matched: list<string>, unknown: list<array{raw: string, suggestion: string}>, warnings: list<string>}
     */
    public function parseAndMatchGenres(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [
                'raw' => '',
                'normalized' => '',
                'matched' => [],
                'unknown' => [],
                'warnings' => ['Genre film wajib diisi.'],
            ];
        }

        $tokens = preg_split('/[,;\/|]+/', $raw);
        $canonical = Film::CANONICAL_GENRES;
        $lowerCanonicalMap = [];
        foreach ($canonical as $cg) {
            $lowerCanonicalMap[strtolower($cg)] = $cg;
        }

        // Aliases & common English translations / typos
        $aliasMap = [
            'action' => 'Aksi',
            'aksi' => 'Aksi',
            'animation' => 'Animasi',
            'animasi' => 'Animasi',
            'anime' => 'Animasi',
            'kartun' => 'Animasi',
            'drama' => 'Drama',
            'fantasy' => 'Fantasi',
            'fantasi' => 'Fantasi',
            'horror' => 'Horor',
            'horor' => 'Horor',
            'horer' => 'Horor',
            'family' => 'Keluarga',
            'keluarga' => 'Keluarga',
            'comedy' => 'Komedi',
            'komedi' => 'Komedi',
            'komik' => 'Komedi',
            'komidi' => 'Komedi',
            'mystery' => 'Misteri',
            'misteri' => 'Misteri',
            'mistis' => 'Misteri',
            'adventure' => 'Petualangan',
            'petualangan' => 'Petualangan',
            'petualang' => 'Petualangan',
            'roman' => 'Romantis',
            'romance' => 'Romantis',
            'romantis' => 'Romantis',
            'romantik' => 'Romantis',
            'sci-fi' => 'Sci-Fi',
            'scifi' => 'Sci-Fi',
            'science fiction' => 'Sci-Fi',
            'fiksi ilmiah' => 'Sci-Fi',
            'thriller' => 'Thriller',
            'triler' => 'Thriller',
            'tegang' => 'Thriller',
        ];

        $matched = [];
        $unknown = [];
        $warnings = [];

        foreach ($tokens as $token) {
            $tokenClean = trim((string) $token);
            $tokenLower = strtolower($tokenClean);
            if ($tokenLower === '') {
                continue;
            }

            if (isset($aliasMap[$tokenLower])) {
                $matched[] = $aliasMap[$tokenLower];
            } elseif (isset($lowerCanonicalMap[$tokenLower])) {
                $matched[] = $lowerCanonicalMap[$tokenLower];
            } else {
                // Find closest suggestion using Levenshtein distance
                $closestSuggestion = null;
                $lowestDistance = PHP_INT_MAX;
                foreach ($canonical as $cg) {
                    $dist = levenshtein($tokenLower, strtolower($cg));
                    if ($dist < $lowestDistance) {
                        $lowestDistance = $dist;
                        $closestSuggestion = $cg;
                    }
                }
                $unknown[] = [
                    'raw' => $tokenClean,
                    'suggestion' => $closestSuggestion ?: 'Drama',
                ];
                $warnings[] = "Genre '{$tokenClean}' tidak dikenal. Saran terdekat: '{$closestSuggestion}'.";
            }
        }

        $uniqueMatched = array_values(array_unique($matched));

        return [
            'raw' => $raw,
            'normalized' => implode(', ', $uniqueMatched),
            'matched' => $uniqueMatched,
            'unknown' => $unknown,
            'warnings' => $warnings,
        ];
    }

    /**
     * Analyze and normalize each row, detecting duplicates and mapping genres/ratings.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function analyzeRows(array $rows): array
    {
        $seenKeys = [];

        // Preload existing database titles and years
        $existingFilms = Film::withTrashed()->get(['title', 'release_year', 'slug']);
        $dbKeys = [];
        foreach ($existingFilms as $ef) {
            $key = $this->normalizeDuplicateKey((string) $ef->title, $ef->release_year);
            $dbKeys[$key] = true;
            // Also key by title alone for fallback
            $titleOnlyKey = $this->normalizeDuplicateKey((string) $ef->title, null);
            $dbKeys[$titleOnlyKey] = true;
        }

        $analyzed = [];
        $currentYear = (int) date('Y');
        $maxAllowedYear = $currentYear + 1;

        foreach ($rows as $index => $row) {
            $warnings = [];
            $errors = [];
            $cellErrors = [];

            // 1. Title validation (Wajib)
            $rawTitle = trim((string) ($row['title'] ?? ''));
            if ($rawTitle === '') {
                $errors[] = 'Judul film wajib diisi.';
                $cellErrors['title'] = 'Judul film wajib diisi.';
                $cleanTitle = '';
                $slug = '';
            } else {
                $cleanTitle = mb_substr($rawTitle, 0, 180);
                $slug = Str::slug($cleanTitle);
            }

            // 2. Duration validation (Wajib: 1-400 menit)
            $rawDuration = trim((string) ($row['duration_minutes'] ?? ''));
            if ($rawDuration === '') {
                $errors[] = 'Durasi wajib diisi angka menit (1–400).';
                $cellErrors['duration_minutes'] = 'Durasi wajib diisi.';
                $duration = 0;
            } elseif (! ctype_digit($rawDuration) || (int) $rawDuration < 1 || (int) $rawDuration > 400) {
                $errors[] = 'Durasi film harus antara 1 sampai 400 menit.';
                $cellErrors['duration_minutes'] = 'Harus antara 1–400 menit.';
                $durationDigits = (int) preg_replace('/[^0-9]/', '', $rawDuration);
                $duration = ($durationDigits >= 1 && $durationDigits <= 400) ? $durationDigits : 0;
            } else {
                $duration = (int) $rawDuration;
            }

            // 3. Release Year (Opsional: 1900 s/d tahun depan)
            $rawYear = trim((string) ($row['release_year'] ?? ''));
            $releaseYear = null;
            if ($rawYear !== '') {
                $yearDigits = (int) preg_replace('/[^0-9]/', '', $rawYear);
                if (ctype_digit($rawYear) && strlen($rawYear) === 4 && $yearDigits >= 1900 && $yearDigits <= $maxAllowedYear) {
                    $releaseYear = $yearDigits;
                } else {
                    $errors[] = "Tahun rilis '{$rawYear}' tidak valid (harus antara 1900–{$maxAllowedYear}).";
                    $cellErrors['release_year'] = "Harus antara 1900–{$maxAllowedYear}.";
                    $releaseYear = null;
                }
            }

            // 4. Duplicate detection (judul + tahun sama)
            $isDuplicate = false;
            if ($cleanTitle !== '') {
                $dupKey = $this->normalizeDuplicateKey($cleanTitle, $releaseYear);
                $titleOnlyKey = $this->normalizeDuplicateKey($cleanTitle, null);

                if (isset($dbKeys[$dupKey]) || ($releaseYear === null && isset($dbKeys[$titleOnlyKey]))) {
                    $isDuplicate = true;
                    $warnings[] = 'Film dengan judul dan tahun ini sudah ada di database (duplikat).';
                } elseif (isset($seenKeys[$dupKey])) {
                    $isDuplicate = true;
                    $warnings[] = 'Judul dan tahun film duplikat dengan baris sebelumnya di dalam berkas ini.';
                } else {
                    $seenKeys[$dupKey] = true;
                }
            }

            // 5. Genre validation & normalization (Wajib)
            $rawGenre = trim((string) ($row['genre'] ?? ''));
            $genreAnalysis = $this->parseAndMatchGenres($rawGenre);
            if ($rawGenre === '') {
                $errors[] = 'Genre film wajib diisi.';
                $cellErrors['genre'] = 'Genre wajib diisi.';
                $normalizedGenres = '';
            } else {
                $normalizedGenres = $genreAnalysis['normalized'];
                if (! empty($genreAnalysis['unknown'])) {
                    foreach ($genreAnalysis['warnings'] as $gw) {
                        $warnings[] = $gw;
                    }
                    $cellErrors['genre'] = 'Memuat genre tidak baku ('.implode(', ', array_column($genreAnalysis['unknown'], 'raw')).').';
                }
                if ($normalizedGenres === '') {
                    $errors[] = 'Tidak ada genre baku yang cocok.';
                    $cellErrors['genre'] = 'Pilih atau petakan ke genre baku.';
                }
            }

            // 6. Age rating validation (Wajib: SU, 13+, 17+)
            $rawAge = trim((string) ($row['age_rating'] ?? ''));
            if ($rawAge === '') {
                $errors[] = 'Klasifikasi usia wajib diisi (SU, 13+, 17+).';
                $cellErrors['age_rating'] = 'Wajib diisi (SU, 13+, 17+).';
                $normalizedAge = '13+';
            } else {
                $ageWarning = null;
                $isUnknown = false;
                $isVariation21 = false;
                $normalizedAge = $this->normalizeAgeRating($rawAge, $ageWarning, $isUnknown, $isVariation21);

                if ($isVariation21) {
                    $errors[] = "Klasifikasi '{$rawAge}' merupakan variasi 21+ yang perlu dikonfirmasi admin (saran: petakan ke 17+).";
                    $cellErrors['age_rating'] = 'Variasi 21+: konfirmasi ke 17+ atau sesuaikan.';
                } elseif ($isUnknown) {
                    $errors[] = "Klasifikasi usia '{$rawAge}' tidak dikenal. Saran: pilih SU, 13+, atau 17+.";
                    $cellErrors['age_rating'] = 'Tidak dikenal. Saran: SU / 13+ / 17+.';
                } elseif ($ageWarning) {
                    $warnings[] = $ageWarning;
                }
            }

            // 7. Synopsis (Opsional, kosong diizinkan tapi ditandai "Perlu dilengkapi")
            $rawSynopsis = (string) ($row['synopsis'] ?? '');
            $synopsis = trim(strip_tags($rawSynopsis));
            $needsSynopsis = false;
            if ($synopsis === '' || $synopsis === 'Sinopsis belum tersedia untuk film ini.' || $synopsis === 'Sinopsis belum tersedia.') {
                $synopsis = '';
                $needsSynopsis = true;
                $warnings[] = 'Sinopsis kosong (perlu dilengkapi).';
            }

            // 8. Is Active / Tampil (Bawaan: DISEMBUNYIKAN / 0 / false)
            $rawActive = strtolower(trim((string) ($row['is_active'] ?? '0')));
            $isActive = in_array($rawActive, ['1', 'ya', 'yes', 'true', 'aktif', 'on'], true);

            // Determine status
            // We support Indonesian labels ('baru', 'duplikat', 'masalah') as primary status,
            // with aliases for backwards compatibility with legacy tests
            if (! empty($errors)) {
                $status = 'masalah';
            } elseif ($isDuplicate) {
                $status = 'duplikat';
            } elseif (! empty($genreAnalysis['unknown'])) {
                $status = 'masalah';
                $errors[] = 'Terdapat genre tak dikenal yang perlu dipetakan ke genre baku.';
            } else {
                $status = 'baru';
            }

            $analyzed[] = [
                'row_index' => $index + 1,
                'title' => $cleanTitle,
                'raw_title' => $rawTitle,
                'slug' => $slug,
                'duration_minutes' => $duration,
                'genre' => $normalizedGenres ?: $rawGenre,
                'raw_genre' => $rawGenre,
                'unknown_genres' => $genreAnalysis['unknown'] ?? [],
                'age_rating' => $normalizedAge,
                'release_year' => $releaseYear,
                'synopsis' => $synopsis,
                'needs_synopsis' => $needsSynopsis,
                'is_active' => $isActive,
                'poster_filename' => trim((string) ($row['poster_filename'] ?? '')),
                'trailer_url' => trim((string) ($row['trailer_url'] ?? '')),
                'status' => $status,
                'is_duplicate' => $isDuplicate,
                'errors' => $errors,
                'warnings' => $warnings,
                'cell_errors' => $cellErrors,
                'is_included' => $status !== 'masalah',
            ];
        }

        return $analyzed;
    }

    /**
     * Map genres to Film::CANONICAL_GENRES.
     *
     * @param  list<string>  $warnings
     */
    public function normalizeGenres(string $raw, ?array &$warnings = null): string
    {
        $warnings = [];
        if (trim($raw) === '') {
            $warnings[] = 'Genre kosong, otomatis dikelompokkan ke Drama.';

            return 'Drama';
        }

        $tokens = preg_split('/[,;\/|]+/', $raw);
        $canonical = Film::CANONICAL_GENRES;
        $lowerMap = [];
        foreach ($canonical as $cg) {
            $lowerMap[strtolower($cg)] = $cg;
        }

        // Aliases & common English translations / typos
        $aliasMap = [
            'action' => 'Aksi',
            'aksi' => 'Aksi',
            'animation' => 'Animasi',
            'animasi' => 'Animasi',
            'anime' => 'Animasi',
            'kartun' => 'Animasi',
            'drama' => 'Drama',
            'fantasy' => 'Fantasi',
            'fantasi' => 'Fantasi',
            'horror' => 'Horor',
            'horor' => 'Horor',
            'horer' => 'Horor',
            'family' => 'Keluarga',
            'keluarga' => 'Keluarga',
            'comedy' => 'Komedi',
            'komedi' => 'Komedi',
            'komik' => 'Komedi',
            'komidi' => 'Komedi',
            'mystery' => 'Misteri',
            'misteri' => 'Misteri',
            'mistis' => 'Misteri',
            'adventure' => 'Petualangan',
            'petualangan' => 'Petualangan',
            'petualang' => 'Petualangan',
            'roman' => 'Romantis',
            'romance' => 'Romantis',
            'romantis' => 'Romantis',
            'romantik' => 'Romantis',
            'sci-fi' => 'Sci-Fi',
            'scifi' => 'Sci-Fi',
            'science fiction' => 'Sci-Fi',
            'fiksi ilmiah' => 'Sci-Fi',
            'thriller' => 'Thriller',
            'triler' => 'Thriller',
            'tegang' => 'Thriller',
        ];

        $matched = [];
        foreach ($tokens as $token) {
            $cleanToken = strtolower(trim($token));
            if ($cleanToken === '') {
                continue;
            }

            if (isset($aliasMap[$cleanToken])) {
                $matched[] = $aliasMap[$cleanToken];
            } elseif (isset($lowerMap[$cleanToken])) {
                $matched[] = $lowerMap[$cleanToken];
            } else {
                // Fuzzy match
                $bestMatch = null;
                $highestSimilar = 0;
                foreach ($canonical as $cg) {
                    similar_text($cleanToken, strtolower($cg), $percent);
                    if ($percent > $highestSimilar && $percent >= 65) {
                        $highestSimilar = $percent;
                        $bestMatch = $cg;
                    }
                }
                if ($bestMatch) {
                    $matched[] = $bestMatch;
                    $warnings[] = "Genre '{$token}' dikoreksi otomatis menjadi '{$bestMatch}'.";
                } else {
                    $warnings[] = "Genre '{$token}' tidak dikenali dan diabaikan.";
                }
            }
        }

        $unique = array_values(array_unique($matched));
        if (empty($unique)) {
            $warnings[] = 'Tidak ada genre valid yang cocok, diset ke Drama.';

            return 'Drama';
        }

        return implode(', ', $unique);
    }

    public function normalizeGenre(string $raw, ?array &$warnings = null): string
    {
        return $this->normalizeGenres($raw, $warnings);
    }

    /**
     * Normalize age rating (SU, 13+, 17+).
     */
    public function normalizeAgeRating(
        string $raw,
        ?string &$warning = null,
        bool &$isUnknown = false,
        bool &$isVariation21 = false
    ): string {
        $warning = null;
        $isUnknown = false;
        $isVariation21 = false;
        $clean = strtoupper(trim(str_replace([' ', '-'], '', $raw)));

        if ($clean === '' || in_array($clean, ['13', '13+', 'R13', 'R13+', 'PG13', 'PG-13', 'REMAJA', 'PG'], true)) {
            return '13+';
        }
        if (in_array($clean, ['SU', 'G', 'SEMUAUMUR', 'SEMUA', 'ALL', 'BO', 'A'], true)) {
            return 'SU';
        }
        if (in_array($clean, ['17', '17+', 'D17', 'R17', 'DEWASA', 'R'], true)) {
            return '17+';
        }
        if (in_array($clean, ['21', '21+', 'D21', 'NC17', '18', '18+'], true)) {
            $isVariation21 = true;
            $warning = "Klasifikasi usia '{$raw}' merupakan variasi 21+ yang perlu dikonfirmasi admin (saran: petakan ke 17+).";

            return '17+';
        }

        $isUnknown = true;
        $warning = "Klasifikasi usia '{$raw}' tidak dikenal. Saran: pilih SU, 13+, atau 17+.";

        return '13+';
    }

    /**
     * Safely extract a ZIP archive of posters to a staging directory.
     * Protects against Zip Slip (path traversal) and dangerous file types.
     *
     * @return array<string, array{filename: string, staged_path: string, size: int}> Keyed by lowercase basename
     */
    public function extractPosterZip(UploadedFile|string $zipFile, string $batchId): array
    {
        $zipPath = $zipFile instanceof UploadedFile ? $zipFile->getRealPath() : $zipFile;

        $zip = new ZipArchive;
        $openResult = $zip->open($zipPath);
        if ($openResult !== true) {
            throw new RuntimeException("Gagal membuka file ZIP poster. Kode error: {$openResult}.");
        }

        $stagingDir = storage_path("app/tmp_posters/{$batchId}");
        if (! is_dir($stagingDir)) {
            mkdir($stagingDir, 0755, true);
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $extracted = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);

            // Security 1: Prevent Zip Slip (path traversal)
            if (str_contains($entryName, '..') || str_starts_with($entryName, '/') || str_starts_with($entryName, '\\')) {
                continue;
            }

            // Security 2: Skip MacOS metadata folders & hidden files
            if (str_contains($entryName, '__MACOSX') || basename($entryName)[0] === '.') {
                continue;
            }

            $ext = strtolower(pathinfo($entryName, PATHINFO_EXTENSION));
            if (! in_array($ext, $allowedExtensions, true)) {
                continue;
            }

            $contents = $zip->getFromIndex($i);
            if ($contents === false || strlen($contents) === 0) {
                continue;
            }

            // Security 3: Validate genuine image header via getimagesizefromstring
            $imageInfo = @getimagesizefromstring($contents);
            if ($imageInfo === false || ! in_array($imageInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
                continue;
            }

            $baseName = basename($entryName);
            $cleanFilename = Str::slug(pathinfo($baseName, PATHINFO_FILENAME)).'.'.$ext;
            $destPath = "{$stagingDir}/{$cleanFilename}";

            file_put_contents($destPath, $contents);

            $extracted[strtolower($baseName)] = [
                'filename' => $baseName,
                'staged_path' => $destPath,
                'size' => strlen($contents),
            ];
            // Also key by slug for easy slug matching
            $extracted[strtolower(pathinfo($baseName, PATHINFO_FILENAME))] = [
                'filename' => $baseName,
                'staged_path' => $destPath,
                'size' => strlen($contents),
            ];
        }

        $zip->close();

        return $extracted;
    }

    /**
     * Extract poster zip and process them directly into 600x900 WebP in public storage.
     *
     * @return array<string, string> Keyed by filename/basename with value being public storage path
     */
    public function extractAndProcessPosters(UploadedFile|string $zipFile, ?string $batchId = null): array
    {
        $batchId = $batchId ?: Str::uuid()->toString();
        $extracted = $this->extractPosterZip($zipFile, $batchId);
        $processed = [];

        foreach ($extracted as $key => $info) {
            if (isset($info['staged_path']) && file_exists($info['staged_path'])) {
                if (str_contains($key, '.')) {
                    $stored = $this->cropAndStorePoster($info['staged_path']);
                    $processed[$key] = $stored;
                    $noExt = strtolower(pathinfo($key, PATHINFO_FILENAME));
                    $processed[$noExt] = $stored;
                }
            }
        }

        return $processed;
    }

    /**
     * Center-crop an image to standard 2:3 aspect ratio and convert to 600x900 WebP (fallback JPG).
     * Automatically strips EXIF metadata through fresh truecolor canvas creation.
     */
    public function cropAndStorePoster(string $imagePath): string
    {
        if (! function_exists('imagecreatefromstring')) {
            throw new RuntimeException('Ekstensi GD PHP wajib aktif untuk memproses poster.');
        }

        $contents = file_get_contents($imagePath);
        if ($contents === false) {
            throw new RuntimeException("Gagal membaca berkas poster dari path: {$imagePath}");
        }

        $source = @imagecreatefromstring($contents);
        if ($source === false) {
            throw new RuntimeException('File gambar poster rusak atau tidak valid.');
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);

        if ($srcW <= 0 || $srcH <= 0) {
            imagedestroy($source);
            throw new RuntimeException('Dimensi gambar tidak valid.');
        }

        $srcRatio = $srcW / $srcH;
        $targetRatio = self::TARGET_RATIO; // 600 / 900 = 0.6667

        // Center crop calculation
        if ($srcRatio > $targetRatio) {
            // Source is wider than 2:3 -> crop horizontally
            $cropH = $srcH;
            $cropW = (int) round($srcH * $targetRatio);
            $cropX = (int) round(($srcW - $cropW) / 2);
            $cropY = 0;
        } else {
            // Source is taller than 2:3 or equal -> crop vertically
            $cropW = $srcW;
            $cropH = (int) round($srcW / $targetRatio);
            $cropX = 0;
            $cropY = (int) round(($srcH - $cropH) / 2);
        }

        $target = imagecreatetruecolor(self::TARGET_WIDTH, self::TARGET_HEIGHT);
        imagealphablending($target, false);
        imagesavealpha($target, true);

        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            $cropX,
            $cropY,
            self::TARGET_WIDTH,
            self::TARGET_HEIGHT,
            $cropW,
            $cropH
        );

        // Fallback WebP -> JPG if imagewebp is unsupported
        $useWebp = function_exists('imagewebp');
        ob_start();
        if ($useWebp) {
            $ok = @imagewebp($target, null, 82);
            $extension = 'webp';
        } else {
            $ok = @imagejpeg($target, null, 85);
            $extension = 'jpg';
        }
        $encoded = ob_get_clean();

        imagedestroy($source);
        imagedestroy($target);

        if (! $ok || $encoded === false) {
            throw new RuntimeException('Gagal mengompresi poster.');
        }

        $storagePath = 'posters/'.Str::uuid().'.'.$extension;
        Storage::disk('public')->put($storagePath, $encoded);

        return $storagePath;
    }

    /**
     * Match a poster from extracted ZIP list by filename, slug, or title.
     */
    public function matchPoster(string|array $title, string|array $slug = '', ?string $posterFilename = null, array $posters = []): ?string
    {
        if (is_array($title)) {
            $row = $title;
            $posters = is_array($slug) ? $slug : (is_array($posterFilename) ? $posterFilename : $posters);
            $actualTitle = (string) ($row['title'] ?? '');
            $actualSlug = (string) ($row['slug'] ?? Str::slug($actualTitle));
            $actualFilename = $row['poster_filename'] ?? ($row['matched_poster'] ?? null);

            return $this->matchPoster($actualTitle, $actualSlug, $actualFilename, $posters);
        }

        if (is_array($slug)) {
            $posters = $slug;
            $slug = Str::slug($title);
            $posterFilename = null;
        }

        $titleSlug = Str::slug($title);

        // 1. Direct filename match
        if ($posterFilename) {
            $key = strtolower(basename($posterFilename));
            if (isset($posters[$key])) {
                $val = $posters[$key];

                return is_array($val) ? $val['staged_path'] : $key;
            }
            $noExt = strtolower(pathinfo($posterFilename, PATHINFO_FILENAME));
            if (isset($posters[$noExt])) {
                $val = $posters[$noExt];

                return is_array($val) ? $val['staged_path'] : $noExt;
            }
        }

        // 2. Match actual filename with extension
        foreach ($posters as $k => $val) {
            if (str_contains($k, '.')) {
                $kBase = strtolower(pathinfo($k, PATHINFO_FILENAME));
                if ($kBase === $titleSlug || Str::slug($kBase) === $titleSlug) {
                    return is_array($val) ? ($val['staged_path'] ?? $k) : $k;
                }
            }
        }

        // 3. Slug or normalized title match without extension
        if ($slug !== '') {
            $slugKey = strtolower((string) $slug);
            if (isset($posters[$slugKey])) {
                $val = $posters[$slugKey];

                return is_array($val) ? $val['staged_path'] : $slugKey;
            }
        }

        if (isset($posters[$titleSlug])) {
            $val = $posters[$titleSlug];

            return is_array($val) ? $val['staged_path'] : $titleSlug;
        }

        return null;
    }

    /**
     * Protect against CSV formula injection by prepending apostrophe to dangerous leading characters.
     */
    public function sanitizeCsvCell(mixed $value): string
    {
        $str = (string) $value;
        if ($str !== '' && in_array($str[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$str;
        }

        return $str;
    }

    /**
     * Find existing film by title and year (supporting normalized matching).
     */
    public function findExistingFilm(string $title, ?int $releaseYear = null): ?Film
    {
        $query = Film::withTrashed();

        if ($releaseYear) {
            $exact = (clone $query)->where('title', $title)->where('release_year', $releaseYear)->first();
            if ($exact) {
                return $exact;
            }
        } else {
            $exact = (clone $query)->where('title', $title)->first();
            if ($exact) {
                return $exact;
            }
        }

        $targetKey = $this->normalizeDuplicateKey($title, $releaseYear);
        $candidates = (clone $query)->where('title', 'like', $title)->get();
        foreach ($candidates as $cand) {
            if ($this->normalizeDuplicateKey((string) $cand->title, $cand->release_year) === $targetKey) {
                return $cand;
            }
        }

        return null;
    }

    /**
     * Execute a chunk of rows (typically 25 rows) in a dedicated DB transaction.
     *
     * @param  array<int, array<string, mixed>>  $rowsChunk
     * @param  array<string, mixed>  $availablePosters
     * @return array{imported: int, skipped: int, failed: int, failed_rows: list<array{row_index: int, title: string, error: string}>}
     */
    public function executeChunk(
        array $rowsChunk,
        string $strategy,
        string $batchId,
        ?int $userId = null,
        string $originalFilename = 'import.csv',
        array $availablePosters = []
    ): array {
        return DB::transaction(function () use ($rowsChunk, $strategy, $batchId, $userId, $originalFilename, $availablePosters) {
            $chunkImported = 0;
            $chunkSkipped = 0;
            $chunkFailed = 0;
            $chunkFailedRows = [];

            $batch = ImportBatch::firstOrCreate(
                ['id' => $batchId],
                [
                    'filename' => $originalFilename ?: 'import.csv',
                    'total_rows' => 0,
                    'imported_count' => 0,
                    'skipped_count' => 0,
                    'failed_count' => 0,
                    'failed_rows' => [],
                    'user_id' => $userId,
                    'status' => 'processing',
                ]
            );

            foreach ($rowsChunk as $row) {
                $rowIndex = (int) ($row['row_index'] ?? 0);
                $title = trim((string) ($row['title'] ?? ''));

                // Excluded rows
                if (isset($row['is_included']) && ! $row['is_included']) {
                    $chunkSkipped++;

                    continue;
                }

                // Empty title error
                if ($title === '') {
                    $chunkFailed++;
                    $chunkFailedRows[] = [
                        'row_index' => $rowIndex,
                        'title' => '(Judul Kosong)',
                        'error' => 'Judul film wajib diisi.',
                    ];

                    continue;
                }

                // Missing required duration or genre
                $duration = (int) ($row['duration_minutes'] ?? 0);
                if ($duration <= 0 || $duration > 400) {
                    $chunkFailed++;
                    $chunkFailedRows[] = [
                        'row_index' => $rowIndex,
                        'title' => $title,
                        'error' => 'Durasi film harus antara 1 sampai 400 menit.',
                    ];

                    continue;
                }

                $genre = trim((string) ($row['genre'] ?? ''));
                if ($genre === '') {
                    $chunkFailed++;
                    $chunkFailedRows[] = [
                        'row_index' => $rowIndex,
                        'title' => $title,
                        'error' => 'Genre film wajib diisi.',
                    ];

                    continue;
                }

                $releaseYear = ! empty($row['release_year']) ? (int) $row['release_year'] : null;
                $existing = $this->findExistingFilm($title, $releaseYear);

                if ($existing) {
                    if ($strategy === 'skip') {
                        $chunkSkipped++;

                        continue;
                    } elseif ($strategy === 'update') {
                        if ($existing->trashed()) {
                            $existing->restore();
                        }

                        $matchedPosterPath = $this->matchPoster(
                            $title,
                            $existing->slug,
                            $row['poster_filename'] ?? ($row['matched_poster'] ?? null),
                            $availablePosters
                        );
                        $posterPath = null;
                        if ($matchedPosterPath && file_exists($matchedPosterPath)) {
                            $posterPath = $this->cropAndStorePoster($matchedPosterPath);
                        } elseif ($matchedPosterPath && is_string($matchedPosterPath) && Storage::disk('public')->exists($matchedPosterPath)) {
                            $posterPath = $matchedPosterPath;
                        }

                        $existing->update([
                            'duration_minutes' => $duration,
                            'genre' => $genre,
                            'age_rating' => $row['age_rating'] ?? $existing->age_rating,
                            'release_year' => $releaseYear ?: $existing->release_year,
                            'synopsis' => ! empty($row['synopsis']) ? $row['synopsis'] : ($existing->synopsis ?: 'Sinopsis belum tersedia.'),
                            'poster_path' => $posterPath ?: $existing->poster_path,
                            'trailer_url' => $row['trailer_url'] ?? $existing->trailer_url,
                            'is_active' => isset($row['is_active']) ? (bool) $row['is_active'] : $existing->is_active,
                            'import_batch_id' => $batchId,
                        ]);

                        $chunkImported++;

                        continue;
                    }
                }

                // New film creation
                $baseSlug = Str::slug($title);
                $slug = $baseSlug;
                $counter = 1;
                while (Film::withTrashed()->where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.(++$counter);
                }

                $matchedPosterPath = $this->matchPoster(
                    $title,
                    $slug,
                    $row['poster_filename'] ?? ($row['matched_poster'] ?? null),
                    $availablePosters
                );

                $posterPath = null;
                if ($matchedPosterPath && file_exists($matchedPosterPath)) {
                    $posterPath = $this->cropAndStorePoster($matchedPosterPath);
                } elseif ($matchedPosterPath && is_string($matchedPosterPath) && Storage::disk('public')->exists($matchedPosterPath)) {
                    $posterPath = $matchedPosterPath;
                }

                Film::create([
                    'title' => $title,
                    'slug' => $slug,
                    'duration_minutes' => $duration,
                    'genre' => $genre,
                    'age_rating' => $row['age_rating'] ?? '13+',
                    'release_year' => $releaseYear,
                    'synopsis' => ! empty($row['synopsis']) ? $row['synopsis'] : 'Sinopsis belum tersedia.',
                    'is_active' => isset($row['is_active']) ? (bool) $row['is_active'] : false, // Default DISEMBUNYIKAN
                    'poster_path' => $posterPath,
                    'trailer_url' => $row['trailer_url'] ?? null,
                    'import_batch_id' => $batchId,
                    'is_sample' => false,
                ]);

                $chunkImported++;
            }

            // Sync stats to ImportBatch
            $batch->imported_count += $chunkImported;
            $batch->skipped_count += $chunkSkipped;
            $batch->failed_count = ($batch->failed_count ?? 0) + $chunkFailed;
            $batch->failed_rows = array_merge((array) ($batch->failed_rows ?? []), $chunkFailedRows);
            $batch->save();

            return [
                'imported' => $chunkImported,
                'skipped' => $chunkSkipped,
                'failed' => $chunkFailed,
                'failed_rows' => $chunkFailedRows,
            ];
        });
    }

    /**
     * Execute the bulk import in batches of 25 rows for shared hosting safety.
     */
    public function executeImport(
        array $rows,
        mixed $arg2 = 'skip',
        mixed $arg3 = null,
        mixed $arg4 = null,
        mixed $arg5 = null,
        array $arg6 = []
    ): array {
        if (in_array(strtolower((string) $arg2), ['skip', 'update'], true)) {
            $strategy = strtolower((string) $arg2);
            $batchId = (string) ($arg3 ?: Str::uuid()->toString());
            $userId = is_int($arg4) ? $arg4 : null;
            $originalFilename = is_string($arg5) ? $arg5 : (is_string($arg4) ? $arg4 : 'import_manual.csv');
            $posters = is_array($arg5) ? $arg5 : (is_array($arg4) ? $arg4 : $arg6);
        } else {
            $strategy = 'skip';
            $batchId = (string) $arg2;
            $originalFilename = is_string($arg3) ? $arg3 : 'import_manual.csv';
            $userId = is_int($arg4) ? $arg4 : null;
            $posters = is_array($arg5) ? $arg5 : (is_array($arg4) ? $arg4 : $arg6);
        }

        // Initialize batch container
        $batch = ImportBatch::updateOrCreate(
            ['id' => $batchId],
            [
                'filename' => $originalFilename ?: 'import_manual.csv',
                'total_rows' => count($rows),
                'imported_count' => 0,
                'skipped_count' => 0,
                'failed_count' => 0,
                'failed_rows' => [],
                'user_id' => $userId,
                'status' => 'processing',
            ]
        );

        $chunks = array_chunk($rows, 25);
        foreach ($chunks as $chunk) {
            $this->executeChunk($chunk, $strategy, $batchId, $userId, $originalFilename, $posters);
        }

        $batch->refresh();
        $batch->update(['status' => 'completed']);

        // Collect unmatched poster names
        $unmatchedNames = [];
        foreach ($posters as $k => $item) {
            $unmatchedNames[] = is_array($item) ? ($item['filename'] ?? $k) : $k;
        }
        $unmatchedNames = array_values(array_unique($unmatchedNames));

        // Cleanup temporary staging folder if exists
        $stagingDir = storage_path("app/tmp_posters/{$batchId}");
        if (is_dir($stagingDir)) {
            $files = glob("{$stagingDir}/*");
            if ($files) {
                foreach ($files as $f) {
                    if (is_file($f)) {
                        @unlink($f);
                    }
                }
            }
            @rmdir($stagingDir);
        }

        return [
            'batch' => $batch,
            'imported_count' => $batch->imported_count,
            'skipped_count' => $batch->skipped_count,
            'failed_count' => $batch->failed_count,
            'unmatched_posters' => $unmatchedNames,
        ];
    }

    /**
     * Export all active and inactive films in canonical CSV format with injection protection.
     */
    public function exportAllFilmsCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="katalog_film_synemax_'.date('Ymd_His').'.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Indonesian Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'judul',
                'genre',
                'tahun',
                'durasi',
                'klasifikasi',
                'sinopsis',
                'poster',
                'trailer',
                'tampil',
            ]);

            Film::query()
                ->orderBy('title')
                ->chunk(100, function ($films) use ($handle) {
                    foreach ($films as $film) {
                        fputcsv($handle, [
                            $this->sanitizeCsvCell($film->title),
                            $this->sanitizeCsvCell($film->genre),
                            $film->release_year ?: ($film->release_date ? $film->release_date->format('Y') : ''),
                            $film->duration_minutes,
                            $this->sanitizeCsvCell($film->age_rating),
                            $this->sanitizeCsvCell($film->synopsis),
                            $film->poster_path ? basename($film->poster_path) : '',
                            $this->sanitizeCsvCell($film->trailer_url ?? ''),
                            $film->is_active ? 'ya' : 'tidak',
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Rollback (undo) an import batch by soft-deleting all imported films.
     */
    public function rollbackBatch(string|int $batchId): int
    {
        $batch = is_numeric($batchId)
            ? ImportBatch::find($batchId)
            : ImportBatch::where('batch_uuid', (string) $batchId)->orWhere('id', (string) $batchId)->first();

        $uuid = $batch ? $batch->batch_uuid : (string) $batchId;
        $id = $batch ? (string) $batch->id : (string) $batchId;

        return DB::transaction(function () use ($batch, $uuid, $id) {
            $films = Film::where('import_batch_id', $uuid)
                ->orWhere('import_batch_id', $id)
                ->get();
            $count = $films->count();

            foreach ($films as $film) {
                $film->delete(); // Soft delete per requirement B4
            }

            if ($batch) {
                $batch->update(['status' => 'cancelled']);
            }

            return $count;
        });
    }
}
