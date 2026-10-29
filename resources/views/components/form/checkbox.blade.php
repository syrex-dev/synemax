@props([
    'name',
    'value' => '1',
    'checked' => false,
    'id' => null,
    'disabled' => false,
    'label' => null,
    'hint' => null,
])

@php
    $inputId = $id ?? ($name ? $name . '_' . \Illuminate\Support\Str::slug($value) : 'cb_' . \Illuminate\Support\Str::random(8));
@endphp

<label for="{{ $inputId }}" class="group relative inline-flex min-h-[44px] cursor-pointer items-center gap-3 select-none {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}">
    <span class="relative flex size-5 shrink-0 items-center justify-center">
        <input
            type="checkbox"
            name="{{ $name }}"
            value="{{ $value }}"
            id="{{ $inputId }}"
            {{ $checked ? 'checked' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $attributes->merge(['class' => 'peer sr-only']) }}
        >
        {{-- Custom Checkbox Box with WCAG 3:1 high-contrast border in both themes --}}
        <span class="size-5 rounded-md border-2 border-slate-500 bg-white transition-all duration-150 peer-focus-visible:ring-2 peer-focus-visible:ring-amber-500 peer-focus-visible:ring-offset-2 peer-checked:border-amber-600 peer-checked:bg-amber-500 dark:border-slate-400 dark:bg-slate-900 dark:peer-checked:border-amber-400 dark:peer-checked:bg-amber-400 group-hover:border-amber-500"></span>
        {{-- High-contrast Dark SVG Checkmark --}}
        <svg class="pointer-events-none absolute size-3.5 text-slate-950 opacity-0 transition-opacity duration-150 peer-checked:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
    </span>
    @if ($label || $slot->isNotEmpty())
        <span class="text-sm font-medium text-text transition-colors">
            {{ $label ?? $slot }}
            @if ($hint)
                <span class="block text-xs font-normal text-text-muted">{{ $hint }}</span>
            @endif
        </span>
    @endif
</label>
