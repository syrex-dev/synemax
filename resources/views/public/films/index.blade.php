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

    {{-- Interactive Film Filter & Search Section --}}
    <section
        x-data="{
            genre: '{{ $selectedGenre }}',
            tahun: '{{ $selectedYear }}',
            search: '{{ $searchQuery }}',
            isSearching: false,
            setFilter(g, y) {
                const url = new URL(window.location.origin + window.location.pathname);
                const targetGenre = g !== undefined ? g : this.genre;
                const targetTahun = y !== undefined ? y : this.tahun;
                if (targetGenre) url.searchParams.set('genre', targetGenre);
                if (targetTahun) url.searchParams.set('tahun', targetTahun);
                if (this.search) url.searchParams.set('cari', this.search);
                window.location.href = url.toString();
            },
            async liveSearch() {
                this.isSearching = true;
                const url = new URL(window.location.origin + window.location.pathname);
                if (this.genre) url.searchParams.set('genre', this.genre);
                if (this.tahun) url.searchParams.set('tahun', this.tahun);
                const trimmed = (this.search || '').trim();
                if (trimmed.length > 0) {
                    url.searchParams.set('cari', trimmed);
                } else {
                    url.searchParams.delete('cari');
                }
                url.searchParams.delete('page');

                window.history.replaceState({}, '', url.toString());

                try {
                    const response = await fetch(url.toString(), {
                        headers: {
                            'Accept': 'text/html'
                        }
                    });
                    const html = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    const newGrid = doc.getElementById('public-films-grid-container');
                    const currentGrid = document.getElementById('public-films-grid-container');
                    if (newGrid && currentGrid) {
                        currentGrid.innerHTML = newGrid.innerHTML;
                        if (window.Alpine && window.Alpine.initTree) {
                            window.Alpine.initTree(currentGrid);
                        }
                    }
                } catch (e) {
                    console.error('Public live search error:', e);
                } finally {
                    this.isSearching = false;
                }
            },
            clearSearch() {
                this.search = '';
                this.liveSearch();
            },
            resetFilters() {
                window.location.href = '{{ route('films.index') }}';
            },
            resetFilter() {
                this.resetFilters();
            }
        }"
        class="space-y-6"
    >
        {{-- Search Input Form Live Search --}}
        <form action="{{ route('films.index') }}" method="GET" @submit.prevent="liveSearch()" class="relative w-full">
            @if ($selectedGenre)
                <input type="hidden" name="genre" value="{{ $selectedGenre }}">
            @endif
            @if ($selectedYear)
                <input type="hidden" name="tahun" value="{{ $selectedYear }}">
            @endif
            <div class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted">
                <svg x-show="!isSearching" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <svg x-show="isSearching" x-cloak class="size-4 animate-spin text-amber-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <input
                type="text"
                name="cari"
                x-model="search"
                @input.debounce.300ms="liveSearch()"
                placeholder="Cari judul film favoritmu..."
                aria-label="Cari judul film"
                autocomplete="off"
                class="w-full rounded-2xl border border-slate-300 dark:border-white/10 bg-surface pl-10 pr-10 py-2.5 text-xs sm:text-sm font-medium text-text placeholder:text-text-muted focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-sm transition-colors"
            >
            <button
                type="button"
                x-show="search"
                x-cloak
                @click="clearSearch()"
                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-text-muted hover:text-text p-1"
                title="Hapus pencarian"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </form>

        {{-- Unified Accessible Film Filter Bar --}}
        <x-public.film-filter-bar :genres="$genres" :years="$years" />

        {{-- Dynamic Film Grid & Pagination Container --}}
        <div
            id="public-films-grid-container"
            class="space-y-6 transition-opacity duration-150"
            :class="{ 'opacity-60 pointer-events-none': isSearching }"
        >
            {{-- Results Counter --}}
            <div class="flex items-center justify-between text-xs font-semibold text-text-muted px-1">
                <span>Menampilkan <strong class="text-text">{{ $films->total() }}</strong> film</span>
                @if ($films->hasPages())
                    <span>Halaman <strong class="text-text">{{ $films->currentPage() }}</strong> dari <strong class="text-text">{{ $films->lastPage() }}</strong></span>
                @endif
            </div>

            {{-- Film Grid (Server-Rendered 30 items per page) --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 sm:gap-6">
                @forelse ($films as $film)
                    <a
                        href="{{ route('films.show', $film->slug ?: $film->id) }}"
                        class="group relative flex flex-col rounded-2xl overflow-hidden border border-slate-300 dark:border-white/10 bg-surface dark:bg-[#0B101B] shadow-sm hover:shadow-xl hover:border-amber-500 transition-all duration-200"
                    >
                        {{-- Poster Image 2:3 with single Age Rating Badge --}}
                        <div class="relative w-full aspect-[2/3] overflow-hidden bg-slate-900">
                            @if ($film->posterUrl())
                                <img
                                    src="{{ $film->posterUrl() }}"
                                    alt="{{ $film->title }}"
                                    loading="lazy"
                                    class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                                >
                            @else
                                {{-- Fallback Gradient Poster When Poster is Null --}}
                                <div class="flex size-full flex-col items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-amber-950/40 p-3 text-center">
                                    <svg class="size-8 text-amber-500/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                    </svg>
                                    <span class="mt-2 text-xs font-black text-amber-200/80 line-clamp-2">{{ $film->title }}</span>
                                </div>
                            @endif

                            {{-- Single Clean Age Rating Badge (Top-Right) --}}
                            @if ($film->age_rating)
                                <span class="absolute top-2 right-2 rounded-md bg-black/75 px-1.5 py-0.5 text-[10px] font-black uppercase text-amber-400 backdrop-blur-md border border-white/15">
                                    {{ $film->age_rating }}
                                </span>
                            @endif
                        </div>

                        {{-- Title, Genre & Year Details --}}
                        <div class="flex flex-1 flex-col p-3 sm:p-3.5">
                            <h3 class="text-xs sm:text-sm font-black text-text line-clamp-1 group-hover:text-amber-500 transition">{{ $film->title }}</h3>
                            <div class="mt-1 flex items-center justify-between gap-1 text-[11px] font-semibold text-text-muted">
                                <span class="line-clamp-2 leading-tight" title="{{ $film->genre }}">{{ $film->formattedGenresText(3) }}</span>
                                @if ($film->release_year)
                                    <span class="shrink-0">{{ $film->release_year }}</span>
                                @endif
                            </div>
                            @if ($film->duration_minutes)
                                <div class="mt-2 pt-2 border-t border-slate-200 dark:border-white/5 flex items-center gap-1 text-[10px] text-text-subtle">
                                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>{{ $film->duration_minutes }} menit</span>
                                </div>
                            @endif
                        </div>
                    </a>
                @empty
                    {{-- Empty State --}}
                    <div class="col-span-full rounded-2xl border border-dashed border-slate-300 dark:border-white/15 bg-surface dark:bg-[#0B101B] p-12 text-center">
                        <svg class="mx-auto size-12 text-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                        </svg>
                        <h3 class="mt-3 text-base font-bold text-text">Belum ada film untuk filter ini</h3>
                        <p class="mt-1 text-xs text-text-muted">Coba ubah kata kunci pencarian, genre, atau tahun rilis.</p>
                        <a
                            href="{{ route('films.index') }}"
                            class="mt-4 inline-flex items-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 px-4 py-2 text-xs font-bold transition shadow-xs"
                        >
                            Reset Semua Filter
                        </a>
                    </div>
                @endforelse
            </div>

            {{-- Server Pagination Links --}}
            @if ($films->hasPages())
                <div class="mt-8 pt-4 border-t border-slate-200 dark:border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <span class="text-xs font-semibold text-text-muted">
                        Menampilkan {{ $films->firstItem() }}–{{ $films->lastItem() }} dari {{ $films->total() }} film
                    </span>
                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                        {{ $films->links() }}
                    </div>
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
