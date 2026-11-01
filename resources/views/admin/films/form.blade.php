@extends('layouts.admin')

@section('title', $film->exists ? 'Ubah Film' : 'Tambah Film Baru')

@section('content')
    <script>
        function filmFormHandler() {
            return {
                trailerUrl: @js(old('trailer_url', $film->trailer_url ?? '')),
                filmTitle: @js(old('title', $film->title ?? '')),
                trailerError: '',
                get youtubeId() {
                    if (!this.trailerUrl) return null;
                    const trimmed = this.trailerUrl.trim();
                    const match = trimmed.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts|live)\/|.*[?&]v=)|youtu\.be\/)([^"&?/\s]{11})/i);
                    return match ? match[1] : null;
                },
                previewTrailer(event) {
                    const id = this.youtubeId;
                    if (!id) {
                        this.trailerError = 'Tautan YouTube tidak dikenali. Masukkan tautan youtube.com atau youtu.be yang valid.';
                        return;
                    }
                    this.trailerError = '';
                    window.dispatchEvent(new CustomEvent('open-trailer', {
                        detail: {
                            id: id,
                            title: this.filmTitle || 'Pratinjau Trailer',
                            trigger: event ? event.currentTarget : null
                        }
                    }));
                }
            };
        }
    </script>

    <div x-data="filmFormHandler()">
        <x-public.trailer-modal />

        <x-admin.page-header
            eyebrow="Katalog"
            title="{{ $film->exists ? 'Ubah Film: ' . $film->title : 'Tambah Film Baru' }}"
            subtitle="Tambahkan film ke katalog bioskop. Poster menggunakan rasio standar 2:3 (600 × 900 px, maks 5 MB)."
        >
            <a href="{{ route('admin.films.index') }}" class="btn-secondary text-xs inline-flex items-center gap-1.5">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                <span>Kembali ke Katalog</span>
            </a>
            @if ($film->exists && $film->slug)
                <a href="{{ url('/film/' . $film->slug) }}" target="_blank" class="btn-secondary text-xs inline-flex items-center gap-1.5">
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
                    <span>Film belum tersimpan: periksa {{ $errors->count() }} kolom berikut</span>
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
                        <input
                            id="title"
                            name="title"
                            x-model="filmTitle"
                            value="{{ old('title', $film->title) }}"
                            required
                            maxlength="180"
                            placeholder="Contoh: Interstellar"
                            class="mt-1.5 min-h-[44px] w-full rounded-xl border {{ $errors->has('title') ? 'border-red-500 focus:ring-red-500 ring-1 ring-red-500' : 'border-border' }} px-3 text-sm"
                            @if ($errors->has('title')) aria-invalid="true" @endif
                        >
                        @error('title')
                            <p class="mt-1 text-xs text-red-500 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="slug" class="block text-xs font-bold uppercase tracking-wider text-text">
                            Alamat Halaman (URL, Opsional)
                        </label>
                        <input
                            id="slug"
                            name="slug"
                            value="{{ old('slug', $film->slug) }}"
                            placeholder="Otomatis dibuat dari judul jika kosong"
                            class="mt-1.5 min-h-[44px] w-full rounded-xl border {{ $errors->has('slug') ? 'border-red-500 focus:ring-red-500' : 'border-border' }} px-3 text-sm font-mono"
                        >
                        @error('slug')
                            <p class="mt-1 text-xs text-red-500 font-semibold">{{ $message }}</p>
                        @enderror
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
                            class="min-h-[38px] w-full max-w-sm rounded-xl border border-border px-3 text-xs"
                        >
                    </div>
                    @error('genre')
                        <p class="mt-1.5 text-xs text-red-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2 pt-2 border-t border-border">
                    <div>
                        <label for="release_year" class="block text-xs font-bold uppercase tracking-wider text-text">
                            Tahun Rilis
                        </label>
                        <input
                            id="release_year"
                            name="release_year"
                            type="number"
                            min="1900"
                            max="2100"
                            value="{{ old('release_year', $film->release_year ?: ($film->release_date ? $film->release_date->format('Y') : date('Y'))) }}"
                            class="mt-1.5 min-h-[44px] w-full rounded-xl border {{ $errors->has('release_year') ? 'border-red-500' : 'border-border' }} px-3 text-sm"
                        >
                        @error('release_year')
                            <p class="mt-1 text-xs text-red-500 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="duration_minutes" class="block text-xs font-bold uppercase tracking-wider text-text">
                            Durasi (Menit) <span class="text-danger">*</span>
                        </label>
                        <input
                            id="duration_minutes"
                            name="duration_minutes"
                            type="number"
                            min="1"
                            max="400"
                            value="{{ old('duration_minutes', $film->duration_minutes ?? 110) }}"
                            required
                            class="mt-1.5 min-h-[44px] w-full rounded-xl border {{ $errors->has('duration_minutes') ? 'border-red-500 focus:ring-red-500 ring-1 ring-red-500' : 'border-border' }} px-3 text-sm"
                            @if ($errors->has('duration_minutes')) aria-invalid="true" @endif
                        >
                        @error('duration_minutes')
                            <p class="mt-1 text-xs text-red-500 font-semibold">{{ $message }}</p>
                        @enderror
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
                        <div class="flex items-center justify-between">
                            <label for="trailer_url" class="block text-xs font-bold uppercase tracking-wider text-text">
                                Tautan Trailer YouTube (Opsional)
                            </label>
                            <button
                                type="button"
                                @click="previewTrailer($event)"
                                class="text-xs font-bold text-amber-500 hover:text-amber-400 hover:underline inline-flex items-center gap-1"
                            >
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Pratinjau trailer</span>
                            </button>
                        </div>
                        <input
                            id="trailer_url"
                            name="trailer_url"
                            type="url"
                            x-model="trailerUrl"
                            placeholder="https://www.youtube.com/watch?v=..."
                            value="{{ old('trailer_url', $film->trailer_url) }}"
                            class="mt-1.5 min-h-[44px] w-full rounded-xl border {{ $errors->has('trailer_url') ? 'border-red-500' : 'border-border' }} px-3 text-sm"
                        >
                        <p x-show="trailerError" x-text="trailerError" class="mt-1 text-xs text-red-500 font-semibold" style="display: none;"></p>
                        @error('trailer_url')
                            <p class="mt-1 text-xs text-red-500 font-semibold">{{ $message }}</p>
                        @enderror

                        {{-- In-Form Live Trailer Player & Placeholder --}}
                        <div class="mt-3">
                            {{-- Live Video Player saat ID YouTube terdeteksi --}}
                            <div x-show="youtubeId" class="overflow-hidden rounded-xl border border-border bg-black/40">
                                <div class="aspect-video w-full">
                                    <iframe
                                        x-bind:src="youtubeId ? ('https://www.youtube-nocookie.com/embed/' + youtubeId) : ''"
                                        class="size-full rounded-xl"
                                        frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen
                                    ></iframe>
                                </div>
                                <div class="flex items-center justify-between p-2.5 bg-surface text-xs border-t border-border">
                                    <span class="font-semibold text-text truncate">Pratinjau Video YouTube</span>
                                    <span class="text-[11px] text-text-muted font-mono" x-text="'ID: ' + youtubeId"></span>
                                </div>
                            </div>

                            {{-- Feedback saat format URL belum valid --}}
                            <div x-show="trailerUrl && trailerUrl.trim().length > 0 && !youtubeId" class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-700 dark:text-amber-300 flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>Format tautan YouTube belum dikenali. Contoh: <code>https://www.youtube.com/watch?v=...</code></span>
                            </div>

                            {{-- Placeholder ramah saat kosong --}}
                            <div x-show="!trailerUrl || trailerUrl.trim().length === 0" class="rounded-xl border border-dashed border-border p-3.5 text-center text-xs text-text-muted bg-surface-2/40">
                                <p class="font-medium text-text">Pratinjau Trailer</p>
                                <p class="text-[11px] mt-0.5 text-text-muted">Masukkan tautan YouTube di atas untuk melihat pratinjau video secara langsung di sini.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-2 border-t border-border">
                    <label for="synopsis" class="block text-xs font-bold uppercase tracking-wider text-text">
                        Sinopsis Lengkap Film <span class="text-danger">*</span>
                    </label>
                    <textarea
                        id="synopsis"
                        name="synopsis"
                        rows="4"
                        required
                        maxlength="3000"
                        placeholder="Tuliskan jalan cerita film untuk menarik minat penonton..."
                        class="mt-1.5 w-full rounded-xl border {{ $errors->has('synopsis') ? 'border-red-500 focus:ring-red-500 ring-1 ring-red-500' : 'border-border' }} p-3 text-sm"
                        @if ($errors->has('synopsis')) aria-invalid="true" @endif
                    >{{ old('synopsis', $film->synopsis) }}</textarea>
                    @error('synopsis')
                        <p class="mt-1 text-xs text-red-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Poster Upload --}}
                <div class="pt-2 border-t border-border">
                    <x-admin.image-upload
                        name="poster"
                        label="Poster Film Resmi (600 × 900 px)"
                        hint="Rasio portrait 2:3 (600 × 900 px), JPG/PNG/WebP, maks 5 MB."
                        :currentValue="$film->poster_path"
                        :recommendedWidth="600"
                        :recommendedHeight="900"
                        maxSizeMb="5"
                    />
                    @error('poster')
                        <p class="mt-1.5 text-xs text-red-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Pengaturan Lanjutan (Sutradara, Pemeran, Bahasa) --}}
                <div class="pt-3 border-t border-border space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-text-muted">
                        Pengaturan Lanjutan (Opsional)
                    </h3>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="director" class="block text-xs font-bold text-text">Sutradara</label>
                            <input
                                id="director"
                                name="director"
                                value="{{ old('director', $film->director) }}"
                                maxlength="180"
                                placeholder="Contoh: Christopher Nolan"
                                class="mt-1 min-h-[44px] w-full rounded-xl border border-border px-3 text-sm"
                            >
                            @error('director')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="casts" class="block text-xs font-bold text-text">Pemeran</label>
                            <input
                                id="casts"
                                name="casts"
                                value="{{ old('casts', $film->casts) }}"
                                maxlength="1000"
                                placeholder="Contoh: Matthew McConaughey, Anne Hathaway"
                                class="mt-1 min-h-[44px] w-full rounded-xl border border-border px-3 text-sm"
                            >
                            @error('casts')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="language" class="block text-xs font-bold text-text">Bahasa</label>
                            <input
                                id="language"
                                name="language"
                                value="{{ old('language', $film->language) }}"
                                maxlength="100"
                                placeholder="Contoh: Indonesia, Inggris"
                                class="mt-1 min-h-[44px] w-full rounded-xl border border-border px-3 text-sm"
                            >
                            @error('language')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="pt-2 border-t border-border">
                    <label for="license_notes" class="block text-xs font-bold uppercase tracking-wider text-text">
                        Catatan Lisensi / Internal (Hanya Admin)
                    </label>
                    <textarea id="license_notes" name="license_notes" rows="2" maxlength="3000" placeholder="Catatan internal pengelola..." class="mt-1.5 w-full rounded-xl border border-border p-3 text-sm">{{ old('license_notes', $film->license_notes) }}</textarea>
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
