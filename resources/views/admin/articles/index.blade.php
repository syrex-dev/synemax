@extends('layouts.admin')

@section('title', 'Kabar Bioskop')

@section('content')
    <div>
        <x-admin.page-header
            eyebrow="Publikasi & Komunikasi"
            title="Kabar Bioskop"
            subtitle="Tulis dan kelola berita seputar bioskop serta pengumuman fasilitas untuk penonton."
        >
            <a href="{{ route('admin.articles.create') }}" class="inline-flex min-h-[42px] items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition-colors">
                <span class="text-base leading-none">+</span>
                <span>Tulis Artikel Baru</span>
            </a>
        </x-admin.page-header>

        {{-- Filters: Kategori & Status --}}
        <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.articles.index', array_filter(['status' => $currentStatus])) }}" class="inline-flex min-h-[38px] items-center rounded-xl px-4 text-xs font-bold transition-colors {{ empty($currentCategory) ? 'bg-amber-500 text-slate-950 shadow-xs' : 'border border-slate-300 dark:border-white/10 bg-surface text-text-muted hover:text-text' }}">
                    Semua Kategori
                </a>
                <a href="{{ route('admin.articles.index', array_filter(['kategori' => 'Berita', 'status' => $currentStatus])) }}" class="inline-flex min-h-[38px] items-center rounded-xl px-4 text-xs font-bold transition-colors {{ $currentCategory === 'Berita' ? 'bg-amber-500 text-slate-950 shadow-xs' : 'border border-slate-300 dark:border-white/10 bg-surface text-text-muted hover:text-text' }}">
                    Berita
                </a>
                <a href="{{ route('admin.articles.index', array_filter(['kategori' => 'Pengumuman', 'status' => $currentStatus])) }}" class="inline-flex min-h-[38px] items-center rounded-xl px-4 text-xs font-bold transition-colors {{ $currentCategory === 'Pengumuman' ? 'bg-amber-500 text-slate-950 shadow-xs' : 'border border-slate-300 dark:border-white/10 bg-surface text-text-muted hover:text-text' }}">
                    Pengumuman
                </a>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.articles.index', array_filter(['kategori' => $currentCategory])) }}" class="inline-flex min-h-[36px] items-center rounded-lg px-3 text-xs font-semibold transition-colors {{ empty($currentStatus) ? 'bg-surface-2 text-text font-bold' : 'text-text-muted hover:text-text' }}">
                    Semua Status
                </a>
                <a href="{{ route('admin.articles.index', array_filter(['status' => 'terbit', 'kategori' => $currentCategory])) }}" class="inline-flex min-h-[36px] items-center rounded-lg px-3 text-xs font-semibold transition-colors {{ $currentStatus === 'terbit' ? 'bg-surface-2 text-text font-bold' : 'text-text-muted hover:text-text' }}">
                    Terbit
                </a>
                <a href="{{ route('admin.articles.index', array_filter(['status' => 'draft', 'kategori' => $currentCategory])) }}" class="inline-flex min-h-[36px] items-center rounded-lg px-3 text-xs font-semibold transition-colors {{ $currentStatus === 'draft' ? 'bg-surface-2 text-text font-bold' : 'text-text-muted hover:text-text' }}">
                    Draft
                </a>
            </div>
        </div>

        @if ($articles->isEmpty())
            <div class="mt-6 rounded-2xl border border-border bg-surface shadow-token">
                <x-admin.empty-state
                    title="Belum ada artikel publikasi"
                    description="Tulis berita seputar bioskop mini atau pengumuman fasilitas untuk penonton."
                    actionUrl="{{ route('admin.articles.create') }}"
                    actionLabel="Tulis Artikel Baru"
                />
            </div>
        @else
            {{-- DESKTOP TABLE VIEW (1024px and up) --}}
            <div class="mt-6 hidden lg:block admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="col-title-flex">Artikel & Ringkasan</th>
                            <th class="col-nowrap">Kategori</th>
                            <th class="col-nowrap">Status Publikasi</th>
                            <th class="col-nowrap">Tanggal Terbit</th>
                            <th class="col-actions-sticky">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($articles as $article)
                            <tr>
                                <td class="col-title-flex">
                                    <div class="flex items-center gap-3">
                                        @if ($article->cover_path)
                                            <img src="{{ $article->coverUrl() }}" alt="" class="h-12 w-20 shrink-0 rounded-lg object-cover border border-border shadow-xs">
                                        @else
                                            <div class="flex h-12 w-20 shrink-0 items-center justify-center rounded-lg border border-border bg-surface-2 text-[10px] font-semibold text-text-muted text-center p-1 leading-tight">
                                                Tanpa gambar
                                            </div>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5">
                                                <a href="{{ route('admin.articles.edit', $article) }}" class="font-bold text-text hover:text-amber-500 line-clamp-2 leading-snug" title="{{ $article->judul }}">
                                                    {{ $article->judul }}
                                                </a>
                                            </div>
                                            <p class="mt-0.5 line-clamp-1 text-xs text-text-muted">{{ $article->ringkasan }}</p>
                                            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                                @if ($article->is_pinned)
                                                    <span class="inline-flex items-center rounded-md bg-amber-500/15 px-1.5 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                                        Disematkan
                                                    </span>
                                                @endif
                                                <span class="font-mono text-[11px] text-text-muted truncate max-w-[220px]" title="/kabar/{{ $article->slug }}">/kabar/{{ $article->slug }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="col-nowrap">
                                    @if ($article->kategori === 'Berita')
                                        <span class="inline-flex items-center rounded-full bg-blue-500/15 px-2.5 py-0.5 text-xs font-bold text-blue-500 border border-blue-500/30">Berita</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-500/15 px-2.5 py-0.5 text-xs font-bold text-amber-500 border border-amber-500/30">Pengumuman</span>
                                    @endif
                                </td>
                                <td class="col-nowrap">
                                    @if ($article->status_publikasi === 'draft')
                                        <span class="inline-flex items-center rounded-full bg-slate-200 dark:bg-white/10 px-2.5 py-0.5 text-xs font-semibold text-text-muted">Draft</span>
                                    @elseif ($article->isScheduled())
                                        <span class="inline-flex items-center rounded-full bg-purple-500/15 px-2.5 py-0.5 text-xs font-bold text-purple-500 border border-purple-500/30">
                                            Terjadwal ({{ format_tanggal_pendek_id($article->tanggal_terbit) }} {{ format_jam_id($article->tanggal_terbit) }})
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-success/15 px-2.5 py-0.5 text-xs font-bold text-success border border-success/30">Terbit</span>
                                    @endif
                                </td>
                                <td class="col-nowrap text-xs text-text-muted">
                                    {{ $article->tanggal_terbit ? format_tanggal_id($article->tanggal_terbit) . ' ' . format_jam_id($article->tanggal_terbit) : '-' }}
                                </td>
                                <td class="col-actions-sticky">
                                    <x-admin.action-menu
                                        editUrl="{{ route('admin.articles.edit', $article) }}"
                                        viewUrl="{{ route('articles.show', ['slug' => $article->slug]) }}"
                                        deleteUrl="{{ route('admin.articles.destroy', $article) }}"
                                        deleteMessage="Hapus artikel '{{ addslashes($article->judul) }}'?"
                                        itemName="artikel"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- MOBILE STACKED CARDS VIEW (< 1024px) --}}
            <div class="mt-6 grid gap-4 lg:hidden">
                @foreach ($articles as $article)
                    <div class="rounded-2xl border border-border bg-surface p-4 shadow-token space-y-3">
                        <div class="flex items-start gap-3">
                            @if ($article->cover_path)
                                <img src="{{ $article->coverUrl() }}" alt="" class="size-14 shrink-0 rounded-xl object-cover border border-border">
                            @else
                                <div class="flex size-14 shrink-0 items-center justify-center rounded-xl border border-border bg-surface-2 text-[10px] font-semibold text-text-muted text-center p-1 leading-tight">
                                    Tanpa gambar
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('admin.articles.edit', $article) }}" class="font-bold text-text hover:text-amber-500 leading-snug">
                                    {{ $article->judul }}
                                </a>
                                <p class="text-xs text-text-muted line-clamp-1 mt-0.5">{{ $article->ringkasan }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                    @if ($article->kategori === 'Berita')
                                        <span class="inline-flex items-center rounded-full bg-blue-500/15 px-2 py-0.5 text-[11px] font-bold text-blue-500">Berita</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-500/15 px-2 py-0.5 text-[11px] font-bold text-amber-500">Pengumuman</span>
                                    @endif

                                    @if ($article->status_publikasi === 'draft')
                                        <span class="inline-flex items-center rounded-full bg-slate-200 dark:bg-white/10 px-2 py-0.5 text-[11px] font-semibold text-text-muted">Draft</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-success/15 px-2 py-0.5 text-[11px] font-bold text-success">Terbit</span>
                                    @endif

                                    @if ($article->is_pinned)
                                        <span class="inline-flex items-center rounded-md bg-amber-500/15 px-1.5 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-400">
                                            Disematkan
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t border-border pt-3 text-xs">
                            <span class="text-text-muted">{{ $article->tanggal_terbit ? format_tanggal_id($article->tanggal_terbit) : '-' }}</span>
                            <x-admin.action-menu
                                editUrl="{{ route('admin.articles.edit', $article) }}"
                                viewUrl="{{ route('articles.show', ['slug' => $article->slug]) }}"
                                deleteUrl="{{ route('admin.articles.destroy', $article) }}"
                                deleteMessage="Hapus artikel '{{ addslashes($article->judul) }}'?"
                                itemName="artikel"
                            />
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $articles->links() }}
            </div>
        @endif
    </div>
@endsection
