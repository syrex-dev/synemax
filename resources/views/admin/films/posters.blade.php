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
        <div
            class="rounded-2xl border border-border bg-surface p-4 shadow-token"
            x-data="{ count: {{ count($unmatchedPosters) }} }"
            @unmatched-count-changed.window="count = $event.detail"
        >
            <p class="text-xs text-text-muted font-medium">Poster Belum Terpasang</p>
            <p class="text-2xl font-black mt-1" :class="count > 0 ? 'text-amber-500' : 'text-text'" x-text="count">
                {{ count($unmatchedPosters) }}
            </p>
            <span class="text-[10px] text-text-muted">Gambar yang menunggu dipasangkan secara manual</span>
        </div>
    </div>

    {{-- Form Unggah Banyak Berkas / ZIP dengan Indikator Progres Interaktif --}}
    <div
        class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-6"
        x-data="bulkPosterUploaderComponent()"
    >
        <div>
            <h2 class="text-base font-bold text-text">Unggah Poster Sekaligus</h2>
            <p class="text-xs text-text-muted mt-1">
                Pilih beberapa file gambar sekaligus atau unggah satu file ZIP. Sistem otomatis memangkas ke rasio 2:3 (600 × 900 px), mengonversi ke WebP, membuang metadata EXIF, dan mencocokkan otomatis ke film berdasarkan nama berkas/slug. Poster yang tidak cocok langsung masuk ke antrean manual di bawah.
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
                        onclick="window.location.hash = 'antrean-manual'; window.location.reload();"
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
                                            <p class="text-[11px] text-amber-500 font-medium truncate">
                                                Nama berkas tidak cocok judul film. Masuk antrean pasang manual di bawah.
                                            </p>
                                        </template>
                                        <template x-if="item.status === 'failed'">
                                            <p class="text-[11px] text-rose-600 dark:text-rose-400 truncate" x-text="item.message"></p>
                                        </template>
                                        <template x-if="item.compression">
                                            <p class="text-[10px] text-text-muted truncate" x-text="`Kompresi: ${item.compression.before} → ${item.compression.after} (${item.compression.format})`"></p>
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

    {{-- ==================== DAFTAR POSTER BELUM TERPASANG (ANTREAN MANUAL) ==================== --}}
    <div id="antrean-manual" class="scroll-mt-6">
        @if (empty($unmatchedPosters))
            <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-4">
                <div>
                    <h3 class="text-base font-bold text-text">Poster Belum Terpasang (0)</h3>
                    <p class="text-xs text-text-muted mt-0.5">
                        Poster yang tidak cocok otomatis dengan judul/slug film dapat dipasangkan secara manual melalui pilihan film di bawah tiap poster.
                    </p>
                </div>
                <div class="rounded-xl border border-border bg-surface-2/30 p-8 text-center text-xs text-text-muted">
                    <svg class="size-8 text-success mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="font-bold text-text">Semua poster telah terpasang rapi</p>
                    <p class="mt-1">Tidak ada berkas poster antrean yang belum terpasang ke film katalog.</p>
                </div>
            </div>
        @else
            <div
                class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-4"
                x-data="unmatchedPostersComponent({
                    posterList: {{ Js::from(array_map(fn($p) => [
                        'filename' => $p['filename'],
                        'url' => $p['url'],
                        'selectName' => 'film_id_'.md5($p['filename'])
                    ], $unmatchedPosters)) }},
                    filmTitles: {{ Js::from($filmOptions) }},
                    totalInitial: {{ count($unmatchedPosters) }}
                })"
            >
                {{-- Header & Batch Actions --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-text">
                                Poster Belum Terpasang (<span x-text="remainingCount"></span>)
                            </h3>
                            <template x-if="selectedCount > 0">
                                <span class="inline-flex items-center rounded-full bg-amber-500/15 px-2.5 py-0.5 text-xs font-bold text-amber-500 border border-amber-500/30">
                                    <span x-text="selectedCount"></span> dipilih
                                </span>
                            </template>
                        </div>
                        <p class="text-xs text-text-muted mt-0.5">
                            Pilih judul film pada dropdown di bawah masing-masing poster untuk memasangkannya secara manual ke katalog bioskop.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        {{-- Pasangkan Semua yang Dipilih --}}
                        <button
                            type="button"
                            @click="openBatchPreviewModal()"
                            :disabled="selectedCount === 0 || isSubmittingBatch"
                            class="inline-flex min-h-[38px] items-center justify-center gap-1.5 rounded-xl bg-amber-500 px-4 py-1.5 text-xs font-bold text-slate-950 hover:bg-amber-400 shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <template x-if="isSubmittingBatch">
                                <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <template x-if="!isSubmittingBatch">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </template>
                            <span>Pasangkan Semua yang Dipilih (<span x-text="selectedCount"></span>)</span>
                        </button>
                    </div>
                </div>

                {{-- ==================== MODAL PRATINJAU PEMASANGAN MASSAL ==================== --}}
                <div
                    x-show="previewBatchModal"
                    x-cloak
                    class="fixed inset-0 z-[10000] flex items-center justify-center p-4 sm:p-6"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Konfirmasi Pasangkan Poster Massal"
                >
                    <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" @click="previewBatchModal = false"></div>
                    <div class="relative w-full max-w-2xl max-h-[85vh] flex flex-col rounded-3xl border border-border bg-surface p-6 shadow-2xl space-y-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-base font-black text-text">Pratinjau Pemasangan Poster Massal</h3>
                                <p class="text-xs text-text-muted mt-1">Periksa kembali daftar poster dan film penerima sebelum dipasangkan.</p>
                            </div>
                            <button type="button" @click="previewBatchModal = false" class="p-1.5 rounded-xl hover:bg-surface-2 text-text-muted hover:text-text transition" aria-label="Tutup pratinjau">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="flex-1 overflow-y-auto divide-y divide-border rounded-xl border border-border">
                            <template x-for="(item, idx) in batchPreviewItems" :key="idx">
                                <div class="p-3 flex items-center justify-between gap-3 text-xs bg-surface-2/20">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <input type="checkbox" x-model="item.checked" class="rounded border-border text-amber-500 focus:ring-amber-500 size-4">
                                        <img :src="item.url" :alt="item.filename" class="size-12 rounded object-cover border border-border bg-surface shrink-0">
                                        <div class="min-w-0">
                                            <p class="font-bold text-text truncate" x-text="item.filename"></p>
                                            <p class="text-[11px] text-amber-500 font-semibold truncate" x-text="`→ ${item.filmTitle}`"></p>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 shrink-0">
                                        Siap dipasang
                                    </span>
                                </div>
                            </template>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-border">
                            <span class="text-xs text-text-muted">
                                <strong class="text-text" x-text="batchPreviewItems.filter(i => i.checked).length"></strong> poster dipilih
                            </span>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="previewBatchModal = false" class="px-4 py-2 rounded-xl border border-border text-xs font-semibold text-text hover:bg-surface-2">
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    @click="executeBatchAssignment()"
                                    :disabled="batchPreviewItems.filter(i => i.checked).length === 0 || isSubmittingBatch"
                                    class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs disabled:opacity-50"
                                >
                                    <span x-text="isSubmittingBatch ? 'Memasangkan...' : 'Pasangkan Sekarang'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Toast / Banner --}}
                <template x-if="banner">
                    <div
                        class="rounded-xl p-3.5 text-xs font-semibold flex items-center justify-between gap-3 animate-fade-in"
                        :class="{
                            'bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400': banner.type === 'success',
                            'bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400': banner.type === 'error',
                            'bg-amber-500/10 border border-amber-500/30 text-amber-500': banner.type === 'info'
                        }"
                    >
                        <span x-text="banner.message"></span>
                        <button type="button" @click="banner = null" class="opacity-70 hover:opacity-100 p-1 rounded-lg text-text hover:bg-surface-2 transition shrink-0" aria-label="Tutup pemberitahuan">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </template>

                {{-- Dynamic Empty State when all assigned/deleted --}}
                <div x-show="remainingCount === 0" class="rounded-xl border border-border bg-surface-2/30 p-8 text-center text-xs text-text-muted">
                    <svg class="size-8 text-success mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="font-bold text-text">Semua poster telah terpasang rapi</p>
                    <p class="mt-1">Tidak ada berkas poster antrean yang belum terpasang ke film katalog.</p>
                </div>

                {{-- Grid of Poster Cards using original custom x-ui.select --}}
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" x-show="remainingCount > 0">
                    @foreach ($unmatchedPosters as $poster)
                        @php
                            $posterKey = md5($poster['filename']);
                        @endphp
                        <div
                            x-show="!removedPosters['{{ $poster['filename'] }}']"
                            data-poster-card="{{ $poster['filename'] }}"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="rounded-xl border border-border bg-surface-2/40 p-4 space-y-3 flex flex-col justify-between transition-all"
                            id="card-{{ $posterKey }}"
                        >
                            <div class="flex gap-3">
                                <img
                                    src="{{ $poster['url'] }}"
                                    alt="{{ $poster['filename'] }}"
                                    class="h-28 w-20 shrink-0 rounded-lg object-cover border border-border bg-surface shadow-xs"
                                    loading="lazy"
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
                                <div>
                                    <label class="block text-xs font-semibold text-text mb-1">Pasangkan ke Film</label>

                                    {{-- Original Custom SynemaX Dropdown Component with Search --}}
                                    <x-ui.select
                                        name="film_id_{{ $posterKey }}"
                                        placeholder="Pilih film penerima..."
                                        size="sm"
                                        :options="$filmOptions"
                                        @change="onFilmSelected('{{ $poster['filename'] }}', $event)"
                                    />
                                </div>

                                <div class="flex items-center justify-between gap-2 pt-1">
                                    <button
                                        type="button"
                                        @click="assignSingle('{{ $poster['filename'] }}')"
                                        :disabled="!selectedFilms['{{ $poster['filename'] }}'] || processingFiles['{{ $poster['filename'] }}']"
                                        class="inline-flex min-h-[34px] items-center justify-center gap-1.5 rounded-lg bg-amber-500 px-3.5 py-1 text-xs font-bold text-slate-950 hover:bg-amber-400 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <template x-if="processingFiles['{{ $poster['filename'] }}']">
                                            <svg class="size-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </template>
                                        <span>Pasangkan</span>
                                    </button>

                                    <button
                                        type="button"
                                        @click="deleteSingle('{{ $poster['filename'] }}')"
                                        :disabled="processingFiles['{{ $poster['filename'] }}']"
                                        class="inline-flex min-h-[30px] items-center justify-center rounded-lg border border-danger/30 px-2.5 py-1 text-xs font-semibold text-danger hover:bg-danger/10 transition-colors disabled:opacity-50"
                                        title="Hapus poster ini dari antrean"
                                    >
                                        Hapus Poster
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    async function compressPosterClientSide(file) {
        if (!file || !file.type.startsWith('image/')) {
            return { file, originalSize: file?.size || 0, compressedSize: file?.size || 0, isCompressed: false, format: 'Asli' };
        }

        return new Promise((resolve) => {
            const originalSize = file.size;
            const img = new Image();
            img.crossOrigin = 'anonymous';

            img.onload = () => {
                try {
                    let w = img.naturalWidth || img.width;
                    let h = img.naturalHeight || img.height;

                    if (!w || !h) {
                        return resolve({ file, originalSize, compressedSize: originalSize, isCompressed: false, format: 'Asli' });
                    }

                    // Strict dimension guard: do not downscale below 600x900
                    const maxDim = 1500;
                    if (w > maxDim || h > maxDim) {
                        if (w > h) {
                            const newW = maxDim;
                            const newH = Math.round((h * maxDim) / w);
                            if (newW >= 600 && newH >= 900) {
                                w = newW;
                                h = newH;
                            }
                        } else {
                            const newH = maxDim;
                            const newW = Math.round((w * maxDim) / h);
                            if (newW >= 600 && newH >= 900) {
                                w = newW;
                                h = newH;
                            }
                        }
                    }

                    const canvas = document.createElement('canvas');
                    canvas.width = w;
                    canvas.height = h;
                    const ctx = canvas.getContext('2d');
                    if (!ctx) {
                        return resolve({ file, originalSize, compressedSize: originalSize, isCompressed: false, format: 'Asli' });
                    }

                    ctx.drawImage(img, 0, 0, w, h);

                    // WebP conversion
                    canvas.toBlob((webpBlob) => {
                        if (webpBlob && webpBlob.type === 'image/webp' && webpBlob.size < originalSize) {
                            const compressedFile = new File([webpBlob], file.name.replace(/\.[^.]+$/, '') + '.webp', {
                                type: 'image/webp',
                                lastModified: Date.now()
                            });
                            return resolve({
                                file: compressedFile,
                                originalSize,
                                compressedSize: webpBlob.size,
                                format: 'WebP',
                                isCompressed: true
                            });
                        }

                        // Fallback JPEG quality 0.85
                        canvas.toBlob((jpegBlob) => {
                            if (jpegBlob && jpegBlob.size < originalSize) {
                                const compressedFile = new File([jpegBlob], file.name.replace(/\.[^.]+$/, '') + '.jpg', {
                                    type: 'image/jpeg',
                                    lastModified: Date.now()
                                });
                                return resolve({
                                    file: compressedFile,
                                    originalSize,
                                    compressedSize: jpegBlob.size,
                                    format: 'JPEG',
                                    isCompressed: true
                                });
                            }

                            resolve({ file, originalSize, compressedSize: originalSize, isCompressed: false, format: 'Asli' });
                        }, 'image/jpeg', 0.85);

                    }, 'image/webp', 0.85);

                } catch (err) {
                    console.warn('Compression error, using raw file:', err);
                    resolve({ file, originalSize, compressedSize: originalSize, isCompressed: false, format: 'Asli' });
                }
            };

            img.onerror = () => {
                resolve({ file, originalSize, compressedSize: originalSize, isCompressed: false, format: 'Asli' });
            };

            img.src = URL.createObjectURL(file);
        });
    }

    function bulkPosterUploaderComponent() {
        return {
            imageFiles: [],
            zipFile: null,
            mode: 'idle',
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
                    if (typeof window.showToast === 'function') {
                        window.showToast({
                            type: 'warning',
                            message: 'Pilih minimal satu berkas gambar atau berkas arsip ZIP terlebih dahulu.'
                        });
                    }
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

                    this.statusMessage = 'Mengompresi gambar (' + this.currentIndex + '/' + this.totalCount + '): ' + file.name + '...';
                    const compResult = await compressPosterClientSide(file);
                    const uploadFile = compResult.file;

                    this.statusMessage = 'Memproses & mencocokkan nama poster (' + this.currentIndex + '/' + this.totalCount + '): ' + file.name + '...';

                    const fd = new FormData();
                    fd.append('image', uploadFile);
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
                            if (compResult.isCompressed) {
                                resData.compression = {
                                    before: this.formatSize(compResult.originalSize),
                                    after: this.formatSize(compResult.compressedSize),
                                    format: compResult.format
                                };
                            }
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
                this.statusMessage = 'Selesai! ' + this.stats.matched + ' cocok otomatis, ' + this.stats.unmatched + ' belum terpasang, ' + this.stats.failed + ' gagal.';
            },

            async processZipArchive() {
                this.mode = 'uploading_zip';
                this.statusMessage = 'Mengunggah berkas arsip ZIP (' + this.zipFile.name + ')...';
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
                    this.statusMessage = 'Memproses poster ' + this.currentIndex + ' dari ' + this.totalCount + ' (' + item.filename + ')...';

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

                    this.progressPercent = 20 + Math.round((this.currentIndex / this.totalCount) * 80);
                }

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
                this.statusMessage = 'Selesai! ' + this.stats.matched + ' cocok otomatis, ' + this.stats.unmatched + ' belum terpasang, ' + this.stats.failed + ' gagal.';
            }
        };
    }

    function unmatchedPostersComponent(config) {
        return {
            posterList: config.posterList || [],
            filmTitles: config.filmTitles || {},
            totalInitial: config.totalInitial || 0,
            removedPosters: {},
            selectedFilms: {},
            processingFiles: {},
            isSubmittingBatch: false,
            previewBatchModal: false,
            batchPreviewItems: [],
            banner: null,
            _bannerTimer: null,

            get remainingCount() {
                const removedCount = Object.keys(this.removedPosters).filter((k) => this.removedPosters[k]).length;
                return Math.max(0, this.totalInitial - removedCount);
            },

            get selectedCount() {
                let count = 0;
                for (let i = 0; i < this.posterList.length; i++) {
                    const p = this.posterList[i];
                    if (!this.removedPosters[p.filename] && this.selectedFilms[p.filename]) {
                        count++;
                    }
                }
                return count;
            },

            onFilmSelected(filename, val) {
                const filmId = (typeof val === 'object' && val !== null) ? (val.target?.value || val.detail?.value || '') : val;
                if (filmId) {
                    this.selectedFilms = { ...this.selectedFilms, [filename]: String(filmId) };
                } else {
                    const next = { ...this.selectedFilms };
                    delete next[filename];
                    this.selectedFilms = next;
                }
            },

            removeCardFromDisplay(filename) {
                // 1. Reassign removedPosters with new object reference for Alpine reactivity
                this.removedPosters = { ...this.removedPosters, [filename]: true };

                // 2. Clean up selectedFilms
                const newSelected = { ...this.selectedFilms };
                delete newSelected[filename];
                this.selectedFilms = newSelected;

                // 3. Immediately animate and remove the card element from DOM
                const escaped = CSS.escape ? CSS.escape(filename) : filename.replace(/["\\]/g, '\\$&');
                const cardEl = document.querySelector(`[data-poster-card="${escaped}"]`) || document.querySelector(`[data-poster-card="${filename}"]`);
                if (cardEl) {
                    cardEl.style.transition = 'all 0.28s cubic-bezier(0.4, 0, 0.2, 1)';
                    cardEl.style.opacity = '0';
                    cardEl.style.transform = 'scale(0.92)';
                    setTimeout(() => {
                        cardEl.remove();
                    }, 280);
                }

                // 4. Update count badge & window event
                this.notifyCountChange();
            },

            showBanner(type, message) {
                this.banner = { type, message };
                if (this._bannerTimer) clearTimeout(this._bannerTimer);
                this._bannerTimer = setTimeout(() => {
                    this.banner = null;
                }, 8000);
            },

            notify(type, message) {
                if (typeof window.showToast === 'function') {
                    window.showToast({ type, message });
                }
                this.showBanner(type, message);
            },

            notifyCountChange() {
                window.dispatchEvent(new CustomEvent('unmatched-count-changed', {
                    detail: this.remainingCount
                }));
            },

            async assignSingle(filename) {
                const filmId = this.selectedFilms[filename];
                if (!filmId) {
                    this.notify('warning', 'Pilih film penerima terlebih dahulu pada poster ini.');
                    return;
                }

                this.processingFiles = { ...this.processingFiles, [filename]: true };
                try {
                    const res = await fetch('{{ route('admin.films.posters.assign') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            poster_filename: filename,
                            film_id: parseInt(filmId, 10)
                        })
                    });

                    const data = await res.json();
                    if (res.ok && data.success) {
                        // Success: clear card immediately from display!
                        this.removeCardFromDisplay(filename);
                        this.notify('success', data.message || 'Poster berhasil dipasangkan.');
                    } else {
                        // Failed: do NOT remove card, leave it on display with error toast!
                        this.notify('error', data.message || 'Gagal memasangkan poster.');
                    }
                } catch (err) {
                    // Error: do NOT remove card, leave it on display!
                    this.notify('error', 'Gagal menghubungi server: ' + err.message);
                } finally {
                    const nextProc = { ...this.processingFiles };
                    delete nextProc[filename];
                    this.processingFiles = nextProc;
                }
            },

            openBatchPreviewModal() {
                const items = [];
                for (let i = 0; i < this.posterList.length; i++) {
                    const poster = this.posterList[i];
                    if (!this.removedPosters[poster.filename]) {
                        const filmId = this.selectedFilms[poster.filename];
                        if (filmId) {
                            let filmTitle = this.filmTitles[filmId] || ('Film #' + filmId);
                            filmTitle = filmTitle.replace(/^★\s*\[Belum Ada Poster\]\s*/, '').replace(/\s*\(Ganti poster\)\s*$/, '');

                            items.push({
                                filename: poster.filename,
                                url: poster.url,
                                filmId: filmId,
                                filmTitle: filmTitle,
                                checked: true
                            });
                        }
                    }
                }

                if (items.length === 0) {
                    this.notify('warning', 'Pilih minimal satu film penerima terlebih dahulu pada poster yang ingin dipasangkan.');
                    return;
                }

                this.batchPreviewItems = items;
                this.previewBatchModal = true;
            },

            async executeBatchAssignment() {
                const selected = this.batchPreviewItems.filter(i => i.checked);
                if (selected.length === 0) {
                    this.notify('warning', 'Pilih minimal satu poster pada tabel pratinjau.');
                    return;
                }

                this.isSubmittingBatch = true;
                try {
                    const assignments = selected.map(i => ({
                        poster_filename: i.filename,
                        film_id: parseInt(i.filmId, 10)
                    }));

                    const res = await fetch('{{ route('admin.films.posters.assign_batch') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ assignments })
                    });

                    const data = await res.json();
                    if (res.ok && data.success) {
                        const successfulItems = data.items || [];
                        for (let i = 0; i < successfulItems.length; i++) {
                            const fname = successfulItems[i].filename || successfulItems[i].poster_filename;
                            this.removeCardFromDisplay(fname);
                        }

                        this.previewBatchModal = false;

                        const reverts = data.items || [];
                        if (typeof window.showToast === 'function') {
                            window.showToast({
                                type: data.failed_count > 0 ? 'warning' : 'success',
                                message: data.message || `Berhasil memasangkan ${data.assigned_count} poster ke katalog.`,
                                duration: 8000,
                                onUndo: () => this.undoBatchAssignment(reverts)
                            });
                        }
                    } else {
                        this.notify('error', data.message || 'Gagal memasangkan kumpulan poster.');
                    }
                } catch (err) {
                    this.notify('error', 'Gagal menghubungi server: ' + err.message);
                } finally {
                    this.isSubmittingBatch = false;
                }
            },

            async undoBatchAssignment(reverts) {
                if (!reverts || reverts.length === 0) return;

                try {
                    const res = await fetch('{{ route('admin.films.posters.undo_batch') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ reverts })
                    });

                    const data = await res.json();
                    if (res.ok && data.success) {
                        window.location.reload();
                    } else {
                        this.notify('error', data.message || 'Gagal mengurungkan pemasangan poster.');
                    }
                } catch (e) {
                    this.notify('error', 'Gagal menghubungi server saat mengurungkan: ' + e.message);
                }
            },

            deleteSingle(filename) {
                if (typeof window.openConfirmModal === 'function') {
                    window.openConfirmModal({
                        title: 'Hapus Poster Antrean',
                        message: 'Hapus berkas poster "' + filename + '" dari antrean poster belum terpasang?',
                        confirmText: 'Hapus Poster',
                        confirmClass: 'bg-danger text-white hover:bg-danger/90',
                        cancelText: 'Batal',
                        onConfirm: () => {
                            this.executeDeleteSingle(filename);
                        }
                    });
                } else {
                    this.executeDeleteSingle(filename);
                }
            },

            async executeDeleteSingle(filename) {
                this.processingFiles = { ...this.processingFiles, [filename]: true };
                try {
                    const url = '{{ route('admin.films.posters.destroy', '__FILE__') }}'.replace('__FILE__', encodeURIComponent(filename));
                    const res = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await res.json();
                    if (res.ok && data.success) {
                        this.removeCardFromDisplay(filename);
                        this.notify('info', 'Poster berhasil dihapus dari antrean.');
                    } else {
                        this.notify('error', data.message || 'Gagal menghapus poster.');
                    }
                } catch (err) {
                    this.notify('error', 'Gagal menghapus berkas: ' + err.message);
                } finally {
                    const nextProc = { ...this.processingFiles };
                    delete nextProc[filename];
                    this.processingFiles = nextProc;
                }
            }
        };
    }
</script>
@endpush
