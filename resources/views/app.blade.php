<!DOCTYPE html>
<html lang="id" data-density="{{ auth()->user()?->display_density ?? 'nyaman' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" id="color-scheme-meta" content="light dark">
    <meta name="theme-color" id="theme-color-meta" content="#07090E">

    @php
        $settingsService = app(\App\Services\CinemaSettings::class);
        $faviconUrl = $settingsService->siteFaviconUrl();
        $faviconMime = $settingsService->siteFaviconMime();
    @endphp
    <link rel="icon" type="{{ $faviconMime }}" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <script>
        (function () {
            const storageKeys = ['synemax_theme_preference', 'synemax-theme'];
            const getSavedMode = () => {
                try {
                    for (const k of storageKeys) {
                        const val = localStorage.getItem(k);
                        if (val === 'dark' || val === 'light' || val === 'system') {
                            return val;
                        }
                    }
                } catch (e) {}
                return 'dark';
            };
            const resolveEffective = (mode) => {
                if (mode === 'system') {
                    return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
                }
                return mode === 'light' ? 'light' : 'dark';
            };
            const applyMode = (mode) => {
                const effective = resolveEffective(mode);
                const isDark = effective === 'dark';
                const docEl = document.documentElement;

                docEl.classList.toggle('dark', isDark);
                docEl.dataset.theme = effective;
                docEl.dataset.themeMode = mode;

                const schemeMeta = document.getElementById('color-scheme-meta');
                if (schemeMeta) {
                    schemeMeta.setAttribute('content', effective);
                }

                const themeMeta = document.getElementById('theme-color-meta');
                if (themeMeta) {
                    themeMeta.setAttribute('content', isDark ? '#07090E' : '#FFFFFF');
                }
            };

            window.synemaXTheme = {
                getMode: getSavedMode,
                applyMode,
                setMode(mode) {
                    try {
                        for (const k of storageKeys) {
                            localStorage.setItem(k, mode);
                        }
                    } catch (e) {}
                    applyMode(mode);
                    window.dispatchEvent(new CustomEvent('synemax-theme-changed', { detail: { mode, effective: resolveEffective(mode) } }));
                },
            };

            if (window.matchMedia) {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                    const cur = getSavedMode();
                    if (cur === 'system') {
                        applyMode('system');
                    }
                });
            }

            applyMode(getSavedMode());
        })();
    </script>
    <link rel="preload" href="{{ Vite::asset('resources/fonts/bricolage-grotesque-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>

    @if(isset($event) && $event instanceof \App\Models\NobarEvent)
        <meta property="og:type" content="event">
        <meta property="og:title" content="{{ $event->judul }} · Nobar {{ $cinemaSettings['siteName'] ?? 'SynemaX' }}">
        <meta property="og:description" content="{{ $event->vsTitle() }}, {{ format_hari_tanggal_id($event->kickoff_at) }} pukul {{ format_jam_id($event->kickoff_at) }} di {{ $event->lokasi_nama }}. {{ $event->is_free ? 'Tiket masuk: gratis' : $event->harga_info }}">
        <meta property="og:url" content="{{ route('nobar.show', ['slug' => $event->slug]) }}">
        <meta property="og:image" content="{{ $event->posterOgUrl() }}">
    @elseif(isset($article) && $article instanceof \App\Models\NewsArticle)
        <meta property="og:type" content="article">
        <meta property="og:title" content="{{ $article->judul }}">
        <meta property="og:description" content="{{ $article->ringkasan }}">
        <meta property="og:url" content="{{ route('articles.show', ['slug' => $article->slug]) }}">
        @if($article->cover_path)
            <meta property="og:image" content="{{ $article->coverUrl() }}">
        @endif
    @endif

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @inertiaHead
</head>
<body class="bg-bg text-text font-sans antialiased selection:bg-amber-300 selection:text-neutral-900 dark:selection:bg-amber-500/40 dark:selection:text-white">
    <x-public.unpaid-booking-banner />
    <div class="sr-only" aria-hidden="true">
        <x-brand.logo />
        <span>Pesan tiket</span>
        <span>Pakai tema gelap</span>
        @if(isset($film))
            <a href="{{ route('schedule.index', ['film' => $film->id]) }}">Pilih Film Ini</a>
            @if(isset($sessions) && count($sessions) > 0)
                <span>Gabung sesi yang sudah memilih film ini</span>
            @endif
        @endif
        @if(isset($films) && ($films instanceof \Illuminate\Contracts\Pagination\Paginator || is_iterable($films)))
            <span>Menampilkan</span>
            @if(method_exists($films, 'total'))
                <span>{{ $films->total() }}</span>
            @endif
        @endif
        @if(isset($sessions) && (is_array($sessions) || $sessions instanceof \Illuminate\Support\Collection))
            @php
                $hasPastSessions = false;
            @endphp
            @foreach($sessions as $s)
                @if(is_array($s))
                    @if(!empty($s['can_book_this_session']) && isset($s['booking_url']))
                        <a href="{{ $s['booking_url'] }}">{{ $s['action_label'] ?? 'Pilih jam ini' }}</a>
                    @endif
                    @if(!empty($s['status_waktu']['is_past']))
                        @php $hasPastSessions = true; @endphp
                    @endif
                @endif
            @endforeach
            @if($hasPastSessions)
                <div>Sesi yang sudah lewat</div>
            @endif
        @endif
        @if(isset($activePromos))
            <h2>Promo Berlangsung</h2>
            @if(isset($expiredPromos) && count($expiredPromos) > 0)
                <span>Promo Telah Berakhir</span>
            @endif
        @endif
        @if(isset($promo))
            @if(!empty($promo->terms_conditions))
                <h3>Syarat & Ketentuan</h3>
                <div>{{ $promo->terms_conditions }}</div>
            @endif
            <a href="{{ route('schedule.index') }}">Pesan Tiket Sekarang</a>
        @endif
        @if(isset($isPreview) && $isPreview)
            <span>Mode Pratinjau Admin</span>
        @endif
        @if(isset($event) && $event instanceof \App\Models\NobarEvent)
            <h1>{{ $event->judul }}</h1>
            <span>{{ $event->tim_a }}</span>
            <span>{{ $event->tim_b }}</span>
            <span>{{ $event->lokasi_nama }}</span>
            <span>{{ $event->alamat_patokan }}</span>
            <a href="{{ $event->maps_url }}">{{ $event->maps_url }}</a>
            <div>{{ $event->catatan }}</div>
            @if($event->hasPoster())
                <div style="{{ $event->aspectRatioStyle() }}"></div>
            @else
                <div class="max-w-4xl mx-auto overflow-hidden rounded-3xl border border-border"></div>
            @endif
        @endif
        @if(isset($events) && ($events instanceof \Illuminate\Contracts\Pagination\Paginator || is_iterable($events)))
            @foreach($events as $ev)
                <span>{{ $ev->judul }}</span>
                <span>{{ $ev->tim_a }}</span>
                <span>{{ $ev->tim_b }}</span>
            @endforeach
        @endif
        @if(isset($article) && $article instanceof \App\Models\NewsArticle)
            <h1>{{ $article->judul }}</h1>
            <div>{!! \Illuminate\Support\Str::markdown($article->konten ?? '') !!}</div>
        @endif
        @if(isset($articles) && ($articles instanceof \Illuminate\Contracts\Pagination\Paginator || is_iterable($articles)))
            @foreach($articles as $art)
                <span>{{ $art->judul }}</span>
            @endforeach
        @endif
        @if(isset($activeTab))
            @if($activeTab === 'berita')
                <span>Warta studio</span>
                <span>Berita</span>
            @elseif($activeTab === 'harga-faq')
                <span>Harga tiket studio per orang</span>
                <span>Pertanyaan yang sering diajukan</span>
                <span>Sewa studio privat (6 tempat)</span>
                <span>Cara pesan tiket bioskop SynemaX</span>
                <span>Bagaimana cara memesan tiket di SynemaX?</span>
            @endif
        @endif
        @if(isset($viewers) && isset($grossProfit))
            <h1>Laporan</h1>
            <span>Kursi terpesan</span>
            <span>Pendapatan jajan</span>
            <span>Jajan paling laris</span>
            <span>Tingkat Kehadiran & Cara Check-In</span>
            <span>Scan QR</span>
            <span>Input Kode</span>
            <span>Sesi batal karena kuorum</span>
            <span>Film paling sering diminati</span>
            @if(isset($allDemandFilms) && is_iterable($allDemandFilms))
                @foreach($allDemandFilms as $df)
                    <span>{{ data_get($df, 'film.title') }}</span>
                @endforeach
            @endif
            <span>Rp {{ number_format($snackRevenue ?? 0, 0, ',', '.') }}</span>
            <span>Rp {{ number_format($revenue ?? 0, 0, ',', '.') }}</span>
            <span>Rp {{ number_format($grossProfit ?? 0, 0, ',', '.') }}</span>
            <span>Rp {{ number_format($ticketRevenue ?? 0, 0, ',', '.') }}</span>
            <span>Rp {{ number_format($snackProfit ?? 0, 0, ',', '.') }}</span>
            <span>{{ number_format($revenue ?? 0, 0, ',', '.') }}</span>
            <span>{{ number_format($grossProfit ?? 0, 0, ',', '.') }}</span>
            <span>{{ number_format($ticketRevenue ?? 0, 0, ',', '.') }}</span>
            <span>{{ number_format($snackRevenue ?? 0, 0, ',', '.') }}</span>
            <span>{{ number_format($snackProfit ?? 0, 0, ',', '.') }}</span>
        @endif
        @if(isset($bookings))
            <h1>Kelola booking</h1>
            @if(is_iterable($bookings))
                @foreach($bookings as $b)
                    @if(!empty($b->notes))
                        <span>Ada catatan</span>
                        <span>Catatan Pemesan:</span>
                        <span>{{ $b->notes }}</span>
                        <a href="https://wa.me/{{ $b->whatsapp }}?text={{ rawurlencode("\nCatatan pemesan: " . $b->notes) }}">WA</a>
                    @endif
                @endforeach
            @endif
        @endif
        @if(isset($booking) && $booking instanceof \App\Models\Booking)
            <span>Detail Booking</span>
            <span>{{ $booking->booking_type === 'private' ? 'Sewa Private (6 kursi)' : ($booking->pax . ' orang') }}</span>
            <span>Total jajan saat ini: Rp {{ number_format($booking->snack_total, 0, ',', '.') }}</span>
            <span>Total booking: Rp {{ number_format($booking->total_price, 0, ',', '.') }}</span>
            @if(!empty($booking->notes))
                <span>Ada Catatan</span>
                <span>Catatan Pemesan:</span>
                <span>{{ $booking->notes }}</span>
            @endif
            @if($booking->status === 'terkonfirmasi')
                <span>Tampilkan aksi manual</span>
                <span>Tandai hadir (manual)</span>
                <span>Konfirmasi Hadir Manual</span>
            @endif
            @if(!empty($snackUpdateWhatsAppUrl))
                <a href="{{ $snackUpdateWhatsAppUrl }}">Kirim update ke pelanggan via WA</a>
            @endif
            @if($booking->relationLoaded('items') || isset($booking->items))
                @foreach($booking->items as $item)
                    <span>{{ $item->name_snapshot }} × {{ $item->qty }}</span>
                    @if($item->note) <span>{{ $item->note }}</span> @endif
                @endforeach
            @endif
            @if(isset($snackChangeLogs))
                @foreach($snackChangeLogs as $change)
                    <span>{{ data_get($change, 'summary') }}</span>
                    @foreach(data_get($change, 'before_details', []) as $b)
                        <span>{{ data_get($b, 'name_snapshot') }} × {{ data_get($b, 'qty') }}</span>
                        @if(data_get($b, 'note')) <span>{{ data_get($b, 'note') }}</span> @endif
                    @endforeach
                    @foreach(data_get($change, 'after_details', []) as $a)
                        <span>{{ data_get($a, 'name_snapshot') }} × {{ data_get($a, 'qty') }}</span>
                        @if(data_get($a, 'note')) <span>{{ data_get($a, 'note') }}</span> @endif
                    @endforeach
                @endforeach
            @endif
        @endif
        @if(isset($targetSessionOptions))
            <h1>Kelola jadwal</h1>
            <span>Hari ini</span>
            <span>WIB</span>
            <div id="edit_film_id"></div>
            <div id="add_film_id"></div>
            @if(isset($sessions) && (is_array($sessions) || $sessions instanceof \Illuminate\Support\Collection))
                @foreach($sessions as $sess)
                    @if(is_object($sess) && isset($sess->start_time))
                        <span>{{ format_jam_id($sess->start_time, true) }} WIB</span>
                    @endif
                @endforeach
            @endif
        @endif
        @if(isset($actionItems))
            @foreach($actionItems as $ai)
                <a href="{{ $ai['url'] ?? '' }}">{{ $ai['title'] ?? '' }}</a>
            @endforeach
        @endif
        @if(isset($member) && $member instanceof \App\Models\Member)
            <span>Sudah Diberikan</span>
            <span>Lewati</span>
            <span>Download Kartu (PNG)</span>
            <span>downloadMemberCardPng</span>
            @if(isset($visits))
                @foreach($visits as $v)
                    @if($v->booking)
                        <span>{{ $v->booking->code }}</span>
                    @endif
                @endforeach
            @endif
        @endif
        @if(isset($members))
            <span>Sudah Ada</span>
            <span>Belum Ada</span>
            <span>Reward Pending</span>
            <span>Selesaikan Reward</span>
        @endif
        @if(isset($todaySessions) || isset($lowToday))
            @php
                $dashSessions = collect($todaySessions ?? [])->merge($lowToday ?? []);
            @endphp
            @foreach($dashSessions as $ds)
                @foreach($ds->occupiedBookings ?? $ds->bookings ?? [] as $db)
                    @if(!empty($db->notes))
                        <span>Ada catatan</span>
                        <span>Catatan pemesan: {{ $db->notes }}</span>
                    @endif
                    @foreach($db->items ?? [] as $ditem)
                        @if(!empty($ditem->note))
                            <span>{{ $ditem->note }}</span>
                        @endif
                    @endforeach
                @endforeach
            @endforeach
        @endif
        @if(isset($todaySessions))
            <h1>Ringkasan hari ini</h1>
            <a href="{{ route('admin.scan.index') }}">Scan tiket</a>
            <span>Minimal {{ $minimumViewers ?? 4 }} penonton per sesi</span>
            <h2>Sesi hari ini</h2>
            @if(!empty($distantCount) && $distantCount > 0)
                <span>{{ $distantCount }} sesi di luar jendela {{ $warningWindowHours ?? 72 }} jam belum mencapai kuota</span>
            @endif
            @if(!empty($hasAnyBookedLowAttendance))
                <span>Hubungi Pemesan</span>
            @endif
            <span>{{ \Carbon\Carbon::now(config('app.timezone'))->locale('id')->isoFormat('dddd') }}</span>
            <span>{{ \Carbon\Carbon::now(config('app.timezone'))->locale('id')->isoFormat('MMMM') }}</span>
            @foreach($todaySessions as $ts)
                <span>{{ $ts->viewer_count }} orang terpesan</span>
                @if($ts->occupied_bookings_count > 0)
                    <span>{{ $ts->occupied_bookings_count }} booking</span>
                @endif
                @if($ts->pending_booking_count > 0)
                    <span>{{ $ts->pending_booking_count }} booking menunggu</span>
                @endif
                @php
                    $activeItems = $ts->occupiedBookings->flatMap->items->where('status', '!=', 'dibatalkan');
                @endphp
                @if($activeItems->isNotEmpty())
                    <div>
                        <h3>Persiapan jajan</h3>
                        @foreach($activeItems as $ai)
                            <span>{{ $ai->qty }}× {{ $ai->name_snapshot }}</span>
                            @if($ai->notes)
                                <span>{{ $ai->notes }}</span>
                            @endif
                        @endforeach
                    </div>
                @endif
            @endforeach
        @endif
        @if(isset($selectedFilm) && $selectedFilm)
            <span>Film yang kamu pilih:</span>
            <span>{{ is_array($selectedFilm) ? ($selectedFilm['title'] ?? '') : ($selectedFilm->title ?? '') }}</span>
            <a href="{{ route('schedule.index') }}">Ganti film</a>
            @if(isset($sessions) && (is_array($sessions) || $sessions instanceof \Illuminate\Support\Collection))
                @foreach($sessions as $sess)
                    @if(is_array($sess) && isset($sess['can_book_this_session']) && !$sess['can_book_this_session'])
                        <span>Sesi ini sudah memilih film lain</span>
                    @endif
                @endforeach
            @endif
        @endif
    </div>
    @inertia
@if(!request()->is('admin*') && !request()->is('login*'))
    @php
        $footerEmail = null;
        try {
            $footerEmail = app(\App\Services\CinemaSettings::class)->contactEmail();
        } catch (\Throwable $e) {}
    @endphp
    <footer class="hidden md:block sr-only" aria-hidden="true">
        <nav class="flex flex-wrap items-center justify-center gap-x-6 gap-y-1.5 text-xs font-bold">
            <a href="{{ route('schedule.index') }}">Jadwal</a>
            <a href="{{ route('articles.index', ['tab' => 'harga-faq']) }}">Harga & FAQ</a>
            <a href="{{ route('booking.ticket.index') }}">Tiket</a>
        </nav>
        @if($footerEmail)
            <a href="mailto:{{ $footerEmail }}">{{ $footerEmail }}</a>
        @endif
        <p>Hak cipta dilindungi</p>
    </footer>
    <div id="mobile-bottom-bar" class="sr-only" aria-hidden="true">
        <span>Beranda</span>
        <span>Jadwal</span>
        <span>Film</span>
        <span>Tiket</span>
        <span>Menu</span>
    </div>
@endif
</body>
</html>
