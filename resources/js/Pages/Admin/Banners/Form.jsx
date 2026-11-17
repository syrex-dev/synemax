import { useState, useRef } from 'react';
import { Link, Head, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import {
    Sparkles,
    ArrowLeft,
    UploadCloud,
    X,
    Eye,
    AlertCircle,
} from 'lucide-react';

export default function Form({
    banner,
    isEdit = false,
    films = [],
    promos = [],
    nobars = [],
}) {
    const desktopInputRef = useRef(null);
    const mobileInputRef = useRef(null);

    const [desktopPreview, setDesktopPreview] = useState(
        banner.image_path ? `/storage/${banner.image_path}` : null
    );
    const [mobilePreview, setMobilePreview] = useState(
        banner.mobile_image_path ? `/storage/${banner.mobile_image_path}` : null
    );

    const form = useForm({
        _method: isEdit ? 'PUT' : 'POST',
        title: banner.title || '',
        type: banner.type || 'hero',
        image: null,
        mobile_image: null,
        target_type: banner.target_type || 'none',
        target_id: banner.target_id || '',
        cta_url: banner.cta_url || '',
        is_active: banner.is_active !== undefined ? Boolean(banner.is_active) : true,
        sort_order: banner.sort_order || 0,
    });

    const handleDesktopImageChange = (e) => {
        const file = e.target.files?.[0];
        if (file) {
            form.setData('image', file);
            const reader = new FileReader();
            reader.onload = (ev) => setDesktopPreview(ev.target?.result);
            reader.readAsDataURL(file);
        }
    };

    const handleMobileImageChange = (e) => {
        const file = e.target.files?.[0];
        if (file) {
            form.setData('mobile_image', file);
            const reader = new FileReader();
            reader.onload = (ev) => setMobilePreview(ev.target?.result);
            reader.readAsDataURL(file);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const url = isEdit ? `/admin/banners/${banner.id}` : '/admin/banners';
        form.post(url, {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout title={isEdit ? 'Ubah Slide Banner' : 'Tambah Slide Hero'}>
            <Head title={`${isEdit ? 'Ubah Slide Banner' : 'Tambah Slide Hero'} | Admin SynemaX`} />

            <div className="max-w-4xl mx-auto space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-text-muted mb-1">
                            <Link href="/admin/banners" className="hover:text-amber-500 transition-colors">
                                Banner Hero
                            </Link>
                            <span>/</span>
                            <span className="text-text">{isEdit ? 'Ubah Slide' : 'Tambah Baru'}</span>
                        </div>
                        <h1 className="text-2xl font-black tracking-tight text-text">
                            {isEdit ? `Ubah Banner: ${banner.title}` : 'Tambah Slide Hero Baru'}
                        </h1>
                        <p className="text-xs text-text-muted mt-1">
                            Visual layar lebar beranda (1600 × 680 px) tanpa teks overlay.
                        </p>
                    </div>

                    <Link
                        href="/admin/banners"
                        className="inline-flex min-h-[42px] items-center gap-1.5 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors shadow-xs"
                    >
                        <ArrowLeft className="size-4" />
                        <span>Kembali ke Daftar</span>
                    </Link>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="rounded-2xl border border-border bg-surface p-6 shadow-xs space-y-5 text-xs">
                        <h2 className="text-base font-bold text-text">Informasi Banner</h2>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label className="block font-bold text-text mb-1">
                                    Judul Banner <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={form.data.title}
                                    onChange={(e) => form.setData('title', e.target.value)}
                                    placeholder="Contoh: Malam di Bukit Bintang"
                                    required
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                />
                                {form.errors.title && (
                                    <p className="text-rose-500 mt-1 font-semibold">{form.errors.title}</p>
                                )}
                            </div>

                            <div>
                                <label className="block font-bold text-text mb-1">
                                    Tipe Penempatan <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    value={form.data.type}
                                    onChange={(e) => form.setData('type', e.target.value)}
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs font-semibold text-text focus:border-amber-500 focus:outline-none"
                                >
                                    <option value="hero">Hero Carousel (Beranda Utama)</option>
                                    <option value="promo">Promo Banner</option>
                                    <option value="snack">Snack Bar Banner</option>
                                </select>
                            </div>
                        </div>

                        {/* Desktop Image Upload */}
                        <div className="pt-3 border-t border-border space-y-3">
                            <div>
                                <label className="block font-bold text-text">
                                    Gambar Layar Lebar Desktop (1600 × 680 px) {!isEdit && <span className="text-rose-500">*</span>}
                                </label>
                                <p className="text-[11px] text-text-muted mt-0.5">
                                    Format: JPG, PNG, atau WebP. Maksimal 3 MB. Resolusi rekomendasi 1600 × 680 px (rasio ~2.35:1).
                                </p>
                            </div>

                            <div className="flex flex-col sm:flex-row items-start gap-4">
                                {desktopPreview ? (
                                    <div className="relative group shrink-0 w-full sm:w-72 h-32 rounded-xl overflow-hidden border border-border bg-surface-2">
                                        <img
                                            src={desktopPreview}
                                            alt="Preview Desktop"
                                            className="w-full h-full object-cover"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => {
                                                form.setData('image', null);
                                                setDesktopPreview(banner.image_path ? `/storage/${banner.image_path}` : null);
                                                if (desktopInputRef.current) desktopInputRef.current.value = '';
                                            }}
                                            className="absolute top-2 right-2 p-1 rounded-full bg-black/70 text-white hover:bg-rose-600 transition"
                                        >
                                            <X className="size-4" />
                                        </button>
                                    </div>
                                ) : (
                                    <div
                                        onClick={() => desktopInputRef.current?.click()}
                                        className="w-full sm:w-72 h-32 rounded-xl border-2 border-dashed border-border bg-surface-2 flex flex-col items-center justify-center p-4 cursor-pointer hover:border-amber-500 transition text-center"
                                    >
                                        <UploadCloud className="size-7 text-text-muted mb-1" />
                                        <span className="text-xs font-bold text-text">Upload Gambar Desktop</span>
                                        <span className="text-[10px] text-text-muted">1600 × 680 px</span>
                                    </div>
                                )}

                                <div>
                                    <input
                                        ref={desktopInputRef}
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        onChange={handleDesktopImageChange}
                                        className="hidden"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => desktopInputRef.current?.click()}
                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-border bg-surface text-xs font-semibold text-text hover:border-amber-500 transition"
                                    >
                                        <UploadCloud className="size-3.5" />
                                        <span>Pilih Gambar Desktop</span>
                                    </button>
                                    {form.errors.image && (
                                        <p className="text-rose-500 text-xs font-semibold mt-1">{form.errors.image}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Mobile Image Upload (Optional) */}
                        <div className="pt-3 border-t border-border space-y-3">
                            <div>
                                <label className="block font-bold text-text">
                                    Gambar Khusus HP / Mobile (Opsional, Rasio 4:3 / 1:1)
                                </label>
                                <p className="text-[11px] text-text-muted mt-0.5">
                                    Jika diunggah, gambar ini akan ditampilkan pada layar handphone agar tidak terpotong.
                                </p>
                            </div>

                            <div className="flex flex-col sm:flex-row items-start gap-4">
                                {mobilePreview ? (
                                    <div className="relative group shrink-0 w-32 h-32 rounded-xl overflow-hidden border border-border bg-surface-2">
                                        <img
                                            src={mobilePreview}
                                            alt="Preview Mobile"
                                            className="w-full h-full object-cover"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => {
                                                form.setData('mobile_image', null);
                                                setMobilePreview(banner.mobile_image_path ? `/storage/${banner.mobile_image_path}` : null);
                                                if (mobileInputRef.current) mobileInputRef.current.value = '';
                                            }}
                                            className="absolute top-2 right-2 p-1 rounded-full bg-black/70 text-white hover:bg-rose-600 transition"
                                        >
                                            <X className="size-4" />
                                        </button>
                                    </div>
                                ) : (
                                    <div
                                        onClick={() => mobileInputRef.current?.click()}
                                        className="w-32 h-32 rounded-xl border-2 border-dashed border-border bg-surface-2 flex flex-col items-center justify-center p-2 cursor-pointer hover:border-amber-500 transition text-center"
                                    >
                                        <UploadCloud className="size-5 text-text-muted mb-1" />
                                        <span className="text-[11px] font-bold text-text">Upload HP</span>
                                    </div>
                                )}

                                <div>
                                    <input
                                        ref={mobileInputRef}
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        onChange={handleMobileImageChange}
                                        className="hidden"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => mobileInputRef.current?.click()}
                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-border bg-surface text-xs font-semibold text-text hover:border-amber-500 transition"
                                    >
                                        <UploadCloud className="size-3.5" />
                                        <span>Pilih Gambar HP</span>
                                    </button>
                                    {form.errors.mobile_image && (
                                        <p className="text-rose-500 text-xs font-semibold mt-1">{form.errors.mobile_image}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Click Target Link */}
                        <div className="pt-3 border-t border-border space-y-4">
                            <h3 className="font-bold text-text text-sm">Aksi Saat Banner Diklik</h3>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block font-bold text-text mb-1">Jenis Tujuan</label>
                                    <select
                                        value={form.data.target_type}
                                        onChange={(e) => {
                                            form.setData({
                                                ...form.data,
                                                target_type: e.target.value,
                                                target_id: '',
                                            });
                                        }}
                                        className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs font-semibold text-text focus:border-amber-500 focus:outline-none"
                                    >
                                        <option value="none">Hanya Tampilan (Tanpa Tautan)</option>
                                        <option value="film">Halaman Film</option>
                                        <option value="promo">Halaman Promo</option>
                                        <option value="nobar">Halaman Nobar</option>
                                        <option value="custom">Tautan / URL Kustom</option>
                                    </select>
                                </div>

                                {form.data.target_type === 'film' && (
                                    <div>
                                        <label className="block font-bold text-text mb-1">Pilih Film</label>
                                        <select
                                            value={form.data.target_id}
                                            onChange={(e) => form.setData('target_id', e.target.value)}
                                            className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs font-semibold text-text focus:border-amber-500 focus:outline-none"
                                        >
                                            <option value="">-- Pilih Film --</option>
                                            {films.map((f) => (
                                                <option key={f.id} value={f.slug || f.id}>
                                                    {f.title}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                )}

                                {form.data.target_type === 'promo' && (
                                    <div>
                                        <label className="block font-bold text-text mb-1">Pilih Promo</label>
                                        <select
                                            value={form.data.target_id}
                                            onChange={(e) => form.setData('target_id', e.target.value)}
                                            className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs font-semibold text-text focus:border-amber-500 focus:outline-none"
                                        >
                                            <option value="">-- Pilih Promo --</option>
                                            {promos.map((p) => (
                                                <option key={p.id} value={p.slug || p.id}>
                                                    {p.title}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                )}

                                {form.data.target_type === 'nobar' && (
                                    <div>
                                        <label className="block font-bold text-text mb-1">Pilih Event Nobar</label>
                                        <select
                                            value={form.data.target_id}
                                            onChange={(e) => form.setData('target_id', e.target.value)}
                                            className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs font-semibold text-text focus:border-amber-500 focus:outline-none"
                                        >
                                            <option value="">-- Pilih Event Nobar --</option>
                                            {nobars.map((n) => (
                                                <option key={n.id} value={n.slug || n.id}>
                                                    {n.judul}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                )}

                                {form.data.target_type === 'custom' && (
                                    <div>
                                        <label className="block font-bold text-text mb-1">Tautan URL (https://...)</label>
                                        <input
                                            type="url"
                                            value={form.data.cta_url}
                                            onChange={(e) => form.setData('cta_url', e.target.value)}
                                            placeholder="https://example.com/halaman"
                                            className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                        />
                                        {form.errors.cta_url && (
                                            <p className="text-rose-500 mt-1 font-semibold">{form.errors.cta_url}</p>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Order & Status */}
                        <div className="flex flex-wrap items-center gap-6 pt-3 border-t border-border">
                            <div className="w-32">
                                <label className="block font-bold text-text mb-1">Urutan Slide</label>
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
                                <span className="font-semibold text-text">Tampilkan slide ini di beranda</span>
                            </label>
                        </div>
                    </div>

                    {/* Submit Bar */}
                    <div className="flex items-center justify-end gap-3">
                        <Link
                            href="/admin/banners"
                            className="rounded-xl border border-border px-5 py-2.5 text-xs font-semibold text-text hover:bg-surface-2"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-xl bg-amber-500 px-6 py-2.5 text-xs font-bold text-slate-950 hover:bg-amber-400 shadow-xs disabled:opacity-50"
                        >
                            {form.processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Terbitkan Slide'}
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}

