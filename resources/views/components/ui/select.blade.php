@props([
    'name',
    'id' => null,
    'value' => null,
    'options' => [],
    'label' => null,
    'placeholder' => 'Pilih opsi...',
    'error' => null,
    'required' => false,
    'disabled' => false,
    'searchable' => null,
    'autoSubmit' => false,
    'size' => 'default',
    'buttonClass' => '',
])

@php
    $selectId = $id ?? 'select_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $name) . '_' . substr(md5(uniqid('', true)), 0, 6);
    $currentValue = old($name, $value ?? '');

    // Normalize options
    $normalizedOptions = [];
    $rawOptions = $options instanceof \Illuminate\Support\Collection ? $options->toArray() : (array)$options;

    // Parse options from slot if empty
    $slotContent = trim((string)$slot);
    if (empty($rawOptions) && !empty($slotContent)) {
        if (preg_match_all('/<option\s+value=["\']([^"\']*)["\'][^>]*>(.*?)<\/option>/is', $slotContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $rawOptions[$match[1]] = trim(strip_tags($match[2]));
            }
        }
    }

    $isAssoc = array_keys($rawOptions) !== range(0, count($rawOptions) - 1);

    foreach ($rawOptions as $k => $v) {
        if (is_array($v)) {
            $val = (string)($v['value'] ?? $k);
            $lbl = (string)($v['label'] ?? $val);
            $icon = $v['icon'] ?? null;
            $desc = $v['description'] ?? null;
        } elseif ($isAssoc) {
            $val = (string)$k;
            $lbl = (string)$v;
            $icon = null;
            $desc = null;
        } else {
            $val = (string)$v;
            $lbl = (string)$v;
            $icon = null;
            $desc = null;
        }
        $normalizedOptions[] = [
            'value' => $val,
            'label' => $lbl,
            'icon' => $icon,
            'description' => $desc,
        ];
    }

    $isSearchable = $searchable ?? (count($normalizedOptions) > 8);
    $errorMessage = $error ?? ($errors->has($name) ? $errors->first($name) : null);

    // Initial label
    $selectedLabel = $placeholder;
    foreach ($normalizedOptions as $opt) {
        if ((string)$opt['value'] === (string)$currentValue) {
            $selectedLabel = $opt['label'];
            break;
        }
    }
@endphp

