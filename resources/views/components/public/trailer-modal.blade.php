<div
    x-data="{
        isOpen: false,
        videoId: '',
        videoTitle: '',
        triggerEl: null,
        youtubeUrl: '',
        open(id, title, trigger = null) {
            if (!id) return;
            this.videoId = id;
            this.videoTitle = title || 'Trailer';
            this.triggerEl = trigger;
            this.youtubeUrl = 'https://www.youtube.com/watch?v=' + id;
            this.isOpen = true;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => {
                const container = this.$refs.iframeContainer;
                if (container) {
                    container.innerHTML = '';
                    const iframe = document.createElement('iframe');
                    iframe.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(this.videoId) + '?autoplay=1&rel=0&playsinline=1';
                    iframe.title = this.videoTitle;
                    iframe.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
                    iframe.setAttribute('allowfullscreen', 'true');
                    iframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
                    iframe.className = 'w-full h-full rounded-2xl border-0';
                    container.appendChild(iframe);
                }
                if (this.$refs.closeBtn) {
                    this.$refs.closeBtn.focus();
                }
            });
        },
        close() {
            this.isOpen = false;
            document.body.style.overflow = '';
            const container = this.$refs.iframeContainer;
            if (container) {
                container.innerHTML = ''; // Destroy iframe to immediately stop audio
            }
            if (this.triggerEl && typeof this.triggerEl.focus === 'function') {
                this.triggerEl.focus();
            }
        }
    }"
    x-on:open-trailer.window="open($event.detail.id, $event.detail.title, $event.detail.trigger)"
    x-on:keydown.escape.window="if (isOpen) close()"
    x-teleport="body"
>
    <div
        x-show="isOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
        role="dialog"
        aria-modal="true"
        :aria-label="'Trailer ' + videoTitle"
    >
        {{-- Backdrop --}}
        <div
            x-show="isOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="close()"
            class="fixed inset-0 bg-black/85 backdrop-blur-sm"
            aria-hidden="true"
        ></div>

        {{-- Modal Content Box --}}
        <div
            x-show="isOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative z-10 w-full max-w-[800px] flex flex-col items-center max-h-[90dvh]"
            @click.stop
        >
            {{-- Movie Title --}}
            <div class="w-full text-center mb-3 px-4">
                <h3 class="text-base sm:text-lg font-bold text-white truncate" x-text="videoTitle"></h3>
            </div>

            {{-- 16:9 Video Container --}}
            <div
                class="w-full aspect-video bg-black rounded-2xl overflow-hidden shadow-2xl border border-white/10"
                x-ref="iframeContainer"
            >
                {{-- iframe injected on open, removed on close --}}
            </div>

            {{-- Footer Controls below video --}}
            <div class="mt-4 flex flex-col items-center gap-2">
                {{-- Circular 44px Close Button --}}
                <button
                    type="button"
                    x-ref="closeBtn"
                    @click="close()"
                    class="size-11 rounded-full bg-surface-2 border border-border text-text hover:bg-surface hover:text-white flex items-center justify-center shadow-lg transition-transform hover:scale-105 focus-visible:outline-2 focus-visible:outline-amber-500"
                    aria-label="Tutup jendela trailer"
                >
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                {{-- Fallback YouTube link --}}
                <a
                    :href="youtubeUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-[11px] text-text-muted hover:text-amber-400 hover:underline inline-flex items-center gap-1 transition"
                >
                    <span>Buka langsung di YouTube</span>
                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>
