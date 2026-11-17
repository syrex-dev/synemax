export function FilmCardSkeleton({ count = 8 }) {
    return (
        <>
            {Array.from({ length: count }).map((_, idx) => (
                <div
                    key={idx}
                    className="flex flex-col rounded-2xl overflow-hidden border border-border/80 bg-surface shadow-2xs animate-pulse"
                >
                    {/* Poster 2:3 */}
                    <div className="relative w-full aspect-[2/3] bg-surface-2 dark:bg-white/5" />
                    {/* Meta info */}
                    <div className="p-2.5 sm:p-3.5 space-y-2">
                        <div className="h-4 bg-surface-2 dark:bg-white/10 rounded-md w-4/5" />
                        <div className="flex items-center justify-between pt-1 border-t border-border/50">
                            <div className="h-3 bg-surface-2 dark:bg-white/5 rounded-md w-1/3" />
                            <div className="h-3 bg-surface-2 dark:bg-white/5 rounded-md w-1/4" />
                        </div>
                    </div>
                </div>
            ))}
        </>
    );
}

export function NobarCardSkeleton({ count = 3 }) {
    return (
        <>
            {Array.from({ length: count }).map((_, idx) => (
                <div
                    key={idx}
                    className="flex flex-col rounded-2xl overflow-hidden border border-border/80 bg-surface shadow-2xs animate-pulse p-4 space-y-4"
                >
                    <div className="relative w-full aspect-[4/5] bg-surface-2 dark:bg-white/5 rounded-xl" />
                    <div className="space-y-2">
                        <div className="h-4 bg-surface-2 dark:bg-white/10 rounded-md w-3/4" />
                        <div className="h-3 bg-surface-2 dark:bg-white/5 rounded-md w-1/2" />
                    </div>
                    <div className="h-10 bg-surface-2 dark:bg-white/10 rounded-xl w-full" />
                </div>
            ))}
        </>
    );
}

export function PageSkeleton() {
    return (
        <div className="space-y-8 animate-pulse py-4">
            {/* Hero banner skeleton */}
            <div className="w-full aspect-[16/7] sm:aspect-[21/9] rounded-2xl sm:rounded-3xl bg-surface-2 dark:bg-white/5" />

            {/* Chips skeleton */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div className="h-14 rounded-2xl bg-surface-2 dark:bg-white/5" />
                <div className="h-14 rounded-2xl bg-surface-2 dark:bg-white/5" />
                <div className="h-14 rounded-2xl bg-surface-2 dark:bg-white/5" />
            </div>

            {/* Filter pills skeleton */}
            <div className="flex gap-2 overflow-x-auto py-1">
                {Array.from({ length: 6 }).map((_, idx) => (
                    <div key={idx} className="h-8 w-20 rounded-xl bg-surface-2 dark:bg-white/5 shrink-0" />
                ))}
            </div>

            {/* Film grid skeleton */}
            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 sm:gap-6">
                <FilmCardSkeleton count={8} />
            </div>
        </div>
    );
}

