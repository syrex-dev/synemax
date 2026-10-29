@props([
    'genres' => [],
    'years' => [],
])

<div
    x-data="{
        canScrollLeft: false,
        canScrollRight: false,
        isDragging: false,
        startX: 0,
        scrollLeftStart: 0,
        hasMoved: false,
        yearOpen: false,

        init() {
            const scroller = this.$refs.genreScroller;
            if (!scroller) return;

            const updateScroll = () => {
                const tolerance = 4;
                this.canScrollLeft = scroller.scrollLeft > tolerance;
                this.canScrollRight = (scroller.scrollLeft + scroller.clientWidth) < (scroller.scrollWidth - tolerance);
            };

            scroller.addEventListener('scroll', updateScroll, { passive: true });
            
            const ro = new ResizeObserver(() => updateScroll());
            ro.observe(scroller);

            this.$nextTick(() => {
                updateScroll();
                this.centerActiveChip();
            });

            this.$watch('genre', () => {
                this.$nextTick(() => {
                    this.centerActiveChip();
                    updateScroll();
                });
            });
        },

        scrollLeft() {
            const scroller = this.$refs.genreScroller;
            if (!scroller) return;
            const delta = scroller.clientWidth * 0.7;
            scroller.scrollBy({ left: -delta, behavior: 'smooth' });
        },

        scrollRight() {
            const scroller = this.$refs.genreScroller;
            if (!scroller) return;
            const delta = scroller.clientWidth * 0.7;
            scroller.scrollBy({ left: delta, behavior: 'smooth' });
        },

        onWheel(e) {
            const scroller = this.$refs.genreScroller;
            if (!scroller) return;
            if (Math.abs(e.deltaX) > Math.abs(e.deltaY)) return;
            if (scroller.scrollWidth > scroller.clientWidth) {
                e.preventDefault();
                scroller.scrollLeft += (e.deltaY * 0.8);
            }
        },

        onPointerDown(e) {
            const scroller = this.$refs.genreScroller;
            this.isDragging = true;
            this.hasMoved = false;
            this.startX = e.pageX || e.clientX;
            this.scrollLeftStart = scroller.scrollLeft;
        },

        onPointerMove(e) {
            if (!this.isDragging) return;
            const x = e.pageX || e.clientX;
            const walk = (x - this.startX);
            if (Math.abs(walk) > 5) {
                this.hasMoved = true;
            }
            this.$refs.genreScroller.scrollLeft = this.scrollLeftStart - walk;
        },

        onPointerUp() {
            this.isDragging = false;
        },

        selectGenre(g) {
            if (this.hasMoved) return;
            this.setFilter(g, undefined);
        },

        centerActiveChip() {
            const scroller = this.$refs.genreScroller;
            if (!scroller) return;
            const active = scroller.querySelector('[data-active=\'true\']');
            if (active) {
                active.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' });
            }
        },

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
    class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-2xl border border-slate-300 dark:border-white/10 bg-surface dark:bg-[#0B101B] p-3 sm:p-3.5 shadow-sm"
>
    {{-- ================================================================= --}}
    {{-- 1. SCROLLABLE GENRE CHIPS AREA (flex: 1, min-width: 0)            --}}
    {{-- ================================================================= --}}
    <div class="relative flex-1 min-w-0 flex items-center">
        {{-- Left Fade Gradient --}}
        <div
            x-show="canScrollLeft"
            x-cloak
            x-transition:enter="transition-opacity duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-none absolute left-0 inset-y-0 w-12 bg-gradient-to-r from-surface dark:from-[#0B101B] to-transparent z-10"
        ></div>

        {{-- Left Scroll Arrow (Desktop 36px) --}}
        <button
            type="button"
            x-show="canScrollLeft"
            x-cloak
            x-transition:enter="transition-opacity duration-150"
            x-transition:enter-start="opacity-0 scale-90"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition-opacity duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-90"
            @click.stop="scrollLeft()"
            class="hidden sm:flex absolute left-1 size-9 items-center justify-center rounded-full border border-border bg-surface/95 dark:bg-[#111726]/95 text-text hover:bg-amber-500 hover:text-slate-950 hover:border-amber-500 transition-all shadow-md z-20 focus-visible:outline-2 focus-visible:outline-amber-500"
            aria-label="Geser genre ke kiri"
        >
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        {{-- Scrolling Track --}}
        <div
            x-ref="genreScroller"
            @wheel="onWheel($event)"
            @pointerdown="onPointerDown($event)"
            @pointermove="onPointerMove($event)"
            @pointerup="onPointerUp()"
            @pointercancel="onPointerUp()"
            class="flex items-center gap-2 overflow-x-auto scroll-smooth py-1 px-1 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden select-none cursor-grab active:cursor-grabbing w-full"
            style="scroll-snap-type: x proximity;"
        >
            {{-- All Genres Chip --}}
            <button
                type="button"
                @click="selectGenre('')"
                :data-active="genre === '' ? 'true' : 'false'"
                class="shrink-0 min-h-[38px] rounded-xl px-4 text-xs font-bold transition-all focus-visible:outline-2 focus-visible:outline-amber-500"
                :class="genre === ''
                    ? 'bg-amber-500 text-slate-950 shadow-xs'
                    : 'border border-slate-300 dark:border-white/10 bg-surface text-text-muted hover:text-text hover:border-amber-500/50'"
                style="scroll-snap-align: center;"
            >
                Semua Genre
            </button>

            {{-- Canonical Genres Chips --}}
            @foreach ($genres as $g)
                <button
                    type="button"
                    @click="selectGenre('{{ $g }}')"
                    :data-active="genre === '{{ $g }}' ? 'true' : 'false'"
                    class="shrink-0 min-h-[38px] rounded-xl px-4 text-xs font-bold transition-all focus-visible:outline-2 focus-visible:outline-amber-500"
                    :class="genre === '{{ $g }}'
                        ? 'bg-amber-500 text-slate-950 shadow-xs'
                        : 'border border-slate-300 dark:border-white/10 bg-surface text-text-muted hover:text-text hover:border-amber-500/50'"
                    style="scroll-snap-align: center;"
                >
                    {{ $g }}
                </button>
            @endforeach
        </div>

        {{-- Right Fade Gradient --}}
        <div
            x-show="canScrollRight"
            x-cloak
            x-transition:enter="transition-opacity duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-none absolute right-0 inset-y-0 w-12 bg-gradient-to-l from-surface dark:from-[#0B101B] to-transparent z-10"
        ></div>

        {{-- Right Scroll Arrow (Desktop 36px) --}}
        <button
            type="button"
            x-show="canScrollRight"
            x-cloak
            x-transition:enter="transition-opacity duration-150"
            x-transition:enter-start="opacity-0 scale-90"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition-opacity duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-90"
            @click.stop="scrollRight()"
            class="hidden sm:flex absolute right-1 size-9 items-center justify-center rounded-full border border-border bg-surface/95 dark:bg-[#111726]/95 text-text hover:bg-amber-500 hover:text-slate-950 hover:border-amber-500 transition-all shadow-md z-20 focus-visible:outline-2 focus-visible:outline-amber-500"
            aria-label="Geser genre ke kanan"
        >
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>

    {{-- ================================================================= --}}
    {{-- 2. ACCESSIBLE CUSTOM YEAR COMBOBOX (LEBAR TETAP >= 150px)         --}}
    {{-- ================================================================= --}}
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
            class="relative inline-flex min-h-[44px] min-w-[150px] items-center justify-between rounded-xl border border-slate-300 dark:border-white/15 bg-surface px-3 py-2 text-xs font-bold text-text hover:border-amber-500 transition-all shadow-xs focus-visible:outline-2 focus-visible:outline-amber-500"
        >
            <div class="flex items-center gap-2 min-w-0 pr-6">
                {{-- Calendar Icon --}}
                <svg class="size-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <div class="truncate text-left leading-tight">
                    <span class="block text-[10px] font-semibold text-text-muted uppercase tracking-wider">Tahun</span>
                    <span class="block font-bold text-text truncate" x-text="tahun ? tahun : 'Semua tahun'"></span>
                </div>
            </div>

            {{-- Chevron Icon (Dedicated space on right, rotates when open) --}}
            <svg
                class="absolute right-3 size-4 shrink-0 text-text-muted transition-transform duration-200"
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
            class="inline-flex min-h-[44px] items-center gap-1.5 rounded-xl border border-danger/30 bg-danger/10 px-3 py-2 text-xs font-bold text-danger hover:bg-danger/20 transition shadow-xs"
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
            class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 sm:hidden"
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
