@extends('layouts.admin')

@section('title', 'Pratinjau & Impor Film')

@section('content')
<div
    class="space-y-6"
    x-data="{
        activeStep: '{{ !empty($autoResume) ? 'importing' : 'preview' }}', // 'preview' | 'importing' | 'completed'
        filterStatus: 'all',
        duplicateStrategy: '{{ $duplicateStrategy ?? 'skip' }}',
        batchToken: '{{ $batchToken }}',
        totalRows: {{ count($rows) }},
        chunkSize: 25,
        excludedRows: [],
        
        // Progress State
        progressPercent: {{ !empty($rows) ? min(100, (int) round((($initialProcessed ?? 0) / max(1, count($rows))) * 100)) : 0 }},
        processedCount: {{ $initialProcessed ?? 0 }},
        importedCount: {{ $initialImported ?? 0 }},
        skippedCount: {{ $initialSkipped ?? 0 }},
        failedCount: {{ $initialFailed ?? 0 }},
        currentChunk: {{ $initialChunk ?? 0 }},
        totalChunks: Math.ceil({{ count($rows) }} / 25),
        isProcessing: false,
        isDone: false,
        hasError: false,
        statusMessage: '{{ !empty($autoResume) ? 'Melanjutkan proses impor batch...' : 'Menyiapkan proses impor...' }}',

        init() {
            @if (!empty($autoResume))
                this.$nextTick(() => {
                    this.startChunkedImport(true);
                });
            @endif
        },

        toggleRow(idx) {
            if (this.excludedRows.includes(idx)) {
                this.excludedRows = this.excludedRows.filter(i => i !== idx);
            } else {
                this.excludedRows.push(idx);
            }
        },
        isExcluded(idx) {
            return this.excludedRows.includes(idx);
        },
        get includedCount() {
            return Math.max(0, this.totalRows - this.excludedRows.length);
        },

        // Map unknown genre to canonical
        mapGenre(idx, rawToken, targetGenre) {
            const input = document.getElementById('row-genre-' + idx);
            if (!input) return;
            let current = input.value;
            let tokens = current.split(/[,;\/|]+/).map(t => t.trim());
            let updated = tokens.map(t => t.toLowerCase() === rawToken.toLowerCase() ? targetGenre : t);
            input.value = Array.from(new Set(updated)).filter(Boolean).join(', ');
            input.dispatchEvent(new Event('input'));
        },
        // Remove unknown genre
        removeGenre(idx, rawToken) {
            const input = document.getElementById('row-genre-' + idx);
            if (!input) return;
            let current = input.value;
            let tokens = current.split(/[,;\/|]+/).map(t => t.trim());
            let updated = tokens.filter(t => t.toLowerCase() !== rawToken.toLowerCase());
            input.value = Array.from(new Set(updated)).filter(Boolean).join(', ');
            input.dispatchEvent(new Event('input'));
        },

        // Step 3: Chunked AJAX Import
        async startChunkedImport(isResume = false) {
            this.activeStep = 'importing';
            this.isProcessing = true;
            this.hasError = false;
            if (!isResume && this.currentChunk === 0 && this.processedCount === 0) {
                this.currentChunk = 0;
                this.processedCount = 0;
                this.importedCount = 0;
                this.skippedCount = 0;
                this.failedCount = 0;
                this.progressPercent = 0;
            }

            const form = document.getElementById('preview-form');
            const formData = new FormData(form);

            // Collect rows object from form inputs
            const rowsPayload = {};
            for (let pair of formData.entries()) {
                const match = pair[0].match(/^rows\[(\d+)\]\[([a-z_]+)\]$/);
                if (match) {
                    const rIdx = match[1];
                    const key = match[2];
                    if (!rowsPayload[rIdx]) rowsPayload[rIdx] = {};
                    rowsPayload[rIdx][key] = pair[1];
                }
            }

            while (this.processedCount < this.totalRows && this.isProcessing) {
                this.statusMessage = `Memproses potongan ${this.currentChunk + 1} dari ${this.totalChunks} (${Math.min(this.totalRows, (this.currentChunk + 1) * this.chunkSize)} dari ${this.totalRows} baris)...`;

                try {
                    const response = await fetch('{{ route('admin.films.import.chunk') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            batch_token: this.batchToken,
                            chunk_index: this.currentChunk,
                            chunk_size: this.chunkSize,
                            duplicate_strategy: this.duplicateStrategy,
                            excluded_rows: this.excludedRows,
                            rows: rowsPayload
                        })
                    });

                    if (!response.ok) {
                        const errData = await response.json().catch(() => ({}));
                        throw new Error(errData.message || 'Gagal memproses potongan impor server.');
                    }

                    const res = await response.json();
                    this.processedCount = res.processed;
                    this.progressPercent = res.percent;
                    this.importedCount = res.batch_imported;
                    this.skippedCount = res.batch_skipped;
                    this.failedCount = res.batch_failed;

                    if (res.is_last || this.processedCount >= this.totalRows) {
                        this.isDone = true;
                        this.isProcessing = false;
                        this.activeStep = 'completed';
                        this.statusMessage = 'Seluruh data berhasil diproses!';
                        break;
                    }

                    this.currentChunk++;
                } catch (err) {
                    this.isProcessing = false;
                    this.hasError = true;
                    this.statusMessage = 'Terjadi kesalahan: ' + err.message;
                    break;
                }
            }
        }
    }"
