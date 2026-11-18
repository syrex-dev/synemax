import { useState } from 'react';
import { Link, Head, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import Modal from '../../../Components/Modal';
import SearchableSelect from '../../../Components/SearchableSelect';
import {
    Calendar,
    Clock,
    Film,
    Plus,
    RefreshCw,
    Edit2,
    Trash2,
    AlertCircle,
    CheckCircle2,
    Lock,
    Unlock,
    Users,
    ChevronDown,
    ChevronUp
} from 'lucide-react';

export default function Index({
    sessions = [],
    sessionsByDate = {},
    films = [],
    filmOptions = [],
    todayDate = '',
    templates = [],
    holidays = [],
    blockedDates = [],
}) {
    // Modal states
    const [addModalOpen, setAddModalOpen] = useState(false);
    const [editModalOpen, setEditModalOpen] = useState(false);
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);

    // Form states
    const [selectedDate, setSelectedDate] = useState(todayDate || Object.keys(sessionsByDate)[0] || '');
    const [selectedSession, setSelectedSession] = useState(null);
    const [startTime, setStartTime] = useState('12:00');
    const [durationMinutes, setDurationMinutes] = useState(120);
    const [filmId, setFilmId] = useState('');
    const [isPrivate, setIsPrivate] = useState(false);
    const [status, setStatus] = useState('tersedia');

    const filmSelectOptions = [
        { value: '', label: 'Belum ditentukan (Biarkan penonton memilih)' },
        ...films.map((f) => ({
            value: String(f.id),
            label: f.title,
        })),
    ];

    const currentSessions = sessionsByDate[selectedDate] || [];

    const openAddModal = () => {
        setStartTime('12:00');
        setDurationMinutes(120);
        setFilmId('');
        setIsPrivate(false);
        setStatus('tersedia');
        setAddModalOpen(true);
    };

    const openEditModal = (s) => {
        setSelectedSession(s);
        setStartTime(s.start_time?.slice(0, 5) || '12:00');
        setDurationMinutes(s.duration_minutes || 120);
        setFilmId(s.film_id ? String(s.film_id) : '');
        setIsPrivate(Boolean(s.is_private));
        setStatus(s.status || 'tersedia');
        setEditModalOpen(true);
    };

    const handleCreateSession = (e) => {
        e.preventDefault();
        router.post(
            '/admin/jadwal',
            {
                session_date: selectedDate,
                start_time: startTime,
                duration_minutes: durationMinutes,
                film_id: filmId || null,
                is_private: isPrivate,
                status: status,
            },
            {
                preserveScroll: true,
                onSuccess: () => setAddModalOpen(false),
            }
        );
    };

    const handleUpdateSession = (e) => {
        e.preventDefault();
        if (!selectedSession) return;
        router.put(
            `/admin/jadwal/${selectedSession.id}`,
            {
                start_time: startTime,
                duration_minutes: durationMinutes,
                film_id: filmId || null,
                is_private: isPrivate,
                status: status,
            },
            {
                preserveScroll: true,
                onSuccess: () => setEditModalOpen(false),
            }
        );
    };

    const handleDeleteSession = () => {
        if (!selectedSession) return;
        router.delete(`/admin/jadwal/${selectedSession.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeleteModalOpen(false),
        });
    };

    const handleGenerate = () => {
        router.post('/admin/jadwal/generate', {}, { preserveScroll: true });
    };

    const toggleSessionStatus = (s) => {
        const nextStatus = s.status === 'tersedia' ? 'ditutup' : 'tersedia';
        router.patch(`/admin/jadwal/${s.id}/toggle-status`, { status: nextStatus }, { preserveScroll: true });
    };

    const formatDateHeading = (dateStr) => {
        if (!dateStr) return '';
        try {
            return new Date(dateStr).toLocaleDateString('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            });
        } catch (e) {
            return dateStr;
        }
    };

    return (
        <AdminLayout title="Kelola Jadwal">
            <Head title="Kelola Jadwal · Panel Admin" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-black text-text tracking-tight">
                            Kelola Jadwal
                        </h1>
                        <p className="mt-1 text-xs sm:text-sm text-text-muted">
                            Atur slot sesi tayang, tentukan judul film, status buka/tutup, dan sinkronkan kuorum.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <button
                            type="button"
                            onClick={handleGenerate}
                            className="inline-flex min-h-11 items-center gap-2 rounded-xl border border-border bg-surface px-4 text-xs font-bold text-text hover:bg-surface-2 transition shadow-xs"
                        >
                            <RefreshCw className="size-4 text-amber-500" />
                            <span>Generate 14 Hari</span>
                        </button>

                        <button
                            type="button"
                            onClick={openAddModal}
                            className="btn-gold-cinema inline-flex min-h-11 items-center gap-2 rounded-xl px-4 text-xs font-bold shadow-sm"
                        >
                            <Plus className="size-4" />
                            <span>Tambah Sesi</span>
                        </button>
                    </div>
                </div>

                {/* 14-Day Date Selector Scrollbar */}
                <div className="overflow-x-auto pb-2 -mx-4 px-4 sm:mx-0 sm:px-0">
                    <div className="flex items-center gap-2 min-w-max">
                        {Object.keys(sessionsByDate).map((dateKey) => {
                            const isSelected = selectedDate === dateKey;
                            const isToday = dateKey === todayDate;
                            const count = sessionsByDate[dateKey]?.length || 0;

                            const d = new Date(dateKey);
                            const dayName = d.toLocaleDateString('id-ID', { weekday: 'short' });
                            const dayNum = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });

                            return (
                                <button
                                    key={dateKey}
                                    type="button"
                                    onClick={() => setSelectedDate(dateKey)}
                                    className={`flex flex-col items-center justify-center p-3 rounded-2xl min-w-[90px] border transition-all ${
                                        isSelected
                                            ? 'bg-amber-500 text-slate-950 border-amber-400 font-black shadow-md'
                                            : 'bg-surface border-border text-text hover:border-amber-500/50 hover:bg-surface-2'
                                    }`}
                                >
                                    <span className="text-[10px] uppercase font-bold tracking-wider">
                                        {isToday ? 'Hari Ini' : dayName}
                                    </span>
                                    <span className="text-sm font-black mt-0.5">{dayNum}</span>
                                    <span className={`text-[10px] mt-1 px-1.5 py-0.2 rounded-md ${
                                        isSelected ? 'bg-black/20 text-slate-950 font-bold' : 'text-text-muted'
                                    }`}>
                                        {count} sesi
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                </div>

                {/* Date Summary Title */}
                <div className="flex items-center justify-between p-4 rounded-2xl bg-surface border border-border shadow-xs">
                    <div className="flex items-center gap-2.5">
                        <Calendar className="size-5 text-amber-500" />
                        <div>
                            <h2 className="text-sm font-black text-text">
                                {formatDateHeading(selectedDate)}
                            </h2>
                            <span className="text-xs text-text-muted">
                                {currentSessions.length} sesi terdaftar
                            </span>
                        </div>
                    </div>

                    <button
                        type="button"
                        onClick={openAddModal}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-500/10 text-amber-500 hover:bg-amber-500/20 text-xs font-bold transition"
                    >
                        <Plus className="size-3.5" />
                        <span>Tambah di Tanggal Ini</span>
                    </button>
                </div>

                {/* Sessions Table / Cards */}
                {currentSessions.length === 0 ? (
                    <div className="rounded-2xl border border-dashed border-border bg-surface-2/60 p-12 text-center">
                        <Clock className="size-8 mx-auto text-text-muted mb-2" />
                        <h3 className="text-sm font-bold text-text">Belum ada sesi di tanggal ini</h3>
                        <p className="text-xs text-text-muted mt-1 max-w-sm mx-auto">
                            Klik tambah sesi untuk memasukkan jam tayang baru atau gunakan tombol generate jadwal.
                        </p>
                    </div>
                ) : (
                    <div className="grid gap-3">
                        {currentSessions.map((session) => {
                            const occupiedSeats = session.occupied_seats || 0;
                            const maxCapacity = session.capacity || 6;
                            const isClosed = session.status === 'ditutup';

                            return (
                                <div
                                    key={session.id}
                                    className={`rounded-2xl border bg-surface p-4 sm:p-5 transition-all flex flex-col md:flex-row md:items-center justify-between gap-4 ${
                                        isClosed ? 'opacity-60 border-border bg-surface-2/40' : 'border-border shadow-xs hover:border-amber-500/40'
                                    }`}
                                >
                                    <div className="flex items-start gap-4">
                                        <div className="font-mono text-center shrink-0 w-24 py-2 px-3 rounded-xl bg-surface-2 border border-border">
                                            <div className="text-sm font-black text-text">
                                                {session.start_time?.slice(0, 5)}
                                            </div>
                                            <div className="text-[11px] text-text-muted">
                                                {session.end_time?.slice(0, 5)} WIB
                                            </div>
                                        </div>

                                        <div className="space-y-1.5">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h3 className="text-base font-black text-text">
                                                    {session.film?.title || 'Belum Ditentukan (Penonton Memilih)'}
                                                </h3>
                                                {session.is_private && (
                                                    <span className="rounded-md bg-amber-500/10 border border-amber-500/30 px-2 py-0.5 text-[10px] font-bold text-amber-500">
                                                        Privat
                                                    </span>
                                                )}
                                                <span className={`rounded-md px-2 py-0.5 text-[10px] font-bold capitalize ${
                                                    session.status === 'tersedia'
                                                        ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20'
                                                        : 'bg-rose-500/10 text-rose-500 border border-rose-500/20'
                                                }`}>
                                                    {session.status}
                                                </span>
                                            </div>

                                            <div className="flex flex-wrap items-center gap-3 text-xs text-text-muted">
                                                <span className="flex items-center gap-1 font-mono font-bold text-text">
                                                    <Users className="size-3.5 text-amber-500" />
                                                    {occupiedSeats}/{maxCapacity} Kursi Terisi
                                                </span>
                                                <span>·</span>
                                                <span>Durasi: {session.duration_minutes || 120} mnt</span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Action Buttons */}
                                    <div className="flex flex-wrap items-center gap-2 w-full md:w-auto justify-end md:justify-start pt-3 md:pt-0 border-t md:border-t-0 border-border/50 shrink-0">
                                        <button
                                            type="button"
                                            onClick={() => toggleSessionStatus(session)}
                                            title={isClosed ? 'Buka sesi' : 'Tutup sesi'}
                                            className={`p-2 rounded-xl border text-xs font-bold transition ${
                                                isClosed
                                                    ? 'border-emerald-500/40 text-emerald-500 hover:bg-emerald-500/10'
                                                    : 'border-border text-text-muted hover:text-text hover:bg-surface-2'
                                            }`}
                                        >
                                            {isClosed ? <Unlock className="size-4" /> : <Lock className="size-4" />}
                                        </button>

                                        <button
                                            type="button"
                                            onClick={() => openEditModal(session)}
                                            className="btn-secondary-cinema inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold"
                                        >
                                            <Edit2 className="size-3.5" />
                                            <span>Edit Sesi & Film</span>
                                        </button>

                                        <button
                                            type="button"
                                            onClick={() => {
                                                setSelectedSession(session);
                                                setDeleteModalOpen(true);
                                            }}
                                            className="p-2 rounded-xl border border-border text-text-muted hover:text-rose-500 hover:bg-surface-2 transition"
                                            title="Hapus Sesi"
                                        >
                                            <Trash2 className="size-4" />
                                        </button>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>

            {/* Modal Tambah Sesi */}
            <Modal
                isOpen={addModalOpen}
                onClose={() => setAddModalOpen(false)}
                title="Tambah Sesi Baru"
                maxWidth="max-w-lg"
            >
                <form onSubmit={handleCreateSession} className="space-y-4">
                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1">
                            <label className="text-xs font-bold text-text">Jam Mulai (WIB)</label>
                            <input
                                type="time"
                                required
                                value={startTime}
                                onChange={(e) => setStartTime(e.target.value)}
                                className="input-cinema w-full py-2 px-3 text-xs rounded-xl"
                            />
                        </div>
                        <div className="space-y-1">
                            <label className="text-xs font-bold text-text">Durasi (Menit)</label>
                            <input
                                type="number"
                                min="30"
                                max="300"
                                value={durationMinutes}
                                onChange={(e) => setDurationMinutes(Number(e.target.value))}
                                className="input-cinema w-full py-2 px-3 text-xs rounded-xl"
                            />
                        </div>
                    </div>

                    {/* Styled Searchable Film Dropdown (Resolves User Request 3!) */}
                    <div className="space-y-1">
                        <label className="text-xs font-bold text-text">Pilihan Film Tayang</label>
                        <SearchableSelect
                            id="add_film_id"
                            options={filmSelectOptions}
                            value={filmId}
                            onChange={(val) => setFilmId(val)}
                            placeholder="Cari judul film..."
                        />
                        <p className="text-[11px] text-text-muted">
                            Ketik judul untuk mencari film seketika tanpa perlu menekan tombol enter.
                        </p>
                    </div>

                    <div className="flex items-center gap-4 pt-2">
                        <label className="flex items-center gap-2 cursor-pointer text-xs font-bold text-text">
                            <input
                                type="checkbox"
                                checked={isPrivate}
                                onChange={(e) => setIsPrivate(e.target.checked)}
                                className="rounded border-border text-amber-500 focus:ring-amber-500"
                            />
                            <span>Sewa Privat Penuh</span>
                        </label>
                    </div>

                    <div className="flex items-center justify-end gap-2 pt-4 border-t border-border">
                        <button
                            type="button"
                            onClick={() => setAddModalOpen(false)}
                            className="btn-secondary-cinema px-4 py-2 text-xs font-bold rounded-xl"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            className="btn-gold-cinema px-5 py-2 text-xs font-bold rounded-xl shadow-md"
                        >
                            Simpan Sesi
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Modal Edit Sesi & Film */}
            <Modal
                isOpen={editModalOpen}
                onClose={() => setEditModalOpen(false)}
                title="Edit Sesi & Judul Film"
                maxWidth="max-w-lg"
            >
                <form onSubmit={handleUpdateSession} className="space-y-4">
                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1">
                            <label className="text-xs font-bold text-text">Jam Mulai (WIB)</label>
                            <input
                                type="time"
                                required
                                value={startTime}
                                onChange={(e) => setStartTime(e.target.value)}
                                className="input-cinema w-full py-2 px-3 text-xs rounded-xl"
                            />
                        </div>
                        <div className="space-y-1">
                            <label className="text-xs font-bold text-text">Durasi (Menit)</label>
                            <input
                                type="number"
                                min="30"
                                max="300"
                                value={durationMinutes}
                                onChange={(e) => setDurationMinutes(Number(e.target.value))}
                                className="input-cinema w-full py-2 px-3 text-xs rounded-xl"
                            />
                        </div>
                    </div>

                    {/* Styled Searchable Film Dropdown (Resolves User Request 3!) */}
                    <div className="space-y-1">
                        <label className="text-xs font-bold text-text">Pilihan Film Tayang</label>
                        <SearchableSelect
                            id="edit_film_id"
                            options={filmSelectOptions}
                            value={filmId}
                            onChange={(val) => setFilmId(val)}
                            placeholder="Cari judul film..."
                        />
                        <p className="text-[11px] text-text-muted">
                            Ketik judul untuk mencari film seketika tanpa perlu menekan tombol enter.
                        </p>
                    </div>

                    <div className="flex items-center gap-4 pt-2">
                        <label className="flex items-center gap-2 cursor-pointer text-xs font-bold text-text">
                            <input
                                type="checkbox"
                                checked={isPrivate}
                                onChange={(e) => setIsPrivate(e.target.checked)}
                                className="rounded border-border text-amber-500 focus:ring-amber-500"
                            />
                            <span>Sewa Privat Penuh</span>
                        </label>
                    </div>

                    <div className="flex items-center justify-end gap-2 pt-4 border-t border-border">
                        <button
                            type="button"
                            onClick={() => setEditModalOpen(false)}
                            className="btn-secondary-cinema px-4 py-2 text-xs font-bold rounded-xl"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            className="btn-gold-cinema px-5 py-2 text-xs font-bold rounded-xl shadow-md"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Modal Hapus Sesi */}
            <Modal
                isOpen={deleteModalOpen}
                onClose={() => setDeleteModalOpen(false)}
                title="Hapus Sesi Penayangan"
                maxWidth="max-w-md"
            >
                <div className="space-y-4">
                    <p className="text-xs text-text-muted leading-relaxed">
                        Yakin ingin menghapus sesi jam <span className="font-bold text-text">{selectedSession?.start_time?.slice(0, 5)} WIB</span> pada tanggal {selectedDate}? Sesi yang sudah memiliki booking tidak dapat dihapus sembarangan.
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
                            onClick={handleDeleteSession}
                            className="px-4 py-2 text-xs font-bold rounded-xl bg-rose-600 hover:bg-rose-500 text-white transition shadow-sm"
                        >
                            Hapus Sesi
                        </button>
                    </div>
                </div>
            </Modal>
        </AdminLayout>
    );
}

