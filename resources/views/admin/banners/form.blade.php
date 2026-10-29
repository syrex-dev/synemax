@extends('layouts.admin')

@section('title', $isEdit ? 'Ubah Slide Hero' : 'Tambah Slide Hero')

@section('content')
    <div x-data="{
        targetType: '{{ old('target_type', $banner->target_type ?? 'none') }}',
        filmTargetId: '{{ old('target_id', $banner->target_id ?? '') }}',
        promoTargetId: '{{ old('target_id', $banner->target_id ?? '') }}',
        nobarTargetId: '{{ old('target_id', $banner->target_id ?? '') }}'
    }">
        <x-admin.page-header
            eyebrow="Banner Beranda"
            title="{{ $isEdit ? 'Ubah Slide Hero' : 'Tambah Slide Hero' }}"
            subtitle="Slide hero beranda berupa gambar penuh 1600 × 680 px tanpa teks overlay."
        >
            <a href="{{ route('admin.banners.index') }}" class="btn-secondary text-xs">
                ← Kembali ke Daftar
            </a>
            @if ($isEdit && $banner->resolvedUrl())
                <a href="{{ $banner->resolvedUrl() }}" target="_blank" class="btn-secondary text-xs">
                    Lihat di Situs ↗
                </a>
            @endif
        </x-admin.page-header>

        <form method="POST" action="{{ $isEdit ? route('admin.banners.update', $banner) : route('admin.banners.store') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif
            <input type="hidden" name="type" value="hero">

            <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-5">
                <h2 class="text-base font-bold text-text">Informasi Banner</h2>

                <div>
                    <label for="title" class="block text-xs font-bold uppercase tracking-wider text-text">
                        Judul Internal Slide <span class="text-danger">*</span>
                    </label>
                    <input id="title" name="title" type="text" required value="{{ old('title', $banner->title) }}" placeholder="Contoh: Promo Spesial Weekend Oktober" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                    <p class="mt-1 text-[11px] text-text-muted">Judul ini digunakan untuk pengelolaan internal admin dan teks alternatif (alt text) aksesibilitas.</p>
                </div>

                {{-- Two-stage Link Selector --}}
                <div class="rounded-xl border border-border p-4 bg-surface-2 space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-text">Tujuan Tautan Saat Slide Diklik</h3>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div @change="if ($event.target.name === 'target_type') targetType = $event.target.value">
                            <x-ui.select
                                name="target_type"
                                id="target_type"
                                label="Tahap 1: Pilih Jenis Halaman"
                                :value="old('target_type', $banner->target_type ?? 'none')"
                                :options="[
                                    'none' => 'Tanpa Tautan (Gambar Saja)',
                                    'promo' => 'Halaman Detail Promo',
                                    'film' => 'Halaman Detail Film',
                                    'nobar' => 'Halaman Detail Nobar',
                                    'custom' => 'URL Bebas (Tautan Luar / Khusus)',
                                ]"
                            />
                        </div>

                        <input type="hidden" name="target_id" :value="targetType === 'film' ? filmTargetId : (targetType === 'promo' ? promoTargetId : (targetType === 'nobar' ? nobarTargetId : ''))">

                        {{-- Tahap 2: Pilihan Data Berdasarkan Jenis --}}
                        <div x-show="targetType === 'film'" x-cloak @change="if ($event.target.name === 'target_id_film') filmTargetId = $event.target.value">
                            <x-ui.select
                                name="target_id_film"
                                id="target_id_film"
                                label="Tahap 2: Pilih Film"
                                placeholder="-- Pilih Film dari Katalog --"
                                :value="old('target_id', $banner->target_id)"
                                :options="$films->pluck('title', 'slug')->prepend('-- Pilih Film dari Katalog --', '')->toArray()"
                            />
                        </div>

                        <div x-show="targetType === 'promo'" x-cloak @change="if ($event.target.name === 'target_id_promo') promoTargetId = $event.target.value">
                            <x-ui.select
                                name="target_id_promo"
                                id="target_id_promo"
                                label="Tahap 2: Pilih Promo"
                                placeholder="-- Pilih Promo --"
                                :value="old('target_id', $banner->target_id)"
                                :options="$promos->pluck('title', 'slug')->prepend('-- Pilih Promo --', '')->toArray()"
                            />
                        </div>

                        <div x-show="targetType === 'nobar'" x-cloak @change="if ($event.target.name === 'target_id_nobar') nobarTargetId = $event.target.value">
                            <x-ui.select
                                name="target_id_nobar"
                                id="target_id_nobar"
                                label="Tahap 2: Pilih Acara Nobar"
                                placeholder="-- Pilih Acara Nobar --"
                                :value="old('target_id', $banner->target_id)"
                                :options="$nobars->pluck('judul', 'slug')->prepend('-- Pilih Acara Nobar --', '')->toArray()"
                            />
                        </div>

                        <div x-show="targetType === 'custom'" x-cloak>
                            <label for="cta_url" class="block text-xs font-semibold text-text">Tahap 2: Masukkan URL Tujuan</label>
                            <input id="cta_url" name="cta_url" type="url" placeholder="https://..." value="{{ old('cta_url', $banner->cta_url) }}" class="mt-1 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                        </div>
                    </div>
                </div>

                {{-- Image Uploads --}}
                <div class="grid gap-6 sm:grid-cols-2 pt-2 border-t border-border">
                    <x-admin.image-upload
                        name="image"
                        label="Gambar Desktop Layar Lebar (1600 × 680 px)"
                        hint="Rasio 2,35:1 (1600 × 680 px), JPG/PNG/WebP, maks 3 MB. Gambar visual murni tanpa teks bertumpuk."
                        :currentValue="$banner->image_path"
                        :recommendedWidth="1600"
                        :recommendedHeight="680"
                        :required="!$isEdit"
                        maxSizeMb="3"
                    />

                    <x-admin.image-upload
                        name="mobile_image"
                        label="Gambar Mobile Vertikal (Opsional, 1200 × 900 px)"
                        hint="Rasio 4:3 (1200 × 900 px), jika kosong akan menggunakan gambar desktop dengan object-fit cover."
                        :currentValue="$banner->mobile_image_path"
                        :recommendedWidth="1200"
                        :recommendedHeight="900"
                        maxSizeMb="3"
                    />
                </div>

                <div class="grid gap-4 sm:grid-cols-2 pt-3 border-t border-border">
                    <div>
                        <label for="sort_order" class="block text-xs font-bold uppercase tracking-wider text-text">Nomor Urutan Tampil</label>
                        <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $banner->sort_order ?? 0) }}" class="mt-1.5 min-h-[44px] w-full sm:w-48 rounded-xl border px-3 text-sm">
                    </div>
                    <div class="flex items-center pt-5">
                        <x-form.switch
                            name="is_active"
                            value="1"
                            label="Aktifkan Slide Hero"
                            :checked="old('is_active', $banner->is_active ?? true)"
                        />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.banners.index') }}" class="btn-secondary">
                    Batal
                </a>
                <button type="submit" class="inline-flex min-h-[44px] items-center rounded-xl bg-amber-500 px-6 font-bold text-slate-950 shadow-sm hover:bg-amber-400">
                    {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Slide Hero' }}
                </button>
            </div>
        </form>
    </div>
@endsection
