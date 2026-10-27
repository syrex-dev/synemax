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

<span {{ $attributes->merge(['class' => 'font-extrabold tracking-tight text-text select-none inline-flex items-center ' . $sizeClasses]) }}>
    SynemaX
</span>
