@extends('layouts.public')

@section('title', 'Tiket ' . $booking->code . ': SynemaX Premiere')
@section('meta')
    <meta name="description" content="Tiket digital resmi SynemaX untuk booking {{ $booking->code }}.">
    <meta property="og:title" content="Tiket SynemaX: {{ $booking->code }}">
    <meta property="og:description" content="{{ $booking->film?->title ?? 'Film Sesi' }}, {{ format_tanggal_id($booking->session->session_date) }}, {{ format_jam_id($booking->session->start_time) }}">
@endsection

@push('styles')
<style>
    @page {
        size: A4 portrait;
        margin: 0;
    }
    @media print {
        header, footer, nav, .no-print, button, a, [class*="z-[9999]"] {
            display: none !important;
        }
        body, html {
            background: #ffffff !important;
            background-color: #ffffff !important;
            color: #0f172a !important;
            padding: 0 !important;
            margin: 0 !important;
            height: auto !important;
            min-height: 0 !important;
        }
        main {
            display: block !important;
            padding: 10mm 0 0 0 !important;
            margin: 0 !important;
        }
        .print-ticket-container {
            max-width: 380px !important;
            width: 100% !important;
            margin: 0 auto !important;
            padding: 0 !important;
        }
        #printable-ticket, .print-ticket {
            max-width: 380px !important;
            width: 380px !important;
            margin: 0 auto !important;
            background-color: #ffffff !important;
            color: #0f172a !important;
            border: 1px solid #cbd5e1 !important;
            box-shadow: none !important;
            border-radius: 12px !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        #printable-ticket *, .print-ticket * {
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
        $flowState = $booking->flowState();
        $myFilm = $booking->film;
        $session = $booking->session;
        $isCancelled = $flowState === 'dibatalkan';
        $isExpired = $flowState === 'kedaluwarsa';
        $isCompleted = in_array($flowState, ['selesai', 'terlewat', 'hadir', 'tidak_datang'], true);
        $isPaid = $booking->isPaid();
        $isWaitingPenonton = $flowState === 'menunggu_penonton';
        $isDiproses = $flowState === 'diproses';
        $isAktif = $flowState === 'aktif';

        $candidateTallies = ($session && method_exists($session, 'candidateFilmTallies')) ? $session->candidateFilmTallies() : collect();
        $myFilmTally = $candidateTallies->firstWhere('film_id', $booking->film_id)['seats'] ?? ($booking->people_count ?? $booking->seats_reserved);
        $quorum = $session ? $session->quorum() : 3;
        $shortage = max(0, $quorum - $myFilmTally);

        $settings = app(\App\Services\CinemaSettings::class);
        $studioName = $settings->ticketStudioName();
        $arriveMinutes = $settings->ticketArriveMinutes();
        $ticketNotes = $settings->ticketNotes();
        $canPrint = $isAktif || $isCompleted;
        $statusWaktu = $sessionStatus ?? $session?->statusWaktu();
    @endphp

    <div class="mx-auto max-w-[520px] w-full px-4 py-5 sm:py-8 print-ticket-container"
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

                 if (this.status === 'menunggu_konfirmasi' || !@js($session?->isConfirmed() ?? false)) {
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
                 fetch(@js(route('booking.status.json', ['code' => $booking->code], false)))
                     .then(res => res.json())
                     .then(data => {
                         this.polling = false;
                         if (data.status && (data.status !== this.status || Boolean(data.session_confirmed) !== @js($session?->isConfirmed() ?? false))) {
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
        @if (session('status') && $flowState !== 'diproses')
            <div role="status" class="mb-4 rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-4 text-xs font-bold text-emerald-400 no-print">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div role="alert" class="mb-4 rounded-xl border border-danger/40 bg-danger-surface p-4 text-xs font-bold text-danger-text no-print">
                {{ session('error') }}
            </div>
        @endif

        @if (session('warning'))
            <div role="status" class="mb-4 rounded-xl border border-warning/40 bg-warning-surface p-4 text-xs font-bold text-warning no-print">
                {{ session('warning') }}
            </div>
        @endif

        {{-- ==================== STEPPER / STATUS HEADER ==================== --}}
        @if ($isCancelled || $isExpired)
            {{-- Satu baris status merah + alasan untuk booking batal atau kedaluwarsa --}}
            <div class="mb-5 rounded-xl border border-danger/40 bg-danger-surface p-4 text-xs font-semibold text-danger-text no-print">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <x-ui.status :status="$isExpired ? 'kedaluwarsa' : 'dibatalkan'" variant="solid" />
                        <span>
                            {{ $isExpired ? 'Batas waktu pembayaran habis dan tempat pesanan telah dilepas.' : ($booking->cancellation_reason ?: 'Booking ini telah dibatalkan.') }}
                        </span>
                    </div>
                    @if ($booking->refund_needed)
                        <span class="text-[11px] font-bold text-danger-text/90">Perlu proses refund oleh admin via WhatsApp.</span>
                    @endif
                </div>
            </div>
        @else
            {{-- Stepper 2 Langkah (Bayar di tempat) atau 3 Langkah (Online) --}}
            @if ($booking->payment_method === 'di_tempat')
                {{-- Alur Bayar di tempat: 2 tahap (Booking -> Tiket / Menunggu penonton / Tiket aktif) --}}
                @php
                    $step2Label = match ($flowState) {
                        'menunggu_penonton' => 'Menunggu penonton',
                        'aktif' => 'Tiket aktif',
                        default => 'Tiket',
                    };
                    $step2Active = $flowState === 'menunggu_penonton';
                    $step2Done = in_array($flowState, ['aktif', 'selesai', 'hadir'], true);
                @endphp
                <nav aria-label="Tahapan Pesanan" class="mb-5 no-print">
                    <ol class="grid grid-cols-2 gap-2 text-center text-xs">
                        {{-- Langkah 1: Booking (Selesai) --}}
                        <li class="flex items-center justify-center gap-1.5 rounded-xl border border-emerald-600/30 bg-emerald-500/10 p-2 font-medium">
                            <span class="grid size-5 place-items-center rounded-full bg-emerald-700 text-white" aria-hidden="true">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </span>
                            <span class="font-bold text-slate-800 dark:text-slate-100">Booking</span>
                        </li>

                        {{-- Langkah 2: Tiket aktif / Menunggu penonton --}}
                        @if ($step2Done)
                            <li class="flex items-center justify-center gap-1.5 rounded-xl border border-emerald-600/30 bg-emerald-500/10 p-2 font-medium">
                                <span class="grid size-5 place-items-center rounded-full bg-emerald-700 text-white" aria-hidden="true">
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </span>
                                <span class="font-bold text-slate-800 dark:text-slate-100">{{ $step2Label }}</span>
                            </li>
                        @else
                            <li class="flex items-center justify-center gap-1.5 rounded-xl border border-amber-500/50 bg-amber-500/10 p-2 font-bold">
                                <span class="grid size-5 place-items-center rounded-full bg-amber-400 text-slate-950 text-[11px] font-black" aria-hidden="true">2</span>
                                <span class="text-amber-900 dark:text-amber-200">{{ $step2Label }}</span>
                            </li>
                        @endif
                    </ol>
                </nav>
            @else
                {{-- Alur Online (QRIS / Transfer): 3 tahap (Booking -> Bayar -> Tiket) --}}
                @php
                    $step2Done = in_array($flowState, ['diproses', 'menunggu_penonton', 'aktif', 'selesai', 'hadir'], true);
                    $step2Active = $flowState === 'menunggu_bayar';

                    $step3Label = match ($flowState) {
                        'menunggu_penonton' => 'Menunggu penonton',
                        'aktif' => 'Tiket aktif',
                        default => 'Tiket',
                    };
                    $step3Done = in_array($flowState, ['aktif', 'selesai', 'hadir'], true);
                    $step3Active = in_array($flowState, ['diproses', 'menunggu_penonton'], true);
                @endphp
                <nav aria-label="Tahapan Pesanan" class="mb-5 no-print">
                    <ol class="grid grid-cols-3 gap-2 text-center text-xs">
                        {{-- Langkah 1: Booking (Selesai) --}}
                        <li class="flex items-center justify-center gap-1.5 rounded-xl border border-emerald-600/30 bg-emerald-500/10 p-2 font-medium">
                            <span class="grid size-5 place-items-center rounded-full bg-emerald-700 text-white" aria-hidden="true">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </span>
                            <span class="font-bold text-slate-800 dark:text-slate-100">Booking</span>
                        </li>

                        {{-- Langkah 2: Bayar --}}
                        @if ($step2Done)
                            <li class="flex items-center justify-center gap-1.5 rounded-xl border border-emerald-600/30 bg-emerald-500/10 p-2 font-medium">
                                <span class="grid size-5 place-items-center rounded-full bg-emerald-700 text-white" aria-hidden="true">
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </span>
                                <span class="font-bold text-slate-800 dark:text-slate-100">Bayar</span>
                            </li>
                        @elseif ($step2Active)
                            <li class="flex items-center justify-center gap-1.5 rounded-xl border border-amber-500/50 bg-amber-500/10 p-2 font-bold">
                                <span class="grid size-5 place-items-center rounded-full bg-amber-400 text-slate-950 text-[11px] font-black" aria-hidden="true">2</span>
                                <span class="text-amber-900 dark:text-amber-200">Bayar</span>
                            </li>
                        @else
                            <li class="flex items-center justify-center gap-1.5 rounded-xl border border-border bg-surface p-2 font-medium text-text-muted">
                                <span class="grid size-5 place-items-center rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-[11px] font-bold" aria-hidden="true">2</span>
                                <span>Bayar</span>
                            </li>
                        @endif

                        {{-- Langkah 3: Tiket / Menunggu penonton / Tiket aktif --}}
                        @if ($step3Done)
                            <li class="flex items-center justify-center gap-1.5 rounded-xl border border-emerald-600/30 bg-emerald-500/10 p-2 font-medium">
                                <span class="grid size-5 place-items-center rounded-full bg-emerald-700 text-white" aria-hidden="true">
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </span>
                                <span class="font-bold text-slate-800 dark:text-slate-100">{{ $step3Label }}</span>
                            </li>
                        @elseif ($step3Active)
                            <li class="flex items-center justify-center gap-1.5 rounded-xl border border-amber-500/50 bg-amber-500/10 p-2 font-bold">
                                <span class="grid size-5 place-items-center rounded-full bg-amber-400 text-slate-950 text-[11px] font-black" aria-hidden="true">3</span>
                                <span class="text-amber-900 dark:text-amber-200">{{ $step3Label }}</span>
                            </li>
                        @else
                            <li class="flex items-center justify-center gap-1.5 rounded-xl border border-border bg-surface p-2 font-medium text-text-muted">
                                <span class="grid size-5 place-items-center rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-[11px] font-bold" aria-hidden="true">3</span>
                                <span>{{ $step3Label }}</span>
                            </li>
                        @endif
                    </ol>
                </nav>
            @endif
        @endif

        {{-- ==================== KARTU STATUS KHUSUS ==================== --}}
        {{-- 1. Kartu Status: Bukti Pembayaran Diterima / Diproses --}}
        @if ($flowState === 'diproses')
            <div class="mb-5 rounded-2xl border border-amber-500/30 bg-surface p-5 text-xs text-text shadow-sm no-print">
                <div class="flex items-start gap-3">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400 font-bold">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <div class="flex-1 min-w-0">
                        <h2 class="text-sm font-bold text-amber-600 dark:text-amber-400">Bukti pembayaran diterima, menunggu konfirmasi admin</h2>
                        <p class="mt-1 text-text-muted leading-relaxed">
                            Admin bioskop sedang memverifikasi bukti transfer Anda. Tiket aktif akan diterbitkan segera setelah pembayaran diverifikasi.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- 2. Banner Perlu Pilih Ulang --}}
        @if ($booking->needsReselect())
            <div class="mb-5 rounded-xl border border-amber-400/50 bg-amber-500/10 p-4 text-xs shadow-sm no-print">
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
        @endif

        {{-- 3. KOTAK "MENUNGGU PENONTON" DIRAPIKAN (Hanya Tampil Saat flowState == 'menunggu_penonton' dan shortage >= 1) --}}
        @if ($flowState === 'menunggu_penonton' && $shortage >= 1)
            <div id="menunggu-penonton-box" class="mb-5 rounded-2xl border border-amber-500/40 bg-surface p-5 shadow-sm space-y-4 no-print">
                {{-- Baris Atas: Chip Status (Kiri) dan Tombol Perbarui (Kanan) --}}
                <div class="flex items-center justify-between gap-3">
                    <x-ui.status status="menunggu_penonton">Menunggu penonton</x-ui.status>

                    <button type="button"
                            @click="checkStatus(true)"
                            :disabled="polling"
                            class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center gap-1.5 rounded-xl border border-border bg-surface-2 hover:bg-surface px-3 text-xs font-bold text-text hover:border-amber-400 transition"
                            aria-label="Perbarui status pemesanan">
                        <svg class="size-4 text-text-muted" :class="{ 'animate-spin': polling }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span class="hidden sm:inline" x-text="polling ? 'Memperbarui...' : 'Perbarui'">Perbarui</span>
                    </button>
                </div>

                {{-- Judul & Progres --}}
                <div>
                    <h2 class="text-sm sm:text-base font-black text-text tracking-tight">
                        Butuh {{ $shortage }} orang lagi untuk film {{ $myFilm?->title ?? 'Pilihan' }}
                    </h2>
                    
                    {{-- Progres Bar Tipis --}}
                    <div class="mt-2.5 space-y-1">
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-surface-2 border border-border">
                            <div class="h-full rounded-full bg-amber-500 transition-all duration-500" style="width: {{ min(100, round(($myFilmTally / $quorum) * 100)) }}%"></div>
                        </div>
                        <p class="text-[11px] font-semibold text-text-muted">
                            {{ $myFilmTally }} dari {{ $quorum }} orang
                        </p>
                    </div>

                    {{-- Satu Kalimat Batas --}}
                    <p class="mt-2 text-xs text-text-muted leading-relaxed">
                        Sesi dipastikan paling lambat {{ $session ? format_jam_id($session->confirmationDeadline()) : '-' }}. Jika belum cukup, booking dibatalkan tanpa biaya.
                    </p>
                </div>

                {{-- Tombol Aksi: Baris 1 Tombol Utama Penuh, Baris 2 Grid 3 Kolom Outline --}}
                <div class="space-y-2 pt-1 border-t border-border">
                    {{-- Baris 1: Ajak Teman via WhatsApp (Full Width) --}}
                    @if ($ajakTemanUrl)
                        <a href="{{ $ajakTemanUrl }}" target="_blank" rel="noopener noreferrer"
                           class="flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl bg-[#25D366] hover:bg-[#20BD5A] px-4 text-xs font-bold text-white transition">
                            <svg class="size-4 fill-current" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                            <span>Ajak teman via WhatsApp</span>
                        </a>
                    @endif

                    {{-- Baris 2: Grid 3 Kolom Outline Berukuran Sama di Desktop, Tumpuk 1 Kolom di Mobile --}}
                    @php
                        $hasOtherFilms = $allFilms->where('id', '!=', $booking->film_id)->isNotEmpty();
                        $canUpgrade = $canUpgradeCouple || $canUpgradePrivate;
                    @endphp
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        @if ($hasOtherFilms)
                            <button type="button" @click="changeFilmModal = true"
                                    class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-border bg-surface-2 hover:bg-surface px-3 text-xs font-semibold text-text hover:border-slate-400 dark:hover:border-white/30 transition">
                                Ikut film lain
                            </button>
                        @endif

                        <button type="button" @click="rescheduleModal = true"
                                class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-border bg-surface-2 hover:bg-surface px-3 text-xs font-semibold text-text hover:border-slate-400 dark:hover:border-white/30 transition">
                            Pindah sesi
                        </button>

                        @if ($canUpgrade)
                            <button type="button" @click="upgradeModal = true"
                                    class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-border bg-surface-2 hover:bg-surface px-3 text-xs font-semibold text-text hover:border-slate-400 dark:hover:border-white/30 transition">
                                Upgrade paket
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- ==================== KARTU E-TIKET RESMI ==================== --}}
        <div class="w-full">
            <x-ticket.card :booking="$booking" mode="customer" />
        </div>

        {{-- ==================== TOMBOL AKSI TIKET (NO-PRINT) ==================== --}}
        <div class="mt-6 space-y-3 no-print w-full">
            {{-- Status Aktif: Simpan Tiket PDF (Utama) & Ubah Jajan (Sekunder) --}}
            @if ($isAktif)
                <button type="button"
                        @click="printTicket()"
                        class="btn-gold-cinema flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl px-5 text-sm font-bold shadow-none">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span>Simpan tiket (PDF)</span>
                </button>

                @if ($snackWhatsAppUrl && $booking->items->isNotEmpty())
                    <a href="{{ $snackWhatsAppUrl }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="flex min-h-[44px] w-full items-center justify-center rounded-xl border border-border bg-surface px-4 text-xs font-bold text-text hover:border-slate-400 dark:hover:border-white/30 transition shadow-none">
                        Ubah jajan
                    </a>
                @endif
            @endif

            {{-- Status Dibatalkan, Kedaluwarsa, Selesai: Aksi Utama Pesan Sesi Lain --}}
            @if (in_array($flowState, ['dibatalkan', 'kedaluwarsa', 'selesai', 'hadir', 'tidak_datang'], true) || $isCancelled || $isExpired)
                <a href="{{ route('schedule.date', ['date' => $session?->session_date ?? now()->toDateString()]) }}"
                   class="btn-gold-cinema flex min-h-[44px] w-full items-center justify-center rounded-xl px-4 text-xs font-bold shadow-none">
                    Pesan sesi lain
                </a>
            @endif

            {{-- Tautan Teks Batalkan Booking Bila Boleh --}}
            @if ($canCancel && ! in_array($flowState, ['dibatalkan', 'kedaluwarsa', 'selesai', 'hadir', 'tidak_datang'], true) && ! $isCancelled && ! $isExpired)
                <div class="pt-4 text-center">
                    <button type="button"
                            @click="cancelModal = true"
                            class="text-xs text-text-muted hover:text-red-500 hover:underline transition">
                        Batalkan booking ini {{ ! ($session?->isConfirmed() ?? false) ? '(Gratis)' : '' }}
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
                                            {{ format_tanggal_id($alt->session_date) }} · {{ format_jam_id($alt->start_time) }}
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
                                    Sisa {{ $alt->remainingSeats() }} tempat
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
                                        <p class="text-xs font-bold text-text">Paket Couple (2 orang)</p>
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
                                        <p class="text-xs font-bold text-text">Sewa Studio Privat (6 orang)</p>
                                        <p class="text-[10px] text-text-muted">Kunci seluruh 6 tempat studio.</p>
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
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
                 @click.self="cancelModal = false">
                <div class="w-full max-w-sm rounded-2xl border border-border dark:border-white/10 bg-surface dark:bg-[#0E131F] p-5 shadow-2xl">
                    <h3 class="text-base font-bold text-text dark:text-white">Batalkan booking ini?</h3>
                    <p class="mt-2 text-xs leading-relaxed text-text-muted">
                        @if (! ($session?->isConfirmed() ?? false))
                            Sesi belum pasti tayang. Pembatalan ini bebas biaya (gratis). Tempat Anda akan dilepaskan kembali.
                        @else
                            Tempat yang sudah Anda pesan akan dilepaskan kembali agar dapat dipesan penonton lain.
                        @endif
                    </p>
                    <form method="POST" action="{{ route('booking.cancel', ['code' => $booking->code]) }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="whatsapp" value="{{ $booking->whatsapp }}">
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="cancelModal = false"
                                    class="inline-flex min-h-10 items-center justify-center rounded-xl border border-border bg-surface-2 dark:bg-transparent dark:border-white/20 px-3.5 text-xs font-bold text-text hover:bg-surface transition">
                                Jangan batalkan
                            </button>
                            <button type="submit"
                                    class="inline-flex min-h-10 items-center justify-center rounded-xl bg-danger px-4 text-xs font-bold text-white hover:opacity-90 transition">
                                Ya, batalkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
