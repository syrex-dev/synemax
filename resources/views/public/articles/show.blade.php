@extends('layouts.public')

@section('title', $article->judul . ': SynemaX')

@section('meta')
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $article->judul }}">
    <meta property="og:description" content="{{ $article->ringkasan }}">
    <meta property="og:url" content="{{ route('articles.show', ['slug' => $article->slug]) }}">
    @if ($article->cover_path)
        <meta property="og:image" content="{{ $article->coverUrl() }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $article->judul }}">
    <meta name="twitter:description" content="{{ $article->ringkasan }}">
    @if ($article->cover_path)
        <meta name="twitter:image" content="{{ $article->coverUrl() }}">
    @endif
@endsection

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 sm:py-10">
        {{-- Navigasi Balik --}}
        <div class="mb-5">
            <a href="{{ route('articles.index') }}" class="inline-flex min-h-11 items-center gap-2 text-xs font-bold text-accent-link transition">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                <span>Kembali ke kabar & pengumuman</span>
            </a>
        </div>

        {{-- Alert Pratinjau Admin --}}
        @if ($isPreview)
            <div role="alert" class="mb-5 rounded-2xl border border-amber-400/40 bg-amber-500/10 p-4 text-xs text-amber-300">
                <span class="font-bold">Mode Pratinjau Admin</span>: Artikel ini belum terbit secara publik.
            </div>
        @endif

        {{-- Kartu Utama Artikel --}}
        <article class="rounded-3xl border border-border dark:border-white/10 bg-surface dark:bg-gradient-to-b dark:from-surface dark:via-[#0C111D] dark:to-[#07090E] p-6 sm:p-10 shadow-2xl">
            {{-- Cover Image --}}
            @if ($article->cover_path)
                <div class="relative mb-8 max-h-[420px] w-full">
                    <x-ui.image
                        :src="$article->coverUrl()"
                        :alt="'Cover ' . $article->judul"
                        :title="$article->judul"
                        :badge="$article->kategori"
                        aspect="aspect-[16/9]"
                        class="w-full max-h-[420px] shadow-inner"
                    />
                </div>
            @endif

            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="rounded-lg border border-amber-400/40 bg-amber-500/15 px-3 py-1 text-xs font-black uppercase tracking-wider text-amber-600 dark:text-amber-300">
                        {{ $article->kategori }}
                    </span>
                    <span class="text-xs text-text-muted flex items-center gap-1.5">
                        <svg class="size-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>{{ ($article->tanggal_terbit ?: $article->created_at)->locale('id')->translatedFormat('l, d F Y') }}</span>
                    </span>
                    @if ($article->is_pinned)
                        <span class="rounded-lg bg-gradient-to-r from-amber-400 to-amber-600 px-2.5 py-1 text-[10px] font-black uppercase text-black">
                            Pilihan
                        </span>
                    @endif
                </div>

                <h1 class="mt-4 text-2xl font-black text-text sm:text-4xl leading-snug tracking-tight">
                    {{ $article->judul }}
                </h1>

                {{-- Ringkasan / Lead Text --}}
                <div class="mt-5 rounded-2xl border-l-4 border-amber-400 bg-amber-500/5 p-4 text-xs sm:text-sm leading-relaxed text-slate-700 dark:text-slate-200">
                    {{ $article->ringkasan }}
                </div>

                {{-- Konten Utama (Markdown Aman) --}}
                <div class="prose prose-sm dark:prose-invert mt-8 max-w-none text-xs sm:text-sm text-text leading-relaxed space-y-4">
                    {!! $article->kontenHtml() !!}
                </div>

                {{-- Bagikan ke WhatsApp --}}
                <div class="mt-10 flex flex-wrap items-center justify-between gap-4 border-t border-border dark:border-white/10 pt-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-amber-500">Bagikan warta ini</p>
                        <p class="mt-0.5 text-xs text-text-muted">Beri tahu teman atau keluarga tentang kabar ini.</p>
                    </div>

                    <a href="{{ $article->shareWhatsAppUrl() }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-5 text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500 hover:text-white dark:hover:text-black transition shadow-sm">
                        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2z"/>
                        </svg>
                        <span>Kirim lewat WhatsApp</span>
                    </a>
                </div>
            </div>
        </article>

        {{-- Artikel Terkait --}}
        @if ($relatedArticles->isNotEmpty())
            <div class="mt-12 border-t border-border dark:border-white/10 pt-8">
                <h2 class="text-base font-black uppercase tracking-wider text-amber-500">Kabar terkait lainnya</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    @foreach ($relatedArticles as $rel)
                        <a href="{{ route('articles.show', ['slug' => $rel->slug]) }}"
                           class="rounded-2xl border border-border dark:border-white/10 bg-surface-2/60 dark:bg-surface/60 p-4 shadow-sm transition hover:border-amber-400/50 hover:-translate-y-0.5">
                            <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">{{ $rel->kategori }}</span>
                            <h3 class="mt-1.5 text-xs font-bold text-text line-clamp-2 leading-snug hover:text-amber-500 dark:hover:text-amber-400">{{ $rel->judul }}</h3>
                            <p class="mt-1.5 text-[11px] text-text-muted">{{ ($rel->tanggal_terbit ?: $rel->created_at)->locale('id')->translatedFormat('d F Y') }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
