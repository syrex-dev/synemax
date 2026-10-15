@extends('layouts.public')

@section('title', $article->judul . ' · SynemaX Home Cinema')

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
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 sm:py-12">
        {{-- Breadcrumb --}}
        <nav aria-label="Breadcrumb" class="mb-6 flex items-center gap-2 text-xs text-text-muted">
            <a href="{{ route('home') }}" class="hover:text-text">Beranda</a>
            <span>/</span>
            <a href="{{ route('articles.index') }}" class="hover:text-text">Kabar & Promo</a>
            <span>/</span>
            <span class="truncate font-semibold text-text">{{ $article->judul }}</span>
        </nav>

        {{-- Preview Banner for Admin --}}
        @if ($isPreview)
            <div role="alert" class="mb-6 flex items-center gap-3 rounded-2xl border border-warning/40 bg-warning/10 p-4 text-xs font-bold text-warning">
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>Mode Pratinjau Admin: Artikel ini belum terbit secara publik (status draft atau jadwal terbit di masa depan).</span>
            </div>
        @endif

        {{-- Article Main Card --}}
        <article class="overflow-hidden rounded-3xl border border-border bg-surface shadow-token">
            {{-- Cover Image --}}
            @if ($article->cover_path)
                <div class="relative max-h-[420px] w-full overflow-hidden bg-neutral-900">
                    <img src="{{ $article->coverUrl() }}" alt="Cover {{ $article->judul }}" class="size-full max-h-[420px] object-cover">
                </div>
            @endif

            <div class="p-6 sm:p-10">
                {{-- Meta Header --}}
                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded-full px-3 py-1 text-xs font-bold shadow-xs {{ $article->kategoriBadgeClasses() }}">
                        {{ $article->kategori }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-xs text-text-muted">
                        <svg class="size-4 shrink-0 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>{{ ($article->tanggal_terbit ?: $article->created_at)->translatedFormat('l, d F Y') }}</span>
                    </span>
                    @if ($article->is_pinned)
                        <span class="rounded-full bg-accent/15 px-2.5 py-0.5 text-[11px] font-bold text-accent">
                            Artikel Pilihan
                        </span>
                    @endif
                </div>

                <h1 class="mt-4 text-2xl font-black text-text sm:text-3xl lg:text-4xl leading-tight">
                    {{ $article->judul }}
                </h1>

                {{-- Summary Lead --}}
                <div class="mt-4 rounded-2xl border-l-4 border-accent bg-surface-2 p-4 text-sm font-medium leading-relaxed text-text">
                    {{ $article->ringkasan }}
                </div>

                {{-- Main Content (Rendered Safe Markdown) --}}
                <div class="prose prose-sm sm:prose-base dark:prose-invert mt-8 max-w-none text-text leading-relaxed">
                    {!! $article->kontenHtml() !!}
                </div>

                {{-- Share Box --}}
                <div class="mt-10 flex flex-wrap items-center justify-between gap-4 border-t border-border pt-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Bagikan Artikel Ini</p>
                        <p class="text-xs text-text-muted">Informasikan promo atau kabar ini ke teman nontonmu.</p>
                    </div>

                    <a href="{{ $article->shareWhatsAppUrl() }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-success/40 bg-success/10 px-4 text-xs font-bold text-success hover:bg-success/20">
                        <svg class="size-4 text-success" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2zm.01 1.67c2.2 0 4.26.86 5.82 2.42a8.17 8.17 0 012.41 5.82c0 4.54-3.7 8.24-8.24 8.24-1.45 0-2.88-.38-4.13-1.12l-.3-.18-3.08.81.82-3-.19-.31a8.16 8.16 0 01-1.25-4.44c0-4.54 3.7-8.24 8.24-8.24zm4.52 11.66c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.98-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.25-1.5-1.4-1.75-.15-.25-.02-.39.11-.51.11-.11.25-.29.38-.44.12-.14.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.35-.77-1.85-.2-.49-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1s.9 2.43 1.03 2.6c.13.17 1.77 2.7 4.28 3.79.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.15-1.18-.06-.1-.23-.17-.48-.29z"/>
                        </svg>
                        <span>Bagikan ke WhatsApp</span>
                    </a>
                </div>
            </div>
        </article>

        {{-- Related Articles --}}
        @if ($relatedArticles->isNotEmpty())
            <div class="mt-12 border-t border-border pt-8">
                <h3 class="text-xl font-black text-text">Kabar Terkait Lainnya</h3>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    @foreach ($relatedArticles as $rel)
                        <a href="{{ route('articles.show', ['slug' => $rel->slug]) }}" class="group rounded-2xl border border-border bg-surface p-4 transition hover:border-accent">
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $rel->kategoriBadgeClasses() }}">{{ $rel->kategori }}</span>
                            <h4 class="mt-2 text-sm font-bold text-text group-hover:text-accent line-clamp-2 leading-snug">{{ $rel->judul }}</h4>
                            <p class="mt-1 text-[11px] text-text-muted">{{ ($rel->tanggal_terbit ?: $rel->created_at)->translatedFormat('d M Y') }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
