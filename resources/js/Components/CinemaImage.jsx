import { useState } from 'react';

/**
 * CinemaImage component replicating Blade's x-ui.image.
 * Handles loading states, network errors, broken links, and beautiful gradient fallback
 * so that raw alt text or broken image icons NEVER leak into the UI.
 */
export default function CinemaImage({
    src,
    alt = '',
    title,
    badge,
    aspect = 'aspect-[16/9]',
    rounded = 'rounded-2xl',
    className = '',
    imgClass = 'size-full object-cover transition duration-500 group-hover:scale-105',
    loading = 'lazy',
    width,
    height,
}) {
    const hasInitialSrc = Boolean(src && src.trim() !== '');
    const [failed, setFailed] = useState(!hasInitialSrc);
    const [loaded, setLoaded] = useState(false);

    const displayTitle = title || alt || 'SynemaX';

    return (
        <div
            className={`relative overflow-hidden bg-slate-100 dark:bg-black/60 border border-border dark:border-white/5 ${aspect} ${rounded} ${className}`}
        >
            {hasInitialSrc && !failed && (
                <img
                    src={src}
                    alt={alt}
                    width={width}
                    height={height}
                    loading={loading}
                    onLoad={(e) => {
                        if (e.target && e.target.naturalWidth > 0) {
                            setLoaded(true);
                        } else {
                            setFailed(true);
                        }
                    }}
                    onError={() => setFailed(true)}
                    className={`${imgClass} ${rounded} ${loaded ? 'opacity-100' : 'opacity-0'} transition-opacity duration-300`}
                />
            )}

            {/* Gradient Fallback when image fails or src is empty */}
            {failed && (
                <div
                    className={`absolute inset-0 flex flex-col justify-end p-4 bg-gradient-to-br from-slate-900 via-[#0C111D] to-[#1E1408] text-white select-none pointer-events-none ${rounded}`}
                    aria-hidden="true"
                >
                    <div className="absolute -right-6 -top-6 size-28 rounded-full bg-amber-500/20 blur-2xl" />
                    <div className="relative z-10">
                        {badge && (
                            <span className="inline-block px-2 py-0.5 rounded text-[10px] font-black tracking-wider uppercase bg-amber-500/20 text-amber-300 border border-amber-500/30 mb-1.5 shadow-xs">
                                {badge}
                            </span>
                        )}
                        <p className="text-xs sm:text-sm font-bold text-white line-clamp-2 leading-tight drop-shadow-xs">
                            {displayTitle}
                        </p>
                    </div>
                </div>
            )}
        </div>
    );
}

