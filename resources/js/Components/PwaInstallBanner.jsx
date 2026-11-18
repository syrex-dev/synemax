import { useState, useEffect } from 'react';
import { Download, X, Smartphone, Check } from 'lucide-react';

export default function PwaInstallBanner() {
    const [deferredPrompt, setDeferredPrompt] = useState(null);
    const [showBanner, setShowBanner] = useState(false);
    const [isInstalled, setIsInstalled] = useState(false);

    useEffect(() => {
        // Check if already in standalone PWA mode
        const checkStandalone = () => {
            const isPwa = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            setIsInstalled(isPwa);
            return isPwa;
        };

        if (checkStandalone()) return;

        // Check if recently dismissed
        const dismissedAt = localStorage.getItem('synemax_pwa_dismissed');
        const now = Date.now();
        const twoDays = 2 * 24 * 60 * 60 * 1000;

        if (dismissedAt && now - Number(dismissedAt) < twoDays) {
            // Dismissed recently, but still listen for event in case user clicks manual install
        }

        const handleBeforeInstallPrompt = (e) => {
            e.preventDefault();
            setDeferredPrompt(e);
            window.__synemax_pwa_prompt = e;

            // If not recently dismissed, show banner
            if (!dismissedAt || now - Number(dismissedAt) >= twoDays) {
                setShowBanner(true);
            }
        };

        const handleAppInstalled = () => {
            setIsInstalled(true);
            setShowBanner(false);
            setDeferredPrompt(null);
            window.__synemax_pwa_prompt = null;
        };

        window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
        window.addEventListener('appinstalled', handleAppInstalled);

        // Fallback for mobile devices if prompt didn't fire immediately
        const timer = setTimeout(() => {
            if (!checkStandalone() && (!dismissedAt || now - Number(dismissedAt) >= twoDays)) {
                // Show banner if on mobile screen
                if (window.innerWidth < 1024) {
                    setShowBanner(true);
                }
            }
        }, 1500);

        return () => {
            window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
            window.removeEventListener('appinstalled', handleAppInstalled);
            clearTimeout(timer);
        };
    }, []);

    const handleInstall = async () => {
        const prompt = deferredPrompt || window.__synemax_pwa_prompt;
        if (prompt) {
            prompt.prompt();
            const choice = await prompt.userChoice;
            if (choice.outcome === 'accepted') {
                setShowBanner(false);
                setIsInstalled(true);
            }
            setDeferredPrompt(null);
            window.__synemax_pwa_prompt = null;
        } else {
            // If deferredPrompt is unavailable (e.g. iOS Safari)
            const isIos = /iPhone|iPad|iPod/.test(navigator.userAgent);
            if (isIos) {
                alert('Untuk memasang di iPhone/iPad: Tekan tombol Bagikan (Share) di Safari, lalu pilih "Tambahkan ke Layar Utama" (Add to Home Screen).');
            } else {
                alert('Buka menu browser Anda (titik 3 di kanan atas) dan pilih "Install Aplikasi" atau "Tambahkan ke Layar Utama".');
            }
        }
    };

    const handleDismiss = () => {
        setShowBanner(false);
        localStorage.setItem('synemax_pwa_dismissed', String(Date.now()));
    };

    if (isInstalled || !showBanner) return null;

    return (
        <aside
            aria-label="Pemberitahuan Pasang Aplikasi SynemaX"
            className="fixed bottom-20 lg:bottom-4 left-3 right-3 sm:left-auto sm:right-6 sm:w-96 z-50 animate-in slide-in-from-bottom-5 duration-300"
        >
            <div className="rounded-2xl border-2 border-amber-500/50 bg-surface/98 dark:bg-[#0E131F]/98 backdrop-blur-md p-3.5 sm:p-4 shadow-2xl flex items-start gap-3">
                {/* Cinema App Icon */}
                <img
                    src="/icon-192.png"
                    alt="SynemaX App Icon"
                    className="size-12 rounded-xl border border-amber-500/40 shadow-xs shrink-0 object-contain bg-[#0A0D15]"
                />

                <div className="min-w-0 flex-1">
                    <div className="flex items-center justify-between gap-1">
                        <h4 className="text-xs sm:text-sm font-black text-text truncate">
                            Install SynemaX Admin
                        </h4>
                        <button
                            type="button"
                            onClick={handleDismiss}
                            className="p-1 rounded-lg text-text-muted hover:text-text hover:bg-surface-2 transition shrink-0"
                            aria-label="Tutup"
                        >
                            <X className="size-4" />
                        </button>
                    </div>

                    <p className="text-[11px] text-text-muted leading-relaxed mt-0.5">
                        Pasang di layar utama HP untuk akses cepat satu ketukan dan pengalaman layar penuh tanpa browser bar.
                    </p>

                    <div className="mt-2.5 flex items-center gap-2">
                        <button
                            type="button"
                            onClick={handleInstall}
                            className="btn-gold-cinema px-3.5 py-1.5 rounded-xl text-xs font-black shadow-xs inline-flex items-center gap-1.5 flex-1 justify-center"
                        >
                            <Download className="size-3.5 stroke-[2.5]" />
                            <span>Pasang Sekarang</span>
                        </button>
                        <button
                            type="button"
                            onClick={handleDismiss}
                            className="px-2.5 py-1.5 rounded-xl text-xs font-bold text-text-muted hover:text-text transition"
                        >
                            Nanti
                        </button>
                    </div>
                </div>
            </div>
        </aside>
    );
}

