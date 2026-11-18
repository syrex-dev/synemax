import { useState, useEffect, useRef } from 'react';
import { Link, Head, useForm, router } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import Modal from '../../../Components/Modal';
import CinemaImage from '../../../Components/CinemaImage';
import {
    Calendar,
    Clock,
    Film as FilmIcon,
    Users,
    Coffee,
    CheckCircle2,
    Check,
    AlertCircle,
    ChevronLeft,
    ChevronRight,
    Plus,
    Minus,
    ArrowRight,
    Sparkles,
    CreditCard,
    DollarSign,
    ShieldCheck,
    X,
    Info,
    Search,
    Play,
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
    const lockedFilm = session.film;
    const remainingSeats = session.remaining_seats ?? (session.capacity - occupiedSeats);

    // Initial selected film
    const requestedFilmId = new URLSearchParams(window.location.search).get('film');
    const initialFilm = session.film || (requestedFilmId ? films.find((f) => String(f.id) === String(requestedFilmId)) : null) || films[0];

    // Wizard step: 1 (Jadwal), 2 (Paket & Film), 3 (Jajan), 4 (Ringkasan)
    const [step, setStep] = useState(2);
    const [selectedPackage, setSelectedPackage] = useState(canReguler ? 'reguler' : canCouple ? 'couple' : 'private');
    const [pax, setPax] = useState(1);
    const [selectedFilmId, setSelectedFilmId] = useState(initialFilm ? String(initialFilm.id) : '');
    const [customerName, setCustomerName] = useState('');
    const [whatsapp, setWhatsapp] = useState('');
    const [paymentMethod, setPaymentMethod] = useState(paymentMethods[0] || 'di_tempat');
    const [snacksCart, setSnacksCart] = useState({}); // { snackId: { qty, note, item } }
    const [activeSnackCategory, setActiveSnackCategory] = useState(snackCategories[0]?.id || null);

    // Film search/filter in modal or list
    const [filmSearch, setFilmSearch] = useState('');
    const [trailerModalFilm, setTrailerModalFilm] = useState(null);
    const [unpaidModalOpen, setUnpaidModalOpen] = useState(Boolean(existingUnpaidBooking));

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

    // Update form data when state changes
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

    const filteredFilms = films.filter((f) => {
        if (!filmSearch.trim()) return true;
        const q = filmSearch.toLowerCase();
        return f.title?.toLowerCase().includes(q) || f.genre?.toLowerCase().includes(q);
    });

    const activeFilm = films.find((f) => String(f.id) === String(selectedFilmId)) || initialFilm;

    return (
        <PublicLayout title="Pesan Tiket Studio">
            <Head title="Pesan Tiket Studio">
                <meta
                    name="description"
                    content="Pilih paket, film calon/terkunci, suasana studio, dan jajan untuk sesi studio mini SynemaX dengan standar bioskop modern."
                />
            </Head>

            <div className="mx-auto max-w-xl px-4 pb-8 pt-4 sm:px-6 sm:pb-12 sm:pt-6">
                {/* Modal Notifikasi Unpaid Booking Sebelumnya */}
                {unpaidModalOpen && existingUnpaidBooking && (
                    <Modal show={unpaidModalOpen} onClose={() => setUnpaidModalOpen(false)} maxWidth="md">
                        <div className="p-6 space-y-4">
                            <div className="flex items-start gap-3">
                                <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                    <AlertCircle className="size-5" />
                                </div>
                                <div>
                                    <h3 className="text-sm font-bold text-text">Kamu masih punya booking yang belum dibayar</h3>
                                    <p className="mt-1 text-xs text-text-muted leading-relaxed">
                                        Booking <strong className="text-text font-mono">{existingUnpaidBooking.code}</strong> masih aktif menahan tempatmu.
                                    </p>
                                </div>
                            </div>

                            <div className="rounded-xl border border-border bg-surface-2 p-3.5 text-xs space-y-1">
                                <div className="flex justify-between">
                                    <span className="text-text-muted">Total Tagihan:</span>
                                    <span className="font-bold text-text">
                                        Rp {Number(existingUnpaidBooking.total_price || 0).toLocaleString('id-ID')}
                                    </span>
                                </div>
                            </div>

                            <div className="space-y-2 pt-2">
                                <Link
                                    href={`/pembayaran/${existingUnpaidBooking.code}`}
                                    className="btn-gold-cinema flex w-full min-h-[44px] items-center justify-center rounded-xl text-xs font-bold shadow-md"
                                >
                                    Lanjutkan Pembayaran
                                </Link>
                                <button
                                    type="button"
                                    onClick={() => setUnpaidModalOpen(false)}
                                    className="flex w-full min-h-[40px] items-center justify-center rounded-xl border border-border bg-surface px-4 text-xs font-bold text-text hover:bg-surface-2 transition"
                                >
                                    Tutup & Lanjutkan Pesan Baru
                                </button>
                            </div>
                        </div>
                    </Modal>
                )}

                {/* Header & Sesi Yang Dipilih */}
                <div className="flex flex-wrap items-center justify-between gap-3 border-b border-border pb-4">
                    <div>
                        <Link
                            href={`/jadwal/${session.session_date}`}
                            className="inline-flex min-h-11 items-center gap-1.5 text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline"
                        >
                            <ChevronLeft className="size-4" />
                            <span>Ganti jadwal</span>
                        </Link>
                        <h1 className="mt-1 text-2xl font-black tracking-tight text-text sm:text-3xl">Amankan tempatmu</h1>
                    </div>
                    <div className="rounded-xl border border-border bg-surface-2 px-3 py-2 text-right">
                        <span className="text-[10px] font-bold uppercase tracking-wider text-text-muted">Sisa Kursi</span>
                        <p className="font-mono text-base font-black text-amber-600 dark:text-amber-400">
                            {remainingSeats} / {session.capacity}
                        </p>
                    </div>
                </div>

                {/* Info Jadwal Mini Banner */}
                <div className="mt-4 flex items-center justify-between rounded-2xl border border-border bg-surface p-4 shadow-xs">
                    <div className="flex items-center gap-3">
                        <span className="flex size-10 items-center justify-center rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-400/30">
                            <Clock className="size-5" />
                        </span>
                        <div>
                            <p className="text-xs font-bold text-text">{formatDateId(session.session_date)}</p>
                            <p className="font-mono text-sm font-black text-amber-600 dark:text-amber-400">
                                {session.start_time?.slice(0, 5)} - {session.end_time?.slice(0, 5)} WIB
                            </p>
                        </div>
                    </div>
                    {isConfirmed && (
                        <span className="rounded-lg bg-emerald-500/15 border border-emerald-500/30 px-2.5 py-1 text-[10px] font-bold uppercase text-emerald-600 dark:text-emerald-400">
                            Film Terkunci
                        </span>
                    )}
                </div>

                {/* Error Banner */}
                {Object.keys(errors).length > 0 && (
                    <div className="mt-4 rounded-xl border border-danger/40 bg-danger-surface p-4 text-xs font-semibold text-danger-text space-y-1">
                        {Object.values(errors).map((err, i) => (
                            <p key={i}>{err}</p>
                        ))}
                    </div>
                )}

                {/* Stepper Wizard Indicator */}
                <div className="mt-6 flex items-center justify-between border-b border-border pb-4 text-xs">
                    <button
                        type="button"
                        onClick={() => setStep(2)}
                        className={`font-black pb-1 border-b-2 transition ${
                            step === 2
                                ? 'border-amber-500 text-amber-600 dark:text-amber-400'
                                : 'border-transparent text-text-muted hover:text-text'
                        }`}
                    >
                        1. Paket & Film
                    </button>
                    {snacksEnabled && (
                        <button
                            type="button"
                            onClick={() => setStep(3)}
                            className={`font-black pb-1 border-b-2 transition ${
                                step === 3
                                    ? 'border-amber-500 text-amber-600 dark:text-amber-400'
                                    : 'border-transparent text-text-muted hover:text-text'
                            }`}
                        >
                            2. Menu Jajan ({Object.keys(snacksCart).length})
                        </button>
                    )}
                    <button
                        type="button"
                        onClick={() => setStep(4)}
                        className={`font-black pb-1 border-b-2 transition ${
                            step === 4
                                ? 'border-amber-500 text-amber-600 dark:text-amber-400'
                                : 'border-transparent text-text-muted hover:text-text'
                        }`}
                    >
                        {snacksEnabled ? '3. Ringkasan & Bayar' : '2. Ringkasan & Bayar'}
                    </button>
                </div>

                {/* ==================== STEP 2: PAKET, FILM, & DATA DIRI ==================== */}
                {step === 2 && (
                    <div className="mt-6 space-y-6">
                        {/* 1. Pilih Paket Nonton */}
                        <div>
                            <h2 className="text-sm font-black uppercase tracking-wider text-text-muted">1. Pilih Paket Nonton</h2>
                            <div className="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                {/* Reguler */}
                                <button
                                    type="button"
                                    onClick={() => handlePackageSelect('reguler')}
                                    disabled={!canReguler}
                                    className={`relative flex flex-col justify-between p-4 rounded-2xl border text-left transition ${
                                        selectedPackage === 'reguler'
                                            ? 'border-amber-500 bg-amber-500/10 shadow-md ring-1 ring-amber-500'
                                            : canReguler
                                            ? 'border-border bg-surface hover:bg-surface-2'
                                            : 'border-border/40 bg-surface-2/40 opacity-40 cursor-not-allowed'
                                    }`}
                                >
                                    <div>
                                        <p className="text-xs font-black text-text uppercase tracking-wide">Reguler</p>
                                        <p className="text-[11px] text-text-muted mt-0.5">Per orang/kursi</p>
                                    </div>
                                    <p className="mt-3 font-mono text-sm font-black text-amber-600 dark:text-amber-400">
                                        Rp {Number(unitPrice).toLocaleString('id-ID')}
                                    </p>
                                    {!canReguler && (
                                        <span className="text-[10px] text-danger-text mt-1">{regulerDisabledReason}</span>
                                    )}
                                </button>

                                {/* Couple */}
                                <button
                                    type="button"
                                    onClick={() => handlePackageSelect('couple')}
                                    disabled={!canCouple}
                                    className={`relative flex flex-col justify-between p-4 rounded-2xl border text-left transition ${
                                        selectedPackage === 'couple'
                                            ? 'border-amber-500 bg-amber-500/10 shadow-md ring-1 ring-amber-500'
                                            : canCouple
                                            ? 'border-border bg-surface hover:bg-surface-2'
                                            : 'border-border/40 bg-surface-2/40 opacity-40 cursor-not-allowed'
                                    }`}
                                >
                                    <div>
                                        <p className="text-xs font-black text-text uppercase tracking-wide">Couple</p>
                                        <p className="text-[11px] text-text-muted mt-0.5">Paket 2 kursi santai</p>
                                    </div>
                                    <p className="mt-3 font-mono text-sm font-black text-amber-600 dark:text-amber-400">
                                        Rp {Number(couplePrice).toLocaleString('id-ID')}
                                    </p>
                                    {!canCouple && (
                                        <span className="text-[10px] text-danger-text mt-1">{coupleDisabledReason}</span>
                                    )}
                                </button>

                                {/* Private */}
                                <button
                                    type="button"
                                    onClick={() => handlePackageSelect('private')}
                                    disabled={!canPrivate}
                                    className={`relative flex flex-col justify-between p-4 rounded-2xl border text-left transition ${
                                        selectedPackage === 'private'
                                            ? 'border-amber-500 bg-amber-500/10 shadow-md ring-1 ring-amber-500'
                                            : canPrivate
                                            ? 'border-border bg-surface hover:bg-surface-2'
                                            : 'border-border/40 bg-surface-2/40 opacity-40 cursor-not-allowed'
                                    }`}
                                >
                                    <div>
                                        <p className="text-xs font-black text-text uppercase tracking-wide">Private Studio</p>
                                        <p className="text-[11px] text-text-muted mt-0.5">Sewa seluruh studio (6 kursi)</p>
                                    </div>
                                    <p className="mt-3 font-mono text-sm font-black text-amber-600 dark:text-amber-400">
                                        Rp {Number(privatePrice).toLocaleString('id-ID')}
                                    </p>
                                    {!canPrivate && (
                                        <span className="text-[10px] text-danger-text mt-1">{privateDisabledReason}</span>
                                    )}
                                </button>
                            </div>

                            {/* Pax counter for reguler */}
                            {selectedPackage === 'reguler' && (
                                <div className="mt-3 flex items-center justify-between rounded-xl border border-border bg-surface p-3 text-xs">
                                    <span className="font-bold text-text">Jumlah Tiket:</span>
                                    <div className="flex items-center gap-3">
                                        <button
                                            type="button"
                                            onClick={() => setPax(Math.max(1, pax - 1))}
                                            disabled={pax <= 1}
                                            className="flex size-8 items-center justify-center rounded-lg border border-border bg-surface-2 text-text hover:bg-surface transition disabled:opacity-30"
                                        >
                                            <Minus className="size-3.5" />
                                        </button>
                                        <span className="font-mono text-base font-black w-6 text-center">{pax}</span>
                                        <button
                                            type="button"
                                            onClick={() => setPax(Math.min(remainingSeats, pax + 1))}
                                            disabled={pax >= remainingSeats}
                                            className="flex size-8 items-center justify-center rounded-lg border border-border bg-surface-2 text-text hover:bg-surface transition disabled:opacity-30"
                                        >
                                            <Plus className="size-3.5" />
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* 2. Pilih Film */}
                        <div>
                            <div className="flex items-center justify-between">
                                <h2 className="text-sm font-black uppercase tracking-wider text-text-muted">2. Pilih Film Tayang</h2>
                                {isConfirmed ? (
                                    <span className="text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                                        Terkunci: {lockedFilm?.title}
                                    </span>
                                ) : (
                                    <span className="text-[11px] text-text-muted">Bebas pilih film calon</span>
                                )}
                            </div>

                            {!isConfirmed && (
                                <div className="mt-3 space-y-3">
                                    <div className="relative">
                                        <Search className="size-4 text-text-muted absolute left-3 top-3" />
                                        <input
                                            type="text"
                                            value={filmSearch}
                                            onChange={(e) => setFilmSearch(e.target.value)}
                                            placeholder="Cari judul film atau genre..."
                                            className="w-full rounded-xl border border-border bg-surface pl-9 pr-4 py-2 text-xs text-text placeholder:text-text-muted focus:border-amber-500 focus:outline-none"
                                        />
                                    </div>

                                    <div className="max-h-64 overflow-y-auto space-y-2 pr-1">
                                        {filteredFilms.map((f) => {
                                            const isPicked = String(f.id) === String(selectedFilmId);
                                            return (
                                                <div
                                                    key={f.id}
                                                    onClick={() => setSelectedFilmId(String(f.id))}
                                                    className={`flex items-center justify-between p-3 rounded-2xl border cursor-pointer transition ${
                                                        isPicked
                                                            ? 'border-amber-500 bg-amber-500/10 shadow-xs'
                                                            : 'border-border bg-surface hover:bg-surface-2'
                                                    }`}
                                                >
                                                    <div className="flex items-center gap-3 min-w-0">
                                                        <CinemaImage
                                                            src={f.poster_url || (f.poster_path ? `/storage/${f.poster_path}` : null)}
                                                            alt={f.title}
                                                            title={f.title}
                                                            aspect="aspect-[2/3]"
                                                            className="w-12 shrink-0 rounded-lg"
                                                        />
                                                        <div className="min-w-0 truncate">
                                                            <p className="text-xs font-black text-text truncate">{f.title}</p>
                                                            <p className="text-[11px] text-text-muted mt-0.5">
                                                                {f.genre} · {f.duration_minutes}m · {f.age_rating || 'SU'}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div className="shrink-0 flex items-center gap-2">
                                                        {isPicked && (
                                                            <span className="flex size-5 items-center justify-center rounded-full bg-amber-500 text-black">
                                                                <Check className="size-3 font-bold" />
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}

                            {isConfirmed && lockedFilm && (
                                <div className="mt-3 flex items-center gap-3 p-3.5 rounded-2xl border border-emerald-500/40 bg-emerald-500/10">
                                    <CinemaImage
                                        src={lockedFilm.poster_url || (lockedFilm.poster_path ? `/storage/${lockedFilm.poster_path}` : null)}
                                        alt={lockedFilm.title}
                                        title={lockedFilm.title}
                                        aspect="aspect-[2/3]"
                                        className="w-14 shrink-0 rounded-lg"
                                    />
                                    <div>
                                        <p className="text-xs font-black text-text">{lockedFilm.title}</p>
                                        <p className="text-[11px] text-text-muted mt-0.5">
                                            {lockedFilm.genre} · {lockedFilm.duration_minutes}m · {lockedFilm.age_rating}
                                        </p>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* 3. Data Diri */}
                        <div>
                            <h2 className="text-sm font-black uppercase tracking-wider text-text-muted">3. Data Diri Pemesan</h2>
                            <div className="mt-3 space-y-3">
                                <div>
                                    <label htmlFor="customer_name" className="block text-xs font-bold text-text-muted mb-1">
                                        Nama Lengkap
                                    </label>
                                    <input
                                        id="customer_name"
                                        type="text"
                                        required
                                        value={customerName}
                                        onChange={(e) => setCustomerName(e.target.value)}
                                        placeholder="Contoh: Budi Santoso"
                                        className="w-full rounded-xl border border-border bg-surface px-3.5 py-2.5 text-xs text-text focus:border-amber-500 focus:outline-none"
                                    />
                                </div>
                                <div>
                                    <label htmlFor="whatsapp" className="block text-xs font-bold text-text-muted mb-1">
                                        Nomor WhatsApp (Aktif)
                                    </label>
                                    <input
                                        id="whatsapp"
                                        type="tel"
                                        required
                                        value={whatsapp}
                                        onChange={(e) => setWhatsapp(e.target.value)}
                                        placeholder="Contoh: 081234567890"
                                        className="w-full rounded-xl border border-border bg-surface px-3.5 py-2.5 text-xs text-text focus:border-amber-500 focus:outline-none"
                                    />
                                    <p className="mt-1 text-[11px] text-text-muted">Tiket digital akan dikirimkan dan diverifikasi melalui nomor ini.</p>
                                </div>
                            </div>
                        </div>

                        {/* Action CTA Next */}
                        <div className="pt-4 border-t border-border flex justify-end">
                            <button
                                type="button"
                                onClick={() => setStep(snacksEnabled ? 3 : 4)}
                                disabled={!customerName.trim() || !whatsapp.trim() || !selectedFilmId}
                                className="btn-gold-cinema inline-flex min-h-11 items-center gap-2 rounded-xl px-6 text-xs font-bold shadow-md disabled:opacity-40"
                            >
                                <span>{snacksEnabled ? 'Pilih Jajan' : 'Lanjut ke Pembayaran'}</span>
                                <ArrowRight className="size-4" />
                            </button>
                        </div>
                    </div>
                )}

                {/* ==================== STEP 3: MENU JAJAN ==================== */}
                {step === 3 && snacksEnabled && (
                    <div className="mt-6 space-y-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h2 className="text-sm font-black uppercase tracking-wider text-text-muted">Menu Makanan & Minuman</h2>
                                <p className="text-xs text-text-muted mt-0.5">Pesan jajan sekarang agar siap saat kamu tiba.</p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setStep(4)}
                                className="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline"
                            >
                                Lewati jajan
                            </button>
                        </div>

                        {/* Category tabs */}
                        <div className="flex gap-2 overflow-x-auto pb-2 scrollbar-none">
                            {snackCategories.map((cat) => (
                                <button
                                    key={cat.id}
                                    type="button"
                                    onClick={() => setActiveSnackCategory(cat.id)}
                                    className={`shrink-0 rounded-xl px-3.5 py-1.5 text-xs font-bold transition ${
                                        activeSnackCategory === cat.id
                                            ? 'btn-gold-cinema shadow-xs'
                                            : 'border border-border bg-surface text-text-muted hover:text-text'
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
                                            className="flex items-center justify-between p-3.5 rounded-2xl border border-border bg-surface shadow-xs"
                                        >
                                            <div className="flex items-center gap-3 min-w-0">
                                                <CinemaImage
                                                    src={item.photo_url || (item.photo_path ? `/storage/${item.photo_path}` : null)}
                                                    alt={item.name}
                                                    title={item.name}
                                                    aspect="aspect-square"
                                                    className="size-14 shrink-0 rounded-xl"
                                                />
                                                <div className="min-w-0">
                                                    <p className="text-xs font-bold text-text truncate">{item.name}</p>
                                                    <p className="font-mono text-xs font-bold text-amber-600 dark:text-amber-400 mt-0.5">
                                                        Rp {Number(item.price || 0).toLocaleString('id-ID')}
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-2">
                                                {qty > 0 && (
                                                    <button
                                                        type="button"
                                                        onClick={() => updateSnackQty(item, -1)}
                                                        className="flex size-7 items-center justify-center rounded-lg border border-border bg-surface-2 text-text"
                                                    >
                                                        <Minus className="size-3" />
                                                    </button>
                                                )}
                                                {qty > 0 && <span className="font-mono text-xs font-bold w-4 text-center">{qty}</span>}
                                                <button
                                                    type="button"
                                                    onClick={() => updateSnackQty(item, 1)}
                                                    className="flex size-7 items-center justify-center rounded-lg bg-amber-500 text-black font-bold"
                                                >
                                                    <Plus className="size-3" />
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
                                className="inline-flex min-h-10 items-center gap-1 text-xs font-bold text-text-muted hover:text-text"
                            >
                                <ChevronLeft className="size-4" />
                                <span>Kembali</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => setStep(4)}
                                className="btn-gold-cinema inline-flex min-h-11 items-center gap-2 rounded-xl px-6 text-xs font-bold shadow-md"
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
                        {/* Ringkasan Biaya */}
                        <div className="rounded-2xl border border-border bg-surface-2 p-5 shadow-xs space-y-4">
                            <h2 className="text-sm font-black uppercase tracking-wider text-text">Ringkasan Pesanan</h2>

                            <div className="space-y-2 text-xs border-b border-border pb-3">
                                <div className="flex justify-between text-text-muted">
                                    <span>
                                        Tiket (
                                        {selectedPackage === 'private'
                                            ? 'Private Studio (6 Kursi)'
                                            : selectedPackage === 'couple'
                                            ? 'Paket Couple (2 Kursi)'
                                            : `${pax} Orang`}
                                        )
                                    </span>
                                    <span className="font-mono font-bold text-text">
                                        Rp {Number(ticketTotal).toLocaleString('id-ID')}
                                    </span>
                                </div>

                                {Object.values(snacksCart).map((entry) => (
                                    <div key={entry.item.id} className="flex justify-between text-text-muted">
                                        <span>
                                            {entry.item.name} × {entry.qty}
                                        </span>
                                        <span className="font-mono font-bold text-text">
                                            Rp {Number((entry.item.price || 0) * entry.qty).toLocaleString('id-ID')}
                                        </span>
                                    </div>
                                ))}
                            </div>

                            <div className="flex justify-between items-baseline pt-1">
                                <span className="text-xs font-bold text-text">Total Pembayaran</span>
                                <span className="font-mono text-2xl font-black text-amber-600 dark:text-amber-400">
                                    Rp {Number(grandTotal).toLocaleString('id-ID')}
                                </span>
                            </div>
                        </div>

                        {/* Pilihan Metode Pembayaran */}
                        <div>
                            <h2 className="text-sm font-black uppercase tracking-wider text-text-muted">Metode Pembayaran</h2>
                            <div className="mt-3 space-y-2">
                                {paymentMethods.map((method) => {
                                    const isMethodPicked = paymentMethod === method;
                                    const label =
                                        method === 'qris'
                                            ? 'QRIS Instan (BCA, Mandiri, GoPay, ShopeePay, Dana, dll.)'
                                            : method === 'transfer'
                                            ? 'Transfer Bank Manual'
                                            : 'Bayar Tunai di Kasir Studio (Walk-in)';

                                    return (
                                        <label
                                            key={method}
                                            className={`flex items-center gap-3 p-3.5 rounded-2xl border cursor-pointer transition ${
                                                isMethodPicked
                                                    ? 'border-amber-500 bg-amber-500/10 shadow-xs'
                                                    : 'border-border bg-surface hover:bg-surface-2'
                                            }`}
                                        >
                                            <input
                                                type="radio"
                                                name="payment_method"
                                                value={method}
                                                checked={isMethodPicked}
                                                onChange={() => setPaymentMethod(method)}
                                                className="text-amber-500 focus:ring-amber-500"
                                            />
                                            <span className="text-xs font-bold text-text">{label}</span>
                                        </label>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Submit Button */}
                        <div className="pt-4 border-t border-border flex justify-between items-center">
                            <button
                                type="button"
                                onClick={() => setStep(snacksEnabled ? 3 : 2)}
                                className="inline-flex min-h-10 items-center gap-1 text-xs font-bold text-text-muted hover:text-text"
                            >
                                <ChevronLeft className="size-4" />
                                <span>Kembali</span>
                            </button>
                            <button
                                type="submit"
                                disabled={processing}
                                className="btn-gold-cinema inline-flex min-h-12 items-center justify-center gap-2 rounded-xl px-7 text-sm font-black shadow-lg disabled:opacity-40"
                            >
                                <span>{processing ? 'Memproses Pesanan...' : 'Konfirmasi & Pesan Tiket'}</span>
                                <Check className="size-4" />
                            </button>
                        </div>
                    </form>
                )}
            </div>
        </PublicLayout>
    );
}

