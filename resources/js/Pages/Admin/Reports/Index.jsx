import { useState } from 'react';
import { Link, Head, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import {
    DollarSign,
    Users,
    TrendingUp,
    Film,
    Coffee,
    Calendar,
    Download,
    Clock,
    Zap,
    CheckCircle2,
    AlertCircle,
    ChevronRight,
    ArrowUpRight
} from 'lucide-react';

export default function Index({
    period = 'harian',
    referenceDate = '',
    startDate = '',
    endDate = '',
    revenue = 0,
    ticketRevenue = 0,
    snackRevenue = 0,
    grossProfit = 0,
    snackProfit = 0,
    electricityCost = 0,
    viewers = 0,
    bookingCount = 0,
    paidSessionCount = 0,
    hourlyOccupancy = [],
    snackPopularity = [],
    filmPopularity = [],
    confirmedFilms = [],
    allDemandFilms = [],
    dailySummary = [],
}) {
    const [selectedPeriod, setSelectedPeriod] = useState(period);
    const [currentDate, setCurrentDate] = useState(referenceDate);

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(val || 0);
    };

    const handlePeriodChange = (p) => {
        setSelectedPeriod(p);
        router.get('/admin/laporan', { period: p, date: currentDate }, { preserveState: true });
    };

    const handleDateChange = (d) => {
        setCurrentDate(d);
        router.get('/admin/laporan', { period: selectedPeriod, date: d }, { preserveState: true });
    };

    return (
        <AdminLayout title="Laporan & Analisis">
            <Head title="Laporan & Analisis · Panel Admin" />

            <div className="space-y-6">
                {/* Header & Controls */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-black text-text tracking-tight">
                            Laporan & Statistik
                        </h1>
                        <p className="mt-1 text-xs sm:text-sm text-text-muted">
                            Ringkasan performa penjualan tiket, jajan, okupansi studio, dan laba kotor.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2.5">
                        {/* Period Pill Selector */}
                        <div className="inline-flex rounded-xl bg-surface border border-border p-1 shadow-xs">
                            {['harian', 'mingguan', 'bulanan'].map((p) => (
                                <button
                                    key={p}
                                    type="button"
                                    onClick={() => handlePeriodChange(p)}
                                    className={`px-3 py-1.5 rounded-lg text-xs font-bold capitalize transition ${
                                        selectedPeriod === p
                                            ? 'bg-amber-500 text-slate-950 shadow-xs'
                                            : 'text-text-muted hover:text-text'
                                    }`}
                                >
                                    {p}
                                </button>
                            ))}
                        </div>

                        {/* Date Picker Input */}
                        <input
                            type="date"
                            value={currentDate}
                            onChange={(e) => handleDateChange(e.target.value)}
                            aria-label="Pilih tanggal laporan"
                            className="input-cinema text-xs py-2 px-3 rounded-xl shadow-xs"
                        />

                        {/* Export Button */}
                        <a
                            href={`/admin/laporan/export?period=${selectedPeriod}&date=${currentDate}`}
                            className="inline-flex min-h-11 items-center gap-2 rounded-xl border border-border bg-surface px-4 text-xs font-bold text-text hover:bg-surface-2 transition shadow-xs"
                        >
                            <Download className="size-4 text-text-muted" />
                            <span>Ekspor CSV</span>
                        </a>
                    </div>
                </div>

                {/* Top Financial KPI Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs">
                        <div className="flex items-center justify-between text-text-muted">
                            <span className="text-xs font-bold uppercase tracking-wider">Total Pendapatan</span>
                            <DollarSign className="size-4 text-emerald-500" />
                        </div>
                        <div className="mt-2 text-2xl font-black text-text font-mono">
                            {formatRupiah(revenue)}
                        </div>
                        <div className="mt-2 flex items-center justify-between text-xs text-text-muted border-t border-border pt-2">
                            <span>Tiket: {formatRupiah(ticketRevenue)}</span>
                            <span>Jajan: {formatRupiah(snackRevenue)}</span>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs">
                        <div className="flex items-center justify-between text-text-muted">
                            <span className="text-xs font-bold uppercase tracking-wider">Estimasi Laba Kotor</span>
                            <TrendingUp className="size-4 text-amber-500" />
                        </div>
                        <div className="mt-2 text-2xl font-black text-amber-500 font-mono">
                            {formatRupiah(grossProfit)}
                        </div>
                        <div className="mt-2 flex items-center justify-between text-xs text-text-muted border-t border-border pt-2">
                            <span>Margin Jajan: {formatRupiah(snackProfit)}</span>
                            <span>Listrik: -{formatRupiah(electricityCost)}</span>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs">
                        <div className="flex items-center justify-between text-text-muted">
                            <span className="text-xs font-bold uppercase tracking-wider">Total Penonton</span>
                            <Users className="size-4 text-amber-500" />
                        </div>
                        <div className="mt-2 text-2xl font-black text-text font-mono">
                            {viewers} <span className="text-sm font-normal text-text-muted">orang</span>
                        </div>
                        <div className="mt-2 flex items-center justify-between text-xs text-text-muted border-t border-border pt-2">
                            <span>{bookingCount} transaksi booking</span>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs">
                        <div className="flex items-center justify-between text-text-muted">
                            <span className="text-xs font-bold uppercase tracking-wider">Sesi Terbayar</span>
                            <Film className="size-4 text-amber-500" />
                        </div>
                        <div className="mt-2 text-2xl font-black text-text font-mono">
                            {paidSessionCount} <span className="text-sm font-normal text-text-muted">sesi</span>
                        </div>
                        <div className="mt-2 flex items-center justify-between text-xs text-text-muted border-t border-border pt-2">
                            <span>Sesi berjalan terkonfirmasi</span>
                        </div>
                    </div>
                </div>

                {/* 4-Section Tidy Grid (Resolves User Request 4: "rapihkan halaman laporan"!) */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* 1. Okupansi Berdasarkan Jam */}
                    <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs space-y-4">
                        <div className="flex items-center justify-between border-b border-border pb-3">
                            <div className="flex items-center gap-2">
                                <Clock className="size-4 text-amber-500" />
                                <h2 className="text-sm font-black text-text">Okupansi Berdasarkan Jam</h2>
                            </div>
                            <span className="text-xs text-text-muted">Performa Jam Tayang</span>
                        </div>

                        {hourlyOccupancy && hourlyOccupancy.length > 0 ? (
                            <div className="space-y-3">
                                {hourlyOccupancy.map((item, idx) => (
                                    <div key={idx} className="space-y-1">
                                        <div className="flex items-center justify-between text-xs">
                                            <span className="font-mono font-bold text-text">{item.time}</span>
                                            <span className="text-text-muted">
                                                {item.seats} kursi ({item.percent}%)
                                            </span>
                                        </div>
                                        <div className="h-2 w-full rounded-full bg-surface-2 overflow-hidden">
                                            <div
                                                className="h-full bg-amber-500 rounded-full transition-all duration-500"
                                                style={{ width: `${Math.min(100, item.percent)}%` }}
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="py-8 text-center text-xs text-text-muted">
                                Belum ada sesi dengan okupansi pada periode ini.
                            </div>
                        )}
                    </div>

                    {/* 2. Jajan Paling Laris */}
                    <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs space-y-4">
                        <div className="flex items-center justify-between border-b border-border pb-3">
                            <div className="flex items-center gap-2">
                                <Coffee className="size-4 text-amber-500" />
                                <h2 className="text-sm font-black text-text">Jajan Paling Laris</h2>
                            </div>
                            <span className="text-xs text-text-muted">Top 10 Menu</span>
                        </div>

                        {snackPopularity && snackPopularity.length > 0 ? (
                            <div className="divide-y divide-border">
                                {snackPopularity.map((snack, idx) => (
                                    <div key={idx} className="py-2.5 flex items-center justify-between text-xs">
                                        <div className="flex items-center gap-3">
                                            <span className="size-5 rounded-full bg-surface-2 text-center font-mono font-bold text-text-muted flex items-center justify-center text-[10px]">
                                                {idx + 1}
                                            </span>
                                            <span className="font-bold text-text">{snack.name_snapshot}</span>
                                        </div>
                                        <span className="font-mono font-bold text-amber-500">
                                            {snack.quantity} terjual
                                        </span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="py-8 text-center text-xs text-text-muted">
                                Belum ada jajan terjual pada periode ini.
                            </div>
                        )}
                    </div>

                    {/* 3. Film Paling Sering Diminati */}
                    <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs space-y-4">
                        <div className="flex items-center justify-between border-b border-border pb-3">
                            <div className="flex items-center gap-2">
                                <Film className="size-4 text-amber-500" />
                                <h2 className="text-sm font-black text-text">Film Paling Sering Diminati</h2>
                            </div>
                            <span className="rounded bg-amber-500/10 border border-amber-500/30 px-2 py-0.5 text-[10px] font-bold text-amber-500 uppercase">
                                Semua Minat
                            </span>
                        </div>

                        {filmPopularity && filmPopularity.length > 0 ? (
                            <div className="divide-y divide-border">
                                {filmPopularity.map((item, idx) => (
                                    <div key={idx} className="py-2.5 flex items-center justify-between text-xs">
                                        <div className="flex items-center gap-3 min-w-0">
                                            <span className="size-5 rounded-full bg-surface-2 text-center font-mono font-bold text-text-muted flex items-center justify-center text-[10px] shrink-0">
                                                {idx + 1}
                                            </span>
                                            <span className="font-bold text-text truncate">
                                                {item.film?.title || item.title || 'Judul Film'}
                                            </span>
                                        </div>
                                        <span className="font-mono font-bold text-text text-right shrink-0">
                                            {item.booking_count} booking ({item.viewer_count} peminat)
                                        </span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="py-8 text-center text-xs text-text-muted">
                                Belum ada preferensi film terekam.
                            </div>
                        )}
                    </div>

                    {/* 4. Film Terkonfirmasi Ditonton */}
                    <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs space-y-4">
                        <div className="flex items-center justify-between border-b border-border pb-3">
                            <div className="flex items-center gap-2">
                                <CheckCircle2 className="size-4 text-emerald-500" />
                                <h2 className="text-sm font-black text-text">Film Terkonfirmasi Ditonton</h2>
                            </div>
                            <span className="text-xs text-text-muted">Tiket Pasti Tayang</span>
                        </div>

                        {confirmedFilms && confirmedFilms.length > 0 ? (
                            <div className="divide-y divide-border">
                                {confirmedFilms.map((item, idx) => (
                                    <div key={idx} className="py-2.5 flex items-center justify-between text-xs">
                                        <div className="flex items-center gap-3 min-w-0">
                                            <span className="size-5 rounded-full bg-surface-2 text-center font-mono font-bold text-emerald-500 flex items-center justify-center text-[10px] shrink-0">
                                                {idx + 1}
                                            </span>
                                            <span className="font-bold text-text truncate">
                                                {item.film?.title || item.title || 'Judul Film'}
                                            </span>
                                        </div>
                                        <span className="font-mono font-bold text-emerald-500 text-right shrink-0">
                                            {item.booking_count} booking ({item.viewer_count} kursi)
                                        </span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="py-8 text-center text-xs text-text-muted">
                                Belum ada film terkonfirmasi ditonton pada periode ini.
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
