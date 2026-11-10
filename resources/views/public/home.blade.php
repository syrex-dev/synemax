@extends('layouts.public')

@section('title', setting('site_name', 'SynemaX') . ' – ' . setting('site_tagline', 'Studio Bioskop Mini Privat'))
@section('meta')
    <meta name="description" content="{{ setting('seo_meta_description', 'Bioskop mini privat desa dengan teknologi modern, sofa empuk, dan audio sinematik.') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ setting('site_name', 'SynemaX') }}">
    <meta property="og:description" content="{{ setting('seo_meta_description', 'Bioskop mini privat desa.') }}">
    @if (setting('site_og_image'))
        <meta property="og:image" content="{{ Storage::disk('public')->url(setting('site_og_image')) }}">
    @endif
@endsection

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-4 sm:px-6 sm:py-6 space-y-7 sm:space-y-12">

        {{-- ============================================================== --}}
        {{-- 1. HERO CAROUSEL BANNER (Full-Bleed Mobile, Rasio Asli / 4:3) --}}
        {{-- ============================================================== --}}
        <section aria-label="Sorotan Utama Bioskop" class="-mx-4 sm:mx-0">
            @if ($heroBanners->count() > 0)
                <x-ui.carousel
                    id="hero-carousel"
                    :total="$heroBanners->count()"
                    :autoplay="setting('home_hero_autoplay', true)"
                    :interval="((int) setting('home_hero_interval', 5)) * 1000"
                    :fade="true"
                    :bleed="false"
                    label="Sorotan Utama Bioskop"
                    arrowPrevLabel="Banner sebelumnya"
                    arrowNextLabel="Banner berikutnya"
                    arrowPrevLoopLabel="Kembali ke banner terakhir"
                    arrowNextLoopLabel="Kembali ke banner pertama"
                    trackClass="gap-0 w-full"
                >
                    @foreach ($heroBanners as $index => $banner)
                        <div
                            data-carousel-slide="{{ $index }}"
                            class="w-full shrink-0 snap-start overflow-hidden sm:rounded-3xl bg-slate-900 border-y sm:border border-border dark:border-white/10"
                        >
                            @php
                                $targetUrl = $banner->resolvedUrl();
                            @endphp
                            <div class="relative w-full aspect-[4/3] sm:aspect-[2.35/1] max-h-[300px] sm:max-h-none overflow-hidden select-none">
                                @if ($targetUrl)
                                    <a href="{{ $targetUrl }}" class="block size-full" title="{{ $banner->title }}">
                                @endif

                                @if ($banner->imageUrl())
                                    <picture class="size-full">
                                        @if ($banner->mobile_image_path)
                                            <source media="(max-width: 640px)" srcset="{{ Storage::disk('public')->url($banner->mobile_image_path) }}">
                                        @endif
                                        <img
                                            src="{{ $banner->imageUrl() }}"
                                            alt="{{ $banner->title }}"
                                            @if ($loop->first)
                                                loading="eager"
                                                fetchpriority="high"
                                            @else
                                                loading="lazy"
                                            @endif
                                            draggable="false"
                                            class="size-full object-cover object-center select-none"
                                        >
                                    </picture>
                                @else
                                    <div class="flex size-full flex-col items-center justify-center p-6 text-center bg-surface-2 text-text">
                                        <x-brand.logo size="lg" />
                                        <p class="mt-2 text-xs sm:text-sm font-medium text-text-muted">
                                            {{ setting('site_tagline', 'Studio Bioskop Mini Privat') }}
                                        </p>
                                    </div>
                                @endif

                                @if ($targetUrl)
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </x-ui.carousel>
            @endif
        </section>


        {{-- ============================================================== --}}
        {{-- 3. KATALOG FILM (Carousel Horisontal Mobile ~2.3 Poster)       --}}
        {{-- ============================================================== --}}
        <script>
        function homeCatalogManager() {
            const config = {{ Js::from([
                'initialGenre' => $selectedGenre ?? '',
                'initialTahun' => (string) ($selectedYear ?? ''),
                'initialFilms' => $filmsData ?? [],
                'hasActiveFilterOnLoad' => (($selectedGenre ?? '') !== '') || (($selectedYear ?? '') !== '' && is_numeric($selectedYear)),
                'initialTotalCount' => $totalFilteredCount ?? 0,
                'filmsLimit' => $filmsLimit ?? 12,
                'catalogEndpoint' => route('films.index', [], false),
                'homeEndpoint' => route('home', [], false),
            ]) }};

            const initialList = Array.isArray(config.initialFilms) ? config.initialFilms : [];

            return {
                genre: config.initialGenre || new URLSearchParams(window.location.search).get('genre') || '',
                tahun: config.initialTahun || new URLSearchParams(window.location.search).get('tahun') || '',
                films: initialList,
                defaultFilms: config.hasActiveFilterOnLoad ? null : initialList,
                totalMatchCount: Number(config.initialTotalCount || initialList.length),
                isLoading: false,
                abortController: null,
                requestSeq: 0,

                get hasActiveFilter() {
                    return Boolean(this.genre || this.tahun);
                },

                get filteredFilms() {
                    return this.films;
                },

                get catalogUrl() {
                    const url = new URL(config.catalogEndpoint, window.location.origin);
                    if (this.genre) url.searchParams.set('genre', this.genre);
                    if (this.tahun) url.searchParams.set('tahun', this.tahun);
                    return url.toString();
                },

                async setFilter(g, t) {
                    if (g !== undefined) this.genre = g;
                    if (t !== undefined) this.tahun = t;

                    const url = new URL(window.location);
                    if (this.genre) url.searchParams.set('genre', this.genre); else url.searchParams.delete('genre');
                    if (this.tahun) url.searchParams.set('tahun', this.tahun); else url.searchParams.delete('tahun');
                    window.history.replaceState({}, '', url);

                    if (!this.genre && !this.tahun) {
                        if (this.abortController) {
                            this.abortController.abort();
                            this.abortController = null;
                        }
                        this.requestSeq++;
                        if (this.defaultFilms !== null) {
                            this.isLoading = false;
                            this.films = this.defaultFilms;
                            this.totalMatchCount = this.defaultFilms.length;
                            return;
                        }
                        window.location.href = config.homeEndpoint;
                        return;
                    }

                    if (this.abortController) {
                        this.abortController.abort();
                    }
                    const controller = new AbortController();
                    this.abortController = controller;
                    const currentSeq = ++this.requestSeq;
                    this.isLoading = true;

                    try {
                        const fetchUrl = new URL(config.catalogEndpoint, window.location.origin);
                        if (this.genre) fetchUrl.searchParams.set('genre', this.genre);
                        if (this.tahun) fetchUrl.searchParams.set('tahun', this.tahun);
                        fetchUrl.searchParams.set('limit', String(config.filmsLimit || 12));

                        const response = await fetch(fetchUrl.toString(), {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            signal: controller.signal,
                        });

                        if (!response.ok) {
                            throw new Error('Gagal memuat film.');
                        }

                        const data = await response.json();
                        if (currentSeq !== this.requestSeq) return;

                        this.films = Array.isArray(data.films) ? data.films : [];
                        this.totalMatchCount = typeof data.count === 'number' ? data.count : this.films.length;
                    } catch (error) {
                        if (error && error.name === 'AbortError') return;
                        if (typeof window.showToast === 'function') {
                            window.showToast({ type: 'error', message: 'Gagal memuat hasil filter film.' });
                        }
                    } finally {
                        if (currentSeq === this.requestSeq) {
                            this.isLoading = false;
                        }
                    }
                },

                resetFilter() {
                    this.setFilter('', '');
                }
            };
        }
        </script>

        <section
            aria-label="Katalog Film"
            id="katalog-film"
            x-data="homeCatalogManager()"
            class="space-y-4 sm:space-y-6"
        >
            {{-- Header Section: Amber Accent Line + Title + Action in 1 Line --}}
            <div class="flex items-center justify-between gap-3 border-l-4 border-amber-500 pl-3 sm:pl-4">
                <div class="min-w-0">
                    <h2 class="text-xl sm:text-2xl font-black text-text tracking-tight">Katalog Film</h2>
                    <p class="mt-0.5 text-xs text-text-muted line-clamp-1 sm:line-clamp-none">Pilihan film on-demand berkualitas untuk diputar di studio</p>
                </div>
                <a href="{{ route('films.index') }}" :href="catalogUrl" class="shrink-0 whitespace-nowrap text-xs sm:text-sm font-bold text-amber-800 dark:text-amber-400 hover:underline">
                    <span class="sm:hidden">Semua &gt;</span>
                    <span class="hidden sm:inline">Lihat semua film &gt;</span>
                </a>
            </div>

            {{-- Reusable Film Filter Bar Component --}}
            <x-public.film-filter-bar :genres="$genres" :years="$years" />

            {{-- Responsive Film Grid (2 Kolom Mobile, 3 Kolom Tablet, 4 Kolom Desktop) --}}
            <div
                class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 sm:gap-6 transition-opacity duration-150"
                :class="{ 'opacity-60 pointer-events-none': isLoading }"
            >
                <template x-for="film in filteredFilms" :key="film.id">
                    <a
                        :href="film.url"
                        class="group relative flex flex-col rounded-xl sm:rounded-2xl overflow-hidden border border-border bg-surface shadow-2xs hover:border-amber-500 transition-colors"
                    >
                        {{-- Poster Image 2:3 with single Age Rating Badge --}}
                        <div class="relative w-full aspect-[2/3] overflow-hidden bg-slate-900">
                            <template x-if="film.poster_url">
                                <img
                                    :src="film.poster_url"
                                    :alt="film.title"
                                    loading="lazy"
                                    draggable="false"
                                    class="size-full object-cover transition-transform duration-200 group-hover:scale-102 select-none"
                                >
                            </template>
                            <template x-if="!film.poster_url">
                                <div class="flex size-full flex-col items-center justify-center p-3 text-center bg-slate-900 border border-white/10 text-text">
                                    <svg class="size-8 text-amber-500/60 mb-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                    </svg>
                                    <span class="text-[11px] font-bold text-amber-200 line-clamp-2" x-text="film.title"></span>
                                </div>
                            </template>

                            {{-- Single Clean Age Rating Badge in Corner --}}
                            <div class="absolute top-2 right-2 z-10" x-show="film.age_rating">
                                <span class="inline-flex items-center rounded-md bg-black/80 px-1.5 py-0.5 text-[10px] font-black text-white border border-white/20" x-text="film.age_rating"></span>
                            </div>

                            {{-- Custom Pin Badge (Contoh: Baru Ditambahkan, Trending, dll) --}}
                            <template x-if="film.custom_badge">
                                <div class="absolute bottom-2 inset-x-0 flex justify-center px-2 z-10 pointer-events-none">
                                    <span class="inline-flex items-center justify-center bg-[#E50914] text-white font-black text-[10px] sm:text-xs px-2.5 py-0.5 rounded shadow-md truncate max-w-full drop-shadow-md select-none border border-white/20" x-text="film.custom_badge"></span>
                                </div>
                            </template>
                        </div>

                        {{-- Metadata Below Poster: 2 lines max title + meta --}}
                        <div class="p-2.5 sm:p-3 flex flex-col flex-1 justify-between">
                            <div>
                                <h3 class="font-bold text-xs sm:text-sm text-text line-clamp-2 min-h-[2.2rem] group-hover:text-amber-500 transition-colors" x-text="film.title"></h3>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-[11px] text-text-muted border-t border-border pt-1.5 font-medium">
                                <span class="truncate max-w-[65%]" x-text="film.genre ? film.genre.split(',')[0].trim() : '-'"></span>
                                <span x-text="film.release_year || '-'"></span>
                            </div>
                        </div>
                    </a>
                </template>
            </div>

            {{-- Footer Action when Filter Yields More Films Than Homepage Limit --}}
            <div
                x-show="hasActiveFilter && !isLoading && totalMatchCount > filteredFilms.length"
                x-cloak
                class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border bg-surface px-4 py-3 text-xs"
            >
                <span class="text-text-muted">
                    Menampilkan <strong class="text-text" x-text="filteredFilms.length"></strong> dari <strong class="text-text" x-text="totalMatchCount"></strong> film yang cocok.
                </span>
                <a
                    :href="catalogUrl"
                    class="inline-flex items-center gap-1 font-bold text-amber-800 dark:text-amber-400 hover:underline"
                >
                    <span>Lihat semua <span x-text="totalMatchCount"></span> film</span>
                    <span aria-hidden="true">&gt;</span>
                </a>
            </div>

            {{-- Empty State for Filter --}}
            <div x-show="!isLoading && filteredFilms.length === 0" x-cloak class="rounded-xl border border-border bg-surface p-8 text-center">
                <p class="text-sm font-bold text-text">Belum ada film untuk filter ini</p>
                <p class="mt-1 text-xs text-text-muted">Coba ubah pilihan genre atau tahun rilis Anda.</p>
                <button type="button" @click="resetFilter()" class="mt-3 inline-flex min-h-11 items-center rounded-xl bg-accent px-4 py-1.5 text-xs font-bold text-accent-contrast shadow-xs">
                    Reset Filter
                </button>
            </div>
        </section>

        {{-- Section Divider --}}
        <div class="border-t border-border"></div>

        {{-- ============================================================== --}}
        {{-- 4. PROMO & PENAWARAN (Carousel Bleed, Peek 10%, Panah & Loop) --}}
        {{-- ============================================================== --}}
        @if ($promos->count() > 0)
            <section aria-label="Promo dan Penawaran" class="space-y-4 sm:space-y-6">
                {{-- Header Section --}}
                <div class="flex items-center justify-between gap-3 border-l-4 border-amber-500 pl-3 sm:pl-4">
                    <div class="min-w-0">
                        <h2 class="text-xl sm:text-2xl font-black text-text tracking-tight">Promo & Penawaran</h2>
                        <p class="mt-0.5 text-xs text-text-muted line-clamp-1 sm:line-clamp-none">Penawaran hemat dan paket spesial menonton di SynemaX</p>
                    </div>
                    <a href="{{ route('promos.index') }}" class="shrink-0 whitespace-nowrap text-xs sm:text-sm font-bold text-amber-800 dark:text-amber-400 hover:underline">
                        <span class="sm:hidden">Semua &gt;</span>
                        <span class="hidden sm:inline">Lihat semua &gt;</span>
                    </a>
                </div>

                {{-- Carousel Viewport with Bleed on mobile and Peek 10% --}}
                <x-ui.carousel
                    id="promo-carousel"
                    :total="$promos->count()"
                    :bleed="true"
                    label="Promo dan Penawaran"
                    arrowPrevLabel="Promo sebelumnya"
                    arrowNextLabel="Promo berikutnya"
                    arrowPrevLoopLabel="Kembali ke promo terakhir"
                    arrowNextLoopLabel="Kembali ke promo pertama"
                    trackClass="gap-3 sm:gap-6"
                >
                    @foreach ($promos as $index => $promo)
                        <div
                            data-carousel-slide="{{ $index }}"
                            class="w-[86%] sm:w-[70%] lg:w-[48%] shrink-0 snap-start"
                        >
                            <a
                                href="{{ route('promos.show', $promo->slug) }}"
                                class="group relative block w-full aspect-[2/1] overflow-hidden rounded-xl sm:rounded-2xl border border-border bg-surface shadow-2xs hover:border-amber-500 transition-colors focus-visible:outline-2 focus-visible:outline-amber-500"
                            >
                                @if ($promo->imageUrl())
                                    <img
                                        src="{{ $promo->imageUrl() }}"
                                        alt="{{ $promo->title }}"
                                        loading="lazy"
                                        draggable="false"
                                        class="size-full object-cover transition-transform duration-200 group-hover:scale-102 select-none"
                                    >
                                @else
                                    <div class="flex size-full flex-col items-center justify-center p-4 text-center bg-surface-2 text-text">
                                        <span class="text-sm font-bold text-accent">{{ $promo->title }}</span>
                                    </div>
                                @endif
                            </a>
                        </div>
                    @endforeach
                </x-ui.carousel>
            </section>
        @endif

        {{-- Section Divider --}}
        <div class="border-t border-border"></div>

        {{-- ============================================================== --}}
        {{-- 5. MAKANAN & MINUMAN (Carousel Bleed on Mobile, Grid Desktop) --}}
        {{-- ============================================================== --}}
        @if (count($snackBanners ?? []) > 0)
            <section aria-label="Makanan dan Minuman" class="space-y-4 sm:space-y-6">
                {{-- Header Section --}}
                <div class="flex items-center justify-between gap-3 border-l-4 border-amber-500 pl-3 sm:pl-4">
                    <div class="min-w-0">
                        <h2 class="text-xl sm:text-2xl font-black text-text tracking-tight">Makanan & Minuman</h2>
                        <p class="mt-0.5 text-xs text-text-muted line-clamp-1 sm:line-clamp-none">Popcorn hangat, minuman segar, dan camilan lezat menemani nontonmu</p>
                    </div>
                    <a href="{{ route('booking.ticket.index') }}" class="shrink-0 whitespace-nowrap text-xs sm:text-sm font-bold text-amber-800 dark:text-amber-400 hover:underline">
                        <span class="sm:hidden">Pesan &gt;</span>
                        <span class="hidden sm:inline">Pesan camilan &gt;</span>
                    </a>
                </div>

                <x-ui.carousel
                    id="snack-carousel"
                    :total="count($snackBanners)"
                    :bleed="true"
                    label="Makanan dan Minuman"
                    arrowPrevLabel="Menu sebelumnya"
                    arrowNextLabel="Menu berikutnya"
                    arrowPrevLoopLabel="Kembali ke menu terakhir"
                    arrowNextLoopLabel="Kembali ke menu pertama"
                    trackClass="gap-3 sm:gap-6"
                >
                    @foreach ($snackBanners as $index => $sb)
                        <div
                            data-carousel-slide="{{ $index }}"
                            class="w-[86%] sm:w-[70%] lg:w-[48%] shrink-0 snap-start"
                        >
                            <a
                                href="{{ $sb['url'] ?? route('booking.ticket.index') }}"
                                class="group relative block aspect-[2/1] w-full overflow-hidden rounded-xl sm:rounded-2xl border border-border bg-surface shadow-2xs hover:border-amber-500 transition-colors focus-visible:outline-2 focus-visible:outline-amber-500"
                                title="{{ $sb['title'] ?? 'Makanan & Minuman' }}"
                            >
                                @php
                                    $sbUrl = !empty($sb['image_path']) ? (str_starts_with($sb['image_path'], 'http') ? $sb['image_path'] : Storage::disk('public')->url($sb['image_path'])) : null;
                                @endphp
                                @if ($sbUrl)
                                    <img
                                        src="{{ $sbUrl }}"
                                        alt="{{ $sb['title'] ?? 'Makanan & Minuman' }}"
                                        loading="lazy"
                                        draggable="false"
                                        class="size-full object-cover transition-transform duration-200 group-hover:scale-102 select-none"
                                    >
                                @else
                                    <div class="flex size-full flex-col items-center justify-center p-4 text-center bg-surface-2 text-text">
                                        <span class="text-sm font-bold text-accent">{{ $sb['title'] ?? 'Makanan & Minuman' }}</span>
                                    </div>
                                @endif
                            </a>
                        </div>
                    @endforeach
                </x-ui.carousel>
            </section>
        @endif

        {{-- Section Divider --}}
        <div class="border-t border-border"></div>

        {{-- ============================================================== --}}
        {{-- 6. NOBAR MENDATANG (Gambar Di Atas, 1 Kolom Mobile, 2x2 Desktop)--}}
        {{-- ============================================================== --}}
        @if ($infoNobarEnabled && $upcomingNobar->count() > 0)
            <section aria-label="Nonton Bareng Mendatang" class="space-y-4 sm:space-y-6">
                {{-- Header Section --}}
                <div class="flex items-center justify-between gap-3 border-l-4 border-amber-500 pl-3 sm:pl-4">
                    <div class="min-w-0">
                        <h2 class="text-xl sm:text-2xl font-black text-text tracking-tight">Nobar Mendatang</h2>
                        <p class="mt-0.5 text-xs text-text-muted line-clamp-1 sm:line-clamp-none">Siaran langsung pertandingan seru komunitas di layar bioskop</p>
                    </div>
                    <a href="{{ route('nobar.index') }}" class="shrink-0 whitespace-nowrap text-xs sm:text-sm font-bold text-amber-800 dark:text-amber-400 hover:underline">
                        <span class="sm:hidden">Semua &gt;</span>
                        <span class="hidden sm:inline">Semua acara &gt;</span>
                    </a>
                </div>

                {{-- Grid: 1 Kolom Mobile, 2 Kolom Desktop --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    @foreach ($upcomingNobar as $nobar)
                        <a
                            href="{{ route('nobar.show', $nobar->slug) }}"
                            class="group relative flex flex-col rounded-xl sm:rounded-2xl overflow-hidden border border-border bg-surface shadow-2xs hover:border-amber-500 transition-colors focus-visible:outline-2 focus-visible:outline-amber-500"
                        >
                            {{-- Banner Gambar 2:1 Di Atas --}}
                            <div class="relative w-full aspect-[2/1] overflow-hidden bg-surface-2">
                                @if ($nobar->hasPoster())
                                    <img
                                        src="{{ $nobar->posterUrl() }}"
                                        alt="{{ $nobar->posterAltText() }}"
                                        loading="lazy"
                                        draggable="false"
                                        class="size-full object-cover group-hover:scale-102 transition-transform duration-200 select-none"
                                    >
                                @else
                                    <div class="flex size-full flex-col items-center justify-center p-4 text-center bg-surface-sunken">
                                        <span class="text-xs font-bold text-text-muted uppercase tracking-wider">Nobar SynemaX</span>
                                    </div>
                                @endif

                                {{-- Kickoff Date Badge Overlay --}}
                                <div class="absolute top-2.5 left-2.5 z-10">
                                    <span class="inline-flex items-center gap-1 rounded-md bg-black/80 px-2 py-0.5 text-xs font-black text-white border border-white/20">
                                        {{ format_tanggal_pendek_id($nobar->kickoff_at) }} · {{ format_jam_id($nobar->kickoff_at) }}
                                    </span>
                                </div>
                            </div>

                            {{-- Isi Ditumpuk Satu Kolom: Judul 2 baris, Liga, Baris Terpisah Harga & Lokasi --}}
                            <div class="p-3.5 sm:p-4 flex flex-col flex-1 justify-between gap-2">
                                <div>
                                    <h3 class="font-bold text-sm sm:text-base text-text group-hover:text-amber-500 transition-colors line-clamp-2">
                                        {{ $nobar->judul }}
                                    </h3>
                                    <p class="text-xs text-text-muted mt-0.5">
                                        {{ $nobar->vsTitle() }} · <span class="font-semibold text-text">{{ $nobar->kompetisi }}</span>
                                    </p>
                                </div>

                                <div class="pt-2 border-t border-border flex items-center justify-between text-xs">
                                    <span class="text-text-muted">Studio 1</span>
                                    <span class="font-bold text-accent">
                                        {{ $nobar->is_free ? 'Gratis' : ($nobar->harga_info ?: 'Tiket Tersedia') }}
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

    </div>
@endsection
