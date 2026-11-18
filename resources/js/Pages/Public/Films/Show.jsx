import { useState } from 'react';
import { Link, Head } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import Modal from '../../../Components/Modal';
import {
    ChevronLeft,
    Clock,
    Play,
    Calendar,
    Users,
    ArrowRight,
    Film as FilmIcon,
} from 'lucide-react';

export default function Show({
    film,
    sessions = [],
    otherFilms = [],
    matchedGenreLabel = '',
}) {
    const [trailerOpen, setTrailerOpen] = useState(false);

    // Extract youtube ID
    const extractYoutubeId = (url) => {
        if (!url) return null;
        const match = url.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/);
        return match ? match[1] : null;
    };

    const ytId = extractYoutubeId(film?.trailer_url);
    const poster = film?.poster_url || (film?.poster_path ? (film.poster_path.startsWith('http') ? film.poster_path : `/storage/${film.poster_path}`) : null);
    const genres = film?.genresList || (film?.genre ? film.genre.split(',').map((g) => g.trim()) : []);
    const bookingUrl = `/jadwal?film=${film?.id}`;

    return (
        <PublicLayout title={film?.title}>
            <Head title={film?.title}>
                <meta
                    name="description"
                    content={film?.synopsis ? film.synopsis.slice(0, 150) : `Tonton film ${film?.title} di studio privat SynemaX.`}
                />
            </Head>

            {/* Trailer Modal */}
            <Modal
                show={trailerOpen}
                onClose={() => setTrailerOpen(false)}
                title={`Trailer: ${film?.title}`}
                maxWidth="3xl"
            >
                {ytId && (
                    <div className="relative w-full aspect-video rounded-2xl overflow-hidden bg-black">
                        <iframe
                            src={`https://www.youtube-nocookie.com/embed/${ytId}?autoplay=1`}
                            title={`Trailer ${film?.title}`}
                            className="size-full border-0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowFullScreen
                        />
                    </div>
                )}
            </Modal>

            {/* Header Hero Banner with visible cinematic blurred backdrop */}
            <div className="relative overflow-hidden bg-bg dark:bg-[#07090E] transition-colors duration-200 border-b border-border/60">
                {poster && (
                    <div
                        className="absolute inset-0 z-0 bg-cover bg-[center_20%] pointer-events-none opacity-45 dark:opacity-30 blur-2xl scale-110 transform"
                        style={{ backgroundImage: `url('${poster}')` }}
                        aria-hidden="true"
                    />
                )}
                {/* Gradient overlays to ensure high contrast & readable typography */}
                <div className="absolute inset-0 z-0 bg-gradient-to-t from-bg via-bg/85 to-bg/30 dark:from-[#07090E] dark:via-[#07090E]/85 dark:to-[#07090E]/30 pointer-events-none" />
                <div className="absolute inset-0 z-0 bg-gradient-to-r from-bg/95 via-bg/75 to-transparent dark:from-[#07090E]/95 dark:via-[#07090E]/75 dark:to-transparent pointer-events-none" />

                <div className="relative z-10 mx-auto max-w-6xl px-4 pt-4 sm:pt-6 pb-8 sm:px-6">
                    {/* Back Link */}
                    <div className="mb-5">
                        <Link
                            href="/film"
                            className="inline-flex items-center gap-1.5 text-xs font-bold text-amber-500 hover:text-amber-400 transition"
                        >
                            <ChevronLeft className="size-4" />
                            <span>Katalog film</span>
                        </Link>
                    </div>

                    {/* Desktop & Mobile Main Details (Poster on Left, Info & Synopsis on Right) */}
                    <div className="flex flex-col md:flex-row items-center md:items-start gap-6 lg:gap-8">
                        {/* Selected Film Poster (Compact, Sleek Hero Card) */}
                        <div className="w-48 sm:w-52 md:w-56 lg:w-64 aspect-[2/3] shrink-0 rounded-2xl overflow-hidden border-2 border-white/20 dark:border-white/10 bg-slate-950 shadow-2xl relative mx-auto md:mx-0 group">
                            {poster ? (
                                <img
                                    src={poster}
                                    alt={film?.title}
                                    className="size-full object-cover"
                                />
                            ) : (
                                <div className="flex size-full flex-col items-center justify-center bg-slate-900 p-6 text-center">
                                    <FilmIcon className="size-12 text-amber-500/60 mb-3" />
                                    <span className="text-sm font-bold text-amber-200">{film?.title}</span>
                                </div>
                            )}

                            {film?.custom_badge && (
                                <div className="absolute bottom-3 inset-x-0 flex justify-center px-3 z-10 pointer-events-none">
                                    <span className="inline-flex items-center justify-center bg-[#E50914] text-white font-black text-xs px-3 py-1 rounded-md shadow-lg truncate max-w-full border border-white/20">
                                        {film.custom_badge}
                                    </span>
                                </div>
                            )}
                        </div>

                        {/* Info details and Synopsis beside card */}
                        <div className="flex-1 space-y-4 text-center md:text-left w-full min-w-0">
                            <h1 className="text-2xl sm:text-3xl lg:text-4xl font-black text-text tracking-tight">
                                {film?.title}
                            </h1>

                            {/* Tags row */}
                            <div className="flex flex-wrap items-center justify-center md:justify-start gap-2 pt-0.5">
                                {film?.age_rating && (
                                    <span className="rounded-lg border border-amber-500/40 bg-amber-500/10 px-2.5 py-1 text-xs font-black uppercase text-amber-500">
                                        {film.age_rating}
                                    </span>
                                )}
                                {genres.map((g) => (
                                    <span
                                        key={g}
                                        className="rounded-full border border-border bg-surface/90 px-3 py-1 text-xs font-bold text-text shadow-2xs"
                                    >
                                        {g}
                                    </span>
                                ))}
                                {film?.duration_minutes && (
                                    <span className="rounded-full border border-border bg-surface/90 px-3 py-1 text-xs font-bold text-text-muted shadow-2xs">
                                        {film.duration_minutes} menit
                                    </span>
                                )}
                                {film?.release_year && (
                                    <span className="rounded-full border border-border bg-surface/90 px-3 py-1 text-xs font-bold text-text-muted shadow-2xs">
                                        {film.release_year}
                                    </span>
                                )}
                            </div>

                            {/* Meta details (Director, Cast, Language) */}
                            {(film?.director || film?.casts || film?.language) && (
                                <dl className="space-y-1.5 text-xs text-text-muted pt-2 border-t border-border/60 text-left">
                                    {film?.director && (
                                        <div className="flex gap-2">
                                            <dt className="font-bold text-text shrink-0 w-20">Sutradara</dt>
                                            <dd className="text-text/90">: {film.director}</dd>
                                        </div>
                                    )}
                                    {film?.casts && (
                                        <div className="flex gap-2">
                                            <dt className="font-bold text-text shrink-0 w-20">Pemeran</dt>
                                            <dd className="text-text/90">: {film.casts}</dd>
                                        </div>
                                    )}
                                    {film?.language && (
                                        <div className="flex gap-2">
                                            <dt className="font-bold text-text shrink-0 w-20">Bahasa</dt>
                                            <dd className="text-text/90">: {film.language}</dd>
                                        </div>
                                    )}
                                </dl>
                            )}

                            {/* Synopsis (Directly beside the poster card below genres) */}
                            {film?.synopsis && (
                                <div className="space-y-1.5 pt-2 border-t border-border/60 text-left">
                                    <h2 className="text-xs font-black uppercase tracking-wider text-amber-500">
                                        Sinopsis
                                    </h2>
                                    <p className="text-xs sm:text-sm leading-relaxed text-text-muted whitespace-pre-line max-w-3xl">
                                        {film.synopsis}
                                    </p>
                                </div>
                            )}

                            {/* Actions CTA: Pilih Film Ini & Tonton Trailer */}
                            <div className="flex flex-wrap items-center justify-center md:justify-start gap-3 pt-2">
                                <Link
                                    href={bookingUrl}
                                    className="btn-gold-cinema inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-6 py-2.5 text-xs sm:text-sm font-black shadow-md shadow-amber-500/20 hover:scale-[1.02] transition"
                                >
                                    <span>Pilih Film Ini</span>
                                    <ArrowRight className="size-4" />
                                </Link>

                                {ytId && (
                                    <button
                                        type="button"
                                        onClick={() => setTrailerOpen(true)}
                                        className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-amber-500/40 bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 dark:text-amber-400 px-4 py-2.5 text-xs sm:text-sm font-bold transition shadow-xs hover:border-amber-500 hover:scale-[1.02]"
                                    >
                                        <Play className="size-4 fill-current text-amber-500" />
                                        <span>Tonton Trailer</span>
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Content Body: Screening Sessions, Similar Films */}
            <div className="mx-auto max-w-6xl px-4 py-8 sm:px-6 space-y-10">
                {/* Upcoming Screening Sessions for this film */}
                {sessions && sessions.length > 0 && (
                    <section aria-label="Jadwal Penayangan Film Ini" className="space-y-4">
                        <div className="flex items-center justify-between border-l-4 border-amber-500 pl-3">
                            <h2 className="text-lg font-black text-text">Gabung sesi yang sudah memilih film ini</h2>
                            <Link href={bookingUrl} className="text-xs font-bold text-amber-500 hover:underline">
                                Lihat Semua Jadwal &gt;
                            </Link>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            {sessions.map((s) => (
                                <Link
                                    key={s.id}
                                    href={`/booking/${s.id}?film=${film?.id}`}
                                    className="p-4 rounded-2xl border border-border bg-surface hover:border-amber-500 transition shadow-xs flex flex-col justify-between"
                                >
                                    <div>
                                        <p className="text-xs font-bold text-amber-700 dark:text-amber-400">
                                            {s.session_date}
                                        </p>
                                        <p className="font-tabular font-black text-lg text-text mt-0.5">
                                            {s.start_time} - {s.end_time} WIB
                                        </p>
                                    </div>
                                    <div className="mt-3 pt-2 border-t border-border/60 flex items-center justify-between text-xs font-bold text-amber-600 dark:text-amber-400">
                                        <span>Pesan Sesi Ini</span>
                                        <ArrowRight className="size-3.5" />
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </section>
                )}

                {/* Similar Films (Filled 6-Col Grid without gaps) */}
                {otherFilms && otherFilms.length > 0 && (
                    <section aria-label="Film Rekomendasi Sejenis" className="space-y-4">
                        <div className="flex items-center justify-between border-l-4 border-amber-500 pl-3">
                            <h2 className="text-lg font-black text-text">
                                Rekomendasi Film Serupa {matchedGenreLabel ? `(${matchedGenreLabel})` : ''}
                            </h2>
                            <Link href="/film" className="text-xs font-bold text-amber-500 hover:underline">
                                Semua Film &gt;
                            </Link>
                        </div>

                        <div className={`grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 ${
                            otherFilms.length >= 6
                                ? 'lg:grid-cols-6'
                                : otherFilms.length === 5
                                    ? 'lg:grid-cols-5'
                                    : otherFilms.length === 4
                                        ? 'lg:grid-cols-4'
                                        : 'lg:grid-cols-3'
                        } gap-3 sm:gap-4`}>
                            {otherFilms.map((other) => {
                                const oPoster = other.poster_url || (other.poster_path ? (other.poster_path.startsWith('http') ? other.poster_path : `/storage/${other.poster_path}`) : null);
                                return (
                                    <Link
                                        key={other.id}
                                        href={`/film/${other.slug || other.id}`}
                                        className="group flex flex-col rounded-xl sm:rounded-2xl overflow-hidden border border-border bg-surface shadow-xs hover:border-amber-500 transition hover:shadow-md"
                                    >
                                        <div className="w-full aspect-[2/3] bg-slate-900 overflow-hidden relative">
                                            {oPoster ? (
                                                <img
                                                    src={oPoster}
                                                    alt={other.title}
                                                    className="size-full object-cover group-hover:scale-105 transition duration-300"
                                                    loading="lazy"
                                                />
                                            ) : (
                                                <div className="flex size-full items-center justify-center p-2 text-center text-xs font-bold text-amber-200">
                                                    {other.title}
                                                </div>
                                            )}
                                            {other.age_rating && (
                                                <span className="absolute top-2 left-2 rounded-md bg-black/75 backdrop-blur-xs px-1.5 py-0.5 text-[9px] font-black text-amber-400 border border-white/20">
                                                    {other.age_rating}
                                                </span>
                                            )}
                                        </div>
                                        <div className="p-2.5">
                                            <h3 className="font-bold text-xs text-text line-clamp-1 group-hover:text-amber-500 transition">
                                                {other.title}
                                            </h3>
                                            <p className="text-[10px] text-text-muted mt-0.5 truncate">{other.genre}</p>
                                        </div>
                                    </Link>
                                );
                            })}
                        </div>
                    </section>
                )}
            </div>
        </PublicLayout>
    );
}
