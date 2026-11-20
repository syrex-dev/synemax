import { useState, useEffect, useRef } from 'react';
import { Link, Head, router } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import { FilmCardSkeleton } from '../../Components/Skeleton';
import {
    Film as FilmIcon,
    Calendar,
    Sparkles,
    RotateCcw,
    ChevronDown,
    Search,
    X,
} from 'lucide-react';

export default function Home({
    heroBanners = [],
    infoChips = [],
    films = [],
    filmsData = [],
    selectedGenre: initGenre = '',
    selectedYear: initYear = '',
    totalFilteredCount = 0,
    filmsLimit = 12,
    genres = [],
    years = [],
    promos = [],
    upcomingNobar = [],
    infoNobarEnabled = false,
    snackBanners = [],
    cinemaSettings,
}) {
    // --- Hero Carousel State ---
    const validHeroBanners = (heroBanners || []).filter((b) => !!b.image_path);
    const heroCount = validHeroBanners.length;
    const [heroIndex, setHeroIndex] = useState(0);

    // Touch & Swipe gestures for Hero Carousel
    const touchStartX = useRef(0);
    const touchStartY = useRef(0);
    const touchEndX = useRef(0);
    const touchEndY = useRef(0);
    const isSwiping = useRef(false);
    const wasSwiped = useRef(false);

    const handleTouchStart = (e) => {
        if (!e.touches || e.touches.length === 0) return;
        touchStartX.current = e.touches[0].clientX;
        touchStartY.current = e.touches[0].clientY;
        touchEndX.current = e.touches[0].clientX;
        touchEndY.current = e.touches[0].clientY;
        isSwiping.current = true;
        wasSwiped.current = false;
    };

    const handleTouchMove = (e) => {
        if (!isSwiping.current || !e.touches || e.touches.length === 0) return;
        touchEndX.current = e.touches[0].clientX;
        touchEndY.current = e.touches[0].clientY;
        if (Math.abs(touchEndX.current - touchStartX.current) > 10) {
            wasSwiped.current = true;
        }
    };

    const handleTouchEnd = () => {
        if (!isSwiping.current) return;
        isSwiping.current = false;
        if (heroCount <= 1) return;

        const diffX = touchEndX.current - touchStartX.current;
        const diffY = touchEndY.current - touchStartY.current;

        // Dominant horizontal swipe with minimum threshold of 35px
        if (Math.abs(diffX) > 35 && Math.abs(diffX) > Math.abs(diffY)) {
            if (diffX < 0) {
                // Swiped left -> Next slide
                setHeroIndex((prev) => (prev + 1) % heroCount);
            } else {
                // Swiped right -> Previous slide
                setHeroIndex((prev) => (prev - 1 + heroCount) % heroCount);
            }
        }
    };

    // Desktop mouse drag gestures
    const mouseStartX = useRef(0);
    const mouseEndX = useRef(0);
    const isMouseDown = useRef(false);

    const handleMouseDown = (e) => {
        mouseStartX.current = e.clientX;
        mouseEndX.current = e.clientX;
        isMouseDown.current = true;
        wasSwiped.current = false;
    };

    const handleMouseMove = (e) => {
        if (!isMouseDown.current) return;
        mouseEndX.current = e.clientX;
        if (Math.abs(mouseEndX.current - mouseStartX.current) > 10) {
            wasSwiped.current = true;
        }
    };

    const handleMouseUp = () => {
        if (!isMouseDown.current) return;
        isMouseDown.current = false;
        if (heroCount <= 1) return;

        const diffX = mouseEndX.current - mouseStartX.current;
        if (Math.abs(diffX) > 40) {
            if (diffX < 0) {
                setHeroIndex((prev) => (prev + 1) % heroCount);
            } else {
                setHeroIndex((prev) => (prev - 1 + heroCount) % heroCount);
            }
        }
    };

    useEffect(() => {
        if (heroCount <= 1) return;
        const intervalTime = (cinemaSettings?.homeHeroInterval || 5) * 1000;
        const timer = setInterval(() => {
            setHeroIndex((prev) => (prev + 1) % heroCount);
        }, intervalTime);
        return () => clearInterval(timer);
    }, [heroCount, cinemaSettings]);

    // --- Dynamic Film Filter State ---
    const [genre, setGenre] = useState(initGenre);
    const [year, setYear] = useState(initYear);
    const [filmList, setFilmList] = useState(filmsData.length > 0 ? filmsData : films);
    const [totalMatches, setTotalMatches] = useState(totalFilteredCount);
    const [isLoading, setIsLoading] = useState(false);
    const [yearDropdownOpen, setYearDropdownOpen] = useState(false);
    const yearDropdownRef = useRef(null);

    // Synchronize film list when Inertia partial reload updates props
    useEffect(() => {
        setFilmList(filmsData.length > 0 ? filmsData : films);
        setTotalMatches(totalFilteredCount);
        setGenre(initGenre);
        setYear(initYear);
    }, [filmsData, films, totalFilteredCount, initGenre, initYear]);

    // Close year dropdown on outside click
    useEffect(() => {
        const handleClickOutside = (e) => {
            if (yearDropdownRef.current && !yearDropdownRef.current.contains(e.target)) {
                setYearDropdownOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const triggerFilter = (nextGenre, nextYear) => {
        setIsLoading(true);
        const params = {};
        if (nextGenre) params.genre = nextGenre;
        if (nextYear) params.tahun = nextYear;

        router.get('/', params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['filmsData', 'totalFilteredCount', 'selectedGenre', 'selectedYear'],
            onFinish: () => setIsLoading(false),
        });
    };

    const handleSelectGenre = (g) => {
        const next = genre === g ? '' : g;
        setGenre(next);
        triggerFilter(next, year);
    };

    const handleSelectYear = (y) => {
        const next = year === String(y) ? '' : String(y);
        setYear(next);
        setYearDropdownOpen(false);
        triggerFilter(genre, next);
    };

    const handleResetFilter = () => {
        setGenre('');
        setYear('');
        setYearDropdownOpen(false);
        triggerFilter('', '');
    };

    const hasActiveFilter = Boolean(genre || year);

    return (
        <PublicLayout title="Beranda">
            <Head title="Beranda">
                <meta
                    name="description"
                    content="Bioskop mini privat eksklusif dengan kapasitas 6 penonton, AC dingin, dan tata suara Dolby Surround."
                />
            </Head>

            <div className="mx-auto max-w-6xl px-4 py-4 sm:px-6 sm:py-6 space-y-7 sm:space-y-12">
                {/* 1. HERO CAROUSEL BANNER */}
                {heroCount > 0 && (
                    <section aria-label="Sorotan Utama Bioskop" className="-mx-4 sm:mx-0">
                        <div className="relative overflow-hidden sm:rounded-3xl border-y sm:border border-border/80 dark:border-white/10 bg-slate-900 shadow-xl">
                            <div
                                className="relative w-full aspect-[4/3] sm:aspect-[2.35/1] max-h-[300px] sm:max-h-[460px] touch-pan-y select-none cursor-grab active:cursor-grabbing"
                                onTouchStart={handleTouchStart}
                                onTouchMove={handleTouchMove}
                                onTouchEnd={handleTouchEnd}
                                onTouchCancel={() => { isSwiping.current = false; }}
                                onMouseDown={handleMouseDown}
                                onMouseMove={handleMouseMove}
                                onMouseUp={handleMouseUp}
                                onMouseLeave={handleMouseUp}
                            >
                                {validHeroBanners.map((banner, idx) => {
                                    const active = idx === heroIndex;
                                    const imgUrl = banner.image_path
                                        ? (banner.image_path.startsWith('http') ? banner.image_path : `/storage/${banner.image_path}`)
                                        : null;
                                    const mobileImgUrl = banner.mobile_image_path
                                        ? (banner.mobile_image_path.startsWith('http') ? banner.mobile_image_path : `/storage/${banner.mobile_image_path}`)
                                        : null;

                                    if (!imgUrl) return null;

                                    const bannerContent = (
                                        <picture className="size-full">
                                            {mobileImgUrl && (
                                                <source media="(max-width: 640px)" srcSet={mobileImgUrl} />
                                            )}
                                            <img
                                                src={imgUrl}
                                                alt={banner.title || 'Sorotan Bioskop'}
                                                className="size-full object-cover object-center select-none pointer-events-none"
                                                loading={idx === 0 ? 'eager' : 'lazy'}
                                                draggable="false"
                                            />
                                        </picture>
                                    );

                                    return (
                                        <div
                                            key={banner.id || idx}
                                            className={`absolute inset-0 transition-opacity duration-700 ease-in-out ${active ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'
                                                }`}
                                        >
                                            {banner.cta_url ? (
                                                <a
                                                    href={banner.cta_url}
                                                    onClick={(e) => {
                                                        if (wasSwiped.current) {
                                                            e.preventDefault();
                                                        }
                                                    }}
                                                    className="block size-full"
                                                    tabIndex={active ? 0 : -1}
                                                    aria-label={banner.title || 'Lihat detail banner'}
                                                >
                                                    {bannerContent}
                                                </a>
                                            ) : (
                                                bannerContent
                                            )}
                                        </div>
                                    );
                                })}

                                {/* Indicators */}
                                {heroCount > 1 && (
                                    <div className="absolute bottom-3 inset-x-0 z-20 flex items-center justify-center gap-1.5 pointer-events-none">
                                        {validHeroBanners.map((_, dotIdx) => (
                                            <button
                                                key={dotIdx}
                                                type="button"
                                                onClick={() => setHeroIndex(dotIdx)}
                                                className={`pointer-events-auto h-1.5 rounded-full transition-all duration-300 ${
                                                    dotIdx === heroIndex ? 'w-6 bg-amber-500' : 'w-2 bg-white/40'
                                                }`}
                                                aria-label={`Lihat banner ${dotIdx + 1}`}
                                            />
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    </section>
                )}

                {/* 3. KATALOG FILM SECTION */}
                <section aria-label="Katalog Film" id="katalog-film" className="space-y-4 sm:space-y-6">
                    {/* Header */}
                    <div className="flex items-center justify-between gap-3 border-l-4 border-amber-500 pl-3 sm:pl-4">
                        <div className="min-w-0">
                            <h2 className="text-xl sm:text-2xl font-black text-text tracking-tight">Katalog Film</h2>
                            <p className="mt-0.5 text-xs text-text-muted line-clamp-1 sm:line-clamp-none">
                                Pilihan film on-demand berkualitas untuk diputar di studio privat
                            </p>
                        </div>
                        <Link
                            href={`/film${genre ? `?genre=${encodeURIComponent(genre)}` : ''}${year ? `${genre ? '&' : '?'}tahun=${year}` : ''}`}
                            className="shrink-0 whitespace-nowrap text-xs sm:text-sm font-bold text-amber-700 dark:text-amber-400 hover:underline"
                        >
                            <span className="sm:hidden">Semua &gt;</span>
                            <span className="hidden sm:inline">Lihat semua film &gt;</span>
                        </Link>
                    </div>

                    {/* Filter Bar: Genre Pills & Year Selector */}
                    <div className="relative z-30 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 p-3 sm:p-4 rounded-2xl border border-border/80 bg-surface/80 dark:bg-black/20 backdrop-blur-sm">
                        {/* Genre scrollable list */}
                        <div className="flex items-center gap-1.5 overflow-x-auto pt-1 pb-2.5 sm:pb-3 custom-scrollbar flex-1">
                            <button
                                type="button"
                                onClick={() => handleSelectGenre('')}
                                className={`px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition shadow-xs ${genre === ''
                                    ? 'bg-amber-500 text-slate-950 font-black shadow-amber-500/20'
                                    : 'border border-border bg-surface text-text hover:bg-surface-2'
                                    }`}
                            >
                                Semua Genre
                            </button>
                            {genres.map((g) => (
                                <button
                                    key={g}
                                    type="button"
                                    onClick={() => handleSelectGenre(g)}
                                    className={`px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition shadow-xs ${genre === g
                                        ? 'bg-amber-500 text-slate-950 font-black shadow-amber-500/20'
                                        : 'border border-border bg-surface text-text hover:bg-surface-2'
                                        }`}
                                >
                                    {g}
                                </button>
                            ))}
                        </div>

                        {/* Year Filter Dropdown & Reset */}
                        <div className="flex items-center gap-2 shrink-0">
                            <div ref={yearDropdownRef} className="relative z-30">
                                <button
                                    type="button"
                                    onClick={() => setYearDropdownOpen(!yearDropdownOpen)}
                                    className={`flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-bold border transition ${year
                                        ? 'bg-amber-500/15 border-amber-500 text-amber-900 dark:text-amber-300'
                                        : 'border-border bg-surface text-text hover:bg-surface-2'
                                        }`}
                                >
                                    <Calendar className="size-3.5 text-amber-500" />
                                    <span>{year ? `Tahun: ${year}` : 'Tahun'}</span>
                                    <ChevronDown className="size-3 text-text-muted" />
                                </button>

                                {yearDropdownOpen && (
                                    <div className="absolute left-0 sm:left-auto sm:right-0 mt-1.5 w-44 max-h-56 overflow-y-auto rounded-2xl border border-border/80 dark:border-white/10 bg-surface dark:bg-[#0E131F] p-1.5 shadow-[0_10px_38px_rgba(0,0,0,0.35),0_10px_20px_rgba(0,0,0,0.2)] z-50 custom-scrollbar animate-in fade-in zoom-in-95 duration-100">
                                        <button
                                            type="button"
                                            onClick={() => handleSelectYear('')}
                                            className={`w-full text-left px-3 py-2 rounded-xl text-xs font-semibold transition ${year === '' ? 'bg-amber-500 text-slate-950 font-black' : 'hover:bg-surface-raised text-text'
                                                }`}
                                        >
                                            Semua Tahun
                                        </button>
                                        {years.map((y) => (
                                            <button
                                                key={y}
                                                type="button"
                                                onClick={() => handleSelectYear(y)}
                                                className={`w-full text-left px-3 py-2 rounded-xl text-xs font-semibold transition ${year === String(y)
                                                    ? 'bg-amber-500 text-slate-950 font-black'
                                                    : 'hover:bg-surface-raised text-text'
                                                    }`}
                                            >
                                                {y}
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>

                            {/* Reset Button */}
                            {hasActiveFilter && (
                                <button
                                    type="button"
                                    onClick={handleResetFilter}
                                    className="flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold border border-red-500/30 bg-red-500/10 text-red-600 dark:text-red-400 hover:bg-red-500/20 transition"
                                >
                                    <RotateCcw className="size-3" />
                                    <span>Reset</span>
                                </button>
                            )}
                        </div>
                    </div>

                    {/* Film Grid */}
                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 sm:gap-6">
                        {isLoading ? (
                            <FilmCardSkeleton count={filmsLimit || 8} />
                        ) : (
                            filmList.map((film) => {
                                const poster = film.poster_url || (film.poster_path ? (film.poster_path.startsWith('http') ? film.poster_path : `/storage/${film.poster_path}`) : null);
                                const filmUrl = film.url || `/film/${film.slug || film.id}`;
                                const mainGenre = film.genre ? film.genre.split(',')[0].trim() : '-';

                                return (
                                    <Link
                                        key={film.id}
                                        href={filmUrl}
                                        className="group relative flex flex-col rounded-2xl overflow-hidden border border-border/80 bg-surface shadow-2xs hover:border-amber-500 transition-all duration-200 cursor-pointer select-none"
                                    >
                                        {/* Poster 2:3 */}
                                        <div className="relative w-full aspect-[2/3] overflow-hidden bg-slate-900 pointer-events-none select-none">
                                            {poster ? (
                                                <img
                                                    src={poster}
                                                    alt={film.title}
                                                    loading="lazy"
                                                    className="size-full object-cover transition-transform duration-200 group-hover:scale-103 select-none pointer-events-none"
                                                />
                                            ) : (
                                                <div className="flex size-full flex-col items-center justify-center p-3 text-center bg-slate-900 border border-white/10 text-white">
                                                    <FilmIcon className="size-8 text-amber-500/60 mb-1.5" />
                                                    <span className="text-[11px] font-bold text-amber-200 line-clamp-2">
                                                        {film.title}
                                                    </span>
                                                </div>
                                            )}

                                            {/* Age Rating Badge */}
                                            {film.age_rating && (
                                                <div className="absolute top-2 right-2 z-10">
                                                    <span className="inline-flex items-center rounded-md bg-black/80 px-1.5 py-0.5 text-[10px] font-black text-white border border-white/20">
                                                        {film.age_rating}
                                                    </span>
                                                </div>
                                            )}

                                            {/* Custom Badge */}
                                            {film.custom_badge && (
                                                <div className="absolute bottom-2 inset-x-0 flex justify-center px-2 z-10 pointer-events-none">
                                                    <span className="inline-flex items-center justify-center bg-[#E50914] text-white font-black text-[10px] sm:text-xs px-2.5 py-0.5 rounded shadow-md truncate max-w-full border border-white/20">
                                                        {film.custom_badge}
                                                    </span>
                                                </div>
                                            )}
                                        </div>

                                        {/* Film Info */}
                                        <div className="p-3 sm:p-4 flex flex-col flex-1 justify-between gap-2">
                                            <h3 className="font-bold text-sm sm:text-base text-text line-clamp-2 min-h-[2.5rem] group-hover:text-amber-500 transition-colors">
                                                {film.title}
                                            </h3>
                                            <div className="flex items-center justify-between text-xs text-text-muted border-t border-border/60 pt-2 font-medium">
                                                <span className="truncate max-w-[65%]">{mainGenre}</span>
                                                <span>{film.release_year || '-'}</span>
                                            </div>
                                        </div>
                                    </Link>
                                );
                            }))}
                    </div>

                    {/* Empty State */}
                    {!isLoading && filmList.length === 0 && (
                        <div className="rounded-2xl border border-border/80 bg-surface p-8 text-center space-y-2">
                            <p className="text-sm font-bold text-text">Belum ada film untuk filter ini</p>
                            <p className="text-xs text-text-muted">Coba ubah pilihan genre atau tahun rilis Anda.</p>
                            <button
                                type="button"
                                onClick={handleResetFilter}
                                className="mt-2 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-amber-500 text-slate-950 font-bold text-xs shadow-md shadow-amber-500/20"
                            >
                                <RotateCcw className="size-3.5" />
                                <span>Reset Filter</span>
                            </button>
                        </div>
                    )}

                    {/* View All Matches button if more exist */}
                    {hasActiveFilter && !isLoading && totalMatches > filmList.length && (
                        <div className="flex items-center justify-between p-3.5 rounded-2xl border border-border bg-surface text-xs font-semibold">
                            <span className="text-text-muted">
                                Menampilkan <strong className="text-text">{filmList.length}</strong> dari <strong className="text-text">{totalMatches}</strong> film yang cocok.
                            </span>
                            <Link
                                href={`/film?genre=${encodeURIComponent(genre)}&tahun=${year}`}
                                className="text-amber-700 dark:text-amber-400 font-bold hover:underline"
                            >
                                Lihat semua {totalMatches} film &gt;
                            </Link>
                        </div>
                    )}
                </section>

                <div className="border-t border-border/60 dark:border-white/10" />

                {/* 4. PROMO & PENAWARAN */}
                {promos.length > 0 && (
                    <section aria-label="Promo dan Penawaran" className="space-y-4 sm:space-y-6">
                        <div className="flex items-center justify-between gap-3 border-l-4 border-amber-500 pl-3 sm:pl-4">
                            <div className="min-w-0">
                                <h2 className="text-xl sm:text-2xl font-black text-text tracking-tight">Promo & Penawaran</h2>
                                <p className="mt-0.5 text-xs text-text-muted line-clamp-1 sm:line-clamp-none">
                                    Penawaran hemat dan paket spesial menonton di SynemaX
                                </p>
                            </div>
                            <Link href="/promo" className="shrink-0 text-xs sm:text-sm font-bold text-amber-700 dark:text-amber-400 hover:underline">
                                Lihat semua &gt;
                            </Link>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            {promos.map((promo) => {
                                const pImg = promo.image_path ? (promo.image_path.startsWith('http') ? promo.image_path : `/storage/${promo.image_path}`) : null;
                                return (
                                    <Link
                                        key={promo.id}
                                        href={`/promo/${promo.slug}`}
                                        className="group relative block w-full aspect-[2/1] overflow-hidden rounded-2xl border border-border bg-surface shadow-2xs hover:border-amber-500 transition-colors"
                                    >
                                        {pImg ? (
                                            <img
                                                src={pImg}
                                                alt={promo.title}
                                                loading="lazy"
                                                className="size-full object-cover transition-transform duration-200 group-hover:scale-102 select-none"
                                            />
                                        ) : (
                                            <div className="flex size-full flex-col items-center justify-center p-4 text-center bg-surface-2 text-text">
                                                <span className="text-sm font-bold text-amber-500">{promo.title}</span>
                                            </div>
                                        )}
                                    </Link>
                                );
                            })}
                        </div>
                    </section>
                )}

                {/* 5. MAKANAN & MINUMAN (SNACKS) */}
                {snackBanners && snackBanners.length > 0 && (
                    <>
                        <div className="border-t border-border/60 dark:border-white/10" />
                        <section aria-label="Makanan dan Minuman" className="space-y-4 sm:space-y-6">
                            <div className="flex items-center justify-between gap-3 border-l-4 border-amber-500 pl-3 sm:pl-4">
                                <div className="min-w-0">
                                    <h2 className="text-xl sm:text-2xl font-black text-text tracking-tight">Makanan & Minuman</h2>
                                    <p className="mt-0.5 text-xs text-text-muted line-clamp-1 sm:line-clamp-none">
                                        Popcorn hangat, minuman segar, dan camilan lezat menemani nontonmu
                                    </p>
                                </div>
                                <Link href="/tiket" className="shrink-0 text-xs sm:text-sm font-bold text-amber-700 dark:text-amber-400 hover:underline">
                                    Pesan camilan &gt;
                                </Link>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                {snackBanners.map((sb, idx) => {
                                    const sbImg = sb.image_path ? (sb.image_path.startsWith('http') ? sb.image_path : `/storage/${sb.image_path}`) : null;
                                    return (
                                        <Link
                                            key={idx}
                                            href={sb.url || '/tiket'}
                                            className="group relative block aspect-[2/1] w-full overflow-hidden rounded-2xl border border-border bg-surface shadow-2xs hover:border-amber-500 transition-colors"
                                        >
                                            {sbImg ? (
                                                <img
                                                    src={sbImg}
                                                    alt={sb.title || 'Camilan'}
                                                    loading="lazy"
                                                    className="size-full object-cover transition-transform duration-200 group-hover:scale-102 select-none"
                                                />
                                            ) : (
                                                <div className="flex size-full flex-col items-center justify-center p-4 text-center bg-surface-2 text-text">
                                                    <span className="text-sm font-bold text-amber-500">{sb.title}</span>
                                                </div>
                                            )}
                                        </Link>
                                    );
                                })}
                            </div>
                        </section>
                    </>
                )}

                {/* 6. NOBAR MENDATANG */}
                {infoNobarEnabled && upcomingNobar.length > 0 && (
                    <>
                        <div className="border-t border-border/60 dark:border-white/10" />
                        <section aria-label="Nonton Bareng Mendatang" className="space-y-4 sm:space-y-6">
                            <div className="flex items-center justify-between gap-3 border-l-4 border-amber-500 pl-3 sm:pl-4">
                                <div className="min-w-0">
                                    <h2 className="text-xl sm:text-2xl font-black text-text tracking-tight">Nobar Mendatang</h2>
                                    <p className="mt-0.5 text-xs text-text-muted line-clamp-1 sm:line-clamp-none">
                                        Siaran langsung pertandingan seru komunitas di layar bioskop
                                    </p>
                                </div>
                                <Link href="/nobar" className="shrink-0 text-xs sm:text-sm font-bold text-amber-700 dark:text-amber-400 hover:underline">
                                    Semua acara &gt;
                                </Link>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                                {upcomingNobar.map((nobar) => {
                                    const poster = nobar.poster_url || (nobar.poster_path ? (nobar.poster_path.startsWith('http') ? nobar.poster_path : `/storage/${nobar.poster_path}`) : null);
                                    const kickoffDate = new Date(nobar.kickoff_at).toLocaleDateString('id-ID', {
                                        weekday: 'short',
                                        day: 'numeric',
                                        month: 'short',
                                    });
                                    const kickoffTime = new Date(nobar.kickoff_at).toLocaleTimeString('id-ID', {
                                        hour: '2-digit',
                                        minute: '2-digit',
                                    });

                                    return (
                                        <Link
                                            key={nobar.id}
                                            href={`/nobar/${nobar.slug}`}
                                            className="group relative flex flex-col rounded-2xl overflow-hidden border border-border bg-surface shadow-2xs hover:border-amber-500 transition-colors"
                                        >
                                            <div className="relative w-full aspect-[2/1] overflow-hidden bg-surface-2">
                                                {poster ? (
                                                    <img
                                                        src={poster}
                                                        alt={nobar.judul}
                                                        loading="lazy"
                                                        className="size-full object-cover group-hover:scale-102 transition-transform duration-200 select-none"
                                                    />
                                                ) : (
                                                    <div className="flex size-full flex-col items-center justify-center p-4 text-center bg-surface-sunken">
                                                        <span className="text-xs font-bold text-text-muted uppercase">Nobar SynemaX</span>
                                                    </div>
                                                )}
                                                <div className="absolute top-2.5 left-2.5 z-10">
                                                    <span className="inline-flex items-center gap-1 rounded-md bg-black/80 px-2 py-0.5 text-xs font-black text-white border border-white/20">
                                                        {kickoffDate} · {kickoffTime} WIB
                                                    </span>
                                                </div>
                                            </div>

                                            <div className="p-3.5 sm:p-4 flex flex-col flex-1 justify-between gap-2">
                                                <div>
                                                    <h3 className="font-bold text-sm sm:text-base text-text group-hover:text-amber-500 transition-colors line-clamp-2">
                                                        {nobar.judul}
                                                    </h3>
                                                    <p className="text-xs text-text-muted mt-0.5">
                                                        {nobar.vs_title ? `${nobar.vs_title} · ` : ''}<span className="font-semibold text-text">{nobar.kompetisi}</span>
                                                    </p>
                                                </div>

                                                <div className="pt-2 border-t border-border flex items-center justify-between text-xs">
                                                    <span className="text-text-muted">Studio 1</span>
                                                    <span className="font-bold text-amber-600 dark:text-amber-400">
                                                        {nobar.is_free ? 'Gratis' : (nobar.harga_info || 'Tiket Tersedia')}
                                                    </span>
                                                </div>
                                            </div>
                                        </Link>
                                    );
                                })}
                            </div>
                        </section>
                    </>
                )}
            </div>
        </PublicLayout>
    );
}

