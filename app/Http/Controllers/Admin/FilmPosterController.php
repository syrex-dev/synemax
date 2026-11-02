<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Film;
use App\Services\FilmBulkImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FilmPosterController extends Controller
{
    private const UNMATCHED_DIR = 'posters/unmatched';

    /**
     * Show dedicated bulk posters management page.
     */
    public function index(): View
    {
        $filmsWithoutPoster = Film::query()
            ->where(function ($q) {
                $q->whereNull('poster_path')->orWhere('poster_path', '');
            })
            ->orderBy('title')
            ->get();

        // Scan unmatched posters from public storage
        $unmatchedFiles = Storage::disk('public')->files(self::UNMATCHED_DIR);
        $unmatchedPosters = [];

        foreach ($unmatchedFiles as $filePath) {
            $baseName = basename($filePath);
            $fullPath = Storage::disk('public')->path($filePath);
            $size = file_exists($fullPath) ? filesize($fullPath) : 0;
            $imageInfo = file_exists($fullPath) ? @getimagesize($fullPath) : false;

            $isSmall = $imageInfo && ($imageInfo[0] < 300);

            $unmatchedPosters[] = [
                'filename' => $baseName,
                'path' => $filePath,
                'url' => Storage::disk('public')->url($filePath),
                'size' => $size,
                'width' => $imageInfo ? $imageInfo[0] : 0,
                'height' => $imageInfo ? $imageInfo[1] : 0,
                'is_small' => $isSmall,
                'warning' => $isSmall ? 'Lebar gambar kurang dari 300 piksel, poster mungkin terlihat buram.' : null,
            ];
        }

        // Options for searchable film picker (<x-ui.select>)
        $filmOptions = [];
        foreach ($filmsWithoutPoster as $film) {
            $year = $film->release_year ?: ($film->release_date ? $film->release_date->format('Y') : '');
            $label = $film->title.($year ? " ({$year})" : '').' - '.$film->genre;
            $filmOptions[$film->id] = $label;
        }

        return view('admin.films.posters', [
            'filmsWithoutPoster' => $filmsWithoutPoster,
            'unmatchedPosters' => $unmatchedPosters,
            'filmOptions' => $filmOptions,
        ]);
    }

    /**
     * Process a single poster image file: validates, crops, compresses, and matches against unassigned films.
     *
     * @return array{
     *     success: bool,
     *     status: 'matched'|'unmatched'|'failed',
     *     filename: string,
     *     film_id: ?int,
     *     film_title: ?string,
     *     poster_url: ?string,
     *     warning: ?string,
     *     message: string
     * }
     */
    public function processPosterFile(string $sourcePath, string $originalFilename, FilmBulkImporter $importer, ?string $ocrText = null): array
    {
        if (! file_exists($sourcePath)) {
            return [
                'success' => false,
                'status' => 'failed',
                'filename' => $originalFilename,
                'film_id' => null,
                'film_title' => null,
                'poster_url' => null,
                'warning' => null,
                'message' => 'Berkas gambar tidak ditemukan di sistem.',
            ];
        }

        // Genuine image validation
        $imageInfo = @getimagesize($sourcePath);
        if ($imageInfo === false || ! in_array($imageInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return [
                'success' => false,
                'status' => 'failed',
                'filename' => $originalFilename,
                'film_id' => null,
                'film_title' => null,
                'poster_url' => null,
                'warning' => null,
                'message' => 'Format berkas tidak valid. Harap gunakan format JPG, PNG, atau WebP.',
            ];
        }

        $warning = ($imageInfo[0] < 300) ? 'Lebar gambar kurang dari 300px (kualitas resolusi rendah).' : null;

        // Crop & store 600x900 WebP
        try {
            $storedPath = $importer->cropAndStorePoster($sourcePath);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 'failed',
                'filename' => $originalFilename,
                'film_id' => null,
                'film_title' => null,
                'poster_url' => null,
                'warning' => null,
                'message' => 'Gagal memproses gambar: '.$e->getMessage(),
            ];
        }

        $origNoExt = strtolower(pathinfo($originalFilename, PATHINFO_FILENAME));
        $origSlug = Str::slug($origNoExt);

        // Fetch films currently without poster
        $filmsWithoutPoster = Film::query()
            ->where(function ($q) {
                $q->whereNull('poster_path')->orWhere('poster_path', '');
            })
            ->orderBy('title')
            ->get();

        $matchedFilm = null;
        $matchMethod = null;

        // 1. First, check filename matching
        foreach ($filmsWithoutPoster as $film) {
            $filmSlug = $film->slug;
            $filmTitleSlug = Str::slug($film->title);

            $isExact = ($origNoExt === strtolower($film->title))
                || ($origSlug === $filmSlug)
                || ($origSlug === $filmTitleSlug);

            $isSubstring = false;
            if (! $isExact && strlen($filmSlug) >= 4 && strlen($origSlug) >= 4) {
                if (str_contains($origSlug, $filmSlug) || str_contains($filmSlug, $origSlug)) {
                    $isSubstring = true;
                }
            }

            if ($isExact || $isSubstring) {
                $matchedFilm = $film;
                $matchMethod = 'nama berkas';
                break;
            }
        }

        // 2. If no filename match, attempt OCR text matching against film titles
        if ($matchedFilm === null && ! empty($ocrText)) {
            $ocrMatch = $this->matchFilmFromOcr($ocrText, $filmsWithoutPoster);
            if ($ocrMatch) {
                $matchedFilm = $ocrMatch['film'];
                $matchMethod = 'deteksi teks judul di foto (OCR)';
            }
        }

        if ($matchedFilm) {
            $matchedFilm->update(['poster_path' => $storedPath, 'is_sample' => false]);

            $msg = $matchMethod
                ? "Berhasil dicocokkan otomatis ({$matchMethod}) ke film \"{$matchedFilm->title}\"."
                : "Berhasil dicocokkan otomatis ke film \"{$matchedFilm->title}\".";

            return [
                'success' => true,
                'status' => 'matched',
                'match_method' => $matchMethod,
                'filename' => $originalFilename,
                'film_id' => $matchedFilm->id,
                'film_title' => $matchedFilm->title,
                'poster_url' => Storage::disk('public')->url($storedPath),
                'warning' => $warning,
                'message' => $msg,
            ];
        }

        // Unmatched -> move to unmatched storage directory
        $destUnmatched = self::UNMATCHED_DIR.'/'.Str::slug($origNoExt).'_'.Str::random(6).'.'.pathinfo($storedPath, PATHINFO_EXTENSION);
        Storage::disk('public')->move($storedPath, $destUnmatched);

        return [
            'success' => true,
            'status' => 'unmatched',
            'filename' => $originalFilename,
            'film_id' => null,
            'film_title' => null,
            'poster_url' => Storage::disk('public')->url($destUnmatched),
            'warning' => $warning,
            'message' => 'Tidak ditemukan film yang cocok. Disimpan ke antrean poster belum terpasang.',
        ];
    }

    /**
     * AJAX endpoint: Process a single uploaded image with optional OCR text.
     */
    public function processSingle(Request $request, FilmBulkImporter $importer): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|max:5120',
            'ocr_text' => 'nullable|string',
        ], [
            'image.required' => 'Pilih berkas gambar untuk diproses.',
            'image.max' => 'Ukuran berkas gambar maksimal 5 MB.',
        ]);

        $file = $request->file('image');
        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            return response()->json([
                'success' => false,
                'status' => 'failed',
                'filename' => $file?->getClientOriginalName() ?? 'unknown',
                'message' => 'Berkas unggahan tidak valid atau gagal diunggah.',
            ], 422);
        }

        $originalName = $file->getClientOriginalName();
        $tempPath = $file->getRealPath();
        $ocrText = $request->input('ocr_text');

        $result = $this->processPosterFile($tempPath, $originalName, $importer, $ocrText);

        return response()->json($result);
    }

    /**
     * AJAX endpoint: Upload and extract ZIP archive into temporary batch folder.
     */
    public function uploadZip(Request $request, FilmBulkImporter $importer): JsonResponse
    {
        $request->validate([
            'zip_file' => 'required|file|mimes:zip|max:51200',
        ], [
            'zip_file.required' => 'Pilih berkas ZIP untuk diunggah.',
            'zip_file.mimes' => 'Berkas harus berformat ZIP.',
            'zip_file.max' => 'Ukuran berkas ZIP maksimal 50 MB.',
        ]);

        $zipFile = $request->file('zip_file');
        if (! $zipFile instanceof UploadedFile || ! $zipFile->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Berkas ZIP tidak valid.',
            ], 422);
        }

        try {
            $batchId = Str::uuid()->toString();
            $extracted = $importer->extractPosterZip($zipFile, $batchId);

            $files = [];
            $seen = [];
            foreach ($extracted as $key => $item) {
                if (str_contains($key, '.') && ! isset($seen[$item['staged_path']])) {
                    $seen[$item['staged_path']] = true;
                    $files[] = [
                        'filename' => $item['filename'],
                        'staged_path' => $item['staged_path'],
                        'size' => $item['size'],
                    ];
                }
            }

            if (empty($files)) {
                $this->deleteStagingDirectory(storage_path("app/tmp_posters/{$batchId}"));

                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ditemukan berkas gambar yang valid di dalam arsip ZIP.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'batch_id' => $batchId,
                'total' => count($files),
                'files' => $files,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengekstrak berkas ZIP: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * AJAX endpoint: Process a single staged poster from an extracted ZIP batch.
     */
    public function processBatchItem(Request $request, FilmBulkImporter $importer): JsonResponse
    {
        $validated = $request->validate([
            'batch_id' => 'required|string',
            'filename' => 'required|string',
            'staged_path' => 'required|string',
            'ocr_text' => 'nullable|string',
        ]);

        $batchId = $validated['batch_id'];
        $stagedPath = $validated['staged_path'];
        $filename = $validated['filename'];
        $ocrText = $validated['ocr_text'] ?? null;

        // Security check: ensure path is inside temporary batch folder
        $allowedPrefix = realpath(storage_path("app/tmp_posters/{$batchId}"));
        $realPath = realpath($stagedPath);

        if (! $allowedPrefix || ! $realPath || ! str_starts_with($realPath, $allowedPrefix)) {
            return response()->json([
                'success' => false,
                'status' => 'failed',
                'filename' => $filename,
                'message' => 'Akses berkas tidak diizinkan atau batch tidak valid.',
            ], 403);
        }

        $result = $this->processPosterFile($realPath, $filename, $importer, $ocrText);

        // Delete the staged file after processing
        @unlink($realPath);

        return response()->json($result);
    }

    /**
     * AJAX endpoint: Cleanup temporary staging folder for a batch.
     */
    public function cleanupBatch(Request $request): JsonResponse
    {
        $batchId = $request->input('batch_id');
        if ($batchId && is_string($batchId) && preg_match('/^[a-zA-Z0-9\-_]+$/', $batchId)) {
            $stagingDir = storage_path("app/tmp_posters/{$batchId}");
            $this->deleteStagingDirectory($stagingDir);
        }

        return response()->json(['success' => true]);
    }

    private function deleteStagingDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = glob("{$dir}/*");
        if ($files) {
            foreach ($files as $f) {
                @unlink($f);
            }
        }
        @rmdir($dir);
    }

    /**
     * Handle bulk upload of multiple image files or ZIP archive (standard form submit & backwards compatibility).
     */
    public function upload(Request $request, FilmBulkImporter $importer): RedirectResponse|JsonResponse
    {
        $request->validate([
            'images.*' => 'nullable|file|max:5120', // max 5MB per image
            'posters.*' => 'nullable|file|max:5120',
            'zip_file' => 'nullable|file|mimes:zip|max:51200', // max 50MB
        ], [
            'images.*.max' => 'Ukuran berkas gambar maksimal 5 MB per gambar.',
            'posters.*.max' => 'Ukuran berkas gambar maksimal 5 MB per gambar.',
            'zip_file.mimes' => 'Berkas harus berformat ZIP.',
            'zip_file.max' => 'Ukuran berkas ZIP maksimal 50 MB.',
        ]);

        $uploadedImages = $request->file('images', []);
        if (empty($uploadedImages)) {
            $uploadedImages = $request->file('posters', []);
        }
        $zipFile = $request->file('zip_file');

        if (empty($uploadedImages) && $zipFile === null) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Pilih minimal satu berkas gambar atau berkas ZIP untuk diunggah.'], 422);
            }

            return back()->with('error', 'Pilih minimal satu berkas gambar atau berkas ZIP untuk diunggah.');
        }

        $allCandidatePaths = []; // Keyed by lowercase original filename
        $stagingDir = storage_path('app/tmp_bulk_posters_'.Str::uuid());
        if (! is_dir($stagingDir)) {
            mkdir($stagingDir, 0755, true);
        }

        // 1. Process individual uploaded images
        if (is_array($uploadedImages)) {
            foreach ($uploadedImages as $img) {
                if ($img instanceof UploadedFile && $img->isValid()) {
                    $originalName = $img->getClientOriginalName();
                    $destPath = $stagingDir.'/'.Str::slug(pathinfo($originalName, PATHINFO_FILENAME)).'_'.time().'.'.$img->getClientOriginalExtension();
                    $img->move($stagingDir, basename($destPath));
                    $allCandidatePaths[strtolower($originalName)] = [
                        'filename' => $originalName,
                        'staged_path' => $destPath,
                    ];
                }
            }
        }

        // 2. Process ZIP archive if provided
        if ($zipFile instanceof UploadedFile && $zipFile->isValid()) {
            try {
                $batchId = Str::uuid()->toString();
                $extracted = $importer->extractPosterZip($zipFile, $batchId);
                foreach ($extracted as $k => $item) {
                    if (str_contains($k, '.')) {
                        $allCandidatePaths[strtolower($item['filename'])] = $item;
                    }
                }
            } catch (\Exception $e) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Gagal memproses berkas ZIP: '.$e->getMessage()], 422);
                }

                return back()->with('error', 'Gagal memproses berkas ZIP: '.$e->getMessage());
            }
        }

        if (empty($allCandidatePaths)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Tidak ditemukan berkas gambar yang valid pada data yang diunggah.'], 422);
            }

            return back()->with('error', 'Tidak ditemukan berkas gambar yang valid pada data yang diunggah.');
        }

        $matchedCount = 0;
        $unmatchedCount = 0;
        $warningsCount = 0;
        $failedCount = 0;

        foreach ($allCandidatePaths as $candidate) {
            $sourcePath = $candidate['staged_path'];
            $origFilename = $candidate['filename'];

            $res = $this->processPosterFile($sourcePath, $origFilename, $importer);

            if ($res['status'] === 'matched') {
                $matchedCount++;
            } elseif ($res['status'] === 'unmatched') {
                $unmatchedCount++;
            } else {
                $failedCount++;
            }

            if (! empty($res['warning'])) {
                $warningsCount++;
            }

            @unlink($sourcePath);
        }

        // Cleanup temporary staging folder
        $this->deleteStagingDirectory($stagingDir);

        $msg = "Proses poster selesai: {$matchedCount} poster berhasil dicocokkan otomatis.";
        if ($unmatchedCount > 0) {
            $msg .= " {$unmatchedCount} poster belum terpasang dan dapat dipasangkan secara manual.";
        }
        if ($warningsCount > 0) {
            $msg .= " Catatan: {$warningsCount} gambar memiliki lebar di bawah 300px (peringatan kualitas).";
        }
        if ($failedCount > 0) {
            $msg .= " {$failedCount} berkas gagal diproses.";
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'matched' => $matchedCount,
                'unmatched' => $unmatchedCount,
                'failed' => $failedCount,
                'warnings' => $warningsCount,
                'message' => $msg,
            ]);
        }

        return redirect()->route('admin.films.posters.index')->with('status', $msg);
    }

    /**
     * Manually assign an unmatched poster to a selected film.
     */
    public function assign(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'film_id' => 'required|exists:films,id',
            'poster_filename' => 'required|string',
        ], [
            'film_id.required' => 'Pilih film yang ingin dipasangkan.',
            'film_id.exists' => 'Film yang dipilih tidak ditemukan.',
            'poster_filename.required' => 'Poster tidak valid.',
        ]);

        $film = Film::findOrFail($validated['film_id']);
        $filename = basename($validated['poster_filename']);
        $sourcePath = self::UNMATCHED_DIR.'/'.$filename;

        if (! Storage::disk('public')->exists($sourcePath)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Berkas poster tidak ditemukan di penyimpanan.'], 404);
            }

            return back()->with('error', 'Berkas poster tidak ditemukan di penyimpanan.');
        }

        // Move to permanent posters folder
        $permanentPath = 'posters/'.Str::uuid().'.'.pathinfo($filename, PATHINFO_EXTENSION);
        Storage::disk('public')->move($sourcePath, $permanentPath);

        $film->update(['poster_path' => $permanentPath, 'is_sample' => false]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'film_id' => $film->id,
                'film_title' => $film->title,
                'poster_filename' => $filename,
                'poster_url' => Storage::disk('public')->url($permanentPath),
                'message' => "Poster berhasil dipasangkan ke film \"{$film->title}\".",
            ]);
        }

        return redirect()->route('admin.films.posters.index')
            ->with('status', "Poster berhasil dipasangkan ke film \"{$film->title}\".");
    }

    /**
     * Batch assign multiple unmatched posters to films at once.
     */
    public function assignBatch(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'assignments' => 'required|array|min:1',
            'assignments.*.film_id' => 'required|exists:films,id',
            'assignments.*.poster_filename' => 'required|string',
        ], [
            'assignments.required' => 'Pilih minimal satu poster dan film untuk dipasangkan.',
            'assignments.min' => 'Pilih minimal satu poster dan film untuk dipasangkan.',
            'assignments.*.film_id.required' => 'Pilih film untuk poster.',
            'assignments.*.film_id.exists' => 'Film yang dipilih tidak ditemukan.',
        ]);

        $assignedCount = 0;
        $failedCount = 0;
        $assignedItems = [];

        foreach ($validated['assignments'] as $item) {
            $filmId = $item['film_id'];
            $filename = basename($item['poster_filename']);
            $sourcePath = self::UNMATCHED_DIR.'/'.$filename;

            if (! Storage::disk('public')->exists($sourcePath)) {
                $failedCount++;

                continue;
            }

            $film = Film::find($filmId);
            if (! $film) {
                $failedCount++;

                continue;
            }

            $permanentPath = 'posters/'.Str::uuid().'.'.pathinfo($filename, PATHINFO_EXTENSION);
            Storage::disk('public')->move($sourcePath, $permanentPath);

            $film->update(['poster_path' => $permanentPath, 'is_sample' => false]);
            $assignedCount++;

            $assignedItems[] = [
                'filename' => $filename,
                'film_id' => $film->id,
                'film_title' => $film->title,
            ];
        }

        $msg = "{$assignedCount} poster berhasil dipasangkan ke film.";
        if ($failedCount > 0) {
            $msg .= " ({$failedCount} poster gagal).";
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'assigned_count' => $assignedCount,
                'failed_count' => $failedCount,
                'items' => $assignedItems,
                'message' => $msg,
            ]);
        }

        return redirect()->route('admin.films.posters.index')->with('status', $msg);
    }

    /**
     * AJAX endpoint: Find matching film for an OCR text string without saving yet.
     */
    public function matchOcr(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ocr_text' => 'required|string',
            'filename' => 'nullable|string',
        ]);

        $ocrText = $validated['ocr_text'];
        $cleanPreview = Str::limit(trim((string) preg_replace('/\s+/', ' ', $ocrText)), 80, '...');

        $match = $this->matchFilmFromOcr($ocrText);

        if ($match) {
            $film = $match['film'];

            return response()->json([
                'success' => true,
                'matched' => true,
                'film_id' => $film->id,
                'film_title' => $film->title,
                'confidence' => $match['confidence'],
                'match_type' => $match['match_type'],
                'preview_ocr' => $cleanPreview,
            ]);
        }

        return response()->json([
            'success' => true,
            'matched' => false,
            'preview_ocr' => $cleanPreview,
            'message' => ! empty($cleanPreview)
                ? "Tidak ditemukan film katalog yang cocok dengan teks pada gambar ini (Teks terbaca: \"{$cleanPreview}\")."
                : 'Tidak ditemukan teks judul yang terbaca pada gambar ini.',
        ]);
    }

    /**
     * Matches an OCR text string against candidate films using multi-strategy fuzzy matching:
     * 1. Direct phrase & title variant matching (e.g. core title without colon subtitles, parentheses, dashes)
     * 2. Unspaced letter-spaced title matching (for wide kerning on movie poster titles: "S E K A W A N  L I M O")
     * 3. Leet/stylized typography normalization ('0'->'o', '1'->'i', '3'->'e', '4'->'a', '5'->'s', '8'->'b')
     * 4. Roman numeral & sequel normalization ('ii'->'2', 'ke-2'->'2')
     * 5. Distinct multi-word consecutive phrase / n-gram containment
     * 6. Fuzzy token overlap with Levenshtein tolerance for stylized OCR typography
     * 7. Sliding window string similarity for spaced-out poster titles
     *
     * @param  Collection<int, Film>|null  $candidateFilms
     * @return array{
     *     film: Film,
     *     confidence: float,
     *     match_type: string,
     *     preview_ocr: string
     * }|null
     */
    public function matchFilmFromOcr(string $ocrText, ?Collection $candidateFilms = null): ?array
    {
        if (empty(trim($ocrText))) {
            return null;
        }

        $rawClean = $this->normalizeOcrText($ocrText);
        $ocrNorm = $this->normalizeRomanNumerals($rawClean);
        $ocrLeet = $this->normalizeLeetText($ocrNorm);
        $ocrNoSpaces = (string) preg_replace('/\s+/u', '', $ocrNorm);
        $ocrLeetNoSpaces = (string) preg_replace('/\s+/u', '', $ocrLeet);
        $ocrWords = array_values(array_filter(explode(' ', $ocrNorm), fn (string $w): bool => mb_strlen($w) >= 2));
        $previewOcr = Str::limit(trim((string) preg_replace('/\s+/', ' ', $rawClean)), 80, '...');

        $candidateFilms ??= Film::query()
            ->where(function ($q): void {
                $q->whereNull('poster_path')->orWhere('poster_path', '');
            })
            ->orderBy('title')
            ->get();

        $bestMatch = null;
        $highestScore = 0.0;

        foreach ($candidateFilms as $film) {
            $rawTitleClean = $this->normalizeOcrText($film->title);
            $titleNorm = $this->normalizeRomanNumerals($rawTitleClean);
            $titleLeet = $this->normalizeLeetText($titleNorm);
            $titleNoSpaces = (string) preg_replace('/\s+/u', '', $titleNorm);
            $titleLeetNoSpaces = (string) preg_replace('/\s+/u', '', $titleLeet);

            if (mb_strlen($titleNoSpaces) < 2) {
                continue;
            }

            // Generate title variations (e.g. without "(2024)", without subtitles after ":")
            $titleVariants = [$titleNorm];
            if (str_contains($film->title, '(')) {
                $withoutParen = trim((string) preg_replace('/\s*\([^)]*\)/u', '', $film->title));
                if (! empty($withoutParen)) {
                    $titleVariants[] = $this->normalizeRomanNumerals($this->normalizeOcrText($withoutParen));
                }
            }
            if (str_contains($film->title, ':')) {
                $beforeColon = trim(explode(':', $film->title)[0]);
                if (! empty($beforeColon)) {
                    $titleVariants[] = $this->normalizeRomanNumerals($this->normalizeOcrText($beforeColon));
                }
            }
            if (str_contains($film->title, ' - ')) {
                $beforeDash = trim(explode(' - ', $film->title)[0]);
                if (! empty($beforeDash)) {
                    $titleVariants[] = $this->normalizeRomanNumerals($this->normalizeOcrText($beforeDash));
                }
            }
            $titleVariants = array_values(array_unique(array_filter($titleVariants)));

            // 1. Exact phrase & spaced matching for title and variants
            foreach ($titleVariants as $v) {
                $vNoSpaces = (string) preg_replace('/\s+/u', '', $v);
                if (mb_strlen($v) >= 3 && str_contains($ocrNorm, $v)) {
                    return [
                        'film' => $film,
                        'confidence' => 1.0,
                        'match_type' => 'exact_phrase',
                        'preview_ocr' => $previewOcr,
                    ];
                }
                if (mb_strlen($vNoSpaces) >= 4 && (str_contains($ocrNoSpaces, $vNoSpaces) || str_contains($ocrLeetNoSpaces, $this->normalizeLeetText($vNoSpaces)))) {
                    return [
                        'film' => $film,
                        'confidence' => 0.98,
                        'match_type' => 'spaced_title_match',
                        'preview_ocr' => $previewOcr,
                    ];
                }
            }

            // 2. Sub-phrase match: if title is multi-word, check prominent sub-phrases (>= 2 words, >= 6 chars)
            $titleWords = array_values(array_filter(explode(' ', $titleNorm), fn (string $w): bool => mb_strlen($w) >= 2));
            $titleWordCount = count($titleWords);

            if ($titleWordCount >= 2) {
                for ($len = min($titleWordCount, 4); $len >= 2; $len--) {
                    for ($start = 0; $start <= $titleWordCount - $len; $start++) {
                        $subSlice = array_slice($titleWords, $start, $len);
                        $subPhrase = implode(' ', $subSlice);
                        $subPhraseNoSpaces = implode('', $subSlice);

                        if (mb_strlen($subPhraseNoSpaces) >= 6) {
                            if (str_contains($ocrNorm, $subPhrase) || str_contains($ocrNoSpaces, $subPhraseNoSpaces) || str_contains($ocrLeetNoSpaces, $this->normalizeLeetText($subPhraseNoSpaces))) {
                                $subScore = 0.88 + (0.05 * ($len / $titleWordCount));
                                if ($subScore > $highestScore) {
                                    $highestScore = $subScore;
                                    $bestMatch = [
                                        'film' => $film,
                                        'confidence' => round($subScore, 2),
                                        'match_type' => 'prominent_subphrase',
                                        'preview_ocr' => $previewOcr,
                                    ];
                                }
                            }
                        }
                    }
                }
            }

            // 3. Fuzzy token matching (tolerant of stylized OCR typos: "Sekavan" vs "Sekawan", "Lirno" vs "Limo")
            if (! empty($titleWords)) {
                $totalWeight = 0.0;
                $matchedWeight = 0.0;

                foreach ($titleWords as $tw) {
                    $twLen = mb_strlen($tw);
                    if ($twLen < 2) {
                        continue;
                    }

                    $isStopword = in_array($tw, ['di', 'ke', 'dari', 'yang', 'dan', 'atau', 'ini', 'itu', 'pada', 'untuk', 'si', 'sang', 'the', 'of', 'in', 'and'], true);
                    $weight = $isStopword ? 0.5 : ($twLen >= 5 ? 1.5 : 1.0);
                    $totalWeight += $weight;

                    $bestWordHit = 0.0;

                    if (in_array($tw, $ocrWords, true)) {
                        $bestWordHit = 1.0;
                    } else {
                        foreach ($ocrWords as $ow) {
                            $owLen = mb_strlen($ow);
                            if (abs($owLen - $twLen) > 2) {
                                continue;
                            }

                            $dist = levenshtein($tw, $ow);
                            $maxAllowedDist = $twLen <= 4 ? 1 : ($twLen <= 7 ? 2 : 3);

                            if ($dist <= $maxAllowedDist) {
                                $sim = 1.0 - ($dist / max($twLen, $owLen));
                                if ($sim > $bestWordHit) {
                                    $bestWordHit = $sim;
                                }
                            }
                        }

                        if ($bestWordHit < 0.8 && $twLen >= 5 && (str_contains($ocrNoSpaces, $tw) || str_contains($ocrLeetNoSpaces, $this->normalizeLeetText($tw)))) {
                            $bestWordHit = 0.95;
                        }
                    }

                    $matchedWeight += ($bestWordHit * $weight);
                }

                if ($totalWeight > 0) {
                    $tokenScore = $matchedWeight / $totalWeight;
                    $threshold = $titleWordCount === 1 ? 0.75 : ($titleWordCount === 2 ? 0.60 : 0.50);

                    if ($tokenScore >= $threshold && $tokenScore > $highestScore) {
                        $highestScore = $tokenScore;
                        $bestMatch = [
                            'film' => $film,
                            'confidence' => round($tokenScore, 2),
                            'match_type' => 'fuzzy_token_overlap',
                            'preview_ocr' => $previewOcr,
                        ];
                    }
                }
            }

            // 4. Sliding window string similarity on unspaced string
            if (mb_strlen($titleNoSpaces) >= 5 && mb_strlen($ocrNoSpaces) >= mb_strlen($titleNoSpaces)) {
                $tLen = mb_strlen($titleNoSpaces);
                $ocrLen = mb_strlen($ocrNoSpaces);

                for ($i = 0; $i <= $ocrLen - $tLen; $i++) {
                    $chunk = mb_substr($ocrNoSpaces, $i, $tLen);
                    similar_text($titleNoSpaces, $chunk, $pct);
                    $simScore = $pct / 100.0;

                    if ($simScore >= 0.78 && $simScore > $highestScore) {
                        $highestScore = $simScore;
                        $bestMatch = [
                            'film' => $film,
                            'confidence' => round($simScore, 2),
                            'match_type' => 'sliding_similarity',
                            'preview_ocr' => $previewOcr,
                        ];
                    }
                }
            }
        }

        return $bestMatch;
    }

    private function normalizeOcrText(string $text): string
    {
        $clean = mb_strtolower($text, 'UTF-8');
        $clean = (string) preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $clean);

        return trim((string) preg_replace('/\s+/u', ' ', $clean));
    }

    private function normalizeLeetText(string $text): string
    {
        $leetMap = [
            '0' => 'o',
            '1' => 'i',
            '3' => 'e',
            '4' => 'a',
            '5' => 's',
            '8' => 'b',
            '|' => 'i',
            '!' => 'i',
        ];

        return strtr($text, $leetMap);
    }

    private function normalizeRomanNumerals(string $text): string
    {
        $patterns = [
            '/\bke\s*[-–]?\s*([0-9]+)\b/u' => '$1',
            '/\bpart\s*([0-9]+)\b/u' => '$1',
            '/\bviii\b/u' => '8',
            '/\bvii\b/u' => '7',
            '/\bvi\b/u' => '6',
            '/\biv\b/u' => '4',
            '/\bv\b/u' => '5',
            '/\biii\b/u' => '3',
            '/\bii\b/u' => '2',
        ];

        return (string) preg_replace(array_keys($patterns), array_values($patterns), $text);
    }

    /**
     * Delete an unmatched poster.
     */
    public function destroy(Request $request, string $filename): RedirectResponse|JsonResponse
    {
        $cleanName = basename($filename);
        $path = self::UNMATCHED_DIR.'/'.$cleanName;

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'filename' => $cleanName,
                'message' => 'Poster belum terpasang berhasil dihapus.',
            ]);
        }

        return redirect()->route('admin.films.posters.index')
            ->with('status', 'Poster belum terpasang berhasil dihapus.');
    }
}
