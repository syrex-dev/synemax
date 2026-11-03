@props([
    'id' => null,
    'total' => 1,
    'autoplay' => false,
    'interval' => 5000,
    'showArrows' => true,
    'showDots' => true,
    'loop' => true,
    'bleed' => true,
    'fade' => false,
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
        fade: @js((bool) $fade),
        touchStartX: 0,
        touchEndX: 0,
        timer: null,
        resumeTimer: null,
        isInteracting: false,
        trackEl: null,

        init() {
            this.trackEl = this.$refs.track;
            if (this.fade) {
                this.setupFadeSlides();
            }
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

        setupFadeSlides() {
            if (!this.trackEl) return;
            const slides = Array.from(this.trackEl.children).filter(c => c.dataset.carouselSlide !== undefined);
            slides.forEach((slide, idx) => {
                slide.style.gridArea = '1 / 1';
                slide.classList.add('transition-opacity', 'duration-700', 'ease-in-out');
                if (idx === 0) {
                    slide.classList.add('opacity-100', 'z-10', 'pointer-events-auto');
                    slide.classList.remove('opacity-0', 'z-0', 'pointer-events-none', 'invisible');
                } else {
                    slide.classList.add('opacity-0', 'z-0', 'pointer-events-none', 'invisible');
                    slide.classList.remove('opacity-100', 'z-10', 'pointer-events-auto');
                }
            });
        },

        updateFade(activeIdx) {
            if (!this.fade || !this.trackEl) return;
            const slides = Array.from(this.trackEl.children).filter(c => c.dataset.carouselSlide !== undefined);
            slides.forEach((slide, idx) => {
                if (idx === activeIdx) {
                    slide.classList.add('opacity-100', 'z-10', 'pointer-events-auto');
                    slide.classList.remove('opacity-0', 'z-0', 'pointer-events-none', 'invisible');
                    slide.removeAttribute('aria-hidden');
                } else {
                    slide.classList.add('opacity-0', 'z-0', 'pointer-events-none', 'invisible');
                    slide.classList.remove('opacity-100', 'z-10', 'pointer-events-auto');
                    slide.setAttribute('aria-hidden', 'true');
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
            if (this.fade || !this.trackEl) return;
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

        goTo(idx) {
            const clamped = Math.max(0, Math.min(this.total - 1, idx));
            if (this.fade) {
                this.current = clamped;
                this.updateFade(clamped);
            } else {
                this.scrollToIndex(clamped);
            }
        },

        next(isAuto = false) {
            if (!isAuto) this.pauseForInteraction();
            if (this.fade) {
                const nextIdx = (this.current + 1) % this.total;
                this.current = nextIdx;
                this.updateFade(nextIdx);
                return;
            }
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
            if (this.fade) {
                const prevIdx = (this.current - 1 + this.total) % this.total;
                this.current = prevIdx;
                this.updateFade(prevIdx);
                return;
            }
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
        @if ($fade)
            <div
                x-ref="track"
                @touchstart.passive="touchStartX = $event.touches[0].clientX; pauseForInteraction()"
                @touchend.passive="touchEndX = $event.changedTouches[0].clientX; if (Math.abs(touchStartX - touchEndX) > 40) { touchStartX > touchEndX ? next() : prev(); }; pauseForInteraction()"
                class="carousel-viewport relative w-full overflow-hidden select-none {{ $trackClass }}"
                style="display: grid; grid-template-columns: 1fr; grid-template-rows: 1fr;"
            >
                {{ $slot }}
            </div>
        @else
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
        @endif
    </div>

    {{-- Dots Indicator --}}
    @if ($showDots && (int) $total > 1)
        <div class="mt-3 flex items-center justify-center gap-1.5" role="tablist" aria-label="Indikator slide">
            <template x-for="idx in total" :key="idx">
                <button
                    type="button"
                    @click="goTo(idx - 1)"
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
