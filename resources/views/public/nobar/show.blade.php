@extends('layouts.public')

@section('title', $event->judul . ': Nobar SynemaX')

@section('meta')
    <meta property="og:type" content="event">
    <meta property="og:title" content="{{ $event->judul }} · Nobar SynemaX">
    <meta property="og:description" content="{{ $event->vsTitle() }}, {{ $event->kickoff_at->locale('id')->translatedFormat('l, d F Y') }} pukul {{ $event->kickoff_at->format('H.i') }} WIB di {{ $event->lokasi_nama }}. {{ $event->is_free ? 'Tiket masuk: gratis' : $event->harga_info }}">
    <meta property="og:url" content="{{ route('nobar.show', ['slug' => $event->slug]) }}">
    <meta property="og:image" content="{{ $event->posterOgUrl() }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $event->judul }}">
    <meta name="twitter:description" content="{{ $event->vsTitle() }}, {{ $event->kickoff_at->locale('id')->translatedFormat('l, d F Y') }} pukul {{ $event->kickoff_at->format('H.i') }} WIB di {{ $event->lokasi_nama }}">
    <meta name="twitter:image" content="{{ $event->posterOgUrl() }}">
@endsection

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 sm:py-10"
         x-data="{
             zoomOpen: false,
             triggerEl: null,
             openModal(el) {
                 this.triggerEl = el;
                 this.zoomOpen = true;
                 document.body.classList.add('overflow-hidden');
                 this.$nextTick(() => {
                     this.$refs.closeModalBtn?.focus();
                 });
             },
             closeModal() {
                 this.zoomOpen = false;
                 document.body.classList.remove('overflow-hidden');
                 if (this.triggerEl) {
                     this.triggerEl.focus();
                 }
             },
             handleTab(event) {
                 if (!this.zoomOpen) return;
                 const focusables = Array.from(this.$refs.modalDialog?.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex=\'-1\'])') || []);
                 if (focusables.length === 0) return;
                 const first = focusables[0];
                 const last = focusables[focusables.length - 1];
                 if (event.shiftKey && document.activeElement === first) {
                     event.preventDefault();
                     last.focus();
                 } else if (!event.shiftKey && document.activeElement === last) {
                     event.preventDefault();
                     first.focus();
                 }
             }
         }"
         @keydown.escape.window="if (zoomOpen) closeModal()">

        {{-- Navigasi Balik --}}
        <div class="mb-5">
            <a href="{{ route('nobar.index') }}" class="inline-flex min-h-11 items-center gap-2 text-xs font-bold text-amber-400 hover:text-amber-300 transition">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                <span>Kembali ke jadwal nobar</span>
            </a>
        </div>

        {{-- Alert Admin Preview --}}
        @if ($isPreview)
            <div role="alert" class="mb-5 rounded-2xl border border-amber-400/40 bg-amber-500/10 p-4 text-xs text-amber-300">
                <span class="font-black">Mode Pratinjau Admin</span>: Acara ini berstatus draft dan belum terbit ke publik.
            </div>
        @endif

        {{-- Alert Status Acara --}}
        @if ($event->status_acara !== 'aktif')
            <div class="mb-5 rounded-2xl border border-danger/40 bg-danger-surface p-4 text-xs text-danger-text">
                <p class="font-bold">Status acara: {{ ucfirst($event->status_acara) }}</p>
                @if ($event->alasan_status)
                    <p class="mt-1 leading-relaxed">{{ $event->alasan_status }}</p>
                @endif
            </div>
        @endif

        @if ($event->hasPoster())
            <div class="lg:grid lg:grid-cols-[380px,1fr] lg:gap-8 lg:items-start">
                {{-- Kolom Kiri: Poster Asli Tanpa Potong dengan Bingkai Cinema --}}
                <aside class="rounded-3xl border border-white/10 bg-gradient-to-b from-surface via-[#0C111D] to-[#07090E] p-4 shadow-xl">
                    <div class="group relative flex items-center justify-center overflow-hidden rounded-2xl bg-black/60 p-2 border border-white/5 shadow-inner">
                        <img src="{{ $event->posterUrl() }}"
                             alt="{{ $event->posterAltText() }}"
                             style="{{ $event->aspectRatioStyle() }}"
                             fetchpriority="high"
                             class="max-h-[60vh] w-auto max-w-full object-contain rounded-xl">

                        <button type="button"
                                @click="openModal($el)"
                                class="absolute inset-0 flex items-center justify-center bg-black/50 backdrop-blur-xs opacity-0 transition duration-300 group-hover:opacity-100 focus:opacity-100 focus:outline-none"
                                aria-label="Perbesar poster">
                            <span class="inline-flex items-center gap-1.5 rounded-xl bg-surface/90 border border-white/20 px-3.5 py-2 text-xs font-black text-white shadow-lg">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                </svg>
                                <span>Perbesar poster</span>
                            </span>
                        </button>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <button type="button"
                                @click="openModal($el)"
                                class="inline-flex min-h-11 flex-1 items-center justify-center gap-1.5 rounded-xl border border-white/10 bg-surface px-3 text-xs font-bold text-text hover:border-amber-400 hover:text-amber-400 transition">
                            Perbesar poster
                        </button>
                        <a href="{{ $event->posterUrl() }}"
                           download="{{ $event->posterDownloadFilename() }}"
                           class="inline-flex min-h-11 flex-1 items-center justify-center gap-1.5 rounded-xl border border-white/10 bg-surface px-3 text-xs font-bold text-text hover:border-amber-400 hover:text-amber-400 transition">
                            Simpan poster
                        </a>
                    </div>
                </aside>

                {{-- Kolom Kanan: Detail Acara & Panel Pertandingan --}}
                <article class="mt-6 lg:mt-0 space-y-5 rounded-3xl border border-white/10 bg-gradient-to-b from-surface via-[#0C111D] to-[#07090E] p-6 sm:p-8 shadow-xl">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-lg border border-white/20 bg-black/60 px-3 py-1 text-xs font-black uppercase tracking-wider text-text">
                                {{ $event->display_status }}
                            </span>
                            @if ($event->kompetisi)
                                <span class="rounded-lg border border-cyan-500/30 bg-cyan-500/10 px-3 py-1 text-xs font-black uppercase tracking-wider text-cyan-400">
                                    {{ $event->kompetisi }}
                                </span>
                            @endif
                            @if ($event->is_free)
                                <span class="rounded-lg bg-emerald-500/20 border border-emerald-400/40 px-3 py-1 text-xs font-black uppercase text-emerald-400">
                                    Tiket masuk gratis
                                </span>
                            @elseif ($event->harga_info)
                                <span class="rounded-lg border border-amber-400/40 bg-amber-500/15 px-3 py-1 text-xs font-black uppercase text-amber-300">
                                    {{ $event->harga_info }}
                                </span>
                            @endif
                        </div>

                        <h1 class="mt-4 text-2xl font-black text-text sm:text-4xl leading-snug">
                            {{ $event->judul }}
                        </h1>
                    </div>

                    {{-- Scoreboard Pertandingan: Team A vs Team B --}}
                    <div class="rounded-2xl border border-white/10 bg-black/40 p-5 text-center shadow-inner">
                        <p class="text-xs font-bold uppercase tracking-wider text-amber-500">Pertandingan</p>
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 sm:px-6">
                            <div class="flex-1 text-center sm:text-left">
                                <span class="text-xl font-black text-white sm:text-3xl">{{ $event->tim_a }}</span>
                            </div>
                            <div class="px-3">
                                <span class="rounded-full bg-amber-500/15 border border-amber-400/30 px-3 py-1 text-xs font-black uppercase tracking-widest text-amber-400">lawan</span>
                            </div>
                            <div class="flex-1 text-center sm:text-right">
                                <span class="text-xl font-black text-white sm:text-3xl">{{ $event->tim_b ?: 'TBA' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Waktu Kickoff Besar & Lokasi --}}
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-white/10 bg-surface/60 p-4">
                            <span class="block text-xs font-bold uppercase tracking-wider text-text-muted">Waktu kickoff</span>
                            <p class="mt-1 font-tabular text-2xl font-black text-amber-400">
                                {{ $event->kickoff_at->format('H.i') }} WIB
                            </p>
                            <p class="mt-1 text-xs text-text">
                                {{ $event->kickoff_at->locale('id')->translatedFormat('l, d F Y') }} (durasi perkiraan {{ $event->durasi_menit }} menit)
                            </p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-surface/60 p-4">
                            <span class="block text-xs font-bold uppercase tracking-wider text-text-muted">Lokasi nobar</span>
                            <p class="mt-1 text-sm font-black text-text">
                                {{ $event->lokasi_nama }}
                            </p>
                            <p class="mt-1 text-xs text-text-muted leading-relaxed">
                                {{ $event->alamat_patokan }}
                            </p>
                        </div>
                    </div>

                    {{-- Tombol Utama & Tautan --}}
                    <div class="flex flex-wrap gap-3 pt-2">
                        <a href="{{ $event->maps_url }}" target="_blank" rel="noopener noreferrer"
                           class="btn-gold-cinema inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-xl px-5 text-xs font-bold shadow-md sm:flex-none">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>Buka peta lokasi</span>
                        </a>
                        <a href="{{ route('home') }}#jadwal"
                           class="inline-flex min-h-11 items-center justify-center rounded-xl border border-white/20 bg-surface px-5 text-xs font-bold text-text hover:border-amber-400 hover:text-amber-300 transition">
                            Jadwal bioskop
                        </a>
                    </div>

                    {{-- Catatan / Tata Tertib Acara --}}
                    @if ($event->catatan)
                        <div class="border-t border-white/10 pt-4 text-xs text-text-muted leading-relaxed">
                            <h2 class="text-sm font-black text-text uppercase tracking-wider text-amber-500 mb-2">Catatan dan tata tertib nobar</h2>
                            <div class="whitespace-pre-line space-y-1">{{ $event->catatan }}</div>
                        </div>
                    @endif
                </article>
            </div>
        @else
            <article class="max-w-4xl mx-auto overflow-hidden rounded-3xl border border-border bg-gradient-to-b from-surface via-[#0C111D] to-[#07090E] p-6 sm:p-8 shadow-xl space-y-5">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-lg border border-white/20 bg-black/60 px-3 py-1 text-xs font-black uppercase tracking-wider text-text">
                            {{ $event->display_status }}
                        </span>
                        @if ($event->kompetisi)
                            <span class="rounded-lg border border-cyan-500/30 bg-cyan-500/10 px-3 py-1 text-xs font-black uppercase tracking-wider text-cyan-400">
                                {{ $event->kompetisi }}
                            </span>
                        @endif
                        @if ($event->is_free)
                            <span class="rounded-lg bg-emerald-500/20 border border-emerald-400/40 px-3 py-1 text-xs font-black uppercase text-emerald-400">
                                Tiket masuk gratis
                            </span>
                        @elseif ($event->harga_info)
                            <span class="rounded-lg border border-amber-400/40 bg-amber-500/15 px-3 py-1 text-xs font-black uppercase text-amber-300">
                                {{ $event->harga_info }}
                            </span>
                        @endif
                    </div>

                    <h1 class="mt-4 text-2xl font-black text-text sm:text-4xl leading-snug">
                        {{ $event->judul }}
                    </h1>
                </div>

                {{-- Scoreboard Pertandingan --}}
                <div class="rounded-2xl border border-white/10 bg-black/40 p-5 text-center shadow-inner">
                    <p class="text-xs font-bold uppercase tracking-wider text-amber-500">Pertandingan</p>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3 sm:px-6">
                        <div class="flex-1 text-center sm:text-left">
                            <span class="text-xl font-black text-white sm:text-3xl">{{ $event->tim_a }}</span>
                        </div>
                        <div class="px-3">
                            <span class="rounded-full bg-amber-500/15 border border-amber-400/30 px-3 py-1 text-xs font-black uppercase tracking-widest text-amber-400">lawan</span>
                        </div>
                        <div class="flex-1 text-center sm:text-right">
                            <span class="text-xl font-black text-white sm:text-3xl">{{ $event->tim_b ?: 'TBA' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Waktu Kickoff Besar & Lokasi --}}
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border border-white/10 bg-surface/60 p-4">
                        <span class="block text-xs font-bold uppercase tracking-wider text-text-muted">Waktu kickoff</span>
                        <p class="mt-1 font-tabular text-2xl font-black text-amber-400">
                            {{ $event->kickoff_at->format('H.i') }} WIB
                        </p>
                        <p class="mt-1 text-xs text-text">
                            {{ $event->kickoff_at->locale('id')->translatedFormat('l, d F Y') }} (durasi perkiraan {{ $event->durasi_menit }} menit)
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-surface/60 p-4">
                        <span class="block text-xs font-bold uppercase tracking-wider text-text-muted">Lokasi nobar</span>
                        <p class="mt-1 text-sm font-black text-text">
                            {{ $event->lokasi_nama }}
                        </p>
                        <p class="mt-1 text-xs text-text-muted leading-relaxed">
                            {{ $event->alamat_patokan }}
                        </p>
                    </div>
                </div>

                {{-- Tombol Utama & Tautan --}}
                <div class="flex flex-wrap gap-3 pt-2">
                    <a href="{{ $event->maps_url }}" target="_blank" rel="noopener noreferrer"
                       class="btn-gold-cinema inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-xl px-5 text-xs font-bold shadow-md sm:flex-none">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Buka peta lokasi</span>
                    </a>
                    <a href="{{ route('home') }}#jadwal"
                       class="inline-flex min-h-11 items-center justify-center rounded-xl border border-white/20 bg-surface px-5 text-xs font-bold text-text hover:border-amber-400 hover:text-amber-300 transition">
                        Jadwal bioskop
                    </a>
                </div>

                {{-- Catatan / Tata Tertib Acara --}}
                @if ($event->catatan)
                    <div class="border-t border-white/10 pt-4 text-xs text-text-muted leading-relaxed">
                        <h2 class="text-sm font-black text-text uppercase tracking-wider text-amber-500 mb-2">Catatan dan tata tertib nobar</h2>
                        <div class="whitespace-pre-line space-y-1">{{ $event->catatan }}</div>
                    </div>
                @endif
            </article>
        @endif

        @if ($event->hasPoster())
            {{-- Modal Perbesar Poster --}}
            <div x-show="zoomOpen" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-md p-4"
                 @click.self="closeModal()"
                 @keydown.tab="handleTab($event)">
                <div x-ref="modalDialog" role="dialog" aria-modal="true" aria-labelledby="modal-poster-title"
                     class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl border border-white/20 bg-[#0E131F] p-5 shadow-2xl">
                    <div class="flex items-center justify-between border-b border-white/10 pb-3">
                        <h2 id="modal-poster-title" class="text-sm font-black text-white">
                            Poster {{ $event->judul }}
                        </h2>
                        <button type="button"
                                x-ref="closeModalBtn"
                                @click="closeModal()"
                                class="flex size-9 items-center justify-center rounded-xl border border-white/20 text-text hover:border-amber-400 hover:text-amber-400"
                                aria-label="Tutup poster">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="mt-4 flex items-center justify-center bg-black/50 p-2 rounded-2xl">
                        <img src="{{ $event->posterUrl() }}" alt="{{ $event->posterAltText() }}" class="max-h-[70vh] w-auto max-w-full object-contain rounded-xl">
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
