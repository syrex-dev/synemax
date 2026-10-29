@props([
    'banner',
])

<article class="group relative flex flex-col overflow-hidden rounded-3xl border border-border dark:border-white/10 bg-surface dark:bg-[#0B0F19] transition-all duration-300 hover:border-amber-400/60 hover:shadow-[0_12px_30px_-5px_rgba(212,175,55,0.2)] hover:-translate-y-1">
    {{-- Container Banner Rasio 16:9 Anti-Layout Shift --}}
    <div class="relative aspect-[16/9] w-full overflow-hidden bg-gradient-to-br from-[#1E2538] via-[#0E1322] to-[#070A12]">
        @if ($banner->imageUrl())
            <img src="{{ $banner->imageUrl() }}"
                 alt="{{ $banner->title }}"
                 loading="lazy"
                 class="size-full object-cover transition-transform duration-500 group-hover:scale-105">
            <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent"></div>
        @else
            {{-- Visual Ambient Gradient Cinema --}}
            <div class="relative size-full p-5 sm:p-6 flex flex-col justify-between overflow-hidden">
                <div class="pointer-events-none absolute -right-10 -top-10 size-48 rounded-full bg-amber-500/20 blur-2xl"></div>
                <div class="pointer-events-none absolute -left-10 -bottom-10 size-48 rounded-full bg-cyan-500/15 blur-2xl"></div>

                {{-- Watermark Simbol Cinema --}}
                <div class="pointer-events-none absolute right-3 bottom-2 opacity-10">
                    <svg class="size-28 text-amber-300" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2zm0 3v2h2V7H4zm0 4v2h2v-2H4zm0 4v2h2v-2H4zm16-8h-2v2h2V7zm0 4h-2v2h2v-2zm0 4h-2v2h2v-2zm-12-8h8v10H8V7z"/>
                    </svg>
                </div>

                {{-- Top Badge --}}
                <div class="relative z-10 flex items-center justify-between">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-400/40 bg-amber-500/20 backdrop-blur-md px-3 py-1 text-[10px] font-black uppercase tracking-wider text-amber-300 shadow-sm">
                        <span class="size-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                        {{ $banner->badge ?: 'Promo Spesial' }}
                    </span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-white/50">SynemaX Deal</span>
                </div>

                {{-- Center Content --}}
                <div class="relative z-10 my-auto">
                    <h3 class="text-base sm:text-lg font-black text-white leading-tight drop-shadow-md group-hover:text-amber-300 transition">
                        {{ $banner->title }}
                    </h3>
                    @if ($banner->subtitle)
                        <p class="mt-1 text-xs text-slate-300/90 line-clamp-2">
                            {{ $banner->subtitle }}
                        </p>
                    @endif
                </div>

                {{-- Bottom CTA Link --}}
                <div class="relative z-10 pt-2 flex items-center justify-between border-t border-white/10">
                    <span class="text-[11px] font-black text-amber-400 group-hover:underline flex items-center gap-1">
                        <span>{{ $banner->cta_text ?: 'Lihat Rincian' }}</span>
                        <svg class="size-3.5 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                </div>
            </div>
        @endif

        {{-- Clickable Full Overlay Link --}}
        <a href="{{ $banner->cta_url ?: route('articles.index') }}"
           class="absolute inset-0 z-20"
           aria-label="{{ $banner->title }}"></a>
    </div>
</article>
