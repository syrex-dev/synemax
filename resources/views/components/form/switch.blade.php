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
    $inputId = $id ?? ($name ? 'switch_' . $name : 'switch_' . \Illuminate\Support\Str::random(8));
@endphp

<label for="{{ $inputId }}" class="group relative inline-flex min-h-[44px] cursor-pointer items-center justify-between gap-3 select-none {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}">
    @if ($label || $slot->isNotEmpty())
        <span class="text-sm font-medium text-text">
            {{ $label ?? $slot }}
            @if ($hint)
                <span class="block text-xs font-normal text-text-muted">{{ $hint }}</span>
            @endif
        </span>
    @endif
    <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
        <input
            type="checkbox"
            name="{{ $name }}"
            value="{{ $value }}"
            id="{{ $inputId }}"
            {{ $checked ? 'checked' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $attributes->merge(['class' => 'peer sr-only']) }}
        >
        {{-- Track with high contrast border --}}
        <span class="h-6 w-11 rounded-full border-2 border-slate-500 bg-slate-200 transition-colors duration-200 peer-focus-visible:ring-2 peer-focus-visible:ring-amber-500 peer-focus-visible:ring-offset-2 peer-checked:border-amber-600 peer-checked:bg-amber-500 dark:border-slate-400 dark:bg-slate-800 dark:peer-checked:border-amber-400 dark:peer-checked:bg-amber-500"></span>
        {{-- Thumb --}}
        <span class="absolute left-1 size-4 rounded-full bg-white shadow transition-transform duration-200 peer-checked:translate-x-5 peer-checked:bg-slate-950 dark:bg-slate-100"></span>
    </span>
</label>
