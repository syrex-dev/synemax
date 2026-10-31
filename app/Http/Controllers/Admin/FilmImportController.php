<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Film;
use App\Models\ImportBatch;
use App\Services\FilmBulkImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FilmImportController extends Controller
{
    /**
     * Show import upload form and recent import history.
     */
    public function create(): View
    {
        $recentBatches = ImportBatch::with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.films.import', [
            'recentBatches' => $recentBatches,
        ]);
    }

    /**
     * Download CSV template for bulk film import.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_impor_film_synemax.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header (9 canonical columns)
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

            // Exactly 2 sample rows per requirement B1
            fputcsv($handle, [
                'Inception',
                'Sci-Fi; Aksi',
                '2010',
                '148',
                '13+',
                'Seorang pencuri menyusup ke dalam mimpi orang lain untuk menanamkan ide rahasia.',
                'inception.jpg',
                'https://www.youtube.com/watch?v=YoHD9XEInc0',
                'tidak',
            ]);
            fputcsv($handle, [
                'Petualangan Sherina 2',
                'Petualangan; Komedi; Keluarga',
                '2023',
                '126',
                'SU',
                'Sherina dan Sadam kembali bertemu di hutan Kalimantan untuk petualangan baru.',
                'sherina2.jpg',
                'https://www.youtube.com/watch?v=sample',
                'tidak',
            ]);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Handle upload and show preview with inline correction.
     */
    public function preview(Request $request, FilmBulkImporter $importer): View|RedirectResponse
    {
        $request->validate([
            'csv_file' => 'required|file|max:5120', // max 5MB per requirement B2
            'zip_file' => 'nullable|file|mimes:zip|max:51200', // max 50MB
            'posters_zip' => 'nullable|file|mimes:zip|max:51200',
        ], [
            'csv_file.required' => 'Pilih berkas CSV film yang ingin diimpor.',
            'csv_file.max' => 'Ukuran berkas CSV maksimal 5 MB.',
            'zip_file.mimes' => 'Berkas poster harus berformat ZIP.',
            'zip_file.max' => 'Ukuran berkas ZIP poster maksimal 50 MB.',
            'posters_zip.mimes' => 'Berkas poster harus berformat ZIP.',
            'posters_zip.max' => 'Ukuran berkas ZIP poster maksimal 50 MB.',
        ]);

        $csvFile = $request->file('csv_file');
        $zipFile = $request->file('zip_file') ?? $request->file('posters_zip');

        $content = file_get_contents($csvFile->getRealPath());
        if ($content === false || trim($content) === '') {
            return back()->with('error', 'Berkas CSV kosong atau tidak dapat dibaca.');
        }

        $batchId = Str::uuid()->toString();
        $rawRows = $importer->parseCsv($content);

        if (empty($rawRows)) {
            return back()->with('error', 'Tidak ada baris data film yang ditemukan di dalam berkas CSV.');
        }

        if (count($rawRows) > 2000) {
            return back()->with('error', 'Jumlah data melebihi batas maksimal 2.000 baris per berkas.');
        }

        $analyzedRows = $importer->analyzeRows($rawRows);

        // Process ZIP if provided
        $extractedPosters = [];
        if ($zipFile !== null) {
            try {
                $extractedPosters = $importer->extractPosterZip($zipFile, $batchId);
            } catch (\Exception $e) {
                return back()->with('error', 'Gagal memproses file ZIP poster: '.$e->getMessage());
            }
        }

        $matchedPostersCount = 0;
        $unmatchedPosters = array_keys($extractedPosters);
        if (! empty($extractedPosters)) {
            foreach ($analyzedRows as &$arow) {
                $matched = $importer->matchPoster($arow['title'], $arow['slug'], $arow['poster_filename'] ?? null, $extractedPosters);
                if ($matched) {
                    $arow['matched_poster'] = $matched;
                    $matchedPostersCount++;
                    $unmatchedPosters = array_values(array_filter($unmatchedPosters, fn ($p) => $p !== $matched));
                }
            }
            unset($arow);
        }

        // Cache staged data for 2 hours
        Cache::put("import_preview_{$batchId}", [
            'batch_id' => $batchId,
            'filename' => $csvFile->getClientOriginalName(),
            'rows' => $analyzedRows,
            'posters' => $extractedPosters,
        ], now()->addHours(2));

        $stats = [
            'total' => count($analyzedRows),
            'valid' => count(array_filter($analyzedRows, fn ($r) => in_array($r['status'], ['baru', 'valid'], true))),
            'warning' => count(array_filter($analyzedRows, fn ($r) => $r['status'] === 'warning')),
            'duplikat' => count(array_filter($analyzedRows, fn ($r) => in_array($r['status'], ['duplikat', 'duplicate'], true))),
            'error' => count(array_filter($analyzedRows, fn ($r) => in_array($r['status'], ['masalah', 'invalid', 'error'], true))),
            'posters_count' => $matchedPostersCount,
        ];

        $summary = [
            'total' => $stats['total'],
            'baru' => $stats['valid'],
            'duplikat' => $stats['duplikat'],
            'masalah' => $stats['error'],
            'valid' => $stats['valid'],
            'duplicate' => $stats['duplikat'],
            'invalid' => $stats['error'],
            'warning' => $stats['warning'],
            'has_poster' => $matchedPostersCount,
        ];

        return view('admin.films.import-preview', [
            'batchId' => $batchId,
            'batchToken' => $batchId,
            'filename' => $csvFile->getClientOriginalName(),
            'rows' => $analyzedRows,
            'stats' => $stats,
            'summary' => $summary,
            'duplicateStrategy' => $request->input('duplicate_strategy', 'skip'),
            'totalPosters' => count($extractedPosters),
            'unmatchedPosters' => $unmatchedPosters,
            'canonicalGenres' => Film::CANONICAL_GENRES,
            'extractedPosters' => $extractedPosters,
        ]);
    }

    /**
     * Resume an import batch after browser tab was closed.
     */
    public function resume(string $batchToken): View|RedirectResponse
    {
        $batch = ImportBatch::find($batchToken);
        if (! $batch) {
            return redirect()->route('admin.films.import.create')
                ->with('error', 'Batch impor tidak ditemukan.');
        }

        $staged = Cache::get("import_preview_{$batchToken}");
        if (! $staged) {
            return redirect()->route('admin.films.import.create')
                ->with('error', 'Sesi data pratinjau batch impor telah kedaluwarsa dari cache server. Silakan unggah kembali berkas CSV Anda.');
        }

        $rows = $staged['rows'] ?? [];
        $extractedPosters = $staged['posters'] ?? [];
        $processed = (int) ($batch->imported_count + $batch->skipped_count + $batch->failed_count);
        $initialChunk = (int) floor($processed / 25);

        $stats = [
            'total' => count($rows),
            'valid' => count(array_filter($rows, fn ($r) => in_array($r['status'], ['baru', 'valid'], true))),
            'warning' => count(array_filter($rows, fn ($r) => $r['status'] === 'warning')),
            'duplikat' => count(array_filter($rows, fn ($r) => in_array($r['status'], ['duplikat', 'duplicate'], true))),
            'error' => count(array_filter($rows, fn ($r) => in_array($r['status'], ['masalah', 'invalid', 'error'], true))),
            'posters_count' => count(array_filter($rows, fn ($r) => ! empty($r['matched_poster']))),
        ];

        $summary = [
            'total' => $stats['total'],
            'baru' => $stats['valid'],
            'duplikat' => $stats['duplikat'],
            'masalah' => $stats['error'],
            'valid' => $stats['valid'],
            'duplicate' => $stats['duplikat'],
            'invalid' => $stats['error'],
            'warning' => $stats['warning'],
            'has_poster' => $stats['posters_count'],
        ];

        $unmatchedPosters = array_keys($extractedPosters);

        return view('admin.films.import-preview', [
            'batchId' => $batchToken,
            'batchToken' => $batchToken,
            'filename' => $staged['filename'] ?? ($batch->csv_filename ?: 'import.csv'),
            'rows' => $rows,
            'stats' => $stats,
            'summary' => $summary,
            'duplicateStrategy' => 'skip',
            'totalPosters' => count($extractedPosters),
            'unmatchedPosters' => $unmatchedPosters,
            'canonicalGenres' => Film::CANONICAL_GENRES,
            'extractedPosters' => $extractedPosters,
            'autoResume' => true,
            'initialChunk' => $initialChunk,
            'initialProcessed' => $processed,
            'initialImported' => (int) $batch->imported_count,
            'initialSkipped' => (int) $batch->skipped_count,
            'initialFailed' => (int) $batch->failed_count,
        ]);
    }

    /**
     * Process a chunk of 25 rows via AJAX for shared hosting safety (immune to 30s timeout).
     */
    public function processChunk(Request $request, FilmBulkImporter $importer): JsonResponse
    {
        $batchToken = (string) ($request->input('batch_token') ?: $request->input('batch_id'));
        $chunkIndex = (int) $request->input('chunk_index', 0);
        $chunkSize = (int) $request->input('chunk_size', 25);
        $strategy = strtolower((string) $request->input('duplicate_strategy', 'skip'));

        $staged = Cache::get("import_preview_{$batchToken}");
        if (! $staged) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesi pratinjau impor telah kedaluwarsa. Silakan unggah kembali berkas CSV Anda.',
            ], 419);
        }

        $rows = $staged['rows'];
        $posters = $staged['posters'] ?? [];

        // Apply any excluded rows or inline edits submitted from client
        $excludedIndices = array_map('intval', (array) $request->input('excluded_rows', []));
        $editedRows = $request->input('rows', []);

        foreach ($rows as $idx => &$row) {
            if (in_array((int) $idx, $excludedIndices, true)) {
                $row['is_included'] = false;
            }
            if (is_array($editedRows) && isset($editedRows[$idx])) {
                $e = $editedRows[$idx];
                if (isset($e['is_included'])) {
                    $row['is_included'] = ! empty($e['is_included']);
                }
                if (isset($e['title'])) {
                    $row['title'] = trim((string) $e['title']);
                }
                if (isset($e['genre'])) {
                    $row['genre'] = trim((string) $e['genre']);
                }
                if (isset($e['duration_minutes'])) {
                    $row['duration_minutes'] = (int) $e['duration_minutes'];
                }
                if (isset($e['age_rating'])) {
                    $row['age_rating'] = trim((string) $e['age_rating']);
                }
                if (isset($e['release_year'])) {
                    $row['release_year'] = (int) $e['release_year'];
                }
                if (isset($e['synopsis'])) {
                    $row['synopsis'] = trim((string) $e['synopsis']);
                }
                if (isset($e['is_active'])) {
                    $row['is_active'] = (bool) $e['is_active'];
                }
                if (isset($e['poster_filename']) || isset($e['matched_poster'])) {
                    $row['poster_filename'] = trim((string) ($e['poster_filename'] ?? $e['matched_poster']));
                }
            }
        }
        unset($row);

        $totalRows = count($rows);
        $offset = $chunkIndex * $chunkSize;
        $chunk = array_slice($rows, $offset, $chunkSize);

        if (empty($chunk)) {
            $batch = ImportBatch::find($batchToken);
            if ($batch) {
                $batch->update(['status' => 'completed']);
            }

            return response()->json([
                'status' => 'ok',
                'batch_token' => $batchToken,
                'chunk_index' => $chunkIndex,
                'processed' => $totalRows,
                'total' => $totalRows,
                'percent' => 100,
                'is_last' => true,
                'batch_imported' => $batch?->imported_count ?? 0,
                'batch_skipped' => $batch?->skipped_count ?? 0,
                'batch_failed' => $batch?->failed_count ?? 0,
            ]);
        }

        $result = $importer->executeChunk(
            $chunk,
            $strategy,
            $batchToken,
            auth()->id(),
            $staged['filename'] ?? 'import.csv',
            $posters
        );

        $processedCount = min($totalRows, $offset + count($chunk));
        $isLast = $processedCount >= $totalRows;
        $batch = ImportBatch::find($batchToken);

        if ($isLast && $batch) {
            $batch->update(['status' => 'completed']);
        }

        return response()->json([
            'status' => 'ok',
            'batch_token' => $batchToken,
            'chunk_index' => $chunkIndex,
            'processed' => $processedCount,
            'total' => $totalRows,
            'percent' => (int) round(($processedCount / max(1, $totalRows)) * 100),
            'is_last' => $isLast,
            'chunk_imported' => $result['imported'],
            'chunk_skipped' => $result['skipped'],
            'chunk_failed' => $result['failed'],
            'batch_imported' => $batch?->imported_count ?? $result['imported'],
            'batch_skipped' => $batch?->skipped_count ?? $result['skipped'],
            'batch_failed' => $batch?->failed_count ?? $result['failed'],
        ]);
    }

    /**
     * Download problem rows report in CSV.
     */
    public function downloadErrorReport(string $batchToken): StreamedResponse
    {
        $batch = ImportBatch::find($batchToken)
            ?: ImportBatch::where('batch_uuid', $batchToken)->first();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="laporan_masalah_impor_'.$batchToken.'.csv"',
        ];

        return response()->stream(function () use ($batch) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['baris', 'judul', 'alasan_masalah']);

            $failedRows = (array) ($batch?->failed_rows ?? []);
            if (empty($failedRows)) {
                fputcsv($handle, ['-', '-', 'Tidak ada baris yang bermasalah pada batch ini.']);
            } else {
                foreach ($failedRows as $fr) {
                    fputcsv($handle, [
                        $fr['row_index'] ?? '-',
                        $fr['title'] ?? '-',
                        $fr['error'] ?? 'Terjadi kesalahan.',
                    ]);
                }
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export all films in catalog in canonical CSV format.
     */
    public function exportCatalog(FilmBulkImporter $importer): StreamedResponse
    {
        return $importer->exportAllFilmsCsv();
    }

    /**
     * Confirm and execute the bulk import (full batch fallback).
     */
    public function confirm(Request $request, FilmBulkImporter $importer): RedirectResponse
    {
        $batchId = (string) ($request->input('batch_token') ?: $request->input('batch_id'));
        $staged = Cache::get("import_preview_{$batchId}");

        if (! $staged) {
            return redirect()->route('admin.films.import.create')
                ->with('error', 'Sesi pratinjau impor telah kedaluwarsa. Silakan unggah kembali berkas CSV Anda.');
        }

        $rows = $staged['rows'];
        $posters = $staged['posters'] ?? [];

        // Check if admin excluded rows or made inline edits in the preview table
        $excludedIndices = array_map('intval', (array) $request->input('excluded_rows', []));
        $editedRows = $request->input('rows', []);

        foreach ($rows as $idx => &$row) {
            $isExcluded = in_array((int) $idx, $excludedIndices, true);
            $row['is_included'] = ! $isExcluded;

            if (is_array($editedRows) && isset($editedRows[$idx])) {
                $e = $editedRows[$idx];
                if (isset($e['is_included'])) {
                    $row['is_included'] = ! empty($e['is_included']);
                }
                if (isset($e['title'])) {
                    $row['title'] = trim((string) $e['title']);
                }
                if (isset($e['duration_minutes'])) {
                    $row['duration_minutes'] = (int) $e['duration_minutes'];
                }
                if (isset($e['genre'])) {
                    $row['genre'] = trim((string) $e['genre']);
                }
                if (isset($e['age_rating'])) {
                    $row['age_rating'] = trim((string) $e['age_rating']);
                }
                if (isset($e['release_year'])) {
                    $row['release_year'] = (int) $e['release_year'];
                }
                if (isset($e['synopsis'])) {
                    $row['synopsis'] = trim((string) $e['synopsis']);
                }
                if (isset($e['is_active'])) {
                    $row['is_active'] = (bool) $e['is_active'];
                }
                if (isset($e['poster_filename']) || isset($e['matched_poster'])) {
                    $row['poster_filename'] = trim((string) ($e['poster_filename'] ?? $e['matched_poster']));
                }
            }
        }
        unset($row);

        $strategy = $request->input('duplicate_strategy', 'skip');

        $result = $importer->executeImport(
            $rows,
            $strategy,
            $batchId,
            auth()->id(),
            $staged['filename'] ?? 'import.csv',
            $posters
        );

        Cache::forget("import_preview_{$batchId}");

        $msg = "Berhasil mengimpor {$result['imported_count']} film ke katalog.";
        if ($result['skipped_count'] > 0) {
            $msg .= " ({$result['skipped_count']} baris dilewati).";
        }

        return redirect()->route('admin.films.index')
            ->with('status', $msg)
            ->with('undo_batch_id', $batchId);
    }

    /**
     * Rollback (undo) an import batch.
     */
    public function rollback(string $batchId, FilmBulkImporter $importer): RedirectResponse
    {
        $count = $importer->rollbackBatch($batchId);

        return redirect()->route('admin.films.index')
            ->with('status', "Impor film batch ({$batchId}) berhasil dibatalkan. {$count} film telah diurungkan.")
            ->with('undo_batch_id', $batchId);
    }
}
