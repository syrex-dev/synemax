import { useState, useEffect } from 'react';
import { Link, Head, useForm, router } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import Modal from '../../../Components/Modal';
import { Film as FilmIcon, AlertCircle, CheckCircle, Clock, XCircle, Search } from 'lucide-react';

export default function Index({
    categorizedBookings = {},
    counts = {},
    initialTab = 'aktif',
    oldInput = {},
    errors = {},
    flash = {},
}) {
    const [activeTab, setActiveTab] = useState(initialTab);
    const [cancelModalOpen, setCancelModalOpen] = useState(false);
    const [cancelCode, setCancelCode] = useState('');

    const belumBayar = categorizedBookings.belum_bayar || [];
    const diproses = categorizedBookings.diproses || [];
    const aktif = categorizedBookings.aktif || [];
    const riwayat = categorizedBookings.riwayat || [];

    const tabCounts = {
        belum_bayar: counts.belum_bayar ?? belumBayar.length,
        diproses: counts.diproses ?? diproses.length,
        aktif: counts.aktif ?? aktif.length,
        riwayat: counts.riwayat ?? riwayat.length,
    };

    const { data, setData, post, processing, errors: formErrors, reset } = useForm({
        code: oldInput.code || '',
        whatsapp: oldInput.whatsapp || '',
    });

    const handleLookupSubmit = (e) => {
        e.preventDefault();
        post('/tiket/tambah', {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const handleOpenCancelModal = (code) => {
        setCancelCode(code);
        setCancelModalOpen(true);
    };

    const handleConfirmCancel = () => {
        if (!cancelCode) return;
        router.post(`/tiket/${cancelCode}/batal`, {}, {
            preserveScroll: true,
            onFinish: () => {
                setCancelModalOpen(false);
                setCancelCode('');
            },
        });
    };

    return (
        <PublicLayout title="Tiket Saya">
            <Head>
                <meta
                    name="description"
                    content="Akses tiket bioskop digital, cek status pemesanan, dan selesaikan pembayaran booking di SynemaX."
                />
                <meta name="robots" content="noindex, follow" />
            </Head>

            <div className="mx-auto max-w-[520px] w-full px-4 py-5 sm:py-8">
                {/* Header Halaman Tiket */}
                <header className="mb-6">
                    <h1 className="text-2xl font-black text-text sm:text-3xl tracking-tight">Tiket Saya</h1>
                    <p className="mt-1.5 text-xs sm:text-sm text-text-muted leading-relaxed">
                        Kelola pesanan, selesaikan pembayaran yang tertunda, atau buka tiket digital yang tersimpan pada perangkat ini.
                    </p>
                </header>

                {/* Navigasi 4 Tab */}
                <div className="border-b border-border">
                    <nav className="flex flex-wrap gap-1 sm:gap-2 pb-px" aria-label="Kategori Tiket">
                        <button
                            type="button"
                            onClick={() => setActiveTab('belum_bayar')}
                            className={`whitespace-nowrap px-3 py-2.5 text-xs sm:text-sm font-bold transition-colors ${
                                activeTab === 'belum_bayar'
                                    ? 'border-amber-500 text-amber-600 dark:text-amber-400 font-black border-b-2'
                                    : 'border-transparent text-text-muted hover:text-text'
                            }`}
                        >
                            Belum bayar ({tabCounts.belum_bayar})
                        </button>

                        <button
                            type="button"
                            onClick={() => setActiveTab('diproses')}
                            className={`whitespace-nowrap px-3 py-2.5 text-xs sm:text-sm font-bold transition-colors ${
                                activeTab === 'diproses'
                                    ? 'border-amber-500 text-amber-600 dark:text-amber-400 font-black border-b-2'
                                    : 'border-transparent text-text-muted hover:text-text'
                            }`}
                        >
                            Diproses ({tabCounts.diproses})
                        </button>

                        <button
                            type="button"
                            onClick={() => setActiveTab('aktif')}
                            className={`whitespace-nowrap px-3 py-2.5 text-xs sm:text-sm font-bold transition-colors ${
                                activeTab === 'aktif'
                                    ? 'border-amber-500 text-amber-600 dark:text-amber-400 font-black border-b-2'
                                    : 'border-transparent text-text-muted hover:text-text'
                            }`}
                        >
                            Tiket aktif ({tabCounts.aktif})
                        </button>

                        <button
                            type="button"
                            onClick={() => setActiveTab('riwayat')}
                            className={`whitespace-nowrap px-3 py-2.5 text-xs sm:text-sm font-bold transition-colors ${
                                activeTab === 'riwayat'
                                    ? 'border-amber-500 text-amber-600 dark:text-amber-400 font-black border-b-2'
                                    : 'border-transparent text-text-muted hover:text-text'
                            }`}
                        >
                            Riwayat ({tabCounts.riwayat})
                        </button>
                    </nav>
                </div>

                {/* ==================== TAB 1: BELUM BAYAR ==================== */}
                {activeTab === 'belum_bayar' && (
                    <div className="mt-5 space-y-4">
                        {belumBayar.length > 0 ? (
                            belumBayar.map((b) => (
                                <BookingItemCard
                                    key={b.code}
                                    booking={b}
                                    type="belum_bayar"
                                    onCancel={() => handleOpenCancelModal(b.code)}
                                />
                            ))
                        ) : (
                            <EmptyTabState message="Tidak ada booking yang menunggu pembayaran." subMessage="Semua pesananmu sudah diselesaikan atau sudah kedaluwarsa." />
                        )}
                    </div>
                )}

                {/* ==================== TAB 2: DIPROSES ==================== */}
                {activeTab === 'diproses' && (
                    <div className="mt-5 space-y-4">
                        {diproses.length > 0 ? (
                            diproses.map((b) => (
                                <BookingItemCard
                                    key={b.code}
                                    booking={b}
                                    type="diproses"
                                />
                            ))
                        ) : (
                            <EmptyTabState message="Tidak ada booking yang sedang diproses." subMessage="Bukti bayar yang kamu kirimkan akan diverifikasi oleh admin di sini." />
                        )}
                    </div>
                )}

                {/* ==================== TAB 3: TIKET AKTIF ==================== */}
                {activeTab === 'aktif' && (
                    <div className="mt-5 space-y-4">
                        {aktif.length > 0 ? (
                            aktif.map((b) => (
                                <BookingItemCard
                                    key={b.code}
                                    booking={b}
                                    type="aktif"
                                />
                            ))
                        ) : (
                            <EmptyTabState message="Belum ada tiket aktif." subMessage="Tiket yang sudah terkonfirmasi untuk sesi mendatang akan muncul di sini." />
                        )}
                    </div>
                )}

                {/* ==================== TAB 4: RIWAYAT ==================== */}
                {activeTab === 'riwayat' && (
                    <div className="mt-5 space-y-4">
                        {riwayat.length > 0 ? (
                            riwayat.map((b) => (
                                <BookingItemCard
                                    key={b.code}
                                    booking={b}
                                    type="riwayat"
                                />
                            ))
                        ) : (
                            <EmptyTabState message="Belum ada riwayat pesanan." subMessage="Tiket yang telah selesai ditonton atau dibatalkan akan tersimpan di sini." />
                        )}
                    </div>
                )}

                {/* ==================== FORM TAMBAH TIKET MANUAL ==================== */}
                <section className="mt-10 rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-xs">
                    <h2 className="text-sm font-bold text-text">Tiket tidak muncul?</h2>
                    <p className="mt-1 text-xs text-text-muted leading-relaxed">
                        Tambahkan booking ke perangkat ini dengan memasukkan kode booking dan nomor WhatsApp yang digunakan saat memesan.
                    </p>

                    <form onSubmit={handleLookupSubmit} className="mt-4 space-y-4">
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label htmlFor="lookup_code" className="block text-[11px] font-bold uppercase tracking-wider text-text-muted">
                                    Kode booking
                                </label>
                                <input
                                    id="lookup_code"
                                    type="text"
                                    required
                                    placeholder="Contoh: HC-261103-A7K2"
                                    value={data.code}
                                    onChange={(e) => setData('code', e.target.value.toUpperCase())}
                                    className="mt-1.5 min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 font-tabular text-xs font-bold uppercase text-text focus:border-amber-400 focus:outline-none"
                                />
                                {formErrors.code && (
                                    <p className="mt-1 text-xs text-red-500 font-bold">{formErrors.code}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="lookup_whatsapp" className="block text-[11px] font-bold uppercase tracking-wider text-text-muted">
                                    Nomor WhatsApp
                                </label>
                                <input
                                    id="lookup_whatsapp"
                                    type="tel"
                                    inputMode="numeric"
                                    required
                                    placeholder="Contoh: 081234567890"
                                    value={data.whatsapp}
                                    onChange={(e) => setData('whatsapp', e.target.value)}
                                    className="mt-1.5 min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 font-tabular text-xs font-bold text-text focus:border-amber-400 focus:outline-none"
                                />
                                {formErrors.whatsapp && (
                                    <p className="mt-1 text-xs text-red-500 font-bold">{formErrors.whatsapp}</p>
                                )}
                            </div>
                        </div>

                        <div className="flex justify-end pt-1">
                            <button
                                type="submit"
                                disabled={processing}
                                className="btn-gold-cinema inline-flex min-h-11 items-center justify-center rounded-xl px-4 text-xs font-black shadow-xs disabled:opacity-50"
                            >
                                <Search className="size-3.5 mr-1.5" />
                                <span>{processing ? 'Mencari...' : 'Cari & tambahkan tiket'}</span>
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            {/* Cancel Modal */}
            <Modal show={cancelModalOpen} onClose={() => setCancelModalOpen(false)} maxWidth="sm">
                <div className="p-6">
                    <h3 className="text-base font-black text-text dark:text-white">Batalkan booking ini?</h3>
                    <p className="mt-2 text-xs leading-relaxed text-text-muted">
                        Apakah kamu yakin ingin membatalkan booking <strong className="text-text font-black">{cancelCode}</strong>? Tempat yang telah kamu pesan akan segera dilepaskan kembali agar bisa dipesan penonton lain.
                    </p>

                    <div className="mt-5 flex justify-end gap-2.5">
                        <button
                            type="button"
                            onClick={() => setCancelModalOpen(false)}
                            className="inline-flex min-h-10 items-center justify-center rounded-xl border border-border px-3.5 text-xs font-bold text-text hover:bg-surface-2 transition"
                        >
                            Jangan batalkan
                        </button>
                        <button
                            type="button"
                            onClick={handleConfirmCancel}
                            className="inline-flex min-h-10 items-center justify-center rounded-xl bg-red-600 px-4 text-xs font-bold text-white hover:bg-red-700 transition"
                        >
                            Ya, batalkan
                        </button>
                    </div>
                </div>
            </Modal>
        </PublicLayout>
    );
}

function EmptyTabState({ message, subMessage }) {
    return (
        <div className="rounded-2xl border border-dashed border-border bg-surface-2 p-8 text-center">
            <p className="text-sm font-bold text-text">{message}</p>
            <p className="mt-1 text-xs text-text-muted">{subMessage}</p>
            <div className="mt-4">
                <Link
                    href="/jadwal"
                    className="btn-gold-cinema inline-flex min-h-10 items-center justify-center rounded-xl px-4 text-xs font-bold"
                >
                    Lihat jadwal
                </Link>
            </div>
        </div>
    );
}

function BookingItemCard({ booking, type, onCancel }) {
    const [timeLeft, setTimeLeft] = useState(0);

    useEffect(() => {
        if (!booking.payment_expires_at || type !== 'belum_bayar') return;
        const target = new Date(booking.payment_expires_at).getTime();

        const update = () => {
            const diff = Math.max(0, Math.floor((target - Date.now()) / 1000));
            setTimeLeft(diff);
        };
        update();
        const timer = setInterval(update, 1000);
        return () => clearInterval(timer);
    }, [booking.payment_expires_at, type]);

    const formatTimer = (sec) => {
        const m = Math.floor(sec / 60);
        const s = sec % 60;
        return `${m}.${s < 10 ? '0' : ''}${s}`;
    };

    const targetUrl = type === 'belum_bayar'
        ? booking.payment_url || `/pembayaran/${booking.code}`
        : booking.ticket_url || `/tiket/${booking.code}`;

    const borderColor = type === 'belum_bayar'
        ? 'border-amber-500/40 bg-surface dark:bg-[#0E131F]'
        : type === 'aktif'
        ? 'border-emerald-500/30 bg-surface dark:bg-[#0E131F]'
        : 'border-border bg-surface dark:bg-[#0E131F]';

    return (
        <div className={`rounded-2xl border ${borderColor} p-4 sm:p-5 shadow-xs`}>
            <div className="flex items-start gap-3.5">
                <a
                    href={targetUrl}
                    className="group/poster block size-16 sm:size-20 rounded-xl overflow-hidden bg-slate-800 shrink-0 border border-border hover:border-amber-500 transition relative"
                    title={booking.film_title}
                >
                    {booking.film_poster ? (
                        <img
                            src={booking.film_poster}
                            alt={booking.film_title}
                            className="size-full object-cover group-hover/poster:scale-105 transition-transform duration-200"
                        />
                    ) : (
                        <div className="size-full flex flex-col items-center justify-center p-1.5 text-center bg-slate-900">
                            <FilmIcon className="size-5 text-amber-500/40 mb-0.5" />
                            <span className="text-[9px] font-bold text-amber-200/90 line-clamp-2 leading-tight">
                                {booking.film_title}
                            </span>
                        </div>
                    )}
                </a>

                <div className="flex-1 min-w-0">
                    <div className="flex items-center justify-between gap-2">
                        <a
                            href={targetUrl}
                            className={`font-tabular text-xs font-black uppercase tracking-wider hover:underline ${
                                type === 'belum_bayar'
                                    ? 'text-amber-500'
                                    : type === 'aktif'
                                    ? 'text-emerald-500'
                                    : 'text-text-muted hover:text-text'
                            }`}
                        >
                            {booking.code}
                        </a>
                        <span
                            className={`inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider ${
                                booking.status_type === 'warning'
                                    ? 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/30'
                                    : booking.status_type === 'success'
                                    ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30'
                                    : booking.status_type === 'info'
                                    ? 'bg-blue-500/10 text-blue-700 dark:text-blue-300 border border-blue-500/30'
                                    : booking.status_type === 'danger'
                                    ? 'bg-red-500/10 text-red-700 dark:text-red-300 border border-red-500/30'
                                    : 'bg-slate-500/10 text-slate-700 dark:text-slate-300 border border-slate-500/30'
                            }`}
                        >
                            {booking.status_label || booking.status}
                        </span>
                    </div>

                    <h2 className="mt-1 text-sm sm:text-base font-black text-text truncate">
                        <a href={targetUrl} className="hover:text-amber-500 transition">
                            {booking.film_title}
                        </a>
                    </h2>

                    <p className="mt-0.5 text-xs text-text-muted">
                        {booking.formatted_date} &bull; {booking.formatted_time}
                    </p>

                    <div className="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                        <span className="font-semibold text-text">
                            Jumlah: {booking.people_count} orang
                        </span>
                        {booking.formatted_price && (
                            <>
                                <span className="text-text-muted">&bull;</span>
                                <span className="font-black text-text">
                                    {booking.formatted_price}
                                </span>
                            </>
                        )}
                    </div>

                    {type === 'belum_bayar' && (
                        <p className="mt-2 text-xs font-bold text-amber-800 dark:text-amber-300">
                            Sisa waktu bayar: <span className="font-tabular font-black">{formatTimer(timeLeft)}</span>
                        </p>
                    )}
                </div>
            </div>

            <div className="mt-4 pt-3 border-t border-border flex items-center justify-end gap-2.5">
                {type === 'belum_bayar' && (
                    <>
                        <button
                            type="button"
                            onClick={onCancel}
                            className="inline-flex min-h-10 items-center justify-center rounded-xl border border-border px-3.5 text-xs font-bold text-text hover:text-red-600 hover:border-red-500 transition"
                        >
                            Batalkan
                        </button>
                        <a
                            href={targetUrl}
                            className="btn-gold-cinema inline-flex min-h-10 items-center justify-center rounded-xl px-4 text-xs font-black shadow-xs"
                        >
                            Lanjutkan pembayaran
                        </a>
                    </>
                )}

                {type === 'diproses' && (
                    <a
                        href={targetUrl}
                        className="inline-flex min-h-10 items-center justify-center rounded-xl border border-border px-4 text-xs font-bold text-text hover:border-amber-500 transition"
                    >
                        Lihat detail
                    </a>
                )}

                {type === 'aktif' && (
                    <a
                        href={targetUrl}
                        className="btn-gold-cinema inline-flex min-h-10 items-center justify-center rounded-xl px-4 text-xs font-black shadow-xs"
                    >
                        Lihat tiket
                    </a>
                )}

                {type === 'riwayat' && (
                    <a
                        href={targetUrl}
                        className="inline-flex min-h-10 items-center justify-center rounded-xl border border-border px-3.5 text-xs font-semibold text-text-muted hover:text-text transition"
                    >
                        Lihat arsip tiket
                    </a>
                )}
            </div>
        </div>
    );
}

