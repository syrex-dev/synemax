import { useState } from 'react';
import { Link, Head, useForm, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import Modal from '../../../Components/Modal';
import {
    ShieldAlert,
    Plus,
    ArrowLeft,
    ChevronUp,
    ChevronDown,
    Edit3,
    Trash2,
    Check,
    X,
    AlertCircle,
    Film,
} from 'lucide-react';

export default function Index({
    ageRatings = [],
    activeCount = 0,
}) {
    const [addModalOpen, setAddModalOpen] = useState(false);
    const [editModalOpen, setEditModalOpen] = useState(false);
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [selectedRating, setSelectedRating] = useState(null);

    // Add Form
    const addForm = useForm({
        code: '',
        label: '',
        min_age: 0,
        description: '',
        is_active: true,
    });

    // Edit Form
    const editForm = useForm({
        code: '',
        label: '',
        min_age: 0,
        description: '',
        is_active: true,
    });

    // Delete Form
    const deleteForm = useForm({
        action: 'delete',
        target_code: '',
    });

    const handleOpenEdit = (rating) => {
        setSelectedRating(rating);
        editForm.setData({
            code: rating.code,
            label: rating.label,
            min_age: rating.min_age,
            description: rating.description || '',
            is_active: Boolean(rating.is_active),
        });
        setEditModalOpen(true);
    };

    const handleOpenDelete = (rating) => {
        setSelectedRating(rating);
        deleteForm.setData({
            action: (rating.films_count || 0) > 0 ? 'reassign' : 'delete',
            target_code: '',
        });
        setDeleteModalOpen(true);
    };

    const handleAddSubmit = (e) => {
        e.preventDefault();
        addForm.post('/admin/age-ratings', {
            preserveScroll: true,
            onSuccess: () => {
                setAddModalOpen(false);
                addForm.reset();
            },
        });
    };

    const handleEditSubmit = (e) => {
        e.preventDefault();
        if (!selectedRating) return;
        editForm.put(`/admin/age-ratings/${selectedRating.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setEditModalOpen(false);
                setSelectedRating(null);
            },
        });
    };

    const handleDeleteSubmit = (e) => {
        e.preventDefault();
        if (!selectedRating) return;
        deleteForm.delete(`/admin/age-ratings/${selectedRating.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteModalOpen(false);
                setSelectedRating(null);
            },
        });
    };

    const handleToggle = (rating) => {
        router.patch(`/admin/age-ratings/${rating.id}/toggle`, {}, {
            preserveScroll: true,
        });
    };

    const handleReorder = (id, direction) => {
        router.post('/admin/age-ratings/reorder', { id, direction }, {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout title="Klasifikasi Usia">
            <Head title="Klasifikasi Usia | Admin SynemaX" />

            <div className="space-y-6">
                {/* Header & Actions */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-text-muted mb-1">
                            <Link href="/admin/films" className="hover:text-amber-500 transition-colors">
                                Katalog Film
                            </Link>
                            <span>/</span>
                            <span className="text-text">Klasifikasi Usia</span>
                        </div>
                        <h1 className="text-2xl font-black tracking-tight text-text flex items-center gap-2.5">
                            <ShieldAlert className="size-6 text-amber-500" />
                            <span>Klasifikasi Usia</span>
                            <span className="text-xs font-bold text-amber-600 dark:text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-0.5 rounded-full">
                                {activeCount} aktif / {ageRatings.length} total
                            </span>
                        </h1>
                        <p className="text-xs text-text-muted mt-1">
                            Kelola batas umur penonton (SU, 13+, 17+, dll.). Data digunakan pada katalog, jadwal, form film, dan tiket.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href="/admin/films"
                            className="inline-flex min-h-[42px] items-center gap-1.5 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors shadow-xs"
                        >
                            <ArrowLeft className="size-4" />
                            <span>Kembali ke Film</span>
                        </Link>
                        <button
                            type="button"
                            onClick={() => {
                                addForm.reset();
                                setAddModalOpen(true);
                            }}
                            className="inline-flex min-h-[42px] items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition-colors shadow-xs"
                        >
                            <Plus className="size-4" />
                            <span>Tambah Klasifikasi</span>
                        </button>
                    </div>
                </div>

                {/* Table & List Card */}
                <div className="rounded-2xl border border-border bg-surface overflow-hidden shadow-xs">
                    {/* Desktop Table View */}
                    <div className="hidden md:block overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead>
                                <tr className="border-b border-border bg-surface-2 text-text-muted uppercase tracking-wider text-[11px] font-bold">
                                    <th className="py-3 px-4 w-16 text-center">Urutan</th>
                                    <th className="py-3 px-4 w-28">Kode</th>
                                    <th className="py-3 px-4">Label</th>
                                    <th className="py-3 px-4 w-32">Usia Minimal</th>
                                    <th className="py-3 px-4 w-28 text-center">Jumlah Film</th>
                                    <th className="py-3 px-4 w-28 text-center">Status</th>
                                    <th className="py-3 px-4 w-36 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {ageRatings.length > 0 ? (
                                    ageRatings.map((rating, index) => (
                                        <tr key={rating.id} className="hover:bg-surface-2/60 transition-colors">
                                            {/* Reorder */}
                                            <td className="py-3 px-4 text-center">
                                                <div className="flex items-center justify-center gap-1">
                                                    <button
                                                        type="button"
                                                        onClick={() => handleReorder(rating.id, 'up')}
                                                        disabled={index === 0}
                                                        className="rounded p-1 text-text-muted hover:text-amber-500 disabled:opacity-20 disabled:cursor-not-allowed"
                                                        title="Geser Naik"
                                                    >
                                                        <ChevronUp className="size-3.5" />
                                                    </button>
                                                    <span className="font-mono text-text-muted text-[11px] w-4 text-center">
                                                        {rating.sort_order}
                                                    </span>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleReorder(rating.id, 'down')}
                                                        disabled={index === ageRatings.length - 1}
                                                        className="rounded p-1 text-text-muted hover:text-amber-500 disabled:opacity-20 disabled:cursor-not-allowed"
                                                        title="Geser Turun"
                                                    >
                                                        <ChevronDown className="size-3.5" />
                                                    </button>
                                                </div>
                                            </td>

                                            {/* Code */}
                                            <td className="py-3 px-4 font-black">
                                                <span className="inline-flex items-center rounded-md border border-border bg-surface-2 px-2.5 py-1 text-xs font-black tracking-wide text-amber-500 font-mono">
                                                    {rating.code}
                                                </span>
                                            </td>

                                            {/* Label & Description */}
                                            <td className="py-3 px-4">
                                                <div className="font-bold text-text">{rating.label}</div>
                                                {rating.description && (
                                                    <div className="text-[11px] text-text-muted line-clamp-1 mt-0.5">
                                                        {rating.description}
                                                    </div>
                                                )}
                                            </td>

                                            {/* Min Age */}
                                            <td className="py-3 px-4">
                                                <span className="font-semibold text-text">
                                                    {rating.min_age === 0 ? 'Semua usia' : `≥ ${rating.min_age} tahun`}
                                                </span>
                                            </td>

                                            {/* Film Count */}
                                            <td className="py-3 px-4 text-center">
                                                {(rating.films_count || 0) > 0 ? (
                                                    <span className="inline-flex items-center rounded-full bg-surface-2 border border-border px-2.5 py-0.5 text-[11px] font-bold text-text">
                                                        {rating.films_count} film
                                                    </span>
                                                ) : (
                                                    <span className="text-text-muted text-[11px]">0 film</span>
                                                )}
                                            </td>

                                            {/* Status */}
                                            <td className="py-3 px-4 text-center">
                                                <button
                                                    type="button"
                                                    onClick={() => handleToggle(rating)}
                                                    className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold transition-colors ${
                                                        rating.is_active
                                                            ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/20'
                                                            : 'bg-surface-2 text-text-muted border border-border hover:text-text'
                                                    }`}
                                                    title="Klik untuk ubah status aktif"
                                                >
                                                    <span
                                                        className={`size-1.5 rounded-full ${
                                                            rating.is_active ? 'bg-emerald-500' : 'bg-text-muted'
                                                        }`}
                                                    />
                                                    <span>{rating.is_active ? 'Aktif' : 'Nonaktif'}</span>
                                                </button>
                                            </td>

                                            {/* Actions */}
                                            <td className="py-3 px-4 text-right">
                                                <div className="flex items-center justify-end gap-1.5">
                                                    <button
                                                        type="button"
                                                        onClick={() => handleOpenEdit(rating)}
                                                        className="rounded-lg border border-border bg-surface-2 px-2.5 py-1 text-xs font-semibold text-text hover:border-amber-500 hover:text-amber-500 transition-colors"
                                                    >
                                                        Ubah
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleOpenDelete(rating)}
                                                        className="rounded-lg border border-rose-500/30 bg-rose-500/10 px-2.5 py-1 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-500/20 transition-colors"
                                                    >
                                                        Hapus
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={7} className="py-8 text-center text-text-muted">
                                            Belum ada data klasifikasi usia.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Mobile Card List View */}
                    <div className="md:hidden divide-y divide-border">
                        {ageRatings.length > 0 ? (
                            ageRatings.map((rating, index) => (
                                <div key={rating.id} className="p-4 space-y-3">
                                    <div className="flex items-start justify-between gap-2">
                                        <div className="flex items-center gap-2">
                                            <span className="inline-flex items-center rounded-md border border-border bg-surface-2 px-2.5 py-1 text-xs font-black tracking-wide text-amber-500 font-mono">
                                                {rating.code}
                                            </span>
                                            <div>
                                                <h3 className="font-bold text-text text-sm leading-tight">{rating.label}</h3>
                                                <span className="text-xs text-text-muted">
                                                    {rating.min_age === 0 ? 'Semua usia' : `≥ ${rating.min_age} thn`}
                                                </span>
                                            </div>
                                        </div>

                                        <button
                                            type="button"
                                            onClick={() => handleToggle(rating)}
                                            className={`inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[10px] font-bold ${
                                                rating.is_active
                                                    ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30'
                                                    : 'bg-surface-2 text-text-muted border border-border'
                                            }`}
                                        >
                                            <span className={`size-1.5 rounded-full ${rating.is_active ? 'bg-emerald-500' : 'bg-text-muted'}`} />
                                            <span>{rating.is_active ? 'Aktif' : 'Nonaktif'}</span>
                                        </button>
                                    </div>

                                    {rating.description && (
                                        <p className="text-xs text-text-muted">{rating.description}</p>
                                    )}

                                    <div className="flex items-center justify-between pt-2 border-t border-border/60 text-xs">
                                        <div className="flex items-center gap-1">
                                            <button
                                                type="button"
                                                onClick={() => handleReorder(rating.id, 'up')}
                                                disabled={index === 0}
                                                className="p-1 rounded border border-border text-text-muted disabled:opacity-20"
                                            >
                                                <ChevronUp className="size-3.5" />
                                            </button>
                                            <span className="font-mono text-text-muted text-xs px-1">{rating.sort_order}</span>
                                            <button
                                                type="button"
                                                onClick={() => handleReorder(rating.id, 'down')}
                                                disabled={index === ageRatings.length - 1}
                                                className="p-1 rounded border border-border text-text-muted disabled:opacity-20"
                                            >
                                                <ChevronDown className="size-3.5" />
                                            </button>
                                            <span className="text-text-muted ml-2">({rating.films_count || 0} film)</span>
                                        </div>

                                        <div className="flex items-center gap-2">
                                            <button
                                                type="button"
                                                onClick={() => handleOpenEdit(rating)}
                                                className="px-2.5 py-1 rounded-lg border border-border font-semibold text-text hover:border-amber-500"
                                            >
                                                Ubah
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => handleOpenDelete(rating)}
                                                className="px-2.5 py-1 rounded-lg border border-rose-500/30 bg-rose-500/10 font-semibold text-rose-600 dark:text-rose-400"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <div className="py-8 text-center text-text-muted text-xs">
                                Belum ada data klasifikasi usia.
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Modal Tambah */}
            <Modal show={addModalOpen} onClose={() => setAddModalOpen(false)} maxWidth="md">
                <form onSubmit={handleAddSubmit} className="p-6 space-y-4">
                    <div className="flex items-center justify-between pb-3 border-b border-border">
                        <h2 className="text-base font-bold text-text flex items-center gap-2">
                            <Plus className="size-5 text-amber-500" />
                            <span>Tambah Klasifikasi Usia</span>
                        </h2>
                        <button
                            type="button"
                            onClick={() => setAddModalOpen(false)}
                            className="p-1 rounded-lg text-text-muted hover:text-text"
                        >
                            <X className="size-5" />
                        </button>
                    </div>

                    <div className="space-y-4 text-xs">
                        <div>
                            <label className="block font-bold text-text mb-1">
                                Kode Klasifikasi <span className="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                value={addForm.data.code}
                                onChange={(e) => addForm.setData('code', e.target.value.toUpperCase())}
                                placeholder="Contoh: 17+, SU, 13+, 21+"
                                maxLength={8}
                                required
                                className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs font-mono font-bold text-text focus:border-amber-500 focus:outline-none"
                            />
                            {addForm.errors.code && (
                                <p className="text-rose-500 mt-1 font-semibold">{addForm.errors.code}</p>
                            )}
                        </div>

                        <div>
                            <label className="block font-bold text-text mb-1">
                                Label <span className="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                value={addForm.data.label}
                                onChange={(e) => addForm.setData('label', e.target.value)}
                                placeholder="Contoh: Dewasa 17 Tahun Ke Atas"
                                required
                                className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                            />
                            {addForm.errors.label && (
                                <p className="text-rose-500 mt-1 font-semibold">{addForm.errors.label}</p>
                            )}
                        </div>

                        <div>
                            <label className="block font-bold text-text mb-1">
                                Usia Minimal (Tahun) <span className="text-rose-500">*</span>
                            </label>
                            <input
                                type="number"
                                min={0}
                                max={99}
                                value={addForm.data.min_age}
                                onChange={(e) => addForm.setData('min_age', e.target.value)}
                                required
                                className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                            />
                            <p className="text-text-muted text-[11px] mt-1">Masukkan 0 untuk Semua Umur (SU).</p>
                            {addForm.errors.min_age && (
                                <p className="text-rose-500 mt-1 font-semibold">{addForm.errors.min_age}</p>
                            )}
                        </div>

                        <div>
                            <label className="block font-bold text-text mb-1">Deskripsi Tambahan</label>
                            <textarea
                                rows={2}
                                value={addForm.data.description}
                                onChange={(e) => addForm.setData('description', e.target.value)}
                                placeholder="Keterangan singkat pedoman penonton..."
                                className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                            />
                        </div>

                        <label className="flex items-center gap-2 cursor-pointer pt-1">
                            <input
                                type="checkbox"
                                checked={addForm.data.is_active}
                                onChange={(e) => addForm.setData('is_active', e.target.checked)}
                                className="rounded border-border text-amber-500 focus:ring-amber-500"
                            />
                            <span className="font-semibold text-text">Aktifkan klasifikasi ini sekarang</span>
                        </label>
                    </div>

                    <div className="flex items-center justify-end gap-2 pt-4 border-t border-border">
                        <button
                            type="button"
                            onClick={() => setAddModalOpen(false)}
                            className="rounded-xl border border-border px-4 py-2 text-xs font-semibold text-text hover:bg-surface-2"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={addForm.processing}
                            className="rounded-xl bg-amber-500 px-5 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 disabled:opacity-50"
                        >
                            {addForm.processing ? 'Menyimpan...' : 'Simpan Klasifikasi'}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Modal Ubah */}
            <Modal show={editModalOpen} onClose={() => setEditModalOpen(false)} maxWidth="md">
                <form onSubmit={handleEditSubmit} className="p-6 space-y-4">
                    <div className="flex items-center justify-between pb-3 border-b border-border">
                        <h2 className="text-base font-bold text-text flex items-center gap-2">
                            <Edit3 className="size-5 text-amber-500" />
                            <span>Ubah Klasifikasi Usia</span>
                        </h2>
                        <button
                            type="button"
                            onClick={() => setEditModalOpen(false)}
                            className="p-1 rounded-lg text-text-muted hover:text-text"
                        >
                            <X className="size-5" />
                        </button>
                    </div>

                    <div className="space-y-4 text-xs">
                        <div>
                            <label className="block font-bold text-text mb-1">
                                Kode Klasifikasi
                                {(selectedRating?.films_count || 0) > 0 && (
                                    <span className="ml-2 font-normal text-[11px] text-amber-500">
                                        (Terkunci karena digunakan {selectedRating.films_count} film)
                                    </span>
                                )}
                            </label>
                            <input
                                type="text"
                                value={editForm.data.code}
                                onChange={(e) => editForm.setData('code', e.target.value.toUpperCase())}
                                disabled={(selectedRating?.films_count || 0) > 0}
                                maxLength={8}
                                required
                                className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs font-mono font-bold text-text disabled:opacity-60 disabled:cursor-not-allowed focus:border-amber-500 focus:outline-none"
                            />
                            {editForm.errors.code && (
                                <p className="text-rose-500 mt-1 font-semibold">{editForm.errors.code}</p>
                            )}
                        </div>

                        <div>
                            <label className="block font-bold text-text mb-1">
                                Label <span className="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                value={editForm.data.label}
                                onChange={(e) => editForm.setData('label', e.target.value)}
                                required
                                className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                            />
                            {editForm.errors.label && (
                                <p className="text-rose-500 mt-1 font-semibold">{editForm.errors.label}</p>
                            )}
                        </div>

                        <div>
                            <label className="block font-bold text-text mb-1">
                                Usia Minimal (Tahun) <span className="text-rose-500">*</span>
                            </label>
                            <input
                                type="number"
                                min={0}
                                max={99}
                                value={editForm.data.min_age}
                                onChange={(e) => editForm.setData('min_age', e.target.value)}
                                required
                                className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                            />
                            {editForm.errors.min_age && (
                                <p className="text-rose-500 mt-1 font-semibold">{editForm.errors.min_age}</p>
                            )}
                        </div>

                        <div>
                            <label className="block font-bold text-text mb-1">Deskripsi Tambahan</label>
                            <textarea
                                rows={2}
                                value={editForm.data.description}
                                onChange={(e) => editForm.setData('description', e.target.value)}
                                className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                            />
                        </div>

                        <label className="flex items-center gap-2 cursor-pointer pt-1">
                            <input
                                type="checkbox"
                                checked={editForm.data.is_active}
                                onChange={(e) => editForm.setData('is_active', e.target.checked)}
                                className="rounded border-border text-amber-500 focus:ring-amber-500"
                            />
                            <span className="font-semibold text-text">Status Aktif</span>
                        </label>
                    </div>

                    <div className="flex items-center justify-end gap-2 pt-4 border-t border-border">
                        <button
                            type="button"
                            onClick={() => setEditModalOpen(false)}
                            className="rounded-xl border border-border px-4 py-2 text-xs font-semibold text-text hover:bg-surface-2"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={editForm.processing}
                            className="rounded-xl bg-amber-500 px-5 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 disabled:opacity-50"
                        >
                            {editForm.processing ? 'Menyimpan...' : 'Perbarui'}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Modal Hapus */}
            <Modal show={deleteModalOpen} onClose={() => setDeleteModalOpen(false)} maxWidth="md">
                <form onSubmit={handleDeleteSubmit} className="p-6 space-y-4">
                    <div className="flex items-center justify-between pb-3 border-b border-border">
                        <h2 className="text-base font-bold text-rose-500 flex items-center gap-2">
                            <Trash2 className="size-5" />
                            <span>Hapus Klasifikasi Usia</span>
                        </h2>
                        <button
                            type="button"
                            onClick={() => setDeleteModalOpen(false)}
                            className="p-1 rounded-lg text-text-muted hover:text-text"
                        >
                            <X className="size-5" />
                        </button>
                    </div>

                    <div className="space-y-4 text-xs">
                        <p className="text-text">
                            Apakah Anda yakin ingin menghapus klasifikasi{' '}
                            <span className="font-bold text-amber-500 font-mono">
                                {selectedRating?.code} ({selectedRating?.label})
                            </span>
                            ?
                        </p>

                        {(selectedRating?.films_count || 0) > 0 ? (
                            <div className="rounded-xl border border-amber-500/30 bg-amber-500/10 p-3.5 space-y-3">
                                <p className="font-semibold text-amber-700 dark:text-amber-400">
                                    Klasifikasi ini sedang digunakan oleh{' '}
                                    <span className="font-bold">{selectedRating.films_count} film</span>. Pilih klasifikasi
                                    pengganti untuk film-film tersebut:
                                </p>

                                <div>
                                    <label className="block font-bold text-text mb-1">
                                        Klasifikasi Pengganti <span className="text-rose-500">*</span>
                                    </label>
                                    <select
                                        value={deleteForm.data.target_code}
                                        onChange={(e) => deleteForm.setData('target_code', e.target.value)}
                                        required
                                        className="w-full rounded-xl border border-border bg-surface px-3 py-2 text-xs font-bold text-text focus:border-amber-500 focus:outline-none"
                                    >
                                        <option value="">-- Pilih Klasifikasi Pengganti --</option>
                                        {ageRatings
                                            .filter((r) => r.id !== selectedRating?.id)
                                            .map((r) => (
                                                <option key={r.id} value={r.code}>
                                                    {r.code} - {r.label}
                                                </option>
                                            ))}
                                    </select>
                                </div>
                            </div>
                        ) : (
                            <p className="text-text-muted">
                                Klasifikasi ini tidak sedang digunakan oleh film manapun dan aman untuk dihapus.
                            </p>
                        )}
                    </div>

                    <div className="flex items-center justify-end gap-2 pt-4 border-t border-border">
                        <button
                            type="button"
                            onClick={() => setDeleteModalOpen(false)}
                            className="rounded-xl border border-border px-4 py-2 text-xs font-semibold text-text hover:bg-surface-2"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={deleteForm.processing}
                            className="rounded-xl bg-rose-600 px-5 py-2 text-xs font-bold text-white hover:bg-rose-500 disabled:opacity-50"
                        >
                            {deleteForm.processing ? 'Menghapus...' : 'Ya, Hapus'}
                        </button>
                    </div>
                </form>
            </Modal>
        </AdminLayout>
    );
}

