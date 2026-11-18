import { Link, Head } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import {
    Clock,
    Users,
    Film,
    Sparkles,
    CheckCircle2,
    Lock,
    AlertCircle,
    ChevronRight,
    ArrowRight,
} from 'lucide-react';

export default function Schedule({
    targetDateStr,
    targetDateFormatted,
    todayStr,
    dates = [],
    holidayDates = {},
    sessionPrice = 5000,
    hasAnyAvailableDate = true,
    sessions = [],
    closedReason = null,
    holiday = null,
    blockedDate = null,
    bookingCutoffMinutes = 30,
    selectedFilm = null,
}) {
    const upcomingSessions = sessions.filter((s) => !s.status_waktu?.is_past);
    const pastSessions = sessions.filter((s) => s.status_waktu?.is_past);

    return (
        <PublicLayout title={`Jadwal ${targetDateFormatted}`}>
            <Head title={`Jadwal ${targetDateFormatted}`}>
                <meta
                    name="description"
                    content="Pilih jadwal nonton di studio mini SynemaX. Kapasitas tiap sesi maksimal enam tempat."
                />
            </Head>

            <div className="mx-auto max-w-4xl px-4 py-6 sm:px-6 sm:py-10">
                {/* Stepper Header */}
                <div className="border-b border-border/80 dark:border-white/10 pb-4">
                    <div className="flex items-center gap-2">
                        <span className="size-2 rounded-full bg-amber-400 animate-pulse" />
                        <p className="text-xs font-bold uppercase tracking-wider text-amber-500 dark:text-amber-400">
                            Langkah 1 dari 5: Jadwal
                        </p>
                    </div>
                    <h1 className="mt-1 text-2xl font-black tracking-tight text-text sm:text-3xl">
                        Pilih hari dan jam nonton
                    </h1>
                    <p className="mt-1 text-xs text-text-muted">
                        Kapasitas maksimal 6 penonton per sesi. Ketersediaan tempat diperbarui otomatis saat ada pemesanan baru.
                    </p>
                </div>

                {/* Selected Film Banner */}
                {selectedFilm && (
                    <div className="mt-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3.5 rounded-2xl border border-amber-500/30 bg-amber-500/10 p-3.5 sm:p-4">
                        <div className="flex items-center gap-3.5 min-w-0">
                            <div className="w-12 h-16 shrink-0 rounded-xl overflow-hidden bg-slate-900 border border-white/10 relative">
                                {selectedFilm.poster_url ? (
                                    <img
                                        src={selectedFilm.poster_url}
                                        alt={selectedFilm.title}
                                        className="size-full object-cover"
                                    />
                                ) : (
                                    <div className="flex size-full items-center justify-center text-[10px] text-amber-200 font-bold p-1 text-center">
                                        {selectedFilm.title}
                                    </div>
                                )}
                            </div>
                            <div className="min-w-0">
                                <p className="text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">
                                    Film yang kamu pilih:
                                </p>
                                <h2 className="text-sm sm:text-base font-black text-text truncate">
                                    {selectedFilm.title}
                                </h2>
                                <p className="text-xs text-text-muted truncate">
                                    {selectedFilm.genre} · {selectedFilm.duration_minutes} menit {selectedFilm.age_rating ? `· ${selectedFilm.age_rating}` : ''}
                                </p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2 shrink-0">
                            <Link
                                href="/film"
                                className="inline-flex min-h-9 items-center justify-center rounded-xl border border-border bg-surface px-3 py-1.5 text-xs font-bold text-text hover:bg-surface-2 transition"
                            >
                                Ganti film
                            </Link>
                        </div>
                    </div>
                )}

                {/* Horizontal Date Picker (14 Days) */}
                <div className="relative -mx-4 px-4 sm:mx-0 sm:px-0">
                    <nav
                        className="mt-6 flex snap-x gap-2 overflow-x-auto pb-3 custom-scrollbar scroll-smooth"
                        aria-label="Pilih tanggal nonton"
                    >
                        {dates.map((d) => {
                            const isSelected = d.date_key === targetDateStr;
                            const query = selectedFilm ? `?film=${selectedFilm.id}` : '';
                            return (
                                <Link
                                    key={d.date_key}
                                    href={`/jadwal/${d.date_key}${query}`}
                                    aria-current={isSelected ? 'date' : undefined}
                                    className={`min-w-24 shrink-0 snap-start rounded-2xl border p-2.5 text-center transition-all ${
                                        isSelected
                                            ? 'border-amber-500 bg-amber-500/15 text-text font-bold shadow-md shadow-amber-500/10'
                                            : 'border-border bg-surface text-text-muted hover:border-amber-400/50 hover:text-text'
                                    }`}
                                >
                                    <span
                                        className={`block text-[11px] font-bold uppercase tracking-wider ${
                                            isSelected ? 'text-amber-700 dark:text-amber-400' : 'text-text-muted'
                                        }`}
                                    >
                                        {d.is_today ? 'Hari ini' : d.day_name}
                                    </span>
                                    <span className="mt-0.5 block font-tabular text-base font-black text-text">
                                        {d.date_formatted}
                                    </span>
                                    {d.is_holiday && (
                                        <span className="mt-1 inline-block rounded bg-amber-400/20 px-1.5 py-0.5 text-[9px] font-black uppercase text-amber-700 dark:text-amber-300 border border-amber-400/30">
                                            Libur
                                        </span>
                                    )}
                                </Link>
                            );
                        })}
                    </nav>
                </div>

                {/* Selected Date Header */}
                <div className="mt-6 flex items-baseline justify-between gap-3 border-b border-border/80 dark:border-white/10 pb-2">
                    <div>
                        <h2 className="text-lg font-black text-text">{targetDateFormatted}</h2>
                    </div>
                    {holidayDates[targetDateStr] && (
                        <span className="rounded-xl bg-amber-400/20 border border-amber-400/40 px-2.5 py-1 text-xs font-black text-amber-700 dark:text-amber-300">
                            {holidayDates[targetDateStr]}
                        </span>
                    )}
                </div>

                {/* Closed Reason Banner */}
                {closedReason ? (
                    <div className="mt-6 rounded-2xl border border-red-500/30 bg-red-500/10 p-5 text-sm text-red-700 dark:text-red-300 flex items-start gap-3">
                        <AlertCircle className="size-5 shrink-0 mt-0.5" />
                        <div>
                            <p className="font-bold">Studio tutup pada tanggal ini</p>
                            <p className="mt-1 text-xs opacity-90">{closedReason}</p>
                        </div>
                    </div>
                ) : sessions.length === 0 ? (
                    <div className="mt-6 rounded-2xl border border-border bg-surface p-8 text-center space-y-4">
                        <div className="max-w-md mx-auto space-y-1.5">
                            <p className="text-base font-bold text-text">Jadwal sedang disiapkan</p>
                            <p className="text-xs text-text-muted leading-relaxed">
                                Sesi penayangan untuk tanggal ini belum dibuka atau sedang dalam penyesuaian jadwal operator.
                            </p>
                        </div>
                    </div>
                ) : (
                    <div className="mt-6 space-y-4 pb-8 sm:pb-4">
                        {upcomingSessions.length === 0 ? (
                            <div className="rounded-2xl border border-dashed border-border bg-surface-2 p-5 text-center text-xs text-text-muted">
                                Semua sesi penayangan untuk hari ini telah selesai atau sedang berlangsung.
                            </div>
                        ) : (
                            upcomingSessions.map((session) => {
                                const sw = session.status_waktu || {};
                                const canBook = session.can_book_this_session;
                                const sessionFilm = session.film;
                                const bookingUrl = `/booking/${session.id}${selectedFilm ? `?film=${selectedFilm.id}` : ''}`;
                                const occupiedSeats = session.occupied_seats || 0;
                                const capacity = session.capacity || 6;
                                const remainingSeats = Math.max(0, capacity - occupiedSeats);
                                const startTimeRaw = (session.start_time_formatted || session.start_time || '').replace(/\s*WIB/i, '').slice(0, 5);
                                const endTimeRaw = (session.end_time_formatted || session.end_time || '').replace(/\s*WIB/i, '').slice(0, 5);
                                const formattedTimeRange = session.time_range_formatted || `${startTimeRaw} - ${endTimeRaw} WIB`;

                                return (
                                    <article
                                        key={session.id}
                                        className={`rounded-2xl border bg-surface p-4 sm:p-5 transition-all shadow-xs ${
                                            canBook
                                                ? 'border-border/80 hover:border-amber-500/70 hover:shadow-md'
                                                : 'border-border/40 opacity-70 bg-surface/50 border-dashed'
                                        }`}
                                    >
                                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                            {/* Time & Capacity Badge */}
                                            <div className="space-y-2">
                                                <div className="flex items-center gap-3">
                                                    <span className="font-tabular font-black text-xl text-text">
                                                        {formattedTimeRange}
                                                    </span>
                                                    {session.is_private ? (
                                                        <span className="px-2 py-0.5 rounded-lg bg-purple-500/15 border border-purple-500/30 text-purple-700 dark:text-purple-300 text-xs font-bold">
                                                            Sewa Privat
                                                        </span>
                                                    ) : (
                                                        <span
                                                            className={`px-2 py-0.5 rounded-lg text-xs font-bold border ${
                                                                remainingSeats > 0
                                                                    ? 'bg-emerald-500/15 border-emerald-500/30 text-emerald-700 dark:text-emerald-400'
                                                                    : 'bg-red-500/15 border-red-500/30 text-red-600 dark:text-red-400'
                                                            }`}
                                                        >
                                                            {remainingSeats > 0 ? `${remainingSeats} Kursi Kosong` : 'Penuh'}
                                                        </span>
                                                    )}
                                                </div>

                                                {/* Film Status */}
                                                <div>
                                                    {sessionFilm ? (
                                                        <div className="flex items-center gap-2">
                                                            <Film className="size-4 text-amber-500 shrink-0" />
                                                            <span className="text-sm font-bold text-text truncate">
                                                                {sessionFilm.title}
                                                            </span>
                                                            {session.is_confirmed && (
                                                                <span className="text-[10px] font-bold px-1.5 py-0.2 rounded bg-amber-500/20 text-amber-700 dark:text-amber-300">
                                                                    Pasti Tayang
                                                                </span>
                                                            )}
                                                        </div>
                                                    ) : (
                                                        <p className="text-xs text-text-muted flex items-center gap-1.5">
                                                            <Sparkles className="size-3.5 text-amber-500" />
                                                            <span>Film bebas dipilih pemesan pertama</span>
                                                        </p>
                                                    )}
                                                </div>
                                            </div>

                                            {/* Booking CTA Button */}
                                            <div className="flex items-center gap-3 shrink-0 w-full sm:w-auto">
                                                {canBook ? (
                                                    <Link
                                                        href={session.booking_url || bookingUrl}
                                                        className="btn-gold-cinema inline-flex min-h-11 w-full sm:w-auto items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-xs font-black shadow-md shadow-amber-500/20"
                                                    >
                                                        <span>{session.action_label || 'Pilih jam ini'}</span>
                                                        <ArrowRight className="size-4" />
                                                    </Link>
                                                ) : (
                                                    <span className="inline-flex min-h-11 w-full sm:w-auto items-center justify-center px-4 py-2 text-xs font-bold text-text-muted border border-border/40 rounded-xl bg-surface-raised cursor-not-allowed">
                                                        {sw.label || 'Tidak Tersedia'}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </article>
                                );
                            })
                        )}
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}
