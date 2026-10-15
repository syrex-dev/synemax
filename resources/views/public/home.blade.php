@extends('layouts.public')

@section('title', 'SynemaX: Bioskop Mini Desa')
@section('meta')
    <meta name="description" content="Nonton dekat, seru bareng di studio mini SynemaX. Pilih film dan amankan kursi tanpa harus membuat akun. Kapasitas enam kursi per sesi.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="SynemaX: Bioskop Mini Desa">
    <meta property="og:description" content="Film pilihanmu, suasana rumah. Pilih jadwal dan amankan kursimu dengan cepat.">
    <meta property="og:image" content="{{ asset('social-banner.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="SynemaX: Bioskop Mini Desa">
    <meta name="twitter:description" content="Film pilihanmu, suasana rumah. Pilih jadwal dan amankan kursimu dengan cepat.">
    <meta name="twitter:image" content="{{ asset('social-banner.png') }}">
@endsection

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10">
        {{-- HERO: Panel Layar Proyektor Menyala --}}
        <section class="hero-screen-panel p-5 sm:p-8 lg:p-10">
            @if ($nextAvailableSession && $nextAvailableSession->film)
                @php
                    $heroFilm = $nextAvailableSession->film;
                    $heroTime = substr((string) $nextAvailableSession->start_time, 0, 5) . ' WIB';
                    $heroDate = $nextAvailableSession->startsAt()->locale('id')->translatedFormat('l, d F Y');
                @endphp
                <div class="flex flex-col gap-6 sm:flex-row sm:items-center lg:gap-10">
                    <div class="w-32 shrink-0 sm:w-44 lg:w-52">
                        <div class="aspect-[2/3] w-full overflow-hidden rounded-[var(--radius-control)] bg-[#16201F]">
                            @if ($heroFilm->poster_path)
                                <img src="{{ Storage::url($heroFilm->poster_path) }}"
                                     alt="Poster {{ $heroFilm->title }}"
                                     loading="eager"
                                     fetchpriority="high"
                                     class="size-full object-cover">
                            @else
                                <div class="flex size-full items-center justify-center p-3 text-center text-xs font-bold text-white/70">
                                    {{ $heroFilm->title }}
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-[#4A605E]">Tayangan terdekat di Studio 1</p>
                        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#16201F] sm:text-4xl lg:text-5xl leading-tight">
                            {{ $heroFilm->title }}
                        </h1>
                        <p class="mt-2 text-xs font-medium text-[#4A605E]">
                            {{ $heroFilm->genre }}, {{ $heroFilm->duration_minutes }} menit, batas usia {{ $heroFilm->age_rating }}
                        </p>
                        <p class="mt-3 max-w-xl text-sm leading-relaxed text-[#16201F]/80">
                            {{ $heroFilm->synopsis ?: 'Ajak keluarga atau teman nonton bareng di studio mini berpendingin ruangan. Kapasitas enam kursi tiap sesi.' }}
                        </p>

                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <a href="{{ route('booking.create', ['session' => $nextAvailableSession->id]) }}"
                               class="inline-flex min-h-12 items-center justify-center rounded-[var(--radius-control)] bg-primary px-6 text-sm font-bold text-white shadow-xs transition hover:opacity-95 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                                Pesan tiket
                            </a>
                            <span class="inline-flex min-h-12 items-center rounded-[var(--radius-control)] bg-[#FFC62E] px-4 font-tabular text-sm font-bold text-[#16201F]">
                                {{ $heroTime }}
                            </span>
                        </div>
                        <p class="mt-2 text-xs text-[#4A605E]">
                            Sesi {{ $heroDate }}. Tiket hari biasa mulai Rp {{ number_format($weekdayPrice, 0, ',', '.') }} per orang.
                        </p>
                    </div>
                </div>
            @else
                <div class="max-w-2xl py-4 sm:py-6">
                    <p class="text-xs font-bold text-[#4A605E]">Studio mini desa</p>
                    <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#16201F] sm:text-5xl">
                        Film pilihanmu, suasana rumah.
                    </h1>
                    <p class="mt-4 text-base leading-relaxed text-[#16201F]/80">
                        Ajak keluarga atau teman menonton bareng di studio mini berpendingin ruangan. Pilih film, amankan kursi, dan pesan jajan tanpa ribet membuat akun.
                    </p>
                    <div class="mt-6">
                        <a href="#jadwal" class="inline-flex min-h-12 items-center justify-center rounded-[var(--radius-control)] bg-primary px-6 text-sm font-bold text-white shadow-xs hover:opacity-95">
                            Pesan tiket
                        </a>
                    </div>
                    <p class="mt-3 text-xs text-[#4A605E]">
                        Kapasitas maksimal 6 kursi per sesi. Tiket hari biasa mulai Rp {{ number_format($weekdayPrice, 0, ',', '.') }} per orang.
                    </p>
                </div>
            @endif
        </section>

        {{-- Booking Saya (Tersimpan Lokal di Perangkat Pengguna) --}}
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
                 class="mt-10 rounded-[var(--radius-control)] border border-border bg-surface p-5">
            <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-border pb-3">
                <h2 class="text-base font-bold text-text">Booking saya di perangkat ini</h2>
                <p class="text-xs text-text-muted">Nomor WhatsApp tetap diminta saat memeriksa status.</p>
            </div>
            <ul class="mt-4 space-y-2">
                <template x-for="code in codes" :key="code">
                    <li class="flex flex-wrap items-center justify-between gap-3 rounded-[var(--radius-control)] border border-border-input bg-surface-2 p-3">
                        <a :href="`{{ route('booking.lookup.form') }}?code=${encodeURIComponent(code)}`"
                           class="font-tabular text-sm font-bold text-primary underline underline-offset-2"
                           x-text="code"></a>
                        <div class="flex gap-2">
                            <a :href="`{{ route('booking.lookup.form') }}?code=${encodeURIComponent(code)}`"
                               class="inline-flex min-h-9 items-center rounded-[var(--radius-control)] bg-primary px-3 text-xs font-bold text-white">
                                Cek status
                            </a>
                            <button type="button"
                                    @click="removeCode(code)"
                                    class="inline-flex min-h-9 items-center rounded-[var(--radius-control)] border border-border-input px-3 text-xs font-medium text-text hover:border-danger-border hover:text-danger-text">
                                Hapus
                            </button>
                        </div>
                    </li>
                </template>
            </ul>
        </section>

        {{-- Jadwal 7 Hari --}}
        <section id="jadwal" class="scroll-mt-8 pt-12 sm:pt-16">
            <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-border pb-3">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-text sm:text-3xl">Pilih hari nonton</h2>
                    <p class="mt-1 text-sm text-text-muted">Jadwal 7 hari ke depan. Tiket mulai Rp {{ number_format($weekdayPrice, 0, ',', '.') }} per orang.</p>
                </div>
            </div>

            <nav class="mt-6 flex snap-x gap-2.5 overflow-x-auto pb-3" aria-label="Jadwal 7 hari ke depan">
                @foreach ($dates as $date)
                    @php($dateKey = $date->toDateString())
                    <a href="{{ route('schedule.date', ['date' => $dateKey]) }}"
                       class="min-w-36 snap-start rounded-[var(--radius-control)] border border-border-input bg-surface p-3.5 transition hover:border-primary">
                        <div class="flex items-start justify-between gap-1">
                            <span class="text-xs font-bold text-text">{{ $loop->first ? 'Hari ini' : $date->locale('id')->translatedFormat('l') }}</span>
                            @if ($holidayDates->has($dateKey))
                                <span class="rounded-[var(--radius-control)] bg-[#FFC62E] px-1.5 py-0.5 text-[10px] font-bold text-[#16201F]">Libur</span>
                            @endif
                        </div>
                        <span class="mt-1 block font-tabular text-base font-extrabold text-text">{{ $date->format('d M') }}</span>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @forelse ($sessionsByDate->get($dateKey, collect()) as $session)
                                <span class="rounded-[var(--radius-control)] bg-surface-2 px-1.5 py-0.5 font-tabular text-[11px] font-semibold text-text">
                                    {{ substr((string) $session->start_time, 0, 5) }}
                                </span>
                            @empty
                                <span class="text-xs text-text-muted">Menyusul</span>
                            @endforelse
                        </div>
                    </a>
                @endforeach
            </nav>
        </section>

        {{-- Dinding Poster Film (Rasio 2:3, Rapat Tanpa Bingkai Kartu) --}}
        <section class="pt-12 sm:pt-16">
            <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-border pb-3">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-text sm:text-3xl">Film tersedia</h2>
                    <p class="mt-1 text-sm text-text-muted">Pilihan tontonan di studio mini SynemaX minggu ini. Klik poster atau judul untuk melihat jadwal.</p>
                </div>
                <span class="font-tabular text-xs font-medium text-text-muted">{{ $films->count() }} film</span>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 md:grid-cols-4 xl:grid-cols-5">
                @forelse ($films as $film)
                    <article class="flex flex-col">
                        <a href="{{ route('home') }}#jadwal" class="group block overflow-hidden rounded-[var(--radius-control)] bg-surface-2">
                            <div class="relative aspect-[2/3] w-full overflow-hidden bg-surface-2">
                                @if ($film->poster_path)
                                    <img src="{{ Storage::url($film->poster_path) }}"
                                         alt="Poster {{ $film->title }}"
                                         loading="lazy"
                                         width="500"
                                         height="750"
                                         class="size-full object-cover transition duration-200 group-hover:scale-[1.02]">
                                @else
                                    <div class="flex size-full items-center justify-center p-3 text-center text-xs font-bold text-text-muted">
                                        {{ $film->title }}
                                    </div>
                                @endif
                                <span class="absolute right-2 top-2 rounded-[var(--radius-control)] bg-[#16201F]/80 px-1.5 py-0.5 font-tabular text-[10px] font-bold text-white">
                                    {{ $film->age_rating }}
                                </span>
                            </div>
                        </a>
                        <div class="mt-2.5">
                            <h3 class="text-sm font-bold text-text leading-snug">
                                <a href="{{ route('home') }}#jadwal" class="hover:text-primary">
                                    {{ $film->title }}
                                </a>
                            </h3>
                            <p class="mt-0.5 text-xs text-text-muted">
                                {{ $film->genre }}
                            </p>
                        </div>
                    </article>
                @empty
                    <p class="col-span-full rounded-[var(--radius-control)] border border-border bg-surface p-5 text-sm text-text-muted">
                        Katalog film sedang disiapkan pengelola studio. Silakan periksa kembali beberapa saat lagi.
                    </p>
                @endforelse
            </div>
        </section>

        {{-- Nobar di Luar Ruangan --}}
        @if ($infoNobarEnabled && $upcomingNobar->isNotEmpty())
            <section class="pt-12 sm:pt-16">
                <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-border pb-3">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-text sm:text-3xl">Nobar di luar ruangan</h2>
                        <p class="mt-1 text-sm text-text-muted">Nonton bareng gratis di halaman depan bioskop mini SynemaX.</p>
                    </div>
                    <a href="{{ route('nobar.index') }}" class="text-xs font-bold text-primary hover:underline">
                        Semua acara nobar
                    </a>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($upcomingNobar as $event)
                        <article class="flex flex-col justify-between rounded-[var(--radius-control)] border border-border bg-surface p-5">
                            <div>
                                @if ($event->hasPoster())
                                    <a href="{{ route('nobar.show', ['slug' => $event->slug]) }}" class="block aspect-[4/5] w-full overflow-hidden rounded-[var(--radius-control)] bg-surface-2 mb-4">
                                        <img src="{{ $event->posterUrl() }}"
                                             alt="{{ $event->posterAltText() }}"
                                             loading="lazy"
                                             class="size-full object-cover object-top transition duration-200 hover:scale-[1.02]">
                                    </a>
                                @endif

                                <div class="flex items-center justify-between gap-2">
                                    <span class="rounded-[var(--radius-control)] bg-surface-2 px-2 py-0.5 text-[11px] font-bold text-text">
                                        {{ $event->display_status }}
                                    </span>
                                    @if ($event->is_free)
                                        <span class="rounded-[var(--radius-control)] bg-[#FFC62E] px-2 py-0.5 text-[11px] font-bold text-[#16201F]">
                                            Gratis
                                        </span>
                                    @elseif ($event->harga_info)
                                        <span class="text-xs font-semibold text-text-muted">
                                            {{ $event->harga_info }}
                                        </span>
                                    @endif
                                </div>

                                <h3 class="mt-3 text-base font-bold text-text">
                                    <a href="{{ route('nobar.show', ['slug' => $event->slug]) }}" class="hover:text-primary">
                                        {{ $event->judul }}
                                    </a>
                                </h3>

                                <div class="mt-3 flex items-baseline justify-between rounded-[var(--radius-control)] bg-surface-2 p-3 text-sm">
                                    <span class="font-bold text-text">{{ $event->tim_a }}</span>
                                    <span class="text-xs text-text-muted">lawan</span>
                                    <span class="font-bold text-text">{{ $event->tim_b ?: 'TBA' }}</span>
                                </div>

                                <p class="mt-3 font-tabular text-xs font-bold text-text">
                                    Kickoff: {{ $event->kickoff_at->locale('id')->translatedFormat('l, d F Y') }}, {{ $event->kickoff_at->format('H.i') }} WIB
                                </p>
                                <p class="mt-1 text-xs text-text-muted">
                                    Lokasi: {{ $event->lokasi_nama }}
                                </p>
                            </div>

                            <div class="mt-5 pt-3 border-t border-border">
                                <a href="{{ route('nobar.show', ['slug' => $event->slug]) }}"
                                   class="inline-flex min-h-11 w-full items-center justify-center rounded-[var(--radius-control)] bg-primary px-4 text-xs font-bold text-white shadow-xs hover:opacity-95">
                                    Lihat detail nobar
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Kabar dan Pengumuman --}}
        @if ($infoNobarEnabled && $latestArticles->isNotEmpty())
            <section class="pt-12 sm:pt-16">
                <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-border pb-3">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-text sm:text-3xl">Kabar dan pengumuman</h2>
                        <p class="mt-1 text-sm text-text-muted">Informasi terbaru seputar jadwal dan kegiatan bioskop.</p>
                    </div>
                    <a href="{{ route('articles.index') }}" class="text-xs font-bold text-primary hover:underline">
                        Semua kabar
                    </a>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($latestArticles as $article)
                        <article class="flex flex-col justify-between rounded-[var(--radius-control)] border border-border bg-surface p-5">
                            <div>
                                @if ($article->cover_path)
                                    <a href="{{ route('articles.show', ['slug' => $article->slug]) }}" class="block aspect-video w-full overflow-hidden rounded-[var(--radius-control)] bg-surface-2 mb-3">
                                        <img src="{{ $article->coverUrl() }}" alt="{{ $article->judul }}" loading="lazy" class="size-full object-cover transition duration-200 hover:scale-[1.02]">
                                    </a>
                                @endif
                                <div class="flex items-center justify-between gap-2">
                                    <span class="rounded-[var(--radius-control)] bg-surface-2 px-2 py-0.5 text-[11px] font-bold text-text">
                                        {{ $article->kategori }}
                                    </span>
                                    <span class="text-[11px] text-text-muted">
                                        {{ $article->tanggal_terbit ? $article->tanggal_terbit->locale('id')->diffForHumans() : '' }}
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

        {{-- Bantuan & Informasi Biaya --}}
        <section class="mt-12 grid gap-6 border-t border-border pt-12 sm:grid-cols-2">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-text">Harga tiket studio</h2>
                <p class="mt-1 text-sm text-text-muted">Tarif resmi per orang untuk setiap sesi penayangan.</p>
                <dl class="mt-4 divide-y divide-border rounded-[var(--radius-control)] border border-border bg-surface px-4">
                    <div class="flex justify-between py-3 text-sm">
                        <dt class="text-text-muted">Hari biasa (Senin sampai Kamis)</dt>
                        <dd class="font-tabular font-bold text-text">Rp {{ number_format($weekdayPrice, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between py-3 text-sm">
                        <dt class="text-text-muted">Akhir pekan (Jumat sampai Minggu)</dt>
                        <dd class="font-tabular font-bold text-text">Rp {{ number_format($weekendPrice, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between py-3 text-sm">
                        <dt class="text-text-muted">Hari libur nasional</dt>
                        <dd class="font-tabular font-bold text-text">Rp {{ number_format($holidayPrice, 0, ',', '.') }}</dd>
                    </div>
                </dl>
            </div>

            <div>
                <h2 class="text-xl font-bold tracking-tight text-text">Pertanyaan yang sering diajukan</h2>
                <div class="mt-4 space-y-2">
                    <details class="rounded-[var(--radius-control)] border border-border bg-surface p-3.5">
                        <summary class="cursor-pointer font-bold text-sm text-text">Perlu membuat akun sebelum memesan?</summary>
                        <p class="mt-2 text-xs leading-relaxed text-text-muted">Tidak perlu. Anda cukup mengisi nama pemesan dan nomor WhatsApp saat booking.</p>
                    </details>
                    <details class="rounded-[var(--radius-control)] border border-border bg-surface p-3.5">
                        <summary class="cursor-pointer font-bold text-sm text-text">Bisa memesan seluruh studio untuk rombongan?</summary>
                        <p class="mt-2 text-xs leading-relaxed text-text-muted">Bisa. Pilih opsi Sewa Studio Privat pada langkah tipe nonton untuk mengunci seluruh 6 kursi khusus rombongan Anda.</p>
                    </details>
                    <details class="rounded-[var(--radius-control)] border border-border bg-surface p-3.5">
                        <summary class="cursor-pointer font-bold text-sm text-text">Bagaimana cara membayar?</summary>
                        <p class="mt-2 text-xs leading-relaxed text-text-muted">Pembayaran dapat diselesaikan melalui QRIS, transfer bank, atau bayar langsung di tempat sebelum masuk studio.</p>
                    </details>
                </div>
            </div>
        </section>

        {{-- Kontak Admin --}}
        @if ($adminWhatsAppUrl)
            <section class="mt-12 rounded-[var(--radius-control)] border border-border bg-surface p-6 text-center">
                <h2 class="text-lg font-bold text-text">Ada pertanyaan seputar jadwal atau sewa studio?</h2>
                <p class="mt-1 text-xs text-text-muted">Pengelola studio siap membantu konfirmasi pemesanan dan pertanyaan Anda.</p>
                <div class="mt-4">
                    <a href="{{ $adminWhatsAppUrl }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] bg-primary px-5 text-xs font-bold text-white shadow-xs hover:opacity-95">
                        Hubungi pengelola via WhatsApp
                    </a>
                </div>
            </section>
        @endif
    </div>
@endsection
