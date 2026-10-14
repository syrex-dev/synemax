@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'text-base font-semibold text-success']) }}>
        {{ $status }}
    </div>
@endif
