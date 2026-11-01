@extends('layouts.admin')

@section('title', $isEdit ? 'Ubah Promo' : 'Tambah Promo Baru')

@section('content')
    <div>
        <x-admin.page-header
            eyebrow="Penawaran Khusus"
            title="{{ $isEdit ? 'Ubah Promo: ' . $promo->title : 'Tambah Promo Baru' }}"
            subtitle="Promo dikelola lewat upload banner gambar 1200 × 600 px. Setiap promo memiliki halaman detail sendiri."
        >
            <a href="{{ route('admin.promos.index') }}" class="btn-secondary text-xs inline-flex items-center gap-1.5">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                <span>Kembali ke Daftar</span>
            </a>
            @if ($isEdit && $promo->slug)
                <a href="{{ url('/promo/' . $promo->slug) }}" target="_blank" class="btn-secondary text-xs inline-flex items-center gap-1.5">
                    <span>Lihat di Situs</span>
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                </a>
            @endif
        </x-admin.page-header>

        {{-- Alert Error Ringkasan Utama (Inline di atas form) --}}
        @if ($errors->any())
            <div id="form-error-summary" class="mt-6 rounded-2xl border border-red-500/30 bg-red-500/10 p-5 text-xs text-red-600 dark:text-red-400 space-y-2" role="alert">
                <div class="flex items-center gap-2 font-bold text-sm">
                    <svg class="size-4 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>Promo belum tersimpan: periksa {{ $errors->count() }} kolom berikut</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-text pl-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('error'))
            <div class="mt-6 rounded-2xl border border-red-500/30 bg-red-500/10 p-5 text-xs text-red-600 dark:text-red-400 font-bold flex items-center gap-2" role="alert">
                <svg class="size-4 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ $isEdit ? route('admin.promos.update', $promo) : route('admin.promos.store') }}" enctype="multipart/form-data" class="mt-6 space-y-6" novalidate data-track-dirty="true">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-5">
                <h2 class="text-base font-bold text-text">Informasi Promo</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="title" class="block text-xs font-bold uppercase tracking-wider text-text">
                            Judul Promo <span class="text-danger">*</span>
                        </label>
                        <input id="title" name="title" type="text" required value="{{ old('title', $promo->title) }}" placeholder="Contoh: Paket Nonton Berdua Hemat 20%" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm @error('title') border-red-500 ring-1 ring-red-500 @enderror" @error('title') aria-invalid="true" @enderror>
                        @error('title')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="slug" class="block text-xs font-bold uppercase tracking-wider text-text">
                            Alamat Halaman (URL, Opsional)
                        </label>
                        <input id="slug" name="slug" type="text" value="{{ old('slug', $promo->slug) }}" placeholder="Otomatis dari judul jika dikosongkan" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm font-mono @error('slug') border-red-500 ring-1 ring-red-500 @enderror" @error('slug') aria-invalid="true" @enderror>
                        @error('slug')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Banner Upload --}}
                <div class="pt-2 border-t border-border">
                    <x-admin.image-upload
                        name="image"
                        label="Gambar Banner Promo (1200 × 600 px)"
                        hint="Wajib rasio 2:1 (1200 × 600 px), JPG/PNG/WebP, maks 2 MB. Gambar murni tanpa teks bertumpuk di beranda."
                        :currentValue="$promo->image_path"
                        :recommendedWidth="1200"
                        :recommendedHeight="600"
                        :required="!$isEdit"
                        maxSizeMb="2"
                    />
                    @error('image')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Periode Berlaku --}}
                <div class="grid gap-4 sm:grid-cols-2 pt-3 border-t border-border">
                    <div>
                        <label for="start_date" class="block text-xs font-bold uppercase tracking-wider text-text">Tanggal Mulai Berlaku</label>
                        <input id="start_date" name="start_date" type="date" value="{{ old('start_date', $promo->start_date?->toDateString()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm @error('start_date') border-red-500 ring-1 ring-red-500 @enderror" @error('start_date') aria-invalid="true" @enderror>
                        @error('start_date')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="end_date" class="block text-xs font-bold uppercase tracking-wider text-text">Tanggal Selesai (Kadaluarsa)</label>
                        <input id="end_date" name="end_date" type="date" value="{{ old('end_date', $promo->end_date?->toDateString()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm @error('end_date') border-red-500 ring-1 ring-red-500 @enderror" @error('end_date') aria-invalid="true" @enderror>
                        @error('end_date')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Deskripsi Promo --}}
                <div class="pt-3 border-t border-border">
                    <label for="description" class="block text-xs font-bold uppercase tracking-wider text-text">Deskripsi Lengkap Promo</label>
                    <textarea id="description" name="description" rows="4" placeholder="Jelaskan penawaran menarik promo ini kepada penonton..." class="mt-1.5 w-full rounded-xl border p-3 text-sm @error('description') border-red-500 ring-1 ring-red-500 @enderror" @error('description') aria-invalid="true" @enderror>{{ old('description', $promo->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Syarat & Ketentuan --}}
                <div class="pt-3 border-t border-border">
                    <label for="terms_conditions" class="block text-xs font-bold uppercase tracking-wider text-text">Syarat & Ketentuan (S&K)</label>
                    <textarea id="terms_conditions" name="terms_conditions" rows="4" placeholder="Tuliskan syarat dan ketentuan bernomor, contoh:&#10;1. Berlaku untuk pemesanan hari Senin-Kamis.&#10;2. Tidak dapat digabungkan dengan promo lain.&#10;3. Kuota terbatas 10 pemesan per hari." class="mt-1.5 w-full rounded-xl border p-3 text-sm @error('terms_conditions') border-red-500 ring-1 ring-red-500 @enderror" @error('terms_conditions') aria-invalid="true" @enderror>{{ old('terms_conditions', $promo->terms_conditions) }}</textarea>
                    @error('terms_conditions')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2 pt-3 border-t border-border">
                    <div>
                        <label for="sort_order" class="block text-xs font-bold uppercase tracking-wider text-text">Nomor Urutan Tampil</label>
                        <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $promo->sort_order ?? 0) }}" class="mt-1.5 min-h-[44px] w-full sm:w-48 rounded-xl border px-3 text-sm @error('sort_order') border-red-500 ring-1 ring-red-500 @enderror" @error('sort_order') aria-invalid="true" @enderror>
                        @error('sort_order')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-center pt-5">
                        <x-form.switch
                            name="is_active"
                            value="1"
                            label="Aktifkan Promo"
                            :checked="old('is_active', $promo->is_active ?? true)"
                        />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.promos.index') }}" class="btn-secondary">
                    Batal
                </a>
                <button type="submit" class="inline-flex min-h-[44px] items-center rounded-xl bg-amber-500 px-6 font-bold text-slate-950 shadow-sm hover:bg-amber-400">
                    {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Promo Baru' }}
                </button>
            </div>
        </form>
    </div>

    {{-- Script Autofokus ke Kolom Salah Pertama Saat Gagal Validasi --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const firstInvalidInput = document.querySelector('form input[aria-invalid="true"], form textarea[aria-invalid="true"], form select[aria-invalid="true"], form .border-red-500');
            if (firstInvalidInput) {
                firstInvalidInput.focus();
            } else {
                const summary = document.getElementById('form-error-summary');
                if (summary) summary.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    </script>
@endsection
