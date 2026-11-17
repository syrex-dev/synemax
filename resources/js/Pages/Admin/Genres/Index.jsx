import { useState } from 'react';
import { Link, Head, useForm, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import Modal from '../../../Components/Modal';
import {
    Tag,
    Plus,
    ArrowLeft,
    ChevronUp,
    ChevronDown,
    Edit3,
    Trash2,
    GitMerge,
    Check,
    X,
    AlertCircle,
    Film,
} from 'lucide-react';

export default function Index({
    genres = [],
    parentCandidates = [],
    activeCount = 0,
}) {
    const [addModalOpen, setAddModalOpen] = useState(false);
    const [editModalOpen, setEditModalOpen] = useState(false);
    const [mergeModalOpen, setMergeModalOpen] = useState(false);
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [selectedGenre, setSelectedGenre] = useState(null);

    // Add Form
    const addForm = useForm({
        name: '',
        parent_id: '',
        is_active: true,
    });

    // Edit Form
    const editForm = useForm({
        name: '',
        parent_id: '',
        is_active: true,
    });

    // Merge Form
    const mergeForm = useForm({
        target_genre_id: '',
    });

    // Delete Form
    const deleteForm = useForm({
        action: 'delete',
        target_genre_id: '',
    });

    const handleOpenEdit = (genre) => {
        setSelectedGenre(genre);
        editForm.setData({
            name: genre.name,
            parent_id: genre.parent_id ? String(genre.parent_id) : '',
            is_active: Boolean(genre.is_active),
        });
        setEditModalOpen(true);
    };

    const handleOpenMerge = (genre) => {
        setSelectedGenre(genre);
        mergeForm.setData({ target_genre_id: '' });
        setMergeModalOpen(true);
    };

    const handleOpenDelete = (genre) => {
        setSelectedGenre(genre);
        deleteForm.setData({
            action: (genre.films_count || 0) > 0 ? 'merge' : 'delete',
            target_genre_id: '',
        });
        setDeleteModalOpen(true);
    };

    const handleAddSubmit = (e) => {
        e.preventDefault();
        addForm.post('/admin/genres', {
            preserveScroll: true,
            onSuccess: () => {
                setAddModalOpen(false);
                addForm.reset();
            },
        });
    };

    const handleEditSubmit = (e) => {
        e.preventDefault();
        if (!selectedGenre) return;
        editForm.put(`/admin/genres/${selectedGenre.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setEditModalOpen(false);
                setSelectedGenre(null);
            },
        });
    };

    const handleMergeSubmit = (e) => {
        e.preventDefault();
        if (!selectedGenre) return;
        mergeForm.post(`/admin/genres/${selectedGenre.id}/merge`, {
            preserveScroll: true,
            onSuccess: () => {
                setMergeModalOpen(false);
                setSelectedGenre(null);
            },
        });
    };

    const handleDeleteSubmit = (e) => {
        e.preventDefault();
        if (!selectedGenre) return;
        deleteForm.delete(`/admin/genres/${selectedGenre.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteModalOpen(false);
                setSelectedGenre(null);
            },
        });
    };

    const handleReorder = (genreId, direction) => {
        router.post(`/admin/genres/${genreId}/reorder`, { direction }, {
            preserveScroll: true,
        });
    };

    const handleToggleStatus = (genreId) => {
        router.patch(`/admin/genres/${genreId}/status`, {}, {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout title="Kelola Genre Film">
            <Head>
                <title>Kelola Genre Film - Admin SynemaX</title>
            </Head>

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-border pb-5">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-text-muted mb-1">
                            <Link href="/admin/films" className="hover:text-amber-500 transition">
                                Katalog Film
                            </Link>
                            <span>/</span>
                            <span className="text-text">Genre</span>
                        </div>
                        <h1 className="text-2xl font-black text-text tracking-tight">Kelola Genre Film</h1>
                        <p className="text-xs text-text-muted mt-1">
                            Satu sumber kebenaran untuk seluruh katalog, filter beranda, dan form film. Total {genres.length} genre ({activeCount} aktif).
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href="/admin/films"
                            className="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface px-4 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition shadow-xs"
                        >
                            <ArrowLeft className="size-3.5" />
                            <span>Kembali ke Film</span>
                        </Link>
                        <button
                            type="button"
                            onClick={() => setAddModalOpen(true)}
                            className="btn-gold-cinema inline-flex min-h-[40px] items-center gap-1.5 rounded-xl px-4 text-xs font-bold shadow-md"
                        >
                            <Plus className="size-4" />
                            <span>Tambah Genre Baru</span>
                        </button>
                    </div>
                </div>

                {/* Genres Table */}
                <div className="overflow-hidden rounded-2xl border border-border bg-surface shadow-xs">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="border-b border-border bg-surface-2 text-text-muted uppercase text-[10px] font-bold tracking-wider">
                                <tr>
                                    <th className="py-3 px-4 w-16 text-center">Urutan</th>
                                    <th className="py-3 px-4">Nama Genre</th>
                                    <th className="py-3 px-4">Kategori Induk</th>
                                    <th className="py-3 px-4 text-center">Jumlah Film</th>
                                    <th className="py-3 px-4 text-center">Status</th>
                                    <th className="py-3 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {genres.length === 0 ? (
                                    <tr>
                                        <td colSpan="6" className="p-8 text-center text-text-muted">
                                            Belum ada data genre.
                                        </td>
                                    </tr>
                                ) : (
                                    genres.map((genre, idx) => (
                                        <tr key={genre.id} className="transition hover:bg-surface-2/60">
                                            <td className="py-3 px-4 text-center">
                                                <div className="flex items-center justify-center gap-1">
                                                    <button
                                                        type="button"
                                                        onClick={() => handleReorder(genre.id, 'up')}
                                                        disabled={idx === 0}
                                                        className="p-1 rounded hover:bg-surface-2 text-text-muted hover:text-text disabled:opacity-20"
                                                        title="Geser ke atas"
                                                    >
                                                        <ChevronUp className="size-3.5" />
                                                    </button>
                                                    <span className="font-mono text-text font-bold text-[11px] w-5 text-center">
                                                        {genre.sort_order}
                                                    </span>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleReorder(genre.id, 'down')}
                                                        disabled={idx === genres.length - 1}
                                                        className="p-1 rounded hover:bg-surface-2 text-text-muted hover:text-text disabled:opacity-20"
                                                        title="Geser ke bawah"
                                                    >
                                                        <ChevronDown className="size-3.5" />
                                                    </button>
                                                </div>
                                            </td>
                                            <td className="py-3 px-4 font-bold text-text">
                                                <div className="flex items-center gap-2">
                                                    <span>{genre.name}</span>
                                                    {genre.is_parent && (
                                                        <span className="rounded bg-amber-500/15 border border-amber-400/30 px-1.5 py-0.2 text-[9px] font-black uppercase text-amber-600 dark:text-amber-400">
                                                            Induk ({genre.children?.length || 0})
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="py-3 px-4 text-text-muted">
                                                {genre.parent?.name ? (
                                                    <span className="rounded-md border border-border bg-surface-2 px-2 py-0.5 text-[10px] font-medium text-text">
                                                        {genre.parent.name}
                                                    </span>
                                                ) : (
                                                    <span className="text-[11px] opacity-40">-</span>
                                                )}
                                            </td>
                                            <td className="py-3 px-4 text-center font-mono font-bold text-text">
                                                {genre.films_count || 0}
                                            </td>
                                            <td className="py-3 px-4 text-center">
                                                <button
                                                    type="button"
                                                    onClick={() => handleToggleStatus(genre.id)}
                                                    className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase transition ${
                                                        genre.is_active
                                                            ? 'bg-emerald-500/15 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400'
                                                            : 'bg-slate-500/15 border border-slate-500/30 text-text-muted'
                                                    }`}
                                                >
                                                    <span className={`size-1.5 rounded-full ${genre.is_active ? 'bg-emerald-500' : 'bg-slate-400'}`} />
                                                    <span>{genre.is_active ? 'Aktif' : 'Nonaktif'}</span>
                                                </button>
                                            </td>
                                            <td className="py-3 px-4 text-right">
                                                <div className="flex items-center justify-end gap-1.5">
                                                    <button
                                                        type="button"
                                                        onClick={() => handleOpenMerge(genre)}
                                                        className="p-1.5 rounded-lg border border-border bg-surface text-text hover:text-amber-500 transition"
                                                        title="Gabungkan genre ini ke genre lain"
                                                    >
                                                        <GitMerge className="size-3.5" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleOpenEdit(genre)}
                                                        className="p-1.5 rounded-lg border border-border bg-surface text-text hover:text-amber-500 transition"
                                                        title="Ubah genre"
                                                    >
                                                        <Edit3 className="size-3.5" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleOpenDelete(genre)}
                                                        className="p-1.5 rounded-lg border border-danger/30 bg-danger/10 text-danger hover:bg-danger/20 transition"
                                                        title="Hapus genre"
                                                    >
                                                        <Trash2 className="size-3.5" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Modal Tambah Genre */}
            <Modal show={addModalOpen} onClose={() => setAddModalOpen(false)} maxWidth="sm">
                <form onSubmit={handleAddSubmit} className="p-6 space-y-4">
                    <h3 className="text-base font-black text-text">Tambah Genre Baru</h3>

                    <div>
                        <label className="block text-xs font-bold text-text-muted mb-1">
                            Nama Genre <span className="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            required
                            value={addForm.data.name}
                            onChange={(e) => addForm.setData('name', e.target.value)}
                            placeholder="Contoh: Psikologis Thriller"
                            className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                        />
                        {addForm.errors.name && <p className="text-danger-text text-[11px] mt-1">{addForm.errors.name}</p>}
                    </div>

                    <div>
                        <label className="block text-xs font-bold text-text-muted mb-1">
                            Kategori Induk (Opsional)
                        </label>
                        <select
                            value={addForm.data.parent_id}
                            onChange={(e) => addForm.setData('parent_id', e.target.value)}
                            className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                        >
                            <option value="">Tidak ada (Jadikan Induk Utama)</option>
                            {parentCandidates.map((p) => (
                                <option key={p.id} value={p.id}>{p.name}</option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="flex items-center gap-2 cursor-pointer text-xs">
                            <input
                                type="checkbox"
                                checked={addForm.data.is_active}
                                onChange={(e) => addForm.setData('is_active', e.target.checked)}
                                className="rounded text-amber-500 focus:ring-amber-500"
                            />
                            <span className="font-bold text-text">Aktifkan langsung</span>
                        </label>
                    </div>

                    <div className="flex justify-end gap-2 pt-2 border-t border-border">
                        <button
                            type="button"
                            onClick={() => setAddModalOpen(false)}
                            className="inline-flex min-h-[38px] items-center rounded-xl border border-border bg-surface-2 px-3.5 text-xs font-bold text-text"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={addForm.processing}
                            className="btn-gold-cinema inline-flex min-h-[38px] items-center rounded-xl px-4 text-xs font-bold shadow-md"
                        >
                            Simpan Genre
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Modal Ubah Genre */}
            <Modal show={editModalOpen} onClose={() => setEditModalOpen(false)} maxWidth="sm">
                <form onSubmit={handleEditSubmit} className="p-6 space-y-4">
                    <h3 className="text-base font-black text-text">Ubah Genre</h3>

                    <div>
                        <label className="block text-xs font-bold text-text-muted mb-1">
                            Nama Genre <span className="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            required
                            value={editForm.data.name}
                            onChange={(e) => editForm.setData('name', e.target.value)}
                            className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                        />
                        {editForm.errors.name && <p className="text-danger-text text-[11px] mt-1">{editForm.errors.name}</p>}
                    </div>

                    <div>
                        <label className="block text-xs font-bold text-text-muted mb-1">
                            Kategori Induk
                        </label>
                        <select
                            value={editForm.data.parent_id}
                            onChange={(e) => editForm.setData('parent_id', e.target.value)}
                            className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                        >
                            <option value="">Tidak ada (Jadikan Induk Utama)</option>
                            {parentCandidates
                                .filter((p) => p.id !== selectedGenre?.id)
                                .map((p) => (
                                    <option key={p.id} value={p.id}>{p.name}</option>
                                ))}
                        </select>
                    </div>

                    <div>
                        <label className="flex items-center gap-2 cursor-pointer text-xs">
                            <input
                                type="checkbox"
                                checked={editForm.data.is_active}
                                onChange={(e) => editForm.setData('is_active', e.target.checked)}
                                className="rounded text-amber-500 focus:ring-amber-500"
                            />
                            <span className="font-bold text-text">Genre Aktif</span>
                        </label>
                    </div>

                    <div className="flex justify-end gap-2 pt-2 border-t border-border">
                        <button
                            type="button"
                            onClick={() => setEditModalOpen(false)}
                            className="inline-flex min-h-[38px] items-center rounded-xl border border-border bg-surface-2 px-3.5 text-xs font-bold text-text"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={editForm.processing}
                            className="btn-gold-cinema inline-flex min-h-[38px] items-center rounded-xl px-4 text-xs font-bold shadow-md"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Modal Gabungkan Genre */}
            {selectedGenre && (
                <Modal show={mergeModalOpen} onClose={() => setMergeModalOpen(false)} maxWidth="sm">
                    <form onSubmit={handleMergeSubmit} className="p-6 space-y-4">
                        <h3 className="text-base font-black text-text">Gabungkan Genre: {selectedGenre.name}</h3>
                        <p className="text-xs text-text-muted leading-relaxed">
                            Seluruh film yang menggunakan genre <strong>{selectedGenre.name}</strong> akan dialihkan ke genre tujuan di bawah ini, kemudian genre ini akan dihapus.
                        </p>

                        <div>
                            <label className="block text-xs font-bold text-text-muted mb-1">
                                Pilih Genre Tujuan
                            </label>
                            <select
                                required
                                value={mergeForm.data.target_genre_id}
                                onChange={(e) => mergeForm.setData('target_genre_id', e.target.value)}
                                className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                            >
                                <option value="">-- Pilih Genre Tujuan --</option>
                                {genres
                                    .filter((g) => g.id !== selectedGenre.id)
                                    .map((g) => (
                                        <option key={g.id} value={g.id}>{g.name}</option>
                                    ))}
                            </select>
                        </div>

                        <div className="flex justify-end gap-2 pt-2 border-t border-border">
                            <button
                                type="button"
                                onClick={() => setMergeModalOpen(false)}
                                className="inline-flex min-h-[38px] items-center rounded-xl border border-border bg-surface-2 px-3.5 text-xs font-bold text-text"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                disabled={!mergeForm.data.target_genre_id || mergeForm.processing}
                                className="btn-gold-cinema inline-flex min-h-[38px] items-center rounded-xl px-4 text-xs font-bold shadow-md disabled:opacity-40"
                            >
                                Gabungkan Genre
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {/* Modal Hapus Genre */}
            {selectedGenre && (
                <Modal show={deleteModalOpen} onClose={() => setDeleteModalOpen(false)} maxWidth="sm">
                    <form onSubmit={handleDeleteSubmit} className="p-6 space-y-4">
                        <h3 className="text-base font-black text-text">Hapus Genre: {selectedGenre.name}?</h3>

                        {(selectedGenre.films_count || 0) > 0 ? (
                            <div className="space-y-3">
                                <p className="text-xs text-text-muted leading-relaxed">
                                    Genre ini saat ini dipakai oleh <strong className="text-text">{selectedGenre.films_count} film</strong>. Pilih genre tujuan untuk mengalihkan film-film tersebut:
                                </p>
                                <select
                                    required
                                    value={deleteForm.data.target_genre_id}
                                    onChange={(e) => deleteForm.setData('target_genre_id', e.target.value)}
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                >
                                    <option value="">-- Pilih Genre Pengganti --</option>
                                    {genres
                                        .filter((g) => g.id !== selectedGenre.id)
                                        .map((g) => (
                                            <option key={g.id} value={g.id}>{g.name}</option>
                                        ))}
                                </select>
                            </div>
                        ) : (
                            <p className="text-xs text-text-muted leading-relaxed">
                                Apakah Anda yakin ingin menghapus genre ini? Aksi ini tidak dapat dibatalkan.
                            </p>
                        )}

                        <div className="flex justify-end gap-2 pt-2 border-t border-border">
                            <button
                                type="button"
                                onClick={() => setDeleteModalOpen(false)}
                                className="inline-flex min-h-[38px] items-center rounded-xl border border-border bg-surface-2 px-3.5 text-xs font-bold text-text"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                disabled={deleteForm.processing}
                                className="inline-flex min-h-[38px] items-center rounded-xl bg-danger px-4 text-xs font-bold text-white hover:opacity-90 transition disabled:opacity-50"
                            >
                                Ya, Hapus Genre
                            </button>
                        </div>
                    </form>
                </Modal>
            )}
        </AdminLayout>
    );
}

