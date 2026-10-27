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
            <a href="{{ route('nobar.index') }}" class="inline-flex min-h-11 items-center gap-1.5 text-xs font-bold text-primary hover:underline">
                Kembali ke jadwal nobar
            </a>
        </div>

        {{-- Alert Admin Preview --}}
        @if ($isPreview)
            <div role="alert" class="mb-5 rounded-[var(--radius-control)] border border-border-input bg-surface-2 p-4 text-xs text-text">
                <span class="font-bold">Mode Pratinjau Admin</span>: Acara ini berstatus draft dan belum terbit ke publik.
            </div>
        @endif

        {{-- Alert Status Acara --}}
        @if ($event->status_acara !== 'aktif')
            <div class="mb-5 rounded-[var(--radius-control)] border border-danger-border bg-danger-surface p-4 text-xs text-danger-text">
                <p class="font-bold">Status acara: {{ ucfirst($event->status_acara) }}</p>
                @if ($event->alasan_status)
                    <p class="mt-1 leading-relaxed">{{ $event->alasan_status }}</p>
                @endif
            </div>
        @endif

        @if ($event->hasPoster())
            <div class="lg:grid lg:grid-cols-[380px,1fr] lg:gap-8 lg:items-start">
                {{-- Kolom Kiri: Poster Asli Tanpa Potong --}}
                <aside class="rounded-[var(--radius-control)] border border-border bg-surface p-3 sm:p-4 shadow-xs">
                    <div class="group relative flex items-center justify-center overflow-hidden rounded-[var(--radius-control)] bg-surface-2 p-2">
                        <img src="{{ $event->posterUrl() }}"
                             alt="{{ $event->posterAltText() }}"
                             style="{{ $event->aspectRatioStyle() }}"
                             fetchpriority="high"
                             class="max-h-[60vh] w-auto max-w-full object-contain rounded-[var(--radius-control)]">

                        <button type="button"
                                @click="openModal($el)"
                                class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition group-hover:opacity-100 focus:opacity-100 focus:outline-none"
                                aria-label="Perbesar poster">
                            <span class="inline-flex items-center gap-1.5 rounded-[var(--radius-control)] bg-surface px-3 py-1.5 text-xs font-bold text-text shadow-xs">
                                Perbesar poster
                            </span>
                        </button>
                    </div>

                    <div class="mt-3 flex gap-2">
                        <button type="button"
                                @click="openModal($el)"
                                class="inline-flex min-h-11 flex-1 items-center justify-center rounded-[var(--radius-control)] border-[1.5px] border-border-input bg-surface px-3 text-xs font-bold text-text hover:border-primary">
                            Perbesar poster
                        </button>
                        <a href="{{ $event->posterUrl() }}"
                           download="{{ $event->posterDownloadFilename() }}"
                           class="inline-flex min-h-11 flex-1 items-center justify-center rounded-[var(--radius-control)] border-[1.5px] border-border-input bg-surface px-3 text-xs font-bold text-text hover:border-primary">
                            Simpan poster
                        </a>
                    </div>
                </aside>

                {{-- Kolom Kanan: Detail Acara & Panel Pertandingan --}}
                <article class="space-y-5 rounded-[var(--radius-control)] border border-border bg-surface p-5 sm:p-6 shadow-xs">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-[var(--radius-control)] border border-border-input bg-surface px-2.5 py-0.5 text-xs font-bold text-text">
                                {{ $event->display_status }}
                            </span>
                            @if ($event->kompetisi)
                                <span class="rounded-[var(--radius-control)] bg-surface-2 px-2.5 py-0.5 text-xs font-bold text-primary">
                                    {{ $event->kompetisi }}
                                </span>
                            @endif
                            @if ($event->is_free)
                                <span class="rounded-[var(--radius-control)] bg-[var(--accent-kuning)] px-2.5 py-0.5 text-xs font-bold text-[var(--accent-kuning-contrast)]">
                                    Tiket masuk gratis
                                </span>
                            @elseif ($event->harga_info)
                                <span class="rounded-[var(--radius-control)] border border-border-input bg-surface-2 px-2.5 py-0.5 text-xs font-bold text-text">
                                    {{ $event->harga_info }}
                                </span>
                            @endif
                        </div>

                        <h1 class="mt-3 text-2xl font-black text-text sm:text-3xl leading-snug">
                            {{ $event->judul }}
                        </h1>
                    </div>

                    {{-- Wireframe Panel Layar: Dua Tim Besar dan "lawan" Kecil Biasa --}}
                    <div class="rounded-[var(--radius-control)] border border-border bg-surface-2 p-5 text-center">
                        <p class="text-xs font-bold text-text-muted">Pertandingan</p>
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 sm:px-6">
                            <div class="flex-1 text-center sm:text-left">
                                <span class="text-xl font-black text-text sm:text-2xl">{{ $event->tim_a }}</span>
                            </div>
                            <div class="px-3">
                                <span class="text-xs font-medium text-text-muted">lawan</span>
                            </div>
                            <div class="flex-1 text-center sm:text-right">
                                <span class="text-xl font-black text-text sm:text-2xl">{{ $event->tim_b ?: 'TBA' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Waktu Kickoff Besar & Lokasi --}}
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-[var(--radius-control)] border border-border bg-surface p-4">
                            <span class="block text-xs font-bold text-text-muted">Waktu kickoff</span>
                            <p class="mt-1 font-tabular text-xl sm:text-2xl font-black text-primary">
                                {{ $event->kickoff_at->format('H.i') }} WIB
                            </p>
                            <p class="mt-1 text-xs text-text">
                                {{ $event->kickoff_at->locale('id')->translatedFormat('l, d F Y') }} (durasi perkiraan {{ $event->durasi_menit }} menit)
                            </p>
                        </div>

                        <div class="rounded-[var(--radius-control)] border border-border bg-surface p-4">
                            <span class="block text-xs font-bold text-text-muted">Lokasi nobar</span>
                            <p class="mt-1 text-sm font-black text-text">
                                {{ $event->lokasi_nama }}
                            </p>
                            <p class="mt-1 text-xs text-text-muted leading-relaxed">
                                {{ $event->alamat_patokan }}
                            </p>
                        </div>
                    </div>

                    {{-- Tombol Utama & Tautan --}}
                    <div class="flex flex-wrap gap-2.5 pt-2">
                        <a href="{{ $event->maps_url }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex min-h-11 flex-1 items-center justify-center rounded-[var(--radius-control)] bg-primary px-5 text-xs font-bold text-white shadow-xs hover:opacity-95 sm:flex-none">
                            Buka peta lokasi
                        </a>
                        <a href="{{ route('home') }}#jadwal"
                           class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] border-[1.5px] border-border-input bg-surface px-4 text-xs font-bold text-text hover:border-primary">
                            Jadwal bioskop
                        </a>
                    </div>

                    {{-- Catatan / Tata Tertib Acara --}}
                    @if ($event->catatan)
                        <div class="border-t border-border pt-4 text-xs text-text-muted leading-relaxed">
                            <h2 class="text-sm font-bold text-text mb-1.5">Catatan dan tata tertib nobar</h2>
                            <div class="whitespace-pre-line space-y-1">{{ $event->catatan }}</div>
                        </div>
                    @endif
                </article>
            </div>
        @else
            <article class="max-w-4xl mx-auto overflow-hidden rounded-3xl border border-border bg-surface p-6 sm:p-8 shadow-token space-y-5">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-[var(--radius-control)] border border-border-input bg-surface px-2.5 py-0.5 text-xs font-bold text-text">
                            {{ $event->display_status }}
                        </span>
                        @if ($event->kompetisi)
                            <span class="rounded-[var(--radius-control)] bg-surface-2 px-2.5 py-0.5 text-xs font-bold text-primary">
                                {{ $event->kompetisi }}
                            </span>
                        @endif
                        @if ($event->is_free)
                            <span class="rounded-[var(--radius-control)] bg-[var(--accent-kuning)] px-2.5 py-0.5 text-xs font-bold text-[var(--accent-kuning-contrast)]">
                                Tiket masuk gratis
                            </span>
                        @elseif ($event->harga_info)
                            <span class="rounded-[var(--radius-control)] border border-border-input bg-surface-2 px-2.5 py-0.5 text-xs font-bold text-text">
                                {{ $event->harga_info }}
                            </span>
                        @endif
                    </div>

                    <h1 class="mt-3 text-2xl font-black text-text sm:text-3xl leading-snug">
                        {{ $event->judul }}
                    </h1>
                </div>

                {{-- Wireframe Panel Layar: Dua Tim Besar dan "lawan" Kecil Biasa --}}
                <div class="rounded-[var(--radius-control)] border border-border bg-surface-2 p-5 text-center">
                    <p class="text-xs font-bold text-text-muted">Pertandingan</p>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3 sm:px-6">
                        <div class="flex-1 text-center sm:text-left">
                            <span class="text-xl font-black text-text sm:text-2xl">{{ $event->tim_a }}</span>
                        </div>
                        <div class="px-3">
                            <span class="text-xs font-medium text-text-muted">lawan</span>
                        </div>
                        <div class="flex-1 text-center sm:text-right">
                            <span class="text-xl font-black text-text sm:text-2xl">{{ $event->tim_b ?: 'TBA' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Waktu Kickoff Besar & Lokasi --}}
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-[var(--radius-control)] border border-border bg-surface p-4">
                        <span class="block text-xs font-bold text-text-muted">Waktu kickoff</span>
                        <p class="mt-1 font-tabular text-xl sm:text-2xl font-black text-primary">
                            {{ $event->kickoff_at->format('H.i') }} WIB
                        </p>
                        <p class="mt-1 text-xs text-text">
                            {{ $event->kickoff_at->locale('id')->translatedFormat('l, d F Y') }} (durasi perkiraan {{ $event->durasi_menit }} menit)
                        </p>
                    </div>

                    <div class="rounded-[var(--radius-control)] border border-border bg-surface p-4">
                        <span class="block text-xs font-bold text-text-muted">Lokasi nobar</span>
                        <p class="mt-1 text-sm font-black text-text">
                            {{ $event->lokasi_nama }}
                        </p>
                        <p class="mt-1 text-xs text-text-muted leading-relaxed">
                            {{ $event->alamat_patokan }}
                        </p>
                    </div>
                </div>

                {{-- Tombol Utama & Tautan --}}
                <div class="flex flex-wrap gap-2.5 pt-2">
                    <a href="{{ $event->maps_url }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex min-h-11 flex-1 items-center justify-center rounded-[var(--radius-control)] bg-primary px-5 text-xs font-bold text-white shadow-xs hover:opacity-95 sm:flex-none">
                        Buka peta lokasi
                    </a>
                    <a href="{{ route('home') }}#jadwal"
                       class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] border-[1.5px] border-border-input bg-surface px-4 text-xs font-bold text-text hover:border-primary">
                        Jadwal bioskop
                    </a>
                </div>

                {{-- Catatan / Tata Tertib Acara --}}
                @if ($event->catatan)
                    <div class="border-t border-border pt-4 text-xs text-text-muted leading-relaxed">
                        <h2 class="text-sm font-bold text-text mb-1.5">Catatan dan tata tertib nobar</h2>
                        <div class="whitespace-pre-line space-y-1">{{ $event->catatan }}</div>
                    </div>
                @endif
            </article>
        @endif

        @if ($event->hasPoster())
            {{-- Modal Perbesar Poster --}}
            <div x-show="zoomOpen" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-overlay p-4"
                 @click.self="closeModal()"
                 @keydown.tab="handleTab($event)">
                <div x-ref="modalDialog" role="dialog" aria-modal="true" aria-labelledby="modal-poster-title"
                     class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-[var(--radius-control)] border border-border bg-surface p-4 shadow-token">
                    <div class="flex items-center justify-between border-b border-border pb-3">
                        <h2 id="modal-poster-title" class="text-sm font-bold text-text">
                            Poster {{ $event->judul }}
                        </h2>
                        <button type="button"
                                x-ref="closeModalBtn"
                                @click="closeModal()"
                                class="flex size-9 items-center justify-center rounded-[var(--radius-control)] border-[1.5px] border-border-input text-text hover:border-primary"
                                aria-label="Tutup poster">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="mt-3 flex items-center justify-center bg-surface-2 p-2 rounded-[var(--radius-control)]">
                        <img src="{{ $event->posterUrl() }}" alt="{{ $event->posterAltText() }}" class="max-h-[70vh] w-auto max-w-full object-contain rounded-[var(--radius-control)]">
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
