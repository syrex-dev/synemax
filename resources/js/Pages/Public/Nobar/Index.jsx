import { Link, Head } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import Pagination from '../../../Components/Pagination';
import { Calendar, MapPin, Clock, Share2, Film, Radio } from 'lucide-react';

export default function Index({ events, currentFilter = 'semua' }) {
    const eventItems = events?.data || (Array.isArray(events) ? events : []);

    const filterLinks = [
        { key: 'semua', label: 'Semua acara', href: '/nobar' },
        { key: 'mendatang', label: 'Akan datang', href: '/nobar?status=mendatang' },
        { key: 'selesai', label: 'Selesai', href: '/nobar?status=selesai' },
    ];

    const formatDateTime = (dateStr) => {
        if (!dateStr) return '';
        try {
            const d = new Date(dateStr);
            return d.toLocaleDateString('id-ID', {
                weekday: 'short',
                day: 'numeric',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            }) + ' WIB';
        } catch (e) {
            return dateStr;
        }
    };

    return (
        <PublicLayout title="Jadwal Nobar Sepak Bola">
            <Head>
                <meta
                    name="description"
                    content="Nonton bareng pertandingan sepak bola seru di SynemaX. Layar lebar proyektor, tata suara jernih, suasana hangat komunitas warga."
                />
            </Head>

            <div className="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10 space-y-8">
                {/* Header ala Cinema Lounge */}
                <div className="relative overflow-hidden rounded-3xl border border-border dark:border-white/10 bg-surface dark:bg-gradient-to-br dark:from-surface dark:via-[#0C111D] dark:to-[#07090E] p-6 sm:p-8 shadow-2xl">
                    <div className="absolute -right-16 -top-16 size-64 rounded-full bg-amber-500/10 blur-3xl pointer-events-none" />
                    <div className="absolute -left-16 -bottom-16 size-64 rounded-full bg-cyan-500/10 blur-3xl pointer-events-none" />

                    <div className="relative flex flex-wrap items-center justify-between gap-5">
                        <div>
                            <span className="inline-flex items-center gap-1.5 rounded-full border border-amber-400/40 bg-amber-500/15 px-3 py-1 text-xs font-black uppercase tracking-wider text-amber-500 dark:text-amber-300">
                                <span className="size-2 rounded-full bg-amber-400 animate-pulse" />
                                Nobar komunitas
                            </span>
                            <h1 className="mt-2 text-2xl font-black text-text sm:text-4xl tracking-tight">
                                Jadwal nonton bareng
                            </h1>
                            <p className="mt-2 max-w-xl text-xs sm:text-sm text-text-muted leading-relaxed">
                                Nikmati ketegangan pertandingan sepak bola di layar proyektor dan tata suara jernih bersama sesama penikmat bola.
                            </p>
                        </div>
                        <div>
                            <Link
                                href="/jadwal"
                                className="inline-flex min-h-11 items-center gap-2 rounded-xl border border-border dark:border-white/20 bg-surface-2 dark:bg-surface/80 px-4 text-xs font-bold text-text hover:border-amber-400 hover:text-amber-500 dark:hover:text-amber-300 transition"
                            >
                                <Film className="size-4 text-amber-500" />
                                <span>Jadwal film bioskop</span>
                            </Link>
                        </div>
                    </div>

                    {/* Filter tabs */}
                    <div className="mt-6 flex flex-wrap gap-2 border-t border-border dark:border-white/10 pt-5">
                        {filterLinks.map((item) => {
                            const isActive = currentFilter === item.key;
                            return (
                                <Link
                                    key={item.key}
                                    href={item.href}
                                    className={`inline-flex min-h-10 items-center rounded-xl px-4 text-xs font-black transition ${
                                        isActive
                                            ? 'btn-gold-cinema shadow-md'
                                            : 'border border-border dark:border-white/10 bg-surface-2 dark:bg-surface/60 text-text-muted hover:border-amber-400/50 hover:text-text'
                                    }`}
                                >
                                    {item.label}
                                </Link>
                            );
                        })}
                    </div>
                </div>

                {/* Event Cards */}
                {eventItems.length === 0 ? (
                    <div className="rounded-2xl border border-dashed border-border bg-surface-2/60 p-12 text-center">
                        <span className="inline-flex size-14 items-center justify-center rounded-2xl bg-surface border border-border text-amber-500 mb-3 shadow-xs">
                            <Radio className="size-6" />
                        </span>
                        <h2 className="text-base font-bold text-text">Belum ada jadwal nobar</h2>
                        <p className="mt-1 text-xs text-text-muted max-w-md mx-auto">
                            Jadwal pertandingan berikutnya sedang dipersiapkan. Pantau terus halaman ini atau cek film bioskop kami.
                        </p>
                        <Link
                            href="/jadwal"
                            className="mt-5 btn-gold-cinema inline-flex min-h-11 items-center rounded-xl px-6 text-xs font-bold shadow-md"
                        >
                            Lihat jadwal film
                        </Link>
                    </div>
                ) : (
                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {eventItems.map((item) => {
                            const posterSrc = item.poster_url || (item.poster_path ? `/storage/${item.poster_path}` : null);
                            return (
                                <article
                                    key={item.id}
                                    className="flex flex-col justify-between rounded-2xl border border-border bg-surface p-4 sm:p-5 transition-all duration-300 hover:border-amber-400/50 hover:shadow-xl group"
                                >
                                    <div>
                                        {posterSrc ? (
                                            <div className="relative mb-4 aspect-[4/5] rounded-xl overflow-hidden bg-slate-950 flex items-center justify-center">
                                                <img
                                                    src={posterSrc}
                                                    alt={item.poster_alt || item.judul}
                                                    loading="lazy"
                                                    className="size-full object-cover transition-transform duration-500 group-hover:scale-105"
                                                />
                                                <div className="absolute left-3 top-3 flex flex-wrap gap-1.5 pointer-events-none">
                                                    <span className="rounded-lg px-2.5 py-1 text-[10px] font-black uppercase tracking-wider border border-white/20 bg-black/80 text-white backdrop-blur-md shadow-md">
                                                        {item.display_status || 'Akan datang'}
                                                    </span>
                                                    {item.is_pinned && (
                                                        <span className="rounded-lg bg-gradient-to-r from-amber-400 to-amber-600 px-2.5 py-1 text-[10px] font-black uppercase text-black shadow-md">
                                                            Pilihan
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="mb-4 rounded-xl border border-border/80 bg-surface-2 p-4 text-center">
                                                <span className="text-[10px] font-black uppercase tracking-wider text-amber-500">
                                                    {item.kompetisi || 'Pertandingan'}
                                                </span>
                                                <h3 className="mt-1 text-lg font-black text-text">
                                                    {item.vs_title || `${item.tim_a} vs ${item.tim_b || ''}`}
                                                </h3>
                                            </div>
                                        )}

                                        <div className="space-y-2">
                                            {item.kompetisi && (
                                                <span className="text-[11px] font-bold text-amber-500 uppercase tracking-wider">
                                                    {item.kompetisi}
                                                </span>
                                            )}
                                            <h2 className="text-base font-black text-text line-clamp-2 group-hover:text-amber-500 transition-colors">
                                                <Link href={`/nobar/${item.slug || item.id}`}>
                                                    {item.judul}
                                                </Link>
                                            </h2>
                                            <div className="pt-2 space-y-1.5 text-xs text-text-muted">
                                                <div className="flex items-center gap-2">
                                                    <Clock className="size-3.5 text-amber-500 shrink-0" />
                                                    <span>{formatDateTime(item.kickoff_at)}</span>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <MapPin className="size-3.5 text-amber-500 shrink-0" />
                                                    <span className="truncate">{item.lokasi_nama}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="mt-5 pt-4 border-t border-border flex items-center justify-between gap-2">
                                        <span className={`inline-flex rounded-lg px-2.5 py-1 text-[11px] font-black ${
                                            item.is_free ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-500 border border-amber-500/20'
                                        }`}>
                                            {item.is_free ? 'Gratis' : (item.harga_info || 'Berbayar')}
                                        </span>
                                        <Link
                                            href={`/nobar/${item.slug || item.id}`}
                                            className="btn-gold-cinema inline-flex items-center rounded-xl px-4 py-2 text-xs font-bold"
                                        >
                                            Detail Acara
                                        </Link>
                                    </div>
                                </article>
                            );
                        })}
                    </div>
                )}

                {/* Pagination */}
                {events?.links && (
                    <Pagination links={events.links} />
                )}
            </div>
        </PublicLayout>
    );
}
