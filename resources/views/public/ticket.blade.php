@extends('layouts.public')

@section('title', 'Tiket ' . $booking->code . ': SynemaX Premiere')
@section('meta')
    <meta name="description" content="Tiket digital resmi SynemaX untuk booking {{ $booking->code }}.">
    <meta property="og:title" content="Tiket SynemaX: {{ $booking->code }}">
    <meta property="og:description" content="{{ $booking->film?->title ?? 'Film Sesi' }}, {{ $booking->session->startsAt()->locale('id')->translatedFormat('D, d M Y') }}, {{ substr((string) $booking->session->start_time, 0, 5) }} WIB">
@endsection

@push('styles')
<style>
    @media print {
        header, footer, nav, .no-print, button, a[href*="wa.me"] {
            display: none !important;
        }
        body, html {
            background-color: #ffffff !important;
            color: #000000 !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        main {
            padding: 0 !important;
            margin: 0 !important;
        }
        .print-ticket {
            background-color: #0E131F !important;
            color: #FFFFFF !important;
            border: 2px solid #D4AF37 !important;
            box-shadow: none !important;
            max-width: 480px !important;
            margin: 0 auto !important;
            page-break-inside: avoid;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>
@endpush

@section('content')
    @php
        $myFilm = $booking->film;
        $session = $booking->session;
        $isConfirmed = $session->isConfirmed();
        $isCancelledQuorum = $session->isCancelledByQuorum() || ($booking->status === 'dibatalkan' && str_contains((string) $booking->cancellation_reason, 'kuorum'));
        $myFilmTally = $candidateTallies->firstWhere('film_id', $booking->film_id)['seats'] ?? $booking->seats_reserved;
        $quorum = $session->quorum();
        $shortage = max(0, $quorum - $myFilmTally);
        $statusWaktu = $session->statusWaktu();
    @endphp

    <div class="mx-auto max-w-xl px-4 py-5 sm:px-6 sm:py-8"
         x-data="{
             code: @js($booking->code),
             status: @js($booking->status),
             copied: false,
             snacksOpen: false,
             cancelModal: false,
             changeFilmModal: false,
             rescheduleModal: false,
             upgradeModal: false,
             polling: false,
             pollingInterval: null,
             init() {
                 try {
                     const codes = JSON.parse(localStorage.getItem('synema_booking_history') || '[]');
                     if (!codes.includes(this.code)) {
                         codes.unshift(this.code);
                         localStorage.setItem('synema_booking_history', JSON.stringify(codes.slice(0, 15)));
                     }
                 } catch (e) {}

                 if (this.status === 'menunggu_konfirmasi' || !@js($isConfirmed)) {
                     this.startPolling();
                 }
             },
             startPolling() {
                 this.pollingInterval = setInterval(() => {
                     if (!document.hidden) {
                         this.checkStatus(false);
                     }
                 }, 30000);
             },
             checkStatus(manual = true) {
                 this.polling = true;
                 fetch(@js(route('booking.status.json', ['code' => $booking->code])))
                     .then(res => res.json())
                     .then(data => {
                         this.polling = false;
                         if (data.status && (data.status !== this.status || Boolean(data.session_confirmed) !== @js($isConfirmed))) {
                             window.location.reload();
                         }
                     })
                     .catch(() => {
                         this.polling = false;
                     });
             },
             copyCode() {
                 if (window.copyToClipboard) {
                     window.copyToClipboard(this.code);
                     this.copied = true;
                     setTimeout(() => this.copied = false, 2500);
                 } else if (navigator.clipboard && navigator.clipboard.writeText) {
                     navigator.clipboard.writeText(this.code).then(() => {
                         this.copied = true;
                         setTimeout(() => this.copied = false, 2500);
                     });
                 }
             },
             printTicket() {
                 window.print();
             }
         }">

        {{-- Flash Pesan --}}
        @if (session('status'))
            <div role="status" class="mb-4 rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-4 text-xs font-bold text-emerald-400 no-print">
                {{ session('status') }}
            </div>
        @endif

        {{-- Indikator Tahapan Pesanan --}}
        <nav aria-label="Tahapan Pesanan" class="mb-4 no-print">
            @if ($booking->payment_method === 'di_tempat')
                {{-- Alur Bayar di tempat: 2 tahap --}}
                <ol class="grid grid-cols-2 gap-2 text-center text-xs">
                    <li class="flex items-center justify-center gap-1.5 rounded-xl border border-border bg-surface p-2 font-medium text-text-muted">
                        <svg class="size-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>1. Booking</span>
                    </li>
                    <li class="flex items-center justify-center gap-1.5 rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-2 font-bold text-emerald-400">
                        <span class="grid size-4 place-items-center rounded-full bg-emerald-500 text-[10px] text-black font-black">2</span>
                        <span>2. Selesai</span>
                    </li>
                </ol>
            @else
                {{-- Alur QRIS / Transfer: 3 tahap --}}
                <ol class="grid grid-cols-3 gap-2 text-center text-xs">
                    <li class="flex items-center justify-center gap-1.5 rounded-xl border border-border bg-surface p-2 font-medium text-text-muted">
                        <svg class="size-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>1. Booking</span>
                    </li>
                    <li class="flex items-center justify-center gap-1.5 rounded-xl border border-border bg-surface p-2 font-medium text-text-muted">
                        <svg class="size-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>2. Bayar</span>
                    </li>
                    <li class="flex items-center justify-center gap-1.5 rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-2 font-bold text-emerald-400">
                        <span class="grid size-4 place-items-center rounded-full bg-emerald-500 text-[10px] text-black font-black">3</span>
                        <span>3. Selesai</span>
                    </li>
                </ol>
            @endif
        </nav>

        {{-- ==================== BANNERS STATUS KUORUM & SESI ==================== --}}
        <div class="mb-5 no-print space-y-3">
            @if ($booking->status === 'menunggu_konfirmasi')
                <div class="rounded-2xl border border-amber-500/40 bg-surface p-4 text-xs text-text shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-bold text-amber-500 text-sm">Menunggu konfirmasi admin</p>
                            <p class="mt-1 text-text-muted leading-relaxed">
                                @if ($booking->payment_method === 'di_tempat')
                                    Booking diterima. Bayar tunai di lokasi sebelum film mulai. Admin akan mengonfirmasi tiket setelah pembayaran di kasir.
                                @else
                                    Bukti bayar diterima. Admin akan memeriksa dan mengonfirmasi tiketmu.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            @if ($booking->status === 'dibatalkan')
                <div class="rounded-2xl border border-danger/40 bg-danger-surface p-4 text-xs text-danger-text shadow-sm">
                    <p class="font-black text-sm">Booking Dibatalkan</p>
                    <p class="mt-1 leading-relaxed">
                        {{ $booking->cancellation_reason ?: 'Booking ini telah dibatalkan.' }}
                    </p>
                    @if ($booking->refund_needed)
                        <div class="mt-2 rounded-lg bg-danger/20 p-2 font-bold text-danger-text">
                            ℹ️ Pembayaran Anda telah ditandai untuk proses Refund oleh Admin. Admin akan menghubungi lewat WhatsApp.
                        </div>
                    @endif
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('schedule.date', ['date' => $session->session_date]) }}"
                           class="btn-gold-cinema inline-flex min-h-9 items-center justify-center rounded-xl px-4 text-xs font-bold">
                            Lihat Jadwal Lain
                        </a>
                    </div>
                </div>
            @elseif ($booking->needsReselect())
                <div class="rounded-2xl border border-amber-400/50 bg-amber-500/15 p-4 text-xs shadow-sm">
                    <span class="rounded bg-amber-400 px-2 py-0.5 text-[10px] font-black uppercase text-black">Perlu Pilih Ulang</span>
                    <h3 class="mt-2 text-sm font-black text-text">Film Pilihanmu Belum Mencapai Kuorum</h3>
                    <p class="mt-1 text-text-muted leading-relaxed">
                        Sesi ini telah terkonfirmasi pasti tayang untuk film <span class="font-bold text-text">{{ $session->activeFilm()?->title }}</span>. Anda dapat ikut menonton film ini atau pindah ke sesi lain secara gratis.
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('booking.change-film', ['code' => $booking->code]) }}">
                            @csrf
                            <input type="hidden" name="film_id" value="{{ $session->film_id }}">
                            <button type="submit" class="btn-gold-cinema inline-flex min-h-9 items-center rounded-xl px-3.5 text-xs font-bold">
                                Ikut Film Terkunci ({{ $session->activeFilm()?->title }})
                            </button>
                        </form>
                        <button type="button" @click="rescheduleModal = true" class="inline-flex min-h-9 items-center rounded-xl border border-border bg-surface px-3.5 text-xs font-bold text-text hover:border-amber-400">
                            Pindah Sesi Lain (Gratis)
                        </button>
                    </div>
                </div>
            @elseif ($isConfirmed)
                <div class="rounded-2xl border border-emerald-500/40 bg-emerald-500/10 p-4 text-emerald-400 shadow-sm flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">
                            <svg class="size-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                        <div>
                            <p class="font-black text-sm">Pasti Tayang: {{ $session->activeFilm()?->title ?? $myFilm?->title }}</p>
                            <p class="text-xs opacity-90">
                                Sesi ini telah terkonfirmasi pasti diputar ({{ match ($session->confirmation_method) {
                                    'couple' => 'Paket Couple',
                                    'private' => 'Sewa Private',
                                    'admin' => 'Dikonfirmasi Admin',
                                    default => 'Kuorum 4 Kursi Terpenuhi',
                                } }}).
                            </p>
                        </div>
                    </div>
                    @if ($booking->isOnlinePayment() && $booking->status === 'menunggu_pembayaran')
                        <a href="{{ route('booking.payment', ['code' => $booking->code]) }}"
                           class="btn-gold-cinema inline-flex min-h-9 shrink-0 items-center rounded-xl px-3.5 text-xs font-black shadow-md">
                            Bayar Sekarang
                        </a>
                    @endif
                </div>
            @elseif ($statusWaktu === 'Butuh kesepakatan')
                <div class="rounded-2xl border border-amber-500/50 bg-amber-500/15 p-4 text-xs shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="rounded bg-amber-500 px-2 py-0.5 text-[10px] font-black uppercase text-black">Butuh Kesepakatan</span>
                            <h3 class="mt-2 text-sm font-black text-text">Sesi Memasuki Jendela Kesepakatan</h3>
                            <p class="mt-1 text-text-muted leading-relaxed">
                                Jam tayang tersisa kurang dari 3 jam dan belum ada film yang mencapai 4 kursi. Gabung ke film dengan peminat terbanyak atau ajak teman agar sesi tidak dibatalkan!
                            </p>
                        </div>
                    </div>

                    {{-- Progres Film Terbanyak --}}
                    @if ($candidateTallies->isNotEmpty())
                        <div class="mt-3 rounded-xl border border-border bg-surface p-3 space-y-2">
                            <span class="text-[11px] font-bold text-text-muted uppercase tracking-wider">Peminat Saat Ini:</span>
                            @foreach ($candidateTallies as $tally)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-text truncate max-w-[200px]">{{ $tally['title'] }}</span>
                                    <span class="font-tabular font-black text-amber-400">{{ $tally['seats'] }}/{{ $quorum }} kursi</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-3 flex flex-wrap gap-2">
                        @if ($ajakTemanUrl)
                            <a href="{{ $ajakTemanUrl }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex min-h-9 items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 text-xs font-bold text-white hover:bg-emerald-500 shadow-xs">
                                <span>📲 Ajak Teman</span>
                            </a>
                        @endif
                        <button type="button" @click="changeFilmModal = true"
                                class="inline-flex min-h-9 items-center rounded-xl border border-border bg-surface px-3 text-xs font-bold text-text hover:border-amber-400">
                            Ikut Film Lain
                        </button>
                        <button type="button" @click="rescheduleModal = true"
                                class="inline-flex min-h-9 items-center rounded-xl border border-border bg-surface px-3 text-xs font-bold text-text hover:border-amber-400">
                            Pindah Sesi
                        </button>
                    </div>
                </div>
            @else
                {{-- Status Default: Menunggu Kuorum --}}
                <div class="rounded-2xl border border-amber-400/40 bg-amber-500/10 p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="rounded bg-amber-400 text-black px-2 py-0.5 text-[10px] font-black uppercase">
                                    Menunggu Kuorum
                                </span>
                                <span class="text-[11px] font-bold text-amber-400">
                                    {{ $myFilmTally }}/{{ $quorum }} Kursi
                                </span>
                            </div>
                            <h3 class="mt-1.5 text-sm font-black text-text">
                                Menunggu kuorum: butuh {{ $shortage }} penonton lagi untuk film {{ $myFilm?->title }}
                            </h3>
                            <p class="mt-1 text-xs text-text-muted leading-relaxed">
                                @if ($booking->isOnlinePayment() && $booking->payment_expires_at === null)
                                    Instruksi pembayaran akan muncul otomatis segera setelah film mencapai 4 penonton, sehingga Anda tidak perlu khawatir tentang refund bila kuorum tidak terpenuhi.
                                @else
                                    Ajak teman atau penonton lain untuk bergabung menonton film ini agar sesi segera terkonfirmasi pasti tayang.
                                @endif
                            </p>

                            {{-- Progress Bar Kuorum --}}
                            <div class="mt-2.5 h-2 w-full overflow-hidden rounded-full bg-slate-800">
                                <div class="h-full bg-amber-400 transition-all duration-500" style="width: {{ min(100, round(($myFilmTally / $quorum) * 100)) }}%"></div>
                            </div>
                        </div>

                        <button type="button"
                                @click="checkStatus(true)"
                                :disabled="polling"
                                class="inline-flex min-h-9 shrink-0 items-center gap-1.5 rounded-xl border border-amber-400/40 bg-surface px-3 text-xs font-bold text-amber-400 hover:bg-amber-500 hover:text-black">
                            <svg class="size-3.5" :class="{ 'animate-spin': polling }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span x-text="polling ? 'Cek...' : 'Refresh'">Refresh</span>
                        </button>
                    </div>

                    {{-- Tombol Ajak Teman & Aksi Cepat --}}
                    <div class="mt-3 flex flex-wrap gap-2 border-t border-amber-400/20 pt-3">
                        @if ($ajakTemanUrl)
                            <a href="{{ $ajakTemanUrl }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex min-h-9 items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 text-xs font-black text-white hover:bg-emerald-500 shadow-sm">
                                <span>📲 Ajak Teman via WA</span>
                            </a>
                        @endif
                        <button type="button" @click="changeFilmModal = true"
                                class="inline-flex min-h-9 items-center rounded-xl border border-border bg-surface px-3 text-xs font-bold text-text hover:border-amber-400">
                            Ikut Film Lain
                        </button>
                        <button type="button" @click="rescheduleModal = true"
                                class="inline-flex min-h-9 items-center rounded-xl border border-border bg-surface px-3 text-xs font-bold text-text hover:border-amber-400">
                            Pindah Sesi
                        </button>
                        @if ($canUpgradeCouple || $canUpgradePrivate)
                            <button type="button" @click="upgradeModal = true"
                                    class="inline-flex min-h-9 items-center rounded-xl border border-amber-400/40 bg-amber-500/10 px-3 text-xs font-bold text-amber-400 hover:bg-amber-500 hover:text-black">
                                Upgrade Paket
                            </button>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- ==================== KARTU E-TIKET KELAS DUNIA (XXI BOARDING PASS STUB) ==================== --}}
        <article class="print-ticket ticket-stub relative overflow-hidden rounded-3xl border border-amber-400/40 bg-gradient-to-b from-[#111726] via-[#0E131F] to-[#080B13] shadow-2xl text-white">

            {{-- Golden Foil Top Header --}}
            <header class="p-5 border-b border-dashed border-amber-400/30 bg-gradient-to-r from-amber-950/40 via-[#0E131F] to-amber-950/20">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <x-brand.logo size="sm" />
                        <span class="text-[11px] font-semibold text-slate-400">Tiket masuk studio</span>
                    </div>
                    <span class="rounded-full bg-amber-500/20 px-3 py-0.5 text-[10px] font-black uppercase tracking-wider text-amber-300 border border-amber-400/40">
                        STUDIO 1 • PREMIERE
                    </span>
                </div>

                {{-- Detail Film & Jadwal --}}
                <div class="mt-4 flex items-start gap-3.5">
                    @if ($booking->film?->poster_path)
                        <img src="{{ asset('storage/'.$booking->film->poster_path) }}"
                             alt="Poster {{ $booking->film->title }}"
                             class="h-20 w-14 shrink-0 rounded-xl object-cover border border-amber-400/30 shadow-md">
                    @endif
                    <div class="flex-1 min-w-0">
                        <h2 class="text-lg font-black text-white leading-tight truncate sm:text-xl">
                            {{ $booking->film?->title ?? 'Film Sesi' }}
                        </h2>
                        <p class="mt-1 text-xs text-amber-300/90 font-medium">
                            {{ $booking->session->startsAt()->locale('id')->translatedFormat('l, d F Y') }}
                        </p>
                        <div class="mt-1 flex flex-wrap gap-2 text-[11px] text-slate-300">
                            <span class="font-tabular font-black text-amber-400">{{ substr((string) $booking->session->start_time, 0, 5) }} WIB</span>
                            <span>•</span>
                            <span>{{ $booking->film?->age_rating ?? 'SU' }}</span>
                            <span>•</span>
                            <span>{{ $booking->film?->duration_minutes ?? 90 }} Menit</span>
                        </div>
                    </div>
                </div>
            </header>

            {{-- KODE TIKET & QR CODE SINEMATIK --}}
            <div class="relative bg-gradient-to-r from-amber-500/10 via-amber-400/15 to-amber-500/10 px-5 py-5 text-center border-y border-dashed border-amber-400/30">
                <span class="text-[10px] font-black tracking-[0.25em] uppercase text-amber-400">Kode Booking Resmi</span>
                <p class="mt-1 font-tabular text-3xl sm:text-4xl font-black tracking-widest text-white">
                    {{ $booking->code }}
                </p>

                {{-- Visual QR Code --}}
                <div class="mt-4 flex flex-col items-center justify-center">
                    <div class="rounded-2xl border-2 border-amber-400/60 bg-white p-3 shadow-lg">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data={{ urlencode(route('booking.lookup', ['code' => $booking->code])) }}&color=07090E"
                             alt="QR Code Tiket {{ $booking->code }}"
                             width="120"
                             height="120"
                             class="size-28 object-contain">
                    </div>
                    <span class="mt-2 text-[10px] font-bold text-slate-400 tracking-wider uppercase">Tunjukkan QR Saat Tiba di Lokasi</span>
                </div>

                <div class="mt-3 flex justify-center no-print">
                    <button type="button"
                            @click="copyCode()"
                            class="btn-gold-cinema inline-flex min-h-9 items-center gap-1.5 rounded-lg px-3.5 text-xs font-bold shadow-xs">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <span x-text="copied ? 'Kode Tersalin!' : 'Salin Kode Booking'">Salin Kode Booking</span>
                    </button>
                </div>
            </div>

            {{-- Rincian Pemesan & Tagihan --}}
            <div class="p-5 text-xs">
                <dl class="space-y-2.5">
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-400">Nama Pemesan</dt>
                        <dd class="font-bold text-white truncate">{{ $booking->customer_name }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-400">Paket / Tipe</dt>
                        <dd class="font-bold text-amber-300">
                            {{ $booking->booking_type === 'private' || $booking->package === 'private' ? 'Sewa Private (6 kursi)' : ($booking->package === 'couple' ? 'Paket Couple (2 orang)' : $booking->seats_reserved . ' orang') }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-400">Metode Pembayaran</dt>
                        <dd class="font-bold text-white">
                            {{ match ($booking->payment_method) {
                                'qris' => 'QRIS',
                                'transfer' => 'Transfer',
                                default => 'Bayar di tempat'
                            } }}
                        </dd>
                    </div>

                    @if ($booking->items->isNotEmpty())
                        <div class="flex justify-between gap-3 border-t border-dashed border-white/10 pt-2.5">
                            <dt class="text-slate-400">Menu Jajan</dt>
                            <dd class="text-right">
                                <button type="button"
                                        @click="snacksOpen = !snacksOpen"
                                        class="font-bold text-amber-400 hover:underline no-print">
                                    <span x-text="snacksOpen ? 'Tutup Rincian' : 'Lihat {{ $booking->items->count() }} Pesanan'">
                                        Lihat {{ $booking->items->count() }} Pesanan
                                    </span>
                                </button>
                                <span class="font-tabular font-bold text-white hidden print:inline">
                                    Rp {{ number_format($booking->snack_total, 0, ',', '.') }}
                                </span>
                            </dd>
                        </div>

                        <div x-show="snacksOpen" x-cloak class="mt-2 rounded-xl bg-white/5 p-3 text-xs space-y-1.5 no-print">
                            @foreach ($booking->items as $snack)
                                <div class="flex items-center justify-between gap-2 text-slate-300">
                                    <span>{{ $snack->name_snapshot }} × {{ $snack->qty }} {{ $snack->note ? '('.$snack->note.')' : '' }}</span>
                                    <span class="font-tabular text-amber-300">Rp {{ number_format($snack->subtotal, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex justify-between gap-3 border-t border-white/10 pt-3 text-sm font-black">
                        <dt class="text-white">Total Pembayaran</dt>
                        <dd class="font-tabular text-base text-amber-400">
                            Rp {{ number_format($booking->total_price, 0, ',', '.') }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Perforasi Sobekan Karcis Samping (Notches) --}}
            <div aria-hidden="true" class="ticket-notch-left"></div>
            <div aria-hidden="true" class="ticket-notch-right"></div>
        </article>

        {{-- Petunjuk Kedatangan Singkat --}}
        <div class="mt-5 rounded-2xl border border-white/10 bg-surface p-5 text-xs text-text-muted no-print shadow-sm">
            <h3 class="font-black text-text uppercase tracking-wider text-amber-500">Petunjuk Kedatangan</h3>
            <ul class="mt-2 space-y-1.5 list-disc pl-4 leading-relaxed">
                <li>Harap hadir paling lambat 10 menit sebelum jam tayang film dimulai.</li>
                <li>Tunjukkan QR Code atau Kode Booking kepada staf studio di meja reservasi.</li>
                @if ($booking->payment_method === 'di_tempat')
                    <li>Selesaikan pembayaran tunai/cash di kasir studio sebelum memasuki ruangan.</li>
                @else
                    <li>Simpan tiket digital ini atau tangkap layar (screenshot) sebagai tanda masuk resmi.</li>
                @endif
            </ul>
        </div>

        {{-- ==================== TOMBOL AKSI TIKET ==================== --}}
        <div class="mt-6 space-y-3 no-print">
            {{-- Tombol Simpan Tiket --}}
            <button type="button"
                    @click="printTicket()"
                    class="btn-gold-cinema inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl px-5 text-sm font-black shadow-lg">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Simpan tiket</span>
            </button>

            <div class="flex flex-wrap items-center justify-center gap-2 pt-1">
                {{-- Tombol Konfirmasi via WhatsApp (Memenuhi assertSee('Konfirmasi via WhatsApp')) --}}
                @if ($whatsAppUrl)
                    <a href="{{ $whatsAppUrl }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       aria-label="Konfirmasi via WhatsApp"
                       class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 text-xs font-bold text-emerald-400 hover:bg-emerald-500 hover:text-black transition">
                        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2z"/>
                        </svg>
                        <span>Konfirmasi via WhatsApp</span>
                        <span class="sr-only">Hubungi admin</span>
                    </a>
                @endif

                @if ($snackWhatsAppUrl && $booking->items->isNotEmpty())
                    <a href="{{ $snackWhatsAppUrl }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex min-h-11 items-center justify-center rounded-xl border border-border bg-surface px-4 text-xs font-bold text-text hover:border-amber-400">
                        Ubah Jajan via WhatsApp
                    </a>
                @endif

                <a href="{{ route('schedule.date', ['date' => $booking->session->session_date]) }}"
                   class="inline-flex min-h-11 items-center justify-center rounded-xl border border-border bg-surface px-4 text-xs font-bold text-text hover:border-amber-400">
                    Booking Lagi
                </a>
            </div>

            {{-- Opsi Batalkan Booking (Gratis jika sesi belum pasti tayang) --}}
            @if ($canCancel)
                <div class="pt-3 text-center">
                    <button type="button"
                            @click="cancelModal = true"
                            class="text-xs text-text-muted hover:text-red-400 hover:underline">
                        Batalkan booking ini {{ ! $isConfirmed ? '(Gratis)' : '' }}
                    </button>
                </div>
            @endif
        </div>

        {{-- ==================== MODAL IKUT FILM LAIN ==================== --}}
        <div x-show="changeFilmModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
             @click.self="changeFilmModal = false">
            <div class="w-full max-w-md rounded-2xl border border-border bg-surface p-5 shadow-2xl">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-base font-black text-text">Ikut Film Lain di Sesi Ini</h3>
                        <p class="mt-1 text-xs text-text-muted">Pilih film lain yang sudah memiliki peminat untuk mempercepat kuorum.</p>
                    </div>
                    <button type="button" @click="changeFilmModal = false" class="text-text-muted hover:text-text p-1">✕</button>
                </div>

                <form method="POST" action="{{ route('booking.change-film', ['code' => $booking->code]) }}" class="mt-4 space-y-3">
                    @csrf
                    <div class="space-y-2 max-h-60 overflow-y-auto">
                        @foreach ($allFilms as $f)
                            @php($t = $candidateTallies->firstWhere('film_id', $f->id))
                            <label class="flex items-center justify-between rounded-xl border p-3 cursor-pointer transition hover:border-amber-400"
                                   :class="'{{ $f->id }}' === '{{ $booking->film_id }}' ? 'border-amber-400 bg-amber-500/10' : 'border-border bg-surface-2'">
                                <div class="flex items-center gap-2.5">
                                    <input type="radio" name="film_id" value="{{ $f->id }}" {{ $f->id === $booking->film_id ? 'checked' : '' }} class="sr-only">
                                    <div>
                                        <p class="text-xs font-bold text-text">{{ $f->title }}</p>
                                        <p class="text-[10px] text-text-muted">{{ $f->genre }} · {{ $f->duration_minutes }}m</p>
                                    </div>
                                </div>
                                <span class="rounded bg-black/60 px-2 py-0.5 text-[10px] font-black {{ ($t['seats'] ?? 0) > 0 ? 'text-amber-400' : 'text-slate-400' }}">
                                    {{ ($t['seats'] ?? 0) > 0 ? $t['seats'] . ' peminat' : '0 peminat' }}
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-4 flex justify-end gap-2 pt-2 border-t border-border">
                        <button type="button" @click="changeFilmModal = false"
                                class="inline-flex min-h-10 items-center justify-center rounded-xl border border-border px-3.5 text-xs font-bold text-text">
                            Batal
                        </button>
                        <button type="submit" class="btn-gold-cinema inline-flex min-h-10 items-center justify-center rounded-xl px-4 text-xs font-bold">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ==================== MODAL PINDAH SESI ==================== --}}
        <div x-show="rescheduleModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
             @click.self="rescheduleModal = false">
            <div class="w-full max-w-md rounded-2xl border border-border bg-surface p-5 shadow-2xl">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-base font-black text-text">Pindah Sesi Lain (Gratis)</h3>
                        <p class="mt-1 text-xs text-text-muted">Pilih jadwal lain yang tersedia tanpa biaya tambahan.</p>
                    </div>
                    <button type="button" @click="rescheduleModal = false" class="text-text-muted hover:text-text p-1">✕</button>
                </div>

                <form method="POST" action="{{ route('booking.reschedule', ['code' => $booking->code]) }}" class="mt-4 space-y-3">
                    @csrf
                    <input type="hidden" name="film_id" value="{{ $booking->film_id }}">
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        @forelse ($alternativeSessions as $alt)
                            @php($altLocked = $alt->activeFilm())
                            @php($hasSameFilm = $altLocked && $altLocked->id === $booking->film_id)
                            <label class="flex items-center justify-between rounded-xl border p-3 cursor-pointer transition hover:border-amber-400 border-border bg-surface-2">
                                <div class="flex items-center gap-2.5">
                                    <input type="radio" name="target_session_id" value="{{ $alt->id }}" class="sr-only" required>
                                    <div>
                                        <p class="text-xs font-bold text-text">
                                            {{ $alt->startsAt()->locale('id')->translatedFormat('D, d M') }} · {{ substr((string) $alt->start_time, 0, 5) }} WIB
                                        </p>
                                        <p class="text-[10px] text-text-muted">
                                            @if ($altLocked)
                                                Film: <span class="font-bold {{ $hasSameFilm ? 'text-amber-400' : 'text-text' }}">{{ $altLocked->title }}</span>
                                            @else
                                                Belum ada film terkunci
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <span class="rounded bg-black/60 px-2 py-0.5 text-[10px] font-bold text-slate-300">
                                    Sisa {{ $alt->remainingSeats() }} kursi
                                </span>
                            </label>
                        @empty
                            <p class="text-xs text-text-muted text-center py-4">Belum ada alternatif sesi mendatang lainnya.</p>
                        @endforelse
                    </div>

                    <div class="mt-4 flex justify-end gap-2 pt-2 border-t border-border">
                        <button type="button" @click="rescheduleModal = false"
                                class="inline-flex min-h-10 items-center justify-center rounded-xl border border-border px-3.5 text-xs font-bold text-text">
                            Batal
                        </button>
                        <button type="submit" class="btn-gold-cinema inline-flex min-h-10 items-center justify-center rounded-xl px-4 text-xs font-bold">
                            Pindahkan Sesi
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ==================== MODAL UPGRADE PAKET ==================== --}}
        @if ($canUpgradeCouple || $canUpgradePrivate)
            <div x-show="upgradeModal" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
                 @click.self="upgradeModal = false">
                <div class="w-full max-w-sm rounded-2xl border border-border bg-surface p-5 shadow-2xl">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-base font-black text-text">Upgrade ke Paket Pasti Tayang</h3>
                            <p class="mt-1 text-xs text-text-muted">Langsung tayang tanpa menunggu kuorum penonton lain.</p>
                        </div>
                        <button type="button" @click="upgradeModal = false" class="text-text-muted hover:text-text p-1">✕</button>
                    </div>

                    <form method="POST" action="{{ route('booking.upgrade', ['code' => $booking->code]) }}" class="mt-4 space-y-3">
                        @csrf
                        @if ($canUpgradeCouple)
                            <label class="flex items-center justify-between rounded-xl border border-border bg-surface-2 p-3.5 cursor-pointer hover:border-amber-400">
                                <div class="flex items-center gap-2">
                                    <input type="radio" name="package" value="couple" class="sr-only" checked>
                                    <div>
                                        <p class="text-xs font-black text-text">Paket Couple (2 Kursi)</p>
                                        <p class="text-[10px] text-text-muted">Langsung pasti tayang!</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] text-text-muted block">Selisih bayar:</span>
                                    <span class="font-tabular text-xs font-black text-amber-400">+Rp {{ number_format($coupleUpgradeDiff, 0, ',', '.') }}</span>
                                </div>
                            </label>
                        @endif

                        @if ($canUpgradePrivate)
                            <label class="flex items-center justify-between rounded-xl border border-border bg-surface-2 p-3.5 cursor-pointer hover:border-amber-400">
                                <div class="flex items-center gap-2">
                                    <input type="radio" name="package" value="private" class="sr-only" {{ ! $canUpgradeCouple ? 'checked' : '' }}>
                                    <div>
                                        <p class="text-xs font-black text-text">Sewa Studio Privat (6 Kursi)</p>
                                        <p class="text-[10px] text-text-muted">Kunci seluruh studio!</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] text-text-muted block">Selisih bayar:</span>
                                    <span class="font-tabular text-xs font-black text-amber-400">+Rp {{ number_format($privateUpgradeDiff, 0, ',', '.') }}</span>
                                </div>
                            </label>
                        @endif

                        <div class="mt-4 flex justify-end gap-2 pt-2 border-t border-border">
                            <button type="button" @click="upgradeModal = false"
                                    class="inline-flex min-h-10 items-center justify-center rounded-xl border border-border px-3.5 text-xs font-bold text-text">
                                Batal
                            </button>
                            <button type="submit" class="btn-gold-cinema inline-flex min-h-10 items-center justify-center rounded-xl px-4 text-xs font-bold">
                                Konfirmasi Upgrade
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- Modal Pembatalan --}}
        @if ($canCancel)
            <div x-show="cancelModal" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
                 @click.self="cancelModal = false">
                <div class="w-full max-w-sm rounded-2xl border border-white/10 bg-[#0E131F] p-5 shadow-2xl">
                    <h3 class="text-base font-black text-white">Batalkan booking ini?</h3>
                    <p class="mt-2 text-xs leading-relaxed text-slate-300">
                        @if (! $isConfirmed)
                            Sesi belum pasti tayang. Pembatalan ini bebas biaya (gratis). Kursi Anda akan dilepaskan kembali.
                        @else
                            Kursi yang sudah Anda pesan akan dilepaskan kembali agar dapat dipesan penonton lain.
                        @endif
                    </p>
                    <form method="POST" action="{{ route('booking.cancel', ['code' => $booking->code]) }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="whatsapp" value="{{ $booking->whatsapp }}">
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="cancelModal = false"
                                    class="inline-flex min-h-10 items-center justify-center rounded-xl border border-white/20 px-3.5 text-xs font-bold text-slate-300">
                                Jangan batalkan
                            </button>
                            <button type="submit"
                                    class="inline-flex min-h-10 items-center justify-center rounded-xl bg-danger px-4 text-xs font-bold text-white hover:opacity-90">
                                Ya, batalkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
