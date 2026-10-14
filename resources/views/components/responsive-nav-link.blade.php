@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block min-h-12 w-full border-l-4 border-accent bg-accent/10 py-3 pe-4 ps-3 text-start text-base font-semibold text-accent transition duration-150 ease-in-out focus-visible:outline-2 focus-visible:outline-accent'
            : 'block min-h-12 w-full border-l-4 border-transparent py-3 pe-4 ps-3 text-start text-base font-medium text-text-muted transition duration-150 ease-in-out hover:border-border hover:bg-surface-2 hover:text-text focus-visible:outline-2 focus-visible:outline-accent';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