<div
    {{ $attributes->merge(['class' => 'relative w-full']) }}
    x-data="{
        open: false,
        search: '',
        selectedValue: '{{ addslashes((string)$currentValue) }}',
        selectedLabel: '{{ addslashes((string)$selectedLabel) }}',
        focusedIndex: -1,
        options: {{ Js::from($normalizedOptions) }},
        desktopStyles: {},
        isFlipped: false,

        get filteredOptions() {
            if (!this.search) return this.options;
            const q = this.search.toLowerCase();
            return this.options.filter(o =>
                o.label.toLowerCase().includes(q) ||
                (o.description && o.description.toLowerCase().includes(q))
            );
        },

        init() {
            // Keep label in sync with selectedValue
            const found = this.options.find(o => String(o.value) === String(this.selectedValue));
            if (found) {
                this.selectedLabel = found.label;
            }
        },

        setValue(val) {
            this.selectedValue = val;
            const found = this.options.find(o => String(o.value) === String(val));
            if (found) this.selectedLabel = found.label;
            if (this.$refs.hiddenInput) this.$refs.hiddenInput.value = val;
        },

        toggle() {
            if ({{ $disabled ? 'true' : 'false' }}) return;
            if (this.open) {
                this.close();
            } else {
                this.openDropdown();
            }
        },

        openDropdown() {
            this.open = true;
            this.search = '';
            this.focusedIndex = this.options.findIndex(o => String(o.value) === String(this.selectedValue));
            this.$nextTick(() => {
                this.updatePosition();
                if (this.$refs.searchInput) {
                    this.$refs.searchInput.focus();
                } else if (this.$refs.desktopListbox) {
                    this.$refs.desktopListbox.focus();
                }
            });
        },

        close() {
            this.open = false;
            this.focusedIndex = -1;
            this.$refs.button?.focus();
        },

        select(opt) {
            this.selectedValue = opt.value;
            this.selectedLabel = opt.label;
            const hiddenInput = this.$refs.hiddenInput;
            if (hiddenInput) {
                hiddenInput.value = opt.value;
                hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                @if ($autoSubmit)
                    this.$nextTick(() => {
                        hiddenInput.form?.requestSubmit ? hiddenInput.form.requestSubmit() : hiddenInput.form?.submit();
                    });
                @endif
            }
            this.close();
        },

        updatePosition() {
            if (window.innerWidth < 640) return;
            const btn = this.$refs.button;
            if (!btn) return;
            const rect = btn.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom;
            const spaceAbove = rect.top;
            const panelHeight = Math.min(320, (this.filteredOptions.length * 40) + ({{ $isSearchable ? '50' : '16' }}));

            this.isFlipped = spaceBelow < 250 && spaceAbove > spaceBelow;
            const top = this.isFlipped
                ? Math.max(10, rect.top - panelHeight - 6)
                : rect.bottom + 6;

            const targetWidth = Math.max(rect.width, 220);
            const left = Math.max(10, Math.min(rect.left, window.innerWidth - targetWidth - 10));

            this.desktopStyles = {
                top: `${top}px`,
                left: `${left}px`,
                width: `${targetWidth}px`,
            };
        },

        onKeyDown(e) {
            if (e.key === 'Escape') {
                this.close();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!this.open) {
                    this.openDropdown();
                } else {
                    this.focusedIndex = Math.min(this.focusedIndex + 1, this.filteredOptions.length - 1);
                    this.scrollFocusedIntoView();
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (!this.open) {
                    this.openDropdown();
                } else {
                    this.focusedIndex = Math.max(this.focusedIndex - 1, 0);
                    this.scrollFocusedIntoView();
                }
            } else if (e.key === 'Home') {
                if (this.open) {
                    e.preventDefault();
                    this.focusedIndex = 0;
                    this.scrollFocusedIntoView();
                }
            } else if (e.key === 'End') {
                if (this.open) {
                    e.preventDefault();
                    this.focusedIndex = this.filteredOptions.length - 1;
                    this.scrollFocusedIntoView();
                }
            } else if (e.key === 'Enter' || e.key === ' ') {
                if (this.open && this.focusedIndex >= 0 && this.focusedIndex < this.filteredOptions.length) {
                    e.preventDefault();
                    this.select(this.filteredOptions[this.focusedIndex]);
                } else if (!this.open && e.key === 'Enter') {
                    e.preventDefault();
                    this.openDropdown();
                }
            }
        },

        scrollFocusedIntoView() {
            this.$nextTick(() => {
                const listbox = window.innerWidth < 640 ? this.$refs.mobileListbox : this.$refs.desktopListbox;
                const items = listbox?.querySelectorAll('[role=option]');
                if (items && items[this.focusedIndex]) {
                    items[this.focusedIndex].scrollIntoView({ block: 'nearest' });
                }
            });
        }
    }"
    @keydown="onKeyDown($event)"
    @synemax-select-set.window="if ($event.detail.name === '{{ $name }}') setValue($event.detail.value)"
