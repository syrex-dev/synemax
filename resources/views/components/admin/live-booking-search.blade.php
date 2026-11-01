{{-- Live Booking Quick Search Component --}}
<div
    x-data="{
        query: '',
        results: [],
        loading: false,
        open: false,
        selectedIndex: -1,
        emptyState: false,

        init() {
            window.addEventListener('keydown', (e) => {
                if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
                    e.preventDefault();
                    this.$refs.searchInput.focus();
                    this.$refs.searchInput.select();
                }
            });
        },

        debounceTimer: null,
        onInput() {
            clearTimeout(this.debounceTimer);
            const q = this.query.trim();
            if (q.length < 2) {
                this.results = [];
                this.open = false;
                this.emptyState = false;
                this.selectedIndex = -1;
                return;
            }

            this.loading = true;
            this.debounceTimer = setTimeout(() => {
                this.performSearch(q);
            }, 250);
        },

        async performSearch(q) {
            try {
                const res = await fetch(`{{ route('admin.bookings.live_search') }}?q=${encodeURIComponent(q)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.results = data.results || [];
                    this.emptyState = this.results.length === 0;
                    this.open = true;
                    this.selectedIndex = -1;
                }
            } catch (err) {
                console.error('Search error', err);
            } finally {
                this.loading = false;
            }
        },

        onEnter(e) {
            if (this.results.length === 1) {
                e.preventDefault();
                window.location.href = this.results[0].url;
            } else if (this.selectedIndex >= 0 && this.selectedIndex < this.results.length) {
                e.preventDefault();
                window.location.href = this.results[this.selectedIndex].url;
            }
        },

        onKeyDown(e) {
            if (!this.open || this.results.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.selectedIndex = (this.selectedIndex + 1) % this.results.length;
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.selectedIndex = (this.selectedIndex - 1 + this.results.length) % this.results.length;
            } else if (e.key === 'Escape') {
                this.open = false;
            }
        }
    }"
    @click.outside="open = false"
    class="relative w-full max-w-lg"
>
    <div class="relative">
        <label for="admin-live-search" class="sr-only">Cari booking secara langsung</label>
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-text-muted">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
        </div>

        <input
            id="admin-live-search"
            x-ref="searchInput"
            type="text"
            x-model="query"
            @input="onInput()"
            @keydown.enter="onEnter($event)"
            @keydown="onKeyDown($event)"
            @focus="if (query.trim().length >= 2 && results.length > 0) open = true"
            placeholder="Cari kode (4 huruf), nama, WhatsApp…"
            autocomplete="off"
            class="min-h-[44px] w-full rounded-xl border border-slate-300 dark:border-white/15 bg-surface pl-10 pr-12 text-xs sm:text-sm text-text placeholder:text-text-muted focus:border-amber-400 focus:outline-none transition shadow-none"
        >

        {{-- Shortcut badge '/' --}}
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
            <template x-if="loading">
                <svg class="size-4 animate-spin text-amber-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </template>
            <template x-if="!loading">
                <kbd class="hidden sm:inline-flex items-center justify-center rounded-md border border-slate-300 dark:border-white/20 bg-surface-2 px-1.5 py-0.5 text-[10px] font-mono font-bold text-text-muted">
                    /
                </kbd>
            </template>
        </div>
    </div>

    {{-- Dropdown results --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute left-0 right-0 top-full mt-1.5 z-50 max-h-96 overflow-y-auto rounded-2xl border border-slate-300 dark:border-white/15 bg-surface p-1.5 shadow-xl text-xs"
    >
        {{-- Empty state --}}
        <template x-if="emptyState">
            <div class="p-4 text-center text-text-muted">
                <p class="font-medium text-xs">Tidak ketemu.</p>
                <p class="mt-0.5 text-[11px] text-text-muted/80">Coba 4 karakter terakhir kode atau nomor telepon.</p>
            </div>
        </template>

        {{-- Results list --}}
        <template x-for="(item, idx) in results" :key="item.id">
            <a
                :href="item.url"
                @mouseenter="selectedIndex = idx"
                class="flex items-center justify-between gap-3 rounded-xl p-2.5 transition text-left"
                :class="selectedIndex === idx ? 'bg-amber-500/10 text-text' : 'hover:bg-surface-2 text-text'"
            >
                <div class="min-w-0 flex-1 space-y-0.5">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-text truncate" x-text="item.customer_name"></span>
                        <span class="font-mono text-[11px] font-bold text-amber-600 dark:text-amber-400" x-text="item.code"></span>
                    </div>
                    <div class="flex items-center gap-2 text-[11px] text-text-muted truncate">
                        <span x-text="item.film_title"></span>
                        <span class="opacity-50">·</span>
                        <span x-text="item.session_info"></span>
                        <span class="opacity-50">·</span>
                        <span x-text="item.whatsapp"></span>
                    </div>
                </div>

                <div class="shrink-0 text-right space-y-0.5">
                    <div class="font-bold text-text" x-text="item.total_price_formatted"></div>
                    <div class="text-[10px] font-semibold text-text-muted" x-text="item.status_label"></div>
                </div>
            </a>
        </template>
    </div>
</div>
