@extends('layouts.public')

@section('title', 'Tiket ' . $booking->code . ' — SynemaX')
@section('meta')
    <meta name="description" content="Tiket digital SynemaX untuk booking {{ $booking->code }}.">
    <meta property="og:title" content="Tiket SynemaX — {{ $booking->code }}">
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
            background-color: #ECEFEA !important;
            color: #16201F !important;
            border: 1.5px solid #16201F !important;
            box-shadow: none !important;
            max-width: 100% !important;
            margin: 0 auto !important;
            page-break-inside: avoid;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .print-ticket * {
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
             statusText: '',
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
                 document.addEventListener('visibilitychange', () => {
                     if (!document.hidden && this.status === 'menunggu_konfirmasi') {
                         this.checkStatus(false);
                     }
                 });
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
        <nav aria-label="Tahapan Pesanan" class="mb-4 no-print">
            @if ($booking->payment_method === 'di_tempat')
                {{-- Alur Bayar di tempat: 2 tahap --}}
                <ol class="grid grid-cols-2 gap-2 text-center text-xs">
                    <li class="flex items-center justify-center gap-1.5 rounded-[var(--radius-control)] border border-border bg-surface-2 p-2 font-medium text-text-muted">
                        <span>1. Booking</span>
                    </li>
                    <li class="flex items-center justify-center gap-1.5 rounded-[var(--radius-control)] border border-primary/40 bg-surface p-2 font-bold text-primary">
                        <span>2. Selesai</span>
                    </li>
                </ol>
            @else
                {{-- Alur QRIS / Transfer: 3 tahap --}}
                <ol class="grid grid-cols-3 gap-2 text-center text-xs">
                    <li class="flex items-center justify-center gap-1.5 rounded-[var(--radius-control)] border border-border bg-surface-2 p-2 font-medium text-text-muted">
                        <span>1. Booking</span>
                    </li>
                    <li class="flex items-center justify-center gap-1.5 rounded-[var(--radius-control)] border border-border bg-surface-2 p-2 font-medium text-text-muted">
                        <span>2. Bayar</span>
                    </li>
                    <li class="flex items-center justify-center gap-1.5 rounded-[var(--radius-control)] border border-primary/40 bg-surface p-2 font-bold text-primary">
                        <span>3. Selesai</span>
                    </li>
                </ol>
            @endif
        </nav>

        {{-- Flash Pesan --}}
        @if (session('status'))
            <div role="status" class="mb-4 rounded-[var(--radius-control)] border border-success-border bg-success-surface p-3 text-xs font-bold text-success-text no-print">
                {{ session('status') }}
            </div>
        @endif

        {{-- Banner Status di Atas Tiket --}}
        <div class="mb-4 no-print">
            @if ($booking->status === 'menunggu_konfirmasi')
                <div class="rounded-[var(--radius-control)] border border-border-input bg-surface p-3.5 text-xs text-text shadow-xs">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-bold text-primary">Menunggu Konfirmasi Admin</p>
                            <p class="mt-0.5 text-text-muted leading-relaxed">
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
                                class="inline-flex min-h-9 shrink-0 items-center gap-1.5 rounded-[var(--radius-control)] border border-border-input bg-surface px-2.5 py-1 text-xs font-bold text-text hover:border-primary disabled:opacity-50"
                                title="Periksa status terbaru">
                            <span :class="{ 'animate-spin': polling }">↻</span>
                            <span x-text="polling ? 'Memeriksa...' : 'Perbarui'">Perbarui</span>
                        </button>
                    </div>
                </div>
            @elseif ($booking->status === 'terkonfirmasi')
                <div class="rounded-[var(--radius-control)] border border-success-border bg-success-surface p-3.5 text-xs text-success-text">
                    <p class="font-bold">Tiket terkonfirmasi</p>
                    <p class="mt-0.5 leading-relaxed opacity-95">Tiket sudah aktif. Tunjukkan kode tiket ini saat tiba di studio.</p>
                </div>
            @elseif ($booking->status === 'kedaluwarsa')
                <div class="rounded-[var(--radius-control)] border border-danger-border bg-danger-surface p-3.5 text-xs text-danger-text">
                    <p class="font-bold">Booking kedaluwarsa</p>
                    <p class="mt-0.5 leading-relaxed opacity-95">Batas waktu pembayaran telah habis dan kursi telah dilepaskan kembali.</p>
                    <div class="mt-2.5">
                        <a href="{{ route('schedule.date', ['date' => $booking->session->session_date]) }}"
                           class="inline-flex min-h-9 items-center justify-center rounded-[var(--radius-control)] bg-primary px-3 text-xs font-bold text-white hover:opacity-95">
                            Booking lagi
                        </a>
                    </div>
                </div>
            @elseif ($booking->status === 'dibatalkan')
                <div class="rounded-[var(--radius-control)] border border-danger-border bg-danger-surface p-3.5 text-xs text-danger-text">
                    <p class="font-bold">Booking dibatalkan</p>
                    <p class="mt-0.5 leading-relaxed opacity-95">Pemesanan ini telah dibatalkan.</p>
                    <div class="mt-2.5">
                        <a href="{{ route('schedule.date', ['date' => $booking->session->session_date]) }}"
                           class="inline-flex min-h-9 items-center justify-center rounded-[var(--radius-control)] bg-primary px-3 text-xs font-bold text-white hover:opacity-95">
                            Booking lagi
                        </a>
                    </div>
                </div>
            @elseif ($booking->status === 'hadir')
                <div class="rounded-[var(--radius-control)] border border-success-border bg-success-surface p-3.5 text-xs text-success-text">
                    <p class="font-bold">Tiket sudah dipakai</p>
                    <p class="mt-0.5 leading-relaxed opacity-95">Terima kasih telah berkunjung dan menonton bersama kami di SynemaX.</p>
                </div>
            @endif

            {{-- Jika bukti pernah ditolak admin --}}
            @if ($booking->proof_rejection_note && $booking->status === 'menunggu_pembayaran')
                <div class="mt-2.5 rounded-[var(--radius-control)] border border-danger-border bg-danger-surface p-3.5 text-xs text-danger-text">
                    <p class="font-bold">Bukti transfer sebelumnya belum disetujui admin:</p>
                    <p class="mt-0.5 font-semibold">{{ $booking->proof_rejection_note }}</p>
                    <div class="mt-2.5">
                        <a href="{{ route('booking.payment', ['code' => $booking->code]) }}"
                           class="inline-flex min-h-9 items-center justify-center rounded-[var(--radius-control)] bg-primary px-3 text-xs font-bold text-white hover:opacity-95">
                            Unggah ulang bukti bayar
                        </a>
                    </div>
                </div>
            @endif
        </div>

        {{-- KARTU TIKET DIGITAL FISIK (Kertas #ECEFEA di kedua tema, Sobekan di Tengah, Pita Kuning Karcis) --}}
        <article class="print-ticket ticket-paper-card relative overflow-hidden rounded-[var(--radius-control)] border border-[#C2CECA] shadow-sm {{ in_array($booking->status, ['dibatalkan', 'kedaluwarsa']) ? 'opacity-60 grayscale-[40%]' : '' }}"
                 style="background-color: var(--ticket-paper-color); color: #16201F;">

            {{-- Header Tiket: Sebutan Brand + Status --}}
            <header class="p-4 sm:p-5 border-b border-dashed border-[#C2CECA]">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <x-brand.logo />
                        <span class="text-[11px] font-semibold text-[#16201F]/70">Tiket Masuk Studio</span>
                    </div>
                    <div>
                        @php
                            $statusLabel = match ($booking->status) {
                                'terkonfirmasi' => 'Terkonfirmasi',
                                'menunggu_konfirmasi' => 'Menunggu konfirmasi',
                                'menunggu_pembayaran' => 'Menunggu bayar',
                                'kedaluwarsa' => 'Kedaluwarsa',
                                'dibatalkan' => 'Dibatalkan',
                                'hadir' => 'Selesai / Hadir',
                                default => $booking->status,
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-[var(--radius-control)] border border-[#16201F]/20 bg-white/80 px-2 py-0.5 text-[11px] font-bold text-[#16201F]">
                            {{ $statusLabel }}
                        </span>
                    </div>
                </div>

                {{-- Judul Film & Jadwal --}}
                <div class="mt-4 flex items-start gap-3.5">
                    @if ($booking->film?->poster_path)
                        <img src="{{ asset('storage/'.$booking->film->poster_path) }}"
                             alt="Poster {{ $booking->film->title }}"
                             class="h-16 w-11 shrink-0 rounded-[var(--radius-control)] object-cover shadow-xs">
                    @endif
                    <div class="flex-1 min-w-0">
                        <h2 class="text-base font-extrabold text-[#16201F] leading-tight truncate sm:text-lg">
                            {{ $booking->film?->title ?? 'Film Sesi' }}
                        </h2>
                        <p class="mt-1 text-xs text-[#16201F]/80">
                            {{ $booking->session->startsAt()->locale('id')->translatedFormat('l, d F Y') }}
                        </p>
                        <p class="mt-0.5 text-xs text-[#16201F]/70">
                            <span class="font-tabular font-bold text-[#16201F]">{{ substr((string) $booking->session->start_time, 0, 5) }} WIB</span>, {{ $booking->film?->age_rating ?? 'SU' }}, {{ $booking->film?->duration_minutes ?? 90 }} menit
                        </p>
                    </div>
                </div>
            </header>

            {{-- PITA KUNING KARCIS: KODE TIKET ANGKA TABULAR DENGAN TEKS ARANG --}}
            <div class="bg-[#FFC62E] text-[#16201F] px-4 py-4 text-center border-y border-dashed border-[#C2CECA] relative">
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#16201F]/70">Kode Booking</span>
                <p class="mt-0.5 font-tabular text-3xl font-black tracking-wider text-[#16201F] sm:text-4xl">
                    {{ $booking->code }}
                </p>

                <div class="mt-2 flex justify-center no-print">
                    <button type="button"
                            @click="copyCode()"
                            class="inline-flex min-h-9 items-center gap-1.5 rounded-[var(--radius-control)] border border-[#16201F]/20 bg-white/80 px-3 text-xs font-bold text-[#16201F] hover:bg-white">
                        <span x-text="copied ? 'Kode tersalin' : 'Salin kode'">Salin kode</span>
                    </button>
                </div>
            </div>

            {{-- Rincian Pemesan & Biaya --}}
            <div class="p-4 sm:p-5 text-xs">
                <dl class="space-y-2">
                    <div class="flex justify-between gap-3">
                        <dt class="text-[#16201F]/70">Pemesan</dt>
                        <dd class="font-bold text-[#16201F] truncate">{{ $booking->customer_name }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-[#16201F]/70">Tipe nonton</dt>
                        <dd class="font-bold text-[#16201F]">
                            {{ $booking->booking_type === 'private' ? 'Sewa Private (6 kursi)' : $booking->pax . ' orang' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-[#16201F]/70">Metode bayar</dt>
                        <dd class="font-bold text-[#16201F]">
                            {{ match ($booking->payment_method) {
                                'qris' => 'QRIS',
                                'transfer' => 'Transfer',
                                default => 'Bayar di tempat'
                            } }}
                        </dd>
                    </div>

                    @if ($booking->items->isNotEmpty())
                        <div class="flex justify-between gap-3 border-t border-dashed border-[#C2CECA] pt-2">
                            <dt class="text-[#16201F]/70">Pesanan jajan</dt>
                            <dd class="text-right">
                                <button type="button"
                                        @click="snacksOpen = !snacksOpen"
                                        class="font-bold text-primary hover:underline no-print">
                                    <span x-text="snacksOpen ? 'Tutup rincian ↑' : '{{ $booking->items->count() }} item ↓'">
                                        {{ $booking->items->count() }} item ↓
                                    </span>
                                </button>
                                <span class="font-tabular font-bold text-[#16201F] hidden print:inline">
                                    Rp {{ number_format($booking->snack_total, 0, ',', '.') }}
                                </span>
                            </dd>
                        </div>

                        {{-- Accordion Jajanan --}}
                        <div x-show="snacksOpen" x-cloak class="mt-2 rounded-[var(--radius-control)] bg-white/70 p-2.5 text-xs space-y-1 no-print">
                            @foreach ($booking->items as $snack)
                                <div class="flex items-center justify-between gap-2 text-[#16201F]/80">
                                    <span>{{ $snack->name_snapshot }} × {{ $snack->qty }} {{ $snack->note ? '('.$snack->note.')' : '' }}</span>
                                    <span class="font-tabular text-[#16201F]">Rp {{ number_format($snack->subtotal, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex justify-between gap-3 border-t border-[#C2CECA] pt-2 text-sm font-extrabold">
                        <dt class="text-[#16201F]">Total pembayaran</dt>
                        <dd class="font-tabular text-base text-[#16201F]">
                            Rp {{ number_format($booking->total_price, 0, ',', '.') }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Perforasi Sobekan Karcis di Tengah (Center Cutouts) --}}
            <div aria-hidden="true" class="absolute -left-3 top-1/2 size-6 -translate-y-1/2 rounded-full border border-border bg-bg"></div>
            <div aria-hidden="true" class="absolute -right-3 top-1/2 size-6 -translate-y-1/2 rounded-full border border-border bg-bg"></div>
        </article>

        {{-- Petunjuk Kedatangan Singkat --}}
        <div class="mt-4 rounded-[var(--radius-control)] border border-border bg-surface p-4 text-xs text-text-muted no-print">
            <h3 class="font-bold text-text">Petunjuk kedatangan</h3>
            <ul class="mt-1.5 space-y-1 list-disc pl-4">
                <li>Harap datang paling lambat 10 menit sebelum jam sesi dimulai.</li>
                <li>Tunjukkan kode booking kepada petugas di lokasi studio.</li>
                @if ($booking->payment_method === 'di_tempat')
                    <li>Selesaikan pembayaran tunai sebelum memasuki studio.</li>
                @else
                    <li>Simpan tiket ini atau tangkap layar (screenshot) sebagai bukti kedatangan.</li>
                @endif
            </ul>
        </div>

        {{-- Aksi Tiket: Simpan tiket, Salin kode, Hubungi admin, Booking lagi --}}
        <div class="mt-5 space-y-2.5 no-print">
            {{-- Tombol Utama: Simpan Tiket --}}
            <button type="button"
                    @click="printTicket()"
                    class="inline-flex min-h-11 w-full items-center justify-center rounded-[var(--radius-control)] bg-primary px-5 text-xs font-bold text-white shadow-xs transition hover:opacity-95">
                Simpan tiket
            </button>

            <div class="flex flex-wrap items-center justify-center gap-2 pt-1">
                @if ($whatsAppUrl)
                    <a href="{{ $whatsAppUrl }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       aria-label="Konfirmasi via WhatsApp"
                       class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] border border-border-input bg-surface px-4 text-xs font-bold text-text hover:border-primary">
                        Hubungi admin
                    </a>
                @endif

                @if ($snackWhatsAppUrl && $booking->items->isNotEmpty())
                    <a href="{{ $snackWhatsAppUrl }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] border border-border-input bg-surface px-4 text-xs font-bold text-text hover:border-primary">
                        Ubah jajan via WhatsApp
                    </a>
                @endif

                <a href="{{ route('schedule.date', ['date' => $booking->session->session_date]) }}"
                   class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] border border-border-input bg-surface px-4 text-xs font-bold text-text hover:border-primary">
                    Booking lagi
                </a>
            </div>

            {{-- Opsi Batalkan Booking Jika Memenuhi Syarat --}}
            @if ($canCancel)
                <div class="pt-2 text-center">
                    <button type="button"
                            @click="cancelModal = true"
                            class="text-xs text-text-muted hover:text-danger-text hover:underline">
                        Batalkan booking ini
                    </button>
                </div>
            @endif
        </div>

        {{-- Dialog Modal Pembatalan Booking --}}
        @if ($canCancel)
            <div x-show="cancelModal" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-overlay p-4"
                 @click.self="cancelModal = false">
                <div class="w-full max-w-sm rounded-[var(--radius-control)] border border-border bg-surface p-5 shadow-token">
                    <h3 class="text-base font-bold text-text">Batalkan booking ini?</h3>
                    <p class="mt-2 text-xs leading-relaxed text-text-muted">
                        Kursi yang sudah Anda pesan akan dilepaskan kembali agar dapat dipesan penonton lain.
                    </p>
                    <form method="POST" action="{{ route('booking.cancel', ['code' => $booking->code]) }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="whatsapp" value="{{ $booking->whatsapp }}">
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="cancelModal = false"
                                    class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] border border-border-input px-3 text-xs font-bold text-text">
                                Jangan batalkan
                            </button>
                            <button type="submit"
                                    class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] bg-danger px-3 text-xs font-bold text-white hover:opacity-95">
                                Ya, batalkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
