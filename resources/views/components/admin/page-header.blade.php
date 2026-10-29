@props([
    'eyebrow' => null,
    'title',
    'subtitle' => null,
])

<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between pb-6 border-b border-border">
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-600 dark:text-amber-400">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-1 text-2xl sm:text-3xl font-black text-text tracking-tight truncate">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-xs sm:text-sm text-text-muted">{{ $subtitle }}</p>
        @endif
    </div>
    @if ($slot->isNotEmpty())
        <div class="flex shrink-0 flex-wrap items-center gap-2.5">
            {{ $slot }}
        </div>
    @endif
</div>
