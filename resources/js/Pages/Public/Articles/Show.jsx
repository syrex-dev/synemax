import { Link, Head } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import { Calendar, ArrowLeft, Share2, ArrowRight } from 'lucide-react';

export default function Show({ article, relatedArticles = [], isPreview = false }) {
    if (!article) return null;

    const coverSrc = article.cover_url || (article.cover_path ? `/storage/${article.cover_path}` : null);

    const formatDate = (dateStr) => {
        if (!dateStr) return '';
        try {
            return new Date(dateStr).toLocaleDateString('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            });
        } catch (e) {
            return dateStr;
        }
    };

    const handleShareWhatsApp = () => {
        const url = window.location.href;
        const text = `${article.judul}\n\n${article.ringkasan || ''}\n\nBaca selengkapnya: ${url}`;
        window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
    };

    return (
        <PublicLayout title={article.judul}>
            <Head>
                <meta property="og:type" content="article" />
                <meta property="og:title" content={article.judul} />
                {article.ringkasan && (
                    <meta property="og:description" content={article.ringkasan} />
                )}
            </Head>

            <div className="mx-auto max-w-4xl px-4 py-6 sm:px-6 sm:py-10 space-y-6">
                {/* Back Link */}
                <div>
                    <Link
                        href="/kabar"
                        className="inline-flex min-h-11 items-center gap-2 text-xs font-bold text-amber-500 hover:text-amber-400 transition"
                    >
                        <ArrowLeft className="size-4" />
                        <span>Kabar & Pengumuman</span>
                    </Link>
                </div>

                {/* Preview Alert */}
                {isPreview && (
                    <div role="alert" className="rounded-2xl border border-amber-400/40 bg-amber-500/10 p-4 text-xs text-amber-300">
                        <span className="font-black">Mode Pratinjau Admin</span>: Artikel ini belum terbit ke publik atau masih berstatus draft/terjadwal.
                    </div>
                )}

                {/* Article Header Card */}
                <article className="rounded-3xl border border-border dark:border-white/10 bg-surface overflow-hidden shadow-2xl">
                    {coverSrc && (
                        <div className="relative aspect-[21/9] sm:aspect-[2/1] w-full overflow-hidden bg-slate-950">
                            <img
                                src={coverSrc}
                                alt={article.judul}
                                className="size-full object-cover"
                            />
                        </div>
                    )}

                    <div className="p-6 sm:p-10 space-y-6">
                        <div className="flex flex-wrap items-center gap-3">
                            <span className="rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-black uppercase tracking-wider text-amber-500">
                                {article.kategori}
                            </span>
                            <div className="flex items-center gap-1.5 text-xs text-text-muted">
                                <Calendar className="size-3.5" />
                                <span>{formatDate(article.tanggal_terbit || article.created_at)}</span>
                            </div>
                        </div>

                        <h1 className="text-2xl sm:text-4xl font-black text-text tracking-tight leading-tight">
                            {article.judul}
                        </h1>

                        {article.ringkasan && (
                            <p className="text-base sm:text-lg text-text-muted italic border-l-4 border-amber-500 pl-4 py-1 leading-relaxed">
                                {article.ringkasan}
                            </p>
                        )}

                        <div className="pt-6 border-t border-border">
                            <div
                                className="prose dark:prose-invert max-w-none text-sm sm:text-base text-text leading-relaxed space-y-4"
                                dangerouslySetInnerHTML={{ __html: article.konten_html || article.konten }}
                            />
                        </div>

                        {/* Share section */}
                        <div className="pt-8 border-t border-border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <span className="text-xs font-bold text-text-muted">Bagikan warta ini ke teman & komunitas:</span>
                            <button
                                type="button"
                                onClick={handleShareWhatsApp}
                                className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-5 transition shadow-sm"
                            >
                                <Share2 className="size-4" />
                                <span>Bagikan ke WhatsApp</span>
                            </button>
                        </div>
                    </div>
                </article>

                {/* Related Articles */}
                {relatedArticles && relatedArticles.length > 0 && (
                    <div className="pt-8 space-y-4">
                        <h2 className="text-xl font-black text-text">Kabar Terkait Lainnya</h2>
                        <div className="grid gap-4 sm:grid-cols-3">
                            {relatedArticles.map((rel) => (
                                <Link
                                    key={rel.id}
                                    href={`/kabar/${rel.slug || rel.id}`}
                                    className="rounded-2xl border border-border bg-surface p-4 hover:border-amber-500 hover:shadow-md transition group"
                                >
                                    <span className="text-[10px] font-bold text-amber-500 uppercase">
                                        {rel.kategori}
                                    </span>
                                    <h3 className="mt-1 text-sm font-bold text-text group-hover:text-amber-500 line-clamp-2">
                                        {rel.judul}
                                    </h3>
                                    <p className="mt-2 text-xs text-text-muted">
                                        {formatDate(rel.tanggal_terbit || rel.created_at)}
                                    </p>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}
