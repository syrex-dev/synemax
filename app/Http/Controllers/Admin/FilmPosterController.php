<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Film;
use App\Services\FilmBulkImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
            ->select(['id', 'title', 'slug', 'poster_path'])
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
            $imageInfo = ($size > 0 && $size < 15 * 1024 * 1024) ? @getimagesize($fullPath) : false;

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
        // Include minimal columns to save memory on 2GB RAM device
        $allFilms = Film::query()
            ->select(['id', 'title', 'release_year', 'release_date', 'genre', 'poster_path'])
            ->orderBy('title')
            ->get();
        $filmOptions = [];
        foreach ($allFilms as $film) {
            $year = $film->release_year ?: ($film->release_date ? $film->release_date->format('Y') : '');
            $hasPoster = ! empty($film->poster_path);
            $prefix = $hasPoster ? '' : '★ [Belum Ada Poster] ';
            $suffix = $hasPoster ? ' (Ganti poster)' : '';
            $label = $prefix.$film->title.($year ? " ({$year})" : '').' - '.$film->genre.$suffix;
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
    public function processPosterFile(string $sourcePath, string $originalFilename, FilmBulkImporter $importer): array
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

        // Check filename matching against film title / slug
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
                'stored_path' => $storedPath,
                'poster_filename' => basename($storedPath),
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
            'unmatched_filename' => basename($destUnmatched),
            'stored_path' => $destUnmatched,
            'film_id' => null,
            'film_title' => null,
            'poster_url' => Storage::disk('public')->url($destUnmatched),
            'warning' => $warning,
            'message' => 'Tidak ditemukan film yang cocok. Disimpan ke antrean poster belum terpasang.',
        ];
    }

    /**
     * AJAX endpoint: Process a single uploaded image.
     */
    public function processSingle(Request $request, FilmBulkImporter $importer): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|max:5120',
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

        $result = $this->processPosterFile($tempPath, $originalName, $importer);

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
        ]);

        $batchId = $validated['batch_id'];
        $stagedPath = $validated['staged_path'];
        $filename = $validated['filename'];

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

        $result = $this->processPosterFile($realPath, $filename, $importer);

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
        $filename = basename(urldecode($validated['poster_filename']));
        $sourcePath = self::UNMATCHED_DIR.'/'.$filename;

        if (! Storage::disk('public')->exists($sourcePath)) {
            // Case-insensitive fallback check in unmatched folder
            $files = Storage::disk('public')->files(self::UNMATCHED_DIR);
            $found = null;
            foreach ($files as $f) {
                if (strtolower(basename($f)) === strtolower($filename)) {
                    $found = $f;
                    $filename = basename($f);
                    break;
                }
            }
            if ($found) {
                $sourcePath = $found;
            } else {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Berkas poster tidak ditemukan di penyimpanan.'], 404);
                }

                return back()->with('error', 'Berkas poster tidak ditemukan di penyimpanan.');
            }
        }

        // Move to permanent posters folder
        $permanentPath = 'posters/'.Str::uuid().'.'.pathinfo($filename, PATHINFO_EXTENSION);
        Storage::disk('public')->move($sourcePath, $permanentPath);

        // Remove old poster file if exists and not a sample
        $oldPoster = $film->poster_path;
        if (! empty($oldPoster) && Storage::disk('public')->exists($oldPoster) && ! str_starts_with($oldPoster, 'samples/')) {
            Storage::disk('public')->delete($oldPoster);
        }

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
            $filename = basename(urldecode($item['poster_filename']));
            $sourcePath = self::UNMATCHED_DIR.'/'.$filename;

            if (! Storage::disk('public')->exists($sourcePath)) {
                $files = Storage::disk('public')->files(self::UNMATCHED_DIR);
                $found = null;
                foreach ($files as $f) {
                    if (strtolower(basename($f)) === strtolower($filename)) {
                        $found = $f;
                        $filename = basename($f);
                        break;
                    }
                }
                if ($found) {
                    $sourcePath = $found;
                } else {
                    $failedCount++;

                    continue;
                }
            }

            $film = Film::find($filmId);
            if (! $film) {
                $failedCount++;

                continue;
            }

            $previousPosterPath = $film->poster_path;
            $permanentPath = 'posters/'.Str::uuid().'.'.pathinfo($filename, PATHINFO_EXTENSION);
            Storage::disk('public')->move($sourcePath, $permanentPath);

            // Clean up old poster from disk to save storage on 8GB STB
            if (! empty($previousPosterPath) && Storage::disk('public')->exists($previousPosterPath) && ! str_starts_with($previousPosterPath, 'samples/')) {
                Storage::disk('public')->delete($previousPosterPath);
            }

            $film->update(['poster_path' => $permanentPath, 'is_sample' => false]);
            $assignedCount++;

            $assignedItems[] = [
                'filename' => $item['poster_filename'],
                'poster_filename' => $item['poster_filename'],
                'film_id' => $film->id,
                'film_title' => $film->title,
                'permanent_path' => $permanentPath,
                'previous_poster_path' => $previousPosterPath,
            ];
        }

        if ($assignedCount === 0 && $failedCount > 0) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'assigned_count' => 0,
                    'failed_count' => $failedCount,
                    'items' => [],
                    'message' => "Gagal memasangkan {$failedCount} poster: berkas tidak ditemukan di penyimpanan antrean.",
                ], 422);
            }

            return back()->with('error', "Gagal memasangkan {$failedCount} poster: berkas tidak ditemukan di antrean.");
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
     * Delete an unmatched poster.
     */
    public function destroy(Request $request, string $filename): RedirectResponse|JsonResponse
    {
        $cleanName = basename(urldecode($filename));
        $path = self::UNMATCHED_DIR.'/'.$cleanName;

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        } else {
            // Also check by slug prefix or case-insensitive match in unmatched dir
            $slug = Str::slug(pathinfo($cleanName, PATHINFO_FILENAME));
            $files = Storage::disk('public')->files(self::UNMATCHED_DIR);
            foreach ($files as $f) {
                $base = basename($f);
                if (strtolower($base) === strtolower($cleanName) || ($slug && str_starts_with($base, $slug))) {
                    Storage::disk('public')->delete($f);
                    $cleanName = $base;
                    break;
                }
            }
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

    /**
     * Delete / unbind an uploaded poster that was matched automatically (if wrongly uploaded/matched).
     */
    public function removeMatched(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'film_id' => 'required|integer|exists:films,id',
            'stored_path' => 'nullable|string',
        ]);

        $film = Film::find($validated['film_id']);
        if (! $film) {
            return response()->json(['success' => false, 'message' => 'Film tidak ditemukan.'], 404);
        }

        $pathToDelete = $validated['stored_path'] ?? $film->poster_path;
        if (! empty($pathToDelete) && Storage::disk('public')->exists($pathToDelete) && ! str_starts_with($pathToDelete, 'samples/')) {
            Storage::disk('public')->delete($pathToDelete);
        }

        $film->update([
            'poster_path' => null,
            'is_sample' => false,
        ]);

        return response()->json([
            'success' => true,
            'film_id' => $film->id,
            'film_title' => $film->title,
            'message' => "Pemasangan poster film \"{$film->title}\" dibatalkan dan berkas berhasil dihapus.",
        ]);
    }
}
