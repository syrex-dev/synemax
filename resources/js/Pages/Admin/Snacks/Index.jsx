import { useState } from 'react';
import { Link, Head, useForm, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import Pagination from '../../../Components/Pagination';
import Modal from '../../../Components/Modal';
import {
    Coffee,
    Plus,
    Edit3,
    Trash2,
    Check,
    X,
    AlertCircle,
    Layers,
    Save,
} from 'lucide-react';

export default function Index({
    categories = [],
    items,
}) {
    const itemList = items?.data || [];
    const [editCategoryModal, setEditCategoryModal] = useState(false);
    const [selectedCategory, setSelectedCategory] = useState(null);

    // New Category Form
    const newCategoryForm = useForm({
        name: '',
        sort_order: 0,
    });

    // Edit Category Form
    const editCategoryForm = useForm({
        name: '',
        sort_order: 0,
        is_active: true,
    });

    const handleCreateCategory = (e) => {
        e.preventDefault();
        newCategoryForm.post('/admin/jajanan/kategori', {
            preserveScroll: true,
            onSuccess: () => newCategoryForm.reset(),
        });
    };

    const handleOpenEditCategory = (cat) => {
        setSelectedCategory(cat);
        editCategoryForm.setData({
            name: cat.name,
            sort_order: cat.sort_order,
            is_active: Boolean(cat.is_active),
        });
        setEditCategoryModal(true);
    };

    const handleUpdateCategory = (e) => {
        e.preventDefault();
        if (!selectedCategory) return;
        editCategoryForm.patch(`/admin/jajanan/kategori/${selectedCategory.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setEditCategoryModal(false);
                setSelectedCategory(null);
            },
        });
    };

    const handleDeleteCategory = (cat) => {
        if (confirm(`Yakin hapus kategori "${cat.name}"? Menu dalam kategori ini mungkin terdampak.`)) {
            router.delete(`/admin/jajanan/kategori/${cat.id}`, {
                preserveScroll: true,
            });
        }
    };

    const handleToggleAvailability = (item) => {
        router.patch(`/admin/jajanan/menu/${item.id}/tersedia`, {}, {
            preserveScroll: true,
        });
    };

    const handleDeleteItem = (item) => {
        if (confirm(`Hapus menu "${item.name}"? Riwayat pesanan tetap tersimpan.`)) {
            router.delete(`/admin/jajanan/menu/${item.id}`, {
                preserveScroll: true,
            });
        }
    };

    return (
        <AdminLayout title="Menu Jajanan">
            <Head title="Menu Jajanan | Admin SynemaX" />

            <div className="space-y-8">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="text-xs font-bold uppercase tracking-wider text-amber-500 mb-1">
                            Warung & F&B Bioskop
                        </div>
                        <h1 className="text-2xl font-black tracking-tight text-text flex items-center gap-2.5">
                            <Coffee className="size-6 text-amber-500" />
                            <span>Menu Jajanan</span>
                        </h1>
                        <p className="text-xs text-text-muted mt-1">
                            Atur kategori, harga jual, stok, dan ketersediaan menu warung mini studio.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href="/admin/jajanan/siapkan"
                            className="inline-flex min-h-[42px] items-center gap-1.5 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-bold text-text hover:border-amber-500 transition-colors shadow-xs"
                        >
                            <span>Daftar Siapkan</span>
                        </Link>
                        <Link
                            href="/admin/jajanan/menu/buat"
                            className="inline-flex min-h-[42px] items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition-colors"
                        >
                            <Plus className="size-4" />
                            <span>Tambah Menu</span>
                        </Link>
                    </div>
                </div>

                {/* Section: Kategori */}
                <section className="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-xs space-y-5">
                    <div className="flex items-center justify-between">
                        <div>
                            <h2 className="text-base font-bold text-text flex items-center gap-2">
                                <Layers className="size-4 text-amber-500" />
                                <span>Kategori Jajanan</span>
                            </h2>
                            <p className="text-xs text-text-muted mt-0.5">
                                Kategori mengelompokkan menu di halaman pemesanan.
                            </p>
                        </div>
                        <span className="text-xs font-bold text-text-muted">
                            {categories.length} kategori
                        </span>
                    </div>

                    {/* Form Tambah Kategori */}
                    <form
                        onSubmit={handleCreateCategory}
                        className="grid gap-3 rounded-xl border border-border bg-surface-2/50 p-4 sm:grid-cols-[1fr_140px_auto] sm:items-end text-xs"
                    >
                        <div>
                            <label className="block font-bold text-text mb-1">Nama Kategori</label>
                            <input
                                type="text"
                                value={newCategoryForm.data.name}
                                onChange={(e) => newCategoryForm.setData('name', e.target.value)}
                                placeholder="Contoh: Minuman Dingin, Popcorn, Snack Gurih"
                                required
                                maxLength={100}
                                className="w-full rounded-xl border border-border bg-surface px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                            />
                        </div>

                        <div>
                            <label className="block font-bold text-text mb-1">Urutan Tampil</label>
                            <input
                                type="number"
                                min={0}
                                max={65535}
                                value={newCategoryForm.data.sort_order}
                                onChange={(e) => newCategoryForm.setData('sort_order', e.target.value)}
                                className="w-full rounded-xl border border-border bg-surface px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                            />
                        </div>

                        <button
                            type="submit"
                            disabled={newCategoryForm.processing}
                            className="min-h-[38px] rounded-xl bg-amber-500 px-4 text-xs font-bold text-slate-950 hover:bg-amber-400 disabled:opacity-50"
                        >
                            + Kategori
                        </button>
                    </form>

                    {/* Daftar Kategori */}
                    <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        {categories.map((cat) => (
                            <div
                                key={cat.id}
                                className="flex items-center justify-between p-3 rounded-xl border border-border bg-surface-2 text-xs"
                            >
                                <div>
                                    <h4 className="font-bold text-text">{cat.name}</h4>
                                    <div className="flex items-center gap-2 text-[11px] text-text-muted mt-0.5">
                                        <span>Urutan: {cat.sort_order}</span>
                                        <span>•</span>
                                        <span className={cat.is_active ? 'text-emerald-600 font-semibold' : 'text-text-muted'}>
                                            {cat.is_active ? 'Aktif' : 'Nonaktif'}
                                        </span>
                                    </div>
                                </div>

                                <div className="flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        onClick={() => handleOpenEditCategory(cat)}
                                        className="px-2 py-1 rounded-lg border border-border text-text hover:border-amber-500 transition font-semibold text-[11px]"
                                    >
                                        Ubah
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => handleDeleteCategory(cat)}
                                        className="p-1 rounded-lg text-rose-500 hover:bg-rose-500/10 transition"
                                        title="Hapus Kategori"
                                    >
                                        <Trash2 className="size-3.5" />
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>

                {/* Section: Daftar Menu */}
                <section className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h2 className="text-base font-bold text-text">Daftar Menu Jajanan</h2>
                        <span className="text-xs text-text-muted">
                            Total {items?.total || itemList.length} menu
                        </span>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {itemList.map((item) => {
                            const photoUrl = item.photo_path ? `/storage/${item.photo_path}` : null;
                            const isLowStock = item.stock_mode === 'angka' && item.stock_qty <= item.stock_alert_threshold;

                            return (
                                <article
                                    key={item.id}
                                    className="rounded-2xl border border-border bg-surface p-4 flex flex-col justify-between shadow-xs"
                                >
                                    <div>
                                        <div className="flex gap-3">
                                            {photoUrl ? (
                                                <img
                                                    src={photoUrl}
                                                    alt={item.name}
                                                    className="size-16 shrink-0 rounded-xl object-cover border border-border"
                                                />
                                            ) : (
                                                <div className="size-16 shrink-0 rounded-xl bg-surface-2 border border-border flex items-center justify-center text-[10px] text-text-muted text-center p-1 font-semibold">
                                                    Tanpa foto
                                                </div>
                                            )}
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-start justify-between gap-1">
                                                    <h3 className="font-bold text-text text-sm line-clamp-1 leading-snug">
                                                        {item.name}
                                                    </h3>
                                                </div>
                                                <p className="text-[11px] text-text-muted mt-0.5">
                                                    {item.category?.name} · {item.prep_minutes} mnt
                                                </p>
                                                <p className="text-xs font-bold text-amber-500 mt-1">
                                                    Rp {Number(item.price).toLocaleString('id-ID')}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="mt-3 text-xs">
                                            <p className={`text-[11px] font-semibold ${isLowStock ? 'text-rose-500' : 'text-text-muted'}`}>
                                                {item.stock_mode === 'angka' ? (
                                                    <span>Stok: {item.stock_qty}{isLowStock ? ' (Menipis)' : ''}</span>
                                                ) : (
                                                    <span>Status: {item.is_available ? 'Tersedia' : 'Habis'} (Toggle)</span>
                                                )}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="mt-4 pt-3 border-t border-border flex items-center justify-between gap-2 text-xs">
                                        <div className="flex items-center gap-1.5">
                                            <button
                                                type="button"
                                                onClick={() => handleToggleAvailability(item)}
                                                className={`px-2.5 py-1 rounded-lg border text-[11px] font-bold transition ${
                                                    item.is_available
                                                        ? 'border-emerald-500/30 text-emerald-600 bg-emerald-500/10'
                                                        : 'border-border text-text-muted bg-surface-2'
                                                }`}
                                            >
                                                {item.is_available ? 'Tersedia' : 'Habis'}
                                            </button>
                                        </div>

                                        <div className="flex items-center gap-1.5">
                                            <Link
                                                href={`/admin/jajanan/menu/${item.id}/ubah`}
                                                className="px-2.5 py-1 rounded-lg border border-border text-text font-semibold hover:border-amber-500 text-[11px]"
                                            >
                                                Ubah
                                            </Link>
                                            <button
                                                type="button"
                                                onClick={() => handleDeleteItem(item)}
                                                className="p-1.5 rounded-lg border border-rose-500/30 text-rose-500 hover:bg-rose-500/10"
                                                title="Hapus Menu"
                                            >
                                                <Trash2 className="size-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            );
                        })}
                    </div>

                    {/* Pagination */}
                    {items?.links && (
                        <div className="pt-4">
                            <Pagination links={items.links} />
                        </div>
                    )}
                </section>
            </div>

            {/* Modal Ubah Kategori */}
            <Modal show={editCategoryModal} onClose={() => setEditCategoryModal(false)} maxWidth="sm">
                <form onSubmit={handleUpdateCategory} className="p-6 space-y-4">
                    <div className="flex items-center justify-between pb-3 border-b border-border">
                        <h2 className="text-base font-bold text-text">Ubah Kategori Jajanan</h2>
                        <button
                            type="button"
                            onClick={() => setEditCategoryModal(false)}
                            className="p-1 rounded-lg text-text-muted hover:text-text"
                        >
                            <X className="size-5" />
                        </button>
                    </div>

                    <div className="space-y-4 text-xs">
                        <div>
                            <label className="block font-bold text-text mb-1">Nama Kategori</label>
                            <input
                                type="text"
                                value={editCategoryForm.data.name}
                                onChange={(e) => editCategoryForm.setData('name', e.target.value)}
                                required
                                maxLength={100}
                                className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                            />
                        </div>

                        <div>
                            <label className="block font-bold text-text mb-1">Urutan Tampil</label>
                            <input
                                type="number"
                                min={0}
                                max={65535}
                                value={editCategoryForm.data.sort_order}
                                onChange={(e) => editCategoryForm.setData('sort_order', e.target.value)}
                                required
                                className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                            />
                        </div>

                        <label className="flex items-center gap-2 cursor-pointer pt-1">
                            <input
                                type="checkbox"
                                checked={editCategoryForm.data.is_active}
                                onChange={(e) => editCategoryForm.setData('is_active', e.target.checked)}
                                className="rounded border-border text-amber-500 focus:ring-amber-500"
                            />
                            <span className="font-semibold text-text">Kategori Aktif</span>
                        </label>
                    </div>

                    <div className="flex items-center justify-end gap-2 pt-3 border-t border-border">
                        <button
                            type="button"
                            onClick={() => setEditCategoryModal(false)}
                            className="rounded-xl border border-border px-4 py-2 text-xs font-semibold text-text hover:bg-surface-2"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            disabled={editCategoryForm.processing}
                            className="rounded-xl bg-amber-500 px-5 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400"
                        >
                            Simpan Kategori
                        </button>
                    </div>
                </form>
            </Modal>
        </AdminLayout>
    );
}

