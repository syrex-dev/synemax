@props([
    'size' => 'default',
])

@php
    $sizeClasses = match ($size) {
        'sm' => 'text-lg',
        'lg' => 'text-2xl sm:text-3xl',
        default => 'text-xl sm:text-2xl',
    };
@endphp

<span {{ $attributes->merge(['class' => 'font-black tracking-wider select-none inline-flex items-center gap-2 ' . $sizeClasses]) }}>
    <span class="flex size-7 sm:size-8 items-center justify-center rounded-lg bg-gradient-to-br from-amber-400 via-amber-500 to-amber-700 text-[#07090E] shadow-[0_0_12px_rgba(212,175,55,0.4)]">
        <svg class="size-4 sm:size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2zm0 3v2h2V7H4zm0 4v2h2v-2H4zm0 4v2h2v-2H4zm16-8h-2v2h2V7zm0 4h-2v2h2v-2zm0 4h-2v2h2v-2zm-12-8h8v10H8V7z"/>
        </svg>
    </span>
    <span class="flex flex-col">
        <span class="bg-gradient-to-r from-amber-200 via-amber-400 to-amber-500 bg-clip-text font-black text-transparent tracking-widest uppercase">
            SynemaX
        </span>
        <span class="text-[9px] -mt-1 font-bold tracking-[0.25em] text-amber-500/80 uppercase">
            Premiere Cinema
        </span>
    </span>
</span>
