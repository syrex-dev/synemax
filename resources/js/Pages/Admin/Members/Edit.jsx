import { useState } from 'react';
import { Link, Head, useForm, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Users, ArrowLeft, Upload, Trash2 } from 'lucide-react';

export default function Edit({ member }) {
    const { data, setData, post, processing, errors } = useForm({
        _method: 'PUT',
        name: member.name || '',
        nickname: member.nickname || '',
        photo: null,
        remove_photo: false,
        total_visits: member.total_visits ?? 0,
        status: member.status || 'aktif',
        has_physical_card: Boolean(member.has_physical_card),
        notes: member.notes || '',
    });

    const [previewPhoto, setPreviewPhoto] = useState(member.photo_url || null);

    const handlePhotoChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setData('photo', file);
            setData('remove_photo', false);
            setPreviewPhoto(URL.createObjectURL(file));
        }
    };

    const handleRemovePhoto = () => {
        setData('photo', null);
        setData('remove_photo', true);
        setPreviewPhoto(null);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(`/admin/members/${member.id}`);
    };

    return (
        <AdminLayout title={`Ubah Member: ${member.name}`} isFormPage={true}>
            <Head title={`Ubah Member: ${member.name} · Admin`} />

            <div className="max-w-2xl mx-auto space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-text-muted mb-1">
                            <Link href="/admin/members" className="hover:text-amber-500 transition-colors">Member</Link>
                            <span>/</span>
                            <Link href={`/admin/members/${member.id}`} className="hover:text-amber-500 transition-colors">{member.name}</Link>
                            <span>/</span>
                            <span className="text-text">Ubah</span>
                        </div>
                        <h1 className="text-xl font-bold tracking-tight text-text sm:text-2xl">Ubah Data Member</h1>
                        <p className="text-xs text-text-muted mt-1">
                            Perbarui profil dan status member <strong className="text-text">{member.name}</strong> (ID: {member.code}).
                        </p>
                    </div>

                    <Link
                        href={`/admin/members/${member.id}`}
                        className="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface px-3.5 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition"
                    >
                        <ArrowLeft className="size-4" />
                        <span>Kembali</span>
                    </Link>
                </div>

                {/* Form Card */}
                <div className="rounded-2xl border border-border bg-surface p-6 shadow-sm">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* ID Member (Read Only) */}
                        <div>
                            <label className="block text-xs font-semibold text-text-muted mb-1.5">
                                ID Unik Member (Permanen)
                            </label>
                            <input
                                type="text"
                                value={member.code}
                                disabled
                                className="w-full min-h-[44px] rounded-xl border border-border bg-surface-2 px-3.5 text-sm font-mono text-text-muted cursor-not-allowed"
                            />
                        </div>

                        {/* Nama Lengkap */}
                        <div>
                            <label htmlFor="name" className="block text-xs font-semibold text-text-muted mb-1.5">
                                Nama Lengkap <span className="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                maxLength={80}
                                required
                                className={`w-full min-h-[44px] rounded-xl border ${
                                    errors.name ? 'border-rose-500' : 'border-border'
                                } bg-surface-2 px-3.5 text-sm text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30`}
                            />
                            {errors.name && <p className="text-xs text-rose-500 mt-1">{errors.name}</p>}
                        </div>

                        {/* Nama Panggilan */}
                        <div>
                            <label htmlFor="nickname" className="block text-xs font-semibold text-text-muted mb-1.5">
                                Nama Panggilan (Opsional)
                            </label>
                            <input
                                type="text"
                                id="nickname"
                                value={data.nickname}
                                onChange={(e) => setData('nickname', e.target.value)}
                                maxLength={50}
                                className="w-full min-h-[44px] rounded-xl border border-border bg-surface-2 px-3.5 text-sm text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30"
                            />
                            {errors.nickname && <p className="text-xs text-rose-500 mt-1">{errors.nickname}</p>}
                        </div>

                        {/* Upload Foto */}
                        <div>
                            <label className="block text-xs font-semibold text-text-muted mb-1.5">
                                Foto Profil Member
                            </label>
                            <div className="flex items-center gap-4">
                                {previewPhoto ? (
                                    <img
                                        src={previewPhoto}
                                        alt="Foto Member"
                                        className="size-16 rounded-xl object-cover border border-border shrink-0"
                                    />
                                ) : (
                                    <div className="size-16 rounded-xl bg-surface-2 border border-border flex items-center justify-center text-text-muted shrink-0">
                                        <Users className="size-6" />
                                    </div>
                                )}
                                <div className="space-y-2">
                                    <div className="flex items-center gap-2">
                                        <label className="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-border bg-surface text-xs font-bold text-text hover:bg-surface-2 cursor-pointer transition">
                                            <Upload className="size-3.5 text-amber-500" />
                                            <span>Ganti Foto</span>
                                            <input
                                                type="file"
                                                accept="image/*"
                                                className="hidden"
                                                onChange={handlePhotoChange}
                                            />
                                        </label>
                                        {previewPhoto && (
                                            <button
                                                type="button"
                                                onClick={handleRemovePhoto}
                                                className="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-rose-500/30 bg-rose-500/10 text-xs font-bold text-rose-600 hover:bg-rose-500/20 transition"
                                            >
                                                <Trash2 className="size-3.5" />
                                                <span>Hapus Foto</span>
                                            </button>
                                        )}
                                    </div>
                                    <p className="text-[11px] text-text-muted">Rasio 1:1 (400 × 400 px), maks 2 MB.</p>
                                </div>
                            </div>
                            {errors.photo && <p className="text-xs text-rose-500 mt-1">{errors.photo}</p>}
                        </div>

                        {/* Total Kunjungan Member */}
                        <div>
                            <label htmlFor="total_visits" className="block text-xs font-semibold text-text-muted mb-1.5">
                                Total Kunjungan Member <span className="text-amber-500">*</span>
                            </label>
                            <input
                                type="number"
                                id="total_visits"
                                min={0}
                                max={999}
                                value={data.total_visits}
                                onChange={(e) => setData('total_visits', parseInt(e.target.value, 10) || 0)}
                                required
                                className="w-full min-h-[44px] rounded-xl border border-border bg-surface-2 px-3.5 text-sm font-bold text-text focus:border-amber-500"
                            />
                            <p className="text-[11px] text-text-muted mt-1">
                                Sesuaikan jumlah stempel / kunjungan member secara manual.
                            </p>
                        </div>

                        {/* Status Member */}
                        <div>
                            <label htmlFor="status" className="block text-xs font-semibold text-text-muted mb-1.5">
                                Status Member <span className="text-rose-500">*</span>
                            </label>
                            <select
                                id="status"
                                value={data.status}
                                onChange={(e) => setData('status', e.target.value)}
                                className="w-full min-h-[44px] rounded-xl border border-border bg-surface-2 px-3.5 text-xs text-text focus:border-amber-500"
                            >
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                                <option value="diblokir">Diblokir</option>
                            </select>
                            {errors.status && <p className="text-xs text-rose-500 mt-1">{errors.status}</p>}
                        </div>

                        {/* Status Kartu Fisik */}
                        <div className="rounded-xl border border-border bg-surface-2 p-4">
                            <label className="flex items-start gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.has_physical_card}
                                    onChange={(e) => setData('has_physical_card', e.target.checked)}
                                    className="mt-0.5 size-4 rounded border-border text-amber-500 focus:ring-amber-500/30"
                                />
                                <div>
                                    <span className="block text-xs font-bold text-text">Kartu fisik member sudah dibuat / diserahkan</span>
                                    <span className="block text-[11px] text-text-muted mt-0.5">
                                        Memudahkan admin melihat di daftar apakah kartu fisik member sudah ada atau belum dibuatkan.
                                    </span>
                                </div>
                            </label>
                        </div>

                        {/* Catatan Internal */}
                        <div>
                            <label htmlFor="notes" className="block text-xs font-semibold text-text-muted mb-1.5">
                                Catatan Internal (Opsional)
                            </label>
                            <textarea
                                id="notes"
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                rows={3}
                                maxLength={300}
                                className="w-full rounded-xl border border-border bg-surface-2 p-3 text-xs text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30"
                            />
                        </div>

                        {/* Buttons */}
                        <div className="flex justify-end gap-3 pt-4 border-t border-border">
                            <Link
                                href={`/admin/members/${member.id}`}
                                className="min-h-[44px] inline-flex items-center justify-center rounded-xl border border-border bg-surface px-5 text-xs font-semibold text-text hover:bg-surface-2 transition"
                            >
                                Batal
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="btn-gold-cinema min-h-[44px] inline-flex items-center justify-center rounded-xl px-6 text-xs font-bold disabled:opacity-50"
                            >
                                {processing ? 'Menyimpan...' : 'Perbarui Member'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}

