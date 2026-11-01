@props([
    'id' => null,
    'total' => 1,
    'autoplay' => false,
    'interval' => 5000,
    'showArrows' => true,
    'showDots' => true,
    'loop' => true,
    'bleed' => true,
    'label' => 'Carousel',
    'arrowPrevLabel' => 'Sebelumnya',
    'arrowNextLabel' => 'Berikutnya',
    'arrowPrevLoopLabel' => 'Kembali ke slide terakhir',
    'arrowNextLoopLabel' => 'Kembali ke slide pertama',
    'trackClass' => 'flex gap-4 sm:gap-6',
])

@php
    $carouselId = $id ?? 'carousel-'.uniqid();
    $autoplayMs = (int) $interval;
@endphp

<div
    id="{{ $carouselId }}"
    x-data="{
        current: 0,
        total: {{ max(1, (int) $total) }},
        autoplay: @js((bool) $autoplay),
        intervalMs: {{ $autoplayMs }},
        timer: null,
        resumeTimer: null,
        isInteracting: false,
        trackEl: null,

        init() {
            this.trackEl = this.$refs.track;
            if (this.autoplay && this.total > 1 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                this.startAuto();
            }
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.stopAuto();
                } else if (this.autoplay && !this.isInteracting) {
                    this.startAuto();
                }
            });
        },

        startAuto() {
            this.stopAuto();
            if (!this.autoplay || this.total <= 1) return;
            this.timer = setInterval(() => {
                if (!this.isInteracting) {
                    this.next(true);
                }
            }, this.intervalMs);
        },

        stopAuto() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },

        pauseForInteraction() {
            this.isInteracting = true;
            this.stopAuto();
            if (this.resumeTimer) clearTimeout(this.resumeTimer);
            this.resumeTimer = setTimeout(() => {
                this.isInteracting = false;
                if (this.autoplay) this.startAuto();
            }, 5000);
        },

        onScroll() {
            if (!this.trackEl) return;
            const scrollWidth = this.trackEl.scrollWidth - this.trackEl.clientWidth;
            if (scrollWidth <= 0) {
                this.current = 0;
                return;
            }
            const children = Array.from(this.trackEl.children).filter(c => c.dataset.carouselSlide !== undefined);
            if (children.length === 0) return;

            const trackRect = this.trackEl.getBoundingClientRect();
            let closestIdx = 0;
            let minDiff = Infinity;

            children.forEach((child, idx) => {
                const childRect = child.getBoundingClientRect();
                const diff = Math.abs(childRect.left - trackRect.left);
                if (diff < minDiff) {
                    minDiff = diff;
                    closestIdx = idx;
                }
            });

            this.current = closestIdx;
        },

        scrollToIndex(idx) {
            if (!this.trackEl) return;
            const clamped = Math.max(0, Math.min(this.total - 1, idx));
            const children = Array.from(this.trackEl.children).filter(c => c.dataset.carouselSlide !== undefined);
            const target = children[clamped];
            if (target) {
                const scrollLeft = target.offsetLeft - this.trackEl.offsetLeft;
                this.trackEl.scrollTo({
                    left: scrollLeft,
                    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'
                });
                this.current = clamped;
            }
        },

        next(isAuto = false) {
            if (!isAuto) this.pauseForInteraction();
            if (this.current >= this.total - 1) {
                if ({{ $loop ? 'true' : 'false' }}) {
                    this.scrollToIndex(0);
                }
            } else {
                this.scrollToIndex(this.current + 1);
            }
        },

        prev() {
            this.pauseForInteraction();
            if (this.current <= 0) {
                if ({{ $loop ? 'true' : 'false' }}) {
                    this.scrollToIndex(this.total - 1);
                }
            } else {
                this.scrollToIndex(this.current - 1);
            }
        }
    }"
    class="relative group/carousel w-full"
    aria-label="{{ $label }}"
    @mouseenter="pauseForInteraction()"
    @mouseleave="isInteracting = false; if (autoplay) startAuto()"
    @focusin="pauseForInteraction()"
    @focusout="isInteracting = false; if (autoplay) startAuto()"
    @keydown.arrow-left.prevent="prev()"
    @keydown.arrow-right.prevent="next()"
>
    {{-- Carousel Scroll Track (Bleed to edge on mobile, constrained on desktop) --}}
    <div
        class="relative overflow-hidden {{ $bleed ? '-mx-4 px-4 sm:mx-0 sm:px-0' : '' }}"
    >
        <div
            x-ref="track"
            @scroll.passive="onScroll()"
            @touchstart.passive="pauseForInteraction()"
            @touchend.passive="pauseForInteraction()"
            class="carousel-viewport flex overflow-x-auto scroll-smooth overscroll-x-contain select-none scrollbar-none snap-x snap-mandatory {{ $trackClass }}"
            style="touch-action: pan-x pan-y pinch-zoom; -webkit-overflow-scrolling: touch; scrollbar-width: none; -ms-overflow-style: none;"
        >
            {{ $slot }}
        </div>
    </div>

    {{-- Arrows: Semi-transparent Circular CGV style (Visible on mobile & desktop, min 44px tap target) --}}
    @if ($showArrows && (int) $total > 1)
        {{-- Prev Arrow --}}
        <button
            type="button"
            @click.stop="prev()"
            class="absolute left-1 sm:left-3 top-1/2 -translate-y-1/2 z-20 flex size-11 items-center justify-center rounded-full text-white focus-visible:outline-2 focus-visible:outline-amber-500 transition-opacity"
            :aria-label="current === 0 ? '{{ $arrowPrevLoopLabel }}' : '{{ $arrowPrevLabel }}'"
        >
            <span class="flex size-9 items-center justify-center rounded-full bg-black/60 dark:bg-black/75 hover:bg-black/90 text-white border border-white/20 shadow-md">
                <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </span>
        </button>

        {{-- Next Arrow --}}
        <button
            type="button"
            @click.stop="next()"
            class="absolute right-1 sm:right-3 top-1/2 -translate-y-1/2 z-20 flex size-11 items-center justify-center rounded-full text-white focus-visible:outline-2 focus-visible:outline-amber-500 transition-opacity"
            :aria-label="current === total - 1 ? '{{ $arrowNextLoopLabel }}' : '{{ $arrowNextLabel }}'"
        >
            <span class="flex size-9 items-center justify-center rounded-full bg-black/60 dark:bg-black/75 hover:bg-black/90 text-white border border-white/20 shadow-md">
                <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </span>
        </button>
    @endif

    {{-- Dots Indicator --}}
    @if ($showDots && (int) $total > 1)
        <div class="mt-3 flex items-center justify-center gap-1.5" role="tablist" aria-label="Indikator slide">
            <template x-for="idx in total" :key="idx">
                <button
                    type="button"
                    @click="scrollToIndex(idx - 1)"
                    role="tab"
                    :aria-selected="current === idx - 1"
                    :aria-label="`Ke slide ${idx}`"
                    class="h-1.5 rounded-full transition-all duration-200 focus-visible:outline-none"
                    :class="current === idx - 1 ? 'w-6 bg-amber-500' : 'w-1.5 bg-slate-300 dark:bg-white/25 hover:bg-amber-400/60'"
                ></button>
            </template>
        </div>
    @endif
</div>
