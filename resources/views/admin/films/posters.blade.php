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

    {{-- Ringkasan Metrik Poster & Pembersih Sampah Berkas --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
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
        <div
            class="rounded-2xl border border-border bg-surface p-4 shadow-token flex flex-col justify-between"
            x-data="{
                isCleaning: false,
                async cleanStorage() {
                    if (!confirm('Bersihkan seluruh berkas sementara upload ZIP/staged dan poster yang tidak terpakai dari penyimpanan STB?')) return;
                    this.isCleaning = true;
                    try {
                        const res = await fetch('{{ route('admin.films.posters.cleanup_storage') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const data = await res.json();
                        if (res.ok && data.success) {
                            if (typeof window.showToast === 'function') {
                                window.showToast({ type: 'success', message: data.message });
                            }
                        } else {
                            if (typeof window.showToast === 'function') {
                                window.showToast({ type: 'error', message: data.message || 'Gagal membersihkan sampah berkas.' });
                            }
                        }
                    } catch (e) {
                        if (typeof window.showToast === 'function') {
                            window.showToast({ type: 'error', message: 'Koneksi terputus: ' + e.message });
                        }
                    } finally {
                        this.isCleaning = false;
                    }
                }
            }"
        >
            <div>
                <div class="flex items-center justify-between">
                    <p class="text-xs text-text-muted font-medium">Kebersihan Penyimpanan STB</p>
                    <a href="{{ route('admin.films.index', ['trash' => 1]) }}" class="text-[11px] font-bold text-amber-500 hover:underline">
                        Kotak Sampah Film &rarr;
                    </a>
                </div>
                <p class="text-xs font-semibold text-text mt-1">Sisa Upload & Poster Yatim</p>
                <p class="text-[10px] text-text-muted mt-0.5">Bersihkan folder sementara hasil upload & poster tak terpakai agar memori 8 GB STB tetap lega.</p>
            </div>
            <div class="pt-2">
                <button
                    type="button"
                    @click="cleanStorage()"
                    :disabled="isCleaning"
                    class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border border-border bg-surface-2 hover:bg-surface-2/80 px-3 py-1.5 text-xs font-bold text-text transition disabled:opacity-50"
                >
                    <svg class="size-3.5 text-amber-500" :class="isCleaning ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span x-text="isCleaning ? 'Membersihkan...' : 'Bersihkan Sampah Berkas'"></span>
                </button>
            </div>
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
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs font-bold text-text">
                        <div class="flex items-center gap-2">
                            <span>Aktivitas Berkas Poster</span>
                            <span class="text-[11px] text-text-muted font-normal" x-text="`(${results.length} riwayat tercatat)`"></span>
                        </div>
                        <template x-if="stats.unmatched > 0">
                            <button
                                type="button"
                                @click="deleteAllUnmatchedInBatch()"
                                class="text-[11px] text-danger hover:underline font-semibold"
                            >
                                Hapus Semua Belum Terpasang (<span x-text="stats.unmatched"></span>)
                            </button>
                        </template>
                    </div>

                    <div class="max-h-72 overflow-y-auto divide-y divide-border rounded-xl border border-border bg-surface">
                        <template x-for="(item, idx) in results" :key="idx">
                            <div class="p-3 flex items-center justify-between gap-3 text-xs hover:bg-surface-2/40 transition">
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

                                {{-- Status Badges & Action Buttons (Spectate & Hapus) --}}
                                <div class="shrink-0 flex items-center gap-1.5 sm:gap-2">
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

                                    {{-- Spectate Trigger (if poster_url exists) --}}
                                    <template x-if="item.poster_url">
                                        <button
                                            type="button"
                                            @click="openSpectateFromActivity(item)"
                                            class="p-1.5 rounded-lg border border-border bg-surface hover:bg-surface-2 text-text-muted hover:text-amber-500 transition"
                                            title="Lihat Pratinjau Poster"
                                            aria-label="Lihat poster"
                                        >
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </template>

                                    {{-- Hapus Poster Button --}}
                                    <button
                                        type="button"
                                        @click="deleteActivityItem(item, idx)"
                                        :disabled="item._isDeleting"
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg border border-rose-500/30 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 font-semibold text-[11px] transition disabled:opacity-50"
                                        title="Hapus poster yang salah terupload ini"
                                    >
                                        <template x-if="item._isDeleting">
                                            <svg class="size-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </template>
                                        <template x-if="!item._isDeleting">
                                            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </template>
                                        <span>Hapus</span>
                                    </button>
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
                        'width' => $p['width'],
                        'height' => $p['height'],
                        'size' => round($p['size'] / 1024, 1).' KB',
                        'posterKey' => md5($p['filename']),
                        'selectName' => 'film_id_'.md5($p['filename'])
                    ], $unmatchedPosters)) }},
                    filmTitles: {{ Js::from($filmOptions) }},
                    totalInitial: {{ count($unmatchedPosters) }}
                })"
                @keydown.window="if (spectateModalOpen) onSpectateKeyDown($event)"
                @open-spectate-modal.window="openSpectateDirect($event.detail)"
                @poster-card-deleted.window="removeCardFromDisplay($event.detail)"
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
                    @keydown.escape.window="previewBatchModal = false"
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

                {{-- ==================== MODAL SPECTATE / LIHAT POSTER ==================== --}}
                <div
                    x-show="spectateModalOpen"
                    x-cloak
                    class="fixed inset-0 z-[10000] flex items-center justify-center p-3 sm:p-6"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Pratinjau Poster Penuh"
                >
                    {{-- Backdrop --}}
                    <div
                        x-show="spectateModalOpen"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 bg-black/85 backdrop-blur-sm cursor-zoom-out"
                        @click="closeSpectate()"
                        aria-hidden="true"
                    ></div>

                    {{-- Modal Box --}}
                    <div
                        x-show="spectateModalOpen"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="relative z-10 w-full max-w-xl max-h-[92dvh] flex flex-col rounded-3xl border border-slate-300 dark:border-white/15 bg-surface p-4 sm:p-5 shadow-2xl space-y-3"
                        @click.stop
                    >
                        {{-- Modal Header --}}
                        <div class="flex items-center justify-between gap-3 pb-2 border-b border-border shrink-0">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1 rounded-md bg-amber-500/15 px-2 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Spectate Poster
                                    </span>
                                    <span class="text-[11px] text-text-muted" x-show="activePosterList.length > 1" x-text="`(${spectateIndex + 1} dari ${activePosterList.length})`"></span>
                                    <span class="text-[11px] text-text-muted" x-text="spectateData.width ? `${spectateData.width} × ${spectateData.height} px · ${spectateData.size}` : ''"></span>
                                </div>
                                <h3 class="text-xs sm:text-sm font-bold text-text truncate mt-1" x-text="spectateData.filename" :title="spectateData.filename"></h3>
                            </div>

                            <button
                                type="button"
                                @click="closeSpectate()"
                                class="p-2 rounded-xl hover:bg-surface-2 text-text-muted hover:text-text transition shrink-0"
                                aria-label="Tutup spectate poster"
                                title="Tutup (ESC)"
                            >
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        {{-- Modal Image Center Area with Prev / Next Navigation --}}
                        <div class="relative flex-1 min-h-0 flex items-center justify-center rounded-2xl bg-black/60 p-2 border border-border/80 overflow-hidden">
                            {{-- Previous Button --}}
                            <button
                                type="button"
                                x-show="activePosterList.length > 1"
                                @click="spectatePrev()"
                                class="absolute left-2 top-1/2 -translate-y-1/2 size-9 rounded-full bg-black/70 hover:bg-black/90 text-white border border-white/20 flex items-center justify-center transition shadow-lg z-10"
                                title="Poster sebelumnya (Panah Kiri ←)"
                                aria-label="Poster sebelumnya"
                            >
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>

                            {{-- Poster Image --}}
                            <img
                                :src="spectateData.url"
                                :alt="spectateData.filename"
                                class="max-h-[62dvh] w-auto max-w-full rounded-xl object-contain shadow-2xl transition-all select-none"
                            >

                            {{-- Next Button --}}
                            <button
                                type="button"
                                x-show="activePosterList.length > 1"
                                @click="spectateNext()"
                                class="absolute right-2 top-1/2 -translate-y-1/2 size-9 rounded-full bg-black/70 hover:bg-black/90 text-white border border-white/20 flex items-center justify-center transition shadow-lg z-10"
                                title="Poster berikutnya (Panah Kanan →)"
                                aria-label="Poster berikutnya"
                            >
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>

                        {{-- Modal Footer --}}
                        <div class="flex items-center justify-between pt-1 border-t border-border shrink-0 text-xs">
                            <span class="text-[11px] text-text-muted hidden sm:inline">
                                Navigasi: <kbd class="px-1.5 py-0.5 rounded bg-surface-2 border border-border font-mono text-[10px]">←</kbd> <kbd class="px-1.5 py-0.5 rounded bg-surface-2 border border-border font-mono text-[10px]">→</kbd> atau <kbd class="px-1.5 py-0.5 rounded bg-surface-2 border border-border font-mono text-[10px]">ESC</kbd>
                            </span>
                            <span class="text-[11px] text-text-muted sm:hidden">Klik di luar untuk menutup</span>
                            <button
                                type="button"
                                @click="closeSpectate()"
                                class="px-4 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs transition shadow-xs"
                            >
                                Tutup Pratinjau
                            </button>
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
                            x-data="posterCardItem({
                                filename: {{ Js::from($poster['filename']) }},
                                posterKey: '{{ $posterKey }}',
                                url: {{ Js::from($poster['url']) }},
                                width: {{ (int) $poster['width'] }},
                                height: {{ (int) $poster['height'] }},
                                size: {{ Js::from(round($poster['size'] / 1024, 1).' KB') }}
                            })"
                            x-show="!isDeleted && !removedPosters[filename]"
                            @poster-card-deleted.window="if ($event.detail === filename) { isDeleted = true; const el = document.getElementById('card-' + posterKey); if (el) { el.style.transition = 'all 0.28s cubic-bezier(0.4, 0, 0.2, 1)'; el.style.opacity = '0'; el.style.transform = 'scale(0.92)'; setTimeout(() => { try { el.remove(); } catch(e){} }, 280); } }"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="rounded-xl border border-border bg-surface-2/40 p-4 space-y-3 flex flex-col justify-between transition-all"
                            id="card-{{ $posterKey }}"
                            data-poster-card="{{ $poster['filename'] }}"
                        >
                            <div class="flex gap-3">
                                {{-- Thumbnail with hover spectate overlay --}}
                                <div
                                    class="group relative h-28 w-20 shrink-0 rounded-lg overflow-hidden border border-border bg-surface shadow-xs cursor-pointer select-none"
                                    @click="openSpectateDirect({ url: url, filename: filename, width: width, height: height, size: size })"
                                    title="Klik untuk spectate / lihat poster penuh"
                                >
                                    <img
                                        src="{{ $poster['url'] }}"
                                        alt="{{ $poster['filename'] }}"
                                        class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
                                        loading="lazy"
                                    >
                                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-1 text-white">
                                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span class="text-[9px] font-bold uppercase tracking-wider">Spectate</span>
                                    </div>
                                </div>

                                <div class="min-w-0 flex-1 space-y-1">
                                    <p class="text-xs font-bold text-text truncate" title="{{ $poster['filename'] }}">
                                        {{ $poster['filename'] }}
                                    </p>
                                    <p class="text-[11px] text-text-muted">
                                        {{ $poster['width'] }} × {{ $poster['height'] }} px · {{ round($poster['size'] / 1024, 1) }} KB
                                    </p>
                                    <div class="flex flex-wrap items-center gap-2 pt-0.5">
                                        <button
                                            type="button"
                                            @click="openSpectateDirect({ url: url, filename: filename, width: width, height: height, size: size })"
                                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-500 hover:text-amber-400 dark:text-amber-400 dark:hover:text-amber-300 transition-colors"
                                            title="Lihat poster dalam ukuran besar di popup"
                                        >
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <span>Lihat Poster</span>
                                        </button>
                                        @if ($poster['is_small'])
                                            <span class="inline-flex items-center rounded-md bg-warning/15 px-1.5 py-0.5 text-[10px] font-bold text-warning border border-warning/30" title="{{ $poster['warning'] }}">
                                                Resolusi Rendah
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="pt-2 border-t border-border space-y-2">
                                <div>
                                    <label class="block text-xs font-semibold text-text mb-1">Pasangkan ke Film</label>

                                    {{-- Wrapper capturing select changes seamlessly --}}
                                    <div @change="onSelectChange($event)" @synemax-select-changed="onSelectChange($event)">
                                        <x-ui.select
                                            name="film_id_{{ $posterKey }}"
                                            placeholder="Pilih film penerima..."
                                            size="sm"
                                            :options="$filmOptions"
                                        />
                                    </div>
                                </div>

                                <div class="flex items-center justify-between gap-2 pt-1">
                                    <button
                                        type="button"
                                        @click="assign()"
                                        :disabled="!selectedFilmId || isProcessing"
                                        class="inline-flex min-h-[34px] items-center justify-center gap-1.5 rounded-lg px-3.5 py-1 text-xs font-bold transition-all shadow-xs"
                                        :class="selectedFilmId && !isProcessing
                                            ? 'bg-amber-500 hover:bg-amber-400 text-slate-950 cursor-pointer'
                                            : 'bg-amber-500/40 text-slate-950/50 cursor-not-allowed opacity-60'"
                                    >
                                        <template x-if="isProcessing">
                                            <svg class="size-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </template>
                                        <span>Pasangkan</span>
                                    </button>

                                    <button
                                        type="button"
                                        @click="deleteCard()"
                                        :disabled="isProcessing"
                                        class="inline-flex min-h-[30px] items-center justify-center rounded-lg border border-danger/30 px-2.5 py-1 text-xs font-semibold text-danger hover:bg-danger/10 transition-colors disabled:opacity-50"
                                        title="Hapus poster ini dari antrean"
                                    >
                                        <template x-if="isProcessing">
                                            <svg class="size-3 animate-spin mr-1" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </template>
                                        <span>Hapus Poster</span>
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
            },

            async deleteActivityItem(item, idx) {
                item._isDeleting = true;
                try {
                    if (item.status === 'matched') {
                        // Undo matched poster on the film
                        const res = await fetch('{{ route('admin.films.posters.remove_matched') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                film_id: item.film_id,
                                stored_path: item.stored_path
                            })
                        });
                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.results.splice(idx, 1);
                            this.stats.matched = Math.max(0, this.stats.matched - 1);
                            this.totalCount = Math.max(0, this.totalCount - 1);
                            if (typeof window.showToast === 'function') {
                                window.showToast({ type: 'info', message: data.message || `Poster film "${item.film_title}" berhasil dihapus.` });
                            }
                        } else {
                            throw new Error(data.message || 'Gagal menghapus poster.');
                        }
                    } else if (item.status === 'unmatched') {
                        // Delete unmatched temporary file
                        const targetFile = item.unmatched_filename || item.filename;
                        const url = '{{ route('admin.films.posters.destroy', '__FILE__') }}'.replace('__FILE__', encodeURIComponent(targetFile));
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
                            this.results.splice(idx, 1);
                            this.stats.unmatched = Math.max(0, this.stats.unmatched - 1);
                            this.totalCount = Math.max(0, this.totalCount - 1);
                            window.dispatchEvent(new CustomEvent('poster-card-deleted', { detail: targetFile }));
                            if (typeof window.showToast === 'function') {
                                window.showToast({ type: 'info', message: `Berkas "${item.filename}" berhasil dihapus dari antrean.` });
                            }
                        } else {
                            throw new Error(data.message || 'Gagal menghapus berkas.');
                        }
                    } else {
                        // Failed item
                        this.results.splice(idx, 1);
                        this.stats.failed = Math.max(0, this.stats.failed - 1);
                        this.totalCount = Math.max(0, this.totalCount - 1);
                    }
                } catch (err) {
                    if (typeof window.showToast === 'function') {
                        window.showToast({ type: 'error', message: err.message });
                    }
                } finally {
                    item._isDeleting = false;
                }
            },

            async deleteAllUnmatchedInBatch() {
                const unmatched = this.results.filter(i => i.status === 'unmatched');
                if (unmatched.length === 0) return;
                for (let item of [...unmatched]) {
                    const idx = this.results.indexOf(item);
                    if (idx !== -1) {
                        await this.deleteActivityItem(item, idx);
                    }
                }
            },

            openSpectateFromActivity(item) {
                window.dispatchEvent(new CustomEvent('open-spectate-modal', {
                    detail: {
                        url: item.poster_url,
                        filename: item.filename,
                        width: '',
                        height: '',
                        size: ''
                    }
                }));
            }
        };
    }

    function posterCardItem(config) {
        return {
            filename: config.filename,
            posterKey: config.posterKey,
            url: config.url,
            width: config.width || 0,
            height: config.height || 0,
            size: config.size || '',
            selectedFilmId: '',
            isProcessing: false,
            isDeleted: false,

            onSelectChange(e) {
                const val = (typeof e === 'object' && e !== null)
                    ? (e.detail?.value !== undefined ? e.detail.value : (e.target?.value !== undefined ? e.target.value : e.detail))
                    : e;
                this.selectedFilmId = val ? String(val).trim() : '';
                if (typeof this.setFilmSelected === 'function') {
                    this.setFilmSelected(this.filename, this.selectedFilmId);
                }
            },

            async assign() {
                if (!this.selectedFilmId) {
                    if (typeof window.showToast === 'function') {
                        window.showToast({ type: 'warning', message: 'Pilih film penerima terlebih dahulu pada poster ini.' });
                    }
                    return;
                }

                this.isProcessing = true;
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
                            poster_filename: this.filename,
                            film_id: parseInt(this.selectedFilmId, 10)
                        })
                    });

                    const data = await res.json();
                    if (res.ok && data.success) {
                        this.isDeleted = true;
                        const el = document.getElementById('card-' + this.posterKey);
                        if (el) {
                            el.style.transition = 'all 0.28s cubic-bezier(0.4, 0, 0.2, 1)';
                            el.style.opacity = '0';
                            el.style.transform = 'scale(0.92)';
                            setTimeout(() => { try { el.remove(); } catch (err) {} }, 280);
                        }
                        if (typeof this.removeCardFromDisplay === 'function') {
                            this.removeCardFromDisplay(this.filename);
                        }
                        window.dispatchEvent(new CustomEvent('poster-card-deleted', { detail: this.filename }));
                        if (typeof window.showToast === 'function') {
                            window.showToast({
                                type: 'success',
                                message: data.message || `Poster berhasil dipasangkan ke film "${data.film_title}".`
                            });
                        }
                    } else {
                        if (typeof window.showToast === 'function') {
                            window.showToast({
                                type: 'error',
                                message: data.message || 'Gagal memasangkan poster.'
                            });
                        }
                    }
                } catch (err) {
                    if (typeof window.showToast === 'function') {
                        window.showToast({
                            type: 'error',
                            message: 'Gagal menghubungi server: ' + err.message
                        });
                    }
                } finally {
                    this.isProcessing = false;
                }
            },

            deleteCard() {
                if (typeof window.openConfirmModal === 'function') {
                    window.openConfirmModal({
                        title: 'Hapus Poster Antrean',
                        message: `Hapus berkas poster "${this.filename}" dari antrean poster belum terpasang?`,
                        confirmText: 'Hapus Poster',
                        confirmClass: 'bg-danger text-white hover:bg-danger/90',
                        cancelText: 'Batal',
                        onConfirm: () => {
                            this.executeDelete();
                        }
                    });
                } else {
                    this.executeDelete();
                }
            },

            async executeDelete() {
                this.isProcessing = true;
                try {
                    const url = '{{ route('admin.films.posters.destroy', '__FILE__') }}'.replace('__FILE__', encodeURIComponent(this.filename));
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
                        this.isDeleted = true;
                        const el = document.getElementById('card-' + this.posterKey);
                        if (el) {
                            el.style.transition = 'all 0.28s cubic-bezier(0.4, 0, 0.2, 1)';
                            el.style.opacity = '0';
                            el.style.transform = 'scale(0.92)';
                            setTimeout(() => { try { el.remove(); } catch (err) {} }, 280);
                        }
                        if (typeof this.removeCardFromDisplay === 'function') {
                            this.removeCardFromDisplay(this.filename);
                        }
                        window.dispatchEvent(new CustomEvent('poster-card-deleted', { detail: this.filename }));
                        if (typeof window.showToast === 'function') {
                            window.showToast({
                                type: 'info',
                                message: 'Poster berhasil dihapus dari antrean.'
                            });
                        }
                    } else {
                        if (typeof window.showToast === 'function') {
                            window.showToast({
                                type: 'error',
                                message: data.message || 'Gagal menghapus poster.'
                            });
                        }
                    }
                } catch (err) {
                    if (typeof window.showToast === 'function') {
                        window.showToast({
                            type: 'error',
                            message: 'Gagal menghapus berkas: ' + err.message
                        });
                    }
                } finally {
                    this.isProcessing = false;
                }
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
            spectateModalOpen: false,
            spectateData: { url: '', filename: '', width: '', height: '', size: '' },
            spectateIndex: -1,

            get activePosterList() {
                return this.posterList.filter(p => !this.removedPosters[p.filename]);
            },

            openSpectateDirect(data) {
                this.spectateData = data || { url: '', filename: '', width: '', height: '', size: '' };
                this.spectateIndex = -1;
                this.spectateModalOpen = true;
                document.body.style.overflow = 'hidden';
            },

            openSpectate(filename) {
                const list = this.activePosterList;
                const idx = list.findIndex(p => p.filename === filename);
                if (idx !== -1) {
                    this.spectateIndex = idx;
                    this.spectateData = list[idx];
                } else {
                    const fallback = this.posterList.find(p => p.filename === filename);
                    this.spectateData = fallback || { url: '', filename: filename, width: '', height: '', size: '' };
                    this.spectateIndex = 0;
                }
                this.spectateModalOpen = true;
                document.body.style.overflow = 'hidden';
            },

            closeSpectate() {
                this.spectateModalOpen = false;
                document.body.style.overflow = '';
            },

            spectatePrev() {
                const list = this.activePosterList;
                if (list.length <= 1) return;
                this.spectateIndex = (this.spectateIndex - 1 + list.length) % list.length;
                this.spectateData = list[this.spectateIndex];
            },

            spectateNext() {
                const list = this.activePosterList;
                if (list.length <= 1) return;
                this.spectateIndex = (this.spectateIndex + 1) % list.length;
                this.spectateData = list[this.spectateIndex];
            },

            onSpectateKeyDown(e) {
                if (!this.spectateModalOpen) return;
                if (e.key === 'Escape') {
                    this.closeSpectate();
                } else if (e.key === 'ArrowLeft') {
                    this.spectatePrev();
                } else if (e.key === 'ArrowRight') {
                    this.spectateNext();
                }
            },

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

            setFilmSelected(filename, filmId) {
                const idStr = filmId ? String(filmId).trim() : '';
                if (idStr) {
                    this.selectedFilms = { ...this.selectedFilms, [filename]: idStr };
                } else {
                    const next = { ...this.selectedFilms };
                    delete next[filename];
                    this.selectedFilms = next;
                }
            },

            onFilmSelected(filename, val) {
                const filmId = (typeof val === 'object' && val !== null) ? (val.target?.value || val.detail?.value || '') : val;
                this.setFilmSelected(filename, filmId);
            },

            removeCardFromDisplay(filename) {
                // 1. Reassign removedPosters with new object reference for Alpine reactivity
                this.removedPosters = { ...this.removedPosters, [filename]: true };

                // 2. Clean up selectedFilms
                const newSelected = { ...this.selectedFilms };
                delete newSelected[filename];
                this.selectedFilms = newSelected;

                // 3. Immediately animate and remove the card element from DOM
                const item = this.posterList.find(p => p.filename === filename);
                const cardEl = (item?.posterKey ? document.getElementById('card-' + item.posterKey) : null)
                    || document.querySelector(`[data-poster-card="${filename.replace(/"/g, '\\"')}"]`);
                if (cardEl) {
                    cardEl.style.transition = 'all 0.28s cubic-bezier(0.4, 0, 0.2, 1)';
                    cardEl.style.opacity = '0';
                    cardEl.style.transform = 'scale(0.92)';
                    setTimeout(() => {
                        try { cardEl.remove(); } catch (err) {}
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
                        this.removeCardFromDisplay(filename);
                        this.notify('success', data.message || 'Poster berhasil dipasangkan.');
                    } else {
                        this.notify('error', data.message || 'Gagal memasangkan poster.');
                    }
                } catch (err) {
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

                    let data = null;
                    try {
                        data = await res.json();
                    } catch (parseErr) {
                        throw new Error('Respon server tidak valid atau terjadi timeout (HTTP ' + res.status + ').');
                    }

                    if (res.ok && data.success && data.assigned_count > 0) {
                        const successfulItems = data.items || [];
                        const successfulFnames = [];
                        for (let i = 0; i < successfulItems.length; i++) {
                            const fname = successfulItems[i].filename || successfulItems[i].poster_filename;
                            successfulFnames.push(fname);
                            this.removeCardFromDisplay(fname);
                            window.dispatchEvent(new CustomEvent('poster-card-deleted', { detail: fname }));
                        }

                        this.batchPreviewItems = this.batchPreviewItems.filter(i => !successfulFnames.includes(i.filename));
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
                    } else if (res.ok && data.success && data.assigned_count === 0) {
                        this.notify('warning', data.message || 'Tidak ada poster yang berhasil dipasangkan.');
                    } else {
                        this.notify('error', data?.message || ('Gagal memasangkan kumpulan poster (HTTP ' + res.status + ').'));
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
