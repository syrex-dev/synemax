@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex min-h-11 items-center border-b-2 border-accent px-1 pt-1 text-sm font-semibold leading-5 text-accent transition duration-150 ease-in-out focus-visible:outline-2 focus-visible:outline-accent'
            : 'inline-flex min-h-11 items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium leading-5 text-text-muted transition duration-150 ease-in-out hover:border-border hover:text-text focus-visible:outline-2 focus-visible:outline-accent';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
