import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import Modal from './Modal';
import { Users, Phone, User, FileText, CreditCard, CheckCircle2, Minus, Plus } from 'lucide-react';

export default function EditBookingModal({ booking, isOpen, onClose }) {
    if (!booking) return null;

    const [customerName, setCustomerName] = useState(booking.customer_name || '');
    const [whatsapp, setWhatsapp] = useState(booking.whatsapp || '');
    const [notes, setNotes] = useState(booking.notes || '');
    const [pax, setPax] = useState(Number(booking.pax || booking.people_count || 1));
    const [paymentMethod, setPaymentMethod] = useState(booking.payment_method || 'di_tempat');
    const [status, setStatus] = useState(booking.status || 'menunggu_pembayaran');
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState({});

    useEffect(() => {
        if (booking) {
            setCustomerName(booking.customer_name || '');
            setWhatsapp(booking.whatsapp || '');
            setNotes(booking.notes || '');
            setPax(Number(booking.pax || booking.people_count || 1));
            setPaymentMethod(booking.payment_method || 'di_tempat');
            setStatus(booking.status || 'menunggu_pembayaran');
            setErrors({});
        }
    }, [booking]);

    const handleSubmit = (e) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        router.patch(
            `/admin/booking/${booking.id}`,
            {
                customer_name: customerName,
                whatsapp: whatsapp,
                notes: notes,
                pax: pax,
                payment_method: paymentMethod,
                status: status,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setProcessing(false);
                    onClose();
                },
                onError: (err) => {
                    setProcessing(false);
                    setErrors(err);
                },
            }
        );
    };

    return (
        <Modal
            isOpen={isOpen}
            onClose={onClose}
            title={`Ubah Data Booking #${booking.id} (${booking.code})`}
            maxWidth="max-w-lg"
        >
            <form onSubmit={handleSubmit} className="space-y-4 pt-1">
                {/* Customer Name */}
                <div>
                    <label className="text-xs font-bold uppercase tracking-wider text-text-muted flex items-center gap-1.5 mb-1.5">
                        <User className="size-3.5 text-amber-500" />
                        <span>Nama Pemesan</span>
                    </label>
                    <input
                        type="text"
                        required
                        value={customerName}
                        onChange={(e) => setCustomerName(e.target.value)}
                        placeholder="Nama lengkap pemesan..."
                        className="input-cinema w-full px-3.5 py-2.5 text-sm rounded-xl"
                    />
                    {errors.customer_name && (
                        <p className="mt-1 text-xs text-rose-500 font-semibold">{errors.customer_name}</p>
                    )}
                </div>

                {/* WhatsApp */}
                <div>
                    <label className="text-xs font-bold uppercase tracking-wider text-text-muted flex items-center gap-1.5 mb-1.5">
                        <Phone className="size-3.5 text-emerald-500" />
                        <span>Nomor WhatsApp</span>
                    </label>
                    <input
                        type="tel"
                        required
                        value={whatsapp}
                        onChange={(e) => setWhatsapp(e.target.value)}
                        placeholder="Contoh: 081234567890"
                        className="input-cinema w-full px-3.5 py-2.5 text-sm rounded-xl font-mono"
                    />
                    {errors.whatsapp && (
                        <p className="mt-1 text-xs text-rose-500 font-semibold">{errors.whatsapp}</p>
                    )}
                </div>

                {/* Pax (Jumlah Orang) */}
                {booking.booking_type !== 'private' ? (
                    <div className="rounded-2xl border border-border bg-surface-2/40 p-3.5 space-y-2.5">
                        <div className="flex items-center justify-between">
                            <label className="text-xs font-bold uppercase tracking-wider text-text-muted flex items-center gap-1.5">
                                <Users className="size-3.5 text-amber-500" />
                                <span>Jumlah Orang (Pax)</span>
                            </label>
                            <span className="font-mono font-bold text-amber-500 text-xs">
                                {pax} Orang
                            </span>
                        </div>

                        {/* Interactive Stepper */}
                        <div className="flex items-center justify-between gap-3 bg-surface rounded-xl border border-border p-1.5">
                            <button
                                type="button"
                                disabled={pax <= 1}
                                onClick={() => setPax((p) => Math.max(1, p - 1))}
                                className="size-9 rounded-lg bg-surface-2 border border-border flex items-center justify-center font-bold text-text hover:bg-amber-500 hover:text-slate-950 disabled:opacity-40 disabled:cursor-not-allowed transition"
                            >
                                <Minus className="size-4" />
                            </button>

                            <div className="font-mono font-black text-lg text-text">
                                {pax} <span className="text-xs font-normal text-text-muted">orang / kursi</span>
                            </div>

                            <button
                                type="button"
                                disabled={pax >= 10}
                                onClick={() => setPax((p) => Math.min(10, p + 1))}
                                className="size-9 rounded-lg bg-surface-2 border border-border flex items-center justify-center font-bold text-text hover:bg-amber-500 hover:text-slate-950 disabled:opacity-40 disabled:cursor-not-allowed transition"
                            >
                                <Plus className="size-4" />
                            </button>
                        </div>

                        {/* Quick Pills */}
                        <div className="grid grid-cols-5 gap-1.5 pt-1">
                            {[1, 2, 3, 4, 5, 6, 7, 8, 9, 10].map((n) => (
                                <button
                                    key={n}
                                    type="button"
                                    onClick={() => setPax(n)}
                                    className={`py-1 rounded-lg text-xs font-mono font-bold transition ${
                                        pax === n
                                            ? 'bg-amber-500 text-slate-950 font-black shadow-xs'
                                            : 'bg-surface border border-border text-text hover:bg-surface-2'
                                    }`}
                                >
                                    {n}
                                </button>
                            ))}
                        </div>
                    </div>
                ) : (
                    <div className="rounded-xl border border-border bg-surface-2/40 p-3 text-xs text-text-muted">
                        <span className="font-bold text-text">Tipe Paket: Sewa Private Studio</span>
                        <p className="mt-0.5">Kapasitas penuh 6 kursi secara privat.</p>
                    </div>
                )}

                {/* Payment Method & Status Grid */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label className="text-xs font-bold uppercase tracking-wider text-text-muted flex items-center gap-1.5 mb-1.5">
                            <CreditCard className="size-3.5 text-amber-500" />
                            <span>Metode Bayar</span>
                        </label>
                        <select
                            value={paymentMethod}
                            onChange={(e) => setPaymentMethod(e.target.value)}
                            className="input-cinema w-full px-3 py-2 text-xs rounded-xl"
                        >
                            <option value="di_tempat">Di Tempat (Tunai)</option>
                            <option value="qris">QRIS</option>
                            <option value="transfer">Transfer Bank</option>
                        </select>
                    </div>

                    <div>
                        <label className="text-xs font-bold uppercase tracking-wider text-text-muted flex items-center gap-1.5 mb-1.5">
                            <CheckCircle2 className="size-3.5 text-amber-500" />
                            <span>Status Booking</span>
                        </label>
                        <select
                            value={status}
                            onChange={(e) => setStatus(e.target.value)}
                            className="input-cinema w-full px-3 py-2 text-xs rounded-xl"
                        >
                            <option value="menunggu_pembayaran">Menunggu Pembayaran</option>
                            <option value="menunggu_konfirmasi">Menunggu Konfirmasi</option>
                            <option value="terkonfirmasi">Terkonfirmasi</option>
                            <option value="hadir">Hadir di Studio</option>
                            <option value="tidak_hadir">Tidak Hadir (No Show)</option>
                            <option value="kedaluwarsa">Kedaluwarsa</option>
                            <option value="dibatalkan">Dibatalkan</option>
                        </select>
                    </div>
                </div>

                {/* Notes */}
                <div>
                    <label className="text-xs font-bold uppercase tracking-wider text-text-muted flex items-center gap-1.5 mb-1.5">
                        <FileText className="size-3.5 text-amber-500" />
                        <span>Catatan Pemesan (Opsional)</span>
                    </label>
                    <textarea
                        rows={2}
                        value={notes}
                        onChange={(e) => setNotes(e.target.value)}
                        placeholder="Catatan khusus pelanggan..."
                        className="input-cinema w-full px-3.5 py-2 text-xs rounded-xl"
                    />
                </div>

                {/* Actions */}
                <div className="flex items-center justify-end gap-2.5 pt-3 border-t border-border">
                    <button
                        type="button"
                        onClick={onClose}
                        disabled={processing}
                        className="btn-secondary-cinema px-4 py-2.5 text-xs font-bold rounded-xl"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        disabled={processing}
                        className="btn-gold-cinema px-5 py-2.5 text-xs font-bold rounded-xl shadow-sm disabled:opacity-50"
                    >
                        {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}
