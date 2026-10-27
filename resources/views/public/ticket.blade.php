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
    <div class="mx-auto max-w-xl px-4 py-5 sm:px-6 sm:py-8"
         x-data="{
             code: @js($booking->code),
             status: @js($booking->status),
             copied: false,
             snacksOpen: false,
             cancelModal: false,
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

                 if (this.status === 'menunggu_konfirmasi') {
                     this.startPolling();
                 }
             },
             startPolling() {
                 this.pollingInterval = setInterval(() => {
                     if (!document.hidden && this.status === 'menunggu_konfirmasi') {
                         this.checkStatus(false);
                     }
                 }, 35000);
             },
             checkStatus(manual = true) {
                 this.polling = true;
                 fetch(@js(route('booking.status.json', ['code' => $booking->code])))
                     .then(res => res.json())
                     .then(data => {
                         this.polling = false;
                         if (data.status && data.status !== this.status) {
                             window.location.reload();
                         }
                     })
                     .catch(() => {
                         this.polling = false;
                     });
             },
             copyCode() {
                 if (navigator.clipboard && navigator.clipboard.writeText) {
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

        {{-- Indikator Tahapan Pesanan --}}
        <nav aria-label="Tahapan Pesanan" class="mb-5 no-print">
            @if ($booking->payment_method === 'di_tempat')
                {{-- Alur Bayar di tempat: 2 tahap --}}
                <ol class="grid grid-cols-2 gap-2 text-center text-xs">
                    <li class="flex items-center justify-center gap-1.5 rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-2 font-bold text-emerald-400">
                        <span>1. Booking</span>
                    </li>
                    <li class="flex items-center justify-center gap-1.5 rounded-xl border border-amber-400 bg-amber-500/15 p-2 font-black text-amber-400 shadow-[0_0_12px_rgba(212,175,55,0.2)]">
                        <span>2. Selesai</span>
                    </li>
                </ol>
            @else
                {{-- Alur QRIS / Transfer: 3 tahap --}}
                <ol class="grid grid-cols-3 gap-2 text-center text-xs">
                    <li class="flex items-center justify-center gap-1.5 rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-2 font-bold text-emerald-400">
                        <span>1. Booking</span>
                    </li>
                    <li class="flex items-center justify-center gap-1.5 rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-2 font-bold text-emerald-400">
                        <span>2. Bayar</span>
                    </li>
                    <li class="flex items-center justify-center gap-1.5 rounded-xl border border-amber-400 bg-amber-500/15 p-2 font-black text-amber-400 shadow-[0_0_12px_rgba(212,175,55,0.2)]">
                        <span>3. Tiket Aktif</span>
                    </li>
                </ol>
            @endif
        </nav>

        {{-- Flash Pesan --}}
        @if (session('status'))
            <div role="status" class="mb-4 rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-4 text-xs font-bold text-emerald-400 no-print">
                {{ session('status') }}
            </div>
        @endif

        {{-- Banner Status Pemesanan --}}
        <div class="mb-5 no-print">
            @if ($booking->status === 'menunggu_konfirmasi')
                <div class="rounded-2xl border border-amber-400/40 bg-amber-500/10 p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wider text-amber-400">Menunggu konfirmasi admin</p>
                            <p class="mt-1 text-xs text-text-muted leading-relaxed">
                                @if ($booking->payment_method === 'di_tempat')
                                    Booking diterima. Bayar tunai di lokasi sebelum film mulai. Admin akan mengonfirmasi lewat WhatsApp.
                                @else
                                    Bukti bayar diterima. Admin akan memeriksa dan mengonfirmasi tiketmu.
                                @endif
                            </p>
                        </div>
                        <button type="button"
                                @click="checkStatus(true)"
                                :disabled="polling"
                                class="inline-flex min-h-9 shrink-0 items-center gap-1.5 rounded-xl border border-amber-400/40 bg-surface px-3 text-xs font-bold text-amber-400 hover:bg-amber-500 hover:text-black">
                            <svg class="size-3.5" :class="{ 'animate-spin': polling }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span x-text="polling ? 'Memeriksa...' : 'Perbarui Status'">Perbarui Status</span>
                        </button>
                    </div>
                </div>
            @elseif ($booking->status === 'terkonfirmasi')
                <div class="rounded-2xl border border-emerald-500/40 bg-emerald-500/10 p-4 text-emerald-400 shadow-sm flex items-center gap-3">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                    <div>
                        <p class="font-black text-sm">Tiket Terkonfirmasi & Siap Digunakan</p>
                        <p class="text-xs opacity-90">Tunjukkan barcode / kode booking kepada petugas saat tiba di studio.</p>
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
                        {{-- QR Code Image via standard SVG Generator --}}
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
                        <dt class="text-slate-400">Tipe Tiket</dt>
                        <dd class="font-bold text-amber-300">
                            {{ $booking->booking_type === 'private' ? 'Sewa Private (6 kursi)' : $booking->pax . ' orang' }}
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
                {{-- Tombol Konfirmasi via WhatsApp (Memenuhi assertSee('Konfirmasi via WhatsApp') dan wa.me link) --}}
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

            {{-- Opsi Batalkan Booking --}}
            @if ($canCancel)
                <div class="pt-3 text-center">
                    <button type="button"
                            @click="cancelModal = true"
                            class="text-xs text-text-muted hover:text-red-400 hover:underline">
                        Batalkan booking ini
                    </button>
                </div>
            @endif
        </div>

        {{-- Modal Pembatalan --}}
        @if ($canCancel)
            <div x-show="cancelModal" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
                 @click.self="cancelModal = false">
                <div class="w-full max-w-sm rounded-2xl border border-white/10 bg-[#0E131F] p-5 shadow-2xl">
                    <h3 class="text-base font-black text-white">Batalkan booking ini?</h3>
                    <p class="mt-2 text-xs leading-relaxed text-slate-300">
                        Kursi yang sudah Anda pesan akan dilepaskan kembali agar dapat dipesan penonton lain.
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
