@props([
    'src' => null,
    'alt' => '',
    'title' => null,
    'badge' => null,
    'aspect' => 'aspect-[16/9]',
    'class' => '',
    'imgClass' => 'size-full object-cover transition duration-500 group-hover:scale-105',
    'loading' => 'lazy',
    'rounded' => 'rounded-2xl',
])

@php
    $hasSrc = filled($src);
    $displayTitle = $title ?: ($alt ?: 'SynemaX');
@endphp

<div
    x-data="{
        loaded: false,
        failed: {{ $hasSrc ? 'false' : 'true' }},
        onImgLoad(e) {
            if (e.target && e.target.naturalWidth > 0) {
                this.loaded = true;
            } else {
                this.failed = true;
            }
        },
        onImgError() {
            this.failed = true;
        }
    }"
    {{ $attributes->merge(['class' => "relative overflow-hidden bg-slate-100 dark:bg-black/60 border border-border dark:border-white/5 {$aspect} {$rounded} {$class}"]) }}
>
    @if ($hasSrc)
        <img
            src="{{ $src }}"
            alt="{{ $alt }}"
            loading="{{ $loading }}"
            x-show="!failed"
            x-on:load="onImgLoad($event)"
            x-on:error="onImgError()"
            class="{{ $imgClass }} {{ $rounded }}"
        >
    @endif

    {{-- Fallback: Elegan, Bergradasi, Menampilkan Judul (Teks Alt Tidak Pernah Bocor Sebagai Teks Biasa) --}}
    <div
        x-show="failed"
        x-cloak
        class="absolute inset-0 flex flex-col justify-end p-4 bg-gradient-to-br from-slate-900 via-[#0C111D] to-[#1E1408] text-white select-none pointer-events-none {{ $rounded }}"
        aria-hidden="true"
    >
        <div class="absolute -right-6 -top-6 size-28 rounded-full bg-amber-500/20 blur-2xl"></div>
        <div class="relative z-10">
            @if ($badge)
                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-black tracking-wider uppercase bg-amber-500/20 text-amber-300 border border-amber-500/30 mb-1.5 shadow-xs">
                    {{ $badge }}
                </span>
            @endif
            <p class="text-xs sm:text-sm font-bold text-white line-clamp-2 leading-tight drop-shadow-sm">
                {{ $displayTitle }}
            </p>
        </div>
    </div>
</div>
