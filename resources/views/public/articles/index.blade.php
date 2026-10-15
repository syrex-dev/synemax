@extends('layouts.public')

@section('title', 'Kabar & Promo · SynemaX Home Cinema')

@section('meta')
    <meta property="og:type" content="website">
    <meta property="og:title" content="Kabar & Promo Terbaru · SynemaX">
    <meta property="og:description" content="Kumpulan berita, promo tiket, update jajanan, dan pengumuman terbaru dari SynemaX Home Cinema.">
    <meta property="og:url" content="{{ route('articles.index') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Kabar & Promo Terbaru · SynemaX">
    <meta name="twitter:description" content="Kumpulan berita, promo tiket, dan info terbaru SynemaX Home Cinema.">
@endsection

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
        {{-- Hero Header --}}
        <div class="rounded-3xl border border-border bg-surface p-6 sm:p-10 shadow-token">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-accent/10 px-3 py-1 text-xs font-bold text-accent">
                Warta Studio
            </span>
            <h1 class="mt-3 text-3xl font-black text-text sm:text-4xl">Kabar & Promo Terbaru</h1>
            <p class="mt-2 max-w-xl text-sm text-text-muted leading-relaxed">
                Temukan pengumuman terkini, promo sesi bioskop, dan informasi menarik seputar SynemaX Home Cinema.
            </p>

            {{-- Category Filter --}}
            <div class="mt-6 flex flex-wrap gap-2 border-t border-border pt-6">
                <a href="{{ route('articles.index') }}" class="inline-flex min-h-10 items-center rounded-xl px-4 text-xs font-bold transition {{ empty($currentCategory) ? 'bg-accent text-accent-contrast shadow-xs' : 'border border-border bg-bg text-text-muted hover:text-text' }}">
                    Semua Kategori
                </a>
                @foreach (['Berita', 'Promo', 'Pengumuman'] as $cat)
                    <a href="{{ route('articles.index', ['kategori' => $cat]) }}" class="inline-flex min-h-10 items-center rounded-xl px-4 text-xs font-bold transition {{ $currentCategory === $cat ? 'bg-accent text-accent-contrast shadow-xs' : 'border border-border bg-bg text-text-muted hover:text-text' }}">
                        {{ $cat }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Articles Grid --}}
        <div class="mt-8">
            @if ($articles->isEmpty())
                <div class="rounded-3xl border border-dashed border-border bg-surface p-12 text-center">
                    <svg class="mx-auto size-12 text-text-muted/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                    </svg>
                    <h2 class="mt-3 text-lg font-black text-text">Belum Ada Artikel</h2>
                    <p class="mt-1 text-xs text-text-muted">Kabar atau promo untuk kategori ini belum tersedia. Silakan cek kembali nanti.</p>
                </div>
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($articles as $item)
                        <article class="group flex flex-col justify-between overflow-hidden rounded-3xl border border-border bg-surface transition duration-200 hover:-translate-y-1 hover:border-accent hover:shadow-token">
                            <div>
                                {{-- Cover Image --}}
                                <div class="relative aspect-[16/9] w-full overflow-hidden bg-surface-2">
                                    @if ($item->cover_path)
                                        <img src="{{ $item->coverUrl() }}" alt="Cover {{ $item->judul }}" class="size-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                                    @else
                                        <div class="flex size-full flex-col items-center justify-center bg-gradient-to-br from-surface to-surface-2 p-4 text-center">
                                            <svg class="size-8 text-text-muted/50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                            </svg>
                                            <span class="mt-1 text-xs font-bold text-text-muted">SynemaX News</span>
                                        </div>
                                    @endif

                                    <div class="absolute left-3 top-3 flex flex-wrap gap-1.5">
                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-bold shadow-xs backdrop-blur-md {{ $item->kategoriBadgeClasses() }}">
                                            {{ $item->kategori }}
                                        </span>
                                        @if ($item->is_pinned)
                                            <span class="rounded-full bg-accent px-2.5 py-1 text-[11px] font-bold text-accent-contrast shadow-xs">
                                                Pilihan
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="p-5">
                                    <div class="flex items-center gap-1.5 text-[11px] text-text-muted">
                                        <svg class="size-3.5 shrink-0 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span>{{ ($item->tanggal_terbit ?: $item->created_at)->translatedFormat('d M Y') }}</span>
                                    </div>

                                    <h2 class="mt-2 text-lg font-black text-text leading-snug group-hover:text-accent">
                                        <a href="{{ route('articles.show', ['slug' => $item->slug]) }}">
                                            {{ $item->judul }}
                                        </a>
                                    </h2>

                                    <p class="mt-2 text-xs text-text-muted leading-relaxed line-clamp-3">
                                        {{ $item->ringkasan }}
                                    </p>
                                </div>
                            </div>

                            <div class="border-t border-border p-5 pt-3">
                                <a href="{{ route('articles.show', ['slug' => $item->slug]) }}" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-border bg-surface-2 px-4 text-xs font-bold text-text transition group-hover:border-accent group-hover:bg-accent group-hover:text-accent-contrast">
                                    <span>Baca Selengkapnya</span>
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $articles->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
