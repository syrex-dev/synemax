import { usePage } from '@inertiajs/react';

export default function BrandLogo({
    size = 'default',
    logoLight = null,
    logoDark = null,
    logoUrl = null,
    siteName = null,
    forcedTheme = null,
    className = '',
}) {
    const { cinemaSettings } = usePage().props;

    const brandText = siteName || cinemaSettings?.siteName || 'SynemaX';
    const brandDisplay = cinemaSettings?.navbarBrandDisplay || 'logo_text';

    const rawLight = logoLight || logoUrl || cinemaSettings?.siteLogo;
    const rawDark = logoDark || cinemaSettings?.siteLogoDark;

    const formatUrl = (path) => {
        if (!path) return null;
        if (path.startsWith('/') || path.startsWith('http')) return path;
        return `/storage/${path}`;
    };

    const urlLight = formatUrl(rawLight);
    const urlDark = formatUrl(rawDark);

    const sizeClasses = {
        sm: 'text-lg',
        lg: 'text-2xl sm:text-3xl',
        default: 'text-xl sm:text-2xl',
    }[size] || 'text-xl sm:text-2xl';

    const imgSizeClasses = {
        sm: 'h-7 sm:h-8 max-w-[150px]',
        lg: 'h-10 sm:h-12 max-w-[220px]',
        default: 'h-8 sm:h-9 max-w-[180px]',
    }[size] || 'h-8 sm:h-9 max-w-[180px]';

    const pillImgSizeClasses = {
        sm: 'h-6 sm:h-7 max-w-[140px]',
        lg: 'h-9 sm:h-11 max-w-[200px]',
        default: 'h-7 sm:h-8 max-w-[170px]',
    }[size] || 'h-7 sm:h-8 max-w-[170px]';

    const hasImage = (urlLight || urlDark) && brandDisplay !== 'text_only';
    const showText = brandDisplay !== 'logo_only';

    if (forcedTheme === 'light') {
        return (
            <span className={`inline-flex items-center gap-2.5 select-none ${className}`}>
                {hasImage ? (
                    urlLight ? (
                        <img src={urlLight} alt={brandText} className={`${imgSizeClasses} w-auto object-contain`} />
                    ) : (
                        <span className="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-900/90 shadow-sm border border-slate-700/50">
                            <img src={urlDark} alt={brandText} className={`${pillImgSizeClasses} w-auto object-contain`} />
                        </span>
                    )
                ) : brandDisplay !== 'text_only' ? (
                    <span className="flex size-7 sm:size-8 items-center justify-center rounded-lg bg-amber-600 text-white shadow-xs">
                        <svg className="size-4 sm:size-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2zm0 3v2h2V7H4zm0 4v2h2v-2H4zm0 4v2h2v-2H4zm16-8h-2v2h2V7zm0 4h-2v2h2v-2zm0 4h-2v2h2v-2zm-12-8h8v10H8V7z" />
                        </svg>
                    </span>
                ) : null}

                {showText && (
                    <span className="flex flex-col">
                        <span className={`font-black tracking-widest uppercase text-slate-900 ${sizeClasses}`}>
                            {brandText}
                        </span>
                        <span className="text-[9px] -mt-1 font-bold tracking-[0.25em] text-amber-700 uppercase">
                            Premiere Cinema
                        </span>
                    </span>
                )}
            </span>
        );
    }

    return (
        <span className={`inline-flex items-center gap-2.5 select-none ${className}`}>
            {hasImage ? (
                <>
                    {urlLight && (
                        <img
                            src={urlLight}
                            alt={brandText}
                            className={`logo-light ${imgSizeClasses} w-auto object-contain`}
                        />
                    )}
                    {urlDark ? (
                        <img
                            src={urlDark}
                            alt={brandText}
                            className={`logo-dark ${imgSizeClasses} w-auto object-contain`}
                        />
                    ) : urlLight ? (
                        <span className="logo-dark inline-flex items-center px-2 py-0.5 rounded-full bg-white/95 shadow-sm border border-white/20">
                            <img src={urlLight} alt={brandText} className={`${pillImgSizeClasses} w-auto object-contain`} />
                        </span>
                    ) : null}
                </>
            ) : brandDisplay !== 'text_only' ? (
                <span className="flex size-7 sm:size-8 items-center justify-center rounded-lg bg-amber-600 text-white shadow-xs">
                    <svg className="size-4 sm:size-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2zm0 3v2h2V7H4zm0 4v2h2v-2H4zm0 4v2h2v-2H4zm16-8h-2v2h2V7zm0 4h-2v2h2v-2zm0 4h-2v2h2v-2zm-12-8h8v10H8V7z" />
                    </svg>
                </span>
            ) : null}

            {showText && (
                <span className="flex flex-col">
                    <span className={`font-black tracking-widest uppercase text-slate-900 dark:text-transparent dark:bg-gradient-to-r dark:from-amber-200 dark:via-amber-400 dark:to-amber-500 dark:bg-clip-text ${sizeClasses}`}>
                        {brandText}
                    </span>
                    <span className="text-[9px] -mt-1 font-bold tracking-[0.25em] text-amber-700 dark:text-amber-400 uppercase">
                        Premiere Cinema
                    </span>
                </span>
            )}
        </span>
    );
}
