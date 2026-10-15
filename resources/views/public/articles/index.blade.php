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
        {{-- Header Halaman --}}
        <div class="rounded-[var(--radius-control)] border border-border bg-surface p-5 sm:p-8 shadow-xs">
            <span class="inline-flex items-center rounded-[var(--radius-control)] bg-surface-2 px-2.5 py-1 text-xs font-bold text-primary">
                Warta studio
            </span>
            <h1 class="mt-2 text-2xl font-black text-text sm:text-3xl">Kabar dan promo</h1>
            <p class="mt-1.5 max-w-xl text-xs sm:text-sm text-text-muted leading-relaxed">
                Temukan pengumuman terkini, promo sesi bioskop mini, dan informasi seputar SynemaX.
            </p>

            {{-- Filter Kategori --}}
            <div class="mt-5 flex flex-wrap gap-2 border-t border-border pt-5">
                <a href="{{ route('articles.index') }}" class="inline-flex min-h-10 items-center rounded-[var(--radius-control)] px-3.5 text-xs font-bold transition {{ empty($currentCategory) ? 'bg-primary text-white shadow-xs' : 'border-[1.5px] border-border-input bg-surface text-text hover:border-primary' }}">
                    Semua kategori
                </a>
                @foreach (['Berita', 'Promo', 'Pengumuman'] as $cat)
                    <a href="{{ route('articles.index', ['kategori' => $cat]) }}" class="inline-flex min-h-10 items-center rounded-[var(--radius-control)] px-3.5 text-xs font-bold transition {{ $currentCategory === $cat ? 'bg-primary text-white shadow-xs' : 'border-[1.5px] border-border-input bg-surface text-text hover:border-primary' }}">
                        {{ $cat }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Daftar Artikel --}}
        <div class="mt-8">
            @if ($articles->isEmpty())
                <div class="rounded-[var(--radius-control)] border border-dashed border-border bg-surface p-10 text-center">
                    <h2 class="text-base font-bold text-text">Belum ada artikel</h2>
                    <p class="mt-1 text-xs text-text-muted">Kabar atau promo untuk kategori ini belum tersedia. Silakan cek kembali nanti.</p>
                </div>
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($articles as $item)
                        <article class="flex flex-col justify-between rounded-[var(--radius-control)] border border-border bg-surface p-4 sm:p-5 shadow-xs transition hover:border-primary">
                            <div>
                                {{-- Cover --}}
                                @if ($item->cover_path)
                                    <div class="relative aspect-[16/9] w-full overflow-hidden rounded-[var(--radius-control)] bg-surface-2 mb-4">
                                        <img src="{{ $item->coverUrl() }}" alt="Cover {{ $item->judul }}" class="size-full object-cover rounded-[var(--radius-control)]" loading="lazy">
                                        <div class="absolute left-2.5 top-2.5 flex flex-wrap gap-1.5">
                                            <span class="rounded-[var(--radius-control)] border border-border-input bg-surface/95 px-2 py-0.5 text-[10px] font-bold text-text shadow-xs">
                                                {{ $item->kategori }}
                                            </span>
                                            @if ($item->is_pinned)
                                                <span class="rounded-[var(--radius-control)] bg-primary px-2 py-0.5 text-[10px] font-bold text-white shadow-xs">
                                                    Pilihan
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <div>
                                    <p class="text-[11px] font-bold text-text-muted">
                                        {{ ($item->tanggal_terbit ?: $item->created_at)->locale('id')->translatedFormat('d F Y') }}
                                    </p>

                                    <h2 class="mt-1 text-base font-bold text-text leading-snug">
                                        <a href="{{ route('articles.show', ['slug' => $item->slug]) }}" class="hover:text-primary">
                                            {{ $item->judul }}
                                        </a>
                                    </h2>

                                    <p class="mt-2 text-xs text-text-muted leading-relaxed line-clamp-3">
                                        {{ $item->ringkasan }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 border-t border-border pt-3">
                                <a href="{{ route('articles.show', ['slug' => $item->slug]) }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-[var(--radius-control)] border-[1.5px] border-border-input bg-surface px-4 text-xs font-bold text-text transition hover:border-primary">
                                    Baca selengkapnya
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
