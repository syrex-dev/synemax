@extends('layouts.admin')

@section('title', $isEdit ? 'Ubah Promo' : 'Tambah Promo Baru')

@section('content')
    <div>
        <x-admin.page-header
            eyebrow="Penawaran Khusus"
            title="{{ $isEdit ? 'Ubah Promo: ' . $promo->title : 'Tambah Promo Baru' }}"
            subtitle="Promo dikelola lewat upload banner gambar 1200 × 600 px. Setiap promo memiliki halaman detail sendiri."
        >
            <a href="{{ route('admin.promos.index') }}" class="btn-secondary text-xs">
                ← Kembali ke Daftar
            </a>
            @if ($isEdit && $promo->slug)
                <a href="{{ url('/promo/' . $promo->slug) }}" target="_blank" class="btn-secondary text-xs">
                    Lihat di Situs ↗
                </a>
            @endif
        </x-admin.page-header>

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
                        <input id="title" name="title" type="text" required value="{{ old('title', $promo->title) }}" placeholder="Contoh: Paket Nonton Berdua Hemat 20%" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                    </div>

                    <div>
                        <label for="slug" class="block text-xs font-bold uppercase tracking-wider text-text">
                            Slug URL (Opsional)
                        </label>
                        <input id="slug" name="slug" type="text" value="{{ old('slug', $promo->slug) }}" placeholder="Otomatis dari judul jika dikosongkan" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm font-mono">
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
                </div>

                {{-- Periode Berlaku --}}
                <div class="grid gap-4 sm:grid-cols-2 pt-3 border-t border-border">
                    <div>
                        <label for="start_date" class="block text-xs font-bold uppercase tracking-wider text-text">Tanggal Mulai Berlaku</label>
                        <input id="start_date" name="start_date" type="date" value="{{ old('start_date', $promo->start_date?->toDateString()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                    </div>
                    <div>
                        <label for="end_date" class="block text-xs font-bold uppercase tracking-wider text-text">Tanggal Selesai (Kadaluarsa)</label>
                        <input id="end_date" name="end_date" type="date" value="{{ old('end_date', $promo->end_date?->toDateString()) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                    </div>
                </div>

                {{-- Deskripsi Promo --}}
                <div class="pt-3 border-t border-border">
                    <label for="description" class="block text-xs font-bold uppercase tracking-wider text-text">Deskripsi Lengkap Promo</label>
                    <textarea id="description" name="description" rows="4" placeholder="Jelaskan penawaran menarik promo ini kepada penonton..." class="mt-1.5 w-full rounded-xl border p-3 text-sm">{{ old('description', $promo->description) }}</textarea>
                </div>

                {{-- Syarat & Ketentuan --}}
                <div class="pt-3 border-t border-border">
                    <label for="terms_conditions" class="block text-xs font-bold uppercase tracking-wider text-text">Syarat & Ketentuan (S&K)</label>
                    <textarea id="terms_conditions" name="terms_conditions" rows="4" placeholder="Tuliskan syarat dan ketentuan bernomor, contoh:&#10;1. Berlaku untuk pemesanan hari Senin-Kamis.&#10;2. Tidak dapat digabungkan dengan promo lain.&#10;3. Kuota terbatas 10 pemesan per hari." class="mt-1.5 w-full rounded-xl border p-3 text-sm">{{ old('terms_conditions', $promo->terms_conditions) }}</textarea>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 pt-3 border-t border-border">
                    <div>
                        <label for="sort_order" class="block text-xs font-bold uppercase tracking-wider text-text">Nomor Urutan Tampil</label>
                        <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $promo->sort_order ?? 0) }}" class="mt-1.5 min-h-[44px] w-full sm:w-48 rounded-xl border px-3 text-sm">
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
@endsection
