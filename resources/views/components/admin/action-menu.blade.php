@props([
    'editUrl' => null,
    'viewUrl' => null,
    'duplicateUrl' => null,
    'deleteUrl' => null,
    'deleteMessage' => 'Apakah Anda yakin ingin menghapus data ini?',
    'itemName' => 'item',
])

<div x-data="{ open: false }" class="relative inline-flex items-center gap-1.5 justify-end">
    @if ($editUrl)
        <a href="{{ $editUrl }}" class="inline-flex min-h-[36px] items-center gap-1 rounded-lg border border-slate-400 dark:border-white/20 bg-surface px-3 py-1.5 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors shadow-xs" title="Edit {{ $itemName }}">
            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
            <span>Edit</span>
        </a>
    @endif

    @if ($viewUrl || $duplicateUrl || $deleteUrl)
        <div class="relative">
            <button
                type="button"
                @click="open = !open"
                @click.outside="open = false"
                :aria-expanded="open"
                aria-label="Menu aksi {{ $itemName }}"
                class="inline-flex size-9 items-center justify-center rounded-lg border border-slate-400 dark:border-white/20 bg-surface text-text hover:border-amber-500 hover:text-amber-500 transition-colors shadow-xs"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <circle cx="12" cy="5" r="1.5" />
                    <circle cx="12" cy="12" r="1.5" />
                    <circle cx="12" cy="19" r="1.5" />
                </svg>
            </button>

            <div
                x-show="open"
                x-cloak
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 top-full z-50 mt-1.5 w-44 rounded-xl border border-border bg-surface p-1 shadow-token"
            >
                @if ($viewUrl)
                    <a href="{{ $viewUrl }}" target="_blank" class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-text hover:bg-surface-2 transition-colors">
                        <svg class="size-3.5 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        <span>Lihat di situs</span>
                    </a>
                @endif

                @if ($duplicateUrl)
                    <form method="POST" action="{{ $duplicateUrl }}" class="w-full">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-text hover:bg-surface-2 transition-colors text-left">
                            <svg class="size-3.5 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <span>Duplikat</span>
                        </button>
                    </form>
                @endif

                @if ($deleteUrl)
                    <div class="my-1 border-t border-border"></div>
                    <form method="POST" action="{{ $deleteUrl }}" data-confirm="{{ $deleteMessage }}" data-confirm-title="Hapus {{ $itemName }}" data-confirm-action="Hapus" data-confirm-is-danger="true" class="w-full" novalidate>
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-bold text-danger hover:bg-danger/10 transition-colors text-left">
                            <svg class="size-3.5 text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>Hapus</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>
