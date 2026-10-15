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
        {{-- Header Halaman --}}
        <div class="rounded-[var(--radius-control)] border border-border bg-surface p-5 sm:p-8 shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <span class="inline-flex items-center rounded-[var(--radius-control)] bg-surface-2 px-2.5 py-1 text-xs font-bold text-primary">
                        Nobar komunitas
                    </span>
                    <h1 class="mt-2 text-2xl font-black text-text sm:text-3xl">Jadwal nonton bareng</h1>
                    <p class="mt-1.5 max-w-xl text-xs sm:text-sm text-text-muted leading-relaxed">
                        Nikmati ketegangan pertandingan sepak bola di layar proyektor dan tata suara jernih bersama sesama penikmat bola warga desa.
                    </p>
                </div>
                <div>
                    <a href="{{ route('home') }}#jadwal" class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] border-[1.5px] border-border-input bg-surface px-4 text-xs font-bold text-text hover:border-primary">
                        Jadwal film bioskop
                    </a>
                </div>
            </div>

            {{-- Filter Kategori Acara --}}
            <div class="mt-5 flex flex-wrap gap-2 border-t border-border pt-5">
                <a href="{{ route('nobar.index') }}" class="inline-flex min-h-10 items-center rounded-[var(--radius-control)] px-3.5 text-xs font-bold transition {{ $currentFilter === 'semua' ? 'bg-primary text-white shadow-xs' : 'border-[1.5px] border-border-input bg-surface text-text hover:border-primary' }}">
                    Semua acara
                </a>
                <a href="{{ route('nobar.index', ['status' => 'mendatang']) }}" class="inline-flex min-h-10 items-center rounded-[var(--radius-control)] px-3.5 text-xs font-bold transition {{ $currentFilter === 'mendatang' ? 'bg-primary text-white shadow-xs' : 'border-[1.5px] border-border-input bg-surface text-text hover:border-primary' }}">
                    Akan datang
                </a>
                <a href="{{ route('nobar.index', ['status' => 'selesai']) }}" class="inline-flex min-h-10 items-center rounded-[var(--radius-control)] px-3.5 text-xs font-bold transition {{ $currentFilter === 'selesai' ? 'bg-primary text-white shadow-xs' : 'border-[1.5px] border-border-input bg-surface text-text hover:border-primary' }}">
                    Selesai
                </a>
            </div>
        </div>

        {{-- Daftar Acara Nobar --}}
        <div class="mt-8">
            @if ($events->isEmpty())
                <div class="rounded-[var(--radius-control)] border border-dashed border-border bg-surface p-10 text-center">
                    <h2 class="text-base font-bold text-text">Belum ada jadwal nobar</h2>
                    <p class="mt-1 text-xs text-text-muted">Jadwal pertandingan berikutnya sedang dipersiapkan. Pantau terus halaman ini atau cek film bioskop kami.</p>
                    <a href="{{ route('home') }}#jadwal" class="mt-4 inline-flex min-h-11 items-center rounded-[var(--radius-control)] bg-primary px-5 text-xs font-bold text-white hover:opacity-95">
                        Lihat jadwal film
                    </a>
                </div>
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($events as $item)
                        <article class="flex flex-col justify-between rounded-[var(--radius-control)] border border-border bg-surface p-4 sm:p-5 shadow-xs transition hover:border-primary">
                            <div>
                                @if ($item->hasPoster())
                                    <div class="relative aspect-[4/5] w-full overflow-hidden rounded-[var(--radius-control)] bg-surface-2 flex items-center justify-center p-2">
                                        <img src="{{ $item->posterUrl() }}" alt="{{ $item->posterAltText() }}" class="max-h-full max-w-full object-contain rounded-[var(--radius-control)]" loading="lazy">

                                        <div class="absolute left-2.5 top-2.5 flex flex-wrap gap-1.5">
                                            <span class="rounded-[var(--radius-control)] px-2 py-0.5 text-[10px] font-bold border border-border-input bg-surface/95 text-text shadow-xs">
                                                {{ $item->display_status }}
                                            </span>
                                            @if ($item->is_pinned)
                                                <span class="rounded-[var(--radius-control)] bg-primary px-2 py-0.5 text-[10px] font-bold text-white shadow-xs">
                                                    Pilihan
                                                </span>
                                            @endif
                                        </div>

                                        @if ($item->is_free)
                                            <div class="absolute bottom-2.5 right-2.5 rounded-[var(--radius-control)] bg-success px-2 py-0.5 text-[10px] font-bold text-white">
                                                Gratis
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <div class="{{ $item->hasPoster() ? 'mt-4' : '' }}">
                                    @if ($item->kompetisi)
                                        <p class="text-xs font-semibold text-primary">{{ $item->kompetisi }}</p>
                                    @endif

                                    <h2 class="mt-1 text-base font-bold text-text leading-snug">
                                        <a href="{{ route('nobar.show', ['slug' => $item->slug]) }}" class="hover:text-primary">
                                            {{ $item->judul }}
                                        </a>
                                    </h2>

                                    {{-- Panel Layar: Dua Tim Besar & "lawan" Kecil Biasa --}}
                                    <div class="mt-3 flex items-baseline justify-between rounded-[var(--radius-control)] bg-surface-2 p-3 text-xs">
                                        <span class="font-bold text-text truncate">{{ $item->tim_a }}</span>
                                        <span class="px-2 text-text-muted">lawan</span>
                                        <span class="font-bold text-text truncate text-right">{{ $item->tim_b ?: 'TBA' }}</span>
                                    </div>

                                    <div class="mt-3 flex items-baseline justify-between">
                                        <span class="font-tabular text-sm font-extrabold text-primary">
                                            {{ $item->kickoff_at->format('H.i') }} WIB
                                        </span>
                                        <span class="text-[11px] text-text-muted">
                                            {{ $item->kickoff_at->locale('id')->translatedFormat('D, d M Y') }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-text-muted">
                                        Lokasi: {{ $item->lokasi_nama }}
                                    </p>

                                    @if ($item->status_acara !== 'aktif' && $item->alasan_status)
                                        <div class="mt-3 rounded-[var(--radius-control)] border border-danger-border bg-danger-surface p-2.5 text-xs text-danger-text">
                                            <p class="font-bold">Info: {{ $item->alasan_status }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-5 border-t border-border pt-3">
                                <a href="{{ route('nobar.show', ['slug' => $item->slug]) }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-[var(--radius-control)] bg-primary px-4 text-xs font-bold text-white shadow-xs transition hover:opacity-95">
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
