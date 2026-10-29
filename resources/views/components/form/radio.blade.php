@props([
    'name',
    'value',
    'checked' => false,
    'id' => null,
    'disabled' => false,
    'label' => null,
    'hint' => null,
])

@php
    $inputId = $id ?? ($name . '_' . \Illuminate\Support\Str::slug($value));
@endphp

<label for="{{ $inputId }}" class="group relative inline-flex min-h-[44px] cursor-pointer items-center gap-3 select-none {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}">
    <span class="relative flex size-5 shrink-0 items-center justify-center">
        <input
            type="radio"
            name="{{ $name }}"
            value="{{ $value }}"
            id="{{ $inputId }}"
            {{ $checked ? 'checked' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $attributes->merge(['class' => 'peer sr-only']) }}
        >
        {{-- Custom Radio Ring with WCAG 3:1 contrast --}}
        <span class="size-5 rounded-full border-2 border-slate-500 bg-white transition-all duration-150 peer-focus-visible:ring-2 peer-focus-visible:ring-amber-500 peer-focus-visible:ring-offset-2 peer-checked:border-amber-600 dark:border-slate-400 dark:bg-slate-900 dark:peer-checked:border-amber-400 group-hover:border-amber-500"></span>
        {{-- Custom Radio Inner Dot --}}
        <span class="pointer-events-none absolute size-2.5 rounded-full bg-amber-600 dark:bg-amber-400 opacity-0 transition-opacity duration-150 peer-checked:opacity-100"></span>
    </span>
    @if ($label || $slot->isNotEmpty())
        <span class="text-sm font-medium text-text">
            {{ $label ?? $slot }}
            @if ($hint)
                <span class="block text-xs font-normal text-text-muted">{{ $hint }}</span>
            @endif
        </span>
    @endif
</label>
