@extends('layouts.public')

@section('title', 'SynemaX: Bioskop Mini Desa Berkualitas Premiere')
@section('meta')
    <meta name="description" content="Nonton dekat, seru bareng di studio mini SynemaX. Pengalaman bioskop modern standar XXI & CGV dengan tata suara menggelegar dan kursi empuk.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="SynemaX: Bioskop Mini Premiere Desa">
    <meta property="og:description" content="Film pilihan terbaik, suasana home cinema berkelas. Amankan kursimu sekarang.">
    <meta property="og:image" content="{{ asset('social-banner.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="SynemaX: Bioskop Mini Premiere Desa">
    <meta name="twitter:description" content="Film pilihan terbaik, suasana home cinema berkelas. Amankan kursimu sekarang.">
    <meta name="twitter:image" content="{{ asset('social-banner.png') }}">
@endsection

@section('content')
    <div x-data="{
             trailerModal: false,
             activeFilm: null,
             openTrailer(film) {
                 this.activeFilm = film;
                 this.trailerModal = true;
             },
             closeTrailer() {
                 this.trailerModal = false;
                 this.activeFilm = null;
             },
             getEmbedUrl(url) {
                 if (!url) return '';
                 const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
                 const match = url.match(regExp);
                 return (match && match[2].length === 11) ? 'https://www.youtube-nocookie.com/embed/' + match[2] + '?autoplay=1' : url;
             }
         }"
         class="mx-auto max-w-6xl px-4 py-4 sm:px-6 sm:py-8">

        {{-- HERO SECTION: Banner Sinematik Mewah ala XXI Premiere --}}
        <section class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-[#0F1424] via-[#090D17] to-[#04060A] p-5 shadow-[0_20px_60px_-15px_rgba(0,0,0,0.9)] sm:p-8 lg:p-10">
            {{-- Ambient Lighting / Glow Effect --}}
            <div class="pointer-events-none absolute -left-20 -top-20 size-80 rounded-full bg-amber-500/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -right-20 -bottom-20 size-80 rounded-full bg-cyan-500/10 blur-3xl"></div>

            @if ($nextAvailableSession && $nextAvailableSession->film)
                @php
                    $heroFilm = $nextAvailableSession->film;
                    $heroTime = substr((string) $nextAvailableSession->start_time, 0, 5) . ' WIB';
                    $heroDate = $nextAvailableSession->startsAt()->locale('id')->translatedFormat('l, d F Y');
                @endphp
                <div class="relative z-10 flex flex-col gap-6 sm:flex-row sm:items-center lg:gap-10">
                    {{-- Poster Film --}}
                    <div class="group relative w-36 shrink-0 sm:w-48 lg:w-56 mx-auto sm:mx-0">
                        <div class="aspect-[2/3] w-full overflow-hidden rounded-2xl border-2 border-amber-400/30 bg-[#0E131F] shadow-[0_12px_30px_rgba(0,0,0,0.8)] transition duration-300 group-hover:scale-102 group-hover:border-amber-400">
                            @if ($heroFilm->poster_path)
                                <img src="{{ Storage::url($heroFilm->poster_path) }}"
                                     alt="Poster {{ $heroFilm->title }}"
                                     loading="eager"
                                     fetchpriority="high"
                                     class="size-full object-cover">
                            @else
                                <div class="flex size-full flex-col items-center justify-center p-4 text-center">
                                    <svg class="size-10 text-amber-400/50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                    </svg>
                                    <span class="mt-2 text-xs font-bold text-amber-200">{{ $heroFilm->title }}</span>
                                </div>
                            @endif
                        </div>
                        <span class="absolute -top-2 -right-2 rounded-lg bg-gradient-to-r from-amber-500 to-amber-700 px-2 py-0.5 font-tabular text-[10px] font-black uppercase tracking-wider text-black shadow-md">
                            {{ $heroFilm->age_rating }}
                        </span>
                    </div>

                    {{-- Informasi Film & Aksi --}}
                    <div class="flex-1 min-w-0 text-center sm:text-left">
                        <div class="inline-flex items-center gap-2 rounded-full border border-amber-400/30 bg-amber-400/10 px-3 py-1 text-[11px] font-bold text-amber-300 uppercase tracking-widest shadow-xs">
                            <span class="size-2 rounded-full bg-amber-400 animate-pulse"></span>
                            Sedang Tayang di Studio 1
                        </div>

                        <h1 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl lg:text-5xl leading-tight">
                            {{ $heroFilm->title }}
                        </h1>

                        <div class="mt-2.5 flex flex-wrap items-center justify-center sm:justify-start gap-2 text-xs font-medium text-slate-300">
                            <span class="rounded-md bg-white/10 px-2 py-0.5 text-amber-300 font-semibold">{{ $heroFilm->genre }}</span>
                            <span class="rounded-md bg-white/10 px-2 py-0.5 font-tabular">{{ $heroFilm->duration_minutes }} Menit</span>
                            <span class="rounded-md bg-white/10 px-2 py-0.5 font-tabular text-amber-200">Format Layar Lebar & Dolby Sound</span>
                        </div>

                        <p class="mt-3.5 max-w-xl text-xs sm:text-sm leading-relaxed text-slate-300/90 line-clamp-3">
                            {{ $heroFilm->synopsis ?: 'Ajak keluarga dan teman nonton bareng di studio mini berpendingin ruangan dengan proyektor jernih dan tata suara sinematik.' }}
                        </p>

                        <div class="mt-6 flex flex-wrap items-center justify-center sm:justify-start gap-3">
                            {{-- Tombol Pesan Tiket (Memenuhi assertSee('Pesan tiket')) --}}
                            <a href="{{ route('booking.create', ['session' => $nextAvailableSession->id]) }}"
                               class="btn-gold-cinema inline-flex min-h-12 items-center justify-center gap-2 rounded-xl px-7 text-sm font-black shadow-lg">
                                <svg class="size-4" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2zm0 3v2h2V7H4zm0 4v2h2v-2H4zm0 4v2h2v-2H4zm16-8h-2v2h2V7zm0 4h-2v2h2v-2zm0 4h-2v2h2v-2zm-12-8h8v10H8V7z"/>
                                </svg>
                                <span>Pesan tiket</span>
                            </a>

                            {{-- Tombol Tonton Trailer --}}
                            <button type="button"
                                    @click="openTrailer(@js($heroFilm))"
                                    class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/5 backdrop-blur-md px-5 text-sm font-bold text-white transition hover:bg-white/15 hover:border-amber-400">
                                <svg class="size-5 text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                                <span>Tonton Trailer</span>
                            </button>

                            <div class="inline-flex min-h-12 items-center rounded-xl border border-amber-400/40 bg-amber-500/10 px-4 font-tabular text-sm font-black text-amber-300">
                                Sesi Terdekat: {{ $heroTime }}
                            </div>
                        </div>

                        <p class="mt-3 text-xs text-slate-400">
                            Sesi tayang {{ $heroDate }}. Tiket hari biasa mulai Rp {{ number_format($weekdayPrice, 0, ',', '.') }} per orang.
                        </p>
                    </div>
                </div>
            @else
                <div class="relative z-10 max-w-2xl py-4 sm:py-6">
                    <div class="inline-flex items-center gap-2 rounded-full border border-amber-400/30 bg-amber-400/10 px-3 py-1 text-[11px] font-bold text-amber-300 uppercase tracking-widest">
                        Studio Mini Eksklusif Desa
                    </div>
                    <h1 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-5xl leading-tight">
                        Sensasi Nonton Bioskop Modern di Dekat Anda
                    </h1>
                    <p class="mt-4 text-sm sm:text-base leading-relaxed text-slate-300">
                        Nikmati deretan film terbaik dengan proyektor laser definisi tinggi, AC dingin, dan tata suara surround layaknya bioskop komersial. Amankan kursimu tanpa antre.
                    </p>
                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <a href="#jadwal" class="btn-gold-cinema inline-flex min-h-12 items-center justify-center gap-2 rounded-xl px-7 text-sm font-black shadow-lg">
                            <span>Pesan tiket</span>
                        </a>
                    </div>
                    <p class="mt-3 text-xs text-slate-400">
                        Kapasitas privat 6 kursi per sesi. Tiket hari biasa mulai Rp {{ number_format($weekdayPrice, 0, ',', '.') }} per orang.
                    </p>
                </div>
            @endif
        </section>

        {{-- Booking Saya (Tersimpan Lokal di Browser) --}}
        <section id="booking-saya"
                 x-data="{
                     codes: [],
                     loadCodes() {
                         try {
                             const stored = JSON.parse(localStorage.getItem('synemax:booking-codes') || '[]');
                             this.codes = Array.isArray(stored) ? [...new Set(stored.filter((code) => typeof code === 'string'))] : [];
                         } catch {
                             this.codes = [];
                         }
                     },
                     removeCode(code) {
                         this.codes = this.codes.filter((saved) => saved !== code);
                         try {
                             localStorage.setItem('synemax:booking-codes', JSON.stringify(this.codes));
                         } catch {
                             this.loadCodes();
                         }
                     }
                 }"
                 x-init="loadCodes()"
                 x-show="codes.length > 0"
                 x-cloak
                 class="mt-8 rounded-2xl border border-border bg-surface/80 backdrop-blur-md p-5 shadow-sm">
            <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-border pb-3">
                <div class="flex items-center gap-2">
                    <span class="flex size-6 items-center justify-center rounded-md bg-amber-500/20 text-amber-400">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                    </span>
                    <h2 class="text-base font-bold text-text">Booking saya di perangkat ini</h2>
                </div>
                <p class="text-xs text-text-muted">Nomor WhatsApp tetap diminta saat memeriksa status tiket.</p>
            </div>
            <ul class="mt-4 space-y-2">
                <template x-for="code in codes" :key="code">
                    <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border bg-surface-2/60 p-3">
                        <a :href="`{{ route('booking.lookup.form') }}?code=${encodeURIComponent(code)}`"
                           class="font-tabular text-sm font-bold text-primary underline underline-offset-2"
                           x-text="code"></a>
                        <div class="flex gap-2">
                            <a :href="`{{ route('booking.lookup.form') }}?code=${encodeURIComponent(code)}`"
                               class="btn-gold-cinema inline-flex min-h-9 items-center rounded-lg px-3.5 text-xs font-bold shadow-xs">
                                Cek status
                            </a>
                            <button type="button"
                                    @click="removeCode(code)"
                                    class="inline-flex min-h-9 items-center rounded-lg border border-border px-3 text-xs font-medium text-text-muted hover:border-danger hover:text-danger">
                                Hapus
                            </button>
                        </div>
                    </li>
                </template>
            </ul>
        </section>

        {{-- JADWAL 7 HARI KE DEPAN (Memenuhi assertSee('Jadwal 7 hari ke depan')) --}}
        <section id="jadwal" class="scroll-mt-12 pt-12 sm:pt-16">
            <div class="flex flex-wrap items-end justify-between gap-3 border-b border-border pb-4">
                <div>
                    <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-amber-500">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Pilih Waktu Menonton</span>
                    </div>
                    <h2 class="mt-1 text-2xl font-black tracking-tight text-text sm:text-3xl">Jadwal 7 hari ke depan</h2>
                    <p class="mt-0.5 text-xs sm:text-sm text-text-muted">Pilih tanggal untuk melihat ketersediaan kursi di Studio 1. Tiket mulai Rp {{ number_format($weekdayPrice, 0, ',', '.') }} per orang.</p>
                </div>
            </div>

            {{-- Horizontal Scrolling Date Pills --}}
            <nav class="mt-6 flex snap-x gap-2.5 overflow-x-auto pb-3 scrollbar-none" aria-label="Jadwal 7 hari ke depan">
                @foreach ($dates as $date)
                    @php($dateKey = $date->toDateString())
                    <a href="{{ route('schedule.date', ['date' => $dateKey]) }}"
                       class="group min-w-36 shrink-0 snap-start rounded-2xl border border-border bg-surface p-3.5 transition-all duration-200 hover:border-primary hover:shadow-[0_8px_20px_-4px_rgba(212,175,55,0.2)]">
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-xs font-bold text-text-muted group-hover:text-primary">
                                {{ $loop->first ? 'Hari ini' : $date->locale('id')->translatedFormat('l') }}
                            </span>
                            @if ($holidayDates->has($dateKey))
                                <span class="rounded-md bg-amber-500/20 px-1.5 py-0.5 text-[10px] font-bold text-amber-400">Libur</span>
                            @endif
                        </div>
                        <span class="mt-1.5 block font-tabular text-xl font-black text-text group-hover:text-primary">
                            {{ $date->format('d M') }}
                        </span>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @forelse ($sessionsByDate->get($dateKey, collect()) as $session)
                                <span class="rounded-md bg-surface-2 px-2 py-0.5 font-tabular text-[11px] font-bold text-text">
                                    {{ substr((string) $session->start_time, 0, 5) }}
                                </span>
                            @empty
                                <span class="text-[11px] text-text-muted">Jadwal menyusul</span>
                            @endforelse
                        </div>
                    </a>
                @endforeach
            </nav>
        </section>

        {{-- DAFTAR FILM TERSEDIA (Memenuhi assertSee('Film tersedia')) --}}
        <section class="pt-12 sm:pt-16">
            <div class="flex flex-wrap items-end justify-between gap-3 border-b border-border pb-4">
                <div>
                    <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-amber-500">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                        </svg>
                        <span>Now Showing & Coming Soon</span>
                    </div>
                    <h2 class="mt-1 text-2xl font-black tracking-tight text-text sm:text-3xl">Film tersedia</h2>
                    <p class="mt-0.5 text-xs sm:text-sm text-text-muted">Koleksi film pilihan di Studio Mini SynemaX. Klik poster untuk melihat trailer atau pesan tiket langsung.</p>
                </div>
                <span class="rounded-full bg-surface-2 px-3 py-1 font-tabular text-xs font-bold text-text-muted">
                    {{ $films->count() }} Judul Film
                </span>
            </div>

            {{-- Grid Poster Film Berasio 2:3 ala Bioskop Komersial --}}
            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                @forelse ($films as $film)
                    <article class="group relative flex flex-col overflow-hidden rounded-2xl border border-border bg-surface transition-all duration-300 hover:border-amber-400/60 hover:shadow-[0_12px_30px_-5px_rgba(212,175,55,0.25)]">
                        <div class="relative aspect-[2/3] w-full overflow-hidden bg-[#0A0D14]">
                            @if ($film->poster_path)
                                <img src="{{ Storage::url($film->poster_path) }}"
                                     alt="Poster {{ $film->title }}"
                                     loading="lazy"
                                     width="500"
                                     height="750"
                                     class="size-full object-cover transition duration-300 group-hover:scale-105">
                            @else
                                <div class="flex size-full flex-col items-center justify-center p-3 text-center">
                                    <svg class="size-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                    </svg>
                                    <span class="mt-2 text-xs font-bold text-slate-400">{{ $film->title }}</span>
                                </div>
                            @endif

                            {{-- Rating Usia Badge --}}
                            <span class="absolute right-2.5 top-2.5 rounded-lg bg-black/80 backdrop-blur-md px-2 py-0.5 font-tabular text-[10px] font-black uppercase text-amber-400 border border-amber-400/30">
                                {{ $film->age_rating }}
                            </span>

                            {{-- Durasi Badge --}}
                            <span class="absolute left-2.5 bottom-2.5 rounded-md bg-black/80 backdrop-blur-md px-2 py-0.5 font-tabular text-[10px] font-bold text-slate-200">
                                {{ $film->duration_minutes }}m
                            </span>

                            {{-- Hover Overlay dengan Tombol Trailer --}}
                            <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-black/70 p-3 opacity-0 backdrop-blur-xs transition duration-200 group-hover:opacity-100">
                                <button type="button"
                                        @click="openTrailer(@js($film))"
                                        class="inline-flex min-h-9 w-full items-center justify-center gap-1.5 rounded-lg bg-white/20 px-3 text-xs font-bold text-white hover:bg-white/30">
                                    <svg class="size-4 text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M8 5v14l11-7z" />
                                    </svg>
                                    <span>Trailer</span>
                                </button>
                                <a href="{{ route('home') }}#jadwal"
                                   class="btn-gold-cinema inline-flex min-h-9 w-full items-center justify-center rounded-lg px-3 text-xs font-bold shadow-xs">
                                    Pesan Tiket
                                </a>
                            </div>
                        </div>

                        {{-- Metadata Judul & Genre --}}
                        <div class="p-3">
                            <h3 class="text-sm font-bold text-text leading-snug line-clamp-1 group-hover:text-primary">
                                <a href="{{ route('home') }}#jadwal">
                                    {{ $film->title }}
                                </a>
                            </h3>
                            <p class="mt-1 text-xs text-text-muted truncate">
                                {{ $film->genre }}
                            </p>
                        </div>
                    </article>
                @empty
                    <p class="col-span-full rounded-2xl border border-border bg-surface p-6 text-center text-sm text-text-muted">
                        Katalog film sedang disiapkan pengelola studio. Silakan periksa kembali beberapa saat lagi.
                    </p>
                @endforelse
            </div>
        </section>

        {{-- ACARA NOBAR (Bila Aktif) --}}
        @if ($infoNobarEnabled && $upcomingNobar->isNotEmpty())
            <section class="pt-12 sm:pt-16">
                <div class="flex flex-wrap items-end justify-between gap-3 border-b border-border pb-4">
                    <div>
                        <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-cyan-400">
                            <span class="size-2 rounded-full bg-cyan-400 animate-pulse"></span>
                            <span>Acara Komunitas</span>
                        </div>
                        <h2 class="mt-1 text-2xl font-black tracking-tight text-text sm:text-3xl">Nobar di luar ruangan</h2>
                        <p class="mt-0.5 text-xs sm:text-sm text-text-muted">Nonton bareng seru di halaman terbuka SynemaX dengan layar tancap modern.</p>
                    </div>
                    <a href="{{ route('nobar.index') }}" class="text-xs font-bold text-primary hover:underline">
                        Semua acara nobar
                    </a>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($upcomingNobar as $event)
                        <article class="flex flex-col justify-between rounded-2xl border border-border bg-surface p-5 shadow-sm transition hover:border-cyan-500/50">
                            <div>
                                @if ($event->hasPoster())
                                    <a href="{{ route('nobar.show', ['slug' => $event->slug]) }}" class="block aspect-[4/5] w-full overflow-hidden rounded-xl bg-surface-2 mb-4">
                                        <img src="{{ $event->posterUrl() }}"
                                             alt="{{ $event->posterAltText() }}"
                                             loading="lazy"
                                             class="size-full object-cover object-top transition duration-200 hover:scale-102">
                                    </a>
                                @endif
                                <span class="rounded-md bg-cyan-500/10 px-2 py-0.5 text-[11px] font-bold text-cyan-400">
                                    {{ $event->kickoff_at->locale('id')->translatedFormat('l, d F Y - H:i') }} WIB
                                </span>
                                <h3 class="mt-2 text-base font-bold text-text">
                                    <a href="{{ route('nobar.show', ['slug' => $event->slug]) }}" class="hover:text-primary">
                                        {{ $event->judul }}
                                    </a>
                                </h3>
                                <p class="mt-1.5 text-xs leading-relaxed text-text-muted line-clamp-2">
                                    {{ $event->ringkasan ?: 'Ajak teman dan keluarga meramaikan nobar seru di pelataran bioskop.' }}
                                </p>
                            </div>
                            <div class="mt-4 pt-3 border-t border-border flex items-center justify-between">
                                <span class="font-tabular text-xs font-bold text-emerald-400">Gratis / Terbuka</span>
                                <a href="{{ route('nobar.show', ['slug' => $event->slug]) }}" class="text-xs font-bold text-primary hover:underline">
                                    Rincian acara
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- KABAR & ARTIKEL TERBARU (Bila Aktif) --}}
        @if ($infoNobarEnabled && $latestArticles->isNotEmpty())
            <section class="pt-12 sm:pt-16">
                <div class="flex flex-wrap items-end justify-between gap-3 border-b border-border pb-4">
                    <div>
                        <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-amber-500">
                            <span>Informasi & Pengumuman</span>
                        </div>
                        <h2 class="mt-1 text-2xl font-black tracking-tight text-text sm:text-3xl">Kabar bioskop</h2>
                        <p class="mt-0.5 text-xs sm:text-sm text-text-muted">Seputar promo tiket, menu jajan baru, dan agenda spesial SynemaX.</p>
                    </div>
                    <a href="{{ route('articles.index') }}" class="text-xs font-bold text-primary hover:underline">
                        Semua kabar
                    </a>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($latestArticles as $article)
                        <article class="flex flex-col justify-between rounded-2xl border border-border bg-surface p-5 shadow-sm">
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="rounded-md bg-amber-500/10 px-2 py-0.5 text-[11px] font-bold text-amber-400">
                                        {{ $article->kategori ?: 'Info' }}
                                    </span>
                                    <span class="text-[11px] text-text-muted">
                                        {{ $article->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <h3 class="mt-2 text-base font-bold text-text">
                                    <a href="{{ route('articles.show', ['slug' => $article->slug]) }}" class="hover:text-primary">
                                        {{ $article->judul }}
                                    </a>
                                </h3>
                                <p class="mt-1.5 text-xs leading-relaxed text-text-muted line-clamp-3">
                                    {{ $article->ringkasan }}
                                </p>
                            </div>
                            <div class="mt-4 pt-3 border-t border-border">
                                <a href="{{ route('articles.show', ['slug' => $article->slug]) }}" class="text-xs font-bold text-primary hover:underline">
                                    Baca selengkapnya
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- HARGA TIKET & FAQ --}}
        <section class="mt-12 grid gap-6 border-t border-border pt-12 sm:grid-cols-2">
            <div class="rounded-2xl border border-border bg-surface p-6 shadow-sm">
                <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-amber-500">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Transparansi Tarif</span>
                </div>
                <h2 class="mt-1 text-xl font-black text-text">Harga tiket studio</h2>
                <p class="mt-1 text-xs text-text-muted">Tarif resmi per orang untuk setiap sesi pemutaran di Studio 1.</p>
                <dl class="mt-4 divide-y divide-border rounded-xl border border-border bg-surface-2/40 px-4">
                    <div class="flex justify-between py-3 text-sm">
                        <dt class="text-text-muted">Hari biasa (Senin sampai Kamis)</dt>
                        <dd class="font-tabular font-black text-amber-400">Rp {{ number_format($weekdayPrice, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between py-3 text-sm">
                        <dt class="text-text-muted">Akhir pekan (Jumat sampai Minggu)</dt>
                        <dd class="font-tabular font-black text-amber-400">Rp {{ number_format($weekendPrice, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between py-3 text-sm">
                        <dt class="text-text-muted">Hari libur nasional</dt>
                        <dd class="font-tabular font-black text-amber-400">Rp {{ number_format($holidayPrice, 0, ',', '.') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl border border-border bg-surface p-6 shadow-sm">
                <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-amber-500">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Informasi Pemesanan</span>
                </div>
                <h2 class="mt-1 text-xl font-black text-text">Pertanyaan yang sering diajukan</h2>
                <div class="mt-4 space-y-2.5">
                    <details class="group rounded-xl border border-border bg-surface-2/40 p-3.5">
                        <summary class="cursor-pointer font-bold text-sm text-text flex items-center justify-between">
                            <span>Perlu membuat akun sebelum memesan?</span>
                            <span class="text-amber-400 text-xs transition group-open:rotate-180">▼</span>
                        </summary>
                        <p class="mt-2 text-xs leading-relaxed text-text-muted">Tidak perlu. Anda cukup mengisi nama dan nomor WhatsApp saat booking. E-tiket instan diterbitkan langsung.</p>
                    </details>
                    <details class="group rounded-xl border border-border bg-surface-2/40 p-3.5">
                        <summary class="cursor-pointer font-bold text-sm text-text flex items-center justify-between">
                            <span>Bisa memesan seluruh studio untuk rombongan?</span>
                            <span class="text-amber-400 text-xs transition group-open:rotate-180">▼</span>
                        </summary>
                        <p class="mt-2 text-xs leading-relaxed text-text-muted">Bisa. Pilih opsi Sewa Studio Privat pada formulir pemesanan untuk mengunci seluruh 6 kursi khusus rombongan Anda.</p>
                    </details>
                    <details class="group rounded-xl border border-border bg-surface-2/40 p-3.5">
                        <summary class="cursor-pointer font-bold text-sm text-text flex items-center justify-between">
                            <span>Bagaimana cara melakukan pembayaran?</span>
                            <span class="text-amber-400 text-xs transition group-open:rotate-180">▼</span>
                        </summary>
                        <p class="mt-2 text-xs leading-relaxed text-text-muted">Kami mendukung QRIS instan, Transfer Bank, atau bayar langsung di kasir studio sebelum penayangan.</p>
                    </details>
                </div>
            </div>
        </section>

        {{-- BANTUAN WHATSAPP --}}
        @if ($adminWhatsAppUrl)
            <section class="mt-12 rounded-3xl border border-amber-400/20 bg-gradient-to-r from-amber-500/10 via-surface to-surface p-8 text-center shadow-lg">
                <h2 class="text-xl font-black text-text">Ada pertanyaan seputar jadwal atau reservasi privat?</h2>
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

        {{-- MODAL TRAILER FILM INTERAKTIF (Ala Bioskop Modern) --}}
        <div x-show="trailerModal"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/85 p-4 backdrop-blur-md"
             @keydown.escape.window="closeTrailer()"
             @click.self="closeTrailer()">
            <div class="relative w-full max-w-3xl overflow-hidden rounded-3xl border border-amber-400/30 bg-[#0E131F] shadow-[0_25px_60px_-15px_rgba(0,0,0,0.9)] animate-in fade-in zoom-in-95 duration-200">
                {{-- Header Modal --}}
                <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
                    <div class="flex items-center gap-2.5">
                        <span class="flex size-7 items-center justify-center rounded-lg bg-amber-500 text-black">
                            <svg class="size-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M8 5v14l11-7z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-base font-black text-white" x-text="activeFilm?.title"></h3>
                            <p class="text-[11px] text-amber-400" x-text="activeFilm ? activeFilm.genre + ' • ' + activeFilm.duration_minutes + ' Menit' : ''"></p>
                        </div>
                    </div>
                    <button type="button"
                            @click="closeTrailer()"
                            class="rounded-xl border border-white/10 bg-white/5 p-2 text-slate-300 hover:bg-white/15 hover:text-white">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Player Video YouTube / Placeholder --}}
                <div class="aspect-video w-full bg-black">
                    <template x-if="activeFilm && activeFilm.trailer_url">
                        <iframe :src="getEmbedUrl(activeFilm.trailer_url)"
                                class="size-full"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen></iframe>
                    </template>
                    <template x-if="activeFilm && !activeFilm.trailer_url">
                        <div class="flex size-full flex-col items-center justify-center p-6 text-center text-slate-400">
                            <svg class="size-12 text-amber-500/50 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <p class="text-sm font-bold text-white">Trailer Resmi Segera Tayang</p>
                            <p class="mt-1 text-xs max-w-sm" x-text="activeFilm?.synopsis"></p>
                        </div>
                    </template>
                </div>

                {{-- Footer Modal --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-white/10 p-4 bg-[#090D17]">
                    <p class="text-xs text-slate-400 line-clamp-1 flex-1 min-w-0" x-text="activeFilm?.synopsis"></p>
                    <a href="#jadwal"
                       @click="closeTrailer()"
                       class="btn-gold-cinema inline-flex min-h-10 items-center justify-center rounded-xl px-5 text-xs font-black shadow-md">
                        Pesan Tiket Sesi Ini
                    </a>
                </div>
            </div>
        </div>

    </div>
@endsection
