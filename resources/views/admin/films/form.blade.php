@extends('layouts.admin')

@section('title', $film->exists ? 'Ubah Film' : 'Tambah Film Baru')

@section('content')
    <script>
        function filmFormHandler() {
            return {
                trailerUrl: @js(old('trailer_url', $film->trailer_url ?? '')),
                filmTitle: @js(old('title', $film->title ?? '')),
                customBadge: @js(old('custom_badge', $film->custom_badge ?? '')),
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
                            class="mt-1.5 min-h-[44px] w-full rounded-xl border {{ $errors->has('title') ? 'border-red-500 focus:ring-red-500 ring-1 ring-red-500' : 'border-border' }} bg-surface text-text px-3 text-sm"
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
                            class="mt-1.5 min-h-[44px] w-full rounded-xl border {{ $errors->has('slug') ? 'border-red-500 focus:ring-red-500' : 'border-border' }} bg-surface text-text px-3 text-sm font-mono"
                        >
                        @error('slug')
                            <p class="mt-1 text-xs text-red-500 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="pt-2 border-t border-border">
                    @php
                        $currentGenres = old('genres', $film->genresList());
                        $allActiveGenres = \App\Models\Genre::cachedActive();
                        $genreNamesInDb = $allActiveGenres->pluck('name')->all();
                        $fallbackCanon = \App\Models\Genre::defaultFallbackGenres();

                        // 18 Standard Cinema Genres
                        $mainGenreNames = array_values(array_intersect($fallbackCanon, $genreNamesInDb));
                        if (empty($mainGenreNames)) {
                            $mainGenreNames = $fallbackCanon;
                        }

                        // Custom / Niche Subgenres in database
                        $customGenreNames = array_values(array_diff($genreNamesInDb, $mainGenreNames));

                        // Any extra genres on existing film
                        $extraFilmGenres = array_values(array_diff((array) $currentGenres, $genreNamesInDb));
                        foreach ($extraFilmGenres as $extraG) {
                            if (! in_array($extraG, $customGenreNames, true)) {
                                $customGenreNames[] = $extraG;
                            }
                        }
                    @endphp

                    {{-- Interactive Admin-Friendly Genre Selector --}}
                    <div
                        class="space-y-3"
                        x-data="{
                            search: '',
                            selected: @js(array_values(array_unique((array) $currentGenres))),
                            mainGenres: @js($mainGenreNames),
                            customGenres: @js($customGenreNames),
                            newChips: [],
                            showCustom: false,

                            get hasSearch() {
                                return this.search.trim().length > 0;
                            },

                            get filteredMainGenres() {
                                if (!this.hasSearch) return this.mainGenres;
                                const q = this.search.toLowerCase().trim();
                                return this.mainGenres.filter(g => g.toLowerCase().includes(q));
                            },

                            get filteredCustomGenres() {
                                if (!this.hasSearch) return this.customGenres;
                                const q = this.search.toLowerCase().trim();
                                return this.customGenres.filter(g => g.toLowerCase().includes(q));
                            },

                            get exactMatchExists() {
                                const q = this.search.toLowerCase().trim();
                                if (!q) return true;
                                const all = [...this.mainGenres, ...this.customGenres, ...this.selected];
                                return all.some(g => g.toLowerCase() === q);
                            },

                            isSelected(genre) {
                                return this.selected.includes(genre);
                            },

                            toggle(genre) {
                                const idx = this.selected.indexOf(genre);
                                if (idx !== -1) {
                                    this.selected.splice(idx, 1);
                                } else {
                                    this.selected.push(genre);
                                }
                            },

                            remove(genre) {
                                const idx = this.selected.indexOf(genre);
                                if (idx !== -1) this.selected.splice(idx, 1);
                                const nIdx = this.newChips.indexOf(genre);
                                if (nIdx !== -1) this.newChips.splice(nIdx, 1);
                            },

                            clearAll() {
                                this.selected = [];
                                this.newChips = [];
                            },

                            addNewFromSearch() {
                                const raw = this.search.trim();
                                if (!raw) return;
                                const parts = raw.split(/[,;\n]+/).map(p => p.trim()).filter(Boolean);
                                for (let p of parts) {
                                    const all = [...this.mainGenres, ...this.customGenres];
                                    const found = all.find(g => g.toLowerCase() === p.toLowerCase());
                                    const target = found || p;
                                    if (!this.selected.includes(target)) {
                                        this.selected.push(target);
                                    }
                                    if (!found && !this.newChips.includes(target)) {
                                        this.newChips.push(target);
                                    }
                                }
                                this.search = '';
                            }
                        }"
                    >
                        {{-- Header Row: Label & Selected Count --}}
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-text">
                                Genre Film <span class="text-danger">*</span>
                                <span class="text-text-muted font-normal text-[11px] ml-1">
                                    (<span x-text="selected.length"></span> dipilih)
                                </span>
                            </label>
                            <button
                                type="button"
                                x-show="selected.length > 0"
                                @click="clearAll()"
                                class="text-xs text-danger hover:underline font-semibold"
                            >
                                Kosongkan Semua
                            </button>
                        </div>

                        {{-- Hidden inputs for standard form submission --}}
                        <template x-for="genre in selected" :key="'input_' + genre">
                            <input type="hidden" name="genres[]" :value="genre">
                        </template>
                        <template x-for="newG in newChips" :key="'new_' + newG">
                            <input type="hidden" name="new_genres[]" :value="newG">
                        </template>

                        {{-- Pinned Selected Genres Badges (At Top) --}}
                        <div class="rounded-xl border border-border bg-surface-2/30 p-2.5">
                            <div class="flex flex-wrap items-center gap-1.5" x-show="selected.length > 0">
                                <template x-for="genre in selected" :key="'badge_' + genre">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500 text-slate-950 font-bold px-2.5 py-1 text-xs shadow-xs transition-transform hover:scale-105">
                                        <span x-text="genre"></span>
                                        <button
                                            type="button"
                                            @click="remove(genre)"
                                            class="text-slate-950/70 hover:text-slate-950 text-sm leading-none ml-0.5"
                                            title="Hapus genre ini"
                                            aria-label="Hapus genre"
                                        >&times;</button>
                                    </span>
                                </template>
                            </div>
                            <p x-show="selected.length === 0" class="text-xs text-text-muted italic py-0.5">
                                Belum ada genre dipilih. Klik tombol genre utama di bawah atau ketik di kolom pencarian.
                            </p>
                        </div>

                        {{-- Quick Search & Add Bar --}}
                        <div class="space-y-1.5">
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-text-muted pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input
                                    type="text"
                                    x-model="search"
                                    @keydown.enter.prevent="addNewFromSearch()"
                                    placeholder="Cari genre cepat (misal: Horor, Komedi), atau ketik genre baru lalu tekan Enter..."
                                    class="min-h-[40px] w-full rounded-xl border border-border bg-surface pl-9 pr-8 text-xs text-text placeholder:text-text-muted focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500 transition"
                                >
                                <button
                                    type="button"
                                    x-show="search"
                                    @click="search = ''"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted hover:text-text text-sm font-bold p-0.5"
                                    title="Bersihkan pencarian"
                                >&times;</button>
                            </div>

                            {{-- Prompt to create new genre from search --}}
                            <div x-show="hasSearch && !exactMatchExists" class="pt-0.5">
                                <button
                                    type="button"
                                    @click="addNewFromSearch()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500/15 border border-amber-500/30 text-amber-600 dark:text-amber-400 font-bold text-xs hover:bg-amber-500/25 transition"
                                >
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                    <span>Tambahkan &ldquo;<span x-text="search.trim()"></span>&rdquo; sebagai genre baru (Tekan Enter)</span>
                                </button>
                            </div>
                        </div>

                        {{-- Section 1: 18 Genre Utama Bioskop --}}
                        <div class="space-y-1.5">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-text-muted">
                                Genre Utama Bioskop
                            </p>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="genre in filteredMainGenres" :key="'main_' + genre">
                                    <button
                                        type="button"
                                        @click="toggle(genre)"
                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all select-none"
                                        :class="isSelected(genre)
                                            ? 'bg-amber-500 text-slate-950 font-bold border-amber-500 shadow-xs'
                                            : 'bg-surface hover:bg-surface-2 border-border text-text hover:border-amber-500/50'"
                                        x-text="genre"
                                    ></button>
                                </template>
                            </div>
                            <p x-show="filteredMainGenres.length === 0 && hasSearch" class="text-xs text-text-muted italic py-1">
                                Tidak ada genre utama yang cocok dengan &ldquo;<span x-text="search"></span>&rdquo;.
                            </p>
                        </div>

                        {{-- Section 2: Subgenre / Tag Kustom (Collapsible) --}}
                        <template x-if="customGenres.length > 0">
                            <div class="space-y-1.5 pt-1">
                                <button
                                    type="button"
                                    @click="showCustom = !showCustom"
                                    class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-600 dark:text-amber-400 hover:text-amber-500 transition"
                                >
                                    <svg
                                        class="size-3.5 transition-transform duration-200"
                                        :class="showCustom || hasSearch ? 'rotate-90' : ''"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2.5"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                    <span x-text="(showCustom || hasSearch ? 'Sembunyikan' : 'Tampilkan') + ' Subgenre & Tag Kustom (' + customGenres.length + ')'"></span>
                                </button>

                                <div
                                    x-show="showCustom || hasSearch"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    class="rounded-xl border border-border bg-surface-2/40 p-2.5 max-h-48 overflow-y-auto [scrollbar-width:thin] space-y-1"
                                >
                                    <div class="flex flex-wrap gap-1.5">
                                        <template x-for="genre in filteredCustomGenres" :key="'custom_' + genre">
                                            <button
                                                type="button"
                                                @click="toggle(genre)"
                                                class="px-2.5 py-1 rounded-lg text-[11px] font-medium border transition-all select-none"
                                                :class="isSelected(genre)
                                                    ? 'bg-amber-500 text-slate-950 font-bold border-amber-500 shadow-xs'
                                                    : 'bg-surface hover:bg-surface-2 border-border/80 text-text hover:border-amber-500/40'"
                                                x-text="genre"
                                            ></button>
                                        </template>
                                    </div>
                                    <p x-show="filteredCustomGenres.length === 0 && hasSearch" class="text-xs text-text-muted italic py-1">
                                        Tidak ada subgenre kustom yang cocok.
                                    </p>
                                </div>
                            </div>
                        </template>
                    </div>

                    @error('genre')
                        <p class="mt-1.5 text-xs text-danger font-semibold">{{ $message }}</p>
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
                            class="mt-1.5 min-h-[44px] w-full rounded-xl border {{ $errors->has('release_year') ? 'border-red-500' : 'border-border' }} bg-surface text-text px-3 text-sm"
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
                            class="mt-1.5 min-h-[44px] w-full rounded-xl border {{ $errors->has('duration_minutes') ? 'border-red-500 focus:ring-red-500 ring-1 ring-red-500' : 'border-border' }} bg-surface text-text px-3 text-sm"
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
                            :options="\App\Models\AgeRating::optionsArray($film->age_rating)"
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
                            class="mt-1.5 min-h-[44px] w-full rounded-xl border {{ $errors->has('trailer_url') ? 'border-red-500' : 'border-border' }} bg-surface text-text px-3 text-sm font-mono"
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
                        class="mt-1.5 w-full rounded-xl border {{ $errors->has('synopsis') ? 'border-red-500 focus:ring-red-500 ring-1 ring-red-500' : 'border-border' }} bg-surface text-text p-3 text-sm"
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

                {{-- Pengaturan Pin / Badge Khusus Poster Film --}}
                <div class="pt-3 border-t border-border space-y-4">
                    <div>
                        <label for="custom_badge" class="block text-xs font-bold uppercase tracking-wider text-text">
                            Label Pin / Badge Poster (Tampil di Publik & Admin)
                        </label>
                        <p class="mt-1 text-xs text-text-muted">
                            Beri label mencolok warna merah di sisi bawah poster film (contoh: "Baru Ditambahkan", "Trending", "Segera Hadir", dll). Kosongkan jika tanpa pin.
                        </p>
                    </div>

                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-semibold text-text-muted">Pilihan Cepat:</span>
                            <button
                                type="button"
                                @click="customBadge = 'Baru Ditambahkan'"
                                class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-bold transition-all shadow-xs"
                                :class="customBadge === 'Baru Ditambahkan' ? 'bg-[#E50914] text-white ring-2 ring-red-400' : 'bg-surface-2 text-text border border-border hover:border-red-400'"
                            >
                                <span>🔥 Baru Ditambahkan</span>
                            </button>
                            <button
                                type="button"
                                @click="customBadge = 'Trending'"
                                class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-bold transition-all shadow-xs"
                                :class="customBadge === 'Trending' ? 'bg-[#E50914] text-white ring-2 ring-red-400' : 'bg-surface-2 text-text border border-border hover:border-red-400'"
                            >
                                <span>⚡ Trending</span>
                            </button>
                            <button
                                type="button"
                                @click="customBadge = 'Segera Hadir'"
                                class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-bold transition-all shadow-xs"
                                :class="customBadge === 'Segera Hadir' ? 'bg-[#E50914] text-white ring-2 ring-red-400' : 'bg-surface-2 text-text border border-border hover:border-red-400'"
                            >
                                <span>⏳ Segera Hadir</span>
                            </button>
                            <button
                                type="button"
                                @click="customBadge = 'Eksklusif'"
                                class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-bold transition-all shadow-xs"
                                :class="customBadge === 'Eksklusif' ? 'bg-[#E50914] text-white ring-2 ring-red-400' : 'bg-surface-2 text-text border border-border hover:border-red-400'"
                            >
                                <span>⭐ Eksklusif</span>
                            </button>
                            <button
                                type="button"
                                @click="customBadge = 'Favorit'"
                                class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-bold transition-all shadow-xs"
                                :class="customBadge === 'Favorit' ? 'bg-[#E50914] text-white ring-2 ring-red-400' : 'bg-surface-2 text-text border border-border hover:border-red-400'"
                            >
                                <span>❤️ Favorit</span>
                            </button>
                            <button
                                type="button"
                                x-show="customBadge && customBadge.length > 0"
                                @click="customBadge = ''"
                                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold text-danger hover:underline transition-all"
                            >
                                <span>✕ Hapus Pin (Kosongkan)</span>
                            </button>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3 items-start">
                            <div class="sm:col-span-2">
                                <div class="relative">
                                    <input
                                        id="custom_badge"
                                        name="custom_badge"
                                        x-model="customBadge"
                                        maxlength="50"
                                        placeholder="Ketik teks pin khusus atau klik tombol cepat di atas..."
                                        class="min-h-[44px] w-full rounded-xl border border-border bg-surface text-text px-3.5 text-sm placeholder:text-text-muted focus:border-red-500 focus:ring-1 focus:ring-red-500 transition-colors"
                                    >
                                    <button
                                        type="button"
                                        x-show="customBadge && customBadge.length > 0"
                                        @click="customBadge = ''"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted hover:text-text p-1"
                                        title="Kosongkan"
                                    >
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                @error('custom_badge')
                                    <p class="mt-1 text-xs text-red-500 font-semibold">{{ $message }}</p>
                                @enderror
                                <p class="mt-1.5 text-[11px] text-text-muted">
                                    Maksimal 50 karakter. Teks akan tampil dalam bentuk badge merah kontras di sisi bawah kartu poster.
                                </p>
                            </div>

                            {{-- Live Preview Box --}}
                            <div class="rounded-xl border border-border bg-surface-2 p-3 text-center flex flex-col items-center justify-center">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-text-muted mb-2">Pratinjau Pin Poster</span>
                                <div class="relative w-28 aspect-[2/3] rounded-lg overflow-hidden border border-border bg-slate-900 shadow-sm flex items-center justify-center">
                                    @if ($film->poster_path)
                                        <img src="{{ $film->posterUrl() }}" alt="Poster" class="size-full object-cover">
                                    @else
                                        <div class="p-2 text-center text-text-muted text-[10px] leading-tight">Poster Film</div>
                                    @endif

                                    <template x-if="customBadge && customBadge.trim() !== ''">
                                        <div class="absolute bottom-1.5 inset-x-0 flex justify-center px-1 z-10 pointer-events-none">
                                            <span class="inline-flex items-center justify-center bg-[#E50914] text-white font-black text-[9px] px-2 py-0.5 rounded shadow-md truncate max-w-full drop-shadow-md border border-white/20 select-none" x-text="customBadge"></span>
                                        </div>
                                    </template>
                                    <template x-if="!customBadge || customBadge.trim() === ''">
                                        <span class="absolute bottom-1.5 text-[8px] text-text-muted italic bg-black/60 px-1.5 py-0.5 rounded">Tanpa Pin</span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
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
                                class="mt-1 min-h-[44px] w-full rounded-xl border border-border bg-surface text-text px-3 text-sm"
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
                                class="mt-1 min-h-[44px] w-full rounded-xl border border-border bg-surface text-text px-3 text-sm"
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
                                class="mt-1 min-h-[44px] w-full rounded-xl border border-border bg-surface text-text px-3 text-sm"
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
                    <textarea id="license_notes" name="license_notes" rows="2" maxlength="3000" placeholder="Catatan internal pengelola..." class="mt-1.5 w-full rounded-xl border border-border bg-surface text-text p-3 text-sm">{{ old('license_notes', $film->license_notes) }}</textarea>
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
