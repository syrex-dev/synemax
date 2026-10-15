@extends('layouts.public')

@section('title', 'Jadwal Nobar Sepak Bola · SynemaX Home Cinema')

@section('meta')
    <meta property="og:type" content="website">
    <meta property="og:title" content="Jadwal Nobar Sepak Bola · SynemaX">
    <meta property="og:description" content="Nonton bareng pertandingan sepak bola seru di SynemaX Home Cinema. Layar lebar 120 inci, tata suara surround, suasana hangat komunitas.">
    <meta property="og:url" content="{{ route('nobar.index') }}">
    <meta property="og:image" content="{{ url(asset('social-banner.png')) }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Jadwal Nobar Sepak Bola · SynemaX">
    <meta name="twitter:description" content="Nonton bareng pertandingan sepak bola seru di SynemaX Home Cinema.">
    <meta name="twitter:image" content="{{ url(asset('social-banner.png')) }}">
@endsection

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
        {{-- Hero Header --}}
        <div class="rounded-3xl border border-border bg-surface p-6 sm:p-10 shadow-token">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-accent/10 px-3 py-1 text-xs font-bold text-accent">
                        Nobar Komunitas
                    </span>
                    <h1 class="mt-3 text-3xl font-black text-text sm:text-4xl">Jadwal Nonton Bareng</h1>
                    <p class="mt-2 max-w-xl text-sm text-text-muted leading-relaxed">
                        Nikmati ketegangan pertandingan sepak bola di layar lebar proyektor dan tata suara menggelegar bersama sesama penikmat bola.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('home') }}#jadwal" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-border bg-surface-2 px-4 text-xs font-bold text-text hover:border-accent">
                        <span>Jadwal Film Bioskop</span>
                    </a>
                </div>
            </div>

            {{-- Filter Status --}}
            <div class="mt-6 flex flex-wrap gap-2 border-t border-border pt-6">
                <a href="{{ route('nobar.index') }}" class="inline-flex min-h-10 items-center rounded-xl px-4 text-xs font-bold transition {{ $currentFilter === 'semua' ? 'bg-accent text-accent-contrast shadow-xs' : 'border border-border bg-bg text-text-muted hover:text-text' }}">
                    Semua Acara
                </a>
                <a href="{{ route('nobar.index', ['status' => 'mendatang']) }}" class="inline-flex min-h-10 items-center rounded-xl px-4 text-xs font-bold transition {{ $currentFilter === 'mendatang' ? 'bg-accent text-accent-contrast shadow-xs' : 'border border-border bg-bg text-text-muted hover:text-text' }}">
                    Akan Datang
                </a>
                <a href="{{ route('nobar.index', ['status' => 'selesai']) }}" class="inline-flex min-h-10 items-center rounded-xl px-4 text-xs font-bold transition {{ $currentFilter === 'selesai' ? 'bg-accent text-accent-contrast shadow-xs' : 'border border-border bg-bg text-text-muted hover:text-text' }}">
                    Selesai
                </a>
            </div>
        </div>

        {{-- Events Grid --}}
        <div class="mt-8">
            @if ($events->isEmpty())
                <div class="rounded-3xl border border-dashed border-border bg-surface p-12 text-center">
                    <svg class="mx-auto size-12 text-text-muted/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <h2 class="mt-3 text-lg font-black text-text">Belum Ada Jadwal Nobar</h2>
                    <p class="mt-1 text-xs text-text-muted">Jadwal pertandingan berikutnya sedang dipersiapkan. Pantau terus halaman ini atau cek film bioskop kami.</p>
                    <a href="{{ route('home') }}#jadwal" class="mt-4 inline-flex min-h-11 items-center rounded-xl bg-accent px-5 text-xs font-bold text-accent-contrast hover:opacity-90">
                        Lihat Jadwal Film
                    </a>
                </div>
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($events as $item)
                        <article class="group flex flex-col justify-between overflow-hidden rounded-3xl border border-border bg-surface transition duration-200 hover:-translate-y-1 hover:border-accent hover:shadow-token">
                            <div>
                                @if ($item->hasPoster())
                                    {{-- 4:5 Portrait Thumbnail Header --}}
                                    <div class="relative aspect-[4/5] w-full overflow-hidden bg-surface-2">
                                        <img src="{{ $item->posterUrl() }}" alt="{{ $item->posterAltText() }}" class="size-full object-cover object-top transition duration-300 group-hover:scale-105" loading="lazy">

                                        {{-- Status Badges --}}
                                        <div class="absolute left-3 top-3 flex flex-wrap gap-1.5">
                                            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold shadow-xs backdrop-blur-md {{ $item->badgeClasses() }}">
                                                {{ $item->display_status }}
                                            </span>
                                            @if ($item->is_pinned)
                                                <span class="rounded-full bg-accent px-2.5 py-1 text-[11px] font-bold text-accent-contrast shadow-xs">
                                                    Pilihan
                                                </span>
                                            @endif
                                        </div>

                                        @if ($item->is_free)
                                            <div class="absolute bottom-3 right-3 rounded-full bg-success/90 px-2.5 py-0.5 text-[10px] font-black uppercase text-success-contrast backdrop-blur-md">
                                                Gratis
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    {{-- Clean typographic header when no poster exists --}}
                                    <div class="border-b border-border bg-gradient-to-br from-surface to-surface-2 p-5">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold shadow-xs {{ $item->badgeClasses() }}">
                                                {{ $item->display_status }}
                                            </span>
                                            @if ($item->is_pinned)
                                                <span class="rounded-full bg-accent px-2.5 py-1 text-[11px] font-bold text-accent-contrast shadow-xs">
                                                    Pilihan
                                                </span>
                                            @endif
                                            @if ($item->is_free)
                                                <span class="rounded-full bg-success/90 px-2.5 py-0.5 text-[10px] font-black uppercase text-success-contrast">
                                                    Gratis
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- Event Details --}}
                                <div class="p-5">
                                    @if ($item->kompetisi)
                                        <p class="text-xs font-bold uppercase tracking-wider text-accent">{{ $item->kompetisi }}</p>
                                    @endif

                                    <h2 class="mt-1 text-lg font-black text-text leading-snug group-hover:text-accent">
                                        <a href="{{ route('nobar.show', ['slug' => $item->slug]) }}">
                                            {{ $item->judul }}
                                        </a>
                                    </h2>

                                    {{-- Versus Teams Card --}}
                                    <div class="mt-3 flex items-center justify-between rounded-xl border border-border bg-surface-2 px-3.5 py-2.5 text-xs font-bold">
                                        <span class="truncate text-text">{{ $item->tim_a }}</span>
                                        <span class="px-2 font-mono text-[11px] text-accent">VS</span>
                                        <span class="truncate text-text text-right">{{ $item->tim_b ?: 'TBA' }}</span>
                                    </div>

                                    {{-- Info Meta --}}
                                    <div class="mt-3 space-y-1.5 text-xs text-text-muted">
                                        <div class="flex items-center gap-2">
                                            <svg class="size-4 shrink-0 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span>{{ $item->kickoff_at->translatedFormat('l, d M Y · H:i') }} WIB</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <svg class="size-4 shrink-0 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span class="truncate">{{ $item->lokasi_nama }}</span>
                                        </div>
                                    </div>

                                    @if ($item->status_acara !== 'aktif' && $item->alasan_status)
                                        <div class="mt-3 rounded-xl border border-danger/30 bg-danger/10 p-2.5 text-xs text-danger">
                                            <p class="font-bold">Info: {{ $item->alasan_status }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="border-t border-border p-5 pt-3">
                                <a href="{{ route('nobar.show', ['slug' => $item->slug]) }}" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-accent px-4 text-xs font-bold text-accent-contrast shadow-xs transition hover:opacity-90">
                                    <span>Lihat Detail Acara</span>
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $events->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
