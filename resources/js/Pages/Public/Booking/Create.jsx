import { useState, useEffect } from 'react';
import { Link, Head, useForm } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import Modal from '../../../Components/Modal';
import CinemaImage from '../../../Components/CinemaImage';
import {
    Calendar,
    Clock,
    User,
    Heart,
    Crown,
    Ticket,
    Check,
    AlertCircle,
    ChevronLeft,
    Plus,
    Minus,
    ArrowRight,
    CreditCard,
    DollarSign,
    ShieldCheck,
    QrCode,
    Building2,
    X,
    Search,
    Play,
    Volume2,
    Lock,
    BadgeCheck,
    Phone,
    Armchair,
    Receipt,
    Sparkles,
    Film,
    ExternalLink,
} from 'lucide-react';

export default function Create({
    session,
    films = [],
    filmList = [],
    candidateTallies = {},
    quorum = 3,
    unitPrice = 20000,
    couplePrice = 45000,
    privatePrice = 120000,
    canCouple = true,
    coupleDisabledReason = null,
    canPrivate = true,
    privateDisabledReason = null,
    canReguler = true,
    regulerDisabledReason = null,
    paymentMethods = ['di_tempat', 'qris'],
    paymentInstructions = null,
    paymentTimeLimit = 15,
    onlinePaymentCutoff = 20,
    occupiedSeats = 0,
    snackCategories = [],
    snacksEnabled = false,
    snackMaxQty = 10,
    existingUnpaidBooking = null,
}) {
    const isConfirmed = Boolean(session.is_confirmed || session.status === 'terkonfirmasi');
    const remainingSeats = session.remaining_seats ?? (session.capacity - occupiedSeats);

    // Initial selected film & fixed state
    const requestedFilmId = typeof window !== 'undefined' ? new URLSearchParams(window.location.search).get('film') : null;
    const initialFilm = session.film || (requestedFilmId ? films.find((f) => String(f.id) === String(requestedFilmId)) : null) || films[0];
    const isFilmFixed = Boolean(session.film || (requestedFilmId && films.some((f) => String(f.id) === String(requestedFilmId))));

    // Fallback if backend passed empty array
    const activePaymentMethods = paymentMethods && paymentMethods.length > 0 ? paymentMethods : ['di_tempat'];
    const hasSnacks = Boolean(snacksEnabled || (snackCategories && snackCategories.length > 0));

    // Wizard step: 2 (Paket & Film), 3 (Jajan), 4 (Ringkasan & Bayar)
    const [step, setStep] = useState(2);
    const [selectedPackage, setSelectedPackage] = useState(canReguler ? 'reguler' : canCouple ? 'couple' : 'private');
    const [pax, setPax] = useState(1);
    const [selectedFilmId, setSelectedFilmId] = useState(initialFilm ? String(initialFilm.id) : '');
    const [customerName, setCustomerName] = useState('');
    const [whatsapp, setWhatsapp] = useState('');
    const [paymentMethod, setPaymentMethod] = useState(activePaymentMethods[0] || 'di_tempat');
    const [snacksCart, setSnacksCart] = useState({});
    const [activeSnackCategory, setActiveSnackCategory] = useState(snackCategories[0]?.id || null);

    // Synchronize payment method if options change
    useEffect(() => {
        if (!activePaymentMethods.includes(paymentMethod)) {
            setPaymentMethod(activePaymentMethods[0] || 'di_tempat');
        }
    }, [paymentMethods]);

    // Auto-scroll to top of content whenever order step changes or on mount
    useEffect(() => {
        window.scrollTo({ top: 0, left: 0, behavior: 'instant' });
    }, [step]);

    // Film search/filter & modals
    const [filmSearch, setFilmSearch] = useState('');
    const [trailerModalFilm, setTrailerModalFilm] = useState(null);
    const [unpaidModalOpen, setUnpaidModalOpen] = useState(Boolean(existingUnpaidBooking));
    const [showFullSynopsis, setShowFullSynopsis] = useState(false);

    // Form submission
    const { data, setData, post, processing, errors } = useForm({
        session_id: session.id,
        film_id: selectedFilmId,
        booking_type: selectedPackage === 'private' ? 'private' : selectedPackage === 'couple' ? 'couple' : 'per_orang',
        pax: selectedPackage === 'private' ? session.capacity : selectedPackage === 'couple' ? 2 : pax,
        customer_name: customerName,
        whatsapp: whatsapp,
        payment_method: paymentMethod,
        snacks: [],
    });

    // Update form data on state change
    useEffect(() => {
        const type = selectedPackage === 'private' ? 'private' : selectedPackage === 'couple' ? 'couple' : 'per_orang';
        const finalPax = selectedPackage === 'private' ? session.capacity : selectedPackage === 'couple' ? 2 : pax;

        setData({
            session_id: session.id,
            film_id: selectedFilmId,
            booking_type: type,
            pax: finalPax,
            customer_name: customerName,
            whatsapp: whatsapp,
            payment_method: paymentMethod,
            snacks: Object.entries(snacksCart).map(([id, info]) => ({
                id: Number(id),
                qty: info.qty,
                note: info.note || '',
            })),
        });
    }, [selectedPackage, pax, selectedFilmId, customerName, whatsapp, paymentMethod, snacksCart]);

    const handlePackageSelect = (pkg) => {
        if (pkg === 'reguler' && !canReguler) return;
        if (pkg === 'couple' && !canCouple) return;
        if (pkg === 'private' && !canPrivate) return;

        setSelectedPackage(pkg);
        if (pkg === 'private') {
            setPax(session.capacity);
        } else if (pkg === 'couple') {
            setPax(2);
        } else {
            setPax(1);
        }
    };

    // Snacks handlers
    const updateSnackQty = (item, delta) => {
        setSnacksCart((prev) => {
            const current = prev[item.id]?.qty || 0;
            const next = Math.max(0, Math.min(snackMaxQty, current + delta));
            if (next === 0) {
                const copy = { ...prev };
                delete copy[item.id];
                return copy;
            }
            return {
                ...prev,
                [item.id]: {
                    ...prev[item.id],
                    qty: next,
                    item,
                    note: prev[item.id]?.note || '',
                },
            };
        });
    };

    // Calculate totals
    const ticketTotal =
        selectedPackage === 'private'
            ? privatePrice
            : selectedPackage === 'couple'
            ? couplePrice
            : unitPrice * pax;

    const snackTotal = Object.values(snacksCart).reduce((sum, entry) => {
        return sum + (entry.item?.price || 0) * entry.qty;
    }, 0);

    const grandTotal = ticketTotal + snackTotal;

    const handleSubmitBooking = (e) => {
        e.preventDefault();
        post(`/booking/${session.id}`, {
            preserveScroll: true,
        });
    };

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

    const extractYoutubeId = (url) => {
        if (!url) return null;
        const match = url.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/);
        return match ? match[1] : null;
    };

    const filteredFilms = films.filter((f) => {
        if (!filmSearch.trim()) return true;
        const q = filmSearch.toLowerCase();
        return f.title?.toLowerCase().includes(q) || f.genre?.toLowerCase().includes(q);
    });

    const activeFilm = films.find((f) => String(f.id) === String(selectedFilmId)) || initialFilm;
    const activeTrailerId = extractYoutubeId(trailerModalFilm?.trailer_url);

    const resolveFilmPoster = (film) => {
        if (!film) return null;
        if (film.poster_url) return film.poster_url;
        if (film.poster_path) {
            return /^https?:\/\//i.test(film.poster_path)
                ? film.poster_path
                : `/storage/${film.poster_path.replace(/^\/+/, '')}`;
        }
        return null;
    };

    return (
        <PublicLayout title="Pesan Tiket Studio">
            <Head title="Pesan Tiket Studio">
                <meta
                    name="description"
                    content="Pemesanan tiket bioskop mini privat SynemaX. Pengalaman menonton eksklusif ber-AC dengan tata suara Dolby surround."
                />
            </Head>

            {/* Trailer Lightbox Modal */}
            {trailerModalFilm && activeTrailerId && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 animate-in fade-in duration-200"
                    role="dialog"
                    aria-modal="true"
                    aria-label={`Trailer ${trailerModalFilm.title}`}
                >
                    <div
                        className="fixed inset-0 bg-black/90 backdrop-blur-md"
                        onClick={() => setTrailerModalFilm(null)}
                        aria-hidden="true"
                    />
                    <div className="relative z-10 w-full max-w-3xl overflow-hidden rounded-2xl border border-white/10 bg-slate-950 shadow-2xl">
                        <div className="flex items-center justify-between border-b border-white/10 px-4 py-3 text-white">
                            <div className="flex items-center gap-2 min-w-0">
                                <span className="inline-flex items-center gap-1 rounded-full bg-amber-500/20 px-2.5 py-0.5 text-[11px] font-black uppercase text-amber-400">
                                    <Play className="size-3 fill-amber-400" />
                                    <span>Trailer</span>
                                </span>
                                <h3 className="text-sm font-bold truncate text-white">{trailerModalFilm.title}</h3>
                            </div>
                            <div className="flex items-center gap-2">
                                <a
                                    href={`https://www.youtube.com/watch?v=${activeTrailerId}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-semibold text-slate-300 hover:text-white hover:bg-white/10 transition"
                                    title="Buka di YouTube"
                                >
                                    <ExternalLink className="size-3.5" />
                                    <span className="hidden sm:inline">Buka di YouTube</span>
                                </a>
                                <button
                                    type="button"
                                    onClick={() => setTrailerModalFilm(null)}
                                    className="rounded-lg p-1 text-slate-400 hover:bg-white/10 hover:text-white transition cursor-pointer"
                                    aria-label="Tutup trailer"
                                >
                                    <X className="size-5" />
                                </button>
                            </div>
                        </div>
                        <div className="aspect-video w-full bg-black">
                            <iframe
                                src={`https://www.youtube.com/embed/${activeTrailerId}?autoplay=1&rel=0`}
                                title={`Trailer ${trailerModalFilm.title}`}
                                className="size-full border-0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share; fullscreen"
                                allowFullScreen
                            />
                        </div>
                    </div>
                </div>
            )}

            {/* Modal Notifikasi Unpaid Booking Sebelumnya */}
            {unpaidModalOpen && existingUnpaidBooking && (
                <Modal show={unpaidModalOpen} onClose={() => setUnpaidModalOpen(false)} maxWidth="md">
                    <div className="p-6 space-y-4">
                        <div className="flex items-start gap-3">
                            <div className="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                <AlertCircle className="size-5" />
                            </div>
                            <div>
                                <h3 className="text-base font-black text-text">Selesaikan Pembayaran Sebelumnya</h3>
                                <p className="mt-1 text-xs text-text-muted leading-relaxed">
                                    Kamu masih memiliki pesanan aktif dengan kode <strong className="font-mono text-text">{existingUnpaidBooking.code}</strong>.
                                </p>
                            </div>
                        </div>

                        <div className="rounded-2xl border border-border bg-surface-2 p-4 text-xs space-y-1.5">
                            <div className="flex justify-between items-center">
                                <span className="text-text-muted">Total Pembayaran:</span>
                                <span className="font-mono text-base font-black text-amber-600 dark:text-amber-400">
                                    Rp {Number(existingUnpaidBooking.total_price || 0).toLocaleString('id-ID')}
                                </span>
                            </div>
                        </div>

                        <div className="space-y-2 pt-2">
                            <Link
                                href={`/pembayaran/${existingUnpaidBooking.code}`}
                                className="btn-gold-cinema flex w-full min-h-[44px] items-center justify-center rounded-xl text-xs font-black shadow-md"
                            >
                                Lanjutkan Pembayaran
                            </Link>
                            <button
                                type="button"
                                onClick={() => setUnpaidModalOpen(false)}
                                className="flex w-full min-h-[44px] items-center justify-center rounded-xl border border-border bg-surface px-4 text-xs font-bold text-text hover:bg-surface-2 transition"
                            >
                                Tutup & Buat Pesanan Baru
                            </button>
                        </div>
                    </div>
                </Modal>
            )}

            <div className="mx-auto max-w-3xl px-4 pt-3 pb-28 sm:px-6 sm:pb-20 sm:pt-6">
                {/* 1. TOP BAR NAVIGATION */}
                <div className="flex items-center justify-between gap-3 pb-4">
                    <Link
                        href={`/jadwal/${session.session_date}`}
                        className="group inline-flex items-center gap-1.5 rounded-xl border border-border bg-surface px-3 py-2 text-xs font-bold text-text-muted transition hover:border-amber-500/40 hover:text-text hover:bg-surface-2 active:scale-95 shadow-xs"
                    >
                        <ChevronLeft className="size-4 transition group-hover:-translate-x-0.5" />
                        <span>Ganti Jadwal</span>
                    </Link>

                    <div className="flex items-center gap-2">
                        <span className="flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                            <span className="size-2 rounded-full bg-emerald-500 animate-pulse" />
                            <span>{remainingSeats} / {session.capacity} Kursi Tersedia</span>
                        </span>
                    </div>
                </div>

                {/* 2. CINEMA SESSION PASS MARQUEE CARD */}
                <div className="relative overflow-hidden rounded-3xl border border-amber-500/25 bg-gradient-to-br from-surface via-surface to-amber-500/[0.04] p-5 sm:p-6 shadow-sm">
                    <div className="absolute -right-8 -top-8 size-32 rounded-full bg-amber-500/10 blur-2xl pointer-events-none" />

                    <div className="flex items-start justify-between gap-3">
                        <div className="space-y-1">
                            <div className="flex items-center gap-2 flex-wrap">
                                <span className="inline-flex items-center gap-1 rounded-md bg-amber-500 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-black">
                                    SynemaX Premiere
                                </span>
                                <span className="inline-flex items-center gap-1 text-[11px] font-bold text-text-muted">
                                    <Volume2 className="size-3 text-amber-500" />
                                    <span>Dolby Surround</span>
                                </span>
                                {isConfirmed && (
                                    <span className="inline-flex items-center gap-1 rounded-md bg-emerald-500/15 border border-emerald-500/30 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-600 dark:text-emerald-400">
                                        <Lock className="size-3" />
                                        <span>Film Terkunci</span>
                                    </span>
                                )}
                            </div>
                            <h1 className="text-xl sm:text-2xl font-black text-text tracking-tight pt-1">
                                Pemesanan Tiket Studio
                            </h1>
                        </div>

                        <div className="shrink-0 text-right">
                            <span className="text-[10px] font-bold uppercase tracking-wider text-text-muted block">Studio</span>
                            <span className="font-mono text-sm sm:text-base font-black text-amber-600 dark:text-amber-400">
                                STUDIO 1
                            </span>
                        </div>
                    </div>

                    {/* Session Time & Date Pill Bar */}
                    <div className="mt-4 pt-4 border-t border-border/70 flex flex-wrap items-center justify-between gap-2.5">
                        <div className="flex items-center gap-2 text-xs font-bold text-text">
                            <Calendar className="size-4 text-amber-500 shrink-0" />
                            <span>{formatDateId(session.session_date)}</span>
                        </div>
                        <div className="flex items-center gap-2 rounded-xl bg-amber-500/10 border border-amber-500/20 px-3 py-1.5 font-mono text-xs font-black text-amber-600 dark:text-amber-400">
                            <Clock className="size-3.5 text-amber-500 shrink-0" />
                            <span>{session.start_time?.slice(0, 5)} - {session.end_time?.slice(0, 5)} WIB</span>
                        </div>
                    </div>
                </div>

                {/* Error Banner */}
                {Object.keys(errors).length > 0 && (
                    <div className="mt-4 rounded-2xl border border-danger/40 bg-danger-surface p-4 text-xs font-semibold text-danger-text space-y-1">
                        {Object.values(errors).map((err, i) => (
                            <p key={i} className="flex items-center gap-1.5">
                                <AlertCircle className="size-3.5 shrink-0" />
                                <span>{err}</span>
                            </p>
                        ))}
                    </div>
                )}

                {/* 3. STEPPER WIZARD INDICATOR */}
                <div className="mt-5 grid grid-cols-3 gap-1.5 sm:gap-2 p-1.5 rounded-2xl border border-border/80 bg-surface-2/60">
                    <button
                        type="button"
                        onClick={() => setStep(2)}
                        className={`flex items-center justify-center gap-1.5 rounded-xl py-2 px-2 text-xs transition cursor-pointer ${
                            step === 2
                                ? 'bg-amber-500 text-black shadow-xs font-black'
                                : step > 2
                                ? 'bg-surface text-amber-600 dark:text-amber-400 font-bold'
                                : 'text-text-muted hover:text-text font-semibold'
                        }`}
                    >
                        <span className={`flex size-4 items-center justify-center rounded-full text-[10px] font-black shrink-0 ${
                            step === 2 ? 'bg-black text-amber-400' : step > 2 ? 'bg-amber-500 text-black' : 'bg-border text-text-muted'
                        }`}>
                            {step > 2 ? <Check className="size-2.5 stroke-[3]" /> : '1'}
                        </span>
                        <span className="truncate">Paket & Film</span>
                    </button>

                    {hasSnacks ? (
                        <button
                            type="button"
                            onClick={() => {
                                if (customerName.trim() && whatsapp.trim() && selectedFilmId) {
                                    setStep(3);
                                }
                            }}
                            disabled={!customerName.trim() || !whatsapp.trim() || !selectedFilmId}
                            className={`flex items-center justify-center gap-1.5 rounded-xl py-2 px-2 text-xs transition disabled:opacity-40 cursor-pointer ${
                                step === 3
                                    ? 'bg-amber-500 text-black shadow-xs font-black'
                                    : step > 3
                                    ? 'bg-surface text-amber-600 dark:text-amber-400 font-bold'
                                    : 'text-text-muted hover:text-text font-semibold'
                            }`}
                        >
                            <span className={`flex size-4 items-center justify-center rounded-full text-[10px] font-black shrink-0 ${
                                step === 3 ? 'bg-black text-amber-400' : step > 3 ? 'bg-amber-500 text-black' : 'bg-border text-text-muted'
                            }`}>
                                {step > 3 ? <Check className="size-2.5 stroke-[3]" /> : '2'}
                            </span>
                            <span className="truncate">
                                Jajan {Object.keys(snacksCart).length > 0 ? `(${Object.keys(snacksCart).length})` : ''}
                            </span>
                        </button>
                    ) : (
                        <div className="flex items-center justify-center py-2 px-2 text-[11px] font-medium text-text-subtle opacity-50 select-none">
                            <span>(Tanpa Jajan)</span>
                        </div>
                    )}

                    <button
                        type="button"
                        onClick={() => {
                            if (customerName.trim() && whatsapp.trim() && selectedFilmId) {
                                setStep(4);
                            }
                        }}
                        disabled={!customerName.trim() || !whatsapp.trim() || !selectedFilmId}
                        className={`flex items-center justify-center gap-1.5 rounded-xl py-2 px-2 text-xs transition disabled:opacity-40 cursor-pointer ${
                            step === 4
                                ? 'bg-amber-500 text-black shadow-xs font-black'
                                : 'text-text-muted hover:text-text font-semibold'
                        }`}
                    >
                        <span className={`flex size-4 items-center justify-center rounded-full text-[10px] font-black shrink-0 ${
                            step === 4 ? 'bg-black text-amber-400' : 'bg-border text-text-muted'
                        }`}>
                            {hasSnacks ? '3' : '2'}
                        </span>
                        <span className="truncate">Ringkasan Bayar</span>
                    </button>
                </div>

                {/* ==================== STEP 2: PAKET, FILM, & DATA DIRI ==================== */}
                {step === 2 && (
                    <div className="mt-6 space-y-7">
                        {/* 1. PILIH PAKET NONTON */}
                        <section className="space-y-3.5">
                            <div className="flex flex-col sm:flex-row sm:items-baseline justify-between gap-1 border-b border-border/60 pb-2">
                                <h2 className="text-base sm:text-lg font-black text-text tracking-tight flex items-center gap-2">
                                    <span className="flex size-6 items-center justify-center rounded-lg bg-amber-500 text-black text-xs font-black">
                                        1
                                    </span>
                                    <span>Pilih Tipe Pengalaman Nonton</span>
                                </h2>
                                <span className="text-xs text-text-muted">
                                    Sesuaikan dengan suasana dan teman nontonmu
                                </span>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-4">
                                {/* Reguler Card */}
                                <button
                                    type="button"
                                    onClick={() => handlePackageSelect('reguler')}
                                    disabled={!canReguler}
                                    className={`group relative flex flex-col justify-between overflow-hidden p-4 sm:p-5 rounded-2xl border text-left transition-all duration-200 cursor-pointer ${
                                        selectedPackage === 'reguler'
                                            ? 'border-amber-500 ring-2 ring-amber-500/40 bg-gradient-to-br from-amber-500/15 via-amber-500/[0.04] to-surface shadow-md shadow-amber-500/10'
                                            : canReguler
                                            ? 'border-border/80 bg-surface hover:border-amber-500/50 hover:bg-surface-2 shadow-xs'
                                            : 'border-border/40 bg-surface-2/40 opacity-40 cursor-not-allowed'
                                    }`}
                                >
                                    <div className="flex items-start justify-between gap-2.5 w-full">
                                        <div className="flex items-start gap-2.5 min-w-0 flex-1">
                                            <div
                                                className={`flex size-10 shrink-0 items-center justify-center rounded-xl transition ${
                                                    selectedPackage === 'reguler'
                                                        ? 'bg-amber-500 text-black font-black shadow-md shadow-amber-500/30'
                                                        : 'bg-amber-500/15 text-amber-500 border border-amber-500/30'
                                                }`}
                                            >
                                                <User className="size-5" />
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-center gap-1.5 flex-wrap">
                                                    <h3 className="text-sm sm:text-base font-black text-text tracking-tight">
                                                        Reguler
                                                    </h3>
                                                    <span className="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/30">
                                                        Fleksibel
                                                    </span>
                                                </div>
                                                <p className="text-xs text-text-muted mt-1 leading-snug">
                                                    Per kursi, bebas atur jumlah
                                                </p>
                                            </div>
                                        </div>

                                        <div
                                            className={`size-5 rounded-full border-2 flex items-center justify-center shrink-0 transition mt-0.5 ${
                                                selectedPackage === 'reguler'
                                                    ? 'border-amber-500 bg-amber-500 text-black shadow-xs'
                                                    : 'border-border bg-surface-2'
                                            }`}
                                        >
                                            {selectedPackage === 'reguler' && <Check className="size-3 stroke-[3]" />}
                                        </div>
                                    </div>

                                    <div className="mt-4 pt-3 border-t border-border/50 w-full">
                                        <span className="block text-[11px] font-bold uppercase tracking-wider text-text-muted">
                                            Tarif Per Orang
                                        </span>
                                        <div className="mt-0.5 flex items-baseline gap-1.5 flex-wrap">
                                            <span className="font-mono text-xl sm:text-2xl font-black text-amber-600 dark:text-amber-400">
                                                Rp {Number(unitPrice).toLocaleString('id-ID')}
                                            </span>
                                            <span className="text-xs font-semibold text-text-muted">/orang</span>
                                        </div>
                                    </div>

                                    {!canReguler && (
                                        <p className="text-xs text-danger-text mt-2 font-bold leading-tight">{regulerDisabledReason}</p>
                                    )}
                                </button>

                                {/* Couple Velvet Card */}
                                <button
                                    type="button"
                                    onClick={() => handlePackageSelect('couple')}
                                    disabled={!canCouple}
                                    className={`group relative flex flex-col justify-between overflow-hidden p-4 sm:p-5 rounded-2xl border text-left transition-all duration-200 cursor-pointer ${
                                        selectedPackage === 'couple'
                                            ? 'border-amber-500 ring-2 ring-amber-500/40 bg-gradient-to-br from-amber-500/15 via-amber-500/[0.04] to-surface shadow-md shadow-amber-500/10'
                                            : canCouple
                                            ? 'border-border/80 bg-surface hover:border-amber-500/50 hover:bg-surface-2 shadow-xs'
                                            : 'border-border/40 bg-surface-2/40 opacity-40 cursor-not-allowed'
                                    }`}
                                >
                                    <div className="flex items-start justify-between gap-2.5 w-full">
                                        <div className="flex items-start gap-2.5 min-w-0 flex-1">
                                            <div
                                                className={`flex size-10 shrink-0 items-center justify-center rounded-xl transition ${
                                                    selectedPackage === 'couple'
                                                        ? 'bg-rose-500 text-white font-black shadow-md shadow-rose-500/30'
                                                        : 'bg-rose-500/15 text-rose-500 border border-rose-500/30'
                                                }`}
                                            >
                                                <Heart className="size-5 fill-current" />
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-center gap-1.5 flex-wrap">
                                                    <h3 className="text-sm sm:text-base font-black text-text tracking-tight">
                                                        Couple Velvet
                                                    </h3>
                                                    <span className="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30">
                                                        Favorit
                                                    </span>
                                                </div>
                                                <p className="text-xs text-text-muted mt-1 leading-snug">
                                                    2 Kursi sofa santai tanpa sekat
                                                </p>
                                            </div>
                                        </div>

                                        <div
                                            className={`size-5 rounded-full border-2 flex items-center justify-center shrink-0 transition mt-0.5 ${
                                                selectedPackage === 'couple'
                                                    ? 'border-amber-500 bg-amber-500 text-black shadow-xs'
                                                    : 'border-border bg-surface-2'
                                            }`}
                                        >
                                            {selectedPackage === 'couple' && <Check className="size-3 stroke-[3]" />}
                                        </div>
                                    </div>

                                    <div className="mt-4 pt-3 border-t border-border/50 w-full">
                                        <span className="block text-[11px] font-bold uppercase tracking-wider text-text-muted">
                                            Tarif Paket
                                        </span>
                                        <div className="mt-0.5 flex items-baseline gap-1.5 flex-wrap">
                                            <span className="font-mono text-xl sm:text-2xl font-black text-amber-600 dark:text-amber-400">
                                                Rp {Number(couplePrice).toLocaleString('id-ID')}
                                            </span>
                                            <span className="text-xs font-semibold text-text-muted">/2 orang</span>
                                        </div>
                                    </div>

                                    {!canCouple && (
                                        <p className="text-xs text-danger-text mt-2 font-bold leading-tight">{coupleDisabledReason}</p>
                                    )}
                                </button>

                                {/* Private Studio VIP Card */}
                                <button
                                    type="button"
                                    onClick={() => handlePackageSelect('private')}
                                    disabled={!canPrivate}
                                    className={`group relative flex flex-col justify-between overflow-hidden p-4 sm:p-5 rounded-2xl border text-left transition-all duration-200 cursor-pointer ${
                                        selectedPackage === 'private'
                                            ? 'border-amber-500 ring-2 ring-amber-500/40 bg-gradient-to-br from-amber-500/15 via-amber-500/[0.04] to-surface shadow-md shadow-amber-500/10'
                                            : canPrivate
                                            ? 'border-border/80 bg-surface hover:border-amber-500/50 hover:bg-surface-2 shadow-xs'
                                            : 'border-border/40 bg-surface-2/40 opacity-40 cursor-not-allowed'
                                    }`}
                                >
                                    <div className="flex items-start justify-between gap-2.5 w-full">
                                        <div className="flex items-start gap-2.5 min-w-0 flex-1">
                                            <div
                                                className={`flex size-10 shrink-0 items-center justify-center rounded-xl transition ${
                                                    selectedPackage === 'private'
                                                        ? 'bg-purple-600 text-white font-black shadow-md shadow-purple-600/30'
                                                        : 'bg-purple-500/15 text-purple-600 dark:text-purple-400 border border-purple-500/30'
                                                }`}
                                            >
                                                <Crown className="size-5" />
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-center gap-1.5 flex-wrap">
                                                    <h3 className="text-sm sm:text-base font-black text-text tracking-tight">
                                                        Private Studio
                                                    </h3>
                                                    <span className="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-purple-500/15 text-purple-700 dark:text-purple-300 border border-purple-500/30">
                                                        VIP Hall
                                                    </span>
                                                </div>
                                                <p className="text-xs text-text-muted mt-1 leading-snug">
                                                    Sewa penuh ({session.capacity || 6} kursi)
                                                </p>
                                            </div>
                                        </div>

                                        <div
                                            className={`size-5 rounded-full border-2 flex items-center justify-center shrink-0 transition mt-0.5 ${
                                                selectedPackage === 'private'
                                                    ? 'border-amber-500 bg-amber-500 text-black shadow-xs'
                                                    : 'border-border bg-surface-2'
                                            }`}
                                        >
                                            {selectedPackage === 'private' && <Check className="size-3 stroke-[3]" />}
                                        </div>
                                    </div>

                                    <div className="mt-4 pt-3 border-t border-border/50 w-full">
                                        <span className="block text-[11px] font-bold uppercase tracking-wider text-text-muted">
                                            Tarif Sewa Studio
                                        </span>
                                        <div className="mt-0.5 flex items-baseline gap-1.5 flex-wrap">
                                            <span className="font-mono text-xl sm:text-2xl font-black text-amber-600 dark:text-amber-400">
                                                Rp {Number(privatePrice).toLocaleString('id-ID')}
                                            </span>
                                            <span className="text-xs font-semibold text-text-muted">/sesi</span>
                                        </div>
                                    </div>

                                    {!canPrivate && (
                                        <p className="text-xs text-danger-text mt-2 font-bold leading-tight">{privateDisabledReason}</p>
                                    )}
                                </button>
                            </div>

                            {/* Interactive Ticket & Seat Selector for Reguler */}
                            {selectedPackage === 'reguler' && (
                                <div className="p-4 sm:p-5 rounded-2xl border border-amber-500/30 bg-gradient-to-r from-amber-500/[0.06] to-surface shadow-xs animate-in fade-in duration-200">
                                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div className="space-y-1">
                                            <div className="flex items-center gap-2">
                                                <Ticket className="size-5 text-amber-500" />
                                                <p className="text-base font-black text-text">Berapa Tiket yang Kamu Pesan?</p>
                                            </div>
                                            <p className="text-xs text-text-muted">
                                                Tersedia {remainingSeats} kursi tersisa di studio penayangan ini
                                            </p>

                                            {/* Visual Armchair Dots */}
                                            <div className="pt-2 flex items-center gap-1.5 flex-wrap">
                                                {Array.from({ length: Math.min(10, remainingSeats) }).map((_, i) => (
                                                    <div
                                                        key={i}
                                                        className={`flex size-7 items-center justify-center rounded-lg border transition ${
                                                            i < pax
                                                                ? 'border-amber-500 bg-amber-500 text-black shadow-xs'
                                                                : 'border-border/60 bg-surface-2 text-text-muted opacity-50'
                                                        }`}
                                                        title={`Kursi ${i + 1}`}
                                                    >
                                                        <Armchair className="size-3.5" />
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-3 self-end sm:self-auto shrink-0 bg-surface p-1.5 rounded-2xl border border-border shadow-xs">
                                            <button
                                                type="button"
                                                onClick={() => setPax(Math.max(1, pax - 1))}
                                                disabled={pax <= 1}
                                                className="flex size-11 items-center justify-center rounded-xl bg-surface-2 text-text hover:bg-surface-sunken transition disabled:opacity-30 cursor-pointer active:scale-95"
                                                aria-label="Kurangi tiket"
                                            >
                                                <Minus className="size-5" />
                                            </button>
                                            <div className="w-12 text-center">
                                                <span className="font-mono text-2xl font-black text-text block leading-none">{pax}</span>
                                                <span className="text-[10px] uppercase font-bold text-text-muted tracking-wider">Tiket</span>
                                            </div>
                                            <button
                                                type="button"
                                                onClick={() => setPax(Math.min(remainingSeats, pax + 1))}
                                                disabled={pax >= remainingSeats}
                                                className="flex size-11 items-center justify-center rounded-xl bg-amber-500 text-black hover:bg-amber-400 font-bold transition disabled:opacity-30 cursor-pointer active:scale-95 shadow-xs"
                                                aria-label="Tambah tiket"
                                            >
                                                <Plus className="size-5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </section>

                        {/* 2. FILM SHOWCASE OR SELECTION */}
                        <section className="space-y-3.5">
                            <div className="flex items-baseline justify-between gap-1 border-b border-border/60 pb-2">
                                <h2 className="text-base sm:text-lg font-black text-text tracking-tight flex items-center gap-2">
                                    <span className="flex size-6 items-center justify-center rounded-lg bg-amber-500 text-black text-xs font-black">
                                        2
                                    </span>
                                    <span>{isFilmFixed ? 'Film yang Diputar' : 'Pilih Film Tayang'}</span>
                                </h2>
                                <span className="text-xs text-text-muted">
                                    {isFilmFixed ? 'Terkunci untuk sesi ini' : 'Bebas pilih film favoritmu'}
                                </span>
                            </div>

                            {isFilmFixed && initialFilm ? (
                                <div className="relative overflow-hidden rounded-3xl border border-border bg-gradient-to-br from-surface-2 via-surface to-surface p-4 sm:p-5 shadow-sm">
                                    <div className="flex gap-4 sm:gap-5 items-start">
                                        <div className="shrink-0 relative group">
                                            <CinemaImage
                                                src={initialFilm.poster_url || (initialFilm.poster_path ? `/storage/${initialFilm.poster_path}` : null)}
                                                alt={initialFilm.title}
                                                title={initialFilm.title}
                                                aspect="aspect-[2/3]"
                                                className="w-20 sm:w-24 rounded-2xl shadow-md border border-white/10"
                                            />
                                            {initialFilm.age_rating && (
                                                <span className="absolute top-1.5 left-1.5 rounded-md bg-black/80 px-1.5 py-0.5 text-[9px] font-black text-amber-400 border border-white/20">
                                                    {initialFilm.age_rating}
                                                </span>
                                            )}
                                        </div>

                                        <div className="min-w-0 flex-1 space-y-2">
                                            <div>
                                                <div className="flex items-center gap-2 flex-wrap">
                                                    <span className="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 border border-emerald-500/30 px-2 py-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
                                                        <Check className="size-3" />
                                                        <span>Film Terpilih</span>
                                                    </span>
                                                    <span className="text-xs font-semibold text-text-muted">
                                                        {initialFilm.release_year || '2026'}
                                                    </span>
                                                </div>
                                                <h3 className="text-lg sm:text-xl font-black text-text tracking-tight mt-1 line-clamp-1">
                                                    {initialFilm.title}
                                                </h3>
                                            </div>

                                            <div className="flex items-center gap-2 flex-wrap text-xs text-text-muted">
                                                <span className="rounded-lg bg-surface-2 px-2 py-1 font-semibold text-text">
                                                    {initialFilm.genre}
                                                </span>
                                                <span className="rounded-lg bg-surface-2 px-2 py-1 font-semibold text-text">
                                                    {initialFilm.duration_minutes} Menit
                                                </span>
                                            </div>

                                            {initialFilm.synopsis && (
                                                <div className="text-xs text-text-muted leading-relaxed">
                                                    <p className={showFullSynopsis ? '' : 'line-clamp-2'}>
                                                        {initialFilm.synopsis}
                                                    </p>
                                                    {initialFilm.synopsis.length > 120 && (
                                                        <button
                                                            type="button"
                                                            onClick={() => setShowFullSynopsis(!showFullSynopsis)}
                                                            className="text-amber-500 font-bold hover:underline mt-0.5 cursor-pointer"
                                                        >
                                                            {showFullSynopsis ? 'Ringkas' : 'Baca selengkapnya'}
                                                        </button>
                                                    )}
                                                </div>
                                            )}

                                            {initialFilm.trailer_url && (
                                                <div className="pt-1">
                                                    <button
                                                        type="button"
                                                        onClick={() => setTrailerModalFilm(initialFilm)}
                                                        className="inline-flex items-center gap-1.5 rounded-xl border border-amber-500/40 bg-amber-500/10 px-3 py-1.5 text-xs font-bold text-amber-500 hover:bg-amber-500 hover:text-black transition cursor-pointer active:scale-95"
                                                    >
                                                        <Play className="size-3.5 fill-current" />
                                                        <span>Putar Trailer</span>
                                                    </button>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    <div className="relative">
                                        <Search className="size-4 text-text-muted absolute left-4 top-3.5" />
                                        <input
                                            type="text"
                                            value={filmSearch}
                                            onChange={(e) => setFilmSearch(e.target.value)}
                                            placeholder="Cari judul film atau genre favorit..."
                                            className="w-full rounded-2xl border border-border bg-surface pl-11 pr-4 py-3 text-sm text-text placeholder:text-text-muted focus:border-amber-500 focus:outline-none min-h-[46px] shadow-xs"
                                        />
                                    </div>

                                    <div className="max-h-72 overflow-y-auto space-y-2 pr-1 custom-scrollbar">
                                        {filteredFilms.map((f) => {
                                            const isPicked = String(f.id) === String(selectedFilmId);
                                            return (
                                                <div
                                                    key={f.id}
                                                    onClick={() => setSelectedFilmId(String(f.id))}
                                                    className={`group flex items-center justify-between p-3.5 rounded-2xl border cursor-pointer transition ${
                                                        isPicked
                                                            ? 'border-amber-500 ring-1 ring-amber-500/40 bg-amber-500/[0.08] dark:bg-amber-500/10 shadow-xs'
                                                            : 'border-border bg-surface hover:bg-surface-2'
                                                    }`}
                                                >
                                                    <div className="flex items-center gap-3.5 min-w-0">
                                                        <CinemaImage
                                                            src={f.poster_url || (f.poster_path ? `/storage/${f.poster_path}` : null)}
                                                            alt={f.title}
                                                            title={f.title}
                                                            aspect="aspect-[2/3]"
                                                            className="w-12 shrink-0 rounded-xl shadow-xs"
                                                        />
                                                        <div className="min-w-0">
                                                            <p className="text-sm font-black text-text truncate group-hover:text-amber-500 transition">
                                                                {f.title}
                                                            </p>
                                                            <p className="text-xs text-text-muted mt-0.5">
                                                                {f.genre} · {f.duration_minutes}m · {f.age_rating || 'SU'}
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <div className="shrink-0 pl-2">
                                                        <div
                                                            className={`size-6 rounded-full border-2 flex items-center justify-center transition ${
                                                                isPicked
                                                                    ? 'border-amber-500 bg-amber-500 text-black shadow-xs'
                                                                    : 'border-border bg-surface'
                                                            }`}
                                                        >
                                                            {isPicked && <Check className="size-3.5 stroke-[3]" />}
                                                        </div>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}
                        </section>

                        {/* 3. DATA PEMESAN */}
                        <section className="space-y-3.5">
                            <div className="flex flex-col sm:flex-row sm:items-baseline justify-between gap-1 border-b border-border/60 pb-2">
                                <h2 className="text-base sm:text-lg font-black text-text tracking-tight flex items-center gap-2">
                                    <span className="flex size-6 items-center justify-center rounded-lg bg-amber-500 text-black text-xs font-black">
                                        3
                                    </span>
                                    <span>Data Pemesan</span>
                                </h2>
                                <span className="text-xs text-text-muted">
                                    E-tiket & kode QR masuk akan dikirimkan ke WhatsApp
                                </span>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div className="space-y-1.5">
                                    <label htmlFor="customer_name" className="block text-xs font-bold text-text">
                                        Nama Lengkap Pemesan *
                                    </label>
                                    <div className="relative">
                                        <User className="size-4 text-text-muted absolute left-3.5 top-3.5" />
                                        <input
                                            id="customer_name"
                                            type="text"
                                            required
                                            value={customerName}
                                            onChange={(e) => setCustomerName(e.target.value)}
                                            placeholder="Nama sesuai identitas"
                                            className="w-full rounded-2xl border border-border bg-surface pl-10 pr-4 py-3 text-sm min-h-[46px] text-text placeholder:text-text-muted focus:border-amber-500 focus:outline-none transition shadow-xs"
                                        />
                                    </div>
                                </div>

                                <div className="space-y-1.5">
                                    <label htmlFor="whatsapp" className="block text-xs font-bold text-text">
                                        Nomor WhatsApp Aktif *
                                    </label>
                                    <div className="relative">
                                        <Phone className="size-4 text-text-muted absolute left-3.5 top-3.5" />
                                        <input
                                            id="whatsapp"
                                            type="tel"
                                            required
                                            value={whatsapp}
                                            onChange={(e) => setWhatsapp(e.target.value)}
                                            placeholder="Contoh: 081234567890"
                                            className="w-full rounded-2xl border border-border bg-surface pl-10 pr-4 py-3 text-sm min-h-[46px] text-text placeholder:text-text-muted focus:border-amber-500 focus:outline-none transition shadow-xs"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="flex items-center gap-2 rounded-2xl border border-border bg-surface-2/60 p-3 text-xs text-text-muted">
                                <ShieldCheck className="size-4 text-emerald-500 shrink-0" />
                                <span>Privasi data terjaga. Tiket resmi terverifikasi langsung secara digital.</span>
                            </div>
                        </section>

                        {/* CTA Next */}
                        <div className="pt-4 border-t border-border flex justify-end">
                            <button
                                type="button"
                                onClick={() => setStep(hasSnacks ? 3 : 4)}
                                disabled={!customerName.trim() || !whatsapp.trim() || !selectedFilmId}
                                className="btn-gold-cinema inline-flex min-h-12 w-full sm:w-auto items-center justify-center gap-2 rounded-2xl px-8 text-sm font-black shadow-lg shadow-amber-500/20 disabled:opacity-40 cursor-pointer active:scale-95"
                            >
                                <span>{hasSnacks ? 'Lanjut Pilih Jajan & Minum' : 'Lanjut ke Ringkasan Bayar'}</span>
                                <ArrowRight className="size-4" />
                            </button>
                        </div>
                    </div>
                )}

                {/* ==================== STEP 3: CONCESSION & CAFE ==================== */}
                {step === 3 && hasSnacks && (
                    <div className="mt-6 space-y-6">
                        <div className="flex items-center justify-between border-b border-border/60 pb-3">
                            <div>
                                <h2 className="text-base sm:text-lg font-black text-text tracking-tight flex items-center gap-2">
                                    <span className="flex size-6 items-center justify-center rounded-lg bg-amber-500 text-black text-xs font-black">
                                        2
                                    </span>
                                    <span>SynemaX Concession & Café</span>
                                </h2>
                                <p className="text-xs text-text-muted mt-0.5">
                                    Camilan hangat & minuman segar siap saji saat kamu tiba di studio
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setStep(4)}
                                className="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline cursor-pointer"
                            >
                                Lewati
                            </button>
                        </div>

                        {/* Category tabs */}
                        <div className="flex gap-2 overflow-x-auto pb-2 scrollbar-none">
                            {snackCategories.map((cat) => (
                                <button
                                    key={cat.id}
                                    type="button"
                                    onClick={() => setActiveSnackCategory(cat.id)}
                                    className={`shrink-0 rounded-xl px-4 py-2 text-xs font-bold transition cursor-pointer ${
                                        activeSnackCategory === cat.id
                                            ? 'bg-amber-500 text-black font-black shadow-xs'
                                            : 'border border-border bg-surface text-text-muted hover:text-text hover:bg-surface-2'
                                    }`}
                                >
                                    {cat.name}
                                </button>
                            ))}
                        </div>

                        {/* Snack items */}
                        <div className="space-y-3">
                            {snackCategories
                                .find((c) => c.id === activeSnackCategory)
                                ?.items?.map((item) => {
                                    const qty = snacksCart[item.id]?.qty || 0;
                                    return (
                                        <div
                                            key={item.id}
                                            className="flex items-center justify-between p-3.5 sm:p-4 rounded-2xl border border-border bg-surface shadow-xs hover:border-amber-500/40 transition"
                                        >
                                            <div className="flex items-center gap-3.5 min-w-0">
                                                <CinemaImage
                                                    src={item.photo_url || (item.photo_path ? `/storage/${item.photo_path}` : null)}
                                                    alt={item.name}
                                                    title={item.name}
                                                    aspect="aspect-square"
                                                    className="size-14 sm:size-16 shrink-0 rounded-2xl shadow-xs"
                                                />
                                                <div className="min-w-0">
                                                    <p className="text-sm font-black text-text truncate">{item.name}</p>
                                                    <p className="font-mono text-sm font-bold text-amber-600 dark:text-amber-400 mt-0.5">
                                                        Rp {Number(item.price || 0).toLocaleString('id-ID')}
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-2 bg-surface-2 p-1 rounded-xl border border-border">
                                                {qty > 0 && (
                                                    <button
                                                        type="button"
                                                        onClick={() => updateSnackQty(item, -1)}
                                                        className="flex size-8 items-center justify-center rounded-lg bg-surface text-text hover:bg-surface-sunken transition cursor-pointer"
                                                        aria-label="Kurang"
                                                    >
                                                        <Minus className="size-3.5" />
                                                    </button>
                                                )}
                                                {qty > 0 && (
                                                    <span className="font-mono text-xs font-black w-6 text-center text-text">
                                                        {qty}
                                                    </span>
                                                )}
                                                <button
                                                    type="button"
                                                    onClick={() => updateSnackQty(item, 1)}
                                                    className="flex size-8 items-center justify-center rounded-lg bg-amber-500 text-black font-black hover:bg-amber-400 transition cursor-pointer shadow-xs"
                                                    aria-label="Tambah"
                                                >
                                                    <Plus className="size-3.5" />
                                                </button>
                                            </div>
                                        </div>
                                    );
                                })}
                        </div>

                        {/* Navigation Buttons */}
                        <div className="pt-4 border-t border-border flex justify-between items-center">
                            <button
                                type="button"
                                onClick={() => setStep(2)}
                                className="inline-flex min-h-11 items-center gap-1.5 text-xs font-bold text-text-muted hover:text-text cursor-pointer"
                            >
                                <ChevronLeft className="size-4" />
                                <span>Kembali ke Paket</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => setStep(4)}
                                className="btn-gold-cinema inline-flex min-h-12 items-center gap-2 rounded-2xl px-7 text-xs sm:text-sm font-black shadow-md cursor-pointer"
                            >
                                <span>Lanjut ke Ringkasan</span>
                                <ArrowRight className="size-4" />
                            </button>
                        </div>
                    </div>
                )}

                {/* ==================== STEP 4: RINGKASAN & METODE BAYAR ==================== */}
                {step === 4 && (
                    <form onSubmit={handleSubmitBooking} className="mt-6 space-y-6">
                        {/* THE PREMIERE CINEMA TICKET RECEIPT */}
                        <div className="relative overflow-hidden rounded-3xl border border-amber-500/30 bg-surface shadow-md">
                            {/* Receipt Top Header */}
                            <div className="p-5 sm:p-6 border-b border-dashed border-border/80 relative">
                                <div className="flex items-center justify-between gap-3 pb-3.5 border-b border-border/50">
                                    <div className="flex items-center gap-2">
                                        <Receipt className="size-4.5 text-amber-500" />
                                        <span className="text-xs font-black uppercase tracking-wider text-text">
                                            Rincian Pemesanan Tiket
                                        </span>
                                    </div>
                                    <span className="text-[11px] font-mono text-text-muted">SYNEMAX PASS</span>
                                </div>

                                {/* Film Poster & Details Card */}
                                <div className="mt-4 flex items-center gap-3.5 sm:gap-4">
                                    {resolveFilmPoster(activeFilm) ? (
                                        <img
                                            src={resolveFilmPoster(activeFilm)}
                                            alt={activeFilm?.title || 'Poster Film'}
                                            className="w-16 sm:w-20 aspect-[2/3] rounded-2xl object-cover shadow-md border border-border/80 shrink-0"
                                        />
                                    ) : (
                                        <div className="w-16 sm:w-20 aspect-[2/3] rounded-2xl bg-surface-2 border border-border/80 flex flex-col items-center justify-center shrink-0 text-text-muted">
                                            <Film className="size-6 text-amber-500/70" />
                                        </div>
                                    )}

                                    <div className="min-w-0 flex-1 space-y-1">
                                        <div className="flex flex-wrap items-center gap-1.5 mb-1">
                                            <span className="inline-block rounded-md bg-amber-500/15 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                                {selectedPackage === 'private'
                                                    ? 'Private Studio'
                                                    : selectedPackage === 'couple'
                                                    ? 'Paket Couple'
                                                    : 'Reguler'}
                                            </span>
                                            {activeFilm?.age_rating && (
                                                <span className="rounded-md bg-surface-2 px-1.5 py-0.5 text-[10px] font-bold text-text-muted border border-border">
                                                    {activeFilm.age_rating}
                                                </span>
                                            )}
                                        </div>

                                        <h3 className="text-base sm:text-lg font-black text-text tracking-tight line-clamp-2">
                                            {activeFilm?.title || 'Film Studio SynemaX'}
                                        </h3>

                                        <p className="text-xs text-text-muted flex items-center gap-1.5 flex-wrap">
                                            <span>{formatDateId(session.session_date)}</span>
                                            <span>·</span>
                                            <span className="font-mono font-bold text-amber-600 dark:text-amber-400">
                                                {session.start_time?.slice(0, 5)} WIB
                                            </span>
                                            <span>·</span>
                                            <span>STUDIO 1</span>
                                        </p>

                                        {activeFilm?.genre && (
                                            <p className="text-[11px] text-text-muted truncate">
                                                {activeFilm.genre} {activeFilm.duration_minutes ? `· ${activeFilm.duration_minutes} Menit` : ''}
                                            </p>
                                        )}
                                    </div>
                                </div>

                                {/* Semicircular Ticket Notches */}
                                <div className="absolute -bottom-3 -left-3 size-6 rounded-full bg-[var(--surface-canvas)] border-r border-border/80" />
                                <div className="absolute -bottom-3 -right-3 size-6 rounded-full bg-[var(--surface-canvas)] border-l border-border/80" />
                            </div>

                            {/* Receipt Line Items */}
                            <div className="p-5 sm:p-6 space-y-3 bg-surface-2/30">
                                <div className="flex justify-between items-center text-xs">
                                    <span className="text-text-muted">
                                        Tiket Nonton (
                                        {selectedPackage === 'private'
                                            ? `Private Studio · ${session.capacity || 6} Kursi`
                                            : selectedPackage === 'couple'
                                            ? 'Couple Velvet · 2 Kursi'
                                            : `Reguler · ${pax} Orang`}
                                        )
                                    </span>
                                    <span className="font-mono font-bold text-text">
                                        Rp {Number(ticketTotal).toLocaleString('id-ID')}
                                    </span>
                                </div>

                                {Object.values(snacksCart).map((entry) => (
                                    <div key={entry.item.id} className="flex justify-between items-center text-xs">
                                        <span className="text-text-muted">
                                            {entry.item.name} × {entry.qty}
                                        </span>
                                        <span className="font-mono font-bold text-text">
                                            Rp {Number((entry.item.price || 0) * entry.qty).toLocaleString('id-ID')}
                                        </span>
                                    </div>
                                ))}

                                <div className="pt-3 border-t border-border flex justify-between items-baseline">
                                    <span className="text-sm font-black text-text">Total Pembayaran</span>
                                    <span className="font-mono text-2xl font-black text-amber-600 dark:text-amber-400">
                                        Rp {Number(grandTotal).toLocaleString('id-ID')}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* PILIHAN METODE PEMBAYARAN */}
                        <div className="space-y-3.5">
                            <div className="flex items-baseline justify-between border-b border-border/60 pb-2">
                                <h2 className="text-base font-black text-text tracking-tight flex items-center gap-2">
                                    <CreditCard className="size-4 text-amber-500" />
                                    <span>Pilih Metode Pembayaran</span>
                                </h2>
                                <span className="text-xs text-text-muted">Pilih satu opsi pembayaran</span>
                            </div>

                            <div className="space-y-2.5">
                                {activePaymentMethods.map((method) => {
                                    const isMethodPicked = paymentMethod === method;
                                    const meta =
                                        method === 'qris'
                                            ? {
                                                  icon: <QrCode className="size-5" />,
                                                  title: 'QRIS Instan',
                                                  badge: 'Otomatis',
                                                  desc: 'BCA, Mandiri, GoPay, OVO, ShopeePay, Dana, dll.',
                                              }
                                            : method === 'transfer'
                                            ? {
                                                  icon: <Building2 className="size-5" />,
                                                  title: 'Transfer Bank Manual',
                                                  badge: 'Rekening',
                                                  desc: 'Transfer ke rekening resmi bioskop & unggah bukti.',
                                              }
                                            : {
                                                  icon: <DollarSign className="size-5" />,
                                                  title: 'Bayar di Kasir Studio (Walk-in)',
                                                  badge: 'Tunai / Kasir',
                                                  desc: 'Bayar langsung di kasir studio sebelum penayangan dimulai.',
                                              };

                                    return (
                                        <div
                                            key={method}
                                            role="radio"
                                            tabIndex={0}
                                            aria-checked={isMethodPicked}
                                            onClick={() => setPaymentMethod(method)}
                                            onKeyDown={(e) => {
                                                if (e.key === ' ' || e.key === 'Enter') {
                                                    e.preventDefault();
                                                    setPaymentMethod(method);
                                                }
                                            }}
                                            className={`group relative flex items-start gap-3.5 p-4 rounded-2xl border cursor-pointer select-none transition-all ${
                                                isMethodPicked
                                                    ? 'border-amber-500 bg-amber-500/[0.08] dark:bg-amber-500/10 shadow-sm ring-1 ring-amber-500/30'
                                                    : 'border-border bg-surface hover:bg-surface-2 hover:border-border/80'
                                            }`}
                                        >
                                            <input
                                                type="radio"
                                                name="payment_method"
                                                value={method}
                                                checked={isMethodPicked}
                                                onChange={() => setPaymentMethod(method)}
                                                className="sr-only"
                                            />
                                            <div
                                                className={`mt-0.5 flex size-11 shrink-0 items-center justify-center rounded-2xl transition ${
                                                    isMethodPicked
                                                        ? 'bg-amber-500 text-black shadow-xs font-black'
                                                        : 'bg-surface-2 text-text-muted group-hover:text-text'
                                                }`}
                                            >
                                                {meta.icon}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center gap-2 flex-wrap">
                                                    <span className="text-sm font-bold text-text leading-tight">{meta.title}</span>
                                                    <span className="inline-flex items-center rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400">
                                                        {meta.badge}
                                                    </span>
                                                </div>
                                                <p className="mt-1 text-xs text-text-muted leading-relaxed">{meta.desc}</p>
                                            </div>
                                            <div className="shrink-0 pt-0.5">
                                                <div
                                                    className={`flex size-6 items-center justify-center rounded-full border-2 transition ${
                                                        isMethodPicked
                                                            ? 'border-amber-500 bg-amber-500 text-black shadow-xs'
                                                            : 'border-border bg-surface'
                                                    }`}
                                                >
                                                    {isMethodPicked && <Check className="size-3.5 stroke-[3]" />}
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Submit Actions */}
                        <div className="pt-4 border-t border-border flex flex-col sm:flex-row justify-between items-center gap-3">
                            <button
                                type="button"
                                onClick={() => setStep(hasSnacks ? 3 : 2)}
                                className="inline-flex min-h-11 items-center gap-1.5 text-xs font-bold text-text-muted hover:text-text cursor-pointer"
                            >
                                <ChevronLeft className="size-4" />
                                <span>{hasSnacks ? 'Kembali ke Menu Jajan' : 'Kembali ke Paket & Film'}</span>
                            </button>
                            <button
                                type="submit"
                                disabled={processing}
                                className="btn-gold-cinema inline-flex min-h-12 w-full sm:w-auto items-center justify-center gap-2 rounded-2xl px-8 text-sm font-black shadow-lg shadow-amber-500/25 disabled:opacity-40 cursor-pointer active:scale-95"
                            >
                                <span>{processing ? 'Memproses Pesanan...' : 'Konfirmasi & Pesan Tiket'}</span>
                                <Check className="size-4" />
                            </button>
                        </div>

                        {/* FLOATING MOBILE CHECKOUT BAR */}
                        <div className="fixed bottom-0 left-0 right-0 z-40 bg-surface/95 backdrop-blur-md border-t border-border p-3.5 sm:hidden shadow-2xl">
                            <div className="flex items-center justify-between gap-3 max-w-3xl mx-auto">
                                <div>
                                    <span className="text-[10px] uppercase font-bold text-text-muted tracking-wider block">
                                        Total Bayar
                                    </span>
                                    <span className="font-mono text-lg font-black text-amber-600 dark:text-amber-400">
                                        Rp {Number(grandTotal).toLocaleString('id-ID')}
                                    </span>
                                </div>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="btn-gold-cinema inline-flex min-h-[46px] items-center justify-center gap-1.5 rounded-xl px-5 text-xs font-black shadow-md disabled:opacity-40"
                                >
                                    <span>{processing ? 'Memproses...' : 'Pesan Tiket'}</span>
                                    <ArrowRight className="size-4" />
                                </button>
                            </div>
                        </div>
                    </form>
                )}
            </div>
        </PublicLayout>
    );
}
