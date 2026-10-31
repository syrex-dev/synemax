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

    {{-- Form Unggah Banyak Berkas / ZIP --}}
    <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-6">
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
                        class="block w-full text-xs text-text file:mr-4 file:rounded-xl file:border-0 file:bg-surface-2 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-text hover:file:bg-slate-200 dark:hover:file:bg-white/10 file:cursor-pointer border border-border rounded-xl p-2 bg-surface-2/40"
                    >
                    <p class="text-[11px] text-text-muted">
                        Maksimal 5 MB per gambar. Anda bisa memilih puluhan gambar sekaligus.
                    </p>
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
                        class="block w-full text-xs text-text file:mr-4 file:rounded-xl file:border-0 file:bg-surface-2 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-text hover:file:bg-slate-200 dark:hover:file:bg-white/10 file:cursor-pointer border border-border rounded-xl p-2 bg-surface-2/40"
                    >
                    <p class="text-[11px] text-text-muted">
                        Arsip ZIP berisi kumpulan berkas gambar poster. Maksimal 50 MB.
                    </p>
                </div>
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-amber-500 px-6 py-2.5 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition-colors w-full sm:w-auto"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12" />
                    </svg>
                    <span>Proses & Cocokkan Poster Otomatis</span>
                </button>
            </div>
        </form>
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