>
    {{-- Header Halaman --}}
    <x-admin.page-header
        eyebrow="Impor Massal"
        title="Langkah 2 & 3: Periksa & Impor"
        subtitle="Periksa keabsahan data, sesuaikan bila ada kesalahan, lalu impor ke dalam katalog bioskop."
    >
        <div class="flex items-center gap-2">
            <a
                href="{{ route('admin.films.import.create') }}"
                class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-slate-300 dark:border-white/20 bg-surface px-4 py-2 text-xs font-bold text-text-muted hover:text-text transition-colors"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Unggah Ulang File</span>
            </a>
            <a
                href="{{ route('admin.films.index') }}"
                class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-slate-300 dark:border-white/20 bg-surface px-4 py-2 text-xs font-bold text-text-muted hover:text-text transition-colors"
            >
                <span>Batal</span>
            </a>
        </div>
    </x-admin.page-header>

    {{-- Ringkasan Metrik (Langkah 2) --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <div class="rounded-2xl border border-border bg-surface p-4 shadow-token cursor-pointer hover:border-amber-500 transition" @click="filterStatus = 'all'">
            <p class="text-xs text-text-muted font-medium">Total Baris</p>
            <p class="text-2xl font-black text-text mt-1">{{ $summary['total'] }}</p>
            <span class="text-[10px] text-text-muted">Semua baris di file</span>
        </div>
        <div class="rounded-2xl border border-success/30 bg-success/5 p-4 shadow-token cursor-pointer hover:border-success transition" @click="filterStatus = 'baru'">
            <p class="text-xs text-success font-semibold">Baru</p>
            <p class="text-2xl font-black text-success mt-1">{{ $summary['baru'] }}</p>
            <span class="text-[10px] text-success/80">Belum ada di database</span>
        </div>
        <div class="rounded-2xl border border-warning/30 bg-warning/5 p-4 shadow-token cursor-pointer hover:border-warning transition" @click="filterStatus = 'duplikat'">
            <p class="text-xs text-warning font-semibold">Duplikat</p>
            <p class="text-2xl font-black text-warning mt-1">{{ $summary['duplikat'] }}</p>
            <span class="text-[10px] text-warning/80">
                Judul & tahun sama
            </span>
        </div>
        <div class="rounded-2xl border border-danger/30 bg-danger/5 p-4 shadow-token cursor-pointer hover:border-danger transition" @click="filterStatus = 'masalah'">
            <p class="text-xs text-danger font-semibold">Ada Masalah</p>
            <p class="text-2xl font-black text-danger mt-1">{{ $summary['masalah'] }}</p>
            <span class="text-[10px] text-danger/80">Perlu dikoreksi</span>
        </div>
        <div class="rounded-2xl border border-border bg-surface p-4 shadow-token">
            <p class="text-xs text-text-muted font-medium">Poster Ditemukan</p>
            <p class="text-2xl font-black text-text mt-1">{{ $summary['has_poster'] }}</p>
            <span class="text-[10px] text-text-muted">dari {{ $totalPosters }} berkas ZIP</span>
        </div>
    </div>

    {{-- Alert Ringkasan B1 --}}
    <div class="rounded-2xl border border-border bg-surface p-4 text-xs text-text flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
        <div>
            <span class="font-bold text-sm text-text">
                {{ $summary['total'] }} baris: {{ $summary['baru'] }} baru, {{ $summary['duplikat'] }} duplikat, {{ $summary['masalah'] }} bermasalah.
            </span>
            <p class="text-text-muted mt-0.5">
                Admin dapat memperbaiki data langsung di tabel sebelum mengimpor. Baris dengan status bermasalah dapat diperbaiki atau diabaikan.
            </p>
        </div>
        <div class="w-full sm:w-72 shrink-0">
            <x-ui.select
                name="duplicate_strategy_selector"
                label="Perlakuan Duplikat"
                :value="$duplicateStrategy"
                :options="[
                    'skip' => 'Lewati (bawaan)',
                    'update' => 'Perbarui data film yang ada',
                ]"
            />
        </div>
    </div>

    {{-- ============================================================== --}}
    {{-- LANGKAH 3: PROGRES BAR IN-APP & HASIL IMPOR                    --}}
    {{-- ============================================================== --}}
    <div
        x-show="activeStep === 'importing' || activeStep === 'completed'"
        x-cloak
        class="rounded-2xl border border-amber-500/40 bg-surface p-6 shadow-token space-y-4"
    >
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-text">
                    <span x-show="activeStep === 'importing'">Langkah 3: Mengimpor Film ke Katalog</span>
                    <span x-show="activeStep === 'completed'" class="text-success">Impor Film Selesai!</span>
                </h3>
                <p class="text-xs text-text-muted mt-0.5" x-text="statusMessage"></p>
            </div>
            <div class="text-right">
                <span class="text-xl font-black text-amber-500" x-text="progressPercent + '%'"></span>
            </div>
        </div>

        {{-- Progress Bar --}}
        <div class="h-3 w-full rounded-full bg-surface-2 overflow-hidden border border-border">
            <div
                class="h-full bg-amber-500 transition-all duration-300"
                :style="'width: ' + progressPercent + '%'"
            ></div>
        </div>

        {{-- Counters Live --}}
        <div class="grid grid-cols-3 gap-3 text-center text-xs">
            <div class="rounded-xl border border-success/30 bg-success/5 p-2.5">
                <p class="text-text-muted text-[11px]">Berhasil Diimpor</p>
                <p class="text-lg font-black text-success mt-0.5" x-text="importedCount"></p>
            </div>
            <div class="rounded-xl border border-warning/30 bg-warning/5 p-2.5">
                <p class="text-text-muted text-[11px]">Dilewati (Duplikat)</p>
                <p class="text-lg font-black text-warning mt-0.5" x-text="skippedCount"></p>
            </div>
            <div class="rounded-xl border border-danger/30 bg-danger/5 p-2.5">
                <p class="text-text-muted text-[11px]">Ada Masalah / Gagal</p>
                <p class="text-lg font-black text-danger mt-0.5" x-text="failedCount"></p>
            </div>
        </div>

        {{-- Tombol Aksi Pasca Selesai --}}
        <div x-show="activeStep === 'completed'" class="pt-3 border-t border-border flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <a
                    :href="'{{ route('admin.films.index') }}?batch_id=' + batchToken"
                    class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-amber-500 px-5 py-2 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span>Lihat Film yang Baru Masuk</span>
                </a>

                <a
                    :href="'{{ url('/admin/films/impor/report-errors') }}/' + batchToken"
                    class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-white/20 bg-surface px-4 py-2 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition shadow-xs"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Unduh Laporan Baris Bermasalah (CSV)</span>
                </a>
            </div>

            <a
                href="{{ route('admin.films.index') }}"
                class="text-xs font-semibold text-text-muted hover:text-text"
            >
                Kembali ke Katalog Lengkap →
            </a>
        </div>
    </div>

    {{-- ============================================================== --}}
    {{-- LANGKAH 2: FORM & TABEL PRATINJAU INTERAKTIF                  --}}
    {{-- ============================================================== --}}
    <form
        id="preview-form"
        action="{{ route('admin.films.import.confirm') }}"
        method="POST"
        novalidate
        x-show="activeStep === 'preview'"
        class="space-y-4"
    >
        @csrf
        <input type="hidden" name="batch_token" value="{{ $batchToken }}">
        <input type="hidden" name="duplicate_strategy" :value="duplicateStrategy">

        {{-- Hidden input array untuk baris yang tidak dicentang --}}
        <template x-for="idx in excludedRows" :key="'ex-'+idx">
            <input type="hidden" name="excluded_rows[]" :value="idx">
        </template>

        {{-- Banner Ringkasan Genre Baru yang Akan Dibuat --}}
        @if (!empty($newGenresList))
            <div class="rounded-xl border border-amber-500/40 bg-amber-500/10 p-4 text-xs text-text flex items-start gap-3">
                <div class="p-1.5 rounded-lg bg-amber-500/20 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="space-y-1.5">
                    <p class="font-bold text-text">
                        {{ count($newGenresList) }} genre baru akan dibuat otomatis di database:
                    </p>
                    <div class="flex flex-wrap items-center gap-1.5">
                        @foreach ($newGenresList as $ng)
                            <span class="inline-flex items-center rounded-md bg-amber-500/25 px-2.5 py-1 text-xs font-bold text-amber-800 dark:text-amber-300 border border-amber-500/40">
                                {{ $ng }}
                            </span>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-text-muted">
                        Genre baru akan otomatis tersimpan ke tabel genre saat impor dieksekusi. Genre yang dibuat batch tidak ikut terhapus saat pembatalan impor; Anda dapat mengelolanya di halaman Kelola Genre.
                    </p>
                </div>
            </div>
        @endif

        {{-- Filter Bar & Tombol Impor N Film --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center flex-wrap gap-2">
                <span class="text-xs font-semibold text-text-muted">Filter Cepat:</span>
                <div class="inline-flex rounded-xl border border-border bg-surface p-1 text-xs">
                    <button
                        type="button"
                        @click="filterStatus = 'all'"
                        :class="filterStatus === 'all' ? 'bg-amber-500 font-bold text-slate-950' : 'text-text hover:text-amber-500'"
                        class="px-2.5 py-1 rounded-lg transition"
                    >Semua ({{ $summary['total'] }})</button>
                    <button
                        type="button"
                        @click="filterStatus = 'baru'"
                        :class="filterStatus === 'baru' ? 'bg-amber-500 font-bold text-slate-950' : 'text-text hover:text-amber-500'"
                        class="px-2.5 py-1 rounded-lg transition"
                    >Baru ({{ $summary['baru'] }})</button>
                    <button
                        type="button"
                        @click="filterStatus = 'duplikat'"
                        :class="filterStatus === 'duplikat' ? 'bg-amber-500 font-bold text-slate-950' : 'text-text hover:text-amber-500'"
                        class="px-2.5 py-1 rounded-lg transition"
                    >Duplikat ({{ $summary['duplikat'] }})</button>
                    <button
                        type="button"
                        @click="filterStatus = 'masalah'"
                        :class="filterStatus === 'masalah' ? 'bg-amber-500 font-bold text-slate-950' : 'text-text hover:text-amber-500'"
                        class="px-2.5 py-1 rounded-lg transition"
                    >Ada Masalah ({{ $summary['masalah'] }})</button>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button
                    type="button"
                    id="start-import-btn"
                    @click="startChunkedImport()"
                    class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-amber-500 px-6 py-2.5 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition-colors"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12" />
                    </svg>
                    <span>Impor <span x-text="includedCount"></span> Film</span>
                </button>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- VIEW MOBILE: KARTU BERTUMPUK DI BAWAH 1024px                   --}}
        {{-- ============================================================== --}}
        <div class="block lg:hidden space-y-4">
            @foreach ($rows as $index => $row)
                @php
                    $rowStatus = $row['status'];
                    $isBaru = in_array($rowStatus, ['baru', 'valid'], true);
                    $isDup = in_array($rowStatus, ['duplikat', 'duplicate'], true);
                    $isMasalah = in_array($rowStatus, ['masalah', 'invalid', 'error'], true);
                    $cellErrors = $row['cell_errors'] ?? [];
                    $rowNewGenres = $row['new_genres'] ?? [];
                @endphp
                <div
                    x-show="filterStatus === 'all' || (filterStatus === 'baru' && {{ $isBaru ? 'true' : 'false' }}) || (filterStatus === 'duplikat' && {{ $isDup ? 'true' : 'false' }}) || (filterStatus === 'masalah' && {{ $isMasalah ? 'true' : 'false' }})"
                    :class="isExcluded({{ $index }}) ? 'opacity-40 bg-surface-2/20' : 'bg-surface'"
                    class="rounded-2xl border border-border p-4 shadow-xs space-y-3"
                    x-data="{
                        chips: @js(array_values(array_filter(array_map('trim', explode(',', (string) $row['genre']))))),
                        inputVal: '',
                        addChip(val) {
                            const clean = (val || this.inputVal).trim();
                            if (!clean) return;
                            const parts = clean.split(/[,;\n]+/).map(p => p.trim()).filter(Boolean);
                            for (let p of parts) {
                                if (!this.chips.map(c => c.toLowerCase()).includes(p.toLowerCase())) {
                                    this.chips.push(p);
                                }
                            }
                            this.inputVal = '';
                        },
                        removeChip(idx) {
                            this.chips.splice(idx, 1);
                        },
                        replaceChip(oldVal, newVal) {
                            const idx = this.chips.findIndex(c => c.toLowerCase() === oldVal.toLowerCase());
                            if (idx !== -1) {
                                this.chips.splice(idx, 1, newVal);
                            }
                        }
                    }"
                >
                    {{-- Header Kartu --}}
                    <div class="flex items-center justify-between border-b border-border pb-2.5">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                :checked="!isExcluded({{ $index }})"
                                @change="toggleRow({{ $index }})"
                                aria-label="Sertakan baris {{ $index + 1 }}"
                                class="rounded border-border text-amber-500 focus:ring-amber-500"
                            >
                            <span class="font-bold text-xs text-text">Baris #{{ $index + 1 }}</span>
                        </label>
                        <div>
                            @if ($isBaru)
                                <span class="inline-flex items-center rounded-md bg-emerald-500/15 px-2 py-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                                    Baru
                                </span>
                            @elseif ($isDup)
                                <span class="inline-flex items-center rounded-md bg-amber-500/15 px-2 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                    Duplikat
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-md bg-rose-500/15 px-2 py-0.5 text-[10px] font-bold text-rose-600 dark:text-rose-400 border border-rose-500/30">
                                    Ada Masalah
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Judul Film --}}
                    <div>
                        <label class="block text-[11px] font-bold text-text mb-1">Judul Film</label>
                        <textarea
                            name="rows[{{ $index }}][title]"
                            rows="2"
                            class="w-full min-h-[38px] max-h-24 overflow-y-auto resize-y rounded-lg border {{ isset($cellErrors['title']) ? 'border-rose-500 ring-1 ring-rose-500' : 'border-border' }} bg-surface px-2.5 py-1 text-xs font-bold text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                        >{{ $row['title'] }}</textarea>
                        @if (isset($cellErrors['title']))
                            <p class="text-[10px] text-rose-500 mt-0.5 font-semibold">{{ $cellErrors['title'] }}</p>
                        @endif
                    </div>

                    {{-- Genre Chips --}}
                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-bold text-text">Genre (Chip Pembungkus)</label>
                        <input type="hidden" name="rows[{{ $index }}][genre]" :value="chips.join(', ')">
                        
                        <div class="flex flex-wrap items-center gap-1.5">
                            <template x-for="(chip, chipIdx) in chips" :key="chipIdx">
                                <span class="inline-flex items-center gap-1 rounded-md bg-surface-2 px-2 py-0.5 text-[11px] font-bold text-text border border-border">
                                    <span x-text="chip"></span>
                                    <button type="button" @click="removeChip(chipIdx)" class="text-text-muted hover:text-rose-500 text-xs leading-none">&times;</button>
                                </span>
                            </template>
                        </div>

                        {{-- Genre Typo Warnings / New Genre badges --}}
                        @if (!empty($rowNewGenres))
                            <div class="space-y-1 pt-1">
                                @foreach ($rowNewGenres as $rng)
                                    <div class="flex items-center gap-1.5 flex-wrap text-[10px] p-1.5 rounded-lg bg-amber-500/10 border border-amber-500/30 text-text">
                                        <span class="font-bold text-amber-600 dark:text-amber-400">Genre baru: {{ $rng['name'] }}</span>
                                        @if (!empty($rng['typo_warning']))
                                            <span class="text-text-muted">(Mirip '{{ $rng['typo_warning']['similar_to'] }}')</span>
                                            <button
                                                type="button"
                                                @click="replaceChip('{{ $rng['name'] }}', '{{ $rng['typo_warning']['similar_to'] }}')"
                                                class="px-1.5 py-0.5 rounded bg-amber-500 font-bold text-slate-950 hover:bg-amber-400"
                                            >
                                                Pakai {{ $rng['typo_warning']['similar_to'] }}
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="pt-1">
                            <input
                                type="text"
                                x-model="inputVal"
                                @keydown.enter.prevent="addChip()"
                                @keydown.comma.prevent="addChip()"
                                placeholder="+ Tambah genre (Enter/koma)..."
                                class="w-full rounded-lg border border-border bg-surface px-2.5 py-1 text-xs text-text placeholder:text-text-muted focus:border-amber-500 focus:outline-none"
                            >
                        </div>
                    </div>

                    {{-- 3-Col Meta: Durasi, Klasifikasi, Tahun --}}
                    <div class="grid grid-cols-3 gap-2 pt-1 border-t border-border">
                        <div>
                            <label class="block text-[10px] font-semibold text-text-muted">Durasi (m)</label>
                            <input
                                type="number"
                                name="rows[{{ $index }}][duration_minutes]"
                                value="{{ $row['duration_minutes'] }}"
                                class="w-full text-center rounded-lg border {{ isset($cellErrors['duration_minutes']) ? 'border-rose-500 ring-1 ring-rose-500' : 'border-border' }} bg-surface px-1 py-1 text-xs text-text"
                            >
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-text-muted">Klasifikasi</label>
                            <x-ui.select
                                name="rows[{{ $index }}][age_rating]"
                                size="sm"
                                :value="$row['age_rating']"
                                :options="\App\Models\AgeRating::optionsArray($row['age_rating'])"
                            />
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-text-muted">Tahun</label>
                            <input
                                type="number"
                                name="rows[{{ $index }}][release_year]"
                                value="{{ $row['release_year'] }}"
                                placeholder="{{ date('Y') }}"
                                class="w-full text-center rounded-lg border border-border bg-surface px-1 py-1 text-xs text-text"
                            >
                        </div>
                    </div>

                    {{-- Sinopsis --}}
                    <div>
                        <label class="block text-[11px] font-bold text-text mb-1">Sinopsis</label>
                        <textarea
                            name="rows[{{ $index }}][synopsis]"
                            rows="2"
                            class="w-full min-h-[50px] max-h-24 overflow-y-auto resize-y rounded-lg border border-border bg-surface px-2.5 py-1 text-xs text-text focus:border-amber-500 resize-y"
                        >{{ $row['synopsis'] }}</textarea>
                    </div>

                    {{-- Errors & Warnings in full --}}
                    @if (!empty($row['errors']) || !empty($row['warnings']))
                        <div class="p-2.5 rounded-xl border border-rose-500/20 bg-rose-500/5 space-y-1 text-xs">
                            @foreach ((array) ($row['errors'] ?? []) as $err)
                                <p class="text-rose-600 dark:text-rose-400 font-semibold break-words leading-relaxed">&bull; {{ $err }}</p>
                            @endforeach
                            @foreach ((array) ($row['warnings'] ?? []) as $warn)
                                <p class="text-amber-600 dark:text-amber-400 text-[11px] break-words leading-relaxed">&bull; {{ $warn }}</p>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- ============================================================== --}}
        {{-- VIEW DESKTOP: TABEL LEBAR DENGAN STICKY JUDUL (>= 1024px)       --}}
        {{-- ============================================================== --}}
        <div class="hidden lg:block rounded-2xl border border-border bg-surface shadow-token overflow-x-auto max-h-[640px] relative">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="sticky top-0 bg-surface-2/95 backdrop-blur-sm z-20 border-b border-border text-[11px] font-bold text-text uppercase tracking-wider">
                    <tr>
                        <th class="p-3 w-10 text-center">Pilih</th>
                        <th class="p-3 w-12 text-center">No</th>
                        <th class="p-3 w-28">Status</th>
                        <th class="p-3 min-w-[220px] sticky left-0 bg-surface-2 z-30 border-r border-border shadow-xs">Judul Film</th>
                        <th class="p-3 min-w-[240px]">Genre</th>
                        <th class="p-3 w-24 text-center">Durasi (m)</th>
                        <th class="p-3 w-28 text-center">Klasifikasi</th>
                        <th class="p-3 w-24 text-center">Tahun</th>
                        <th class="p-3 min-w-[150px]">Poster</th>
                        <th class="p-3 min-w-[240px]">Sinopsis</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach ($rows as $index => $row)
                        @php
                            $rowStatus = $row['status'];
                            $isBaru = in_array($rowStatus, ['baru', 'valid'], true);
                            $isDup = in_array($rowStatus, ['duplikat', 'duplicate'], true);
                            $isMasalah = in_array($rowStatus, ['masalah', 'invalid', 'error'], true);
                            $cellErrors = $row['cell_errors'] ?? [];
                            $rowNewGenres = $row['new_genres'] ?? [];
                        @endphp
                        <tr
                            x-show="filterStatus === 'all' || (filterStatus === 'baru' && {{ $isBaru ? 'true' : 'false' }}) || (filterStatus === 'duplikat' && {{ $isDup ? 'true' : 'false' }}) || (filterStatus === 'masalah' && {{ $isMasalah ? 'true' : 'false' }})"
                            :class="isExcluded({{ $index }}) ? 'opacity-40 bg-surface-2/20' : ''"
                            class="hover:bg-surface-2/40 transition-colors"
                            x-data="{
                                chips: @js(array_values(array_filter(array_map('trim', explode(',', (string) $row['genre']))))),
                                inputVal: '',
                                addChip(val) {
                                    const clean = (val || this.inputVal).trim();
                                    if (!clean) return;
                                    const parts = clean.split(/[,;\n]+/).map(p => p.trim()).filter(Boolean);
                                    for (let p of parts) {
                                        if (!this.chips.map(c => c.toLowerCase()).includes(p.toLowerCase())) {
                                            this.chips.push(p);
                                        }
                                    }
                                    this.inputVal = '';
                                },
                                removeChip(idx) {
                                    this.chips.splice(idx, 1);
                                },
                                replaceChip(oldVal, newVal) {
                                    const idx = this.chips.findIndex(c => c.toLowerCase() === oldVal.toLowerCase());
                                    if (idx !== -1) {
                                        this.chips.splice(idx, 1, newVal);
                                    }
                                }
                            }"
                        >
                            {{-- Checkbox Include / Exclude --}}
                            <td class="p-3 text-center">
                                <input
                                    type="checkbox"
                                    :checked="!isExcluded({{ $index }})"
                                    @change="toggleRow({{ $index }})"
                                    aria-label="Sertakan baris {{ $index + 1 }}"
                                    class="rounded border-border text-amber-500 focus:ring-amber-500"
                                >
                            </td>

                            {{-- Nomor Baris --}}
                            <td class="p-3 text-center font-mono text-text-muted">
                                {{ $index + 1 }}
                            </td>

                            {{-- Status Chip (Baru / Duplikat / Ada Masalah) --}}
                            <td class="p-3">
                                @if ($isBaru)
                                    <span class="inline-flex items-center rounded-md bg-emerald-500/15 px-2 py-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                                        Baru
                                    </span>
                                @elseif ($isDup)
                                    <span class="inline-flex items-center rounded-md bg-amber-500/15 px-2 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-400 border border-amber-500/30" title="Film sudah ada di database">
                                        Duplikat
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-rose-500/15 px-2 py-0.5 text-[10px] font-bold text-rose-600 dark:text-rose-400 border border-rose-500/30" title="{{ implode('; ', (array) ($row['errors'] ?? [])) }}">
                                        Ada Masalah
                                    </span>
                                @endif

                                @if (!empty($row['errors']))
                                    <p class="text-[10px] text-rose-600 dark:text-rose-400 mt-1 font-semibold leading-tight break-words">
                                        {{ implode('; ', (array) $row['errors']) }}
                                    </p>
                                @endif
                                @if (!empty($row['warnings']))
                                    <p class="text-[10px] text-amber-600 dark:text-amber-400 mt-0.5 leading-tight break-words">
                                        {{ implode('; ', (array) $row['warnings']) }}
                                    </p>
                                @endif
                            </td>

                            {{-- Judul (Sticky Column on Desktop, Resizable & Scrollable) --}}
                            <td class="p-3 sticky left-0 bg-surface z-10 border-r border-border shadow-xs">
                                <textarea
                                    name="rows[{{ $index }}][title]"
                                    rows="2"
                                    class="w-full min-h-[38px] max-h-24 overflow-y-auto resize-y rounded-lg border {{ isset($cellErrors['title']) ? 'border-rose-500 ring-1 ring-rose-500' : 'border-border' }} bg-surface px-2.5 py-1 text-xs font-bold text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                                >{{ $row['title'] }}</textarea>
                                @if (isset($cellErrors['title']))
                                    <p class="text-[10px] text-rose-500 mt-0.5 font-semibold">{{ $cellErrors['title'] }}</p>
                                @endif
                            </td>

                            {{-- Genre (Chips Wrapping + Chip Input + Typo Suggestion) --}}
                            <td class="p-3 min-w-[240px]">
                                <input type="hidden" name="rows[{{ $index }}][genre]" :value="chips.join(', ')">

                                <div class="flex flex-wrap items-center gap-1.5">
                                    <template x-for="(chip, chipIdx) in chips" :key="chipIdx">
                                        <span class="inline-flex items-center gap-1 rounded-md bg-surface-2 px-2 py-0.5 text-[11px] font-bold text-text border border-border">
                                            <span x-text="chip"></span>
                                            <button type="button" @click="removeChip(chipIdx)" class="text-text-muted hover:text-rose-500 text-xs leading-none">&times;</button>
                                        </span>
                                    </template>
                                </div>

                                {{-- Genre Typo Warnings / New Genre badges --}}
                                @if (!empty($rowNewGenres))
                                    <div class="space-y-1 pt-1.5">
                                        @foreach ($rowNewGenres as $rng)
                                            <div class="flex items-center gap-1.5 flex-wrap text-[10px] p-1.5 rounded-lg bg-amber-500/10 border border-amber-500/30 text-text">
                                                <span class="font-bold text-amber-600 dark:text-amber-400">Genre baru: {{ $rng['name'] }}</span>
                                                @if (!empty($rng['typo_warning']))
                                                    <span class="text-text-muted">(Mirip '{{ $rng['typo_warning']['similar_to'] }}')</span>
                                                    <button
                                                        type="button"
                                                        @click="replaceChip('{{ $rng['name'] }}', '{{ $rng['typo_warning']['similar_to'] }}')"
                                                        class="px-1.5 py-0.5 rounded bg-amber-500 font-bold text-slate-950 hover:bg-amber-400"
                                                    >
                                                        Pakai {{ $rng['typo_warning']['similar_to'] }}
                                                    </button>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="pt-1.5">
                                    <input
                                        type="text"
                                        x-model="inputVal"
                                        @keydown.enter.prevent="addChip()"
                                        @keydown.comma.prevent="addChip()"
                                        placeholder="+ Tambah genre..."
                                        class="w-full rounded-lg border border-border bg-surface px-2 py-1 text-xs text-text placeholder:text-text-muted focus:border-amber-500 focus:outline-none"
                                    >
                                </div>
                            </td>

                            {{-- Durasi (Editable) --}}
                            <td class="p-3 text-center">
                                <input
                                    type="number"
                                    name="rows[{{ $index }}][duration_minutes]"
                                    value="{{ $row['duration_minutes'] }}"
                                    class="w-16 text-center rounded-lg border {{ isset($cellErrors['duration_minutes']) ? 'border-rose-500 ring-1 ring-rose-500' : 'border-border' }} bg-surface px-1.5 py-1 text-xs text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                                >
                                @if (isset($cellErrors['duration_minutes']))
                                    <p class="text-[10px] text-rose-500 mt-0.5 font-semibold">{{ $cellErrors['duration_minutes'] }}</p>
                                @endif
                            </td>

                            {{-- Klasifikasi Usia (Editable via x-ui.select) --}}
                            <td class="p-3 text-center min-w-[120px]">
                                <x-ui.select
                                    name="rows[{{ $index }}][age_rating]"
                                    size="sm"
                                    :value="$row['age_rating']"
                                    :options="\App\Models\AgeRating::optionsArray($row['age_rating'])"
                                />
                                @if (isset($cellErrors['age_rating']))
                                    <div class="mt-1 text-[10px] text-rose-500 text-left space-y-0.5 font-semibold">
                                        <p>{{ $cellErrors['age_rating'] }}</p>
                                        <a href="{{ Route::has('admin.age_ratings.index') ? route('admin.age_ratings.index') : url('/admin/age-ratings') }}" target="_blank" class="inline-flex items-center gap-1 font-bold text-amber-500 hover:text-amber-400 underline">
                                            <span>+ Tambah Klasifikasi</span>
                                            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    </div>
                                @endif
                            </td>

                            {{-- Tahun Rilis (Editable) --}}
                            <td class="p-3 text-center">
                                <input
                                    type="number"
                                    name="rows[{{ $index }}][release_year]"
                                    value="{{ $row['release_year'] }}"
                                    placeholder="{{ date('Y') }}"
                                    class="w-16 text-center rounded-lg border {{ isset($cellErrors['release_year']) ? 'border-rose-500 ring-1 ring-rose-500' : 'border-border' }} bg-surface px-1.5 py-1 text-xs text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                                >
                            </td>

                            {{-- Poster --}}
                            <td class="p-3">
                                @if (!empty($row['matched_poster']))
                                    <span class="inline-flex items-center gap-1 font-mono text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold" title="{{ $row['matched_poster'] }}">
                                        <svg class="size-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        <span class="truncate max-w-[120px]">{{ $row['matched_poster'] }}</span>
                                    </span>
                                    <input type="hidden" name="rows[{{ $index }}][matched_poster]" value="{{ $row['matched_poster'] }}">
                                @else
                                    <span class="text-[11px] text-text-muted italic">Tanpa poster</span>
                                    <input type="hidden" name="rows[{{ $index }}][matched_poster]" value="">
                                @endif
                            </td>

                            {{-- Sinopsis (Scrollable and Resizable Textarea) --}}
                            <td class="p-3 min-w-[240px]">
                                <textarea
                                    name="rows[{{ $index }}][synopsis]"
                                    rows="2"
                                    placeholder="Sinopsis (opsional)..."
                                    class="w-full min-h-[50px] max-h-24 overflow-y-auto resize-y rounded-lg border border-border bg-surface px-2.5 py-1 text-xs text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                                >{{ $row['synopsis'] }}</textarea>
                                @if (!empty($row['needs_synopsis']))
                                    <span class="inline-flex items-center rounded bg-amber-500/15 px-1.5 py-0.5 text-[9px] font-bold text-amber-600 dark:text-amber-400 mt-1">
                                        Perlu dilengkapi
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Bottom Bar Aksi --}}
        <div class="flex items-center justify-between pt-2">
            <span class="text-xs text-text-muted">
                Ditemukan <span class="font-bold text-text">{{ $summary['total'] }}</span> baris. Baris yang tidak dicentang akan dilewati dari impor.
            </span>

            <button
                type="button"
                @click="startChunkedImport()"
                class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-amber-500 px-8 py-2.5 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition-colors"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12" />
                </svg>
                <span>Impor <span x-text="includedCount"></span> Film Sekarang</span>
            </button>
        </div>
    </form>
</div>
@endsection
