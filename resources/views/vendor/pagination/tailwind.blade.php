@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi Halaman" class="flex items-center justify-between gap-3 w-full">
        {{-- Mobile: Tombol Prev & Next dengan Ikon & Nomor Halaman --}}
        <div class="flex w-full items-center justify-between gap-2 sm:hidden">
            {{-- Tombol Sebelumnya --}}
            @if ($paginator->onFirstPage())
                <span class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface-2/40 px-3 py-2 text-xs font-semibold text-text-muted opacity-40 cursor-not-allowed select-none">
                    <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span>Sebelumnya</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface-2 px-3.5 py-2 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition shadow-xs">
                    <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span>Sebelumnya</span>
                </a>
            @endif

            {{-- Indikator Halaman Mobile --}}
            <span class="font-tabular text-xs font-bold text-text-muted">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            {{-- Tombol Berikutnya --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface-2 px-3.5 py-2 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition shadow-xs">
                    <span>Berikutnya</span>
                    <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            @else
                <span class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface-2/40 px-3 py-2 text-xs font-semibold text-text-muted opacity-40 cursor-not-allowed select-none">
                    <span>Berikutnya</span>
                    <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            @endif
        </div>

        {{-- Desktop: List Halaman Lengkap (sm:flex) --}}
        <div class="hidden sm:flex sm:items-center sm:gap-1.5">
            {{-- Tombol Sebelumnya --}}
            @if ($paginator->onFirstPage())
                <span class="inline-flex size-9 items-center justify-center rounded-xl border border-border bg-surface-2/40 text-text-muted opacity-40 cursor-not-allowed select-none" aria-disabled="true" aria-label="Halaman sebelumnya">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex size-9 items-center justify-center rounded-xl border border-border bg-surface-2 text-text hover:border-amber-500 hover:text-amber-500 transition shadow-xs" aria-label="Halaman sebelumnya">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
            @endif

            {{-- Elemen Angka Halaman (Smart Clean Window) --}}
            @php
                $current = $paginator->currentPage();
                $last = $paginator->lastPage();

                if ($last <= 8) {
                    $pageList = range(1, $last);
                } elseif ($current <= 4) {
                    $pageList = [1, 2, 3, 4, 5, '...', $last];
                } elseif ($current >= $last - 3) {
                    $pageList = [1, '...', $last - 4, $last - 3, $last - 2, $last - 1, $last];
                } else {
                    $pageList = [1, '...', $current - 1, $current, $current + 1, '...', $last];
                }
            @endphp

            @foreach ($pageList as $pageItem)
                @if ($pageItem === '...')
                    <span class="inline-flex size-9 items-center justify-center text-xs font-bold text-text-muted select-none">…</span>
                @elseif ($pageItem == $current)
                    <span aria-current="page" class="inline-flex size-9 items-center justify-center rounded-xl bg-amber-500 text-slate-950 text-xs font-black shadow-sm select-none">
                        {{ $pageItem }}
                    </span>
                @else
                    <a href="{{ $paginator->url($pageItem) }}" class="inline-flex size-9 items-center justify-center rounded-xl border border-border bg-surface-2 text-text hover:border-amber-500 hover:text-amber-500 text-xs font-bold transition shadow-xs" aria-label="Ke halaman {{ $pageItem }}">
                        {{ $pageItem }}
                    </a>
                @endif
            @endforeach

            {{-- Tombol Berikutnya --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex size-9 items-center justify-center rounded-xl border border-border bg-surface-2 text-text hover:border-amber-500 hover:text-amber-500 transition shadow-xs" aria-label="Halaman berikutnya">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            @else
                <span class="inline-flex size-9 items-center justify-center rounded-xl border border-border bg-surface-2/40 text-text-muted opacity-40 cursor-not-allowed select-none" aria-disabled="true" aria-label="Halaman berikutnya">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
