import { useState, useRef } from 'react';
import { Link, Head, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import CinemaImage from '../../../Components/CinemaImage';
import {
    Film as FilmIcon,
    ArrowLeft,
    Upload,
    Play,
    AlertCircle,
    Check,
    ExternalLink,
    Eye,
} from 'lucide-react';

export default function Form({ film = {} }) {
    const isEdit = Boolean(film?.id);

    const { data, setData, post, processing, errors } = useForm({
        title: film.title || '',
        slug: film.slug || '',
        genre: film.genre || '',
        duration_minutes: film.duration_minutes || '',
        release_year: film.release_year || new Date().getFullYear(),
        age_rating: film.age_rating || 'SU',
        synopsis: film.synopsis || '',
        trailer_url: film.trailer_url || '',
        custom_badge: film.custom_badge || '',
        is_active: film.is_active !== undefined ? Boolean(film.is_active) : true,
        poster: null,
        _method: isEdit ? 'PUT' : 'POST',
    });

    const [posterPreview, setPosterPreview] = useState(
        film.poster_url || (film.poster_path ? `/storage/${film.poster_path}` : null)
    );
    const fileInputRef = useRef(null);

    const handlePosterChange = (e) => {
        const file = e.target.files?.[0];
        if (file) {
            setData('poster', file);
            setPosterPreview(URL.createObjectURL(file));
        }
    };

    const extractYoutubeId = (url) => {
        if (!url) return null;
        const match = url.match(
            /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts|live)\/|.*[?&]v=)|youtu\.be\/)([^"&?/\s]{11})/i
        );
        return match ? match[1] : null;
    };

    const youtubeId = extractYoutubeId(data.trailer_url);

    const handleSubmit = (e) => {
        e.preventDefault();
        const url = isEdit ? `/admin/films/${film.id}` : '/admin/films';

        post(url, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout title={isEdit ? `Ubah Film: ${film.title}` : 'Tambah Film Baru'}>
            <Head>
                <title>{isEdit ? `Ubah Film: ${film.title}` : 'Tambah Film Baru'} - Admin SynemaX</title>
            </Head>

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-border pb-5">
                    <div>
                        <Link
                            href="/admin/films"
                            className="inline-flex items-center gap-1.5 text-xs font-bold text-text-muted hover:text-amber-500 transition mb-1"
                        >
                            <ArrowLeft className="size-3.5" />
                            <span>Kembali ke Katalog</span>
                        </Link>
                        <h1 className="text-2xl font-black text-text tracking-tight">
                            {isEdit ? `Ubah Film: ${film.title}` : 'Tambah Film Baru'}
                        </h1>
                        <p className="text-xs text-text-muted mt-1">
                            Rasio poster standar 2:3 (600 × 900 px, maks 5 MB).
                        </p>
                    </div>

                    {isEdit && film.slug && (
                        <Link
                            href={`/film/${film.slug}`}
                            target="_blank"
                            className="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface px-4 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition shadow-xs"
                        >
                            <ExternalLink className="size-3.5" />
                            <span>Lihat di Situs</span>
                        </Link>
                    )}
                </div>

                {/* Form Errors Alert */}
                {Object.keys(errors).length > 0 && (
                    <div className="rounded-2xl border border-danger/40 bg-danger-surface p-4 text-xs font-semibold text-danger-text space-y-1">
                        <div className="flex items-center gap-2 font-bold mb-1">
                            <AlertCircle className="size-4" />
                            <span>Periksa {Object.keys(errors).length} kolom yang bermasalah:</span>
                        </div>
                        {Object.values(errors).map((err, i) => (
                            <p key={i}>• {err}</p>
                        ))}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Kolom Kiri: Informasi Utama (2 span) */}
                        <div className="lg:col-span-2 space-y-5 rounded-2xl border border-border bg-surface p-6 shadow-xs">
                            <h2 className="text-sm font-black uppercase tracking-wider text-text">Informasi Film</h2>

                            <div className="space-y-4 text-xs">
                                <div>
                                    <label htmlFor="title" className="block font-bold text-text mb-1">
                                        Judul Film <span className="text-danger">*</span>
                                    </label>
                                    <input
                                        id="title"
                                        type="text"
                                        required
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        placeholder="Contoh: Pengabdi Setan"
                                        className="w-full rounded-xl border border-border bg-surface-2 px-3.5 py-2.5 text-xs text-text focus:border-amber-500 focus:outline-none"
                                    />
                                    {errors.title && <p className="text-danger-text text-[11px] mt-1">{errors.title}</p>}
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label htmlFor="genre" className="block font-bold text-text mb-1">
                                            Genre Film (Pisahkan dengan koma) <span className="text-danger">*</span>
                                        </label>
                                        <input
                                            id="genre"
                                            type="text"
                                            required
                                            value={data.genre}
                                            onChange={(e) => setData('genre', e.target.value)}
                                            placeholder="Horor, Cerita Seru, Misteri"
                                            className="w-full rounded-xl border border-border bg-surface-2 px-3.5 py-2.5 text-xs text-text focus:border-amber-500 focus:outline-none"
                                        />
                                        {errors.genre && <p className="text-danger-text text-[11px] mt-1">{errors.genre}</p>}
                                    </div>

                                    <div>
                                        <label htmlFor="age_rating" className="block font-bold text-text mb-1">
                                            Batas Usia Penonton <span className="text-danger">*</span>
                                        </label>
                                        <select
                                            id="age_rating"
                                            value={data.age_rating}
                                            onChange={(e) => setData('age_rating', e.target.value)}
                                            className="w-full rounded-xl border border-border bg-surface-2 px-3.5 py-2.5 text-xs text-text focus:border-amber-500 focus:outline-none"
                                        >
                                            <option value="SU">Semua Umur (SU)</option>
                                            <option value="13+">Remaja (13+)</option>
                                            <option value="17+">Dewasa (17+)</option>
                                            <option value="21+">Dewasa Matang (21+)</option>
                                        </select>
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label htmlFor="duration_minutes" className="block font-bold text-text mb-1">
                                            Durasi (Menit) <span className="text-danger">*</span>
                                        </label>
                                        <input
                                            id="duration_minutes"
                                            type="number"
                                            min="1"
                                            required
                                            value={data.duration_minutes}
                                            onChange={(e) => setData('duration_minutes', e.target.value)}
                                            placeholder="Contoh: 115"
                                            className="w-full rounded-xl border border-border bg-surface-2 px-3.5 py-2.5 text-xs text-text focus:border-amber-500 focus:outline-none"
                                        />
                                        {errors.duration_minutes && (
                                            <p className="text-danger-text text-[11px] mt-1">{errors.duration_minutes}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="release_year" className="block font-bold text-text mb-1">
                                            Tahun Rilis <span className="text-danger">*</span>
                                        </label>
                                        <input
                                            id="release_year"
                                            type="number"
                                            min="1900"
                                            max="2099"
                                            required
                                            value={data.release_year}
                                            onChange={(e) => setData('release_year', e.target.value)}
                                            placeholder="Contoh: 2026"
                                            className="w-full rounded-xl border border-border bg-surface-2 px-3.5 py-2.5 text-xs text-text focus:border-amber-500 focus:outline-none"
                                        />
                                        {errors.release_year && (
                                            <p className="text-danger-text text-[11px] mt-1">{errors.release_year}</p>
                                        )}
                                    </div>
                                </div>

                                <div>
                                    <label htmlFor="synopsis" className="block font-bold text-text mb-1">
                                        Sinopsis Lengkap
                                    </label>
                                    <textarea
                                        id="synopsis"
                                        rows="4"
                                        value={data.synopsis}
                                        onChange={(e) => setData('synopsis', e.target.value)}
                                        placeholder="Tuliskan alur cerita singkat film ini..."
                                        className="w-full rounded-xl border border-border bg-surface-2 px-3.5 py-2.5 text-xs text-text focus:border-amber-500 focus:outline-none"
                                    />
                                    {errors.synopsis && <p className="text-danger-text text-[11px] mt-1">{errors.synopsis}</p>}
                                </div>

                                <div>
                                    <label htmlFor="trailer_url" className="block font-bold text-text mb-1">
                                        Tautan Trailer YouTube (Opsional)
                                    </label>
                                    <input
                                        id="trailer_url"
                                        type="url"
                                        value={data.trailer_url}
                                        onChange={(e) => setData('trailer_url', e.target.value)}
                                        placeholder="https://www.youtube.com/watch?v=..."
                                        className="w-full rounded-xl border border-border bg-surface-2 px-3.5 py-2.5 text-xs text-text focus:border-amber-500 focus:outline-none"
                                    />
                                    {youtubeId && (
                                        <div className="mt-3 aspect-video max-w-sm rounded-xl overflow-hidden border border-border shadow-xs">
                                            <iframe
                                                src={`https://www.youtube-nocookie.com/embed/${youtubeId}`}
                                                title="Trailer Preview"
                                                className="size-full"
                                                allowFullScreen
                                            />
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Kolom Kanan: Poster & Visibilitas (1 span) */}
                        <div className="space-y-5">
                            {/* Poster Card */}
                            <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs space-y-4">
                                <h2 className="text-sm font-black uppercase tracking-wider text-text">Poster Film</h2>

                                <div className="text-center">
                                    <div className="relative mx-auto aspect-[2/3] max-w-[200px] rounded-2xl overflow-hidden border border-border bg-slate-900 shadow-md flex items-center justify-center">
                                        {posterPreview ? (
                                            <img
                                                src={posterPreview}
                                                alt="Pratinjau Poster"
                                                className="size-full object-cover"
                                            />
                                        ) : (
                                            <div className="p-4 text-center text-text-muted">
                                                <FilmIcon className="size-10 mx-auto text-text-muted/40 mb-2" />
                                                <span className="text-[11px]">Belum ada poster</span>
                                            </div>
                                        )}
                                    </div>

                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        onChange={handlePosterChange}
                                        className="hidden"
                                    />

                                    <button
                                        type="button"
                                        onClick={() => fileInputRef.current?.click()}
                                        className="mt-3 inline-flex min-h-[38px] items-center gap-1.5 rounded-xl border border-border bg-surface-2 px-4 text-xs font-bold text-text hover:bg-surface transition"
                                    >
                                        <Upload className="size-3.5" />
                                        <span>{posterPreview ? 'Ganti Poster' : 'Unggah Poster'}</span>
                                    </button>
                                </div>
                            </div>

                            {/* Pengaturan & Label Khusus */}
                            <div className="rounded-2xl border border-border bg-surface p-5 shadow-xs space-y-4 text-xs">
                                <h2 className="text-sm font-black uppercase tracking-wider text-text">Pengaturan Tampil</h2>

                                <div>
                                    <label htmlFor="custom_badge" className="block font-bold text-text mb-1">
                                        Custom Badge (Opsional)
                                    </label>
                                    <input
                                        id="custom_badge"
                                        type="text"
                                        value={data.custom_badge}
                                        onChange={(e) => setData('custom_badge', e.target.value)}
                                        placeholder="Trending, Pilihan, Hot"
                                        className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                    />
                                    <p className="text-[10px] text-text-muted mt-1">Ditampilkan di sudut kartu film.</p>
                                </div>

                                <div className="pt-3 border-t border-border">
                                    <label className="flex items-center gap-3 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={data.is_active}
                                            onChange={(e) => setData('is_active', e.target.checked)}
                                            className="rounded text-amber-500 focus:ring-amber-500 size-4"
                                        />
                                        <div>
                                            <span className="font-bold text-text">Tampilkan di Katalog Publik</span>
                                            <p className="text-[11px] text-text-muted">
                                                Jika dimatikan, penonton tidak akan melihat film ini di website.
                                            </p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            {/* Tombol Simpan */}
                            <button
                                type="submit"
                                disabled={processing}
                                className="btn-gold-cinema w-full inline-flex min-h-[48px] items-center justify-center gap-2 rounded-xl px-6 text-sm font-black shadow-lg disabled:opacity-40"
                            >
                                <Check className="size-4" />
                                <span>{processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Tambahkan Film'}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}

