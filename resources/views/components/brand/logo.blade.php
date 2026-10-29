@props([
    'size' => 'default',
    'logoLight' => null,
    'logoDark' => null,
    'logoUrl' => null,
    'siteName' => null,
])

@php
    $sizeClasses = match ($size) {
        'sm' => 'text-lg',
        'lg' => 'text-2xl sm:text-3xl',
        default => 'text-xl sm:text-2xl',
    };
    $imgSizeClasses = match ($size) {
        'sm' => 'h-7 sm:h-8 max-w-[150px]',
        'lg' => 'h-10 sm:h-12 max-w-[220px]',
        default => 'h-8 sm:h-9 max-w-[180px]',
    };
    $pillImgSizeClasses = match ($size) {
        'sm' => 'h-6 sm:h-7 max-w-[140px]',
        'lg' => 'h-9 sm:h-11 max-w-[200px]',
        default => 'h-7 sm:h-8 max-w-[170px]',
    };

    $rawLight = $logoLight ?? $logoUrl ?? (function_exists('setting') ? setting('site_logo') : null);
    $rawDark = $logoDark ?? (function_exists('setting') ? setting('site_logo_dark') : null);

    $urlLight = $rawLight ? (str_starts_with($rawLight, '/') || str_starts_with($rawLight, 'http') ? $rawLight : \Illuminate\Support\Facades\Storage::url($rawLight)) : null;
    $urlDark = $rawDark ? (str_starts_with($rawDark, '/') || str_starts_with($rawDark, 'http') ? $rawDark : \Illuminate\Support\Facades\Storage::url($rawDark)) : null;

    $brandText = $siteName ?? (function_exists('setting') ? setting('site_name', 'SynemaX') : 'SynemaX');
    $brandDisplay = function_exists('setting') ? setting('navbar_brand_display', 'logo_text') : 'logo_text';

    $hasImage = ($urlLight || $urlDark) && $brandDisplay !== 'text_only';
    $showText = $brandDisplay !== 'logo_only';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 select-none']) }}>
    @if ($hasImage)
        @if ($urlLight && $urlDark)
            <img src="{{ $urlLight }}" alt="{{ $brandText }}" class="logo-light {{ $imgSizeClasses }} w-auto object-contain">
            <img src="{{ $urlDark }}" alt="{{ $brandText }}" class="logo-dark {{ $imgSizeClasses }} w-auto object-contain">
        @elseif ($urlLight)
            <img src="{{ $urlLight }}" alt="{{ $brandText }}" class="logo-light {{ $imgSizeClasses }} w-auto object-contain">
            <span class="logo-dark inline-flex items-center px-2 py-0.5 rounded-full bg-white/95 shadow-sm border border-white/20">
                <img src="{{ $urlLight }}" alt="{{ $brandText }}" class="{{ $pillImgSizeClasses }} w-auto object-contain">
            </span>
        @else
            <span class="logo-light inline-flex items-center px-2 py-0.5 rounded-full bg-slate-900/90 shadow-sm border border-slate-700/50">
                <img src="{{ $urlDark }}" alt="{{ $brandText }}" class="{{ $pillImgSizeClasses }} w-auto object-contain">
            </span>
            <img src="{{ $urlDark }}" alt="{{ $brandText }}" class="logo-dark {{ $imgSizeClasses }} w-auto object-contain">
        @endif
    @elseif ($brandDisplay !== 'text_only')
        <span class="flex size-7 sm:size-8 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 via-amber-600 to-amber-700 text-white shadow-[0_0_12px_rgba(212,175,55,0.4)]">
            <svg class="size-4 sm:size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2zm0 3v2h2V7H4zm0 4v2h2v-2H4zm0 4v2h2v-2H4zm16-8h-2v2h2V7zm0 4h-2v2h2v-2zm0 4h-2v2h2v-2zm-12-8h8v10H8V7z"/>
            </svg>
        </span>
    @endif

    @if ($showText)
        <span class="flex flex-col">
            <span class="font-black tracking-widest uppercase text-slate-900 dark:text-transparent dark:bg-gradient-to-r dark:from-amber-200 dark:via-amber-400 dark:to-amber-500 dark:bg-clip-text {{ $sizeClasses }}">
                {{ $brandText }}
            </span>
            <span class="text-[9px] -mt-1 font-bold tracking-[0.25em] text-amber-700 dark:text-amber-400 uppercase">
                Premiere Cinema
            </span>
        </span>
    @endif
</span>
