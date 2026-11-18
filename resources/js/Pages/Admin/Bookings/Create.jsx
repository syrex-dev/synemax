import { useState, useMemo } from 'react';
import { Link, Head, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import {
    Ticket,
    Calendar,
    Clock,
    Users,
    Film,
    Search,
    Plus,
    Minus,
    Check,
    X,
    CreditCard,
    DollarSign,
    CheckCircle2,
    Coffee,
    ArrowLeft,
    Printer,
    ExternalLink
} from 'lucide-react';

export default function Create({
    sessions = [],
    selectedSession = null,
    films = [],
    categories = [],
    settings = {},
    today = '',
    snackMaxQty = 10,
    lastBooking = null,
}) {
    // Session state
    const [currentSessionId, setCurrentSessionId] = useState(selectedSession?.id || sessions[0]?.id || null);
    const activeSession = useMemo(() => sessions.find((s) => s.id === currentSessionId) || sessions[0], [currentSessionId, sessions]);

    // Package & Pax state
    const [bookingType, setBookingType] = useState('reguler');
    const [pax, setPax] = useState(1);

    // Film state
    const [filmSearch, setFilmSearch] = useState('');
    const [selectedFilmId, setSelectedFilmId] = useState(activeSession?.film_id || null);

    // Multi-member state (Resolves User Request 5!)
    const [memberSearchQuery, setMemberSearchQuery] = useState('');
    const [selectedMembers, setSelectedMembers] = useState([]);
    const [memberSearching, setMemberSearching] = useState(false);
    const [memberSearchResults, setMemberSearchResults] = useState([]);

    // Customer info
    const [customerName, setCustomerName] = useState('');
    const [whatsapp, setWhatsapp] = useState('');
    const [notes, setNotes] = useState('');

    // Snack orders: { [itemId]: qty }
    const [snackQuantities, setSnackQuantities] = useState({});

    // Payment state
    const [paymentMethod, setPaymentMethod] = useState('di_tempat');
    const [cashReceived, setCashReceived] = useState(0);
    const [markAttended, setMarkAttended] = useState(true);

    // Price calculation
    const unitPrice = settings.startingPrice || 5000;
    const ticketTotal = useMemo(() => {
        if (bookingType === 'private') {
            return settings.privatePrice || 36000;
        }
        if (bookingType === 'couple') {
            return settings.couplePrice || 12000;
        }
        return pax * unitPrice;
    }, [bookingType, pax, unitPrice, settings]);

    const snackTotal = useMemo(() => {
        let sum = 0;
        categories.forEach((cat) => {
            cat.items?.forEach((item) => {
                const qty = snackQuantities[item.id] || 0;
                if (qty > 0) {
                    sum += qty * (item.price || 0);
                }
            });
        });
        return sum;
    }, [snackQuantities, categories]);

    const grandTotal = ticketTotal + snackTotal;
    const changeAmount = Math.max(0, cashReceived - grandTotal);

    // Filter films
    const filteredFilms = useMemo(() => {
        if (!filmSearch.trim()) return films;
        const q = filmSearch.toLowerCase();
        return films.filter((f) => f.title?.toLowerCase().includes(q));
    }, [films, filmSearch]);

    // Search members via AJAX
    const searchMembers = async (q) => {
        if (!q.trim()) {
            setMemberSearchResults([]);
            return;
        }
        setMemberSearching(true);
        try {
            const res = await fetch(`/admin/booking/member-lookup?q=${encodeURIComponent(q)}&session_id=${currentSessionId || ''}`);
            const data = await res.json();
            if (data.found && data.members) {
                setMemberSearchResults(data.members);
            } else {
                setMemberSearchResults([]);
            }
        } catch (e) {
            setMemberSearchResults([]);
        } finally {
            setMemberSearching(false);
        }
    };

    const addMember = (m) => {
        if (selectedMembers.some((item) => item.id === m.id)) return;
        if (selectedMembers.length >= pax) {
            // Replace last if already at capacity or allow up to pax
            setSelectedMembers([...selectedMembers.slice(0, pax - 1), m]);
        } else {
            setSelectedMembers([...selectedMembers, m]);
        }
        // Prefill name if empty
        if (!customerName) {
            setCustomerName(m.name);
        }
        setMemberSearchQuery('');
        setMemberSearchResults([]);
    };

    const removeMember = (id) => {
        setSelectedMembers(selectedMembers.filter((m) => m.id !== id));
    };

    const updateSnackQty = (itemId, delta) => {
        const cur = snackQuantities[itemId] || 0;
        const next = Math.max(0, Math.min(snackMaxQty, cur + delta));
        setSnackQuantities({ ...snackQuantities, [itemId]: next });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const payload = {
            session_id: currentSessionId,
            package: bookingType,
            pax: pax,
            film_id: selectedFilmId,
            customer_name: customerName || (selectedMembers[0]?.name || 'Penonton Walk-in'),
            whatsapp: whatsapp || null,
            member_codes: selectedMembers.map((m) => m.code),
            payment_method: paymentMethod,
            cash_received: cashReceived,
            mark_as_attended: markAttended,
            notes: notes || null,
            snacks: Object.entries(snackQuantities)
                .filter(([_, qty]) => qty > 0)
                .map(([itemId, qty]) => ({ id: itemId, qty })),
        };

        router.post('/admin/booking/pesan-di-tempat', payload, {
            preserveScroll: true,
            onSuccess: () => {
                setSnackQuantities({});
                setSelectedMembers([]);
                setCashReceived(0);
                setNotes('');
            },
        });
    };

    return (
        <AdminLayout title="Loket Pemesanan di Tempat (Walk-in)">
            <Head title="Loket Walk-in · Panel Admin" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-bold text-text-muted">
                            <Link href="/admin/booking" className="hover:text-text">Booking</Link>
                            <span>/</span>
                            <span className="text-amber-500">Loket Pemesanan Walk-in</span>
                        </div>
                        <h1 className="mt-1 text-2xl sm:text-3xl font-black text-text tracking-tight flex items-center gap-2.5">
                            <Ticket className="size-6 text-amber-500" />
                            <span>Loket Pemesanan di Tempat</span>
                        </h1>
                        <p className="mt-1 text-xs sm:text-sm text-text-muted">
                            Kasir & pemesanan langsung untuk penonton walk-in tanpa ponsel atau akun web.
                        </p>
                    </div>

                    <Link
                        href="/admin/booking"
                        className="inline-flex min-h-11 items-center gap-2 rounded-xl border border-border bg-surface px-4 text-xs font-bold text-text hover:bg-surface-2 transition shadow-xs"
                    >
                        <ArrowLeft className="size-4" />
                        <span>Daftar Booking</span>
                    </Link>
                </div>

                {/* Banner Transaksi Sukses Terakhir */}
                {lastBooking && (
                    <div className="rounded-2xl border border-emerald-500/40 bg-emerald-500/10 p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div className="flex items-center gap-3.5">
                            <div className="size-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0">
                                <CheckCircle2 className="size-6" />
                            </div>
                            <div>
                                <div className="flex items-center gap-2">
                                    <span className="text-xs font-black uppercase text-emerald-600 dark:text-emerald-400">
                                        Transaksi Berhasil
                                    </span>
                                    <span className="font-mono text-xs font-bold px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-700 dark:text-emerald-300">
                                        {lastBooking.code}
                                    </span>
                                </div>
                                <p className="text-sm font-bold text-text mt-0.5">
                                    Pemesan: {lastBooking.customer_name} · Total: {lastBooking.total_price_formatted}
                                    {lastBooking.change_amount > 0 && (
                                        <span className="text-emerald-500 ml-2 font-black">
                                            (Kembalian: {lastBooking.change_amount_formatted})
                                        </span>
                                    )}
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-2 self-end sm:self-center">
                            <a
                                href={lastBooking.ticket_url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="btn-gold-cinema px-4 py-2 text-xs font-bold rounded-xl shadow-xs inline-flex items-center gap-1.5"
                            >
                                <Printer className="size-3.5" />
                                <span>Cetak Tiket</span>
                            </a>
                        </div>
                    </div>
                )}

                {/* Main POS 2-Column Grid */}
                <form onSubmit={handleSubmit} className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left Form: Sections 1 to 5 */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* 1. Sesi & Jadwal */}
                        <section className="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-xs space-y-4">
                            <div className="flex items-center justify-between border-b border-border/80 pb-3">
                                <h2 className="text-sm font-black uppercase tracking-wider text-amber-500 flex items-center gap-2">
                                    <Calendar className="size-4.5" />
                                    <span>1. Pilih Sesi Penayangan</span>
                                </h2>
                                <span className="text-xs font-bold text-text-muted">
                                    {sessions.length} Sesi Terdaftar
                                </span>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3.5 max-h-[380px] overflow-y-auto p-1 pr-1.5 scrollbar-thin">
                                {sessions.map((s) => {
                                    const isSelected = currentSessionId === s.id;
                                    const remSeats = s.capacity - (s.occupied_bookings?.reduce((acc, b) => acc + (b.seats_reserved || 0), 0) || 0);

                                    return (
                                        <button
                                            key={s.id}
                                            type="button"
                                            onClick={() => {
                                                setCurrentSessionId(s.id);
                                                if (s.film_id) setSelectedFilmId(s.film_id);
                                            }}
                                            className={`p-4 rounded-2xl border-2 text-left transition-all relative group flex flex-col justify-between min-h-[115px] ${
                                                isSelected
                                                    ? 'border-amber-500 bg-amber-500/15 shadow-md ring-2 ring-amber-500/20'
                                                    : 'border-border bg-surface-2/70 hover:bg-surface-2 hover:border-amber-500/50'
                                            }`}
                                        >
                                            <div>
                                                <div className="flex items-center justify-between gap-1">
                                                    <span className="text-base sm:text-lg font-black text-text font-mono tracking-tight">
                                                        {s.start_time?.slice(0, 5)} WIB
                                                    </span>
                                                    {isSelected && (
                                                        <span className="size-5 rounded-full bg-amber-500 text-slate-950 flex items-center justify-center shrink-0">
                                                            <Check className="size-3 stroke-[3]" />
                                                        </span>
                                                    )}
                                                </div>

                                                <div className="text-xs font-medium text-text-muted mt-1">
                                                    {s.session_date}
                                                </div>

                                                {s.film?.title && (
                                                    <div className="text-xs font-bold text-amber-600 dark:text-amber-400 truncate mt-1 flex items-center gap-1">
                                                        <Film className="size-3 shrink-0" />
                                                        <span className="truncate">{s.film.title}</span>
                                                    </div>
                                                )}
                                            </div>

                                            <div className="mt-3 pt-2 border-t border-border/50 flex items-center justify-between">
                                                <span className={`inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold ${
                                                    remSeats <= 2
                                                        ? 'bg-rose-500/15 text-rose-600 dark:text-rose-400'
                                                        : 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
                                                }`}>
                                                    Sisa {remSeats} Kursi
                                                </span>
                                                <span className="text-[10px] text-text-muted">
                                                    Kap. {s.capacity}
                                                </span>
                                            </div>
                                        </button>
                                    );
                                })}
                            </div>
                        </section>

                        {/* 2. Paket & Pax */}
                        <section className="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-xs space-y-4">
                            <h2 className="text-sm font-black uppercase tracking-wider text-amber-500 flex items-center gap-2 border-b border-border/80 pb-3">
                                <Users className="size-4.5" />
                                <span>2. Tipe Paket & Jumlah Orang</span>
                            </h2>

                            <div className="grid grid-cols-3 gap-3">
                                {[
                                    { key: 'reguler', label: 'Reguler', desc: 'Per orang' },
                                    { key: 'couple', label: 'Couple', desc: 'Paket 2 Orang' },
                                    { key: 'private', label: 'Privat', desc: 'Sewa Penuh' },
                                ].map((pkg) => (
                                    <button
                                        key={pkg.key}
                                        type="button"
                                        onClick={() => {
                                            setBookingType(pkg.key);
                                            if (pkg.key === 'couple') setPax(2);
                                            if (pkg.key === 'private') setPax(6);
                                        }}
                                        className={`p-3.5 sm:p-4 rounded-2xl border-2 text-center transition-all ${
                                            bookingType === pkg.key
                                                ? 'border-amber-500 bg-amber-500/15 text-text font-black shadow-xs ring-2 ring-amber-500/20'
                                                : 'border-border bg-surface-2/60 text-text-muted hover:bg-surface-2 hover:border-amber-500/40'
                                        }`}
                                    >
                                        <div className="text-sm font-black">{pkg.label}</div>
                                        <div className="text-xs text-text-muted mt-1">{pkg.desc}</div>
                                    </button>
                                ))}
                            </div>

                            {bookingType === 'reguler' && (
                                <div className="pt-2">
                                    <label className="text-xs font-bold text-text block mb-2.5">Jumlah Orang (Anak / Dewasa):</label>
                                    <div className="flex flex-wrap gap-2.5">
                                        {[1, 2, 3, 4, 5, 6].map((num) => (
                                            <button
                                                key={num}
                                                type="button"
                                                onClick={() => setPax(num)}
                                                className={`size-11 sm:size-12 rounded-xl border-2 font-mono font-black text-sm transition ${
                                                    pax === num
                                                        ? 'border-amber-500 bg-amber-500 text-slate-950 font-black shadow-sm ring-2 ring-amber-500/20'
                                                        : 'border-border bg-surface-2 text-text hover:border-amber-500/50'
                                                }`}
                                            >
                                                {num} Org
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </section>

                        {/* 3. Pilihan Film */}
                        <section className="rounded-2xl border border-border bg-surface p-5 shadow-xs space-y-3">
                            <div className="flex items-center justify-between">
                                <h2 className="text-xs font-black uppercase tracking-wider text-amber-500 flex items-center gap-2">
                                    <Film className="size-4" />
                                    <span>3. Pilihan Judul Film</span>
                                </h2>
                                <input
                                    type="search"
                                    value={filmSearch}
                                    onChange={(e) => setFilmSearch(e.target.value)}
                                    placeholder="Cari judul film..."
                                    className="input-cinema text-xs py-1.5 px-3 rounded-xl w-48"
                                />
                            </div>

                            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 max-h-56 overflow-y-auto p-1">
                                {filteredFilms.map((film) => {
                                    const isSelected = selectedFilmId === film.id;
                                    const posterUrl = film.poster_path ? `/storage/${film.poster_path}` : null;

                                    return (
                                        <button
                                            key={film.id}
                                            type="button"
                                            onClick={() => setSelectedFilmId(film.id)}
                                            className={`rounded-xl border p-2 text-left transition-all ${
                                                isSelected
                                                    ? 'border-amber-500 bg-amber-500/10 shadow-xs'
                                                    : 'border-border bg-surface-2/60 hover:border-amber-500/40'
                                            }`}
                                        >
                                            <div className="aspect-[3/4] rounded-lg bg-slate-950 overflow-hidden mb-2">
                                                {posterUrl ? (
                                                    <img src={posterUrl} alt={film.title} className="size-full object-cover" />
                                                ) : (
                                                    <div className="size-full flex items-center justify-center text-text-muted text-[10px]">
                                                        No Poster
                                                    </div>
                                                )}
                                            </div>
                                            <div className="text-xs font-bold text-text line-clamp-1">{film.title}</div>
                                            <div className="text-[10px] text-text-muted">{film.duration_minutes} mnt · {film.age_rating}</div>
                                        </button>
                                    );
                                })}
                            </div>
                        </section>

                        {/* 4. Data Penonton & MULTI-MEMBER Support (Resolves User Request 5!) */}
                        <section className="rounded-2xl border border-border bg-surface p-5 shadow-xs space-y-4">
                            <div className="flex items-center justify-between border-b border-border pb-2">
                                <h2 className="text-xs font-black uppercase tracking-wider text-amber-500 flex items-center gap-2">
                                    <CreditCard className="size-4" />
                                    <span>4. Kartu Member & Data Penonton</span>
                                </h2>
                                <span className="text-[11px] font-bold text-text-muted">
                                    Terpilih: {selectedMembers.length} / {pax} Member
                                </span>
                            </div>

                            {/* Member Search Input */}
                            <div className="space-y-2">
                                <label className="text-xs font-bold text-text block">
                                    Cari & Tambah Kartu Member (Scan Barcode / ID / Nama):
                                </label>
                                <div className="flex gap-2">
                                    <input
                                        type="text"
                                        value={memberSearchQuery}
                                        onChange={(e) => {
                                            setMemberSearchQuery(e.target.value);
                                            searchMembers(e.target.value);
                                        }}
                                        placeholder="Ketik ID atau nama member..."
                                        className="input-cinema flex-1 text-xs py-2 px-3 rounded-xl"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => searchMembers(memberSearchQuery)}
                                        className="btn-gold-cinema px-4 py-2 text-xs font-bold rounded-xl"
                                    >
                                        Cari
                                    </button>
                                </div>

                                {/* Search Results Dropdown */}
                                {memberSearchResults.length > 0 && (
                                    <div className="rounded-xl border border-border bg-surface p-2 shadow-lg space-y-1 max-h-40 overflow-y-auto">
                                        {memberSearchResults.map((m) => (
                                            <button
                                                key={m.id}
                                                type="button"
                                                onClick={() => addMember(m)}
                                                className="w-full flex items-center justify-between p-2 rounded-lg hover:bg-surface-2 text-xs text-left transition"
                                            >
                                                <div className="font-bold text-text">
                                                    {m.name} <span className="font-mono text-amber-500 text-[11px]">({m.code})</span>
                                                </div>
                                                <span className="text-emerald-500 font-bold text-[11px]">+ Pilih</span>
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>

                            {/* Selected Members Chips List (Supports up to PAX members!) */}
                            {selectedMembers.length > 0 && (
                                <div className="space-y-2 pt-2">
                                    <label className="text-xs font-bold text-text block">
                                        Member Yang Mendapatkan Stempel Kunjungan:
                                    </label>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        {selectedMembers.map((m, index) => (
                                            <div
                                                key={m.id}
                                                className="flex items-center justify-between p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-xs"
                                            >
                                                <div className="flex items-center gap-2.5">
                                                    <span className="size-6 rounded-full bg-amber-500 text-slate-950 font-black text-[10px] flex items-center justify-center">
                                                        {index + 1}
                                                    </span>
                                                    <div>
                                                        <div className="font-bold text-text">{m.name}</div>
                                                        <div className="font-mono text-[10px] text-amber-500">{m.code}</div>
                                                    </div>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={() => removeMember(m.id)}
                                                    className="p-1 rounded-md text-text-muted hover:text-rose-500 transition"
                                                    title="Lepas member"
                                                >
                                                    <X className="size-3.5" />
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Customer Name & WhatsApp */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                                <div className="space-y-1">
                                    <label className="text-xs font-bold text-text">Nama Penonton / Pemesan *</label>
                                    <input
                                        type="text"
                                        required
                                        value={customerName}
                                        onChange={(e) => setCustomerName(e.target.value)}
                                        placeholder="Contoh: Rina / Rombongan Joko"
                                        className="input-cinema w-full text-xs py-2 px-3 rounded-xl"
                                    />
                                </div>
                                <div className="space-y-1">
                                    <label className="text-xs font-bold text-text">Nomor WhatsApp (Opsional)</label>
                                    <input
                                        type="tel"
                                        value={whatsapp}
                                        onChange={(e) => setWhatsapp(e.target.value)}
                                        placeholder="081234567890"
                                        className="input-cinema w-full text-xs py-2 px-3 rounded-xl"
                                    />
                                </div>
                            </div>
                        </section>

                        {/* 5. Jajan & Camilan */}
                        {categories.length > 0 && (
                            <section className="rounded-2xl border border-border bg-surface p-5 shadow-xs space-y-3">
                                <h2 className="text-xs font-black uppercase tracking-wider text-amber-500 flex items-center gap-2">
                                    <Coffee className="size-4" />
                                    <span>5. Tambah Jajanan & Minuman</span>
                                </h2>

                                <div className="space-y-4 max-h-60 overflow-y-auto p-1">
                                    {categories.map((cat) => (
                                        <div key={cat.id} className="space-y-2">
                                            <h3 className="text-xs font-bold text-text-muted">{cat.name}</h3>
                                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                {cat.items?.map((item) => {
                                                    const qty = snackQuantities[item.id] || 0;
                                                    return (
                                                        <div
                                                            key={item.id}
                                                            className="flex items-center justify-between p-2.5 rounded-xl border border-border bg-surface-2/60 text-xs"
                                                        >
                                                            <div>
                                                                <div className="font-bold text-text">{item.name}</div>
                                                                <div className="font-mono text-[11px] text-amber-500">
                                                                    Rp {item.price?.toLocaleString('id-ID')}
                                                                </div>
                                                            </div>
                                                            <div className="flex items-center gap-2">
                                                                <button
                                                                    type="button"
                                                                    onClick={() => updateSnackQty(item.id, -1)}
                                                                    className="size-7 rounded-lg border border-border bg-surface flex items-center justify-center text-text hover:bg-surface-2 transition"
                                                                >
                                                                    <Minus className="size-3" />
                                                                </button>
                                                                <span className="font-mono font-bold w-4 text-center">{qty}</span>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => updateSnackQty(item.id, 1)}
                                                                    className="size-7 rounded-lg border border-border bg-surface flex items-center justify-center text-text hover:bg-surface-2 transition"
                                                                >
                                                                    <Plus className="size-3" />
                                                                </button>
                                                            </div>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </section>
                        )}
                    </div>

                    {/* Right Summary & Cashier Panel */}
                    <div className="space-y-6">
                        <div className="sticky top-20 rounded-3xl border border-border bg-surface p-6 shadow-xl space-y-5">
                            <h2 className="text-sm font-black text-text border-b border-border pb-3">
                                Ringkasan Pembayaran Loket
                            </h2>

                            <div className="space-y-2.5 text-xs">
                                <div className="flex justify-between text-text-muted">
                                    <span>Subtotal Tiket ({pax} Orang):</span>
                                    <span className="font-mono font-bold text-text">
                                        Rp {ticketTotal.toLocaleString('id-ID')}
                                    </span>
                                </div>
                                <div className="flex justify-between text-text-muted">
                                    <span>Subtotal Jajan:</span>
                                    <span className="font-mono font-bold text-text">
                                        Rp {snackTotal.toLocaleString('id-ID')}
                                    </span>
                                </div>
                                <div className="flex justify-between items-baseline pt-3 border-t border-border">
                                    <span className="text-sm font-black text-text">TOTAL BAYAR:</span>
                                    <span className="text-xl font-black text-amber-500 font-mono">
                                        Rp {grandTotal.toLocaleString('id-ID')}
                                    </span>
                                </div>
                            </div>

                            {/* Payment Method Selector */}
                            <div className="space-y-2 pt-2">
                                <label className="text-xs font-bold text-text block">Metode Pembayaran:</label>
                                <div className="grid grid-cols-3 gap-2">
                                    {['di_tempat', 'qris', 'transfer'].map((m) => (
                                        <button
                                            key={m}
                                            type="button"
                                            onClick={() => setPaymentMethod(m)}
                                            className={`p-2 rounded-xl border text-xs font-bold capitalize transition ${
                                                paymentMethod === m
                                                    ? 'border-amber-500 bg-amber-500/15 text-amber-500'
                                                    : 'border-border bg-surface-2 text-text-muted hover:text-text'
                                            }`}
                                        >
                                            {m === 'di_tempat' ? 'Tunai' : m}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {/* Cash Input & Quick Cash Buttons */}
                            {paymentMethod === 'di_tempat' && (
                                <div className="space-y-2 pt-2">
                                    <label className="text-xs font-bold text-text block">Uang Tunai Diterima:</label>
                                    <div className="grid grid-cols-3 gap-1.5">
                                        {[5000, 10000, 20000, 50000, 100000].map((amt) => (
                                            <button
                                                key={amt}
                                                type="button"
                                                onClick={() => setCashReceived(amt)}
                                                className="py-1 px-2 rounded-lg border border-border bg-surface-2 text-[11px] font-mono hover:border-amber-500 transition"
                                            >
                                                {amt / 1000}k
                                            </button>
                                        ))}
                                        <button
                                            type="button"
                                            onClick={() => setCashReceived(grandTotal)}
                                            className="py-1 px-2 rounded-lg border border-amber-500/40 bg-amber-500/10 text-amber-500 text-[11px] font-bold"
                                        >
                                            Uang Pas
                                        </button>
                                    </div>
                                    <input
                                        type="number"
                                        value={cashReceived || ''}
                                        onChange={(e) => setCashReceived(Number(e.target.value))}
                                        placeholder="Rp 0"
                                        className="input-cinema w-full text-sm font-mono py-2 px-3 rounded-xl mt-1"
                                    />
                                    {cashReceived > 0 && (
                                        <div className="flex justify-between items-center text-xs font-bold pt-1">
                                            <span className="text-text-muted">Kembalian:</span>
                                            <span className={`font-mono ${changeAmount >= 0 ? 'text-emerald-500 font-black' : 'text-rose-500'}`}>
                                                Rp {changeAmount.toLocaleString('id-ID')}
                                            </span>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Options */}
                            <div className="pt-2">
                                <label className="flex items-center gap-2 cursor-pointer text-xs font-bold text-text">
                                    <input
                                        type="checkbox"
                                        checked={markAttended}
                                        onChange={(e) => setMarkAttended(e.target.checked)}
                                        className="rounded border-border text-amber-500 focus:ring-amber-500"
                                    />
                                    <span>Langsung Masuk Studio (Tandai Hadir)</span>
                                </label>
                            </div>

                            {/* Submit Button */}
                            <button
                                type="submit"
                                className="btn-gold-cinema w-full py-3 rounded-xl text-xs sm:text-sm font-black shadow-lg shadow-amber-500/20"
                            >
                                Simpan Transaksi Loket
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}

