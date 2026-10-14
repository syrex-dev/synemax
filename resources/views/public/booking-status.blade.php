@extends('layouts.public')

@section('title', 'Cek status booking')
@section('meta')
    <meta name="description" content="Lihat status tiket dan pesanan jajan Home Cinema dengan kode booking dan nomor WhatsApp.">
@endsection

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-7 sm:px-6 sm:py-10">
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">Tanpa akun</p>
        <h1 class="mt-2 text-3xl font-black text-text sm:text-4xl">Cek booking</h1>
        <p class="mt-2 text-base text-text-muted">Masukkan kode booking dan nomor WhatsApp yang dipakai saat memesan.</p>

        @if ($errors->any())
            <div role="alert" class="mt-5 rounded-xl border border-danger/30 bg-danger/10 p-4 text-base text-danger">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        @unless (isset($booking))
            <form method="POST" action="{{ route('booking.lookup') }}" class="mt-6 space-y-4 rounded-2xl border border-border bg-surface p-5 shadow-token sm:p-6">
                @csrf
                <div>
                    <label for="code" class="text-base font-semibold text-text">Kode booking</label>
                    <input id="code" name="code" value="{{ old('code', request('code')) }}" required maxlength="20" autocomplete="off" class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 font-mono text-base uppercase text-text focus:border-accent focus:ring-accent" placeholder="HC-260101-A7K2">
                </div>
                <div>
                    <label for="whatsapp" class="text-base font-semibold text-text">Nomor WhatsApp</label>
                    <input id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" required type="tel" inputmode="numeric" autocomplete="tel" class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-base text-text focus:border-accent focus:ring-accent" placeholder="081234567890">
                </div>
                <button class="inline-flex min-h-14 w-full items-center justify-center rounded-xl bg-accent px-5 text-base font-bold text-accent-contrast hover:opacity-90">Lihat status</button>
            </form>
        @else
            @php
                $statusInfo = match ($booking->status) {
                    'menunggu_konfirmasi' => ['⏳', 'Menunggu konfirmasi', 'Admin akan memeriksa booking-mu.'],
                    'terkonfirmasi' => ['✓', 'Terkonfirmasi', 'Booking sudah dikonfirmasi admin.'],
                    'hadir' => ['🎟', 'Hadir / Selesai', 'Terima kasih sudah nonton bareng kami!'],
                    'tidak_hadir' => ['!', 'Tidak hadir', 'Hubungi admin kalau ada yang perlu diklarifikasi.'],
                    default => ['×', 'Dibatalkan', 'Booking dan pesanan jajannya sudah dibatalkan.'],
                };
                $timeline = [
                    ['menunggu_konfirmasi', 'Menunggu konfirmasi'],
                    ['terkonfirmasi', 'Terkonfirmasi'],
                    $booking->status === 'tidak_hadir'
                        ? ['tidak_hadir', 'Tidak hadir']
                        : ['hadir', 'Hadir / Selesai'],
                ];
            @endphp
            <article class="mt-6 overflow-hidden rounded-3xl border border-border bg-surface shadow-token">
                <header class="border-b border-dashed border-border p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm text-text-muted">Kode booking</p>
                            <p class="mt-1 font-mono text-2xl font-black tracking-wide text-accent">{{ $booking->code }}</p>
                        </div>
                        <span class="inline-flex min-h-10 items-center gap-2 rounded-full bg-surface-2 px-3 text-sm font-bold text-text"><span aria-hidden="true">{{ $statusInfo[0] }}</span>{{ $statusInfo[1] }}</span>
                    </div>
                    <p class="mt-4 text-base text-text-muted">{{ $statusInfo[2] }}</p>
                </header>

                @if ($booking->status === 'dibatalkan')
                    <div class="p-5 sm:p-6">
                        <p role="status" class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-base font-semibold text-danger">× Booking dibatalkan. Semua jajanan di booking ini ikut dibatalkan.</p>
                    </div>
                @else
                    @php($currentIndex = collect($timeline)->search(fn ($state) => $state[0] === $booking->status))
                    <ol aria-label="Perjalanan status booking" class="grid grid-cols-3 gap-2 px-5 pt-5 sm:px-6">
                        @foreach ($timeline as $index => [$status, $label])
                            @php($isComplete = $currentIndex !== false && $index <= $currentIndex)
                            <li class="rounded-xl border border-border bg-bg p-3 text-center text-sm {{ $isComplete ? 'border-success' : '' }}" @if ($status === $booking->status) aria-current="step" @endif>
                                <span class="mx-auto grid size-8 place-items-center rounded-full {{ $isComplete ? 'bg-success/15 text-success' : 'bg-surface-2 text-text-muted' }}" aria-hidden="true">{{ $isComplete ? '✓' : $index + 1 }}</span>
                                <span class="mt-2 block font-semibold text-text">{{ $label }}</span>
                            </li>
                        @endforeach
                    </ol>
                    <p class="px-5 pt-3 text-sm text-text-muted sm:px-6">Status sekarang: <strong class="text-text">{{ $statusInfo[1] }}</strong></p>
                @endif

                <div class="p-5 sm:p-6">
                    <h2 class="text-lg font-bold text-text">Rincian booking</h2>
                    <dl class="mt-4 grid grid-cols-[auto_1fr] gap-x-4 gap-y-3 text-base">
                        <dt class="text-text-muted">Pemesan</dt><dd class="font-semibold text-text">{{ $booking->customer_name }}</dd>
                        <dt class="text-text-muted">Film</dt><dd class="text-text">{{ $booking->film?->title ?? 'Home Cinema' }}</dd>
                        <dt class="text-text-muted">Jadwal</dt><dd class="text-text">{{ $booking->session->startsAt()->locale('id')->translatedFormat('D, d M Y') }} · {{ substr((string) $booking->session->start_time, 0, 5) }} WIB</dd>
                        <dt class="text-text-muted">Jumlah</dt><dd class="text-text">{{ $booking->booking_type === 'private' ? 'Sewa Private (6 kursi)' : $booking->pax.' orang' }}</dd>
                        <dt class="text-text-muted">Tiket</dt><dd class="text-text">Rp {{ number_format($booking->ticket_total, 0, ',', '.') }}</dd>
                        <dt class="text-text-muted">Jajan</dt>
                        <dd class="text-text">
                            @if ($booking->items->isNotEmpty())
                                <ul class="space-y-3">
                                    @foreach ($booking->items as $item)
                                        @php($itemStatus = ['baru' => 'Baru', 'disiapkan' => 'Disiapkan', 'siap' => 'Siap', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'][$item->status] ?? $item->status)
                                        <li class="rounded-xl border border-border bg-bg p-3">
                                            <p class="font-semibold">{{ $item->name_snapshot }} × {{ $item->qty }} · Rp {{ number_format($item->price_snapshot * $item->qty, 0, ',', '.') }}</p>
                                            <p class="mt-1 inline-flex items-center gap-2 text-sm text-text-muted"><span aria-hidden="true">{{ $item->status === 'selesai' ? '✓' : ($item->status === 'dibatalkan' ? '×' : '◷') }}</span>Status jajan: {{ $itemStatus }}</p>
                                            @if ($item->note)<p class="mt-1 text-sm text-text-muted">Catatan: {{ $item->note }}</p>@endif
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="text-text-muted">Tidak pesan jajan</span>
                            @endif
                        </dd>
                        <dt class="font-bold text-text">Total bayar</dt><dd class="font-black text-accent">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</dd>
                    </dl>

                    <div role="note" class="mt-5 rounded-xl border border-warning/30 bg-warning/10 p-4 text-base leading-6 text-text">
                        <p class="font-bold text-warning">Pesanan jajan tidak bisa diubah sendiri.</p>
                        <p class="mt-1">Hubungi admin lewat WhatsApp kalau mau menambah atau mengurangi.</p>
                    </div>
                    @if (($snackWhatsAppUrl ?? null) && $booking->status !== 'dibatalkan')
                        <a href="{{ $snackWhatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex min-h-14 w-full items-center justify-center rounded-xl bg-success px-4 text-center text-base font-bold text-success-contrast hover:opacity-90">Ubah / Tambah Pesanan via WhatsApp</a>
                    @endif

                    @if ($booking->status !== 'dibatalkan' && ($whatsAppUrl ?? null))
                        <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex min-h-12 w-full items-center justify-center rounded-xl border border-border px-4 text-base font-semibold text-text hover:bg-surface-2">Kirim rincian booking via WhatsApp</a>
                    @endif

                    @if (in_array($booking->status, ['menunggu_konfirmasi', 'terkonfirmasi'], true))
                        <div x-data>
                            <button type="button" @click="$refs.cancelDialog.showModal()" class="mt-6 min-h-12 w-full rounded-xl border border-danger/40 px-4 font-bold text-danger">Batalkan booking</button>
                            <dialog x-ref="cancelDialog" @click.self="$refs.cancelDialog.close()" class="m-auto max-h-[90dvh] w-[calc(100%-1.5rem)] max-w-md overflow-y-auto rounded-3xl border border-border bg-surface p-0 text-text shadow-token backdrop:bg-overlay">
                                <section aria-labelledby="cancel-title" class="p-5">
                                    <h2 id="cancel-title" class="text-xl font-bold text-text">Yakin mau membatalkan?</h2>
                                    <p class="mt-2 text-base text-text-muted">Tiket dan semua jajan di booking ini ikut dibatalkan.</p>
                                    <form method="POST" action="{{ route('booking.cancel', ['code' => $booking->code]) }}" class="mt-5">
                                        @csrf
                                        <label for="cancel_whatsapp" class="text-base font-semibold text-text">Nomor WhatsApp untuk verifikasi</label>
                                        <input id="cancel_whatsapp" name="whatsapp" value="{{ $booking->whatsapp }}" required type="tel" inputmode="numeric" autocomplete="tel" class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-base text-text focus:border-accent focus:ring-accent">
                                        <p class="mt-2 text-sm text-text-muted">Pembatalan mandiri ditutup {{ app(\App\Services\CinemaSettings::class)->integer('cancellation_cutoff_hours', 3) }} jam sebelum sesi.</p>
                                        <div class="mt-5 grid grid-cols-2 gap-3">
                                            <button type="button" @click="$refs.cancelDialog.close()" class="min-h-12 rounded-xl border border-border px-3 font-bold text-text">Jangan dulu</button>
                                            <button class="min-h-12 rounded-xl bg-danger px-3 font-bold text-danger-contrast">Ya, batalkan</button>
                                        </div>
                                    </form>
                                </section>
                            </dialog>
                        </div>
                    @endif
                </div>
            </article>
        @endunless
    </section>
@endsection
