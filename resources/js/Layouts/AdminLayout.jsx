import { useState, useEffect } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import BrandLogo from '../Components/BrandLogo';
import ThemeToggle from '../Components/ThemeToggle';
import FlashMessage from '../Components/FlashMessage';
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
    User,
    CheckSquare,
    ExternalLink,
} from 'lucide-react';

export default function AdminLayout({ children, title }) {
    const { url } = usePage();
    const { auth, cinemaSettings } = usePage().props;
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
        <div className="min-h-screen flex bg-bg font-sans text-text antialiased transition-colors duration-200">
            <FlashMessage />

            {/* Mobile Sidebar Backdrop */}
            {sidebarOpen && (
                <div
                    className="fixed inset-0 z-40 bg-black/60 backdrop-blur-xs lg:hidden transition-opacity"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            {/* Sidebar Desktop & Mobile Drawer */}
            <aside
                className={`fixed lg:sticky top-0 z-50 h-screen w-64 border-r border-border/80 dark:border-white/10 bg-surface dark:bg-[#07090E] flex flex-col shrink-0 transition-transform duration-200 lg:translate-x-0 ${
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                {/* Brand Header */}
                <div className="h-16 flex items-center justify-between px-5 border-b border-border/60 dark:border-white/10 shrink-0">
                    <Link href="/admin" className="flex items-center gap-2">
                        <BrandLogo size="sm" />
                    </Link>
                    <button
                        type="button"
                        onClick={() => setSidebarOpen(false)}
                        className="lg:hidden p-1.5 rounded-lg border border-border text-text-muted hover:text-text"
                    >
                        <X className="size-5" />
                    </button>
                </div>

                {/* Nav Links */}
                <div className="flex-1 overflow-y-auto px-3.5 py-4 space-y-6 custom-scrollbar">
                    {navSections.map((section) => (
                        <div key={section.title}>
                            <h4 className="px-3 text-[10px] font-black tracking-wider uppercase text-text-muted/80 mb-2">
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
                                            className={`flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition select-none ${
                                                active
                                                    ? 'bg-amber-500/15 text-amber-900 dark:text-amber-400 border border-amber-500/30'
                                                    : 'text-text-muted hover:text-text hover:bg-surface-raised dark:hover:bg-white/5'
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

                {/* Footer User & Shortcut */}
                <div className="p-3 border-t border-border/60 dark:border-white/10 shrink-0">
                    <a
                        href="/"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold text-text-muted hover:text-text hover:bg-surface-raised transition mb-1"
                    >
                        <span>Lihat Website</span>
                        <ExternalLink className="size-3.5" />
                    </a>
                </div>
            </aside>

            {/* Right Container */}
            <div className="flex-1 flex flex-col min-w-0">
                {/* Topbar */}
                <header className="sticky top-0 z-30 h-16 border-b border-border/80 dark:border-white/10 bg-surface/85 dark:bg-[#07090E]/85 backdrop-blur-md px-4 sm:px-6 flex items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            onClick={() => setSidebarOpen(true)}
                            className="lg:hidden p-2 rounded-xl border border-border text-text hover:bg-surface-2"
                            aria-label="Buka menu"
                        >
                            <Menu className="size-5" />
                        </button>
                        <h2 className="text-base sm:text-lg font-bold text-text truncate">
                            {title || 'Panel Pengelola'}
                        </h2>
                    </div>

                    <div className="flex items-center gap-3">
                        {/* Theme Switcher */}
                        <ThemeToggle variant="responsive" />

                        {/* User Menu Dropdown */}
                        <div className="relative">
                            <button
                                type="button"
                                onClick={() => setUserDropdownOpen(!userDropdownOpen)}
                                className="flex items-center gap-2.5 p-1.5 sm:px-3 sm:py-1.5 rounded-xl border border-border hover:bg-surface-raised transition focus:outline-none"
                            >
                                <span className="flex size-7 items-center justify-center rounded-lg bg-amber-500 text-slate-950 font-black text-xs">
                                    {auth?.user?.name ? auth.user.name.charAt(0).toUpperCase() : 'A'}
                                </span>
                                <span className="hidden sm:inline text-xs font-bold text-text">
                                    {auth?.user?.name || 'Admin'}
                                </span>
                                <ChevronDown className="size-3.5 text-text-muted hidden sm:inline" />
                            </button>

                            {userDropdownOpen && (
                                <div
                                    className="absolute right-0 mt-2 w-48 rounded-2xl border border-border/80 dark:border-white/10 bg-surface dark:bg-[#0E131F] p-1.5 shadow-xl z-50 animate-in fade-in zoom-in-95"
                                    onClick={() => setUserDropdownOpen(false)}
                                >
                                    <div className="px-3 py-2 border-b border-border/60 dark:border-white/10 mb-1">
                                        <p className="text-xs font-bold text-text truncate">{auth?.user?.name}</p>
                                        <p className="text-[11px] text-text-muted truncate">{auth?.user?.email}</p>
                                    </div>
                                    <Link
                                        href="/profile"
                                        className="flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold text-text hover:bg-surface-raised transition"
                                    >
                                        <User className="size-4 text-text-muted" />
                                        <span>Profil Saya</span>
                                    </Link>
                                    <button
                                        type="button"
                                        onClick={handleLogout}
                                        className="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 transition text-left"
                                    >
                                        <LogOut className="size-4" />
                                        <span>Keluar (Logout)</span>
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>
                </header>

                {/* Page Content */}
                <main className="flex-1 p-4 sm:p-6 lg:p-8">
                    {children}
                </main>
            </div>
        </div>
    );
}

