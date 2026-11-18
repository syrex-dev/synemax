import { useState, useEffect } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import BrandLogo from '../Components/BrandLogo';
import ThemeToggle from '../Components/ThemeToggle';
import FlashMessage from '../Components/FlashMessage';
import PwaInstallBanner from '../Components/PwaInstallBanner';
import {
    LayoutDashboard,
    ShoppingBag,
    QrCode,
    Users,
    Calendar,
    Film,
    Sparkles,
    Tag,
    Newspaper,
    Coffee,
    FileText,
    Settings,
    LogOut,
    Menu,
    X,
    ChevronDown,
    CheckSquare,
    ExternalLink,
    Smartphone,
} from 'lucide-react';

export default function AdminLayout({ children, title, isFormPage = false }) {
    const { url } = usePage();
    const { auth } = usePage().props;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [userDropdownOpen, setUserDropdownOpen] = useState(false);

    useEffect(() => {
        if (title) {
            document.title = `${title} | SynemaX Admin`;
        }
    }, [title]);

    const navSections = [
        {
            title: 'OPERASIONAL',
            items: [
                { name: 'Hari Ini', href: '/admin', icon: LayoutDashboard, exact: true },
                { name: 'Loket (Pesan di Tempat)', href: '/admin/booking/pesan-di-tempat', icon: ShoppingBag },
                { name: 'Scan Tiket', href: '/admin/scan', icon: QrCode },
                { name: 'Scan Member', href: '/admin/members/scan', icon: Users },
                { name: 'Booking', href: '/admin/booking', icon: FileText, exact: true },
                { name: 'Member', href: '/admin/members', icon: Users },
                { name: 'Jadwal', href: '/admin/jadwal', icon: Calendar },
                { name: 'Nobar', href: '/admin/nobar', icon: Sparkles },
            ],
        },
        {
            title: 'KONTEN',
            items: [
                { name: 'Film', href: '/admin/films', icon: Film },
                { name: 'Banner Hero', href: '/admin/banners', icon: Sparkles },
                { name: 'Promo', href: '/admin/promos', icon: Tag },
                { name: 'Kabar', href: '/admin/berita', icon: Newspaper },
                { name: 'Jajanan', href: '/admin/jajanan', icon: Coffee, exact: true },
            ],
        },
        {
            title: 'LAINNYA',
            items: [
                { name: 'Daftar Siapkan', href: '/admin/jajanan/siapkan', icon: CheckSquare },
                { name: 'Laporan', href: '/admin/laporan', icon: FileText },
                { name: 'Pengaturan', href: '/admin/pengaturan', icon: Settings },
            ],
        },
    ];

    const isLinkActive = (item) => {
        if (item.exact) {
            return url === item.href;
        }
        return url.startsWith(item.href);
    };

    const handleLogout = (e) => {
        e.preventDefault();
        router.post('/logout');
    };

    return (
        <div className="min-h-screen bg-bg font-admin text-text antialiased transition-colors duration-200" data-admin-panel>
            <FlashMessage />

            {/* Sticky Full-Width Header */}
            <header className="sticky top-0 z-30 border-b border-border bg-surface lg:bg-surface/95 lg:backdrop-blur pt-[env(safe-area-inset-top)]">
                <div className="mx-auto flex h-14 lg:h-16 max-w-screen-2xl items-center justify-between gap-2 px-3 sm:px-6">
                    {/* Left: Mobile Hamburger + Logo + Breadcrumb */}
                    <div className="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
                        <button
                            type="button"
                            onClick={() => setSidebarOpen(!sidebarOpen)}
                            className="inline-flex size-11 items-center justify-center rounded-xl border border-slate-300 dark:border-white/15 bg-surface text-text hover:bg-surface-2 focus-visible:outline-2 focus-visible:outline-amber-500 lg:hidden shrink-0"
                            aria-label="Buka menu navigasi"
                        >
                            <Menu className="size-5" />
                        </button>

                        <Link href="/admin" className="flex items-center gap-2 min-w-0 shrink">
                            <BrandLogo size="sm" />
                        </Link>

                        {/* Breadcrumb (Desktop Only) */}
                        <div className="hidden md:flex items-center text-xs text-text-muted min-w-0 truncate">
                            <span className="mx-1.5 text-border-input font-semibold">/</span>
                            <span className="font-medium">Pengelola</span>
                            <span className="mx-1.5 text-border-input font-semibold">/</span>
                            <span className="font-bold text-text truncate">{title || 'Hari Ini'}</span>
                        </div>
                    </div>

                    {/* Right: Theme Toggle + Account Dropdown */}
                    <div className="flex items-center gap-2 shrink-0">
                        <ThemeToggle variant="responsive" />

                        <div className="relative">
                            <button
                                type="button"
                                onClick={() => setUserDropdownOpen(!userDropdownOpen)}
                                className="inline-flex size-11 sm:w-auto sm:px-3 sm:gap-2 items-center justify-center rounded-xl border border-slate-300 dark:border-white/15 bg-surface text-text hover:border-amber-500 transition shadow-xs focus-visible:outline-2 focus-visible:outline-amber-500"
                                aria-label="Menu Akun Admin"
                            >
                                <span className="flex size-7 items-center justify-center rounded-lg bg-amber-500 text-slate-950 font-black text-xs">
                                    {auth?.user?.name ? auth.user.name.charAt(0).toUpperCase() : 'A'}
                                </span>
                                <span className="hidden sm:inline font-bold text-xs text-text max-w-[120px] truncate">
                                    {auth?.user?.name || 'Admin'}
                                </span>
                                <ChevronDown className="hidden sm:inline size-3.5 text-text-muted" />
                            </button>

                            {userDropdownOpen && (
                                <div
                                    className="absolute right-0 top-full mt-2 w-64 rounded-2xl border border-border bg-surface p-3 shadow-2xl z-50 space-y-2 text-xs animate-in fade-in zoom-in-95"
                                    onClick={() => setUserDropdownOpen(false)}
                                >
                                    <div className="px-2 py-1.5 border-b border-border pb-2">
                                        <p className="font-bold text-text truncate">{auth?.user?.name || 'Administrator'}</p>
                                        <p className="text-[11px] text-text-muted truncate">{auth?.user?.email || 'admin@synemax.test'}</p>
                                    </div>

                                    <div className="border-t border-border pt-1 space-y-1">
                                        <a
                                            href="/"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="flex min-h-[40px] items-center gap-2.5 rounded-xl px-2.5 py-1.5 font-semibold text-text hover:bg-surface-2 transition"
                                        >
                                            <ExternalLink className="size-4 text-text-muted" />
                                            <span>Lihat Situs Publik</span>
                                        </a>

                                        <Link
                                            href="/admin/pengaturan"
                                            className="flex min-h-[40px] items-center gap-2.5 rounded-xl px-2.5 py-1.5 font-semibold text-text hover:bg-surface-2 transition"
                                        >
                                            <Settings className="size-4 text-text-muted" />
                                            <span>Pengaturan Bioskop</span>
                                        </Link>

                                        <form onSubmit={handleLogout} className="pt-1 border-t border-border">
                                            <button
                                                type="submit"
                                                className="flex min-h-[40px] w-full items-center gap-2.5 rounded-xl px-2.5 py-1.5 font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition text-left"
                                            >
                                                <LogOut className="size-4 text-rose-600" />
                                                <span>Keluar (Logout)</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </header>

            {/* Mobile Drawer (Only shown when opened) */}
            {sidebarOpen && (
                <div className="fixed inset-0 z-50 lg:hidden">
                    <div
                        className="fixed inset-0 bg-black/60 transition-opacity"
                        onClick={() => setSidebarOpen(false)}
                    />
                    <div className="relative flex w-[min(86vw,320px)] flex-col h-[100dvh] bg-surface border-r border-border shadow-2xl z-50 overflow-y-auto overscroll-contain animate-in slide-in-from-left duration-200">
                        <div className="flex items-center justify-between p-4 border-b border-border shrink-0">
                            <BrandLogo size="sm" />
                            <button
                                type="button"
                                onClick={() => setSidebarOpen(false)}
                                className="size-11 inline-flex items-center justify-center rounded-xl border border-border text-text hover:bg-surface-2"
                                aria-label="Tutup menu navigasi"
                            >
                                <X className="size-5" />
                            </button>
                        </div>

                        <div className="flex-1 overflow-y-auto p-4 space-y-6">
                            {navSections.map((section) => (
                                <div key={section.title}>
                                    <h4 className="px-3 text-[11px] font-bold uppercase tracking-[0.15em] text-text-muted mb-2">
                                        {section.title}
                                    </h4>
                                    <div className="space-y-1">
                                        {section.items.map((item) => {
                                            const active = isLinkActive(item);
                                            const Icon = item.icon;
                                            return (
                                                <Link
                                                    key={item.href}
                                                    href={item.href}
                                                    onClick={() => setSidebarOpen(false)}
                                                    className={`flex min-h-[44px] items-center gap-3 px-3 py-2 rounded-xl text-sm font-semibold transition ${
                                                        active
                                                            ? 'bg-amber-500/15 text-amber-900 dark:text-amber-400 font-bold border border-amber-500/30'
                                                            : 'text-text-muted hover:text-text hover:bg-surface-2'
                                                    }`}
                                                >
                                                    <Icon className={`size-4 shrink-0 ${active ? 'text-amber-500' : 'text-text-muted'}`} />
                                                    <span>{item.name}</span>
                                                </Link>
                                            );
                                        })}
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="border-t border-border p-4 bg-surface-2/60 space-y-3 shrink-0">
                            <div className="flex items-center gap-3">
                                <div className="flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-500/20 text-amber-500 font-bold text-xs">
                                    {auth?.user?.name ? auth.user.name.charAt(0).toUpperCase() : 'A'}
                                </div>
                                <div className="min-w-0 flex-1">
                                    <p className="text-xs font-bold text-text truncate">{auth?.user?.name || 'Admin Bioskop'}</p>
                                    <p className="text-[11px] text-text-muted truncate">{auth?.user?.email || 'admin@synemax.test'}</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                onClick={() => {
                                    setSidebarOpen(false);
                                    window.dispatchEvent(new CustomEvent('synemax-open-pwa-install', { detail: { type: 'admin' } }));
                                }}
                                className="flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl border border-amber-500/40 bg-amber-500/10 px-3 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 transition"
                            >
                                <Smartphone className="size-4 text-amber-500" />
                                <span>Install Aplikasi Admin</span>
                            </button>
                            <a
                                href="/"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-border bg-surface px-3 text-xs font-bold text-text hover:bg-surface-2 transition"
                            >
                                <ExternalLink className="size-4 text-text-muted" />
                                <span>Lihat Situs Publik</span>
                            </a>
                            <button
                                type="button"
                                onClick={handleLogout}
                                className="flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl border border-rose-500/40 bg-rose-500/10 px-3 text-xs font-bold text-rose-600 hover:bg-rose-500/20 transition"
                            >
                                <LogOut className="size-4" />
                                <span>Keluar</span>
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Desktop Layout Container */}
            <div className="mx-auto flex max-w-screen-2xl">
                {/* Desktop Sticky Sidebar */}
                <aside className="sticky top-16 hidden h-[calc(100vh-4rem)] w-64 shrink-0 overflow-y-auto border-r border-border p-4 lg:block">
                    <div className="space-y-6">
                        {navSections.map((section) => (
                            <div key={section.title}>
                                <h4 className="px-3 text-[11px] font-bold uppercase tracking-[0.15em] text-text-muted mb-2">
                                    {section.title}
                                </h4>
                                <div className="space-y-1">
                                    {section.items.map((item) => {
                                        const active = isLinkActive(item);
                                        const Icon = item.icon;
                                        return (
                                            <Link
                                                key={item.href}
                                                href={item.href}
                                                className={`flex min-h-[44px] items-center gap-3 px-3 py-2 rounded-xl text-sm font-semibold transition ${
                                                    active
                                                        ? 'bg-amber-100 text-amber-900 font-bold border-l-4 border-amber-500 rounded-r-xl rounded-l-none pl-2.5 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30 dark:rounded-xl dark:pl-3'
                                                        : 'text-text-muted hover:text-text hover:bg-surface-2'
                                                }`}
                                            >
                                                <Icon className={`size-4 shrink-0 ${active ? 'text-amber-500' : 'text-text-muted'}`} />
                                                <span>{item.name}</span>
                                            </Link>
                                        );
                                    })}
                                </div>
                            </div>
                        ))}
                    </div>
                </aside>

                {/* Main Content Column */}
                <div className="min-w-0 flex-1">
                    <main className="mx-auto min-w-0 max-w-screen-xl px-4 py-6 sm:px-6 lg:px-8 pb-24 lg:pb-8">
                        {children}
                    </main>
                </div>
            </div>

            {/* Mobile Bottom Navigation Bar (< lg, always visible on mobile) */}
            <nav
                aria-label="Navigasi bawah mobile"
                className="fixed inset-x-0 bottom-0 z-40 block lg:hidden border-t border-border bg-surface/95 backdrop-blur pb-[env(safe-area-inset-bottom)] shadow-xl"
            >
                <div className="grid grid-cols-5 h-16 items-center px-1">
                    <Link
                        href="/admin"
                        className={`flex flex-col items-center justify-center gap-1 text-center py-1.5 min-h-[44px] ${
                            url === '/admin'
                                ? 'text-amber-800 dark:text-amber-400 font-bold border-t-2 border-amber-500 dark:border-amber-400 -mt-[1px]'
                                : 'text-text-muted hover:text-text border-t-2 border-transparent'
                        }`}
                    >
                        <LayoutDashboard className="size-5" />
                        <span className="text-[10px] leading-none">Hari Ini</span>
                    </Link>

                    <Link
                        href="/admin/booking"
                        className={`flex flex-col items-center justify-center gap-1 text-center py-1.5 min-h-[44px] ${
                            url.startsWith('/admin/booking') && !url.includes('/pesan-di-tempat')
                                ? 'text-amber-800 dark:text-amber-400 font-bold border-t-2 border-amber-500 dark:border-amber-400 -mt-[1px]'
                                : 'text-text-muted hover:text-text border-t-2 border-transparent'
                        }`}
                    >
                        <FileText className="size-5" />
                        <span className="text-[10px] leading-none">Booking</span>
                    </Link>

                    <Link
                        href="/admin/jadwal"
                        className={`flex flex-col items-center justify-center gap-1 text-center py-1.5 min-h-[44px] ${
                            url.startsWith('/admin/jadwal')
                                ? 'text-amber-800 dark:text-amber-400 font-bold border-t-2 border-amber-500 dark:border-amber-400 -mt-[1px]'
                                : 'text-text-muted hover:text-text border-t-2 border-transparent'
                        }`}
                    >
                        <Calendar className="size-5" />
                        <span className="text-[10px] leading-none">Jadwal</span>
                    </Link>

                    <Link
                        href="/admin/nobar"
                        className={`flex flex-col items-center justify-center gap-1 text-center py-1.5 min-h-[44px] ${
                            url.startsWith('/admin/nobar')
                                ? 'text-amber-800 dark:text-amber-400 font-bold border-t-2 border-amber-500 dark:border-amber-400 -mt-[1px]'
                                : 'text-text-muted hover:text-text border-t-2 border-transparent'
                        }`}
                    >
                        <Sparkles className="size-5" />
                        <span className="text-[10px] leading-none">Nobar</span>
                    </Link>

                    <button
                        type="button"
                        onClick={() => setSidebarOpen(true)}
                        className="flex flex-col items-center justify-center gap-1 text-center py-1.5 min-h-[44px] text-text-muted hover:text-text border-t-2 border-transparent"
                    >
                        <Menu className="size-5" />
                        <span className="text-[10px] leading-none">Menu</span>
                    </button>
                </div>
            </nav>

            {/* PWA Mobile Install Banner */}
            <PwaInstallBanner forceType="admin" />
        </div>
    );
}
