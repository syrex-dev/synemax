@extends('layouts.public')

@section('title', 'Jadwal nobar sepak bola: SynemaX')

@section('meta')
    <meta property="og:type" content="website">
    <meta property="og:title" content="Jadwal nobar sepak bola: SynemaX">
    <meta property="og:description" content="Nonton bareng pertandingan sepak bola seru di SynemaX. Layar lebar proyektor, tata suara jernih, suasana hangat komunitas warga.">
    <meta property="og:url" content="{{ route('nobar.index') }}">
    <meta property="og:image" content="{{ url(asset('social-banner.png')) }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Jadwal nobar sepak bola: SynemaX">
    <meta name="twitter:description" content="Nonton bareng pertandingan sepak bola seru di SynemaX.">
    <meta name="twitter:image" content="{{ url(asset('social-banner.png')) }}">
@endsection

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10">
        {{-- Header Halaman ala Stadium Cinema XXI/CGV --}}
        <div class="relative overflow-hidden rounded-3xl border border-border dark:border-white/10 bg-surface dark:bg-gradient-to-br dark:from-surface dark:via-[#0C111D] dark:to-[#07090E] p-6 sm:p-8 shadow-2xl">
            <div class="absolute -right-16 -top-16 size-64 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>
            <div class="absolute -left-16 -bottom-16 size-64 rounded-full bg-cyan-500/10 blur-3xl pointer-events-none"></div>

            <div class="relative flex flex-wrap items-center justify-between gap-5">
                <div>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-400/40 bg-amber-500/15 px-3 py-1 text-xs font-black uppercase tracking-wider text-amber-500 dark:text-amber-300">
                        <span class="size-2 rounded-full bg-amber-400 animate-pulse"></span>
                        Nobar komunitas
                    </span>
                    <h1 class="mt-2 text-2xl font-black text-text sm:text-4xl tracking-tight">Jadwal nonton bareng</h1>
                    <p class="mt-2 max-w-xl text-xs sm:text-sm text-text-muted leading-relaxed">
                        Nikmati ketegangan pertandingan sepak bola di layar proyektor dan tata suara jernih bersama sesama penikmat bola warga desa.
                    </p>
                </div>
                <div>
                    <a href="{{ route('schedule.index') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-border dark:border-white/20 bg-surface-2 dark:bg-surface/80 px-4 text-xs font-bold text-text hover:border-amber-400 hover:text-amber-500 dark:hover:text-amber-300 transition">
                        <svg class="size-4 text-amber-500 dark:text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2zm0 3v2h2V7H4zm0 4v2h2v-2H4zm0 4v2h2v-2H4zm16-8h-2v2h2V7zm0 4h-2v2h2v-2zm0 4h-2v2h2v-2zm-12-8h8v10H8V7z"/>
                        </svg>
                        <span>Jadwal film bioskop</span>
                    </a>
                </div>
            </div>

            {{-- Filter Kategori Acara --}}
            <div class="mt-6 flex flex-wrap gap-2 border-t border-border dark:border-white/10 pt-5">
                <a href="{{ route('nobar.index') }}" class="inline-flex min-h-10 items-center rounded-xl px-4 text-xs font-black transition {{ $currentFilter === 'semua' ? 'btn-gold-cinema shadow-md' : 'border border-border dark:border-white/10 bg-surface-2 dark:bg-surface/60 text-text-muted hover:border-amber-400/50 hover:text-text' }}">
                    Semua acara
                </a>
                <a href="{{ route('nobar.index', ['status' => 'mendatang']) }}" class="inline-flex min-h-10 items-center rounded-xl px-4 text-xs font-black transition {{ $currentFilter === 'mendatang' ? 'btn-gold-cinema shadow-md' : 'border border-border dark:border-white/10 bg-surface-2 dark:bg-surface/60 text-text-muted hover:border-amber-400/50 hover:text-text' }}">
                    Akan datang
                </a>
                <a href="{{ route('nobar.index', ['status' => 'selesai']) }}" class="inline-flex min-h-10 items-center rounded-xl px-4 text-xs font-black transition {{ $currentFilter === 'selesai' ? 'btn-gold-cinema shadow-md' : 'border border-border dark:border-white/10 bg-surface-2 dark:bg-surface/60 text-text-muted hover:border-amber-400/50 hover:text-text' }}">
                    Selesai
                </a>
            </div>
        </div>

        {{-- Daftar Acara Nobar --}}
        <div class="mt-8">
            @if ($events->isEmpty())
                <div class="rounded-3xl border border-dashed border-border dark:border-white/15 bg-surface-2/60 dark:bg-surface/40 p-12 text-center backdrop-blur-sm">
                    <span class="inline-flex size-14 items-center justify-center rounded-2xl bg-amber-500/10 text-2xl text-amber-500 dark:text-amber-400 mb-3">
                        ⚽
                    </span>
                    <h2 class="text-base font-black text-text">Belum ada jadwal nobar</h2>
                    <p class="mt-1 text-xs text-text-muted max-w-md mx-auto">Jadwal pertandingan berikutnya sedang dipersiapkan. Pantau terus halaman ini atau cek film bioskop kami.</p>
                    <a href="{{ route('schedule.index') }}" class="mt-5 btn-gold-cinema inline-flex min-h-11 items-center rounded-xl px-6 text-xs font-bold shadow-md">
                        Lihat jadwal film
                    </a>
                </div>
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($events as $item)
                        <article class="flex flex-col justify-between rounded-3xl border border-border dark:border-white/10 bg-surface dark:bg-gradient-to-b dark:from-surface dark:via-[#0B0F19] dark:to-surface p-4 sm:p-5 shadow-xl transition-all duration-300 hover:border-amber-400/50 hover:-translate-y-1 group">
                            <div>
                                @if ($item->hasPoster())
                                    <div class="relative mb-4">
                                        <x-ui.image
                                            :src="$item->posterUrl()"
                                            :alt="$item->posterAltText()"
                                            :title="$item->judul"
                                            :badge="$item->display_status"
                                            aspect="aspect-[4/5]"
                                            imgClass="max-h-full max-w-full object-contain mx-auto rounded-xl transition duration-500 group-hover:scale-105"
                                            class="flex items-center justify-center p-2 shadow-inner"
                                        />

                                        <div class="absolute left-3 top-3 flex flex-wrap gap-1.5 pointer-events-none">
                                            <span class="rounded-lg px-2.5 py-1 text-[10px] font-black uppercase tracking-wider border border-border dark:border-white/20 bg-surface/90 dark:bg-black/80 text-text dark:text-white backdrop-blur-md shadow-md">
                                                {{ $item->display_status }}
                                            </span>
                                            @if ($item->is_pinned)
                                                <span class="rounded-lg bg-gradient-to-r from-amber-400 to-amber-600 px-2.5 py-1 text-[10px] font-black uppercase text-black shadow-md">
                                                    Pilihan
                                                </span>
                                            @endif
                                        </div>

                                        @if ($item->is_free)
                                            <div class="absolute bottom-3 right-3 rounded-lg bg-emerald-500/90 backdrop-blur-md border border-emerald-400/50 px-2.5 py-1 text-[10px] font-black uppercase text-white shadow-md pointer-events-none">
                                                Gratis
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <div>
                                    @if ($item->kompetisi)
                                        <div class="flex items-center gap-1.5">
                                            <span class="size-1.5 rounded-full bg-cyan-500 dark:bg-cyan-400"></span>
                                            <p class="text-xs font-bold uppercase tracking-wider text-cyan-600 dark:text-cyan-400">{{ $item->kompetisi }}</p>
                                        </div>
                                    @endif

                                    <h2 class="mt-1.5 text-base font-black text-text leading-snug group-hover:text-amber-500 dark:group-hover:text-amber-400 transition">
                                        <a href="{{ route('nobar.show', ['slug' => $item->slug]) }}">
                                            {{ $item->judul }}
                                        </a>
                                    </h2>

                                    {{-- Panel Pertandingan: Fixture Box Team A vs Team B --}}
                                    <div class="mt-3 flex items-center justify-between rounded-xl border border-border dark:border-white/10 bg-surface-2 dark:bg-black/40 p-3 text-xs shadow-inner">
                                        <span class="font-black text-text truncate max-w-[42%]">{{ $item->tim_a }}</span>
                                        <span class="px-2 py-0.5 rounded bg-amber-500/15 text-[10px] font-black uppercase text-amber-500 dark:text-amber-400 border border-amber-400/30">lawan</span>
                                        <span class="font-black text-text truncate max-w-[42%] text-right">{{ $item->tim_b ?: 'TBA' }}</span>
                                    </div>

                                    <div class="mt-3 flex items-baseline justify-between border-t border-border dark:border-white/10 pt-2.5">
                                        <span class="font-tabular text-sm font-black text-amber-600 dark:text-amber-400">
                                            {{ format_jam_id($item->kickoff_at) }}
                                        </span>
                                        <span class="text-[11px] font-medium text-text-muted">
                                            {{ format_tanggal_id($item->kickoff_at) }}
                                        </span>
                                    </div>
                                    <p class="mt-1.5 text-xs text-text-muted flex items-center gap-1.5 truncate">
                                        <svg class="size-3.5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <span>Lokasi: {{ $item->lokasi_nama }}</span>
                                    </p>

                                    @if ($item->status_acara !== 'aktif' && $item->alasan_status)
                                        <div class="mt-3 rounded-xl border border-danger/40 bg-danger-surface p-2.5 text-xs text-danger-text">
                                            <p class="font-bold">Info: {{ $item->alasan_status }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-5 border-t border-border dark:border-white/10 pt-3">
                                <a href="{{ route('nobar.show', ['slug' => $item->slug]) }}" class="btn-gold-cinema inline-flex min-h-11 w-full items-center justify-center rounded-xl px-4 text-xs font-black shadow-md">
                                    Lihat detail acara
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
