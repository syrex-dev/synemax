<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Film;
use App\Services\FilmBulkImporter;
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
     * Handle bulk upload of multiple image files or ZIP archive.
     */
    public function upload(Request $request, FilmBulkImporter $importer): RedirectResponse
    {
        $request->validate([
            'images.*' => 'nullable|file|max:5120', // max 5MB per image
            'zip_file' => 'nullable|file|mimes:zip|max:51200', // max 50MB
        ], [
            'images.*.max' => 'Ukuran berkas gambar maksimal 5 MB per gambar.',
            'zip_file.mimes' => 'Berkas harus berformat ZIP.',
            'zip_file.max' => 'Ukuran berkas ZIP maksimal 50 MB.',
        ]);

        $uploadedImages = $request->file('images', []);
        if (empty($uploadedImages)) {
            $uploadedImages = $request->file('posters', []);
        }
        $zipFile = $request->file('zip_file');

        if (empty($uploadedImages) && $zipFile === null) {
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
                return back()->with('error', 'Gagal memproses berkas ZIP: '.$e->getMessage());
            }
        }

        if (empty($allCandidatePaths)) {
            return back()->with('error', 'Tidak ditemukan berkas gambar yang valid pada data yang diunggah.');
        }

        // 3. Process candidate images and attempt auto-matching
        $filmsWithoutPoster = Film::query()
            ->where(function ($q) {
                $q->whereNull('poster_path')->orWhere('poster_path', '');
            })
            ->get();

        $matchedCount = 0;
        $unmatchedCount = 0;
        $warningsCount = 0;

        foreach ($allCandidatePaths as $candidate) {
            $sourcePath = $candidate['staged_path'];
            if (! file_exists($sourcePath)) {
                continue;
            }

            // Genuine image validation
            $imageInfo = @getimagesize($sourcePath);
            if ($imageInfo === false || ! in_array($imageInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
                @unlink($sourcePath);

                continue;
            }

            if ($imageInfo[0] < 300) {
                $warningsCount++;
            }

            // Crop & store 600x900 WebP (fallback JPG)
            try {
                $storedPath = $importer->cropAndStorePoster($sourcePath);
            } catch (\Exception $e) {
                @unlink($sourcePath);

                continue;
            }

            $origFilename = $candidate['filename'];
            $origNoExt = strtolower(pathinfo($origFilename, PATHINFO_FILENAME));
            $origSlug = Str::slug($origNoExt);

            // Attempt matching
            $matchedFilm = null;
            foreach ($filmsWithoutPoster as $film) {
                $filmSlug = $film->slug;
                $filmTitleSlug = Str::slug($film->title);

                if (
                    $origNoExt === strtolower($film->title) ||
                    $origSlug === $filmSlug ||
                    $origSlug === $filmTitleSlug ||
                    str_contains($origSlug, $filmSlug) ||
                    str_contains($filmSlug, $origSlug)
                ) {
                    $matchedFilm = $film;
                    break;
                }
            }

            if ($matchedFilm) {
                $matchedFilm->update(['poster_path' => $storedPath]);
                // Remove from local list so other posters don't match the same film
                $filmsWithoutPoster = $filmsWithoutPoster->reject(fn ($f) => $f->id === $matchedFilm->id);
                $matchedCount++;
            } else {
                // Move to unmatched folder
                $destUnmatched = self::UNMATCHED_DIR.'/'.Str::slug($origNoExt).'_'.Str::random(6).'.'.pathinfo($storedPath, PATHINFO_EXTENSION);
                Storage::disk('public')->copy($storedPath, $destUnmatched);
                Storage::disk('public')->delete($storedPath);
                $unmatchedCount++;
            }

            @unlink($sourcePath);
        }

        // Cleanup temporary staging folder
        $files = glob("{$stagingDir}/*");
        if ($files) {
            foreach ($files as $f) {
                @unlink($f);
            }
        }
        @rmdir($stagingDir);

        $msg = "Proses poster selesai: {$matchedCount} poster berhasil dicocokkan otomatis.";
        if ($unmatchedCount > 0) {
            $msg .= " {$unmatchedCount} poster belum terpasang dan dapat dipasangkan secara manual.";
        }
        if ($warningsCount > 0) {
            $msg .= " Catatan: {$warningsCount} gambar memiliki lebar di bawah 300px (peringatan kualitas).";
        }

        return redirect()->route('admin.films.posters.index')->with('status', $msg);
    }

    /**
     * Manually assign an unmatched poster to a selected film.
     */
    public function assign(Request $request): RedirectResponse
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
            return back()->with('error', 'Berkas poster tidak ditemukan di penyimpanan.');
        }

        // Move to permanent posters folder
        $permanentPath = 'posters/'.Str::uuid().'.'.pathinfo($filename, PATHINFO_EXTENSION);
        Storage::disk('public')->copy($sourcePath, $permanentPath);
        Storage::disk('public')->delete($sourcePath);

        $film->update(['poster_path' => $permanentPath]);

        return redirect()->route('admin.films.posters.index')
            ->with('status', "Poster berhasil dipasangkan ke film \"{$film->title}\".");
    }

    /**
     * Delete an unmatched poster.
     */
    public function destroy(string $filename): RedirectResponse
    {
        $cleanName = basename($filename);
        $path = self::UNMATCHED_DIR.'/'.$cleanName;

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return redirect()->route('admin.films.posters.index')
            ->with('status', 'Poster belum terpasang berhasil dihapus.');
    }
}
