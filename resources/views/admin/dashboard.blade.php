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

    <div class="mt-7 grid gap-3 sm:grid-cols-3">
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
