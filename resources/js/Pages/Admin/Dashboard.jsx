import { useState, useEffect, useCallback } from 'react';
import { Link, Head, router } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
    Users,
    Film,
    Clock,
    AlertCircle,
    Calendar,
    ChevronRight,
    QrCode,
    Ticket,
    Maximize2,
    Minimize2,
    DollarSign,
    CheckCircle2,
    AlertTriangle,
    Info,
    RefreshCw,
    Utensils,
    Bot,
    MessageSquare,
    ExternalLink,
    ArrowUpRight,
    Phone,
    ShieldAlert,
} from 'lucide-react';

export default function Dashboard({
    todaySessions = [],
    todayViewers = 0,
    todayCapacity = 0,
    todayOccupancyPercent = 0,
    waitingBookings = 0,
    totalActionsCount = 0,
    actionItems = [],
    lowToday = [],
    lowTomorrow = [],
    lowUpcoming = [],
    distantCount = 0,
    hasAnyBookedLowAttendance = false,
    // Quick operational info props
    todayRevenue = 0,
    todayPaidCount = 0,
    recentBookings = [],
    unresolvedFeedbacksCount = 0,
    availableSnacksCount = 0,
    isSnacksEnabled = true,
    telegramCsBotEnabled = true,
    telegramCsBotUsername = 'SynemaXChat_bot',
    telegramAdminConfigured = false,
}) {
    const [density, setDensity] = useState('nyaman');
    const [isReloading, setIsReloading] = useState(false);

    const toggleDensity = () => {
        const next = density === 'nyaman' ? 'ringkas' : 'nyaman';
        setDensity(next);
        router.post('/admin/dashboard/density', { density: next }, { preserveState: true, preserveScroll: true });
    };

    const handleRefresh = useCallback(() => {
        setIsReloading(true);
        router.reload({
            preserveScroll: true,
            onFinish: () => setIsReloading(false),
        });
    }, []);

    // 15s auto-refresh when dashboard tab is active/visible
    useEffect(() => {
        const intervalId = setInterval(() => {
            if (document.visibilityState === 'visible') {
                handleRefresh();
            }
        }, 15000);

        const handleVisibilityChange = () => {
            if (document.visibilityState === 'visible') {
                handleRefresh();
            }
        };

        document.addEventListener('visibilitychange', handleVisibilityChange);
        return () => {
            clearInterval(intervalId);
            document.removeEventListener('visibilitychange', handleVisibilityChange);
        };
    }, [handleRefresh]);

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(val || 0);
    };

    const getActionBadge = (level) => {
        switch (level) {
            case 'critical':
                return {
                    bg: 'bg-rose-500/10 text-rose-500 border border-rose-500/30',
                    dot: 'bg-rose-500',
                    icon: AlertCircle,
                };
            case 'warning':
                return {
                    bg: 'bg-amber-500/10 text-amber-500 border border-amber-500/30',
                    dot: 'bg-amber-500',
                    icon: AlertTriangle,
                };
            default:
                return {
                    bg: 'bg-blue-500/10 text-blue-500 border border-blue-500/30',
                    dot: 'bg-blue-500',
                    icon: Info,
                };
        }
    };

    const getBookingStatusBadge = (status, hasProof) => {
        switch (status) {
            case 'terkonfirmasi':
                return (
                    <span className="inline-flex items-center gap-1 rounded-md bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
                        <CheckCircle2 className="size-2.5" />
                        Lunas
                    </span>
                );
            case 'menunggu_konfirmasi':
                return (
                    <span className="inline-flex items-center gap-1 rounded-md bg-amber-500/10 border border-amber-500/30 px-2 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-400">
                        <Clock className="size-2.5" />
                        {hasProof ? 'Bukti Ada' : 'Konfirmasi'}
                    </span>
                );
            case 'selesai':
            case 'hadir':
                return (
                    <span className="inline-flex items-center rounded-md bg-slate-500/10 border border-slate-500/20 px-2 py-0.5 text-[10px] font-bold text-slate-500">
                        Selesai
                    </span>
                );
            case 'dibatalkan':
                return (
                    <span className="inline-flex items-center rounded-md bg-rose-500/10 border border-rose-500/20 px-2 py-0.5 text-[10px] font-bold text-rose-500">
                        Batal
                    </span>
                );
            default:
                return (
                    <span className="inline-flex items-center rounded-md bg-surface-2 border border-border px-2 py-0.5 text-[10px] font-semibold text-text-muted capitalize">
                        {status?.replace('_', ' ') || 'Menunggu'}
                    </span>
                );
        }
    };

    return (
        <AdminLayout title="Ringkasan Hari Ini">
            <Head title="Ringkasan Hari Ini · Admin SynemaX" />

            <div className="space-y-6">
                {/* 1. Header & Quick Actions */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="inline-flex size-2 rounded-full bg-emerald-500 animate-pulse" />
                            <p className="text-xs font-bold uppercase tracking-wider text-text-muted">
                                Operasional Bioskop
                            </p>
                        </div>
                        <h1 className="mt-1 text-2xl sm:text-3xl font-black text-text tracking-tight">
                            Ringkasan Hari Ini
                        </h1>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        {/* Auto-refresh Button */}
                        <button
                            type="button"
                            onClick={handleRefresh}
                            disabled={isReloading}
                            title="Segarkan data terbaru"
                            className="inline-flex min-h-[42px] items-center gap-1.5 rounded-xl border border-border bg-surface px-3.5 text-xs font-bold text-text hover:bg-surface-2 transition shadow-xs cursor-pointer disabled:opacity-60"
                        >
                            <RefreshCw className={`size-3.5 text-amber-500 ${isReloading ? 'animate-spin' : ''}`} />
                            <span className="hidden sm:inline">Segarkan</span>
                        </button>

                        <Link
                            href="/admin/booking/pesan-di-tempat"
                            className="inline-flex min-h-[42px] items-center gap-2 rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 text-xs sm:text-sm font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 transition shadow-xs"
                        >
                            <Ticket className="size-4" />
                            <span>Loket Walk-in</span>
                        </Link>

                        <Link
                            href="/admin/scan"
                            className="inline-flex min-h-[42px] items-center gap-2 rounded-xl border border-border bg-surface px-3.5 text-xs sm:text-sm font-bold text-text hover:bg-surface-2 transition shadow-xs"
                        >
                            <QrCode className="size-4 text-amber-500" />
                            <span>Scan Tiket</span>
                        </Link>

                        <Link
                            href="/admin/jadwal"
                            className="btn-gold-cinema inline-flex min-h-[42px] items-center gap-2 rounded-xl px-4 text-xs sm:text-sm font-bold shadow-sm"
                        >
                            <Calendar className="size-4" />
                            <span>Kelola Sesi</span>
                        </Link>

                        <button
                            type="button"
                            onClick={toggleDensity}
                            title="Ubah kerapatan tampilan"
                            className="inline-flex size-[42px] items-center justify-center rounded-xl border border-border bg-surface text-text-muted hover:text-text hover:bg-surface-2 transition cursor-pointer"
                        >
                            {density === 'nyaman' ? <Minimize2 className="size-4" /> : <Maximize2 className="size-4" />}
                        </button>
                    </div>
                </div>

                {/* 2. Top Stats Overview: 4 Clean KPI Metric Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {/* Stat 1: Pendapatan Hari Ini */}
                    <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs transition hover:border-amber-500/30">
                        <div className="flex items-center justify-between text-text-muted">
                            <span className="text-xs font-bold uppercase tracking-wider">Pendapatan Hari Ini</span>
                            <div className="flex size-7 items-center justify-center rounded-lg bg-amber-500/10 text-amber-500">
                                <DollarSign className="size-4" />
                            </div>
                        </div>
                        <div className="mt-2.5 flex items-baseline gap-1">
                            <span className="text-2xl lg:text-3xl font-black text-text font-mono tracking-tight">
                                {formatRupiah(todayRevenue)}
                            </span>
                        </div>
                        <p className="mt-2 text-xs text-text-muted">
                            {todayPaidCount > 0 ? (
                                <span className="font-medium text-emerald-600 dark:text-emerald-400">
                                    {todayPaidCount} booking lunas hari ini
                                </span>
                            ) : (
                                'Belum ada transaksi lunas hari ini'
                            )}
                        </p>
                    </div>

                    {/* Stat 2: Okupansi Kursi */}
                    <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs transition hover:border-sky-500/30">
                        <div className="flex items-center justify-between text-text-muted">
                            <span className="text-xs font-bold uppercase tracking-wider">Okupansi Hari Ini</span>
                            <div className="flex size-7 items-center justify-center rounded-lg bg-sky-500/10 text-sky-500">
                                <Users className="size-4" />
                            </div>
                        </div>
                        <div className="mt-2.5 flex items-baseline gap-2">
                            <span className="text-2xl lg:text-3xl font-black text-text font-mono">
                                {todayViewers}
                            </span>
                            <span className="text-xs text-text-muted font-medium">
                                / {todayCapacity} kursi ({todayOccupancyPercent}%)
                            </span>
                        </div>
                        <div className="mt-3 h-2 w-full rounded-full bg-surface-2 overflow-hidden">
                            <div
                                className="h-full bg-sky-500 rounded-full transition-all duration-500"
                                style={{ width: `${Math.min(100, todayOccupancyPercent)}%` }}
                            />
                        </div>
                    </div>

                    {/* Stat 3: Sesi Terjadwal */}
                    <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs transition hover:border-emerald-500/30">
                        <div className="flex items-center justify-between text-text-muted">
                            <span className="text-xs font-bold uppercase tracking-wider">Sesi Terjadwal</span>
                            <div className="flex size-7 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-500">
                                <Film className="size-4" />
                            </div>
                        </div>
                        <div className="mt-2.5 flex items-baseline gap-2">
                            <span className="text-2xl lg:text-3xl font-black text-text font-mono">
                                {todaySessions.length}
                            </span>
                            <span className="text-xs text-text-muted font-medium">sesi tayang</span>
                        </div>
                        <p className="mt-2 text-xs text-text-muted">
                            {todaySessions.filter((s) => (s.viewer_count || s.occupied_seats || 0) > 0).length} sesi memiliki penonton
                        </p>
                    </div>

                    {/* Stat 4: Antrean Konfirmasi */}
                    <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs transition hover:border-amber-500/30">
                        <div className="flex items-center justify-between text-text-muted">
                            <span className="text-xs font-bold uppercase tracking-wider">Perlu Konfirmasi</span>
                            <div className={`flex size-7 items-center justify-center rounded-lg ${waitingBookings > 0 ? 'bg-amber-500/10 text-amber-500' : 'bg-surface-2 text-text-muted'}`}>
                                <Clock className="size-4" />
                            </div>
                        </div>
                        <div className="mt-2.5 flex items-baseline gap-2">
                            <span className="text-2xl lg:text-3xl font-black text-text font-mono">
                                {waitingBookings}
                            </span>
                            <span className="text-xs text-text-muted font-medium">booking online</span>
                        </div>
                        <div className="mt-2 flex items-center justify-between">
                            <Link
                                href="/admin/booking?status=menunggu_konfirmasi"
                                className="inline-flex items-center gap-1 text-xs font-bold text-amber-500 hover:underline"
                            >
                                <span>Periksa pembayaran</span>
                                <ChevronRight className="size-3" />
                            </Link>
                            {unresolvedFeedbacksCount > 0 && (
                                <Link
                                    href="/admin/bot-feedbacks"
                                    className="text-[10px] font-bold text-rose-500 hover:underline"
                                    title="Pesan/keluhan penonton perlu respon"
                                >
                                    {unresolvedFeedbacksCount} keluhan bot
                                </Link>
                            )}
                        </div>
                    </div>
                </div>

                {/* 3. Main Dashboard Workspace: 2-Column Asymmetric Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    {/* LEFT / MAIN COLUMN: Sesi Hari Ini & Antrean Booking Terbaru (lg:col-span-8) */}
                    <div className="lg:col-span-8 space-y-6">
                        {/* Section A: Sesi Tayang Hari Ini */}
                        <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs space-y-4">
                            <div className="flex items-center justify-between border-b border-border pb-3">
                                <div className="flex items-center gap-2">
                                    <Film className="size-4 text-amber-500" />
                                    <h2 className="text-sm font-black text-text">Daftar Sesi Hari Ini</h2>
                                </div>
                                <span className="rounded-full bg-surface-2 border border-border px-2.5 py-0.5 text-xs font-bold text-text-muted">
                                    {todaySessions.length} Sesi Terjadwal
                                </span>
                            </div>

                            {todaySessions.length === 0 ? (
                                <div className="py-10 text-center space-y-3">
                                    <Calendar className="size-8 mx-auto text-text-muted/60" />
                                    <p className="text-xs text-text-muted">
                                        Tidak ada jadwal sesi penayangan untuk hari ini.
                                    </p>
                                    <Link
                                        href="/admin/jadwal"
                                        className="inline-flex items-center gap-1.5 text-xs font-bold text-amber-500 hover:underline"
                                    >
                                        <span>+ Buka Jadwal Tayang</span>
                                        <ChevronRight className="size-3" />
                                    </Link>
                                </div>
                            ) : (
                                <div className="divide-y divide-border">
                                    {todaySessions.map((session) => {
                                        const seatsReserved = session.viewer_count || session.occupied_seats || 0;
                                        const maxCap = session.capacity || session.seat_capacity || 6;
                                        const occPercent = maxCap > 0 ? Math.round((seatsReserved / maxCap) * 100) : 0;
                                        const isLow = !session.is_private && seatsReserved > 0 && seatsReserved < (session.minimum_viewers || 4);

                                        return (
                                            <div
                                                key={session.id}
                                                className={`flex flex-col md:flex-row md:items-center justify-between gap-4 transition hover:bg-surface-2/40 rounded-xl px-2.5 ${
                                                    density === 'ringkas' ? 'py-2.5' : 'py-3.5'
                                                }`}
                                            >
                                                {/* Left: Time & Film Info */}
                                                <div className="flex items-start gap-3.5 min-w-0">
                                                    <div className="font-mono text-center shrink-0 w-20 py-1.5 px-2 rounded-xl bg-surface-2 border border-border">
                                                        <div className="text-xs font-black text-text">
                                                            {session.start_time?.slice(0, 5)}
                                                        </div>
                                                        <div className="text-[10px] text-text-muted">
                                                            {session.end_time?.slice(0, 5)} WIB
                                                        </div>
                                                    </div>

                                                    <div className="space-y-1 min-w-0">
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <h3 className="font-black text-sm text-text truncate">
                                                                {session.film?.title || 'Film belum ditentukan'}
                                                            </h3>
                                                            {session.is_private ? (
                                                                <span className="rounded-md bg-amber-500/10 border border-amber-500/30 px-2 py-0.5 text-[10px] font-bold text-amber-500">
                                                                    Privat
                                                                </span>
                                                            ) : (
                                                                <span className="rounded-md bg-slate-500/10 border border-slate-500/20 px-2 py-0.5 text-[10px] font-bold text-text-muted">
                                                                    Reguler
                                                                </span>
                                                            )}
                                                            {session.film?.age_rating?.code && (
                                                                <span className="rounded-md bg-surface-2 border border-border px-1.5 py-0.2 text-[9px] font-mono text-text-muted">
                                                                    {session.film.age_rating.code}
                                                                </span>
                                                            )}
                                                        </div>

                                                        {/* Occupancy and Quorum Details */}
                                                        <div className="flex flex-wrap items-center gap-2.5 text-xs text-text-muted">
                                                            <span className="font-semibold text-text">
                                                                {seatsReserved}/{maxCap} Kursi ({occPercent}%)
                                                            </span>
                                                            {isLow && (
                                                                <span className="inline-flex items-center gap-1 rounded bg-amber-500/10 text-amber-600 dark:text-amber-400 text-[10px] font-semibold px-1.5 py-0.5">
                                                                    Butuh penonton tambahan
                                                                </span>
                                                            )}
                                                            <span className="text-[10px] text-text-muted capitalize">
                                                                &bull; {session.status}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Right: Quick Action Buttons */}
                                                <div className="flex items-center gap-2 shrink-0 self-end md:self-center">
                                                    <Link
                                                        href={`/admin/booking/pesan-di-tempat?session_id=${session.id}`}
                                                        className="btn-gold-cinema px-3 py-1.5 rounded-lg text-xs font-bold inline-flex items-center gap-1.5"
                                                    >
                                                        <Ticket className="size-3.5" />
                                                        <span>Loket Walk-in</span>
                                                    </Link>
                                                    <Link
                                                        href={`/admin/jadwal/${session.id}`}
                                                        className="btn-secondary-cinema px-3 py-1.5 rounded-lg text-xs font-bold"
                                                    >
                                                        Detail
                                                    </Link>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>

                        {/* Section B: Pesanan & Booking Terbaru (Quick Orders Feed) */}
                        <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs space-y-4">
                            <div className="flex items-center justify-between border-b border-border pb-3">
                                <div className="flex items-center gap-2">
                                    <Clock className="size-4 text-amber-500" />
                                    <h2 className="text-sm font-black text-text">Pesanan & Booking Terbaru</h2>
                                </div>
                                <Link
                                    href="/admin/booking"
                                    className="text-xs font-bold text-amber-500 hover:underline inline-flex items-center gap-1"
                                >
                                    <span>Kelola Semua Booking</span>
                                    <ChevronRight className="size-3" />
                                </Link>
                            </div>

                            {recentBookings.length === 0 ? (
                                <div className="py-8 text-center text-xs text-text-muted">
                                    Belum ada transaksi pemesanan tiket masuk saat ini.
                                </div>
                            ) : (
                                <div className="divide-y divide-border">
                                    {recentBookings.map((b) => (
                                        <div
                                            key={b.id}
                                            className="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs transition hover:bg-surface-2/40 rounded-xl px-2.5"
                                        >
                                            <div className="space-y-1 min-w-0">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <Link
                                                        href={`/admin/booking?search=${encodeURIComponent(b.code)}`}
                                                        className="font-mono font-black text-text hover:text-amber-500 transition-colors"
                                                    >
                                                        {b.code}
                                                    </Link>
                                                    {getBookingStatusBadge(b.status, b.has_proof)}
                                                    <span className="text-[10px] text-text-muted">
                                                        {b.created_at_human}
                                                    </span>
                                                </div>
                                                <div className="flex flex-wrap items-center gap-2 text-text-muted text-[11px]">
                                                    <span className="font-semibold text-text">
                                                        {b.customer_name}
                                                    </span>
                                                    <span>&bull;</span>
                                                    <span>{b.film_title}</span>
                                                    <span>&bull;</span>
                                                    <span>{b.session_time} WIB</span>
                                                    <span>&bull;</span>
                                                    <span className="capitalize">{b.seats_reserved} Kursi ({b.package})</span>
                                                </div>
                                            </div>

                                            <div className="flex items-center justify-between sm:justify-end gap-3 shrink-0">
                                                <div className="text-right">
                                                    <div className="font-mono font-bold text-text">
                                                        {formatRupiah(b.total_price)}
                                                    </div>
                                                    <div className="text-[10px] text-text-muted uppercase font-mono">
                                                        {b.payment_method}
                                                    </div>
                                                </div>

                                                <Link
                                                    href={`/admin/booking?search=${encodeURIComponent(b.code)}`}
                                                    className="p-1.5 rounded-lg border border-border bg-surface-2 text-text hover:text-amber-500 hover:border-amber-500/40 transition"
                                                    title="Buka rincian booking"
                                                >
                                                    <ChevronRight className="size-4" />
                                                </Link>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* RIGHT / SIDEBAR COLUMN: Compact Action Items & Operational Hub (lg:col-span-4) */}
                    <div className="lg:col-span-4 space-y-6">
                        {/* Widget 1: Persiapan & Tindakan (Compact, Clean & Non-redundant) */}
                        <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs space-y-3.5">
                            <div className="flex items-center justify-between border-b border-border pb-3">
                                <div className="flex items-center gap-2">
                                    <ShieldAlert className="size-4 text-amber-500" />
                                    <h2 className="text-sm font-black text-text">
                                        Perhatian & Tindakan
                                    </h2>
                                </div>
                                <span className={`rounded-full font-mono text-xs font-black px-2.5 py-0.5 ${
                                    totalActionsCount > 0 ? 'bg-amber-500/20 text-amber-600 dark:text-amber-400' : 'bg-surface-2 text-text-muted'
                                }`}>
                                    {totalActionsCount}
                                </span>
                            </div>

                            {actionItems.length === 0 ? (
                                <div className="py-4 px-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-start gap-2.5">
                                    <CheckCircle2 className="size-4 text-emerald-500 shrink-0 mt-0.5" />
                                    <div className="text-xs">
                                        <p className="font-bold text-emerald-600 dark:text-emerald-400">
                                            Semua Sistem Siap
                                        </p>
                                        <p className="text-[11px] text-text-muted mt-0.5 leading-relaxed">
                                            Tidak ada antrean pembayaran kedaluwarsa atau kendala konfigurasi yang mendesak.
                                        </p>
                                    </div>
                                </div>
                            ) : (
                                <div className="space-y-2">
                                    {actionItems.map((act, i) => {
                                        const badge = getActionBadge(act.level);
                                        const Icon = badge.icon;
                                        return (
                                            <Link
                                                key={i}
                                                href={act.url}
                                                className="group flex items-center justify-between p-2.5 rounded-xl border border-border/70 bg-surface-2/60 hover:bg-surface-2 hover:border-amber-500/40 transition-all text-xs"
                                            >
                                                <div className="flex items-start gap-2.5 min-w-0 pr-2">
                                                    <span className={`p-1.5 rounded-lg shrink-0 mt-0.5 ${badge.bg}`}>
                                                        <Icon className="size-3.5" />
                                                    </span>
                                                    <span className="font-bold text-text group-hover:text-amber-500 transition-colors leading-snug">
                                                        {act.title}
                                                    </span>
                                                </div>
                                                <ChevronRight className="size-4 text-text-muted group-hover:text-amber-500 group-hover:translate-x-0.5 transition-all shrink-0" />
                                            </Link>
                                        );
                                    })}
                                </div>
                            )}
                        </div>

                        {/* Widget 2: Status Layanan & Integrasi Bioskop */}
                        <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs space-y-3.5">
                            <div className="flex items-center justify-between border-b border-border pb-3">
                                <div className="flex items-center gap-2">
                                    <Bot className="size-4 text-amber-500" />
                                    <h2 className="text-sm font-black text-text">Status Layanan Bioskop</h2>
                                </div>
                            </div>

                            <div className="space-y-2.5 text-xs">
                                {/* Bot Telegram CS */}
                                <div className="flex items-center justify-between p-2.5 rounded-xl bg-surface-2 border border-border">
                                    <div className="flex items-center gap-2">
                                        <span className="size-2 rounded-full bg-emerald-500" />
                                        <span className="font-semibold text-text">Bot CS 24 Jam</span>
                                    </div>
                                    <a
                                        href={`https://t.me/${telegramCsBotUsername}`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-[11px] font-mono text-sky-500 hover:underline inline-flex items-center gap-1"
                                    >
                                        <span>@{telegramCsBotUsername}</span>
                                        <ExternalLink className="size-2.5" />
                                    </a>
                                </div>

                                {/* Bot Admin Notification */}
                                <div className="flex items-center justify-between p-2.5 rounded-xl bg-surface-2 border border-border">
                                    <div className="flex items-center gap-2">
                                        <span className={`size-2 rounded-full ${telegramAdminConfigured ? 'bg-emerald-500' : 'bg-amber-500'}`} />
                                        <span className="font-semibold text-text">Bot Notifikasi Admin</span>
                                    </div>
                                    <span className="text-[11px] text-text-muted font-medium">
                                        {telegramAdminConfigured ? 'Terhubung' : 'Belum Mulai'}
                                    </span>
                                </div>

                                {/* Keluhan & Feedback */}
                                <div className="flex items-center justify-between p-2.5 rounded-xl bg-surface-2 border border-border">
                                    <div className="flex items-center gap-2">
                                        <MessageSquare className="size-3.5 text-amber-500" />
                                        <span className="font-semibold text-text">Pusat Keluhan Bot</span>
                                    </div>
                                    <Link
                                        href="/admin/bot-feedbacks"
                                        className="text-[11px] font-bold text-amber-500 hover:underline"
                                    >
                                        {unresolvedFeedbacksCount > 0 ? (
                                            <span className="text-rose-500">{unresolvedFeedbacksCount} perlu respon</span>
                                        ) : (
                                            'Terkendali'
                                        )}
                                    </Link>
                                </div>

                                {/* Snack Menu */}
                                <div className="flex items-center justify-between p-2.5 rounded-xl bg-surface-2 border border-border">
                                    <div className="flex items-center gap-2">
                                        <Utensils className="size-3.5 text-amber-500" />
                                        <span className="font-semibold text-text">Menu Camilan</span>
                                    </div>
                                    <Link
                                        href="/admin/snack-menu"
                                        className="text-[11px] font-bold text-text hover:text-amber-500"
                                    >
                                        {availableSnacksCount} Menu Tersedia
                                    </Link>
                                </div>
                            </div>
                        </div>

                        {/* Widget 3: Quick Action Tiles */}
                        <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs space-y-3">
                            <h2 className="text-sm font-black text-text border-b border-border pb-2.5">
                                Akses Cepat Operasional
                            </h2>
                            <div className="grid grid-cols-2 gap-2">
                                <Link
                                    href="/admin/booking/pesan-di-tempat"
                                    className="p-3 rounded-xl border border-border bg-surface-2 hover:bg-surface hover:border-amber-500/40 transition text-center group cursor-pointer"
                                >
                                    <Ticket className="size-5 mx-auto text-amber-500 group-hover:scale-110 transition-transform" />
                                    <span className="mt-1.5 block text-xs font-bold text-text">
                                        Pesan Loket
                                    </span>
                                </Link>

                                <Link
                                    href="/admin/scan"
                                    className="p-3 rounded-xl border border-border bg-surface-2 hover:bg-surface hover:border-amber-500/40 transition text-center group cursor-pointer"
                                >
                                    <QrCode className="size-5 mx-auto text-sky-500 group-hover:scale-110 transition-transform" />
                                    <span className="mt-1.5 block text-xs font-bold text-text">
                                        Scan Tiket
                                    </span>
                                </Link>

                                <Link
                                    href="/admin/members/scan"
                                    className="p-3 rounded-xl border border-border bg-surface-2 hover:bg-surface hover:border-amber-500/40 transition text-center group cursor-pointer"
                                >
                                    <Users className="size-5 mx-auto text-emerald-500 group-hover:scale-110 transition-transform" />
                                    <span className="mt-1.5 block text-xs font-bold text-text">
                                        Scan Member
                                    </span>
                                </Link>

                                <Link
                                    href="/admin/bot-feedbacks"
                                    className="p-3 rounded-xl border border-border bg-surface-2 hover:bg-surface hover:border-amber-500/40 transition text-center group cursor-pointer"
                                >
                                    <Bot className="size-5 mx-auto text-purple-500 group-hover:scale-110 transition-transform" />
                                    <span className="mt-1.5 block text-xs font-bold text-text">
                                        Keluhan Bot
                                    </span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
