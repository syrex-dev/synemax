import { useState } from 'react';
import { Link, Head, router, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import Pagination from '../../../Components/Pagination';
import Modal from '../../../Components/Modal';
import {
    Calendar,
    Plus,
    Eye,
    Edit3,
    Trash2,
    Pin,
    AlertCircle,
    X,
    Clock,
    MapPin,
    Tv,
} from 'lucide-react';

export default function Index({
    events,
    currentFilter = '',
}) {
    const eventList = events?.data || [];
    const [statusModalOpen, setStatusModalOpen] = useState(false);
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [selectedEvent, setSelectedEvent] = useState(null);

    const statusForm = useForm({
        status_acara: 'aktif',
        alasan_status: '',
    });

    const filterTabs = [
        { key: '', label: 'Semua Acara' },
        { key: 'terbit', label: 'Terbit' },
        { key: 'draft', label: 'Draft' },
        { key: 'ditunda', label: 'Ditunda' },
        { key: 'dibatalkan', label: 'Dibatalkan' },
    ];

    const handleOpenStatusModal = (event) => {
        setSelectedEvent(event);
        statusForm.setData({
            status_acara: event.status_acara || 'aktif',
            alasan_status: event.alasan_status || '',
        });
        setStatusModalOpen(true);
    };

    const handleSaveStatus = (e) => {
        e.preventDefault();
        if (!selectedEvent) return;
        statusForm.patch(`/admin/nobar/${selectedEvent.id}/status`, {
            preserveScroll: true,
            onSuccess: () => {
                setStatusModalOpen(false);
                setSelectedEvent(null);
            },
        });
    };

    const handleTogglePin = (event) => {
        router.post(`/admin/nobar/${event.id}/pin`, {}, {
            preserveScroll: true,
        });
    };

    const handleDelete = (event) => {
        setSelectedEvent(event);
        setDeleteModalOpen(true);
    };

    const confirmDelete = () => {
        if (!selectedEvent) return;
        router.delete(`/admin/nobar/${selectedEvent.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteModalOpen(false);
                setSelectedEvent(null);
            },
        });
    };

    return (
        <AdminLayout title="Jadwal Nobar">
            <Head title="Jadwal Nobar | Admin SynemaX" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="text-xs font-bold uppercase tracking-wider text-amber-500 mb-1">
                            Komunitas & Siaran
                        </div>
                        <h1 className="text-2xl font-black tracking-tight text-text flex items-center gap-2.5">
                            <Tv className="size-6 text-amber-500" />
                            <span>Jadwal Nonton Bareng</span>
                        </h1>
                        <p className="text-xs text-text-muted mt-1 max-w-xl">
                            Kelola jadwal nobar sepak bola, balapan, dan siaran langsung untuk komunitas bioskop.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href="/admin/nobar/buat"
                            className="inline-flex min-h-[42px] items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition-colors"
                        >
                            <Plus className="size-4" />
                            <span>Tambah Acara Nobar</span>
                        </Link>
                    </div>
                </div>

                {/* Status Filter Tabs */}
                <div className="flex flex-wrap gap-2">
                    {filterTabs.map((tab) => {
                        const isActive = currentFilter === tab.key;
                        return (
                            <Link
                                key={tab.key}
                                href={tab.key ? `/admin/nobar?status=${tab.key}` : '/admin/nobar'}
                                className={`inline-flex min-h-[38px] items-center rounded-xl px-4 text-xs font-bold transition-colors ${
                                    isActive
                                        ? 'bg-amber-500 text-slate-950 shadow-xs'
                                        : 'border border-border bg-surface text-text-muted hover:text-text'
                                }`}
                            >
                                {tab.label}
                            </Link>
                        );
                    })}
                </div>

                {/* Main List */}
                <div className="rounded-2xl border border-border bg-surface overflow-hidden shadow-xs">
                    {eventList.length > 0 ? (
                        <>
                            {/* Desktop Table */}
                            <div className="hidden lg:block overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead>
                                        <tr className="border-b border-border bg-surface-2 text-text-muted uppercase tracking-wider text-[11px] font-bold">
                                            <th className="py-3 px-4 w-12 text-center">Pin</th>
                                            <th className="py-3 px-4">Poster & Acara</th>
                                            <th className="py-3 px-4 w-44">Waktu Kick-off</th>
                                            <th className="py-3 px-4 w-32 text-center">Publikasi</th>
                                            <th className="py-3 px-4 w-32 text-center">Status Acara</th>
                                            <th className="py-3 px-4 w-36 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border">
                                        {eventList.map((event) => {
                                            const posterUrl = event.poster_path ? `/storage/${event.poster_path}` : null;

                                            return (
                                                <tr key={event.id} className="hover:bg-surface-2/60 transition-colors">
                                                    {/* Pin */}
                                                    <td className="py-3 px-4 text-center">
                                                        <button
                                                            type="button"
                                                            onClick={() => handleTogglePin(event)}
                                                            className={`p-1.5 rounded-lg transition ${
                                                                event.is_pinned
                                                                    ? 'text-amber-500 bg-amber-500/10'
                                                                    : 'text-text-muted hover:text-text'
                                                            }`}
                                                            title={event.is_pinned ? 'Lepas Pin' : 'Pin ke Atas'}
                                                        >
                                                            <Pin className="size-4" />
                                                        </button>
                                                    </td>

                                                    {/* Poster & Title */}
                                                    <td className="py-3 px-4">
                                                        <div className="flex items-center gap-3">
                                                            {posterUrl ? (
                                                                <img
                                                                    src={posterUrl}
                                                                    alt={event.judul}
                                                                    className="h-14 w-20 shrink-0 rounded-lg object-cover border border-border"
                                                                />
                                                            ) : (
                                                                <div className="h-14 w-20 shrink-0 rounded-lg border border-border bg-surface-2 flex items-center justify-center text-[10px] font-semibold text-text-muted text-center p-1">
                                                                    Tanpa Poster
                                                                </div>
                                                            )}
                                                            <div className="min-w-0 flex-1">
                                                                <Link
                                                                    href={`/admin/nobar/${event.id}/ubah`}
                                                                    className="font-bold text-text hover:text-amber-500 line-clamp-1 text-sm"
                                                                >
                                                                    {event.judul}
                                                                </Link>
                                                                <div className="flex items-center gap-2 mt-1 text-[11px] text-text-muted">
                                                                    {event.liga_event && (
                                                                        <span className="font-semibold text-text">
                                                                            {event.liga_event}
                                                                        </span>
                                                                    )}
                                                                    <span>•</span>
                                                                    <span>{event.is_free ? 'Gratis' : 'Berbayar'}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    {/* Kickoff */}
                                                    <td className="py-3 px-4">
                                                        <div className="font-semibold text-text">
                                                            {event.kickoff_at}
                                                        </div>
                                                        <div className="text-[11px] text-text-muted mt-0.5">
                                                            Durasi: {event.durasi_menit || 120} mnt
                                                        </div>
                                                    </td>

                                                    {/* Status Publikasi */}
                                                    <td className="py-3 px-4 text-center">
                                                        <span
                                                            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold ${
                                                                event.status_publikasi === 'terbit'
                                                                    ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30'
                                                                    : 'bg-surface-2 text-text-muted border border-border'
                                                            }`}
                                                        >
                                                            {event.status_publikasi === 'terbit' ? 'Terbit' : 'Draft'}
                                                        </span>
                                                    </td>

                                                    {/* Status Acara */}
                                                    <td className="py-3 px-4 text-center">
                                                        <button
                                                            type="button"
                                                            onClick={() => handleOpenStatusModal(event)}
                                                            className="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-bold border border-border bg-surface-2 text-text hover:border-amber-500 transition"
                                                        >
                                                            <span>{event.status_acara}</span>
                                                        </button>
                                                    </td>

                                                    {/* Actions */}
                                                    <td className="py-3 px-4 text-right">
                                                        <div className="flex items-center justify-end gap-1.5">
                                                            {event.status_publikasi === 'terbit' && (
                                                                <a
                                                                    href={`/nobar/${event.slug}`}
                                                                    target="_blank"
                                                                    rel="noreferrer"
                                                                    className="p-1.5 rounded-lg border border-border text-text-muted hover:text-text hover:border-amber-500 transition"
                                                                    title="Lihat Halaman Publik"
                                                                >
                                                                    <Eye className="size-3.5" />
                                                                </a>
                                                            )}
                                                            <Link
                                                                href={`/admin/nobar/${event.id}/ubah`}
                                                                className="px-2.5 py-1 rounded-lg border border-border font-semibold text-text hover:border-amber-500 hover:text-amber-500 transition"
                                                            >
                                                                Ubah
                                                            </Link>
                                                            <button
                                                                type="button"
                                                                onClick={() => handleDelete(event)}
                                                                className="p-1.5 rounded-lg border border-rose-500/30 bg-rose-500/10 text-rose-600 dark:text-rose-400 hover:bg-rose-500/20 transition"
                                                                title="Hapus Acara"
                                                            >
                                                                <Trash2 className="size-3.5" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>

                            {/* Mobile View */}
                            <div className="lg:hidden divide-y divide-border">
                                {eventList.map((event) => {
                                    const posterUrl = event.poster_path ? `/storage/${event.poster_path}` : null;
                                    return (
                                        <div key={event.id} className="p-4 space-y-3">
                                            <div className="flex items-start gap-3">
                                                {posterUrl && (
                                                    <img
                                                        src={posterUrl}
                                                        alt={event.judul}
                                                        className="h-16 w-24 shrink-0 rounded-lg object-cover border border-border"
                                                    />
                                                )}
                                                <div className="min-w-0 flex-1">
                                                    <h3 className="font-bold text-text text-sm line-clamp-2">{event.judul}</h3>
                                                    <div className="text-xs text-text-muted mt-1">
                                                        {event.kickoff_at}
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="flex items-center justify-between pt-2 border-t border-border/60 text-xs">
                                                <div className="flex items-center gap-1.5">
                                                    <span className="px-2 py-0.5 rounded-full border border-border bg-surface-2 font-bold text-[10px]">
                                                        {event.status_acara}
                                                    </span>
                                                </div>

                                                <div className="flex items-center gap-2">
                                                    <Link
                                                        href={`/admin/nobar/${event.id}/ubah`}
                                                        className="px-3 py-1 rounded-lg border border-border font-semibold text-text"
                                                    >
                                                        Ubah
                                                    </Link>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleDelete(event)}
                                                        className="p-1 rounded-lg border border-rose-500/30 text-rose-500"
                                                    >
                                                        <Trash2 className="size-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>

                            {/* Pagination */}
                            {events.links && (
                                <div className="p-4 border-t border-border">
                                    <Pagination links={events.links} />
                                </div>
                            )}
                        </>
                    ) : (
                        <div className="p-12 text-center space-y-3">
                            <Tv className="size-10 text-text-muted mx-auto opacity-50" />
                            <h3 className="font-bold text-text">Belum ada acara nobar</h3>
                            <p className="text-xs text-text-muted max-w-sm mx-auto">
                                Tambahkan jadwal nobar pertandingan sepak bola untuk komunitas Anda.
                            </p>
                            <Link
                                href="/admin/nobar/buat"
                                className="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition"
                            >
                                <Plus className="size-4" />
                                <span>Tambah Acara Nobar</span>
                            </Link>
                        </div>
                    )}
                </div>
            </div>

            {/* Modal Ganti Status Acara */}
            <Modal show={statusModalOpen} onClose={() => setStatusModalOpen(false)} maxWidth="sm">
                <form onSubmit={handleSaveStatus} className="p-6 space-y-4">
                    <div className="flex items-center justify-between pb-3 border-b border-border">
                        <h2 className="text-base font-bold text-text">Ubah Status Acara</h2>
                        <button
                            type="button"
                            onClick={() => setStatusModalOpen(false)}
                            className="p-1 rounded-lg text-text-muted hover:text-text"
                        >
                            <X className="size-5" />
                        </button>
                    </div>

                    <div className="space-y-4 text-xs">
                        <div>
                            <label className="block font-bold text-text mb-1">Status Acara</label>
                            <select
                                value={statusForm.data.status_acara}
                                onChange={(e) => statusForm.setData('status_acara', e.target.value)}
                                className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs font-bold text-text focus:border-amber-500 focus:outline-none"
                            >
                                <option value="aktif">Aktif (Sesuai Jadwal)</option>
                                <option value="ditunda">Ditunda</option>
                                <option value="dibatalkan">Dibatalkan</option>
                                <option value="selesai">Selesai</option>
                            </select>
                        </div>

                        <div>
                            <label className="block font-bold text-text mb-1">Alasan / Catatan Penundaan</label>
                            <textarea
                                rows={3}
                                value={statusForm.data.alasan_status}
                                onChange={(e) => statusForm.setData('alasan_status', e.target.value)}
                                placeholder="Tuliskan alasan jika ditunda atau dibatalkan..."
                                className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div className="flex items-center justify-end gap-2 pt-3 border-t border-border">
                        <button
                            type="button"
                            onClick={() => setStatusModalOpen(false)}
                            className="rounded-xl border border-border px-4 py-2 text-xs font-semibold text-text hover:bg-surface-2"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={statusForm.processing}
                            className="rounded-xl bg-amber-500 px-5 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400"
                        >
                            Simpan Status
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Modal Hapus */}
            <Modal show={deleteModalOpen} onClose={() => setDeleteModalOpen(false)} maxWidth="sm">
                <div className="p-6 space-y-4">
                    <div className="flex items-center justify-between pb-3 border-b border-border">
                        <h2 className="text-base font-bold text-rose-500 flex items-center gap-2">
                            <Trash2 className="size-5" />
                            <span>Hapus Acara Nobar</span>
                        </h2>
                        <button
                            type="button"
                            onClick={() => setDeleteModalOpen(false)}
                            className="p-1 rounded-lg text-text-muted hover:text-text"
                        >
                            <X className="size-5" />
                        </button>
                    </div>

                    <p className="text-xs text-text leading-relaxed">
                        Hapus acara nobar <strong className="text-text font-bold">"{selectedEvent?.judul}"</strong>?
                    </p>

                    <div className="flex items-center justify-end gap-2 pt-3 border-t border-border">
                        <button
                            type="button"
                            onClick={() => setDeleteModalOpen(false)}
                            className="rounded-xl border border-border px-4 py-2 text-xs font-semibold text-text hover:bg-surface-2"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            onClick={confirmDelete}
                            className="rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white hover:bg-rose-500"
                        >
                            Ya, Hapus
                        </button>
                    </div>
                </div>
            </Modal>
        </AdminLayout>
    );
}

