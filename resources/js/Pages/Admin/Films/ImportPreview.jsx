import { useState, useEffect, useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import {
    Layers,
    ArrowLeft,
    CheckCircle2,
    AlertTriangle,
    XCircle,
    Copy,
    Image,
    Play,
    RefreshCw,
    Film,
    Filter,
    Check,
    X,
    ExternalLink,
} from 'lucide-react';

export default function ImportPreview({
    batchId,
    batchToken = batchId,
    filename = 'import.csv',
    rows: initialRows = [],
    stats = {},
    summary = {},
    duplicateStrategy: initialDuplicateStrategy = 'skip',
    totalPosters = 0,
    unmatchedPosters = [],
    canonicalGenres = [],
    newGenresList = [],
    extractedPosters = {},
    publishOnImport = true,
    autoResume = false,
    initialChunk = 0,
    initialProcessed = 0,
    initialImported = 0,
    initialSkipped = 0,
    initialFailed = 0,
}) {
    // Active Step: 'preview' | 'importing' | 'completed'
    const [activeStep, setActiveStep] = useState(autoResume ? 'importing' : 'preview');
    const [rows, setRows] = useState(initialRows);
    const [filterStatus, setFilterStatus] = useState('all'); // 'all' | 'valid' | 'duplikat' | 'warning' | 'error'
    const [duplicateStrategy, setDuplicateStrategy] = useState(initialDuplicateStrategy || 'skip');
    const [excludedRows, setExcludedRows] = useState([]);

    // Progress State for Chunked Import
    const chunkSize = 25;
    const totalRows = rows.length;
    const totalChunks = Math.ceil(totalRows / chunkSize);
    const [currentChunk, setCurrentChunk] = useState(initialChunk || 0);
    const [processedCount, setProcessedCount] = useState(initialProcessed || 0);
    const [importedCount, setImportedCount] = useState(initialImported || 0);
    const [skippedCount, setSkippedCount] = useState(initialSkipped || 0);
    const [failedCount, setFailedCount] = useState(initialFailed || 0);
    const [progressPercent, setProgressPercent] = useState(
        totalRows > 0 ? Math.min(100, Math.round(((initialProcessed || 0) / totalRows) * 100)) : 0
    );
    const [isProcessing, setIsProcessing] = useState(false);
    const [statusMessage, setStatusMessage] = useState(
        autoResume ? 'Melanjutkan proses impor batch...' : 'Menyiapkan proses impor...'
    );
    const [errorMessage, setErrorMessage] = useState('');

    // Toggle include/exclude row
    const toggleRow = (idx) => {
        setExcludedRows((prev) =>
            prev.includes(idx) ? prev.filter((i) => i !== idx) : [...prev, idx]
        );
    };

    const isExcluded = (idx) => excludedRows.includes(idx);
    const includedCount = Math.max(0, totalRows - excludedRows.length);

    // Update row value inline
    const updateRowField = (idx, field, value) => {
        setRows((prev) => {
            const next = [...prev];
            next[idx] = { ...next[idx], [field]: value };
            return next;
        });
    };

    // Map unknown genre to a canonical genre
    const mapGenre = (idx, rawToken, targetGenre) => {
        const row = rows[idx];
        if (!row) return;
        const current = row.genre || '';
        const tokens = current.split(/[,;\/|]+/).map((t) => t.trim());
        const updated = tokens.map((t) =>
            t.toLowerCase() === rawToken.toLowerCase() ? targetGenre : t
        );
        const unique = Array.from(new Set(updated)).filter(Boolean).join(', ');
        updateRowField(idx, 'genre', unique);
    };

    // Remove unknown genre token
    const removeGenre = (idx, rawToken) => {
        const row = rows[idx];
        if (!row) return;
        const current = row.genre || '';
        const tokens = current.split(/[,;\/|]+/).map((t) => t.trim());
        const updated = tokens.filter((t) => t.toLowerCase() !== rawToken.toLowerCase());
        const unique = Array.from(new Set(updated)).filter(Boolean).join(', ');
        updateRowField(idx, 'genre', unique);
    };

    // Start Chunked AJAX Import
    const startChunkedImport = async (isResume = false) => {
        setActiveStep('importing');
        setIsProcessing(true);
        setErrorMessage('');

        let currentCh = isResume ? currentChunk : 0;
        let pCount = isResume ? processedCount : 0;
        let impCount = isResume ? importedCount : 0;
        let skCount = isResume ? skippedCount : 0;
        let fCount = isResume ? failedCount : 0;

        if (!isResume) {
            setCurrentChunk(0);
            setProcessedCount(0);
            setImportedCount(0);
            setSkippedCount(0);
            setFailedCount(0);
            setProgressPercent(0);
        }

        // Build payload map of rows
        const rowsPayload = {};
        rows.forEach((r, idx) => {
            rowsPayload[idx] = {
                title: r.title,
                release_year: r.release_year,
                genre: r.genre,
                duration_minutes: r.duration_minutes,
                ticket_price: r.ticket_price,
                age_rating: r.age_rating,
                synopsis: r.synopsis,
                poster_path: r.matched_poster || r.poster_path || '',
            };
        });

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        while (pCount < totalRows) {
            setStatusMessage(
                `Memproses potongan ${currentCh + 1} dari ${totalChunks} (${Math.min(
                    totalRows,
                    (currentCh + 1) * chunkSize
                )} dari ${totalRows} baris)...`
            );

            try {
                const res = await fetch('/admin/films/impor/process-chunk', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        batch_token: batchToken,
                        chunk_index: currentCh,
                        chunk_size: chunkSize,
                        duplicate_strategy: duplicateStrategy,
                        excluded_rows: excludedRows,
                        rows: rowsPayload,
                    }),
                });

                if (!res.ok) {
                    const errData = await res.json().catch(() => ({}));
                    throw new Error(errData.message || 'Gagal memproses potongan impor di server.');
                }

                const data = await res.json();
                pCount = data.processed;
                impCount = data.batch_imported;
                skCount = data.batch_skipped;
                fCount = data.batch_failed;

                setProcessedCount(pCount);
                setProgressPercent(data.percent || Math.round((pCount / totalRows) * 100));
                setImportedCount(impCount);
                setSkippedCount(skCount);
                setFailedCount(fCount);

                if (data.is_last || pCount >= totalRows) {
                    setIsProcessing(false);
                    setActiveStep('completed');
                    setStatusMessage('Seluruh data berhasil diproses!');
                    break;
                }

                currentCh++;
                setCurrentChunk(currentCh);
            } catch (err) {
                setIsProcessing(false);
                setErrorMessage(err.message || 'Terjadi kesalahan pada potongan impor.');
                setStatusMessage('Proses terhenti.');
                break;
            }
        }
    };

    // Auto resume on mount if requested
    useEffect(() => {
        if (autoResume) {
            startChunkedImport(true);
        }
    }, [autoResume]);

    // Filtered rows for Preview Table
    const filteredRows = rows.filter((r) => {
        if (filterStatus === 'valid') {
            return r.status === 'baru' || r.status === 'valid';
        }
        if (filterStatus === 'duplikat') {
            return r.status === 'duplikat' || r.status === 'duplicate';
        }
        if (filterStatus === 'warning') {
            return r.status === 'warning';
        }
        if (filterStatus === 'error') {
            return r.status === 'masalah' || r.status === 'invalid' || r.status === 'error';
        }
        return true;
    });

    return (
        <AdminLayout title="Pratinjau Impor Film" isFormPage={true}>
            <Head title="Pratinjau & Konfirmasi Impor Film · Admin" />

            <div className="space-y-6 max-w-6xl mx-auto pb-12">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-text-muted mb-1">
                            <Link href="/admin/films" className="hover:text-amber-500 transition-colors">Katalog</Link>
                            <span>/</span>
                            <Link href="/admin/films/impor" className="hover:text-amber-500 transition-colors">Impor Massal</Link>
                            <span>/</span>
                            <span className="text-text">Pratinjau Data</span>
                        </div>
                        <h1 className="text-2xl font-black text-text tracking-tight">Pratinjau & Konfirmasi Impor</h1>
                        <p className="text-xs text-text-muted mt-1">
                            Berkas: <strong className="text-text">{filename}</strong> · Periksa validitas data dan atur strategi duplikasi sebelum menyimpan ke database.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href="/admin/films/impor"
                            className="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-bold text-text hover:bg-surface-2 transition shadow-xs"
                        >
                            <ArrowLeft className="size-4" />
                            <span>Unggah Ulang CSV</span>
                        </Link>
                    </div>
                </div>

                {/* ==================== STEP 1: PREVIEW TABLE ==================== */}
                {activeStep === 'preview' && (
                    <div className="space-y-6 animate-in fade-in">
                        {/* Summary & Strategy Bar */}
                        <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
                            <div className="rounded-2xl border border-border bg-surface p-4 text-center">
                                <p className="text-[10px] font-bold uppercase tracking-wider text-text-muted">Total Baris</p>
                                <p className="text-2xl font-black text-text mt-1">{totalRows}</p>
                            </div>
                            <div className="rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-4 text-center">
                                <p className="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Siap Ditambah</p>
                                <p className="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{stats.valid || 0}</p>
                            </div>
                            <div className="rounded-2xl border border-amber-500/20 bg-amber-500/5 p-4 text-center">
                                <p className="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Duplikat Terdeteksi</p>
                                <p className="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{stats.duplikat || 0}</p>
                            </div>
                            <div className="rounded-2xl border border-rose-500/20 bg-rose-500/5 p-4 text-center">
                                <p className="text-[10px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Data Tidak Valid</p>
                                <p className="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{stats.error || 0}</p>
                            </div>
                            <div className="rounded-2xl border border-sky-500/20 bg-sky-500/5 p-4 text-center col-span-2 sm:col-span-1">
                                <p className="text-[10px] font-bold uppercase tracking-wider text-sky-600 dark:text-sky-400">Poster Cocok</p>
                                <p className="text-2xl font-black text-sky-600 dark:text-sky-400 mt-1">{stats.posters_count || 0}</p>
                            </div>
                        </div>

                        {/* Strategy & Filter Controls */}
                        <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            {/* Duplicate Strategy Option */}
                            <div className="space-y-1">
                                <label htmlFor="duplicate-strategy-select" className="block text-xs font-bold text-text">
                                    Jika Ditemukan Judul & Tahun yang Sama:
                                </label>
                                <select
                                    id="duplicate-strategy-select"
                                    value={duplicateStrategy}
                                    onChange={(e) => setDuplicateStrategy(e.target.value)}
                                    className="min-h-[40px] rounded-xl border border-border bg-surface-2 px-3 text-xs font-semibold text-text focus:border-amber-500 focus:outline-none"
                                >
                                    <option value="skip">Lewati (Pertahankan data film yang sudah ada di bioskop)</option>
                                    <option value="update">Perbarui / Timpa (Ubah harga, genre, & deskripsi dengan data CSV baru)</option>
                                </select>
                            </div>

                            {/* Filter Status Tabs */}
                            <div className="flex flex-wrap items-center gap-1.5 p-1 rounded-xl bg-surface-2 border border-border text-xs font-semibold">
                                <button
                                    type="button"
                                    onClick={() => setFilterStatus('all')}
                                    className={`px-3 py-1.5 rounded-lg transition text-[11px] ${
                                        filterStatus === 'all' ? 'bg-surface text-text font-bold shadow-xs' : 'text-text-muted hover:text-text'
                                    }`}
                                >
                                    Semua ({totalRows})
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setFilterStatus('valid')}
                                    className={`px-3 py-1.5 rounded-lg transition text-[11px] ${
                                        filterStatus === 'valid' ? 'bg-surface text-emerald-600 dark:text-emerald-400 font-bold shadow-xs' : 'text-text-muted hover:text-text'
                                    }`}
                                >
                                    Baru ({stats.valid || 0})
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setFilterStatus('duplikat')}
                                    className={`px-3 py-1.5 rounded-lg transition text-[11px] ${
                                        filterStatus === 'duplikat' ? 'bg-surface text-amber-600 dark:text-amber-400 font-bold shadow-xs' : 'text-text-muted hover:text-text'
                                    }`}
                                >
                                    Duplikat ({stats.duplikat || 0})
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setFilterStatus('error')}
                                    className={`px-3 py-1.5 rounded-lg transition text-[11px] ${
                                        filterStatus === 'error' ? 'bg-surface text-rose-600 dark:text-rose-400 font-bold shadow-xs' : 'text-text-muted hover:text-text'
                                    }`}
                                >
                                    Eror ({stats.error || 0})
                                </button>
                            </div>
                        </div>

                        {/* Interactive Rows Table */}
                        <div className="rounded-2xl border border-border bg-surface overflow-hidden shadow-xs">
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs text-text border-collapse">
                                    <thead className="border-b border-border bg-surface-2/60 text-[11px] font-bold uppercase tracking-wider text-text-muted">
                                        <tr>
                                            <th className="py-3 px-3 text-center w-12">Impor</th>
                                            <th className="py-3 px-3">Status</th>
                                            <th className="py-3 px-3 min-w-[200px]">Judul Film</th>
                                            <th className="py-3 px-3 w-20">Tahun</th>
                                            <th className="py-3 px-3 min-w-[180px]">Genre</th>
                                            <th className="py-3 px-3 w-20">Durasi</th>
                                            <th className="py-3 px-3 w-28">Harga Tiket</th>
                                            <th className="py-3 px-3 text-center w-16">Poster</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border">
                                        {filteredRows.map((row, idx) => {
                                            const excluded = isExcluded(idx);
                                            return (
                                                <tr
                                                    key={idx}
                                                    className={`transition ${
                                                        excluded
                                                            ? 'opacity-40 bg-surface-2/30'
                                                            : 'hover:bg-surface-2/40'
                                                    }`}
                                                >
                                                    {/* Checkbox */}
                                                    <td className="py-3 px-3 text-center">
                                                        <input
                                                            type="checkbox"
                                                            checked={!excluded}
                                                            onChange={() => toggleRow(idx)}
                                                            className="size-4 rounded border-border bg-surface-2 text-amber-500 focus:ring-amber-500 cursor-pointer"
                                                        />
                                                    </td>

                                                    {/* Status Badge */}
                                                    <td className="py-3 px-3">
                                                        <span
                                                            className={`inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ${
                                                                row.status === 'baru' || row.status === 'valid'
                                                                    ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30'
                                                                    : row.status === 'duplikat' || row.status === 'duplicate'
                                                                    ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30'
                                                                    : 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30'
                                                            }`}
                                                        >
                                                            {row.status === 'baru' || row.status === 'valid'
                                                                ? 'Valid'
                                                                : row.status === 'duplikat' || row.status === 'duplicate'
                                                                ? 'Duplikat'
                                                                : 'Masalah'}
                                                        </span>
                                                        {row.status_message && (
                                                            <p className="text-[10px] text-text-muted mt-0.5 max-w-[160px] truncate" title={row.status_message}>
                                                                {row.status_message}
                                                            </p>
                                                        )}
                                                    </td>

                                                    {/* Title */}
                                                    <td className="py-3 px-3">
                                                        <input
                                                            type="text"
                                                            value={row.title || ''}
                                                            onChange={(e) => updateRowField(idx, 'title', e.target.value)}
                                                            className="w-full h-8 rounded-lg border border-border bg-surface-2 px-2 text-xs font-bold text-text focus:border-amber-500 focus:outline-none"
                                                        />
                                                    </td>

                                                    {/* Year */}
                                                    <td className="py-3 px-3">
                                                        <input
                                                            type="text"
                                                            value={row.release_year || ''}
                                                            onChange={(e) => updateRowField(idx, 'release_year', e.target.value)}
                                                            className="w-full h-8 rounded-lg border border-border bg-surface-2 px-2 text-xs font-semibold text-text focus:border-amber-500 focus:outline-none"
                                                        />
                                                    </td>

                                                    {/* Genre */}
                                                    <td className="py-3 px-3 space-y-1">
                                                        <input
                                                            type="text"
                                                            value={row.genre || ''}
                                                            onChange={(e) => updateRowField(idx, 'genre', e.target.value)}
                                                            className="w-full h-8 rounded-lg border border-border bg-surface-2 px-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                                        />
                                                        {/* Unknown genres suggestion tags */}
                                                        {row.new_genres && row.new_genres.length > 0 && (
                                                            <div className="flex flex-wrap gap-1">
                                                                {row.new_genres.map((ng) => (
                                                                    <span
                                                                        key={ng.name}
                                                                        className="inline-flex items-center gap-1 rounded bg-amber-500/15 px-1.5 py-0.5 text-[9px] font-bold text-amber-600 dark:text-amber-400"
                                                                    >
                                                                        <span>{ng.name}</span>
                                                                        <button
                                                                            type="button"
                                                                            onClick={() => removeGenre(idx, ng.name)}
                                                                            className="hover:text-rose-500"
                                                                            title="Hapus genre ini"
                                                                        >
                                                                            <X className="size-2.5" />
                                                                        </button>
                                                                    </span>
                                                                ))}
                                                            </div>
                                                        )}
                                                    </td>

                                                    {/* Duration */}
                                                    <td className="py-3 px-3">
                                                        <input
                                                            type="number"
                                                            value={row.duration_minutes || ''}
                                                            onChange={(e) => updateRowField(idx, 'duration_minutes', e.target.value)}
                                                            className="w-full h-8 rounded-lg border border-border bg-surface-2 px-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                                        />
                                                    </td>

                                                    {/* Price */}
                                                    <td className="py-3 px-3">
                                                        <input
                                                            type="number"
                                                            value={row.ticket_price || ''}
                                                            onChange={(e) => updateRowField(idx, 'ticket_price', e.target.value)}
                                                            className="w-full h-8 rounded-lg border border-border bg-surface-2 px-2 text-xs font-semibold text-text focus:border-amber-500 focus:outline-none"
                                                        />
                                                    </td>

                                                    {/* Poster Thumbnail */}
                                                    <td className="py-3 px-3 text-center">
                                                        {row.matched_poster ? (
                                                            <span className="inline-flex size-7 items-center justify-center rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-500 mx-auto" title="Poster Cocok">
                                                                <Image className="size-4" />
                                                            </span>
                                                        ) : (
                                                            <span className="inline-flex size-7 items-center justify-center rounded-lg bg-surface-2 text-text-muted mx-auto" title="Belum ada poster">
                                                                -
                                                            </span>
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {/* Sticky Bottom Action Bar */}
                        <div className="sticky bottom-4 z-20 rounded-2xl border border-amber-500/40 bg-surface/95 backdrop-blur p-4 sm:p-5 shadow-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <p className="text-sm font-bold text-text">
                                    <span className="text-amber-500">{includedCount}</span> dari {totalRows} baris film siap diimpor
                                </p>
                                <p className="text-xs text-text-muted mt-0.5">
                                    Data akan diproses dalam potongan 25 baris untuk memastikan kecepatan dan stabilitas server.
                                </p>
                            </div>

                            <div className="flex items-center gap-3">
                                <Link
                                    href="/admin/films/impor"
                                    className="min-h-[44px] px-4 rounded-xl border border-border bg-surface text-xs font-semibold text-text hover:bg-surface-2 transition flex items-center justify-center"
                                >
                                    Batal
                                </Link>
                                <button
                                    type="button"
                                    onClick={() => startChunkedImport(false)}
                                    disabled={includedCount === 0}
                                    className="min-h-[44px] px-6 rounded-xl bg-amber-500 text-xs font-bold text-slate-950 hover:bg-amber-400 disabled:opacity-40 transition shadow-sm flex items-center justify-center gap-2"
                                >
                                    <Play className="size-4" />
                                    <span>Mulai Impor Sekarang ({includedCount} Film)</span>
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                {/* ==================== STEP 2: CHUNKED PROGRESS ==================== */}
                {activeStep === 'importing' && (
                    <div className="rounded-2xl border border-border bg-surface p-8 shadow-xs space-y-6 max-w-xl mx-auto text-center animate-in fade-in">
                        <div className="size-16 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-500">
                            <RefreshCw className="size-8 animate-spin" />
                        </div>

                        <div>
                            <h2 className="text-lg font-black text-text">Sedang Memproses Impor Film</h2>
                            <p className="text-xs text-text-muted mt-1">{statusMessage}</p>
                        </div>

                        {/* Progress Bar */}
                        <div className="space-y-1.5">
                            <div className="h-3 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                                <div
                                    className="h-full rounded-full bg-amber-500 transition-all duration-300"
                                    style={{ width: `${progressPercent}%` }}
                                />
                            </div>
                            <div className="flex justify-between text-[11px] font-bold text-text-muted font-mono">
                                <span>{processedCount} / {totalRows} baris</span>
                                <span>{progressPercent}%</span>
                            </div>
                        </div>

                        {/* Stats grid */}
                        <div className="grid grid-cols-3 gap-3 pt-2">
                            <div className="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-3 text-center">
                                <p className="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Diimpor</p>
                                <p className="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">{importedCount}</p>
                            </div>
                            <div className="rounded-xl border border-amber-500/20 bg-amber-500/5 p-3 text-center">
                                <p className="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Dilewati</p>
                                <p className="text-xl font-black text-amber-600 dark:text-amber-400 mt-0.5">{skippedCount}</p>
                            </div>
                            <div className="rounded-xl border border-rose-500/20 bg-rose-500/5 p-3 text-center">
                                <p className="text-[10px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Gagal</p>
                                <p className="text-xl font-black text-rose-600 dark:text-rose-400 mt-0.5">{failedCount}</p>
                            </div>
                        </div>

                        {/* Error Alert */}
                        {errorMessage && (
                            <div className="p-4 rounded-xl border border-rose-500/40 bg-rose-500/10 text-xs text-rose-600 dark:text-rose-400 space-y-3">
                                <p className="font-bold">{errorMessage}</p>
                                <button
                                    type="button"
                                    onClick={() => startChunkedImport(true)}
                                    className="min-h-[38px] px-4 rounded-xl bg-amber-500 text-xs font-bold text-slate-950 hover:bg-amber-400 transition"
                                >
                                    Coba Lanjutkan Lagi
                                </button>
                            </div>
                        )}
                    </div>
                )}

                {/* ==================== STEP 3: COMPLETED ==================== */}
                {activeStep === 'completed' && (
                    <div className="rounded-2xl border border-emerald-500/30 bg-surface p-8 shadow-xs space-y-6 max-w-xl mx-auto text-center animate-in zoom-in-95">
                        <div className="size-16 mx-auto rounded-2xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-500">
                            <CheckCircle2 className="size-8" />
                        </div>

                        <div>
                            <h2 className="text-xl font-black text-text">Impor Film Selesai!</h2>
                            <p className="text-xs text-text-muted mt-1 leading-relaxed">
                                Seluruh berkas film dari <strong>{filename}</strong> telah selesai diproses ke katalog bioskop.
                            </p>
                        </div>

                        {/* Outcomes Grid */}
                        <div className="grid grid-cols-3 gap-3">
                            <div className="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-3 text-center">
                                <p className="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Berhasil</p>
                                <p className="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">{importedCount}</p>
                            </div>
                            <div className="rounded-xl border border-amber-500/20 bg-amber-500/5 p-3 text-center">
                                <p className="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Dilewati</p>
                                <p className="text-2xl font-black text-amber-600 dark:text-amber-400 mt-0.5">{skippedCount}</p>
                            </div>
                            <div className="rounded-xl border border-rose-500/20 bg-rose-500/5 p-3 text-center">
                                <p className="text-[10px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Gagal</p>
                                <p className="text-2xl font-black text-rose-600 dark:text-rose-400 mt-0.5">{failedCount}</p>
                            </div>
                        </div>

                        {/* Next Steps Buttons */}
                        <div className="pt-2 flex flex-col sm:flex-row gap-3 justify-center">
                            <Link
                                href="/admin/films"
                                className="min-h-[46px] px-6 rounded-xl bg-amber-500 text-xs font-bold text-slate-950 hover:bg-amber-400 transition shadow-sm flex items-center justify-center gap-2"
                            >
                                <Film className="size-4" />
                                <span>Buka Katalog Film</span>
                            </Link>

                            {totalPosters > 0 && (
                                <Link
                                    href="/admin/films/posters"
                                    className="min-h-[46px] px-5 rounded-xl border border-border bg-surface text-xs font-bold text-text hover:bg-surface-2 transition flex items-center justify-center gap-2"
                                >
                                    <Image className="size-4 text-amber-500" />
                                    <span>Pasangkan Poster ({totalPosters})</span>
                                </Link>
                            )}

                            <Link
                                href="/admin/films/impor"
                                className="min-h-[46px] px-4 rounded-xl border border-border bg-surface text-xs font-semibold text-text-muted hover:text-text hover:bg-surface-2 transition flex items-center justify-center"
                            >
                                Impor Berkas Lain
                            </Link>
                        </div>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
