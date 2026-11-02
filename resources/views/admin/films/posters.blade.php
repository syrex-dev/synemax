@extends('layouts.admin')

@section('title', 'Poster Massal')

@section('content')
<div class="space-y-6">
    <x-admin.page-header
        eyebrow="Katalog"
        title="Poster Massal"
        subtitle="Unggah banyak gambar atau berkas ZIP sekaligus untuk dicocokkan otomatis ke poster film."
    >
        <div class="flex items-center gap-2">
            <a
                href="{{ route('admin.films.index') }}"
                class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-slate-300 dark:border-white/20 bg-surface px-4 py-2 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors shadow-xs"
            >
                <span>Kembali ke Katalog</span>
            </a>
            <a
                href="{{ route('admin.films.import.create') }}"
                class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-slate-300 dark:border-white/20 bg-surface px-4 py-2 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors shadow-xs"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12" />
                </svg>
                <span>Impor Film CSV</span>
            </a>
        </div>
    </x-admin.page-header>

    @if (session('status'))
        <div class="rounded-xl border border-success/30 bg-success/10 p-4 text-xs font-semibold text-success">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-xs font-semibold text-danger">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-xs text-danger space-y-1">
            <p class="font-bold">Terjadi kesalahan pada data yang diunggah:</p>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Ringkasan Metrik Poster --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="rounded-2xl border border-border bg-surface p-4 shadow-token">
            <p class="text-xs text-text-muted font-medium">Film Belum Memiliki Poster</p>
            <p class="text-2xl font-black text-text mt-1">{{ $filmsWithoutPoster->count() }}</p>
            <span class="text-[10px] text-text-muted">Film di katalog yang menggunakan placeholder polos</span>
        </div>
        <div class="rounded-2xl border border-border bg-surface p-4 shadow-token">
            <p class="text-xs text-text-muted font-medium">Poster Belum Terpasang</p>
            <p class="text-2xl font-black {{ count($unmatchedPosters) > 0 ? 'text-amber-500' : 'text-text' }} mt-1">
                {{ count($unmatchedPosters) }}
            </p>
            <span class="text-[10px] text-text-muted">Gambar yang menunggu dipasangkan secara manual</span>
        </div>
    </div>

    {{-- Form Unggah Banyak Berkas / ZIP dengan Indikator Progres Interaktif --}}
    <div
        class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-6"
        x-data="{
            imageFiles: [],
            zipFile: null,
            mode: 'idle', // 'idle' | 'uploading_zip' | 'processing' | 'done' | 'error'
            progressPercent: 0,
            currentIndex: 0,
            totalCount: 0,
            currentFileName: '',
            statusMessage: 'Pilih berkas gambar atau ZIP untuk memulai.',
            results: [],
            stats: { matched: 0, unmatched: 0, failed: 0 },

            handleImagesChange(e) {
                this.imageFiles = Array.from(e.target.files || []);
                if (this.imageFiles.length > 0) {
                    this.zipFile = null;
                    const zipInput = document.getElementById('zip_file');
                    if (zipInput) zipInput.value = '';
                }
                this.resetProgressState();
            },

            handleZipChange(e) {
                this.zipFile = e.target.files && e.target.files[0] ? e.target.files[0] : null;
                if (this.zipFile) {
                    this.imageFiles = [];
                    const imgInput = document.getElementById('images');
                    if (imgInput) imgInput.value = '';
                }
                this.resetProgressState();
            },

            resetProgressState() {
                this.mode = 'idle';
                this.progressPercent = 0;
                this.currentIndex = 0;
                this.totalCount = 0;
                this.currentFileName = '';
                this.results = [];
                this.stats = { matched: 0, unmatched: 0, failed: 0 };
            },

            clearAllInputs() {
                this.resetProgressState();
                this.imageFiles = [];
                this.zipFile = null;
                const imgInput = document.getElementById('images');
                if (imgInput) imgInput.value = '';
                const zipInput = document.getElementById('zip_file');
                if (zipInput) zipInput.value = '';
            },

            formatSize(bytes) {
                if (!bytes || bytes <= 0) return '0 B';
                const units = ['B', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(1024));
                return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + units[i];
            },

            get totalImageSize() {
                return this.imageFiles.reduce((acc, f) => acc + f.size, 0);
            },

            async startProcess() {
                if (this.imageFiles.length === 0 && !this.zipFile) {
                    alert('Pilih minimal satu berkas gambar atau berkas arsip ZIP terlebih dahulu.');
                    return;
                }

                if (this.zipFile) {
                    await this.processZipArchive();
                } else if (this.imageFiles.length > 0) {
                    await this.processImagesSequentially();
                }
            },

            async processImagesSequentially() {
                this.mode = 'processing';
                this.totalCount = this.imageFiles.length;
                this.currentIndex = 0;
                this.results = [];
                this.stats = { matched: 0, unmatched: 0, failed: 0 };
                this.progressPercent = 0;

                for (let i = 0; i < this.imageFiles.length; i++) {
                    const file = this.imageFiles[i];
                    this.currentIndex = i + 1;
                    this.currentFileName = file.name;
                    this.statusMessage = `Memproses poster ${this.currentIndex} dari ${this.totalCount} (${file.name})...`;

                    const fd = new FormData();
                    fd.append('image', file);
                    fd.append('_token', '{{ csrf_token() }}');

                    try {
                        const response = await fetch('{{ route('admin.films.posters.process_single') }}', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: fd
                        });

                        const resData = await response.json();

                        if (response.ok && resData.success) {
                            this.results.unshift(resData);
                            if (resData.status === 'matched') {
                                this.stats.matched++;
                            } else {
                                this.stats.unmatched++;
                            }
                        } else {
                            this.results.unshift({
                                filename: file.name,
                                status: 'failed',
                                message: resData.message || 'Gagal memproses gambar ini.'
                            });
                            this.stats.failed++;
                        }
                    } catch (err) {
                        this.results.unshift({
                            filename: file.name,
                            status: 'failed',
                            message: 'Gagal menghubungi server: ' + err.message
                        });
                        this.stats.failed++;
                    }

                    this.progressPercent = Math.round((this.currentIndex / this.totalCount) * 100);
                }

                this.mode = 'done';
                this.statusMessage = `Selesai! ${this.stats.matched} cocok otomatis, ${this.stats.unmatched} belum terpasang, ${this.stats.failed} gagal.`;
            },

            async processZipArchive() {
                this.mode = 'uploading_zip';
                this.statusMessage = `Mengunggah berkas arsip ZIP (${this.zipFile.name})...`;
                this.progressPercent = 10;
                this.results = [];
                this.stats = { matched: 0, unmatched: 0, failed: 0 };

                const fd = new FormData();
                fd.append('zip_file', this.zipFile);
                fd.append('_token', '{{ csrf_token() }}');

                let uploadRes;
                try {
                    const response = await fetch('{{ route('admin.films.posters.upload_zip') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: fd
                    });

                    uploadRes = await response.json();

                    if (!response.ok || !uploadRes.success) {
                        throw new Error(uploadRes.message || 'Gagal mengekstrak berkas ZIP.');
                    }
                } catch (err) {
                    this.mode = 'error';
                    this.statusMessage = 'Gagal memproses ZIP: ' + err.message;
                    return;
                }

                const batchId = uploadRes.batch_id;
                const files = uploadRes.files || [];
                this.totalCount = files.length;
                this.currentIndex = 0;
                this.mode = 'processing';
                this.progressPercent = 20;

                for (let i = 0; i < files.length; i++) {
                    const item = files[i];
                    this.currentIndex = i + 1;
                    this.currentFileName = item.filename;
                    this.statusMessage = `Memproses poster ${this.currentIndex} dari ${this.totalCount} (${item.filename})...`;

                    try {
                        const itemRes = await fetch('{{ route('admin.films.posters.process_batch_item') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                batch_id: batchId,
                                filename: item.filename,
                                staged_path: item.staged_path
                            })
                        });

                        const itemData = await itemRes.json();

                        if (itemRes.ok && itemData.success) {
                            this.results.unshift(itemData);
                            if (itemData.status === 'matched') {
                                this.stats.matched++;
                            } else {
                                this.stats.unmatched++;
                            }
                        } else {
                            this.results.unshift({
                                filename: item.filename,
                                status: 'failed',
                                message: itemData.message || 'Gagal memproses poster.'
                            });
                            this.stats.failed++;
                        }
                    } catch (err) {
                        this.results.unshift({
                            filename: item.filename,
                            status: 'failed',
                            message: 'Gagal memproses berkas: ' + err.message
                        });
                        this.stats.failed++;
                    }

                    // Map 20% -> 100%
                    this.progressPercent = 20 + Math.round((this.currentIndex / this.totalCount) * 80);
                }

                // Cleanup batch folder
                fetch('{{ route('admin.films.posters.cleanup_batch') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ batch_id: batchId })
                }).catch(() => {});

                this.mode = 'done';
                this.statusMessage = `Selesai! ${this.stats.matched} cocok otomatis, ${this.stats.unmatched} belum terpasang, ${this.stats.failed} gagal.`;
            }
        }"
    >
        <div>
            <h2 class="text-base font-bold text-text">Unggah Poster Sekaligus</h2>
            <p class="text-xs text-text-muted mt-1">
                Pilih beberapa file gambar sekaligus atau unggah satu file ZIP. Sistem otomatis memangkas ke rasio 2:3 (600 × 900 px), mengonversi ke WebP, membuang metadata EXIF, dan mencocokkan ke film berdasarkan nama berkas atau judul.
            </p>
        </div>

        <form
            action="{{ route('admin.films.posters.upload') }}"
            method="POST"
            enctype="multipart/form-data"
            class="space-y-5"
            @submit.prevent="startProcess()"
        >
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- Multi-file Picker --}}
                <div class="space-y-1.5">
                    <label for="images" class="block text-xs font-bold text-text">
                        Pilih Banyak Berkas Gambar (JPG, PNG, WebP)
                    </label>
                    <input
                        type="file"
                        id="images"
                        name="images[]"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                        :disabled="mode === 'processing' || mode === 'uploading_zip'"
                        @change="handleImagesChange($event)"
                        class="block w-full text-xs text-text file:mr-4 file:rounded-xl file:border-0 file:bg-surface-2 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-text hover:file:bg-slate-200 dark:hover:file:bg-white/10 file:cursor-pointer border border-border rounded-xl p-2 bg-surface-2/40 disabled:opacity-50"
                    >
                    <div class="flex items-center justify-between text-[11px] text-text-muted mt-1">
                        <span>Maksimal 5 MB per gambar. Anda bisa memilih puluhan gambar sekaligus.</span>
                    </div>

                    {{-- Selected Images Indicator --}}
                    <template x-if="imageFiles.length > 0">
                        <div class="mt-2 flex items-center justify-between gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-xs text-amber-500">
                            <div class="flex items-center gap-2 min-w-0">
                                <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="font-bold truncate" x-text="`${imageFiles.length} berkas gambar dipilih (${formatSize(totalImageSize)})`"></span>
                            </div>
                            <button
                                type="button"
                                @click="clearAllInputs()"
                                :disabled="mode === 'processing' || mode === 'uploading_zip'"
                                class="text-[11px] underline hover:text-amber-400 font-semibold shrink-0"
                            >
                                Ganti
                            </button>
                        </div>
                    </template>
                </div>

                {{-- ZIP Archive --}}
                <div class="space-y-1.5">
                    <label for="zip_file" class="block text-xs font-bold text-text">
                        Atau Unggah Satu Berkas Arsip ZIP
                    </label>
                    <input
                        type="file"
                        id="zip_file"
                        name="zip_file"
                        accept=".zip"
                        :disabled="mode === 'processing' || mode === 'uploading_zip'"
                        @change="handleZipChange($event)"
                        class="block w-full text-xs text-text file:mr-4 file:rounded-xl file:border-0 file:bg-surface-2 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-text hover:file:bg-slate-200 dark:hover:file:bg-white/10 file:cursor-pointer border border-border rounded-xl p-2 bg-surface-2/40 disabled:opacity-50"
                    >
                    <div class="flex items-center justify-between text-[11px] text-text-muted mt-1">
                        <span>Arsip ZIP berisi kumpulan berkas poster. Maksimal 50 MB.</span>
                    </div>

                    {{-- Selected ZIP Indicator --}}
                    <template x-if="zipFile">
                        <div class="mt-2 flex items-center justify-between gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-xs text-amber-500">
                            <div class="flex items-center gap-2 min-w-0">
                                <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                </svg>
                                <span class="font-bold truncate" x-text="`${zipFile.name} (${formatSize(zipFile.size)})`"></span>
                            </div>
                            <button
                                type="button"
                                @click="clearAllInputs()"
                                :disabled="mode === 'processing' || mode === 'uploading_zip'"
                                class="text-[11px] underline hover:text-amber-400 font-semibold shrink-0"
                            >
                                Ganti
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="pt-2 flex flex-wrap items-center gap-3">
                <button
                    type="submit"
                    :disabled="mode === 'processing' || mode === 'uploading_zip' || (imageFiles.length === 0 && !zipFile)"
                    class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-amber-500 px-6 py-2.5 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition-colors disabled:opacity-50 disabled:cursor-not-allowed w-full sm:w-auto"
                >
                    <template x-if="mode === 'processing' || mode === 'uploading_zip'">
                        <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <template x-if="mode !== 'processing' && mode !== 'uploading_zip'">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12" />
                        </svg>
                    </template>
                    <span x-text="mode === 'processing' ? `Memproses Poster (${currentIndex}/${totalCount})...` : (mode === 'uploading_zip' ? 'Mengunggah Arsip ZIP...' : 'Proses & Cocokkan Poster Otomatis')"></span>
                </button>

                <template x-if="imageFiles.length > 0 || zipFile">
                    <button
                        type="button"
                        @click="clearAllInputs()"
                        :disabled="mode === 'processing' || mode === 'uploading_zip'"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-border bg-surface px-4 py-2.5 text-xs font-semibold text-text-muted hover:text-text hover:bg-surface-2 transition-colors disabled:opacity-50"
                    >
                        Reset Pilihan
                    </button>
                </template>
            </div>
        </form>

        {{-- ==================== LIVE PROGRESS & FEED SECTION ==================== --}}
        <template x-if="mode !== 'idle'">
            <div class="mt-6 rounded-2xl border border-amber-500/30 bg-surface-2/40 p-5 sm:p-6 space-y-5 animate-fade-in">
                {{-- Header Progres & Persentase --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="space-y-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <template x-if="mode === 'processing' || mode === 'uploading_zip'">
                                <span class="relative flex size-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full size-3 bg-amber-500"></span>
                                </span>
                            </template>
                            <template x-if="mode === 'done'">
                                <span class="flex size-3 rounded-full bg-emerald-500"></span>
                            </template>
                            <template x-if="mode === 'error'">
                                <span class="flex size-3 rounded-full bg-rose-500"></span>
                            </template>

                            <h3 class="text-sm font-bold text-text">
                                <span x-show="mode === 'processing' || mode === 'uploading_zip'">Sedang Memproses Poster Massal</span>
                                <span x-show="mode === 'done'">Pemrosesan Poster Selesai</span>
                                <span x-show="mode === 'error'">Proses Terhenti</span>
                            </h3>
                        </div>
                        <p class="text-xs text-text-muted truncate" x-text="statusMessage"></p>
                    </div>

                    {{-- Persentase Besar --}}
                    <div class="flex items-baseline gap-2 shrink-0 sm:text-right">
                        <span class="text-3xl font-black text-amber-500 tracking-tight" x-text="progressPercent + '%'"></span>
                        <span class="text-xs font-bold text-text-muted" x-show="totalCount > 0" x-text="`(${currentIndex}/${totalCount})`"></span>
                    </div>
                </div>

                {{-- Visual Progress Bar --}}
                <div class="space-y-1.5">
                    <div class="h-3 w-full overflow-hidden rounded-full progress-track bg-slate-200 dark:bg-slate-800">
                        <div
                            class="h-full rounded-full transition-all duration-300 bg-gradient-to-r from-amber-500 to-amber-400 shadow-xs"
                            :class="{ 'bg-emerald-500 from-emerald-500 to-emerald-400': mode === 'done' && stats.failed === 0, 'bg-rose-500 from-rose-500 to-rose-400': mode === 'error' }"
                            :style="`width: ${progressPercent}%`"
                        ></div>
                    </div>
                </div>

                {{-- Ringkasan Metrik Hasil (Status Kartu) --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
                    {{-- Total --}}
                    <div class="rounded-xl border border-border bg-surface p-3 text-center">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-text-muted">Total Berkas</p>
                        <p class="text-xl font-black text-text mt-0.5" x-text="totalCount"></p>
                    </div>

                    {{-- Cocok Otomatis (Sukses) --}}
                    <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-3 text-center">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Cocok Otomatis</p>
                        <p class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5" x-text="stats.matched"></p>
                    </div>

                    {{-- Belum Terpasang --}}
                    <div class="rounded-xl border border-amber-500/20 bg-amber-500/5 p-3 text-center">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Belum Terpasang</p>
                        <p class="text-xl font-black text-amber-600 dark:text-amber-400 mt-0.5" x-text="stats.unmatched"></p>
                    </div>

                    {{-- Gagal --}}
                    <div class="rounded-xl border border-rose-500/20 bg-rose-500/5 p-3 text-center">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Gagal / Eror</p>
                        <p class="text-xl font-black text-rose-600 dark:text-rose-400 mt-0.5" x-text="stats.failed"></p>
                    </div>
                </div>

                {{-- Tombol Aksi Pasca Selesai --}}
                <div x-show="mode === 'done'" class="pt-2 flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        onclick="window.location.reload()"
                        class="inline-flex min-h-[40px] items-center justify-center gap-2 rounded-xl bg-amber-500 px-5 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition-colors shadow-xs"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Perbarui Halaman & Lihat Antrean Manual</span>
                    </button>
                    <button
                        type="button"
                        @click="clearAllInputs()"
                        class="inline-flex min-h-[40px] items-center justify-center rounded-xl border border-border bg-surface px-4 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition-colors"
                    >
                        Unggah Berkas Lainnya
                    </button>
                </div>

                {{-- Live Activity Stream / Daftar Hasil --}}
                <div class="space-y-2 pt-2">
                    <div class="flex items-center justify-between text-xs font-bold text-text">
                        <span>Aktivitas Berkas Poster</span>
                        <span class="text-[11px] text-text-muted font-normal" x-text="`${results.length} riwayat tercatat`"></span>
                    </div>

                    <div class="max-h-64 overflow-y-auto divide-y divide-border rounded-xl border border-border bg-surface">
                        <template x-for="(item, idx) in results" :key="idx">
                            <div class="p-3 flex items-center justify-between gap-3 text-xs">
                                <div class="flex items-center gap-3 min-w-0">
                                    {{-- Status Icon --}}
                                    <template x-if="item.status === 'matched'">
                                        <div class="size-7 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                    </template>
                                    <template x-if="item.status === 'unmatched'">
                                        <div class="size-7 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                    </template>
                                    <template x-if="item.status === 'failed'">
                                        <div class="size-7 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </div>
                                    </template>

                                    {{-- File & Detail Information --}}
                                    <div class="min-w-0">
                                        <p class="font-bold text-text truncate" x-text="item.filename"></p>
                                        <template x-if="item.status === 'matched'">
                                            <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium truncate">
                                                Dicocokkan ke: <span class="font-bold" x-text="item.film_title"></span>
                                            </p>
                                        </template>
                                        <template x-if="item.status === 'unmatched'">
                                            <p class="text-[11px] text-text-muted truncate">
                                                Tidak ada film cocok. Masuk antrean pasang manual.
                                            </p>
                                        </template>
                                        <template x-if="item.status === 'failed'">
                                            <p class="text-[11px] text-rose-600 dark:text-rose-400 truncate" x-text="item.message"></p>
                                        </template>
                                    </div>
                                </div>

                                {{-- Status Badges --}}
                                <div class="shrink-0 flex items-center gap-1.5">
                                    <template x-if="item.warning">
                                        <span class="inline-flex items-center rounded-md bg-warning/15 px-2 py-0.5 text-[10px] font-bold text-warning border border-warning/30" title="Resolusi rendah">
                                            &lt; 300px
                                        </span>
                                    </template>
                                    <template x-if="item.status === 'matched'">
                                        <span class="inline-flex items-center rounded-md bg-emerald-500/15 px-2 py-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                                            Cocok
                                        </span>
                                    </template>
                                    <template x-if="item.status === 'unmatched'">
                                        <span class="inline-flex items-center rounded-md bg-amber-500/15 px-2 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                            Antrean Manual
                                        </span>
                                    </template>
                                    <template x-if="item.status === 'failed'">
                                        <span class="inline-flex items-center rounded-md bg-rose-500/15 px-2 py-0.5 text-[10px] font-bold text-rose-600 dark:text-rose-400 border border-rose-500/30">
                                            Gagal
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Daftar Poster Belum Terpasang --}}
    <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-4">
        <div>
            <h3 class="text-base font-bold text-text">Poster Belum Terpasang ({{ count($unmatchedPosters) }})</h3>
            <p class="text-xs text-text-muted mt-0.5">
                Poster yang tidak cocok otomatis dengan judul/slug film dapat dipasangkan secara manual melalui pilihan film di bawah tiap poster.
            </p>
        </div>

        @if (empty($unmatchedPosters))
            <div class="rounded-xl border border-border bg-surface-2/30 p-8 text-center text-xs text-text-muted">
                <svg class="size-8 text-success mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="font-bold text-text">Semua poster telah terpasang rapi</p>
                <p class="mt-1">Tidak ada berkas poster antrean yang belum terpasang ke film katalog.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($unmatchedPosters as $poster)
                    <div class="rounded-xl border border-border bg-surface-2/40 p-4 space-y-3 flex flex-col justify-between">
                        <div class="flex gap-3">
                            <img
                                src="{{ $poster['url'] }}"
                                alt="{{ $poster['filename'] }}"
                                class="h-28 w-20 shrink-0 rounded-lg object-cover border border-border bg-surface shadow-xs"
                            >
                            <div class="min-w-0 flex-1 space-y-1">
                                <p class="text-xs font-bold text-text truncate" title="{{ $poster['filename'] }}">
                                    {{ $poster['filename'] }}
                                </p>
                                <p class="text-[11px] text-text-muted">
                                    {{ $poster['width'] }} × {{ $poster['height'] }} px · {{ round($poster['size'] / 1024, 1) }} KB
                                </p>
                                @if ($poster['is_small'])
                                    <span class="inline-flex items-center rounded-md bg-warning/15 px-2 py-0.5 text-[10px] font-bold text-warning border border-warning/30" title="{{ $poster['warning'] }}">
                                        Resolusi Rendah
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="pt-2 border-t border-border space-y-2">
                            {{-- Form Pasangkan Manual --}}
                            <form
                                action="{{ route('admin.films.posters.assign') }}"
                                method="POST"
                                class="space-y-2"
                            >
                                @csrf
                                <input type="hidden" name="poster_filename" value="{{ $poster['filename'] }}">

                                <div>
                                    <x-ui.select
                                        name="film_id"
                                        label="Pasangkan ke Film"
                                        placeholder="Pilih film penerima..."
                                        size="sm"
                                        :options="$filmOptions"
                                    />
                                </div>

                                <div class="flex items-center justify-between gap-2 pt-1">
                                    <button
                                        type="submit"
                                        class="inline-flex min-h-[34px] items-center justify-center gap-1.5 rounded-lg bg-amber-500 px-3 py-1 text-xs font-bold text-slate-950 hover:bg-amber-400 transition-colors"
                                    >
                                        <span>Pasangkan</span>
                                    </button>
                                </div>
                            </form>

                            <div class="flex justify-end">
                                <form
                                    action="{{ route('admin.films.posters.destroy', $poster['filename']) }}"
                                    method="POST"
                                    data-confirm="Hapus berkas poster ini?"
                                    data-confirm-title="Hapus Poster"
                                    data-confirm-action="Hapus Berkas"
                                    data-confirm-is-danger="true"
                                    novalidate
                                    class="inline"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="inline-flex min-h-[30px] items-center justify-center rounded-lg border border-danger/30 px-2.5 py-1 text-xs font-semibold text-danger hover:bg-danger/10 transition-colors"
                                        title="Hapus poster ini"
                                    >
                                        Hapus Poster
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
