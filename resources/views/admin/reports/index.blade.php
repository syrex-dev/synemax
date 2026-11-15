@extends('layouts.admin')

@section('title', 'Laporan')

@section('content')
<div class="space-y-7">
    {{-- Header & Aksi Unduh --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="inline-flex items-center gap-2 rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-[11px] font-bold text-amber-600 dark:text-amber-400">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
                <span>Angka Studio & Keuangan</span>
            </div>
            <h1 class="mt-2 text-2xl font-black tracking-tight text-text sm:text-3xl">Laporan Studio</h1>
            <p class="mt-1 text-xs text-text-muted">
                Periode data: <span class="font-bold text-text">{{ $startDate->locale('id')->translatedFormat('j M Y') }}</span> – <span class="font-bold text-text">{{ $endDate->locale('id')->translatedFormat('j M Y') }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a
                href="{{ route('admin.reports.export', ['period' => $period, 'date' => $referenceDate->toDateString()]) }}"
                class="inline-flex min-h-[42px] items-center gap-2 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-bold text-text hover:bg-surface-2 hover:border-amber-500/50 transition shadow-2xs"
            >
                <svg class="size-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span>Unduh CSV</span>
            </a>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div class="rounded-2xl border border-border bg-surface p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="grid gap-3 sm:grid-cols-[180px_1fr_auto] sm:items-end">
            <div>
                <x-ui.select
                    name="period"
                    id="period"
                    label="Rentang Periode"
                    :value="$period"
                    :options="[
                        'harian' => 'Harian (1 Hari)',
                        'mingguan' => 'Mingguan (7 Hari)',
                        'bulanan' => 'Bulanan (1 Bulan)',
                    ]"
                />
            </div>
            <div>
                <label for="date" class="block text-xs font-semibold text-text-muted mb-1.5">Tanggal Acuan</label>
                <input
                    id="date"
                    name="date"
                    type="date"
                    value="{{ $referenceDate->toDateString() }}"
                    class="w-full min-h-[44px] rounded-xl border border-border bg-surface-2 px-3 text-xs font-semibold text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30 transition"
                >
            </div>
            <button
                type="submit"
                class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-amber-500 px-6 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                </svg>
                <span>Tampilkan Laporan</span>
            </button>
        </form>
    </div>

    {{-- Ringkasan Metrik Utama & Finansial (Dua Tingkat Rapi & Berhierarki) --}}
    <div class="space-y-3">
        {{-- Tingkat 1: 4 Metrik Utama --}}
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <article class="relative overflow-hidden rounded-2xl border border-amber-500/40 bg-gradient-to-br from-amber-500/10 via-surface to-surface p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Total Pendapatan</p>
                    <span class="rounded-lg bg-amber-500/15 p-1.5 text-amber-600 dark:text-amber-400">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                </div>
                <p class="mt-2 text-2xl font-black text-text font-tabular">Rp {{ number_format($revenue, 0, ',', '.') }}</p>
                <p class="mt-1 text-[11px] text-text-muted">Akumulasi tiket (Rp {{ number_format($ticketRevenue, 0, ',', '.') }}) + jajan</p>
            </article>

            <article class="relative overflow-hidden rounded-2xl border border-emerald-500/40 bg-gradient-to-br from-emerald-500/10 via-surface to-surface p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Laba Kasar Studio</p>
                    <span class="rounded-lg bg-emerald-500/15 p-1.5 text-emerald-600 dark:text-emerald-400">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" /></svg>
                    </span>
                </div>
                <p class="mt-2 text-2xl font-black text-emerald-600 dark:text-emerald-400 font-tabular">Rp {{ number_format($grossProfit, 0, ',', '.') }}</p>
                <p class="mt-1 text-[11px] text-text-muted">Tiket + laba jajan - beban listrik</p>
            </article>

            <article class="rounded-2xl border border-border bg-surface p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Kursi terpesan</p>
                    <span class="rounded-lg bg-surface-2 p-1.5 text-text-muted">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                    </span>
                </div>
                <p class="mt-2 text-2xl font-black text-text font-tabular">{{ number_format($viewers, 0, ',', '.') }} <span class="text-sm font-semibold text-text-muted">kursi</span></p>
                <p class="mt-1 text-[11px] text-text-muted">Dari {{ $bookingCount }} booking sah</p>
            </article>

            <article class="rounded-2xl border border-border bg-surface p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Sesi Batal Kuorum</p>
                    <span class="rounded-lg bg-surface-2 p-1.5 text-text-muted">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                    </span>
                </div>
                <p class="mt-2 text-2xl font-black text-text font-tabular">{{ $cancelledByQuorumCount }} <span class="text-sm font-semibold text-text-muted">sesi</span></p>
                <p class="mt-1 text-[11px] text-text-muted">Sesi otomatis ditutup karena kuorum tidak tercapai</p>
            </article>
        </div>

        {{-- Tingkat 2: 6 Kartu Rincian Finansial & Jajan --}}
        <div class="grid gap-3 grid-cols-2 sm:grid-cols-3 lg:grid-cols-6">
            <article class="rounded-xl border border-border bg-surface p-3.5 shadow-2xs">
                <p class="text-[11px] font-semibold text-text-muted">Pendapatan Tiket</p>
                <p class="mt-1 text-base font-black text-text font-tabular">Rp {{ number_format($ticketRevenue, 0, ',', '.') }}</p>
            </article>
            <article class="rounded-xl border border-border bg-surface p-3.5 shadow-2xs">
                <p class="text-[11px] font-semibold text-text-muted">Pendapatan Jajan</p>
                <p class="mt-1 text-base font-black text-text font-tabular">Rp {{ number_format($snackRevenue, 0, ',', '.') }}</p>
            </article>
            <article class="rounded-xl border border-border bg-surface p-3.5 shadow-2xs">
                <p class="text-[11px] font-semibold text-text-muted">Laba Jajan</p>
                <p class="mt-1 text-base font-black text-emerald-600 dark:text-emerald-400 font-tabular">Rp {{ number_format($snackProfit, 0, ',', '.') }}</p>
            </article>
            <article class="rounded-xl border border-border bg-surface p-3.5 shadow-2xs">
                <p class="text-[11px] font-semibold text-text-muted">Rata-rata Jajan / Booking</p>
                <p class="mt-1 text-base font-black text-text font-tabular">Rp {{ number_format($averageSnackValue, 0, ',', '.') }}</p>
            </article>
            <article class="rounded-xl border border-border bg-surface p-3.5 shadow-2xs">
                <p class="text-[11px] font-semibold text-text-muted">Booking Pesan Jajan</p>
                <p class="mt-1 text-base font-black text-text font-tabular">{{ $snackBookingCount }} <span class="text-xs font-semibold text-text-muted">({{ $snackBookingPercent }}%)</span></p>
            </article>
            <article class="rounded-xl border border-border bg-surface p-3.5 shadow-2xs">
                <p class="text-[11px] font-semibold text-text-muted">Listrik</p>
                <p class="mt-1 text-base font-black text-text font-tabular">Rp {{ number_format($electricityCost, 0, ',', '.') }}</p>
            </article>
        </div>
    </div>

    {{-- BAGIAN UTAMA: Okupansi, Jajan, dan Film (Grid 2 Kolom Lega & Rapi) --}}
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- KARTU 1: Okupansi Berdasarkan Jam --}}
        <article class="flex flex-col justify-between rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-sm">
            <div>
                <div class="flex items-center justify-between gap-3 pb-3 border-b border-border">
                    <div>
                        <h2 class="text-base font-bold text-text">Okupansi Berdasarkan Jam</h2>
                        <p class="text-xs text-text-muted mt-0.5">{{ $sessionCount }} sesi terjadwal pada periode ini.</p>
                    </div>
                    <span class="rounded-full bg-amber-500/10 px-2.5 py-1 text-[11px] font-bold text-amber-600 dark:text-amber-400 border border-amber-500/20">
                        Tingkat Keterisian
                    </span>
                </div>

                <div class="mt-4 space-y-3.5">
                    @forelse ($occupancyByTime as $time => $occupancy)
                        @php
                            $pct = (int) ($occupancy['percent'] ?? 0);
                            $colorClass = match (true) {
                                $pct >= 50 => 'bg-emerald-500',
                                $pct > 0 => 'bg-amber-500',
                                default => 'bg-border',
                            };
                            $badgeColor = match (true) {
                                $pct >= 50 => 'text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 border-emerald-500/30',
                                $pct > 0 => 'text-amber-600 dark:text-amber-400 bg-amber-500/10 border-amber-500/30',
                                default => 'text-text-muted bg-surface-2 border-border',
                            };
                        @endphp
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-text">{{ $time }} WIB</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-text-muted font-tabular">{{ $occupancy['occupied'] }}/{{ $occupancy['capacity'] }} kursi</span>
                                    <span class="inline-flex min-w-[42px] justify-center rounded-md border px-1.5 py-0.5 text-[10px] font-bold font-tabular {{ $badgeColor }}">
                                        {{ $pct }}%
                                    </span>
                                </div>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-surface-2 border border-border/40">
                                <div
                                    class="h-full rounded-full {{ $colorClass }} transition-all duration-300"
                                    style="width: {{ min(100, $pct) }}%"
                                ></div>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-xs text-text-muted">
                            Belum ada sesi di periode ini.
                        </div>
                    @endforelse
                </div>
            </div>
        </article>

        {{-- KARTU 2: Jajan Paling Laris --}}
        <article class="flex flex-col justify-between rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-sm">
            <div>
                <div class="flex items-center justify-between gap-3 pb-3 border-b border-border">
                    <div>
                        <h2 class="text-base font-bold text-text">Jajan Paling Laris</h2>
                        <p class="text-xs text-text-muted mt-0.5">Camilan & minuman dengan volume penjualan tertinggi.</p>
                    </div>
                    <span class="rounded-full bg-amber-500/10 px-2.5 py-1 text-[11px] font-bold text-amber-600 dark:text-amber-400 border border-amber-500/20">
                        Penjualan Jajan
                    </span>
                </div>

                <div class="mt-4">
                    @if ($snackPopularity->isNotEmpty())
                        <div class="divide-y divide-border/60">
                            @foreach ($snackPopularity as $index => $snackStats)
                                <div class="flex items-center justify-between gap-3 py-2.5 first:pt-0 last:pb-0">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="inline-flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-black {{ $index < 3 ? 'bg-amber-500 text-slate-950 shadow-2xs' : 'bg-surface-2 text-text-muted' }}">
                                            {{ $index + 1 }}
                                        </span>
                                        <span class="truncate text-xs font-bold text-text" title="{{ $snackStats->name_snapshot }}">
                                            {{ $snackStats->name_snapshot }}
                                        </span>
                                    </div>
                                    <span class="shrink-0 rounded-lg border border-border bg-surface-2 px-2.5 py-1 text-xs font-bold text-text font-tabular">
                                        {{ (int) $snackStats->quantity }} buah
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <div class="inline-flex size-12 items-center justify-center rounded-2xl border border-border bg-surface-2 text-text-muted">
                                <svg class="size-6 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" />
                                </svg>
                            </div>
                            <p class="mt-3 text-xs font-bold text-text">Belum ada jajan terjual pada periode ini.</p>
                            <p class="mt-1 text-[11px] text-text-muted max-w-xs">Pesanan jajan akan otomatis muncul saat booking menyertakan camilan atau minuman.</p>
                        </div>
                    @endif
                </div>
            </div>
        </article>

        {{-- KARTU 3: Film Terkonfirmasi Ditonton --}}
        <article class="flex flex-col justify-between rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-sm">
            <div>
                <div class="flex items-center justify-between gap-3 pb-3 border-b border-border">
                    <div>
                        <h2 class="text-base font-bold text-text">Film Terkonfirmasi Ditonton</h2>
                        <p class="text-xs text-text-muted mt-0.5">Hanya dari booking yang sudah terbayar/terkonfirmasi.</p>
                    </div>
                    <span class="rounded-full bg-emerald-500/10 px-2.5 py-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400 border border-emerald-500/25">
                        Tayang Sah
                    </span>
                </div>

                <div class="mt-4">
                    @if ($filmPopularity->isNotEmpty())
                        <div class="divide-y divide-border/60">
                            @foreach ($filmPopularity as $index => $filmStats)
                                <div class="flex items-center justify-between gap-3 py-2.5 first:pt-0 last:pb-0">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="inline-flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-black {{ $index < 3 ? 'bg-amber-500 text-slate-950 shadow-2xs' : 'bg-surface-2 text-text-muted' }}">
                                            {{ $index + 1 }}
                                        </span>
                                        <span class="truncate text-xs font-bold text-text" title="{{ $filmStats->film?->title ?? 'Film dihapus' }}">
                                            {{ $filmStats->film?->title ?? 'Film dihapus' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="rounded-lg bg-surface-2 px-2 py-0.5 text-[11px] font-semibold text-text-muted font-tabular">
                                            {{ (int) $filmStats->booking_count }} booking
                                        </span>
                                        <span class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-[11px] font-bold text-emerald-600 dark:text-emerald-400 font-tabular">
                                            {{ (int) $filmStats->viewer_count }} kursi
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-10 text-center text-xs text-text-muted">
                            Belum ada booking terkonfirmasi.
                        </div>
                    @endif
                </div>
            </div>
        </article>

        {{-- KARTU 4: Film Paling Sering Diminati --}}
        <article class="flex flex-col justify-between rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-sm">
            <div>
                <div class="flex items-center justify-between gap-3 pb-3 border-b border-border">
                    <div>
                        <h2 class="text-base font-bold text-text">Film Paling Sering Diminati</h2>
                        <p class="text-xs text-text-muted mt-0.5">Termasuk pilihan pemesan pada sesi batal kuorum.</p>
                    </div>
                    <span class="rounded-full bg-amber-500/10 px-2.5 py-1 text-[11px] font-bold text-amber-600 dark:text-amber-400 border border-amber-500/25">
                        Semua Minat
                    </span>
                </div>

                <div class="mt-4">
                    @if ($filmDemandPopularity->isNotEmpty())
                        <div class="divide-y divide-border/60">
                            @foreach ($filmDemandPopularity as $index => $filmStats)
                                <div class="flex items-center justify-between gap-3 py-2.5 first:pt-0 last:pb-0">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="inline-flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-black {{ $index < 3 ? 'bg-amber-500 text-slate-950 shadow-2xs' : 'bg-surface-2 text-text-muted' }}">
                                            {{ $index + 1 }}
                                        </span>
                                        <span class="truncate text-xs font-bold text-text" title="{{ $filmStats->film?->title ?? 'Film dihapus' }}">
                                            {{ $filmStats->film?->title ?? 'Film dihapus' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="rounded-lg bg-surface-2 px-2 py-0.5 text-[11px] font-semibold text-text-muted font-tabular">
                                            {{ (int) $filmStats->booking_count }} booking
                                        </span>
                                        <span class="rounded-lg border border-amber-500/30 bg-amber-500/10 px-2 py-0.5 text-[11px] font-bold text-amber-600 dark:text-amber-400 font-tabular">
                                            {{ (int) $filmStats->interested_viewers }} peminat
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-10 text-center text-xs text-text-muted">
                            Belum ada data peminat film.
                        </div>
                    @endif
                </div>
            </div>
        </article>
    </div>

    {{-- TABEL: Pendapatan per Hari Sesi --}}
    <section class="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-border">
            <div>
                <h2 class="text-base font-bold text-text">Pendapatan per Hari Sesi</h2>
                <p class="text-xs text-text-muted mt-0.5">Rincian pendapatan tiket, jajan, dan total omzet per hari sesi pada periode ini.</p>
            </div>
            <span class="rounded-full bg-surface-2 border border-border px-3 py-1 text-xs font-semibold text-text-muted">
                {{ $dailySummary->count() }} hari tercatat
            </span>
        </div>

        {{-- Desktop Table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-border bg-surface-2 text-text-muted uppercase tracking-wider text-[11px] font-bold">
                    <tr>
                        <th class="px-4 py-3">Tanggal Sesi</th>
                        <th class="px-4 py-3 text-center">Kursi Terpesan</th>
                        <th class="px-4 py-3 text-right">Tiket</th>
                        <th class="px-4 py-3 text-right">Jajan</th>
                        <th class="px-4 py-3 text-right font-black text-text">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border font-tabular">
                    @php
                        $sumViewers = 0;
                        $sumTicket = 0;
                        $sumSnack = 0;
                        $sumTotal = 0;
                    @endphp
                    @forelse ($dailySummary as $day)
                        @php
                            $sumViewers += (int) $day->viewers;
                            $sumTicket += (int) $day->ticket_revenue;
                            $sumSnack += (int) $day->snack_revenue;
                            $sumTotal += (int) $day->revenue;
                        @endphp
                        <tr class="hover:bg-surface-2/60 transition">
                            <td class="px-4 py-3 font-semibold text-text">
                                {{ \Carbon\Carbon::parse($day->report_date)->locale('id')->translatedFormat('l, j M Y') }}
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-text">
                                {{ (int) $day->viewers }} kursi
                            </td>
                            <td class="px-4 py-3 text-right text-text-muted">
                                Rp {{ number_format((int) $day->ticket_revenue, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-text-muted">
                                Rp {{ number_format((int) $day->snack_revenue, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-extrabold text-amber-600 dark:text-amber-400">
                                Rp {{ number_format((int) $day->revenue, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-text-muted">
                                Belum ada pendapatan di periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($dailySummary->isNotEmpty())
                    <tfoot class="border-t-2 border-border bg-surface-2/80 font-tabular font-bold">
                        <tr>
                            <td class="px-4 py-3 text-text font-black">TOTAL KESELURUHAN</td>
                            <td class="px-4 py-3 text-center text-text font-black">{{ number_format($sumViewers, 0, ',', '.') }} kursi</td>
                            <td class="px-4 py-3 text-right text-text">Rp {{ number_format($sumTicket, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-text">Rp {{ number_format($sumSnack, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-base text-amber-600 dark:text-amber-400 font-black">Rp {{ number_format($sumTotal, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        {{-- Mobile Cards --}}
        <div class="space-y-3 md:hidden">
            @forelse ($dailySummary as $day)
                <article class="rounded-xl border border-border bg-surface-2/50 p-4 space-y-2.5">
                    <h3 class="font-bold text-xs text-text border-b border-border/60 pb-2">
                        {{ \Carbon\Carbon::parse($day->report_date)->locale('id')->translatedFormat('D, j M Y') }}
                    </h3>
                    <div class="grid grid-cols-2 gap-2 text-xs font-tabular">
                        <div>
                            <span class="text-text-muted text-[11px]">Kursi terpesan</span>
                            <p class="font-bold text-text">{{ (int) $day->viewers }} kursi</p>
                        </div>
                        <div>
                            <span class="text-text-muted text-[11px]">Tiket</span>
                            <p class="font-bold text-text">Rp {{ number_format((int) $day->ticket_revenue, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <span class="text-text-muted text-[11px]">Jajan</span>
                            <p class="font-bold text-text">Rp {{ number_format((int) $day->snack_revenue, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <span class="text-text-muted text-[11px]">Total Pendapatan</span>
                            <p class="font-extrabold text-amber-600 dark:text-amber-400">Rp {{ number_format((int) $day->revenue, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </article>
            @empty
                <p class="rounded-xl border border-border bg-surface-2 p-6 text-center text-xs text-text-muted">
                    Belum ada pendapatan di periode ini.
                </p>
            @endforelse
        </div>
    </section>

    {{-- STATISTIK TINGKAT KEHADIRAN & CARA CHECK-IN --}}
    <section class="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-border">
            <div>
                <h2 class="text-base font-bold text-text">Tingkat Kehadiran & Cara Check-In</h2>
                <p class="text-xs text-text-muted mt-0.5">Statistik penonton yang hadir, tidak hadir, dan rincian metode validasi tiket pada periode ini.</p>
            </div>
            <span class="rounded-full bg-amber-500/10 px-2.5 py-1 text-[11px] font-bold text-amber-600 dark:text-amber-400 border border-amber-500/20">
                {{ $period === 'harian' ? 'Hari Ini' : ucfirst($period) }}
            </span>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-emerald-500/35 bg-emerald-500/10 p-4">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-emerald-800 dark:text-emerald-300">Telah Hadir</p>
                    <span class="rounded-full bg-emerald-500/20 p-1 text-emerald-600 dark:text-emerald-400">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    </span>
                </div>
                <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-2 font-tabular">
                    {{ (int) ($attendanceStats->hadir_people ?? 0) }} <span class="text-xs font-bold text-emerald-700 dark:text-emerald-300">orang</span>
                </p>
                <p class="text-[11px] text-text-muted mt-1">
                    {{ (int) ($attendanceStats->hadir_bookings ?? 0) }} booking terverifikasi
                </p>
            </div>

            <div class="rounded-xl border border-amber-500/35 bg-amber-500/10 p-4">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-amber-800 dark:text-amber-300">Belum Hadir</p>
                    <span class="rounded-full bg-amber-500/20 p-1 text-amber-600 dark:text-amber-400">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                </div>
                <p class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-2 font-tabular">
                    {{ (int) ($attendanceStats->belum_hadir_people ?? 0) }} <span class="text-xs font-bold text-amber-700 dark:text-amber-300">orang</span>
                </p>
                <p class="text-[11px] text-text-muted mt-1">
                    {{ (int) ($attendanceStats->belum_hadir_bookings ?? 0) }} booking aktif menunggu kedatangan
                </p>
            </div>

            <div class="rounded-xl border border-red-500/35 bg-red-500/10 p-4">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-red-800 dark:text-red-300">Tidak Hadir (No-Show)</p>
                    <span class="rounded-full bg-red-500/20 p-1 text-red-600 dark:text-red-400">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </span>
                </div>
                <p class="text-2xl font-black text-red-600 dark:text-red-400 mt-2 font-tabular">
                    {{ (int) ($attendanceStats->tidak_hadir_people ?? 0) }} <span class="text-xs font-bold text-red-700 dark:text-red-300">orang</span>
                </p>
                <p class="text-[11px] text-text-muted mt-1">
                    {{ (int) ($attendanceStats->tidak_hadir_bookings ?? 0) }} booking ditandai tidak datang
                </p>
            </div>
        </div>

        <div class="pt-3 border-t border-border">
            <h3 class="text-xs font-bold uppercase tracking-wider text-text-muted mb-2">Metode Validasi Check-In</h3>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5 text-xs">
                @php
                    $methods = [
                        'qr' => ['label' => 'Scan QR', 'count' => $checkInMethodStats['qr'] ?? 0],
                        'kode' => ['label' => 'Input Kode', 'count' => $checkInMethodStats['kode'] ?? 0],
                        'daftar' => ['label' => 'Tab Daftar', 'count' => $checkInMethodStats['daftar'] ?? 0],
                        'admin_manual' => ['label' => 'Manual Admin', 'count' => $checkInMethodStats['admin_manual'] ?? 0],
                        'legacy' => ['label' => 'Sistem Lama', 'count' => $checkInMethodStats['legacy'] ?? 0],
                    ];
                @endphp
                @foreach ($methods as $mKey => $mData)
                    <div class="p-3 rounded-xl border border-border bg-surface-2/50 flex flex-col justify-between">
                        <span class="text-text-muted font-medium text-[11px]">{{ $mData['label'] }}</span>
                        <span class="text-base font-bold text-text mt-1 font-tabular">{{ $mData['count'] }} <span class="text-[11px] text-text-muted font-normal">tiket</span></span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Program Member & Loyalitas (Bagian 4.7) --}}
    @if (isset($memberReport))
        <section class="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-border pb-3">
                <div>
                    <h2 class="text-base font-bold text-text">Program Member & Loyalitas Kunjungan</h2>
                    <p class="text-xs text-text-muted mt-0.5">Statistik kehadiran member, reward yang ditebus, dan pengembalian dana pada periode ini.</p>
                </div>
                <a href="{{ route('admin.members.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-amber-500 hover:text-amber-400 transition">
                    <span>Kelola Member</span>
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                </a>
            </div>

            <div class="grid gap-3 grid-cols-2 sm:grid-cols-4">
                <div class="rounded-xl border border-border bg-surface-2/60 p-4">
                    <p class="text-xs text-text-muted font-semibold">Total Member Aktif</p>
                    <p class="text-xl font-black text-text mt-1 font-tabular">{{ number_format($memberReport['active_members'], 0, ',', '.') }} orang</p>
                </div>
                <div class="rounded-xl border border-border bg-surface-2/60 p-4">
                    <p class="text-xs text-text-muted font-semibold">Kunjungan Sah Periode Ini</p>
                    <p class="text-xl font-black text-emerald-500 mt-1 font-tabular">{{ number_format($memberReport['visits_period'], 0, ',', '.') }} kali</p>
                </div>
                <div class="rounded-xl border border-border bg-surface-2/60 p-4">
                    <p class="text-xs text-text-muted font-semibold">Reward Diberikan</p>
                    <p class="text-xl font-black text-amber-500 mt-1 font-tabular">{{ $memberReport['discount_rewards_count'] + $memberReport['free_ticket_rewards_count'] }}</p>
                    <p class="text-[10px] text-text-muted mt-0.5">{{ $memberReport['discount_rewards_count'] }} potongan · {{ $memberReport['free_ticket_rewards_count'] }} tiket gratis</p>
                </div>
                <div class="rounded-xl border border-border bg-surface-2/60 p-4">
                    <p class="text-xs text-text-muted font-semibold">Total Beban Reward</p>
                    <p class="text-xl font-black text-amber-500 mt-1 font-tabular">Rp {{ number_format($memberReport['total_reward_cost'], 0, ',', '.') }}</p>
                </div>
            </div>

            @if ($memberReport['pending_refund_cash'] > 0)
                <div class="rounded-xl border border-amber-500/40 bg-amber-500/10 p-4 text-xs font-semibold text-amber-700 dark:text-amber-300 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <svg class="size-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                        <span>Ada <strong>{{ $memberReport['pending_refund_cash'] }} pengembalian tunai reward member</strong> yang belum selesai diserahkan ke pelanggan.</span>
                    </div>
                    <a href="{{ route('admin.members.index') }}" class="font-bold underline shrink-0 hover:text-amber-600">Lihat di Member</a>
                </div>
            @endif

            @if ($memberReport['top_members']->isNotEmpty())
                <div class="pt-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-text-muted mb-2">Member Paling Sering Berkunjung (Periode Ini)</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                        @foreach ($memberReport['top_members'] as $topM)
                            <div class="rounded-xl border border-border bg-surface-2/40 p-3 flex items-center justify-between text-xs">
                                <div>
                                    <p class="font-bold text-text">{{ $topM->name }}</p>
                                    <p class="text-[10px] font-mono text-text-muted">{{ $topM->formattedCode() }}</p>
                                </div>
                                <span class="font-extrabold text-amber-600 dark:text-amber-400 bg-surface px-2.5 py-1 rounded-lg border border-border font-tabular">
                                    {{ $topM->valid_visits_count }} kunjungan
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    @endif
</div>
@endsection
