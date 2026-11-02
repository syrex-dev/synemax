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

            $previousPosterPath = $film->poster_path;
            $permanentPath = 'posters/'.Str::uuid().'.'.pathinfo($filename, PATHINFO_EXTENSION);
            Storage::disk('public')->move($sourcePath, $permanentPath);

            $film->update(['poster_path' => $permanentPath, 'is_sample' => false]);
            $assignedCount++;

            $assignedItems[] = [
                'filename' => $filename,
                'poster_filename' => $filename,
                'film_id' => $film->id,
                'film_title' => $film->title,
                'permanent_path' => $permanentPath,
                'previous_poster_path' => $previousPosterPath,
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
     * Undo a batch assignment (restoring previous poster state).
     */
    public function undoBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reverts' => 'required|array|min:1',
            'reverts.*.film_id' => 'required|exists:films,id',
            'reverts.*.poster_filename' => 'nullable|string',
            'reverts.*.filename' => 'nullable|string',
            'reverts.*.permanent_path' => 'nullable|string',
            'reverts.*.previous_poster_path' => 'nullable|string',
        ]);

        $revertedCount = 0;

        foreach ($validated['reverts'] as $item) {
            $film = Film::find($item['film_id']);
            if (! $film) {
                continue;
            }

            $rawFilename = $item['poster_filename'] ?? $item['filename'] ?? '';
            if (empty($rawFilename)) {
                continue;
            }
            $filename = basename($rawFilename);
            $currentPermanent = $item['permanent_path'] ?? $film->poster_path;
            $previousPath = $item['previous_poster_path'] ?? null;

            if (! empty($currentPermanent) && Storage::disk('public')->exists($currentPermanent)) {
                $unmatchedDest = self::UNMATCHED_DIR.'/'.$filename;
                if (Storage::disk('public')->exists($unmatchedDest)) {
                    $unmatchedDest = self::UNMATCHED_DIR.'/revert_'.Str::random(4).'_'.$filename;
                }
                Storage::disk('public')->move($currentPermanent, $unmatchedDest);
            }

            $film->update([
                'poster_path' => $previousPath,
                'is_sample' => false,
            ]);

            $revertedCount++;
        }

        return response()->json([
            'success' => true,
            'reverted_count' => $revertedCount,
            'message' => "Pemasangan {$revertedCount} poster berhasil diurungkan. Poster dikembalikan ke antrean.",
        ]);
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
            $needsReview = $match['needs_review'] ?? false;
            $isAutoMatched = ! $needsReview;

            return response()->json([
                'success' => true,
                'matched' => $isAutoMatched,
                'needs_review' => $needsReview,
                'film_id' => $isAutoMatched ? $film->id : null,
                'film_title' => $isAutoMatched ? $film->title : null,
                'suggested_film_id' => $film->id,
                'suggested_film_title' => $film->title,
                'confidence' => $match['confidence'],
                'margin' => $match['margin'] ?? 0.0,
                'match_type' => $match['match_type'],
                'preview_ocr' => $cleanPreview,
                'suggestions' => $match['suggestions'] ?? [],
                'message' => $needsReview
                    ? "Teks terbaca pada foto (\"{$cleanPreview}\"), namun memerlukan tinjauan manual (skor {$match['confidence']}, margin {$match['margin']}). Silakan periksa saran di bawah."
                    : "Berhasil dicocokkan otomatis ke film \"{$film->title}\" (keyakinan {$match['confidence']}).",
            ]);
        }

        return response()->json([
            'success' => true,
            'matched' => false,
            'needs_review' => false,
            'preview_ocr' => $cleanPreview,
            'suggestions' => [],
            'message' => ! empty($cleanPreview)
                ? "Tidak ditemukan film katalog yang cocok dengan teks pada gambar ini (Teks terbaca: \"{$cleanPreview}\")."
                : 'Tidak ditemukan teks judul yang terbaca pada gambar ini.',
        ]);
    }

    /**
     * Matches an OCR text string against candidate films using multi-strategy fuzzy matching:
     * 1. Roman numeral normalization ("II" -> "2", etc.)
     * 2. Sequel isolation guard (strictly prevents "Danur" matching "Danur 2" or vice-versa)
     * 3. Subphrase guard (prevents "Mati Rasa" picking "Perayaan Mati Rasa" over exact "Mati Rasa")
     * 4. Kerning & letter-spaced matching ("S E K A W A N  L I M O")
     * 5. Leet typography normalization ('0'->'o', '1'->'i', '3'->'e', '4'->'a', '5'->'s', '8'->'b')
     * 6. Fuzzy token overlap with Levenshtein tolerance
     * 7. Safety margin: requires score >= 0.85 AND margin >= 0.15 over 2nd candidate; otherwise returns needs_review with top 3 suggestions
     *
     * @param  Collection<int, Film>|null  $candidateFilms
     * @return array{
     *     film: Film,
     *     confidence: float,
     *     match_type: string,
     *     preview_ocr: string,
     *     margin: float,
     *     is_unique: bool,
     *     needs_review: bool,
     *     suggestions: array<int, array{id: int, title: string, score: float}>
     * }|null
     */
    public function matchFilmFromOcr(string $ocrText, ?Collection $candidateFilms = null): ?array
    {
        if (empty(trim($ocrText))) {
            return null;
        }

        $rawClean = $this->normalizeOcrText($ocrText);
        $ocrNorm = $this->normalizeLeetText($this->normalizeRomanNumerals($rawClean));
        $ocrLeet = $this->normalizeLeetText($ocrNorm);
        $ocrNoSpaces = (string) preg_replace('/\s+/u', '', $ocrNorm);
        $ocrLeetNoSpaces = (string) preg_replace('/\s+/u', '', $ocrLeet);
        $ocrWords = array_values(array_filter(explode(' ', $ocrNorm), fn (string $w): bool => mb_strlen($w) >= 2));
        $ocrSequel = $this->extractSequelNumber($ocrNorm);
        $previewOcr = Str::limit(trim((string) preg_replace('/\s+/', ' ', $rawClean)), 80, '...');

        $candidateFilms ??= Film::query()
            ->where(function ($q): void {
                $q->whereNull('poster_path')->orWhere('poster_path', '');
            })
            ->orderBy('title')
            ->get();

        $scoredCandidates = [];

        foreach ($candidateFilms as $film) {
            $rawTitleClean = $this->normalizeOcrText($film->title);
            $titleNorm = $this->normalizeLeetText($this->normalizeRomanNumerals($rawTitleClean));
            $titleLeet = $this->normalizeLeetText($titleNorm);
            $titleNoSpaces = (string) preg_replace('/\s+/u', '', $titleNorm);
            $titleLeetNoSpaces = (string) preg_replace('/\s+/u', '', $titleLeet);
            $titleSequel = $this->extractSequelNumber($titleNorm);

            if (mb_strlen($titleNoSpaces) < 2) {
                continue;
            }

            // Check Sequel & Year Isolation:
            $hasSequelConflict = false;
            if ($titleSequel !== null && $ocrSequel === null) {
                $hasSequelConflict = true;
            } elseif ($titleSequel === null && $ocrSequel !== null) {
                $hasSequelConflict = true;
            } elseif ($titleSequel !== null && $ocrSequel !== null && $titleSequel !== $ocrSequel) {
                $hasSequelConflict = true;
            }

            // Year conflict: check if title explicitly contains a 4-digit release year (e.g. 1990, 1995)
            $titleYear = preg_match('/\b(19[89][0-9]|20[0-2][0-9])\b/u', $titleNorm, $mTitle) ? $mTitle[1] : null;
            $ocrYear = preg_match('/\b(19[89][0-9]|20[0-2][0-9])\b/u', $ocrNorm, $mOcr) ? $mOcr[1] : null;
            if ($titleYear !== null && $ocrYear !== null && $titleYear !== $ocrYear) {
                $hasSequelConflict = true;
            }

            // Generate title variations (e.g. without "(2024)", without subtitles after ":", "-")
            $titleVariants = [$titleNorm];
            if (str_contains($film->title, '(')) {
                $withoutParen = trim((string) preg_replace('/\s*\([^)]*\)/u', '', $film->title));
                if (! empty($withoutParen)) {
                    $titleVariants[] = $this->normalizeLeetText($this->normalizeRomanNumerals($this->normalizeOcrText($withoutParen)));
                }
            }
            if (str_contains($film->title, ':')) {
                $beforeColon = trim(explode(':', $film->title)[0]);
                if (! empty($beforeColon)) {
                    $titleVariants[] = $this->normalizeLeetText($this->normalizeRomanNumerals($this->normalizeOcrText($beforeColon)));
                }
            }
            if (str_contains($film->title, ' - ')) {
                $beforeDash = trim(explode(' - ', $film->title)[0]);
                if (! empty($beforeDash)) {
                    $titleVariants[] = $this->normalizeLeetText($this->normalizeRomanNumerals($this->normalizeOcrText($beforeDash)));
                }
            }
            $titleVariants = array_values(array_unique(array_filter($titleVariants)));

            $filmScore = 0.0;
            $filmMatchType = 'fuzzy_token_overlap';

            // If there's a sequel conflict, the film CANNOT match directly
            if ($hasSequelConflict) {
                // Heavily penalize to maximum 0.20 so it never auto-matches
                $filmScore = 0.20;
                $filmMatchType = 'sequel_mismatch_penalty';
                $scoredCandidates[] = [
                    'film' => $film,
                    'score' => $filmScore,
                    'match_type' => $filmMatchType,
                ];

                continue;
            }

            // 1. Exact phrase & spaced matching
            foreach ($titleVariants as $v) {
                $vNoSpaces = (string) preg_replace('/\s+/u', '', $v);
                $isExactFull = ($ocrNorm === $v || $ocrNoSpaces === $vNoSpaces);
                $isExactSubstring = preg_match('/\b'.preg_quote($v, '/').'\b/u', $ocrNorm);

                if ($isExactFull) {
                    $filmScore = 1.0;
                    $filmMatchType = 'exact_phrase';
                    break;
                } elseif ($isExactSubstring) {
                    // Check if this title is a strict substring of another candidate film
                    // whose distinctive words are also present in OCR text (e.g. "Mati Rasa" vs "Perayaan Mati Rasa")
                    $isSubphraseOfLongerCandidate = false;
                    foreach ($candidateFilms as $otherFilm) {
                        if ($otherFilm->id === $film->id) {
                            continue;
                        }
                        $otherTitleNorm = $this->normalizeLeetText($this->normalizeRomanNumerals($this->normalizeOcrText($otherFilm->title)));
                        if (str_contains($otherTitleNorm, $v) && $otherTitleNorm !== $v) {
                            $otherWords = array_diff(explode(' ', $otherTitleNorm), explode(' ', $v));
                            $allOtherWordsInOcr = true;
                            foreach ($otherWords as $ow) {
                                if (mb_strlen($ow) >= 3 && ! in_array($ow, $ocrWords, true)) {
                                    $allOtherWordsInOcr = false;
                                    break;
                                }
                            }
                            if ($allOtherWordsInOcr) {
                                $isSubphraseOfLongerCandidate = true;
                                break;
                            }
                        }
                    }

                    if ($isSubphraseOfLongerCandidate) {
                        $filmScore = 0.70;
                        $filmMatchType = 'subphrase_of_longer_candidate';
                        $scoredCandidates[] = [
                            'film' => $film,
                            'score' => $filmScore,
                            'match_type' => $filmMatchType,
                        ];

                        continue 2;
                    }

                    // Check if this title occurrence in OCR is preceded by an attribution or franchise keyword
                    // (e.g. "tetralogi laskar pelangi" or "dari produser kkn")
                    $pos = mb_strpos($ocrNorm, $v);
                    $isPrecededByAttribution = false;
                    if ($pos !== false && $pos > 0) {
                        $beforeText = mb_substr($ocrNorm, 0, $pos);
                        if (preg_match('/\b(?:tetralogi|trilogi|dari\s+produser|dari\s+penulis|dari\s+kreator|karya|sutradara|jagat|universe)\s*$/u', trim($beforeText))) {
                            $isPrecededByAttribution = true;
                        }
                    }

                    if ($isPrecededByAttribution) {
                        $filmScore = 0.70;
                        $filmMatchType = 'attribution_mention';
                        $scoredCandidates[] = [
                            'film' => $film,
                            'score' => $filmScore,
                            'match_type' => $filmMatchType,
                        ];

                        continue 2;
                    }

                    $vWordCount = count(explode(' ', $v));
                    $ocrWordCount = count($ocrWords);
                    $base = ($ocrWordCount <= $vWordCount + 1) ? 0.98 : 0.95;
                    if ($pos === 0) {
                        $base = min(1.0, $base + 0.04);
                    }
                    $score = $base;

                    if ($score > $filmScore) {
                        $filmScore = $score;
                        $filmMatchType = 'exact_phrase';
                    }
                } elseif (mb_strlen($vNoSpaces) >= 4 && (str_contains($ocrNoSpaces, $vNoSpaces) || str_contains($ocrLeetNoSpaces, $this->normalizeLeetText($vNoSpaces)))) {
                    $score = 0.98;
                    if ($score > $filmScore) {
                        $filmScore = $score;
                        $filmMatchType = 'spaced_title_match';
                    }
                }
            }

            // 2. Sub-phrase match with word-count proportion
            $titleWords = array_values(array_filter(explode(' ', $titleNorm), fn (string $w): bool => mb_strlen($w) >= 2));
            $titleWordCount = count($titleWords);

            if ($titleWordCount >= 2 && $filmScore < 0.90) {
                for ($len = min($titleWordCount, 4); $len >= 2; $len--) {
                    for ($start = 0; $start <= $titleWordCount - $len; $start++) {
                        $subSlice = array_slice($titleWords, $start, $len);
                        $subPhrase = implode(' ', $subSlice);
                        $subPhraseNoSpaces = implode('', $subSlice);

                        if (mb_strlen($subPhraseNoSpaces) >= 6) {
                            if (str_contains($ocrNorm, $subPhrase) || str_contains($ocrNoSpaces, $subPhraseNoSpaces) || str_contains($ocrLeetNoSpaces, $this->normalizeLeetText($subPhraseNoSpaces))) {
                                $subScore = 0.70 + (0.18 * ($len / $titleWordCount));
                                if ($subScore > $filmScore) {
                                    $filmScore = $subScore;
                                    $filmMatchType = 'prominent_subphrase';
                                }
                            }
                        }
                    }
                }
            }

            // 3. Fuzzy token matching with Levenshtein
            if (! empty($titleWords) && $filmScore < 0.90) {
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
                    // Missing words penalty: if title has significant words absent in OCR
                    $unmatchedWordRatio = 1.0 - ($matchedWeight / $totalWeight);
                    if ($unmatchedWordRatio > 0.3) {
                        $tokenScore -= 0.15;
                    }

                    // Check if matched words appear in consecutive order in OCR
                    $inOrder = true;
                    $lastIdx = -1;
                    foreach ($titleWords as $tw) {
                        $slice = array_slice($ocrWords, $lastIdx + 1);
                        $foundIdx = false;
                        foreach ($slice as $idxOffset => $ow) {
                            if ($ow === $tw || (mb_strlen($tw) >= 4 && levenshtein($tw, $ow) <= 1)) {
                                $foundIdx = $idxOffset;
                                break;
                            }
                        }
                        if ($foundIdx === false) {
                            $inOrder = false;
                            break;
                        }
                        $lastIdx = $lastIdx + 1 + $foundIdx;
                    }

                    if (! $inOrder) {
                        // Words scattered or out of order across OCR text: cap score to 0.70
                        $tokenScore = min(0.70, $tokenScore);
                    } else {
                        $firstIdx = -1;
                        foreach ($ocrWords as $idx => $ow) {
                            if ($ow === $titleWords[0] || (mb_strlen($titleWords[0]) >= 4 && levenshtein($titleWords[0], $ow) <= 1)) {
                                $firstIdx = $idx;
                                break;
                            }
                        }
                        $span = ($firstIdx >= 0 && $lastIdx >= $firstIdx) ? ($lastIdx - $firstIdx + 1) : count($titleWords);
                        if ($span > count($titleWords) + 2) {
                            $tokenScore = min(0.75, $tokenScore);
                        }
                    }

                    if ($tokenScore > $filmScore) {
                        $filmScore = $tokenScore;
                        $filmMatchType = 'fuzzy_token_overlap';
                    }
                }
            }

            // 4. Sliding window string similarity
            if (mb_strlen($titleNoSpaces) >= 5 && mb_strlen($ocrNoSpaces) >= mb_strlen($titleNoSpaces) && $filmScore < 0.85) {
                $tLen = mb_strlen($titleNoSpaces);
                $ocrLen = mb_strlen($ocrNoSpaces);

                for ($i = 0; $i <= $ocrLen - $tLen; $i++) {
                    $chunk = mb_substr($ocrNoSpaces, $i, $tLen);
                    similar_text($titleNoSpaces, $chunk, $pct);
                    $simScore = $pct / 100.0;

                    if ($simScore > $filmScore) {
                        $filmScore = $simScore;
                        $filmMatchType = 'sliding_similarity';
                    }
                }
            }

            if ($filmScore >= 0.35) {
                $scoredCandidates[] = [
                    'film' => $film,
                    'score' => round($filmScore, 2),
                    'match_type' => $filmMatchType,
                ];
            }
        }

        if (empty($scoredCandidates)) {
            return null;
        }

        // Sort descending by score
        usort($scoredCandidates, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        $top = $scoredCandidates[0];
        $second = $scoredCandidates[1] ?? null;

        $topScore = $top['score'];
        $secondScore = $second ? $second['score'] : 0.0;
        $margin = round($topScore - $secondScore, 2);

        // Strict auto-match threshold:
        // Top score must be >= 0.85 AND margin must be >= 0.15 over the 2nd candidate
        $isUnique = $margin >= 0.15;
        $isAutoMatched = ($topScore >= 0.85) && $isUnique;
        $needsReview = ! $isAutoMatched;

        // Build up to 3 candidate suggestions for manual review
        $suggestions = [];
        foreach (array_slice($scoredCandidates, 0, 3) as $c) {
            $suggestions[] = [
                'id' => $c['film']->id,
                'title' => $c['film']->title,
                'score' => $c['score'],
                'match_type' => $c['match_type'],
            ];
        }

        return [
            'film' => $top['film'],
            'confidence' => $topScore,
            'match_type' => $top['match_type'],
            'preview_ocr' => $previewOcr,
            'margin' => $margin,
            'is_unique' => $isUnique,
            'needs_review' => $needsReview,
            'suggestions' => $suggestions,
        ];
    }

    private function extractSequelNumber(string $text): ?string
    {
        // Explicit sequel markers: ke-2, part 2, chapter 2, vol 2
        if (preg_match('/\b(?:ke\s*[-–]?\s*|part\s*|chapter\s*|vol(?:ume)?\s*)([0-9]+)\b/u', $text, $m)) {
            return (string) (int) $m[1];
        }

        // Standalone single-digit sequel numbers 2-9
        if (preg_match('/\b([2-9])\b/u', $text, $m)) {
            return (string) (int) $m[1];
        }

        return null;
    }

    private function normalizeOcrText(string $text): string
    {
        $clean = mb_strtolower($text, 'UTF-8');
        $clean = (string) preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $clean);

        return trim((string) preg_replace('/\s+/u', ' ', $clean));
    }

    private function normalizeLeetText(string $text): string
    {
        // Replace OCR visual confusions within/adjacent to words without destroying standalone numbers:
        // '0' inside or adjacent to letters -> 'o' (e.g. 'lim0' -> 'limo')
        $text = (string) preg_replace('/(?<=[a-zA-Z])0|0(?=[a-zA-Z])/u', 'o', $text);
        // '1' inside or adjacent to letters -> 'i' (e.g. 'd1lan' -> 'dilan')
        $text = (string) preg_replace('/(?<=[a-zA-Z])1|1(?=[a-zA-Z])/u', 'i', $text);
        // Visual symbols '|' and '!' -> 'i'
        $text = str_replace(['|', '!'], 'i', $text);

        return $text;
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
