import { useState } from 'react';
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
    ShoppingBag,
    Printer,
    CheckCircle2,
    AlertTriangle,
    Info,
    Sparkles,
    Ticket,
    Maximize2,
    Minimize2
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
}) {
    const [density, setDensity] = useState('nyaman');

    const toggleDensity = () => {
        const next = density === 'nyaman' ? 'ringkas' : 'nyaman';
        setDensity(next);
        router.post('/admin/dashboard/density', { density: next }, { preserveState: true, preserveScroll: true });
    };

    const getActionBadge = (level) => {
        switch (level) {
            case 'critical':
                return {
                    bg: 'bg-rose-500/10 text-rose-500 border border-rose-500/30',
                    icon: AlertCircle,
                };
            case 'warning':
                return {
                    bg: 'bg-amber-500/10 text-amber-500 border border-amber-500/30',
                    icon: AlertTriangle,
                };
            default:
                return {
                    bg: 'bg-blue-500/10 text-blue-500 border border-blue-500/30',
                    icon: Info,
                };
        }
    };

    return (
        <AdminLayout title="Ringkasan Hari Ini">
            <Head title="Ringkasan Hari Ini · Admin" />

            <div className="space-y-6">
                {/* 1. Header & Quick Actions */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="inline-flex size-2 rounded-full bg-emerald-500 animate-pulse" />
                            <p className="text-xs font-bold uppercase tracking-wider text-text-muted">Operasional Bioskop</p>
                        </div>
                        <h1 className="mt-1 text-2xl sm:text-3xl font-black text-text tracking-tight">
                            Ringkasan Hari Ini
                        </h1>
                    </div>

                    <div className="flex flex-wrap items-center gap-2.5">
                        <Link
                            href="/admin/booking/pesan-di-tempat"
                            className="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 text-xs sm:text-sm font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 transition shadow-xs"
                        >
                            <Ticket className="size-4" />
                            <span>Loket Walk-in</span>
                        </Link>

                        <Link
                            href="/admin/scan"
                            className="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-border bg-surface px-4 text-xs sm:text-sm font-bold text-text hover:bg-surface-2 transition shadow-xs"
                        >
                            <QrCode className="size-4 text-amber-500" />
                            <span>Scan Tiket</span>
                        </Link>

                        <Link
                            href="/admin/jadwal"
                            className="btn-gold-cinema inline-flex min-h-[44px] items-center gap-2 rounded-xl px-4 text-xs sm:text-sm font-bold shadow-sm"
                        >
                            <Calendar className="size-4" />
                            <span>Kelola Sesi</span>
                        </Link>

                        <button
                            type="button"
                            onClick={toggleDensity}
                            title="Ubah kerapatan tampilan"
                            className="inline-flex size-11 items-center justify-center rounded-xl border border-border bg-surface text-text-muted hover:text-text hover:bg-surface-2 transition"
                        >
                            {density === 'nyaman' ? <Minimize2 className="size-4" /> : <Maximize2 className="size-4" />}
                        </button>
                    </div>
                </div>

                {/* 2. Top Stats Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs">
                        <div className="flex items-center justify-between text-text-muted">
                            <span className="text-xs font-bold uppercase tracking-wider">Okupansi Hari Ini</span>
                            <Users className="size-4 text-amber-500" />
                        </div>
                        <div className="mt-3 flex items-baseline gap-2">
                            <span className="text-3xl font-black text-text font-mono">{todayViewers}</span>
                            <span className="text-xs text-text-muted">/ {todayCapacity} kursi ({todayOccupancyPercent}%)</span>
                        </div>
                        <div className="mt-3 h-2 w-full rounded-full bg-surface-2 overflow-hidden">
                            <div
                                className="h-full bg-amber-500 rounded-full transition-all duration-500"
                                style={{ width: `${Math.min(100, todayOccupancyPercent)}%` }}
                            />
                        </div>
                    </div>

                    <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs">
                        <div className="flex items-center justify-between text-text-muted">
                            <span className="text-xs font-bold uppercase tracking-wider">Sesi Terjadwal</span>
                            <Film className="size-4 text-amber-500" />
                        </div>
                        <div className="mt-3 flex items-baseline gap-2">
                            <span className="text-3xl font-black text-text font-mono">{todaySessions.length}</span>
                            <span className="text-xs text-text-muted">sesi tayang</span>
                        </div>
                        <p className="mt-2 text-xs text-text-muted">
                            {todaySessions.filter((s) => s.occupied_seats > 0).length} sesi memiliki penonton
                        </p>
                    </div>

                    <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs">
                        <div className="flex items-center justify-between text-text-muted">
                            <span className="text-xs font-bold uppercase tracking-wider">Perlu Konfirmasi</span>
                            <Clock className="size-4 text-amber-500" />
                        </div>
                        <div className="mt-3 flex items-baseline gap-2">
                            <span className="text-3xl font-black text-text font-mono">{waitingBookings}</span>
                            <span className="text-xs text-text-muted">booking online</span>
                        </div>
                        <Link
                            href="/admin/booking?status=menunggu_konfirmasi"
                            className="mt-2 inline-flex items-center gap-1 text-xs font-bold text-amber-500 hover:underline"
                        >
                            <span>Periksa pembayaran</span>
                            <ChevronRight className="size-3" />
                        </Link>
                    </div>
                </div>

                {/* 3. Action Items (Perlu Tindakan) */}
                {actionItems.length > 0 && (
                    <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs space-y-3">
                        <div className="flex items-center justify-between border-b border-border pb-3">
                            <div className="flex items-center gap-2">
                                <AlertTriangle className="size-4 text-amber-500" />
                                <h2 className="text-sm font-black text-text">Persiapan Situs & Perlu Tindakan</h2>
                            </div>
                            <span className="rounded-full bg-amber-500/20 text-amber-600 dark:text-amber-400 font-mono text-xs font-black px-2.5 py-0.5">
                                {totalActionsCount}
                            </span>
                        </div>

                        <div className="grid gap-2">
                            {actionItems.map((act, i) => {
                                const badge = getActionBadge(act.level);
                                const Icon = badge.icon;
                                return (
                                    <Link
                                        key={i}
                                        href={act.url}
                                        className="group flex items-center justify-between p-3 rounded-xl border border-border/60 bg-surface-2/60 hover:bg-surface-2 hover:border-amber-500/50 transition-all text-xs"
                                    >
                                        <div className="flex items-center gap-3">
                                            <span className={`p-1.5 rounded-lg ${badge.bg}`}>
                                                <Icon className="size-3.5" />
                                            </span>
                                            <span className="font-bold text-text group-hover:text-amber-500 transition-colors">
                                                {act.title}
                                            </span>
                                        </div>
                                        <ChevronRight className="size-4 text-text-muted group-hover:text-amber-500 group-hover:translate-x-0.5 transition-all" />
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                )}

                {/* 4. Sesi Hari Ini Timeline */}
                <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs space-y-4">
                    <div className="flex items-center justify-between border-b border-border pb-3">
                        <h2 className="text-sm font-black text-text">Daftar Sesi Hari Ini</h2>
                        <span className="text-xs font-semibold text-text-muted">
                            {todaySessions.length} Sesi Terdaftar
                        </span>
                    </div>

                    {todaySessions.length === 0 ? (
                        <div className="py-8 text-center text-text-muted text-xs">
                            Tidak ada jadwal sesi penayangan untuk hari ini.
                        </div>
                    ) : (
                        <div className="divide-y divide-border">
                            {todaySessions.map((session) => {
                                const seatsReserved = session.viewer_count || session.occupied_seats || 0;
                                const maxCap = session.capacity || 6;
                                const isFull = seatsReserved >= maxCap;

                                return (
                                    <div
                                        key={session.id}
                                        className={`py-4 flex flex-col md:flex-row md:items-center justify-between gap-4 ${
                                            density === 'ringkas' ? 'py-2.5' : 'py-4'
                                        }`}
                                    >
                                        <div className="flex items-start gap-4">
                                            <div className="font-mono text-center shrink-0 w-20 py-1 px-2 rounded-xl bg-surface-2 border border-border">
                                                <div className="text-xs font-black text-text">{session.start_time?.slice(0, 5)}</div>
                                                <div className="text-[10px] text-text-muted">{session.end_time?.slice(0, 5)} WIB</div>
                                            </div>

                                            <div className="space-y-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <h3 className="font-black text-sm text-text">
                                                        {session.film?.title || 'Film belum ditentukan'}
                                                    </h3>
                                                    {session.is_private && (
                                                        <span className="rounded-md bg-amber-500/10 border border-amber-500/30 px-2 py-0.5 text-[10px] font-bold text-amber-500">
                                                            Privat
                                                        </span>
                                                    )}
                                                </div>
                                                <div className="flex items-center gap-3 text-xs text-text-muted">
                                                    <span>{seatsReserved}/{maxCap} Kursi</span>
                                                    <span>·</span>
                                                    <span>{session.status}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2 self-end md:self-center">
                                            <Link
                                                href={`/admin/jadwal/${session.id}`}
                                                className="btn-secondary-cinema px-3 py-1.5 rounded-lg text-xs font-bold"
                                            >
                                                Detail Sesi
                                            </Link>
                                            <Link
                                                href={`/admin/booking/pesan-di-tempat?session_id=${session.id}`}
                                                className="btn-gold-cinema px-3 py-1.5 rounded-lg text-xs font-bold"
                                            >
                                                Isi Loket
                                            </Link>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
