@extends('layouts.public')

@section('title', 'Katalog Film - ' . setting('site_name', 'SynemaX'))

@section('meta')
    <meta name="description" content="Jelajahi koleksi film SynemaX. Pilih film favoritmu berdasarkan genre dan tahun rilis untuk diputar privat di studio mini.">
@endsection

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 space-y-8">
    {{-- Breadcrumb & Heading --}}
    <div class="space-y-3">
        <nav class="flex items-center gap-2 text-xs font-semibold text-text-muted" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-amber-500 transition">Beranda</a>
            <span class="text-text-subtle">/</span>
            <span class="text-text font-bold">Katalog Film</span>
        </nav>
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl sm:text-4xl font-black text-text tracking-tight">Katalog Film</h1>
                <p class="mt-1 text-sm text-text-muted">Pilih film favoritmu untuk diputar di studio bioskop mini privat SynemaX.</p>
            </div>
            <a href="{{ route('schedule.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 px-4 py-2 text-xs sm:text-sm font-bold shadow-md transition">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Lihat Jadwal Tayang</span>
            </a>
        </div>
    </div>

    {{-- Interactive Alpine Filter Section --}}
    <section
        x-data="{
            genre: new URLSearchParams(window.location.search).get('genre') || '{{ $selectedGenre }}' || '',
            tahun: new URLSearchParams(window.location.search).get('tahun') || '{{ $selectedYear }}' || '',
            search: new URLSearchParams(window.location.search).get('q') || '{{ $searchQuery }}' || '',
            allFilms: {{ Js::from($films->map(fn($f) => [
                'id' => $f->id,
                'title' => $f->title,
                'slug' => $f->slug,
                'genre' => $f->genre,
                'release_year' => $f->release_year ?: ($f->release_date ? $f->release_date->format('Y') : null),
                'duration_minutes' => $f->duration_minutes,
                'age_rating' => $f->age_rating,
                'poster_url' => $f->posterUrl(),
                'url' => route('films.show', $f->slug ?: $f->id),
            ])) }},
            get filteredFilms() {
                return this.allFilms.filter(f => {
                    const matchGenre = !this.genre || (f.genre && f.genre.toLowerCase().split(',').map(s => s.trim()).includes(this.genre.toLowerCase()));
                    const matchTahun = !this.tahun || String(f.release_year) === String(this.tahun);
                    const matchSearch = !this.search || (f.title && f.title.toLowerCase().includes(this.search.toLowerCase()));
                    return matchGenre && matchTahun && matchSearch;
                });
            },
            updateUrl() {
                const url = new URL(window.location);
                if (this.genre) url.searchParams.set('genre', this.genre); else url.searchParams.delete('genre');
                if (this.tahun) url.searchParams.set('tahun', this.tahun); else url.searchParams.delete('tahun');
                if (this.search) url.searchParams.set('q', this.search); else url.searchParams.delete('q');
                window.history.replaceState({}, '', url);
            },
            setFilter(g, y) {
                if (g !== undefined) this.genre = g;
                if (y !== undefined) this.tahun = y;
                this.updateUrl();
            },
            setSearch(s) {
                this.search = s;
                this.updateUrl();
            },
            resetFilters() {
                this.genre = '';
                this.tahun = '';
                this.search = '';
                this.updateUrl();
            },
            resetFilter() {
                this.resetFilters();
            }
        }"
        class="space-y-6"
    >
        {{-- Search Input Bar --}}
        <div class="relative w-full">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
                type="text"
                x-model="search"
                @input.debounce.300ms="updateUrl()"
                placeholder="Cari judul film favoritmu..."
                aria-label="Cari judul film"
                class="w-full rounded-2xl border border-slate-300 dark:border-white/10 bg-surface pl-10 pr-10 py-2.5 text-xs sm:text-sm font-medium text-text placeholder:text-text-muted focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-sm"
            >
            <button
                type="button"
                x-show="search"
                x-cloak
                @click="setSearch('')"
                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-text-muted hover:text-text p-1"
                title="Hapus pencarian"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Unified Accessible Film Filter Bar (Chip Scroll with desktop arrows + Custom Accessible Year Dropdown) --}}
        <x-public.film-filter-bar :genres="$genres" :years="$years" />

        {{-- Results Counter & Empty State --}}
        <div class="flex items-center justify-between text-xs font-semibold text-text-muted px-1">
            <span>Menampilkan <strong class="text-text" x-text="filteredFilms.length"></strong> film</span>
        </div>

        {{-- Film Grid (Poster 2:3 Ratio with Single Age Rating Badge) --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 sm:gap-6">
            <template x-for="film in filteredFilms" :key="film.id">
                <a
                    :href="film.url"
                    class="group relative flex flex-col rounded-2xl overflow-hidden border border-slate-300 dark:border-white/10 bg-surface dark:bg-[#0B101B] shadow-sm hover:shadow-xl hover:border-amber-500 transition-all duration-200"
                >
                    {{-- Poster Image 2:3 with single Age Rating Badge --}}
                    <div class="relative w-full aspect-[2/3] overflow-hidden bg-slate-900">
                        <template x-if="film.poster_url">
                            <img
                                :src="film.poster_url"
                                :alt="film.title"
                                loading="lazy"
                                class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                            >
                        </template>

                        {{-- Fallback Gradient Poster When Poster is Null --}}
                        <template x-if="!film.poster_url">
                            <div class="flex size-full flex-col items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-amber-950/40 p-3 text-center">
                                <svg class="size-8 text-amber-500/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                </svg>
                                <span class="mt-2 text-xs font-black text-amber-200/80 line-clamp-2" x-text="film.title"></span>
                            </div>
                        </template>

                        {{-- Single Clean Age Rating Badge (Top-Right) --}}
                        <template x-if="film.age_rating">
                            <span class="absolute top-2 right-2 rounded-md bg-black/75 px-1.5 py-0.5 text-[10px] font-black uppercase text-amber-400 backdrop-blur-md border border-white/15" x-text="film.age_rating"></span>
                        </template>
                    </div>

                    {{-- Title, Genre & Year Details --}}
                    <div class="flex flex-1 flex-col p-3 sm:p-3.5">
                        <h3 class="text-xs sm:text-sm font-black text-text line-clamp-1 group-hover:text-amber-500 transition" x-text="film.title"></h3>
                        <div class="mt-1 flex items-center justify-between gap-1 text-[11px] font-semibold text-text-muted">
                            <span class="truncate" x-text="film.genre || 'Film'"></span>
                            <span x-show="film.release_year" class="shrink-0" x-text="film.release_year"></span>
                        </div>
                        <template x-if="film.duration_minutes">
                            <div class="mt-2 pt-2 border-t border-slate-200 dark:border-white/5 flex items-center gap-1 text-[10px] text-text-subtle">
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span x-text="film.duration_minutes + ' menit'"></span>
                            </div>
                        </template>
                    </div>
                </a>
            </template>
        </div>

        {{-- Empty State --}}
        <div x-show="filteredFilms.length === 0" class="rounded-2xl border border-dashed border-slate-300 dark:border-white/15 bg-surface dark:bg-[#0B101B] p-12 text-center">
            <svg class="mx-auto size-12 text-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
            </svg>
            <h3 class="mt-3 text-base font-bold text-text">Belum ada film untuk filter ini</h3>
            <p class="mt-1 text-xs text-text-muted">Coba ubah kata kunci pencarian, genre, atau tahun rilis.</p>
            <button
                type="button"
                @click="resetFilters()"
                class="mt-4 inline-flex items-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 px-4 py-2 text-xs font-bold transition shadow-xs"
            >
                Reset Semua Filter
            </button>
        </div>
    </section>
</div>
@endsection
