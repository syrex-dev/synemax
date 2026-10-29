@extends('layouts.public')

@section('title', 'Promo & Penawaran Khusus - ' . setting('site_name', 'SynemaX'))

@section('meta')
    <meta name="description" content="Temukan berbagai promo menarik, paket hemat nonton privat, dan penawaran spesial di studio mini SynemaX.">
@endsection

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 space-y-10">
    {{-- Breadcrumb & Heading --}}
    <div class="space-y-3">
        <nav class="flex items-center gap-2 text-xs font-semibold text-text-muted" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-amber-500 transition">Beranda</a>
            <span class="text-text-subtle">/</span>
            <span class="text-text font-bold">Promo & Penawaran</span>
        </nav>
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl sm:text-4xl font-black text-text tracking-tight">Promo & Penawaran</h1>
                <p class="mt-1 text-sm text-text-muted">Nikmati penawaran spesial, diskon tiket, dan paket hemat nonton privat di SynemaX.</p>
            </div>
            <a href="{{ route('schedule.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 px-4 py-2 text-xs sm:text-sm font-bold shadow-md transition">
                <span>Pesan Tiket Sekarang</span>
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
    </div>

    {{-- Active Promos --}}
    <section aria-labelledby="heading-promo-aktif" class="space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-white/10 pb-3">
            <h2 id="heading-promo-aktif" class="text-xl font-black text-text tracking-tight">Promo Berlangsung</h2>
            <span class="text-xs font-semibold text-text-muted">{{ $activePromos->count() }} Penawaran Tersedia</span>
        </div>

        @if ($activePromos->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($activePromos as $promo)
                    <a
                        href="{{ route('promos.show', $promo->slug ?: $promo->id) }}"
                        class="group flex flex-col rounded-3xl overflow-hidden border border-slate-300 dark:border-white/10 bg-surface dark:bg-[#0B101B] shadow-sm hover:shadow-xl hover:border-amber-500 hover:-translate-y-1 transition-all duration-300"
                    >
                        {{-- 1200x600 Image (2:1 Ratio) --}}
                        <div class="relative w-full aspect-[2/1] overflow-hidden bg-slate-950">
                            @if ($promo->imageUrl())
                                <img
                                    src="{{ $promo->imageUrl() }}"
                                    alt="{{ $promo->title }}"
                                    loading="lazy"
                                    class="size-full object-cover transition-transform duration-500 group-hover:scale-105"
                                >
                            @else
                                <div class="flex size-full flex-col items-center justify-center bg-gradient-to-br from-amber-600 via-amber-700 to-slate-950 p-4 text-center text-white">
                                    <span class="text-xs uppercase tracking-widest font-black text-amber-200">Promo Spesial</span>
                                    <strong class="mt-1 text-sm sm:text-base font-black line-clamp-2">{{ $promo->title }}</strong>
                                </div>
                            @endif

                            {{-- Active Period Chip --}}
                            @if ($promo->end_date)
                                <span class="absolute bottom-3 left-3 rounded-xl bg-black/75 backdrop-blur-md px-2.5 py-1 text-[11px] font-bold text-amber-300 border border-white/15">
                                    s.d. {{ \Carbon\Carbon::parse($promo->end_date)->translatedFormat('d M Y') }}
                                </span>
                            @endif
                        </div>

                        {{-- Card Details --}}
                        <div class="flex flex-1 flex-col justify-between p-4 sm:p-5 space-y-3">
                            <div>
                                <h3 class="text-base font-black text-text group-hover:text-amber-500 transition line-clamp-1">
                                    {{ $promo->title }}
                                </h3>
                                @if (filled($promo->description))
                                    <p class="mt-1.5 text-xs text-text-muted line-clamp-2 leading-relaxed">
                                        {{ strip_tags($promo->description) }}
                                    </p>
                                @endif
                            </div>

                            <div class="pt-3 border-t border-slate-200 dark:border-white/5 flex items-center justify-between text-xs font-bold text-accent-link">
                                <span>Lihat Detail Syarat</span>
                                <svg class="size-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="rounded-3xl border border-dashed border-slate-300 dark:border-white/15 p-12 text-center">
                <svg class="mx-auto size-12 text-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                <h3 class="mt-3 text-base font-bold text-text">Belum ada promo aktif</h3>
                <p class="mt-1 text-xs text-text-muted">Pantau terus halaman ini atau follow media sosial kami untuk diskon dan promo menarik.</p>
            </div>
        @endif
    </section>

    {{-- Expired Promos (Dimmed) --}}
    @if ($expiredPromos->count() > 0)
        <section aria-labelledby="heading-promo-berakhir" class="space-y-4 pt-6 border-t border-slate-200 dark:border-white/10">
            <div class="flex items-center justify-between">
                <h2 id="heading-promo-berakhir" class="text-lg font-black text-text-muted tracking-tight">Promo Telah Berakhir</h2>
                <span class="text-xs text-text-subtle">Hanya sebagai arsip</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 opacity-60 grayscale-[40%] hover:grayscale-0 transition duration-300">
                @foreach ($expiredPromos as $promo)
                    <div class="flex flex-col rounded-3xl overflow-hidden border border-slate-300 dark:border-white/10 bg-surface dark:bg-[#0B101B]">
                        <div class="relative w-full aspect-[2/1] overflow-hidden bg-slate-950">
                            @if ($promo->imageUrl())
                                <img
                                    src="{{ $promo->imageUrl() }}"
                                    alt="{{ $promo->title }}"
                                    loading="lazy"
                                    class="size-full object-cover"
                                >
                            @else
                                <div class="flex size-full flex-col items-center justify-center bg-slate-800 p-4 text-center text-slate-300">
                                    <span class="text-sm font-bold line-clamp-2">{{ $promo->title }}</span>
                                </div>
                            @endif
                            <span class="absolute top-3 right-3 rounded-lg bg-red-600/90 text-white px-2 py-0.5 text-[10px] font-black uppercase tracking-wider">
                                Berakhir
                            </span>
                        </div>
                        <div class="p-4 space-y-1">
                            <h3 class="text-sm font-bold text-text line-clamp-1">{{ $promo->title }}</h3>
                            <span class="text-[11px] text-text-muted block">Berakhir pada {{ \Carbon\Carbon::parse($promo->end_date)->translatedFormat('d F Y') }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
