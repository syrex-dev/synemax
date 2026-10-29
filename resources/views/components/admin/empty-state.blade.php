@props([
    'title' => 'Belum ada data',
    'description' => 'Data untuk tampilan ini masih kosong.',
    'actionUrl' => null,
    'actionLabel' => 'Tambah Data',
])

<div class="flex flex-col items-center justify-center p-12 text-center">
    <div class="flex size-14 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-500 mb-4">
        <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
    </div>
    <h3 class="text-base font-bold text-text">{{ $title }}</h3>
    <p class="mt-1 max-w-sm text-xs text-text-muted">{{ $description }}</p>
    @if ($actionUrl)
        <a href="{{ $actionUrl }}" class="mt-5 inline-flex min-h-[40px] items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition-colors shadow-xs">
            <span>+</span>
            <span>{{ $actionLabel }}</span>
        </a>
    @endif
</div>
