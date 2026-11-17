import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

export default function Pagination({ pagination, className = '' }) {
    if (!pagination || pagination.last_page <= 1) {
        return null;
    }

    const { current_page, last_page, path } = pagination;

    const buildUrl = (page) => {
        if (!path) return `?page=${page}`;
        const url = new URL(path, window.location.origin);
        // keep existing query params
        const searchParams = new URLSearchParams(window.location.search);
        searchParams.set('page', page);
        return `${url.pathname}?${searchParams.toString()}`;
    };

    // Calculate a clean sliding window
    const getPageNumbers = () => {
        const pages = [];
        const total = last_page;
        const current = current_page;

        if (total <= 7) {
            for (let i = 1; i <= total; i++) {
                pages.push(i);
            }
        } else {
            if (current <= 3) {
                pages.push(1, 2, 3, 4, '...', total);
            } else if (current >= total - 2) {
                pages.push(1, '...', total - 3, total - 2, total - 1, total);
            } else {
                pages.push(1, '...', current - 1, current, current + 1, '...', total);
            }
        }
        return pages;
    };

    const pages = getPageNumbers();

    return (
        <nav aria-label="Pagination Navigation" className={`flex items-center justify-center gap-1.5 ${className}`}>
            {/* Prev Button */}
            {current_page > 1 ? (
                <Link
                    href={buildUrl(current_page - 1)}
                    preserveScroll
                    preserveState
                    className="flex size-9 sm:size-10 items-center justify-center rounded-xl border border-border bg-surface text-text hover:bg-surface-2 transition shadow-xs"
                    aria-label="Halaman sebelumnya"
                >
                    <ChevronLeft className="size-4" />
                </Link>
            ) : (
                <span className="flex size-9 sm:size-10 items-center justify-center rounded-xl border border-border/40 bg-surface/40 text-text-muted/40 cursor-not-allowed">
                    <ChevronLeft className="size-4" />
                </span>
            )}

            {/* Page numbers */}
            {pages.map((p, idx) => {
                if (p === '...') {
                    return (
                        <span
                            key={`dots-${idx}`}
                            className="flex size-9 sm:size-10 items-center justify-center text-text-muted select-none font-bold"
                        >
                            ...
                        </span>
                    );
                }

                const isActive = p === current_page;
                return (
                    <Link
                        key={p}
                        href={buildUrl(p)}
                        preserveScroll
                        preserveState
                        aria-current={isActive ? 'page' : undefined}
                        className={`flex size-9 sm:size-10 items-center justify-center rounded-xl font-bold text-sm transition shadow-xs ${
                            isActive
                                ? 'bg-amber-500 text-slate-950 font-black shadow-md shadow-amber-500/20'
                                : 'border border-border bg-surface text-text hover:bg-surface-2'
                        }`}
                    >
                        {p}
                    </Link>
                );
            })}

            {/* Next Button */}
            {current_page < last_page ? (
                <Link
                    href={buildUrl(current_page + 1)}
                    preserveScroll
                    preserveState
                    className="flex size-9 sm:size-10 items-center justify-center rounded-xl border border-border bg-surface text-text hover:bg-surface-2 transition shadow-xs"
                    aria-label="Halaman berikutnya"
                >
                    <ChevronRight className="size-4" />
                </Link>
            ) : (
                <span className="flex size-9 sm:size-10 items-center justify-center rounded-xl border border-border/40 bg-surface/40 text-text-muted/40 cursor-not-allowed">
                    <ChevronRight className="size-4" />
                </span>
            )}
        </nav>
    );
}
