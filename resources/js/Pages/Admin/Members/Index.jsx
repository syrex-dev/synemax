import { useState, useTransition } from 'react';
import { Link, Head, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import Pagination from '../../../Components/Pagination';
import Modal from '../../../Components/Modal';
import {
    Users,
    UserCheck,
    CreditCard,
    Gift,
    Search,
    Plus,
    QrCode,
    Download,
    Printer,
    Edit3,
    Trash2,
    Eye,
    Check,
    X,
    Filter,
    AlertCircle
} from 'lucide-react';

export default function Index({
    members,
    stats = {},
    search = '',
    selectedStatus = '',
    selectedCardStatus = '',
    selectedRewardStatus = '',
}) {
    const memberItems = members?.data || (Array.isArray(members) ? members : []);
    const [selectedIds, setSelectedIds] = useState([]);
    const [query, setQuery] = useState(search);
    const [statusFilter, setStatusFilter] = useState(selectedStatus || '');
    const [cardFilter, setCardFilter] = useState(selectedCardStatus || '');
    const [rewardFilter, setRewardFilter] = useState(selectedRewardStatus || '');
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [memberToDelete, setMemberToDelete] = useState(null);
    const [isPending, startTransition] = useTransition();

    const handleSearch = (e) => {
        e?.preventDefault();
        router.get(
            '/admin/members',
            {
                q: query,
                status: statusFilter || undefined,
                card_status: cardFilter || undefined,
                reward_status: rewardFilter || undefined,
            },
            { preserveState: true, replace: true }
        );
    };

    const toggleSelectAll = () => {
        if (selectedIds.length === memberItems.length) {
            setSelectedIds([]);
        } else {
            setSelectedIds(memberItems.map((m) => m.id));
        }
    };

    const toggleSelect = (id) => {
        if (selectedIds.includes(id)) {
            setSelectedIds(selectedIds.filter((item) => item !== id));
        } else {
            setSelectedIds([...selectedIds, id]);
        }
    };

    const handleBulkCard = (issued) => {
        if (selectedIds.length === 0) return;
        router.post(
            '/admin/members/bulk-physical-card',
            { ids: selectedIds, has_physical_card: issued },
            {
                preserveScroll: true,
                onSuccess: () => setSelectedIds([]),
            }
        );
    };

    const handlePrintBatch = () => {
        if (selectedIds.length === 0) return;
        window.location.href = `/admin/members/print-batch?ids=${selectedIds.join(',')}`;
    };

    const confirmDelete = (member) => {
        setMemberToDelete(member);
        setDeleteModalOpen(true);
    };

    const executeDelete = () => {
        if (!memberToDelete) return;
        router.delete(`/admin/members/${memberToDelete.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeleteModalOpen(false),
        });
    };

    return (
        <AdminLayout title="Program Member">
            <Head title="Program Member · Panel Admin" />

            <div className="space-y-6">
                {/* Header & Quick Actions */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-black text-text tracking-tight">
                            Program Member
                        </h1>
                        <p className="mt-1 text-xs sm:text-sm text-text-muted">
                            Kelola data member setia, cetak kartu fisik, dan pantau progres siklus reward.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <a
                            href="/admin/members/export"
                            className="inline-flex min-h-11 items-center gap-2 rounded-xl border border-border bg-surface px-4 text-xs font-bold text-text hover:bg-surface-2 transition shadow-xs"
                        >
                            <Download className="size-4 text-text-muted" />
                            <span>Ekspor CSV</span>
                        </a>

                        <button
                            type="button"
                            onClick={handlePrintBatch}
                            disabled={selectedIds.length === 0}
                            className={`inline-flex min-h-11 items-center gap-2 rounded-xl border border-border px-4 text-xs font-bold transition shadow-xs ${
                                selectedIds.length > 0
                                    ? 'bg-surface text-amber-500 border-amber-500/50 hover:bg-surface-2'
                                    : 'bg-surface-2 text-text-muted opacity-50 cursor-not-allowed'
                            }`}
                        >
                            <Printer className="size-4" />
                            <span>Cetak Kartu ({selectedIds.length})</span>
                        </button>

                        <Link
                            href="/admin/members/scan"
                            className="inline-flex min-h-11 items-center gap-2 rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 transition shadow-xs"
                        >
                            <QrCode className="size-4" />
                            <span>Buka Scan Member</span>
                        </Link>

                        <Link
                            href="/admin/members/create"
                            className="btn-gold-cinema inline-flex min-h-11 items-center gap-2 rounded-xl px-4 text-xs font-bold shadow-sm"
                        >
                            <Plus className="size-4" />
                            <span>Tambah Member</span>
                        </Link>
                    </div>
                </div>

                {/* Top Stat Cards / Filters */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                    <div className="rounded-2xl border border-border bg-surface p-4 shadow-xs">
                        <span className="text-[11px] font-bold uppercase text-text-muted">Total Member</span>
                        <div className="mt-2 text-2xl font-black text-text font-mono">
                            {stats.total || 0}
                        </div>
                    </div>

                    <div className="rounded-2xl border border-border bg-surface p-4 shadow-xs">
                        <span className="text-[11px] font-bold uppercase text-emerald-500">Member Aktif</span>
                        <div className="mt-2 text-2xl font-black text-emerald-500 font-mono">
                            {stats.aktif || 0}
                        </div>
                    </div>

                    <button
                        type="button"
                        onClick={() => {
                            const next = cardFilter === 'belum' ? '' : 'belum';
                            setCardFilter(next);
                            router.get('/admin/members', { card_status: next || undefined, q: query }, { preserveState: true });
                        }}
                        className={`text-left rounded-2xl border p-4 shadow-xs transition-all ${
                            cardFilter === 'belum'
                                ? 'border-amber-500 bg-amber-500/10'
                                : 'border-border bg-surface hover:border-amber-500/40'
                        }`}
                    >
                        <div className="flex items-center justify-between">
                            <span className="text-[11px] font-bold uppercase text-amber-500">Kartu Fisik Belum</span>
                            <CreditCard className="size-4 text-amber-500" />
                        </div>
                        <div className="mt-2 text-2xl font-black text-amber-500 font-mono">
                            {stats.card_missing || 0}
                        </div>
                    </button>

                    <button
                        type="button"
                        onClick={() => {
                            const next = rewardFilter === 'pending' ? '' : 'pending';
                            setRewardFilter(next);
                            router.get('/admin/members', { reward_status: next || undefined, q: query }, { preserveState: true });
                        }}
                        className={`text-left rounded-2xl border p-4 shadow-xs transition-all ${
                            rewardFilter === 'pending'
                                ? 'border-amber-500 bg-amber-500/10'
                                : 'border-border bg-surface hover:border-amber-500/40'
                        }`}
                    >
                        <div className="flex items-center justify-between">
                            <span className="text-[11px] font-bold uppercase text-amber-500">Reward Pending</span>
                            <Gift className="size-4 text-amber-500" />
                        </div>
                        <div className="mt-2 text-2xl font-black text-amber-500 font-mono">
                            {stats.pending_rewards || 0}
                        </div>
                    </button>
                </div>

                {/* Filter & Search Bar */}
                <form onSubmit={handleSearch} className="rounded-2xl border border-border bg-surface p-4 shadow-xs flex flex-wrap items-center gap-3">
                    <div className="relative flex-1 min-w-[200px]">
                        <Search className="size-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted" />
                        <input
                            type="search"
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder="Cari nama, panggilan, atau ID member..."
                            className="input-cinema w-full pl-10 pr-4 py-2 text-xs rounded-xl"
                        />
                    </div>

                    <select
                        value={statusFilter}
                        onChange={(e) => {
                            setStatusFilter(e.target.value);
                            router.get('/admin/members', { status: e.target.value || undefined, q: query }, { preserveState: true });
                        }}
                        aria-label="Filter status member"
                        className="input-cinema py-2 px-3 text-xs rounded-xl"
                    >
                        <option value="">Semua Status</option>
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                        <option value="diblokir">Diblokir</option>
                    </select>

                    <select
                        value={cardFilter}
                        onChange={(e) => {
                            setCardFilter(e.target.value);
                            router.get('/admin/members', { card_status: e.target.value || undefined, q: query }, { preserveState: true });
                        }}
                        aria-label="Filter status kartu fisik"
                        className="input-cinema py-2 px-3 text-xs rounded-xl"
                    >
                        <option value="">Status Kartu Fisik</option>
                        <option value="sudah">Sudah Ada Kartu</option>
                        <option value="belum">Belum Dibuatkan</option>
                    </select>

                    <button
                        type="submit"
                        className="btn-gold-cinema px-4 py-2 text-xs font-bold rounded-xl"
                    >
                        Cari
                    </button>

                    {(query || statusFilter || cardFilter || rewardFilter) && (
                        <button
                            type="button"
                            onClick={() => {
                                setQuery('');
                                setStatusFilter('');
                                setCardFilter('');
                                setRewardFilter('');
                                router.get('/admin/members');
                            }}
                            className="btn-secondary-cinema px-3 py-2 text-xs font-bold rounded-xl"
                        >
                            Reset
                        </button>
                    )}
                </form>

                {/* Bulk Card Actions Bar */}
                {selectedIds.length > 0 && (
                    <div className="flex items-center justify-between p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-xs">
                        <span className="font-bold text-text">
                            {selectedIds.length} member terpilih
                        </span>
                        <div className="flex items-center gap-2">
                            <button
                                type="button"
                                onClick={() => handleBulkCard(true)}
                                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold transition"
                            >
                                <Check className="size-3.5" />
                                <span>Tandai Sudah Kartu Fisik</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => handleBulkCard(false)}
                                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-surface border border-border hover:bg-surface-2 text-text font-bold transition"
                            >
                                <X className="size-3.5" />
                                <span>Tandai Belum</span>
                            </button>
                        </div>
                    </div>
                )}

                {/* Members Table */}
                <div className="rounded-2xl border border-border bg-surface overflow-hidden shadow-xs">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr className="border-b border-border bg-surface-2/60 text-text-muted font-bold uppercase tracking-wider">
                                    <th className="p-3 w-10 text-center">
                                        <input
                                            type="checkbox"
                                            checked={memberItems.length > 0 && selectedIds.length === memberItems.length}
                                            onChange={toggleSelectAll}
                                            aria-label="Pilih semua member"
                                            className="rounded border-border"
                                        />
                                    </th>
                                    <th className="p-3">Member</th>
                                    <th className="p-3">ID Member</th>
                                    <th className="p-3">Kartu Fisik</th>
                                    <th className="p-3">Progres Siklus</th>
                                    <th className="p-3">Total Kunjungan</th>
                                    <th className="p-3">Status</th>
                                    <th className="p-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {memberItems.length === 0 ? (
                                    <tr>
                                        <td colSpan="8" className="p-8 text-center text-text-muted">
                                            Tidak ada data member yang sesuai filter.
                                        </td>
                                    </tr>
                                ) : (
                                    memberItems.map((member) => {
                                        const isSelected = selectedIds.includes(member.id);
                                        const visitsInCycle = (member.valid_visits_count || 0) % 10;
                                        const cycleNum = Math.floor((member.valid_visits_count || 0) / 10) + 1;

                                        return (
                                            <tr
                                                key={member.id}
                                                className={`hover:bg-surface-2/50 transition-colors ${
                                                    isSelected ? 'bg-amber-500/5' : ''
                                                }`}
                                            >
                                                <td className="p-3 text-center">
                                                    <input
                                                        type="checkbox"
                                                        checked={isSelected}
                                                        onChange={() => toggleSelect(member.id)}
                                                        aria-label={`Pilih member ${member.name}`}
                                                        className="rounded border-border"
                                                    />
                                                </td>

                                                {/* Member Details */}
                                                <td className="p-3">
                                                    <div className="flex items-center gap-3">
                                                        <div className="size-9 rounded-full bg-surface-2 border border-border flex items-center justify-center font-black text-amber-500 shrink-0">
                                                            {member.photo_path ? (
                                                                <img
                                                                    src={`/storage/${member.photo_path}`}
                                                                    alt={member.name}
                                                                    className="size-full object-cover rounded-full"
                                                                />
                                                            ) : (
                                                                member.name?.charAt(0).toUpperCase()
                                                            )}
                                                        </div>
                                                        <div className="min-w-0">
                                                            <Link
                                                                href={`/admin/members/${member.id}`}
                                                                className="font-black text-text hover:text-amber-500 transition line-clamp-1"
                                                            >
                                                                {member.name}
                                                            </Link>
                                                            {member.nickname && (
                                                                <span className="text-[11px] text-text-muted block">
                                                                    "{member.nickname}"
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </td>

                                                {/* Member Code */}
                                                <td className="p-3">
                                                    <span className="font-mono font-bold text-text bg-surface-2 px-2 py-0.5 rounded-md border border-border text-[11px]">
                                                        {member.code || member.member_code}
                                                    </span>
                                                </td>

                                                {/* Physical Card Status Badge (Resolves User Request 2!) */}
                                                <td className="p-3">
                                                    {member.has_physical_card ? (
                                                        <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-500 border border-emerald-500/30">
                                                            <Check className="size-3" />
                                                            <span>Sudah Ada</span>
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-500/10 text-amber-500 border border-amber-500/30">
                                                            <AlertCircle className="size-3" />
                                                            <span>Belum Dibuatkan</span>
                                                        </span>
                                                    )}
                                                </td>

                                                {/* Cycle Progress */}
                                                <td className="p-3">
                                                    <div className="space-y-1">
                                                        <div className="flex items-center justify-between text-[10px] text-text-muted">
                                                            <span>{visitsInCycle}/10</span>
                                                            <span>Siklus {cycleNum}</span>
                                                        </div>
                                                        <div className="h-1.5 w-24 rounded-full bg-surface-2 overflow-hidden">
                                                            <div
                                                                className="h-full bg-amber-500 rounded-full"
                                                                style={{ width: `${(visitsInCycle / 10) * 100}%` }}
                                                            />
                                                        </div>
                                                    </div>
                                                </td>

                                                {/* Total Visits */}
                                                <td className="p-3 font-mono font-bold text-text">
                                                    {member.valid_visits_count || 0}
                                                </td>

                                                {/* Membership Status */}
                                                <td className="p-3">
                                                    <span
                                                        className={`inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold capitalize ${
                                                            member.status === 'aktif'
                                                                ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20'
                                                                : 'bg-rose-500/10 text-rose-500 border border-rose-500/20'
                                                        }`}
                                                    >
                                                        {member.status}
                                                    </span>
                                                </td>

                                                {/* Action Buttons */}
                                                <td className="p-3 text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Link
                                                            href={`/admin/members/${member.id}`}
                                                            title="Detail Member"
                                                            className="p-1.5 rounded-lg text-text-muted hover:text-amber-500 hover:bg-surface-2 transition"
                                                        >
                                                            <Eye className="size-3.5" />
                                                        </Link>
                                                        <a
                                                            href={`/admin/members/${member.id}/kartu`}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            title="Cetak Kartu"
                                                            className="p-1.5 rounded-lg text-text-muted hover:text-amber-500 hover:bg-surface-2 transition"
                                                        >
                                                            <Printer className="size-3.5" />
                                                        </a>
                                                        <Link
                                                            href={`/admin/members/${member.id}/edit`}
                                                            title="Ubah Member"
                                                            className="p-1.5 rounded-lg text-text-muted hover:text-amber-500 hover:bg-surface-2 transition"
                                                        >
                                                            <Edit3 className="size-3.5" />
                                                        </Link>
                                                        <button
                                                            type="button"
                                                            onClick={() => confirmDelete(member)}
                                                            title="Hapus Member"
                                                            className="p-1.5 rounded-lg text-text-muted hover:text-rose-500 hover:bg-surface-2 transition"
                                                        >
                                                            <Trash2 className="size-3.5" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Clean Pagination */}
                {members?.links && (
                    <Pagination links={members.links} />
                )}
            </div>

            {/* Delete Modal */}
            <Modal
                isOpen={deleteModalOpen}
                onClose={() => setDeleteModalOpen(false)}
                title="Hapus Data Member"
                maxWidth="max-w-md"
            >
                <div className="space-y-4">
                    <p className="text-xs text-text-muted leading-relaxed">
                        Yakin ingin menghapus member <span className="font-bold text-text">{memberToDelete?.name}</span>? Riwayat kunjungan dan kartu digital akan dinonaktifkan.
                    </p>
                    <div className="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            onClick={() => setDeleteModalOpen(false)}
                            className="btn-secondary-cinema px-4 py-2 text-xs font-bold rounded-xl"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            onClick={executeDelete}
                            className="px-4 py-2 text-xs font-bold rounded-xl bg-rose-600 hover:bg-rose-500 text-white transition shadow-sm"
                        >
                            Hapus
                        </button>
                    </div>
                </div>
            </Modal>
        </AdminLayout>
    );
}

