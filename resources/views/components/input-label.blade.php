@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-base font-semibold text-text']) }}>
    {{ $value ?? $slot }}
</label>
