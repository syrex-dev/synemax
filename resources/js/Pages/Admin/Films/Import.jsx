import { useState } from 'react';
import { Link, Head, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { Download, Film as FilmIcon, ArrowLeft, Upload, FileText, CheckCircle2, AlertCircle } from 'lucide-react';

export default function Import({ recentBatches = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        csv_file: null,
        posters_zip: null,
        duplicate_strategy: 'skip',
        publish_on_import: true,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/admin/films/import/preview');
    };

    return (
        <AdminLayout title="Impor Film Massal" isFormPage={true}>
            <Head title="Impor Film Massal · Admin" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-text-muted mb-1">
                            <Link href="/admin/films" className="hover:text-amber-500 transition-colors">Katalog</Link>
                            <span>/</span>
                            <span className="text-text">Impor Massal</span>
                        </div>
                        <h1 className="text-2xl font-black text-text tracking-tight">Impor Film Massal</h1>
                        <p className="text-xs text-text-muted mt-1">
                            Tambahkan ratusan koleksi film sekaligus melalui file CSV dan arsip ZIP poster.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <a
                            href="/admin/films/import/template"
                            className="inline-flex min-h-[40px] items-center gap-2 rounded-xl border border-slate-300 dark:border-white/20 bg-surface px-4 py-2 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors shadow-xs"
                        >
                            <Download className="size-4" />
                            <span>Unduh Templat CSV</span>
                        </a>
                        <Link
                            href="/admin/films"
                            className="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-bold text-text hover:bg-surface-2 transition shadow-xs"
                        >
                            <ArrowLeft className="size-4" />
                            <span>Kembali ke Katalog</span>
                        </Link>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Form Upload */}
                    <div className="lg:col-span-2 space-y-6">
                        <div className="rounded-2xl border border-border bg-surface p-6 shadow-xs space-y-6">
                            <div>
                                <h2 className="text-base font-bold text-text">Langkah 1: Unggah Berkas</h2>
                                <p className="text-xs text-text-muted mt-1">
                                    Pilih berkas CSV data film dan opsional arsip ZIP poster (maksimal 2.000 baris dan 5 MB per berkas CSV).
                                </p>
                            </div>

                            <form onSubmit={handleSubmit} className="space-y-5">
                                {/* File CSV */}
                                <div className="space-y-1.5">
                                    <label htmlFor="csv_file" className="block text-xs font-bold text-text">
                                        Berkas CSV Data Film <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="file"
                                        id="csv_file"
                                        accept=".csv,.txt"
                                        required
                                        onChange={(e) => setData('csv_file', e.target.files[0])}
                                        className="block w-full text-xs text-text file:mr-4 file:rounded-xl file:border-0 file:bg-surface-2 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-text hover:file:bg-surface-raised file:cursor-pointer border border-border rounded-xl p-2 bg-surface-2/40"
                                    />
                                    {errors.csv_file && <p className="text-xs text-rose-500">{errors.csv_file}</p>}
                                    <p className="text-[11px] text-text-muted">
                                        Deteksi otomatis pemisah koma (,), titik koma (;), tab (\t), atau pipa (|), serta encoding UTF-8.
                                    </p>
                                </div>

                                {/* File ZIP Poster */}
                                <div className="space-y-1.5">
                                    <label htmlFor="posters_zip" className="block text-xs font-bold text-text">
                                        Arsip ZIP Poster Film <span className="text-text-muted font-normal">(Opsional)</span>
                                    </label>
                                    <input
                                        type="file"
                                        id="posters_zip"
                                        accept=".zip"
                                        onChange={(e) => setData('posters_zip', e.target.files[0])}
                                        className="block w-full text-xs text-text file:mr-4 file:rounded-xl file:border-0 file:bg-surface-2 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-text hover:file:bg-surface-raised file:cursor-pointer border border-border rounded-xl p-2 bg-surface-2/40"
                                    />
                                    {errors.posters_zip && <p className="text-xs text-rose-500">{errors.posters_zip}</p>}
                                    <p className="text-[11px] text-text-muted">
                                        Arsip ZIP berisi gambar JPG, PNG, atau WebP (rasio 2:3).
                                    </p>
                                </div>

                                {/* Strategi Duplikat */}
                                <div>
                                    <label htmlFor="duplicate_strategy" className="block text-xs font-bold text-text mb-1.5">
                                        Pilihan untuk Duplikat (Judul & Tahun Sama)
                                    </label>
                                    <select
                                        id="duplicate_strategy"
                                        value={data.duplicate_strategy}
                                        onChange={(e) => setData('duplicate_strategy', e.target.value)}
                                        className="w-full min-h-[44px] rounded-xl border border-border bg-surface-2 px-3.5 text-xs text-text focus:border-amber-500"
                                    >
                                        <option value="skip">Lewati (bawaan) — jangan ubah film yang sudah ada</option>
                                        <option value="update">Perbarui data film yang ada dengan isi berkas ini</option>
                                    </select>
                                </div>

                                {/* Publish switch */}
                                <div className="rounded-xl border border-border bg-surface-2/40 p-3.5">
                                    <label className="flex items-start gap-3 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={data.publish_on_import}
                                            onChange={(e) => setData('publish_on_import', e.target.checked)}
                                            className="mt-0.5 size-4 rounded border-border text-amber-500 focus:ring-amber-500/30"
                                        />
                                        <div>
                                            <span className="block text-xs font-bold text-text">Tampilkan film di katalog setelah diimpor</span>
                                            <span className="block text-[11px] text-text-muted mt-0.5">
                                                Film langsung muncul di katalog bioskop publik dan beranda.
                                            </span>
                                        </div>
                                    </label>
                                </div>

                                <div className="pt-2">
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="btn-gold-cinema inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl px-6 py-2.5 text-xs font-bold w-full sm:w-auto disabled:opacity-50"
                                    >
                                        <FileText className="size-4" />
                                        <span>{processing ? 'Memproses...' : 'Lanjut ke Langkah 2: Periksa Pratinjau'}</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {/* Panduan Kolom CSV */}
                    <div className="space-y-6">
                        <div className="rounded-2xl border border-border bg-surface p-6 shadow-xs space-y-4">
                            <h3 className="text-sm font-bold text-text">Format & Kolom CSV</h3>
                            <p className="text-[11px] text-text-muted">
                                Importer menerima nama kolom bahasa Indonesia maupun alias bahasa Inggris.
                            </p>
                            <div className="space-y-2.5 text-xs text-text-muted">
                                <div>
                                    <span className="font-bold text-text">judul / title (Wajib):</span>
                                    <p className="mt-0.5">Judul film, misalnya "Laskar Pelangi".</p>
                                </div>
                                <div>
                                    <span className="font-bold text-text">genre (Wajib):</span>
                                    <p className="mt-0.5">Genre baku (Aksi, Animasi, Drama, Fantasi, Horor, dll.).</p>
                                </div>
                                <div>
                                    <span className="font-bold text-text">durasi / duration_minutes (Wajib):</span>
                                    <p className="mt-0.5">Durasi angka bulat dalam menit.</p>
                                </div>
                                <div>
                                    <span className="font-bold text-text">klasifikasi / age_rating (Wajib):</span>
                                    <p className="mt-0.5">Rating usia: <strong>SU, 13+, atau 17+</strong>.</p>
                                </div>
                                <div>
                                    <span className="font-bold text-text">tahun / release_year (Opsional):</span>
                                    <p className="mt-0.5">Tahun rilis antara 1900 s/d tahun depan.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}

