import { useState, useEffect } from 'react';
import { Link, Head, router, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import Pagination from '../../../Components/Pagination';
import Modal from '../../../Components/Modal';
import CinemaImage from '../../../Components/CinemaImage';
import {
    Plus,
    Search,
    Upload,
    Download,
    Film as FilmIcon,
    AlertCircle,
    Eye,
    EyeOff,
    Trash2,
    RotateCcw,
    Edit3,
    CheckSquare,
    Square,
    ExternalLink,
    Tag,
    Shield,
    Clock,
    X,
} from 'lucide-react';

export default function Index({
    films,
    showTrash = false,
    filter = '',
    search = '',
    batchId = null,
    allCount = 0,
    activeCount = 0,
    inactiveCount = 0,
    noPosterCount = 0,
    needsCompletionCount = 0,
    trashedCount = 0,
    undoBatchId = null,
    undoBatchCount = 0,
    undoActivatedIds = [],
    canonicalGenres = [],
}) {
    const filmItems = films?.data || [];
    const [searchQuery, setSearchQuery] = useState(search || '');
    const [selectedIds, setSelectedIds] = useState([]);
    const [selectAllGlobal, setSelectAllGlobal] = useState(false);
    const [exportModalOpen, setExportModalOpen] = useState(false);
    const [deleteConfirmFilm, setDeleteConfirmFilm] = useState(null);

    // Filter tab handler
    const handleFilterTab = (targetFilter) => {
        router.get('/admin/films', {
            filter: targetFilter || undefined,
            trash: targetFilter === 'sampah' ? '1' : undefined,
            q: searchQuery || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        router.get('/admin/films', {
            filter: filter || undefined,
            trash: showTrash ? '1' : undefined,
            q: searchQuery || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    // Toggle single selection
    const toggleSelectFilm = (id) => {
        const strId = String(id);
        setSelectedIds((prev) =>
            prev.includes(strId) ? prev.filter((i) => i !== strId) : [...prev, strId]
        );
    };

    // Select all on current page
    const toggleSelectAllPage = () => {
        const pageIds = filmItems.map((f) => String(f.id));
        const allSelected = pageIds.every((id) => selectedIds.includes(id));
        if (allSelected) {
            setSelectedIds((prev) => prev.filter((id) => !pageIds.includes(id)));
            setSelectAllGlobal(false);
        } else {
            setSelectedIds((prev) => Array.from(new Set([...prev, ...pageIds])));
        }
    };

    // Bulk actions
    const handleBulkAction = (action) => {
        if (selectedIds.length === 0 && !selectAllGlobal) return;

        if (action === 'delete') {
            if (!confirm(`Hapus ${selectedIds.length} film yang dipilih?`)) return;
        }

        router.post('/admin/films/bulk-action', {
            action,
            film_ids: selectedIds,
            select_all: selectAllGlobal ? '1' : '0',
            filter: filter || undefined,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setSelectedIds([]);
                setSelectAllGlobal(false);
            },
        });
    };

    // Status toggle for single film
    const handleToggleStatus = (film) => {
        router.patch(`/admin/films/${film.id}/status`, {}, {
            preserveScroll: true,
        });
    };

    // Single delete or restore
    const handleDeleteSingle = (film) => {
        router.delete(`/admin/films/${film.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeleteConfirmFilm(null),
        });
    };

    const handleRestoreSingle = (film) => {
        router.post(`/admin/films/${film.id}/pulihkan`, {}, {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout title="Katalog Film">
            <Head>
                <title>Katalog Film - Admin SynemaX</title>
            </Head>

            <div className="space-y-6">
                {/* Header & Main Actions */}
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-border pb-5">
                    <div>
                        <span className="text-[10px] font-bold uppercase tracking-widest text-amber-500">Kelola film</span>
                        <h1 className="text-2xl sm:text-3xl font-black text-text tracking-tight mt-0.5">Katalog Film</h1>
                        <p className="text-xs text-text-muted mt-1">
                            {Number(allCount).toLocaleString('id-ID')} film, {Number(activeCount).toLocaleString('id-ID')} tampil di katalog bioskop.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            onClick={() => setExportModalOpen(true)}
                            className="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface px-3.5 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition shadow-xs"
                        >
                            <Download className="size-3.5" />
                            <span>Ekspor CSV</span>
                        </button>
                        <Link
                            href="/admin/age-ratings"
                            className="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface px-3.5 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition shadow-xs"
                        >
                            <Shield className="size-3.5" />
                            <span>Klasifikasi Usia</span>
                        </Link>
                        <Link
                            href="/admin/genres"
                            className="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface px-3.5 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition shadow-xs"
                        >
                            <Tag className="size-3.5" />
                            <span>Genre</span>
                        </Link>
                        <a
                            href="/admin/films/posters"
                            className="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface px-3.5 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition shadow-xs"
                        >
                            <FilmIcon className="size-3.5" />
                            <span>Poster Massal</span>
                        </a>
                        <Link
                            href="/admin/films/import"
                            className="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface px-3.5 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition shadow-xs"
                        >
                            <Upload className="size-3.5" />
                            <span>Impor CSV</span>
                        </Link>
                        <Link
                            href="/admin/films/create"
                            className="btn-gold-cinema inline-flex min-h-[40px] items-center gap-1.5 rounded-xl px-4 text-xs font-bold shadow-md"
                        >
                            <Plus className="size-4" />
                            <span>Tambah Film Baru</span>
                        </Link>
                    </div>
                </div>

                {/* Banner Peringatan Film Belum Tampil */}
                {inactiveCount > 0 && !showTrash && (
                    <div className="rounded-2xl border border-amber-500/40 bg-amber-500/10 p-4 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                        <div className="flex items-center gap-3">
                            <div className="p-2 rounded-xl bg-amber-500/20 text-amber-600 dark:text-amber-400 shrink-0">
                                <AlertCircle className="size-5" />
                            </div>
                            <div>
                                <p className="font-bold text-text">{Number(inactiveCount).toLocaleString('id-ID')} film belum tampil di katalog bioskop.</p>
                                <p className="text-text-muted mt-0.5">Film dengan status "Disembunyikan" tidak muncul di katalog publik penonton.</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2 self-end sm:self-auto shrink-0">
                            <button
                                type="button"
                                onClick={() => handleFilterTab('belum_tampil')}
                                className="inline-flex min-h-[36px] items-center rounded-xl border border-border bg-surface px-3 text-xs font-bold text-text hover:bg-surface-2 transition"
                            >
                                Lihat Daftar
                            </button>
                            <button
                                type="button"
                                onClick={() => {
                                    if (confirm(`Tampilkan seluruh ${inactiveCount} film yang berstatus disembunyikan sekarang?`)) {
                                        router.post('/admin/films/bulk-action', {
                                            action: 'activate',
                                            select_all: '1',
                                            filter: 'belum_tampil',
                                        }, { preserveScroll: true });
                                    }
                                }}
                                className="btn-gold-cinema inline-flex min-h-[36px] items-center rounded-xl px-4 text-xs font-bold shadow-xs"
                            >
                                Tampilkan Semua
                            </button>
                        </div>
                    </div>
                )}

                {/* Filter Tabs & Search Bar */}
                <div className="space-y-3">
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-border pb-3">
                        {/* Tabs */}
                        <div className="flex items-center flex-wrap gap-1.5">
                            <button
                                type="button"
                                onClick={() => handleFilterTab('')}
                                className={`rounded-xl px-3 py-1.5 text-xs font-bold transition ${
                                    !filter && !showTrash
                                        ? 'bg-amber-500 text-slate-950 font-black'
                                        : 'text-text-muted hover:text-text bg-surface-2'
                                }`}
                            >
                                Semua ({Number(allCount).toLocaleString('id-ID')})
                            </button>
                            <button
                                type="button"
                                onClick={() => handleFilterTab('belum_tampil')}
                                className={`rounded-xl px-3 py-1.5 text-xs font-bold transition ${
                                    filter === 'belum_tampil'
                                        ? 'bg-amber-500 text-slate-950 font-black'
                                        : 'text-text-muted hover:text-text bg-surface-2'
                                }`}
                            >
                                Belum Tampil ({Number(inactiveCount).toLocaleString('id-ID')})
                            </button>
                            <button
                                type="button"
                                onClick={() => handleFilterTab('tanpa_poster')}
                                className={`rounded-xl px-3 py-1.5 text-xs font-bold transition ${
                                    filter === 'tanpa_poster'
                                        ? 'bg-amber-500 text-slate-950 font-black'
                                        : 'text-text-muted hover:text-text bg-surface-2'
                                }`}
                            >
                                Tanpa Poster ({Number(noPosterCount).toLocaleString('id-ID')})
                            </button>
                            <button
                                type="button"
                                onClick={() => handleFilterTab('perlu_dilengkapi')}
                                className={`rounded-xl px-3 py-1.5 text-xs font-bold transition ${
                                    filter === 'perlu_dilengkapi'
                                        ? 'bg-amber-500 text-slate-950 font-black'
                                        : 'text-text-muted hover:text-text bg-surface-2'
                                }`}
                            >
                                Perlu Dilengkapi ({Number(needsCompletionCount).toLocaleString('id-ID')})
                            </button>
                            <button
                                type="button"
                                onClick={() => handleFilterTab('sampah')}
                                className={`rounded-xl px-3 py-1.5 text-xs font-bold transition ${
                                    showTrash
                                        ? 'bg-red-500 text-white font-black'
                                        : 'text-text-muted hover:text-text bg-surface-2'
                                }`}
                            >
                                Sampah ({Number(trashedCount).toLocaleString('id-ID')})
                            </button>
                        </div>

                        {/* Search Input */}
                        <form onSubmit={handleSearchSubmit} className="relative w-full md:w-64">
                            <Search className="size-4 text-text-muted absolute left-3 top-2.5" />
                            <input
                                type="text"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder="Cari judul film..."
                                className="w-full rounded-xl border border-border bg-surface pl-9 pr-8 py-1.5 text-xs text-text placeholder:text-text-muted focus:border-amber-500 focus:outline-none"
                            />
                            {searchQuery && (
                                <button
                                    type="button"
                                    onClick={() => {
                                        setSearchQuery('');
                                        router.get('/admin/films', { filter: filter || undefined, trash: showTrash ? '1' : undefined });
                                    }}
                                    className="absolute right-2.5 top-2.5 text-text-muted hover:text-text"
                                >
                                    <X className="size-3.5" />
                                </button>
                            )}
                        </form>
                    </div>

                    {/* Batch Actions Bar (Shows when 1 or more films selected) */}
                    {selectedIds.length > 0 && (
                        <div className="flex flex-wrap items-center justify-between gap-3 p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-xs">
                            <div className="flex items-center gap-2">
                                <span className="font-bold text-text">
                                    {selectedIds.length} film terpilih
                                </span>
                            </div>
                            <div className="flex flex-wrap items-center gap-2">
                                {!showTrash ? (
                                    <>
                                        <button
                                            type="button"
                                            onClick={() => handleBulkAction('activate')}
                                            className="inline-flex items-center gap-1.5 rounded-xl border border-emerald-500/40 bg-emerald-500/20 px-3 py-1.5 text-xs font-bold text-emerald-800 dark:text-emerald-200 hover:bg-emerald-500/30"
                                        >
                                            <Eye className="size-3.5" />
                                            <span>Tampilkan</span>
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => handleBulkAction('deactivate')}
                                            className="inline-flex items-center gap-1.5 rounded-xl border border-slate-400 bg-surface px-3 py-1.5 text-xs font-bold text-text hover:bg-surface-2"
                                        >
                                            <EyeOff className="size-3.5" />
                                            <span>Sembunyikan</span>
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => handleBulkAction('delete')}
                                            className="inline-flex items-center gap-1.5 rounded-xl border border-danger/40 bg-danger/10 px-3 py-1.5 text-xs font-bold text-danger hover:bg-danger/20"
                                        >
                                            <Trash2 className="size-3.5" />
                                            <span>Hapus</span>
                                        </button>
                                    </>
                                ) : (
                                    <button
                                        type="button"
                                        onClick={() => handleBulkAction('restore')}
                                        className="inline-flex items-center gap-1.5 rounded-xl border border-emerald-500/40 bg-emerald-500/20 px-3 py-1.5 text-xs font-bold text-emerald-800 dark:text-emerald-200 hover:bg-emerald-500/30"
                                    >
                                        <RotateCcw className="size-3.5" />
                                        <span>Pulihkan</span>
                                    </button>
                                )}
                            </div>
                        </div>
                    )}
                </div>

                {/* Films Table */}
                <div className="rounded-2xl border border-border bg-surface overflow-hidden shadow-xs">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="border-b border-border bg-surface-2 text-text-muted uppercase text-[10px] font-bold tracking-wider">
                                <tr>
                                    <th className="p-3.5 w-10 text-center">
                                        <button
                                            type="button"
                                            onClick={toggleSelectAllPage}
                                            className="text-text hover:text-amber-500"
                                            title="Pilih semua di halaman ini"
                                        >
                                            {filmItems.length > 0 && filmItems.every((f) => selectedIds.includes(String(f.id))) ? (
                                                <CheckSquare className="size-4 text-amber-500" />
                                            ) : (
                                                <Square className="size-4" />
                                            )}
                                        </button>
                                    </th>
                                    <th className="p-3.5 w-16">Poster</th>
                                    <th className="p-3.5">Judul & Genre</th>
                                    <th className="p-3.5">Durasi & Tahun</th>
                                    <th className="p-3.5">Batas Usia</th>
                                    <th className="p-3.5 text-center">Status</th>
                                    <th className="p-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {filmItems.length === 0 ? (
                                    <tr>
                                        <td colSpan="7" className="p-12 text-center text-text-muted">
                                            <FilmIcon className="size-8 mx-auto text-text-muted/40 mb-2" />
                                            <p className="font-bold text-text">Tidak ada film yang cocok</p>
                                            <p className="text-xs text-text-muted mt-1">Coba sesuaikan kata kunci atau filter pencarian.</p>
                                        </td>
                                    </tr>
                                ) : (
                                    filmItems.map((film) => {
                                        const isSelected = selectedIds.includes(String(film.id));
                                        const posterSrc = film.poster_url || (film.poster_path ? `/storage/${film.poster_path}` : null);

                                        return (
                                            <tr
                                                key={film.id}
                                                className={`transition hover:bg-surface-2/60 ${
                                                    isSelected ? 'bg-amber-500/5' : ''
                                                }`}
                                            >
                                                <td className="p-3.5 text-center">
                                                    <input
                                                        type="checkbox"
                                                        checked={isSelected}
                                                        onChange={() => toggleSelectFilm(film.id)}
                                                        className="rounded text-amber-500 focus:ring-amber-500"
                                                    />
                                                </td>
                                                <td className="p-3.5">
                                                    <CinemaImage
                                                        src={posterSrc}
                                                        alt={film.title}
                                                        title={film.title}
                                                        aspect="aspect-[2/3]"
                                                        className="w-11 rounded-lg border border-border shrink-0 shadow-xs"
                                                    />
                                                </td>
                                                <td className="p-3.5">
                                                    <div className="flex items-center gap-1.5 flex-wrap">
                                                        <span className="font-bold text-text text-sm hover:text-amber-500 transition">
                                                            {film.title}
                                                        </span>
                                                        {film.custom_badge && (
                                                            <span className="rounded bg-amber-500/15 border border-amber-400/30 px-1.5 py-0.2 text-[9px] font-black uppercase text-amber-600 dark:text-amber-400">
                                                                {film.custom_badge}
                                                            </span>
                                                        )}
                                                    </div>
                                                    <p className="text-text-muted text-[11px] mt-0.5 truncate max-w-sm">
                                                        {film.genre || 'Belum diisi'}
                                                    </p>
                                                </td>
                                                <td className="p-3.5">
                                                    <p className="font-mono text-text">{film.duration_minutes ? `${film.duration_minutes}m` : '-'}</p>
                                                    <p className="text-text-muted text-[11px]">{film.release_year || '-'}</p>
                                                </td>
                                                <td className="p-3.5">
                                                    <span className="rounded-md border border-border bg-surface-2 px-2 py-0.5 font-bold text-[10px] uppercase text-text">
                                                        {film.age_rating || 'SU'}
                                                    </span>
                                                </td>
                                                <td className="p-3.5 text-center">
                                                    {!showTrash ? (
                                                        <button
                                                            type="button"
                                                            onClick={() => handleToggleStatus(film)}
                                                            className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase transition ${
                                                                film.is_active
                                                                    ? 'bg-emerald-500/15 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/25'
                                                                    : 'bg-slate-500/15 border border-slate-500/30 text-text-muted hover:bg-slate-500/25'
                                                            }`}
                                                            title="Klik untuk mengubah status tampil/sembunyi"
                                                        >
                                                            <span className={`size-1.5 rounded-full ${film.is_active ? 'bg-emerald-500' : 'bg-slate-400'}`} />
                                                            <span>{film.is_active ? 'Tampil' : 'Disembunyikan'}</span>
                                                        </button>
                                                    ) : (
                                                        <span className="text-[10px] font-bold text-danger">Terhapus</span>
                                                    )}
                                                </td>
                                                <td className="p-3.5 text-right">
                                                    <div className="flex items-center justify-end gap-1.5">
                                                        {!showTrash ? (
                                                            <>
                                                                <Link
                                                                    href={`/film/${film.slug || film.id}`}
                                                                    target="_blank"
                                                                    className="p-1.5 rounded-lg border border-border bg-surface text-text-muted hover:text-text hover:border-amber-400 transition"
                                                                    title="Lihat di halaman publik"
                                                                >
                                                                    <ExternalLink className="size-3.5" />
                                                                </Link>
                                                                <Link
                                                                    href={`/admin/films/${film.id}/ubah`}
                                                                    className="p-1.5 rounded-lg border border-border bg-surface text-text hover:text-amber-500 hover:border-amber-400 transition"
                                                                    title="Ubah data film"
                                                                >
                                                                    <Edit3 className="size-3.5" />
                                                                </Link>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleDeleteSingle(film)}
                                                                    className="p-1.5 rounded-lg border border-danger/30 bg-danger/10 text-danger hover:bg-danger/20 transition"
                                                                    title="Hapus film"
                                                                >
                                                                    <Trash2 className="size-3.5" />
                                                                </button>
                                                            </>
                                                        ) : (
                                                            <button
                                                                type="button"
                                                                onClick={() => handleRestoreSingle(film)}
                                                                className="inline-flex items-center gap-1 rounded-lg border border-emerald-500/40 bg-emerald-500/15 px-2.5 py-1 text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/25 transition"
                                                            >
                                                                <RotateCcw className="size-3.5" />
                                                                <span>Pulihkan</span>
                                                            </button>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {films?.links && films.links.length > 3 && (
                        <div className="p-4 border-t border-border flex justify-between items-center flex-wrap gap-3">
                            <span className="text-xs text-text-muted">
                                Menampilkan {films.from || 0} - {films.to || 0} dari {films.total || 0} film
                            </span>
                            <Pagination links={films.links} />
                        </div>
                    )}
                </div>
            </div>

            {/* Modal Ekspor CSV */}
            <Modal show={exportModalOpen} onClose={() => setExportModalOpen(false)} maxWidth="sm">
                <div className="p-6">
                    <h3 className="text-base font-black text-text">Ekspor Data Film ke CSV</h3>
                    <p className="mt-2 text-xs leading-relaxed text-text-muted">
                        Unduh seluruh koleksi film dalam format CSV untuk diolah di spreadsheet atau sebagai cadangan data.
                    </p>
                    <div className="mt-5 flex justify-end gap-2.5">
                        <button
                            type="button"
                            onClick={() => setExportModalOpen(false)}
                            className="inline-flex min-h-10 items-center justify-center rounded-xl border border-border bg-surface-2 px-3.5 text-xs font-bold text-text hover:bg-surface transition"
                        >
                            Tutup
                        </button>
                        <a
                            href="/admin/films/export"
                            download="katalog-film-synemax.csv"
                            onClick={() => setExportModalOpen(false)}
                            className="btn-gold-cinema inline-flex min-h-10 items-center justify-center gap-1.5 rounded-xl px-4 text-xs font-bold shadow-md"
                        >
                            <Download className="size-3.5" />
                            <span>Unduh File CSV</span>
                        </a>
                    </div>
                </div>
            </Modal>
        </AdminLayout>
    );
}

