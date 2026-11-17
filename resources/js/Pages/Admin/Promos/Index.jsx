import { useState } from 'react';
import { Link, Head, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import Pagination from '../../../Components/Pagination';
import Modal from '../../../Components/Modal';
import {
    Tag,
    Plus,
    Calendar,
    ChevronUp,
    ChevronDown,
    Edit3,
    Trash2,
    Eye,
    Copy,
    AlertCircle,
    CheckCircle2,
    X,
} from 'lucide-react';

export default function Index({
    promos,
    currentStatus = '',
    showTrash = false,
    trashedCount = 0,
}) {
    const promoList = promos?.data || [];
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [selectedPromo, setSelectedPromo] = useState(null);

    const statusTabs = [
        { key: '', label: 'Semua Promo' },
        { key: 'aktif', label: 'Aktif Berjalan' },
        { key: 'berakhir', label: 'Sudah Berakhir' },
        { key: 'draft', label: 'Draft / Nonaktif' },
    ];

    const handleToggleStatus = (promo) => {
        router.patch(`/admin/promos/${promo.id}/status`, {}, {
            preserveScroll: true,
        });
    };

    const handleDuplicate = (promo) => {
        router.post(`/admin/promos/${promo.id}/duplikat`, {}, {
            preserveScroll: true,
        });
    };

    const handleDelete = (promo) => {
        setSelectedPromo(promo);
        setDeleteModalOpen(true);
    };

    const confirmDelete = () => {
        if (!selectedPromo) return;
        router.delete(`/admin/promos/${selectedPromo.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteModalOpen(false);
                setSelectedPromo(null);
            },
        });
    };

    const handleRestore = (promo) => {
        router.post(`/admin/promos/${promo.id}/restore`, {}, {
            preserveScroll: true,
        });
    };

    const handleForceDelete = (promo) => {
        if (confirm(`Hapus permanen promo "${promo.title}"? Tindakan ini tidak dapat dibatalkan.`)) {
            router.delete(`/admin/promos/${promo.id}/force`, {
                preserveScroll: true,
            });
        }
    };

    return (
        <AdminLayout title="Kelola Promo">
            <Head title="Kelola Promo | Admin SynemaX" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="text-xs font-bold uppercase tracking-wider text-amber-500 mb-1">
                            Penawaran Khusus
                        </div>
                        <h1 className="text-2xl font-black tracking-tight text-text flex items-center gap-2.5">
                            <Tag className="size-6 text-amber-500" />
                            <span>Promo & Penawaran</span>
                        </h1>
                        <p className="text-xs text-text-muted mt-1 max-w-xl">
                            Promo dikelola lewat upload banner gambar 1200 × 600 px. Setiap promo memiliki halaman detail sendiri untuk syarat & ketentuan.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href="/admin/promos/buat"
                            className="inline-flex min-h-[42px] items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition-colors"
                        >
                            <Plus className="size-4" />
                            <span>Tambah Promo Baru</span>
                        </Link>
                    </div>
                </div>

                {/* Tabs & Trash Filter */}
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex flex-wrap gap-2">
                        {statusTabs.map((tab) => {
                            const isActive = !showTrash && currentStatus === tab.key;
                            return (
                                <Link
                                    key={tab.key}
                                    href={tab.key ? `/admin/promos?status=${tab.key}` : '/admin/promos'}
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

                    {trashedCount > 0 && (
                        <Link
                            href={showTrash ? '/admin/promos' : '/admin/promos?trash=1'}
                            className={`inline-flex min-h-[38px] items-center gap-1.5 rounded-xl border px-3 text-xs font-bold transition-colors ${
                                showTrash
                                    ? 'border-rose-500 bg-rose-500/10 text-rose-600 dark:text-rose-400'
                                    : 'border-border bg-surface text-text-muted hover:text-rose-500'
                            }`}
                        >
                            <Trash2 className="size-3.5" />
                            <span>Sampah ({trashedCount})</span>
                        </Link>
                    )}
                </div>

                {/* Main Content List / Table */}
                <div className="rounded-2xl border border-border bg-surface overflow-hidden shadow-xs">
                    {promoList.length > 0 ? (
                        <>
                            {/* Desktop Table View */}
                            <div className="hidden lg:block overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead>
                                        <tr className="border-b border-border bg-surface-2 text-text-muted uppercase tracking-wider text-[11px] font-bold">
                                            <th className="py-3 px-4 w-20 text-center">Urutan</th>
                                            <th className="py-3 px-4">Banner & Judul Promo</th>
                                            <th className="py-3 px-4 w-44">Periode Berlaku</th>
                                            <th className="py-3 px-4 w-28 text-center">Status</th>
                                            <th className="py-3 px-4 w-44 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border">
                                        {promoList.map((promo, index) => {
                                            const isExpired = promo.end_date && new Date(promo.end_date) < new Date();
                                            const imgUrl = promo.image_path ? `/storage/${promo.image_path}` : null;

                                            return (
                                                <tr key={promo.id} className="hover:bg-surface-2/60 transition-colors">
                                                    {/* Sort Order */}
                                                    <td className="py-3 px-4 text-center font-bold text-text-muted font-mono">
                                                        {promo.sort_order || index + 1}
                                                    </td>

                                                    {/* Banner & Title */}
                                                    <td className="py-3 px-4">
                                                        <div className="flex items-center gap-3">
                                                            {imgUrl ? (
                                                                <img
                                                                    src={imgUrl}
                                                                    alt={promo.title}
                                                                    className="h-12 w-24 shrink-0 rounded-lg object-cover border border-border shadow-xs"
                                                                />
                                                            ) : (
                                                                <div className="flex h-12 w-24 shrink-0 items-center justify-center rounded-lg border border-border bg-surface-2 text-[10px] font-semibold text-text-muted uppercase text-center p-1 leading-tight">
                                                                    Tanpa poster
                                                                </div>
                                                            )}
                                                            <div className="min-w-0 flex-1">
                                                                <Link
                                                                    href={`/admin/promos/${promo.id}/ubah`}
                                                                    className="font-bold text-text hover:text-amber-500 line-clamp-1 leading-snug"
                                                                    title={promo.title}
                                                                >
                                                                    {promo.title}
                                                                </Link>
                                                                <p className="mt-0.5 truncate font-mono text-[11px] text-text-muted">
                                                                    /promo/{promo.slug}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    {/* Period */}
                                                    <td className="py-3 px-4">
                                                        <div className="font-semibold text-text">
                                                            {promo.start_date || promo.end_date ? (
                                                                <span>
                                                                    {promo.start_date ? promo.start_date : '...'} s/d{' '}
                                                                    {promo.end_date ? promo.end_date : '...'}
                                                                </span>
                                                            ) : (
                                                                <span className="text-text-muted">Selamanya</span>
                                                            )}
                                                        </div>
                                                        {isExpired && (
                                                            <span className="text-rose-500 font-bold text-[10px]">
                                                                Telah berakhir
                                                            </span>
                                                        )}
                                                    </td>

                                                    {/* Status */}
                                                    <td className="py-3 px-4 text-center">
                                                        {!showTrash ? (
                                                            <button
                                                                type="button"
                                                                onClick={() => handleToggleStatus(promo)}
                                                                className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold transition-colors ${
                                                                    promo.is_active
                                                                        ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/20'
                                                                        : 'bg-surface-2 text-text-muted border border-border hover:text-text'
                                                                }`}
                                                                title="Klik untuk ubah status aktif"
                                                            >
                                                                <span
                                                                    className={`size-1.5 rounded-full ${
                                                                        promo.is_active ? 'bg-emerald-500' : 'bg-text-muted'
                                                                    }`}
                                                                />
                                                                <span>{promo.is_active ? 'Aktif' : 'Nonaktif'}</span>
                                                            </button>
                                                        ) : (
                                                            <span className="text-rose-500 font-bold text-[11px]">Di Sampah</span>
                                                        )}
                                                    </td>

                                                    {/* Actions */}
                                                    <td className="py-3 px-4 text-right">
                                                        {!showTrash ? (
                                                            <div className="flex items-center justify-end gap-1.5">
                                                                <a
                                                                    href={`/promo/${promo.slug}`}
                                                                    target="_blank"
                                                                    rel="noreferrer"
                                                                    className="p-1.5 rounded-lg border border-border text-text-muted hover:text-text hover:border-amber-500 transition"
                                                                    title="Lihat Halaman Publik"
                                                                >
                                                                    <Eye className="size-3.5" />
                                                                </a>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleDuplicate(promo)}
                                                                    className="p-1.5 rounded-lg border border-border text-text-muted hover:text-text hover:border-amber-500 transition"
                                                                    title="Duplikat Promo"
                                                                >
                                                                    <Copy className="size-3.5" />
                                                                </button>
                                                                <Link
                                                                    href={`/admin/promos/${promo.id}/ubah`}
                                                                    className="px-2.5 py-1 rounded-lg border border-border font-semibold text-text hover:border-amber-500 hover:text-amber-500 transition"
                                                                >
                                                                    Ubah
                                                                </Link>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleDelete(promo)}
                                                                    className="p-1.5 rounded-lg border border-rose-500/30 bg-rose-500/10 text-rose-600 dark:text-rose-400 hover:bg-rose-500/20 transition"
                                                                    title="Hapus ke Sampah"
                                                                >
                                                                    <Trash2 className="size-3.5" />
                                                                </button>
                                                            </div>
                                                        ) : (
                                                            <div className="flex items-center justify-end gap-2">
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleRestore(promo)}
                                                                    className="px-2.5 py-1 rounded-lg border border-emerald-500/30 bg-emerald-500/10 text-emerald-600 font-bold hover:bg-emerald-500/20"
                                                                >
                                                                    Pulihkan
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleForceDelete(promo)}
                                                                    className="px-2.5 py-1 rounded-lg border border-rose-500/30 bg-rose-500/10 text-rose-600 font-bold hover:bg-rose-500/20"
                                                                >
                                                                    Hapus Permanen
                                                                </button>
                                                            </div>
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>

                            {/* Mobile Card List View */}
                            <div className="lg:hidden divide-y divide-border">
                                {promoList.map((promo) => {
                                    const imgUrl = promo.image_path ? `/storage/${promo.image_path}` : null;
                                    return (
                                        <div key={promo.id} className="p-4 space-y-3">
                                            <div className="flex items-start gap-3">
                                                {imgUrl && (
                                                    <img
                                                        src={imgUrl}
                                                        alt={promo.title}
                                                        className="h-16 w-28 shrink-0 rounded-lg object-cover border border-border"
                                                    />
                                                )}
                                                <div className="min-w-0 flex-1">
                                                    <h3 className="font-bold text-text text-sm line-clamp-2">{promo.title}</h3>
                                                    <p className="font-mono text-[11px] text-text-muted mt-0.5">/promo/{promo.slug}</p>
                                                    <div className="flex items-center gap-2 mt-2">
                                                        <span
                                                            className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold ${
                                                                promo.is_active
                                                                    ? 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/30'
                                                                    : 'bg-surface-2 text-text-muted border border-border'
                                                            }`}
                                                        >
                                                            <span
                                                                className={`size-1.5 rounded-full ${
                                                                    promo.is_active ? 'bg-emerald-500' : 'bg-text-muted'
                                                                }`}
                                                            />
                                                            {promo.is_active ? 'Aktif' : 'Nonaktif'}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="flex items-center justify-between pt-2 border-t border-border/60 text-xs">
                                                <a
                                                    href={`/promo/${promo.slug}`}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="text-amber-500 font-bold flex items-center gap-1"
                                                >
                                                    <span>Lihat detail</span>
                                                    <Eye className="size-3.5" />
                                                </a>

                                                <div className="flex items-center gap-2">
                                                    <Link
                                                        href={`/admin/promos/${promo.id}/ubah`}
                                                        className="px-3 py-1 rounded-lg border border-border font-semibold text-text"
                                                    >
                                                        Ubah
                                                    </Link>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleDelete(promo)}
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
                            {promos.links && (
                                <div className="p-4 border-t border-border">
                                    <Pagination links={promos.links} />
                                </div>
                            )}
                        </>
                    ) : (
                        <div className="p-12 text-center space-y-3">
                            <Tag className="size-10 text-text-muted mx-auto opacity-50" />
                            <h3 className="font-bold text-text">Belum ada promo</h3>
                            <p className="text-xs text-text-muted max-w-sm mx-auto">
                                Buat promo menarik dengan mengunggah gambar banner 1200 × 600 px.
                            </p>
                            <Link
                                href="/admin/promos/buat"
                                className="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition"
                            >
                                <Plus className="size-4" />
                                <span>Tambah Promo Baru</span>
                            </Link>
                        </div>
                    )}
                </div>
            </div>

            {/* Modal Konfirmasi Hapus */}
            <Modal show={deleteModalOpen} onClose={() => setDeleteModalOpen(false)} maxWidth="sm">
                <div className="p-6 space-y-4">
                    <div className="flex items-center justify-between pb-3 border-b border-border">
                        <h2 className="text-base font-bold text-rose-500 flex items-center gap-2">
                            <Trash2 className="size-5" />
                            <span>Pindahkan ke Sampah</span>
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
                        Pindahkan promo <strong className="text-text font-bold">"{selectedPromo?.title}"</strong> ke kotak sampah? Promo masih bisa dipulihkan selama 30 hari ke depan.
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
                            Ya, Pindahkan
                        </button>
                    </div>
                </div>
            </Modal>
        </AdminLayout>
    );
}

