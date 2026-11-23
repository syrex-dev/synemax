import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Modal from '@/Components/Modal';
import EditBookingModal from '@/Components/EditBookingModal';
import { 
    ArrowLeft, 
    Copy, 
    Check, 
    ExternalLink, 
    MessageSquare, 
    Calendar, 
    Clock, 
    Film as FilmIcon, 
    Users, 
    Edit, 
    Plus, 
    Minus, 
    Trash2, 
    CheckCircle2, 
    XCircle, 
    ChevronDown, 
    History,
    QrCode,
    AlertTriangle
} from 'lucide-react';

export default function Show({ 
    booking, 
    categories = [], 
    snackMaxQty = 10, 
    snackChangeLogs = [], 
    snackUpdateWhatsAppUrl = null 
}) {
    const [copiedCode, setCopiedCode] = useState(false);
    const [editorOpen, setEditorOpen] = useState(false);
    const [historyOpen, setHistoryOpen] = useState(false);
    const [checkinLogsOpen, setCheckinLogsOpen] = useState(false);
    const [editModalOpen, setEditModalOpen] = useState(false);
    
    // Concession editor state
    const initialItems = {};
    if (booking?.items) {
        booking.items.forEach(item => {
            if (item.menu_item_id) {
                initialItems[item.menu_item_id] = {
                    qty: item.qty,
                    note: item.note || ''
                };
            }
        });
    }
    const [selectedSnacks, setSelectedSnacks] = useState(initialItems);
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);
    const [confirmModal, setConfirmModal] = useState({
        show: false,
        title: '',
        message: '',
        confirmText: 'Lanjutkan',
        cancelText: 'Batal',
        variant: 'primary',
        onConfirm: () => {},
    });

    const copyToClipboard = (code) => {
        navigator.clipboard.writeText(code);
        setCopiedCode(true);
        setTimeout(() => setCopiedCode(false), 2000);
    };

    const formatRupiah = (val) => {
        return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
    };

    const handleStatusAction = (action, confirmMsg) => {
        if (!confirmMsg) {
            router.patch(`/admin/booking/${booking.id}/status`, { action });
            return;
        }

        const isDanger = action === 'batal';
        const isWarning = action === 'tidak_hadir';
        const actionTitle = action === 'batal'
            ? 'Batalkan Booking?'
            : action === 'tandai_dibayar'
            ? 'Konfirmasi Pembayaran?'
            : action === 'tidak_hadir'
            ? 'Tandai Tidak Hadir?'
            : 'Konfirmasi Aksi';

        const confirmButtonText = action === 'batal'
            ? 'Ya, Batalkan'
            : action === 'tandai_dibayar'
            ? 'Konfirmasi Pembayaran'
            : action === 'tidak_hadir'
            ? 'Tandai Tidak Hadir'
            : 'Lanjutkan';

        setConfirmModal({
            show: true,
            title: actionTitle,
            message: confirmMsg,
            confirmText: confirmButtonText,
            cancelText: 'Batal',
            variant: isDanger ? 'danger' : isWarning ? 'warning' : 'primary',
            onConfirm: () => {
                router.patch(`/admin/booking/${booking.id}/status`, { action });
                setConfirmModal(prev => ({ ...prev, show: false }));
            },
        });
    };

    const handleSaveSnacks = (e) => {
        e.preventDefault();
        router.patch(`/admin/booking/${booking.id}/jajan`, {
            snacks: selectedSnacks
        }, {
            onSuccess: () => setEditorOpen(false)
        });
    };

    const updateQty = (itemId, delta) => {
        setSelectedSnacks(prev => {
            const current = prev[itemId] || { qty: 0, note: '' };
            const nextQty = Math.max(0, Math.min(snackMaxQty, current.qty + delta));
            if (nextQty === 0) {
                const next = { ...prev };
                delete next[itemId];
                return next;
            }
            return {
                ...prev,
                [itemId]: { ...current, qty: nextQty }
            };
        });
    };

    const updateNote = (itemId, note) => {
        setSelectedSnacks(prev => {
            if (!prev[itemId]) return prev;
            return {
                ...prev,
                [itemId]: { ...prev[itemId], note }
            };
        });
    };

    const statusBadge = (st) => {
        const map = {
            menunggu_pembayaran: { label: 'Menunggu pembayaran', bg: 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30' },
            menunggu_konfirmasi: { label: 'Menunggu konfirmasi', bg: 'bg-blue-500/15 text-blue-600 dark:text-blue-400 border-blue-500/30' },
            terkonfirmasi: { label: 'Terkonfirmasi', bg: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30' },
            kedaluwarsa: { label: 'Kedaluwarsa', bg: 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/30' },
            hadir: { label: 'Hadir', bg: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30' },
            tidak_hadir: { label: 'Tidak hadir', bg: 'bg-neutral-500/15 text-neutral-600 dark:text-neutral-400 border-neutral-500/30' },
            dibatalkan: { label: 'Dibatalkan', bg: 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/30' },
        };
        const cur = map[st] || { label: st, bg: 'bg-surface-2 text-text-muted border-border' };
        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border ${cur.bg}`}>
                {cur.label}
            </span>
        );
    };

    const cleanPhone = (booking.whatsapp || '').replace(/\D+/g, '');
    const whatsAppMsg = encodeURIComponent(
        `Halo ${booking.customer_name}, admin SynemaX menghubungi terkait booking ${booking.code}.` +
        (booking.notes ? `\nCatatan pemesan: ${booking.notes}` : '')
    );
    const waUrl = cleanPhone ? `https://wa.me/${cleanPhone.startsWith('0') ? '62' + cleanPhone.slice(1) : cleanPhone}?text=${whatsAppMsg}` : null;

    return (
        <AdminLayout title={`Detail Booking - ${booking.customer_name}`}>
            <Head title={`Detail Booking - ${booking.customer_name} - Admin SynemaX`} />

            <div className="space-y-6">
                {/* Breadcrumb */}
                <div className="flex items-center justify-between text-xs sm:text-sm">
                    <Link
                        href="/admin/booking"
                        className="inline-flex items-center gap-1.5 font-bold text-amber-600 dark:text-amber-400 hover:underline"
                    >
                        <ArrowLeft className="size-4" />
                        <span>Kembali ke Kelola Booking</span>
                    </Link>
                    <span className="text-text-muted">ID Booking #{booking.id}</span>
                </div>

                {/* Header Card */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-sm">
                    <div className="space-y-1.5 min-w-0">
                        <p className="text-xs font-bold uppercase tracking-wider text-text-muted">Detail Reservasi Pelanggan</p>
                        <h1 className="text-2xl sm:text-3xl font-black text-text tracking-tight truncate">
                            {booking.customer_name}
                        </h1>
                        <div className="flex flex-wrap items-center gap-2 pt-1 text-xs">
                            <button
                                type="button"
                                onClick={() => copyToClipboard(booking.code)}
                                className="inline-flex items-center gap-1.5 rounded-lg border border-border bg-surface-2 px-2.5 py-1 font-mono font-bold text-text hover:border-amber-400 transition"
                                title="Salin kode booking"
                            >
                                <span>{booking.code}</span>
                                {copiedCode ? <Check className="size-3.5 text-emerald-500" /> : <Copy className="size-3.5 text-text-muted" />}
                            </button>

                            {statusBadge(booking.status)}

                            {booking.payment_proof_path ? (
                                <span className="rounded-full bg-emerald-500/15 border border-emerald-500/30 px-2.5 py-0.5 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                    Bukti Terunggah
                                </span>
                            ) : (booking.payment_method !== 'di_tempat' && booking.status === 'menunggu_pembayaran') ? (
                                <span className="rounded-full bg-amber-500/15 border border-amber-500/30 px-2.5 py-0.5 text-xs font-semibold text-amber-600 dark:text-amber-400">
                                    Belum Ada Bukti
                                </span>
                            ) : null}

                            {booking.customer_profile?.no_show_count >= 2 && (
                                <span className="rounded-full bg-rose-500/15 border border-rose-500/30 px-2.5 py-0.5 text-xs font-semibold text-rose-600 dark:text-rose-400">
                                    Peringatan Tidak Datang {booking.customer_profile.no_show_count}×
                                </span>
                            )}

                            {(booking.notes || (booking.items && booking.items.some(i => i.note))) && (
                                <span className="rounded-full bg-amber-500/15 border border-amber-500/30 px-2.5 py-0.5 text-xs font-bold text-amber-600 dark:text-amber-400">
                                    Ada Catatan
                                </span>
                            )}
                        </div>
                    </div>

                    {/* Contextual Action */}
                    <div className="flex flex-wrap items-center gap-2.5 shrink-0">
                        {booking.status === 'menunggu_pembayaran' && (
                            <button
                                type="button"
                                onClick={() => handleStatusAction('tandai_dibayar', 'Konfirmasi pembayaran tunai untuk booking ini?')}
                                className="inline-flex min-h-11 items-center justify-center rounded-xl bg-amber-500 px-5 text-xs sm:text-sm font-bold text-slate-950 hover:bg-amber-400 transition shadow-sm"
                            >
                                Konfirmasi Pembayaran
                            </button>
                        )}
                        {booking.status === 'menunggu_konfirmasi' && (
                            <button
                                type="button"
                                onClick={() => handleStatusAction('konfirmasi')}
                                className="inline-flex min-h-11 items-center justify-center rounded-xl bg-amber-500 px-5 text-xs sm:text-sm font-bold text-slate-950 hover:bg-amber-400 transition shadow-sm"
                            >
                                Konfirmasi Booking
                            </button>
                        )}
                        {booking.status === 'terkonfirmasi' && (
                            <Link
                                href={`/admin/scan?sesi=${booking.session_id}`}
                                className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-amber-500 px-5 text-xs sm:text-sm font-bold text-slate-950 hover:bg-amber-400 transition shadow-sm"
                            >
                                <QrCode className="size-4" />
                                <span>Scan tiket</span>
                            </Link>
                        )}

                        {/* Ubah Booking */}
                        <button
                            type="button"
                            onClick={() => setEditModalOpen(true)}
                            className="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-xl border border-amber-500/40 bg-amber-500/10 px-3.5 text-xs sm:text-sm font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-500 hover:text-slate-950 active:scale-95 transition shadow-xs"
                            title="Ubah data reservasi pelanggan"
                        >
                            <Edit className="size-4" />
                            <span>Ubah Booking</span>
                        </button>

                        {/* Hapus Booking */}
                        <button
                            type="button"
                            onClick={() => setDeleteModalOpen(true)}
                            className="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-xl border border-rose-500/40 bg-rose-500/10 px-3.5 text-xs sm:text-sm font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-600 hover:text-white active:scale-95 transition shadow-xs"
                            title="Hapus data booking ini secara permanen"
                        >
                            <Trash2 className="size-4" />
                            <span className="hidden sm:inline">Hapus Booking</span>
                        </button>
                    </div>
                </div>

                {/* Attendance Banner */}
                {(booking.status === 'hadir' || booking.checked_in_at) && (
                    <div className="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-xs text-emerald-800 dark:text-emerald-300 flex items-center gap-3">
                        <CheckCircle2 className="size-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                        <div>
                            <p className="font-bold text-emerald-900 dark:text-emerald-200">Telah Hadir di Studio</p>
                            <p className="text-[11px] text-emerald-700 dark:text-emerald-400 mt-0.5">
                                Tiket telah diverifikasi dan dicatat hadir.
                            </p>
                        </div>
                    </div>
                )}

                {/* 2-Column Layout */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    {/* Left Column (8 cols): Film, Snacks, Audit */}
                    <div className="lg:col-span-8 space-y-6">
                        {/* Film & Schedule Card */}
                        <div className="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-sm space-y-4">
                            <div className="flex items-center justify-between pb-3 border-b border-border">
                                <h2 className="text-sm font-bold uppercase tracking-wider text-text-muted">Informasi Film & Jadwal Tayang</h2>
                                <span className="text-xs font-semibold text-text-muted">Studio 1</span>
                            </div>

                            <div className="flex flex-col sm:flex-row gap-4">
                                {booking.session?.film?.poster_url && (
                                    <img
                                        src={booking.session.film.poster_url}
                                        alt={booking.session.film.title}
                                        className="h-28 w-20 rounded-xl object-cover shrink-0 border border-border"
                                    />
                                )}

                                <div className="space-y-2 min-w-0 flex-1">
                                    <h3 className="text-lg font-bold text-text truncate">
                                        {booking.session?.film?.title || booking.film?.title || 'Judul Film'}
                                    </h3>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                        <div>
                                            <span className="text-text-muted">Jadwal Tayang:</span>
                                            <p className="font-bold text-text mt-0.5">
                                                {booking.session?.session_date || '-'}
                                            </p>
                                        </div>
                                        <div>
                                            <span className="text-text-muted">Jam Sesi:</span>
                                            <p className="font-bold text-text mt-0.5">
                                                {booking.session?.start_time ? `${booking.session.start_time.slice(0, 5)} WIB` : '-'}
                                            </p>
                                        </div>
                                        <div>
                                            <span className="text-text-muted">Tipe Paket:</span>
                                            <p className="font-bold text-text mt-0.5">
                                                {booking.booking_type === 'private' ? 'Sewa Private (6 kursi)' : `Reguler (${booking.pax} orang)`}
                                            </p>
                                        </div>
                                        <div>
                                            <span className="text-text-muted">Nomor Kursi:</span>
                                            <p className="font-bold text-amber-600 dark:text-amber-400 mt-0.5">
                                                {booking.seats_reserved > 0 ? `${booking.seats_reserved} Kursi` : '6 Kursi'}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Snack Order Card */}
                        <div className="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-sm space-y-4">
                            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-border">
                                <div>
                                    <h2 className="text-sm font-bold uppercase tracking-wider text-text-muted">Pesanan Jajanan</h2>
                                    <p className="text-xs text-text-muted mt-0.5">
                                        Total jajan saat ini: {formatRupiah(booking.snack_total)} · Total booking: {formatRupiah(booking.total_price)}
                                    </p>
                                </div>

                                <div className="flex flex-wrap items-center gap-2">
                                    {snackUpdateWhatsAppUrl && (
                                        <a
                                            href={snackUpdateWhatsAppUrl}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex min-h-[36px] items-center gap-1.5 rounded-xl bg-emerald-700 hover:bg-emerald-600 text-white px-3 text-xs font-bold transition"
                                        >
                                            <MessageSquare className="size-3.5" />
                                            <span>Kirim update ke pelanggan via WA</span>
                                        </a>
                                    )}

                                    {booking.status !== 'dibatalkan' && (
                                        <button
                                            type="button"
                                            onClick={() => setEditorOpen(true)}
                                            className="inline-flex min-h-[36px] items-center gap-1.5 rounded-xl border border-amber-500/40 bg-amber-500/10 px-3 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 transition"
                                        >
                                            <Edit className="size-3.5" />
                                            <span>Ubah Jajan</span>
                                        </button>
                                    )}
                                </div>
                            </div>

                            {booking.items && booking.items.length > 0 ? (
                                <div className="divide-y divide-border">
                                    {booking.items.map((item) => (
                                        <div key={item.id} className="py-3 flex items-center justify-between gap-4 text-xs sm:text-sm">
                                            <div className="space-y-0.5 min-w-0">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-bold text-text">{item.name_snapshot} × {item.qty}</span>
                                                    <span className={`text-[10px] uppercase font-bold px-1.5 py-0.5 rounded ${
                                                        item.status === 'selesai' ? 'bg-emerald-500/15 text-emerald-400' :
                                                        item.status === 'siap' ? 'bg-sky-500/15 text-sky-400' :
                                                        item.status === 'disiapkan' ? 'bg-amber-500/15 text-amber-400' :
                                                        'bg-surface-2 text-text-muted'
                                                    }`}>
                                                        {item.status}
                                                    </span>
                                                </div>
                                                {item.note && (
                                                    <div className="mt-1 inline-flex items-start gap-1 rounded bg-amber-500/15 px-2 py-0.5 text-xs font-semibold text-amber-600 dark:text-amber-400">
                                                        <span className="font-bold shrink-0">Catatan:</span>
                                                        <span>{item.note}</span>
                                                    </div>
                                                )}
                                            </div>
                                            <div className="font-bold text-text shrink-0">
                                                {formatRupiah(item.price_snapshot * item.qty)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="py-6 text-center text-xs text-text-muted space-y-2">
                                    <p>Belum ada jajan yang dipesan untuk booking ini.</p>
                                    {booking.status !== 'dibatalkan' && (
                                        <button
                                            type="button"
                                            onClick={() => setEditorOpen(true)}
                                            className="inline-flex min-h-[36px] items-center gap-1.5 rounded-xl border border-border bg-surface px-3 font-semibold text-text hover:bg-surface-2 transition"
                                        >
                                            + Tambahkan Jajanan
                                        </button>
                                    )}
                                </div>
                            )}
                        </div>

                        {/* Snack Change Logs (Audit) */}
                        <div className="rounded-2xl border border-border bg-surface shadow-sm overflow-hidden">
                            <button
                                type="button"
                                onClick={() => setHistoryOpen(!historyOpen)}
                                className="flex w-full min-h-[48px] items-center justify-between p-4 text-xs sm:text-sm font-bold text-text hover:bg-surface-2 transition text-left"
                            >
                                <span className="flex items-center gap-2">
                                    <History className="size-4 text-text-muted" />
                                    <span>Riwayat Perubahan Jajan</span>
                                    <span className="rounded-full bg-surface-2 px-2 py-0.5 text-[11px] font-semibold text-text-muted">
                                        {snackChangeLogs.length}
                                    </span>
                                </span>
                                <ChevronDown className={`size-4 text-text-muted transition-transform duration-200 ${historyOpen ? 'rotate-180' : ''}`} />
                            </button>

                            {historyOpen && (
                                <div className="p-4 pt-0 border-t border-border space-y-3">
                                    {snackChangeLogs.length > 0 ? (
                                        <ol className="mt-3 space-y-3">
                                            {snackChangeLogs.map((change, idx) => {
                                                const before = change.before_details || [];
                                                const after = change.after_details || [];
                                                return (
                                                    <li key={idx} className="border-l-2 border-amber-500 pl-3 text-xs space-y-1">
                                                        <div className="flex items-center justify-between font-bold text-text">
                                                            <span>{change.summary || 'Pembaruan jajanan'}</span>
                                                            <span className="text-[11px] font-normal text-text-muted">
                                                                {change.created_at ? new Date(change.created_at).toLocaleDateString('id-ID') : ''}
                                                            </span>
                                                        </div>
                                                        <div className="grid gap-2 sm:grid-cols-2 text-[11px] text-text-muted pt-1">
                                                            <div>
                                                                <span className="font-semibold text-text">Sebelum:</span>
                                                                {before.length > 0 ? (
                                                                    before.map((b, bi) => (
                                                                        <p key={bi} className="truncate">
                                                                            {b.name_snapshot} × {b.qty}{b.note ? ` · ${b.note}` : ''}
                                                                        </p>
                                                                    ))
                                                                ) : (
                                                                    <p className="italic">Tidak ada</p>
                                                                )}
                                                            </div>
                                                            <div>
                                                                <span className="font-semibold text-text">Sesudah:</span>
                                                                {after.length > 0 ? (
                                                                    after.map((a, ai) => (
                                                                        <p key={ai} className="truncate">
                                                                            {a.name_snapshot} × {a.qty}{a.note ? ` · ${a.note}` : ''}
                                                                        </p>
                                                                    ))
                                                                ) : (
                                                                    <p className="italic">Tidak ada</p>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </li>
                                                );
                                            })}
                                        </ol>
                                    ) : (
                                        <p className="pt-3 text-xs text-text-muted">Belum ada riwayat perubahan tercatat.</p>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Right Column (4 cols Sticky): Ringkasan Pembayaran */}
                    <div className="lg:col-span-4 lg:sticky lg:top-24 space-y-4">
                        <div className="rounded-2xl border border-border bg-surface p-5 shadow-sm space-y-4">
                            <div className="flex items-center justify-between pb-3 border-b border-border">
                                <h2 className="text-xs font-bold uppercase tracking-wider text-text-muted">Ringkasan Pembayaran</h2>
                                <span className="rounded-full bg-accent/15 px-2.5 py-0.5 text-[10px] font-bold text-accent uppercase">
                                    {{ di_tempat: 'Di Tempat', qris: 'QRIS', transfer: 'Transfer' }[booking.payment_method] || booking.payment_method}
                                </span>
                            </div>

                            <div className="space-y-2 text-xs">
                                <div className="flex justify-between text-text-muted">
                                    <span>Tiket ({booking.booking_type === 'private' ? 'Privat 6 kursi' : `${booking.pax} kursi`})</span>
                                    <span className="font-bold text-text">{formatRupiah(booking.ticket_total)}</span>
                                </div>
                                <div className="flex justify-between text-text-muted">
                                    <span>Jajanan & Camilan ({booking.items?.reduce((acc, cur) => acc + (cur.qty || 0), 0) || 0} item)</span>
                                    <span className="font-bold text-text">{formatRupiah(booking.snack_total)}</span>
                                </div>
                                <div className="pt-2 border-t border-border flex justify-between items-baseline">
                                    <span className="text-xs font-bold uppercase tracking-wider text-text">Total Tagihan</span>
                                    <span className="text-xl font-black text-amber-600 dark:text-amber-400">
                                        {formatRupiah(booking.total_price)}
                                    </span>
                                </div>
                            </div>

                            {booking.payment_proof_path && (
                                <div className="pt-2 border-t border-border space-y-2">
                                    <span className="block text-xs font-bold uppercase tracking-wider text-text-muted">Bukti Transfer</span>
                                    <a
                                        href={`/admin/booking/${booking.id}/bukti`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="group relative block overflow-hidden rounded-xl border border-border bg-surface-2 p-1 hover:border-amber-400 transition"
                                    >
                                        <img
                                            src={`/admin/booking/${booking.id}/bukti`}
                                            alt={`Bukti transfer ${booking.code}`}
                                            className="h-36 w-full rounded-lg object-cover"
                                        />
                                        <div className="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition group-hover:opacity-100">
                                            <span className="inline-flex items-center gap-1 rounded-lg bg-black/80 px-2.5 py-1 text-xs font-bold text-white">
                                                <span>Lihat Ukuran Penuh</span>
                                                <ExternalLink className="size-3" />
                                            </span>
                                        </div>
                                    </a>
                                </div>
                            )}

                            {/* Customer Contact */}
                            <div className="pt-3 border-t border-border space-y-2 text-xs">
                                <span className="block font-bold uppercase tracking-wider text-text-muted">Kontak Pemesan</span>
                                <p className="font-semibold text-text">{booking.customer_name}</p>
                                <p className="font-mono text-text-muted">{booking.whatsapp}</p>

                                {booking.notes && (
                                    <div className="mt-2 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-text">
                                        <span className="block font-bold text-amber-600 dark:text-amber-400">Catatan Pemesan:</span>
                                        <p className="mt-1 whitespace-pre-line font-medium">{booking.notes}</p>
                                    </div>
                                )}

                                {waUrl && (
                                    <div className="mt-2 w-full">
                                        <a
                                            href={waUrl}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/20 active:scale-95 transition"
                                        >
                                            <MessageSquare className="size-3.5" />
                                            <span>WhatsApp pemesan</span>
                                        </a>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Concession Drawer / Modal */}
            <Modal show={editorOpen} onClose={() => setEditorOpen(false)} maxWidth="2xl">
                <form onSubmit={handleSaveSnacks} className="p-6 space-y-6">
                    <div className="flex items-center justify-between border-b border-border pb-4">
                        <div>
                            <h3 className="text-lg font-bold text-text">Ubah Menu Jajanan</h3>
                            <p className="text-xs text-text-muted mt-0.5">Sesuaikan menu jajan yang dipesan untuk booking #{booking.code}</p>
                        </div>
                        <button
                            type="button"
                            onClick={() => setEditorOpen(false)}
                            className="rounded-lg p-1.5 text-text-muted hover:bg-surface-2 transition"
                        >
                            <XCircle className="size-5" />
                        </button>
                    </div>

                    <div className="max-h-[60vh] overflow-y-auto space-y-6 pr-2">
                        {categories.map((cat) => (
                            <div key={cat.id} className="space-y-3">
                                <h4 className="text-xs font-black uppercase tracking-wider text-amber-500">{cat.name}</h4>
                                <div className="space-y-2">
                                    {cat.items?.map((m) => {
                                        const curQty = selectedSnacks[m.id]?.qty || 0;
                                        const curNote = selectedSnacks[m.id]?.note || '';
                                        return (
                                            <div key={m.id} className="rounded-xl border border-border bg-surface-2/40 p-3 space-y-2">
                                                <div className="flex items-center justify-between">
                                                    <div>
                                                        <p className="text-xs sm:text-sm font-bold text-text">{m.name}</p>
                                                        <p className="text-xs text-text-muted">{formatRupiah(m.price)}</p>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <button
                                                            type="button"
                                                            onClick={() => updateQty(m.id, -1)}
                                                            disabled={curQty <= 0}
                                                            className="size-8 rounded-lg border border-border flex items-center justify-center text-text hover:bg-surface disabled:opacity-30 transition"
                                                        >
                                                            <Minus className="size-3.5" />
                                                        </button>
                                                        <span className="w-6 text-center text-xs sm:text-sm font-black text-text">{curQty}</span>
                                                        <button
                                                            type="button"
                                                            onClick={() => updateQty(m.id, 1)}
                                                            disabled={curQty >= snackMaxQty}
                                                            className="size-8 rounded-lg border border-border flex items-center justify-center text-text hover:bg-surface disabled:opacity-30 transition"
                                                        >
                                                            <Plus className="size-3.5" />
                                                        </button>
                                                    </div>
                                                </div>
                                                {curQty > 0 && (
                                                    <input
                                                        type="text"
                                                        placeholder="Catatan jajan (opsional, misal: dingin / tanpa es)"
                                                        value={curNote}
                                                        onChange={(e) => updateNote(m.id, e.target.value)}
                                                        className="w-full rounded-lg border border-border bg-bg px-2.5 py-1.5 text-xs text-text focus:border-amber-400 focus:outline-none"
                                                    />
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="flex items-center justify-end gap-3 pt-4 border-t border-border">
                        <button
                            type="button"
                            onClick={() => setEditorOpen(false)}
                            className="min-h-10 rounded-xl border border-border px-4 text-xs font-semibold hover:bg-surface-2 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            className="min-h-10 rounded-xl bg-accent px-5 text-xs font-bold text-accent-contrast hover:opacity-90 active:scale-95 transition"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Modal Hapus Booking Permanen */}
            <Modal show={deleteModalOpen} onClose={() => setDeleteModalOpen(false)} maxWidth="md">
                <div className="p-6 space-y-4">
                    <div className="flex items-start gap-3.5">
                        <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-rose-500/15 text-rose-500">
                            <Trash2 className="size-6" />
                        </div>
                        <div>
                            <h3 className="text-base font-black text-text">Hapus Booking {booking.code}?</h3>
                            <p className="mt-1 text-xs text-text-muted leading-relaxed">
                                Apakah Anda yakin ingin menghapus booking atas nama <strong className="text-text">{booking.customer_name}</strong> ({booking.pax || booking.people_count || booking.seats_reserved} orang)? Tindakan ini akan menghapus data tiket, pesanan jajan, dan mengembalikan kapasitas kursi sesi secara permanen.
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center justify-end gap-2.5 pt-2 border-t border-border">
                        <button
                            type="button"
                            onClick={() => setDeleteModalOpen(false)}
                            disabled={isDeleting}
                            className="min-h-10 rounded-xl border border-border bg-surface px-4 text-xs font-semibold text-text hover:bg-surface-2 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            disabled={isDeleting}
                            onClick={() => {
                                setIsDeleting(true);
                                router.delete(`/admin/booking/${booking.id}`, {
                                    onFinish: () => setIsDeleting(false),
                                });
                            }}
                            className="min-h-10 rounded-xl bg-rose-600 px-4 text-xs font-bold text-white hover:bg-rose-500 active:scale-95 transition disabled:opacity-50 inline-flex items-center gap-1.5"
                        >
                            <Trash2 className="size-3.5" />
                            <span>{isDeleting ? 'Menghapus...' : 'Ya, Hapus Permanen'}</span>
                        </button>
                    </div>
                </div>
            </Modal>

            {/* In-App Styled Confirmation Modal */}
            <Modal show={confirmModal.show} onClose={() => setConfirmModal(prev => ({ ...prev, show: false }))} maxWidth="md">
                <div className="p-6 space-y-4">
                    <div className="flex items-start gap-3.5">
                        <div className={`flex size-11 shrink-0 items-center justify-center rounded-xl ${
                            confirmModal.variant === 'danger'
                                ? 'bg-rose-500/15 text-rose-500'
                                : confirmModal.variant === 'warning'
                                ? 'bg-amber-500/15 text-amber-500'
                                : 'bg-emerald-500/15 text-emerald-500'
                        }`}>
                            <AlertTriangle className="size-6" />
                        </div>
                        <div>
                            <h3 className="text-base font-black text-text">{confirmModal.title}</h3>
                            <p className="mt-1 text-xs text-text-muted leading-relaxed">
                                {confirmModal.message}
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center justify-end gap-2.5 pt-3 border-t border-border">
                        <button
                            type="button"
                            onClick={() => setConfirmModal(prev => ({ ...prev, show: false }))}
                            className="min-h-10 rounded-xl border border-border bg-surface px-4 text-xs font-semibold text-text hover:bg-surface-2 transition"
                        >
                            {confirmModal.cancelText || 'Batal'}
                        </button>
                        <button
                            type="button"
                            onClick={confirmModal.onConfirm}
                            className={`min-h-10 rounded-xl px-4 text-xs font-bold transition inline-flex items-center gap-1.5 ${
                                confirmModal.variant === 'danger'
                                    ? 'bg-rose-600 text-white hover:bg-rose-500 active:scale-95'
                                    : confirmModal.variant === 'warning'
                                    ? 'bg-amber-600 text-white hover:bg-amber-500 active:scale-95'
                                    : 'bg-amber-500 text-slate-950 hover:bg-amber-400 active:scale-95'
                            }`}
                        >
                            <span>{confirmModal.confirmText}</span>
                        </button>
                    </div>
                </div>
            </Modal>

            {/* Edit Booking Modal */}
            <EditBookingModal
                booking={booking}
                isOpen={editModalOpen}
                onClose={() => setEditModalOpen(false)}
            />
        </AdminLayout>
    );
}

