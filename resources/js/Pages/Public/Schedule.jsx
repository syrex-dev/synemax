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
                        <p className="text-xs sm:text-sm font-bold uppercase tracking-wider text-amber-500 dark:text-amber-400">
                            Langkah 1 dari 5: Jadwal
                        </p>
                    </div>
                    <h1 className="mt-1 text-2xl font-black tracking-tight text-text sm:text-3xl">
                        Pilih hari dan jam nonton
                    </h1>
                    <p className="mt-1 text-xs sm:text-sm text-text-muted">
                        Kapasitas maksimal 6-10 penonton per sesi. Ketersediaan tempat diperbarui otomatis saat ada pemesanan baru.
                    </p>
                </div>

                {/* Selected Film Banner */}
                {selectedFilm && (
                    <div className="mt-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3.5 rounded-2xl border border-amber-500/30 bg-amber-500/10 p-3.5 sm:p-4">
                        <div className="flex items-center gap-3.5 min-w-0">
                            <div className="w-14 h-20 shrink-0 rounded-xl overflow-hidden bg-slate-900 border border-white/10 relative shadow-sm">
                                {selectedFilm.poster_url ? (
                                    <img
                                        src={selectedFilm.poster_url}
                                        alt={selectedFilm.title}
                                        className="size-full object-cover"
                                    />
                                ) : (
                                    <div className="flex size-full items-center justify-center text-xs text-amber-200 font-bold p-1 text-center">
                                        {selectedFilm.title}
                                    </div>
                                )}
                            </div>
                            <div className="min-w-0">
                                <p className="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">
                                    Film yang kamu pilih:
                                </p>
                                <h2 className="text-base sm:text-lg font-black text-text truncate">
                                    {selectedFilm.title}
                                </h2>
                                <p className="text-xs sm:text-sm text-text-muted truncate mt-0.5">
                                    {selectedFilm.genre} · {selectedFilm.duration_minutes} menit {selectedFilm.age_rating ? `· ${selectedFilm.age_rating}` : ''}
                                </p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2 shrink-0">
                            <Link
                                href="/film"
                                className="inline-flex min-h-10 items-center justify-center rounded-xl border border-border bg-surface px-4 py-2 text-xs sm:text-sm font-bold text-text hover:bg-surface-2 transition"
                            >
                                Ganti film
                            </Link>
                        </div>
                    </div>
                )}

                {/* Horizontal Date Picker (14 Days) */}
                <div className="relative -mx-4 px-4 sm:mx-0 sm:px-0">
                    <nav
                        className="mt-6 flex snap-x gap-2.5 overflow-x-auto pt-1 pb-3 custom-scrollbar scroll-smooth"
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
                                    className={`min-w-28 shrink-0 snap-start rounded-2xl border p-3 text-center transition-all ${
                                        isSelected
                                            ? 'border-amber-500 bg-amber-500/15 text-text font-bold shadow-md shadow-amber-500/10'
                                            : 'border-border bg-surface text-text-muted hover:border-amber-400/50 hover:text-text'
                                    }`}
                                >
                                    <span
                                        className={`block text-xs font-bold uppercase tracking-wider ${
                                            isSelected ? 'text-amber-700 dark:text-amber-400' : 'text-text-muted'
                                        }`}
                                    >
                                        {d.is_today ? 'Hari ini' : d.day_name}
                                    </span>
                                    <span className="mt-1 block font-tabular text-base sm:text-lg font-black text-text">
                                        {d.date_formatted}
                                    </span>
                                    {d.is_holiday && (
                                        <span className="mt-1 inline-block rounded bg-amber-400/20 px-2 py-0.5 text-[10px] font-black uppercase text-amber-700 dark:text-amber-300 border border-amber-400/30">
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
                        <h2 className="text-lg sm:text-xl font-black text-text">{targetDateFormatted}</h2>
                    </div>
                    {holidayDates[targetDateStr] && (
                        <span className="rounded-xl bg-amber-400/20 border border-amber-400/40 px-3 py-1 text-xs font-black text-amber-700 dark:text-amber-300">
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
                            <p className="text-xs sm:text-sm text-text-muted leading-relaxed">
                                Sesi penayangan untuk tanggal ini belum dibuka atau sedang dalam penyesuaian jadwal operator.
                            </p>
                        </div>
                    </div>
                ) : (
                    <div className="mt-6 space-y-4 pb-8 sm:pb-4">
                        {/* TIX ID Style Studio Card with Showtime Grid */}
                        <div className="rounded-3xl border border-border/80 bg-surface shadow-sm overflow-hidden">
                            {/* Studio Header */}
                            <div className="p-4 sm:p-5 border-b border-border/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-surface-2/40">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <h3 className="text-base sm:text-lg font-black text-text tracking-tight">STUDIO 1</h3>
                                        <span className="px-2.5 py-0.5 rounded-full bg-amber-500/15 border border-amber-500/30 text-amber-700 dark:text-amber-400 text-xs font-black">
                                            DOLBY SURROUND
                                        </span>
                                    </div>
                                    <p className="text-xs sm:text-sm text-text-muted mt-1">
                                        {selectedFilm ? 'Pilih jam tayang yang cocok untukmu' : 'Film bebas dipilih pemesan pertama pada jam yang tersedia'}
                                    </p>
                                </div>
                                <div className="text-left sm:text-right">
                                    <span className="text-xs text-text-muted block">Harga Tiket:</span>
                                    <span className="text-sm sm:text-base font-black text-amber-600 dark:text-amber-400 font-mono">
                                        Mulai Rp {Number(sessionPrice || 5000).toLocaleString('id-ID')}
                                    </span>
                                </div>
                            </div>

                            {/* Showtimes Grid */}
                            <div className="p-4 sm:p-6">
                                {upcomingSessions.length === 0 ? (
                                    <div className="rounded-2xl border border-dashed border-border bg-surface-2/60 p-6 text-center text-xs sm:text-sm text-text-muted">
                                        Semua sesi penayangan untuk hari ini telah selesai atau sedang berlangsung.
                                    </div>
                                ) : (
                                    <div className="grid grid-cols-2 xs:grid-cols-3 sm:grid-cols-4 md:grid-cols-4 gap-2.5 sm:gap-3.5">
                                        {upcomingSessions.map((session) => {
                                            const sw = session.status_waktu || {};
                                            const canBook = session.can_book_this_session;
                                            const sessionFilm = session.film;
                                            const bookingUrl = `/booking/${session.id}${selectedFilm ? `?film=${selectedFilm.id}` : ''}`;
                                            const occupiedSeats = session.occupied_seats || 0;
                                            const capacity = session.capacity || 6;
                                            const remainingSeats = Math.max(0, capacity - occupiedSeats);
                                            const startTimeRaw = (session.start_time_formatted || session.start_time || '').replace(/\s*WIB/i, '').slice(0, 5);
                                            const endTimeRaw = (session.end_time_formatted || session.end_time || '').replace(/\s*WIB/i, '').slice(0, 5);

                                            if (!canBook) {
                                                return (
                                                    <div
                                                        key={session.id}
                                                        className="flex flex-col items-center justify-center p-3 sm:p-3.5 rounded-2xl border border-border/40 bg-surface-2/30 opacity-40 cursor-not-allowed text-center min-h-[78px] sm:min-h-[86px]"
                                                    >
                                                        <span className="font-tabular text-base sm:text-lg font-bold text-text-muted line-through">
                                                            {startTimeRaw}
                                                        </span>
                                                        <span className="text-xs text-text-muted mt-1 font-medium">
                                                            {sw.label || 'Selesai'}
                                                        </span>
                                                    </div>
                                                );
                                            }

                                            return (
                                                <Link
                                                    key={session.id}
                                                    href={session.booking_url || bookingUrl}
                                                    title={session.action_label || 'Pilih jam ini'}
                                                    className="group flex flex-col items-center justify-center p-3 sm:p-3.5 rounded-2xl border border-border/80 bg-surface hover:border-amber-500 hover:bg-amber-500/10 hover:shadow-md transition text-center min-h-[78px] sm:min-h-[86px] cursor-pointer"
                                                >
                                                    <span className="font-tabular text-lg sm:text-xl font-black text-text group-hover:text-amber-500 transition">
                                                        {startTimeRaw}
                                                    </span>
                                                    <span className="text-xs text-text-muted mt-0.5 font-medium">
                                                        s/d {endTimeRaw}
                                                    </span>
                                                    <div className="mt-1.5 flex items-center justify-center">
                                                        {session.is_private ? (
                                                            <span className="px-2 py-0.5 rounded-full text-xs font-bold bg-purple-500/15 text-purple-700 dark:text-purple-300">
                                                                Privat
                                                            </span>
                                                        ) : (
                                                            <span className={`px-2 py-0.5 rounded-full text-xs font-bold ${
                                                                remainingSeats > 0
                                                                    ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400'
                                                                    : 'bg-red-500/15 text-red-600 dark:text-red-400'
                                                            }`}>
                                                                {remainingSeats > 0 ? `${remainingSeats} Kursi` : 'Penuh'}
                                                            </span>
                                                        )}
                                                    </div>
                                                    {sessionFilm && !selectedFilm && (
                                                        <span className="mt-1 text-xs font-bold text-amber-600 dark:text-amber-400 truncate max-w-full">
                                                            {sessionFilm.title}
                                                        </span>
                                                    )}
                                                </Link>
                                            );
                                        })}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}
