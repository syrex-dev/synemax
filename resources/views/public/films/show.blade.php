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
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 space-y-10">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs font-semibold text-text-muted" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-amber-500 transition">Beranda</a>
        <span class="text-text-subtle">/</span>
        <a href="{{ route('films.index') }}" class="hover:text-amber-500 transition">Katalog Film</a>
        <span class="text-text-subtle">/</span>
        <span class="text-text font-bold truncate max-w-xs">{{ $film->title }}</span>
    </nav>

    {{-- Film Overview (Poster + Details) --}}
    <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
        {{-- Poster Column (md: 4 cols) --}}
        <div class="md:col-span-4 lg:col-span-4">
            <div class="relative w-full aspect-[2/3] max-w-sm mx-auto rounded-3xl overflow-hidden border border-slate-300 dark:border-white/10 bg-slate-900 shadow-xl">
                @if ($film->posterUrl())
                    <img
                        src="{{ $film->posterUrl() }}"
                        alt="{{ $film->title }}"
                        class="size-full object-cover"
                        priority
                    >
                @else
                    {{-- Fallback Gradient Poster --}}
                    <div class="flex size-full flex-col items-center justify-center bg-gradient-to-br from-slate-950 via-slate-900 to-amber-950/50 p-6 text-center">
                        <svg class="size-16 text-amber-500/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                        </svg>
                        <span class="mt-4 text-base font-black text-amber-200/90">{{ $film->title }}</span>
                        <span class="mt-1 text-xs text-amber-400/60 uppercase tracking-widest font-bold">Katalog Film SynemaX</span>
                    </div>
                @endif

                {{-- Age Rating Badge --}}
                @if ($film->age_rating)
                    <span class="absolute top-3 right-3 rounded-lg bg-black/80 px-2.5 py-1 text-xs font-black uppercase text-amber-400 backdrop-blur-md border border-white/15 shadow-md">
                        {{ $film->age_rating }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Details Column (md: 8 cols) --}}
        <div class="md:col-span-8 lg:col-span-8 space-y-6">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    @if ($film->genre)
                        @foreach (array_map('trim', explode(',', $film->genre)) as $g)
                            <span class="rounded-lg border border-amber-500/30 bg-amber-500/10 px-2.5 py-0.5 text-xs font-bold text-amber-600 dark:text-amber-400">
                                {{ $g }}
                            </span>
                        @endforeach
                    @endif
                    @if ($film->release_year)
                        <span class="rounded-lg border border-slate-300 dark:border-white/10 bg-surface px-2.5 py-0.5 text-xs font-semibold text-text-muted">
                            {{ $film->release_year }}
                        </span>
                    @endif
                    @if ($film->duration_minutes)
                        <span class="rounded-lg border border-slate-300 dark:border-white/10 bg-surface px-2.5 py-0.5 text-xs font-semibold text-text-muted flex items-center gap-1">
                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $film->duration_minutes }} Menit
                        </span>
                    @endif
                </div>

                <h1 class="text-3xl sm:text-4xl font-black text-text tracking-tight">{{ $film->title }}</h1>
            </div>

            {{-- Synopsis --}}
            <div class="space-y-2">
                <h2 class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Sinopsis</h2>
                <div class="text-sm leading-relaxed text-text/90 prose dark:prose-invert max-w-none">
                    @if (filled($film->synopsis))
                        <p class="whitespace-pre-line">{{ $film->synopsis }}</p>
                    @else
                        <p class="italic text-text-muted">Belum ada sinopsis lengkap untuk film ini. Hubungi pengelola untuk request jadwal tayang.</p>
                    @endif
                </div>
            </div>

            {{-- Quick Cinema Studio Perks --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-2">
                <div class="rounded-xl border border-slate-300 dark:border-white/10 bg-surface dark:bg-[#0B101B] p-3 text-center">
                    <span class="text-[11px] font-bold text-text-muted block">Studio AC</span>
                    <strong class="text-xs font-black text-amber-500">Maks. 6 Kursi</strong>
                </div>
                <div class="rounded-xl border border-slate-300 dark:border-white/10 bg-surface dark:bg-[#0B101B] p-3 text-center">
                    <span class="text-[11px] font-bold text-text-muted block">Audio System</span>
                    <strong class="text-xs font-black text-amber-500">Dolby Surround</strong>
                </div>
                <div class="rounded-xl border border-slate-300 dark:border-white/10 bg-surface dark:bg-[#0B101B] p-3 text-center col-span-2 sm:col-span-1">
                    <span class="text-[11px] font-bold text-text-muted block">Model Tayang</span>
                    <strong class="text-xs font-black text-amber-500">Pilihan Penonton</strong>
                </div>
            </div>

            {{-- Action CTA: Pilih Film Ini --}}
            <div class="pt-4 border-t border-slate-200 dark:border-white/10 flex flex-wrap items-center gap-4">
                @php
                    $bookingUrl = $firstAvailableSession
                        ? route('booking.create', ['session' => $firstAvailableSession->id, 'film' => $film->id])
                        : route('schedule.index', ['film' => $film->id]);
                @endphp
                <a href="{{ $bookingUrl }}"
                   class="btn-gold-cinema inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl px-8 text-sm font-black text-black shadow-lg hover:scale-[1.02] transition">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Pilih Film Ini</span>
                </a>

                <a href="{{ route('schedule.index', ['film' => $film->id]) }}"
                   class="inline-flex min-h-12 items-center justify-center rounded-2xl border border-slate-300 dark:border-white/15 bg-surface px-6 text-xs font-bold text-text hover:border-amber-400 transition">
                    Lihat Pilihan Jam di Jadwal
                </a>
            </div>
        </div>
    </div>

    {{-- Opsional: Gabung Sesi yang Sudah Memilih Film Ini (Maksimal 3) --}}
    @if ($sessions->count() > 0)
        <section aria-labelledby="heading-sesi-gabung" class="rounded-3xl border border-slate-300 dark:border-white/10 bg-surface dark:bg-[#0B101B] p-6 sm:p-8 space-y-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 dark:border-white/10 pb-4">
                <div>
                    <h2 id="heading-sesi-gabung" class="text-xl sm:text-2xl font-black text-text tracking-tight">Gabung sesi yang sudah memilih film ini</h2>
                    <p class="text-xs text-text-muted">Peminat telah memilih film ini di sesi berikut. Gabung untuk mempercepat tercapainya kuorum!</p>
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
                    <div class="flex flex-col justify-between rounded-2xl border border-slate-300 dark:border-white/15 bg-surface-2/60 dark:bg-black/30 p-4 transition hover:border-amber-500">
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

                            {{-- Progress Bar Kuorum --}}
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

                        <div class="mt-4 pt-3 border-t border-slate-200 dark:border-white/10 flex items-center justify-between">
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
                        class="group relative flex flex-col rounded-2xl overflow-hidden border border-slate-300 dark:border-white/10 bg-surface dark:bg-[#0B101B] shadow-sm hover:border-amber-500 transition"
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
                                <div class="flex size-full flex-col items-center justify-center bg-gradient-to-br from-slate-900 to-amber-950/40 p-2 text-center">
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
