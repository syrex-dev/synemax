import { useState } from 'react';
import { Link, Head, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import Modal from '../../../Components/Modal';
import {
    User,
    CreditCard,
    Calendar,
    Clock,
    Award,
    Gift,
    Phone,
    MapPin,
    Share2,
    CheckCircle2,
    AlertCircle,
    Plus,
    Printer,
    Edit3,
    ArrowLeft,
    Check,
    X,
    ExternalLink
} from 'lucide-react';

export default function Show({
    member,
    visits = [],
    rewards = [],
    events = [],
    lastValidVisit = null,
}) {
    if (!member) return null;

    const [activeTab, setActiveTab] = useState('kunjungan');
    const [cardModalOpen, setCardModalOpen] = useState(false);
    const [hasPhysicalCard, setHasPhysicalCard] = useState(Boolean(member.has_physical_card));

    const totalValidVisits = visits.filter((v) => v.active).length;
    const currentCycleVisits = totalValidVisits % 10;
    const currentCycleNum = Math.floor(totalValidVisits / 10) + 1;

    const formatDate = (dateStr) => {
        if (!dateStr) return '-';
        try {
            return new Date(dateStr).toLocaleDateString('id-ID', {
                weekday: 'short',
                day: 'numeric',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        } catch (e) {
            return dateStr;
        }
    };

    const handleUpdatePhysicalCard = (val) => {
        router.post(
            `/admin/members/${member.id}/physical-card`,
            { has_physical_card: val },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setHasPhysicalCard(val);
                    setCardModalOpen(false);
                },
            }
        );
    };

    const handleShareWhatsApp = () => {
        const text = `Halo Kak ${member.name}, ini kartu member digital resmi SynemaX Anda:\n` +
            `ID: ${member.code || member.member_code}\n` +
            `Total Kunjungan: ${totalValidVisits} kali\n` +
            `Lihat kartu & kumpulkan stempel di: ${window.location.origin}/member/${member.code || member.member_code}`;
        window.open(`https://wa.me/${member.phone ? member.phone.replace(/\D/g, '') : ''}?text=${encodeURIComponent(text)}`, '_blank');
    };

    return (
        <AdminLayout title={`Member: ${member.name}`}>
            <Head title={`Member ${member.name} · Panel Admin`} />

            <div className="space-y-6">
                {/* Back button */}
                <div>
                    <Link
                        href="/admin/members"
                        className="inline-flex min-h-11 items-center gap-2 text-xs font-bold text-amber-500 hover:text-amber-400 transition"
                    >
                        <ArrowLeft className="size-4" />
                        <span>Kembali ke Data Member</span>
                    </Link>
                </div>

                {/* Profile Header Card */}
                <div className="rounded-3xl border border-border bg-surface p-6 sm:p-8 shadow-xl">
                    <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        <div className="flex items-start sm:items-center gap-4 sm:gap-6">
                            <div className="size-20 sm:size-24 rounded-3xl bg-surface-2 border-2 border-border overflow-hidden flex items-center justify-center text-3xl font-black text-amber-500 shrink-0 shadow-inner">
                                {member.photo_path ? (
                                    <img
                                        src={`/storage/${member.photo_path}`}
                                        alt={member.name}
                                        className="size-full object-cover"
                                    />
                                ) : (
                                    member.name?.charAt(0).toUpperCase()
                                )}
                            </div>

                            <div className="space-y-2">
                                <div className="flex flex-wrap items-center gap-2.5">
                                    <h1 className="text-2xl sm:text-3xl font-black text-text tracking-tight">
                                        {member.name}
                                    </h1>
                                    {member.nickname && (
                                        <span className="text-sm font-bold text-amber-500 bg-amber-500/10 px-2.5 py-0.5 rounded-lg border border-amber-500/30">
                                            "{member.nickname}"
                                        </span>
                                    )}
                                    <span className={`px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase ${
                                        member.status === 'aktif'
                                            ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/30'
                                            : 'bg-rose-500/10 text-rose-500 border border-rose-500/30'
                                    }`}>
                                        {member.status}
                                    </span>
                                </div>

                                <div className="flex flex-wrap items-center gap-3 text-xs text-text-muted">
                                    <span className="font-mono font-bold bg-surface-2 px-2 py-0.5 rounded border border-border">
                                        ID: {member.code || member.member_code}
                                    </span>
                                    {member.phone && (
                                        <div className="flex items-center gap-1">
                                            <Phone className="size-3.5 text-amber-500" />
                                            <span>{member.phone}</span>
                                        </div>
                                    )}
                                    {member.address && (
                                        <div className="flex items-center gap-1">
                                            <MapPin className="size-3.5 text-amber-500" />
                                            <span>{member.address}</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Quick action buttons */}
                        <div className="flex flex-wrap items-center gap-2.5 self-start lg:self-center">
                            <button
                                type="button"
                                onClick={() => setCardModalOpen(true)}
                                className={`inline-flex min-h-11 items-center gap-2 rounded-xl px-4 text-xs font-bold transition shadow-xs ${
                                    hasPhysicalCard
                                        ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/30 hover:bg-emerald-500/20'
                                        : 'bg-amber-500/10 text-amber-500 border border-amber-500/30 hover:bg-amber-500/20'
                                }`}
                            >
                                <CreditCard className="size-4" />
                                <span>{hasPhysicalCard ? 'Kartu Fisik: Siap' : 'Kartu Fisik: Belum'}</span>
                            </button>

                            <button
                                type="button"
                                onClick={handleShareWhatsApp}
                                className="inline-flex min-h-11 items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white px-4 text-xs font-bold transition shadow-sm"
                            >
                                <Share2 className="size-4" />
                                <span>Kirim Kartu WA</span>
                            </button>

                            <a
                                href={`/admin/members/${member.id}/kartu`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex min-h-11 items-center gap-2 rounded-xl border border-border bg-surface px-4 text-xs font-bold text-text hover:bg-surface-2 transition shadow-xs"
                            >
                                <Printer className="size-4 text-amber-500" />
                                <span>Cetak Kartu</span>
                            </a>

                            <Link
                                href={`/admin/members/${member.id}/edit`}
                                className="btn-secondary-cinema inline-flex min-h-11 items-center gap-2 rounded-xl px-4 text-xs font-bold"
                            >
                                <Edit3 className="size-4" />
                                <span>Edit</span>
                            </Link>
                        </div>
                    </div>

                    {/* Cycle Progress Bar Card */}
                    <div className="mt-8 pt-6 border-t border-border grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div className="space-y-1">
                            <span className="text-xs font-bold uppercase text-text-muted">Progres Siklus Stempel</span>
                            <div className="text-xl font-black text-text font-mono">
                                {currentCycleVisits} / 10 Stempel
                            </div>
                            <div className="h-2 w-full rounded-full bg-surface-2 overflow-hidden mt-2">
                                <div
                                    className="h-full bg-amber-500 rounded-full transition-all duration-500"
                                    style={{ width: `${(currentCycleVisits / 10) * 100}%` }}
                                />
                            </div>
                        </div>

                        <div className="space-y-1">
                            <span className="text-xs font-bold uppercase text-text-muted">Total Kunjungan Valid</span>
                            <div className="text-xl font-black text-amber-500 font-mono">
                                {totalValidVisits} Kali Nonton
                            </div>
                            <p className="text-xs text-text-muted">Sedang di Siklus ke-{currentCycleNum}</p>
                        </div>

                        <div className="space-y-1">
                            <span className="text-xs font-bold uppercase text-text-muted">Kunjungan Terakhir</span>
                            <div className="text-sm font-black text-text">
                                {lastValidVisit ? formatDate(lastValidVisit.visited_at || lastValidVisit.created_at) : 'Belum pernah nonton'}
                            </div>
                            <p className="text-xs text-text-muted">
                                {lastValidVisit?.session?.film?.title || '-'}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Tabs Navigation */}
                <div className="flex border-b border-border gap-4 text-xs font-bold">
                    <button
                        type="button"
                        onClick={() => setActiveTab('kunjungan')}
                        className={`pb-3 border-b-2 transition-all ${
                            activeTab === 'kunjungan'
                                ? 'border-amber-500 text-amber-500'
                                : 'border-transparent text-text-muted hover:text-text'
                        }`}
                    >
                        Riwayat Kunjungan ({visits.length})
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('reward')}
                        className={`pb-3 border-b-2 transition-all ${
                            activeTab === 'reward'
                                ? 'border-amber-500 text-amber-500'
                                : 'border-transparent text-text-muted hover:text-text'
                        }`}
                    >
                        Reward & Hadiah ({rewards.length})
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('log')}
                        className={`pb-3 border-b-2 transition-all ${
                            activeTab === 'log'
                                ? 'border-amber-500 text-amber-500'
                                : 'border-transparent text-text-muted hover:text-text'
                        }`}
                    >
                        Log Aktivitas ({events.length})
                    </button>
                </div>

                {/* Tab 1: Kunjungan */}
                {activeTab === 'kunjungan' && (
                    <div className="rounded-2xl border border-border bg-surface overflow-hidden shadow-xs">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr className="border-b border-border bg-surface-2/60 text-text-muted font-bold uppercase tracking-wider">
                                        <th className="p-3">#</th>
                                        <th className="p-3">Waktu Kunjungan</th>
                                        <th className="p-3">Film Ditonton</th>
                                        <th className="p-3">Kode Booking</th>
                                        <th className="p-3">Dicatat Oleh</th>
                                        <th className="p-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {visits.length === 0 ? (
                                        <tr>
                                            <td colSpan="6" className="p-6 text-center text-text-muted">
                                                Belum ada data kunjungan untuk member ini.
                                            </td>
                                        </tr>
                                    ) : (
                                        visits.map((v, idx) => (
                                            <tr key={v.id} className="hover:bg-surface-2/50 transition-colors">
                                                <td className="p-3 font-mono font-bold text-text-muted">
                                                    {visits.length - idx}
                                                </td>
                                                <td className="p-3 font-bold text-text">
                                                    {formatDate(v.visited_at || v.created_at)}
                                                </td>
                                                <td className="p-3">
                                                    <span className="font-bold text-text">
                                                        {v.session?.film?.title || '-'}
                                                    </span>
                                                </td>
                                                <td className="p-3 font-mono text-amber-500 font-bold">
                                                    {v.booking?.code || '-'}
                                                </td>
                                                <td className="p-3 text-text-muted">
                                                    {v.recorded_by_user?.name || 'Sistem Loket'}
                                                </td>
                                                <td className="p-3">
                                                    <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                                                        v.active
                                                            ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20'
                                                            : 'bg-surface-2 text-text-muted border border-border'
                                                    }`}>
                                                        {v.active ? 'Valid' : 'Dibatalkan'}
                                                    </span>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* Tab 2: Reward */}
                {activeTab === 'reward' && (
                    <div className="rounded-2xl border border-border bg-surface overflow-hidden shadow-xs">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr className="border-b border-border bg-surface-2/60 text-text-muted font-bold uppercase tracking-wider">
                                        <th className="p-3">Waktu Diperoleh</th>
                                        <th className="p-3">Deskripsi Reward</th>
                                        <th className="p-3">Status</th>
                                        <th className="p-3">Diselesaikan</th>
                                        <th className="p-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {rewards.length === 0 ? (
                                        <tr>
                                            <td colSpan="5" className="p-6 text-center text-text-muted">
                                                Belum ada reward diperoleh.
                                            </td>
                                        </tr>
                                    ) : (
                                        rewards.map((r) => (
                                            <tr key={r.id} className="hover:bg-surface-2/50 transition-colors">
                                                <td className="p-3 font-bold text-text">
                                                    {formatDate(r.created_at)}
                                                </td>
                                                <td className="p-3">
                                                    <span className="font-bold text-text">
                                                        {r.reward_description || '1 Tiket Nonton Gratis'}
                                                    </span>
                                                </td>
                                                <td className="p-3">
                                                    <span className={`px-2.5 py-1 rounded-full text-[10px] font-bold uppercase ${
                                                        r.status === 'pending'
                                                            ? 'bg-amber-500/15 text-amber-500 border border-amber-500/30'
                                                            : 'bg-emerald-500/15 text-emerald-500 border border-emerald-500/30'
                                                    }`}>
                                                        {r.status}
                                                    </span>
                                                </td>
                                                <td className="p-3 text-text-muted">
                                                    {r.settled_at ? formatDate(r.settled_at) : '-'}
                                                </td>
                                                <td className="p-3 text-right">
                                                    {r.status === 'pending' && (
                                                        <button
                                                            type="button"
                                                            onClick={() => router.post(`/admin/members/${member.id}/rewards/${r.id}/settle`, {}, { preserveScroll: true })}
                                                            className="btn-gold-cinema px-3 py-1.5 rounded-lg text-xs font-bold"
                                                        >
                                                            Klaim Reward
                                                        </button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* Tab 3: Log */}
                {activeTab === 'log' && (
                    <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs space-y-3">
                        {events.length === 0 ? (
                            <p className="text-xs text-text-muted text-center py-6">Belum ada riwayat aktivitas.</p>
                        ) : (
                            <div className="divide-y divide-border">
                                {events.map((e) => (
                                    <div key={e.id} className="py-2.5 flex items-center justify-between text-xs">
                                        <div className="space-y-0.5">
                                            <span className="font-bold text-text">{e.action_description || e.event_type}</span>
                                            <span className="text-[11px] text-text-muted block">{e.user?.name || 'Sistem'}</span>
                                        </div>
                                        <span className="text-text-muted font-mono">{formatDate(e.created_at)}</span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </div>

            {/* Modal Update Status Kartu Fisik */}
            <Modal
                isOpen={cardModalOpen}
                onClose={() => setCardModalOpen(false)}
                title="Status Kartu Fisik Member"
                maxWidth="max-w-md"
            >
                <div className="space-y-4">
                    <p className="text-xs text-text-muted leading-relaxed">
                        Tentukan apakah kartu member fisik berbahan PVC/karton sudah dicetak dan diserahkan kepada <span className="font-bold text-text">{member.name}</span>.
                    </p>

                    <div className="flex flex-col gap-2 pt-2">
                        <button
                            type="button"
                            onClick={() => handleUpdatePhysicalCard(true)}
                            className="flex items-center justify-between p-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-500 font-bold text-xs transition"
                        >
                            <span>Sudah Dibuatkan / Diterima Member</span>
                            <Check className="size-4" />
                        </button>

                        <button
                            type="button"
                            onClick={() => handleUpdatePhysicalCard(false)}
                            className="flex items-center justify-between p-3 rounded-xl border border-border bg-surface hover:bg-surface-2 text-text font-bold text-xs transition"
                        >
                            <span>Belum Dibuatkan (Dalam Antrean)</span>
                            <X className="size-4" />
                        </button>
                    </div>
                </div>
            </Modal>
        </AdminLayout>
    );
}

