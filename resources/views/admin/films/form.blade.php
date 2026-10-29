@extends('layouts.admin')

@section('title', $film->exists ? 'Ubah Film' : 'Tambah Film Baru')

@section('content')
    <div>
        <x-admin.page-header
            eyebrow="Katalog"
            title="{{ $film->exists ? 'Ubah Film: ' . $film->title : 'Tambah Film Baru' }}"
            subtitle="Tambahkan film ke katalog bioskop. Poster menggunakan rasio standar 2:3 (600 × 900 px)."
        >
            <a href="{{ route('admin.films.index') }}" class="btn-secondary text-xs">
                ← Kembali ke Katalog
            </a>
            @if ($film->exists && $film->slug)
                <a href="{{ url('/film/' . $film->slug) }}" target="_blank" class="btn-secondary text-xs">
                    Lihat di Situs ↗
                </a>
            @endif
        </x-admin.page-header>

        <form method="POST" enctype="multipart/form-data" action="{{ $film->exists ? route('admin.films.update', $film) : route('admin.films.store') }}" class="mt-6 space-y-6" novalidate data-track-dirty="true">
            @csrf
            @if ($film->exists)
                @method('PUT')
            @endif

            <div class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-5">
                <h2 class="text-base font-bold text-text">Informasi Utama Film</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="title" class="block text-xs font-bold uppercase tracking-wider text-text">
                            Judul Film <span class="text-danger">*</span>
                        </label>
                        <input id="title" name="title" value="{{ old('title', $film->title) }}" required maxlength="180" placeholder="Contoh: Interstellar" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                    </div>

                    <div>
                        <label for="slug" class="block text-xs font-bold uppercase tracking-wider text-text">
                            Slug URL (Opsional)
                        </label>
                        <input id="slug" name="slug" value="{{ old('slug', $film->slug) }}" placeholder="Otomatis dibuat dari judul" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm font-mono">
                    </div>
                </div>

                <div class="pt-2 border-t border-border">
                    <label class="block text-xs font-bold uppercase tracking-wider text-text">
                        Genre Film <span class="text-danger">*</span> (Pilih satu atau lebih)
                    </label>
                    <div class="mt-2.5 flex flex-wrap gap-2">
                        @php($currentGenres = old('genres', $film->genresList()))
                        @foreach (\App\Models\Film::CANONICAL_GENRES as $canonGenre)
                            <label class="choice-chip text-xs">
                                <input
                                    type="checkbox"
                                    name="genres[]"
                                    value="{{ $canonGenre }}"
                                    class="rounded text-amber-500 focus:ring-amber-500 size-4"
                                    @checked(in_array($canonGenre, (array) $currentGenres, true))
                                >
                                <span class="font-bold">{{ $canonGenre }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <label for="custom_genre" class="text-xs text-text-muted shrink-0">Genre tambahan (opsional):</label>
                        <input
                            id="custom_genre"
                            name="custom_genre"
                            value="{{ old('custom_genre') }}"
                            placeholder="Pisahkan dengan koma jika lebih dari satu"
                            class="min-h-[38px] w-full max-w-sm rounded-xl border px-3 text-xs"
                        >
                    </div>
                    @error('genre')
                        <p class="mt-1 text-xs text-danger font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2 pt-2 border-t border-border">
                    <div>
                        <label for="release_year" class="block text-xs font-bold uppercase tracking-wider text-text">
                            Tahun Rilis
                        </label>
                        <input id="release_year" name="release_year" type="number" min="1900" max="2100" value="{{ old('release_year', $film->release_year ?: ($film->release_date ? $film->release_date->format('Y') : date('Y'))) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                    </div>

                    <div>
                        <label for="duration_minutes" class="block text-xs font-bold uppercase tracking-wider text-text">
                            Durasi (Menit) <span class="text-danger">*</span>
                        </label>
                        <input id="duration_minutes" name="duration_minutes" type="number" min="1" max="600" value="{{ old('duration_minutes', $film->duration_minutes ?? 110) }}" required class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 pt-2 border-t border-border">
                    <div>
                        <x-ui.select
                            name="age_rating"
                            id="age_rating"
                            label="Klasifikasi Usia"
                            :required="true"
                            :value="old('age_rating', $film->age_rating ?? 'SU')"
                            :options="[
                                'SU' => 'SU · Semua Umur',
                                '13+' => '13+ · Remaja',
                                '17+' => '17+ · Dewasa',
                            ]"
                        />
                    </div>

                    <div>
                        <label for="trailer_url" class="block text-xs font-bold uppercase tracking-wider text-text">
                            Tautan Trailer YouTube (Opsional)
                        </label>
                        <input id="trailer_url" name="trailer_url" type="url" placeholder="https://www.youtube.com/watch?v=..." value="{{ old('trailer_url', $film->trailer_url) }}" class="mt-1.5 min-h-[44px] w-full rounded-xl border px-3 text-sm">
                    </div>
                </div>

                <div class="pt-2 border-t border-border">
                    <label for="synopsis" class="block text-xs font-bold uppercase tracking-wider text-text">
                        Sinopsis Lengkap Film <span class="text-danger">*</span>
                    </label>
                    <textarea id="synopsis" name="synopsis" rows="4" required maxlength="3000" placeholder="Tuliskan jalan cerita film untuk menarik minat penonton..." class="mt-1.5 w-full rounded-xl border p-3 text-sm">{{ old('synopsis', $film->synopsis) }}</textarea>
                </div>

                {{-- Poster Upload --}}
                <div class="pt-2 border-t border-border">
                    <x-admin.image-upload
                        name="poster"
                        label="Poster Film Resmi (600 × 900 px)"
                        hint="Rasio portrait 2:3 (600 × 900 px), JPG/PNG/WebP, maks 4 MB."
                        :currentValue="$film->poster_path"
                        :recommendedWidth="600"
                        :recommendedHeight="900"
                        maxSizeMb="4"
                    />
                </div>

                <div class="pt-2 border-t border-border">
                    <label for="license_notes" class="block text-xs font-bold uppercase tracking-wider text-text">
                        Catatan Lisensi / Internal (Hanya Admin)
                    </label>
                    <textarea id="license_notes" name="license_notes" rows="2" maxlength="3000" placeholder="Catatan internal pengelola..." class="mt-1.5 w-full rounded-xl border p-3 text-sm">{{ old('license_notes', $film->license_notes) }}</textarea>
                </div>

                <div class="pt-3 border-t border-border flex items-center justify-between">
                    <div>
                        <p class="text-sm font-bold text-text">Status Katalog Publik</p>
                        <p class="text-xs text-text-muted">Jika dinonaktifkan, film tidak tampil di pencarian publik dan sesi mendatang ditutup.</p>
                    </div>
                    <input type="hidden" name="is_active" value="0">
                    <x-form.switch
                        name="is_active"
                        value="1"
                        :checked="old('is_active', $film->is_active ?? true)"
                    />
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.films.index') }}" class="btn-secondary">
                    Batal
                </a>
                <button type="submit" class="inline-flex min-h-[44px] items-center rounded-xl bg-amber-500 px-6 font-bold text-slate-950 shadow-sm hover:bg-amber-400">
                    {{ $film->exists ? 'Simpan Perubahan' : 'Tambah Film ke Katalog' }}
                </button>
            </div>
        </form>
    </div>
@endsection
