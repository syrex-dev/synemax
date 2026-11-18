import { Link, Head } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import { Sparkles, Calendar, ArrowRight } from 'lucide-react';

export default function Index({ activePromos = [], expiredPromos = [] }) {
    return (
        <PublicLayout title="Promo & Penawaran">
            <Head title="Promo & Penawaran">
                <meta
                    name="description"
                    content="Temukan berbagai promo menarik, paket hemat nonton privat, dan penawaran spesial di studio mini SynemaX."
                />
            </Head>

            <div className="mx-auto max-w-6xl px-4 py-8 sm:px-6 space-y-10">
                {/* Heading */}
                <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div>
                        <h1 className="text-3xl sm:text-4xl font-black text-text tracking-tight">Promo & Penawaran</h1>
                        <p className="mt-1 text-sm text-text-muted">
                            Nikmati penawaran spesial, diskon tiket, dan paket hemat nonton privat di SynemaX.
                        </p>
                    </div>
                    <Link
                        href="/jadwal"
                        className="btn-gold-cinema inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-xs sm:text-sm font-black shadow-md shadow-amber-500/20"
                    >
                        <span>Pesan Tiket Sekarang</span>
                        <ArrowRight className="size-4" />
                    </Link>
                </div>

                {/* Active Promos */}
                <section aria-labelledby="heading-promo-aktif" className="space-y-4">
                    <div className="flex items-center justify-between border-b border-border/80 dark:border-white/10 pb-3">
                        <h2 id="heading-promo-aktif" className="text-xl font-black text-text tracking-tight">
                            Promo Berlangsung
                        </h2>
                        <span className="text-xs font-semibold text-text-muted">
                            {activePromos.length} Penawaran Tersedia
                        </span>
                    </div>

                    {activePromos.length > 0 ? (
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            {activePromos.map((promo) => {
                                const img = promo.image_path ? (promo.image_path.startsWith('http') ? promo.image_path : `/storage/${promo.image_path}`) : null;
                                return (
                                    <Link
                                        key={promo.id}
                                        href={`/promo/${promo.slug || promo.id}`}
                                        className="group flex flex-col rounded-3xl overflow-hidden border border-border bg-surface shadow-xs hover:shadow-xl hover:border-amber-500 hover:-translate-y-1 transition-all duration-300"
                                    >
                                        <div className="relative w-full aspect-[2/1] overflow-hidden bg-slate-950">
                                            {img ? (
                                                <img
                                                    src={img}
                                                    alt={promo.title}
                                                    loading="lazy"
                                                    className="size-full object-cover transition-transform duration-500 group-hover:scale-105"
                                                />
                                            ) : (
                                                <div className="flex size-full flex-col items-center justify-center bg-gradient-to-br from-amber-600 via-amber-700 to-slate-950 p-4 text-center text-white">
                                                    <span className="text-xs uppercase tracking-widest font-black text-amber-200">
                                                        Promo Spesial
                                                    </span>
                                                    <strong className="mt-1 text-sm sm:text-base font-black line-clamp-2">
                                                        {promo.title}
                                                    </strong>
                                                </div>
                                            )}

                                            {promo.end_date && (
                                                <span className="absolute bottom-3 left-3 rounded-xl bg-black/75 backdrop-blur-md px-2.5 py-1 text-[11px] font-bold text-amber-300 border border-white/15">
                                                    s.d. {promo.end_date}
                                                </span>
                                            )}
                                        </div>

                                        <div className="p-5 flex flex-col flex-1 justify-between gap-3">
                                            <div>
                                                <h3 className="text-base font-black text-text group-hover:text-amber-500 transition line-clamp-1">
                                                    {promo.title}
                                                </h3>
                                                {promo.description && (
                                                    <p className="mt-1 text-xs text-text-muted line-clamp-2 leading-relaxed">
                                                        {promo.description}
                                                    </p>
                                                )}
                                            </div>

                                            <div className="flex items-center justify-between text-xs font-bold text-amber-600 dark:text-amber-400 pt-2 border-t border-border/60">
                                                <span>Lihat Detail Promo</span>
                                                <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-1" />
                                            </div>
                                        </div>
                                    </Link>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="rounded-3xl border border-dashed border-border bg-surface p-12 text-center space-y-2">
                            <Sparkles className="mx-auto size-12 text-text-muted" />
                            <p className="text-base font-bold text-text">Belum ada promo aktif</p>
                            <p className="text-xs text-text-muted">Nantikan penawaran menarik berikutnya dari SynemaX.</p>
                        </div>
                    )}
                </section>

                {/* Expired Promos if any */}
                {expiredPromos.length > 0 && (
                    <section aria-labelledby="heading-promo-berakhir" className="space-y-4 pt-4 border-t border-border/80">
                        <div className="border-b border-border/80 pb-3">
                            <h2 id="heading-promo-berakhir" className="text-lg font-bold text-text-muted">
                                Promo yang Telah Berakhir
                            </h2>
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 opacity-60">
                            {expiredPromos.map((promo) => (
                                <div key={promo.id} className="p-4 rounded-2xl border border-border bg-surface text-xs space-y-1">
                                    <h4 className="font-bold text-text truncate">{promo.title}</h4>
                                    <p className="text-text-muted">Berakhir pada {promo.end_date}</p>
                                </div>
                            ))}
                        </div>
                    </section>
                )}
            </div>
        </PublicLayout>
    );
}

