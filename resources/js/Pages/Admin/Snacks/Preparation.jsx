import { useState } from 'react';
import { Link, Head, useForm, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import {
    CheckSquare,
    ArrowLeft,
    Printer,
    Clock,
    Film,
    Calendar,
    Copy,
    Check,
    AlertCircle,
    User,
} from 'lucide-react';

export default function Preparation({
    session,
    aggregates = {},
}) {
    const [copiedCode, setCopiedCode] = useState(null);

    const bulkForm = useForm({
        status: 'baru',
    });

    const handleCopyCode = (code) => {
        navigator.clipboard.writeText(code);
        setCopiedCode(code);
        setTimeout(() => setCopiedCode(null), 2000);
    };

    const handleBulkStatusSubmit = (e) => {
        e.preventDefault();
        bulkForm.patch(`/admin/jajanan/sesi/${session.id}/status`, {
            preserveScroll: true,
        });
    };

    const handleUpdateItemStatus = (item, status) => {
        router.patch(`/admin/jajanan/pesanan/${item.id}/status`, { status }, {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout title="Siapkan Pesanan Jajanan">
            <Head title={`Siapkan Jajanan - Sesi ${session.session_date} | Admin SynemaX`} />

            <div className="space-y-6">
                {/* Header (Hidden in Print) */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between print:hidden">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-text-muted mb-1">
                            <Link href="/admin/jajanan/siapkan" className="hover:text-amber-500 transition-colors">
                                Daftar Siapkan
                            </Link>
                            <span>/</span>
                            <span className="text-text">Rincian Sesi</span>
                        </div>
                        <h1 className="text-2xl font-black tracking-tight text-text">
                            Siapkan Pesanan Jajanan
                        </h1>
                        <p className="text-xs text-text-muted mt-1 flex items-center gap-2">
                            <span>{session.session_date}</span>
                            <span>•</span>
                            <span>{session.start_time?.slice(0, 5)} WIB</span>
                            <span>•</span>
                            <span className="font-semibold text-text">{session.film?.title || 'Film'}</span>
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href="/admin/jajanan/siapkan"
                            className="inline-flex min-h-[42px] items-center gap-1.5 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-bold text-text hover:border-amber-500 transition shadow-xs"
                        >
                            <ArrowLeft className="size-4" />
                            <span>Kembali</span>
                        </Link>
                        <button
                            type="button"
                            onClick={() => window.print()}
                            className="inline-flex min-h-[42px] items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition shadow-xs"
                        >
                            <Printer className="size-4" />
                            <span>Cetak Daftar Dapur</span>
                        </button>
                    </div>
                </div>

                {/* Aggregated Item Counts (Kitchen Summary) */}
                <section className="rounded-2xl border border-border bg-surface p-5 shadow-xs">
                    <h2 className="text-sm font-bold text-text mb-3">
                        Total Jajanan yang Perlu Disiapkan
                    </h2>
                    <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        {Object.entries(aggregates).length > 0 ? (
                            Object.entries(aggregates).map(([name, data]) => (
                                <div
                                    key={name}
                                    className="flex items-center gap-3 rounded-xl border border-border bg-surface-2 p-3 text-xs"
                                >
                                    <span className="inline-flex min-w-[36px] h-8 items-center justify-center rounded-lg bg-surface font-mono font-bold text-amber-500 text-sm border border-border">
                                        {data.qty}×
                                    </span>
                                    <span className="font-bold text-text truncate">{name}</span>
                                </div>
                            ))
                        ) : (
                            <p className="text-xs text-text-muted">Belum ada jajan yang perlu disiapkan.</p>
                        )}
                    </div>
                </section>

                {/* Bulk Status Updater (Hidden in Print) */}
                <form
                    onSubmit={handleBulkStatusSubmit}
                    className="flex flex-wrap items-center gap-3 rounded-2xl border border-border bg-surface p-4 text-xs print:hidden shadow-xs"
                >
                    <span className="font-bold text-text">Ubah status semua item di sesi ini:</span>
                    <select
                        value={bulkForm.data.status}
                        onChange={(e) => bulkForm.setData('status', e.target.value)}
                        className="rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs font-bold text-text focus:border-amber-500 focus:outline-none"
                    >
                        <option value="baru">Baru</option>
                        <option value="disiapkan">Disiapkan</option>
                        <option value="siap">Siap</option>
                        <option value="selesai">Selesai</option>
                    </select>
                    <button
                        type="submit"
                        disabled={bulkForm.processing}
                        className="rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 disabled:opacity-50"
                    >
                        Terapkan Semua
                    </button>
                </form>

                {/* Per-Booking Breakdown */}
                <section className="space-y-4">
                    <h2 className="text-base font-bold text-text">Rincian Per Pemesan</h2>

                    {session.bookings?.map((booking) => {
                        if (!booking.items || booking.items.length === 0) return null;

                        return (
                            <article
                                key={booking.id}
                                className="rounded-2xl border border-border bg-surface overflow-hidden shadow-xs"
                            >
                                <header className="flex flex-wrap items-center justify-between gap-3 border-b border-border bg-surface-2/60 px-4 py-3 text-xs">
                                    <div className="flex items-center gap-2">
                                        <User className="size-4 text-amber-500" />
                                        <h3 className="font-bold text-text text-sm">{booking.customer_name}</h3>
                                        {booking.notes && (
                                            <span className="rounded-full bg-amber-500/15 border border-amber-500/30 px-2 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-400">
                                                Ada Catatan
                                            </span>
                                        )}
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <code className="font-mono text-xs font-bold text-amber-500 bg-surface px-2 py-1 rounded-md border border-border">
                                            {booking.code}
                                        </code>
                                        <button
                                            type="button"
                                            onClick={() => handleCopyCode(booking.code)}
                                            className="p-1 rounded text-text-muted hover:text-text print:hidden"
                                            title="Salin Kode"
                                        >
                                            {copiedCode === booking.code ? (
                                                <Check className="size-3.5 text-emerald-500" />
                                            ) : (
                                                <Copy className="size-3.5" />
                                            )}
                                        </button>
                                    </div>
                                </header>

                                {booking.notes && (
                                    <div className="px-4 py-2 bg-amber-500/5 border-b border-border text-xs text-amber-700 dark:text-amber-400 font-semibold">
                                        Catatan Pemesan: "{booking.notes}"
                                    </div>
                                )}

                                <div className="divide-y divide-border/60">
                                    {booking.items.map((item) => (
                                        <div
                                            key={item.id}
                                            className="p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
                                        >
                                            <div className="space-y-0.5">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-mono font-bold text-amber-500 bg-surface-2 px-1.5 py-0.5 rounded">
                                                        {item.qty}×
                                                    </span>
                                                    <span className="font-bold text-text">{item.name_snapshot}</span>
                                                </div>
                                                {item.note && (
                                                    <p className="text-[11px] text-text-muted italic">
                                                        "{item.note}"
                                                    </p>
                                                )}
                                            </div>

                                            <div className="flex items-center gap-2 print:hidden">
                                                {['baru', 'disiapkan', 'siap', 'selesai'].map((st) => (
                                                    <button
                                                        key={st}
                                                        type="button"
                                                        onClick={() => handleUpdateItemStatus(item, st)}
                                                        className={`px-2.5 py-1 rounded-lg text-[10px] font-bold capitalize transition ${
                                                            item.status === st
                                                                ? 'bg-amber-500 text-slate-950 shadow-xs'
                                                                : 'border border-border bg-surface-2 text-text-muted hover:text-text'
                                                        }`}
                                                    >
                                                        {st}
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </article>
                        );
                    })}
                </section>
            </div>
        </AdminLayout>
    );
}

