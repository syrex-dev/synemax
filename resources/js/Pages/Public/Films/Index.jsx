import { useState, useRef, useEffect } from 'react';
import { Link, Head, router } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import Pagination from '../../../Components/Pagination';
import { FilmCardSkeleton } from '../../../Components/Skeleton';
import {
    Search,
    X,
    Calendar,
    ChevronDown,
    RotateCcw,
    Film as FilmIcon,
    Clock,
    Loader2,
} from 'lucide-react';

export default function Index({
    films,
    genres = [],
    years = [],
    selectedGenre = '',
    selectedYear = '',
    searchQuery = '',
}) {
    const [search, setSearch] = useState(searchQuery || '');
    const [genre, setGenre] = useState(selectedGenre || '');
    const [year, setYear] = useState(selectedYear || '');
    const [yearDropdownOpen, setYearDropdownOpen] = useState(false);
    const [isFiltering, setIsFiltering] = useState(false);
    const yearDropdownRef = useRef(null);
    const debounceTimer = useRef(null);

    // Sync search state if searchQuery prop updates from outside (e.g. browser back/forward)
    useEffect(() => {
        setSearch(searchQuery || '');
    }, [searchQuery]);

    // Clean up debounce timer on component unmount
    useEffect(() => {
        return () => {
            if (debounceTimer.current) {
                clearTimeout(debounceTimer.current);
            }
        };
    }, []);

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

    const triggerFilter = (newParams) => {
        const params = {
            cari: newParams.search !== undefined ? newParams.search : search,
            genre: newParams.genre !== undefined ? newParams.genre : genre,
            tahun: newParams.year !== undefined ? newParams.year : year,
        };

        // Filter out empty params
        const cleanParams = Object.fromEntries(
            Object.entries(params).filter(([_, v]) => v !== '' && v !== null && v !== undefined)
        );

        setIsFiltering(true);
        router.get('/film', cleanParams, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['films', 'searchQuery', 'selectedGenre', 'selectedYear'],
            onFinish: () => setIsFiltering(false),
            onCancel: () => setIsFiltering(false),
            onError: () => setIsFiltering(false),
        });
    };

    const handleSearchChange = (value) => {
        setSearch(value);

        if (debounceTimer.current) {
            clearTimeout(debounceTimer.current);
        }

        debounceTimer.current = setTimeout(() => {
            triggerFilter({ search: value });
        }, 300);
    };

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        if (debounceTimer.current) {
            clearTimeout(debounceTimer.current);
        }
        triggerFilter({ search });
    };

    const handleClearSearch = () => {
        if (debounceTimer.current) {
            clearTimeout(debounceTimer.current);
        }
        setSearch('');
        triggerFilter({ search: '' });
    };

    const handleSelectGenre = (g) => {
        const next = genre === g ? '' : g;
        setGenre(next);
        triggerFilter({ genre: next });
    };

    const handleSelectYear = (y) => {
        const next = year === String(y) ? '' : String(y);
        setYear(next);
        setYearDropdownOpen(false);
        triggerFilter({ year: next });
    };

    const handleResetAll = () => {
        if (debounceTimer.current) {
            clearTimeout(debounceTimer.current);
        }
        setSearch('');
        setGenre('');
        setYear('');
        setYearDropdownOpen(false);
        setIsFiltering(true);
        router.get('/film', {}, {
            replace: true,
            preserveState: true,
            preserveScroll: true,
            only: ['films', 'searchQuery', 'selectedGenre', 'selectedYear'],
            onFinish: () => setIsFiltering(false),
            onCancel: () => setIsFiltering(false),
            onError: () => setIsFiltering(false),
        });
    };

    const hasActiveFilter = Boolean(genre || year || search);
    const filmItems = films?.data || [];

    return (
        <PublicLayout title="Katalog Film">
            <Head title="Katalog Film">
                <meta
                    name="description"
                    content="Jelajahi koleksi film SynemaX. Pilih film favoritmu berdasarkan genre dan tahun rilis untuk diputar privat di studio mini."
                />
            </Head>

            <div className="mx-auto max-w-6xl px-4 py-8 sm:px-6 space-y-8">
                {/* Heading */}
                <div className="space-y-1">
                    <h1 className="text-3xl sm:text-4xl font-black text-text tracking-tight">Katalog Film</h1>
                    <p className="text-sm text-text-muted">
                        Pilih film favoritmu untuk diputar di studio bioskop mini privat SynemaX.
                    </p>
                </div>

                {/* Filter and Search Bar */}
                <div className="space-y-4">
                    {/* Search Bar */}
                    <form onSubmit={handleSearchSubmit} className="relative w-full">
                        {isFiltering ? (
                            <Loader2 className="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-amber-500 animate-spin" />
                        ) : (
                            <Search className="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-text-muted" />
                        )}
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => handleSearchChange(e.target.value)}
                            placeholder="Cari judul film favoritmu..."
                            className="w-full rounded-2xl border border-border bg-surface pl-10 pr-10 py-2.5 text-xs sm:text-sm font-medium text-text placeholder:text-text-muted focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-xs transition"
                        />
                        {search && (
                            <button
                                type="button"
                                onClick={handleClearSearch}
                                className="absolute right-3.5 top-1/2 -translate-y-1/2 text-text-muted hover:text-text p-1 transition hover:scale-110"
                                title="Hapus pencarian"
                                aria-label="Hapus kata kunci pencarian"
                            >
                                <X className="size-4" />
                            </button>
                        )}
                    </form>

                    {/* Genre and Year Filter Bar */}
                    <div className="relative z-30 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 p-3 sm:p-4 rounded-2xl border border-border/80 bg-surface/80 dark:bg-black/20 backdrop-blur-sm">
                        {/* Genre Pills */}
                        <div className="flex items-center gap-1.5 overflow-x-auto pt-1 pb-2.5 sm:pb-3 custom-scrollbar flex-1">
                            <button
                                type="button"
                                onClick={() => handleSelectGenre('')}
                                className={`px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition shadow-xs ${
                                    genre === ''
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
                                    className={`px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition shadow-xs ${
                                        genre === g
                                            ? 'bg-amber-500 text-slate-950 font-black shadow-amber-500/20'
                                            : 'border border-border bg-surface text-text hover:bg-surface-2'
                                    }`}
                                >
                                    {g}
                                </button>
                            ))}
                        </div>

                        {/* Year Selector & Reset */}
                        <div className="flex items-center gap-2 shrink-0">
                            <div ref={yearDropdownRef} className="relative z-30">
                                <button
                                    type="button"
                                    onClick={() => setYearDropdownOpen(!yearDropdownOpen)}
                                    className={`flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-bold border transition ${
                                        year
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
                                            className={`w-full text-left px-3 py-2 rounded-xl text-xs font-semibold transition ${
                                                year === '' ? 'bg-amber-500 text-slate-950 font-black' : 'hover:bg-surface-raised text-text'
                                            }`}
                                        >
                                            Semua Tahun
                                        </button>
                                        {years.map((y) => (
                                            <button
                                                key={y}
                                                type="button"
                                                onClick={() => handleSelectYear(y)}
                                                className={`w-full text-left px-3 py-2 rounded-xl text-xs font-semibold transition ${
                                                    year === String(y)
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

                            {hasActiveFilter && (
                                <button
                                    type="button"
                                    onClick={handleResetAll}
                                    className="flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold border border-red-500/30 bg-red-500/10 text-red-600 dark:text-red-400 hover:bg-red-500/20 transition"
                                >
                                    <RotateCcw className="size-3" />
                                    <span>Reset</span>
                                </button>
                            )}
                        </div>
                    </div>
                </div>

                {/* Counter & Film Grid */}
                <div className="space-y-6">
                    <div className="flex items-center justify-between text-xs font-semibold text-text-muted px-1">
                        <span>Menampilkan <strong className="text-text">{films?.total || filmItems.length}</strong> film</span>
                        {films?.last_page > 1 && (
                            <span>Halaman <strong className="text-text">{films.current_page}</strong> dari <strong className="text-text">{films.last_page}</strong></span>
                        )}
                    </div>

                    <div className={`grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-5 gap-4 sm:gap-6 transition-opacity duration-200 ${isFiltering ? 'opacity-70' : 'opacity-100'}`}>
                        {filmItems.length > 0 ? (
                            filmItems.map((film) => {
                            const poster = film.poster_url || (film.poster_path ? (film.poster_path.startsWith('http') ? film.poster_path : `/storage/${film.poster_path}`) : null);
                            const filmUrl = `/film/${film.slug || film.id}`;

                            return (
                                <Link
                                    key={film.id}
                                    href={filmUrl}
                                    className="group relative flex flex-col rounded-2xl overflow-hidden border border-border/80 bg-surface shadow-xs hover:shadow-xl hover:border-amber-500 transition-all duration-200 cursor-pointer select-none"
                                >
                                    {/* Poster 2:3 */}
                                    <div className="relative w-full aspect-[2/3] overflow-hidden bg-slate-900 pointer-events-none select-none">
                                        {poster ? (
                                            <img
                                                src={poster}
                                                alt={film.title}
                                                loading="lazy"
                                                className="size-full object-cover transition-transform duration-300 group-hover:scale-105 pointer-events-none select-none"
                                            />
                                        ) : (
                                            <div className="flex size-full flex-col items-center justify-center bg-slate-900 border border-white/10 p-3 text-center">
                                                <FilmIcon className="size-8 text-amber-500/60" />
                                                <span className="mt-2 text-xs font-black text-amber-200 line-clamp-2">{film.title}</span>
                                            </div>
                                        )}

                                        {film.age_rating && (
                                            <span className="absolute top-2 right-2 rounded-md bg-black/80 px-1.5 py-0.5 text-[10px] font-black uppercase text-amber-400 backdrop-blur-md border border-white/15">
                                                {film.age_rating}
                                            </span>
                                        )}

                                        {film.custom_badge && (
                                            <div className="absolute bottom-2 inset-x-0 flex justify-center px-2 z-10 pointer-events-none">
                                                <span className="inline-flex items-center justify-center bg-[#E50914] text-white font-black text-[10px] sm:text-xs px-2.5 py-0.5 rounded shadow-md truncate max-w-full border border-white/20">
                                                    {film.custom_badge}
                                                </span>
                                            </div>
                                        )}
                                    </div>

                                    {/* Info */}
                                    <div className="flex flex-1 flex-col p-3 sm:p-3.5 justify-between pointer-events-none select-none">
                                        <div>
                                            <h3 className="text-sm sm:text-base font-black text-text line-clamp-1 group-hover:text-amber-500 transition">
                                                {film.title}
                                            </h3>
                                            <div className="mt-1 flex items-center justify-between gap-1 text-xs font-semibold text-text-muted">
                                                <span className="truncate">{film.genre || '-'}</span>
                                                {film.release_year && (
                                                    <span className="shrink-0">{film.release_year}</span>
                                                )}
                                            </div>
                                        </div>

                                        {film.duration_minutes && (
                                            <div className="mt-2 pt-2 border-t border-border/60 flex items-center gap-1 text-xs text-text-muted">
                                                <Clock className="size-3 text-amber-500" />
                                                <span>{film.duration_minutes} menit</span>
                                            </div>
                                        )}
                                    </div>
                                </Link>
                            );
                        })
                        ) : isFiltering ? (
                            <FilmCardSkeleton count={10} />
                        ) : null}
                    </div>

                    {/* Empty State */}
                    {filmItems.length === 0 && !isFiltering && (
                        <div className="rounded-2xl border border-dashed border-border bg-surface p-12 text-center">
                            <FilmIcon className="mx-auto size-12 text-text-muted" />
                            <h3 className="mt-3 text-base font-bold text-text">Belum ada film untuk filter ini</h3>
                            <p className="mt-1 text-xs text-text-muted">Coba ubah kata kunci pencarian, genre, atau tahun rilis.</p>
                            <button
                                type="button"
                                onClick={handleResetAll}
                                className="mt-4 inline-flex items-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 px-4 py-2 text-xs font-bold transition shadow-xs"
                            >
                                Reset Semua Filter
                            </button>
                        </div>
                    )}

                    {/* Clean Pagination */}
                    {films && (
                        <div className="mt-8 pt-4 border-t border-border/80 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <span className="text-xs font-semibold text-text-muted">
                                Menampilkan {films.from || 0}–{films.to || 0} dari {films.total || 0} film
                            </span>
                            <Pagination pagination={films} />
                        </div>
                    )}
                </div>
            </div>
        </PublicLayout>
    );
}

