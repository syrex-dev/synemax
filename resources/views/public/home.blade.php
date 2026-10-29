@extends('layouts.public')

@section('title', setting('site_name', 'SynemaX') . ' – ' . setting('site_tagline', 'Studio Home Cinema Privat Desa'))
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
    <div class="mx-auto max-w-6xl px-4 py-4 sm:px-6 sm:py-6 space-y-12 sm:space-y-16">

        {{-- ============================================================== --}}
        {{-- 1. HERO CAROUSEL BANNER (Gambar Murni, Transisi Halus)       --}}
        {{-- ============================================================== --}}
        <section
            aria-label="Sorotan Utama Bioskop"
            x-data="{
                current: 0,
                total: {{ max(1, $heroBanners->count()) }},
                timer: null,
                interval: {{ (int) setting('home_hero_interval', 5) * 1000 }},
                autoplay: {{ setting('home_hero_autoplay', true) ? 'true' : 'false' }},
                isDragging: false,
                startX: 0,
                currentTranslate: 0,
                prevTranslate: 0,
                isHovered: false,
                init() {
                    if (this.autoplay && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        this.startAuto();
                    }
                    document.addEventListener('visibilitychange', () => {
                        if (document.hidden) {
                            this.stopAuto();
                        } else if (this.autoplay && !this.isHovered) {
                            this.startAuto();
                        }
                    });
                },
                startAuto() {
                    this.stopAuto();
                    this.timer = setInterval(() => {
                        if (!this.isHovered && !this.isDragging) {
                            this.next();
                        }
                    }, this.interval);
                },
                stopAuto() {
                    if (this.timer) clearInterval(this.timer);
                },
                next() {
                    this.goTo((this.current + 1) % this.total);
                },
                prev() {
                    this.goTo((this.current - 1 + this.total) % this.total);
                },
                goTo(index) {
                    this.current = index;
                    this.preloadNext();
                },
                preloadNext() {
                    const nextIdx = (this.current + 1) % this.total;
                    const nextImg = document.querySelector(`[data-hero-slide='${nextIdx}'] img`);
                    if (nextImg && nextImg.decode) {
                        nextImg.decode().catch(() => {});
                    }
                },
                onPointerDown(e) {
                    this.isDragging = true;
                    this.startX = e.clientX || (e.touches && e.touches[0].clientX);
                    this.stopAuto();
                },
                onPointerMove(e) {
                    if (!this.isDragging) return;
                    const currentX = e.clientX || (e.touches && e.touches[0].clientX);
                    const diff = currentX - this.startX;
                    this.currentTranslate = diff;
                },
                onPointerUp() {
                    if (!this.isDragging) return;
                    this.isDragging = false;
                    const threshold = 60;
                    if (this.currentTranslate < -threshold) {
                        this.next();
                    } else if (this.currentTranslate > threshold) {
                        this.prev();
                    }
                    this.currentTranslate = 0;
                    if (this.autoplay && !this.isHovered) {
                        this.startAuto();
                    }
                }
            }"
            @mouseenter="isHovered = true; stopAuto()"
            @mouseleave="isHovered = false; if (autoplay) startAuto()"
            @focusin="isHovered = true; stopAuto()"
            @focusout="isHovered = false; if (autoplay) startAuto()"
            @keydown.arrow-left.prevent="prev()"
            @keydown.arrow-right.prevent="next()"
            tabindex="0"
            class="relative overflow-hidden rounded-3xl border border-border dark:border-white/10 bg-surface dark:bg-[#07090E] shadow-2xl focus:outline-none focus:ring-2 focus:ring-amber-500"
        >
            {{-- Carousel Aspect Ratio Container (2,35:1 Desktop, 4:3 / Cover Mobile) --}}
            <div
                class="relative w-full aspect-[4/3] sm:aspect-[2.35/1] overflow-hidden select-none cursor-grab active:cursor-grabbing"
                @pointerdown="onPointerDown($event)"
                @pointermove="onPointerMove($event)"
                @pointerup="onPointerUp()"
                @pointercancel="onPointerUp()"
            >
                {{-- Sliding Track Animated with transform: translate3d --}}
                <div
                    class="flex h-full w-full"
                    :style="isDragging
                        ? `transform: translate3d(calc(-${current * 100}% + ${currentTranslate}px), 0, 0); transition: none; will-change: transform;`
                        : `transform: translate3d(-${current * 100}%, 0, 0); transition: transform 700ms cubic-bezier(0.22, 1, 0.36, 1); will-change: transform;`"
                >
                    @foreach ($heroBanners as $index => $banner)
                        <div
                            data-hero-slide="{{ $index }}"
                            class="relative h-full w-full shrink-0 overflow-hidden bg-slate-900"
                        >
                            @php
                                $targetUrl = $banner->resolvedUrl();
                            @endphp
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
                                        class="size-full object-cover object-center"
                                    >
                                </picture>
                            @else
                                {{-- Fallback Brand Hero Artwork --}}
                                <div class="flex size-full flex-col items-center justify-center bg-gradient-to-br from-slate-950 via-[#101827] to-[#1E1B10] p-6 text-center text-white">
                                    <x-brand.logo size="lg" />
                                    <p class="mt-3 max-w-md text-xs sm:text-sm font-medium text-amber-200/90 tracking-wide">
                                        {{ setting('site_tagline', 'Pengalaman Nonton Sinematik Layar Lebar & Audio Dolby') }}
                                    </p>
                                </div>
                            @endif

                            @if ($targetUrl)
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Navigation Arrows (Visible on non-touch desktop) --}}
            @if ($heroBanners->count() > 1)
                <button
                    type="button"
                    @click.stop="prev()"
                    class="absolute left-3 top-1/2 -translate-y-1/2 hidden sm:flex size-11 items-center justify-center rounded-full bg-black/60 text-white backdrop-blur-md border border-white/15 hover:bg-amber-500 hover:text-black transition-all shadow-lg"
                    aria-label="Slide Sebelumnya"
                >
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button
                    type="button"
                    @click.stop="next()"
                    class="absolute right-3 top-1/2 -translate-y-1/2 hidden sm:flex size-11 items-center justify-center rounded-full bg-black/60 text-white backdrop-blur-md border border-white/15 hover:bg-amber-500 hover:text-black transition-all shadow-lg"
                    aria-label="Slide Berikutnya"
                >
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                {{-- Smooth Elongated Dot Indicators --}}
                <div class="absolute bottom-4 inset-x-0 flex items-center justify-center gap-2 z-10 pointer-events-none">
                    @foreach ($heroBanners as $index => $banner)
                        <button
                            type="button"
                            @click="goTo({{ $index }})"
                            class="pointer-events-auto h-2 rounded-full transition-all duration-300 {{ $index === 0 ? 'w-7 bg-amber-400' : 'w-2 bg-white/40 hover:bg-white/70' }}"
                            :class="current === {{ $index }} ? 'w-7 bg-amber-400' : 'w-2 bg-white/40'"
                            aria-label="Ke Slide {{ $index + 1 }}"
                        ></button>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ============================================================== --}}
        {{-- 2. CHIP INFO KECIL (Dari Pengaturan)                           --}}
        {{-- ============================================================== --}}
        @if (count($infoChips) > 0)
            <section aria-label="Informasi Bioskop" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach ($infoChips as $chip)
                    @if (!empty($chip['is_visible']))
                        <div class="flex items-center gap-3 rounded-2xl border border-slate-300 dark:border-white/10 bg-surface dark:bg-[#0B101B] p-3.5 shadow-sm transition hover:border-amber-500/50">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                @if (($chip['icon'] ?? '') === 'chair')
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                                    </svg>
                                @elseif (($chip['icon'] ?? '') === 'tag')
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                @else
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                @endif
                            </span>
                            <span class="text-xs sm:text-sm font-bold text-text leading-snug">
                                {{ $chip['text'] }}
                            </span>
                        </div>
                    @endif
                @endforeach
            </section>
        @endif

        {{-- ============================================================== --}}
        {{-- 3. DAFTAR FILM (Katalog On-Demand Dengan Filter Realtime)      --}}
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
                },
                resetFilter() {
                    this.setFilter('', '');
                }
            }"
            class="space-y-6"
        >
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 border-l-4 border-amber-500 pl-3 sm:pl-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-text tracking-tight uppercase">Katalog Film</h2>
                    <p class="mt-0.5 text-xs text-text-muted">Pilih film favoritmu berdasarkan genre dan tahun rilis</p>
                </div>
                <a href="{{ route('films.index') }}" class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-accent-link hover:underline">
                    <span>Lihat semua film</span>
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>

            {{-- Reusable Film Filter Bar Component --}}
            <x-public.film-filter-bar :genres="$genres" :years="$years" />

            {{-- Film Grid (Poster 2:3 Ratio with Single Age Rating Badge) --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 sm:gap-6">
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
                            <template x-if="!film.poster_url">
                                <div class="flex size-full flex-col items-center justify-center p-4 text-center bg-gradient-to-br from-slate-900 via-amber-950/40 to-slate-950">
                                    <span class="text-xs font-black text-amber-400 uppercase tracking-wider line-clamp-2" x-text="film.title"></span>
                                    <span class="mt-2 text-[10px] text-text-muted">SynemaX Cinema</span>
                                </div>
                            </template>

                            {{-- Single Clean Age Rating Badge in Corner --}}
                            <div class="absolute top-2.5 right-2.5 z-10">
                                <span class="inline-flex items-center rounded-md bg-black/75 backdrop-blur-md px-2 py-0.5 text-[10px] font-black text-white border border-white/20 shadow-sm" x-text="film.age_rating"></span>
                            </div>
                        </div>

                        {{-- Metadata Below Poster --}}
                        <div class="p-3 sm:p-4 flex flex-col flex-1 justify-between">
                            <div>
                                <h3 class="font-bold text-sm sm:text-base text-text group-hover:text-amber-500 transition-colors line-clamp-1" x-text="film.title"></h3>
                                <p class="mt-1 text-xs text-text-muted truncate" x-text="film.genre"></p>
                            </div>
                            <div class="mt-3 flex items-center justify-between text-[11px] text-text-muted border-t border-border/60 pt-2 font-medium">
                                <span x-text="film.release_year || '-'"></span>
                                <span x-text="`${film.duration_minutes} mnt`"></span>
                            </div>
                        </div>
                    </a>
                </template>
            </div>

            {{-- Empty State for Filter --}}
            <div x-show="filteredFilms.length === 0" x-cloak class="rounded-2xl border border-border bg-surface p-12 text-center">
                <p class="text-sm font-bold text-text">Belum ada film untuk filter ini</p>
                <p class="mt-1 text-xs text-text-muted">Coba ubah pilihan genre atau tahun rilis Anda.</p>
                <button type="button" @click="resetFilter()" class="mt-4 inline-flex min-h-[38px] items-center rounded-xl bg-amber-500 px-4 py-1.5 text-xs font-bold text-slate-950 hover:bg-amber-400 shadow-xs">
                    Reset Filter
                </button>
            </div>
        </section>

        {{-- Divider Dotted --}}
        <div class="border-t border-dashed border-slate-300 dark:border-white/10 my-8 sm:my-12"></div>

        {{-- ============================================================== --}}
        {{-- 4. PROMO & PENAWARAN (CGV Partner Deal Style Carousel)        --}}
        {{-- ============================================================== --}}
        @if ($promos->count() > 0)
            <section
                aria-label="Promo dan Penawaran"
                class="space-y-6"
                x-data="{
                    current: 0,
                    total: {{ $promos->count() }},
                    isDragging: false,
                    startX: 0,
                    currentTranslate: 0,
                    hasMoved: false,
                    isTransitioning: false,
                    get stepPercent() {
                        return window.innerWidth < 640 ? 88 : 72;
                    },
                    get gapPx() {
                        return window.innerWidth < 640 ? 16 : 24;
                    },
                    prev() {
                        if (this.current > 0) {
                            this.goTo(this.current - 1);
                        }
                    },
                    next() {
                        if (this.current < this.total - 1) {
                            this.goTo(this.current + 1);
                        }
                    },
                    goTo(idx) {
                        this.current = idx;
                    },
                    onPointerDown(e) {
                        if (this.total <= 1) return;
                        this.isDragging = true;
                        this.hasMoved = false;
                        this.startX = e.clientX || (e.touches && e.touches[0].clientX);
                    },
                    onPointerMove(e) {
                        if (!this.isDragging) return;
                        const clientX = e.clientX || (e.touches && e.touches[0].clientX);
                        const diff = clientX - this.startX;
                        if (Math.abs(diff) > 5) {
                            this.hasMoved = true;
                        }
                        this.currentTranslate = diff;
                    },
                    onPointerUp() {
                        if (!this.isDragging) return;
                        this.isDragging = false;
                        const threshold = 50;
                        if (this.currentTranslate < -threshold && this.current < this.total - 1) {
                            this.next();
                        } else if (this.currentTranslate > threshold && this.current > 0) {
                            this.prev();
                        }
                        this.currentTranslate = 0;
                    }
                }"
            >
                {{-- CGV Style Section Header --}}
                <div class="flex items-center justify-between border-l-4 border-amber-500 pl-3 sm:pl-4">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-text tracking-tight uppercase">Promo & Penawaran</h2>
                        <p class="mt-0.5 text-xs text-text-muted">Penawaran hemat dan paket spesial menonton di SynemaX</p>
                    </div>
                    <a href="{{ route('promos.index') }}" class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-accent-link hover:underline">
                        <span>Lihat semua</span>
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>

                {{-- Carousel Viewport with Peek (72% desktop, 88% mobile) --}}
                <div class="relative overflow-hidden">
                    <div
                        class="select-none cursor-grab active:cursor-grabbing"
                        @pointerdown="onPointerDown($event)"
                        @pointermove="onPointerMove($event)"
                        @pointerup="onPointerUp()"
                        @pointercancel="onPointerUp()"
                    >
                        <div
                            class="flex gap-4 sm:gap-6"
                            :style="isDragging
                                ? `transform: translate3d(calc(-${current} * (${stepPercent}% + ${gapPx}px) + ${currentTranslate}px), 0, 0); transition: none; will-change: transform;`
                                : `transform: translate3d(calc(-${current} * (${stepPercent}% + ${gapPx}px)), 0, 0); transition: transform 650ms cubic-bezier(0.22, 1, 0.36, 1); will-change: transform;`"
                        >
                            @foreach ($promos as $promo)
                                <div
                                    class="shrink-0 w-[88%] sm:w-[72%] transition-transform duration-200"
                                >
                                    <a
                                        href="{{ route('promos.show', $promo->slug) }}"
                                        @click="if (hasMoved) { $event.preventDefault(); hasMoved = false; }"
                                        class="group relative block w-full aspect-[2/1] overflow-hidden rounded-2xl sm:rounded-3xl border border-slate-300 dark:border-white/10 bg-surface shadow-sm hover:shadow-xl hover:border-amber-500 hover:-translate-y-0.5 sm:hover:-translate-y-1 transition-all duration-200 focus-visible:outline-2 focus-visible:outline-amber-500"
                                    >
                                        {{-- Pure Image 2:1 --}}
                                        @if ($promo->imageUrl())
                                            <img
                                                src="{{ $promo->imageUrl() }}"
                                                alt="{{ $promo->title }}"
                                                loading="lazy"
                                                class="size-full object-cover transition-transform duration-300 group-hover:scale-102"
                                            >
                                        @else
                                            <div class="flex size-full flex-col items-center justify-center p-4 text-center bg-gradient-to-br from-amber-950 via-slate-900 to-slate-950">
                                                <span class="text-sm sm:text-base font-bold text-amber-400 uppercase tracking-wider">{{ $promo->title }}</span>
                                            </div>
                                        @endif
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Semi-transparent Circular Arrows (Desktop Only, Pinned Vertically Centered Inside Container) --}}
                    @if ($promos->count() > 1)
                        <button
                            type="button"
                            x-show="current > 0"
                            x-cloak
                            @click="prev()"
                            class="hidden sm:flex absolute left-4 top-1/2 -translate-y-1/2 size-11 items-center justify-center rounded-full bg-black/60 hover:bg-black/85 text-white backdrop-blur-md border border-white/20 transition-all shadow-xl z-20 focus-visible:outline-2 focus-visible:outline-amber-500"
                            aria-label="Geser promo ke kiri"
                        >
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            x-show="current < total - 1"
                            x-cloak
                            @click="next()"
                            class="hidden sm:flex absolute right-4 top-1/2 -translate-y-1/2 size-11 items-center justify-center rounded-full bg-black/60 hover:bg-black/85 text-white backdrop-blur-md border border-white/20 transition-all shadow-xl z-20 focus-visible:outline-2 focus-visible:outline-amber-500"
                            aria-label="Geser promo ke kanan"
                        >
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>

                        {{-- Smooth Elongated Dots Indicator --}}
                        <div class="mt-4 flex items-center justify-center gap-2">
                            @foreach ($promos as $index => $promo)
                                <button
                                    type="button"
                                    @click="goTo({{ $index }})"
                                    class="h-2 rounded-full transition-all duration-300 focus-visible:outline-none"
                                    :class="current === {{ $index }} ? 'w-8 bg-amber-500' : 'w-2.5 bg-slate-400 dark:bg-white/25 hover:bg-amber-400/50'"
                                    aria-label="Lihat Promo {{ $index + 1 }}"
                                ></button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        @endif

        {{-- Divider Dotted --}}
        <div class="border-t border-dashed border-slate-300 dark:border-white/10 my-8 sm:my-12"></div>

        {{-- ============================================================== --}}
        {{-- 5. BLOK MAKANAN & MINUMAN (Kartu Gambar Besar 1200x600)       --}}
        {{-- ============================================================== --}}
        @if (count($snackBanners ?? []) > 0)
            <section aria-label="Makanan dan Minuman" class="space-y-6">
                <div class="flex items-center justify-between border-l-4 border-amber-500 pl-3 sm:pl-4">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-text tracking-tight uppercase">Makanan & Minuman</h2>
                        <p class="mt-0.5 text-xs text-text-muted">Popcorn hangat, minuman segar, dan camilan lezat menemani nontonmu</p>
                    </div>
                    <a href="{{ route('booking.lookup.form') }}" class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-accent-link hover:underline">
                        <span>Pesan camilan</span>
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    @foreach ($snackBanners as $sb)
                        <a
                            href="{{ $sb['url'] ?? route('booking.lookup.form') }}"
                            class="group relative block aspect-[2/1] w-full overflow-hidden rounded-2xl sm:rounded-3xl border border-slate-300 dark:border-white/10 bg-surface shadow-sm hover:shadow-xl hover:border-amber-500 hover:-translate-y-0.5 sm:hover:-translate-y-1 transition-all duration-200 focus-visible:outline-2 focus-visible:outline-amber-500"
                            title="{{ $sb['title'] ?? 'Makanan & Minuman SynemaX' }}"
                        >
                            @php
                                $sbUrl = !empty($sb['image_path']) ? (str_starts_with($sb['image_path'], 'http') ? $sb['image_path'] : Storage::disk('public')->url($sb['image_path'])) : null;
                            @endphp
                            @if ($sbUrl)
                                <img
                                    src="{{ $sbUrl }}"
                                    alt="{{ $sb['title'] ?? 'Makanan & Minuman' }}"
                                    loading="lazy"
                                    class="size-full object-cover transition-transform duration-300 group-hover:scale-102"
                                >
                            @else
                                <div class="flex size-full flex-col items-center justify-center p-4 text-center bg-gradient-to-br from-amber-950 via-slate-900 to-slate-950">
                                    <span class="text-sm font-bold text-amber-400 uppercase tracking-wider">{{ $sb['title'] ?? 'Makanan & Minuman' }}</span>
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Divider Dotted --}}
        <div class="border-t border-dashed border-slate-300 dark:border-white/10 my-8 sm:my-12"></div>

        {{-- ============================================================== --}}
        {{-- 6. NOBAR MENDATANG (Format Grid 2x2, Gambar Murni + Badge)    --}}
        {{-- ============================================================== --}}
        @if ($infoNobarEnabled && $upcomingNobar->count() > 0)
            <section aria-label="Nonton Bareng Mendatang" class="space-y-6">
                <div class="flex items-center justify-between border-l-4 border-amber-500 pl-3 sm:pl-4">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-text tracking-tight uppercase">Nobar Mendatang</h2>
                        <p class="mt-0.5 text-xs text-text-muted">Siaran langsung pertandingan seru komunitas di layar bioskop</p>
                    </div>
                    <a href="{{ route('nobar.index') }}" class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-accent-link hover:underline">
                        <span>Semua acara</span>
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>

                {{-- Grid 2 Kolom x 2 Baris --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    @foreach ($upcomingNobar as $nobar)
                        <a
                            href="{{ route('nobar.show', $nobar->slug) }}"
                            class="group relative flex flex-col rounded-2xl sm:rounded-3xl overflow-hidden border border-slate-300 dark:border-white/10 bg-surface dark:bg-[#0B101B] shadow-sm hover:shadow-xl hover:border-amber-500 hover:-translate-y-0.5 sm:hover:-translate-y-1 transition-all duration-200 focus-visible:outline-2 focus-visible:outline-amber-500"
                        >
                            {{-- Banner Gambar 2:1 --}}
                            <div class="relative w-full aspect-[2/1] overflow-hidden bg-slate-900">
                                @if ($nobar->hasPoster())
                                    <img
                                        src="{{ $nobar->posterUrl() }}"
                                        alt="{{ $nobar->posterAltText() }}"
                                        loading="lazy"
                                        class="size-full object-cover group-hover:scale-102 transition-transform duration-300"
                                    >
                                @else
                                    <div class="flex size-full flex-col items-center justify-center p-4 text-center bg-gradient-to-br from-slate-950 via-slate-900 to-[#101E17]">
                                        <span class="text-xs font-bold text-amber-400 uppercase tracking-wider">{{ $nobar->judul }}</span>
                                    </div>
                                @endif

                                {{-- Kickoff Date Badge Overlay --}}
                                <div class="absolute top-3 left-3 z-10 flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1 rounded-lg bg-black/80 backdrop-blur-md px-2.5 py-1 text-xs font-black text-amber-400 border border-white/20 shadow-md">
                                        {{ format_tanggal_pendek_id($nobar->kickoff_at) }} – {{ format_jam_id($nobar->kickoff_at) }}
                                    </span>
                                </div>
                            </div>

                            <div class="p-4 flex items-center justify-between">
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-bold text-sm sm:text-base text-text group-hover:text-amber-500 transition-colors truncate">
                                        {{ $nobar->judul }}
                                    </h3>
                                    <p class="text-xs text-text-muted mt-0.5">{{ $nobar->vsTitle() }} – <span class="font-semibold text-amber-600 dark:text-amber-400">{{ $nobar->kompetisi }}</span></p>
                                </div>
                                <span class="shrink-0 text-xs font-bold text-emerald-500 ml-3">
                                    {{ $nobar->is_free ? 'Gratis' : ($nobar->harga_info ?: 'Tiket Tersedia') }}
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

    </div>
@endsection
