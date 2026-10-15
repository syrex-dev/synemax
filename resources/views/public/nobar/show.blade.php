@extends('layouts.public')

@section('title', $event->judul . ' · Nobar Sepak Bola SynemaX')

@section('meta')
    <meta property="og:type" content="event">
    <meta property="og:title" content="{{ $event->judul }} · Nobar SynemaX">
    <meta property="og:description" content="{{ $event->vsTitle() }} · {{ $event->kickoff_at->translatedFormat('l, d M Y · H:i') }} WIB di {{ $event->lokasi_nama }}. {{ $event->is_free ? 'Tiket Masuk: Gratis' : $event->harga_info }}">
    <meta property="og:url" content="{{ route('nobar.show', ['slug' => $event->slug]) }}">
    <meta property="og:image" content="{{ $event->posterOgUrl() }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $event->judul }}">
    <meta name="twitter:description" content="{{ $event->vsTitle() }} · {{ $event->kickoff_at->translatedFormat('l, d M Y · H:i') }} WIB di {{ $event->lokasi_nama }}">
    <meta name="twitter:image" content="{{ $event->posterOgUrl() }}">
@endsection

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12"
        x-data="{
            zoomOpen: false,
            triggerEl: null,
            openModal(el) {
                this.triggerEl = el;
                this.zoomOpen = true;
                document.body.classList.add('overflow-hidden');
                this.$nextTick(() => {
                    this.$refs.closeModalBtn?.focus();
                });
            },
            closeModal() {
                this.zoomOpen = false;
                document.body.classList.remove('overflow-hidden');
                if (this.triggerEl) {
                    this.triggerEl.focus();
                }
            },
            handleTab(event) {
                if (!this.zoomOpen) return;
                const focusables = Array.from(this.$refs.modalDialog?.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex=\'-1\'])') || []);
                if (focusables.length === 0) return;
                const first = focusables[0];
                const last = focusables[focusables.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        }"
        @keydown.escape.window="if (zoomOpen) closeModal()">

        {{-- Breadcrumb --}}
        <nav aria-label="Breadcrumb" class="mb-6 flex items-center gap-2 text-xs text-text-muted">
            <a href="{{ route('home') }}" class="hover:text-text">Beranda</a>
            <span>/</span>
            <a href="{{ route('nobar.index') }}" class="hover:text-text">Nobar</a>
            <span>/</span>
            <span class="truncate font-semibold text-text">{{ $event->judul }}</span>
        </nav>

        {{-- Preview Banner for Admin --}}
        @if ($isPreview)
            <div role="alert" class="mb-6 flex items-center gap-3 rounded-2xl border border-warning/40 bg-warning/10 p-4 text-xs font-bold text-warning">
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>Mode Pratinjau Admin: Acara ini berstatus draft dan belum terbit ke publik.</span>
            </div>
        @endif

        {{-- Status Alert (Ditunda / Dibatalkan) --}}
        @if ($event->status_acara !== 'aktif')
            <div class="mb-6 rounded-2xl border border-danger/40 bg-danger/10 p-4 text-sm text-danger">
                <p class="font-bold">Pemberitahuan Status Acara: {{ ucfirst($event->status_acara) }}</p>
                @if ($event->alasan_status)
                    <p class="mt-1 text-xs leading-relaxed text-text">{{ $event->alasan_status }}</p>
                @endif
            </div>
        @endif

        @if ($event->hasPoster())
            {{-- 2-Column Responsive Layout with Sticky Poster --}}
            <div class="lg:grid lg:grid-cols-[380px,1fr] lg:gap-8 lg:items-start">
                {{-- Mobile Title & Badges Header (Visible only on < lg) --}}
                <div class="block lg:hidden mb-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-3 py-1 text-xs font-bold shadow-xs {{ $event->badgeClasses() }}">
                                {{ $event->display_status }}
                            </span>
                            @if ($event->kompetisi)
                                <span class="rounded-full border border-border bg-surface-2 px-3 py-1 text-xs font-bold text-accent">
                                    {{ $event->kompetisi }}
                                </span>
                            @endif
                        </div>

                        @if ($event->is_free)
                            <span class="rounded-full bg-success/15 px-3 py-1 text-xs font-black uppercase text-success border border-success/30">
                                Tiket Masuk: Gratis
                            </span>
                        @elseif ($event->harga_info)
                            <span class="rounded-full bg-accent/10 px-3 py-1 text-xs font-bold text-accent border border-accent/20">
                                {{ $event->harga_info }}
                            </span>
                        @endif
                    </div>

                    <h1 class="mt-4 text-2xl font-black text-text sm:text-3xl leading-tight">
                        {{ $event->judul }}
                    </h1>
                </div>

                {{-- Left Column: Sticky Poster Card --}}
                <aside class="w-full lg:sticky lg:top-24 max-h-[calc(100vh-7rem)] overflow-y-auto">
                    <div class="overflow-hidden rounded-3xl border border-border bg-surface-2/60 p-3 sm:p-4 shadow-token">
                        <div class="group relative overflow-hidden rounded-2xl border border-border bg-surface-2 flex items-center justify-center">
                            <img src="{{ $event->posterUrl() }}"
                                 alt="{{ $event->posterAltText() }}"
                                 style="{{ $event->aspectRatioStyle() }}"
                                 fetchpriority="high"
                                 class="w-full max-h-[70vh] lg:max-h-none object-contain transition duration-300 group-hover:scale-[1.01]">

                            {{-- Overlay Button to Zoom --}}
                            <button type="button"
                                    @click="openModal($el)"
                                    class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition group-hover:opacity-100 focus:opacity-100 focus:outline-none focus:ring-2 focus:ring-accent"
                                    aria-label="Perbesar poster {{ $event->judul }}">
                                <span class="inline-flex items-center gap-2 rounded-xl bg-surface/90 px-3.5 py-2 text-xs font-bold text-text shadow-lg backdrop-blur-sm">
                                    <svg class="size-4 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                                    </svg>
                                    <span>Perbesar Poster</span>
                                </span>
                            </button>
                        </div>

                        {{-- Poster Actions --}}
                        <div class="mt-3 flex items-center gap-2">
                            <button type="button"
                                    @click="openModal($el)"
                                    class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-xl border border-border bg-surface px-3 text-xs font-bold text-text transition hover:border-accent hover:text-accent">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                                </svg>
                                <span>Perbesar</span>
                            </button>
                            <a href="{{ $event->posterUrl() }}"
                               download="{{ $event->posterDownloadFilename() }}"
                               class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-xl border border-border bg-surface px-3 text-xs font-bold text-text transition hover:border-accent hover:text-accent"
                               title="Unduh file poster">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span>Simpan Poster</span>
                            </a>
                        </div>
                    </div>
                </aside>

                {{-- Right Column: Event Details Card --}}
                <article class="mt-6 lg:mt-0 min-w-0 overflow-hidden rounded-3xl border border-border bg-surface p-6 sm:p-8 shadow-token">
                    {{-- Desktop Title & Badges Header (Hidden on mobile) --}}
                    <div class="hidden lg:block mb-6">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-3 py-1 text-xs font-bold shadow-xs {{ $event->badgeClasses() }}">
                                    {{ $event->display_status }}
                                </span>
                                @if ($event->kompetisi)
                                    <span class="rounded-full border border-border bg-surface-2 px-3 py-1 text-xs font-bold text-accent">
                                        {{ $event->kompetisi }}
                                    </span>
                                @endif
                            </div>

                            @if ($event->is_free)
                                <span class="rounded-full bg-success/15 px-3 py-1 text-xs font-black uppercase text-success border border-success/30">
                                    Tiket Masuk: Gratis
                                </span>
                            @elseif ($event->harga_info)
                                <span class="rounded-full bg-accent/10 px-3 py-1 text-xs font-bold text-accent border border-accent/20">
                                    {{ $event->harga_info }}
                                </span>
                            @endif
                        </div>

                        <h1 class="mt-4 text-3xl font-black text-text lg:text-4xl leading-tight">
                            {{ $event->judul }}
                        </h1>
                    </div>

                    {{-- Versus Card --}}
                    <div class="rounded-2xl border border-border bg-surface-2 p-5 text-center">
                        <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Pertandingan</p>
                        <div class="mt-2 flex flex-wrap items-center justify-center gap-4 sm:gap-8">
                            <div class="flex-1 text-center sm:text-right">
                                <span class="text-lg font-black text-text sm:text-2xl">{{ $event->tim_a }}</span>
                            </div>
                            <span class="rounded-full bg-accent px-3 py-1 font-mono text-xs font-black text-accent-contrast">VS</span>
                            <div class="flex-1 text-center sm:text-left">
                                <span class="text-lg font-black text-text sm:text-2xl">{{ $event->tim_b ?: 'TBA' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Event Schedule & Location Grid --}}
                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-border bg-surface-2 p-4">
                            <div class="flex items-center gap-2.5 text-accent">
                                <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="text-xs font-bold uppercase tracking-wider">Waktu Kickoff</span>
                            </div>
                            <p class="mt-2 text-base font-black text-text">
                                {{ $event->kickoff_at->translatedFormat('l, d F Y') }}
                            </p>
                            <p class="text-sm font-bold text-accent">
                                {{ $event->kickoff_at->format('H:i') }} WIB (Estimasi {{ $event->durasi_menit }} Menit)
                            </p>
                        </div>

                        <div class="rounded-2xl border border-border bg-surface-2 p-4">
                            <div class="flex items-center gap-2.5 text-accent">
                                <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="text-xs font-bold uppercase tracking-wider">Lokasi Nobar</span>
                            </div>
                            <p class="mt-2 text-base font-black text-text">
                                {{ $event->lokasi_nama }}
                            </p>
                            <p class="text-xs text-text-muted">
                                {{ $event->alamat_patokan }}
                            </p>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ $event->maps_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-12 flex-1 items-center justify-center gap-2 rounded-xl bg-accent px-5 text-xs font-bold text-accent-contrast shadow-xs hover:opacity-90 whitespace-nowrap sm:flex-initial">
                            <span>Buka Google Maps</span>
                            <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>

                        <a href="{{ route('nobar.calendar', ['slug' => $event->slug]) }}" class="inline-flex min-h-12 flex-1 items-center justify-center gap-2 rounded-xl border border-border bg-surface-2 px-5 text-xs font-bold text-text hover:border-accent whitespace-nowrap sm:flex-initial">
                            <svg class="size-4 shrink-0 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                <line x1="16" y1="2" x2="16" y2="6" />
                                <line x1="8" y1="2" x2="8" y2="6" />
                                <line x1="3" y1="10" x2="21" y2="10" />
                            </svg>
                            <span>Simpan ke Kalender (.ics)</span>
                        </a>

                        <a href="{{ $event->shareWhatsAppUrl() }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-12 flex-1 items-center justify-center gap-2 rounded-xl border border-success/40 bg-success/10 px-5 text-xs font-bold text-success hover:bg-success/20 whitespace-nowrap sm:flex-initial">
                            <svg class="size-4 shrink-0 text-success" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2zm.01 1.67c2.2 0 4.26.86 5.82 2.42a8.17 8.17 0 012.41 5.82c0 4.54-3.7 8.24-8.24 8.24-1.45 0-2.88-.38-4.13-1.12l-.3-.18-3.08.81.82-3-.19-.31a8.16 8.16 0 01-1.25-4.44c0-4.54 3.7-8.24 8.24-8.24zm4.52 11.66c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.98-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.25-1.5-1.4-1.75-.15-.25-.02-.39.11-.51.11-.11.25-.29.38-.44.12-.14.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.35-.77-1.85-.2-.49-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1s.9 2.43 1.03 2.6c.13.17 1.77 2.7 4.28 3.79.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.15-1.18-.06-.1-.23-.17-.48-.29z"/>
                            </svg>
                            <span>Ajak Teman via WhatsApp</span>
                        </a>
                    </div>

                    {{-- Catatan / Fasilitas Acara (Markdown) --}}
                    @if ($event->catatan)
                        <div class="mt-8 border-t border-border pt-6">
                            <h2 class="text-base font-black text-text">Catatan & Informasi Tambahan</h2>
                            <div class="prose prose-sm dark:prose-invert mt-3 max-w-none text-text-muted leading-relaxed">
                                {!! $event->catatanHtml() !!}
                            </div>
                        </div>
                    @endif
                </article>
            </div>
        @else
            {{-- Clean 1-Column Layout When Event Has No Poster --}}
            <article class="max-w-4xl mx-auto overflow-hidden rounded-3xl border border-border bg-surface p-6 sm:p-8 shadow-token">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full px-3 py-1 text-xs font-bold shadow-xs {{ $event->badgeClasses() }}">
                            {{ $event->display_status }}
                        </span>
                        @if ($event->kompetisi)
                            <span class="rounded-full border border-border bg-surface-2 px-3 py-1 text-xs font-bold text-accent">
                                {{ $event->kompetisi }}
                            </span>
                        @endif
                    </div>

                    @if ($event->is_free)
                        <span class="rounded-full bg-success/15 px-3 py-1 text-xs font-black uppercase text-success border border-success/30">
                            Tiket Masuk: Gratis
                        </span>
                    @elseif ($event->harga_info)
                        <span class="rounded-full bg-accent/10 px-3 py-1 text-xs font-bold text-accent border border-accent/20">
                            {{ $event->harga_info }}
                        </span>
                    @endif
                </div>

                <h1 class="mt-4 text-3xl font-black text-text sm:text-4xl leading-tight">
                    {{ $event->judul }}
                </h1>

                {{-- Versus Card --}}
                <div class="mt-6 rounded-2xl border border-border bg-surface-2 p-5 text-center">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Pertandingan</p>
                    <div class="mt-2 flex flex-wrap items-center justify-center gap-4 sm:gap-8">
                        <div class="flex-1 text-center sm:text-right">
                            <span class="text-lg font-black text-text sm:text-2xl">{{ $event->tim_a }}</span>
                        </div>
                        <span class="rounded-full bg-accent px-3 py-1 font-mono text-xs font-black text-accent-contrast">VS</span>
                        <div class="flex-1 text-center sm:text-left">
                            <span class="text-lg font-black text-text sm:text-2xl">{{ $event->tim_b ?: 'TBA' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Event Schedule & Location Grid --}}
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-border bg-surface-2 p-4">
                        <div class="flex items-center gap-2.5 text-accent">
                            <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-xs font-bold uppercase tracking-wider">Waktu Kickoff</span>
                        </div>
                        <p class="mt-2 text-base font-black text-text">
                            {{ $event->kickoff_at->translatedFormat('l, d F Y') }}
                        </p>
                        <p class="text-sm font-bold text-accent">
                            {{ $event->kickoff_at->format('H:i') }} WIB (Estimasi {{ $event->durasi_menit }} Menit)
                        </p>
                    </div>

                    <div class="rounded-2xl border border-border bg-surface-2 p-4">
                        <div class="flex items-center gap-2.5 text-accent">
                            <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="text-xs font-bold uppercase tracking-wider">Lokasi Nobar</span>
                        </div>
                        <p class="mt-2 text-base font-black text-text">
                            {{ $event->lokasi_nama }}
                        </p>
                        <p class="text-xs text-text-muted">
                            {{ $event->alamat_patokan }}
                        </p>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ $event->maps_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-12 flex-1 items-center justify-center gap-2 rounded-xl bg-accent px-5 text-xs font-bold text-accent-contrast shadow-xs hover:opacity-90 whitespace-nowrap sm:flex-initial">
                        <span>Buka Google Maps</span>
                        <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>

                    <a href="{{ route('nobar.calendar', ['slug' => $event->slug]) }}" class="inline-flex min-h-12 flex-1 items-center justify-center gap-2 rounded-xl border border-border bg-surface-2 px-5 text-xs font-bold text-text hover:border-accent whitespace-nowrap sm:flex-initial">
                        <svg class="size-4 shrink-0 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                            <line x1="16" y1="2" x2="16" y2="6" />
                            <line x1="8" y1="2" x2="8" y2="6" />
                            <line x1="3" y1="10" x2="21" y2="10" />
                        </svg>
                        <span>Simpan ke Kalender (.ics)</span>
                    </a>

                    <a href="{{ $event->shareWhatsAppUrl() }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-12 flex-1 items-center justify-center gap-2 rounded-xl border border-success/40 bg-success/10 px-5 text-xs font-bold text-success hover:bg-success/20 whitespace-nowrap sm:flex-initial">
                        <svg class="size-4 shrink-0 text-success" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2zm.01 1.67c2.2 0 4.26.86 5.82 2.42a8.17 8.17 0 012.41 5.82c0 4.54-3.7 8.24-8.24 8.24-1.45 0-2.88-.38-4.13-1.12l-.3-.18-3.08.81.82-3-.19-.31a8.16 8.16 0 01-1.25-4.44c0-4.54 3.7-8.24 8.24-8.24zm4.52 11.66c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.98-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.25-1.5-1.4-1.75-.15-.25-.02-.39.11-.51.11-.11.25-.29.38-.44.12-.14.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.35-.77-1.85-.2-.49-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1s.9 2.43 1.03 2.6c.13.17 1.77 2.7 4.28 3.79.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.15-1.18-.06-.1-.23-.17-.48-.29z"/>
                        </svg>
                        <span>Ajak Teman via WhatsApp</span>
                    </a>
                </div>

                {{-- Catatan / Fasilitas Acara (Markdown) --}}
                @if ($event->catatan)
                    <div class="mt-8 border-t border-border pt-6">
                        <h2 class="text-base font-black text-text">Catatan & Informasi Tambahan</h2>
                        <div class="prose prose-sm dark:prose-invert mt-3 max-w-none text-text-muted leading-relaxed">
                            {!! $event->catatanHtml() !!}
                        </div>
                    </div>
                @endif
            </article>
        @endif

        {{-- Modal Perbesar Poster --}}
        @if ($event->hasPoster())
            <div x-show="zoomOpen"
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
                 @click.self="closeModal()"
                 @keydown="handleTab($event)"
                 role="dialog"
                 aria-modal="true"
                 aria-label="Pratinjau Poster {{ $event->judul }}">
                <div x-ref="modalDialog"
                     class="relative flex max-h-[92vh] max-w-3xl w-full flex-col items-center rounded-3xl border border-border bg-surface p-4 sm:p-6 shadow-2xl">
                    {{-- Header Modal --}}
                    <div class="flex w-full items-center justify-between border-b border-border pb-3">
                        <h3 class="truncate text-sm font-bold text-text">Poster Acara</h3>
                        <button type="button"
                                x-ref="closeModalBtn"
                                @click="closeModal()"
                                class="inline-flex size-9 items-center justify-center rounded-xl border border-border text-text hover:bg-surface-2 focus:outline-none focus:ring-2 focus:ring-accent"
                                aria-label="Tutup pratinjau poster">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Poster Image in Modal --}}
                    <div class="my-4 flex flex-1 items-center justify-center overflow-auto w-full">
                        <img src="{{ $event->posterUrl() }}"
                             alt="{{ $event->posterAltText() }}"
                             class="max-h-[68vh] w-auto max-w-full rounded-xl object-contain shadow-md">
                    </div>

                    {{-- Footer Modal --}}
                    <div class="flex w-full items-center justify-between border-t border-border pt-3">
                        <span class="text-xs text-text-muted">
                            {{ $event->poster_width && $event->poster_height ? $event->poster_width.' × '.$event->poster_height.' px' : 'Rasio potret' }}
                        </span>
                        <a href="{{ $event->posterUrl() }}"
                           download="{{ $event->posterDownloadFilename() }}"
                           class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-accent px-4 text-xs font-bold text-accent-contrast shadow-sm hover:opacity-90">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>Simpan Poster</span>
                        </a>
                    </div>
                </div>
            </div>
        @endif

        {{-- Other Events Recommendation --}}
        @if ($otherEvents->isNotEmpty())
            <div class="mt-12 border-t border-border pt-8">
                <h3 class="text-xl font-black text-text">Jadwal Nobar Lainnya</h3>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    @foreach ($otherEvents as $other)
                        <a href="{{ route('nobar.show', ['slug' => $other->slug]) }}" class="group rounded-2xl border border-border bg-surface p-4 transition hover:border-accent">
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $other->badgeClasses() }}">{{ $other->display_status }}</span>
                            <h4 class="mt-2 text-sm font-bold text-text group-hover:text-accent line-clamp-1">{{ $other->judul }}</h4>
                            <p class="mt-1 text-xs text-text-muted">{{ $other->kickoff_at->translatedFormat('d M · H:i') }} WIB</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
