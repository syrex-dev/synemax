import { useState, useEffect, useRef } from 'react';
import { Link, Head, useForm } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import Modal from '../../../Components/Modal';
import {
    CheckCircle2,
    Clock,
    Copy,
    Check,
    Download,
    ZoomIn,
    AlertTriangle,
    CreditCard,
    UploadCloud,
    X,
    FileCheck,
} from 'lucide-react';

export default function Payment({
    booking,
    whatsAppUrl = null,
    hasQrisImage = false,
    qrisMerchantName = 'SynemaX Cinema',
    qrisPaymentNotes = null,
    qrisVersion = '1',
    transferAccounts = [],
    transferPaymentNotes = null,
    maxPaymentProofKb = 4096,
    paymentTimeLimitMinutes = 15,
}) {
    // Lock scroll to top of content on mount
    useEffect(() => {
        window.scrollTo({ top: 0, left: 0, behavior: 'instant' });
    }, []);

    const [isExpired] = useState(booking?.status === 'kedaluwarsa');
    const [copiedNominal, setCopiedNominal] = useState(false);
    const [copiedAccount, setCopiedAccount] = useState(null);
    const [enlargeModal, setEnlargeModal] = useState(false);
    const [cancelModal, setCancelModal] = useState(false);

    // File upload state & compression
    const [fileError, setFileError] = useState('');
    const [isCompressing, setIsCompressing] = useState(false);
    const [compressStatus, setCompressStatus] = useState('');
    const [selectedFileName, setSelectedFileName] = useState('');
    const [compressedBlob, setCompressedBlob] = useState(null);
    const fileInputRef = useRef(null);

    const { data, setData, post, processing, errors } = useForm({
        whatsapp: booking?.whatsapp || '',
        payment_proof: null,
    });

    const copyNominal = (amount) => {
        if (navigator.clipboard?.writeText) {
            navigator.clipboard.writeText(String(amount)).then(() => {
                setCopiedNominal(true);
                setTimeout(() => setCopiedNominal(false), 2500);
            });
        }
    };

    const copyAccount = (accNum) => {
        if (navigator.clipboard?.writeText) {
            navigator.clipboard.writeText(String(accNum)).then(() => {
                setCopiedAccount(accNum);
                setTimeout(() => setCopiedAccount(null), 2500);
            });
        }
    };

    const compressImage = (file) => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    const maxDimension = 1600;
                    let width = img.width;
                    let height = img.height;

                    if (width > maxDimension || height > maxDimension) {
                        if (width > height) {
                            height = Math.round((height * maxDimension) / width);
                            width = maxDimension;
                        } else {
                            width = Math.round((width * maxDimension) / height);
                            height = maxDimension;
                        }
                    }

                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    canvas.toBlob(
                        (blob) => {
                            if (!blob) {
                                resolve(file);
                                return;
                            }
                            const cleanName = file.name.replace(/\.[^/.]+$/, '') + '.jpg';
                            const newFile = new File([blob], cleanName, {
                                type: 'image/jpeg',
                                lastModified: Date.now(),
                            });
                            resolve(newFile);
                        },
                        'image/jpeg',
                        0.82
                    );
                };
                img.onerror = () => reject(new Error('Gagal memuat gambar'));
                img.src = e.target.result;
            };
            reader.onerror = () => reject(new Error('Gagal membaca file'));
            reader.readAsDataURL(file);
        });
    };

    const handleFileChange = async (event) => {
        const files = event.target.files;
        if (!files || files.length === 0) {
            setFileError('');
            setSelectedFileName('');
            setCompressedBlob(null);
            setData('payment_proof', null);
            return;
        }

        const file = files[0];
        if (!file.type || !file.type.startsWith('image/')) {
            setFileError('Hanya file gambar (JPG, PNG, WebP) yang diperbolehkan.');
            setSelectedFileName('');
            setCompressedBlob(null);
            setData('payment_proof', null);
            if (fileInputRef.current) fileInputRef.current.value = '';
            return;
        }

        setFileError('');
        setIsCompressing(true);
        setCompressStatus('Mengoptimalkan ukuran gambar...');

        try {
            const compressed = await compressImage(file);
            const newSizeKb = Math.round(compressed.size / 1024);
            setSelectedFileName(`${compressed.name} (${newSizeKb} KB)`);
            setCompressedBlob(compressed);
            setData('payment_proof', compressed);
            setCompressStatus('');
        } catch (err) {
            console.error('Compression failed:', err);
            const maxAllowedBytes = (maxPaymentProofKb || 4096) * 1024;
            if (file.size > maxAllowedBytes) {
                setFileError(
                    `Ukuran file (${(file.size / (1024 * 1024)).toFixed(1)} MB) terlalu besar. Silakan pilih foto dengan resolusi lebih kecil.`
                );
                setSelectedFileName('');
                setCompressedBlob(null);
                setData('payment_proof', null);
                if (fileInputRef.current) fileInputRef.current.value = '';
            } else {
                setSelectedFileName(file.name);
                setCompressedBlob(file);
                setData('payment_proof', file);
            }
            setCompressStatus('');
        } finally {
            setIsCompressing(false);
        }
    };

    const handleUploadSubmit = (e) => {
        e.preventDefault();
        if (!data.payment_proof) return;

        post(`/booking/${booking.code}/bukti`, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const cancelForm = useForm({
        whatsapp: booking?.whatsapp || '',
    });

    const handleCancelBooking = (e) => {
        e.preventDefault();
        cancelForm.post(`/booking/${booking.code}/batal`, {
            preserveScroll: false,
        });
    };

    const isPrivate = booking?.booking_type === 'private' || Boolean(booking?.is_private);
    const isCouple = booking?.booking_type === 'couple';

    const snackItems = booking?.items?.filter((it) => it.status !== 'dibatalkan') || [];

    return (
        <PublicLayout title={`Pembayaran Booking ${booking.code}`}>
            <Head title={`Pembayaran Booking ${booking.code}`}>
                <meta name="description" content={`Selesaikan pembayaran untuk booking ${booking.code} di SynemaX.`} />
                {booking.payment_method === 'qris' && hasQrisImage && (
                    <link rel="preload" as="image" href={`/pembayaran/qris?v=${qrisVersion}`} fetchpriority="high" />
                )}
            </Head>

            <div className="mx-auto max-w-[520px] w-full px-4 py-5 sm:py-8">
                {/* Stepper 3 Tahap Alur Booking Online */}
                <nav aria-label="Tahapan Booking" className="mb-5 no-print">
                    <ol className="grid grid-cols-3 gap-2 text-center text-xs">
                        {/* Langkah 1: Booking (Selesai) */}
                        <li className="flex items-center justify-center gap-1.5 rounded-xl border border-emerald-600/30 bg-emerald-500/10 p-2 font-medium">
                            <span className="grid size-5 place-items-center rounded-full bg-emerald-700 text-white" aria-hidden="true">
                                <CheckCircle2 className="size-3.5" />
                            </span>
                            <span className="font-bold text-slate-800 dark:text-slate-100">Booking</span>
                        </li>

                        {/* Langkah 2: Bayar (Aktif) */}
                        <li className="flex items-center justify-center gap-1.5 rounded-xl border border-amber-500/50 bg-amber-500/10 p-2 font-bold">
                            <span className="grid size-5 place-items-center rounded-full bg-amber-400 text-slate-950 text-[11px] font-black" aria-hidden="true">
                                2
                            </span>
                            <span className="text-amber-900 dark:text-amber-200">Bayar</span>
                        </li>

                        {/* Langkah 3: Tiket (Belum) */}
                        <li className="flex items-center justify-center gap-1.5 rounded-xl border border-border bg-surface p-2 font-medium text-text-muted">
                            <span className="grid size-5 place-items-center rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-[11px] font-bold" aria-hidden="true">
                                3
                            </span>
                            <span>Tiket</span>
                        </li>
                    </ol>
                </nav>

                {/* Form Errors */}
                {Object.keys(errors).length > 0 && (
                    <div role="alert" className="mb-4 rounded-xl border border-danger/40 bg-danger-surface p-4 text-xs font-semibold text-danger-text space-y-1">
                        {Object.values(errors).map((err, idx) => (
                            <p key={idx}>{err}</p>
                        ))}
                    </div>
                )}

                {/* Catatan Penolakan Bukti Sebelumnya (Jika Ada) */}
                {booking.proof_rejection_note && (
                    <div className="mb-5 rounded-xl border border-danger/40 bg-danger-surface p-4 text-xs text-danger-text">
                        <p className="font-bold">Bukti pembayaran sebelumnya belum dapat disetujui admin:</p>
                        <p className="mt-1 font-semibold">{booking.proof_rejection_note}</p>
                        <p className="mt-1 opacity-90">Silakan unggah ulang bukti transfer yang jelas agar pesanan Anda dapat segera diverifikasi oleh admin.</p>
                    </div>
                )}

                {!isExpired && (
                    <div className="rounded-2xl border border-border bg-surface-2 dark:bg-[#0E131F] p-5 sm:p-6 shadow-xl">
                        {/* Kartu Total Pembayaran */}
                        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-border pb-4">
                            <div>
                                <span className="text-xs font-bold uppercase tracking-wider text-text">Total Yang Harus Ditransfer</span>
                                <p className="mt-0.5 font-mono text-3xl font-black text-text sm:text-4xl">
                                    Rp {Number(booking.total_price || 0).toLocaleString('id-ID')}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => copyNominal(booking.total_price)}
                                className="btn-gold-cinema inline-flex min-h-10 items-center gap-1.5 rounded-xl px-4 text-xs font-bold shadow-xs hover:scale-105 transition"
                            >
                                {copiedNominal ? <Check className="size-4" /> : <Copy className="size-4" />}
                                <span>{copiedNominal ? 'Nominal tersalin' : 'Salin nominal'}</span>
                            </button>
                        </div>

                        {/* Rincian Transparansi Biaya */}
                        <div className="mt-3.5 pt-3 border-t border-border space-y-2 text-xs">
                            <p className="text-[11px] font-bold uppercase tracking-wider text-text-muted">Rincian Pembayaran</p>
                            <div className="flex justify-between items-center text-text-muted">
                                <span>
                                    Tiket (
                                    {isPrivate
                                        ? `Sewa Private (${booking.seats_reserved || 6} orang)`
                                        : isCouple
                                        ? 'Paket Couple'
                                        : booking.seat_price
                                        ? `${booking.pax} × Rp ${Number(booking.seat_price).toLocaleString('id-ID')}`
                                        : `${booking.pax} orang`}
                                    )
                                </span>
                                <span className="font-mono font-bold text-text">
                                    Rp {Number(booking.ticket_total || 0).toLocaleString('id-ID')}
                                </span>
                            </div>

                            {snackItems.map((snackItem) => (
                                <div key={snackItem.id} className="flex justify-between items-center text-text-muted">
                                    <span className="truncate pr-2">
                                        Jajan ({snackItem.name_snapshot} × {snackItem.qty}
                                        {snackItem.note ? ` · ${snackItem.note}` : ''})
                                    </span>
                                    <span className="font-mono font-semibold text-text shrink-0">
                                        Rp {Number(snackItem.subtotal || 0).toLocaleString('id-ID')}
                                    </span>
                                </div>
                            ))}
                        </div>

                        <div className="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-text-muted">
                            <p>
                                Kode Booking:{' '}
                                <strong className="font-mono font-bold text-amber-700 dark:text-amber-400">
                                    {booking.code}
                                </strong>
                            </p>
                            <p>Transfer tepat sesuai jumlah di atas.</p>
                        </div>

                        {/* ==================== METODE 1: QRIS INSTAN ==================== */}
                        {booking.payment_method === 'qris' && (
                            <div className="mt-6 border-t border-border dark:border-white/10 pt-6">
                                <div className="flex items-center justify-between gap-2">
                                    <h2 className="text-sm font-black text-text">Pindai kode QRIS</h2>
                                    <span className="rounded-lg bg-amber-500/15 border border-amber-400/30 px-2.5 py-0.5 text-xs font-bold text-amber-600 dark:text-amber-400">
                                        {qrisMerchantName || 'SynemaX Cinema'}
                                    </span>
                                </div>

                                {hasQrisImage ? (
                                    <div className="mt-5 text-center">
                                        <div className="relative mx-auto inline-block max-w-sm rounded-2xl border-2 border-amber-400/50 bg-white p-5 shadow-2xl text-center">
                                            {/* Corner brackets */}
                                            <div className="absolute top-2 left-2 size-4 border-t-2 border-l-2 border-black" />
                                            <div className="absolute top-2 right-2 size-4 border-t-2 border-r-2 border-black" />
                                            <div className="absolute bottom-2 left-2 size-4 border-b-2 border-l-2 border-black" />
                                            <div className="absolute bottom-2 right-2 size-4 border-b-2 border-r-2 border-black" />

                                            <img
                                                src={`/pembayaran/qris?v=${qrisVersion}`}
                                                alt={`QRIS ${qrisMerchantName}`}
                                                width="280"
                                                height="280"
                                                className="aspect-square max-h-64 sm:max-h-72 w-auto max-w-full mx-auto cursor-zoom-in object-contain rounded-xl"
                                                onClick={() => setEnlargeModal(true)}
                                                title="Klik untuk memperbesar"
                                            />
                                            <p className="mt-3 font-mono text-[11px] font-black tracking-wider text-neutral-900 uppercase">
                                                QRIS STANDAR PEMBAYARAN NASIONAL
                                            </p>
                                            <p className="mt-0.5 text-[10px] text-neutral-500">
                                                M-Banking & Dompet Digital (BCA, Mandiri, BRI, BNI, GoPay, OVO, Dana)
                                            </p>
                                        </div>
                                        <p className="mt-2.5 text-xs text-text-muted">Ketuk gambar QRIS untuk memperbesar.</p>

                                        <div className="mt-3">
                                            <a
                                                href="/pembayaran/qris?download=1"
                                                download="QRIS-SynemaX"
                                                className="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-xl border border-border dark:border-white/20 bg-surface dark:bg-white/5 px-4 text-xs font-bold text-text dark:text-white hover:border-amber-400 transition"
                                            >
                                                <Download className="size-4 text-amber-500" />
                                                <span>Simpan gambar QRIS</span>
                                            </a>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="mt-4 rounded-xl border border-border bg-surface-2 p-4 text-xs text-text-muted">
                                        Gambar QRIS sedang disiapkan pengelola. Anda dapat menghubungi admin lewat WhatsApp untuk mendapatkan kode pembayaran.
                                    </div>
                                )}

                                {qrisPaymentNotes && (
                                    <div className="mt-4 rounded-xl bg-surface-2 p-3.5 text-xs text-text-muted leading-relaxed">
                                        <p className="font-bold text-text">Catatan Pengelola:</p>
                                        <p className="mt-0.5">{qrisPaymentNotes}</p>
                                    </div>
                                )}

                                <div className="mt-5 rounded-2xl border border-border dark:border-white/10 bg-surface dark:bg-white/5 p-4 text-xs text-text-muted shadow-xs">
                                    <h3 className="font-bold text-text dark:text-white flex items-center gap-1.5">
                                        <span className="size-1.5 rounded-full bg-amber-500" />
                                        <span>Petunjuk Bayar via QRIS</span>
                                    </h3>
                                    <ol className="mt-2 list-decimal space-y-1.5 pl-4 text-text-muted">
                                        <li>Buka aplikasi m-Banking atau dompet digital favorit Anda.</li>
                                        <li>Pilih menu <strong className="text-text font-bold">Scan QRIS</strong>.</li>
                                        <li>Arahkan kamera ke gambar QR di atas atau unggah dari galeri.</li>
                                        <li>
                                            Pastikan nominal bayar tepat{' '}
                                            <strong className="font-mono font-bold text-amber-700 dark:text-amber-400">
                                                Rp {Number(booking.total_price || 0).toLocaleString('id-ID')}
                                            </strong>.
                                        </li>
                                        <li>Simpan bukti transfer dan unggah pada formulir di bawah.</li>
                                    </ol>
                                </div>
                            </div>
                        )}

                        {/* ==================== METODE 2: TRANSFER BANK ==================== */}
                        {booking.payment_method === 'transfer' && (
                            <div className="mt-6 border-t border-border dark:border-white/10 pt-6">
                                <div className="flex items-center justify-between gap-2">
                                    <h2 className="text-sm font-black text-text dark:text-white">Rekening Tujuan Transfer</h2>
                                    <span className="text-xs text-text-muted">{transferAccounts.length} Rekening Tersedia</span>
                                </div>

                                <div className="mt-3.5 space-y-3">
                                    {transferAccounts.length > 0 ? (
                                        transferAccounts.map((acc, idx) => (
                                            <div
                                                key={idx}
                                                className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border dark:border-white/10 bg-surface dark:bg-white/5 p-4 shadow-xs"
                                            >
                                                <div>
                                                    <span className="rounded-md bg-amber-500/15 dark:bg-amber-500/20 px-2 py-0.5 text-xs font-black text-amber-700 dark:text-amber-400 uppercase tracking-wide">
                                                        {acc.bank_name}
                                                    </span>
                                                    <p className="mt-2 font-mono text-xl sm:text-2xl font-black text-text dark:text-white tracking-wider">
                                                        {acc.account_number}
                                                    </p>
                                                    <p className="mt-0.5 text-xs text-text-muted">
                                                        Atas Nama: <strong className="text-text dark:text-slate-200 font-bold">{acc.account_holder}</strong>
                                                    </p>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={() => copyAccount(acc.account_number)}
                                                    className="btn-gold-cinema inline-flex min-h-9 items-center rounded-lg px-3.5 text-xs font-bold shrink-0"
                                                >
                                                    {copiedAccount === acc.account_number ? (
                                                        <Check className="size-3.5 mr-1" />
                                                    ) : (
                                                        <Copy className="size-3.5 mr-1" />
                                                    )}
                                                    <span>{copiedAccount === acc.account_number ? 'Tersalin!' : 'Salin Nomor'}</span>
                                                </button>
                                            </div>
                                        ))
                                    ) : (
                                        <div className="rounded-xl border border-border bg-surface-2 p-4 text-xs text-text-muted">
                                            Rekening pembayaran sedang disiapkan. Silakan hubungi admin lewat WhatsApp.
                                        </div>
                                    )}
                                </div>

                                {transferPaymentNotes && (
                                    <div className="mt-4 rounded-xl bg-surface-2 p-3.5 text-xs text-text-muted leading-relaxed">
                                        <p className="font-bold text-text">Catatan:</p>
                                        <p className="mt-0.5">{transferPaymentNotes}</p>
                                    </div>
                                )}

                                <div className="mt-5 rounded-2xl border border-border dark:border-white/10 bg-surface dark:bg-white/5 p-4 text-xs text-text-muted shadow-xs">
                                    <h3 className="font-bold text-text dark:text-white flex items-center gap-1.5">
                                        <span className="size-1.5 rounded-full bg-amber-500" />
                                        <span>Petunjuk Bayar via Transfer</span>
                                    </h3>
                                    <ol className="mt-2 list-decimal space-y-1.5 pl-4 text-text-muted">
                                        <li>Transfer ke rekening atau nomor e-wallet di atas.</li>
                                        <li>
                                            Pastikan nominal transfer tepat{' '}
                                            <strong className="font-mono font-bold text-amber-700 dark:text-amber-400">
                                                Rp {Number(booking.total_price || 0).toLocaleString('id-ID')}
                                            </strong>.
                                        </li>
                                        <li>Simpan bukti tangkapan layar atau struk ATM untuk diunggah.</li>
                                    </ol>
                                </div>
                            </div>
                        )}

                        {/* ==================== FORMULIR UNGGAH BUKTI BAYAR ==================== */}
                        <div className="mt-8 border-t border-border dark:border-white/10 pt-6">
                            <h3 className="text-sm font-black text-text dark:text-white">Kirim Bukti Pembayaran</h3>
                            <p className="mt-0.5 text-xs text-text-muted">
                                Unggah foto atau tangkapan layar bukti transfer (JPG, PNG, atau WebP maks. {Math.round(maxPaymentProofKb / 1024)} MB).
                            </p>

                            <form onSubmit={handleUploadSubmit} className="mt-4 space-y-4 rounded-2xl border border-border dark:border-white/10 bg-surface dark:bg-white/5 p-5 shadow-xs">
                                <div>
                                    <label htmlFor="payment_proof" className="block text-xs font-bold uppercase tracking-wider text-text-muted">
                                        Pilih Berkas Bukti Transfer
                                    </label>
                                    <input
                                        ref={fileInputRef}
                                        id="payment_proof"
                                        name="payment_proof"
                                        type="file"
                                        required
                                        accept="image/jpeg,image/png,image/webp"
                                        onChange={handleFileChange}
                                        className="mt-2 block w-full text-xs text-text file:mr-3 file:min-h-11 file:rounded-xl file:border file:border-amber-500/40 file:bg-amber-500/10 file:px-4 file:text-xs file:font-bold file:text-amber-700 dark:file:text-amber-400 hover:file:bg-amber-500/20 cursor-pointer"
                                    />
                                    {compressStatus && (
                                        <p className="mt-2 text-xs font-medium text-amber-700 dark:text-amber-400 flex items-center gap-1.5">
                                            <UploadCloud className="size-4 animate-bounce" />
                                            <span>{compressStatus}</span>
                                        </p>
                                    )}
                                    {selectedFileName && !compressStatus && (
                                        <p className="mt-2 text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                                            <FileCheck className="size-4" />
                                            <span>Berkas siap dikirim: {selectedFileName}</span>
                                        </p>
                                    )}
                                    {fileError && (
                                        <p className="mt-2 text-xs font-bold text-danger-text">{fileError}</p>
                                    )}
                                </div>

                                <button
                                    type="submit"
                                    disabled={!selectedFileName || isCompressing || processing}
                                    className="btn-gold-cinema inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl px-5 text-sm font-black shadow-lg transition disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    <span>{processing ? 'Mengirim...' : isCompressing ? 'Menyiapkan gambar...' : 'Kirim bukti bayar'}</span>
                                </button>
                            </form>
                        </div>

                        {/* Aksi Pembatalan */}
                        <div className="mt-6 pt-5 border-t border-border dark:border-white/10 text-center max-w-[400px] mx-auto w-full">
                            <button
                                type="button"
                                onClick={() => setCancelModal(true)}
                                className="text-xs text-text-muted hover:text-red-500 dark:hover:text-red-400 hover:underline transition-colors"
                            >
                                Batalkan booking ini
                            </button>
                        </div>
                    </div>
                )}
            </div>

            {/* Modal Batalkan Booking */}
            <Modal show={cancelModal} onClose={() => setCancelModal(false)} maxWidth="sm">
                <div className="p-6">
                    <h3 className="text-base font-black text-text dark:text-white">Batalkan booking ini?</h3>
                    <p className="mt-2 text-xs leading-relaxed text-text-muted">
                        Apakah Anda yakin ingin membatalkan pemesanan ini? Tempat yang telah dipesan akan dilepaskan kembali.
                    </p>
                    <form onSubmit={handleCancelBooking} className="mt-5">
                        <div className="flex justify-end gap-2.5">
                            <button
                                type="button"
                                onClick={() => setCancelModal(false)}
                                className="inline-flex min-h-10 items-center justify-center rounded-xl border border-border bg-surface-2 dark:bg-transparent dark:border-white/20 px-3.5 text-xs font-bold text-text hover:bg-surface transition"
                            >
                                Jangan batalkan
                            </button>
                            <button
                                type="submit"
                                disabled={cancelForm.processing}
                                className="inline-flex min-h-10 items-center justify-center rounded-xl bg-danger px-4 text-xs font-bold text-white hover:opacity-90 transition disabled:opacity-50"
                            >
                                Ya, batalkan
                            </button>
                        </div>
                    </form>
                </div>
            </Modal>

            {/* Modal Perbesar QRIS */}
            {booking.payment_method === 'qris' && hasQrisImage && (
                <Modal show={enlargeModal} onClose={() => setEnlargeModal(false)} maxWidth="md">
                    <div className="relative p-6 text-center bg-white rounded-3xl">
                        <button
                            type="button"
                            onClick={() => setEnlargeModal(false)}
                            className="absolute right-4 top-4 flex size-9 items-center justify-center rounded-xl bg-neutral-100 text-neutral-800 hover:bg-neutral-200"
                            title="Tutup"
                        >
                            <X className="size-5" />
                        </button>
                        <p className="text-xs font-bold text-neutral-500 uppercase tracking-wider">QRIS Standar SynemaX</p>
                        <p className="mt-1 text-lg font-black text-neutral-900">{qrisMerchantName || 'SynemaX Cinema'}</p>
                        <div className="mx-auto mt-4 max-w-sm rounded-2xl border border-neutral-200 p-3">
                            <img
                                src={`/pembayaran/qris?v=${qrisVersion}`}
                                alt={`QRIS ${qrisMerchantName}`}
                                className="max-h-[60vh] w-auto max-w-full mx-auto object-contain rounded-xl"
                            />
                            <p className="mt-3 font-mono text-sm font-black text-neutral-900">
                                Total: Rp {Number(booking.total_price || 0).toLocaleString('id-ID')}
                            </p>
                        </div>
                    </div>
                </Modal>
            )}
        </PublicLayout>
    );
}

