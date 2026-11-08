@extends('layouts.admin')

@section('title', 'Laporan')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-accent">Angka studio</p><h1 class="mt-2 text-3xl font-black">Laporan</h1><p class="mt-1 text-sm text-text-muted">{{ $startDate->locale('id')->translatedFormat('j M Y') }} – {{ $endDate->locale('id')->translatedFormat('j M Y') }}</p></div>
        <a href="{{ route('admin.reports.export', ['period' => $period, 'date' => $referenceDate->toDateString()]) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-accent/40 px-4 text-sm font-semibold text-accent hover:bg-accent/10">Unduh CSV</a>
    </div>
    <form method="GET" action="{{ route('admin.reports.index') }}" class="mt-6 grid gap-3 rounded-2xl border border-border bg-surface p-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
        <div>
            <x-ui.select
                name="period"
                id="period"
                label="Periode"
                :value="$period"
                :options="[
                    'harian' => 'Harian',
                    'mingguan' => 'Mingguan',
                    'bulanan' => 'Bulanan',
                ]"
            />
        </div>
        <div><label for="date" class="text-xs font-semibold text-text-muted">Tanggal acuan</label><input id="date" name="date" type="date" value="{{ $referenceDate->toDateString() }}" class="mt-2 min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-sm text-text"></div>
        <button class="min-h-11 rounded-lg bg-accent px-5 text-sm font-bold text-accent-contrast hover:opacity-90">Tampilkan</button>
    </form>

    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        @foreach ([['Pendapatan tiket', 'Rp '.number_format($ticketRevenue, 0, ',', '.')], ['Pendapatan jajan', 'Rp '.number_format($snackRevenue, 0, ',', '.')], ['Total pendapatan', 'Rp '.number_format($revenue, 0, ',', '.')], ['Kursi terpesan', $viewers], ['Sesi batal karena kuorum', $cancelledByQuorumCount.' sesi'], ['Laba jajan', 'Rp '.number_format($snackProfit, 0, ',', '.')], ['Rata-rata jajan / booking', 'Rp '.number_format($averageSnackValue, 0, ',', '.')], ['Booking pesan jajan', $snackBookingCount.' ('.$snackBookingPercent.'%)'], ['Listrik', 'Rp '.number_format($electricityCost, 0, ',', '.')], ['Laba kasar studio', 'Rp '.number_format($grossProfit, 0, ',', '.')]] as [$label, $value])
            <article class="rounded-2xl border border-border bg-surface p-5"><p class="text-sm text-text-muted">{{ $label }}</p><p class="mt-2 text-2xl font-black text-accent">{{ $value }}</p></article>
        @endforeach
    </div>

    <section class="mt-9 grid gap-6 md:grid-cols-2 xl:grid-cols-4">
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
            <div class="flex items-center justify-between gap-2">
                <h2 class="font-bold">Film paling sering diminati</h2>
                <span class="rounded bg-accent/10 px-2 py-0.5 text-[10px] font-bold text-accent uppercase">Semua Minat</span>
            </div>
            <p class="mt-1 text-xs text-text-muted">Termasuk pilihan pemesan pada sesi batal kuorum.</p>
            <div class="mt-4 space-y-3">
                @forelse ($filmDemandPopularity as $index => $filmStats)
                    <div class="flex items-center justify-between gap-3 border-b border-border pb-3 last:border-0"><span class="text-sm"><span class="mr-2 text-accent">{{ $index + 1 }}.</span>{{ $filmStats->film?->title ?? 'Film dihapus' }}</span><span class="text-xs text-text-muted">{{ (int) $filmStats->booking_count }} booking ({{ (int) $filmStats->interested_viewers }} peminat)</span></div>
                @empty<p class="text-sm text-text-muted">Belum ada data peminat film.</p>
                @endforelse
            </div>
        </article>
        <article class="rounded-2xl border border-border bg-surface p-5">
            <h2 class="font-bold">Film terkonfirmasi ditonton</h2>
            <p class="mt-1 text-xs text-text-muted">Hanya dari booking yang sudah terbayar/terkonfirmasi.</p>
            <div class="mt-4 space-y-3">
                @forelse ($filmPopularity as $index => $filmStats)
                    <div class="flex items-center justify-between gap-3 border-b border-border pb-3 last:border-0"><span class="text-sm"><span class="mr-2 text-accent">{{ $index + 1 }}.</span>{{ $filmStats->film?->title ?? 'Film dihapus' }}</span><span class="text-xs text-text-muted">{{ (int) $filmStats->booking_count }} booking ({{ (int) $filmStats->viewer_count }} kursi)</span></div>
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
                        <tr class="border-b border-border last:border-0"><td class="py-3">{{ \Carbon\Carbon::parse($day->report_date)->locale('id')->translatedFormat('D, j M Y') }}</td><td class="py-3">{{ (int) $day->viewers }}</td><td class="py-3 text-right">Rp {{ number_format((int) $day->ticket_revenue, 0, ',', '.') }}</td><td class="py-3 text-right">Rp {{ number_format((int) $day->snack_revenue, 0, ',', '.') }}</td><td class="py-3 text-right">Rp {{ number_format((int) $day->revenue, 0, ',', '.') }}</td></tr>
                    @empty<tr><td colspan="5" class="py-4 text-text-muted">Belum ada pendapatan di periode ini.</td></tr>
                    @endforelse
            </x-admin.table>
        </div>
        <div class="mt-4 space-y-3 md:hidden">
            @forelse ($dailySummary as $day)
                <article class="rounded-lg border border-border bg-bg p-4">
                    <h3 class="font-semibold">{{ \Carbon\Carbon::parse($day->report_date)->locale('id')->translatedFormat('D, j M Y') }}</h3>
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

    {{-- STATISTIK TINGKAT KEHADIRAN & METODE CHECK-IN --}}
    <section class="mt-6 rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-border">
            <div>
                <h2 class="text-base font-bold text-text">Tingkat Kehadiran & Cara Check-In</h2>
                <p class="text-xs text-text-muted">Statistik penonton yang hadir, tidak hadir, dan rincian metode validasi tiket pada periode ini.</p>
            </div>
            <span class="badge-accent-soft text-xs font-bold">{{ $period === 'harian' ? 'Hari Ini' : ucfirst($period) }}</span>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4">
                <p class="text-xs font-semibold text-emerald-800 dark:text-emerald-300">Telah Hadir</p>
                <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-tabular">
                    {{ (int) ($attendanceStats->hadir_people ?? 0) }} orang
                </p>
                <p class="text-[11px] text-text-muted mt-0.5">
                    {{ (int) ($attendanceStats->hadir_bookings ?? 0) }} booking terverifikasi
                </p>
            </div>

            <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-4">
                <p class="text-xs font-semibold text-amber-800 dark:text-amber-300">Belum Hadir</p>
                <p class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1 font-tabular">
                    {{ (int) ($attendanceStats->belum_hadir_people ?? 0) }} orang
                </p>
                <p class="text-[11px] text-text-muted mt-0.5">
                    {{ (int) ($attendanceStats->belum_hadir_bookings ?? 0) }} booking aktif menunggu kedatangan
                </p>
            </div>

            <div class="rounded-xl border border-red-500/30 bg-red-500/10 p-4">
                <p class="text-xs font-semibold text-red-800 dark:text-red-300">Tidak Hadir (No-Show)</p>
                <p class="text-2xl font-black text-red-600 dark:text-red-400 mt-1 font-tabular">
                    {{ (int) ($attendanceStats->tidak_hadir_people ?? 0) }} orang
                </p>
                <p class="text-[11px] text-text-muted mt-0.5">
                    {{ (int) ($attendanceStats->tidak_hadir_bookings ?? 0) }} booking ditandai tidak datang
                </p>
            </div>
        </div>

        <div class="pt-3 border-t border-border">
            <h3 class="text-xs font-bold uppercase tracking-wider text-text-muted mb-2">Cara Check-In</h3>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-xs">
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
                    <div class="p-2.5 rounded-xl border border-border bg-surface-2/40 flex flex-col justify-between">
                        <span class="text-text-muted font-medium">{{ $mData['label'] }}</span>
                        <span class="text-base font-bold text-text mt-1 font-tabular">{{ $mData['count'] }} tiket</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Program Member & Loyalitas (Bagian 4.7) --}}
    @if (isset($memberReport))
        <section class="mt-9 rounded-2xl border border-border bg-surface p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-border pb-3">
                <div>
                    <h2 class="font-bold text-text">Program Member & Loyalitas Kunjungan</h2>
                    <p class="text-xs text-text-muted mt-0.5">Statistik kehadiran member, reward yang ditebus, dan pengembalian dana pada periode ini.</p>
                </div>
                <a href="{{ route('admin.members.index') }}" class="text-xs font-bold text-accent hover:underline">
                    Kelola Member →
                </a>
            </div>

            <div class="grid gap-3 grid-cols-2 sm:grid-cols-4">
                <div class="rounded-xl border border-border bg-surface-2/60 p-4">
                    <p class="text-xs text-text-muted font-semibold">Total Member Aktif</p>
                    <p class="text-xl font-black text-text mt-1">{{ number_format($memberReport['active_members'], 0, ',', '.') }} orang</p>
                </div>
                <div class="rounded-xl border border-border bg-surface-2/60 p-4">
                    <p class="text-xs text-text-muted font-semibold">Kunjungan Sah Periode Ini</p>
                    <p class="text-xl font-black text-emerald-500 mt-1">{{ number_format($memberReport['visits_period'], 0, ',', '.') }} kali</p>
                </div>
                <div class="rounded-xl border border-border bg-surface-2/60 p-4">
                    <p class="text-xs text-text-muted font-semibold">Reward Diberikan</p>
                    <p class="text-xl font-black text-accent mt-1">{{ $memberReport['discount_rewards_count'] + $memberReport['free_ticket_rewards_count'] }}</p>
                    <p class="text-[10px] text-text-muted mt-0.5">{{ $memberReport['discount_rewards_count'] }} potongan · {{ $memberReport['free_ticket_rewards_count'] }} gratis tiket</p>
                </div>
                <div class="rounded-xl border border-border bg-surface-2/60 p-4">
                    <p class="text-xs text-text-muted font-semibold">Total Beban Reward</p>
                    <p class="text-xl font-black text-amber-500 mt-1">Rp {{ number_format($memberReport['total_reward_cost'], 0, ',', '.') }}</p>
                </div>
            </div>

            @if ($memberReport['pending_refund_cash'] > 0)
                <div class="rounded-xl border border-amber-500/40 bg-amber-500/10 p-4 text-xs font-semibold text-amber-600 dark:text-amber-400 flex items-center justify-between">
                    <span>Ada <strong>{{ $memberReport['pending_refund_cash'] }} pengembalian tunai reward member</strong> yang belum selesai diserahkan ke pelanggan.</span>
                    <a href="{{ route('admin.members.index') }}" class="font-bold underline">Lihat di Member</a>
                </div>
            @endif

            @if ($memberReport['top_members']->isNotEmpty())
                <div class="pt-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-text-muted mb-2">Member Paling Sering Berkunjung (Periode Ini)</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach ($memberReport['top_members'] as $topM)
                            <div class="rounded-xl border border-border bg-surface-2/40 p-3 flex items-center justify-between text-xs">
                                <div>
                                    <p class="font-bold text-text">{{ $topM->name }}</p>
                                    <p class="text-[10px] font-mono text-text-muted">{{ $topM->formattedCode() }}</p>
                                </div>
                                <span class="font-extrabold text-accent bg-surface px-2.5 py-1 rounded-lg border border-border">
                                    {{ $topM->valid_visits_count }} kunjungan
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    @endif
@endsection
