import { useState, useRef } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import {
    Image,
    Upload,
    FileArchive,
    CheckCircle2,
    AlertCircle,
    XCircle,
    Trash2,
    RefreshCw,
    ExternalLink,
    Eye,
    ArrowLeft,
    Check,
    X,
    Sparkles,
    AlertTriangle,
    Layers,
} from 'lucide-react';

export default function Posters({
    filmsWithoutPoster = [],
    unmatchedPosters: initialUnmatched = [],
    filmOptions = {},
}) {
    const [unmatchedList, setUnmatchedList] = useState(initialUnmatched);
    const [selectedFilmMatches, setSelectedFilmMatches] = useState({}); // { filename: film_id }
    const [selectedForBatch, setSelectedForBatch] = useState([]); // array of filenames

    // Upload & processing state
    const [uploadMode, setUploadMode] = useState('idle'); // 'idle' | 'processing' | 'done' | 'error'
    const [imageFiles, setImageFiles] = useState([]);
    const [zipFile, setZipFile] = useState(null);
    const [processStats, setProcessStats] = useState({ total: 0, current: 0, matched: 0, unmatched: 0, failed: 0 });
    const [statusMessage, setStatusMessage] = useState('');
    const [activityResults, setActivityResults] = useState([]);
    const fileInputRef = useRef(null);
    const zipInputRef = useRef(null);

    // Lightbox / Spectate modal
    const [spectateImage, setSpectateImage] = useState(null);

    // Storage cleanup modal
    const [cleanupModalOpen, setCleanupModalOpen] = useState(false);
    const [isCleaningStorage, setIsCleaningStorage] = useState(false);
    const [cleanupPreview, setCleanupPreview] = useState(null);
    const [isExecutingQuarantine, setIsExecutingQuarantine] = useState(false);
    const [isPurgingQuarantine, setIsPurgingQuarantine] = useState(false);
    const [alertToast, setAlertToast] = useState(null);

    // Batch assignment modal
    const [batchAssignModalOpen, setBatchAssignModalOpen] = useState(false);
    const [isSubmittingBatch, setIsSubmittingBatch] = useState(false);

    const showToast = (message, type = 'success') => {
        setAlertToast({ message, type });
        setTimeout(() => setAlertToast(null), 4000);
    };

    const formatBytes = (bytes) => {
        if (!bytes) return '0 B';
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return bytes + ' B';
    };

    // Open Storage Cleanup Preview
    const openCleanupPreview = async () => {
        setIsCleaningStorage(true);
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('/admin/films/posters/cleanup-storage?dry_run=1', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await res.json();
            if (res.ok && data.success) {
                setCleanupPreview(data);
                setCleanupModalOpen(true);
            } else {
                showToast(data.message || 'Gagal memuat pratinjau pembersihan.', 'error');
            }
        } catch (e) {
            showToast('Koneksi terputus: ' + e.message, 'error');
        } finally {
            setIsCleaningStorage(false);
        }
    };

    // Execute Quarantine
    const executeQuarantine = async () => {
        setIsExecutingQuarantine(true);
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('/admin/films/posters/cleanup-storage', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await res.json();
            if (res.ok && data.success) {
                setCleanupModalOpen(false);
                showToast(data.message, 'success');
            } else {
                showToast(data.message || 'Gagal memindahkan berkas ke karantina.', 'error');
            }
        } catch (e) {
            showToast('Koneksi terputus: ' + e.message, 'error');
        } finally {
            setIsExecutingQuarantine(false);
        }
    };

    // Empty Quarantine
    const emptyQuarantineNow = async () => {
        setIsPurgingQuarantine(true);
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('/admin/films/posters/empty-quarantine', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await res.json();
            if (res.ok && data.success) {
                setCleanupModalOpen(false);
                showToast(data.message, 'success');
            } else {
                showToast(data.message || 'Gagal mengosongkan karantina.', 'error');
            }
        } catch (e) {
            showToast('Koneksi terputus: ' + e.message, 'error');
        } finally {
            setIsPurgingQuarantine(false);
        }
    };

    // Clear Selected Upload Files
    const clearUploadInputs = () => {
        setImageFiles([]);
        setZipFile(null);
        setUploadMode('idle');
        setProcessStats({ total: 0, current: 0, matched: 0, unmatched: 0, failed: 0 });
        setActivityResults([]);
        if (fileInputRef.current) fileInputRef.current.value = '';
        if (zipInputRef.current) zipInputRef.current.value = '';
    };

    // Process Uploaded Files
    const startUploadProcess = async (e) => {
        e.preventDefault();
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        if (imageFiles.length > 0) {
            // Process individual images sequentially
            setUploadMode('processing');
            const total = imageFiles.length;
            setProcessStats({ total, current: 0, matched: 0, unmatched: 0, failed: 0 });
            const results = [];

            let matchedCount = 0;
            let unmatchedCount = 0;
            let failedCount = 0;

            for (let i = 0; i < total; i++) {
                const file = imageFiles[i];
                setStatusMessage(`Memproses berkas ${i + 1} dari ${total}: ${file.name}...`);

                const formData = new FormData();
                formData.append('image', file);

                try {
                    const res = await fetch('/admin/films/posters/process-single', {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrfToken || '',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                    });
                    const data = await res.json();

                    if (data.status === 'matched') matchedCount++;
                    else if (data.status === 'unmatched') unmatchedCount++;
                    else failedCount++;

                    results.unshift(data);
                    setActivityResults([...results]);
                } catch (err) {
                    failedCount++;
                    results.unshift({
                        filename: file.name,
                        status: 'failed',
                        message: 'Gagal mengunggah berkas: ' + err.message,
                    });
                    setActivityResults([...results]);
                }

                setProcessStats({
                    total,
                    current: i + 1,
                    matched: matchedCount,
                    unmatched: unmatchedCount,
                    failed: failedCount,
                });
            }

            setUploadMode('done');
            setStatusMessage(`Selesai! ${matchedCount} poster cocok otomatis, ${unmatchedCount} masuk antrean manual, ${failedCount} gagal.`);
        } else if (zipFile) {
            // Process ZIP
            setUploadMode('processing');
            setStatusMessage('Mengunggah dan mengekstrak berkas arsip ZIP...');

            const formData = new FormData();
            formData.append('zip_file', zipFile);

            try {
                const res = await fetch('/admin/films/posters/upload-zip', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const zipRes = await res.json();
                if (!res.ok || !zipRes.success) {
                    setUploadMode('error');
                    setStatusMessage(zipRes.message || 'Gagal mengekstrak berkas ZIP.');
                    return;
                }

                const batchId = zipRes.batch_id;
                const files = zipRes.files || [];
                const total = files.length;
                setProcessStats({ total, current: 0, matched: 0, unmatched: 0, failed: 0 });

                let matchedCount = 0;
                let unmatchedCount = 0;
                let failedCount = 0;
                const results = [];

                for (let i = 0; i < total; i++) {
                    const item = files[i];
                    setStatusMessage(`Memproses poster ${i + 1} dari ${total}: ${item.filename}...`);

                    try {
                        const itemRes = await fetch('/admin/films/posters/process-batch-item', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': csrfToken || '',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                batch_id: batchId,
                                filename: item.filename,
                                staged_path: item.staged_path,
                            }),
                        });
                        const itemData = await itemRes.json();

                        if (itemData.status === 'matched') matchedCount++;
                        else if (itemData.status === 'unmatched') unmatchedCount++;
                        else failedCount++;

                        results.unshift(itemData);
                        setActivityResults([...results]);
                    } catch (err) {
                        failedCount++;
                        results.unshift({
                            filename: item.filename,
                            status: 'failed',
                            message: 'Gagal memproses item: ' + err.message,
                        });
                        setActivityResults([...results]);
                    }

                    setProcessStats({
                        total,
                        current: i + 1,
                        matched: matchedCount,
                        unmatched: unmatchedCount,
                        failed: failedCount,
                    });
                }

                // Cleanup batch folder
                await fetch('/admin/films/posters/cleanup-batch', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ batch_id: batchId }),
                });

                setUploadMode('done');
                setStatusMessage(`Selesai! ${matchedCount} poster cocok otomatis, ${unmatchedCount} masuk antrean manual.`);
            } catch (err) {
                setUploadMode('error');
                setStatusMessage('Terjadi kesalahan saat memproses arsip ZIP: ' + err.message);
            }
        }
    };

    // Assign single poster to a film
    const handleAssignPoster = async (filename) => {
        const filmId = selectedFilmMatches[filename];
        if (!filmId) {
            showToast('Pilih judul film tujuan terlebih dahulu.', 'error');
            return;
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('/admin/films/posters/assign', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    filename,
                    film_id: filmId,
                }),
            });
            const data = await res.json();
            if (res.ok && data.success) {
                showToast(data.message || 'Poster berhasil dipasangkan!', 'success');
                // Remove from unmatched list
                setUnmatchedList((prev) => prev.filter((p) => p.filename !== filename));
                setSelectedForBatch((prev) => prev.filter((fn) => fn !== filename));
            } else {
                showToast(data.message || 'Gagal memasangkan poster.', 'error');
            }
        } catch (e) {
            showToast('Kesalahan jaringan: ' + e.message, 'error');
        }
    };

    // Delete single unmatched poster
    const handleDeleteUnmatched = async (filename) => {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch(`/admin/films/posters/${encodeURIComponent(filename)}`, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await res.json();
            if (res.ok && data.success) {
                showToast('Poster berhasil dihapus dari antrean.', 'success');
                setUnmatchedList((prev) => prev.filter((p) => p.filename !== filename));
                setSelectedForBatch((prev) => prev.filter((fn) => fn !== filename));
            } else {
                showToast(data.message || 'Gagal menghapus poster.', 'error');
            }
        } catch (e) {
            showToast('Kesalahan jaringan: ' + e.message, 'error');
        }
    };

    // Batch assignment
    const handleBatchAssign = async () => {
        const matchesPayload = {};
        for (const fn of selectedForBatch) {
            if (selectedFilmMatches[fn]) {
                matchesPayload[fn] = selectedFilmMatches[fn];
            }
        }

        if (Object.keys(matchesPayload).length === 0) {
            showToast('Pilih judul film untuk poster yang dicentang terlebih dahulu.', 'error');
            return;
        }

        setIsSubmittingBatch(true);
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('/admin/films/posters/assign-batch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    matches: matchesPayload,
                }),
            });
            const data = await res.json();
            setIsSubmittingBatch(false);
            setBatchAssignModalOpen(false);

            if (res.ok && data.success) {
                showToast(data.message || 'Pemasangan massal berhasil!', 'success');
                const matchedFilenames = Object.keys(matchesPayload);
                setUnmatchedList((prev) => prev.filter((p) => !matchedFilenames.includes(p.filename)));
                setSelectedForBatch((prev) => prev.filter((fn) => !matchedFilenames.includes(fn)));
            } else {
                showToast(data.message || 'Gagal memproses pemasangan massal.', 'error');
            }
        } catch (e) {
            setIsSubmittingBatch(false);
            showToast('Kesalahan jaringan: ' + e.message, 'error');
        }
    };

    // Toggle select all
    const toggleSelectAll = () => {
        if (selectedForBatch.length === unmatchedList.length) {
            setSelectedForBatch([]);
        } else {
            setSelectedForBatch(unmatchedList.map((p) => p.filename));
        }
    };

    const toggleSelectPoster = (filename) => {
        setSelectedForBatch((prev) =>
            prev.includes(filename) ? prev.filter((fn) => fn !== filename) : [...prev, filename]
        );
    };

    const progressPercent = processStats.total > 0
        ? Math.round((processStats.current / processStats.total) * 100)
        : 0;

    return (
        <AdminLayout title="Poster Massal">
            <Head title="Poster Massal · Admin SynemaX" />

            {/* Alert Toast Notification */}
            {alertToast && (
                <div className={`fixed top-4 right-4 z-50 flex items-center gap-2.5 rounded-2xl px-4 py-3 shadow-2xl border text-xs font-bold animate-in fade-in slide-in-from-top duration-200 ${
                    alertToast.type === 'success'
                        ? 'border-emerald-500/40 bg-emerald-950/90 text-emerald-300'
                        : 'border-rose-500/40 bg-rose-950/90 text-rose-300'
                }`}>
                    {alertToast.type === 'success' ? <CheckCircle2 className="size-4 text-emerald-400" /> : <AlertCircle className="size-4 text-rose-400" />}
                    <span>{alertToast.message}</span>
                </div>
            )}

            <div className="space-y-6">
                {/* Header Section */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-text-muted mb-1">
                            <Link href="/admin/films" className="hover:text-amber-500 transition-colors">Katalog</Link>
                            <span>/</span>
                            <span className="text-text">Poster Massal</span>
                        </div>
                        <h1 className="text-2xl font-black text-text tracking-tight">Poster Massal</h1>
                        <p className="text-xs text-text-muted mt-1">
                            Unggah banyak gambar atau berkas ZIP sekaligus untuk dicocokkan otomatis ke poster film.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href="/admin/films"
                            className="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-bold text-text hover:bg-surface-2 transition shadow-xs"
                        >
                            <ArrowLeft className="size-4" />
                            <span>Kembali ke Katalog</span>
                        </Link>
                        <Link
                            href="/admin/films/impor"
                            className="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-slate-300 dark:border-white/20 bg-surface px-4 py-2 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition shadow-xs"
                        >
                            <Layers className="size-4" />
                            <span>Impor Film CSV</span>
                        </Link>
                    </div>
                </div>

                {/* 3 Metric Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    {/* Card 1: Missing Posters */}
                    <div className="rounded-2xl border border-border bg-surface p-4 shadow-xs">
                        <p className="text-xs text-text-muted font-medium">Film Belum Memiliki Poster</p>
                        <p className="text-2xl font-black text-text mt-1">{filmsWithoutPoster.length}</p>
                        <span className="text-[10px] text-text-muted">Film di katalog yang masih menggunakan placeholder polos</span>
                    </div>

                    {/* Card 2: Unmatched Posters */}
                    <div className="rounded-2xl border border-border bg-surface p-4 shadow-xs">
                        <p className="text-xs text-text-muted font-medium">Poster Belum Terpasang</p>
                        <p className={`text-2xl font-black mt-1 ${unmatchedList.length > 0 ? 'text-amber-500' : 'text-text'}`}>
                            {unmatchedList.length}
                        </p>
                        <span className="text-[10px] text-text-muted">Gambar yang menunggu dipasangkan secara manual</span>
                    </div>

                    {/* Card 3: Storage Cleanup */}
                    <div className="rounded-2xl border border-border bg-surface p-4 shadow-xs flex flex-col justify-between">
                        <div>
                            <div className="flex items-center justify-between">
                                <p className="text-xs text-text-muted font-medium">Kebersihan Penyimpanan</p>
                                <Link href="/admin/films?trash=1" className="text-[11px] font-bold text-amber-500 hover:underline">
                                    Kotak Sampah &rarr;
                                </Link>
                            </div>
                            <p className="text-xs font-semibold text-text mt-1">Sisa Upload & Berkas Yatim</p>
                            <p className="text-[10px] text-text-muted mt-0.5">Bersihkan folder sementara hasil upload & poster tak terpakai agar memori tetap lega.</p>
                        </div>
                        <div className="pt-2">
                            <button
                                type="button"
                                onClick={openCleanupPreview}
                                disabled={isCleaningStorage}
                                className="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border border-border bg-surface-2 hover:bg-surface-2/80 px-3 py-1.5 text-xs font-bold text-text transition disabled:opacity-50"
                            >
                                <Trash2 className={`size-3.5 text-amber-500 ${isCleaningStorage ? 'animate-spin' : ''}`} />
                                <span>{isCleaningStorage ? 'Memeriksa...' : 'Bersihkan Sampah Berkas'}</span>
                            </button>
                        </div>
                    </div>
                </div>

                {/* Bulk Upload Form */}
                <div className="rounded-2xl border border-border bg-surface p-6 shadow-xs space-y-6">
                    <div>
                        <h2 className="text-base font-bold text-text">Unggah Poster Sekaligus</h2>
                        <p className="text-xs text-text-muted mt-1 leading-relaxed">
                            Pilih beberapa file gambar sekaligus atau unggah satu file ZIP. Sistem otomatis memangkas ke rasio 2:3 (600 × 900 px), mengonversi ke WebP, membuang metadata EXIF, dan mencocokkan otomatis ke film berdasarkan nama berkas/slug. Poster yang tidak cocok langsung masuk ke antrean manual di bawah.
                        </p>
                    </div>

                    <form onSubmit={startUploadProcess} className="space-y-5">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {/* Images Multi-picker */}
                            <div className="space-y-1.5">
                                <label htmlFor="images-input" className="block text-xs font-bold text-text">
                                    Pilih Banyak Berkas Gambar (JPG, PNG, WebP)
                                </label>
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    id="images-input"
                                    accept="image/jpeg,image/png,image/webp"
                                    multiple
                                    disabled={uploadMode === 'processing'}
                                    onChange={(e) => {
                                        setImageFiles(Array.from(e.target.files || []));
                                        setZipFile(null);
                                        if (zipInputRef.current) zipInputRef.current.value = '';
                                    }}
                                    className="block w-full text-xs text-text file:mr-4 file:rounded-xl file:border-0 file:bg-surface-2 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-text hover:file:bg-surface-raised file:cursor-pointer border border-border rounded-xl p-2 bg-surface-2/40 disabled:opacity-50"
                                />
                                <p className="text-[11px] text-text-muted">Maksimal 5 MB per gambar. Anda bisa memilih puluhan gambar sekaligus.</p>

                                {imageFiles.length > 0 && (
                                    <div className="mt-2 flex items-center justify-between gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-xs text-amber-500">
                                        <div className="flex items-center gap-2 min-w-0">
                                            <Image className="size-4 shrink-0" />
                                            <span className="font-bold truncate">{imageFiles.length} berkas gambar dipilih</span>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={clearUploadInputs}
                                            className="text-[11px] underline hover:text-amber-400 font-semibold shrink-0"
                                        >
                                            Reset
                                        </button>
                                    </div>
                                )}
                            </div>

                            {/* ZIP Archive Picker */}
                            <div className="space-y-1.5">
                                <label htmlFor="zip-input" className="block text-xs font-bold text-text">
                                    Atau Unggah Satu Berkas Arsip ZIP
                                </label>
                                <input
                                    ref={zipInputRef}
                                    type="file"
                                    id="zip-input"
                                    accept=".zip"
                                    disabled={uploadMode === 'processing'}
                                    onChange={(e) => {
                                        const file = e.target.files?.[0] || null;
                                        setZipFile(file);
                                        setImageFiles([]);
                                        if (fileInputRef.current) fileInputRef.current.value = '';
                                    }}
                                    className="block w-full text-xs text-text file:mr-4 file:rounded-xl file:border-0 file:bg-surface-2 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-text hover:file:bg-surface-raised file:cursor-pointer border border-border rounded-xl p-2 bg-surface-2/40 disabled:opacity-50"
                                />
                                <p className="text-[11px] text-text-muted">Arsip ZIP berisi kumpulan berkas poster. Maksimal 50 MB.</p>

                                {zipFile && (
                                    <div className="mt-2 flex items-center justify-between gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-xs text-amber-500">
                                        <div className="flex items-center gap-2 min-w-0">
                                            <FileArchive className="size-4 shrink-0" />
                                            <span className="font-bold truncate">{zipFile.name} ({formatBytes(zipFile.size)})</span>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={clearUploadInputs}
                                            className="text-[11px] underline hover:text-amber-400 font-semibold shrink-0"
                                        >
                                            Reset
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Submit Button */}
                        <div className="pt-2 flex flex-wrap items-center gap-3">
                            <button
                                type="submit"
                                disabled={uploadMode === 'processing' || (imageFiles.length === 0 && !zipFile)}
                                className="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-amber-500 px-6 py-2.5 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {uploadMode === 'processing' ? (
                                    <>
                                        <RefreshCw className="size-4 animate-spin" />
                                        <span>Memproses ({processStats.current}/{processStats.total})...</span>
                                    </>
                                ) : (
                                    <>
                                        <Upload className="size-4" />
                                        <span>Proses & Cocokkan Poster Otomatis</span>
                                    </>
                                )}
                            </button>

                            {(imageFiles.length > 0 || zipFile) && (
                                <button
                                    type="button"
                                    onClick={clearUploadInputs}
                                    disabled={uploadMode === 'processing'}
                                    className="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-border bg-surface px-4 py-2.5 text-xs font-semibold text-text-muted hover:text-text hover:bg-surface-2 transition disabled:opacity-50"
                                >
                                    Batal Pilihan
                                </button>
                            )}
                        </div>
                    </form>

                    {/* LIVE PROGRESS & STREAM */}
                    {uploadMode !== 'idle' && (
                        <div className="mt-6 rounded-2xl border border-amber-500/30 bg-surface-2/40 p-5 sm:p-6 space-y-5 animate-in fade-in">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <div className="flex items-center gap-2">
                                        {uploadMode === 'processing' && (
                                            <span className="size-2.5 rounded-full bg-amber-500 animate-ping" />
                                        )}
                                        {uploadMode === 'done' && (
                                            <span className="size-2.5 rounded-full bg-emerald-500" />
                                        )}
                                        {uploadMode === 'error' && (
                                            <span className="size-2.5 rounded-full bg-rose-500" />
                                        )}
                                        <h3 className="text-sm font-bold text-text">
                                            {uploadMode === 'processing' ? 'Sedang Memproses Poster' : uploadMode === 'done' ? 'Pemrosesan Selesai' : 'Terjadi Kendala'}
                                        </h3>
                                    </div>
                                    <p className="text-xs text-text-muted mt-0.5">{statusMessage}</p>
                                </div>

                                <div className="text-3xl font-black text-amber-500 font-mono tracking-tight sm:text-right">
                                    {progressPercent}%
                                </div>
                            </div>

                            {/* Progress bar */}
                            <div className="h-2.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                                <div
                                    className={`h-full rounded-full transition-all duration-300 ${
                                        uploadMode === 'done' ? 'bg-emerald-500' : uploadMode === 'error' ? 'bg-rose-500' : 'bg-amber-500'
                                    }`}
                                    style={{ width: `${progressPercent}%` }}
                                />
                            </div>

                            {/* 4 Outcome Stat Chips */}
                            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div className="rounded-xl border border-border bg-surface p-3 text-center">
                                    <p className="text-[10px] font-bold uppercase tracking-wider text-text-muted">Total</p>
                                    <p className="text-xl font-black text-text mt-0.5">{processStats.total}</p>
                                </div>
                                <div className="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-3 text-center">
                                    <p className="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Cocok Otomatis</p>
                                    <p className="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">{processStats.matched}</p>
                                </div>
                                <div className="rounded-xl border border-amber-500/20 bg-amber-500/5 p-3 text-center">
                                    <p className="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Antrean Manual</p>
                                    <p className="text-xl font-black text-amber-600 dark:text-amber-400 mt-0.5">{processStats.unmatched}</p>
                                </div>
                                <div className="rounded-xl border border-rose-500/20 bg-rose-500/5 p-3 text-center">
                                    <p className="text-[10px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Gagal</p>
                                    <p className="text-xl font-black text-rose-600 dark:text-rose-400 mt-0.5">{processStats.failed}</p>
                                </div>
                            </div>

                            {/* Done actions */}
                            {uploadMode === 'done' && (
                                <div className="pt-2 flex items-center gap-3">
                                    <button
                                        type="button"
                                        onClick={() => router.reload({ only: ['unmatchedPosters', 'filmsWithoutPoster'] })}
                                        className="inline-flex min-h-[40px] items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition"
                                    >
                                        <RefreshCw className="size-4" />
                                        <span>Perbarui Antrean Manual</span>
                                    </button>
                                    <button
                                        type="button"
                                        onClick={clearUploadInputs}
                                        className="inline-flex min-h-[40px] items-center rounded-xl border border-border bg-surface px-4 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition"
                                    >
                                        Unggah Berkas Lain
                                    </button>
                                </div>
                            )}

                            {/* Activity stream */}
                            {activityResults.length > 0 && (
                                <div className="space-y-2 pt-2">
                                    <p className="text-xs font-bold text-text">Riwayat Pemrosesan ({activityResults.length})</p>
                                    <div className="max-h-60 overflow-y-auto divide-y divide-border rounded-xl border border-border bg-surface">
                                        {activityResults.map((item, idx) => (
                                            <div key={idx} className="p-3 flex items-center justify-between gap-3 text-xs">
                                                <div className="flex items-center gap-2.5 min-w-0">
                                                    {item.status === 'matched' ? (
                                                        <CheckCircle2 className="size-4 text-emerald-500 shrink-0" />
                                                    ) : item.status === 'unmatched' ? (
                                                        <AlertCircle className="size-4 text-amber-500 shrink-0" />
                                                    ) : (
                                                        <XCircle className="size-4 text-rose-500 shrink-0" />
                                                    )}
                                                    <div className="min-w-0">
                                                        <p className="font-bold text-text truncate">{item.filename}</p>
                                                        <p className={`text-[11px] truncate ${item.status === 'matched' ? 'text-emerald-500' : item.status === 'unmatched' ? 'text-amber-500' : 'text-rose-500'}`}>
                                                            {item.status === 'matched' ? `Dicocokkan ke: ${item.film_title}` : item.message}
                                                        </p>
                                                    </div>
                                                </div>

                                                {item.poster_url && (
                                                    <button
                                                        type="button"
                                                        onClick={() => setSpectateImage(item.poster_url)}
                                                        className="size-7 rounded-lg border border-border flex items-center justify-center text-text-muted hover:text-amber-500"
                                                        title="Lihat Gambar"
                                                    >
                                                        <Eye className="size-3.5" />
                                                    </button>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </div>

                {/* UNMATCHED POSTERS QUEUE (ANTREAN MANUAL) */}
                <div id="antrean-manual" className="rounded-2xl border border-border bg-surface p-6 shadow-xs space-y-4">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div className="flex items-center gap-2">
                                <h3 className="text-base font-bold text-text">
                                    Poster Belum Terpasang ({unmatchedList.length})
                                </h3>
                                {selectedForBatch.length > 0 && (
                                    <span className="rounded-full bg-amber-500/15 px-2.5 py-0.5 text-xs font-bold text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                        {selectedForBatch.length} dipilih
                                    </span>
                                )}
                            </div>
                            <p className="text-xs text-text-muted mt-0.5">
                                Pilih judul film pada dropdown di bawah poster untuk memasangkannya secara manual ke katalog bioskop.
                            </p>
                        </div>

                        {unmatchedList.length > 0 && (
                            <div className="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    onClick={toggleSelectAll}
                                    className="min-h-[36px] px-3 rounded-xl border border-border bg-surface text-xs font-semibold text-text hover:bg-surface-2 transition"
                                >
                                    {selectedForBatch.length === unmatchedList.length ? 'Batal Pilih Semua' : 'Pilih Semua'}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setBatchAssignModalOpen(true)}
                                    disabled={selectedForBatch.length === 0}
                                    className="min-h-[36px] px-4 rounded-xl bg-amber-500 text-xs font-bold text-slate-950 hover:bg-amber-400 disabled:opacity-50 transition"
                                >
                                    Pasangkan Terpilih ({selectedForBatch.length})
                                </button>
                            </div>
                        )}
                    </div>

                    {unmatchedList.length === 0 ? (
                        <div className="rounded-xl border border-border bg-surface-2/30 p-8 text-center text-xs text-text-muted space-y-2">
                            <CheckCircle2 className="size-8 text-emerald-500 mx-auto" />
                            <p className="font-bold text-text">Semua poster telah terpasang rapi</p>
                            <p>Tidak ada berkas poster antrean yang belum terpasang ke film katalog.</p>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            {unmatchedList.map((poster) => {
                                const isChecked = selectedForBatch.includes(poster.filename);
                                return (
                                    <div
                                        key={poster.filename}
                                        className={`rounded-2xl border transition overflow-hidden bg-surface flex flex-col justify-between ${
                                            isChecked ? 'border-amber-500 ring-2 ring-amber-500/30' : 'border-border'
                                        }`}
                                    >
                                        {/* Image Viewport */}
                                        <div className="relative aspect-[2/3] bg-black/60 overflow-hidden group">
                                            <img
                                                src={poster.url}
                                                alt={poster.filename}
                                                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                            />

                                            {/* Checkbox overlay top-left */}
                                            <div className="absolute top-2 left-2 z-10">
                                                <input
                                                    type="checkbox"
                                                    checked={isChecked}
                                                    onChange={() => toggleSelectPoster(poster.filename)}
                                                    className="size-5 rounded-lg border-border bg-surface text-amber-500 focus:ring-amber-500 cursor-pointer"
                                                />
                                            </div>

                                            {/* Preview button overlay top-right */}
                                            <button
                                                type="button"
                                                onClick={() => setSpectateImage(poster.url)}
                                                className="absolute top-2 right-2 size-8 rounded-xl bg-black/70 backdrop-blur text-white flex items-center justify-center hover:bg-black transition"
                                                title="Perbesar Poster"
                                            >
                                                <Eye className="size-4" />
                                            </button>

                                            {/* Warning if resolution is small */}
                                            {poster.is_small && (
                                                <div className="absolute bottom-2 inset-x-2 bg-amber-500/90 text-slate-950 font-bold text-[10px] py-1 px-2 rounded-lg text-center backdrop-blur">
                                                    Resolusi &lt; 300px
                                                </div>
                                            )}
                                        </div>

                                        {/* Details & Assignment Controls */}
                                        <div className="p-3.5 space-y-3">
                                            <div className="min-w-0">
                                                <p className="text-xs font-bold text-text truncate" title={poster.filename}>
                                                    {poster.filename}
                                                </p>
                                                <p className="text-[10px] text-text-muted mt-0.5">
                                                    {poster.width && poster.height ? `${poster.width}×${poster.height} px · ` : ''}{formatBytes(poster.size)}
                                                </p>
                                            </div>

                                            {/* Select film dropdown */}
                                            <div>
                                                <label htmlFor={`film_select_${poster.filename}`} className="sr-only">Pilih Film</label>
                                                <select
                                                    id={`film_select_${poster.filename}`}
                                                    value={selectedFilmMatches[poster.filename] || ''}
                                                    onChange={(e) => {
                                                        const val = e.target.value;
                                                        setSelectedFilmMatches((prev) => ({
                                                            ...prev,
                                                            [poster.filename]: val,
                                                        }));
                                                    }}
                                                    className="w-full min-h-[36px] rounded-xl border border-border bg-surface-2 px-2.5 text-[11px] font-semibold text-text focus:border-amber-500 focus:outline-none"
                                                >
                                                    <option value="">Pilih Film Tujuan...</option>
                                                    {Object.entries(filmOptions).map(([fId, label]) => (
                                                        <option key={fId} value={fId}>
                                                            {label}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>

                                            <div className="flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => handleAssignPoster(poster.filename)}
                                                    className="flex-1 min-h-[36px] rounded-xl bg-amber-500 text-xs font-bold text-slate-950 hover:bg-amber-400 transition"
                                                >
                                                    Pasangkan
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => handleDeleteUnmatched(poster.filename)}
                                                    className="size-9 rounded-xl border border-rose-500/30 bg-rose-500/10 text-rose-600 hover:bg-rose-500 hover:text-white transition flex items-center justify-center shrink-0"
                                                    title="Hapus poster dari antrean"
                                                >
                                                    <Trash2 className="size-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>

            {/* LIGHTBOX SPECTATE MODAL */}
            {spectateImage && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md animate-in fade-in"
                    onClick={() => setSpectateImage(null)}
                >
                    <div className="relative max-w-lg max-h-[85vh] rounded-2xl overflow-hidden border border-white/20 shadow-2xl">
                        <img
                            src={spectateImage}
                            alt="Pratinjau Poster"
                            className="max-h-[85vh] w-auto object-contain"
                        />
                        <button
                            type="button"
                            onClick={() => setSpectateImage(null)}
                            className="absolute top-3 right-3 size-9 rounded-xl bg-black/70 text-white flex items-center justify-center hover:bg-black transition"
                        >
                            <X className="size-5" />
                        </button>
                    </div>
                </div>
            )}

            {/* STORAGE CLEANUP PREVIEW MODAL */}
            {cleanupModalOpen && cleanupPreview && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
                    <div className="w-full max-w-lg rounded-2xl border border-border bg-surface p-5 shadow-2xl space-y-4">
                        <div className="flex items-center justify-between border-b border-border pb-3">
                            <div>
                                <h3 className="text-base font-bold text-text">Pratinjau Sampah Penyimpanan</h3>
                                <p className="text-xs text-text-muted mt-0.5">Pemeriksaan berkas yatim dan folder sementara</p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setCleanupModalOpen(false)}
                                className="size-7 rounded-lg flex items-center justify-center text-text-muted hover:text-text hover:bg-surface-2"
                            >
                                <X className="size-4" />
                            </button>
                        </div>

                        <div className="space-y-3">
                            <div className="rounded-xl border border-border bg-surface-2/60 p-3 flex items-center justify-between text-xs">
                                <div>
                                    <span className="font-bold text-text">
                                        {(cleanupPreview.total_orphan_count || 0) + (cleanupPreview.total_temp_count || 0)}
                                    </span>
                                    <span className="text-text-muted"> item ditemukan</span>
                                </div>
                                <div className="font-bold text-amber-500 font-mono">
                                    Potensi bebas: {cleanupPreview.freed_formatted}
                                </div>
                            </div>

                            {/* Orphan list */}
                            {cleanupPreview.orphan_files && cleanupPreview.orphan_files.length > 0 && (
                                <div className="space-y-1.5 max-h-48 overflow-y-auto pr-1 text-xs">
                                    <span className="text-[10px] font-bold uppercase tracking-wider text-text-muted">
                                        Berkas Yatim ({cleanupPreview.orphan_files.length}):
                                    </span>
                                    {cleanupPreview.orphan_files.slice(0, 50).map((item) => (
                                        <div key={item.path} className="flex items-center justify-between rounded-lg border border-border/60 bg-surface px-2.5 py-1.5">
                                            <div className="min-w-0 flex-1 truncate pr-2">
                                                <div className="font-semibold text-text truncate">{item.filename}</div>
                                                <div className="text-[10px] text-text-muted truncate">{item.reason}</div>
                                            </div>
                                            <div className="shrink-0 font-mono text-[11px] text-text-muted">{item.size_formatted}</div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            <p className="text-[11px] text-text-muted pt-1">
                                Berkas akan dipindahkan ke folder karantina dan otomatis terhapus permanen setelah 7 hari.
                            </p>
                        </div>

                        <div className="flex items-center justify-between pt-3 border-t border-border">
                            <button
                                type="button"
                                onClick={emptyQuarantineNow}
                                disabled={isPurgingQuarantine}
                                className="min-h-[38px] px-3 rounded-xl border border-rose-500/40 bg-rose-500/10 text-xs font-bold text-rose-600 hover:bg-rose-500 hover:text-white transition disabled:opacity-50"
                            >
                                {isPurgingQuarantine ? 'Mengosongkan...' : 'Kosongkan Karantina'}
                            </button>

                            <div className="flex items-center gap-2">
                                <button
                                    type="button"
                                    onClick={() => setCleanupModalOpen(false)}
                                    className="min-h-[38px] rounded-xl border border-border px-3.5 text-xs font-semibold text-text hover:bg-surface-2 transition"
                                >
                                    Tutup
                                </button>
                                {(cleanupPreview.total_orphan_count > 0 || cleanupPreview.total_temp_count > 0) && (
                                    <button
                                        type="button"
                                        onClick={executeQuarantine}
                                        disabled={isExecutingQuarantine}
                                        className="min-h-[38px] rounded-xl bg-amber-500 px-4 text-xs font-bold text-slate-950 hover:bg-amber-400 transition shadow-sm disabled:opacity-50"
                                    >
                                        {isExecutingQuarantine ? 'Memindahkan...' : 'Pindahkan ke Karantina'}
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* BATCH ASSIGN MODAL */}
            {batchAssignModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
                    <div className="w-full max-w-md rounded-2xl border border-border bg-surface p-5 shadow-2xl space-y-4">
                        <div className="flex items-center justify-between border-b border-border pb-3">
                            <h3 className="text-base font-bold text-text">Konfirmasi Pasangkan Massal</h3>
                            <button
                                type="button"
                                onClick={() => setBatchAssignModalOpen(false)}
                                className="size-7 rounded-lg flex items-center justify-center text-text-muted hover:bg-surface-2"
                            >
                                <X className="size-4" />
                            </button>
                        </div>

                        <p className="text-xs text-text-muted leading-relaxed">
                            Anda akan memasangkan {selectedForBatch.length} berkas poster terpilih ke film yang telah Anda tentukan. Pastikan judul film tujuan sudah dipilih.
                        </p>

                        <div className="flex items-center justify-end gap-2 pt-2 border-t border-border">
                            <button
                                type="button"
                                onClick={() => setBatchAssignModalOpen(false)}
                                className="min-h-[38px] px-4 rounded-xl border border-border bg-surface text-xs font-semibold text-text hover:bg-surface-2"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                onClick={handleBatchAssign}
                                disabled={isSubmittingBatch}
                                className="min-h-[38px] px-5 rounded-xl bg-amber-500 text-xs font-bold text-slate-950 hover:bg-amber-400 disabled:opacity-50 transition"
                            >
                                {isSubmittingBatch ? 'Memproses...' : 'Ya, Pasangkan Sekarang'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
