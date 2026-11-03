@extends('layouts.public')

@section('title', $activeTab === 'harga-faq' ? 'Harga Tiket & FAQ: SynemaX' : 'Kabar & Pengumuman: SynemaX')

@section('meta')
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $activeTab === 'harga-faq' ? 'Harga Tiket Studio & Tanya Jawab: SynemaX' : 'Kabar & Pengumuman: SynemaX' }}">
    <meta property="og:description" content="Informasi lengkap harga tiket studio mini, FAQ pemesanan, dan pengumuman terkini dari SynemaX.">
    <meta property="og:url" content="{{ route('articles.index', ['tab' => $activeTab]) }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $activeTab === 'harga-faq' ? 'Harga Tiket & FAQ: SynemaX' : 'Kabar & Pengumuman: SynemaX' }}">
    <meta name="twitter:description" content="Informasi lengkap tarif studio, tanya jawab seputar bioskop, dan warta terkini SynemaX.">
@endsection

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10">
        {{-- Header Halaman Editorial --}}
        <div class="relative overflow-hidden rounded-3xl border border-border dark:border-white/10 bg-surface dark:bg-gradient-to-br dark:from-surface dark:via-[#0C111D] dark:to-[#07090E] p-6 sm:p-8 shadow-2xl">
            <div class="absolute -right-16 -top-16 size-64 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>

            <div class="relative">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-400/40 bg-amber-500/15 px-3 py-1 text-xs font-black uppercase tracking-wider text-amber-500 dark:text-amber-300">
                    <span class="size-2 rounded-full bg-amber-400 animate-pulse"></span>
                    Warta studio
                </span>
                <h1 class="mt-2 text-2xl font-black text-text sm:text-4xl tracking-tight">
                    {{ $activeTab === 'harga-faq' ? 'Harga Tiket & Tanya Jawab' : 'Kabar & Pengumuman' }}
                </h1>
                <p class="mt-2 max-w-xl text-xs sm:text-sm text-text-muted leading-relaxed">
                    {{ $activeTab === 'harga-faq'
                        ? 'Transparansi tarif pemutaran film di Studio 1 serta panduan pertanyaan umum bagi penonton dan rombongan.'
                        : 'Temukan warta terkini seputar studio dan informasi kegiatan di SynemaX.' }}
                </p>

                {{-- TAB UTAMA: Berita vs Harga & FAQ --}}
                <div class="mt-6 flex flex-wrap gap-2.5 border-t border-border dark:border-white/10 pt-5">
                    <a href="{{ route('articles.index', ['tab' => 'berita']) }}"
                       class="inline-flex min-h-11 items-center gap-2 rounded-2xl px-5 text-xs font-black transition {{ $activeTab === 'berita' ? 'btn-gold-cinema shadow-md' : 'border border-border dark:border-white/10 bg-surface-2 dark:bg-surface text-text-muted hover:border-amber-400/50 hover:text-text' }}">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                        </svg>
                        <span>Berita</span>
                    </a>
                    <a href="{{ route('articles.index', ['tab' => 'harga-faq']) }}"
                       class="inline-flex min-h-11 items-center gap-2 rounded-2xl px-5 text-xs font-black transition {{ $activeTab === 'harga-faq' ? 'btn-gold-cinema shadow-md' : 'border border-border dark:border-white/10 bg-surface-2 dark:bg-surface text-text-muted hover:border-amber-400/50 hover:text-text' }}">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Harga & FAQ</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- TAB 1: KONTEN BERITA --}}
        @if ($activeTab === 'berita')
            {{-- Sub-Filter Kategori (Semua / Berita / Pengumuman) - Rata Tengah --}}
            <div class="mt-6 flex justify-center items-center gap-2.5 overflow-x-auto py-1 px-2 mx-auto no-scrollbar max-w-full">
                <a href="{{ route('articles.index', ['tab' => 'berita']) }}"
                   class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-xl px-4 py-2 text-xs transition {{ empty($currentCategory) ? 'filter-chip-selected' : 'filter-chip-unselected' }}">
                    Semua warta
                </a>
                @foreach (['Berita', 'Pengumuman'] as $cat)
                    <a href="{{ route('articles.index', ['tab' => 'berita', 'kategori' => $cat]) }}"
                       class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-xl px-4 py-2 text-xs transition {{ $currentCategory === $cat ? 'filter-chip-selected' : 'filter-chip-unselected' }}">
                        {{ $cat }}
                    </a>
                @endforeach
            </div>

            {{-- Daftar Kartu Artikel --}}
            <div class="mt-6">
                @if ($articles->isEmpty())
                    <div class="rounded-xl border border-dashed border-border bg-surface-2/60 p-12 text-center">
                        <span class="inline-flex size-14 items-center justify-center rounded-lg bg-surface border border-border text-amber-500 dark:text-amber-400 mb-3">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z"/></svg>
                        </span>
                        <h2 class="text-base font-bold text-text">Belum ada artikel</h2>
                        <p class="mt-1 text-xs text-text-muted max-w-md mx-auto">Kabar untuk kategori ini belum tersedia. Silakan cek kembali nanti.</p>
                    </div>
                @else
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($articles as $item)
                            <article class="flex flex-col justify-between rounded-xl border border-border bg-surface p-4 sm:p-5 transition-all duration-200 hover:border-amber-400/50 group">
                                <div>
                                    @if ($item->cover_path)
                                        <div class="relative mb-4">
                                            <x-ui.image
                                                :src="$item->coverUrl()"
                                                :alt="'Cover ' . $item->judul"
                                                :title="$item->judul"
                                                :badge="$item->kategori"
                                                aspect="aspect-[16/9]"
                                                class="w-full shadow-inner"
                                            />
                                            {{-- Clean subtle gradient overlay in dark mode only --}}
                                            <div class="pointer-events-none absolute inset-0 hidden dark:block bg-gradient-to-t from-black/80 via-transparent to-transparent rounded-2xl"></div>
                                            <div class="absolute left-3 top-3 flex flex-wrap gap-1.5 pointer-events-none">
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
                                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
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

        {{-- TAB 2: HARGA TIKET & FAQ ACCORDION --}}
        @else
            <div class="mt-8 space-y-10">
                {{-- Grid Tarif Studio & Rombongan --}}
                <div class="grid gap-6 md:grid-cols-2">
                    {{-- Tabel Harga Tiket Per Orang --}}
                    <section class="rounded-3xl border border-border dark:border-white/10 bg-surface dark:bg-[#0B0F19] p-6 sm:p-7 shadow-xl flex flex-col justify-between">
                        <div>
                            <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-amber-500">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Transparansi Tarif</span>
                            </div>
                            <h2 class="mt-1.5 text-xl font-black text-text">Harga tiket studio per orang</h2>
                            <p class="mt-1 text-xs text-text-muted">Tarif resmi per orang untuk setiap sesi penayangan di Studio 1.</p>

                            <dl class="mt-5 divide-y divide-border dark:divide-white/10 rounded-2xl border border-border dark:border-white/10 bg-surface-2/60 dark:bg-black/30 px-4">
                                <div class="flex items-center justify-between py-3.5 text-xs sm:text-sm">
                                    <dt class="text-text-muted">Hari biasa (Senin sampai Kamis)</dt>
                                    <dd class="font-tabular font-black text-amber-500 dark:text-amber-400">Rp {{ number_format($weekdayPrice, 0, ',', '.') }}</dd>
                                </div>
                                <div class="flex items-center justify-between py-3.5 text-xs sm:text-sm">
                                    <dt class="text-text-muted">Akhir pekan (Jumat sampai Minggu)</dt>
                                    <dd class="font-tabular font-black text-amber-500 dark:text-amber-400">Rp {{ number_format($weekendPrice, 0, ',', '.') }}</dd>
                                </div>
                                <div class="flex items-center justify-between py-3.5 text-xs sm:text-sm">
                                    <dt class="text-text-muted">Hari libur nasional</dt>
                                    <dd class="font-tabular font-black text-amber-500 dark:text-amber-400">Rp {{ number_format($holidayPrice, 0, ',', '.') }}</dd>
                                </div>
                            </dl>

                            <div class="mt-3.5 flex items-center gap-2 text-xs text-text-muted font-medium">
                                <span class="flex size-4 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 shrink-0">
                                    <svg class="size-3" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <span>Termasuk pendingin ruangan & audio sinematik</span>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-border dark:border-white/10">
                            <a href="{{ route('schedule.index') }}" class="btn-gold-cinema inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl px-5 text-xs font-black shadow-md whitespace-nowrap">
                                <span>Pesan Tiket Sekarang</span>
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        </div>
                    </section>

                    {{-- Paket Sewa Studio Privat 6 Orang --}}
                    <section class="rounded-3xl border border-border dark:border-white/10 bg-surface dark:bg-[#0B0F19] p-6 sm:p-7 shadow-xl flex flex-col justify-between relative overflow-hidden">
                        <div>
                            <div class="inline-flex items-center gap-1.5 text-xs font-black uppercase tracking-wider text-amber-700 dark:text-amber-400">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.504-1.125-1.125-1.125h-.871a2.25 2.25 0 00-1.591.659l-.439.44a.75.75 0 01-1.06 0l-.44-.44a2.25 2.25 0 00-1.59-.659h-.872c-.621 0-1.125.504-1.125 1.125V18.75m12 0a2.25 2.25 0 002.25-2.25V8.25A2.25 2.25 0 0018 6H6a2.25 2.25 0 00-2.25 2.25v8.25A2.25 2.25 0 006 18.75m12 0H6"/></svg>
                                <span>Eksklusif Rombongan</span>
                            </div>
                            <h2 class="mt-1.5 text-xl font-black text-text">Sewa studio privat (6 tempat)</h2>
                            <p class="mt-1 text-xs text-text-muted">Kunci seluruh 6 tempat khusus rombongan keluarga atau sahabat tanpa ada penonton lain.</p>

                            <div class="mt-5 rounded-2xl border border-amber-500/25 dark:border-amber-400/20 bg-amber-500/5 dark:bg-amber-400/5 p-4 sm:p-5">
                                <div class="flex flex-col sm:flex-row sm:items-baseline sm:justify-between gap-1 pb-3.5 border-b border-amber-500/15 dark:border-amber-400/15">
                                    <span class="text-xs font-bold text-slate-700 dark:text-amber-200">Biaya sewa penuh 1 sesi:</span>
                                    <span class="font-tabular text-xl sm:text-2xl font-black text-amber-700 dark:text-amber-400">Rp {{ number_format($privatePrice, 0, ',', '.') }}</span>
                                </div>
                                <ul class="mt-3.5 space-y-2 text-xs text-slate-700 dark:text-slate-300 font-medium">
                                    <li class="flex items-center gap-2.5">
                                        <span class="flex size-4 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 shrink-0">
                                            <svg class="size-3" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                        <span>Bebas pilih film dari katalog tersedia</span>
                                    </li>
                                    <li class="flex items-center gap-2.5">
                                        <span class="flex size-4 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 shrink-0">
                                            <svg class="size-3" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                        <span>Privasi total untuk 1 rombongan keluarga</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-border dark:border-white/10">
                            <a href="{{ route('schedule.index') }}" class="btn-gold-cinema inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl px-5 text-xs font-black shadow-md whitespace-nowrap">
                                <span>Pilih Jadwal Sewa Privat</span>
                                <svg class="size-4" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                            </a>
                        </div>
                    </section>
                </div>

                {{-- Panduan Cara Pesan Tiket --}}
                <section class="rounded-3xl border border-border dark:border-white/10 bg-surface dark:bg-[#0B0F19] p-6 sm:p-8 shadow-xl">
                    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 pb-6 border-b border-border dark:border-white/10">
                        <div>
                            <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-amber-500">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                                <span>Alur Pemesanan</span>
                            </div>
                            <h2 class="mt-1.5 text-xl sm:text-2xl font-black text-text">Cara pesan tiket bioskop SynemaX</h2>
                            <p class="mt-1 text-xs text-text-muted">Ikuti 4 langkah praktis berikut untuk memesan tiket online tanpa perlu antre.</p>
                        </div>
                        <a href="{{ route('schedule.index') }}" class="btn-gold-cinema inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-xl px-5 text-xs font-black shadow-md">
                            <span>Mulai Pesan Tiket</span>
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {{-- Langkah 1 --}}
                        <div class="rounded-2xl border border-border dark:border-white/10 bg-surface-2/40 dark:bg-black/20 p-5 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="flex size-7 items-center justify-center rounded-xl bg-amber-500/15 text-xs font-black text-amber-600 dark:text-amber-400">01</span>
                                    <svg class="size-5 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                    </svg>
                                </div>
                                <h3 class="mt-4 text-sm font-bold text-text">Pilih Film atau Jadwal</h3>
                                <p class="mt-1.5 text-xs text-text-muted leading-relaxed">Pilih film favorit dari katalog atau tentukan tanggal dan jam pemutaran di halaman jadwal.</p>
                            </div>
                        </div>

                        {{-- Langkah 2 --}}
                        <div class="rounded-2xl border border-border dark:border-white/10 bg-surface-2/40 dark:bg-black/20 p-5 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="flex size-7 items-center justify-center rounded-xl bg-amber-500/15 text-xs font-black text-amber-600 dark:text-amber-400">02</span>
                                    <svg class="size-5 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                    </svg>
                                </div>
                                <h3 class="mt-4 text-sm font-bold text-text">Tentukan Penonton</h3>
                                <p class="mt-1.5 text-xs text-text-muted leading-relaxed">Pilih jumlah tiket per orang atau kunci seluruh 6 tempat lewat opsi sewa studio privat.</p>
                            </div>
                        </div>

                        {{-- Langkah 3 --}}
                        <div class="rounded-2xl border border-border dark:border-white/10 bg-surface-2/40 dark:bg-black/20 p-5 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="flex size-7 items-center justify-center rounded-xl bg-amber-500/15 text-xs font-black text-amber-600 dark:text-amber-400">03</span>
                                    <svg class="size-5 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 class="mt-4 text-sm font-bold text-text">Pilih Camilan (Opsional)</h3>
                                <p class="mt-1.5 text-xs text-text-muted leading-relaxed">Tambah aneka snack hangat dan minuman segar untuk dinikmati langsung saat menonton.</p>
                            </div>
                        </div>

                        {{-- Langkah 4 --}}
                        <div class="rounded-2xl border border-border dark:border-white/10 bg-surface-2/40 dark:bg-black/20 p-5 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="flex size-7 items-center justify-center rounded-xl bg-amber-500/15 text-xs font-black text-amber-600 dark:text-amber-400">04</span>
                                    <svg class="size-5 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 class="mt-4 text-sm font-bold text-text">Bayar & Terima Tiket</h3>
                                <p class="mt-1.5 text-xs text-text-muted leading-relaxed">Isi nama & WhatsApp, selesaikan via QRIS atau Transfer. E-tiket instan langsung terbit.</p>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Accordion FAQ --}}
                <section class="rounded-3xl border border-border dark:border-white/10 bg-surface dark:bg-[#0B0F19] p-6 sm:p-8 shadow-xl">
                    <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-amber-500">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Tanya Jawab</span>
                    </div>
                    <h2 class="mt-1.5 text-xl sm:text-2xl font-black text-text">Pertanyaan yang sering diajukan</h2>
                    <p class="mt-1 text-xs text-text-muted">Semua jawaban penting seputar pemesanan tiket, fasilitas, dan tata tertib studio.</p>

                    <div class="mt-6 space-y-3">
                        <details class="group rounded-2xl border border-border dark:border-white/10 bg-surface-2/40 dark:bg-black/20 p-4 transition-all" open>
                            <summary class="cursor-pointer font-bold text-sm text-text flex items-center justify-between">
                                <span>Bagaimana cara memesan tiket di SynemaX?</span>
                                <span class="text-amber-500 text-xs transition duration-200 group-open:rotate-180">▼</span>
                            </summary>
                            <div class="mt-2.5 text-xs leading-relaxed text-text-muted border-t border-border/50 dark:border-white/5 pt-2.5 space-y-2">
                                <p>Pemesanan tiket bioskop SynemaX dapat dilakukan langsung secara online melalui 4 langkah mudah tanpa perlu membuat akun:</p>
                                <ol class="list-decimal list-inside space-y-1 pl-1">
                                    <li><strong class="text-text">Pilih film & jadwal:</strong> Buka menu Katalog Film untuk memilih film favorit atau langsung ke Jadwal Tayang untuk memilih sesi yang kamu inginkan.</li>
                                    <li><strong class="text-text">Tentukan jumlah penonton:</strong> Pilih jumlah tiket per orang atau gunakan opsi Sewa Studio Privat (6 tempat) untuk rombongan keluarga atau teman.</li>
                                    <li><strong class="text-text">Pilih menu jajan (opsional):</strong> Tambahkan camilan hangat dan minuman segar untuk dinikmati selama pemutaran film.</li>
                                    <li><strong class="text-text">Selesaikan pembayaran:</strong> Isi nama dan nomor WhatsApp aktif, lalu bayar via QRIS instan atau Transfer Bank. E-tiket resmi ber-QR Code akan langsung terbit di layar dan tersimpan di menu Cek Booking.</li>
                                </ol>
                            </div>
                        </details>

                        <details class="group rounded-2xl border border-border dark:border-white/10 bg-surface-2/40 dark:bg-black/20 p-4 transition-all">
                            <summary class="cursor-pointer font-bold text-sm text-text flex items-center justify-between">
                                <span>Perlu membuat akun sebelum memesan?</span>
                                <span class="text-amber-500 text-xs transition duration-200 group-open:rotate-180">▼</span>
                            </summary>
                            <p class="mt-2.5 text-xs leading-relaxed text-text-muted border-t border-border/50 dark:border-white/5 pt-2.5">
                                Tidak perlu. Anda cukup mengisi nama dan nomor WhatsApp saat pemesanan. E-tiket instan dan kode booking akan langsung diterbitkan ke layar dan dapat diakses kembali lewat menu Cek Booking.
                            </p>
                        </details>

                        <details class="group rounded-2xl border border-border dark:border-white/10 bg-surface-2/40 dark:bg-black/20 p-4 transition-all">
                            <summary class="cursor-pointer font-bold text-sm text-text flex items-center justify-between">
                                <span>Bisa memesan seluruh studio untuk rombongan?</span>
                                <span class="text-amber-500 text-xs transition duration-200 group-open:rotate-180">▼</span>
                            </summary>
                            <p class="mt-2.5 text-xs leading-relaxed text-text-muted border-t border-border/50 dark:border-white/5 pt-2.5">
                                Bisa sekali. Pilih opsi Sewa Studio Privat pada formulir pemesanan jadwal untuk mengunci seluruh 6 tempat khusus rombongan Anda tanpa ada penonton lain di studio.
                            </p>
                        </details>

                        <details class="group rounded-2xl border border-border dark:border-white/10 bg-surface-2/40 dark:bg-black/20 p-4 transition-all">
                            <summary class="cursor-pointer font-bold text-sm text-text flex items-center justify-between">
                                <span>Bagaimana cara melakukan pembayaran?</span>
                                <span class="text-amber-500 text-xs transition duration-200 group-open:rotate-180">▼</span>
                            </summary>
                            <p class="mt-2.5 text-xs leading-relaxed text-text-muted border-t border-border/50 dark:border-white/5 pt-2.5">
                                Kami mendukung pembayaran melalui QRIS instan otomatis, Transfer Bank, atau bayar langsung di tempat (kasir studio) sebelum penayangan sesi dimulai.
                            </p>
                        </details>

                        <details class="group rounded-2xl border border-border dark:border-white/10 bg-surface-2/40 dark:bg-black/20 p-4 transition-all">
                            <summary class="cursor-pointer font-bold text-sm text-text flex items-center justify-between">
                                <span>Kapan pintu studio dibuka dan bolehkah pesan camilan?</span>
                                <span class="text-amber-500 text-xs transition duration-200 group-open:rotate-180">▼</span>
                            </summary>
                            <p class="mt-2.5 text-xs leading-relaxed text-text-muted border-t border-border/50 dark:border-white/5 pt-2.5">
                                Pintu studio dibuka 15 menit sebelum waktu pemutaran. Anda bisa memesan aneka camilan hangat (tahu krispi, sosis bakar, mie rebus) serta minuman dingin bersamaan saat memesan tiket online.
                            </p>
                        </details>
                    </div>
                </section>

                {{-- Bantuan WhatsApp --}}
                @if ($adminWhatsAppUrl)
                    <section class="rounded-3xl border border-amber-400/20 bg-gradient-to-r from-amber-500/10 via-surface to-surface p-8 text-center shadow-lg">
                        <h2 class="text-xl font-black text-text">Ada pertanyaan lain seputar reservasi privat?</h2>
                        <p class="mt-1.5 text-xs text-text-muted max-w-md mx-auto">Customer service SynemaX siap membantu rekomendasi film, sesi rombongan, dan konfirmasi reservasi.</p>
                        <div class="mt-5">
                            <a href="{{ $adminWhatsAppUrl }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="btn-gold-cinema inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-6 text-xs font-black shadow-md">
                                <svg class="size-4" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2z"/>
                                </svg>
                                <span>Hubungi Pengelola via WhatsApp</span>
                            </a>
                        </div>
                    </section>
                @endif
            </div>
        @endif
    </div>
@endsection
