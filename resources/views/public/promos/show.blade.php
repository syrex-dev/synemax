@extends('layouts.public')

@section('title', $promo->title . ' - Promo SynemaX')

@section('meta')
    <meta name="description" content="{{ Str::limit(strip_tags($promo->description ?? 'Lihat syarat & ketentuan promo ' . $promo->title . ' di SynemaX.'), 150) }}">
    <meta property="og:title" content="{{ $promo->title }} - Promo SynemaX">
    <meta property="og:description" content="{{ Str::limit(strip_tags($promo->description ?? ''), 150) }}">
    @if ($promo->imageUrl())
        <meta property="og:image" content="{{ $promo->imageUrl() }}">
    @endif
@endsection

@section('content')
<div class="relative overflow-hidden">
    {{-- CGV-Inspired Blurred Backdrop Header --}}
    @if ($promo->imageUrl())
        <div
            class="pointer-events-none absolute -top-24 inset-x-0 h-96 -z-10 bg-cover bg-center opacity-30 dark:opacity-20 blur-3xl transform scale-110"
            style="background-image: url('{{ $promo->imageUrl() }}');"
            aria-hidden="true"
        ></div>
        <div class="pointer-events-none absolute -top-24 inset-x-0 h-96 -z-10 bg-gradient-to-b from-transparent via-bg/80 to-bg" aria-hidden="true"></div>
    @endif

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 space-y-8">
        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-xs font-semibold text-text-muted" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-amber-500 transition">Beranda</a>
            <span class="text-text-subtle">/</span>
            <a href="{{ route('promos.index') }}" class="hover:text-amber-500 transition">Promo</a>
            <span class="text-text-subtle">/</span>
            <span class="text-text font-bold truncate max-w-xs">{{ $promo->title }}</span>
        </nav>

        {{-- Promo Title & Period Chip Header --}}
        <div class="space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                @if ($promo->isExpired())
                    <span class="rounded-xl bg-red-600/90 text-white px-3 py-1 text-xs font-black uppercase tracking-wider shadow-sm">
                        Promo Berakhir
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-xl border border-amber-500/40 bg-amber-500/15 px-3 py-1 text-xs font-bold text-amber-600 dark:text-amber-400">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Periode: {{ $promo->periodFormatted() }}</span>
                    </span>
                @endif
            </div>

            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-text tracking-tight leading-tight">
                {{ $promo->title }}
            </h1>
        </div>

        {{-- Content Card with Full 1200x600 Image --}}
        <div class="rounded-3xl border border-slate-300 dark:border-white/10 bg-surface dark:bg-[#0B101B] overflow-hidden shadow-xl space-y-8 p-4 sm:p-6 lg:p-8">
            {{-- Pure Banner Image 1200x600 --}}
            <div class="relative w-full aspect-[2/1] rounded-2xl overflow-hidden bg-slate-950 border border-slate-200 dark:border-white/10 shadow-md">
                @if ($promo->imageUrl())
                    <img
                        src="{{ $promo->imageUrl() }}"
                        alt="{{ $promo->title }}"
                        class="size-full object-cover"
                        priority
                    >
                @else
                    <div class="flex size-full flex-col items-center justify-center bg-gradient-to-br from-amber-600 via-amber-700 to-slate-950 p-6 text-center text-white">
                        <span class="text-sm font-black uppercase tracking-widest text-amber-200">Promo Eksklusif</span>
                        <strong class="mt-2 text-xl sm:text-2xl font-black">{{ $promo->title }}</strong>
                    </div>
                @endif
            </div>

            {{-- Description Section --}}
            @if (filled($promo->description))
                <div class="space-y-2">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Tentang Promo Ini</h2>
                    <div class="text-sm sm:text-base leading-relaxed text-text/90 prose dark:prose-invert max-w-none">
                        <p class="whitespace-pre-line">{{ $promo->description }}</p>
                    </div>
                </div>
            @endif

            {{-- Terms & Conditions (Numbered List) --}}
            @if (filled($promo->terms_conditions))
                @php
                    $termsList = array_values(array_filter(
                        array_map('trim', preg_split('/\r\n|\r|\n/', (string) $promo->terms_conditions)),
                        fn($line) => $line !== ''
                    ));
                @endphp
                @if (count($termsList) > 0)
                    <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-surface-2/60 dark:bg-black/30 p-5 sm:p-6 space-y-3">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 flex items-center gap-2">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Syarat & Ketentuan</span>
                        </h2>
                        <ol class="space-y-2 text-xs sm:text-sm text-text/80 list-decimal list-outside ml-4 sm:ml-5 leading-relaxed font-medium">
                            @foreach ($termsList as $term)
                                {{-- Remove any leading "1. " or "- " if already present in raw string --}}
                                @php
                                    $cleanTerm = preg_replace('/^(\d+[\.\)]|\-|\*)\s*/', '', $term);
                                @endphp
                                <li>{{ $cleanTerm }}</li>
                            @endforeach
                        </ol>
                    </div>
                @endif
            @endif

            {{-- Booking Call To Action --}}
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-slate-200 dark:border-white/10">
                <div class="text-center sm:text-left">
                    <span class="text-xs text-text-muted block">Siap nonton film favoritmu?</span>
                    <strong class="text-sm font-black text-text">Pesan sesi sekarang dan nikmati promo ini</strong>
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <a
                        href="{{ route('schedule.index') }}"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 px-6 py-3 text-xs sm:text-sm font-black shadow-md transition"
                    >
                        <span>Pesan Tiket Sekarang</span>
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>
            </div>
        </div>

        {{-- Other Promos Carousel/Grid --}}
        @if ($otherPromos->count() > 0)
            <section aria-labelledby="heading-promo-lainnya" class="space-y-4 pt-6">
                <div class="flex items-center justify-between">
                    <h2 id="heading-promo-lainnya" class="text-xl font-black text-text tracking-tight">Promo Lainnya</h2>
                    <a href="{{ route('promos.index') }}" class="text-xs font-bold text-accent-link hover:underline">
                        Lihat Semua
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    @foreach ($otherPromos as $other)
                        <a
                            href="{{ route('promos.show', $other->slug ?: $other->id) }}"
                            class="group flex flex-col rounded-2xl overflow-hidden border border-slate-300 dark:border-white/10 bg-surface dark:bg-[#0B101B] shadow-sm hover:border-amber-500 transition"
                        >
                            <div class="relative w-full aspect-[2/1] overflow-hidden bg-slate-950">
                                @if ($other->imageUrl())
                                    <img
                                        src="{{ $other->imageUrl() }}"
                                        alt="{{ $other->title }}"
                                        loading="lazy"
                                        class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                                    >
                                @else
                                    <div class="flex size-full items-center justify-center bg-slate-900 p-2 text-center">
                                        <span class="text-xs font-bold text-amber-300 line-clamp-1">{{ $other->title }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="p-3">
                                <h3 class="text-xs font-black text-text line-clamp-1 group-hover:text-amber-500">{{ $other->title }}</h3>
                                <span class="text-[10px] text-text-muted mt-0.5 block truncate">{{ $other->periodFormatted() }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
@endsection
