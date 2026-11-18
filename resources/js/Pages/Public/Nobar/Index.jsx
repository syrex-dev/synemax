import { Link, Head } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import Pagination from '../../../Components/Pagination';
import CinemaImage from '../../../Components/CinemaImage';
import { MapPin, Film, Radio } from 'lucide-react';

export default function Index({ events, currentFilter = 'semua' }) {
    const eventItems = events?.data || (Array.isArray(events) ? events : []);

    const filterLinks = [
        { key: 'semua', label: 'Semua acara', href: '/nobar' },
        { key: 'mendatang', label: 'Akan datang', href: '/nobar?status=mendatang' },
        { key: 'selesai', label: 'Selesai', href: '/nobar?status=selesai' },
    ];

    const formatTime = (dateStr) => {
        if (!dateStr) return '';
        try {
            const d = new Date(dateStr);
            const hours = String(d.getHours()).padStart(2, '0');
            const mins = String(d.getMinutes()).padStart(2, '0');
            return `${hours}.${mins} WIB`;
        } catch (e) {
            return '';
        }
    };

    const formatDate = (dateStr) => {
        if (!dateStr) return '';
        try {
            const d = new Date(dateStr);
            return d.toLocaleDateString('id-ID', {
                weekday: 'short',
                day: 'numeric',
                month: 'short',
                year: 'numeric',
            });
        } catch (e) {
            return dateStr;
        }
    };

    return (
        <PublicLayout title="Jadwal Nobar Sepak Bola">
            <Head title="Jadwal Nobar Sepak Bola">
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
                                    className="flex flex-col justify-between rounded-xl border border-border bg-surface p-4 sm:p-5 transition-all duration-200 hover:border-amber-400/50 group"
                                >
                                    <div>
                                        {posterSrc ? (
                                            <div className="relative mb-4">
                                                <CinemaImage
                                                    src={posterSrc}
                                                    alt={item.poster_alt || item.judul}
                                                    title={item.judul}
                                                    badge={item.display_status}
                                                    aspect="aspect-[4/5]"
                                                    imgClass="max-h-full max-w-full object-contain mx-auto rounded-xl transition duration-500 group-hover:scale-105"
                                                    className="flex items-center justify-center p-2 shadow-inner"
                                                />

                                                <div className="absolute left-3 top-3 flex flex-wrap gap-1.5 pointer-events-none">
                                                    <span className="rounded-lg px-2.5 py-1 text-[10px] font-black uppercase tracking-wider border border-border dark:border-white/20 bg-surface/90 dark:bg-black/80 text-text dark:text-white backdrop-blur-md shadow-md">
                                                        {item.display_status || 'Akan datang'}
                                                    </span>
                                                    {item.is_pinned && (
                                                        <span className="rounded-lg bg-gradient-to-r from-amber-400 to-amber-600 px-2.5 py-1 text-[10px] font-black uppercase text-black shadow-md">
                                                            Pilihan
                                                        </span>
                                                    )}
                                                </div>

                                                {item.is_free && (
                                                    <div className="absolute bottom-3 right-3 rounded-lg bg-emerald-500/90 backdrop-blur-md border border-emerald-400/50 px-2.5 py-1 text-[10px] font-black uppercase text-white shadow-md pointer-events-none">
                                                        Gratis
                                                    </div>
                                                )}
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

                                        <div>
                                            {item.kompetisi && (
                                                <div className="flex items-center gap-1.5">
                                                    <span className="size-1.5 rounded-full bg-cyan-500 dark:bg-cyan-400" />
                                                    <p className="text-xs font-bold uppercase tracking-wider text-cyan-600 dark:text-cyan-400">{item.kompetisi}</p>
                                                </div>
                                            )}

                                            <h2 className="mt-1.5 text-base font-black text-text leading-snug group-hover:text-amber-500 dark:group-hover:text-amber-400 transition">
                                                <Link href={`/nobar/${item.slug || item.id}`}>
                                                    {item.judul}
                                                </Link>
                                            </h2>

                                            {/* Panel Pertandingan: Fixture Box Team A vs Team B */}
                                            <div className="mt-3 flex items-center justify-between rounded-xl border border-border dark:border-white/10 bg-surface-2 dark:bg-black/40 p-3 text-xs shadow-inner">
                                                <span className="font-black text-text truncate max-w-[42%]">{item.tim_a}</span>
                                                <span className="px-2 py-0.5 rounded bg-amber-500/15 text-[10px] font-black uppercase text-amber-500 dark:text-amber-400 border border-amber-400/30">lawan</span>
                                                <span className="font-black text-text truncate max-w-[42%] text-right">{item.tim_b || 'TBA'}</span>
                                            </div>

                                            <div className="mt-3 flex items-baseline justify-between border-t border-border dark:border-white/10 pt-2.5">
                                                <span className="font-mono text-sm font-black text-amber-600 dark:text-amber-400">
                                                    {formatTime(item.kickoff_at)}
                                                </span>
                                                <span className="text-[11px] font-medium text-text-muted">
                                                    {formatDate(item.kickoff_at)}
                                                </span>
                                            </div>
                                            <p className="mt-1.5 text-xs text-text-muted flex items-center gap-1.5 truncate">
                                                <MapPin className="size-3.5 text-amber-500 shrink-0" />
                                                <span>Lokasi: {item.lokasi_nama}</span>
                                            </p>

                                            {item.status_acara && item.status_acara !== 'aktif' && item.alasan_status && (
                                                <div className="mt-3 rounded-xl border border-rose-500/40 bg-rose-500/10 p-2.5 text-xs text-rose-300">
                                                    <p className="font-bold">Info: {item.alasan_status}</p>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    <div className="mt-5 border-t border-border dark:border-white/10 pt-3">
                                        <Link
                                            href={`/nobar/${item.slug || item.id}`}
                                            className="btn-gold-cinema inline-flex min-h-11 w-full items-center justify-center rounded-xl px-4 text-xs font-black shadow-md"
                                        >
                                            Lihat detail acara
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

