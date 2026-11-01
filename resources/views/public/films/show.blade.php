@extends('layouts.public')

@section('title', $film->title . ' - ' . setting('site_name', 'SynemaX'))

@section('meta')
    <meta name="description" content="{{ Str::limit(strip_tags($film->synopsis ?? 'Tonton film ' . $film->title . ' di studio privat SynemaX.'), 150) }}">
    <meta property="og:title" content="{{ $film->title }} - SynemaX">
    <meta property="og:description" content="{{ Str::limit(strip_tags($film->synopsis ?? ''), 150) }}">
    @if ($film->posterUrl())
        <meta property="og:image" content="{{ $film->posterUrl() }}">
    @endif
@endsection

@section('content')
@php
    $ytId = extract_youtube_id($film->trailer_url);
    $hasPoster = filled($film->posterUrl());
    $genres = $film->genresList();
    $durationText = format_durasi_id($film->duration_minutes);
    $bookingUrl = $firstAvailableSession
        ? route('booking.create', ['session' => $firstAvailableSession->id, 'film' => $film->id])
        : route('schedule.index', ['film' => $film->id]);
@endphp

{{-- Shared Trailer Modal --}}
<x-public.trailer-modal />

{{-- Header Banner Pola CGV --}}
<div class="relative overflow-hidden bg-surface dark:bg-[#07090E] border-b border-border transition-colors duration-200">
    @if ($hasPoster)
        {{-- Backdrop Layer Pola CGV: Menggunakan file poster yang sama persis, tajam & jelas terlihat --}}
        <div
            class="absolute inset-0 z-0 bg-cover bg-[center_25%] md:bg-[center_38%] pointer-events-none opacity-80 dark:opacity-65 transition-opacity duration-300"
            style="background-image: url('{{ $film->posterUrl() }}');"
            aria-hidden="true"
        ></div>
        {{-- Gradient Overlay Pola CGV: Fading halus dari permukaan solid di bawah ke lapisan lembut di atas --}}
        <div
            class="absolute inset-0 z-0 bg-gradient-to-t from-surface via-surface/85 to-surface/50 dark:from-[#07090E] dark:via-[#07090E]/85 dark:to-[#07090E]/55 pointer-events-none"
            aria-hidden="true"
        ></div>
    @else
        <div class="absolute inset-0 z-0 bg-gradient-to-b from-surface-2 to-surface dark:from-[#0B101B] dark:to-[#07090E] pointer-events-none" aria-hidden="true"></div>
    @endif

    {{-- Content Container --}}
    <div class="relative z-10 mx-auto max-w-6xl px-4 pt-4 sm:pt-6 pb-8 sm:px-6">
        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-xs font-semibold text-text-muted mb-6" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-amber-500 transition">Beranda</a>
            <span class="text-text-muted">/</span>
            <a href="{{ route('films.index') }}" class="hover:text-amber-500 transition">Katalog Film</a>
            <span class="text-text-muted">/</span>
            <span class="text-text font-bold truncate max-w-xs">{{ $film->title }}</span>
        </nav>

        {{-- Desktop Layout (md: 768px and up) --}}
        <div class="hidden md:flex items-start gap-8 min-h-[300px]">
            {{-- Poster Desktop 190 x 285 px --}}
            <div class="w-[190px] h-[285px] shrink-0 rounded-2xl overflow-hidden border border-white/15 bg-black shadow-2xl relative" style="width: 190px; height: 285px; min-width: 190px; max-width: 190px;">
                @if ($hasPoster)
                    <img
                        src="{{ $film->posterUrl() }}"
                        alt="{{ $film->title }}"
                        class="size-full object-cover"
                        style="width: 100%; height: 100%; object-fit: cover;"
                        priority
                    >
                @else
                    <div class="flex size-full flex-col items-center justify-center bg-slate-900 p-4 text-center">
                        <span class="text-xs font-bold text-amber-200/90">{{ $film->title }}</span>
                    </div>
                @endif
            </div>

            {{-- Info & Chips Desktop --}}
            <div class="flex-1 space-y-4">
                <h1 class="text-[28px] font-black text-text tracking-tight leading-tight">{{ $film->title }}</h1>

                {{-- Chips Row: Rating Box, Single Genre Pills, Duration, Trailer Button --}}
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    {{-- Age Rating Box --}}
                    @if ($film->age_rating)
                        <span class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-2.5 py-1 text-xs font-black uppercase text-amber-500">
                            {{ $film->age_rating }}
                        </span>
                    @endif

                    {{-- Single Genre Pills --}}
                    @foreach ($genres as $g)
                        <span class="rounded-full border border-border bg-surface/90 px-3 py-1 text-xs font-bold text-text">
                            {{ $g }}
                        </span>
                    @endforeach

                    {{-- Duration Pill --}}
                    @if ($film->duration_minutes)
                        <span class="rounded-full border border-border bg-surface/90 px-3 py-1 text-xs font-bold text-text-muted">
                            {{ $durationText }}
                        </span>
                    @endif

                    {{-- 40px Circular Trailer Play Button --}}
                    @if ($ytId)
                        <button
                            type="button"
                            @click="$dispatch('open-trailer', { id: '{{ $ytId }}', title: '{{ addslashes($film->title) }}', trigger: $el })"
                            class="size-10 rounded-full bg-amber-500 hover:bg-amber-400 text-slate-950 flex items-center justify-center shadow-md transition-transform hover:scale-105 focus-visible:outline-2 focus-visible:outline-amber-500"
                            aria-label="Putar trailer {{ $film->title }}"
                            title="Putar trailer {{ $film->title }}"
                        >
                            <svg class="size-4 ml-0.5 fill-current" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z" />
                            </svg>
                        </button>
                    @endif
                </div>

                {{-- Info Opsional: Sutradara, Pemeran, Bahasa --}}
                @if ($film->director || $film->casts || $film->language)
                    <dl class="space-y-1.5 text-xs text-text-muted pt-2 border-t border-border max-w-2xl">
                        @if ($film->director)
                            <div class="flex gap-2">
                                <dt class="font-bold text-text shrink-0 w-20">Sutradara</dt>
                                <dd class="text-text/90">: {{ $film->director }}</dd>
                            </div>
                        @endif
                        @if ($film->casts)
                            <div class="flex gap-2">
                                <dt class="font-bold text-text shrink-0 w-20">Pemeran</dt>
                                <dd class="text-text/90">: {{ $film->casts }}</dd>
                            </div>
                        @endif
                        @if ($film->language)
                            <div class="flex gap-2">
                                <dt class="font-bold text-text shrink-0 w-20">Bahasa</dt>
                                <dd class="text-text/90">: {{ $film->language }}</dd>
                            </div>
                        @endif
                    </dl>
                @endif

                {{-- Action CTAs Desktop --}}
                <div class="pt-4 flex items-center gap-3">
                    <a
                        href="{{ $bookingUrl }}"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-amber-500 hover:bg-amber-400 px-6 text-sm font-bold text-slate-950 shadow-md transition-transform hover:scale-102"
                    >
                        <span>Pilih Film Ini</span>
                    </a>
                    <a
                        href="{{ route('schedule.index', ['film' => $film->id]) }}"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-border bg-surface px-5 text-xs font-bold text-text hover:bg-surface-2 transition"
                    >
                        <span>Lihat jadwal</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Mobile Layout (< 768px) --}}
        <div class="md:hidden space-y-4">
            <div class="flex items-start gap-4">
                {{-- Poster Mobile 88 x 132 px --}}
                <div class="w-[88px] h-[132px] shrink-0 rounded-xl overflow-hidden border border-white/15 bg-black shadow-lg relative" style="width: 88px; height: 132px; min-width: 88px; max-width: 88px;">
                    @if ($hasPoster)
                        <img
                            src="{{ $film->posterUrl() }}"
                            alt="{{ $film->title }}"
                            class="size-full object-cover"
                            style="width: 100%; height: 100%; object-fit: cover;"
                            priority
                        >
                    @else
                        <div class="flex size-full flex-col items-center justify-center bg-slate-900 p-2 text-center">
                            <span class="text-[10px] font-bold text-amber-200/90 line-clamp-3">{{ $film->title }}</span>
                        </div>
                    @endif
                </div>

                {{-- Info & Chips Mobile --}}
                <div class="flex-1 space-y-2.5 min-w-0">
                    <h1 class="text-xl font-black text-text tracking-tight leading-tight line-clamp-2">{{ $film->title }}</h1>

                    <div class="flex flex-wrap items-center gap-1.5">
                        @if ($film->age_rating)
                            <span class="rounded-md border border-amber-500/40 bg-amber-500/10 px-2 py-0.5 text-[11px] font-black uppercase text-amber-500">
                                {{ $film->age_rating }}
                            </span>
                        @endif

                        @foreach ($genres as $g)
                            <span class="rounded-full border border-border bg-surface px-2 py-0.5 text-[11px] font-bold text-text">
                                {{ $g }}
                            </span>
                        @endforeach

                        @if ($film->duration_minutes)
                            <span class="rounded-full border border-border bg-surface px-2 py-0.5 text-[11px] font-bold text-text-muted">
                                {{ $durationText }}
                            </span>
                        @endif

                        {{-- 44px Touch Target Trailer Play Button Mobile --}}
                        @if ($ytId)
                            <button
                                type="button"
                                @click="$dispatch('open-trailer', { id: '{{ $ytId }}', title: '{{ addslashes($film->title) }}', trigger: $el })"
                                class="size-11 rounded-full bg-amber-500 hover:bg-amber-400 text-slate-950 flex items-center justify-center shadow-md transition-transform active:scale-95"
                                aria-label="Putar trailer {{ $film->title }}"
                            >
                                <svg class="size-4 ml-0.5 fill-current" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Info Opsional Mobile --}}
            @if ($film->director || $film->casts || $film->language)
                <dl class="space-y-1 text-xs text-text-muted pt-2 border-t border-border">
                    @if ($film->director)
                        <div><strong class="text-text">Sutradara:</strong> {{ $film->director }}</div>
                    @endif
                    @if ($film->casts)
                        <div><strong class="text-text">Pemeran:</strong> {{ $film->casts }}</div>
                    @endif
                    @if ($film->language)
                        <div><strong class="text-text">Bahasa:</strong> {{ $film->language }}</div>
                    @endif
                </dl>
            @endif

            {{-- Action CTAs Mobile --}}
            <div class="grid grid-cols-2 gap-2.5 pt-2">
                <a
                    href="{{ $bookingUrl }}"
                    class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-amber-500 text-xs font-bold text-slate-950 shadow-md text-center"
                >
                    Pilih Film Ini
                </a>
                <a
                    href="{{ route('schedule.index', ['film' => $film->id]) }}"
                    class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-border bg-surface text-xs font-bold text-text text-center hover:bg-surface-2"
                >
                    Lihat jadwal
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Main Page Body --}}
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 space-y-10">
    {{-- Synopsis with 4 lines clamp and "Baca selengkapnya" --}}
    <div x-data="{ expanded: false }" class="rounded-2xl border border-border bg-surface p-6 shadow-token space-y-3">
        <h2 class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">Sinopsis</h2>
        <div class="text-sm leading-relaxed text-text/90">
            @if (filled($film->synopsis))
                <p :class="{ 'line-clamp-4': !expanded }" class="whitespace-pre-line text-sm text-text leading-relaxed">{{ $film->synopsis }}</p>
                @if (strlen($film->synopsis) > 240)
                    <button
                        type="button"
                        @click="expanded = !expanded"
                        class="mt-2 text-xs font-bold text-amber-500 hover:text-amber-400 hover:underline"
                        x-text="expanded ? 'Tampilkan lebih sedikit' : 'Baca selengkapnya'"
                    ></button>
                @endif
            @else
                <p class="italic text-text-muted">Sinopsis belum tersedia untuk film ini.</p>
            @endif
        </div>
    </div>

    {{-- Opsional: Gabung Sesi yang Sudah Memilih Film Ini --}}
    @if ($sessions->count() > 0)
        <section aria-labelledby="heading-sesi-gabung" class="rounded-3xl border border-border bg-surface p-6 sm:p-8 space-y-6 shadow-token">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-border pb-4">
                <div>
                    <h2 id="heading-sesi-gabung" class="text-xl sm:text-2xl font-black text-text tracking-tight">Gabung sesi yang sudah memilih film ini</h2>
                    <p class="text-xs text-text-muted">Peminat telah memilih film ini di sesi berikut. Gabung bersama penonton lain pada sesi ini.</p>
                </div>
                <a href="{{ route('schedule.index', ['film' => $film->id]) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-accent-link hover:underline">
                    <span>Lihat seluruh jadwal</span>
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($sessions as $s)
                    @php
                        $dayName = \Carbon\Carbon::parse($s->session_date)->locale('id')->translatedFormat('l');
                        $startTime = str_replace(':', '.', substr((string) $s->start_time, 0, 5));
                        $capacity = $s->seatCapacity();
                        $occupied = (int) ($s->occupied_seats ?? 0);
                        $remaining = max(0, $capacity - $occupied);
                        $tallySeats = $s->candidateFilmTallies()->firstWhere('film_id', $film->id)['seats'] ?? ($s->film_id === $film->id ? $occupied : 0);
                        $quorum = $s->quorum();
                    @endphp
                    <div class="flex flex-col justify-between rounded-2xl border border-border bg-surface-2/60 p-4 transition hover:border-amber-500">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs font-bold">
                                <span class="text-text font-black">{{ $dayName }} {{ $startTime }} · {{ $tallySeats }}/{{ $quorum }}</span>
                                @if ($s->isConfirmed())
                                    <span class="rounded-md bg-emerald-500/20 px-2 py-0.5 text-[10px] font-black text-emerald-400 border border-emerald-500/30">
                                        Pasti tayang
                                    </span>
                                @else
                                    <span class="rounded-md bg-amber-500/15 px-2 py-0.5 text-[10px] font-black text-amber-500 border border-amber-500/30">
                                        Butuh {{ max(0, $quorum - $tallySeats) }} lagi
                                    </span>
                                @endif
                            </div>

                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-800">
                                <div class="h-full {{ $s->isConfirmed() ? 'bg-emerald-400' : 'bg-amber-400' }}"
                                     style="width: {{ min(100, round(($tallySeats / $quorum) * 100)) }}%"></div>
                            </div>

                            <div class="flex items-center justify-between text-xs text-text-muted pt-1">
                                <span>Kursi tersisa:</span>
                                <strong class="{{ $remaining > 0 ? 'text-emerald-500 font-bold' : 'text-danger font-bold' }}">
                                    {{ $remaining }} kursi
                                </strong>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-border flex items-center justify-between">
                            <div>
                                <span class="text-[10px] text-text-muted block">Tiket Mulai</span>
                                <strong class="text-xs font-black text-text">Rp {{ number_format($s->price_per_seat ?? 20000, 0, ',', '.') }}</strong>
                            </div>

                            @if ($remaining > 0)
                                <a href="{{ route('booking.create', ['session' => $s->id, 'film' => $film->id]) }}"
                                   class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 px-3.5 py-1.5 text-xs font-black shadow-sm transition">
                                    <span>Gabung Sesi</span>
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            @else
                                <span class="text-xs font-bold text-danger">Penuh</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Other Films Recommendation --}}
    @if ($otherFilms->count() > 0)
        <section aria-labelledby="heading-rekomendasi" class="space-y-4">
            <h2 id="heading-rekomendasi" class="text-xl font-black text-text tracking-tight">Film Lainnya</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach ($otherFilms as $other)
                    <a
                        href="{{ route('films.show', $other->slug ?: $other->id) }}"
                        class="group relative flex flex-col rounded-2xl overflow-hidden border border-border bg-surface shadow-token hover:border-amber-500 transition"
                    >
                        <div class="relative w-full aspect-[2/3] overflow-hidden bg-slate-900">
                            @if ($other->posterUrl())
                                <img
                                    src="{{ $other->posterUrl() }}"
                                    alt="{{ $other->title }}"
                                    loading="lazy"
                                    class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                                >
                            @else
                                <div class="flex size-full flex-col items-center justify-center bg-slate-900 p-2 text-center">
                                    <span class="text-xs font-bold text-amber-200/80 line-clamp-2">{{ $other->title }}</span>
                                </div>
                            @endif
                            @if ($other->age_rating)
                                <span class="absolute top-2 right-2 rounded-md bg-black/75 px-1.5 py-0.5 text-[10px] font-black uppercase text-amber-400">
                                    {{ $other->age_rating }}
                                </span>
                            @endif
                        </div>
                        <div class="p-3">
                            <h3 class="text-xs font-black text-text line-clamp-1 group-hover:text-amber-500">{{ $other->title }}</h3>
                            <span class="text-[11px] text-text-muted mt-0.5 block truncate">{{ $other->genre ?: 'Film' }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
