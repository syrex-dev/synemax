@extends('layouts.admin')

@section('title', 'Ringkasan')

@push('styles')
<style>
    @media print {
        header, aside, nav, .no-print, button, a[href*="admin"] {
            display: none !important;
        }
        body, html, main {
            background-color: #ffffff !important;
            color: #000000 !important;
            padding: 0.5cm !important;
            margin: 0 !important;
        }
        .print-container {
            display: block !important;
        }
        .card-print {
            border: 1px solid #000000 !important;
            background: #ffffff !important;
            color: #000000 !important;
            break-inside: avoid;
            box-shadow: none !important;
        }
    }
</style>
@endpush

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-accent">Panel admin</p>
            <h1 class="mt-2 text-3xl font-black">Ringkasan hari ini</h1>
            <p class="mt-1 text-sm text-text-muted">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5 no-print">
            <button type="button"
                    onclick="window.print()"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-white/20 bg-surface px-4 text-sm font-bold text-text hover:border-accent hover:text-accent transition shadow-sm">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak Laporan Ringkas</span>
            </button>
            <a href="{{ route('admin.schedule.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-accent px-4 text-sm font-bold text-accent-contrast hover:opacity-90 transition shadow-sm">
                Kelola sesi
            </a>
        </div>
    </div>

    @if ($qrisWarning)
        <div role="alert" class="mt-6 flex items-start gap-3 rounded-2xl border border-warning/40 bg-warning/10 p-4 text-warning no-print">
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
        <div role="alert" class="mt-4 flex items-start gap-3 rounded-2xl border border-warning/40 bg-warning/10 p-4 text-warning no-print">
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

    {{-- ==================== STATISTIK UTAMA (METRICS GRID) ==================== --}}
    <div class="mt-7 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <article class="rounded-2xl border border-border bg-surface p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Sesi hari ini</p>
            <p class="mt-2 text-3xl font-black text-accent">{{ $todaySessions->count() }}</p>
            <p class="mt-1 text-[11px] text-text-muted">{{ $todayCapacity }} total kapasitas kursi</p>
        </article>

        <article class="rounded-2xl border border-border bg-surface p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Penonton terbooking</p>
            <p class="mt-2 text-3xl font-black text-accent">{{ $todayViewers }}</p>
            <div class="mt-1 flex items-center gap-1.5">
                <div class="h-1.5 w-16 overflow-hidden rounded-full bg-surface-2">
                    <div class="h-full rounded-full bg-accent" style="width: {{ min(100, $todayOccupancyPercent) }}%"></div>
                </div>
                <span class="text-[11px] font-bold text-accent">{{ $todayOccupancyPercent }}% okupansi</span>
            </div>
        </article>

        <article class="rounded-2xl border border-border bg-surface p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Menunggu konfirmasi</p>
            <p class="mt-2 text-3xl font-black text-warning">{{ $waitingBookings }}</p>
            <a href="{{ route('admin.bookings.index', ['status' => 'menunggu_konfirmasi']) }}" class="mt-2 inline-block text-xs font-bold text-accent hover:underline">Lihat booking →</a>
        </article>

        <article class="rounded-2xl border border-border bg-surface p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Menunggu pembayaran</p>
            <p class="mt-2 text-3xl font-black text-accent">{{ $awaitingPaymentCount }}</p>
            <a href="{{ route('admin.bookings.index', ['status' => 'menunggu_pembayaran']) }}" class="mt-2 inline-block text-xs font-bold text-accent hover:underline">Lihat antrean →</a>
        </article>

        <article class="rounded-2xl border border-border bg-surface p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Perlu verifikasi bukti</p>
            <p class="mt-2 text-3xl font-black text-success">{{ $needProofVerificationCount }}</p>
            <a href="{{ route('admin.bookings.index', ['status' => 'menunggu_konfirmasi', 'has_proof' => 1]) }}" class="mt-2 inline-block text-xs font-bold text-accent hover:underline">Periksa bukti →</a>
        </article>
    </div>

    {{-- ==================== GRAFIK PENDAPATAN & RINGKASAN OKUPANSI MINGGUAN ==================== --}}
    <section class="mt-8 rounded-2xl border border-white/10 bg-gradient-to-b from-surface via-surface to-surface-2 p-5 sm:p-6 shadow-md no-print">
        <div class="flex flex-wrap items-baseline justify-between gap-3 border-b border-white/10 pb-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-accent">Laporan Finansial & Operasional</p>
                <h2 class="mt-1 text-lg font-black text-text sm:text-xl">Grafik Pendapatan 7 Hari Terakhir</h2>
            </div>
            <div class="flex flex-wrap items-center gap-4 text-right">
                <div>
                    <span class="block text-[11px] text-text-muted">Pendapatan Hari Ini</span>
                    <span class="font-tabular text-sm font-black text-accent">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</span>
                </div>
                <div class="border-l border-white/10 pl-4">
                    <span class="block text-[11px] text-text-muted">Total 7 Hari</span>
                    <span class="font-tabular text-base font-black text-text">Rp {{ number_format($weeklyRevenue, 0, ',', '.') }}</span>
                </div>
                <div class="border-l border-white/10 pl-4">
                    <span class="block text-[11px] text-text-muted">Okupansi Studio Hari Ini</span>
                    <span class="font-tabular text-sm font-black text-emerald-400">{{ $todayOccupancyPercent }}% ({{ $todayViewers }}/{{ $todayCapacity }} Kursi)</span>
                </div>
            </div>
        </div>

        {{-- Bar Chart Visual SVG / CSS --}}
        <div class="mt-6">
            <div class="grid grid-cols-7 items-end gap-2 sm:gap-4 h-48 pt-6 pb-2">
                @foreach ($revenueTrend as $item)
                    <div class="flex flex-col items-center h-full justify-end group">
                        {{-- Tooltip Nominal di Atas Bar --}}
                        <div class="mb-1.5 opacity-0 group-hover:opacity-100 transition-opacity text-center pointer-events-none">
                            <span class="rounded bg-black/90 px-1.5 py-0.5 text-[10px] font-bold text-amber-300 font-tabular shadow">
                                Rp {{ number_format($item['revenue'], 0, ',', '.') }}
                            </span>
                        </div>

                        {{-- Bar Column --}}
                        <div class="w-full max-w-[42px] rounded-t-xl transition-all duration-300 group-hover:scale-y-105 origin-bottom relative overflow-hidden {{ $item['isToday'] ? 'bg-gradient-to-t from-amber-600 via-amber-500 to-amber-300 shadow-[0_0_15px_rgba(212,175,55,0.4)]' : 'bg-gradient-to-t from-slate-700 via-slate-600 to-slate-500 hover:from-amber-700 hover:to-amber-500' }}"
                             style="height: {{ $item['heightPercent'] }}%;">
                            @if ($item['revenue'] > 0)
                                <div class="absolute inset-x-0 top-0 h-1 bg-white/40"></div>
                            @endif
                        </div>

                        {{-- Label Hari & Tanggal --}}
                        <div class="mt-2 text-center">
                            <span class="block text-[11px] font-black {{ $item['isToday'] ? 'text-accent' : 'text-text-muted' }}">
                                {{ $item['dayName'] }}
                            </span>
                            <span class="block text-[10px] font-medium text-text-muted/70">
                                {{ $item['dateLabel'] }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ==================== SESI HARI INI ==================== --}}
    <section class="mt-10 card-print">
        <h2 class="text-xl font-bold">Sesi hari ini</h2>
        @if ($todaySessions->isEmpty())
            <p class="mt-4 rounded-xl border border-border bg-surface p-4 text-sm text-text-muted">Belum ada sesi. Pastikan jadwal sudah digenerate.</p>
        @else
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ($todaySessions as $session)
                    <article class="rounded-xl border border-border bg-surface p-4 card-print">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-accent">{{ substr((string) $session->start_time, 0, 5) }} WIB</p>
                                <p class="mt-1 text-sm text-text-muted font-semibold">{{ $session->film?->title ?? 'Film belum ditentukan' }}</p>
                            </div>
                            <span class="rounded-full bg-surface-2 px-3 py-1 text-xs text-text-muted font-bold">
                                {{ (int) $session->viewer_count }} kursi terpesan · {{ $session->occupied_bookings_count }} booking
                            </span>
                        </div>
                        <div class="mt-4 border-t border-border pt-3">
                            <div class="flex flex-wrap justify-between gap-2 text-xs">
                                <h3 class="font-semibold text-text">Persiapan jajan</h3>
                                @if ($session->pending_booking_count)
                                    <span class="text-warning font-bold">{{ $session->pending_booking_count }} booking menunggu</span>
                                @endif
                            </div>
                            @php($snackBookings = $session->occupiedBookings->filter(fn ($booking) => $booking->items->isNotEmpty()))
                            @if ($snackBookings->isEmpty())
                                <p class="mt-2 text-xs text-text-muted">Tidak ada pesanan jajan.</p>
                            @else
                                <ul class="mt-2 space-y-2">
                                    @foreach ($snackBookings as $booking)
                                        <li class="rounded-lg bg-surface-2 p-3 card-print">
                                            <p class="text-xs font-semibold text-text">{{ $booking->customer_name }} · {{ $booking->code }}</p>
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

    {{-- ==================== PERLU PERHATIAN ==================== --}}
    <section class="mt-10 no-print">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-warning">Perlu perhatian</p>
                <h2 class="mt-1 text-xl font-bold">Sesi belum mencapai {{ $minimumViewers }} penonton</h2>
            </div>
            <a href="{{ route('admin.bookings.index') }}" class="text-sm font-bold text-accent hover:underline">Hubungi pemesan</a>
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
