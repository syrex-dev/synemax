import { useState } from 'react';
import { Link, Head, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import Pagination from '../../../Components/Pagination';
import Modal from '../../../Components/Modal';
import {
    Newspaper,
    Plus,
    Calendar,
    Pin,
    Eye,
    Edit3,
    Trash2,
    X,
} from 'lucide-react';

export default function Index({
    articles,
    currentCategory = '',
    currentStatus = '',
    showTrash = false,
    trashedCount = 0,
}) {
    const articleList = articles?.data || [];
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [selectedArticle, setSelectedArticle] = useState(null);

    const categories = [
        { key: '', label: 'Semua Kategori' },
        { key: 'Berita', label: 'Berita' },
        { key: 'Pengumuman', label: 'Pengumuman' },
    ];

    const statuses = [
        { key: '', label: 'Semua Status' },
        { key: 'terbit', label: 'Terbit' },
        { key: 'draft', label: 'Draft' },
    ];

    const handleDelete = (article) => {
        setSelectedArticle(article);
        setDeleteModalOpen(true);
    };

    const confirmDelete = () => {
        if (!selectedArticle) return;
        router.delete(`/admin/berita/${selectedArticle.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteModalOpen(false);
                setSelectedArticle(null);
            },
        });
    };

    const handleRestore = (id) => {
        router.post(`/admin/berita/${id}/pulihkan`, {}, {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout title="Kabar Bioskop">
            <Head title="Kabar Bioskop | Admin SynemaX" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="text-xs font-bold uppercase tracking-wider text-amber-500 mb-1">
                            Publikasi & Komunikasi
                        </div>
                        <h1 className="text-2xl font-black tracking-tight text-text flex items-center gap-2.5">
                            <Newspaper className="size-6 text-amber-500" />
                            <span>Kabar Bioskop</span>
                        </h1>
                        <p className="text-xs text-text-muted mt-1 max-w-xl">
                            Tulis dan kelola berita seputar bioskop serta pengumuman fasilitas untuk penonton.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href="/admin/berita/buat"
                            className="inline-flex min-h-[42px] items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition-colors"
                        >
                            <Plus className="size-4" />
                            <span>Tulis Artikel Baru</span>
                        </Link>
                    </div>
                </div>

                {/* Filters */}
                <div className="flex flex-wrap items-center justify-between gap-4">
                    {/* Category Tabs */}
                    <div className="flex flex-wrap gap-2">
                        {categories.map((cat) => {
                            const params = new URLSearchParams();
                            if (cat.key) params.set('kategori', cat.key);
                            if (currentStatus) params.set('status', currentStatus);
                            const url = `/admin/berita${params.toString() ? '?' + params.toString() : ''}`;
                            const isActive = currentCategory === cat.key;

                            return (
                                <Link
                                    key={cat.key}
                                    href={url}
                                    className={`inline-flex min-h-[38px] items-center rounded-xl px-4 text-xs font-bold transition-colors ${
                                        isActive
                                            ? 'bg-amber-500 text-slate-950 shadow-xs'
                                            : 'border border-border bg-surface text-text-muted hover:text-text'
                                    }`}
                                >
                                    {cat.label}
                                </Link>
                            );
                        })}
                    </div>

                    {/* Status Tabs */}
                    <div className="flex flex-wrap items-center gap-2">
                        {statuses.map((st) => {
                            const params = new URLSearchParams();
                            if (currentCategory) params.set('kategori', currentCategory);
                            if (st.key) params.set('status', st.key);
                            const url = `/admin/berita${params.toString() ? '?' + params.toString() : ''}`;
                            const isActive = currentStatus === st.key;

                            return (
                                <Link
                                    key={st.key}
                                    href={url}
                                    className={`inline-flex min-h-[36px] items-center rounded-lg px-3 text-xs font-semibold transition-colors ${
                                        isActive
                                            ? 'bg-surface-2 text-text font-bold border border-border'
                                            : 'text-text-muted hover:text-text'
                                    }`}
                                >
                                    {st.label}
                                </Link>
                            );
                        })}

                        {trashedCount > 0 && (
                            <Link
                                href={showTrash ? '/admin/berita' : '/admin/berita?trash=1'}
                                className={`inline-flex min-h-[36px] items-center gap-1.5 rounded-lg border px-3 text-xs font-bold transition-colors ${
                                    showTrash
                                        ? 'border-rose-500 bg-rose-500/10 text-rose-600'
                                        : 'border-border bg-surface text-text-muted hover:text-rose-500'
                                }`}
                            >
                                <Trash2 className="size-3.5" />
                                <span>Sampah ({trashedCount})</span>
                            </Link>
                        )}
                    </div>
                </div>

                {/* Content Table / Cards */}
                <div className="rounded-2xl border border-border bg-surface overflow-hidden shadow-xs">
                    {articleList.length > 0 ? (
                        <>
                            {/* Desktop Table View */}
                            <div className="hidden lg:block overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead>
                                        <tr className="border-b border-border bg-surface-2 text-text-muted uppercase tracking-wider text-[11px] font-bold">
                                            <th className="py-3 px-4">Artikel & Ringkasan</th>
                                            <th className="py-3 px-4 w-32">Kategori</th>
                                            <th className="py-3 px-4 w-40">Tanggal Terbit</th>
                                            <th className="py-3 px-4 w-28 text-center">Status</th>
                                            <th className="py-3 px-4 w-36 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border">
                                        {articleList.map((article) => {
                                            const coverUrl = article.cover_path ? `/storage/${article.cover_path}` : null;

                                            return (
                                                <tr key={article.id} className="hover:bg-surface-2/60 transition-colors">
                                                    {/* Article & Title */}
                                                    <td className="py-3 px-4">
                                                        <div className="flex items-center gap-3">
                                                            {coverUrl ? (
                                                                <img
                                                                    src={coverUrl}
                                                                    alt={article.judul}
                                                                    className="h-12 w-20 shrink-0 rounded-lg object-cover border border-border"
                                                                />
                                                            ) : (
                                                                <div className="h-12 w-20 shrink-0 rounded-lg border border-border bg-surface-2 flex items-center justify-center text-[10px] font-semibold text-text-muted">
                                                                    No Cover
                                                                </div>
                                                            )}
                                                            <div className="min-w-0 flex-1">
                                                                <div className="flex items-center gap-2">
                                                                    {article.is_pinned && (
                                                                        <Pin className="size-3.5 text-amber-500 fill-amber-500 shrink-0" />
                                                                    )}
                                                                    <Link
                                                                        href={`/admin/berita/${article.id}/ubah`}
                                                                        className="font-bold text-text hover:text-amber-500 line-clamp-1 text-sm"
                                                                    >
                                                                        {article.judul}
                                                                    </Link>
                                                                </div>
                                                                <p className="mt-0.5 truncate text-[11px] text-text-muted">
                                                                    /kabar/{article.slug}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    {/* Category */}
                                                    <td className="py-3 px-4">
                                                        <span className="inline-flex items-center rounded-md border border-border bg-surface-2 px-2 py-0.5 text-[11px] font-bold text-text">
                                                            {article.kategori}
                                                        </span>
                                                    </td>

                                                    {/* Publish Date */}
                                                    <td className="py-3 px-4 text-xs text-text">
                                                        {article.tanggal_terbit || '-'}
                                                    </td>

                                                    {/* Status */}
                                                    <td className="py-3 px-4 text-center">
                                                        <span
                                                            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold ${
                                                                article.status_publikasi === 'terbit'
                                                                    ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30'
                                                                    : 'bg-surface-2 text-text-muted border border-border'
                                                            }`}
                                                        >
                                                            {article.status_publikasi === 'terbit' ? 'Terbit' : 'Draft'}
                                                        </span>
                                                    </td>

                                                    {/* Actions */}
                                                    <td className="py-3 px-4 text-right">
                                                        {!showTrash ? (
                                                            <div className="flex items-center justify-end gap-1.5">
                                                                {article.status_publikasi === 'terbit' && (
                                                                    <a
                                                                        href={`/kabar/${article.slug}`}
                                                                        target="_blank"
                                                                        rel="noreferrer"
                                                                        className="p-1.5 rounded-lg border border-border text-text-muted hover:text-text hover:border-amber-500 transition"
                                                                        title="Lihat di Web"
                                                                    >
                                                                        <Eye className="size-3.5" />
                                                                    </a>
                                                                )}
                                                                <Link
                                                                    href={`/admin/berita/${article.id}/ubah`}
                                                                    className="px-2.5 py-1 rounded-lg border border-border font-semibold text-text hover:border-amber-500 hover:text-amber-500 transition"
                                                                >
                                                                    Ubah
                                                                </Link>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleDelete(article)}
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
                                                                    onClick={() => handleRestore(article.id)}
                                                                    className="px-2.5 py-1 rounded-lg border border-emerald-500/30 bg-emerald-500/10 text-emerald-600 font-bold hover:bg-emerald-500/20"
                                                                >
                                                                    Pulihkan
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
                                {articleList.map((article) => {
                                    const coverUrl = article.cover_path ? `/storage/${article.cover_path}` : null;
                                    return (
                                        <div key={article.id} className="p-4 space-y-3">
                                            <div className="flex items-start gap-3">
                                                {coverUrl && (
                                                    <img
                                                        src={coverUrl}
                                                        alt={article.judul}
                                                        className="h-14 w-20 shrink-0 rounded-lg object-cover border border-border"
                                                    />
                                                )}
                                                <div className="min-w-0 flex-1">
                                                    <h3 className="font-bold text-text text-sm line-clamp-2">{article.judul}</h3>
                                                    <div className="flex items-center gap-2 mt-1 text-xs text-text-muted">
                                                        <span className="font-semibold">{article.kategori}</span>
                                                        <span>•</span>
                                                        <span>{article.status_publikasi}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="flex items-center justify-end gap-2 pt-2 border-t border-border/60 text-xs">
                                                <Link
                                                    href={`/admin/berita/${article.id}/ubah`}
                                                    className="px-3 py-1 rounded-lg border border-border font-semibold text-text"
                                                >
                                                    Ubah
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => handleDelete(article)}
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
                            {articles.links && (
                                <div className="p-4 border-t border-border">
                                    <Pagination links={articles.links} />
                                </div>
                            )}
                        </>
                    ) : (
                        <div className="p-12 text-center space-y-3">
                            <Newspaper className="size-10 text-text-muted mx-auto opacity-50" />
                            <h3 className="font-bold text-text">Belum ada artikel</h3>
                            <p className="text-xs text-text-muted max-w-sm mx-auto">
                                Tulis berita seputar bioskop mini atau pengumuman fasilitas untuk penonton.
                            </p>
                            <Link
                                href="/admin/berita/buat"
                                className="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition"
                            >
                                <Plus className="size-4" />
                                <span>Tulis Artikel Baru</span>
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
                        Pindahkan artikel <strong className="text-text font-bold">"{selectedArticle?.judul}"</strong> ke kotak sampah?
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

