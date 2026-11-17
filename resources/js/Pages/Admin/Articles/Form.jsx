import { useState, useRef } from 'react';
import { Link, Head, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import {
    Newspaper,
    ArrowLeft,
    UploadCloud,
    X,
    Eye,
    Calendar,
    Pin,
    AlertCircle,
} from 'lucide-react';

export default function Form({
    article,
    isEdit = false,
}) {
    const coverInputRef = useRef(null);
    const [coverPreview, setCoverPreview] = useState(
        article.cover_path ? `/storage/${article.cover_path}` : null
    );

    const form = useForm({
        _method: isEdit ? 'PUT' : 'POST',
        judul: article.judul || '',
        slug: article.slug || '',
        kategori: article.kategori || 'Berita',
        ringkasan: article.ringkasan || '',
        konten: article.konten || '',
        cover: null,
        delete_cover: false,
        status_publikasi: article.status_publikasi || 'terbit',
        tanggal_terbit: article.tanggal_terbit ? article.tanggal_terbit.slice(0, 10) : new Date().toISOString().slice(0, 10),
        is_pinned: article.is_pinned !== undefined ? Boolean(article.is_pinned) : false,
    });

    const handleCoverChange = (e) => {
        const file = e.target.files?.[0];
        if (file) {
            form.setData({ ...form.data, cover: file, delete_cover: false });
            const reader = new FileReader();
            reader.onload = (ev) => setCoverPreview(ev.target?.result);
            reader.readAsDataURL(file);
        }
    };

    const handleRemoveCover = () => {
        form.setData({ ...form.data, cover: null, delete_cover: true });
        setCoverPreview(null);
        if (coverInputRef.current) coverInputRef.current.value = '';
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const url = isEdit ? `/admin/berita/${article.id}` : '/admin/berita';
        form.post(url, {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout title={isEdit ? 'Ubah Artikel' : 'Tulis Artikel Baru'}>
            <Head title={`${isEdit ? 'Ubah Artikel' : 'Tulis Artikel Baru'} | Admin SynemaX`} />

            <div className="max-w-4xl mx-auto space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-text-muted mb-1">
                            <Link href="/admin/berita" className="hover:text-amber-500 transition-colors">
                                Kabar Bioskop
                            </Link>
                            <span>/</span>
                            <span className="text-text">{isEdit ? 'Ubah Artikel' : 'Tulis Baru'}</span>
                        </div>
                        <h1 className="text-2xl font-black tracking-tight text-text">
                            {isEdit ? `Ubah Artikel: ${article.judul}` : 'Tulis Artikel Baru'}
                        </h1>
                        <p className="text-xs text-text-muted mt-1">
                            Tulis publikasi berita bioskop atau pengumuman penting bagi penonton.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href="/admin/berita"
                            className="inline-flex min-h-[42px] items-center gap-1.5 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors shadow-xs"
                        >
                            <ArrowLeft className="size-4" />
                            <span>Kembali ke Daftar</span>
                        </Link>
                        {isEdit && article.slug && (
                            <a
                                href={`/kabar/${article.slug}`}
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

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="rounded-2xl border border-border bg-surface p-6 shadow-xs space-y-5 text-xs">
                        <h2 className="text-base font-bold text-text">Informasi Artikel</h2>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <label className="block font-bold text-text mb-1">
                                    Judul Artikel <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={form.data.judul}
                                    onChange={(e) => form.setData('judul', e.target.value)}
                                    placeholder="Contoh: Pembaruan Sistem Audio Dolby Atmos & Layar Baru SynemaX"
                                    required
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                />
                                {form.errors.judul && (
                                    <p className="text-rose-500 mt-1 font-semibold">{form.errors.judul}</p>
                                )}
                            </div>

                            <div>
                                <label className="block font-bold text-text mb-1">Kategori</label>
                                <select
                                    value={form.data.kategori}
                                    onChange={(e) => form.setData('kategori', e.target.value)}
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs font-semibold text-text focus:border-amber-500 focus:outline-none"
                                >
                                    <option value="Berita">Berita</option>
                                    <option value="Pengumuman">Pengumuman</option>
                                </select>
                            </div>

                            <div>
                                <label className="block font-bold text-text mb-1">Slug URL (Opsional)</label>
                                <input
                                    type="text"
                                    value={form.data.slug}
                                    onChange={(e) => form.setData('slug', e.target.value)}
                                    placeholder="Otomatis dari judul jika dikosongkan"
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs font-mono text-text focus:border-amber-500 focus:outline-none"
                                />
                            </div>
                        </div>

                        {/* Cover Image Upload */}
                        <div className="pt-3 border-t border-border space-y-3">
                            <div>
                                <label className="block font-bold text-text">
                                    Gambar Sampul / Cover (JPG/PNG/WebP, Maks 2 MB)
                                </label>
                                <p className="text-[11px] text-text-muted mt-0.5">
                                    Rasio ideal 16:9 atau 2:1 sebagai thumbnail dan banner artikel.
                                </p>
                            </div>

                            <div className="flex flex-col sm:flex-row items-start gap-4">
                                {coverPreview ? (
                                    <div className="relative group shrink-0 w-48 h-28 rounded-xl overflow-hidden border border-border bg-surface-2">
                                        <img
                                            src={coverPreview}
                                            alt="Preview Cover"
                                            className="w-full h-full object-cover"
                                        />
                                        <button
                                            type="button"
                                            onClick={handleRemoveCover}
                                            className="absolute top-2 right-2 p-1 rounded-full bg-black/70 text-white hover:bg-rose-600 transition"
                                        >
                                            <X className="size-4" />
                                        </button>
                                    </div>
                                ) : (
                                    <div
                                        onClick={() => coverInputRef.current?.click()}
                                        className="w-48 h-28 rounded-xl border-2 border-dashed border-border bg-surface-2 flex flex-col items-center justify-center p-3 cursor-pointer hover:border-amber-500 transition text-center"
                                    >
                                        <UploadCloud className="size-6 text-text-muted mb-1" />
                                        <span className="text-xs font-bold text-text">Upload Cover</span>
                                    </div>
                                )}

                                <div>
                                    <input
                                        ref={coverInputRef}
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        onChange={handleCoverChange}
                                        className="hidden"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => coverInputRef.current?.click()}
                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-border bg-surface text-xs font-semibold text-text hover:border-amber-500 transition"
                                    >
                                        <UploadCloud className="size-3.5" />
                                        <span>Pilih Gambar Sampul</span>
                                    </button>
                                    {form.errors.cover && (
                                        <p className="text-rose-500 text-xs font-semibold mt-1">{form.errors.cover}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Summary & Content */}
                        <div className="pt-3 border-t border-border space-y-4">
                            <div>
                                <label className="block font-bold text-text mb-1">
                                    Ringkasan Singkat (Lead / Excerpt)
                                </label>
                                <textarea
                                    rows={2}
                                    value={form.data.ringkasan}
                                    onChange={(e) => form.setData('ringkasan', e.target.value)}
                                    placeholder="Ringkasan 1-2 kalimat untuk preview di beranda..."
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                />
                            </div>

                            <div>
                                <label className="block font-bold text-text mb-1">
                                    Konten Isi Artikel <span className="text-rose-500">*</span>
                                </label>
                                <textarea
                                    rows={10}
                                    value={form.data.konten}
                                    onChange={(e) => form.setData('konten', e.target.value)}
                                    placeholder="Tuliskan isi artikel lengkap di sini..."
                                    required
                                    className="w-full rounded-xl border border-border bg-surface-2 p-3 text-xs text-text focus:border-amber-500 focus:outline-none font-sans leading-relaxed"
                                />
                                {form.errors.konten && (
                                    <p className="text-rose-500 mt-1 font-semibold">{form.errors.konten}</p>
                                )}
                            </div>
                        </div>

                        {/* Publication Date & Status */}
                        <div className="pt-3 border-t border-border grid gap-4 sm:grid-cols-3">
                            <div>
                                <label className="block font-bold text-text mb-1">Tanggal Terbit</label>
                                <input
                                    type="date"
                                    value={form.data.tanggal_terbit}
                                    onChange={(e) => form.setData('tanggal_terbit', e.target.value)}
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                />
                            </div>

                            <div>
                                <label className="block font-bold text-text mb-1">Status Publikasi</label>
                                <select
                                    value={form.data.status_publikasi}
                                    onChange={(e) => form.setData('status_publikasi', e.target.value)}
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs font-semibold text-text focus:border-amber-500 focus:outline-none"
                                >
                                    <option value="terbit">Terbitkan Langsung</option>
                                    <option value="draft">Simpan Sebagai Draft</option>
                                </select>
                            </div>

                            <div className="flex items-center pt-5">
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={form.data.is_pinned}
                                        onChange={(e) => form.setData('is_pinned', e.target.checked)}
                                        className="rounded border-border text-amber-500 focus:ring-amber-500"
                                    />
                                    <span className="font-semibold text-text">Sematkan di paling atas</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {/* Submit Bar */}
                    <div className="flex items-center justify-end gap-3">
                        <Link
                            href="/admin/berita"
                            className="rounded-xl border border-border px-5 py-2.5 text-xs font-semibold text-text hover:bg-surface-2"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-xl bg-amber-500 px-6 py-2.5 text-xs font-bold text-slate-950 hover:bg-amber-400 shadow-xs disabled:opacity-50"
                        >
                            {form.processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Terbitkan Artikel'}
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}

