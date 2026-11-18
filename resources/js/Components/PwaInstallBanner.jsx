import { useState, useEffect } from 'react';
import { Download, X, Smartphone, Share2, PlusSquare, Sparkles, CheckCircle2, Laptop, MoreVertical, Layers } from 'lucide-react';
import Modal from './Modal';

export default function PwaInstallBanner({ forceType = null }) {
    const [showBanner, setShowBanner] = useState(false);
    const [isInstalled, setIsInstalled] = useState(false);
    const [guideModalOpen, setGuideModalOpen] = useState(false);
    const [installedSuccess, setInstalledSuccess] = useState(false);
    const [activeTab, setActiveTab] = useState('android');
    const [isAdmin, setIsAdmin] = useState(false);

    useEffect(() => {
        const adminCheck = forceType ? forceType === 'admin' : (typeof window !== 'undefined' && window.location.pathname.startsWith('/admin'));
        setIsAdmin(adminCheck);

        // Auto-detect device for modal tab
        if (typeof navigator !== 'undefined') {
            const ua = navigator.userAgent || '';
            if (/iPhone|iPad|iPod/.test(ua)) {
                setActiveTab('ios');
            } else if (/Android/.test(ua)) {
                setActiveTab('android');
            } else {
                setActiveTab('desktop');
            }
        }

        // Check if already running in standalone PWA mode
        const checkStandalone = () => {
            if (typeof window === 'undefined') return false;
            const isPwa = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            setIsInstalled(isPwa);
            return isPwa;
        };

        if (checkStandalone()) return;

        // Check if recently dismissed for this specific area
        const storageKey = adminCheck ? 'synemax_pwa_dismissed_admin' : 'synemax_pwa_dismissed_public';
        const dismissedAt = localStorage.getItem(storageKey);
        const now = Date.now();
        const oneDay = 24 * 60 * 60 * 1000;

        const handlePromptReady = () => {
            if (!checkStandalone() && (!dismissedAt || now - Number(dismissedAt) >= oneDay)) {
                setShowBanner(true);
            }
        };

        const handleAppInstalled = () => {
            setIsInstalled(true);
            setShowBanner(false);
            setInstalledSuccess(true);
        };

        const handleManualOpen = (event) => {
            const reqType = event?.detail?.type;
            if (reqType && ((reqType === 'admin') !== adminCheck)) {
                return;
            }
            setShowBanner(true);
        };

        window.addEventListener('synemax-pwa-prompt-ready', handlePromptReady);
        window.addEventListener('appinstalled', handleAppInstalled);
        window.addEventListener('synemax-open-pwa-install', handleManualOpen);

        // Check if prompt is already available in window
        const currentPrompt = adminCheck ? window.__synemax_pwa_prompt_admin : window.__synemax_pwa_prompt_public;
        if (currentPrompt && (!dismissedAt || now - Number(dismissedAt) >= oneDay)) {
            setShowBanner(true);
        } else {
            // Auto offer banner after short delay if not dismissed
            const timer = setTimeout(() => {
                if (!checkStandalone() && (!dismissedAt || now - Number(dismissedAt) >= oneDay)) {
                    setShowBanner(true);
                }
            }, 2000);

            return () => {
                window.removeEventListener('synemax-pwa-prompt-ready', handlePromptReady);
                window.removeEventListener('appinstalled', handleAppInstalled);
                window.removeEventListener('synemax-open-pwa-install', handleManualOpen);
                clearTimeout(timer);
            };
        }

        return () => {
            window.removeEventListener('synemax-pwa-prompt-ready', handlePromptReady);
            window.removeEventListener('appinstalled', handleAppInstalled);
            window.removeEventListener('synemax-open-pwa-install', handleManualOpen);
        };
    }, [forceType]);

    const handleInstall = async () => {
        const prompt = isAdmin ? window.__synemax_pwa_prompt_admin : window.__synemax_pwa_prompt_public;

        if (prompt) {
            try {
                prompt.prompt();
                const choice = await prompt.userChoice;
                if (choice.outcome === 'accepted') {
                    setShowBanner(false);
                    setIsInstalled(true);
                    setInstalledSuccess(true);
                    if (isAdmin) {
                        window.__synemax_pwa_prompt_admin = null;
                    } else {
                        window.__synemax_pwa_prompt_public = null;
                    }
                }
            } catch (err) {
                // If browser failed prompt, fallback to step-by-step guide
                setGuideModalOpen(true);
            }
        } else {
            // If native prompt unavailable (e.g. iOS, or Chrome required manual menu), open guide modal
            setGuideModalOpen(true);
        }
    };

    const handleDismiss = () => {
        setShowBanner(false);
        const storageKey = isAdmin ? 'synemax_pwa_dismissed_admin' : 'synemax_pwa_dismissed_public';
        localStorage.setItem(storageKey, String(Date.now()));
    };

    if (isInstalled && !installedSuccess) return null;

    const appTitle = isAdmin ? 'SynemaX Admin' : 'SynemaX Cinema';
    const appSubtitle = isAdmin ? 'Panel Pengelola Bioskop' : 'Aplikasi Resmi Bioskop';
    const appBadge = isAdmin ? 'ADMIN' : 'BIOSKOP';
    const appDesc = isAdmin
        ? 'Pasang di layar HP untuk akses cepat satu ketukan ke loket kasir, scan tiket, dan kelola jadwal studio.'
        : 'Pasang di layar HP untuk pesan tiket film lebih cepat, cek jadwal bioskop real-time, dan akses tiket instan!';
    const appIcon = isAdmin ? '/icon-admin-192.png?v=5' : '/icon-192.png?v=5';

    return (
        <>
            {/* Universal Install Guidance Modal */}
            <Modal
                show={guideModalOpen}
                onClose={() => setGuideModalOpen(false)}
                title={`Cara Pasang ${appTitle} di HP / Komputer`}
                maxWidth="md"
            >
                <div className="p-4 sm:p-5 space-y-4 text-xs sm:text-sm text-text">
                    {/* App Preview Card */}
                    <div className="flex items-center gap-3 p-3 rounded-2xl border border-amber-500/30 bg-amber-500/5 dark:bg-amber-500/10">
                        <div className="relative shrink-0">
                            <img
                                src={appIcon}
                                alt={appTitle}
                                className="size-12 rounded-xl border border-amber-500/40 shadow-xs object-contain bg-[#07090E]"
                            />
                            <span className="absolute -top-1 -right-1 flex size-3.5 items-center justify-center rounded-full bg-amber-500 text-slate-950 font-black text-[9px]">
                                <Sparkles className="size-2" />
                            </span>
                        </div>
                        <div className="min-w-0 flex-1">
                            <div className="flex items-center gap-1.5">
                                <h4 className="font-black text-text text-sm truncate">{appTitle}</h4>
                                <span className="px-1.5 py-0.5 rounded text-[9px] font-black tracking-wider uppercase bg-amber-500/20 text-amber-500 border border-amber-500/30">
                                    {appBadge}
                                </span>
                            </div>
                            <p className="text-[11px] text-text-muted mt-0.5">{appSubtitle}</p>
                        </div>
                    </div>

                    {/* Platform Selector Tabs */}
                    <div className="flex items-center rounded-xl bg-surface-2 p-1 border border-border">
                        <button
                            type="button"
                            onClick={() => setActiveTab('android')}
                            className={`flex-1 flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-bold transition ${
                                activeTab === 'android'
                                    ? 'bg-amber-500 text-slate-950 shadow-xs'
                                    : 'text-text-muted hover:text-text'
                            }`}
                        >
                            <Smartphone className="size-3.5" />
                            <span>Android</span>
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('ios')}
                            className={`flex-1 flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-bold transition ${
                                activeTab === 'ios'
                                    ? 'bg-amber-500 text-slate-950 shadow-xs'
                                    : 'text-text-muted hover:text-text'
                            }`}
                        >
                            <Share2 className="size-3.5" />
                            <span>iPhone / iPad</span>
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('desktop')}
                            className={`flex-1 flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-bold transition ${
                                activeTab === 'desktop'
                                    ? 'bg-amber-500 text-slate-950 shadow-xs'
                                    : 'text-text-muted hover:text-text'
                            }`}
                        >
                            <Laptop className="size-3.5" />
                            <span>PC / Laptop</span>
                        </button>
                    </div>

                    {/* Android Instructions */}
                    {activeTab === 'android' && (
                        <div className="space-y-2.5 pt-1">
                            <div className="flex items-start gap-3 p-3 rounded-xl border border-border bg-surface-2">
                                <div className="size-6 rounded-lg bg-amber-500/15 text-amber-500 flex items-center justify-center shrink-0 font-black text-xs">
                                    1
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="font-bold flex items-center gap-1.5">
                                        <span>Ketuk menu titik tiga</span>
                                        <MoreVertical className="size-3.5 text-amber-500" />
                                    </p>
                                    <p className="text-text-muted text-xs mt-0.5">
                                        Buka menu browser Chrome di pojok kanan atas layar Anda.
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start gap-3 p-3 rounded-xl border border-border bg-surface-2">
                                <div className="size-6 rounded-lg bg-amber-500/15 text-amber-500 flex items-center justify-center shrink-0 font-black text-xs">
                                    2
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="font-bold">Pilih "Install Aplikasi" atau "Tambahkan ke Layar Utama"</p>
                                    <p className="text-text-muted text-xs mt-0.5">
                                        Cari opsi <span className="font-semibold text-text">Install app</span> atau <span className="font-semibold text-text">Add to Home Screen</span>.
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start gap-3 p-3 rounded-xl border border-border bg-surface-2">
                                <div className="size-6 rounded-lg bg-amber-500/15 text-amber-500 flex items-center justify-center shrink-0 font-black text-xs">
                                    3
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="font-bold">Ketuk "Install" / "Tambah"</p>
                                    <p className="text-text-muted text-xs mt-0.5">
                                        Ikon resmi {appTitle} akan langsung ditambahkan ke layar beranda HP Anda layaknya aplikasi asli.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* iOS Instructions */}
                    {activeTab === 'ios' && (
                        <div className="space-y-2.5 pt-1">
                            <div className="flex items-start gap-3 p-3 rounded-xl border border-border bg-surface-2">
                                <div className="size-6 rounded-lg bg-amber-500/15 text-amber-500 flex items-center justify-center shrink-0 font-black text-xs">
                                    1
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="font-bold flex items-center gap-1.5">
                                        <span>Ketuk tombol Bagikan (Share)</span>
                                        <Share2 className="size-3.5 text-amber-500" />
                                    </p>
                                    <p className="text-text-muted text-xs mt-0.5">
                                        Ikon kotak panah ke atas di bilah navigasi bawah Safari.
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start gap-3 p-3 rounded-xl border border-border bg-surface-2">
                                <div className="size-6 rounded-lg bg-amber-500/15 text-amber-500 flex items-center justify-center shrink-0 font-black text-xs">
                                    2
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="font-bold flex items-center gap-1.5">
                                        <span>Pilih "Tambahkan ke Layar Utama"</span>
                                        <PlusSquare className="size-3.5 text-amber-500" />
                                    </p>
                                    <p className="text-text-muted text-xs mt-0.5">
                                        Gulir ke bawah dan ketuk <span className="font-semibold text-text">Add to Home Screen</span>.
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start gap-3 p-3 rounded-xl border border-border bg-surface-2">
                                <div className="size-6 rounded-lg bg-amber-500/15 text-amber-500 flex items-center justify-center shrink-0 font-black text-xs">
                                    3
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="font-bold">Ketuk "Tambah" (Add)</p>
                                    <p className="text-text-muted text-xs mt-0.5">
                                        Ketuk tombol Tambah di pojok kanan atas untuk menyelesaikan.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Desktop Instructions */}
                    {activeTab === 'desktop' && (
                        <div className="space-y-2.5 pt-1">
                            <div className="flex items-start gap-3 p-3 rounded-xl border border-border bg-surface-2">
                                <div className="size-6 rounded-lg bg-amber-500/15 text-amber-500 flex items-center justify-center shrink-0 font-black text-xs">
                                    1
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="font-bold">Klik ikon Install di bilah alamat browser</p>
                                    <p className="text-text-muted text-xs mt-0.5">
                                        Di browser Chrome atau Edge, klik ikon komputer/download di sebelah kanan kolom URL.
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start gap-3 p-3 rounded-xl border border-border bg-surface-2">
                                <div className="size-6 rounded-lg bg-amber-500/15 text-amber-500 flex items-center justify-center shrink-0 font-black text-xs">
                                    2
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="font-bold">Atau lewat Menu Titik Tiga</p>
                                    <p className="text-text-muted text-xs mt-0.5">
                                        Klik menu browser di kanan atas -&gt; Simpan dan bagikan -&gt; Install {appTitle}.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    <div className="pt-2 flex justify-end">
                        <button
                            type="button"
                            onClick={() => {
                                setGuideModalOpen(false);
                                setShowBanner(false);
                            }}
                            className="btn-gold-cinema px-5 py-2.5 rounded-xl font-bold text-xs"
                        >
                            Saya Mengerti
                        </button>
                    </div>
                </div>
            </Modal>

            {/* Install Success Toast / Alert */}
            {installedSuccess && (
                <div
                    role="alert"
                    className="fixed bottom-20 lg:bottom-5 left-3 right-3 sm:left-auto sm:right-6 sm:w-[380px] z-50 animate-in slide-in-from-bottom-5 duration-300"
                >
                    <div className="rounded-2xl border border-emerald-500/60 bg-surface/98 dark:bg-[#071311]/98 backdrop-blur-md p-3.5 shadow-2xl flex items-center gap-3">
                        <div className="size-10 rounded-xl bg-emerald-500/20 text-emerald-500 flex items-center justify-center shrink-0">
                            <CheckCircle2 className="size-5" />
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="text-xs font-bold text-text">Pemasangan Berhasil!</p>
                            <p className="text-[11px] text-text-muted">{appTitle} telah dipasang di layar utama Anda.</p>
                        </div>
                        <button
                            type="button"
                            onClick={() => setInstalledSuccess(false)}
                            className="p-1 rounded-lg text-text-muted hover:text-text"
                        >
                            <X className="size-4" />
                        </button>
                    </div>
                </div>
            )}

            {/* Floating Install Offer Banner / Notification */}
            {showBanner && !installedSuccess && (
                <aside
                    aria-label={`Tawaran Pasang ${appTitle}`}
                    className="fixed bottom-20 lg:bottom-5 left-3 right-3 sm:left-auto sm:right-6 sm:w-[400px] z-50 animate-in slide-in-from-bottom-5 duration-300"
                >
                    <div className="rounded-2xl border-2 border-amber-500/60 bg-surface/98 dark:bg-[#0E131F]/98 backdrop-blur-md p-3.5 sm:p-4 shadow-2xl flex items-start gap-3 relative ring-4 ring-amber-500/10">
                        {/* Distinct App Icon */}
                        <div className="relative shrink-0">
                            <img
                                src={appIcon}
                                alt={`${appTitle} Icon`}
                                className="size-12 rounded-xl border border-amber-500/40 shadow-xs object-contain bg-[#07090E]"
                            />
                            <span className="absolute -top-1 -right-1 flex size-3.5 items-center justify-center rounded-full bg-amber-500 text-slate-950 font-black text-[9px]">
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
                                    <span className="px-1.5 py-0.2 rounded text-[9px] font-black uppercase bg-amber-500/20 text-amber-500 border border-amber-500/30">
                                        {appBadge}
                                    </span>
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
