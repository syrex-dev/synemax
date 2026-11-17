import { useState } from 'react';
import { Link, Head, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import Pagination from '../../../Components/Pagination';
import Modal from '../../../Components/Modal';
import {
    Sparkles,
    Plus,
    ChevronUp,
    ChevronDown,
    Eye,
    Copy,
    Edit3,
    Trash2,
    CheckCircle2,
    X,
} from 'lucide-react';

export default function Index({
    banners,
    showTrash = false,
    trashedCount = 0,
}) {
    const bannerList = banners?.data || [];
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [selectedBanner, setSelectedBanner] = useState(null);

    const handleToggleStatus = (banner) => {
        router.patch(`/admin/banners/${banner.id}/status`, {}, {
            preserveScroll: true,
        });
    };

    const handleDuplicate = (banner) => {
        router.post(`/admin/banners/${banner.id}/duplikat`, {}, {
            preserveScroll: true,
        });
    };

    const handleDelete = (banner) => {
        setSelectedBanner(banner);
        setDeleteModalOpen(true);
    };

    const confirmDelete = () => {
        if (!selectedBanner) return;
        router.delete(`/admin/banners/${selectedBanner.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteModalOpen(false);
                setSelectedBanner(null);
            },
        });
    };

    const handleRestore = (banner) => {
        router.post(`/admin/banners/${banner.id}/restore`, {}, {
            preserveScroll: true,
        });
    };

    const handleForceDelete = (banner) => {
        if (confirm(`Hapus permanen banner "${banner.title}"? Tindakan ini tidak dapat dibatalkan.`)) {
            router.delete(`/admin/banners/${banner.id}/force`, {
                preserveScroll: true,
            });
        }
    };

    return (
        <AdminLayout title="Banner Hero">
            <Head title="Banner Hero | Admin SynemaX" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="text-xs font-bold uppercase tracking-wider text-amber-500 mb-1">
                            Konten Visual
                        </div>
                        <h1 className="text-2xl font-black tracking-tight text-text flex items-center gap-2.5">
                            <Sparkles className="size-6 text-amber-500" />
                            <span>Slide Banner Hero</span>
                        </h1>
                        <p className="text-xs text-text-muted mt-1 max-w-xl">
                            Banner visual layar lebar beranda (1600 × 680 px) tanpa teks overlay dengan transisi geser halus.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href="/admin/banners/buat"
                            className="inline-flex min-h-[42px] items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition-colors"
                        >
                            <Plus className="size-4" />
                            <span>Tambah Slide Hero</span>
                        </Link>
                    </div>
                </div>

                {/* Filter / Trash Bar */}
                <div className="flex items-center justify-between">
                    <div className="text-xs font-bold text-text-muted">
                        Total {banners?.total || bannerList.length} slide banner
                    </div>

                    {trashedCount > 0 && (
                        <Link
                            href={showTrash ? '/admin/banners' : '/admin/banners?trash=1'}
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

                {/* Main Table / List */}
                <div className="rounded-2xl border border-border bg-surface overflow-hidden shadow-xs">
                    {bannerList.length > 0 ? (
                        <>
                            {/* Desktop Table View */}
                            <div className="hidden lg:block overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead>
                                        <tr className="border-b border-border bg-surface-2 text-text-muted uppercase tracking-wider text-[11px] font-bold">
                                            <th className="py-3 px-4 w-20 text-center">Urutan</th>
                                            <th className="py-3 px-4">Banner & Judul Slide</th>
                                            <th className="py-3 px-4 w-44">Tautan Tujuan</th>
                                            <th className="py-3 px-4 w-28 text-center">Status</th>
                                            <th className="py-3 px-4 w-40 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border">
                                        {bannerList.map((banner, index) => {
                                            const imgUrl = banner.image_path ? `/storage/${banner.image_path}` : null;

                                            return (
                                                <tr key={banner.id} className="hover:bg-surface-2/60 transition-colors">
                                                    {/* Sort Order */}
                                                    <td className="py-3 px-4 text-center font-bold text-text-muted font-mono">
                                                        {banner.sort_order || index + 1}
                                                    </td>

                                                    {/* Banner & Title */}
                                                    <td className="py-3 px-4">
                                                        <div className="flex items-center gap-3">
                                                            {imgUrl ? (
                                                                <img
                                                                    src={imgUrl}
                                                                    alt={banner.title}
                                                                    className="h-14 w-28 shrink-0 rounded-lg object-cover border border-border shadow-xs"
                                                                />
                                                            ) : (
                                                                <div className="flex h-14 w-28 shrink-0 items-center justify-center rounded-lg border border-border bg-surface-2 text-[10px] font-semibold text-text-muted uppercase text-center p-1 leading-tight">
                                                                    Tanpa gambar
                                                                </div>
                                                            )}
                                                            <div className="min-w-0 flex-1">
                                                                <Link
                                                                    href={`/admin/banners/${banner.id}/ubah`}
                                                                    className="font-bold text-text hover:text-amber-500 line-clamp-1 leading-snug"
                                                                >
                                                                    {banner.title}
                                                                </Link>
                                                                <p className="mt-0.5 text-[11px] text-text-muted">
                                                                    Tipe: <span className="font-semibold text-text uppercase">{banner.type}</span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    {/* Target */}
                                                    <td className="py-3 px-4 text-xs">
                                                        <span className="font-semibold text-text">
                                                            {banner.target_type === 'none' && 'Tanpa Tautan'}
                                                            {banner.target_type === 'film' && `Film: ${banner.target_id || '-'}`}
                                                            {banner.target_type === 'promo' && `Promo: ${banner.target_id || '-'}`}
                                                            {banner.target_type === 'nobar' && `Nobar: ${banner.target_id || '-'}`}
                                                            {banner.target_type === 'custom' && (banner.cta_url || 'URL Kustom')}
                                                        </span>
                                                    </td>

                                                    {/* Status */}
                                                    <td className="py-3 px-4 text-center">
                                                        {!showTrash ? (
                                                            <button
                                                                type="button"
                                                                onClick={() => handleToggleStatus(banner)}
                                                                className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold transition-colors ${
                                                                    banner.is_active
                                                                        ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/20'
                                                                        : 'bg-surface-2 text-text-muted border border-border hover:text-text'
                                                                }`}
                                                                title="Klik untuk ubah status aktif"
                                                            >
                                                                <span
                                                                    className={`size-1.5 rounded-full ${
                                                                        banner.is_active ? 'bg-emerald-500' : 'bg-text-muted'
                                                                    }`}
                                                                />
                                                                <span>{banner.is_active ? 'Aktif' : 'Nonaktif'}</span>
                                                            </button>
                                                        ) : (
                                                            <span className="text-rose-500 font-bold text-[11px]">Di Sampah</span>
                                                        )}
                                                    </td>

                                                    {/* Actions */}
                                                    <td className="py-3 px-4 text-right">
                                                        {!showTrash ? (
                                                            <div className="flex items-center justify-end gap-1.5">
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleDuplicate(banner)}
                                                                    className="p-1.5 rounded-lg border border-border text-text-muted hover:text-text hover:border-amber-500 transition"
                                                                    title="Duplikat Slide"
                                                                >
                                                                    <Copy className="size-3.5" />
                                                                </button>
                                                                <Link
                                                                    href={`/admin/banners/${banner.id}/ubah`}
                                                                    className="px-2.5 py-1 rounded-lg border border-border font-semibold text-text hover:border-amber-500 hover:text-amber-500 transition"
                                                                >
                                                                    Ubah
                                                                </Link>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleDelete(banner)}
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
                                                                    onClick={() => handleRestore(banner)}
                                                                    className="px-2.5 py-1 rounded-lg border border-emerald-500/30 bg-emerald-500/10 text-emerald-600 font-bold hover:bg-emerald-500/20"
                                                                >
                                                                    Pulihkan
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleForceDelete(banner)}
                                                                    className="px-2.5 py-1 rounded-lg border border-rose-500/30 bg-rose-500/10 text-rose-600 font-bold hover:bg-rose-500/20"
                                                                >
                                                                    Hapus
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

                            {/* Mobile View */}
                            <div className="lg:hidden divide-y divide-border">
                                {bannerList.map((banner) => {
                                    const imgUrl = banner.image_path ? `/storage/${banner.image_path}` : null;
                                    return (
                                        <div key={banner.id} className="p-4 space-y-3">
                                            <div className="flex items-start gap-3">
                                                {imgUrl && (
                                                    <img
                                                        src={imgUrl}
                                                        alt={banner.title}
                                                        className="h-16 w-32 shrink-0 rounded-lg object-cover border border-border"
                                                    />
                                                )}
                                                <div className="min-w-0 flex-1">
                                                    <h3 className="font-bold text-text text-sm line-clamp-2">{banner.title}</h3>
                                                    <div className="flex items-center gap-2 mt-2">
                                                        <span
                                                            className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold ${
                                                                banner.is_active
                                                                    ? 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/30'
                                                                    : 'bg-surface-2 text-text-muted border border-border'
                                                            }`}
                                                        >
                                                            <span className={`size-1.5 rounded-full ${banner.is_active ? 'bg-emerald-500' : 'bg-text-muted'}`} />
                                                            {banner.is_active ? 'Aktif' : 'Nonaktif'}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="flex items-center justify-end gap-2 pt-2 border-t border-border/60 text-xs">
                                                <Link
                                                    href={`/admin/banners/${banner.id}/ubah`}
                                                    className="px-3 py-1 rounded-lg border border-border font-semibold text-text"
                                                >
                                                    Ubah
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => handleDelete(banner)}
                                                    className="p-1 rounded-lg border border-rose-500/30 text-rose-500"
                                                >
                                                    <Trash2 className="size-4" />
                                                </button>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>

                            {/* Pagination */}
                            {banners.links && (
                                <div className="p-4 border-t border-border">
                                    <Pagination links={banners.links} />
                                </div>
                            )}
                        </>
                    ) : (
                        <div className="p-12 text-center space-y-3">
                            <Sparkles className="size-10 text-text-muted mx-auto opacity-50" />
                            <h3 className="font-bold text-text">Belum ada slide banner hero</h3>
                            <p className="text-xs text-text-muted max-w-sm mx-auto">
                                Unggah banner visual berkualitas tinggi (1600 × 680 px) untuk memukau pengunjung di beranda.
                            </p>
                            <Link
                                href="/admin/banners/buat"
                                className="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition"
                            >
                                <Plus className="size-4" />
                                <span>Tambah Slide Hero</span>
                            </Link>
                        </div>
                    )}
                </div>
            </div>

            {/* Modal Hapus */}
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
                        Pindahkan banner <strong className="text-text font-bold">"{selectedBanner?.title}"</strong> ke kotak sampah?
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

