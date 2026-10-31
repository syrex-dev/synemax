@extends('layouts.admin')

@section('title', 'Impor Film Massal')

@section('content')
<div class="space-y-6">
    <x-admin.page-header
        eyebrow="Katalog"
        title="Impor Film Massal"
        subtitle="Tambahkan ratusan koleksi film sekaligus melalui file CSV dan arsip ZIP poster."
    >
        <div class="flex items-center gap-2">
            <a
                href="{{ route('admin.films.import.template') }}"
                class="inline-flex min-h-[40px] items-center gap-2 rounded-xl border border-slate-300 dark:border-white/20 bg-surface px-4 py-2 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors shadow-xs"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>Unduh Templat CSV</span>
            </a>
            <a
                href="{{ route('admin.films.posters.index') }}"
                class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-slate-300 dark:border-white/20 bg-surface px-4 py-2 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors shadow-xs"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Poster Massal</span>
            </a>
            <a
                href="{{ route('admin.films.index') }}"
                class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-slate-300 dark:border-white/20 bg-surface px-4 py-2 text-xs font-bold text-text-muted hover:text-text transition-colors"
            >
                <span>Kembali ke Katalog</span>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Form Upload (2 Kolom di Desktop) --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-6">
                <div>
                    <h2 class="text-base font-bold text-text">Langkah 1: Unggah Berkas</h2>
                    <p class="text-xs text-text-muted mt-1">Pilih berkas CSV data film dan opsional arsip ZIP poster (maksimal 2.000 baris dan 5 MB per berkas CSV).</p>
                </div>

                <form
                    action="{{ route('admin.films.import.preview') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="space-y-5"
                >
                    @csrf

                    {{-- File CSV --}}
                    <div class="space-y-1.5">
                        <label for="csv_file" class="block text-xs font-bold text-text">
                            Berkas CSV Data Film <span class="text-danger">*</span>
                        </label>
                        <input
                            type="file"
                            id="csv_file"
                            name="csv_file"
                            accept=".csv,.txt"
                            required
                            class="block w-full text-xs text-text file:mr-4 file:rounded-xl file:border-0 file:bg-surface-2 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-text hover:file:bg-slate-200 dark:hover:file:bg-white/10 file:cursor-pointer border border-border rounded-xl p-2 bg-surface-2/40"
                        >
                        <p class="text-[11px] text-text-muted">
                            Deteksi otomatis pemisah koma (,), titik koma (;), tab (\t), atau pipa (|), serta encoding UTF-8 (dengan/tanpa BOM). Maksimal 2.000 baris dan 5 MB.
                        </p>
                    </div>

                    {{-- File ZIP Poster --}}
                    <div class="space-y-1.5">
                        <label for="posters_zip" class="block text-xs font-bold text-text">
                            Arsip ZIP Poster Film <span class="text-text-muted font-normal">(Opsional)</span>
                        </label>
                        <input
                            type="file"
                            id="posters_zip"
                            name="posters_zip"
                            accept=".zip"
                            class="block w-full text-xs text-text file:mr-4 file:rounded-xl file:border-0 file:bg-surface-2 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-text hover:file:bg-slate-200 dark:hover:file:bg-white/10 file:cursor-pointer border border-border rounded-xl p-2 bg-surface-2/40"
                        >
                        <p class="text-[11px] text-text-muted">
                            Arsip ZIP berisi gambar JPG, PNG, atau WebP. Gambar dipotong otomatis ke 600 × 900 px (2:3) dan dicocokkan berdasarkan nama berkas atau judul. Poster juga bisa diunggah kapan saja via halaman Poster Massal.
                        </p>
                    </div>

                    {{-- Strategi Duplikat --}}
                    <div>
                        <x-ui.select
                            id="duplicate_strategy"
                            name="duplicate_strategy"
                            label="Pilihan untuk Duplikat (Judul & Tahun Sama)"
                            :value="'skip'"
                            :options="[
                                'skip' => 'Lewati (bawaan) — jangan ubah film yang sudah ada',
                                'update' => 'Perbarui data film yang ada dengan isi berkas ini',
                            ]"
                        />
                    </div>

                    <div class="pt-2">
                        <button
                            type="submit"
                            class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl bg-amber-500 px-6 py-2.5 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition-colors w-full sm:w-auto"
                        >
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                            <span>Lanjut ke Langkah 2: Periksa Pratinjau</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panduan Kolom CSV (1 Kolom di Desktop) --}}
        <div class="space-y-6">
            <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-4">
                <h3 class="text-sm font-bold text-text">Format & Kolom CSV</h3>
                <p class="text-[11px] text-text-muted">Importer menerima nama kolom bahasa Indonesia maupun alias bahasa Inggris.</p>
                <div class="space-y-2.5 text-xs text-text-muted">
                    <div>
                        <span class="font-bold text-text">judul / title (Wajib):</span>
                        <p class="mt-0.5">Judul film, misalnya "Laskar Pelangi".</p>
                    </div>
                    <div>
                        <span class="font-bold text-text">genre (Wajib):</span>
                        <p class="mt-0.5">Genre baku (Aksi, Animasi, Drama, Fantasi, Horor, Keluarga, Komedi, Misteri, Petualangan, Romantis, Sci-Fi, Thriller). Dapat dipisah koma atau titik koma.</p>
                    </div>
                    <div>
                        <span class="font-bold text-text">durasi / duration_minutes (Wajib):</span>
                        <p class="mt-0.5">Durasi angka bulat dalam menit antara 1 sampai 400 menit.</p>
                    </div>
                    <div>
                        <span class="font-bold text-text">klasifikasi / age_rating (Wajib):</span>
                        <p class="mt-0.5">Rating usia baku: <strong>SU, 13+, atau 17+</strong>. Klasifikasi tak dikenal ditandai "Ada masalah" disertai saran koreksi. Klasifikasi <strong>"21+"</strong> ditandai variasi yang perlu dikonfirmasi admin.</p>
                    </div>
                    <div>
                        <span class="font-bold text-text">tahun / release_year (Opsional):</span>
                        <p class="mt-0.5">Tahun rilis antara 1900 s/d tahun depan. Kolom kosong diperbolehkan (null). Tahun di luar rentang ditandai "Ada masalah" (tidak diganti diam-diam).</p>
                    </div>
                    <div>
                        <span class="font-bold text-text">sinopsis / synopsis (Opsional):</span>
                        <p class="mt-0.5">Ringkasan cerita. Jika kosong, film tetap diimpor dan ditandai "Perlu dilengkapi".</p>
                    </div>
                    <div>
                        <span class="font-bold text-text">poster (Opsional):</span>
                        <p class="mt-0.5">Nama berkas poster (misal: "laskar-pelangi.jpg").</p>
                    </div>
                    <div>
                        <span class="font-bold text-text">trailer / trailer_url (Opsional):</span>
                        <p class="mt-0.5">URL cuplikan video YouTube.</p>
                    </div>
                    <div>
                        <span class="font-bold text-text">tampil / is_active (Opsional):</span>
                        <p class="mt-0.5">ya / tidak (atau 1 / 0, true / false). Bawaan: <strong>tidak</strong> (disembunyikan) agar admin sempat memeriksa sebelum tayang ke publik.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Riwayat Batch Impor & Tombol Urungkan --}}
    @if ($recentBatches->isNotEmpty())
        <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-text">Riwayat Batch Impor</h3>
                    <p class="text-xs text-text-muted mt-0.5">Anda dapat membatalkan atau mengurungkan batch impor tertentu bila terjadi kesalahan.</p>
                </div>
            </div>

            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="col-nowrap">ID Batch</th>
                            <th class="col-nowrap">Waktu</th>
                            <th class="col-nowrap">Berkas</th>
                            <th class="col-nowrap">Status</th>
                            <th class="col-nowrap">Total</th>
                            <th class="col-nowrap">Berhasil</th>
                            <th class="col-nowrap">Gagal</th>
                            <th class="col-nowrap">Pengimpor</th>
                            <th class="col-actions-sticky">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentBatches as $batch)
                            <tr>
                                <td class="col-nowrap font-mono text-xs font-bold text-text">
                                    {{ Str::limit($batch->batch_uuid, 8, '') }}
                                </td>
                                <td class="col-nowrap text-xs text-text-muted">
                                    {{ $batch->created_at->translatedFormat('d M Y, H:i') }}
                                </td>
                                <td class="col-nowrap text-xs text-text font-medium">
                                    {{ $batch->csv_filename ?: 'berkas.csv' }}
                                </td>
                                <td class="col-nowrap">
                                    @if ($batch->status === 'completed')
                                        <span class="inline-flex items-center rounded-md bg-success/15 px-2 py-0.5 text-[10px] font-bold text-success border border-success/30">Selesai</span>
                                    @elseif ($batch->status === 'processing')
                                        <span class="inline-flex items-center rounded-md bg-warning/15 px-2 py-0.5 text-[10px] font-bold text-warning border border-warning/30">Berjalan</span>
                                    @elseif ($batch->status === 'cancelled')
                                        <span class="inline-flex items-center rounded-md bg-danger/15 px-2 py-0.5 text-[10px] font-bold text-danger border border-danger/30">Dibatalkan</span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-surface-2 px-2 py-0.5 text-[10px] font-bold text-text-muted border border-border">{{ $batch->status }}</span>
                                    @endif
                                </td>
                                <td class="col-nowrap text-xs font-bold text-text">
                                    {{ $batch->total_rows }}
                                </td>
                                <td class="col-nowrap text-xs font-bold text-success">
                                    {{ $batch->successful_rows }}
                                </td>
                                <td class="col-nowrap text-xs font-bold {{ $batch->failed_rows > 0 ? 'text-danger' : 'text-text-muted' }}">
                                    {{ $batch->failed_rows }}
                                </td>
                                <td class="col-nowrap text-xs text-text-muted">
                                    {{ $batch->user?->name ?: 'Admin' }}
                                </td>
                                <td class="col-actions-sticky flex items-center gap-2">
                                    @if ($batch->status === 'processing')
                                        <a
                                            href="{{ route('admin.films.import.resume', $batch->id) }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-amber-500/40 bg-amber-500/10 px-2.5 py-1 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 transition-colors"
                                        >
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                            </svg>
                                            <span>Lanjutkan</span>
                                        </a>
                                    @endif
                                    <form
                                        action="{{ route('admin.films.import.rollback', $batch->id) }}"
                                        method="POST"
                                        data-confirm="Batalkan impor batch ini? Seluruh {{ $batch->successful_rows }} film yang diimpor pada batch ini akan dihapus dari katalog."
                                        data-confirm-title="Urungkan Batch Impor"
                                        data-confirm-action="Hapus Seluruh Film Batch Ini"
                                        data-confirm-is-danger="true"
                                        novalidate
                                    >
                                        @csrf
                                        <button
                                            type="submit"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-danger/40 px-2.5 py-1 text-xs font-bold text-danger hover:bg-danger/10 transition-colors"
                                        >
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span>Urungkan</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
