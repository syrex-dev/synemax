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
            <Head>
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

            {/* Header Hero Banner with blurred backdrop */}
            <div className="relative overflow-hidden bg-surface dark:bg-[#07090E] transition-colors duration-200">
                {poster && (
                    <div
                        className="absolute inset-0 z-0 bg-cover bg-[center_30%] pointer-events-none opacity-30 dark:opacity-20 blur-xl"
                        style={{ backgroundImage: `url('${poster}')` }}
                        aria-hidden="true"
                    />
                )}
                <div className="absolute inset-0 z-0 bg-gradient-to-t from-surface via-surface/85 to-transparent dark:from-[#07090E] dark:via-[#07090E]/85 dark:to-transparent pointer-events-none" />

                <div className="relative z-10 mx-auto max-w-6xl px-4 pt-4 sm:pt-6 pb-8 sm:px-6">
                    {/* Back Link */}
                    <div className="mb-6">
                        <Link
                            href="/film"
                            className="inline-flex items-center gap-1.5 text-xs font-bold text-amber-500 hover:text-amber-400 transition"
                        >
                            <ChevronLeft className="size-4" />
                            <span>Katalog film</span>
                        </Link>
                    </div>

                    {/* Desktop & Mobile Main Details */}
                    <div className="flex flex-col md:flex-row items-start gap-6 md:gap-8">
                        {/* Poster */}
                        <div className="w-36 sm:w-48 md:w-[190px] aspect-[2/3] shrink-0 rounded-2xl overflow-hidden border border-white/15 bg-black shadow-2xl relative mx-auto md:mx-0">
                            {poster ? (
                                <img
                                    src={poster}
                                    alt={film?.title}
                                    className="size-full object-cover"
                                />
                            ) : (
                                <div className="flex size-full flex-col items-center justify-center bg-slate-900 p-4 text-center">
                                    <FilmIcon className="size-8 text-amber-500/60 mb-2" />
                                    <span className="text-xs font-bold text-amber-200">{film?.title}</span>
                                </div>
                            )}

                            {film?.custom_badge && (
                                <div className="absolute bottom-2 inset-x-0 flex justify-center px-2 z-10 pointer-events-none">
                                    <span className="inline-flex items-center justify-center bg-[#E50914] text-white font-black text-[10px] sm:text-xs px-2.5 py-0.5 rounded shadow-md truncate max-w-full border border-white/20">
                                        {film.custom_badge}
                                    </span>
                                </div>
                            )}
                        </div>

                        {/* Info details */}
                        <div className="flex-1 space-y-4 text-center md:text-left w-full">
                            <h1 className="text-2xl sm:text-3xl font-black text-text tracking-tight">
                                {film?.title}
                            </h1>

                            {/* Tags row */}
                            <div className="flex flex-wrap items-center justify-center md:justify-start gap-2 pt-1">
                                {film?.age_rating && (
                                    <span className="rounded-lg border border-amber-500/40 bg-amber-500/10 px-2.5 py-1 text-xs font-black uppercase text-amber-500">
                                        {film.age_rating}
                                    </span>
                                )}
                                {genres.map((g) => (
                                    <span
                                        key={g}
                                        className="rounded-full border border-border bg-surface/90 px-3 py-1 text-xs font-bold text-text"
                                    >
                                        {g}
                                    </span>
                                ))}
                                {film?.duration_minutes && (
                                    <span className="rounded-full border border-border bg-surface/90 px-3 py-1 text-xs font-bold text-text-muted">
                                        {film.duration_minutes} menit
                                    </span>
                                )}
                                {film?.release_year && (
                                    <span className="rounded-full border border-border bg-surface/90 px-3 py-1 text-xs font-bold text-text-muted">
                                        {film.release_year}
                                    </span>
                                )}

                                {/* Trailer button */}
                                {ytId && (
                                    <button
                                        type="button"
                                        onClick={() => setTrailerOpen(true)}
                                        className="size-9 rounded-full bg-amber-500 hover:bg-amber-400 text-slate-950 flex items-center justify-center shadow-md transition-transform hover:scale-105 ml-1"
                                        title="Putar trailer"
                                    >
                                        <Play className="size-4 fill-current ml-0.5" />
                                    </button>
                                )}
                            </div>

                            {/* Meta description */}
                            {(film?.director || film?.casts || film?.language) && (
                                <dl className="space-y-1.5 text-xs text-text-muted pt-2 border-t border-border/60 max-w-2xl text-left">
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

                            {/* Booking Action CTA */}
                            <div className="pt-2">
                                <Link
                                    href={bookingUrl}
                                    className="btn-gold-cinema inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl px-7 py-3 text-sm font-black shadow-lg shadow-amber-500/20"
                                >
                                    <span>Pilih Film Ini</span>
                                    <ArrowRight className="size-4" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Content Body: Synopsis, Screening Sessions, Similar Films */}
            <div className="mx-auto max-w-6xl px-4 py-8 sm:px-6 space-y-10">
                {/* Synopsis */}
                {film?.synopsis && (
                    <section aria-label="Sinopsis Film" className="space-y-3">
                        <h2 className="text-lg font-black text-text border-l-4 border-amber-500 pl-3">
                            Sinopsis
                        </h2>
                        <div className="text-sm leading-relaxed text-text-muted whitespace-pre-line max-w-3xl">
                            {film.synopsis}
                        </div>
                    </section>
                )}

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

                {/* Similar Films */}
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

                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            {otherFilms.map((other) => {
                                const oPoster = other.poster_url || (other.poster_path ? (other.poster_path.startsWith('http') ? other.poster_path : `/storage/${other.poster_path}`) : null);
                                return (
                                    <Link
                                        key={other.id}
                                        href={`/film/${other.slug || other.id}`}
                                        className="group flex flex-col rounded-2xl overflow-hidden border border-border bg-surface shadow-xs hover:border-amber-500 transition"
                                    >
                                        <div className="w-full aspect-[2/3] bg-slate-900 overflow-hidden relative">
                                            {oPoster ? (
                                                <img
                                                    src={oPoster}
                                                    alt={other.title}
                                                    className="size-full object-cover group-hover:scale-103 transition duration-200"
                                                />
                                            ) : (
                                                <div className="flex size-full items-center justify-center p-2 text-center text-xs font-bold text-amber-200">
                                                    {other.title}
                                                </div>
                                            )}
                                        </div>
                                        <div className="p-3">
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
