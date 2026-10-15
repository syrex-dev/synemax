@extends('layouts.public')

@section('title', 'SynemaX Home Cinema — Film pilihanmu, suasana rumah')
@section('meta')
    <meta name="description" content="Pilih film dan jadwal, lalu booking bioskop kecil yang hangat bareng keluarga atau teman. Kapasitas enam kursi per sesi.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="SynemaX Home Cinema — Nonton dekat, seru bareng">
    <meta property="og:description" content="Film pilihanmu, suasana rumah. Pilih jadwal dan booking tanpa bikin akun.">
    <meta property="og:image" content="{{ asset('social-banner.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:type" content="image/png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="SynemaX Home Cinema — Nonton dekat, seru bareng">
    <meta name="twitter:description" content="Pilih film dan jadwal, lalu booking gampang tanpa bikin akun.">
    <meta name="twitter:image" content="{{ asset('social-banner.png') }}">
@endsection

@section('content')
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <section class="relative isolate mt-5 overflow-hidden rounded-[2rem] border border-border bg-surface px-5 py-9 shadow-token sm:mt-8 sm:px-10 sm:py-14 lg:px-14">
            <div class="pointer-events-none absolute -right-16 -top-24 -z-10 size-80 rounded-full bg-accent/10 blur-3xl sm:size-[28rem]"></div>
            <div class="max-w-2xl">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-accent">Bioskop kecil, cerita besar</p>
                <h1 class="mt-4 text-4xl font-black leading-[1.08] tracking-tight text-text sm:text-6xl">Film pilihanmu,<br><span class="text-accent">suasana rumah.</span></h1>
                <p class="mt-5 max-w-xl text-base leading-7 text-text-muted sm:text-lg">Ajak keluarga atau teman nonton bareng. Pilih film, amankan kursi, dan kalau mau sekalian pesan jajan—tanpa bikin akun.</p>
                <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                    @if ($nextAvailableSession)
                        <a href="{{ route('schedule.date', ['date' => $nextAvailableSession->session_date]) }}" class="inline-flex min-h-14 w-full items-center justify-center rounded-xl bg-accent px-6 text-base font-bold text-accent-contrast shadow-token hover:opacity-90 sm:w-auto">Booking Sekarang</a>
                        <p class="self-center text-sm text-text-muted">Sesi terdekat: {{ $nextAvailableSession->startsAt()->locale('id')->translatedFormat('D, d M') }} · {{ substr((string) $nextAvailableSession->start_time, 0, 5) }}</p>
                    @else
                        <a href="#jadwal" class="inline-flex min-h-14 w-full items-center justify-center rounded-xl bg-accent px-6 text-base font-bold text-accent-contrast sm:w-auto">Lihat jadwal</a>
                        <p class="self-center text-sm text-text-muted">Jadwal baru segera dibuka, ya.</p>
                    @endif
                </div>
                <p class="mt-4 text-sm font-semibold text-text-muted">Tiket mulai Rp {{ number_format($weekdayPrice, 0, ',', '.') }} per orang · maksimal 6 kursi per sesi</p>
            </div>
            <div aria-hidden="true" class="pointer-events-none absolute bottom-0 right-12 hidden h-56 w-40 -rotate-6 rounded-t-[2rem] border border-accent/30 bg-surface-2 shadow-token md:block">
                <div class="absolute inset-4 rounded-2xl border border-border bg-bg"></div>
                <span class="absolute inset-x-0 bottom-8 text-center text-sm font-black tracking-[0.2em] text-accent">LAYAR KITA</span>
            </div>
        </section>

        <section id="booking-saya" x-data="{
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
                }" x-init="loadCodes()" x-show="codes.length > 0" x-cloak class="mt-5 rounded-2xl border border-border bg-surface p-4 shadow-token sm:p-5">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div><p class="text-sm font-bold uppercase tracking-[0.15em] text-accent">Perangkat ini</p><h2 class="mt-1 text-xl font-bold text-text">Booking Saya</h2></div>
                <p class="text-sm text-text-muted">Kode saja yang disimpan. Nomor WhatsApp tetap diminta saat cek status.</p>
            </div>
            <ul class="mt-3 space-y-2">
                <template x-for="code in codes" :key="code">
                    <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border bg-bg p-3">
                        <a :href="`{{ route('booking.lookup.form') }}?code=${encodeURIComponent(code)}`" class="min-h-11 flex-1 content-center font-mono text-base font-bold text-accent underline underline-offset-2" x-text="code"></a>
                        <button type="button" @click="removeCode(code)" class="min-h-11 rounded-lg border border-border px-3 text-sm font-semibold text-text">Hapus kode</button>
                    </li>
                </template>
            </ul>
        </section>

        <section id="jadwal" class="scroll-mt-6 pt-12 sm:pt-16">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">Langkah pertama</p>
                    <h2 class="mt-2 text-2xl font-black text-text sm:text-3xl">Pilih jadwal nonton</h2>
                </div>
                <p class="text-sm text-text-muted">Jadwal 7 hari ke depan</p>
            </div>
            <nav class="mt-5 flex snap-x gap-3 overflow-x-auto pb-3" aria-label="Jadwal untuk tujuh hari ke depan">
                @foreach ($dates as $date)
                    @php($dateKey = $date->toDateString())
                    <a href="{{ route('schedule.date', ['date' => $dateKey]) }}" class="min-w-40 snap-start rounded-2xl border border-border bg-surface p-4 transition hover:border-accent hover:shadow-token">
                        <span class="flex items-start justify-between gap-2">
                            <span class="font-bold text-text">{{ $loop->first ? 'Hari ini' : $date->locale('id')->translatedFormat('D, d M') }}</span>
                            @if ($holidayDates->has($dateKey))
                                <span class="rounded-full bg-warning/15 px-2 py-1 text-xs font-bold text-warning">Libur</span>
                            @endif
                        </span>
                        <span class="mt-3 flex min-h-8 flex-wrap gap-2">
                            @forelse ($sessionsByDate->get($dateKey, collect()) as $session)
                                <span class="rounded-lg bg-surface-2 px-2 py-1 text-xs font-semibold text-text">{{ substr((string) $session->start_time, 0, 5) }}</span>
                            @empty
                                <span class="text-sm text-text-muted">Jadwal menyusul</span>
                            @endforelse
                        </span>
                    </a>
                @endforeach
            </nav>
        </section>

        <section class="pt-12 sm:pt-16">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">Pilih tontonan</p>
                    <h2 class="mt-2 text-2xl font-black text-text sm:text-3xl">Film tersedia</h2>
                </div>
                <span class="text-sm text-text-muted">{{ $films->count() }} film</span>
            </div>
            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4">
                @forelse ($films as $film)
                    <article class="overflow-hidden rounded-2xl border border-border bg-surface shadow-token">
                        <div class="relative aspect-[2/3] overflow-hidden bg-surface-2" @if ($film->poster_path) x-data="{ posterLoaded: false }" x-init="$nextTick(() => posterLoaded = $refs.poster.complete)" @endif>
                            @if ($film->poster_path)
                                <x-skeleton x-show="!posterLoaded" class="absolute inset-0 rounded-none" />
                                <img x-ref="poster" x-on:load="posterLoaded = true" x-on:error="posterLoaded = true" src="{{ Storage::url($film->poster_path) }}" alt="Poster {{ $film->title }}" loading="lazy" decoding="async" width="500" height="750" class="relative size-full object-cover">
                            @else
                                <div class="absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-overlay to-transparent p-3 sm:p-4">
                                    <span class="mb-2 text-xs font-bold uppercase tracking-[0.14em] text-accent">Pilihan studio</span>
                                    <span class="text-lg font-black leading-tight text-text sm:text-xl">{{ $film->title }}</span>
                                </div>
                            @endif
                            <span class="absolute right-2 top-2 rounded-lg bg-overlay px-2 py-1 text-xs font-bold text-text">{{ $film->age_rating }}</span>
                        </div>
                        <div class="p-3 sm:p-4">
                            <h3 class="line-clamp-2 font-bold leading-snug text-text">{{ $film->title }}</h3>
                            <p class="mt-1 text-sm text-text-muted">{{ $film->genre }} · {{ $film->duration_minutes }} menit</p>
                            <p class="mt-3 line-clamp-3 text-sm leading-5 text-text-muted">{{ $film->synopsis }}</p>
                            @if ($film->trailer_url)
                                <a href="{{ $film->trailer_url }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-accent hover:underline">▶ Lihat trailer</a>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="col-span-full rounded-xl border border-border bg-surface p-5 text-base text-text-muted">Katalog film sedang disiapkan admin. Coba cek lagi sebentar, ya.</p>
                @endforelse
            </div>
        </section>

        {{-- Section Nobar Komunitas --}}
        @if ($infoNobarEnabled && $upcomingNobar->isNotEmpty())
            <section class="pt-12 sm:pt-16">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">Nonton Bareng Sepak Bola</p>
                        <h2 class="mt-2 text-2xl font-black text-text sm:text-3xl">Nobar Komunitas</h2>
                    </div>
                    <a href="{{ route('nobar.index') }}" class="inline-flex items-center gap-1 text-sm font-bold text-accent hover:underline">
                        <span>Semua Acara Nobar</span>
                        <span>→</span>
                    </a>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($upcomingNobar as $event)
                        <article class="flex flex-col justify-between overflow-hidden rounded-2xl border border-border bg-surface p-5 shadow-token transition hover:border-accent">
                            <div>
                                @if ($event->hasPoster())
                                    <a href="{{ route('nobar.show', ['slug' => $event->slug]) }}" class="block aspect-[4/5] w-full overflow-hidden bg-surface-2 rounded-xl mb-4">
                                        <img src="{{ $event->posterUrl() }}" alt="{{ $event->posterAltText() }}" class="size-full object-cover object-top transition duration-300 hover:scale-105" loading="lazy">
                                    </a>
                                @endif
                                <div class="flex items-center justify-between gap-2">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $event->badgeClasses() }}">
                                        {{ $event->display_status }}
                                    </span>
                                    <span class="text-xs font-semibold text-text-muted">
                                        {{ $event->is_free ? 'Gratis' : ($event->harga_info ?: 'Berbayar') }}
                                    </span>
                                </div>
                                <h3 class="mt-3 text-lg font-bold text-text line-clamp-2">
                                    <a href="{{ route('nobar.show', ['slug' => $event->slug]) }}" class="hover:text-accent">
                                        {{ $event->judul }}
                                    </a>
                                </h3>
                                <div class="mt-4 flex items-center justify-center rounded-xl bg-surface-2 p-3 text-center">
                                    <span class="font-bold text-text">{{ $event->tim_a }}</span>
                                    <span class="mx-2 text-xs font-black uppercase text-accent">VS</span>
                                    <span class="font-bold text-text">{{ $event->tim_b ?: 'TBA' }}</span>
                                </div>
                                <p class="mt-3 text-xs text-text-muted flex items-center gap-2">
                                    <svg class="size-4 shrink-0 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>{{ $event->kickoff_at->locale('id')->translatedFormat('l, d F Y · H:i') }} WIB</span>
                                </p>
                                <p class="mt-1.5 text-xs text-text-muted flex items-center gap-2">
                                    <svg class="size-4 shrink-0 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="truncate">{{ $event->lokasi_nama }}</span>
                                </p>
                            </div>
                            <div class="mt-5 pt-4 border-t border-border">
                                <a href="{{ route('nobar.show', ['slug' => $event->slug]) }}" class="inline-flex min-h-10 w-full items-center justify-center rounded-xl border border-border bg-surface-2 px-4 text-xs font-bold text-text transition hover:border-accent hover:text-accent">
                                    Lihat Detail & Lokasi
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Section Kabar & Promo --}}
        @if ($infoNobarEnabled && $latestArticles->isNotEmpty())
            <section class="pt-12 sm:pt-16">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">Informasi & Penawaran</p>
                        <h2 class="mt-2 text-2xl font-black text-text sm:text-3xl">Kabar & Promo</h2>
                    </div>
                    <a href="{{ route('articles.index') }}" class="inline-flex items-center gap-1 text-sm font-bold text-accent hover:underline">
                        <span>Semua Kabar</span>
                        <span>→</span>
                    </a>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($latestArticles as $article)
                        <article class="flex flex-col justify-between overflow-hidden rounded-2xl border border-border bg-surface shadow-token transition hover:border-accent">
                            @if ($article->cover_path)
                                <a href="{{ route('articles.show', ['slug' => $article->slug]) }}" class="block aspect-video overflow-hidden bg-surface-2">
                                    <img src="{{ $article->coverUrl() }}" alt="{{ $article->judul }}" loading="lazy" class="h-full w-full object-cover transition duration-300 hover:scale-105">
                                </a>
                            @endif
                            <div class="flex-1 p-5">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $article->kategori === 'Berita' ? 'bg-blue-500/15 text-blue-500' : ($article->kategori === 'Promo' ? 'bg-emerald-500/15 text-emerald-500' : 'bg-amber-500/15 text-amber-500') }}">
                                        {{ $article->kategori }}
                                    </span>
                                    <span class="text-[11px] text-text-muted">
                                        {{ $article->tanggal_terbit ? $article->tanggal_terbit->locale('id')->diffForHumans() : '' }}
                                    </span>
                                </div>
                                <h3 class="mt-2.5 text-base font-bold text-text line-clamp-2">
                                    <a href="{{ route('articles.show', ['slug' => $article->slug]) }}" class="hover:text-accent">
                                        {{ $article->judul }}
                                    </a>
                                </h3>
                                <p class="mt-2 text-xs leading-relaxed text-text-muted line-clamp-3">
                                    {{ $article->ringkasan }}
                                </p>
                            </div>
                            <div class="p-5 pt-0">
                                <a href="{{ route('articles.show', ['slug' => $article->slug]) }}" class="inline-flex items-center gap-1 text-xs font-bold text-accent hover:underline">
                                    <span>Baca selengkapnya</span>
                                    <span>→</span>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="grid gap-8 py-12 sm:grid-cols-2 sm:py-16">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">Gampang kok</p>
                <h2 class="mt-2 text-2xl font-black text-text sm:text-3xl">Nonton dalam 3 langkah</h2>
                <ol class="mt-5 space-y-4">
                    @foreach ([['1', 'Pilih sesi dan film', 'Cek hari, jam, lalu pilih tontonan yang kamu suka.'], ['2', 'Isi data dan pesan jajan', 'Tulis nama serta WhatsApp. Jajan boleh dilewati.'], ['3', 'Simpan kode booking', 'Admin akan mengonfirmasi lewat WhatsApp.']] as [$number, $title, $copy])
                        <li class="flex gap-3 rounded-xl border border-border bg-surface p-4">
                            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-accent/15 font-black text-accent">{{ $number }}</span>
                            <span><strong class="block text-text">{{ $title }}</strong><span class="mt-1 block text-sm text-text-muted">{{ $copy }}</span></span>
                        </li>
                    @endforeach
                </ol>
            </div>
            <div class="space-y-7">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">Harga tiket</p>
                    <h2 class="mt-2 text-2xl font-black text-text">Jelas dari awal</h2>
                    <dl class="mt-4 divide-y divide-border rounded-xl border border-border bg-surface px-4">
                        <div class="flex justify-between gap-4 py-3 text-base"><dt class="text-text-muted">Hari biasa</dt><dd class="font-bold text-text">Rp {{ number_format($weekdayPrice, 0, ',', '.') }} / orang</dd></div>
                        <div class="flex justify-between gap-4 py-3 text-base"><dt class="text-text-muted">Akhir pekan</dt><dd class="font-bold text-text">Rp {{ number_format($weekendPrice, 0, ',', '.') }} / orang</dd></div>
                        <div class="flex justify-between gap-4 py-3 text-base"><dt class="text-text-muted">Hari libur</dt><dd class="font-bold text-text">Rp {{ number_format($holidayPrice, 0, ',', '.') }} / orang</dd></div>
                    </dl>
                </div>
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">Yang sering ditanya</p>
                    <div class="mt-3 space-y-2">
                        <details class="rounded-xl border border-border bg-surface p-4">
                            <summary class="min-h-8 cursor-pointer content-center font-bold text-text">Perlu bikin akun?</summary>
                            <p class="mt-2 text-sm leading-6 text-text-muted">Nggak perlu. Booking cukup pakai nama dan nomor WhatsApp.</p>
                        </details>
                        <details class="rounded-xl border border-border bg-surface p-4">
                            <summary class="min-h-8 cursor-pointer content-center font-bold text-text">Bisa pesan jajan belakangan?</summary>
                            <p class="mt-2 text-sm leading-6 text-text-muted">Pesanan jajan tidak bisa diubah sendiri setelah booking. Hubungi admin lewat WhatsApp, ya.</p>
                        </details>
                        <details class="rounded-xl border border-border bg-surface p-4">
                            <summary class="min-h-8 cursor-pointer content-center font-bold text-text">Bagaimana cara bayar?</summary>
                            <p class="mt-2 text-sm leading-6 text-text-muted">Cara bayar yang tersedia akan terlihat sebelum booking dikirim. Ikuti petunjuk resmi dari admin.</p>
                        </details>
                    </div>
                </div>
            </div>
        </section>

        <section class="mb-12 rounded-2xl border border-border bg-surface p-5 text-center shadow-token sm:mb-16 sm:p-8">
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">Butuh bantuan?</p>
            <h2 class="mt-2 text-2xl font-black text-text">Tanya admin studio</h2>
            <p class="mt-2 text-base text-text-muted">Kami bantu cek jadwal dan pertanyaan bookingmu.</p>
            @if ($adminWhatsAppUrl)
                <a href="{{ $adminWhatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-success px-5 font-bold text-success-contrast sm:w-auto">Chat admin via WhatsApp</a>
            @endif
        </section>
    </div>
@endsection
