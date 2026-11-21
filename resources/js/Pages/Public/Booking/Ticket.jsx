import { useState, useEffect } from 'react';
import { Link, Head, router, useForm } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import Modal from '../../../Components/Modal';
import {
    CheckCircle2,
    Clock,
    Printer,
    Share2,
    Film,
    Calendar,
    Users,
    AlertCircle,
    ChevronDown,
    ChevronUp,
    MessageCircle,
    Sparkles,
    X,
    Check,
    Coffee,
    ArrowRight,
    Copy,
} from 'lucide-react';

export default function Ticket({
    booking,
    session,
    sessionStatus,
    candidateTallies = [],
    allFilms = [],
    alternativeSessions = [],
    couplePrice = 0,
    privatePrice = 0,
    coupleUpgradeDiff = 0,
    privateUpgradeDiff = 0,
    canUpgradeCouple = false,
    canUpgradePrivate = false,
    ajakTemanUrl = null,
    whatsAppUrl = null,
    snackWhatsAppUrl = null,
    snackLocked = false,
    canCancel = false,
    cancellationCutoffHours = 3,
}) {
    const [snacksOpen, setSnacksOpen] = useState(false);
    const [cancelModal, setCancelModal] = useState(false);
    const [changeFilmModal, setChangeFilmModal] = useState(false);
    const [rescheduleModal, setRescheduleModal] = useState(false);
    const [upgradeModal, setUpgradeModal] = useState(false);
    const [copiedCode, setCopiedCode] = useState(false);

    const flowState = booking?.flow_state || booking?.status;
    const isCancelled = flowState === 'dibatalkan' || booking?.status === 'dibatalkan';
    const isExpired = flowState === 'kedaluwarsa' || booking?.status === 'kedaluwarsa';
    const isCompleted = ['selesai', 'terlewat', 'hadir', 'tidak_datang', 'tidak_hadir'].includes(flowState) || ['selesai', 'hadir'].includes(booking?.status);
    const isWaitingConfirmation = booking?.status === 'menunggu_konfirmasi';
    const isWaitingPenonton = flowState === 'menunggu_penonton' || (!session?.is_confirmed && booking?.status === 'terkonfirmasi' && session?.status !== 'terkonfirmasi');
    const isAktif = (flowState === 'aktif' || booking?.status === 'terkonfirmasi') && !isWaitingPenonton && !isCancelled && !isExpired && !isCompleted;

    const canPrint = isAktif || isCompleted;

    // Polling when waiting confirmation
    useEffect(() => {
        if (!isWaitingConfirmation) return;

        const interval = setInterval(() => {
            router.reload({
                only: ['booking', 'session', 'sessionStatus'],
            });
        }, 6000);

        return () => clearInterval(interval);
    }, [isWaitingConfirmation]);

    const handleCopyCode = () => {
        if (navigator.clipboard?.writeText) {
            navigator.clipboard.writeText(booking.code).then(() => {
                setCopiedCode(true);
                setTimeout(() => setCopiedCode(false), 2000);
            });
        }
    };

    const handlePrint = () => {
        window.print();
    };

    // Cancellation Form
    const cancelForm = useForm({
        whatsapp: booking?.whatsapp || '',
    });

    const handleCancelSubmit = (e) => {
        e.preventDefault();
        cancelForm.post(`/booking/${booking.code}/batal`, {
            preserveScroll: true,
            onSuccess: () => setCancelModal(false),
        });
    };

    // Change Film Form
    const changeFilmForm = useForm({
        film_id: '',
    });

    const handleChangeFilmSubmit = (e) => {
        e.preventDefault();
        if (!changeFilmForm.data.film_id) return;
        changeFilmForm.post(`/booking/${booking.code}/ganti-film`, {
            preserveScroll: true,
            onSuccess: () => setChangeFilmModal(false),
        });
    };

    // Reschedule Form
    const rescheduleForm = useForm({
        new_session_id: '',
    });

    const handleRescheduleSubmit = (e) => {
        e.preventDefault();
        if (!rescheduleForm.data.new_session_id) return;
        rescheduleForm.post(`/booking/${booking.code}/reschedule`, {
            preserveScroll: true,
            onSuccess: () => setRescheduleModal(false),
        });
    };

    // Upgrade Form
    const upgradeForm = useForm({
        target_type: 'private',
    });

    const handleUpgradeSubmit = (type) => {
        upgradeForm.setData('target_type', type);
        upgradeForm.post(`/booking/${booking.code}/upgrade`, {
            preserveScroll: true,
            onSuccess: () => setUpgradeModal(false),
        });
    };

    const bookingSession = booking?.session || session;
    const myFilm = booking?.film || bookingSession?.film || session?.film;
    const isPrivate = booking?.booking_type === 'private' || Boolean(booking?.is_private);
    const isCouple = booking?.booking_type === 'couple';
    const snackItems = booking?.items?.filter((it) => it.status !== 'dibatalkan') || [];

    const resolveTicketPoster = (film) => {
        if (!film) return null;
        if (film.poster_url) return film.poster_url;
        if (film.poster_path) {
            return /^https?:\/\//i.test(film.poster_path)
                ? film.poster_path
                : `/storage/${film.poster_path.replace(/^\/+/, '')}`;
        }
        return null;
    };

    const candidateTalliesList = Array.isArray(candidateTallies)
        ? candidateTallies
        : Object.values(candidateTallies || {});
    const myFilmTally = candidateTalliesList.find((t) => Number(t.film_id) === Number(booking?.film_id))?.seats || (booking?.people_count || booking?.seats_reserved || 1);
    const quorum = session?.quorum || 3;
    const shortage = Math.max(0, quorum - myFilmTally);

    const formatDateId = (dateStr) => {
        if (!dateStr) return '';
        try {
            const d = new Date(dateStr);
            return d.toLocaleDateString('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            });
        } catch {
            return dateStr;
        }
    };

    const formatTimeId = (timeStr) => {
        if (!timeStr) return '';
        return `${timeStr.slice(0, 5)} WIB`;
    };

    return (
        <PublicLayout title={`Tiket ${booking.code}`}>
            <Head title={`Tiket ${booking.code}`}>
                <meta name="description" content={`Tiket digital resmi SynemaX untuk booking ${booking.code}.`} />
            </Head>

            <div className="mx-auto max-w-[520px] w-full px-4 py-5 sm:py-8 print-ticket-container">
                {/* Stepper 3 Tahap Alur Booking Online */}
                <nav aria-label="Tahapan Booking" className="mb-5 no-print">
                    <ol className="grid grid-cols-3 gap-2 text-center text-xs">
                        <li className="flex items-center justify-center gap-1.5 rounded-xl border border-emerald-600/30 bg-emerald-500/10 p-2 font-medium">
                            <span className="grid size-5 place-items-center rounded-full bg-emerald-700 text-white" aria-hidden="true">
                                <CheckCircle2 className="size-3.5" />
                            </span>
                            <span className="font-bold text-slate-800 dark:text-slate-100">Booking</span>
                        </li>

                        <li className="flex items-center justify-center gap-1.5 rounded-xl border border-emerald-600/30 bg-emerald-500/10 p-2 font-medium">
                            <span className="grid size-5 place-items-center rounded-full bg-emerald-700 text-white" aria-hidden="true">
                                <CheckCircle2 className="size-3.5" />
                            </span>
                            <span className="font-bold text-slate-800 dark:text-slate-100">Bayar</span>
                        </li>

                        <li className="flex items-center justify-center gap-1.5 rounded-xl border border-amber-500/50 bg-amber-500/10 p-2 font-bold">
                            <span className="grid size-5 place-items-center rounded-full bg-amber-400 text-slate-950 text-[11px] font-black" aria-hidden="true">
                                3
                            </span>
                            <span className="text-amber-900 dark:text-amber-200">Tiket</span>
                        </li>
                    </ol>
                </nav>

                {/* ==================== STATUS BANNERS ==================== */}
                {/* Dibatalkan */}
                {isCancelled && (
                    <div className="mb-5 rounded-2xl border border-danger/40 bg-danger-surface p-5 text-center text-danger-text no-print">
                        <h2 className="text-base font-black">Booking Telah Dibatalkan</h2>
                        <p className="mt-1 text-xs text-danger-text/90">
                            Pemesanan ini sudah tidak berlaku. Kursi telah dilepaskan kembali ke publik.
                        </p>
                        <div className="mt-4">
                            <Link
                                href="/jadwal"
                                className="btn-gold-cinema inline-flex min-h-10 items-center justify-center rounded-xl px-4 text-xs font-bold"
                            >
                                Pesan Jadwal Baru
                            </Link>
                        </div>
                    </div>
                )}

                {/* Kedaluwarsa */}
                {isExpired && (
                    <div className="mb-5 rounded-2xl border border-danger/40 bg-danger-surface p-5 text-center text-danger-text no-print">
                        <h2 className="text-base font-black">Waktu Pembayaran Habis</h2>
                        <p className="mt-1 text-xs text-danger-text/90">
                            Batas waktu transfer telah berakhir sehingga pesanan otomatis kedaluwarsa.
                        </p>
                        <div className="mt-4">
                            <Link
                                href="/jadwal"
                                className="btn-gold-cinema inline-flex min-h-10 items-center justify-center rounded-xl px-4 text-xs font-bold"
                            >
                                Buat Booking Baru
                            </Link>
                        </div>
                    </div>
                )}

                {/* Menunggu Konfirmasi */}
                {isWaitingConfirmation && (
                    <div className="mb-5 rounded-2xl border border-amber-500/40 bg-amber-500/10 p-5 text-center text-amber-900 dark:text-amber-200 no-print animate-pulse">
                        <div className="mx-auto flex size-10 items-center justify-center rounded-full bg-amber-500/20 text-amber-600 dark:text-amber-400 mb-2">
                            <Clock className="size-5" />
                        </div>
                        <h2 className="text-sm font-black">Bukti Pembayaran Sedang Diverifikasi</h2>
                        <p className="mt-1 text-xs text-text-muted leading-relaxed">
                            Admin kami sedang memeriksa pembayaranmu. Halaman ini akan otomatis diperbarui begitu tiket aktif.
                        </p>
                    </div>
                )}

                {/* Menunggu Penonton / Quorum */}
                {isWaitingPenonton && !isCancelled && !isExpired && (
                    <div className="mb-5 rounded-2xl border border-amber-500/40 bg-amber-500/10 p-5 text-amber-900 dark:text-amber-200 no-print space-y-3">
                        <div className="flex items-center gap-2">
                            <Users className="size-5 text-amber-500" />
                            <h2 className="text-sm font-black">Menunggu Minimal Penonton</h2>
                        </div>
                        <p className="text-xs text-text-muted leading-relaxed">
                            Film pilihanmu saat ini didukung oleh <strong className="text-text">{myFilmTally} penonton</strong>. Sesi butuh minimal{' '}
                            <strong className="text-text">{quorum} penonton</strong> agar tayang.
                        </p>

                        <div className="w-full bg-slate-200 dark:bg-white/10 rounded-full h-2 overflow-hidden">
                            <div
                                className="bg-amber-500 h-full rounded-full transition-all duration-500"
                                style={{ width: `${Math.min(100, (myFilmTally / quorum) * 100)}%` }}
                            />
                        </div>

                        <div className="flex flex-wrap gap-2 pt-2">
                            {ajakTemanUrl && (
                                <a
                                    href={ajakTemanUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="btn-gold-cinema inline-flex min-h-9 items-center gap-1.5 rounded-xl px-3.5 text-xs font-bold shadow-xs"
                                >
                                    <Share2 className="size-3.5" />
                                    <span>Ajak Teman Nonton ({shortage} lagi)</span>
                                </a>
                            )}
                            <button
                                type="button"
                                onClick={() => setChangeFilmModal(true)}
                                className="inline-flex min-h-9 items-center gap-1 rounded-xl border border-border bg-surface px-3 text-xs font-bold text-text hover:bg-surface-2 transition"
                            >
                                <span>Ganti Film</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => setRescheduleModal(true)}
                                className="inline-flex min-h-9 items-center gap-1 rounded-xl border border-border bg-surface px-3 text-xs font-bold text-text hover:bg-surface-2 transition"
                            >
                                <span>Pindah Jadwal</span>
                            </button>
                        </div>
                    </div>
                )}

                {/* Terkonfirmasi / Tiket Aktif */}
                {isAktif && (
                    <div className="mb-5 rounded-2xl border border-emerald-500/40 bg-emerald-500/10 p-4 flex items-center justify-between no-print">
                        <div className="flex items-center gap-2.5">
                            <span className="flex size-8 items-center justify-center rounded-full bg-emerald-600 text-white">
                                <Check className="size-4" />
                            </span>
                            <div>
                                <h2 className="text-xs font-black text-emerald-700 dark:text-emerald-300">Tiket Aktif</h2>
                                <p className="text-[11px] text-emerald-600 dark:text-emerald-400">Siap ditunjukkan ke petugas di studio.</p>
                            </div>
                        </div>
                        {canPrint && (
                            <button
                                type="button"
                                onClick={handlePrint}
                                className="inline-flex min-h-9 items-center gap-1.5 rounded-xl border border-emerald-500/40 bg-emerald-500/20 px-3 text-xs font-bold text-emerald-800 dark:text-emerald-200 hover:bg-emerald-500/30 transition"
                            >
                                <Printer className="size-3.5" />
                                <span>Cetak</span>
                            </button>
                        )}
                    </div>
                )}

                {/* Selesai */}
                {isCompleted && (
                    <div className="mb-5 rounded-2xl border border-border bg-surface-2 p-4 text-center text-xs text-text-muted no-print">
                        <span>Sesi nonton untuk tiket ini telah selesai. Terima kasih telah menonton di SynemaX!</span>
                    </div>
                )}

                {/* ==================== THE DIGITAL TICKET CARD ==================== */}
                <div
                    id="printable-ticket"
                    className="relative overflow-hidden rounded-3xl border border-border bg-surface dark:bg-[#0E131F] shadow-2xl transition-all print-ticket"
                >
                    {/* Ticket Header */}
                    <div className="relative border-b border-border/80 p-5 bg-gradient-to-br from-surface via-surface-2 to-amber-500/5">
                        <div className="flex items-center justify-between">
                            <div>
                                <span className="font-black text-lg text-amber-500 tracking-wider uppercase font-mono">
                                    SynemaX Premiere
                                </span>
                                <p className="text-[10px] text-text-muted uppercase tracking-widest font-bold">
                                    Studio AC & Audio Sinematik
                                </p>
                            </div>
                            <span
                                className={`rounded-xl px-3 py-1 text-[11px] font-black uppercase tracking-wider ${
                                    isAktif
                                        ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/40'
                                        : isWaitingConfirmation
                                        ? 'bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/40'
                                        : isCancelled || isExpired
                                        ? 'bg-danger/20 text-danger-text border border-danger/40'
                                        : 'bg-slate-500/20 text-text-muted border border-slate-500/40'
                                }`}
                            >
                                {isAktif ? 'Aktif' : isWaitingConfirmation ? 'Verifikasi' : booking.status}
                            </span>
                        </div>
                    </div>

                    {/* Tear Notches (Visual Punch Holes) */}
                    <div className="relative flex items-center justify-between no-print">
                        <div className="size-6 -ml-3 rounded-full bg-bg border-r border-border shrink-0 tear-notch" />
                        <div className="flex-1 border-b-2 border-dashed border-border/80 mx-2" />
                        <div className="size-6 -mr-3 rounded-full bg-bg border-l border-border shrink-0 tear-notch" />
                    </div>

                    {/* Ticket Body */}
                    <div className="p-6 space-y-5">
                        {/* Film Details */}
                        <div className="flex gap-4 items-start">
                            {resolveTicketPoster(myFilm) ? (
                                <img
                                    src={resolveTicketPoster(myFilm)}
                                    alt={myFilm?.title || 'Poster Film'}
                                    className="w-20 rounded-xl object-cover aspect-[2/3] shadow-md border border-border shrink-0"
                                />
                            ) : (
                                <div className="w-20 rounded-xl aspect-[2/3] bg-surface-2 border border-border flex items-center justify-center shrink-0 text-text-muted">
                                    <Film className="size-8 text-amber-500/70" />
                                </div>
                            )}
                            <div className="min-w-0 flex-1">
                                <span className="inline-block rounded-md bg-amber-500/15 px-2 py-0.5 text-[10px] font-black uppercase text-amber-600 dark:text-amber-400 border border-amber-400/30 mb-1">
                                    {isPrivate ? 'Sewa Privat' : isCouple ? 'Paket Couple' : 'Tiket Reguler'}
                                </span>
                                <h1 className="text-xl font-black text-text leading-tight truncate">
                                    {myFilm?.title || 'Film Sesi'}
                                </h1>
                                <p className="text-xs text-text-muted mt-1">
                                    {myFilm?.genre || 'Umum'} · {myFilm?.duration_minutes ? `${myFilm.duration_minutes} Menit` : ''} · {myFilm?.age_rating || 'SU'}
                                </p>
                            </div>
                        </div>

                        {/* Schedule & Seat Info */}
                        <div className="grid grid-cols-2 gap-3 rounded-2xl border border-border/70 bg-surface-2 p-4 text-xs">
                            <div>
                                <span className="text-[10px] uppercase font-bold text-text-muted">Tanggal</span>
                                <p className="font-bold text-text mt-0.5">{formatDateId(bookingSession?.session_date)}</p>
                            </div>
                            <div>
                                <span className="text-[10px] uppercase font-bold text-text-muted">Jam Nonton</span>
                                <p className="font-mono font-black text-amber-600 dark:text-amber-400 text-sm mt-0.5">
                                    {formatTimeId(bookingSession?.start_time)}
                                </p>
                            </div>
                            <div className="pt-2 border-t border-border/50">
                                <span className="text-[10px] uppercase font-bold text-text-muted">Jumlah Penonton</span>
                                <p className="font-bold text-text mt-0.5">
                                    {isPrivate ? `${booking.seats_reserved || 6} Orang (Privat)` : `${booking.pax || booking.seats_reserved || 1} Kursi`}
                                </p>
                            </div>
                            <div className="pt-2 border-t border-border/50">
                                <span className="text-[10px] uppercase font-bold text-text-muted">Atas Nama</span>
                                <p className="font-bold text-text truncate mt-0.5">{booking.customer_name}</p>
                            </div>
                        </div>

                        {/* QR Code Barcode Area */}
                        <div className="flex flex-col items-center justify-center rounded-2xl border border-border/80 bg-white p-5 text-center text-black">
                            <img
                                src={`/qr-code?code=${encodeURIComponent(booking.code)}`}
                                alt={`QR Tiket ${booking.code}`}
                                width="180"
                                height="180"
                                className="size-44 object-contain"
                            />
                            <div className="mt-3 flex items-center gap-2">
                                <span className="font-mono text-xl font-black tracking-widest text-black">
                                    {booking.code}
                                </span>
                                <button
                                    type="button"
                                    onClick={handleCopyCode}
                                    className="no-print rounded-lg bg-neutral-200 p-1.5 text-neutral-800 hover:bg-neutral-300 transition"
                                    title="Salin kode booking"
                                >
                                    {copiedCode ? <Check className="size-3.5 text-emerald-600" /> : <Copy className="size-3.5" />}
                                </button>
                            </div>
                            <p className="text-[10px] text-neutral-500 mt-1">
                                Pindai barcode ini pada pintu masuk studio SynemaX
                            </p>
                        </div>

                        {/* Snack Items accordion (if any) */}
                        {snackItems.length > 0 && (
                            <div className="border-t border-border/70 pt-4">
                                <button
                                    type="button"
                                    onClick={() => setSnacksOpen(!snacksOpen)}
                                    className="flex w-full items-center justify-between text-xs font-bold text-text hover:text-amber-500 transition no-print"
                                >
                                    <span className="flex items-center gap-1.5">
                                        <Coffee className="size-4 text-amber-500" />
                                        <span>Pesanan Jajan ({snackItems.length} menu)</span>
                                    </span>
                                    {snacksOpen ? <ChevronUp className="size-4" /> : <ChevronDown className="size-4" />}
                                </button>

                                {(snacksOpen || true) && (
                                    <div className="mt-3 space-y-2 text-xs">
                                        {snackItems.map((snack) => (
                                            <div key={snack.id} className="flex justify-between items-center text-text-muted">
                                                <span>
                                                    {snack.name_snapshot} × {snack.qty}
                                                    {snack.note ? ` (${snack.note})` : ''}
                                                </span>
                                                <span className="font-mono font-semibold text-text">
                                                    Rp {Number(snack.subtotal || 0).toLocaleString('id-ID')}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Important Notes */}
                        <div className="rounded-xl border border-border/60 bg-surface-2 p-3 text-[11px] text-text-muted leading-relaxed">
                            <p className="font-bold text-text">Petunjuk Kehadiran:</p>
                            <p className="mt-0.5">
                                Harap hadir di studio minimal 10-15 menit sebelum jam tayang. Tunjukkan kode tiket atau QR di atas ke kasir/petugas studio.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Additional Action Buttons */}
                <div className="mt-6 flex flex-wrap gap-2.5 justify-center no-print">
                    {whatsAppUrl && (
                        <a
                            href={whatsAppUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex min-h-10 items-center gap-1.5 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/20 transition"
                        >
                            <MessageCircle className="size-4" />
                            <span>Bantuan WhatsApp</span>
                        </a>
                    )}

                    {canCancel && (
                        <button
                            type="button"
                            onClick={() => setCancelModal(true)}
                            className="inline-flex min-h-10 items-center justify-center rounded-xl border border-danger/40 bg-danger/10 px-4 text-xs font-bold text-danger hover:bg-danger/20 transition"
                        >
                            Batalkan Booking
                        </button>
                    )}
                </div>
            </div>

            {/* Modal Batalkan Booking */}
            <Modal show={cancelModal} onClose={() => setCancelModal(false)} maxWidth="sm">
                <div className="p-6">
                    <h3 className="text-base font-black text-text dark:text-white">Batalkan booking ini?</h3>
                    <p className="mt-2 text-xs leading-relaxed text-text-muted">
                        Apakah Anda yakin ingin membatalkan booking {booking.code}? Kursi pesanan Anda akan dilepaskan kembali.
                    </p>
                    <form onSubmit={handleCancelSubmit} className="mt-5 flex justify-end gap-2.5">
                        <button
                            type="button"
                            onClick={() => setCancelModal(false)}
                            className="inline-flex min-h-10 items-center rounded-xl border border-border bg-surface-2 px-3.5 text-xs font-bold text-text hover:bg-surface transition"
                        >
                            Kembali
                        </button>
                        <button
                            type="submit"
                            disabled={cancelForm.processing}
                            className="inline-flex min-h-10 items-center rounded-xl bg-danger px-4 text-xs font-bold text-white hover:opacity-90 transition disabled:opacity-50"
                        >
                            Ya, Batalkan
                        </button>
                    </form>
                </div>
            </Modal>

            {/* Modal Ganti Film */}
            <Modal show={changeFilmModal} onClose={() => setChangeFilmModal(false)} maxWidth="md">
                <div className="p-6">
                    <h3 className="text-base font-black text-text dark:text-white">Pilih Film Lain</h3>
                    <p className="mt-1 text-xs text-text-muted">
                        Pilih film yang sudah memiliki penonton terbanyak agar sesi cepat terkonfirmasi.
                    </p>
                    <form onSubmit={handleChangeFilmSubmit} className="mt-4 space-y-3">
                        <div className="max-h-72 overflow-y-auto space-y-2 pr-1">
                            {allFilms.map((f) => {
                                const tally = candidateTalliesList.find((t) => Number(t.film_id) === Number(f.id));
                                const seats = tally?.seats || 0;
                                const isCurrent = Number(f.id) === Number(booking?.film_id);

                                return (
                                    <label
                                        key={f.id}
                                        className={`flex items-center justify-between p-3 rounded-xl border cursor-pointer transition ${
                                            Number(changeFilmForm.data.film_id) === Number(f.id)
                                                ? 'border-amber-500 bg-amber-500/10'
                                                : 'border-border bg-surface hover:bg-surface-2'
                                        }`}
                                    >
                                        <div className="flex items-center gap-3 min-w-0">
                                            <input
                                                type="radio"
                                                name="film_id"
                                                value={f.id}
                                                checked={Number(changeFilmForm.data.film_id) === Number(f.id)}
                                                onChange={(e) => changeFilmForm.setData('film_id', e.target.value)}
                                                className="text-amber-500 focus:ring-amber-500"
                                            />
                                            <div className="truncate">
                                                <p className="text-xs font-bold text-text truncate">{f.title}</p>
                                                <p className="text-[11px] text-text-muted">{f.genre} · {f.duration_minutes}m</p>
                                            </div>
                                        </div>
                                        <span className="shrink-0 rounded-lg bg-surface-2 px-2 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-400">
                                            {isCurrent ? 'Film Saat Ini' : `${seats} pemilih`}
                                        </span>
                                    </label>
                                );
                            })}
                        </div>
                        <div className="flex justify-end gap-2.5 pt-3 border-t border-border">
                            <button
                                type="button"
                                onClick={() => setChangeFilmModal(false)}
                                className="inline-flex min-h-10 items-center rounded-xl border border-border bg-surface-2 px-3.5 text-xs font-bold text-text"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                disabled={!changeFilmForm.data.film_id || changeFilmForm.processing}
                                className="btn-gold-cinema inline-flex min-h-10 items-center rounded-xl px-4 text-xs font-bold disabled:opacity-40"
                            >
                                Simpan Perubahan Film
                            </button>
                        </div>
                    </form>
                </div>
            </Modal>

            {/* Modal Pindah Jadwal */}
            <Modal show={rescheduleModal} onClose={() => setRescheduleModal(false)} maxWidth="md">
                <div className="p-6">
                    <h3 className="text-base font-black text-text dark:text-white">Pindah Jam / Jadwal Nonton</h3>
                    <p className="mt-1 text-xs text-text-muted">
                        Pilih jadwal sesi lain yang masih memiliki ketersediaan kursi.
                    </p>
                    <form onSubmit={handleRescheduleSubmit} className="mt-4 space-y-3">
                        <div className="max-h-72 overflow-y-auto space-y-2 pr-1">
                            {alternativeSessions.map((alt) => (
                                <label
                                    key={alt.id}
                                    className={`flex items-center justify-between p-3 rounded-xl border cursor-pointer transition ${
                                        Number(rescheduleForm.data.new_session_id) === Number(alt.id)
                                            ? 'border-amber-500 bg-amber-500/10'
                                            : 'border-border bg-surface hover:bg-surface-2'
                                    }`}
                                >
                                    <div className="flex items-center gap-3 min-w-0">
                                        <input
                                            type="radio"
                                            name="new_session_id"
                                            value={alt.id}
                                            checked={Number(rescheduleForm.data.new_session_id) === Number(alt.id)}
                                            onChange={(e) => rescheduleForm.setData('new_session_id', e.target.value)}
                                            className="text-amber-500 focus:ring-amber-500"
                                        />
                                        <div className="truncate">
                                            <p className="text-xs font-bold text-text">
                                                {formatDateId(alt.session_date)}
                                            </p>
                                            <p className="text-[11px] font-mono text-amber-600 dark:text-amber-400">
                                                {formatTimeId(alt.start_time)}
                                            </p>
                                        </div>
                                    </div>
                                    <span className="shrink-0 text-xs text-text-muted">
                                        {alt.film?.title || 'Bebas Pilih Film'}
                                    </span>
                                </label>
                            ))}
                        </div>
                        <div className="flex justify-end gap-2.5 pt-3 border-t border-border">
                            <button
                                type="button"
                                onClick={() => setRescheduleModal(false)}
                                className="inline-flex min-h-10 items-center rounded-xl border border-border bg-surface-2 px-3.5 text-xs font-bold text-text"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                disabled={!rescheduleForm.data.new_session_id || rescheduleForm.processing}
                                className="btn-gold-cinema inline-flex min-h-10 items-center rounded-xl px-4 text-xs font-bold disabled:opacity-40"
                            >
                                Pindah ke Sesi Ini
                            </button>
                        </div>
                    </form>
                </div>
            </Modal>
        </PublicLayout>
    );
}

