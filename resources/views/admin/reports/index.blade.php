@extends('layouts.admin')

@section('title', 'Laporan')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-accent">Angka studio</p><h1 class="mt-2 text-3xl font-black">Laporan</h1><p class="mt-1 text-sm text-text-muted">{{ $startDate->toDateString() }} – {{ $endDate->toDateString() }}</p></div>
        <a href="{{ route('admin.reports.export', ['period' => $period, 'date' => $referenceDate->toDateString()]) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-accent/40 px-4 text-sm font-semibold text-accent hover:bg-accent/10">Unduh CSV</a>
    </div>
    <form method="GET" action="{{ route('admin.reports.index') }}" class="mt-6 grid gap-3 rounded-2xl border border-border bg-surface p-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
        <div><label for="period" class="text-xs font-semibold text-text-muted">Periode</label><select id="period" name="period" class="mt-2 min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-sm text-text"><option value="harian" @selected($period === 'harian')>Harian</option><option value="mingguan" @selected($period === 'mingguan')>Mingguan</option><option value="bulanan" @selected($period === 'bulanan')>Bulanan</option></select></div>
        <div><label for="date" class="text-xs font-semibold text-text-muted">Tanggal acuan</label><input id="date" name="date" type="date" value="{{ $referenceDate->toDateString() }}" class="mt-2 min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-sm text-text"></div>
        <button class="min-h-11 rounded-lg bg-accent px-5 text-sm font-bold text-accent-contrast hover:opacity-90">Tampilkan</button>
    </form>

    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([['Pendapatan tiket', 'Rp '.number_format($ticketRevenue, 0, ',', '.')], ['Pendapatan jajan', 'Rp '.number_format($snackRevenue, 0, ',', '.')], ['Total pendapatan', 'Rp '.number_format($revenue, 0, ',', '.')], ['Kursi terpesan', $viewers], ['Laba jajan', 'Rp '.number_format($snackProfit, 0, ',', '.')], ['Rata-rata jajan / booking jajan', 'Rp '.number_format($averageSnackValue, 0, ',', '.')], ['Booking pesan jajan', $snackBookingCount.' ('.$snackBookingPercent.'%)'], ['Listrik', 'Rp '.number_format($electricityCost, 0, ',', '.')], ['Laba kasar studio', 'Rp '.number_format($grossProfit, 0, ',', '.')]] as [$label, $value])
            <article class="rounded-2xl border border-border bg-surface p-5"><p class="text-sm text-text-muted">{{ $label }}</p><p class="mt-2 text-2xl font-black text-accent">{{ $value }}</p></article>
        @endforeach
    </div>

    <section class="mt-9 grid gap-6 lg:grid-cols-3">
        <article class="rounded-2xl border border-border bg-surface p-5">
            <h2 class="font-bold">Okupansi berdasarkan jam</h2>
            <p class="mt-1 text-xs text-text-muted">{{ $sessionCount }} sesi terjadwal pada periode ini.</p>
            <div class="mt-4 space-y-4">
                @forelse ($occupancyByTime as $time => $occupancy)
                    <div>
                        <div class="mb-1 flex justify-between text-xs"><span>{{ $time }} WIB</span><span>{{ $occupancy['occupied'] }}/{{ $occupancy['capacity'] }} kursi · {{ $occupancy['percent'] }}%</span></div>
                        <div class="h-2 overflow-hidden rounded-full bg-surface-2"><div class="h-full rounded-full bg-accent" style="width: {{ $occupancy['percent'] }}%"></div></div>
                    </div>
                @empty<p class="text-sm text-text-muted">Belum ada sesi di periode ini.</p>
                @endforelse
            </div>
        </article>
        <article class="rounded-2xl border border-border bg-surface p-5">
            <h2 class="font-bold">Jajan paling laris</h2>
            <div class="mt-4 space-y-3">
                @forelse ($snackPopularity as $index => $snackStats)
                    <div class="flex items-center justify-between gap-3 border-b border-border pb-3 last:border-0"><span class="text-sm"><span class="mr-2 text-accent">{{ $index + 1 }}.</span>{{ $snackStats->name_snapshot }}</span><span class="text-xs text-text-muted">{{ (int) $snackStats->quantity }} buah</span></div>
                @empty<p class="text-sm text-text-muted">Belum ada jajan terjual pada periode ini.</p>
                @endforelse
            </div>
        </article>
        <article class="rounded-2xl border border-border bg-surface p-5">
            <h2 class="font-bold">Film paling sering dipilih</h2>
            <div class="mt-4 space-y-3">
                @forelse ($filmPopularity as $index => $filmStats)
                    <div class="flex items-center justify-between gap-3 border-b border-border pb-3 last:border-0"><span class="text-sm"><span class="mr-2 text-accent">{{ $index + 1 }}.</span>{{ $filmStats->film?->title ?? 'Film dihapus' }}</span><span class="text-xs text-text-muted">{{ (int) $filmStats->viewer_count }} kursi terpesan</span></div>
                @empty<p class="text-sm text-text-muted">Belum ada booking terkonfirmasi.</p>
                @endforelse
            </div>
        </article>
    </section>

    <section class="mt-9 rounded-2xl border border-border bg-surface p-5">
        <h2 class="font-bold">Pendapatan per hari sesi</h2>
        <div class="mt-4 hidden md:block">
            <x-admin.table class="rounded-none">
                <x-slot:columns>
                    <col style="width: 20%">
                    <col style="width: 15%">
                    <col style="width: 22%">
                    <col style="width: 22%">
                    <col style="width: 21%">
                </x-slot:columns>
                <x-slot:head>
                    <th scope="col" class="py-2">Tanggal</th>
                    <th scope="col" class="py-2">Kursi terpesan</th>
                    <th scope="col" class="py-2 text-right">Tiket</th>
                    <th scope="col" class="py-2 text-right">Jajan</th>
                    <th scope="col" class="py-2 text-right">Total</th>
                </x-slot:head>
                    @forelse ($dailySummary as $day)
                        <tr class="border-b border-border last:border-0"><td class="py-3">{{ $day->report_date }}</td><td class="py-3">{{ (int) $day->viewers }}</td><td class="py-3 text-right">Rp {{ number_format((int) $day->ticket_revenue, 0, ',', '.') }}</td><td class="py-3 text-right">Rp {{ number_format((int) $day->snack_revenue, 0, ',', '.') }}</td><td class="py-3 text-right">Rp {{ number_format((int) $day->revenue, 0, ',', '.') }}</td></tr>
                    @empty<tr><td colspan="5" class="py-4 text-text-muted">Belum ada pendapatan di periode ini.</td></tr>
                    @endforelse
            </x-admin.table>
        </div>
        <div class="mt-4 space-y-3 md:hidden">
            @forelse ($dailySummary as $day)
                <article class="rounded-lg border border-border bg-bg p-4">
                    <h3 class="font-semibold">{{ $day->report_date }}</h3>
                    <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <div><dt class="text-text-muted">Kursi terpesan</dt><dd class="mt-1 font-semibold">{{ (int) $day->viewers }}</dd></div>
                        <div><dt class="text-text-muted">Tiket</dt><dd class="mt-1 font-semibold">Rp {{ number_format((int) $day->ticket_revenue, 0, ',', '.') }}</dd></div>
                        <div><dt class="text-text-muted">Jajan</dt><dd class="mt-1 font-semibold">Rp {{ number_format((int) $day->snack_revenue, 0, ',', '.') }}</dd></div>
                        <div><dt class="text-text-muted">Total</dt><dd class="mt-1 font-semibold text-accent">Rp {{ number_format((int) $day->revenue, 0, ',', '.') }}</dd></div>
                    </dl>
                </article>
            @empty
                <p class="rounded-lg border border-border bg-bg p-4 text-sm text-text-muted">Belum ada pendapatan di periode ini.</p>
            @endforelse
        </div>
    </section>
@endsection
