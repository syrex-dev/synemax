@extends('layouts.admin')

@section('title', ($isEdit ? 'Ubah Acara Nobar' : 'Tambah Acara Nobar'))

@section('content')
    <div x-data="{
        previewModal: false,
        isFree: @js((bool) old('is_free', $event->is_free)),
        statusAcara: @js(old('status_acara', $event->status_acara)),
        catatanMarkdown: @js(old('catatan', $event->catatan ?? '')),
        catatanTab: 'write',
        posterPreview: null,
        handlePosterChange(event) {
            const file = event.target.files && event.target.files[0];
            if (file) {
                this.posterPreview = URL.createObjectURL(file);
            }
        }
    }">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('admin.nobar.index') }}" class="text-xs font-semibold text-text-muted hover:text-text">← Kembali ke Daftar Nobar</a>
                <h1 class="mt-1 text-3xl font-black text-text">{{ $isEdit ? 'Ubah Acara Nobar' : 'Tambah Acara Nobar' }}</h1>
            </div>
            @if ($isEdit)
                <a href="{{ route('nobar.show', ['slug' => $event->slug]) }}" target="_blank" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-accent bg-accent/10 px-4 text-xs font-bold text-accent hover:bg-accent/20">
                    <span>👁 Pratinjau Tampilan Publik</span>
                </a>
            @endif
        </div>

        @if ($errors->any())
            <div role="alert" class="mt-6 rounded-2xl border border-danger/40 bg-danger/10 p-4 text-sm text-danger">
                <p class="font-bold">Mohon perbaiki isian form berikut:</p>
                <ul class="mt-1 list-disc pl-5 text-xs space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $isEdit ? route('admin.nobar.update', ['nobar' => $event->id]) : route('admin.nobar.store') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            {{-- 1. Informasi Pertandingan & Acara --}}
            <details open class="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-token">
                <summary class="cursor-pointer text-lg font-bold text-text">1. Informasi Pertandingan</summary>
                <p class="mt-1 text-xs text-text-muted">Tentukan judul acara, kompetisi, tim yang bertanding, dan jam mulai siaran.</p>

                <div class="mt-5 space-y-4">
                    <div>
                        <label for="judul" class="text-sm font-semibold">Judul Acara Nobar <span class="text-danger">*</span></label>
                        <input id="judul" name="judul" value="{{ old('judul', $event->judul) }}" required placeholder="Contoh: Nobar Final Perebutan Juara: Tim Biru vs Tim Merah" class="mt-1.5 min-h-12 w-full rounded-xl border border-border bg-bg px-3.5 text-sm text-text">
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="kompetisi" class="text-sm font-semibold">Kompetisi / Liga (opsional)</label>
                            <input id="kompetisi" name="kompetisi" value="{{ old('kompetisi', $event->kompetisi) }}" placeholder="Contoh: Liga Akhir Pekan, Turnamen Komunitas" class="mt-1.5 min-h-12 w-full rounded-xl border border-border bg-bg px-3.5 text-sm text-text">
                        </div>
                        <div>
                            <label for="slug" class="text-sm font-semibold">Slug URL (opsional, otomatis dibuat)</label>
                            <input id="slug" name="slug" value="{{ old('slug', $event->slug) }}" placeholder="nobar-tim-biru-vs-merah" class="mt-1.5 min-h-12 w-full rounded-xl border border-border bg-bg px-3.5 font-mono text-sm text-text">
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="tim_a" class="text-sm font-semibold">Nama Tim A / Tuan Rumah <span class="text-danger">*</span></label>
                            <input id="tim_a" name="tim_a" value="{{ old('tim_a', $event->tim_a) }}" required placeholder="Contoh: Tim Biru" class="mt-1.5 min-h-12 w-full rounded-xl border border-border bg-bg px-3.5 text-sm text-text">
                        </div>
                        <div>
                            <label for="tim_b" class="text-sm font-semibold">Nama Tim B / Penantang (opsional)</label>
                            <input id="tim_b" name="tim_b" value="{{ old('tim_b', $event->tim_b) }}" placeholder="Contoh: Tim Merah" class="mt-1.5 min-h-12 w-full rounded-xl border border-border bg-bg px-3.5 text-sm text-text">
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="kickoff_at" class="text-sm font-semibold">Waktu Kickoff (WIB) <span class="text-danger">*</span></label>
                            <input id="kickoff_at" name="kickoff_at" type="datetime-local" value="{{ old('kickoff_at', $event->kickoff_at ? $event->kickoff_at->format('Y-m-d\TH:i') : '') }}" required class="mt-1.5 min-h-12 w-full rounded-xl border border-border bg-bg px-3.5 text-sm text-text">
                            <p class="mt-1 text-[11px] text-text-muted">Tanggal dan jam mulai pertandingan.</p>
                        </div>
                        <div>
                            <label for="durasi_menit" class="text-sm font-semibold">Estimasi Durasi (menit) <span class="text-danger">*</span></label>
                            <input id="durasi_menit" name="durasi_menit" type="number" min="30" max="360" value="{{ old('durasi_menit', $event->durasi_menit) }}" required class="mt-1.5 min-h-12 w-full rounded-xl border border-border bg-bg px-3.5 text-sm text-text">
                            <p class="mt-1 text-[11px] text-text-muted">Default 150 menit (2x45 menit + jeda & tambahan waktu). Menentukan status 'Berlangsung' dan 'Selesai'.</p>
                        </div>
                    </div>
                </div>
            </details>

            {{-- 2. Lokasi & Akses --}}
            <details open class="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-token">
                <summary class="cursor-pointer text-lg font-bold text-text">2. Lokasi & Google Maps</summary>
                <p class="mt-1 text-xs text-text-muted">Alamat lokasi nobar dan tautan Google Maps untuk navigasi penonton.</p>

                <div class="mt-5 space-y-4">
                    <div>
                        <label for="lokasi_nama" class="text-sm font-semibold">Nama Tempat / Ruangan <span class="text-danger">*</span></label>
                        <input id="lokasi_nama" name="lokasi_nama" value="{{ old('lokasi_nama', $event->lokasi_nama) }}" required placeholder="Contoh: SynemaX Home Cinema Lounge" class="mt-1.5 min-h-12 w-full rounded-xl border border-border bg-bg px-3.5 text-sm text-text">
                    </div>

                    <div>
                        <label for="alamat_patokan" class="text-sm font-semibold">Alamat Lengkap & Patokan <span class="text-danger">*</span></label>
                        <textarea id="alamat_patokan" name="alamat_patokan" rows="2" required placeholder="Contoh: Jl. Sinema No. 12, Lantai 2 (Depan Taman Kota, samping mini market)" class="mt-1.5 w-full rounded-xl border border-border bg-bg p-3.5 text-sm text-text">{{ old('alamat_patokan', $event->alamat_patokan) }}</textarea>
                    </div>

                    <div>
                        <label for="maps_url" class="text-sm font-semibold">Tautan Google Maps (wajib HTTPS) <span class="text-danger">*</span></label>
                        <input id="maps_url" name="maps_url" type="url" value="{{ old('maps_url', $event->maps_url) }}" required placeholder="https://maps.google.com/?q=SynemaX" class="mt-1.5 min-h-12 w-full rounded-xl border border-border bg-bg px-3.5 text-sm text-text">
                    </div>
                </div>
            </details>

            {{-- 3. Tiket & Biaya --}}
            <details open class="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-token">
                <summary class="cursor-pointer text-lg font-bold text-text">3. Tiket & Biaya Masuk</summary>
                <div class="mt-5 space-y-4">
                    <div>
                        <label class="inline-flex items-center gap-3 rounded-xl border border-border bg-surface-2 p-3.5 text-sm font-semibold cursor-pointer">
                            <input type="hidden" name="is_free" value="0">
                            <input type="checkbox" name="is_free" value="1" x-model="isFree" class="size-5 rounded border-border bg-bg text-accent">
                            <span>Acara ini Gratis (Tanpa Tiket Masuk)</span>
                        </label>
                    </div>

                    <div x-show="!isFree" x-cloak>
                        <label for="harga_info" class="text-sm font-semibold">Informasi Biaya / HTM</label>
                        <input id="harga_info" name="harga_info" value="{{ old('harga_info', $event->harga_info) }}" placeholder="Contoh: Beli jajanan min. Rp 15.000 / HTM Rp 20.000 (termasuk minum)" class="mt-1.5 min-h-12 w-full rounded-xl border border-border bg-bg px-3.5 text-sm text-text">
                    </div>
                </div>
            </details>

            {{-- 4. Poster Acara --}}
            <details open class="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-token">
                <summary class="cursor-pointer text-lg font-bold text-text">4. Poster Acara</summary>
                <p class="mt-1 text-xs text-text-muted">Format PNG, JPG, atau WEBP maks. 3 MB. Disimpan aman dan dioptimalkan secara otomatis.</p>

                <div class="mt-5 space-y-4">
                    @if ($isEdit && $event->poster_path)
                        <div class="flex flex-wrap items-center gap-4 rounded-xl border border-border bg-surface-2 p-4">
                            <img src="{{ $event->posterUrl() }}" alt="Poster" class="size-24 rounded-lg object-cover">
                            <div class="flex-1">
                                <p class="text-xs font-bold text-success">Poster aktif tersimpan</p>
                                <label class="mt-2 inline-flex items-center gap-2 text-xs text-danger cursor-pointer">
                                    <input type="checkbox" name="delete_poster" value="1" class="rounded border-border text-danger">
                                    <span>Hapus poster saat ini</span>
                                </label>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label for="poster" class="block text-sm font-semibold">{{ $isEdit && $event->poster_path ? 'Ganti Poster Acara' : 'Unggah Poster Acara' }}</label>
                        <input id="poster" name="poster" type="file" accept="image/png,image/jpeg,image/webp" @change="handlePosterChange($event)" class="mt-2 block w-full text-sm text-text file:mr-3 file:min-h-11 file:rounded-lg file:border-0 file:bg-surface-2 file:px-4 file:font-semibold file:text-text">
                    </div>

                    <div x-show="posterPreview" x-cloak class="mt-3">
                        <p class="text-xs font-semibold text-text-muted">Pratinjau poster baru:</p>
                        <img :src="posterPreview" alt="Pratinjau" class="mt-2 max-h-48 rounded-xl object-contain border border-border">
                    </div>
                </div>
            </details>

            {{-- 5. Catatan / Fasilitas Acara (Markdown) --}}
            <details open class="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-token">
                <summary class="cursor-pointer text-lg font-bold text-text">5. Catatan & Fasilitas (Markdown)</summary>
                <p class="mt-1 text-xs text-text-muted">Jelaskan fasilitas yang disediakan, ketentuan nobar, proyektor, makanan, dll. Mendukung teks tebal, daftar poin, dan tautan aman.</p>

                <div class="mt-5">
                    <textarea id="catatan" name="catatan" x-model="catatanMarkdown" rows="6" placeholder="Tuliskan catatan acara di sini... Contoh:&#10;- Layar proyektor 120 inci&#10;- Disediakan kopi hangat gratis&#10;- Kapasitas ruang 15 orang santai" class="w-full rounded-xl border border-border bg-bg p-3.5 font-mono text-sm text-text"></textarea>
                </div>
            </details>

            {{-- 6. Status & Publikasi --}}
            <details open class="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-token">
                <summary class="cursor-pointer text-lg font-bold text-text">6. Status & Publikasi</summary>

                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="status_publikasi" class="text-sm font-semibold">Status Publikasi</label>
                        <select id="status_publikasi" name="status_publikasi" class="mt-1.5 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-sm text-text">
                            <option value="terbit" @selected(old('status_publikasi', $event->status_publikasi) === 'terbit')>Terbit (Tampil ke Publik)</option>
                            <option value="draft" @selected(old('status_publikasi', $event->status_publikasi) === 'draft')>Draft (Disimpan Privat)</option>
                        </select>
                    </div>

                    <div>
                        <label for="status_acara" class="text-sm font-semibold">Status Acara</label>
                        <select id="status_acara" name="status_acara" x-model="statusAcara" class="mt-1.5 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-sm text-text">
                            <option value="aktif">Aktif (Sesuai Jadwal)</option>
                            <option value="ditunda">Ditunda</option>
                            <option value="dibatalkan">Dibatalkan</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold">Sematkan di Atas</label>
                        <div class="mt-3">
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="hidden" name="is_pinned" value="0">
                                <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned', $event->is_pinned)) class="size-5 rounded border-border bg-bg text-accent">
                                <span>Pin Acara Pilihan</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div x-show="statusAcara !== 'aktif'" x-cloak class="mt-4">
                    <label for="alasan_status" class="text-sm font-semibold">Alasan Status Acara (tampil ke publik)</label>
                    <input id="alasan_status" name="alasan_status" value="{{ old('alasan_status', $event->alasan_status) }}" placeholder="Contoh: Jadwal ditunda oleh pihak liga karena cuaca." class="mt-1.5 min-h-12 w-full rounded-xl border border-border bg-bg px-3.5 text-sm text-text">
                </div>
            </details>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                <a href="{{ route('admin.nobar.index') }}" class="inline-flex min-h-12 items-center rounded-xl border border-border px-5 text-sm font-bold text-text hover:bg-surface-2">
                    Batal
                </a>
                <button type="submit" class="inline-flex min-h-12 items-center rounded-xl bg-accent px-6 text-sm font-bold text-accent-contrast shadow-sm hover:opacity-90">
                    {{ $isEdit ? 'Simpan Perubahan' : 'Tambahkan Acara Nobar' }}
                </button>
            </div>
        </form>
    </div>
@endsection
