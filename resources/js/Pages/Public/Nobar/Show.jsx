import { useState } from 'react';
import { Link, Head } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import Modal from '../../../Components/Modal';
import { Calendar, Clock, MapPin, Share2, ArrowLeft, Download, AlertTriangle, ExternalLink, Ticket } from 'lucide-react';

export default function Show({ event, otherEvents = [], isPreview = false }) {
    const [isPosterModalOpen, setIsPosterModalOpen] = useState(false);

    if (!event) return null;

    const posterSrc = event.poster_url || (event.poster_path ? `/storage/${event.poster_path}` : null);

    const formatFullDate = (dateStr) => {
        if (!dateStr) return '';
        try {
            const d = new Date(dateStr);
            return d.toLocaleDateString('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            });
        } catch (e) {
            return dateStr;
        }
    };

    const formatTime = (dateStr) => {
        if (!dateStr) return '';
        try {
            const d = new Date(dateStr);
            return d.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
            }) + ' WIB';
        } catch (e) {
            return dateStr;
        }
    };

    const handleShareWhatsApp = () => {
        const text = `Ayo nobar ${event.vs_title || event.judul} di SynemaX Home Cinema!\n` +
            `Waktu: ${formatFullDate(event.kickoff_at)} pukul ${formatTime(event.kickoff_at)}\n` +
            `Lokasi: ${event.lokasi_nama}\n` +
            `Info & Reservasi: ${window.location.href}`;
        window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
    };

    return (
        <PublicLayout title={`${event.judul} · Nobar`}>
            <Head title={`${event.judul} · Nobar`}>
                <meta property="og:type" content="event" />
                <meta property="og:title" content={`${event.judul} · Nobar SynemaX`} />
                <meta
                    property="og:description"
                    content={`${event.vs_title || event.judul}, ${formatFullDate(event.kickoff_at)} pukul ${formatTime(event.kickoff_at)} di ${event.lokasi_nama}.`}
                />
            </Head>

            <div className="mx-auto max-w-5xl px-4 py-6 sm:px-6 sm:py-10 space-y-6">
                {/* Back button */}
                <div>
                    <Link
                        href="/nobar"
                        className="inline-flex min-h-11 items-center gap-2 text-xs font-bold text-amber-500 hover:text-amber-400 transition"
                    >
                        <ArrowLeft className="size-4" />
                        <span>Nobar</span>
                    </Link>
                </div>

                {/* Preview Alert */}
                {isPreview && (
                    <div role="alert" className="rounded-2xl border border-amber-400/40 bg-amber-500/10 p-4 text-xs text-amber-300">
                        <span className="font-black">Mode Pratinjau Admin</span>: Acara ini berstatus draft dan belum terbit ke publik.
                    </div>
                )}

                {/* Event Status Alert */}
                {event.status_acara && event.status_acara !== 'aktif' && (
                    <div className="rounded-2xl border border-rose-500/40 bg-rose-500/10 p-4 text-xs text-rose-300 flex items-start gap-3">
                        <AlertTriangle className="size-4 shrink-0 mt-0.5 text-rose-400" />
                        <div>
                            <p className="font-black capitalize">Status acara: {event.status_acara}</p>
                            {event.alasan_status && (
                                <p className="mt-1 text-text-muted leading-relaxed">{event.alasan_status}</p>
                            )}
                        </div>
                    </div>
                )}

                {/* Main Content Layout */}
                <div className={`grid gap-8 ${posterSrc ? 'lg:grid-cols-[360px,1fr]' : 'grid-cols-1'}`}>
                    {/* Poster Column */}
                    {posterSrc && (
                        <aside className="space-y-4">
                            <div className="rounded-3xl border border-border dark:border-white/10 bg-surface dark:bg-slate-900/40 p-4 shadow-xl">
                                <button
                                    type="button"
                                    onClick={() => setIsPosterModalOpen(true)}
                                    className="group relative flex w-full items-center justify-center overflow-hidden rounded-2xl bg-slate-100 dark:bg-black/60 p-2 border border-border dark:border-white/5 shadow-inner cursor-zoom-in"
                                >
                                    <img
                                        src={posterSrc}
                                        alt={event.poster_alt || event.judul}
                                        className="max-h-[500px] w-auto max-w-full object-contain mx-auto rounded-xl transition duration-500 group-hover:scale-105"
                                    />
                                    <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold rounded-2xl">
                                        Klik untuk memperbesar
                                    </div>
                                </button>
                            </div>

                            {/* Action Buttons */}
                            <div className="flex flex-col gap-2">
                                <a
                                    href={`/nobar/${event.slug || event.id}/kalender`}
                                    className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-border bg-surface hover:bg-surface-2 text-xs font-bold text-text transition"
                                >
                                    <Download className="size-4 text-amber-500" />
                                    <span>Simpan ke Kalender (.ics)</span>
                                </a>
                                <button
                                    type="button"
                                    onClick={handleShareWhatsApp}
                                    className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-sm"
                                >
                                    <Share2 className="size-4" />
                                    <span>Bagikan ke WhatsApp</span>
                                </button>
                            </div>
                        </aside>
                    )}

                    {/* Details Column */}
                    <main className="space-y-6">
                        {/* Event Title Header */}
                        <div className="rounded-3xl border border-border dark:border-white/10 bg-surface p-6 sm:p-8 space-y-4 shadow-xl">
                            <div className="flex flex-wrap items-center gap-2">
                                {event.kompetisi && (
                                    <span className="rounded-lg bg-amber-500/10 border border-amber-500/30 px-3 py-1 text-xs font-black text-amber-500 uppercase tracking-wider">
                                        {event.kompetisi}
                                    </span>
                                )}
                                <span className={`rounded-lg px-3 py-1 text-xs font-black uppercase tracking-wider ${
                                    event.is_free ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/15 text-amber-400 border border-amber-500/30'
                                }`}>
                                    {event.is_free ? 'Tiket Masuk Gratis' : (event.harga_info || 'Berbayar')}
                                </span>
                            </div>

                            <h1 className="text-2xl sm:text-3xl font-black text-text tracking-tight">
                                {event.judul}
                            </h1>

                            {/* Match vs banner */}
                            {(event.tim_a || event.tim_b) && (
                                <div className="rounded-2xl bg-surface-2/80 dark:bg-black/30 border border-border p-4 flex items-center justify-between text-center gap-4">
                                    <div className="flex-1 font-black text-sm sm:text-base text-text">
                                        {event.tim_a}
                                    </div>
                                    <span className="px-3 py-1 rounded-full bg-amber-500 text-black text-xs font-black uppercase tracking-wider">
                                        VS
                                    </span>
                                    <div className="flex-1 font-black text-sm sm:text-base text-text">
                                        {event.tim_b || '-'}
                                    </div>
                                </div>
                            )}

                            {/* Logistics details */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-border">
                                <div className="space-y-1">
                                    <span className="text-[11px] font-semibold text-text-muted uppercase tracking-wider">
                                        Waktu Kick-off
                                    </span>
                                    <div className="flex items-center gap-2 text-sm font-bold text-text">
                                        <Calendar className="size-4 text-amber-500" />
                                        <span>{formatFullDate(event.kickoff_at)}</span>
                                    </div>
                                    <div className="flex items-center gap-2 text-xs font-semibold text-text-muted">
                                        <Clock className="size-3.5 text-amber-500" />
                                        <span>{formatTime(event.kickoff_at)} ({event.durasi_menit || 120} menit)</span>
                                    </div>
                                </div>

                                <div className="space-y-1">
                                    <span className="text-[11px] font-semibold text-text-muted uppercase tracking-wider">
                                        Lokasi Acara
                                    </span>
                                    <div className="flex items-center gap-2 text-sm font-bold text-text">
                                        <MapPin className="size-4 text-amber-500 shrink-0" />
                                        <span>{event.lokasi_nama}</span>
                                    </div>
                                    {event.alamat_patokan && (
                                        <p className="text-xs text-text-muted pl-6">
                                            {event.alamat_patokan}
                                        </p>
                                    )}
                                    {event.maps_url && (
                                        <div className="pl-6 pt-1">
                                            <a
                                                href={event.maps_url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="inline-flex items-center gap-1.5 text-xs font-bold text-amber-500 hover:underline"
                                            >
                                                <span>Buka di Google Maps</span>
                                                <ExternalLink className="size-3" />
                                            </a>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Catatan & Peraturan */}
                        {event.catatan && (
                            <div className="rounded-3xl border border-border dark:border-white/10 bg-surface p-6 sm:p-8 space-y-3 shadow-xl">
                                <h2 className="text-base font-black text-text border-l-4 border-amber-500 pl-3">
                                    Catatan & Ketentuan Nobar
                                </h2>
                                <div
                                    className="prose dark:prose-invert max-w-none text-sm text-text-muted leading-relaxed"
                                    dangerouslySetInnerHTML={{ __html: event.catatan_html || event.catatan }}
                                />
                            </div>
                        )}
                    </main>
                </div>

                {/* Other Nobar Events */}
                {otherEvents && otherEvents.length > 0 && (
                    <div className="pt-8 border-t border-border space-y-4">
                        <h2 className="text-xl font-black text-text">Jadwal Nobar Lainnya</h2>
                        <div className="grid gap-4 sm:grid-cols-3">
                            {otherEvents.map((item) => (
                                <Link
                                    key={item.id}
                                    href={`/nobar/${item.slug || item.id}`}
                                    className="rounded-2xl border border-border bg-surface p-4 hover:border-amber-500 hover:shadow-md transition group"
                                >
                                    <span className="text-[10px] font-bold text-amber-500 uppercase">
                                        {item.kompetisi || 'Nobar'}
                                    </span>
                                    <h3 className="mt-1 text-sm font-bold text-text group-hover:text-amber-500 line-clamp-2">
                                        {item.judul}
                                    </h3>
                                    <p className="mt-2 text-xs text-text-muted">
                                        {formatFullDate(item.kickoff_at)}
                                    </p>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}
            </div>

            {/* Poster Zoom Modal */}
            {posterSrc && (
                <Modal
                    isOpen={isPosterModalOpen}
                    onClose={() => setIsPosterModalOpen(false)}
                    title={event.judul}
                    maxWidth="max-w-3xl"
                >
                    <div className="p-4 flex items-center justify-center bg-black/90 rounded-2xl">
                        <img
                            src={posterSrc}
                            alt={event.poster_alt || event.judul}
                            className="max-h-[80vh] w-auto object-contain rounded-xl"
                        />
                    </div>
                </Modal>
            )}
        </PublicLayout>
    );
}

