import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import Modal from '@/Components/Modal';
import EditBookingModal from '@/Components/EditBookingModal';
import { 
    Plus, 
    Trash2, 
    Search, 
    Filter, 
    RotateCcw, 
    Copy, 
    Check, 
    ExternalLink, 
    MessageSquare, 
    Calendar, 
    Clock, 
    CreditCard, 
    AlertTriangle,
    QrCode,
    Edit
} from 'lucide-react';

export default function Index({ bookings, selectedStatus = '', selectedMethod = '', search = '' }) {
    const [q, setQ] = useState(search || '');
    const [status, setStatus] = useState(selectedStatus || '');
    const [method, setMethod] = useState(selectedMethod || '');
    const [cleanModalOpen, setCleanModalOpen] = useState(false);
    const [resetVisits, setResetVisits] = useState(false);
    const [rejectModalData, setRejectModalData] = useState(null);
    const [rejectionNote, setRejectionNote] = useState('');
    const [copiedCode, setCopiedCode] = useState(null);
    const [deleteModalData, setDeleteModalData] = useState(null);
    const [editBookingData, setEditBookingData] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [confirmModal, setConfirmModal] = useState({
        show: false,
        title: '',
        message: '',
        confirmText: 'Lanjutkan',
        cancelText: 'Batal',
        variant: 'danger',
        onConfirm: () => {},
    });

    // Auto-refresh when new bookings arrive (poll every 10 seconds if window visible)
    useEffect(() => {
        const interval = setInterval(() => {
            if (document.visibilityState === 'visible') {
                router.reload({
                    only: ['bookings'],
                    preserveScroll: true,
                    preserveState: true,
                });
            }
        }, 10000);

        return () => clearInterval(interval);
    }, []);

    const handleFilter = (e) => {
        e?.preventDefault();
        router.get('/admin/booking', {
            q: q || undefined,
            status: status || undefined,
            payment_method: method || undefined,
        }, { preserveState: true });
    };

    const handleReset = () => {
        setQ('');
        setStatus('');
        setMethod('');
        router.get('/admin/booking');
    };

    const copyToClipboard = (code) => {
        navigator.clipboard.writeText(code);
        setCopiedCode(code);
        setTimeout(() => setCopiedCode(null), 2000);
    };

    const handleCleanTestData = (e) => {
        e.preventDefault();
        router.post('/admin/booking/bersihkan-data-testing', {
            reset_member_visits: resetVisits ? 1 : 0
        }, {
            onSuccess: () => setCleanModalOpen(false)
        });
    };

    const handleRejectProof = (e) => {
        e.preventDefault();
        if (!rejectModalData) return;
        router.patch(`/admin/booking/${rejectModalData.id}/status`, {
            action: 'tolak_bukti',
            rejection_note: rejectionNote
        }, {
            onSuccess: () => {
                setRejectModalData(null);
                setRejectionNote('');
            }
        });
    };

    const handleStatusAction = (bookingId, action, confirmMsg) => {
        if (!confirmMsg) {
            router.patch(`/admin/booking/${bookingId}/status`, { action });
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
            : action === 'pulihkan'
            ? 'Pulihkan Booking?'
            : 'Konfirmasi Aksi';

        const confirmButtonText = action === 'batal'
            ? 'Ya, Batalkan'
            : action === 'tandai_dibayar'
            ? 'Ya, Tandai Dibayar'
            : action === 'tidak_hadir'
            ? 'Tandai Tidak Hadir'
            : action === 'pulihkan'
            ? 'Ya, Pulihkan'
            : 'Lanjutkan';

        setConfirmModal({
            show: true,
            title: actionTitle,
            message: confirmMsg,
            confirmText: confirmButtonText,
            cancelText: 'Batal',
            variant: isDanger ? 'danger' : isWarning ? 'warning' : 'primary',
            onConfirm: () => {
                router.patch(`/admin/booking/${bookingId}/status`, { action });
                setConfirmModal(prev => ({ ...prev, show: false }));
            },
        });
    };

    const handleToggleBlock = (profileId, isBlocked) => {
        const title = isBlocked ? 'Buka Blokir Nomor WhatsApp?' : 'Blokir Nomor WhatsApp?';
        const message = isBlocked
            ? 'Nomor WhatsApp ini akan dapat melakukan pemesanan tiket kembali di SynemaX.'
            : 'Nomor WhatsApp ini tidak akan dapat membuat booking tiket baru di SynemaX.';

        setConfirmModal({
            show: true,
            title,
            message,
            confirmText: isBlocked ? 'Buka Blokir' : 'Blokir Nomor',
            cancelText: 'Batal',
            variant: isBlocked ? 'primary' : 'danger',
            onConfirm: () => {
                router.patch(`/admin/pelanggan/${profileId}/blokir`);
                setConfirmModal(prev => ({ ...prev, show: false }));
            },
        });
    };

    const formatRupiah = (val) => {
        return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
    };

    const formatDateTimePretty = (session) => {
        if (!session) return '-';
        let datePart = session.session_date;
        try {
            const d = new Date(session.session_date + 'T00:00:00');
            datePart = d.toLocaleDateString('id-ID', {
                weekday: 'short',
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });
        } catch {
            datePart = session.session_date;
        }

        let timePart = session.start_time || '';
        if (timePart) {
            const parts = timePart.split(':');
            timePart = `${parts[0]}:${parts[1]} WIB`;
        }
        return `${datePart} · ${timePart}`;
    };

    const formatPhonePretty = (phone) => {
        if (!phone) return '-';
        let digits = String(phone).replace(/\D+/g, '');
        if (digits.startsWith('62')) {
            digits = '0' + digits.slice(2);
        }
        if (digits.length >= 10) {
            return digits.replace(/(\d{4})(\d{4})(\d+)/, '$1-$2-$3');
        }
        return phone;
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

    return (
        <AdminLayout title="Kelola booking">
            <Head title="Kelola booking - Admin SynemaX" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.2em] text-accent">Pelanggan</p>
                        <div className="flex items-center gap-3 mt-1 flex-wrap">
                            <h1 className="text-2xl sm:text-3xl font-black text-text">Kelola booking</h1>
                            <div className="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                                <span className="relative flex size-2">
                                    <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                    <span className="relative inline-flex size-2 rounded-full bg-emerald-500"></span>
                                </span>
                                <span>Auto-refresh aktif</span>
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-wrap items-center gap-2.5">
                        <Link
                            href="/admin/booking/pesan-di-tempat"
                            className="inline-flex min-h-11 items-center gap-2 rounded-xl bg-accent px-4 py-2 text-xs sm:text-sm font-bold text-accent-contrast shadow-sm transition hover:opacity-90 active:scale-95"
                        >
                            <Plus className="size-4 shrink-0" />
                            <span>Pesan di Tempat (Loket)</span>
                        </Link>

                        {(bookings?.total > 0 || (bookings?.data && bookings.data.length > 0)) && (
                            <button
                                type="button"
                                onClick={() => setCleanModalOpen(true)}
                                className="inline-flex min-h-11 items-center gap-1.5 rounded-xl border border-rose-500/30 bg-rose-500/10 px-3.5 py-2 text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-500/20 active:scale-95 transition"
                                title="Hapus riwayat booking testing"
                            >
                                <Trash2 className="size-4 shrink-0" />
                                <span>Bersihkan Data Testing</span>
                            </button>
                        )}
                    </div>
                </div>

                {/* Filter Box */}
                <form onSubmit={handleFilter} className="grid gap-3 rounded-2xl border border-border bg-surface p-4 sm:grid-cols-[1fr_1fr_1.5fr_auto_auto] sm:items-end">
                    <div>
                        <label htmlFor="filter_status" className="block text-xs font-bold uppercase tracking-wider text-text-muted mb-1.5">Status</label>
                        <select
                            id="filter_status"
                            value={status}
                            onChange={(e) => {
                                setStatus(e.target.value);
                                router.get('/admin/booking', {
                                    q: q || undefined,
                                    status: e.target.value || undefined,
                                    payment_method: method || undefined,
                                }, { preserveState: true });
                            }}
                            className="w-full rounded-xl border border-border bg-bg px-3 py-2.5 text-xs sm:text-sm text-text focus:border-amber-400 focus:outline-none"
                        >
                            <option value="">Semua status</option>
                            <option value="menunggu_pembayaran">Menunggu pembayaran</option>
                            <option value="menunggu_konfirmasi">Menunggu konfirmasi</option>
                            <option value="terkonfirmasi">Terkonfirmasi</option>
                            <option value="kedaluwarsa">Kedaluwarsa</option>
                            <option value="hadir">Hadir</option>
                            <option value="tidak_hadir">Tidak hadir</option>
                            <option value="dibatalkan">Dibatalkan</option>
                        </select>
                    </div>

                    <div>
                        <label htmlFor="filter_method" className="block text-xs font-bold uppercase tracking-wider text-text-muted mb-1.5">Metode Bayar</label>
                        <select
                            id="filter_method"
                            value={method}
                            onChange={(e) => {
                                setMethod(e.target.value);
                                router.get('/admin/booking', {
                                    q: q || undefined,
                                    status: status || undefined,
                                    payment_method: e.target.value || undefined,
                                }, { preserveState: true });
                            }}
                            className="w-full rounded-xl border border-border bg-bg px-3 py-2.5 text-xs sm:text-sm text-text focus:border-amber-400 focus:outline-none"
                        >
                            <option value="">Semua metode</option>
                            <option value="di_tempat">Bayar di tempat</option>
                            <option value="qris">QRIS</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </div>

                    <div>
                        <label htmlFor="search_q" className="block text-xs font-bold uppercase tracking-wider text-text-muted mb-1.5">Cari Booking</label>
                        <div className="relative">
                            <input
                                id="search_q"
                                type="search"
                                placeholder="Nama, kode, WhatsApp…"
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                                className="w-full rounded-xl border border-border bg-bg pl-9 pr-3 py-2.5 text-xs sm:text-sm text-text focus:border-amber-400 focus:outline-none"
                            />
                            <Search className="absolute left-3 top-3 size-4 text-text-muted" />
                        </div>
                    </div>

                    <button
                        type="submit"
                        className="min-h-11 rounded-xl bg-accent px-5 text-xs sm:text-sm font-bold text-accent-contrast hover:opacity-90 active:scale-95 transition"
                    >
                        Filter
                    </button>

                    {(q || status || method) && (
                        <button
                            type="button"
                            onClick={handleReset}
                            className="min-h-11 inline-flex items-center justify-center rounded-xl border border-border px-4 text-xs sm:text-sm font-semibold hover:bg-surface-2 active:scale-95 transition"
                        >
                            Reset
                        </button>
                    )}
                </form>

                {/* Booking List */}
                <div className="space-y-4">
                    {bookings?.data && bookings.data.length > 0 ? (
                        bookings.data.map((booking) => {
                            const whatsAppMsg = encodeURIComponent(
                                `Halo ${booking.customer_name}, admin SynemaX menghubungi untuk booking ${booking.code}.` +
                                (booking.notes ? `\nCatatan pemesan: ${booking.notes}` : '')
                            );
                            const cleanPhone = (booking.whatsapp || '').replace(/\D+/g, '');
                            const waUrl = cleanPhone ? `https://wa.me/${cleanPhone.startsWith('0') ? '62' + cleanPhone.slice(1) : cleanPhone}?text=${whatsAppMsg}` : null;
                            const hasNotes = Boolean(booking.notes || (booking.items && booking.items.some(i => i.note)));

                            return (
                                <article key={booking.id} className="rounded-2xl border border-border bg-surface p-4 sm:p-5 transition hover:border-amber-400/40">
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h2 className="font-bold text-text text-base">{booking.customer_name}</h2>
                                                {statusBadge(booking.status)}
                                                {booking.payment_proof_path ? (
                                                    <span className="rounded-full bg-emerald-500/15 border border-emerald-500/30 px-2.5 py-0.5 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                                        Bukti diunggah
                                                    </span>
                                                ) : (booking.payment_method !== 'di_tempat' && booking.status === 'menunggu_pembayaran') ? (
                                                    <span className="rounded-full bg-amber-500/15 border border-amber-500/30 px-2.5 py-0.5 text-xs font-semibold text-amber-600 dark:text-amber-400">
                                                        Belum ada bukti
                                                    </span>
                                                ) : null}

                                                {booking.customer_profile?.no_show_count >= 2 && (
                                                    <span className="rounded-full bg-rose-500/15 border border-rose-500/30 px-2.5 py-0.5 text-xs font-semibold text-rose-600 dark:text-rose-400">
                                                        Peringatan tidak datang {booking.customer_profile.no_show_count}×
                                                    </span>
                                                )}

                                                {hasNotes && (
                                                    <span className="rounded-full bg-amber-500/15 border border-amber-500/30 px-2.5 py-0.5 text-xs font-bold text-amber-600 dark:text-amber-400">
                                                        Ada catatan
                                                    </span>
                                                )}
                                            </div>

                                            <div className="mt-1 flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => copyToClipboard(booking.code)}
                                                    className="inline-flex items-center gap-1.5 font-mono text-sm font-black text-accent hover:underline"
                                                    title="Klik untuk salin kode"
                                                >
                                                    <span>{booking.code}</span>
                                                    {copiedCode === booking.code ? (
                                                        <Check className="size-3.5 text-emerald-500" />
                                                    ) : (
                                                        <Copy className="size-3.5 text-text-muted" />
                                                    )}
                                                </button>
                                            </div>
                                        </div>

                                        <div className="text-left sm:text-right">
                                            <p className="text-lg font-black text-accent">{formatRupiah(booking.total_price)}</p>
                                            <p className="mt-0.5 text-xs text-text-muted">
                                                {booking.booking_type === 'private' ? 'Sewa Private (6 kursi)' : `${booking.pax} orang`}
                                            </p>
                                            <p className="text-[11px] text-text-muted">
                                                Tiket {formatRupiah(booking.ticket_total)} · Jajan {formatRupiah(booking.snack_total)}
                                            </p>
                                        </div>
                                    </div>

                                    {/* Info Grid */}
                                    <div className="mt-4 grid gap-2 border-t border-border/80 pt-3 text-xs sm:text-sm text-text sm:grid-cols-2">
                                        <div className="flex items-center gap-1.5">
                                            <span className="text-text-muted">WhatsApp:</span>
                                            <span className="font-semibold font-mono">{formatPhonePretty(booking.whatsapp)}</span>
                                        </div>
                                        <div className="flex items-center gap-1.5">
                                            <span className="text-text-muted">Jadwal:</span>
                                            <span className="font-semibold">
                                                {formatDateTimePretty(booking.session)}
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-1.5">
                                            <span className="text-text-muted">Metode bayar:</span>
                                            <strong className="uppercase text-text">
                                                {{ di_tempat: 'Di tempat', qris: 'QRIS', transfer: 'Transfer' }[booking.payment_method] || booking.payment_method}
                                            </strong>
                                        </div>

                                        {booking.notes && (
                                            <div className="sm:col-span-2 rounded-xl border border-amber-500/25 bg-amber-500/10 p-2.5 text-xs text-text">
                                                <span className="font-bold text-amber-600 dark:text-amber-400">Catatan Pemesan: </span>
                                                <span>{booking.notes}</span>
                                            </div>
                                        )}

                                        {booking.items && booking.items.filter(i => i.note).length > 0 && (
                                            <div className="sm:col-span-2 text-xs text-text-muted space-y-0.5">
                                                <span className="font-bold text-amber-600 dark:text-amber-400">Catatan Jajan:</span>
                                                {booking.items.filter(i => i.note).map((sn, idx) => (
                                                    <p key={idx}><span className="font-semibold text-text">{sn.name_snapshot}:</span> {sn.note}</p>
                                                ))}
                                            </div>
                                        )}
                                    </div>

                                    {/* Actions */}
                                    <div className="mt-4 pt-3 border-t border-border/60 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                                        {/* Main & Workflow Actions */}
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Link
                                                href={`/admin/booking/${booking.id}`}
                                                className="inline-flex min-h-[38px] items-center justify-center rounded-xl border border-amber-500/40 bg-amber-500/10 px-3.5 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-500 hover:text-slate-950 active:scale-95 transition shadow-xs"
                                            >
                                                Detail & jajan
                                            </Link>

                                            <button
                                                type="button"
                                                onClick={() => setEditBookingData(booking)}
                                                className="inline-flex min-h-[38px] items-center justify-center gap-1 rounded-xl border border-border bg-surface px-3 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 active:scale-95 transition shadow-xs"
                                                title="Ubah data reservasi"
                                            >
                                                <Edit className="size-3.5" />
                                                <span>Ubah</span>
                                            </button>

                                            {waUrl && (
                                                <a
                                                    href={waUrl}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex min-h-[38px] items-center justify-center gap-1.5 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-3 text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/20 active:scale-95 transition"
                                                >
                                                    <MessageSquare className="size-3.5" />
                                                    <span>WhatsApp</span>
                                                </a>
                                            )}

                                            {booking.payment_proof_path && (
                                                <a
                                                    href={`/admin/booking/${booking.id}/bukti`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex min-h-[38px] items-center justify-center gap-1.5 rounded-xl border border-border px-3 text-xs font-semibold hover:bg-surface-2 active:scale-95 transition"
                                                >
                                                    <span>Bukti bayar</span>
                                                    <ExternalLink className="size-3.5" />
                                                </a>
                                            )}

                                            {/* Status Context Actions */}
                                            {booking.status === 'menunggu_pembayaran' && (
                                                <>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleStatusAction(booking.id, 'tandai_dibayar', 'Tandai booking ini sudah dibayar?')}
                                                        className="min-h-[38px] rounded-xl bg-accent px-3.5 text-xs font-bold text-accent-contrast hover:opacity-90 active:scale-95 transition shadow-xs"
                                                    >
                                                        Tandai sudah dibayar
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleStatusAction(booking.id, 'batal', 'Batalkan booking ini?')}
                                                        className="min-h-[38px] rounded-xl border border-rose-500/60 px-3 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-500/10 active:scale-95 transition"
                                                    >
                                                        Batalkan
                                                    </button>
                                                </>
                                            )}

                                            {booking.status === 'menunggu_konfirmasi' && (
                                                <>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleStatusAction(booking.id, 'konfirmasi')}
                                                        className="min-h-[38px] rounded-xl bg-accent px-3.5 text-xs font-bold text-accent-contrast hover:opacity-90 active:scale-95 transition shadow-xs"
                                                    >
                                                        Konfirmasi booking
                                                    </button>
                                                    {booking.payment_proof_path && (
                                                        <button
                                                            type="button"
                                                            onClick={() => {
                                                                setRejectModalData(booking);
                                                                setRejectionNote('');
                                                            }}
                                                            className="min-h-[38px] rounded-xl border border-amber-500/60 px-3 text-xs font-semibold text-amber-600 dark:text-amber-400 hover:bg-amber-500/10 active:scale-95 transition"
                                                        >
                                                            Tolak bukti
                                                        </button>
                                                    )}
                                                    <button
                                                        type="button"
                                                        onClick={() => handleStatusAction(booking.id, 'batal', 'Batalkan booking ini?')}
                                                        className="min-h-[38px] rounded-xl border border-rose-500/60 px-3 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-500/10 active:scale-95 transition"
                                                    >
                                                        Batalkan
                                                    </button>
                                                </>
                                            )}

                                            {booking.status === 'terkonfirmasi' && (
                                                <>
                                                    <Link
                                                        href={`/admin/scan?sesi=${booking.session_id}`}
                                                        className="inline-flex min-h-[38px] items-center justify-center gap-1.5 rounded-xl border border-amber-500/40 bg-amber-500/10 px-3.5 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 active:scale-95 transition"
                                                    >
                                                        <QrCode className="size-4" />
                                                        <span>Scan tiket</span>
                                                    </Link>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleStatusAction(booking.id, 'tidak_hadir', 'Tandai pemesan tidak hadir? Ini akan menambah catatan tidak datang pada profil pelanggan.')}
                                                        className="min-h-[38px] rounded-xl border border-amber-500/60 px-3 text-xs font-semibold text-amber-600 dark:text-amber-400 hover:bg-amber-500/10 active:scale-95 transition"
                                                    >
                                                        Tandai tidak hadir
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleStatusAction(booking.id, 'batal', 'Batalkan booking ini?')}
                                                        className="min-h-[38px] rounded-xl border border-rose-500/60 px-3 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-500/10 active:scale-95 transition"
                                                    >
                                                        Batalkan
                                                    </button>
                                                </>
                                            )}

                                            {booking.status === 'kedaluwarsa' && (
                                                <button
                                                    type="button"
                                                    onClick={() => handleStatusAction(booking.id, 'pulihkan', 'Pulihkan booking kedaluwarsa ini? Kursi akan kembali ditahan jika kapasitas masih ada.')}
                                                    className="min-h-[38px] rounded-xl border border-accent bg-accent/10 px-3.5 text-xs font-bold text-accent hover:bg-accent/20 active:scale-95 transition"
                                                >
                                                    Pulihkan booking
                                                </button>
                                            )}
                                        </div>

                                        {/* Danger & Profile Actions */}
                                        <div className="flex items-center gap-2 self-end sm:self-auto shrink-0 pt-1 sm:pt-0">
                                            {booking.customer_profile && (
                                                <button
                                                    type="button"
                                                    onClick={() => handleToggleBlock(booking.customer_profile.id, booking.customer_profile.is_blocked)}
                                                    className={`min-h-[38px] rounded-xl border px-3 text-xs font-semibold active:scale-95 transition ${
                                                        booking.customer_profile.is_blocked
                                                            ? 'border-emerald-500/40 text-emerald-600 hover:bg-emerald-500/10'
                                                            : 'border-border text-text-muted hover:text-rose-500 hover:border-rose-500/40'
                                                    }`}
                                                >
                                                    {booking.customer_profile.is_blocked ? 'Buka blokir' : 'Blokir nomor'}
                                                </button>
                                            )}

                                            <button
                                                type="button"
                                                onClick={() => setDeleteModalData(booking)}
                                                className="inline-flex min-h-[38px] items-center justify-center gap-1.5 rounded-xl border border-rose-500/40 bg-rose-500/10 px-3.5 text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-600 hover:text-white active:scale-95 transition shadow-xs"
                                                title="Hapus data booking ini secara permanen"
                                            >
                                                <Trash2 className="size-3.5" />
                                                <span>Hapus</span>
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            );
                        })
                    ) : (
                        <div className="rounded-2xl border border-border bg-surface p-8 text-center text-sm text-text-muted">
                            Belum ada booking yang cocok dengan filter ini.
                        </div>
                    )}
                </div>

                {/* Pagination */}
                {bookings?.links && (
                    <div className="pt-2">
                        <Pagination pagination={bookings} />
                    </div>
                )}
            </div>

            {/* Modal Bersihkan Data Testing */}
            <Modal show={cleanModalOpen} onClose={() => setCleanModalOpen(false)} maxWidth="md">
                <form onSubmit={handleCleanTestData} className="p-6 space-y-4">
                    <div className="flex items-start gap-3.5">
                        <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-rose-500/15 text-rose-500">
                            <Trash2 className="size-6" />
                        </div>
                        <div>
                            <h3 className="text-base font-black text-text">Bersihkan Data Booking Testing?</h3>
                            <p className="mt-1 text-xs text-text-muted leading-relaxed">
                                Tindakan ini akan menghapus seluruh riwayat booking, pesanan jajan, dan bukti bayar testing. Laporan keuangan akan kembali ke Rp 0 dan kapasitas kursi sesi akan di-reset bersih untuk transaksi nyata.
                            </p>
                        </div>
                    </div>

                    <div className="pt-2 border-t border-border">
                        <label className="flex items-start gap-2.5 cursor-pointer text-xs text-text">
                            <input
                                type="checkbox"
                                checked={resetVisits}
                                onChange={(e) => setResetVisits(e.target.checked)}
                                className="mt-0.5 rounded text-rose-500 focus:ring-rose-500"
                            />
                            <span>Reset juga stempel kunjungan member yang dibuat selama uji coba</span>
                        </label>
                    </div>

                    <div className="flex items-center justify-end gap-2.5 pt-2">
                        <button
                            type="button"
                            onClick={() => setCleanModalOpen(false)}
                            className="min-h-10 rounded-xl border border-border bg-surface px-4 text-xs font-semibold text-text hover:bg-surface-2 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            className="min-h-10 rounded-xl bg-rose-600 px-4 text-xs font-bold text-white hover:bg-rose-500 active:scale-95 transition"
                        >
                            Ya, Bersihkan Data Testing
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Modal Tolak Bukti */}
            <Modal show={Boolean(rejectModalData)} onClose={() => setRejectModalData(null)} maxWidth="md">
                <form onSubmit={handleRejectProof} className="p-6 space-y-4">
                    <div>
                        <h3 className="text-base font-bold text-text">Tolak Bukti Transfer</h3>
                        <p className="mt-1 text-xs text-text-muted">
                            Masukkan alasan penolakan. Status booking akan dikembalikan ke menunggu pembayaran dan pelanggan dapat mengunggah bukti baru.
                        </p>
                    </div>

                    <div>
                        <label htmlFor="rejection_note" className="block text-xs font-semibold text-text mb-1">
                            Alasan Penolakan
                        </label>
                        <textarea
                            id="rejection_note"
                            required
                            rows={3}
                            placeholder="Contoh: Nominal kurang / struk tidak terbaca"
                            value={rejectionNote}
                            onChange={(e) => setRejectionNote(e.target.value)}
                            className="w-full rounded-xl border border-border bg-bg p-3 text-xs sm:text-sm text-text focus:border-amber-400 focus:outline-none"
                        />
                    </div>

                    <div className="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            onClick={() => setRejectModalData(null)}
                            className="min-h-10 rounded-xl border border-border px-4 text-xs font-semibold hover:bg-surface-2 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            className="min-h-10 rounded-xl bg-rose-600 px-4 text-xs font-bold text-white hover:bg-rose-500 active:scale-95 transition"
                        >
                            Tolak Bukti
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Modal Hapus Booking Permanen */}
            <Modal show={Boolean(deleteModalData)} onClose={() => setDeleteModalData(null)} maxWidth="md">
                <div className="p-6 space-y-4">
                    <div className="flex items-start gap-3.5">
                        <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-rose-500/15 text-rose-500">
                            <Trash2 className="size-6" />
                        </div>
                        <div>
                            <h3 className="text-base font-black text-text">Hapus Booking {deleteModalData?.code}?</h3>
                            <p className="mt-1 text-xs text-text-muted leading-relaxed">
                                Apakah Anda yakin ingin menghapus booking atas nama <strong className="text-text">{deleteModalData?.customer_name}</strong> ({deleteModalData?.pax || deleteModalData?.people_count} orang)? Tindakan ini akan menghapus data tiket, relasi pesanan jajan, dan melepaskan kapasitas kursi sesi secara permanen.
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center justify-end gap-2.5 pt-2 border-t border-border">
                        <button
                            type="button"
                            onClick={() => setDeleteModalData(null)}
                            disabled={isDeleting}
                            className="min-h-10 rounded-xl border border-border bg-surface px-4 text-xs font-semibold text-text hover:bg-surface-2 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            disabled={isDeleting}
                            onClick={() => {
                                if (!deleteModalData) return;
                                setIsDeleting(true);
                                router.delete(`/admin/booking/${deleteModalData.id}`, {
                                    preserveScroll: true,
                                    onSuccess: () => setDeleteModalData(null),
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
                booking={editBookingData}
                isOpen={Boolean(editBookingData)}
                onClose={() => setEditBookingData(null)}
            />
        </AdminLayout>
    );
}

