import { useState, useRef } from 'react';
import { Link, Head, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import {
    Tag,
    ArrowLeft,
    UploadCloud,
    X,
    Eye,
    AlertCircle,
    Calendar,
} from 'lucide-react';

export default function Form({
    promo,
    isEdit = false,
}) {
    const fileInputRef = useRef(null);
    const [imagePreview, setImagePreview] = useState(
        promo.image_path ? `/storage/${promo.image_path}` : null
    );

    const form = useForm({
        _method: isEdit ? 'PUT' : 'POST',
        title: promo.title || '',
        slug: promo.slug || '',
        image: null,
        start_date: promo.start_date || '',
        end_date: promo.end_date || '',
        description: promo.description || '',
        terms_conditions: promo.terms_conditions || '',
        is_active: promo.is_active !== undefined ? Boolean(promo.is_active) : true,
        sort_order: promo.sort_order || 0,
    });

    const handleImageChange = (e) => {
        const file = e.target.files?.[0];
        if (file) {
            form.setData('image', file);
            const reader = new FileReader();
            reader.onload = (ev) => {
                setImagePreview(ev.target?.result);
            };
            reader.readAsDataURL(file);
        }
    };

    const handleClearImage = () => {
        form.setData('image', null);
        setImagePreview(promo.image_path ? `/storage/${promo.image_path}` : null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const url = isEdit ? `/admin/promos/${promo.id}` : '/admin/promos';
        form.post(url, {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout title={isEdit ? 'Ubah Promo' : 'Tambah Promo Baru'}>
            <Head title={`${isEdit ? 'Ubah Promo' : 'Tambah Promo Baru'} | Admin SynemaX`} />

            <div className="max-w-4xl mx-auto space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-text-muted mb-1">
                            <Link href="/admin/promos" className="hover:text-amber-500 transition-colors">
                                Kelola Promo
                            </Link>
                            <span>/</span>
                            <span className="text-text">{isEdit ? 'Ubah Promo' : 'Tambah Baru'}</span>
                        </div>
                        <h1 className="text-2xl font-black tracking-tight text-text">
                            {isEdit ? `Ubah Promo: ${promo.title}` : 'Tambah Promo Baru'}
                        </h1>
                        <p className="text-xs text-text-muted mt-1">
                            Promo dikelola lewat upload banner gambar 1200 × 600 px (rasio 2:1).
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href="/admin/promos"
                            className="inline-flex min-h-[42px] items-center gap-1.5 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors shadow-xs"
                        >
                            <ArrowLeft className="size-4" />
                            <span>Kembali ke Daftar</span>
                        </Link>
                        {isEdit && promo.slug && (
                            <a
                                href={`/promo/${promo.slug}`}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex min-h-[42px] items-center gap-1.5 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors shadow-xs"
                            >
                                <Eye className="size-4" />
                                <span>Lihat di Web</span>
                            </a>
                        )}
                    </div>
                </div>

                {/* Main Form */}
                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Basic Info */}
                    <div className="rounded-2xl border border-border bg-surface p-6 shadow-xs space-y-5">
                        <h2 className="text-base font-bold text-text">Informasi Promo</h2>

                        <div className="grid gap-4 sm:grid-cols-2 text-xs">
                            <div>
                                <label className="block font-bold text-text mb-1">
                                    Judul Promo <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={form.data.title}
                                    onChange={(e) => form.setData('title', e.target.value)}
                                    placeholder="Contoh: Paket Nonton Berdua Hemat 20%"
                                    required
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                />
                                {form.errors.title && (
                                    <p className="text-rose-500 mt-1 font-semibold">{form.errors.title}</p>
                                )}
                            </div>

                            <div>
                                <label className="block font-bold text-text mb-1">
                                    Alamat URL (Slug, Opsional)
                                </label>
                                <input
                                    type="text"
                                    value={form.data.slug}
                                    onChange={(e) => form.setData('slug', e.target.value)}
                                    placeholder="Otomatis dari judul jika dikosongkan"
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs font-mono text-text focus:border-amber-500 focus:outline-none"
                                />
                                {form.errors.slug && (
                                    <p className="text-rose-500 mt-1 font-semibold">{form.errors.slug}</p>
                                )}
                            </div>
                        </div>

                        {/* Banner Image Upload */}
                        <div className="pt-3 border-t border-border text-xs space-y-3">
                            <div>
                                <label className="block font-bold text-text">
                                    Gambar Banner Promo (1200 × 600 px) {!isEdit && <span className="text-rose-500">*</span>}
                                </label>
                                <p className="text-[11px] text-text-muted mt-0.5">
                                    Format: JPG, PNG, atau WebP. Maksimal 2 MB. Rasio ideal 2:1.
                                </p>
                            </div>

                            <div className="flex flex-col sm:flex-row items-start gap-4">
                                {imagePreview ? (
                                    <div className="relative group shrink-0 w-full sm:w-64 h-32 rounded-xl overflow-hidden border border-border bg-surface-2">
                                        <img
                                            src={imagePreview}
                                            alt="Preview Banner"
                                            className="w-full h-full object-cover"
                                        />
                                        <button
                                            type="button"
                                            onClick={handleClearImage}
                                            className="absolute top-2 right-2 p-1 rounded-full bg-black/70 text-white hover:bg-rose-600 transition"
                                            title="Hapus foto"
                                        >
                                            <X className="size-4" />
                                        </button>
                                    </div>
                                ) : (
                                    <div
                                        onClick={() => fileInputRef.current?.click()}
                                        className="w-full sm:w-64 h-32 rounded-xl border-2 border-dashed border-border bg-surface-2 flex flex-col items-center justify-center p-4 cursor-pointer hover:border-amber-500 transition text-center"
                                    >
                                        <UploadCloud className="size-7 text-text-muted mb-1" />
                                        <span className="text-xs font-bold text-text">Pilih Berkas Banner</span>
                                        <span className="text-[10px] text-text-muted">Klik untuk upload</span>
                                    </div>
                                )}

                                <div className="space-y-2">
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        onChange={handleImageChange}
                                        className="hidden"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => fileInputRef.current?.click()}
                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-border bg-surface text-xs font-semibold text-text hover:border-amber-500 transition"
                                    >
                                        <UploadCloud className="size-3.5" />
                                        <span>Ganti Banner</span>
                                    </button>
                                    {form.errors.image && (
                                        <p className="text-rose-500 text-xs font-semibold">{form.errors.image}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Validity Period */}
                        <div className="grid gap-4 sm:grid-cols-2 pt-3 border-t border-border text-xs">
                            <div>
                                <label className="block font-bold text-text mb-1">Tanggal Mulai Berlaku</label>
                                <input
                                    type="date"
                                    value={form.data.start_date}
                                    onChange={(e) => form.setData('start_date', e.target.value)}
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                />
                                {form.errors.start_date && (
                                    <p className="text-rose-500 mt-1 font-semibold">{form.errors.start_date}</p>
                                )}
                            </div>

                            <div>
                                <label className="block font-bold text-text mb-1">Tanggal Berakhir</label>
                                <input
                                    type="date"
                                    value={form.data.end_date}
                                    onChange={(e) => form.setData('end_date', e.target.value)}
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                />
                                {form.errors.end_date && (
                                    <p className="text-rose-500 mt-1 font-semibold">{form.errors.end_date}</p>
                                )}
                            </div>
                        </div>

                        {/* Descriptions */}
                        <div className="space-y-4 pt-3 border-t border-border text-xs">
                            <div>
                                <label className="block font-bold text-text mb-1">Keterangan Singkat Promo</label>
                                <textarea
                                    rows={3}
                                    value={form.data.description}
                                    onChange={(e) => form.setData('description', e.target.value)}
                                    placeholder="Jelaskan keuntungan promo ini..."
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                />
                            </div>

                            <div>
                                <label className="block font-bold text-text mb-1">Syarat & Ketentuan</label>
                                <textarea
                                    rows={4}
                                    value={form.data.terms_conditions}
                                    onChange={(e) => form.setData('terms_conditions', e.target.value)}
                                    placeholder="Tuliskan syarat dan ketentuan promo (poin per baris)..."
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                />
                            </div>
                        </div>

                        {/* Order & Status */}
                        <div className="flex flex-wrap items-center gap-6 pt-3 border-t border-border text-xs">
                            <div className="w-32">
                                <label className="block font-bold text-text mb-1">Urutan Tampil</label>
                                <input
                                    type="number"
                                    min={0}
                                    value={form.data.sort_order}
                                    onChange={(e) => form.setData('sort_order', e.target.value)}
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                />
                            </div>

                            <label className="flex items-center gap-2 cursor-pointer mt-4">
                                <input
                                    type="checkbox"
                                    checked={form.data.is_active}
                                    onChange={(e) => form.setData('is_active', e.target.checked)}
                                    className="rounded border-border text-amber-500 focus:ring-amber-500"
                                />
                                <span className="font-semibold text-text">Aktifkan promo di situs</span>
                            </label>
                        </div>
                    </div>

                    {/* Submit Bar */}
                    <div className="flex items-center justify-end gap-3">
                        <Link
                            href="/admin/promos"
                            className="rounded-xl border border-border px-5 py-2.5 text-xs font-semibold text-text hover:bg-surface-2"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-xl bg-amber-500 px-6 py-2.5 text-xs font-bold text-slate-950 hover:bg-amber-400 shadow-xs disabled:opacity-50"
                        >
                            {form.processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Terbitkan Promo'}
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}

