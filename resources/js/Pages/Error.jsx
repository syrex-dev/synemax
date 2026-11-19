import { Head, Link, usePage } from '@inertiajs/react';
import PublicLayout from '../Layouts/PublicLayout';
import { Film, Home, Calendar, MessageCircle, AlertTriangle, ShieldAlert, ServerCrash, Clock, RefreshCw } from 'lucide-react';

export default function Error({ status = 404, message = null }) {
    const { auth, cinemaSettings } = usePage().props;
    const waNumber = cinemaSettings?.contactWhatsapp ? String(cinemaSettings.contactWhatsapp).replace(/\D/g, '') : '6285169730960';
    const errorConfigs = {
        404: {
            title: 'Halaman Tidak Ditemukan',
            badge: '404 · Not Found',
            description: message || 'Maaf, halaman, film, atau sesi penayangan yang Anda tuju tidak ditemukan atau tautan telah dipindahkan.',
            icon: Film,
            color: 'text-amber-500',
            bg: 'bg-amber-500/10 border-amber-500/30',
        },
        403: {
            title: 'Akses Ditolak',
            badge: '403 · Forbidden',
            description: message || 'Anda tidak memiliki hak akses yang cukup untuk membuka halaman ini.',
            icon: ShieldAlert,
            color: 'text-rose-500',
            bg: 'bg-rose-500/10 border-rose-500/30',
        },
        419: {
            title: 'Sesi Kedaluwarsa',
            badge: '419 · Page Expired',
            description: message || 'Sesi penjelajahan Anda telah berakhir demi keamanan. Silakan muat ulang halaman atau login kembali.',
            icon: Clock,
            color: 'text-amber-500',
            bg: 'bg-amber-500/10 border-amber-500/30',
        },
        500: {
            title: 'Terjadi Kendala Server',
            badge: '500 · Server Error',
            description: message || 'Terjadi gangguan internal sementara pada server kami. Mohon coba beberapa saat lagi.',
            icon: ServerCrash,
            color: 'text-rose-500',
            bg: 'bg-rose-500/10 border-rose-500/30',
        },
        503: {
            title: 'Sedang Dalam Pemeliharaan',
            badge: '503 · Maintenance',
            description: message || 'Layanan SynemaX sedang dalam proses pemeliharaan rutin untuk meningkatkan kualitas. Segera kembali.',
            icon: RefreshCw,
            color: 'text-sky-500',
            bg: 'bg-sky-500/10 border-sky-500/30',
        },
    };

    const config = errorConfigs[status] || {
        title: 'Terjadi Kesalahan',
        badge: `${status} · Error`,
        description: message || 'Terjadi kesalahan saat memproses permintaan Anda.',
        icon: AlertTriangle,
        color: 'text-amber-500',
        bg: 'bg-amber-500/10 border-amber-500/30',
    };

    const Icon = config.icon;

    return (
        <PublicLayout title={`${config.title} (${status})`}>
            <Head title={`${config.title} · SynemaX`} />

            <div className="min-h-[70vh] flex items-center justify-center px-4 py-12 sm:px-6">
                <div className="w-full max-w-lg text-center space-y-6">
                    {/* Status Badge */}
                    <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border text-xs font-black tracking-wider uppercase shadow-xs transition-all animate-in fade-in zoom-in-95 duration-200">
                        <span className={`inline-block size-2 rounded-full ${config.color === 'text-rose-500' ? 'bg-rose-500' : 'bg-amber-500'} animate-ping`} />
                        <span className={config.color}>{config.badge}</span>
                    </div>

                    {/* Icon Container */}
                    <div className="mx-auto flex size-20 sm:size-24 items-center justify-center rounded-3xl border bg-surface-2/80 dark:bg-surface/50 shadow-inner">
                        <Icon className={`size-10 sm:size-12 ${config.color}`} />
                    </div>

                    {/* Heading & Description */}
                    <div className="space-y-2">
                        <h1 className="text-2xl sm:text-3xl font-black text-text tracking-tight">
                            {config.title}
                        </h1>
                        <p className="text-xs sm:text-sm text-text-muted max-w-md mx-auto leading-relaxed">
                            {config.description}
                        </p>
                    </div>

                    {/* Action Buttons */}
                    <div className="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                        {status === 503 ? (
                            <>
                                <button
                                    type="button"
                                    onClick={() => window.location.reload()}
                                    className="btn-gold-cinema inline-flex min-h-11 w-full sm:w-auto items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-xs font-black shadow-md shadow-amber-500/20"
                                >
                                    <RefreshCw className="size-4" />
                                    <span>Muat Ulang Halaman</span>
                                </button>

                                {auth?.user?.is_admin && (
                                    <div className="flex flex-wrap items-center justify-center gap-2">
                                        <a
                                            href="/admin/pengaturan?tab=operasional"
                                            className="inline-flex min-h-11 w-full sm:w-auto items-center justify-center gap-2 rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-2.5 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 transition shadow-xs"
                                        >
                                            <ShieldAlert className="size-4" />
                                            <span>Buka Pengaturan Admin</span>
                                        </a>
                                        <a
                                            href="/?preview=1"
                                            className="inline-flex min-h-11 w-full sm:w-auto items-center justify-center gap-2 rounded-xl border border-border bg-surface px-4 py-2.5 text-xs font-bold text-text hover:bg-surface-2 transition shadow-xs"
                                        >
                                            <span>Pratinjau Situs</span>
                                        </a>
                                    </div>
                                )}
                            </>
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

                    {/* Quick Help */}
                    <div className="pt-6 border-t border-border/60 text-xs text-text-muted">
                        <span>Butuh bantuan segera? Hubungi kami via </span>
                        <a
                            href={`https://wa.me/${waNumber}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="font-bold text-emerald-600 dark:text-emerald-400 hover:underline inline-flex items-center gap-1"
                        >
                            <MessageCircle className="size-3.5 inline" />
                            <span>WhatsApp Customer Care</span>
                        </a>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
