@extends('layouts.public')

@section('title', 'Tiket '.$booking->code.' · SynemaX')
@section('meta')
    <meta name="description" content="Tiket digital SynemaX untuk booking {{ $booking->code }}.">
    <meta property="og:title" content="Tiket SynemaX · {{ $booking->code }}">
    <meta property="og:description" content="{{ $booking->film?->title ?? 'Home Cinema' }} · {{ $booking->session->startsAt()->locale('id')->translatedFormat('D, d M Y') }} · {{ substr((string) $booking->session->start_time, 0, 5) }} WIB">
@endsection

@push('styles')
<style>
    @media print {
        header, footer, nav, .no-print, button, a[href*="wa.me"] {
            display: none !important;
        }
        body {
            background: white !important;
            color: black !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        main {
            padding: 0 !important;
            margin: 0 !important;
        }
        .print-ticket {
            border: 2px solid #000 !important;
            box-shadow: none !important;
            background: #fff !important;
            color: #000 !important;
            max-width: 100% !important;
            margin: 0 auto !important;
            page-break-inside: avoid;
        }
        .print-ticket * {
            color: #000 !important;
            border-color: #ccc !important;
        }
    }
</style>
@endpush

@section('content')
    <section class="mx-auto max-w-xl px-4 py-5 sm:px-6 sm:py-8"
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

        {{-- Indikator Tahap di Atas --}}
        <nav aria-label="Tahapan Pesanan" class="mb-4 no-print">
            @if ($booking->payment_method === 'di_tempat')
                {{-- Alur Bayar di tempat: 2 tahap --}}
                <ol class="grid grid-cols-2 gap-2 text-center text-xs">
                    <li class="flex items-center justify-center gap-1.5 rounded-xl border border-border bg-surface-2 p-2 font-medium text-text-muted">
                        <svg class="size-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>1. Booking</span>
                    </li>
                    <li class="flex items-center justify-center gap-1.5 rounded-xl border border-success/40 bg-success/10 p-2 font-bold text-success ring-1 ring-success/50">
                        <span class="grid size-4 place-items-center rounded-full bg-success text-[10px] text-white">2</span>
                        <span>2. Selesai</span>
                    </li>
                </ol>
            @else
                {{-- Alur QRIS / Transfer: 3 tahap --}}
                <ol class="grid grid-cols-3 gap-2 text-center text-xs">
                    <li class="flex items-center justify-center gap-1.5 rounded-xl border border-border bg-surface-2 p-2 font-medium text-text-muted">
                        <svg class="size-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>1. Booking</span>
                    </li>
                    <li class="flex items-center justify-center gap-1.5 rounded-xl border border-border bg-surface-2 p-2 font-medium text-text-muted">
                        <svg class="size-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>2. Bayar</span>
                    </li>
                    <li class="flex items-center justify-center gap-1.5 rounded-xl border border-success/40 bg-success/10 p-2 font-bold text-success ring-1 ring-success/50">
                        <span class="grid size-4 place-items-center rounded-full bg-success text-[10px] text-white">3</span>
                        <span>3. Selesai</span>
                    </li>
                </ol>
            @endif
        </nav>

        {{-- Flash Session Message --}}
        @if (session('status'))
            <div role="status" class="mb-4 rounded-2xl border border-success/30 bg-success/10 p-3.5 text-xs font-semibold text-success no-print">
                {{ session('status') }}
            </div>
        @endif

        {{-- Banner Status di Atas Tiket --}}
        <div class="mb-4 no-print">
            @if ($booking->status === 'menunggu_konfirmasi')
                <div class="rounded-2xl border border-accent/30 bg-accent/10 p-3.5 text-xs text-text">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-start gap-2.5">
                            <div class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full bg-accent text-[11px] font-bold text-accent-contrast">
                                i
                            </div>
                            <div>
                                <p class="font-bold text-accent">Menunggu Konfirmasi Admin</p>
                                <p class="mt-0.5 text-text-muted leading-relaxed">
                                    @if ($booking->payment_method === 'di_tempat')
                                        Booking diterima. Bayar tunai di lokasi sebelum film mulai. Admin akan mengonfirmasi lewat WhatsApp.
                                    @else
                                        Bukti bayar diterima. Admin akan memeriksa dan mengonfirmasi tiketmu.
                                    @endif
                                </p>
                            </div>
                        </div>
                        <button type="button"
                                @click="checkStatus(true)"
                                :disabled="polling"
                                class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-border bg-surface px-2.5 py-1 text-[11px] font-bold text-text hover:border-accent disabled:opacity-50"
                                title="Periksa status terbaru">
                            <svg class="size-3 transition-transform" :class="{ 'animate-spin': polling }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span x-text="polling ? 'Memeriksa...' : 'Perbarui'">Perbarui</span>
                        </button>
                    </div>
                </div>
            @elseif ($booking->status === 'terkonfirmasi')
                <div class="rounded-2xl border border-success/30 bg-success/10 p-3.5 text-xs text-success">
                    <div class="flex items-start gap-2.5">
                        <svg class="size-5 shrink-0 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <div>
                            <p class="font-bold">Tiket Terkonfirmasi</p>
                            <p class="mt-0.5 leading-relaxed text-success/90">Tiket terkonfirmasi. Tunjukkan kode ini saat datang ke studio.</p>
                        </div>
                    </div>
                </div>
            @elseif ($booking->status === 'kedaluwarsa')
                <div class="rounded-2xl border border-danger/30 bg-danger/10 p-3.5 text-xs text-danger">
                    <p class="font-bold">Booking Kedaluwarsa</p>
                    <p class="mt-0.5 leading-relaxed text-danger/90">Batas waktu pembayaran telah habis dan kursi telah dilepaskan kembali.</p>
                    <div class="mt-2.5">
                        <a href="{{ route('schedule.date', ['date' => $booking->session->session_date]) }}" class="inline-flex min-h-9 items-center justify-center rounded-xl bg-danger px-3 text-xs font-bold text-danger-contrast hover:opacity-90">
                            Booking lagi
                        </a>
                    </div>
                </div>
            @elseif ($booking->status === 'dibatalkan')
                <div class="rounded-2xl border border-danger/30 bg-danger/10 p-3.5 text-xs text-danger">
                    <p class="font-bold">Booking Dibatalkan</p>
                    <p class="mt-0.5 leading-relaxed text-danger/90">Booking ini telah dibatalkan.</p>
                    <div class="mt-2.5">
                        <a href="{{ route('schedule.date', ['date' => $booking->session->session_date]) }}" class="inline-flex min-h-9 items-center justify-center rounded-xl bg-accent px-3 text-xs font-bold text-accent-contrast hover:opacity-90">
                            Booking lagi
                        </a>
                    </div>
                </div>
            @elseif ($booking->status === 'hadir')
                <div class="rounded-2xl border border-success/30 bg-success/10 p-3.5 text-xs text-success">
                    <p class="font-bold">Tiket Sudah Dipakai</p>
                    <p class="mt-0.5 leading-relaxed text-success/90">Terima kasih telah berkunjung dan menonton bersama kami di SynemaX!</p>
                </div>
            @endif

            {{-- Jika bukti pernah ditolak admin --}}
            @if ($booking->proof_rejection_note && $booking->status === 'menunggu_pembayaran')
                <div class="mt-2.5 rounded-2xl border border-danger/40 bg-danger/10 p-3.5 text-xs text-danger">
                    <p class="font-bold">Bukti transfer sebelumnya ditolak admin:</p>
                    <p class="mt-0.5 font-semibold underline">{{ $booking->proof_rejection_note }}</p>
                    <div class="mt-2.5">
                        <a href="{{ route('booking.payment', ['code' => $booking->code]) }}" class="inline-flex min-h-9 items-center justify-center rounded-xl bg-danger px-3 text-xs font-bold text-danger-contrast hover:opacity-90">
                            Unggah ulang bukti bayar
                        </a>
                    </div>
                </div>
            @endif
        </div>

        {{-- Kartu Tiket Digital (Pahlawan Utama) --}}
        <article class="print-ticket relative overflow-hidden rounded-3xl border border-accent/40 bg-surface shadow-token transition {{ in_array($booking->status, ['dibatalkan', 'kedaluwarsa']) ? 'opacity-60 grayscale-[40%]' : '' }}">
            <div class="h-1.5 w-full bg-accent"></div>

            {{-- Header Tiket: Brand + Chip Status --}}
            <header class="border-b border-dashed border-border p-4 sm:p-6">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-[0.18em] text-accent">SynemaX · Home Cinema</span>
                        <p class="text-xs text-text-muted">Tiket Masuk Studio</p>
                    </div>
                    <div>
                        @php
                            $statusStyle = match ($booking->status) {
                                'terkonfirmasi' => 'bg-success/15 text-success border-success/30',
                                'menunggu_konfirmasi' => 'bg-accent/15 text-accent border-accent/30',
                                'menunggu_pembayaran' => 'bg-warning/15 text-warning border-warning/30',
                                'kedaluwarsa', 'dibatalkan' => 'bg-danger/15 text-danger border-danger/30',
                                'hadir' => 'bg-success/15 text-success border-success/30',
                                default => 'bg-surface-2 text-text-muted border-border',
                            };
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
                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-bold {{ $statusStyle }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                </div>

                {{-- Baris Film & Jadwal --}}
                <div class="mt-4 flex items-start gap-3.5">
                    @if ($booking->film?->poster_path)
                        <img src="{{ asset('storage/'.$booking->film->poster_path) }}"
                             alt="{{ $booking->film->title }}"
                             class="h-16 w-12 shrink-0 rounded-lg object-cover shadow-xs">
                    @endif
                    <div class="flex-1 min-w-0">
                        <h1 class="text-lg font-black text-text leading-tight sm:text-xl truncate">{{ $booking->film?->title ?? 'Film Sesi' }}</h1>
                        <p class="mt-1 text-xs text-text-muted">
                            {{ $booking->session->startsAt()->locale('id')->translatedFormat('l, d F Y') }}
                            · <strong class="text-text font-bold">{{ substr((string) $booking->session->start_time, 0, 5) }} WIB</strong>
                        </p>
                        <p class="mt-0.5 text-xs text-text-muted">
                            {{ $booking->film?->age_rating ?? 'SU' }} · {{ $booking->film?->duration_minutes ?? 90 }} menit
                        </p>
                    </div>
                </div>
            </header>

            {{-- Bagian Kode Booking (Paling Menonjol) --}}
            <div class="bg-surface-2/60 p-4 text-center sm:p-5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-text-muted">Kode Booking</span>
                <p class="mt-1 font-mono text-3xl font-black tracking-wider text-accent sm:text-4xl">{{ $booking->code }}</p>
                <div class="mt-2.5 flex justify-center no-print">
                    <button type="button"
                            @click="copyCode()"
                            class="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-border bg-surface px-3 text-xs font-bold text-text hover:border-accent">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                        </svg>
                        <span x-text="copied ? 'Kode tersalin' : 'Salin kode'">Salin kode</span>
                    </button>
                </div>
            </div>

            {{-- Detail Pemesan & Biaya --}}
            <div class="p-4 sm:p-6 text-xs sm:text-sm">
                <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2.5">
                    <dt class="text-text-muted">Pemesan</dt>
                    <dd class="font-bold text-text text-right truncate">{{ $booking->customer_name }}</dd>

                    <dt class="text-text-muted">Jumlah</dt>
                    <dd class="font-bold text-text text-right">
                        {{ $booking->booking_type === 'private' ? 'Sewa Private (6 kursi)' : $booking->pax.' orang' }}
                    </dd>

                    <dt class="text-text-muted">Metode bayar</dt>
                    <dd class="font-bold text-text text-right uppercase">
                        {{ match ($booking->payment_method) {
                            'qris' => 'QRIS',
                            'transfer' => 'Transfer',
                            default => 'Bayar di tempat'
                        } }}
                    </dd>

                    @if ($booking->items->isNotEmpty())
                        <dt class="text-text-muted">Jajan</dt>
                        <dd class="text-right">
                            <button type="button"
                                    @click="snacksOpen = !snacksOpen"
                                    class="font-semibold text-accent hover:underline no-print">
                                <span x-text="snacksOpen ? 'Tutup rincian jajan ↑' : 'Pesanan jajan ({{ $booking->items->count() }} item) ↓'">
                                    Pesanan jajan ({{ $booking->items->count() }} item) ↓
                                </span>
                            </button>
                            <span class="font-bold text-text hidden print:inline">Rp {{ number_format($booking->snack_total, 0, ',', '.') }}</span>
                        </dd>
                    @endif

                    <dt class="border-t border-border pt-2.5 font-bold text-text">Total bayar</dt>
                    <dd class="border-t border-border pt-2.5 text-right font-black text-accent text-base sm:text-lg">
                        Rp {{ number_format($booking->total_price, 0, ',', '.') }}
                    </dd>
                </dl>

                {{-- Accordion Jajanan --}}
                @if ($booking->items->isNotEmpty())
                    <div x-show="snacksOpen" x-cloak class="mt-3 rounded-xl border border-border bg-surface-2 p-3 text-xs space-y-1.5 no-print">
                        <p class="font-bold text-text uppercase tracking-wider text-[10px]">Rincian Jajanan:</p>
                        @foreach ($booking->items as $snack)
                            <div class="flex items-center justify-between gap-2 text-text-muted">
                                <span>{{ $snack->name_snapshot }} × {{ $snack->qty }} {{ $snack->note ? '('.$snack->note.')' : '' }}</span>
                                <span class="font-mono text-text">Rp {{ number_format($snack->subtotal, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Perforasi Tiket Lingkaran Samping --}}
            <div aria-hidden="true" class="absolute -left-3 top-1/2 size-6 -translate-y-1/2 rounded-full border border-border bg-bg"></div>
            <div aria-hidden="true" class="absolute -right-3 top-1/2 size-6 -translate-y-1/2 rounded-full border border-border bg-bg"></div>
        </article>

        {{-- Blok Langkah Selanjutnya --}}
        <div class="mt-4 rounded-2xl border border-border bg-surface-2/70 p-4 text-xs no-print">
            <h2 class="font-bold uppercase tracking-wider text-text">Langkah Selanjutnya</h2>
            <ul class="mt-2 space-y-1.5 pl-4 list-disc text-text-muted">
                <li>Datang paling lambat 10 menit sebelum jadwal film dimulai.</li>
                <li>Tunjukkan kode booking di atas kepada petugas kasir atau studio.</li>
                @if ($booking->payment_method === 'di_tempat')
                    <li>Selesaikan pembayaran tunai langsung di kasir sebelum masuk studio.</li>
                @else
                    <li>Simpan tiket ini atau ambil tangkapan layar (screenshot) sebagai tanda bukti.</li>
                @endif
            </ul>
        </div>

        {{-- Tombol Aksi Halaman Tiket --}}
        <div class="mt-5 space-y-3 no-print">
            {{-- Tombol Utama: Simpan Tiket (window.print) --}}
            <button type="button"
                    @click="printTicket()"
                    class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-accent px-5 text-sm font-bold text-accent-contrast shadow-sm transition hover:opacity-90">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zM7 9V5a2 2 0 012-2h6a2 2 0 012 2v4" />
                </svg>
                <span>Simpan tiket</span>
            </button>
            <p class="text-center text-[11px] text-text-muted">Gunakan tombol di atas untuk cetak atau simpan PDF, atau tangkap layar (screenshot) ponsel Anda.</p>

            <div class="flex flex-wrap items-center justify-center gap-2 pt-1">
                @if ($whatsAppUrl)
                    <a href="{{ $whatsAppUrl }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       aria-label="Konfirmasi via WhatsApp"
                       class="inline-flex min-h-10 items-center gap-1.5 rounded-xl border border-border bg-surface px-3.5 text-xs font-semibold text-text hover:bg-surface-2">
                        <svg class="size-3.5 text-success" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2zm.01 1.67c2.2 0 4.26.86 5.82 2.42a8.17 8.17 0 012.41 5.82c0 4.54-3.7 8.24-8.24 8.24-1.45 0-2.88-.38-4.13-1.12l-.3-.18-3.08.81.82-3-.19-.31a8.16 8.16 0 01-1.25-4.44c0-4.54 3.7-8.24 8.24-8.24zm4.52 11.66c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.98-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.25-1.5-1.4-1.75-.15-.25-.02-.39.11-.51.11-.11.25-.29.38-.44.12-.14.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.35-.77-1.85-.2-.49-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1s.9 2.43 1.03 2.6c.13.17 1.77 2.7 4.28 3.79.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.15-1.18-.06-.1-.23-.17-.48-.29z"/>
                        </svg>
                        <span>Hubungi admin</span>
                    </a>
                @endif

                @if ($snackWhatsAppUrl && $booking->items->isNotEmpty())
                    <a href="{{ $snackWhatsAppUrl }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex min-h-10 items-center gap-1.5 rounded-xl border border-border bg-surface px-3.5 text-xs font-semibold text-text hover:bg-surface-2">
                        <span>Ubah pesanan jajan via WhatsApp</span>
                    </a>
                @endif

                <a href="{{ route('schedule.date', ['date' => $booking->session->session_date]) }}"
                   class="inline-flex min-h-10 items-center gap-1.5 rounded-xl border border-border bg-surface px-3.5 text-xs font-semibold text-text hover:bg-surface-2">
                    <span>Booking lagi</span>
                </a>
            </div>

            {{-- Tautan Batalkan Booking (Jika memenuhi batas waktu) --}}
            @if ($canCancel)
                <div class="pt-2 text-center">
                    <button type="button"
                            @click="cancelModal = true"
                            class="text-xs text-text-muted hover:text-danger hover:underline">
                        Batalkan booking ini
                    </button>
                </div>
            @endif
        </div>

        {{-- Dialog Modal Konfirmasi Pembatalan --}}
        @if ($canCancel)
            <div x-show="cancelModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" @click.self="cancelModal = false">
                <div class="w-full max-w-sm rounded-2xl border border-border bg-surface p-5 shadow-token">
                    <h3 class="text-base font-bold text-text">Batalkan Booking?</h3>
                    <p class="mt-2 text-xs leading-relaxed text-text-muted">Apakah Anda yakin ingin membatalkan booking ini? Kursi yang telah dipesan akan dilepaskan kembali.</p>
                    <form method="POST" action="{{ route('booking.cancel', ['code' => $booking->code]) }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="whatsapp" value="{{ $booking->whatsapp }}">
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="cancelModal = false" class="min-h-10 rounded-xl border border-border px-3 text-xs font-semibold text-text">
                                Jangan batalkan
                            </button>
                            <button type="submit" class="min-h-10 rounded-xl bg-danger px-3 text-xs font-bold text-danger-contrast hover:opacity-90">
                                Ya, batalkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </section>
@endsection
