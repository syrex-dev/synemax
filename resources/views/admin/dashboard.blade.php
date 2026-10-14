@extends('layouts.admin')

@section('title', 'Ringkasan')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-accent">Panel admin</p>
            <h1 class="mt-2 text-3xl font-black">Ringkasan hari ini</h1>
            <p class="mt-1 text-sm text-text-muted">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</p>
        </div>
        <a href="{{ route('admin.schedule.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-accent px-4 text-sm font-bold text-accent-contrast hover:opacity-90">Kelola sesi</a>
    </div>

    @if ($qrisWarning)
        <div role="alert" class="mt-6 flex items-start gap-3 rounded-2xl border border-warning/40 bg-warning/10 p-4 text-warning">
            <svg class="mt-0.5 size-5 shrink-0 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="flex-1 text-sm leading-relaxed text-text">
                <p class="font-bold text-warning">Metode bayar QRIS dinonaktifkan sementara</p>
                <p class="mt-0.5 text-xs text-text-muted">Metode pembayaran QRIS dipilih aktif di pengaturan, namun gambar kode QRIS belum diunggah atau filenya hilang. Pelanggan saat ini tidak melihat opsi QRIS.</p>
                <a href="{{ route('admin.settings.edit') }}#qris-section" class="mt-2 inline-flex items-center gap-1 font-bold text-accent hover:underline">Unggah gambar QRIS di Pengaturan →</a>
            </div>
        </div>
    @endif

    @if ($transferWarning)
        <div role="alert" class="mt-4 flex items-start gap-3 rounded-2xl border border-warning/40 bg-warning/10 p-4 text-warning">
            <svg class="mt-0.5 size-5 shrink-0 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="flex-1 text-sm leading-relaxed text-text">
                <p class="font-bold text-warning">Metode Transfer belum memiliki rekening aktif</p>
                <p class="mt-0.5 text-xs text-text-muted">Metode pembayaran transfer bank/e-wallet dipilih aktif di pengaturan, namun belum ada rekening yang ditambahkan. Pelanggan saat ini tidak melihat opsi Transfer.</p>
                <a href="{{ route('admin.settings.edit') }}#transfer-section" class="mt-2 inline-flex items-center gap-1 font-bold text-accent hover:underline">Tambah rekening di Pengaturan →</a>
            </div>
        </div>
    @endif

    <div class="mt-7 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <article class="rounded-2xl border border-border bg-surface p-5">
            <p class="text-sm text-text-muted">Sesi hari ini</p>
            <p class="mt-2 text-3xl font-black text-accent">{{ $todaySessions->count() }}</p>
        </article>
        <article class="rounded-2xl border border-border bg-surface p-5">
            <p class="text-sm text-text-muted">Penonton terbooking</p>
            <p class="mt-2 text-3xl font-black text-accent">{{ $todayViewers }}</p>
        </article>
        <article class="rounded-2xl border border-border bg-surface p-5">
            <p class="text-sm text-text-muted">Menunggu konfirmasi</p>
            <p class="mt-2 text-3xl font-black text-warning">{{ $waitingBookings }}</p>
            <a href="{{ route('admin.bookings.index', ['status' => 'menunggu_konfirmasi']) }}" class="mt-2 inline-block text-xs text-accent hover:underline">Lihat booking →</a>
        </article>
        <article class="rounded-2xl border border-border bg-surface p-5">
            <p class="text-sm text-text-muted">Menunggu pembayaran</p>
            <p class="mt-2 text-3xl font-black text-accent">{{ $awaitingPaymentCount }}</p>
            <a href="{{ route('admin.bookings.index', ['status' => 'menunggu_pembayaran']) }}" class="mt-2 inline-block text-xs text-accent hover:underline">Lihat antrean →</a>
        </article>
        <article class="rounded-2xl border border-border bg-surface p-5">
            <p class="text-sm text-text-muted">Perlu verifikasi bukti</p>
            <p class="mt-2 text-3xl font-black text-success">{{ $needProofVerificationCount }}</p>
            <a href="{{ route('admin.bookings.index', ['status' => 'menunggu_konfirmasi', 'has_proof' => 1]) }}" class="mt-2 inline-block text-xs text-accent hover:underline">Periksa bukti →</a>
        </article>
    </div>

    <section class="mt-10">
        <h2 class="text-xl font-bold">Sesi hari ini</h2>
        @if ($todaySessions->isEmpty())
            <p class="mt-4 rounded-xl border border-border bg-surface p-4 text-sm text-text-muted">Belum ada sesi. Pastikan jadwal sudah digenerate.</p>
        @else
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ($todaySessions as $session)
                    <article class="rounded-xl border border-border bg-surface p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div><p class="font-bold">{{ substr((string) $session->start_time, 0, 5) }} WIB</p><p class="mt-1 text-sm text-text-muted">{{ $session->film?->title ?? 'Film belum ditentukan' }}</p></div>
                            <span class="rounded-full bg-surface-2 px-3 py-1 text-xs text-text-muted">{{ (int) $session->viewer_count }} kursi terpesan · {{ $session->occupied_bookings_count }} booking</span>
                        </div>
                        <div class="mt-4 border-t border-border pt-3">
                            <div class="flex flex-wrap justify-between gap-2 text-xs">
                                <h3 class="font-semibold">Persiapan jajan</h3>
                                @if ($session->pending_booking_count)
                                    <span class="text-warning">{{ $session->pending_booking_count }} booking menunggu</span>
                                @endif
                            </div>
                            @php($snackBookings = $session->occupiedBookings->filter(fn ($booking) => $booking->items->isNotEmpty()))
                            @if ($snackBookings->isEmpty())
                                <p class="mt-2 text-xs text-text-muted">Tidak ada pesanan jajan.</p>
                            @else
                                <ul class="mt-2 space-y-2">
                                    @foreach ($snackBookings as $booking)
                                        <li class="rounded-lg bg-surface-2 p-3">
                                            <p class="text-xs font-semibold">{{ $booking->customer_name }} · {{ $booking->code }}</p>
                                            <ul class="mt-1 space-y-1 text-xs text-text-muted">
                                                @foreach ($booking->items as $item)
                                                    <li>{{ $item->qty }}× {{ $item->name_snapshot }}@if ($item->note) · Catatan: {{ $item->note }}@endif</li>
                                                @endforeach
                                            </ul>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="mt-10">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-warning">Perlu perhatian</p>
                <h2 class="mt-1 text-xl font-bold">Sesi belum mencapai {{ $minimumViewers }} penonton</h2>
            </div>
            <a href="{{ route('admin.bookings.index') }}" class="text-sm text-accent hover:underline">Hubungi pemesan</a>
        </div>
        @if ($lowAttendanceSessions->isEmpty())
            <p class="mt-4 rounded-xl border border-border bg-surface p-4 text-sm text-text-muted">Belum ada sesi yang perlu ditindaklanjuti.</p>
        @else
            <div class="mt-4 space-y-3">
                @foreach ($lowAttendanceSessions as $session)
                    <article class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-warning bg-surface p-4">
                        <div>
                            <p class="font-semibold">{{ $session->session_date }} · {{ substr((string) $session->start_time, 0, 5) }} WIB</p>
                            <p class="mt-1 text-xs text-text-muted">{{ $session->film?->title ?? 'Film belum dipilih' }} · {{ (int) $session->viewer_count }} dari minimal {{ $minimumViewers }} kursi terpesan</p>
                        </div>
                        <a href="{{ route('admin.bookings.index', ['session_id' => $session->id]) }}" class="rounded-lg border border-border px-3 py-2 text-xs font-semibold text-accent hover:bg-surface-2">Lihat booking</a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
