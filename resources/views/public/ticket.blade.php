@extends('layouts.public')

@section('title', 'Tiket ' . $booking->code . ': SynemaX Premiere')
@section('meta')
    <meta name="description" content="Tiket digital resmi SynemaX untuk booking {{ $booking->code }}.">
    <meta property="og:title" content="Tiket SynemaX: {{ $booking->code }}">
    <meta property="og:description" content="{{ $booking->film?->title ?? 'Film Sesi' }}, {{ $booking->session->startsAt()->locale('id')->translatedFormat('D, d M Y') }}, {{ substr((string) $booking->session->start_time, 0, 5) }} WIB">
@endsection

@push('styles')
<style>
    @page {
        size: A4 portrait;
        margin: 0;
    }
    @media print {
        header, footer, nav, .no-print, button, a[href*="wa.me"], .action-buttons {
            display: none !important;
        }
        body, html {
            background-color: #ffffff !important;
            color: #0f172a !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        main {
            padding: 16mm 0 !important;
            margin: 0 !important;
        }
        .print-ticket-container {
            max-width: 440px !important;
            width: 100% !important;
            margin: 0 auto !important;
            padding: 0 !important;
        }
        .print-ticket {
            background-color: #ffffff !important;
            color: #0f172a !important;
            border: 1px solid #cbd5e1 !important;
            box-shadow: none !important;
            border-radius: 16px !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .print-ticket * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .tear-notch {
            display: none !important;
        }
    }
</style>
@endpush

@section('content')
    @php
        $myFilm = $booking->film;
        $session = $booking->session;
        $isConfirmed = $session->isConfirmed();
        $isCancelled = $booking->status === 'dibatalkan';
        $isCompleted = in_array($booking->status, ['selesai', 'terlewat', 'hadir'], true);
        $isPaid = $booking->isPaid();
        $isWaitingQuorum = ! $isConfirmed && ! $isCancelled;
        $myFilmTally = $candidateTallies->firstWhere('film_id', $booking->film_id)['seats'] ?? $booking->seats_reserved;
        $quorum = $session->quorum();
        $shortage = max(0, $quorum - $myFilmTally);
        $settings = app(\App\Services\CinemaSettings::class);
        $studioName = $settings->ticketStudioName();
        $arriveMinutes = $settings->ticketArriveMinutes();
        $ticketNotes = $settings->ticketNotes();
        $canPrint = $isConfirmed;
        $statusWaktu = $sessionStatus ?? $session->statusWaktu();
    @endphp

    <div class="mx-auto max-w-xl px-4 py-5 sm:px-6 sm:py-8 print-ticket-container"
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

        {{-- Indikator Tahapan Pesanan / Status Batal --}}
        @if ($booking->status === 'dibatalkan')
            <div class="mb-5 flex flex-wrap items-center gap-3 rounded-xl border border-red-500/30 bg-red-500/10 p-3.5 text-xs no-print">
                <x-ui.status status="dibatalkan" variant="solid" />
                <span class="font-medium text-red-800 dark:text-red-200">
                    {{ $booking->cancellation_reason ?: 'Booking ini telah dibatalkan.' }}
                </span>
            </div>
            @if ($booking->refund_needed)
                <div class="mb-5 rounded-xl border border-red-500/30 bg-red-500/10 p-3 text-xs text-red-800 dark:text-red-200 flex items-center gap-2 no-print">
                    <svg class="size-4 shrink-0 text-red-600 dark:text-red-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                    <span>Pembayaran Anda telah ditandai untuk proses Refund oleh Admin. Admin akan menghubungi lewat WhatsApp.</span>
                </div>
            @endif
        @else
            @if ($booking->payment_method === 'di_tempat')
                {{-- Alur Bayar di tempat: 2 tahap (Booking -> Selesai / Menunggu Penonton) --}}
                @php
                    $step2Label = $isWaitingQuorum ? 'Menunggu penonton' : 'Selesai';
                    $step2Done = ! $isWaitingQuorum && ($isConfirmed || $isCompleted);
                    $step2Active = $isWaitingQuorum || ! $step2Done;
                @endphp
                <nav aria-label="Tahapan Pesanan" class="mb-5 no-print">
                    <ol class="grid grid-cols-2 gap-2 text-center text-xs">
                        {{-- Langkah 1: Booking --}}
                        <li class="flex items-center justify-center gap-1.5 rounded-xl border border-emerald-600/30 bg-emerald-500/10 p-2 font-medium">
                            <span class="grid size-5 place-items-center rounded-full bg-emerald-700 text-white" aria-hidden="true">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </span>
                            <span class="font-bold text-slate-800 dark:text-slate-100"><span class="sr-only">1. Booking</span><span aria-hidden="true">Booking</span></span>
                        </li>

                        {{-- Langkah 2: Selesai / Menunggu Penonton --}}
                        @if ($step2Done)
                            <li class="flex items-center justify-center gap-1.5 rounded-xl border border-emerald-600/30 bg-emerald-500/10 p-2 font-medium">
                                <span class="grid size-5 place-items-center rounded-full bg-emerald-700 text-white" aria-hidden="true">
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </span>
                                <span class="font-bold text-slate-800 dark:text-slate-100"><span class="sr-only">2. {{ $step2Label }}</span><span aria-hidden="true">{{ $step2Label }}</span></span>
                            </li>
                        @else
                            <li class="flex items-center justify-center gap-1.5 rounded-xl border border-amber-500/50 bg-amber-500/10 p-2 font-bold">
                                <span class="grid size-5 place-items-center rounded-full bg-amber-400 text-slate-950 text-[11px] font-black" aria-hidden="true">2</span>
                                <span class="text-amber-900 dark:text-amber-200"><span class="sr-only">2. {{ $step2Label }}</span><span aria-hidden="true">{{ $step2Label }}</span></span>
                            </li>
                        @endif
                    </ol>
                </nav>
            @else
                {{-- Alur Online (QRIS / Transfer): 3 tahap (Booking -> Bayar -> Selesai) --}}
                @php
                    $step1Done = true;
                    $step2Done = $isPaid;
                    $step2Active = ! $isPaid;
                    $step3Label = $isWaitingQuorum ? 'Menunggu penonton' : 'Selesai';
                    $step3Done = $step2Done && ! $isWaitingQuorum && ($isConfirmed || $isCompleted);
                    $step3Active = $step2Done && $isWaitingQuorum;
                @endphp
                <nav aria-label="Tahapan Pesanan" class="mb-5 no-print">
                    <ol class="grid grid-cols-3 gap-2 text-center text-xs">
                        {{-- Langkah 1: Booking --}}
                        <li class="flex items-center justify-center gap-1.5 rounded-xl border border-emerald-600/30 bg-emerald-500/10 p-2 font-medium">
                            <span class="grid size-5 place-items-center rounded-full bg-emerald-700 text-white" aria-hidden="true">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </span>
                            <span class="font-bold text-slate-800 dark:text-slate-100"><span class="sr-only">1. Booking</span><span aria-hidden="true">Booking</span></span>
                        </li>

                        {{-- Langkah 2: Bayar --}}
                        @if ($step2Done)
                            <li class="flex items-center justify-center gap-1.5 rounded-xl border border-emerald-600/30 bg-emerald-500/10 p-2 font-medium">
                                <span class="grid size-5 place-items-center rounded-full bg-emerald-700 text-white" aria-hidden="true">
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </span>
                                <span class="font-bold text-slate-800 dark:text-slate-100"><span class="sr-only">2. Bayar</span><span aria-hidden="true">Bayar</span></span>
                            </li>
                        @elseif ($step2Active)
                            <li class="flex items-center justify-center gap-1.5 rounded-xl border border-amber-500/50 bg-amber-500/10 p-2 font-bold">
                                <span class="grid size-5 place-items-center rounded-full bg-amber-400 text-slate-950 text-[11px] font-black" aria-hidden="true">2</span>
                                <span class="text-amber-900 dark:text-amber-200"><span class="sr-only">2. Bayar</span><span aria-hidden="true">Bayar</span></span>
                            </li>
                        @else
                            <li class="flex items-center justify-center gap-1.5 rounded-xl border border-border bg-surface p-2 font-medium text-text-muted">
                                <span class="grid size-5 place-items-center rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-[11px] font-bold" aria-hidden="true">2</span>
                                <span><span class="sr-only">2. Bayar</span><span aria-hidden="true">Bayar</span></span>
                            </li>
                        @endif

                        {{-- Langkah 3: Selesai / Menunggu Penonton --}}
                        @if ($step3Done)
                            <li class="flex items-center justify-center gap-1.5 rounded-xl border border-emerald-600/30 bg-emerald-500/10 p-2 font-medium">
                                <span class="grid size-5 place-items-center rounded-full bg-emerald-700 text-white" aria-hidden="true">
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </span>
                                <span class="font-bold text-slate-800 dark:text-slate-100"><span class="sr-only">3. {{ $step3Label }}</span><span aria-hidden="true">{{ $step3Label }}</span></span>
                            </li>
                        @elseif ($step3Active)
                            <li class="flex items-center justify-center gap-1.5 rounded-xl border border-amber-500/50 bg-amber-500/10 p-2 font-bold">
                                <span class="grid size-5 place-items-center rounded-full bg-amber-400 text-slate-950 text-[11px] font-black" aria-hidden="true">3</span>
                                <span class="text-amber-900 dark:text-amber-200"><span class="sr-only">3. {{ $step3Label }}</span><span aria-hidden="true">{{ $step3Label }}</span></span>
                            </li>
                        @else
                            <li class="flex items-center justify-center gap-1.5 rounded-xl border border-border bg-surface p-2 font-medium text-text-muted">
                                <span class="grid size-5 place-items-center rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-[11px] font-bold" aria-hidden="true">3</span>
                                <span><span class="sr-only">3. {{ $step3Label }}</span><span aria-hidden="true">{{ $step3Label }}</span></span>
                            </li>
                        @endif
                    </ol>
                </nav>
            @endif
        @endif

        {{-- ==================== BANNERS STATUS SESI & MINIMAL PENONTON ==================== --}}
        @if ($booking->status !== 'dibatalkan')
            <div class="mb-5 no-print space-y-3">
                @if ($booking->status === 'menunggu_konfirmasi')
                    <div class="rounded-xl border border-amber-500/40 bg-surface p-4 text-xs text-text shadow-sm">
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

                @if ($booking->needsReselect())
                <div class="rounded-xl border border-amber-400/50 bg-amber-500/10 p-4 text-xs shadow-sm">
                    <span class="rounded bg-amber-400 px-2 py-0.5 text-[10px] font-bold uppercase text-black">Perlu Pilih Ulang</span>
                    <h3 class="mt-2 text-sm font-bold text-text">Film Pilihan Anda Belum Mencapai Minimal Penonton</h3>
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
                <div class="rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-4 text-emerald-400 shadow-sm flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </span>
                        <div>
                            <p class="font-bold text-sm">Pasti Tayang: {{ $session->activeFilm()?->title ?? $myFilm?->title }}</p>
                            <p class="text-xs opacity-90 text-text-muted">
                                Sesi ini telah terkonfirmasi pasti diputar ({{ match ($session->confirmation_method) {
                                    'couple' => 'Paket Couple',
                                    'private' => 'Sewa Private',
                                    'admin' => 'Dikonfirmasi Admin',
                                    default => 'Minimal 4 Kursi Terpenuhi',
                                } }}).
                            </p>
                        </div>
                    </div>
                    @if ($booking->isOnlinePayment() && $booking->status === 'menunggu_pembayaran')
                        <a href="{{ route('booking.payment', ['code' => $booking->code]) }}"
                           class="btn-gold-cinema inline-flex min-h-9 shrink-0 items-center rounded-xl px-3.5 text-xs font-bold shadow-md">
                            Bayar Sekarang
                        </a>
                    @endif
                </div>
            @elseif ($statusWaktu === 'Butuh kesepakatan')
                <div class="rounded-xl border border-amber-500/50 bg-amber-500/10 p-4 text-xs shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="rounded bg-amber-500 px-2 py-0.5 text-[10px] font-bold uppercase text-black">Butuh Kesepakatan</span>
                            <h3 class="mt-2 text-sm font-bold text-text">Sesi Memasuki Jendela Kesepakatan</h3>
                            <p class="mt-1 text-text-muted leading-relaxed">
                                Jam tayang tersisa kurang dari 3 jam dan belum ada film yang mencapai 4 kursi. Gabung ke film dengan peminat terbanyak atau ajak teman agar sesi tidak dibatalkan!
                            </p>
                        </div>
                    </div>

                    @if ($candidateTallies->isNotEmpty())
                        <div class="mt-3 rounded-xl border border-border bg-surface p-3 space-y-2">
                            <span class="text-[11px] font-bold text-text-muted uppercase tracking-wider">Peminat Saat Ini:</span>
                            @foreach ($candidateTallies as $tally)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-text truncate max-w-[200px]">{{ $tally['title'] }}</span>
                                    <span class="font-tabular font-bold text-amber-400">{{ $tally['seats'] }}/{{ $quorum }} kursi</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-3 flex flex-wrap gap-2">
                        @if ($ajakTemanUrl)
                            <a href="{{ $ajakTemanUrl }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex min-h-9 items-center gap-1.5 rounded-xl bg-emerald-700 px-3.5 text-xs font-bold text-white hover:bg-emerald-600 shadow-xs">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z"/></svg>
                                <span>Ajak Teman</span>
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
                {{-- Status Default: Menunggu Minimal Penonton --}}
                <div class="rounded-xl border border-amber-400/40 bg-amber-500/10 p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="rounded bg-amber-400 text-black px-2 py-0.5 text-[10px] font-bold uppercase">
                                    Menunggu Penonton
                                </span>
                                <span class="text-[11px] font-bold text-amber-400">
                                    {{ $myFilmTally }}/{{ $quorum }} Kursi
                                </span>
                            </div>
                            <h3 class="mt-1.5 text-sm font-bold text-text">
                                Menunggu minimal penonton: butuh {{ $shortage }} penonton lagi untuk film {{ $myFilm?->title }}
                            </h3>
                            <p class="mt-1 text-xs text-text-muted leading-relaxed">
                                @if ($booking->isOnlinePayment() && $booking->payment_expires_at === null)
                                    Instruksi pembayaran akan muncul otomatis segera setelah film mencapai 4 penonton, sehingga Anda tidak perlu khawatir tentang refund bila minimal penonton belum terpenuhi.
                                @else
                                    Ajak teman atau penonton lain untuk bergabung menonton film ini agar sesi segera terkonfirmasi pasti tayang.
                                @endif
                            </p>

                            {{-- Progress Bar Minimal Penonton --}}
                            <div class="mt-2.5 h-2 w-full overflow-hidden rounded-full bg-slate-800">
                                <div class="h-full bg-amber-400 transition-all duration-300" style="width: {{ min(100, round(($myFilmTally / $quorum) * 100)) }}%"></div>
                            </div>
                        </div>

                        <button type="button"
                                @click="checkStatus(true)"
                                :disabled="polling"
                                class="inline-flex min-h-9 shrink-0 items-center gap-1.5 rounded-xl border border-amber-400/40 bg-surface px-3 text-xs font-bold text-amber-400 hover:bg-amber-500 hover:text-black">
                            <svg class="size-3.5" :class="{ 'animate-spin': polling }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            <span x-text="polling ? 'Cek...' : 'Refresh'">Refresh</span>
                        </button>
                    </div>

                    {{-- Tombol Ajak Teman & Aksi Cepat --}}
                    <div class="mt-3 flex flex-wrap gap-2 border-t border-amber-400/20 pt-3">
                        @if ($ajakTemanUrl)
                            <a href="{{ $ajakTemanUrl }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex min-h-9 items-center gap-1.5 rounded-xl bg-emerald-700 px-3.5 text-xs font-bold text-white hover:bg-emerald-600 shadow-sm">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z"/></svg>
                                <span>Ajak Teman via WA</span>
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
        @endif

                {{-- ==================== KARTU E-TIKET RESMI ==================== --}}
        <div class="flex justify-center">
            <x-ticket.card :booking="$booking" mode="customer" />
        </div>

        {{-- ==================== TOMBOL AKSI TIKET (NO-PRINT) ==================== --}}
        <div class="mt-6 space-y-3 no-print action-buttons max-w-[400px] mx-auto w-full">
            {{-- 1. Satu Tombol Utama Menurut Kondisi --}}
            @if ($canPrint)
                <button type="button"
                        @click="printTicket()"
                        class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 px-5 text-sm font-bold border border-amber-500/30 transition">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.076-.667-2.197-1.28-3.183A9.972 9.972 0 0112 3a9.972 9.972 0 016.56 2.646c-.613.986-1.04 2.107-1.28 3.183m-10.56 0A9.96 9.96 0 003 12c0 2.478.9 4.747 2.395 6.486M17.28 13.829A9.96 9.96 0 0121 12c0-2.478-.9-4.747-2.395-6.486m0 0a9.98 9.98 0 00-2.165-2.004M6.72 13.829a9.974 9.974 0 00-2.165 2.004M12 15a3 3 0 100-6 3 3 0 000 6z" />
                    </svg>
                    <span>Simpan tiket (PDF)</span>
                </button>
            @elseif ($isCancelled)
                <p class="text-xs text-text-muted text-center leading-relaxed">
                    Booking ini telah dibatalkan dan tiket tidak dapat digunakan.
                </p>
                <a href="{{ route('schedule.date', ['date' => $booking->session->session_date]) }}"
                   class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 px-5 text-sm font-bold border border-amber-500/30 transition">
                    Lihat Jadwal Lain
                </a>
            @elseif ($isWaitingQuorum)
                <p class="text-xs text-text-muted text-center leading-relaxed">
                    E-tiket aktif dan dapat disimpan setelah sesi dipastikan tayang.
                </p>
            @elseif ($booking->isOnlinePayment() && $booking->status === 'menunggu_pembayaran')
                <a href="{{ route('booking.payment', ['code' => $booking->code]) }}"
                   class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 px-5 text-sm font-bold border border-amber-500/30 transition">
                    Lanjutkan Pembayaran
                </a>
            @else
                <p class="text-xs text-text-muted text-center leading-relaxed">
                    Tiket aktif setelah pembayaran dan sesi terkonfirmasi.
                </p>
            @endif

            {{-- 2. Maksimal Dua Tautan Sekunder: Ubah jajan & Hubungi admin via WhatsApp --}}
            <div class="flex flex-wrap items-center justify-center gap-2 pt-1">
                @if ($snackWhatsAppUrl && $booking->items->isNotEmpty() && ! $isCancelled && ! $isCompleted)
                    <a href="{{ $snackWhatsAppUrl }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex min-h-11 items-center justify-center rounded-xl border border-border bg-surface px-4 text-xs font-bold text-text hover:border-amber-400 transition">
                        Ubah jajan
                    </a>
                @endif

                @if ($whatsAppUrl)
                    <a href="{{ $whatsAppUrl }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       aria-label="Konfirmasi via WhatsApp"
                       class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-emerald-600/40 bg-emerald-500/10 px-4 text-xs font-bold text-emerald-700 dark:text-emerald-300 hover:bg-emerald-600 hover:text-white transition">
                        <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a.75.75 0 01-.974-.948l.582-1.745C3.766 16.71 3 14.475 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/>
                        </svg>
                        <span>Hubungi admin</span>
                        <span class="sr-only">Konfirmasi via WhatsApp</span>
                    </a>
                @endif
            </div>

            {{-- 3. "Booking lagi" sebagai tautan teks HANYA untuk booking selesai/dibatalkan --}}
            @if ($isCompleted || $isCancelled)
                <div class="pt-2 text-center">
                    <a href="{{ route('schedule.date', ['date' => $booking->session->session_date]) }}"
                       class="text-xs font-semibold text-text-muted hover:text-text hover:underline transition">
                        Booking lagi
                    </a>
                </div>
            @endif

            {{-- Batalkan booking jika masih diperbolehkan --}}
            @if ($canCancel)
                <div class="pt-2 text-center">
                    <button type="button"
                            @click="cancelModal = true"
                            class="text-xs text-text-muted hover:text-red-500 hover:underline transition">
                        Batalkan booking ini {{ ! $isConfirmed ? '(Gratis)' : '' }}
                    </button>
                </div>
            @endif
        </div>

        {{-- ==================== MODAL IKUT FILM LAIN ==================== --}}
        <div x-show="changeFilmModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
             @click.self="changeFilmModal = false">
            <div class="w-full max-w-md rounded-2xl border border-border bg-surface p-5 shadow-2xl">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-base font-bold text-text">Ikut Film Lain di Sesi Ini</h3>
                        <p class="mt-1 text-xs text-text-muted">Pilih film lain yang sudah memiliki peminat untuk mempercepat jadwal tayang.</p>
                    </div>
                    <button type="button" @click="changeFilmModal = false" class="text-text-muted hover:text-text p-1 rounded-lg hover:bg-surface-2" aria-label="Tutup"><svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
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
                                <span class="rounded bg-black/60 px-2 py-0.5 text-[10px] font-bold {{ ($t['seats'] ?? 0) > 0 ? 'text-amber-400' : 'text-slate-400' }}">
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
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
             @click.self="rescheduleModal = false">
            <div class="w-full max-w-md rounded-2xl border border-border bg-surface p-5 shadow-2xl">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-base font-bold text-text">Pindah Sesi Lain (Gratis)</h3>
                        <p class="mt-1 text-xs text-text-muted">Pilih jadwal lain yang tersedia tanpa biaya tambahan.</p>
                    </div>
                    <button type="button" @click="rescheduleModal = false" class="text-text-muted hover:text-text p-1 rounded-lg hover:bg-surface-2" aria-label="Tutup"><svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
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
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
                 @click.self="upgradeModal = false">
                <div class="w-full max-w-sm rounded-2xl border border-border bg-surface p-5 shadow-2xl">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-base font-bold text-text">Upgrade ke Paket Pasti Tayang</h3>
                            <p class="mt-1 text-xs text-text-muted">Langsung pasti tayang tanpa menunggu penonton lain.</p>
                        </div>
                        <button type="button" @click="upgradeModal = false" class="text-text-muted hover:text-text p-1 rounded-lg hover:bg-surface-2" aria-label="Tutup"><svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>

                    <form method="POST" action="{{ route('booking.upgrade', ['code' => $booking->code]) }}" class="mt-4 space-y-3">
                        @csrf
                        @if ($canUpgradeCouple)
                            <label class="flex items-center justify-between rounded-xl border border-border bg-surface-2 p-3.5 cursor-pointer hover:border-amber-400">
                                <div class="flex items-center gap-2">
                                    <input type="radio" name="package" value="couple" class="sr-only" checked>
                                    <div>
                                        <p class="text-xs font-bold text-text">Paket Couple (2 Kursi)</p>
                                        <p class="text-[10px] text-text-muted">Langsung pasti tayang.</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] text-text-muted block">Selisih bayar:</span>
                                    <span class="font-tabular text-xs font-bold text-amber-400">+Rp {{ number_format($coupleUpgradeDiff, 0, ',', '.') }}</span>
                                </div>
                            </label>
                        @endif

                        @if ($canUpgradePrivate)
                            <label class="flex items-center justify-between rounded-xl border border-border bg-surface-2 p-3.5 cursor-pointer hover:border-amber-400">
                                <div class="flex items-center gap-2">
                                    <input type="radio" name="package" value="private" class="sr-only" {{ ! $canUpgradeCouple ? 'checked' : '' }}>
                                    <div>
                                        <p class="text-xs font-bold text-text">Sewa Studio Privat (6 Kursi)</p>
                                        <p class="text-[10px] text-text-muted">Kunci seluruh 6 kursi studio.</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] text-text-muted block">Selisih bayar:</span>
                                    <span class="font-tabular text-xs font-bold text-amber-400">+Rp {{ number_format($privateUpgradeDiff, 0, ',', '.') }}</span>
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
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
                 @click.self="cancelModal = false">
                <div class="w-full max-w-sm rounded-2xl border border-white/10 bg-[#0E131F] p-5 shadow-2xl">
                    <h3 class="text-base font-bold text-white">Batalkan booking ini?</h3>
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
