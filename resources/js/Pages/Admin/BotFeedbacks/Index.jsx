import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Modal from '@/Components/Modal';
import {
    Bot,
    MessageSquare,
    Search,
    AlertTriangle,
    CheckCircle2,
    Clock,
    ExternalLink,
    Trash2,
    Send,
    Filter,
    ShieldAlert,
    HelpCircle,
    User,
    Check,
    X,
    Settings,
    Edit3
} from 'lucide-react';

export default function Index({
    feedbacks,
    stats,
    filters,
    botInfo,
    retentionDays = 30,
}) {
    const [search, setSearch] = useState(filters.q || '');
    const [categoryFilter, setCategoryFilter] = useState(filters.category || 'all');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [selectedFeedback, setSelectedFeedback] = useState(null);
    const [adminNote, setAdminNote] = useState('');
    const [noteModalOpen, setNoteModalOpen] = useState(false);
    const [deleteModalItem, setDeleteModalItem] = useState(null);
    const [cleanupModalOpen, setCleanupModalOpen] = useState(false);
    const [cleanupDays, setCleanupDays] = useState(retentionDays > 0 ? retentionDays : 30);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleFilter = (cat, st) => {
        router.get(
            '/admin/bot-feedbacks',
            {
                category: cat !== undefined ? cat : (categoryFilter !== 'all' ? categoryFilter : undefined),
                status: st !== undefined ? st : (statusFilter !== 'all' ? statusFilter : undefined),
                q: search || undefined,
            },
            { preserveState: true }
        );
    };

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        handleFilter();
    };

    const handleReset = () => {
        setSearch('');
        setCategoryFilter('all');
        setStatusFilter('all');
        router.get('/admin/bot-feedbacks');
    };

    const handleOpenNoteModal = (item) => {
        setSelectedFeedback(item);
        setAdminNote(item.admin_note || '');
        setNoteModalOpen(true);
    };

    const handleSaveNote = (e) => {
        e.preventDefault();
        if (!selectedFeedback) return;

        setIsSubmitting(true);
        router.patch(
            `/admin/bot-feedbacks/${selectedFeedback.id}/status`,
            {
                status: selectedFeedback.status,
                admin_note: adminNote,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setNoteModalOpen(false);
                    setSelectedFeedback(null);
                    setAdminNote('');
                },
                onFinish: () => setIsSubmitting(false),
            }
        );
    };

    const handleQuickStatus = (item, newStatus) => {
        router.patch(
            `/admin/bot-feedbacks/${item.id}/status`,
            {
                status: newStatus,
            },
            { preserveScroll: true }
        );
    };

    const handleDelete = () => {
        if (!deleteModalItem) return;

        setIsSubmitting(true);
        router.delete(`/admin/bot-feedbacks/${deleteModalItem.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeleteModalItem(null),
            onFinish: () => setIsSubmitting(false),
        });
    };

    const handleCleanup = () => {
        setIsSubmitting(true);
        router.post(
            '/admin/bot-feedbacks/cleanup',
            { days: cleanupDays },
            {
                preserveScroll: true,
                onSuccess: () => setCleanupModalOpen(false),
                onFinish: () => setIsSubmitting(false),
            }
        );
    };

    const getCategoryBadge = (cat) => {
        switch (cat) {
            case 'ISSUE_REPORT':
                return {
                    label: 'Keluhan / Kendala',
                    className: 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/30',
                    icon: AlertTriangle,
                };
            case 'ORDER_INQUIRY':
                return {
                    label: 'Cek Booking',
                    className: 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30',
                    icon: Search,
                };
            case 'FEEDBACK':
                return {
                    label: 'Masukan / Ulasan',
                    className: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
                    icon: MessageSquare,
                };
            default:
                return {
                    label: 'Bantuan Umum',
                    className: 'bg-surface-2 text-text-muted border-border',
                    icon: HelpCircle,
                };
        }
    };

    const getStatusBadge = (st) => {
        switch (st) {
            case 'AUTO_RESOLVED':
                return {
                    label: 'Ditangani Bot',
                    className: 'bg-blue-500/15 text-blue-600 dark:text-blue-400 border-blue-500/30',
                };
            case 'FLAGGED':
                return {
                    label: 'Perlu Perhatian',
                    className: 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/30 font-black animate-pulse',
                };
            case 'RESOLVED':
                return {
                    label: 'Selesai',
                    className: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
                };
            default:
                return {
                    label: st,
                    className: 'bg-surface-2 text-text-muted border-border',
                };
        }
    };

    return (
        <AdminLayout title="Bot Keluhan & Feedback">
            <Head title="Bot Keluhan & Feedback - Admin SynemaX" />

            <div className="space-y-6">
                {/* Header Section */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-accent">
                            <Bot className="size-4" />
                            <span>Garda Depan Admin</span>
                        </div>
                        <h1 className="mt-1 text-2xl sm:text-3xl font-black text-text">
                            Bot Keluhan & Feedback
                        </h1>
                        <p className="mt-1 text-xs text-text-muted">
                            Pantau laporan keluhan penonton, pengecekan reservasi studio, dan ulasan yang ditangani secara otomatis oleh Bot Telegram 24 jam.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2.5">
                        <button
                            type="button"
                            onClick={() => setCleanupModalOpen(true)}
                            className="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-rose-500/30 bg-rose-500/10 px-3.5 py-2 text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-500/20 active:scale-95 transition"
                            title="Bersihkan data keluhan dan riwayat chat lama"
                        >
                            <Trash2 className="size-3.5" />
                            <span>Bersihkan Data Lama</span>
                        </button>

                        {botInfo.username && (
                            <a
                                href={`https://t.me/${botInfo.username}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-sky-500/30 bg-sky-500/10 px-3.5 py-2 text-xs font-bold text-sky-600 dark:text-sky-400 hover:bg-sky-500/20 active:scale-95 transition"
                            >
                                <Send className="size-3.5" />
                                <span>Buka @{botInfo.username}</span>
                                <ExternalLink className="size-3" />
                            </a>
                        )}

                        <Link
                            href="/admin/pengaturan?tab=notifikasi"
                            className="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface px-3.5 py-2 text-xs font-semibold text-text hover:bg-surface-2 active:scale-95 transition"
                        >
                            <Settings className="size-3.5" />
                            <span>Pengaturan Bot</span>
                        </Link>
                    </div>
                </div>

                {/* Retention Info Banner */}
                <div className="flex flex-wrap items-center justify-between gap-3 p-3.5 rounded-2xl border border-border/80 bg-surface text-xs">
                    <div className="flex items-center gap-2 text-text">
                        <Clock className="size-4 text-amber-500 shrink-0" />
                        <span>
                            <b>Kebijakan Retensi:</b> Data feedback dan tiket obrolan bot otomatis disimpan selama <b>{retentionDays === 0 ? 'Selamanya (Auto-cleanup nonaktif)' : `${retentionDays} hari`}</b> sebelum dibersihkan oleh sistem jadwal harian.
                        </span>
                    </div>
                    <Link
                        href="/admin/pengaturan?tab=notifikasi"
                        className="text-accent font-semibold hover:underline shrink-0"
                    >
                        Ubah Batas Hari Retensi
                    </Link>
                </div>

                {/* Stats Grid */}
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-5">
                    <div className="rounded-2xl border border-border bg-surface p-4 shadow-xs">
                        <div className="flex items-center justify-between">
                            <span className="text-[11px] font-bold uppercase tracking-wider text-text-muted">Total Laporan</span>
                            <MessageSquare className="size-4 text-text-muted" />
                        </div>
                        <p className="mt-2 font-mono text-2xl font-black text-text">{stats.total}</p>
                    </div>

                    <div className="rounded-2xl border border-rose-500/30 bg-rose-500/[0.04] p-4 shadow-xs">
                        <div className="flex items-center justify-between">
                            <span className="text-[11px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Keluhan / Kendala</span>
                            <AlertTriangle className="size-4 text-rose-500" />
                        </div>
                        <p className="mt-2 font-mono text-2xl font-black text-rose-600 dark:text-rose-400">{stats.issues}</p>
                    </div>

                    <div className="rounded-2xl border border-amber-500/30 bg-amber-500/[0.04] p-4 shadow-xs">
                        <div className="flex items-center justify-between">
                            <span className="text-[11px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Cek Booking</span>
                            <Search className="size-4 text-amber-500" />
                        </div>
                        <p className="mt-2 font-mono text-2xl font-black text-amber-600 dark:text-amber-400">{stats.orders}</p>
                    </div>

                    <div className="rounded-2xl border border-emerald-500/30 bg-emerald-500/[0.04] p-4 shadow-xs">
                        <div className="flex items-center justify-between">
                            <span className="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Masukan / Saran</span>
                            <CheckCircle2 className="size-4 text-emerald-500" />
                        </div>
                        <p className="mt-2 font-mono text-2xl font-black text-emerald-600 dark:text-emerald-400">{stats.feedbacks}</p>
                    </div>

                    <div className={`col-span-2 sm:col-span-1 rounded-2xl border p-4 shadow-xs ${
                        stats.flagged > 0
                            ? 'border-rose-500 bg-rose-500/10'
                            : 'border-border bg-surface'
                    }`}>
                        <div className="flex items-center justify-between">
                            <span className="text-[11px] font-bold uppercase tracking-wider text-text-muted">Perlu Perhatian</span>
                            <ShieldAlert className={`size-4 ${stats.flagged > 0 ? 'text-rose-500' : 'text-text-muted'}`} />
                        </div>
                        <p className={`mt-2 font-mono text-2xl font-black ${stats.flagged > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-text'}`}>
                            {stats.flagged}
                        </p>
                    </div>
                </div>

                {/* Filter & Search Bar */}
                <form onSubmit={handleSearchSubmit} className="grid gap-3 rounded-2xl border border-border bg-surface p-4 sm:grid-cols-[1fr_1.2fr_1.2fr_auto_auto] sm:items-end shadow-xs">
                    <div>
                        <label htmlFor="filter_search" className="block text-xs font-bold uppercase tracking-wider text-text-muted mb-1.5">
                            Cari Tiket / Pesan
                        </label>
                        <div className="relative">
                            <input
                                id="filter_search"
                                type="search"
                                placeholder="Pesan, kode booking, username..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full rounded-xl border border-border bg-bg pl-9 pr-3 py-2.5 text-xs sm:text-sm text-text focus:border-amber-400 focus:outline-none"
                            />
                            <Search className="absolute left-3 top-3 size-4 text-text-muted" />
                        </div>
                    </div>

                    <div>
                        <label htmlFor="filter_cat" className="block text-xs font-bold uppercase tracking-wider text-text-muted mb-1.5">
                            Kategori
                        </label>
                        <select
                            id="filter_cat"
                            value={categoryFilter}
                            onChange={(e) => {
                                setCategoryFilter(e.target.value);
                                handleFilter(e.target.value, undefined);
                            }}
                            className="w-full rounded-xl border border-border bg-bg px-3 py-2.5 text-xs sm:text-sm text-text focus:border-amber-400 focus:outline-none"
                        >
                            <option value="all">Semua Kategori</option>
                            <option value="ISSUE_REPORT">Keluhan / Kendala</option>
                            <option value="ORDER_INQUIRY">Cek Status Booking</option>
                            <option value="FEEDBACK">Masukan / Saran</option>
                            <option value="GENERAL">Bantuan Umum</option>
                        </select>
                    </div>

                    <div>
                        <label htmlFor="filter_st" className="block text-xs font-bold uppercase tracking-wider text-text-muted mb-1.5">
                            Status
                        </label>
                        <select
                            id="filter_st"
                            value={statusFilter}
                            onChange={(e) => {
                                setStatusFilter(e.target.value);
                                handleFilter(undefined, e.target.value);
                            }}
                            className="w-full rounded-xl border border-border bg-bg px-3 py-2.5 text-xs sm:text-sm text-text focus:border-amber-400 focus:outline-none"
                        >
                            <option value="all">Semua Status</option>
                            <option value="AUTO_RESOLVED">Ditangani Bot Otomatis</option>
                            <option value="FLAGGED">Perlu Perhatian Admin</option>
                            <option value="RESOLVED">Selesai</option>
                        </select>
                    </div>

                    <button
                        type="submit"
                        className="min-h-11 rounded-xl bg-accent px-5 text-xs sm:text-sm font-bold text-accent-contrast hover:opacity-90 active:scale-95 transition"
                    >
                        Filter
                    </button>

                    {(search || categoryFilter !== 'all' || statusFilter !== 'all') && (
                        <button
                            type="button"
                            onClick={handleReset}
                            className="min-h-11 inline-flex items-center justify-center rounded-xl border border-border px-4 text-xs sm:text-sm font-semibold hover:bg-surface-2 active:scale-95 transition"
                        >
                            Reset
                        </button>
                    )}
                </form>

                {/* List of Feedback Items */}
                <div className="space-y-4">
                    {feedbacks?.data && feedbacks.data.length > 0 ? (
                        feedbacks.data.map((item) => {
                            const catBadge = getCategoryBadge(item.category);
                            const stBadge = getStatusBadge(item.status);
                            const CatIcon = catBadge.icon;
                            const ticketCode = `TKT-${String(item.id).padStart(5, '0')}`;

                            return (
                                <article
                                    key={item.id}
                                    className={`rounded-2xl border p-5 transition shadow-xs ${
                                        item.status === 'FLAGGED'
                                            ? 'border-rose-500/60 bg-rose-500/[0.03]'
                                            : 'border-border bg-surface hover:border-amber-500/40'
                                    }`}
                                >
                                    {/* Top Row: Ticket Code, Date, Badges */}
                                    <div className="flex flex-wrap items-center justify-between gap-2.5 pb-3 border-b border-border/70">
                                        <div className="flex items-center gap-2 flex-wrap">
                                            <span className="font-mono text-xs font-black text-amber-600 dark:text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded-lg">
                                                #{ticketCode}
                                            </span>
                                            <span className={`inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border ${catBadge.className}`}>
                                                <CatIcon className="size-3" />
                                                <span>{catBadge.label}</span>
                                            </span>
                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border ${stBadge.className}`}>
                                                {stBadge.label}
                                            </span>
                                        </div>

                                        <span className="text-[11px] font-medium text-text-muted">
                                            {item.created_at ? new Date(item.created_at).toLocaleString('id-ID', {
                                                day: 'numeric',
                                                month: 'short',
                                                year: 'numeric',
                                                hour: '2-digit',
                                                minute: '2-digit'
                                            }) + ' WIB' : '-'}
                                        </span>
                                    </div>

                                    {/* Middle Section: User & Content */}
                                    <div className="mt-3.5 space-y-3">
                                        {/* User Details */}
                                        <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-text-muted">
                                            <div className="flex items-center gap-1.5 font-bold text-text">
                                                <User className="size-3.5 text-text-muted" />
                                                <span>{item.sender_name || 'Penonton Telegram'}</span>
                                            </div>

                                            {item.telegram_username && (
                                                <a
                                                    href={`https://t.me/${item.telegram_username}`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex items-center gap-1 text-sky-600 dark:text-sky-400 hover:underline font-semibold"
                                                >
                                                    <span>@{item.telegram_username}</span>
                                                    <ExternalLink className="size-2.5" />
                                                </a>
                                            )}

                                            {item.telegram_user_id && (
                                                <span className="font-mono text-[11px] text-text-subtle">
                                                    ID: {item.telegram_user_id}
                                                </span>
                                            )}

                                            {item.booking_code && (
                                                <Link
                                                    href={`/admin/booking?q=${item.booking_code}`}
                                                    className="inline-flex items-center gap-1 font-mono font-bold text-amber-600 dark:text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-md hover:underline"
                                                    title="Lihat detail booking"
                                                >
                                                    <span>Booking: {item.booking_code}</span>
                                                    <ExternalLink className="size-2.5" />
                                                </Link>
                                            )}
                                        </div>

                                        {/* Message Content */}
                                        <div className="rounded-xl border border-border/80 bg-surface-2/60 p-3.5 text-xs text-text leading-relaxed">
                                            <p className="font-semibold text-text whitespace-pre-wrap">{item.message}</p>
                                        </div>

                                        {/* Bot Auto-Reply Preview */}
                                        {item.bot_response && (
                                            <div className="flex items-start gap-2 text-xs text-text-muted bg-surface-2/30 rounded-xl p-2.5 border border-border/40">
                                                <Bot className="size-3.5 text-accent shrink-0 mt-0.5" />
                                                <div className="min-w-0">
                                                    <span className="font-bold text-[11px] uppercase tracking-wider text-text-muted block">Respon Otomatis Bot:</span>
                                                    <p className="text-text-muted truncate mt-0.5">{item.bot_response}</p>
                                                </div>
                                            </div>
                                        )}

                                        {/* Admin Note If Any */}
                                        {item.admin_note && (
                                            <div className="rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-900 dark:text-amber-200">
                                                <p className="font-bold">Catatan Internal Admin:</p>
                                                <p className="mt-0.5 whitespace-pre-wrap">{item.admin_note}</p>
                                            </div>
                                        )}
                                    </div>

                                    {/* Action Bar */}
                                    <div className="mt-4 pt-3 border-t border-border/70 flex flex-wrap items-center justify-between gap-2.5">
                                        <div className="flex flex-wrap items-center gap-2">
                                            {item.status !== 'RESOLVED' && (
                                                <button
                                                    type="button"
                                                    onClick={() => handleQuickStatus(item, 'RESOLVED')}
                                                    className="inline-flex min-h-[34px] items-center gap-1.5 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-3 text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/20 active:scale-95 transition"
                                                >
                                                    <Check className="size-3" />
                                                    <span>Tandai Selesai</span>
                                                </button>
                                            )}

                                            {item.status !== 'FLAGGED' && (
                                                <button
                                                    type="button"
                                                    onClick={() => handleQuickStatus(item, 'FLAGGED')}
                                                    className="inline-flex min-h-[34px] items-center gap-1.5 rounded-xl border border-rose-500/40 bg-rose-500/10 px-3 text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-500/20 active:scale-95 transition"
                                                >
                                                    <ShieldAlert className="size-3" />
                                                    <span>Perlu Perhatian</span>
                                                </button>
                                            )}

                                            <button
                                                type="button"
                                                onClick={() => handleOpenNoteModal(item)}
                                                className="inline-flex min-h-[34px] items-center gap-1.5 rounded-xl border border-border bg-surface px-3 text-xs font-semibold text-text hover:bg-surface-2 active:scale-95 transition"
                                            >
                                                <Edit3 className="size-3" />
                                                <span>{item.admin_note ? 'Ubah Catatan' : 'Tambah Catatan'}</span>
                                            </button>
                                        </div>

                                        <button
                                            type="button"
                                            onClick={() => setDeleteModalItem(item)}
                                            className="inline-flex min-h-[34px] items-center gap-1 rounded-xl border border-rose-500/30 bg-rose-500/10 px-3 text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-500/20 active:scale-95 transition ml-auto"
                                            title="Hapus data tiket laporan ini"
                                        >
                                            <Trash2 className="size-3" />
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                </article>
                            );
                        })
                    ) : (
                        <div className="rounded-2xl border border-border bg-surface p-12 text-center">
                            <Bot className="size-12 text-text-muted mx-auto mb-3 opacity-40" />
                            <h3 className="text-base font-bold text-text">Belum Ada Pesan Keluhan atau Feedback</h3>
                            <p className="mt-1 text-xs text-text-muted max-w-sm mx-auto">
                                Seluruh interaksi dari penonton melalui Bot Telegram akan tercatat otomatis di sini.
                            </p>
                            {botInfo.username && (
                                <a
                                    href={`https://t.me/${botInfo.username}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center gap-1.5 rounded-xl bg-accent px-4 py-2 text-xs font-bold text-accent-contrast mt-4 hover:opacity-90 transition"
                                >
                                    <Send className="size-3.5" />
                                    <span>Tes Kirim Pesan ke Bot @{botInfo.username}</span>
                                </a>
                            )}
                        </div>
                    )}
                </div>

                {/* Pagination */}
                {feedbacks?.links && feedbacks.links.length > 3 && (
                    <div className="flex flex-wrap items-center justify-center gap-1.5 pt-4">
                        {feedbacks.links.map((link, idx) => (
                            <Link
                                key={idx}
                                href={link.url || '#'}
                                preserveScroll
                                dangerouslySetInnerHTML={{ __html: link.label }}
                                className={`min-h-[36px] min-w-[36px] inline-flex items-center justify-center rounded-xl px-3 text-xs font-semibold transition ${
                                    link.active
                                        ? 'bg-accent text-accent-contrast font-bold'
                                        : link.url
                                        ? 'border border-border bg-surface text-text hover:bg-surface-2'
                                        : 'text-text-muted opacity-40 pointer-events-none'
                                }`}
                            />
                        ))}
                    </div>
                )}
            </div>

            {/* Modal Tambah/Ubah Catatan Admin */}
            <Modal show={noteModalOpen} onClose={() => setNoteModalOpen(false)} maxWidth="md">
                <form onSubmit={handleSaveNote} className="p-6 space-y-4">
                    <div className="flex items-start gap-3">
                        <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/15 text-amber-500">
                            <Edit3 className="size-5" />
                        </div>
                        <div>
                            <h3 className="text-base font-black text-text">
                                Catatan Admin #{selectedFeedback ? `TKT-${String(selectedFeedback.id).padStart(5, '0')}` : ''}
                            </h3>
                            <p className="mt-0.5 text-xs text-text-muted">
                                Tambahkan catatan penanganan atau tindak lanjut tim untuk laporan ini.
                            </p>
                        </div>
                    </div>

                    <div>
                        <textarea
                            rows={4}
                            value={adminNote}
                            onChange={(e) => setAdminNote(e.target.value)}
                            placeholder="Tuliskan catatan internal di sini..."
                            className="w-full rounded-xl border border-border bg-surface-2 p-3 text-xs text-text focus:border-amber-400 focus:outline-none"
                        />
                    </div>

                    <div className="flex items-center justify-end gap-2.5 pt-2 border-t border-border">
                        <button
                            type="button"
                            onClick={() => setNoteModalOpen(false)}
                            className="min-h-10 rounded-xl border border-border bg-surface px-4 text-xs font-semibold text-text hover:bg-surface-2 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={isSubmitting}
                            className="btn-gold-cinema inline-flex min-h-10 items-center justify-center rounded-xl px-5 text-xs font-bold disabled:opacity-50"
                        >
                            <span>{isSubmitting ? 'Menyimpan...' : 'Simpan Catatan'}</span>
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Modal Konfirmasi Hapus */}
            <Modal show={Boolean(deleteModalItem)} onClose={() => setDeleteModalItem(null)} maxWidth="md">
                <div className="p-6 space-y-4">
                    <div className="flex items-start gap-3.5">
                        <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-rose-500/15 text-rose-500">
                            <Trash2 className="size-6" />
                        </div>
                        <div>
                            <h3 className="text-base font-black text-text">
                                Hapus Tiket #{deleteModalItem ? `TKT-${String(deleteModalItem.id).padStart(5, '0')}` : ''}?
                            </h3>
                            <p className="mt-1 text-xs text-text-muted leading-relaxed">
                                Apakah Anda yakin ingin menghapus data laporan keluhan / feedback ini? Tindakan ini tidak dapat dibatalkan.
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center justify-end gap-2.5 pt-3 border-t border-border">
                        <button
                            type="button"
                            onClick={() => setDeleteModalItem(null)}
                            className="min-h-10 rounded-xl border border-border bg-surface px-4 text-xs font-semibold text-text hover:bg-surface-2 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            disabled={isSubmitting}
                            onClick={handleDelete}
                            className="min-h-10 rounded-xl bg-rose-600 px-4 text-xs font-bold text-white hover:bg-rose-500 active:scale-95 transition disabled:opacity-50 inline-flex items-center gap-1.5"
                        >
                            <Trash2 className="size-3.5" />
                            <span>{isSubmitting ? 'Menghapus...' : 'Ya, Hapus'}</span>
                        </button>
                    </div>
                </div>
            </Modal>

            {/* Modal Bersihkan Data Lama */}
            <Modal show={cleanupModalOpen} onClose={() => setCleanupModalOpen(false)} maxWidth="md">
                <div className="p-6 space-y-4">
                    <div className="flex items-start gap-3.5">
                        <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-amber-500/15 text-amber-500">
                            <Trash2 className="size-6" />
                        </div>
                        <div>
                            <h3 className="text-base font-black text-text">
                                Bersihkan Data Feedback & Keluhan Lama
                            </h3>
                            <p className="mt-1 text-xs text-text-muted leading-relaxed">
                                Hapus riwayat chat bot CS dan tiket keluhan penonton yang lebih lama dari rentang waktu tertentu untuk menjaga database tetap rapi.
                            </p>
                        </div>
                    </div>

                    <div>
                        <label htmlFor="cleanup_days_select" className="block text-xs font-bold text-text mb-1">
                            Pilih Batas Hari
                        </label>
                        <select
                            id="cleanup_days_select"
                            value={cleanupDays}
                            onChange={(e) => setCleanupDays(parseInt(e.target.value, 10) || 30)}
                            className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                        >
                            <option value={7}>Lebih lama dari 7 Hari</option>
                            <option value={14}>Lebih lama dari 14 Hari</option>
                            <option value={30}>Lebih lama dari 30 Hari (Default)</option>
                            <option value={60}>Lebih lama dari 60 Hari</option>
                            <option value={90}>Lebih lama dari 90 Hari</option>
                        </select>
                    </div>

                    <div className="flex items-center justify-end gap-2.5 pt-3 border-t border-border">
                        <button
                            type="button"
                            onClick={() => setCleanupModalOpen(false)}
                            className="min-h-10 rounded-xl border border-border bg-surface px-4 text-xs font-semibold text-text hover:bg-surface-2 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            disabled={isSubmitting}
                            onClick={handleCleanup}
                            className="min-h-10 rounded-xl bg-rose-600 px-4 text-xs font-bold text-white hover:bg-rose-500 active:scale-95 transition disabled:opacity-50 inline-flex items-center gap-1.5"
                        >
                            <Trash2 className="size-3.5" />
                            <span>{isSubmitting ? 'Membersihkan...' : 'Bersihkan Sekarang'}</span>
                        </button>
                    </div>
                </div>
            </Modal>
        </AdminLayout>
    );
}