>
    @if ($label)
        <label id="{{ $selectId }}-label" class="block text-xs font-semibold text-text mb-1.5">
            {{ $label }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    {{-- Hidden Input for form submission & change tracking --}}
    <input
        type="hidden"
        name="{{ $name }}"
        id="{{ $selectId }}"
        x-ref="hiddenInput"
        :value="selectedValue"
        @if ($required) required @endif
        @if ($disabled) disabled @endif
        data-track-dirty="true"
    >

    {{-- 44px Main Trigger Button --}}
    <button
        type="button"
        x-ref="button"
        id="{{ $selectId }}-button"
        @click="toggle()"
        role="combobox"
        :aria-expanded="open"
        aria-haspopup="listbox"
        aria-controls="{{ $selectId }}-listbox"
        @if ($label) aria-labelledby="{{ $selectId }}-label {{ $selectId }}-button" @endif
        @if ($disabled) disabled @endif
        class="relative inline-flex min-h-[44px] w-full items-center justify-between rounded-xl border {{ $errorMessage ? 'border-danger' : 'border-slate-300 dark:border-white/20' }} bg-surface px-3 py-2 text-xs font-bold text-text hover:border-amber-500 transition-all shadow-xs focus-visible:outline-2 focus-visible:outline-amber-500 disabled:cursor-not-allowed disabled:opacity-60 disabled:bg-slate-100 dark:disabled:bg-white/5 {{ $buttonClass }}"
    >
        <span class="truncate pr-7 text-left block" :class="selectedValue ? 'text-text' : 'text-text-muted'" x-text="selectedLabel || '{{ $placeholder }}'">
            {{ $selectedLabel }}
        </span>

        {{-- Dedicated Chevron on right (rotates on open) --}}
        <svg
            class="absolute right-3.5 size-4 shrink-0 text-text-muted transition-transform duration-200 pointer-events-none"
            :class="open ? 'rotate-180 text-amber-500' : ''"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2.5"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    {{-- Inline validation error --}}
    @if ($errorMessage)
        <p class="mt-1 text-xs font-semibold text-danger">{{ $errorMessage }}</p>
    @endif

    {{-- Teleported Dropdown & Mobile Sheet (Renders at body root to avoid overflow clipping) --}}
    <template x-teleport="body">
        <div x-show="open" x-cloak>
            {{-- Mobile Backdrop --}}
            <div
                x-show="open"
                @click="close()"
                x-transition:enter="transition-opacity ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/60 backdrop-blur-xs z-[9998] sm:hidden"
            ></div>

            {{-- Mobile Bottom Sheet (< 640px) --}}
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                class="fixed inset-x-0 bottom-0 z-[9999] rounded-t-3xl border-t border-border bg-surface p-4 shadow-2xl sm:hidden max-h-[85vh] flex flex-col"
            >
                <div class="flex items-center justify-between pb-3 mb-2 border-b border-border shrink-0">
                    <span class="text-xs font-bold uppercase tracking-wider text-text">{{ $label ?? 'Pilih Opsi' }}</span>
                    <button type="button" @click="close()" class="text-text-muted hover:text-text p-1 min-h-[44px] min-w-[44px] flex items-center justify-center">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                @if ($isSearchable)
                    <div class="mb-3 shrink-0">
                        <div class="relative">
                            <input
                                type="text"
                                x-model="search"
                                placeholder="Cari opsi..."
                                class="w-full min-h-[44px] rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text placeholder-text-muted focus:outline-none focus:border-amber-500"
                            >
                        </div>
                    </div>
                @endif

                <ul
                    role="listbox"
                    id="{{ $selectId }}-mobile-listbox"
                    x-ref="mobileListbox"
                    tabindex="-1"
                    class="overflow-y-auto space-y-1.5 flex-1 [scrollbar-width:thin]"
                >
                    <template x-for="(opt, idx) in filteredOptions" :key="opt.value">
                        <li
                            role="option"
                            :aria-selected="String(selectedValue) === String(opt.value)"
                            @click="select(opt)"
                            class="flex min-h-[48px] items-center justify-between rounded-xl px-3.5 py-2 text-xs font-medium cursor-pointer transition-colors"
                            :class="String(selectedValue) === String(opt.value)
                                ? 'bg-[#FEF3C7] text-[#78350F] dark:bg-amber-500/20 dark:text-amber-300 font-bold border border-amber-300 dark:border-amber-500/30'
                                : (focusedIndex === idx ? 'bg-amber-500/10 text-text' : 'text-text hover:bg-amber-500/10')"
                        >
                            <div class="flex flex-col">
                                <span class="font-bold text-sm" x-text="opt.label"></span>
                                <span x-show="opt.description" class="text-[11px] text-text-muted" x-text="opt.description"></span>
                            </div>
                            <svg x-show="String(selectedValue) === String(opt.value)" class="size-5 text-amber-600 dark:text-amber-400 shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </li>
                    </template>
                    <li x-show="filteredOptions.length === 0" class="py-4 text-center text-xs text-text-muted">
                        Tidak ada opsi yang sesuai
                    </li>
                </ul>
            </div>

            {{-- Desktop Floating Panel (>= 640px) --}}
            <div
                x-show="open"
                x-ref="desktopPanel"
                @click.outside="close()"
                @scroll.window="if (open) updatePosition()"
                @resize.window="if (open) updatePosition()"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                :style="desktopStyles"
                class="hidden sm:flex flex-col fixed z-[9999] rounded-2xl border border-slate-300 dark:border-white/15 bg-surface shadow-2xl p-1.5 max-h-[320px]"
            >
                @if ($isSearchable)
                    <div class="p-1.5 pb-2 border-b border-border shrink-0">
                        <input
                            type="text"
                            x-ref="searchInput"
                            x-model="search"
                            @keydown.arrow-down.prevent="focusedIndex = Math.min(focusedIndex + 1, filteredOptions.length - 1); scrollFocusedIntoView()"
                            @keydown.arrow-up.prevent="focusedIndex = Math.max(focusedIndex - 1, 0); scrollFocusedIntoView()"
                            @keydown.enter.prevent="if (focusedIndex >= 0 && focusedIndex < filteredOptions.length) select(filteredOptions[focusedIndex])"
                            placeholder="Cari..."
                            class="w-full min-h-[34px] rounded-lg border border-border bg-surface-2 px-2.5 py-1 text-xs text-text placeholder-text-muted focus:outline-none focus:border-amber-500"
                        >
                    </div>
                @endif

                <ul
                    role="listbox"
                    id="{{ $selectId }}-listbox"
                    x-ref="desktopListbox"
                    tabindex="-1"
                    class="overflow-y-auto space-y-1 flex-1 py-1 [scrollbar-width:thin]"
                >
                    <template x-for="(opt, idx) in filteredOptions" :key="opt.value">
                        <li
                            role="option"
                            :aria-selected="String(selectedValue) === String(opt.value)"
                            @click="select(opt)"
                            @mouseenter="focusedIndex = idx"
                            class="flex min-h-[40px] items-center justify-between rounded-xl px-3 py-1.5 text-xs font-medium cursor-pointer transition-colors"
                            :class="String(selectedValue) === String(opt.value)
                                ? 'bg-[#FEF3C7] text-[#78350F] dark:bg-amber-500/20 dark:text-amber-300 font-bold border border-amber-300 dark:border-amber-500/30'
                                : (focusedIndex === idx ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400' : 'text-text hover:bg-amber-500/10 hover:text-amber-600 dark:hover:text-amber-400')"
                        >
                            <div class="flex flex-col truncate pr-2">
                                <span class="truncate" x-text="opt.label"></span>
                                <span x-show="opt.description" class="text-[10px] text-text-muted truncate" x-text="opt.description"></span>
                            </div>
                            <svg x-show="String(selectedValue) === String(opt.value)" class="size-4 text-amber-600 dark:text-amber-400 shrink-0 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </li>
                    </template>
                    <li x-show="filteredOptions.length === 0" class="py-3 text-center text-xs text-text-muted">
                        Tidak ada opsi
                    </li>
                </ul>
            </div>
        </div>
    </template>
</div>
