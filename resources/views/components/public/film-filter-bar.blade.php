@props([
    'genres' => [],
    'years' => [],
    'childGenres' => null,
    'selectedGenreModel' => null,
])

<div
    x-data="{
        yearOpen: false,
        toggleYear() {
            this.yearOpen = !this.yearOpen;
            if (this.yearOpen) {
                this.$nextTick(() => {
                    const sel = this.$refs.yearListbox?.querySelector('[aria-selected=\'true\']');
                    sel?.scrollIntoView({ block: 'nearest' });
                });
            }
        },
        selectYear(y) {
            this.setFilter(undefined, y);
            this.yearOpen = false;
            this.$refs.yearBtn?.focus();
        }
    }"
    class="flex flex-col gap-3 rounded-2xl border border-border bg-surface p-3 sm:p-4 shadow-xs"
>
    {{-- Header Row: Label & Year Combobox + Reset Button --}}
    <div class="flex items-center justify-between gap-3 border-b border-border/60 pb-2.5">
        <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-text">
            <svg class="size-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            Pilih Genre
        </span>

        {{-- 2. ACCESSIBLE CUSTOM YEAR COMBOBOX & RESET BUTTON --}}
        <div class="relative shrink-0 flex items-center gap-2">
            {{-- Combobox Trigger Button --}}
        <button
            type="button"
            x-ref="yearBtn"
            @click="toggleYear()"
            role="combobox"
            :aria-expanded="yearOpen"
            aria-haspopup="listbox"
            aria-controls="year-listbox"
            aria-label="Filter berdasarkan tahun rilis"
            class="relative inline-flex min-h-[40px] w-full sm:w-auto sm:min-w-[140px] items-center justify-between rounded-xl border border-border bg-surface-2 px-3 py-1.5 text-xs font-bold text-text hover:border-amber-500 transition-all shadow-xs focus-visible:outline-2 focus-visible:outline-amber-500"
        >
            <div class="flex items-center gap-2 min-w-0 pr-5">
                <svg class="size-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <div class="truncate text-left leading-tight">
                    <span class="block text-[9px] font-semibold text-text-muted uppercase tracking-wider">Tahun</span>
                    <span class="block font-bold text-text truncate text-xs" x-text="tahun ? tahun : 'Semua tahun'"></span>
                </div>
            </div>

            <svg
                class="absolute right-2.5 size-4 shrink-0 text-text-muted transition-transform duration-200"
                :class="yearOpen ? 'rotate-180 text-amber-500' : ''"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2.5"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        {{-- Reset Filter Button (Shown when filter active) --}}
        <button
            type="button"
            x-show="genre || tahun || (typeof search !== 'undefined' && search)"
            x-cloak
            @click="resetFilter ? resetFilter() : resetFilters()"
            class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-danger/30 bg-danger/10 px-3 py-1.5 text-xs font-bold text-danger hover:bg-danger/20 transition shadow-xs shrink-0"
            title="Reset semua filter"
        >
            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span class="hidden sm:inline">Reset</span>
        </button>

        {{-- Mobile Backdrop Overlay --}}
        <div
            x-show="yearOpen"
            x-cloak
            @click="yearOpen = false"
            x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/50 z-50 sm:hidden"
        ></div>

        {{-- Options Listbox (Bottom Sheet on Mobile, Dropdown on Desktop) --}}
        <div
            x-show="yearOpen"
            x-cloak
            x-ref="yearListbox"
            @click.outside="if (window.innerWidth >= 640) yearOpen = false"
            @keydown.escape.prevent="yearOpen = false; $refs.yearBtn?.focus()"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-full sm:translate-y-2 sm:opacity-0"
            x-transition:enter-end="translate-y-0 sm:opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 sm:opacity-100"
            x-transition:leave-end="translate-y-full sm:translate-y-2 sm:opacity-0"
            class="fixed inset-x-0 bottom-0 z-50 rounded-t-3xl border-t border-border bg-surface p-4 shadow-2xl sm:absolute sm:inset-auto sm:right-0 sm:top-full sm:mt-2 sm:w-56 sm:rounded-2xl sm:border sm:p-1.5"
        >
            {{-- Mobile Sheet Header --}}
            <div class="sm:hidden flex items-center justify-between pb-3 mb-2 border-b border-border">
                <span class="text-xs font-bold uppercase tracking-wider text-text">Pilih Tahun Rilis</span>
                <button type="button" @click="yearOpen = false" class="text-text-muted hover:text-text p-1">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <ul
                role="listbox"
                id="year-listbox"
                tabindex="-1"
                class="max-h-60 overflow-y-auto space-y-1 [scrollbar-width:thin]"
            >
                {{-- All Years Option --}}
                <li
                    role="option"
                    :aria-selected="tahun === ''"
                    @click="selectYear('')"
                    class="flex min-h-[44px] sm:min-h-[40px] items-center justify-between rounded-xl px-3 py-2 text-xs font-medium cursor-pointer transition-colors"
                    :class="tahun === ''
                        ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 font-bold'
                        : 'text-text hover:bg-amber-500/10 hover:text-amber-500'"
                >
                    <span>Semua Tahun</span>
                    <svg x-show="tahun === ''" class="size-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </li>

                {{-- Year List --}}
                @foreach ($years as $y)
                    <li
                        role="option"
                        :aria-selected="String(tahun) === '{{ $y }}'"
                        @click="selectYear('{{ $y }}')"
                        class="flex min-h-[44px] sm:min-h-[40px] items-center justify-between rounded-xl px-3 py-2 text-xs font-medium cursor-pointer transition-colors"
                        :class="String(tahun) === '{{ $y }}'
                            ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 font-bold'
                            : 'text-text hover:bg-amber-500/10 hover:text-amber-500'"
                    >
                        <span>{{ $y }}</span>
                        <svg x-show="String(tahun) === '{{ $y }}'" class="size-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

    {{-- Sleek, Neat Single-Line Horizontal Genre Scroll Track --}}
    <div
        class="flex items-center gap-2 overflow-x-auto py-1 scroll-smooth custom-scrollbar -mx-1 px-1"
        style="touch-action: pan-x pan-y pinch-zoom; -webkit-overflow-scrolling: touch;"
        @wheel.passive="$el.scrollLeft += $event.deltaY"
    >
        {{-- All Genres Chip --}}
        <button
            type="button"
            @click="setFilter('', undefined)"
            :data-active="genre === '' ? 'true' : 'false'"
            class="min-h-9 shrink-0 whitespace-nowrap rounded-xl px-4 py-1.5 text-xs font-bold transition-all focus-visible:outline-2 focus-visible:outline-amber-500"
            :class="genre === ''
                ? 'bg-amber-500 text-slate-950 font-black shadow-xs'
                : 'border border-border bg-surface-2 text-text-muted hover:text-text hover:border-amber-500/50'"
        >
            Semua Genre
        </button>

        {{-- Canonical Genres Chips --}}
        @foreach ($genres as $g)
            <button
                type="button"
                @click="setFilter('{{ $g }}', undefined)"
                :data-active="genre === '{{ $g }}' ? 'true' : 'false'"
                class="min-h-9 shrink-0 whitespace-nowrap rounded-xl px-4 py-1.5 text-xs font-bold transition-all focus-visible:outline-2 focus-visible:outline-amber-500"
                :class="genre === '{{ $g }}'
                    ? 'bg-amber-500 text-slate-950 font-black shadow-xs'
                    : 'border border-border bg-surface-2 text-text-muted hover:text-text hover:border-amber-500/50'"
            >
                {{ $g }}
            </button>
        @endforeach
    </div>

    {{-- Sub-genre Chips when Parent Genre or Child Genre is Selected --}}
    @if (!empty($childGenres) && $childGenres->isNotEmpty())
        @php
            $parentName = $selectedGenreModel?->parent ? $selectedGenreModel->parent->name : $selectedGenreModel?->name;
        @endphp
        <div
            class="flex items-center gap-1.5 overflow-x-auto pt-2.5 border-t border-border/60 custom-scrollbar -mx-1 px-1 text-xs"
            style="touch-action: pan-x pan-y pinch-zoom; -webkit-overflow-scrolling: touch;"
            @wheel.passive="$el.scrollLeft += $event.deltaY"
        >
            <span class="text-[10px] font-bold uppercase tracking-wider text-text-muted shrink-0 mr-1">
                Turunan {{ $parentName }}:
            </span>
            <button
                type="button"
                @click="setFilter('{{ $parentName }}', undefined)"
                class="min-h-7 shrink-0 whitespace-nowrap rounded-lg px-2.5 py-1 text-[11px] font-bold transition-all focus-visible:outline-2 focus-visible:outline-amber-500"
                :class="genre === '{{ $parentName }}'
                    ? 'bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/40 font-bold'
                    : 'border border-border/80 bg-surface-2/60 text-text-muted hover:text-text hover:border-amber-500/30'"
            >
                Semua {{ $parentName }}
            </button>
            @foreach ($childGenres as $cg)
                <button
                    type="button"
                    @click="setFilter('{{ $cg->name }}', undefined)"
                    class="min-h-7 shrink-0 whitespace-nowrap rounded-lg px-2.5 py-1 text-[11px] font-bold transition-all focus-visible:outline-2 focus-visible:outline-amber-500"
                    :class="genre === '{{ $cg->name }}'
                        ? 'bg-amber-500 text-slate-950 font-black shadow-xs'
                        : 'border border-border/80 bg-surface-2/60 text-text-muted hover:text-text hover:border-amber-500/40'"
                >
                    {{ $cg->name }}
                </button>
            @endforeach
        </div>
    @endif
</div>
