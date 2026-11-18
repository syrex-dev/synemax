import { Link, Head } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import { ChevronLeft, Calendar, ArrowRight, Tag, Share2 } from 'lucide-react';

export default function Show({ promo }) {
    const img = promo?.image_path ? (promo.image_path.startsWith('http') ? promo.image_path : `/storage/${promo.image_path}`) : null;

    return (
        <PublicLayout title={promo?.title}>
            <Head title={promo?.title}>
                <meta
                    name="description"
                    content={promo?.description ? promo.description.slice(0, 150) : `Lihat promo ${promo?.title} di SynemaX.`}
                />
            </Head>

            <div className="relative overflow-hidden">
                {img && (
                    <div
                        className="pointer-events-none absolute -top-24 inset-x-0 h-96 -z-10 bg-cover bg-center opacity-30 dark:opacity-20 blur-3xl"
                        style={{ backgroundImage: `url('${img}')` }}
                        aria-hidden="true"
                    />
                )}

                <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 space-y-8">
                    {/* Back Link */}
                    <div>
                        <Link
                            href="/promo"
                            className="inline-flex items-center gap-1.5 text-xs font-bold text-amber-500 hover:text-amber-400 transition"
                        >
                            <ChevronLeft className="size-4" />
                            <span>Kembali ke daftar promo</span>
                        </Link>
                    </div>

                    {/* Header Details */}
                    <div className="space-y-3">
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="inline-flex items-center gap-1.5 rounded-xl border border-amber-500/40 bg-amber-500/15 px-3 py-1 text-xs font-bold text-amber-700 dark:text-amber-300">
                                <Calendar className="size-3.5" />
                                <span>Periode: {promo?.start_date ? `${promo.start_date} s.d. ` : ''}{promo?.end_date || 'Selama kuota tersedia'}</span>
                            </span>
                        </div>

                        <h1 className="text-3xl sm:text-4xl lg:text-5xl font-black text-text tracking-tight leading-tight">
                            {promo?.title}
                        </h1>
                    </div>

                    {/* Banner Image & Description */}
                    <div className="rounded-3xl border border-border bg-surface overflow-hidden shadow-xl p-4 sm:p-6 lg:p-8 space-y-8">
                        {img && (
                            <div className="relative w-full aspect-[2/1] rounded-2xl overflow-hidden bg-slate-950 border border-border shadow-md">
                                <img
                                    src={img}
                                    alt={promo.title}
                                    className="size-full object-cover"
                                />
                            </div>
                        )}

                        {promo?.description && (
                            <div className="space-y-4">
                                <h3 className="text-base font-black text-text border-l-4 border-amber-500 pl-3">
                                    Deskripsi & Ketentuan Promo
                                </h3>
                                <div className="text-sm leading-relaxed text-text-muted whitespace-pre-line">
                                    {promo.description}
                                </div>
                            </div>
                        )}

                        {/* CTA */}
                        <div className="pt-4 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-4">
                            <p className="text-xs text-text-muted">
                                Mau menikmati promo ini? Pilih jadwal nontonmu sekarang!
                            </p>
                            <Link
                                href="/jadwal"
                                className="btn-gold-cinema inline-flex items-center justify-center gap-2 rounded-xl px-6 py-3 text-xs sm:text-sm font-black shadow-md shadow-amber-500/20"
                            >
                                <span>Pesan Tiket Sekarang</span>
                                <ArrowRight className="size-4" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}

