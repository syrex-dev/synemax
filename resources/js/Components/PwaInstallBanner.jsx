import { useState, useEffect } from 'react';
import { Download, X, Smartphone, Share2, PlusSquare, Sparkles } from 'lucide-react';
import Modal from './Modal';

export default function PwaInstallBanner({ forceType = null }) {
    const [deferredPrompt, setDeferredPrompt] = useState(null);
    const [showBanner, setShowBanner] = useState(false);
    const [isInstalled, setIsInstalled] = useState(false);
    const [iosModalOpen, setIosModalOpen] = useState(false);
    const [isAdmin, setIsAdmin] = useState(false);

    useEffect(() => {
        // Detect admin vs public
        if (typeof window !== 'undefined') {
            const adminCheck = forceType ? forceType === 'admin' : window.location.pathname.startsWith('/admin');
            setIsAdmin(adminCheck);
        }

        // Check if already running in standalone PWA mode
        const checkStandalone = () => {
            if (typeof window === 'undefined') return false;
            const isPwa = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            setIsInstalled(isPwa);
            return isPwa;
        };

        if (checkStandalone()) return;

        // Check if recently dismissed
        const dismissedAt = localStorage.getItem('synemax_pwa_dismissed');
        const now = Date.now();
        const threeDays = 3 * 24 * 60 * 60 * 1000;

        const handleBeforeInstallPrompt = (e) => {
            e.preventDefault();
            setDeferredPrompt(e);
            window.__synemax_pwa_prompt = e;

            if (!dismissedAt || now - Number(dismissedAt) >= threeDays) {
                setShowBanner(true);
            }
        };

        const handleAppInstalled = () => {
            setIsInstalled(true);
            setShowBanner(false);
            setDeferredPrompt(null);
            window.__synemax_pwa_prompt = null;
        };

        const handleManualOpen = () => {
            setShowBanner(true);
        };

        window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
        window.addEventListener('appinstalled', handleAppInstalled);
        window.addEventListener('synemax-open-pwa-install', handleManualOpen);

        // Auto offer banner for mobile/desktop if not standalone & not recently dismissed
        const timer = setTimeout(() => {
            if (!checkStandalone() && (!dismissedAt || now - Number(dismissedAt) >= threeDays)) {
                setShowBanner(true);
            }
        }, 1500);

        return () => {
            window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
            window.removeEventListener('appinstalled', handleAppInstalled);
            window.removeEventListener('synemax-open-pwa-install', handleManualOpen);
            clearTimeout(timer);
        };
    }, [forceType]);

    const handleInstall = async () => {
        const prompt = deferredPrompt || window.__synemax_pwa_prompt;
        const isIos = typeof navigator !== 'undefined' && /iPhone|iPad|iPod/.test(navigator.userAgent);

        if (prompt) {
            prompt.prompt();
            const choice = await prompt.userChoice;
            if (choice.outcome === 'accepted') {
                setShowBanner(false);
                setIsInstalled(true);
            }
            setDeferredPrompt(null);
            window.__synemax_pwa_prompt = null;
        } else if (isIos) {
            setIosModalOpen(true);
        } else {
            // Android / Desktop fallback if prompt was already used or browser blocked automatic prompt
            alert('Untuk memasang aplikasi: Ketuk tombol menu browser (ikon titik tiga di pojok atas) dan pilih "Install Aplikasi" atau "Tambahkan ke Layar Utama".');
        }
    };

    const handleDismiss = () => {
        setShowBanner(false);
        localStorage.setItem('synemax_pwa_dismissed', String(Date.now()));
    };

    if (isInstalled) return null;

    const appTitle = isAdmin ? 'Aplikasi SynemaX Admin' : 'Aplikasi SynemaX';
    const appDesc = isAdmin
        ? 'Pasang di layar HP untuk akses cepat satu ketukan ke loket kasir, scan tiket, dan pantau bioskop tanpa browser bar.'
        : 'Pasang di layar HP untuk pesan tiket lebih cepat, cek jadwal bioskop real-time, dan akses tiket digital instan!';

    return (
        <>
            {/* iOS Guide Modal */}
            <Modal
                show={iosModalOpen}
                onClose={() => setIosModalOpen(false)}
                title={`Pasang ${appTitle} di iPhone/iPad`}
                maxWidth="md"
            >
                <div className="p-4 sm:p-5 space-y-4 text-xs sm:text-sm text-text">
                    <p className="text-text-muted leading-relaxed">
                        Ikuti 3 langkah mudah berikut di browser Safari untuk memasang aplikasi ke layar utama Anda:
                    </p>

                    <div className="space-y-3 pt-1">
                        <div className="flex items-start gap-3 p-3 rounded-xl border border-border bg-surface-2">
                            <div className="size-7 rounded-lg bg-amber-500/15 text-amber-500 flex items-center justify-center shrink-0 font-black">
                                1
                            </div>
                            <div className="flex-1 min-w-0">
                                <p className="font-bold">Ketuk tombol Bagikan (Share)</p>
                                <p className="text-text-muted text-xs mt-0.5 flex items-center gap-1.5">
                                    <span>Tekan ikon</span>
                                    <Share2 className="size-3.5 text-amber-500" />
                                    <span>di bilah navigasi bawah Safari.</span>
                                </p>
                            </div>
                        </div>

                        <div className="flex items-start gap-3 p-3 rounded-xl border border-border bg-surface-2">
                            <div className="size-7 rounded-lg bg-amber-500/15 text-amber-500 flex items-center justify-center shrink-0 font-black">
                                2
                            </div>
                            <div className="flex-1 min-w-0">
                                <p className="font-bold">Pilih "Tambahkan ke Layar Utama"</p>
                                <p className="text-text-muted text-xs mt-0.5 flex items-center gap-1.5">
                                    <span>Cari dan pilih opsi</span>
                                    <PlusSquare className="size-3.5 text-amber-500" />
                                    <span>(Add to Home Screen).</span>
                                </p>
                            </div>
                        </div>

                        <div className="flex items-start gap-3 p-3 rounded-xl border border-border bg-surface-2">
                            <div className="size-7 rounded-lg bg-amber-500/15 text-amber-500 flex items-center justify-center shrink-0 font-black">
                                3
                            </div>
                            <div className="flex-1 min-w-0">
                                <p className="font-bold">Ketuk "Tambah" (Add)</p>
                                <p className="text-text-muted text-xs mt-0.5">
                                    Ketuk tombol Tambah di pojok kanan atas layar Anda untuk menyelesaikan pemasangan.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="pt-2 flex justify-end">
                        <button
                            type="button"
                            onClick={() => {
                                setIosModalOpen(false);
                                setShowBanner(false);
                            }}
                            className="btn-gold-cinema px-5 py-2.5 rounded-xl font-bold text-xs"
                        >
                            Saya Mengerti
                        </button>
                    </div>
                </div>
            </Modal>

            {/* Floating Install Offer Banner / Notification */}
            {showBanner && (
                <aside
                    aria-label={`Tawaran Pasang ${appTitle}`}
                    className="fixed bottom-18 lg:bottom-4 left-3 right-3 sm:left-auto sm:right-6 sm:w-[400px] z-50 animate-in slide-in-from-bottom-5 duration-300"
                >
                    <div className="rounded-2xl border-2 border-amber-500/60 bg-surface/98 dark:bg-[#0E131F]/98 backdrop-blur-md p-3.5 sm:p-4 shadow-2xl flex items-start gap-3 relative ring-4 ring-amber-500/10">
                        {/* Cinema App Icon */}
                        <div className="relative shrink-0">
                            <img
                                src={isAdmin ? '/icon-admin-192.png?v=4' : '/icon-192.png?v=3'}
                                alt={`${appTitle} Icon`}
                                className="size-12 rounded-xl border border-amber-500/40 shadow-xs object-contain bg-[#0B1220]"
                            />
                            <span className="absolute -top-1 -right-1 flex size-3.5 items-center justify-center rounded-full bg-amber-500 text-slate-950">
                                <Sparkles className="size-2" />
                            </span>
                        </div>

                        <div className="min-w-0 flex-1">
                            <div className="flex items-center justify-between gap-1">
                                <div className="flex items-center gap-1.5 min-w-0">
                                    <Smartphone className="size-3.5 text-amber-500 shrink-0" />
                                    <h4 className="text-xs sm:text-sm font-black text-text truncate">
                                        {appTitle}
                                    </h4>
                                </div>
                                <button
                                    type="button"
                                    onClick={handleDismiss}
                                    className="p-1 rounded-lg text-text-muted hover:text-text hover:bg-surface-2 transition shrink-0"
                                    aria-label="Tutup penawaran pasang aplikasi"
                                >
                                    <X className="size-4" />
                                </button>
                            </div>

                            <p className="text-[11px] text-text-muted leading-relaxed mt-1">
                                {appDesc}
                            </p>

                            <div className="mt-2.5 flex items-center gap-2">
                                <button
                                    type="button"
                                    onClick={handleInstall}
                                    className="btn-gold-cinema px-3.5 py-2 rounded-xl text-xs font-black shadow-md shadow-amber-500/20 inline-flex items-center gap-1.5 flex-1 justify-center hover:scale-[1.02] transition"
                                >
                                    <Download className="size-3.5 stroke-[2.5]" />
                                    <span>Pasang Sekarang</span>
                                </button>
                                <button
                                    type="button"
                                    onClick={handleDismiss}
                                    className="px-2.5 py-2 rounded-xl text-xs font-bold text-text-muted hover:text-text hover:bg-surface-2 transition"
                                >
                                    Nanti
                                </button>
                            </div>
                        </div>
                    </div>
                </aside>
            )}
        </>
    );
}
