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
        {{-- 2. INFO RINGKAS (Horizontal Single-Line Chip Teks Polos)      --}}
        {{-- ============================================================== --}}
        @if (count($infoChips) > 0)
            <section aria-label="Informasi Bioskop" class="relative -mx-4 px-4 sm:mx-0 sm:px-0">
                <div class="flex items-center gap-2 overflow-x-auto scrollbar-none py-1 text-[13px] font-bold text-text scroll-smooth" style="touch-action: pan-x pan-y pinch-zoom; -webkit-overflow-scrolling: touch; scrollbar-width: none; -ms-overflow-style: none;">
                    @foreach ($infoChips as $chip)
                        @if (!empty($chip['is_visible']))
                            <div class="inline-flex h-9 sm:h-10 shrink-0 items-center rounded-full border border-border bg-surface px-4 shadow-2xs whitespace-nowrap">
                                <span>{{ $chip['text'] }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Section Divider --}}
        <div class="border-t border-border"></div>

        {{-- ============================================================== --}}
        {{-- 3. KATALOG FILM (Carousel Horisontal Mobile ~2.3 Poster)       --}}
        {{-- ============================================================== --}}
        <section
            aria-label="Katalog Film"
            id="katalog-film"
            x-data="{
                genre: new URLSearchParams(window.location.search).get('genre') || '',
                tahun: new URLSearchParams(window.location.search).get('tahun') || '',
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
                canFadeLeft: false,
                canFadeRight: false,
                init() {
                    const scroller = this.$refs.filmCarousel;
                    if (scroller) {
                        const update = () => {
                            this.canFadeLeft = scroller.scrollLeft > 4;
                            this.canFadeRight = (scroller.scrollLeft + scroller.clientWidth) < (scroller.scrollWidth - 4);
                        };
                        scroller.addEventListener('scroll', update, { passive: true });
                        const ro = new ResizeObserver(() => update());
                        ro.observe(scroller);
                        this.$nextTick(() => update());
                    }
                },
                get filmMaskStyle() {
                    if (window.innerWidth >= 640) return '';
                    if (this.canFadeLeft && this.canFadeRight) {
                        return 'linear-gradient(to right, transparent 0, black 1rem, black calc(100% - 1rem), transparent 100%)';
                    } else if (this.canFadeLeft) {
                        return 'linear-gradient(to right, transparent 0, black 1rem, black 100%)';
                    } else if (this.canFadeRight) {
                        return 'linear-gradient(to right, black 0, black calc(100% - 1rem), transparent 100%)';
                    }
                    return '';
                },
                get filteredFilms() {
                    return this.allFilms.filter(f => {
                        const matchGenre = !this.genre || (f.genre && f.genre.toLowerCase().split(',').map(s => s.trim()).includes(this.genre.toLowerCase()));
                        const matchTahun = !this.tahun || String(f.release_year) === String(this.tahun);
                        return matchGenre && matchTahun;
                    });
                },
                setFilter(g, t) {
                    if (g !== undefined) this.genre = g;
                    if (t !== undefined) this.tahun = t;
                    const url = new URL(window.location);
                    if (this.genre) url.searchParams.set('genre', this.genre); else url.searchParams.delete('genre');
                    if (this.tahun) url.searchParams.set('tahun', this.tahun); else url.searchParams.delete('tahun');
                    window.history.replaceState({}, '', url);
                    this.$nextTick(() => {
                        const scroller = this.$refs.filmCarousel;
                        if (scroller) {
                            this.canFadeLeft = scroller.scrollLeft > 4;
                            this.canFadeRight = (scroller.scrollLeft + scroller.clientWidth) < (scroller.scrollWidth - 4);
                        }
                    });
                },
                resetFilter() {
                    this.setFilter('', '');
                }
            }"
            class="space-y-4 sm:space-y-6"
        >
            {{-- Header Section: Amber Accent Line + Title + Action in 1 Line --}}
            <div class="flex items-center justify-between gap-3 border-l-4 border-amber-500 pl-3 sm:pl-4">
                <div class="min-w-0">
                    <h2 class="text-xl sm:text-2xl font-black text-text tracking-tight">Katalog Film</h2>
                    <p class="mt-0.5 text-xs text-text-muted line-clamp-1 sm:line-clamp-none">Pilihan film on-demand berkualitas untuk diputar di studio</p>
                </div>
                <a href="{{ route('films.index') }}" class="shrink-0 whitespace-nowrap text-xs sm:text-sm font-bold text-amber-800 dark:text-amber-400 hover:underline">
                    <span class="sm:hidden">Semua &gt;</span>
                    <span class="hidden sm:inline">Lihat semua film &gt;</span>
                </a>
            </div>

            {{-- Reusable Film Filter Bar Component --}}
            <x-public.film-filter-bar :genres="$genres" :years="$years" />

            {{-- Mobile: Horizontal Carousel (~2.3 posters visible, bleed). Desktop: Grid --}}
            <div
                x-ref="filmCarousel"
                class="-mx-4 px-4 sm:mx-0 sm:px-0 flex sm:grid sm:grid-cols-3 md:grid-cols-4 gap-3 sm:gap-6 overflow-x-auto sm:overflow-visible scrollbar-none snap-x snap-mandatory"
                style="touch-action: pan-x pan-y pinch-zoom; -webkit-overflow-scrolling: touch; padding-inline: 1rem; scroll-padding-inline: 1rem;"
                :style="filmMaskStyle ? { maskImage: filmMaskStyle, WebkitMaskImage: filmMaskStyle } : {}"
            >
                <template x-for="film in filteredFilms" :key="film.id">
                    <a
                        :href="film.url"
                        class="w-[42%] sm:w-auto shrink-0 snap-start group relative flex flex-col rounded-xl sm:rounded-2xl overflow-hidden border border-border bg-surface shadow-2xs hover:border-amber-500 transition-colors"
                    >
                        {{-- Poster Image 2:3 with single Age Rating Badge --}}
                        <div class="relative w-full aspect-[2/3] overflow-hidden bg-surface-2">
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
                                <div class="flex size-full flex-col items-center justify-center p-3 text-center bg-surface-sunken">
                                    <span class="text-xs font-bold text-text-muted uppercase tracking-wider">SynemaX</span>
                                </div>
                            </template>

                            {{-- Single Clean Age Rating Badge in Corner --}}
                            <div class="absolute top-2 right-2 z-10">
                                <span class="inline-flex items-center rounded-md bg-black/80 px-1.5 py-0.5 text-[10px] font-black text-white border border-white/20" x-text="film.age_rating"></span>
                            </div>
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

            {{-- Empty State for Filter --}}
            <div x-show="filteredFilms.length === 0" x-cloak class="rounded-xl border border-border bg-surface p-8 text-center">
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
                    <a href="{{ route('booking.lookup.form') }}" class="shrink-0 whitespace-nowrap text-xs sm:text-sm font-bold text-amber-800 dark:text-amber-400 hover:underline">
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
                                href="{{ $sb['url'] ?? route('booking.lookup.form') }}"
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
