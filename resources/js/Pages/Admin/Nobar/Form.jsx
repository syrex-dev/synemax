import { useState, useRef } from 'react';
import { Link, Head, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import {
    Tv,
    ArrowLeft,
    UploadCloud,
    X,
    Eye,
    Calendar,
    MapPin,
    Clock,
    AlertCircle,
} from 'lucide-react';

export default function Form({
    event,
    isEdit = false,
}) {
    const posterInputRef = useRef(null);
    const [posterPreview, setPosterPreview] = useState(
        event.poster_path ? `/storage/${event.poster_path}` : null
    );

    const form = useForm({
        _method: isEdit ? 'PUT' : 'POST',
        judul: event.judul || '',
        slug: event.slug || '',
        kompetisi: event.kompetisi || '',
        tim_a: event.tim_a || '',
        tim_b: event.tim_b || '',
        kickoff_at: event.kickoff_at ? event.kickoff_at.replace(' ', 'T').slice(0, 16) : '',
        durasi_menit: event.durasi_menit || 150,
        lokasi_nama: event.lokasi_nama || 'SynemaX Home Cinema Lounge',
        alamat_patokan: event.alamat_patokan || 'Jl. Sinema No. 12, Lantai 2 (Seberang Taman Kota)',
        maps_url: event.maps_url || 'https://maps.google.com/?q=SynemaX',
        is_free: event.is_free !== undefined ? Boolean(event.is_free) : true,
        harga_info: event.harga_info || '',
        catatan: event.catatan || '',
        poster_alt: event.poster_alt || '',
        poster: null,
        delete_poster: false,
        status_publikasi: event.status_publikasi || 'terbit',
        status_acara: event.status_acara || 'aktif',
        alasan_status: event.alasan_status || '',
        is_pinned: event.is_pinned !== undefined ? Boolean(event.is_pinned) : false,
    });

    const handlePosterChange = (e) => {
        const file = e.target.files?.[0];
        if (file) {
            form.setData({ ...form.data, poster: file, delete_poster: false });
            const reader = new FileReader();
            reader.onload = (ev) => setPosterPreview(ev.target?.result);
            reader.readAsDataURL(file);
        }
    };

    const handleRemovePoster = () => {
        form.setData({ ...form.data, poster: null, delete_poster: true });
        setPosterPreview(null);
        if (posterInputRef.current) posterInputRef.current.value = '';
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const url = isEdit ? `/admin/nobar/${event.id}` : '/admin/nobar';
        form.post(url, {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout title={isEdit ? 'Ubah Acara Nobar' : 'Tambah Acara Nobar'}>
            <Head title={`${isEdit ? 'Ubah Acara Nobar' : 'Tambah Acara Nobar'} | Admin SynemaX`} />

            <div className="max-w-4xl mx-auto space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-text-muted mb-1">
                            <Link href="/admin/nobar" className="hover:text-amber-500 transition-colors">
                                Jadwal Nobar
                            </Link>
                            <span>/</span>
                            <span className="text-text">{isEdit ? 'Ubah Acara' : 'Tambah Baru'}</span>
                        </div>
                        <h1 className="text-2xl font-black tracking-tight text-text">
                            {isEdit ? `Ubah Nobar: ${event.judul}` : 'Tambah Acara Nobar'}
                        </h1>
                        <p className="text-xs text-text-muted mt-1">
                            Lengkapi informasi siaran pertandingan / acara nonton bareng komunitas.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href="/admin/nobar"
                            className="inline-flex min-h-[42px] items-center gap-1.5 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors shadow-xs"
                        >
                            <ArrowLeft className="size-4" />
                            <span>Kembali ke Daftar</span>
                        </Link>
                        {isEdit && event.slug && (
                            <a
                                href={`/nobar/${event.slug}`}
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
                    <div className="rounded-2xl border border-border bg-surface p-6 shadow-xs space-y-5 text-xs">
                        <h2 className="text-base font-bold text-text">Detail Pertandingan & Acara</h2>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <label className="block font-bold text-text mb-1">
                                    Judul Acara <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={form.data.judul}
                                    onChange={(e) => form.setData('judul', e.target.value)}
                                    placeholder="Contoh: Nobar Final Liga Champions: Real Madrid vs Dortmund"
                                    required
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                />
                                {form.errors.judul && (
                                    <p className="text-rose-500 mt-1 font-semibold">{form.errors.judul}</p>
                                )}
                            </div>

                            <div>
                                <label className="block font-bold text-text mb-1">
                                    Kompetisi / Liga
                                </label>
                                <input
                                    type="text"
                                    value={form.data.kompetisi}
                                    onChange={(e) => form.setData('kompetisi', e.target.value)}
                                    placeholder="Contoh: UEFA Champions League, Premier League"
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                />
                            </div>

                            <div>
                                <label className="block font-bold text-text mb-1">
                                    Slug URL (Opsional)
                                </label>
                                <input
                                    type="text"
                                    value={form.data.slug}
                                    onChange={(e) => form.setData('slug', e.target.value)}
                                    placeholder="Otomatis dari judul jika dikosongkan"
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs font-mono text-text focus:border-amber-500 focus:outline-none"
                                />
                            </div>

                            <div>
                                <label className="block font-bold text-text mb-1">
                                    Tim A / Tuan Rumah <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={form.data.tim_a}
                                    onChange={(e) => form.setData('tim_a', e.target.value)}
                                    placeholder="Contoh: Real Madrid"
                                    required
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                />
                                {form.errors.tim_a && (
                                    <p className="text-rose-500 mt-1 font-semibold">{form.errors.tim_a}</p>
                                )}
                            </div>

                            <div>
                                <label className="block font-bold text-text mb-1">
                                    Tim B / Tim Tamu
                                </label>
                                <input
                                    type="text"
                                    value={form.data.tim_b}
                                    onChange={(e) => form.setData('tim_b', e.target.value)}
                                    placeholder="Contoh: Borussia Dortmund"
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                />
                            </div>

                            <div>
                                <label className="block font-bold text-text mb-1">
                                    Waktu Kick-off <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="datetime-local"
                                    value={form.data.kickoff_at}
                                    onChange={(e) => form.setData('kickoff_at', e.target.value)}
                                    required
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                />
                                {form.errors.kickoff_at && (
                                    <p className="text-rose-500 mt-1 font-semibold">{form.errors.kickoff_at}</p>
                                )}
                            </div>

                            <div>
                                <label className="block font-bold text-text mb-1">
                                    Estimasi Durasi (Menit) <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    min={30}
                                    max={360}
                                    value={form.data.durasi_menit}
                                    onChange={(e) => form.setData('durasi_menit', e.target.value)}
                                    required
                                    className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                />
                            </div>
                        </div>

                        {/* Location */}
                        <div className="pt-3 border-t border-border space-y-4">
                            <h3 className="font-bold text-text text-sm">Lokasi & Navigasi</h3>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block font-bold text-text mb-1">
                                        Nama Lokasi <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        value={form.data.lokasi_nama}
                                        onChange={(e) => form.setData('lokasi_nama', e.target.value)}
                                        required
                                        className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <label className="block font-bold text-text mb-1">
                                        URL Google Maps (HTTPS) <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="url"
                                        value={form.data.maps_url}
                                        onChange={(e) => form.setData('maps_url', e.target.value)}
                                        placeholder="https://maps.google.com/?q=..."
                                        required
                                        className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                    />
                                    {form.errors.maps_url && (
                                        <p className="text-rose-500 mt-1 font-semibold">{form.errors.maps_url}</p>
                                    )}
                                </div>

                                <div className="sm:col-span-2">
                                    <label className="block font-bold text-text mb-1">
                                        Alamat / Patokan Lengkap <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        value={form.data.alamat_patokan}
                                        onChange={(e) => form.setData('alamat_patokan', e.target.value)}
                                        required
                                        className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Pricing & Ticket Info */}
                        <div className="pt-3 border-t border-border space-y-4">
                            <h3 className="font-bold text-text text-sm">Tiket & HTM</h3>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block font-bold text-text mb-1">Jenis HTM</label>
                                    <div className="flex items-center gap-4 pt-1">
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="radio"
                                                name="is_free"
                                                checked={form.data.is_free === true}
                                                onChange={() => form.setData('is_free', true)}
                                                className="text-amber-500 focus:ring-amber-500"
                                            />
                                            <span className="font-semibold text-text">Gratis / Free Entry</span>
                                        </label>
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="radio"
                                                name="is_free"
                                                checked={form.data.is_free === false}
                                                onChange={() => form.setData('is_free', false)}
                                                className="text-amber-500 focus:ring-amber-500"
                                            />
                                            <span className="font-semibold text-text">Berbayar / F&B Minimum</span>
                                        </label>
                                    </div>
                                </div>

                                {!form.data.is_free && (
                                    <div>
                                        <label className="block font-bold text-text mb-1">
                                            Keterangan HTM / Biaya
                                        </label>
                                        <input
                                            type="text"
                                            value={form.data.harga_info}
                                            onChange={(e) => form.setData('harga_info', e.target.value)}
                                            placeholder="Contoh: Rp 25.000 (Termasuk Minuman)"
                                            className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                        />
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Poster Upload */}
                        <div className="pt-3 border-t border-border space-y-3">
                            <div>
                                <label className="block font-bold text-text">
                                    Poster Acara Nobar (JPG/PNG/WebP, Maks 3 MB)
                                </label>
                                <p className="text-[11px] text-text-muted mt-0.5">
                                    Resolusi sisi terpendek minimal 600 px.
                                </p>
                            </div>

                            <div className="flex flex-col sm:flex-row items-start gap-4">
                                {posterPreview ? (
                                    <div className="relative group shrink-0 w-36 h-48 rounded-xl overflow-hidden border border-border bg-surface-2">
                                        <img
                                            src={posterPreview}
                                            alt="Preview Poster"
                                            className="w-full h-full object-cover"
                                        />
                                        <button
                                            type="button"
                                            onClick={handleRemovePoster}
                                            className="absolute top-2 right-2 p-1 rounded-full bg-black/70 text-white hover:bg-rose-600 transition"
                                        >
                                            <X className="size-4" />
                                        </button>
                                    </div>
                                ) : (
                                    <div
                                        onClick={() => posterInputRef.current?.click()}
                                        className="w-36 h-48 rounded-xl border-2 border-dashed border-border bg-surface-2 flex flex-col items-center justify-center p-3 cursor-pointer hover:border-amber-500 transition text-center"
                                    >
                                        <UploadCloud className="size-6 text-text-muted mb-1" />
                                        <span className="text-xs font-bold text-text">Upload Poster</span>
                                        <span className="text-[10px] text-text-muted">Min 600 px</span>
                                    </div>
                                )}

                                <div className="space-y-3 flex-1">
                                    <input
                                        ref={posterInputRef}
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        onChange={handlePosterChange}
                                        className="hidden"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => posterInputRef.current?.click()}
                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-border bg-surface text-xs font-semibold text-text hover:border-amber-500 transition"
                                    >
                                        <UploadCloud className="size-3.5" />
                                        <span>Pilih Poster</span>
                                    </button>

                                    <div>
                                        <label className="block font-bold text-text mb-1">
                                            Alt Teks Gambar Poster
                                        </label>
                                        <input
                                            type="text"
                                            value={form.data.poster_alt}
                                            onChange={(e) => form.setData('poster_alt', e.target.value)}
                                            placeholder="Deskripsi poster untuk tunanetra..."
                                            className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                        />
                                    </div>
                                    {form.errors.poster && (
                                        <p className="text-rose-500 text-xs font-semibold">{form.errors.poster}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Status & Options */}
                        <div className="pt-3 border-t border-border space-y-4">
                            <h3 className="font-bold text-text text-sm">Status & Pengaturan Publikasi</h3>

                            <div className="grid gap-4 sm:grid-cols-3">
                                <div>
                                    <label className="block font-bold text-text mb-1">Status Publikasi</label>
                                    <select
                                        value={form.data.status_publikasi}
                                        onChange={(e) => form.setData('status_publikasi', e.target.value)}
                                        className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs font-semibold text-text focus:border-amber-500 focus:outline-none"
                                    >
                                        <option value="terbit">Terbitkan di Web</option>
                                        <option value="draft">Simpan Sebagai Draft</option>
                                    </select>
                                </div>

                                <div>
                                    <label className="block font-bold text-text mb-1">Status Acara</label>
                                    <select
                                        value={form.data.status_acara}
                                        onChange={(e) => form.setData('status_acara', e.target.value)}
                                        className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs font-semibold text-text focus:border-amber-500 focus:outline-none"
                                    >
                                        <option value="aktif">Aktif Sesuai Jadwal</option>
                                        <option value="ditunda">Ditunda</option>
                                        <option value="dibatalkan">Dibatalkan</option>
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
                                        <span className="font-semibold text-text">Pin di paling atas</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Submit Bar */}
                    <div className="flex items-center justify-end gap-3">
                        <Link
                            href="/admin/nobar"
                            className="rounded-xl border border-border px-5 py-2.5 text-xs font-semibold text-text hover:bg-surface-2"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-xl bg-amber-500 px-6 py-2.5 text-xs font-bold text-slate-950 hover:bg-amber-400 shadow-xs disabled:opacity-50"
                        >
                            {form.processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Terbitkan Nobar'}
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}

