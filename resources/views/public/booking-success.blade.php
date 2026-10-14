@extends('layouts.public')

@section('title', 'Tiket digital '.$booking->code)
@section('meta')
    <meta name="description" content="Tiket digital Home Cinema untuk booking {{ $booking->code }}.">
    <meta property="og:type" content="article">
    <meta property="og:title" content="Tiket digital Home Cinema · {{ $booking->code }}">
    <meta property="og:description" content="{{ $booking->film?->title ?? 'Home Cinema' }} · {{ $booking->session->startsAt()->locale('id')->translatedFormat('D, d M Y') }} · {{ substr((string) $booking->session->start_time, 0, 5) }} WIB">
    <meta property="og:image" content="{{ asset('social-banner.png') }}">
@endsection

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-7 sm:px-6 sm:py-12"
             x-data="{
                 code: @js($booking->code),
                 saved: false,
                 copied: false,
                 storageAvailable: true,
                 sessionStorageAvailable: true,
                 init() {
                     try {
                         const draft = JSON.parse(sessionStorage.getItem('synemax:booking-wizard') || 'null');
                         if (draft && draft.sessionId === @js((string) $booking->session_id)) {
                             sessionStorage.removeItem('synemax:booking-wizard');
                         }
                     } catch {
                         this.sessionStorageAvailable = false;
                     }
                     try {
                         const codes = JSON.parse(localStorage.getItem('synemax:booking-codes') || '[]');
                         if (!Array.isArray(codes)) throw new TypeError('Daftar kode booking tidak valid.');
                         const unique = [...new Set([this.code, ...codes.filter((item) => typeof item === 'string')])].slice(0, 20);
                         localStorage.setItem('synemax:booking-codes', JSON.stringify(unique));
                         this.saved = true;
                     } catch {
                         this.storageAvailable = false;
                     }
                 },
                 removeCode() {
                     try {
                         const codes = JSON.parse(localStorage.getItem('synemax:booking-codes') || '[]');
                         const kept = Array.isArray(codes) ? codes.filter((item) => item !== this.code) : [];
                         localStorage.setItem('synemax:booking-codes', JSON.stringify(kept));
                         this.saved = false;
                     } catch {
                         this.storageAvailable = false;
                     }
                 },
                 copyCode() {
                     if (navigator.clipboard && navigator.clipboard.writeText) {
                         navigator.clipboard.writeText(this.code).then(() => this.copied = true, () => this.copyFallback());
                         return;
                     }
                     this.copyFallback();
                 },
                 copyFallback() {
                     const input = this.$refs.code;
                     input.focus();
                     input.select();
                     this.copied = document.execCommand('copy');
                 }
             }">
        <div class="text-center">
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-success">✓ Booking tersimpan</p>
            <h1 class="mt-2 text-3xl font-black text-text sm:text-4xl">Tiket digitalmu</h1>
            <p class="mt-2 text-base text-text-muted">Screenshot halaman ini atau simpan kodenya. Admin akan mengonfirmasi lewat WhatsApp.</p>
        </div>

        <article class="relative mt-6 overflow-hidden rounded-3xl border border-accent/30 bg-surface shadow-token">
            <div class="absolute inset-x-0 top-0 h-2 bg-accent"></div>
            <header class="border-b border-dashed border-border p-5 pt-7 sm:p-7 sm:pt-9">
                <div class="flex items-center justify-between gap-3">
                    <div><p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">SynemaX · Home Cinema</p><p class="mt-1 text-sm text-text-muted">Tiket masuk · Simpan kode booking</p></div>
                    <span class="grid size-12 place-items-center rounded-xl bg-accent/10 text-2xl text-accent" aria-hidden="true">🎟</span>
                </div>
                <p class="mt-6 text-sm font-semibold text-text-muted">KODE BOOKING</p>
                <input x-ref="code" aria-label="Kode booking" readonly value="{{ $booking->code }}" class="mt-1 min-h-12 w-full border-0 bg-transparent p-0 font-mono text-3xl font-black tracking-wider text-accent focus:ring-0 sm:text-4xl">
                <button type="button" @click="copyCode()" class="mt-2 inline-flex min-h-11 items-center gap-2 rounded-lg border border-border px-3 text-sm font-bold text-text">
                    <span aria-hidden="true">▢</span><span aria-live="polite" x-text="copied ? 'Kode tersalin' : 'Salin kode'">Salin kode</span>
                </button>
            </header>

            <div class="p-5 sm:p-7">
                <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-3 text-base">
                    <dt class="text-text-muted">Pemesan</dt><dd class="font-semibold text-text">{{ $booking->customer_name }}</dd>
                    <dt class="text-text-muted">Film</dt><dd class="font-semibold text-text">{{ $booking->film?->title ?? 'Sesi Home Cinema' }}</dd>
                    <dt class="text-text-muted">Tanggal</dt><dd class="text-text">{{ $booking->session->startsAt()->locale('id')->translatedFormat('l, d F Y') }}</dd>
                    <dt class="text-text-muted">Jam</dt><dd class="text-text">{{ substr((string) $booking->session->start_time, 0, 5) }} WIB</dd>
                    <dt class="text-text-muted">Penonton</dt><dd class="text-text">{{ $booking->booking_type === 'private' ? 'Sewa Private (6 kursi)' : $booking->pax.' orang' }}</dd>
                    <dt class="text-text-muted">Tiket</dt><dd class="text-text">Rp {{ number_format($booking->ticket_total, 0, ',', '.') }}</dd>
                    <dt class="text-text-muted">Jajan</dt>
                    <dd class="text-text">
                        @if ($booking->items->isEmpty())
                            Tidak pesan jajan
                        @else
                            <ul class="space-y-2">
                                @foreach ($booking->items as $item)
                                    <li>{{ $item->name_snapshot }} × {{ $item->qty }} · Rp {{ number_format($item->price_snapshot * $item->qty, 0, ',', '.') }}@if ($item->note)<span class="block text-sm text-text-muted">{{ $item->note }}</span>@endif</li>
                                @endforeach
                            </ul>
                            <p class="mt-2 text-sm font-semibold">Subtotal jajan: Rp {{ number_format($booking->snack_total, 0, ',', '.') }}</p>
                        @endif
                    </dd>
                    <dt class="border-t border-border pt-3 font-bold text-text">Total bayar</dt><dd class="border-t border-border pt-3 text-lg font-black text-accent">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</dd>
                    <dt class="text-text-muted">Status</dt><dd class="font-semibold text-warning">⏳ Menunggu konfirmasi</dd>
                </dl>
            </div>
            <div aria-hidden="true" class="absolute -left-4 top-1/2 size-8 -translate-y-1/2 rounded-full border border-border bg-bg"></div>
            <div aria-hidden="true" class="absolute -right-4 top-1/2 size-8 -translate-y-1/2 rounded-full border border-border bg-bg"></div>
        </article>

        <p x-show="saved" x-cloak role="status" class="mt-4 rounded-xl border border-success/30 bg-success/10 p-3 text-sm text-text">
            Kode ini disimpan di perangkat ini supaya gampang ditemukan lagi. Hanya kode yang tersimpan, bukan data nama atau nomor WhatsApp.
            <button type="button" @click="removeCode()" class="ml-1 font-bold text-accent underline underline-offset-2">Hapus kode tersimpan</button>
        </p>
        <p x-show="!storageAvailable" x-cloak class="mt-4 text-sm text-text-muted">Penyimpanan browser tidak tersedia. Simpan atau screenshot kode booking ini, ya.</p>
        <p x-show="!sessionStorageAvailable" x-cloak class="mt-2 text-sm text-text-muted">Browser tidak mengizinkan penghapusan otomatis progres sementara.</p>

        @if ($whatsAppUrl)
            <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="mt-5 inline-flex min-h-14 w-full items-center justify-center gap-2 rounded-xl bg-success px-5 text-base font-bold text-success-contrast hover:opacity-90">Konfirmasi via WhatsApp</a>
        @else
            <p role="status" class="mt-5 rounded-xl border border-warning/30 bg-warning/10 p-4 text-base text-text">Nomor WhatsApp admin belum disetel. Simpan kode ini dan hubungi admin studio.</p>
        @endif
        <a href="{{ route('booking.lookup.form', ['code' => $booking->code]) }}" class="mt-4 inline-flex min-h-12 w-full items-center justify-center rounded-xl border border-border px-4 text-base font-semibold text-text hover:bg-surface-2">Cek status atau batalkan booking</a>
    </section>
@endsection
