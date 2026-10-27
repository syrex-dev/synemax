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
            <a href="{{ route('articles.index') }}" class="inline-flex min-h-11 items-center gap-1.5 text-xs font-bold text-primary hover:underline">
                Kembali ke kabar dan promo
            </a>
        </div>

        {{-- Alert Pratinjau Admin --}}
        @if ($isPreview)
            <div role="alert" class="mb-5 rounded-[var(--radius-control)] border border-border-input bg-surface-2 p-4 text-xs text-text">
                <span class="font-bold">Mode Pratinjau Admin</span>: Artikel ini belum terbit secara publik.
            </div>
        @endif

        {{-- Kartu Utama Artikel --}}
        <article class="rounded-[var(--radius-control)] border border-border bg-surface p-5 sm:p-8 shadow-xs">
            {{-- Cover Image --}}
            @if ($article->cover_path)
                <div class="relative mb-6 max-h-[380px] w-full overflow-hidden rounded-[var(--radius-control)] bg-surface-2">
                    <img src="{{ $article->coverUrl() }}" alt="Cover {{ $article->judul }}" class="size-full max-h-[380px] object-cover rounded-[var(--radius-control)]">
                </div>
            @endif

            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-[var(--radius-control)] border border-border-input bg-surface px-2.5 py-0.5 text-xs font-bold text-text">
                        {{ $article->kategori }}
                    </span>
                    <span class="text-xs text-text-muted">
                        {{ ($article->tanggal_terbit ?: $article->created_at)->locale('id')->translatedFormat('l, d F Y') }}
                    </span>
                    @if ($article->is_pinned)
                        <span class="rounded-[var(--radius-control)] bg-primary px-2.5 py-0.5 text-xs font-bold text-white">
                            Pilihan
                        </span>
                    @endif
                </div>

                <h1 class="mt-3 text-2xl font-black text-text sm:text-3xl leading-snug">
                    {{ $article->judul }}
                </h1>

                {{-- Ringkasan / Lead Text --}}
                <div class="mt-4 rounded-[var(--radius-control)] border-l-2 border-primary bg-surface-2 p-3.5 text-xs sm:text-sm leading-relaxed text-text">
                    {{ $article->ringkasan }}
                </div>

                {{-- Konten Utama (Markdown Aman) --}}
                <div class="prose prose-sm dark:prose-invert mt-6 max-w-none text-xs sm:text-sm text-text leading-relaxed">
                    {!! $article->kontenHtml() !!}
                </div>

                {{-- Bagikan ke WhatsApp --}}
                <div class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-border pt-5">
                    <div>
                        <p class="text-xs font-bold text-text">Bagikan artikel ini</p>
                        <p class="text-xs text-text-muted">Beri tahu teman atau keluarga tentang kabar ini.</p>
                    </div>

                    <a href="{{ $article->shareWhatsAppUrl() }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] bg-primary px-4 text-xs font-bold text-white shadow-xs hover:opacity-95">
                        Kirim lewat WhatsApp
                    </a>
                </div>
            </div>
        </article>

        {{-- Artikel Terkait --}}
        @if ($relatedArticles->isNotEmpty())
            <div class="mt-10 border-t border-border pt-6">
                <h2 class="text-base font-bold text-text">Kabar terkait lainnya</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    @foreach ($relatedArticles as $rel)
                        <a href="{{ route('articles.show', ['slug' => $rel->slug]) }}"
                           class="rounded-[var(--radius-control)] border border-border bg-surface p-3.5 shadow-xs transition hover:border-primary">
                            <span class="text-[10px] font-bold text-primary">{{ $rel->kategori }}</span>
                            <h3 class="mt-1 text-xs font-bold text-text line-clamp-2 leading-snug hover:text-primary">{{ $rel->judul }}</h3>
                            <p class="mt-1 text-[11px] text-text-muted">{{ ($rel->tanggal_terbit ?: $rel->created_at)->locale('id')->translatedFormat('d F Y') }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
