import { Head, Link, usePage } from '@inertiajs/react';
import PublicLayout from '../Layouts/PublicLayout';
import { Film, Home, Calendar, MessageCircle, AlertTriangle, ShieldAlert, ServerCrash, Clock, RefreshCw } from 'lucide-react';

export default function Error({ status = 404, message = null }) {
    const { cinemaSettings } = usePage().props;
    const waNumber = cinemaSettings?.contactWhatsapp ? String(cinemaSettings.contactWhatsapp).replace(/\D/g, '') : '6285169730960';

    const errorConfigs = {
        404: {
            title: 'Halaman Tidak Ditemukan',
            badge: '404 · Not Found',
            description: message || 'Maaf, halaman, film, atau sesi penayangan yang Anda tuju tidak ditemukan atau tautan telah dipindahkan.',
            icon: Film,
            color: 'text-amber-500',
            borderColor: 'border-amber-500/30',
            bgColor: 'bg-amber-500/10',
            glowColor: 'bg-amber-500/15',
        },
        403: {
            title: 'Akses Ditolak',
            badge: '403 · Forbidden',
            description: message || 'Anda tidak memiliki hak akses yang cukup untuk membuka halaman ini.',
            icon: ShieldAlert,
            color: 'text-rose-500',
            borderColor: 'border-rose-500/30',
            bgColor: 'bg-rose-500/10',
            glowColor: 'bg-rose-500/15',
        },
        419: {
            title: 'Sesi Kedaluwarsa',
            badge: '419 · Page Expired',
            description: message || 'Sesi penjelajahan Anda telah berakhir demi keamanan. Silakan muat ulang halaman atau login kembali.',
            icon: Clock,
            color: 'text-amber-500',
            borderColor: 'border-amber-500/30',
            bgColor: 'bg-amber-500/10',
            glowColor: 'bg-amber-500/15',
        },
        500: {
            title: 'Terjadi Kendala Server',
            badge: '500 · Server Error',
            description: message || 'Terjadi gangguan internal sementara pada server kami. Mohon coba beberapa saat lagi.',
            icon: ServerCrash,
            color: 'text-rose-500',
            borderColor: 'border-rose-500/30',
            bgColor: 'bg-rose-500/10',
            glowColor: 'bg-rose-500/15',
        },
        503: {
            title: 'Sedang Dalam Pemeliharaan',
            badge: '503 · Mode Pemeliharaan',
            description: message || 'Layanan SynemaX sedang dalam peningkatan sistem berkala untuk kenyamanan menonton Anda. Silakan kembali beberapa saat lagi.',
            icon: RefreshCw,
            color: 'text-amber-500',
            borderColor: 'border-amber-500/30',
            bgColor: 'bg-amber-500/10',
            glowColor: 'bg-amber-500/20',
        },
    };

    const config = errorConfigs[status] || {
        title: 'Terjadi Kesalahan',
        badge: `${status} · Error`,
        description: message || 'Terjadi kesalahan saat memproses permintaan Anda.',
        icon: AlertTriangle,
        color: 'text-amber-500',
        borderColor: 'border-amber-500/30',
        bgColor: 'bg-amber-500/10',
        glowColor: 'bg-amber-500/15',
    };

    const Icon = config.icon;
    const isMaintenance = status === 503;

    return (
        <PublicLayout title={`${config.title} (${status})`}>
            <Head title={`${config.title} · SynemaX`} />

            <div className="relative min-h-[70vh] flex items-center justify-center px-4 py-12 sm:px-6">
                {/* Ambient Glow */}
                <div
                    className={`pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 size-72 sm:size-96 rounded-full ${config.glowColor} blur-3xl -z-10`}
                    aria-hidden="true"
                />

                {/* Central Card */}
                <div className="relative w-full max-w-lg rounded-3xl border border-border/80 dark:border-white/10 bg-surface/90 dark:bg-[#0B0F19]/90 backdrop-blur-xl p-6 sm:p-10 shadow-2xl space-y-6 text-center animate-in fade-in zoom-in-95 duration-200">
                    {/* Status Badge */}
                    <div>
                        <span className={`inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full border text-xs font-black tracking-wider uppercase shadow-xs ${config.borderColor} ${config.bgColor}`}>
                            <span className="relative flex size-2">
                                <span className={`animate-ping absolute inline-flex size-full rounded-full opacity-75 ${status === 503 ? 'bg-amber-400' : config.color.replace('text-', 'bg-')}`} />
                                <span className={`relative inline-flex size-2 rounded-full ${status === 503 ? 'bg-amber-500' : config.color.replace('text-', 'bg-')}`} />
                            </span>
                            <span className={config.color}>{config.badge}</span>
                        </span>
                    </div>

                    {/* Icon Container */}
                    <div className="relative mx-auto size-20 sm:size-24 flex items-center justify-center">
                        <div className={`absolute inset-0 rounded-3xl ${config.glowColor} blur-lg animate-pulse`} />
                        <div className={`relative size-full rounded-3xl border ${config.borderColor} bg-surface dark:bg-black/40 flex items-center justify-center shadow-inner`}>
                            <Icon className={`size-9 sm:size-10 ${config.color} ${isMaintenance ? 'animate-spin' : ''}`} style={isMaintenance ? { animationDuration: '10s' } : undefined} />
                        </div>
                    </div>

                    {/* Heading & Description */}
                    <div className="space-y-3">
                        <h1 className="text-2xl sm:text-3xl font-black text-text tracking-tight">
                            {config.title}
                        </h1>
                        <div className="rounded-2xl border border-border/70 dark:border-white/5 bg-surface-2/60 dark:bg-white/[0.02] p-4 text-xs sm:text-sm text-text-muted leading-relaxed font-medium">
                            {config.description}
                        </div>
                    </div>

                    {/* Action Buttons */}
                    <div className="pt-1 flex flex-col sm:flex-row items-center justify-center gap-3">
                        {isMaintenance ? (
                            <button
                                type="button"
                                onClick={() => window.location.reload()}
                                className="btn-gold-cinema inline-flex min-h-11 w-full sm:w-auto items-center justify-center gap-2 rounded-xl px-7 py-2.5 text-xs font-black shadow-md shadow-amber-500/20 hover:scale-[1.02] active:scale-[0.98] transition cursor-pointer"
                            >
                                <RefreshCw className="size-4" />
                                <span>Muat Ulang Halaman</span>
                            </button>
                        ) : (
                            <>
                                <Link
                                    href="/"
                                    className="btn-gold-cinema inline-flex min-h-11 w-full sm:w-auto items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-xs font-black shadow-md shadow-amber-500/20"
                                >
                                    <Home className="size-4" />
                                    <span>Kembali ke Beranda</span>
                                </Link>

                                <Link
                                    href="/jadwal"
                                    className="inline-flex min-h-11 w-full sm:w-auto items-center justify-center gap-2 rounded-xl border border-border bg-surface px-5 py-2.5 text-xs font-bold text-text hover:bg-surface-2 transition shadow-xs"
                                >
                                    <Calendar className="size-4 text-amber-500" />
                                    <span>Lihat Jadwal Tayang</span>
                                </Link>
                            </>
                        )}
                    </div>

                    {/* Support Help */}
                    <div className="pt-4 border-t border-border/60 flex flex-col items-center justify-center gap-2 text-xs text-text-muted">
                        <span>{isMaintenance ? 'Pertanyaan seputar pemesanan & tiket bioskop?' : 'Butuh bantuan segera? Hubungi kami via'}</span>
                        <a
                            href={`https://wa.me/${waNumber}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 font-bold hover:bg-emerald-500/20 transition"
                        >
                            <MessageCircle className="size-3.5 text-emerald-500" />
                            <span>WhatsApp Customer Care</span>
                        </a>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
