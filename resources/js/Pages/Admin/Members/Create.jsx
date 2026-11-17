import { useState } from 'react';
import { Link, Head, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Users, ArrowLeft, AlertTriangle, Upload, Check } from 'lucide-react';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        nickname: '',
        photo: null,
        opening_visits: 0,
        status: 'aktif',
        has_physical_card: false,
        notes: '',
        ignore_duplicate_name: false,
    });

    const [previewPhoto, setPreviewPhoto] = useState(null);

    const handlePhotoChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setData('photo', file);
            setPreviewPhoto(URL.createObjectURL(file));
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/admin/members');
    };

    return (
        <AdminLayout title="Tambah Member Baru" isFormPage={true}>
            <Head title="Tambah Member Baru · Admin" />

            <div className="max-w-2xl mx-auto space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-text-muted mb-1">
                            <Link href="/admin/members" className="hover:text-amber-500 transition-colors">Member</Link>
                            <span>/</span>
                            <span className="text-text">Tambah Baru</span>
                        </div>
                        <h1 className="text-xl font-bold tracking-tight text-text sm:text-2xl">Tambah Member Baru</h1>
                        <p className="text-xs text-text-muted mt-1">Daftarkan pelanggan baru ke program loyalitas kunjungan berhadiah.</p>
                    </div>

                    <Link
                        href="/admin/members"
                        className="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface px-3.5 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition"
                    >
                        <ArrowLeft className="size-4" />
                        <span>Kembali</span>
                    </Link>
                </div>

                {/* Form Card */}
                <div className="rounded-2xl border border-border bg-surface p-6 shadow-sm">
                    <form onSubmit={handleSubmit} className="space-y-6">
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
                                placeholder="Contoh: Rian Anggoro"
                                className={`w-full min-h-[44px] rounded-xl border ${
                                    errors.name ? 'border-rose-500' : 'border-border'
                                } bg-surface-2 px-3.5 text-sm text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30`}
                            />
                            {errors.name && <p className="text-xs text-rose-500 mt-1">{errors.name}</p>}
                            <p className="text-[11px] text-text-muted mt-1">Nama boleh sama dengan member lain. Sistem menghasilkan ID unik tersendiri.</p>
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
                                placeholder="Contoh: Rian"
                                className="w-full min-h-[44px] rounded-xl border border-border bg-surface-2 px-3.5 text-sm text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30"
                            />
                            {errors.nickname && <p className="text-xs text-rose-500 mt-1">{errors.nickname}</p>}
                        </div>

                        {/* Upload Foto */}
                        <div>
                            <label className="block text-xs font-semibold text-text-muted mb-1.5">
                                Foto Profil Member (Opsional)
                            </label>
                            <div className="flex items-center gap-4">
                                {previewPhoto ? (
                                    <img
                                        src={previewPhoto}
                                        alt="Preview"
                                        className="size-16 rounded-xl object-cover border border-border shrink-0"
                                    />
                                ) : (
                                    <div className="size-16 rounded-xl bg-surface-2 border border-border flex items-center justify-center text-text-muted shrink-0">
                                        <Users className="size-6" />
                                    </div>
                                )}
                                <div className="space-y-1">
                                    <label className="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-border bg-surface text-xs font-bold text-text hover:bg-surface-2 cursor-pointer transition">
                                        <Upload className="size-3.5 text-amber-500" />
                                        <span>Pilih Foto</span>
                                        <input
                                            type="file"
                                            accept="image/*"
                                            className="hidden"
                                            onChange={handlePhotoChange}
                                        />
                                    </label>
                                    <p className="text-[11px] text-text-muted">Rasio 1:1, JPG/PNG/WebP, maks 2 MB.</p>
                                </div>
                            </div>
                            {errors.photo && <p className="text-xs text-rose-500 mt-1">{errors.photo}</p>}
                        </div>

                        {/* Saldo Awal Kunjungan */}
                        <div>
                            <label htmlFor="opening_visits" className="block text-xs font-semibold text-text-muted mb-1.5">
                                Saldo Awal Kunjungan
                            </label>
                            <select
                                id="opening_visits"
                                value={data.opening_visits}
                                onChange={(e) => setData('opening_visits', parseInt(e.target.value, 10) || 0)}
                                className="w-full min-h-[44px] rounded-xl border border-border bg-surface-2 px-3.5 text-xs text-text focus:border-amber-500"
                            >
                                {[...Array(10).keys()].map((i) => (
                                    <option key={i} value={i}>
                                        {i} kunjungan
                                    </option>
                                ))}
                            </select>
                            <p className="text-[11px] text-text-muted mt-1">
                                Isi hanya jika member merupakan pindahan dari stempel kartu fisik lama.
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
                                        Centang jika member sudah memiliki kartu fisik.
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
                                placeholder="Catatan internal pengelola sinema..."
                                className="w-full rounded-xl border border-border bg-surface-2 p-3 text-xs text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30"
                            />
                        </div>

                        {/* Catatan Privasi */}
                        <div className="rounded-xl border border-border bg-surface-2 p-4 text-xs text-text-muted leading-relaxed">
                            <span className="font-bold text-text">Penting:</span> Sistem tidak meminta maupun menyimpan nomor telepon, email, WhatsApp, ataupun alamat demi privasi pelanggan. ID unik dan kartu digital bertanda tangan adalah identitas utama member.
                        </div>

                        {/* Buttons */}
                        <div className="flex justify-end gap-3 pt-4 border-t border-border">
                            <Link
                                href="/admin/members"
                                className="min-h-[44px] inline-flex items-center justify-center rounded-xl border border-border bg-surface px-5 text-xs font-semibold text-text hover:bg-surface-2 transition"
                            >
                                Batal
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="btn-gold-cinema min-h-[44px] inline-flex items-center justify-center rounded-xl px-6 text-xs font-bold disabled:opacity-50"
                            >
                                {processing ? 'Menyimpan...' : 'Simpan Member Baru'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}

