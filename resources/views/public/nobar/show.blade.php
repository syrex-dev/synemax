@extends('layouts.public')

@section('title', $event->judul . ' · Nobar Sepak Bola SynemaX')

@section('meta')
    <meta property="og:type" content="event">
    <meta property="og:title" content="{{ $event->judul }} · Nobar SynemaX">
    <meta property="og:description" content="{{ $event->vsTitle() }} · {{ $event->kickoff_at->translatedFormat('l, d M Y · H:i') }} WIB di {{ $event->lokasi_nama }}. {{ $event->is_free ? 'Tiket Masuk: Gratis!' : $event->harga_info }}">
    <meta property="og:url" content="{{ route('nobar.show', ['slug' => $event->slug]) }}">
    @if ($event->poster_path)
        <meta property="og:image" content="{{ $event->posterUrl() }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $event->judul }}">
    <meta name="twitter:description" content="{{ $event->vsTitle() }} · {{ $event->kickoff_at->translatedFormat('l, d M Y · H:i') }} WIB di {{ $event->lokasi_nama }}">
    @if ($event->poster_path)
        <meta name="twitter:image" content="{{ $event->posterUrl() }}">
    @endif
@endsection

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 sm:py-12">
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
            <div role="alert" class="mb-6 rounded-2xl border border-warning/40 bg-warning/10 p-4 text-xs font-bold text-warning">
                ⚠️ Mode Pratinjau Admin: Acara ini berstatus draft dan belum terbit ke publik.
            </div>
        @endif

        {{-- Main Event Card --}}
        <article class="overflow-hidden rounded-3xl border border-border bg-surface shadow-token">
            {{-- Poster Header --}}
            @if ($event->poster_path)
                <div class="relative max-h-96 w-full overflow-hidden bg-neutral-900">
                    <img src="{{ $event->posterUrl() }}" alt="Poster {{ $event->judul }}" class="size-full max-h-96 object-contain">
                    <div class="absolute inset-0 bg-gradient-to-t from-surface via-transparent to-transparent"></div>
                </div>
            @endif

            <div class="p-6 sm:p-8">
                {{-- Header Status & Badges --}}
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

                <h1 class="mt-4 text-2xl font-black text-text sm:text-3xl lg:text-4xl leading-tight">
                    {{ $event->judul }}
                </h1>

                {{-- Status Alert (Ditunda / Dibatalkan) --}}
                @if ($event->status_acara !== 'aktif')
                    <div class="mt-4 rounded-2xl border border-danger/40 bg-danger/10 p-4 text-sm text-danger">
                        <p class="font-bold">Pemberitahuan Status Acara: {{ ucfirst($event->status_acara) }}</p>
                        @if ($event->alasan_status)
                            <p class="mt-1 text-xs leading-relaxed text-text">{{ $event->alasan_status }}</p>
                        @endif
                    </div>
                @endif

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
                            <span class="text-lg">📅</span>
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
                            <span class="text-lg">📍</span>
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
                    <a href="{{ $event->maps_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-12 flex-1 items-center justify-center gap-2 rounded-xl bg-accent px-5 text-xs font-bold text-accent-contrast shadow-xs hover:opacity-90 sm:flex-initial">
                        <span>Buka Google Maps</span>
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>

                    <a href="{{ route('nobar.calendar', ['slug' => $event->slug]) }}" class="inline-flex min-h-12 flex-1 items-center justify-center gap-2 rounded-xl border border-border bg-surface-2 px-5 text-xs font-bold text-text hover:border-accent sm:flex-initial">
                        <svg class="size-4 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                            <line x1="16" y1="2" x2="16" y2="6" />
                            <line x1="8" y1="2" x2="8" y2="6" />
                            <line x1="3" y1="10" x2="21" y2="10" />
                        </svg>
                        <span>Simpan ke Kalender (.ics)</span>
                    </a>

                    <a href="{{ $event->shareWhatsAppUrl() }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-12 flex-1 items-center justify-center gap-2 rounded-xl border border-success/40 bg-success/10 px-5 text-xs font-bold text-success hover:bg-success/20 sm:flex-initial">
                        <svg class="size-4 text-success" fill="currentColor" viewBox="0 0 24 24">
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
            </div>
        </article>

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
