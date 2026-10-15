@extends('layouts.admin')

@section('title', 'Kelola Kabar & Promo')

@section('content')
    <div>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-accent">Publikasi & Komunikasi</p>
                <h1 class="mt-1 text-3xl font-black text-text">Kabar & Promo</h1>
            </div>
            <a href="{{ route('admin.articles.create') }}" class="inline-flex min-h-12 items-center gap-2 rounded-xl bg-accent px-5 font-bold text-accent-contrast shadow-sm hover:opacity-90">
                <span class="text-lg">+</span>
                <span>Tulis Artikel Baru</span>
            </a>
        </div>

        {{-- Filters --}}
        <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
            {{-- Category Filter --}}
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.articles.index', array_filter(['status' => $currentStatus])) }}" class="inline-flex min-h-10 items-center rounded-xl px-4 text-xs font-bold transition {{ empty($currentCategory) ? 'bg-accent text-accent-contrast shadow-xs' : 'border border-border bg-surface text-text-muted hover:text-text' }}">
                    Semua Kategori
                </a>
                <a href="{{ route('admin.articles.index', array_filter(['kategori' => 'Berita', 'status' => $currentStatus])) }}" class="inline-flex min-h-10 items-center rounded-xl px-4 text-xs font-bold transition {{ $currentCategory === 'Berita' ? 'bg-accent text-accent-contrast shadow-xs' : 'border border-border bg-surface text-text-muted hover:text-text' }}">
                    Berita
                </a>
                <a href="{{ route('admin.articles.index', array_filter(['kategori' => 'Promo', 'status' => $currentStatus])) }}" class="inline-flex min-h-10 items-center rounded-xl px-4 text-xs font-bold transition {{ $currentCategory === 'Promo' ? 'bg-accent text-accent-contrast shadow-xs' : 'border border-border bg-surface text-text-muted hover:text-text' }}">
                    Promo
                </a>
                <a href="{{ route('admin.articles.index', array_filter(['kategori' => 'Pengumuman', 'status' => $currentStatus])) }}" class="inline-flex min-h-10 items-center rounded-xl px-4 text-xs font-bold transition {{ $currentCategory === 'Pengumuman' ? 'bg-accent text-accent-contrast shadow-xs' : 'border border-border bg-surface text-text-muted hover:text-text' }}">
                    Pengumuman
                </a>
            </div>

            {{-- Status Filter --}}
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.articles.index', array_filter(['kategori' => $currentCategory])) }}" class="inline-flex min-h-9 items-center rounded-lg px-3 text-xs font-semibold transition {{ empty($currentStatus) ? 'bg-surface-2 text-text font-bold' : 'text-text-muted hover:text-text' }}">
                    Semua Status
                </a>
                <a href="{{ route('admin.articles.index', array_filter(['status' => 'terbit', 'kategori' => $currentCategory])) }}" class="inline-flex min-h-9 items-center rounded-lg px-3 text-xs font-semibold transition {{ $currentStatus === 'terbit' ? 'bg-surface-2 text-text font-bold' : 'text-text-muted hover:text-text' }}">
                    Terbit
                </a>
                <a href="{{ route('admin.articles.index', array_filter(['status' => 'draft', 'kategori' => $currentCategory])) }}" class="inline-flex min-h-9 items-center rounded-lg px-3 text-xs font-semibold transition {{ $currentStatus === 'draft' ? 'bg-surface-2 text-text font-bold' : 'text-text-muted hover:text-text' }}">
                    Draft
                </a>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="mt-6 overflow-hidden rounded-2xl border border-border bg-surface shadow-token">
            @if ($articles->isEmpty())
                <div class="p-12 text-center text-text-muted">
                    <svg class="mx-auto size-12 text-text-muted/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                    </svg>
                    <p class="mt-3 text-base font-bold text-text">Belum ada artikel publikasi</p>
                    <p class="mt-1 text-xs">Tulis berita seputar bioskop, pengumuman fasilitas, atau promo diskon tiket.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-border bg-surface-2 text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-5 py-4 font-bold">Artikel</th>
                                <th class="px-5 py-4 font-bold">Kategori</th>
                                <th class="px-5 py-4 font-bold">Status Publikasi</th>
                                <th class="px-5 py-4 font-bold">Tanggal Terbit</th>
                                <th class="px-5 py-4 text-right font-bold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($articles as $article)
                                <tr class="transition hover:bg-surface-2/60">
                                    <td class="px-5 py-4">
                                        <div class="flex items-start gap-4">
                                            @if ($article->cover_path)
                                                <img src="{{ $article->coverUrl() }}" alt="" class="h-14 w-20 flex-shrink-0 rounded-lg object-cover border border-border">
                                            @else
                                                <div class="flex h-14 w-20 flex-shrink-0 items-center justify-center rounded-lg border border-border bg-surface-2 text-xs text-text-muted">
                                                    <span>No Cover</span>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    @if ($article->is_pinned)
                                                        <span class="inline-flex items-center gap-1 rounded bg-amber-500/15 px-1.5 py-0.5 text-[10px] font-bold text-amber-500">
                                                            Disematkan
                                                        </span>
                                                    @endif
                                                    <a href="{{ route('admin.articles.edit', $article) }}" class="font-bold text-text hover:text-accent">
                                                        {{ $article->judul }}
                                                    </a>
                                                </div>
                                                <p class="mt-1 line-clamp-1 text-xs text-text-muted">{{ $article->ringkasan }}</p>
                                                <p class="mt-0.5 font-mono text-[11px] text-text-muted">/kabar/{{ $article->slug }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        @if ($article->kategori === 'Berita')
                                            <span class="rounded-full bg-blue-500/15 px-2.5 py-1 text-xs font-bold text-blue-500">Berita</span>
                                        @elseif ($article->kategori === 'Promo')
                                            <span class="rounded-full bg-emerald-500/15 px-2.5 py-1 text-xs font-bold text-emerald-500">Promo</span>
                                        @else
                                            <span class="rounded-full bg-amber-500/15 px-2.5 py-1 text-xs font-bold text-amber-500">Pengumuman</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        @if ($article->status_publikasi === 'draft')
                                            <span class="rounded-full bg-surface-2 px-2.5 py-1 text-xs font-bold text-text-muted">Draft</span>
                                        @elseif ($article->isScheduled())
                                            <span class="rounded-full bg-purple-500/15 px-2.5 py-1 text-xs font-bold text-purple-500">
                                                Terjadwal ({{ $article->tanggal_terbit->translatedFormat('d M H:i') }})
                                            </span>
                                        @else
                                            <span class="rounded-full bg-emerald-500/15 px-2.5 py-1 text-xs font-bold text-emerald-500">Terbit</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-xs text-text-muted">
                                        {{ $article->tanggal_terbit ? $article->tanggal_terbit->translatedFormat('d F Y, H:i') : '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            <a href="{{ route('articles.show', ['slug' => $article->slug]) }}" target="_blank" title="Pratinjau Publik" class="inline-flex px-2.5 py-1.5 text-xs font-semibold items-center justify-center rounded-lg border border-border bg-surface text-text hover:border-accent hover:text-accent">
                                                Lihat
                                            </a>
                                            <a href="{{ route('admin.articles.edit', $article) }}" title="Ubah Artikel" class="inline-flex px-2.5 py-1.5 text-xs font-semibold items-center justify-center rounded-lg border border-border bg-surface text-text hover:border-accent hover:text-accent">
                                                Edit
                                            </a>
                                            <form method="POST" action="{{ route('admin.articles.destroy', $article) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus artikel \'{{ addslashes($article->judul) }}\'?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Hapus Artikel" class="inline-flex px-2.5 py-1.5 text-xs font-semibold items-center justify-center rounded-lg border border-border bg-surface text-danger hover:border-danger hover:bg-danger/10">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($articles->hasPages())
                    <div class="border-t border-border p-4">
                        {{ $articles->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
