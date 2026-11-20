import { useState, useEffect, useRef } from 'react';
import { Link, usePage, Head, router } from '@inertiajs/react';
import BrandLogo from '../Components/BrandLogo';
import ThemeToggle from '../Components/ThemeToggle';
import FlashMessage from '../Components/FlashMessage';
import PwaInstallBanner from '../Components/PwaInstallBanner';
import {
    Home,
    Calendar,
    Film,
    Ticket,
    Menu,
    X,
    Search,
    Loader2,
    MessageCircle,
    MapPin,
    Phone,
    Mail,
    ExternalLink,
    ChevronRight,
    Sun,
    Moon,
    Monitor,
    Download,
} from 'lucide-react';

export default function PublicLayout({ children, title, isActionPage = false }) {
    const { url } = usePage();
    const { cinemaSettings } = usePage().props;
    const [menuSheetOpen, setMenuSheetOpen] = useState(false);
    const [isWaBubbleDismissed, setIsWaBubbleDismissed] = useState(false);
    const [isScrolled, setIsScrolled] = useState(false);

    // CGV-style navbar search states
    const [isSearchOpen, setIsSearchOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [searchTotalCount, setSearchTotalCount] = useState(0);
    const [isSearching, setIsSearching] = useState(false);
    const searchInputRef = useRef(null);

    const siteName = cinemaSettings?.siteName || 'SynemaX';
    const pageTitle = title || 'Beranda';
    const fullTitle = pageTitle.startsWith(siteName) ? pageTitle : `${siteName} | ${pageTitle}`;

    useEffect(() => {
        document.title = fullTitle;
    }, [fullTitle]);

    // Track scroll position for dynamic elegant navbar elevation
    useEffect(() => {
        const handleScroll = () => {
            setIsScrolled(window.scrollY > 8);
        };
        handleScroll();
        window.addEventListener('scroll', handleScroll, { passive: true });
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    // Auto-focus search input when search opens
    useEffect(() => {
        if (isSearchOpen) {
            setTimeout(() => {
                searchInputRef.current?.focus();
            }, 60);
        }
    }, [isSearchOpen]);

    // Close search popup on route change
    useEffect(() => {
        setIsSearchOpen(false);
    }, [url]);

    // Close search on Escape key
    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'Escape' && isSearchOpen) {
                setIsSearchOpen(false);
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [isSearchOpen]);

    // Debounced search query against backend catalog JSON endpoint
    useEffect(() => {
        const trimmed = searchQuery.trim();
        if (!trimmed) {
            setSearchResults([]);
            setSearchTotalCount(0);
            setIsSearching(false);
            return;
        }

        setIsSearching(true);
        const controller = new AbortController();
        const timer = setTimeout(async () => {
            try {
                const res = await fetch(`/film?format=json&limit=12&cari=${encodeURIComponent(trimmed)}`, {
                    signal: controller.signal,
                    headers: { Accept: 'application/json' },
                });
                if (res.ok) {
                    const data = await res.json();
                    setSearchResults(data.films || []);
                    setSearchTotalCount(data.count ?? (data.films ? data.films.length : 0));
                }
            } catch (err) {
                if (err.name !== 'AbortError') {
                    // silently ignore aborts
                }
            } finally {
                setIsSearching(false);
            }
        }, 250);

        return () => {
            clearTimeout(timer);
            controller.abort();
        };
    }, [searchQuery]);

    const handleOpenSearch = () => {
        setIsSearchOpen(true);
    };

    const handleCloseSearch = () => {
        setIsSearchOpen(false);
    };

    const handleFilmClick = (e, film) => {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        setIsSearchOpen(false);
        const targetUrl = `/film/${film.slug || film.id}`;
        router.visit(targetUrl);
    };

    const handleSearchSubmit = (e) => {
        if (e) e.preventDefault();
        const trimmed = searchQuery.trim();
        if (!trimmed) return;
        setIsSearchOpen(false);
        router.visit(`/film?cari=${encodeURIComponent(trimmed)}`);
    };

    const handleSearchKeyDown = (e) => {
        if (e.key === 'Enter') {
            handleSearchSubmit(e);
        }
    };

    useEffect(() => {
        try {
            if (sessionStorage.getItem('synemax_wa_bubble_dismissed') === '1') {
                setIsWaBubbleDismissed(true);
            }
        } catch (e) {}
    }, []);

    const handleDismissWaBubble = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setIsWaBubbleDismissed(true);
        try {
            sessionStorage.setItem('synemax_wa_bubble_dismissed', '1');
        } catch (err) {}
    };

    const allMenuItems = cinemaSettings?.navbarMenu || [
        { key: 'home', label: 'Beranda', short_label: 'Beranda', url: '/', is_visible: true },
        { key: 'schedule', label: 'Jadwal', short_label: 'Jadwal', url: '/jadwal', is_visible: true },
        { key: 'film', label: 'Film', short_label: 'Film', url: '/film', is_visible: true },
        { key: 'nobar', label: 'Nobar', short_label: 'Nobar', url: '/nobar', is_visible: true },
        { key: 'promo', label: 'Promo', short_label: 'Promo', url: '/promo', is_visible: true },
        { key: 'articles', label: 'Kabar', short_label: 'Kabar', url: '/kabar', is_visible: true },
        { key: 'lookup', label: 'Cek booking', short_label: 'Tiket', url: '/tiket', is_visible: true },
    ];

    const isItemActive = (item) => {
        const itemUrl = item.url || '';
        if (itemUrl === '/' && url === '/') return true;
        if (itemUrl !== '/' && url.startsWith(itemUrl)) return true;
        return false;
    };

    const visibleItems = allMenuItems.filter((i) => i.is_visible);
    const bottomBarItems = visibleItems.filter((i) => i.mobile_position === 'bottom_bar' || ['home', 'schedule', 'film', 'lookup', 'booking_lookup', 'tickets', 'tiket'].includes(i.key)).slice(0, 4);
    const menuSheetItems = visibleItems.filter(
        (i) => i.mobile_position === 'menu' || !['home', 'schedule', 'film', 'lookup', 'booking_lookup', 'tickets', 'tiket'].includes(i.key)
    );

    const getIconForNav = (key) => {
        switch (key) {
            case 'home':
                return <Home className="size-5" />;
            case 'schedule':
                return <Calendar className="size-5" />;
            case 'film':
                return <Film className="size-5" />;
            case 'lookup':
            case 'booking_lookup':
            case 'tickets':
            case 'tiket':
                return <Ticket className="size-5" />;
            default:
                return <Film className="size-5" />;
        }
    };

    const waNumber = cinemaSettings?.contactWhatsapp ? cinemaSettings.contactWhatsapp.replace(/\D/g, '') : '';
    const cleanWaNumber = waNumber.startsWith('0') ? '62' + waNumber.slice(1) : waNumber;
    const mapsLink = cinemaSettings?.mapsUrl || (cinemaSettings?.contactAddress ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(cinemaSettings.contactAddress)}` : null);

    const handleSetTheme = (mode) => {
        if (window.synemaXTheme?.setMode) {
            window.synemaXTheme.setMode(mode);
        } else {
            localStorage.setItem('theme', mode);
            if (mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    };

    return (
        <div className="min-h-screen flex flex-col bg-bg font-sans text-text antialiased transition-colors duration-200">
            <Head title={pageTitle} />
            <FlashMessage />

            {/* Header Sticky Navbar adapting to Light and Dark Theme */}
            <header
                className={`sticky top-0 z-40 w-full transition-colors duration-200 bg-surface dark:bg-[#07090E] border-b border-border/80 dark:border-white/10 ${
                    isScrolled ? 'shadow-md' : 'shadow-xs'
                }`}
            >
                <div className="mx-auto flex h-14 md:h-16 max-w-6xl items-center justify-between gap-3 px-4 sm:px-6 relative">
                    {isSearchOpen ? (
                        /* Active CGV-style search input bar */
                        <div className="flex w-full items-center gap-2 sm:gap-3 animate-in fade-in zoom-in-95 duration-200 py-1">
                            <Link
                                href="/"
                                onClick={handleCloseSearch}
                                className="hidden md:inline-flex shrink-0 items-center gap-2 group transition-transform hover:scale-102"
                            >
                                <BrandLogo size="default" />
                            </Link>

                            <form onSubmit={handleSearchSubmit} className="relative flex-1 flex items-center min-w-0">
                                <div className="relative w-full h-10 sm:h-11 flex items-center rounded-full bg-white dark:bg-[#0D121F] border border-amber-500/40 dark:border-amber-400/30 focus-within:border-amber-500 focus-within:ring-4 focus-within:ring-amber-500/15 pl-3 sm:pl-3.5 pr-1 sm:pr-1.5 transition-all shadow-[0_2px_12px_rgba(217,119,6,0.1)]">
                                    <Search className="size-4 sm:size-4.5 text-amber-500 shrink-0 mr-2 sm:mr-2.5 opacity-90" />
                                    <input
                                        ref={searchInputRef}
                                        type="text"
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                        onKeyDown={handleSearchKeyDown}
                                        placeholder="Cari film, genre, tahun rilis..."
                                        className="input-borderless flex-1 h-full p-0 text-xs sm:text-sm text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 font-medium leading-none"
                                        style={{ border: 'none', outline: 'none', boxShadow: 'none', background: 'transparent' }}
                                        aria-label="Cari film"
                                        autoComplete="off"
                                    />
                                    {searchQuery && (
                                        <button
                                            type="button"
                                            onClick={() => setSearchQuery('')}
                                            className="size-6 flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-white rounded-full hover:bg-slate-100 dark:hover:bg-white/10 transition mr-1.5 shrink-0"
                                            title="Hapus teks"
                                        >
                                            <X className="size-3.5" />
                                        </button>
                                    )}
                                    <button
                                        type="submit"
                                        className="h-7 sm:h-8 px-3.5 sm:px-4 rounded-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black text-[11px] sm:text-xs uppercase tracking-wider shadow-sm hover:shadow transition-all active:scale-95 shrink-0 flex items-center justify-center cursor-pointer"
                                    >
                                        Cari
                                    </button>
                                </div>
                            </form>

                            <button
                                type="button"
                                onClick={handleCloseSearch}
                                className="size-9 sm:size-10 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 dark:bg-white/10 dark:hover:bg-white/20 text-slate-600 dark:text-slate-200 hover:text-slate-950 transition-all shrink-0 border border-slate-200/80 dark:border-white/10 shadow-xs cursor-pointer"
                                aria-label="Tutup pencarian"
                                title="Tutup (Esc)"
                            >
                                <X className="size-4.5 sm:size-5" />
                            </button>
                        </div>
                    ) : (
                        /* Normal Navbar State */
                        <>
                            {/* Brand Logo */}
                            <Link href="/" className="inline-flex min-h-11 items-center gap-2 group transition-transform hover:scale-102 shrink-0">
                                <BrandLogo size="default" />
                            </Link>

                            {/* Desktop Navigation */}
                            <nav className="hidden md:flex items-center gap-1.5 text-sm" aria-label="Navigasi desktop">
                                {visibleItems.map((item) => {
                                    const active = isItemActive(item);
                                    const isTicket = ['lookup', 'booking_lookup', 'tickets', 'tiket'].includes(item.key);

                                    if (isTicket) {
                                        return (
                                            <Link
                                                key={item.key || item.url}
                                                href={item.url || '/tiket'}
                                                className={`btn-gold-cinema inline-flex min-h-10 shrink-0 items-center rounded-xl px-3.5 py-1.5 text-xs font-black shadow-xs ml-1 ${active ? 'ring-2 ring-amber-500 ring-offset-2' : ''
                                                    }`}
                                            >
                                                <Ticket className="size-3.5 mr-1.5" />
                                                <span>Tiket</span>
                                            </Link>
                                        );
                                    }

                                    return (
                                        <Link
                                            key={item.key || item.url}
                                            href={item.url}
                                            className={`relative inline-flex min-h-10 shrink-0 items-center px-3 py-1.5 font-bold transition rounded-lg ${active
                                                ? 'text-[#0F172A] font-extrabold after:content-[\'\'] after:absolute after:bottom-0 after:left-2 after:right-2 after:h-[3px] after:bg-[#F59E0B] after:rounded-full dark:after:hidden dark:bg-amber-500/15 dark:text-amber-400 dark:border dark:border-amber-400/40'
                                                : 'text-slate-600 hover:bg-[#F1F5F9] hover:text-[#0F172A] dark:text-text-muted dark:hover:bg-surface-2 dark:hover:text-amber-400'
                                                }`}
                                        >
                                            {item.label}
                                        </Link>
                                    );
                                })}
                            </nav>

                            {/* Action Area: Search button (CGV style) + Theme toggle */}
                            <div className="flex items-center gap-2 shrink-0">
                                <button
                                    type="button"
                                    onClick={handleOpenSearch}
                                    className="flex size-9 sm:size-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 hover:bg-amber-500 hover:text-white dark:bg-amber-400/15 dark:text-amber-400 dark:hover:bg-amber-500 dark:hover:text-slate-950 transition-all duration-200 shadow-xs ring-1 ring-amber-500/25 hover:scale-105 active:scale-95 shrink-0 cursor-pointer"
                                    title="Cari film (CGV style)"
                                    aria-label="Cari Film"
                                >
                                    <Search className="size-4 sm:size-4.5" />
                                </button>

                                {cinemaSettings?.showThemeToggle && (
                                    <ThemeToggle variant="responsive" />
                                )}
                            </div>
                        </>
                    )}
                </div>
            </header>

            {/* CGV-style Search Overlay & Results Dropdown Modal */}
            {isSearchOpen && (
                <>
                    {/* Backdrop click outside to close */}
                    <div
                        className="fixed inset-0 top-14 md:top-16 bg-black/60 backdrop-blur-xs z-40 animate-in fade-in duration-200"
                        onClick={handleCloseSearch}
                    />

                    {/* Floating Search Results Modal Card */}
                    <div className="fixed top-14 md:top-16 inset-x-0 z-50 px-3 sm:px-6 pt-3 pointer-events-none">
                        <div className="pointer-events-auto mx-auto max-w-4xl bg-surface/98 dark:bg-[#0C101A]/98 rounded-2xl border border-border/80 dark:border-white/10 shadow-2xl backdrop-blur-xl overflow-hidden flex flex-col max-h-[calc(85vh-4rem)] animate-in slide-in-from-top-2 duration-200">
                            {/* Search Header Bar (CGV Screenshot 4 style) */}
                            <div className="flex items-center justify-between border-b border-border/60 dark:border-white/10 px-4 py-3 sm:px-6 shrink-0 bg-surface-2/30">
                                <div className="flex items-center gap-2 min-w-0">
                                    <Search className="size-4.5 text-amber-500 shrink-0" />
                                    <p className="text-xs sm:text-sm text-text truncate">
                                        Hasil pencarian untuk <span className="font-bold text-amber-600 dark:text-amber-400">"{searchQuery || 'semua'}"</span>
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    onClick={handleCloseSearch}
                                    className="size-7 flex items-center justify-center rounded-lg hover:bg-surface-2 text-text-muted hover:text-text transition shrink-0 ml-2"
                                    aria-label="Tutup pencarian"
                                >
                                    <X className="size-4" />
                                </button>
                            </div>

                            {/* Subheader: Category & Count */}
                            <div className="px-4 pt-3 pb-1 sm:px-6 flex items-center justify-between shrink-0">
                                <span className="text-xs font-bold uppercase tracking-wider text-text-muted">
                                    Film ({isSearching ? '...' : searchTotalCount})
                                </span>
                                {searchTotalCount > 0 && (
                                    <a
                                        href={`/film?cari=${encodeURIComponent(searchQuery.trim())}`}
                                        onClick={(e) => {
                                            e.preventDefault();
                                            setIsSearchOpen(false);
                                            router.visit(`/film?cari=${encodeURIComponent(searchQuery.trim())}`);
                                        }}
                                        className="text-[11px] font-bold text-amber-600 dark:text-amber-400 hover:underline flex items-center gap-0.5 cursor-pointer"
                                    >
                                        Lihat di katalog &rarr;
                                    </a>
                                )}
                            </div>

                            {/* Content: Loading, Empty, or Results Grid */}
                            <div className="flex-1 overflow-y-auto">
                                {isSearching ? (
                                    <div className="py-12 flex flex-col items-center justify-center gap-3 text-text-muted">
                                        <Loader2 className="size-7 animate-spin text-amber-500" />
                                        <span className="text-xs font-medium">Mencari film di katalog...</span>
                                    </div>
                                ) : searchQuery.trim() === '' ? (
                                    <div className="py-10 text-center px-4">
                                        <Film className="size-8 text-amber-500/60 mx-auto mb-2" />
                                        <p className="text-xs sm:text-sm font-semibold text-text">Ketik judul film, genre, atau kata kunci</p>
                                        <p className="text-[11px] text-text-muted mt-0.5">Mulai mengetik untuk melihat hasil pencarian langsung</p>
                                    </div>
                                ) : searchResults.length === 0 ? (
                                    <div className="py-10 text-center px-4">
                                        <Film className="size-8 text-text-muted/40 mx-auto mb-2" />
                                        <p className="text-xs sm:text-sm font-semibold text-text">Tidak ada film yang cocok dengan "{searchQuery}"</p>
                                        <p className="text-[11px] text-text-muted mt-1">Coba kata kunci lain atau telusuri seluruh koleksi film.</p>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setIsSearchOpen(false);
                                                router.visit('/film');
                                            }}
                                            className="btn-gold-cinema inline-flex items-center gap-1.5 mt-3 rounded-xl px-3.5 py-1.5 text-xs font-bold shadow-xs cursor-pointer"
                                        >
                                            Jelajahi Katalog Film &rarr;
                                        </button>
                                    </div>
                                ) : (
                                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-4 p-4 sm:p-6">
                                        {searchResults.map((film) => {
                                            const targetUrl = `/film/${film.slug || film.id}`;
                                            return (
                                                <a
                                                    key={film.id}
                                                    href={targetUrl}
                                                    onClick={(e) => handleFilmClick(e, film)}
                                                    className="group flex flex-col rounded-xl overflow-hidden bg-surface-2/40 hover:bg-surface-2 transition-all duration-200 border border-transparent hover:border-amber-500/30 hover:shadow-md p-2 cursor-pointer select-none"
                                                >
                                                    {/* Poster Image */}
                                                    <div className="relative aspect-[2/3] w-full rounded-lg overflow-hidden bg-surface-sunken pointer-events-none select-none">
                                                        {film.poster_url ? (
                                                            <img
                                                                src={film.poster_url}
                                                                alt={film.title}
                                                                className="size-full object-cover group-hover:scale-105 transition-transform duration-300 pointer-events-none select-none"
                                                                loading="lazy"
                                                            />
                                                        ) : (
                                                            <div className="size-full flex items-center justify-center text-text-muted">
                                                                <Film className="size-8 opacity-40" />
                                                            </div>
                                                        )}
                                                        {film.age_rating && (
                                                            <span className="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[10px] font-black bg-black/80 text-amber-400 border border-white/20 shadow-xs">
                                                                {film.age_rating}
                                                            </span>
                                                        )}
                                                    </div>

                                                    {/* Movie Metadata */}
                                                    <div className="mt-2 flex flex-col flex-1 pointer-events-none select-none">
                                                        <h5 className="text-xs sm:text-sm font-bold text-text group-hover:text-amber-600 dark:group-hover:text-amber-400 line-clamp-2 leading-tight uppercase transition-colors">
                                                            {film.title}
                                                        </h5>
                                                        <div className="mt-auto pt-1 flex items-center justify-between text-[10px] sm:text-[11px] text-text-muted">
                                                            <span className="truncate max-w-[90px]">{film.genre || '-'}</span>
                                                            {film.release_year && <span>{film.release_year}</span>}
                                                        </div>
                                                    </div>
                                                </a>
                                            );
                                        })}
                                    </div>
                                )}
                            </div>

                            {/* Footer link to catalog if many results */}
                            {searchTotalCount > searchResults.length && (
                                <div className="border-t border-border/60 dark:border-white/10 px-4 py-2.5 sm:px-6 bg-surface-2/40 text-center shrink-0">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setIsSearchOpen(false);
                                            router.visit(`/film?cari=${encodeURIComponent(searchQuery.trim())}`);
                                        }}
                                        className="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline inline-flex items-center gap-1 cursor-pointer"
                                    >
                                        Lihat semua {searchTotalCount} film di katalog &rarr;
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>
                </>
            )}

            {/* Main Page Content */}
            <main className={`flex-1 ${!isActionPage ? 'pb-20 md:pb-0' : ''}`}>
                {children}
            </main>

            {/* Mobile Bottom Navigation Bar (< 768px) */}
            {!isActionPage && (
                <nav
                    aria-label="Navigasi Bawah Mobile"
                    className="md:hidden fixed bottom-0 inset-x-0 z-40 bg-surface/90 dark:bg-[#07090E]/90 backdrop-blur-md border-t border-border/80 dark:border-white/10 shadow-[0_-4px_20px_rgba(0,0,0,0.1)] transition-colors duration-200"
                    style={{ height: 'calc(64px + env(safe-area-inset-bottom, 0px))', paddingBottom: 'env(safe-area-inset-bottom, 0px)' }}
                >
                    <div className="h-[64px] flex items-stretch justify-around w-full max-w-lg mx-auto">
                        {bottomBarItems.map((bItem) => {
                            const active = isItemActive(bItem);
                            const label = bItem.short_label || bItem.label;
                            const isTicket = ['lookup', 'booking_lookup', 'tickets', 'tiket'].includes(bItem.key) || bItem.url?.includes('/tiket');

                            if (isTicket) {
                                return (
                                    <Link
                                        key={bItem.key || bItem.url}
                                        href={bItem.url || '/tiket'}
                                        className={`flex-1 min-h-[64px] flex flex-col items-center justify-center relative py-1.5 px-1 transition-colors ${active
                                            ? 'text-slate-950 dark:text-amber-400 font-extrabold'
                                            : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white font-medium'
                                            }`}
                                    >
                                        {active && (
                                            <span className="absolute top-0 inset-x-0 mx-auto h-[3px] w-7 bg-amber-500 rounded-full" />
                                        )}
                                        {getIconForNav(bItem.key)}
                                        <span className="text-xs font-semibold leading-tight mt-1">{label}</span>
                                    </Link>
                                );
                            }

                            return (
                                <Link
                                    key={bItem.key || bItem.url}
                                    href={bItem.url}
                                    className={`flex-1 min-h-[64px] flex flex-col items-center justify-center relative py-1.5 px-1 transition-colors ${active
                                        ? 'text-slate-950 dark:text-amber-400 font-extrabold'
                                        : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white font-medium'
                                        }`}
                                >
                                    {active && (
                                        <span className="absolute top-0 inset-x-0 mx-auto h-[3px] w-7 bg-amber-500 rounded-full" />
                                    )}
                                    {getIconForNav(bItem.key)}
                                    <span className="text-xs font-semibold leading-tight mt-1">{label}</span>
                                </Link>
                            );
                        })}

                        {/* Menu button to open Sheet */}
                        <button
                            type="button"
                            onClick={() => setMenuSheetOpen(true)}
                            className="flex-1 min-h-[64px] flex flex-col items-center justify-center relative py-1.5 px-1 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white font-medium transition-colors"
                        >
                            <Menu className="size-5" />
                            <span className="text-xs font-semibold leading-tight mt-1">Menu</span>
                        </button>
                    </div>
                </nav>
            )}

            {/* Mobile Bottom Sheet Menu (Exact Blade Layout: 2-Col Menu, Theme, Contact) */}
            {menuSheetOpen && (
                <div className="md:hidden fixed inset-0 z-50 overflow-hidden">
                    <div
                        className="fixed inset-0 bg-black/60 transition-opacity animate-in fade-in"
                        onClick={() => setMenuSheetOpen(false)}
                    />
                    <div className="fixed inset-x-0 bottom-0 z-50 max-h-[80dvh] rounded-t-3xl border-t border-border dark:border-white/10 bg-surface dark:bg-[#07090E] p-5 shadow-2xl flex flex-col overflow-hidden animate-in slide-in-from-bottom duration-200">
                        {/* Drag Handle */}
                        <div className="mx-auto w-12 h-1.5 bg-slate-300 dark:bg-white/20 rounded-full mb-3 shrink-0" />

                        {/* Header with Brand Logo and Close Button */}
                        <div className="flex items-center justify-between pb-3.5 border-b border-border dark:border-white/10 shrink-0">
                            <BrandLogo size="sm" />
                            <button
                                type="button"
                                onClick={() => setMenuSheetOpen(false)}
                                className="inline-flex size-11 items-center justify-center rounded-xl border border-slate-300 dark:border-white/15 bg-surface text-text hover:bg-surface-2 transition"
                                aria-label="Tutup menu navigasi"
                            >
                                <X className="size-5" />
                            </button>
                        </div>

                        {/* Scrollable Content Area */}
                        <div className="overflow-y-auto space-y-6 pt-4 pb-2 overscroll-contain text-sm flex-1 custom-scrollbar">
                            {/* Navigasi Lainnya (2 Column Grid) */}
                            <div>
                                <p className="text-[11px] font-bold uppercase tracking-wider text-text-muted mb-2.5">Menu Lainnya</p>
                                <div className="grid grid-cols-2 gap-2">
                                    {menuSheetItems.map((sheetItem) => {
                                        const isExternal = sheetItem.url?.startsWith('http');
                                        const isTiket = ['lookup', 'booking_lookup', 'tickets', 'tiket'].includes(sheetItem.key) || sheetItem.url?.includes('/tiket');

                                        if (isExternal || isTiket) {
                                            return (
                                                <a
                                                    key={sheetItem.key || sheetItem.url}
                                                    href={sheetItem.url}
                                                    onClick={() => setMenuSheetOpen(false)}
                                                    className="flex min-h-[44px] items-center justify-between rounded-xl border border-border bg-surface-2 px-3.5 py-2 font-bold text-text hover:border-amber-500 transition-colors shadow-xs"
                                                >
                                                    <span>{sheetItem.label}</span>
                                                    <ChevronRight className="size-4 text-text-muted shrink-0" />
                                                </a>
                                            );
                                        }

                                        return (
                                            <Link
                                                key={sheetItem.key || sheetItem.url}
                                                href={sheetItem.url}
                                                onClick={() => setMenuSheetOpen(false)}
                                                className="flex min-h-[44px] items-center justify-between rounded-xl border border-border bg-surface-2 px-3.5 py-2 font-bold text-text hover:border-amber-500 transition-colors shadow-xs"
                                            >
                                                <span>{sheetItem.label}</span>
                                                <ChevronRight className="size-4 text-text-muted shrink-0" />
                                            </Link>
                                        );
                                    })}
                                    <Link
                                        href="/kabar?tab=harga-faq"
                                        onClick={() => setMenuSheetOpen(false)}
                                        className="flex min-h-[44px] items-center justify-between rounded-xl border border-border bg-surface-2 px-3.5 py-2 font-bold text-text hover:border-amber-500 transition-colors shadow-xs"
                                    >
                                        <span>Harga & FAQ</span>
                                        <ChevronRight className="size-4 text-text-muted shrink-0" />
                                    </Link>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setMenuSheetOpen(false);
                                            window.dispatchEvent(new CustomEvent('synemax-open-pwa-install', { detail: { type: 'public' } }));
                                        }}
                                        className="col-span-2 flex min-h-[44px] items-center justify-between rounded-xl border border-amber-500/40 bg-amber-500/10 px-3.5 py-2 font-bold text-amber-500 hover:bg-amber-500/20 transition-colors shadow-xs"
                                    >
                                        <div className="flex items-center gap-2">
                                            <Download className="size-4 shrink-0" />
                                            <span>Pasang Aplikasi SynemaX</span>
                                        </div>
                                        <ChevronRight className="size-4 shrink-0 text-amber-500/70" />
                                    </button>
                                </div>
                            </div>

                            {/* Pilihan Tampilan (Tema) */}
                            {cinemaSettings?.showThemeToggle && (
                                <div>
                                    <p className="text-[11px] font-bold uppercase tracking-wider text-text-muted mb-2.5">Pilihan Tampilan</p>
                                    <div className="grid grid-cols-3 gap-2">
                                        <button
                                            type="button"
                                            onClick={() => handleSetTheme('light')}
                                            className="flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-border bg-surface-2 text-text px-3 py-2 text-xs font-semibold hover:border-amber-500 transition shadow-xs"
                                        >
                                            <Sun className="size-4" />
                                            <span>Terang</span>
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => handleSetTheme('dark')}
                                            className="flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-border bg-surface-2 text-text px-3 py-2 text-xs font-semibold hover:border-amber-500 transition shadow-xs"
                                        >
                                            <Moon className="size-4" />
                                            <span>Gelap</span>
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => handleSetTheme('system')}
                                            className="flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-border bg-surface-2 text-text px-3 py-2 text-xs font-semibold hover:border-amber-500 transition shadow-xs"
                                        >
                                            <Monitor className="size-4" />
                                            <span>Perangkat</span>
                                        </button>
                                    </div>
                                </div>
                            )}

                            {/* Kontak & Lokasi */}
                            {(cinemaSettings?.contactAddress || cleanWaNumber) && (
                                <div className="space-y-3 pt-3 border-t border-border dark:border-white/10">
                                    <p className="text-[11px] font-bold uppercase tracking-wider text-text-muted">Kontak & Lokasi</p>
                                    {cinemaSettings?.contactAddress && (
                                        <div className="text-xs text-text-muted space-y-1">
                                            <p className="font-bold text-text">{cinemaSettings?.siteName || 'SynemaX'}</p>
                                            <p className="leading-relaxed">{cinemaSettings.contactAddress}</p>
                                            {mapsLink && (
                                                <a
                                                    href={mapsLink}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400 font-bold hover:underline pt-1 text-xs"
                                                >
                                                    <span>{cinemaSettings?.mapsLabel || 'Lihat di Google Maps'}</span>
                                                    <ExternalLink className="size-3.5" />
                                                </a>
                                            )}
                                        </div>
                                    )}

                                    {cleanWaNumber && (
                                        <a
                                            href={`https://wa.me/${cleanWaNumber}?text=${encodeURIComponent('Halo admin SynemaX, saya mau bertanya.')}`}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="flex min-h-[44px] w-full items-center justify-between rounded-xl border border-emerald-500/30 bg-emerald-500/10 dark:bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 px-3.5 py-2.5 text-xs font-bold hover:bg-emerald-500/20 transition-colors shadow-xs"
                                        >
                                            <div className="flex items-center gap-2.5">
                                                <span className="flex size-7 items-center justify-center rounded-lg bg-emerald-500 text-white shrink-0 shadow-xs">
                                                    <MessageCircle className="size-4" />
                                                </span>
                                                <span>Chat WhatsApp Admin</span>
                                            </div>
                                            <ExternalLink className="size-4" />
                                        </a>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {/* Floating WhatsApp Action Button with Mascot on Top & Close Button */}
            {cinemaSettings?.showFloatingWhatsapp && cleanWaNumber && !isWaBubbleDismissed && (
                <div className="fixed bottom-24 md:bottom-8 right-4 sm:right-6 z-40 flex flex-col items-center select-none animate-in fade-in zoom-in-95 duration-200">
                    {/* Close / Dismiss Button ("X") */}
                    <button
                        type="button"
                        onClick={handleDismissWaBubble}
                        className="absolute -top-1.5 -right-1 z-20 flex size-5 items-center justify-center rounded-full bg-slate-900/80 hover:bg-slate-950 text-white/80 hover:text-white border border-white/20 shadow-md backdrop-blur-xs transition hover:scale-110"
                        title="Tutup chat Tanya Bioskop"
                        aria-label="Tutup bubble Tanya Bioskop"
                    >
                        <X className="size-3 stroke-[2.5]" />
                    </button>

                    <a
                        href={`https://wa.me/${cleanWaNumber}?text=Halo%20Admin%20SynemaX,%20saya%20ingin%20bertanya`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="group flex flex-col items-center transition-all duration-300 hover:scale-105"
                        aria-label="Hubungi kami melalui WhatsApp: Tanya Bioskop"
                    >
                        {/* Mascot Character on Top */}
                        <div className="relative -mb-2 z-10 transition-transform duration-300 group-hover:-translate-y-1.5 pointer-events-none">
                            <img
                                src="/images/tanya-bioskop-mascot.png"
                                alt="Maskot Customer Service SynemaX"
                                className="w-14 h-14 sm:w-16 sm:h-16 object-contain drop-shadow-xl"
                            />
                            {/* Live active status indicator */}
                            <span className="absolute bottom-2 right-1 flex size-3">
                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span className="relative inline-flex rounded-full size-3 bg-emerald-500 border-2 border-white dark:border-[#07090E]"></span>
                            </span>
                        </div>

                        {/* Green Pill / Bubble with "Tanya Bioskop" text */}
                        <div className="flex items-center gap-1.5 px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-full bg-emerald-600 group-hover:bg-emerald-500 text-white font-black text-xs sm:text-sm shadow-xl shadow-emerald-950/40 border border-emerald-400/40 transition-colors">
                            <MessageCircle className="size-3.5 sm:size-4 fill-white/20 shrink-0" />
                            <span className="whitespace-nowrap tracking-wide">Tanya Bioskop</span>
                        </div>
                    </a>
                </div>
            )}

            {/* Footer: Hidden on mobile phones */}
            <footer className="hidden md:block border-t border-border/80 dark:border-white/10 bg-surface/90 dark:bg-[#07090E] transition-colors duration-200">
                <div className="mx-auto max-w-6xl px-4 py-12 sm:px-6">
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
                        {/* Col 1: Brand & Bio */}
                        <div className="md:col-span-2 space-y-3">
                            <BrandLogo size="default" />
                            <p className="text-text-muted text-sm max-w-md">
                                {cinemaSettings?.siteTagline || 'Studio Bioskop Mini Privat Eksklusif'}. Nikmati tontonan berkualitas dengan AC dingin, kursi sofa nyaman, dan audio Dolby Surround.
                            </p>
                            <div className="pt-2 text-xs text-amber-700 dark:text-amber-400 font-bold">
                                {cinemaSettings?.capacityDescription || '6 kursi privat per sesi'} · {cinemaSettings?.startingPriceDescription || 'Mulai Rp 5.000'}
                            </div>
                        </div>

                        {/* Col 2: Navigation Links */}
                        <div>
                            <h4 className="text-sm font-bold uppercase tracking-wider text-text mb-3">Navigasi</h4>
                            <ul className="space-y-2 text-sm text-text-muted">
                                {visibleItems.slice(0, 5).map((item) => (
                                    <li key={item.key || item.url}>
                                        <Link href={item.url} className="hover:text-amber-500 transition">
                                            {item.label}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </div>

                        {/* Col 3: Contact & Social */}
                        <div>
                            <h4 className="text-sm font-bold uppercase tracking-wider text-text mb-3">Kontak & Lokasi</h4>
                            <ul className="space-y-2.5 text-sm text-text-muted">
                                {cinemaSettings?.contactAddress && (
                                    <li className="flex items-start gap-2">
                                        <MapPin className="size-4 text-amber-500 shrink-0 mt-0.5" />
                                        <span>{cinemaSettings.contactAddress}</span>
                                    </li>
                                )}
                                {cleanWaNumber && (
                                    <li className="flex items-center gap-2">
                                        <Phone className="size-4 text-amber-500 shrink-0" />
                                        <a href={`https://wa.me/${cleanWaNumber}`} className="hover:text-amber-500 transition">
                                            {cinemaSettings?.contactWhatsapp}
                                        </a>
                                    </li>
                                )}
                                {cinemaSettings?.contactEmail && (
                                    <li className="flex items-center gap-2">
                                        <Mail className="size-4 text-amber-500 shrink-0" />
                                        <a href={`mailto:${cinemaSettings.contactEmail}`} className="hover:text-amber-500 transition">
                                            {cinemaSettings.contactEmail}
                                        </a>
                                    </li>
                                )}
                            </ul>

                            {/* Social Media links */}
                            <div className="flex items-center gap-3 mt-4">
                                {cinemaSettings?.socialSettings?.instagram?.url && (
                                    <a
                                        href={cinemaSettings.socialSettings.instagram.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="Instagram"
                                        className="size-9 rounded-xl border border-border flex items-center justify-center text-text-muted hover:text-amber-500 hover:border-amber-500 transition"
                                    >
                                        <svg className="size-4" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                                        </svg>
                                    </a>
                                )}
                                {cinemaSettings?.socialSettings?.youtube?.url && (
                                    <a
                                        href={cinemaSettings.socialSettings.youtube.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="YouTube"
                                        className="size-9 rounded-xl border border-border flex items-center justify-center text-text-muted hover:text-amber-500 hover:border-amber-500 transition"
                                    >
                                        <svg className="size-4" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
                                        </svg>
                                    </a>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Bottom row */}
                    <div className="mt-8 pt-6 border-t border-border/60 dark:border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-text-muted">
                        <div>
                            {cinemaSettings?.formattedFooterText || (
                                <>
                                    &copy; {new Date().getFullYear()} SynemaX Cinema. Hak cipta dilindungi.
                                </>
                            )}
                        </div>
                        <div className="flex items-center gap-4">
                            <Link href="/kabar?tab=harga-faq" className="hover:text-text transition">
                                Tanya Jawab & Harga
                            </Link>
                        </div>
                    </div>
                </div>
            </footer>

            {/* PWA Mobile & Desktop Install Offer Pop-up */}
            <PwaInstallBanner forceType="public" />
        </div>
    );
}
