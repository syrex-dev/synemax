@extends('layouts.public')

@section('title', 'Kabar dan promo: SynemaX')

@section('meta')
    <meta property="og:type" content="website">
    <meta property="og:title" content="Kabar dan promo: SynemaX">
    <meta property="og:description" content="Kumpulan berita, promo tiket, informasi jajanan studio, dan pengumuman terbaru dari SynemaX.">
    <meta property="og:url" content="{{ route('articles.index') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Kabar dan promo: SynemaX">
    <meta name="twitter:description" content="Kumpulan berita, promo tiket, dan info terbaru SynemaX.">
@endsection

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10">
        {{-- Header Halaman Cinema Editorial --}}
        <div class="relative overflow-hidden rounded-3xl border border-border dark:border-white/10 bg-surface dark:bg-gradient-to-br dark:from-surface dark:via-[#0C111D] dark:to-[#07090E] p-6 sm:p-8 shadow-2xl">
            <div class="absolute -right-16 -top-16 size-64 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>

            <div class="relative">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-400/40 bg-amber-500/15 px-3 py-1 text-xs font-black uppercase tracking-wider text-amber-500 dark:text-amber-300">
                    <span class="size-2 rounded-full bg-amber-400 animate-pulse"></span>
                    Warta studio
                </span>
                <h1 class="mt-2 text-2xl font-black text-text sm:text-4xl tracking-tight">Kabar dan promo</h1>
                <p class="mt-2 max-w-xl text-xs sm:text-sm text-text-muted leading-relaxed">
                    Temukan pengumuman terkini, promo sesi bioskop mini, dan informasi seputar SynemaX.
                </p>

                {{-- Filter Kategori --}}
                <div class="mt-6 flex flex-wrap gap-2 border-t border-border dark:border-white/10 pt-5">
                    <a href="{{ route('articles.index') }}" class="inline-flex min-h-10 items-center rounded-xl px-4 text-xs font-black transition {{ empty($currentCategory) ? 'btn-gold-cinema shadow-md' : 'border border-border dark:border-white/10 bg-surface-2 dark:bg-surface/60 text-text-muted hover:border-amber-400/50 hover:text-text' }}">
                        Semua kategori
                    </a>
                    @foreach (['Berita', 'Promo', 'Pengumuman'] as $cat)
                        <a href="{{ route('articles.index', ['kategori' => $cat]) }}" class="inline-flex min-h-10 items-center rounded-xl px-4 text-xs font-black transition {{ $currentCategory === $cat ? 'btn-gold-cinema shadow-md' : 'border border-border dark:border-white/10 bg-surface-2 dark:bg-surface/60 text-text-muted hover:border-amber-400/50 hover:text-text' }}">
                            {{ $cat }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Daftar Artikel --}}
        <div class="mt-8">
            @if ($articles->isEmpty())
                <div class="rounded-3xl border border-dashed border-border dark:border-white/15 bg-surface-2/60 dark:bg-surface/40 p-12 text-center backdrop-blur-sm">
                    <span class="inline-flex size-14 items-center justify-center rounded-2xl bg-amber-500/10 text-2xl text-amber-500 dark:text-amber-400 mb-3">
                        📰
                    </span>
                    <h2 class="text-base font-black text-text">Belum ada artikel</h2>
                    <p class="mt-1 text-xs text-text-muted max-w-md mx-auto">Kabar atau promo untuk kategori ini belum tersedia. Silakan cek kembali nanti.</p>
                </div>
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($articles as $item)
                        <article class="flex flex-col justify-between rounded-3xl border border-border dark:border-white/10 bg-surface dark:bg-gradient-to-b dark:from-surface dark:via-[#0B0F19] dark:to-surface p-4 sm:p-5 shadow-xl transition-all duration-300 hover:border-amber-400/50 hover:-translate-y-1 group">
                            <div>
                                {{-- 16:9 Cinema Widescreen Cover --}}
                                @if ($item->cover_path)
                                    <div class="relative aspect-[16/9] w-full overflow-hidden rounded-2xl bg-slate-100 dark:bg-black/60 mb-4 border border-border dark:border-white/5 shadow-inner">
                                        <img src="{{ $item->coverUrl() }}" alt="Cover {{ $item->judul }}" class="size-full object-cover rounded-xl transition duration-500 group-hover:scale-105" loading="lazy">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                                        <div class="absolute left-3 top-3 flex flex-wrap gap-1.5">
                                            <span class="rounded-lg border border-border dark:border-white/20 bg-surface/90 dark:bg-black/80 backdrop-blur-md px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-text dark:text-white shadow-md">
                                                {{ $item->kategori }}
                                            </span>
                                            @if ($item->is_pinned)
                                                <span class="rounded-lg bg-gradient-to-r from-amber-400 to-amber-600 px-2.5 py-1 text-[10px] font-black uppercase text-black shadow-md">
                                                    Pilihan
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <div>
                                    <div class="flex items-center gap-1.5 text-[11px] font-bold text-amber-600 dark:text-amber-500">
                                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span>{{ ($item->tanggal_terbit ?: $item->created_at)->locale('id')->translatedFormat('d F Y') }}</span>
                                    </div>

                                    <h2 class="mt-2 text-base font-black text-text leading-snug group-hover:text-amber-500 dark:group-hover:text-amber-400 transition">
                                        <a href="{{ route('articles.show', ['slug' => $item->slug]) }}">
                                            {{ $item->judul }}
                                        </a>
                                    </h2>

                                    <p class="mt-2 text-xs text-text-muted leading-relaxed line-clamp-3">
                                        {{ $item->ringkasan }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 border-t border-border dark:border-white/10 pt-3">
                                <a href="{{ route('articles.show', ['slug' => $item->slug]) }}" class="inline-flex min-h-11 w-full items-center justify-center gap-1.5 rounded-xl border border-border dark:border-white/20 bg-surface-2 dark:bg-surface px-4 text-xs font-bold text-text transition hover:border-amber-400 hover:text-amber-500 dark:hover:text-amber-300">
                                    <span>Baca selengkapnya</span>
                                    <svg class="size-3.5 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
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
