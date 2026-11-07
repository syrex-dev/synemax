@props([
    'badge' => null,
    'size' => 'md', // xs, sm, md, lg
])

@if (filled($badge))
    @php
        $sizeClasses = match ($size) {
            'xs' => 'text-[8px] px-1.5 py-0.5 rounded-xs tracking-normal leading-tight',
            'sm' => 'text-[9px] px-2 py-0.5 rounded tracking-wide leading-tight',
            'lg' => 'text-xs sm:text-sm px-3.5 py-1 rounded-md tracking-wider shadow-lg',
            default => 'text-[10px] sm:text-xs px-2.5 py-0.5 rounded shadow-md tracking-wide',
        };
        $containerBottom = match ($size) {
            'xs' => 'bottom-1 px-1',
            'sm' => 'bottom-1.5 px-1.5',
            'lg' => 'bottom-2.5 px-3',
            default => 'bottom-2 px-2',
        };
    @endphp
    <div class="absolute {{ $containerBottom }} inset-x-0 flex justify-center z-10 pointer-events-none">
        <span class="inline-flex items-center justify-center bg-[#E50914] text-white font-black {{ $sizeClasses }} text-center truncate max-w-full drop-shadow-md select-none border border-white/20">
            {{ $badge }}
        </span>
    </div>
@endif

