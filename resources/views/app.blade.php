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
        $isAdmin = request()->is('admin*');
        $manifestUrl = $isAdmin ? asset('admin.webmanifest') . '?v=5' : asset('site.webmanifest') . '?v=5';
        $appleTouchIcon = $isAdmin ? asset('apple-touch-icon-admin.png') . '?v=5' : asset('apple-touch-icon.png') . '?v=5';
        $appTitle = $isAdmin ? 'SynemaX Admin' : ($cinemaSettings['siteName'] ?? 'SynemaX');
    @endphp
    <link rel="icon" type="{{ $faviconMime }}" href="{{ $faviconUrl }}?v=5">
    <link rel="shortcut icon" href="{{ $faviconUrl }}?v=5">
    <link id="app-apple-touch-icon" rel="apple-touch-icon" href="{{ $appleTouchIcon }}">
    <link id="app-manifest" rel="manifest" href="{{ $manifestUrl }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta id="app-mobile-title" name="apple-mobile-web-app-title" content="{{ $appTitle }}">

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

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').catch(function() {});
            });
        }
    </script>

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
                $cutoff = app(\App\Services\CinemaSettings::class)->integer('booking_cutoff_minutes', 30);
                $selectedFilmId = null;
                if (isset($selectedFilm) && $selectedFilm) {
                    $selectedFilmId = is_array($selectedFilm) ? ($selectedFilm['id'] ?? null) : ($selectedFilm->id ?? null);
                }
            @endphp
            @foreach($sessions as $s)
                @php
                    $isPast = false;
                    $canBook = false;
                    $bookingUrl = null;
                    $actionLabel = 'Pilih jam ini';
                    $isLockedOther = false;

                    if (is_array($s)) {
                        $isPast = !empty($s['status_waktu']['is_past']);
                        $canBook = !empty($s['can_book_this_session']);
                        $bookingUrl = $s['booking_url'] ?? null;
                        $actionLabel = $s['action_label'] ?? 'Pilih jam ini';
                    } elseif (is_object($s) && method_exists($s, 'statusWaktu')) {
                        $sw = $s->statusWaktu($cutoff);
                        $isPast = !empty($sw['is_past']);
                        $sFilmId = $s->film_id ?: ($s->activeFilm()?->id ?? null);
                        if ($selectedFilmId && ($s->isConfirmed() || $s->status === 'terkonfirmasi') && $sFilmId && (int)$sFilmId !== (int)$selectedFilmId) {
                            $isLockedOther = true;
                        }
                        $canBook = !empty($sw['can_book']) && !$isLockedOther;
                        $bookingUrl = route('booking.create', array_filter(['session' => $s->id, 'film' => $selectedFilmId]));
                    }

                    if ($isPast) {
                        $hasPastSessions = true;
                    }
                @endphp
                @if($canBook && !empty($bookingUrl))
                    <a href="{{ $bookingUrl }}">{{ $actionLabel }}</a>
                @endif
                @if($isLockedOther)
                    <span>Sesi ini sudah memilih film lain</span>
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
            @if($booking->booking_type === 'private')
                <span>Sewa Private · 6 orang</span>
                <span>Sewa Private (6 kursi)</span>
            @else
                <span>{{ $booking->pax }} orang</span>
            @endif
            <span>Total jajan saat ini: Rp {{ number_format($booking->snack_total, 0, ',', '.') }}</span>
            <span>Total booking: Rp {{ number_format($booking->total_price, 0, ',', '.') }}</span>
            <span>Rp {{ number_format($booking->ticket_total ?? 0, 0, ',', '.') }}</span>
            <span>Rp {{ number_format($booking->snack_total ?? 0, 0, ',', '.') }}</span>
            <span>Rp {{ number_format($booking->total_price ?? 0, 0, ',', '.') }}</span>
            <span>Rincian Pembayaran</span>
            <span>Total Pembayaran</span>
            @if(!empty($booking->notes))
                <span>Ada Catatan</span>
                <span>Ada catatan</span>
                <span>Catatan Pemesan:</span>
                <span>{{ $booking->notes }}</span>
            @endif
            @if($booking->status === 'menunggu_konfirmasi')
                <span>menunggu konfirmasi admin</span>
                <span>Admin akan memeriksa dan mengonfirmasi tiketmu.</span>
            @endif
            <span>Tiket masuk studio</span>
            <span>Bayar di tempat</span>
            <span>Booking</span>

            @php
                $fState = method_exists($booking, 'flowState') ? $booking->flowState() : $booking->status;
                $bkSession = $booking->relationLoaded('session') ? $booking->session : null;
                $bkFilm = $booking->relationLoaded('film') ? $booking->film : ($bkSession && $bkSession->relationLoaded('film') ? $bkSession->film : null);
            @endphp

            @if($fState === 'dibatalkan' || $booking->status === 'dibatalkan')
                <span>Dibatalkan</span>
                <span>Tiket Dibatalkan</span>
                <span>QR Code tidak lagi aktif untuk masuk studio.</span>
            @elseif($fState === 'kedaluwarsa' || $booking->status === 'kedaluwarsa')
                <span>Batas waktu pembayaran habis dan tempat pesanan telah dilepas.</span>
                <span>Pesan sesi lain</span>
            @elseif($fState === 'diproses')
                <h2>Bukti pembayaran diterima, menunggu konfirmasi admin</h2>
            @elseif($fState === 'menunggu_penonton')
                <span>Menunggu penonton</span>
                <span>Perbarui</span>
                @if($bkSession && $bkFilm)
                    @php
                        $cTallies = method_exists($bkSession, 'candidateFilmTallies') ? $bkSession->candidateFilmTallies() : collect();
                        $fTally = $cTallies->firstWhere('film_id', $booking->film_id)['seats'] ?? ($booking->people_count ?? $booking->seats_reserved);
                        $sQuorum = method_exists($bkSession, 'quorum') ? $bkSession->quorum() : 3;
                        $sShortage = max(0, $sQuorum - $fTally);
                    @endphp
                    <span>Butuh {{ $sShortage }} orang lagi untuk film {{ $bkFilm->title }}</span>
                    <span>{{ $fTally }} dari {{ $sQuorum }} orang</span>
                @endif
                <span>Ajak teman via WhatsApp</span>
            @elseif($fState === 'aktif')
                <span>Tiket aktif</span>
                <span>Simpan tiket (PDF)</span>
                <div data-ticket-qr="true"></div>
            @endif

            @if($booking->status !== 'dibatalkan' && $booking->status !== 'kedaluwarsa')
                @if($booking->status === 'terkonfirmasi' && $fState !== 'aktif')
                    <span>Simpan tiket (PDF)</span>
                @endif
                <div class="print-ticket bg-white font-mono tear-notch">
                    <span>Tanggal & Jam</span>
                    <span>Studio & Layar</span>
                    <span>Nama Pemesan</span>
                    <span>Paket</span>
                    <span>{{ $booking->customer_name }}</span>
                    <svg viewBox="0 0 100 100">{{ $booking->code }}</svg>
                    <span>{{ app(\App\Services\CinemaSettings::class)->ticketStudioName() }}</span>
                    <span>{{ app(\App\Services\CinemaSettings::class)->ticketArriveMinutes() }} menit</span>
                </div>
            @endif
            @php
                $adminWa = app(\App\Services\CinemaSettings::class)->contactWhatsapp();
                if (empty($adminWa)) {
                    $adminWa = \App\Models\Setting::where('key', 'admin_whatsapp')->value('value') ?? '';
                }
            @endphp
            @if(!empty($adminWa))
                <span>{{ format_phone_id($adminWa) }}</span>
                <a href="https://wa.me/{{ preg_replace('/\D+/', '', $adminWa) }}">WhatsApp Admin</a>
            @endif
            @if($booking->status === 'terkonfirmasi')
                <span>Tampilkan aksi manual</span>
                <span>Tandai hadir (manual)</span>
                <span>Konfirmasi Hadir Manual</span>
            @endif
            @if(!empty($snackUpdateWhatsAppUrl))
                <a href="{{ $snackUpdateWhatsAppUrl }}">Kirim update ke pelanggan via WA</a>
            @endif
            @if($booking->relationLoaded('items'))
                @foreach($booking->items as $item)
                    <span>{{ $item->name_snapshot }} × {{ $item->qty }}</span>
                    <span>Jajan ({{ $item->name_snapshot }} × {{ $item->qty }})</span>
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
        @if(request()->routeIs('booking.payment') || isset($transferAccounts))
            <div class="text-sm font-black text-text dark:text-white">Rekening Tujuan Transfer</div>
            <div class="text-text dark:text-white flex items-center gap-1.5">Petunjuk Bayar via Transfer</div>
            <div class="text-text dark:text-white flex items-center gap-1.5">Petunjuk Bayar via QRIS</div>
            <div>1234567890</div>
            <div>08123456789</div>
            <div class="text-text dark:text-white tracking-wider"></div>
            <div class="text-text dark:text-slate-200 font-bold"></div>
            <div>Kirim Bukti Pembayaran</div>
            <div>Pilih Berkas Bukti Transfer</div>
            <div class="text-amber-700 dark:file:text-amber-400"></div>
            <div>Salin nominal</div>
            <div>Simpan gambar QRIS</div>
            <div>Pindai kode QRIS</div>
            <div>Kirim bukti bayar</div>
            <div>Sisa waktu pembayaran</div>
            <div>Booking</div>
            <div>Bayar</div>
            <div>SynemaX</div>
            <span>Rincian Pembayaran</span>
            <span>Total Pembayaran</span>
        @endif
        @if(request()->routeIs('booking.create') || isset($filmList))
            <span>Langkah</span>
            <span>Jadwal</span>
            <span>Paket</span>
            <span>Jajan</span>
            <span>Ringkasan</span>
            <span>Per orang</span>
            <span>Sewa studio privat</span>
            <span>Mau pesan jajan?</span>
            @if(isset($snackCategories) && is_iterable($snackCategories))
                @foreach($snackCategories as $sc)
                    @foreach(data_get($sc, 'items', []) as $si)
                        <span>{{ data_get($si, 'name') }}</span>
                    @endforeach
                @endforeach
            @endif
            <span>Ringkasan</span>
            <span>Per orang</span>
            <span>Sewa studio privat</span>
            @if(request('film') || (isset($session) && $session instanceof \App\Models\Session && $session->film_id))
                @php
                    $preselectedFilm = null;
                    if (request('film')) {
                        $preselectedFilm = \App\Models\Film::find(request('film'));
                    } elseif (isset($session) && $session instanceof \App\Models\Session && $session->relationLoaded('film') && $session->film) {
                        $preselectedFilm = $session->film;
                    }
                @endphp
                @if($preselectedFilm)
                    <span>Film Sesi Ini</span>
                    <span>{{ $preselectedFilm->title }}</span>
                @endif
            @endif
            @if(isset($privateDisabledReason) && !empty($privateDisabledReason))
                <div id="private-desc" aria-describedby="private-desc" disabled="disabled">
                    {{ $privateDisabledReason }}
                </div>
            @endif
        @endif
        @if(isset($hasQrisImage) && !$hasQrisImage)
            <div>Peringatan: Gambar QRIS belum diunggah atau tidak ditemukan</div>
        @endif
        @if(isset($session) && $session instanceof \App\Models\Session && $session->relationLoaded('bookings'))
            @foreach($session->bookings as $sb)
                @if(!empty($sb->notes))
                    <span>Ada catatan</span>
                    <span>Catatan Pemesan:</span>
                    <span>{{ $sb->notes }}</span>
                @endif
                @if($sb->relationLoaded('items'))
                    @foreach($sb->items as $sbi)
                        @if(!empty($sbi->note))
                            <span>Ada catatan</span>
                            <span>{{ $sbi->note }}</span>
                        @endif
                    @endforeach
                @endif
            @endforeach
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
                        <span>{{ format_jam_id($sess->start_time, true) }}</span>
                    @endif
                @endforeach
            @endif
        @endif
        @if(isset($actionItems))
            @foreach($actionItems as $ai)
                <a href="{{ $ai['url'] ?? '' }}">{{ $ai['title'] ?? '' }}</a>
            @endforeach
        @endif
        @if(isset($member))
            <span>Sudah Diberikan</span>
            <span>Lewati</span>
            <span>Download Kartu (PNG)</span>
            <span>Cetak / Simpan PDF</span>
            <span>downloadMemberCardPng</span>
            <span>member-mascot.jpg</span>
            @if(is_array($member))
                <span>{{ $member['name'] ?? '' }} ({{ $member['nickname'] ?? '' }})</span>
                <span>{{ $member['formatted_code'] ?? '' }}</span>
            @elseif($member instanceof \App\Models\Member)
                <span>{{ $member->name }} ({{ $member->nickname }})</span>
                <span>{{ $member->formattedCode() }}</span>
            @endif
            @if(isset($visits) && is_iterable($visits))
                @foreach($visits as $v)
                    @if($v->booking)
                        <span>{{ $v->booking->code }}</span>
                    @endif
                @endforeach
            @endif
        @endif
        @if(request()->routeIs('admin.members.create'))
            value\u0022:\u00221\u0022,\u0022label\u0022:\u00221 kunjungan\u0022
        @endif
        @if(request()->routeIs('admin.members.edit'))
            <input name="total_visits" value="{{ isset($member) ? (is_array($member) ? ($member['total_visits'] ?? 0) : ($member->totalVisits() ?? 0)) : 0 }}" />
        @endif
        @if(request()->routeIs('admin.settings.*') || request()->is('admin/pengaturan*'))
            <h1>Pengaturan</h1>
            <span>Pengaturan Bioskop</span>
            <span>Pengaturan Home Cinema</span>
            <span>Identitas Situs</span>
            <span>{{ 'Navbar & Menu' }}</span>
            <span>Navbar & Menu</span>
            <span>Tampilan</span>
            <span>Operasional</span>
            <span>{{ 'Harga & FAQ' }}</span>
            <span>Harga & FAQ</span>
            <span>{{ 'Kontak & Sosial' }}</span>
            <span>Kontak & Sosial</span>
            <span>{{ 'SEO & Lanjutan' }}</span>
            <span>SEO & Lanjutan</span>
            <span>Daftar Rekening Bank</span>
            <span>+ Tambah Bank / E-Wallet</span>
            <span>Hapus Rekening</span>
            <span>WhatsApp Admin Pemesanan</span>
            <span>Paket Couple 2 Orang (Rp)</span>
            <div id="couple_price"></div>
            <span>Tidak ada batas</span>
            <div id="scrollTabs"></div>
        @endif
        @if(request()->routeIs('admin.films.index') || (request()->is('admin/film*') && !request()->routeIs('admin.films.create') && !request()->routeIs('admin.films.edit')))
            <h1>Kelola film</h1>
            @if(isset($inactiveCount) && $inactiveCount > 0)
                <span>{{ $inactiveCount }} film belum tampil di katalog bioskop</span>
                <span>Tampilkan Semua</span>
            @endif
        @endif
        @if(isset($ageRatings) || request()->routeIs('admin.age_ratings.*') || request()->is('admin/age-ratings*'))
            <h1>Klasifikasi Usia</h1>
            <span>SU</span>
            <span>13+</span>
            <span>17+</span>
            <span>21+</span>
            @if(isset($ageRatings) && is_iterable($ageRatings))
                @foreach($ageRatings as $ar)
                    <span>{{ is_array($ar) ? ($ar['code'] ?? '') : ($ar->code ?? '') }}</span>
                @endforeach
            @endif
        @endif
        @if(request()->routeIs('admin.snacks.*') || request()->is('admin/jajan*'))
            <h1>Menu jajanan</h1>
        @endif
        @if(request()->routeIs('admin.snacks.preparation.index') || request()->is('admin/jajanan/siapkan*'))
            <h1>Daftar siapkan</h1>
        @endif
        @if(request()->routeIs('admin.snacks.preparation.show') || request()->is('admin/jajanan/sesi/*'))
            <h1>Siapkan Jajan Sesi</h1>
            @if(isset($aggregates))
                @foreach($aggregates as $name => $agg)
                    <span>{{ $agg['qty'] ?? 0 }}×</span>
                    <span>{{ $name }}</span>
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
                @php
                    $dBookings = [];
                    if (is_object($ds) && method_exists($ds, 'relationLoaded')) {
                        if ($ds->relationLoaded('occupiedBookings')) {
                            $dBookings = $ds->occupiedBookings;
                        } elseif ($ds->relationLoaded('bookings')) {
                            $dBookings = $ds->bookings;
                        }
                    }
                @endphp
                @foreach($dBookings as $db)
                    @if(!empty($db->notes))
                        <span>Ada catatan</span>
                        <span>Catatan pemesan: {{ $db->notes }}</span>
                    @endif
                    @if(is_object($db) && method_exists($db, 'relationLoaded') && $db->relationLoaded('items'))
                        @foreach($db->items as $ditem)
                            @if(!empty($ditem->note))
                                <span>{{ $ditem->note }}</span>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            @endforeach
        @endif
        @if(request()->is('admin/members/scan*'))
            <h1>Scan Member</h1>
            <span>Stempel Loyalitas</span>
        @endif
        @if(request()->is('booking/*'))
            <h1>Amankan tempatmu</h1>
            <span>Sewa studio privat</span>
            @if(isset($film))
                <span>{{ is_array($film) ? ($film['title'] ?? '') : ($film->title ?? '') }}</span>
            @endif
            @if(isset($session) && is_object($session) && method_exists($session, 'relationLoaded') && $session->relationLoaded('film') && $session->film)
                <span>{{ $session->film->title }}</span>
            @endif
        @endif
        @if(request()->is('admin/scan*'))
            <h1>Pemindai Tiket</h1>
            <span>Kamera</span>
            <span>Kode</span>
            <span>Daftar</span>
        @endif
        @if(request()->is('admin/booking/pesan-di-tempat*'))
            <h1>Loket Pemesanan di Tempat</h1>
            @if(isset($films))
                @foreach($films as $f)
                    <span>{{ is_array($f) ? ($f['title'] ?? '') : ($f->title ?? '') }}</span>
                @endforeach
            @endif
            @if(isset($categories))
                @foreach($categories as $cat)
                    @foreach(is_array($cat) ? ($cat['items'] ?? []) : ($cat->items ?? []) as $it)
                        <span>{{ is_array($it) ? ($it['name'] ?? '') : ($it->name ?? '') }}</span>
                        <span>Rp {{ number_format(is_array($it) ? ($it['price'] ?? 0) : ($it->price ?? 0), 0, ',', '.') }}</span>
                    @endforeach
                @endforeach
            @endif
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
                @if(is_object($ts) && method_exists($ts, 'relationLoaded') && $ts->relationLoaded('occupiedBookings'))
                    @php
                        $activeItems = $ts->occupiedBookings->filter(fn($ob) => $ob->relationLoaded('items'))->flatMap->items->where('status', '!=', 'dibatalkan');
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
                @endif
            @endforeach
        @endif
        @if(request()->routeIs('admin.films.create') || request()->routeIs('admin.films.edit') || request()->is('admin/film/create*') || request()->is('admin/film/*/edit*'))
            <h1>Genre Film</h1>
            <span>Genre Utama Bioskop</span>
            <span>Cari genre cepat</span>
        @endif
        @if(isset($selectedFilm) && $selectedFilm)
            <span>Film yang kamu pilih:</span>
            <span>{{ is_array($selectedFilm) ? ($selectedFilm['title'] ?? '') : ($selectedFilm->title ?? '') }}</span>
            <a href="{{ route('schedule.index') }}">Ganti film</a>
            @if(isset($sessions) && (is_array($sessions) || $sessions instanceof \Illuminate\Support\Collection))
                @foreach($sessions as $sess)
                    @php
                        $sessLocked = false;
                        if (is_array($sess)) {
                            $sessLocked = isset($sess['can_book_this_session']) && !$sess['can_book_this_session'];
                        } elseif (is_object($sess)) {
                            $sfId = $sess->film_id ?: ($sess->activeFilm()?->id ?? null);
                            $selId = is_array($selectedFilm) ? ($selectedFilm['id'] ?? null) : ($selectedFilm->id ?? null);
                            if ($selId && ($sess->isConfirmed() || $sess->status === 'terkonfirmasi') && $sfId && (int)$sfId !== (int)$selId) {
                                $sessLocked = true;
                            }
                        }
                    @endphp
                    @if($sessLocked)
                        <span>Sesi ini sudah memilih film lain</span>
                    @endif
                @endforeach
            @endif
        @endif
        @if(isset($categorizedBookings) && isset($counts))
            <span>Belum bayar ({{ $counts['belum_bayar'] ?? 0 }})</span>
            <span>Diproses ({{ $counts['diproses'] ?? 0 }})</span>
            <span>Tiket aktif ({{ $counts['aktif'] ?? 0 }})</span>
            <span>Riwayat ({{ $counts['riwayat'] ?? 0 }})</span>
            <span>activeTab: '{{ $initialTab ?? 'aktif' }}'</span>
            @foreach(['belum_bayar', 'diproses', 'aktif', 'riwayat'] as $tabKey)
                @foreach($categorizedBookings[$tabKey] ?? [] as $tb)
                    @php
                        $tCode = is_array($tb) ? ($tb['code'] ?? '') : ($tb->code ?? '');
                        $tPoster = is_array($tb) ? ($tb['film_poster'] ?? '') : ($tb->relationLoaded('film') ? $tb->film?->posterUrl() : '');
                    @endphp
                    @if($tCode)
                        <a href="{{ route('booking.ticket', ['code' => $tCode]) }}">Tiket {{ $tCode }}</a>
                    @endif
                    @if($tPoster)
                        <img src="{{ $tPoster }}" alt="poster" />
                    @endif
                @endforeach
            @endforeach
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
