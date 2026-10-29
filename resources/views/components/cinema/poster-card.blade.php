@props([
    'film',
    'rank' => null,
    'isComingSoon' => false,
])

<article class="group relative flex flex-col overflow-hidden rounded-2xl border border-border dark:border-white/10 bg-surface dark:bg-[#0B0F19] transition-all duration-300 hover:border-amber-400/60 hover:shadow-[0_12px_30px_-5px_rgba(212,175,55,0.25)] hover:-translate-y-1">
    {{-- Poster Container Rasio 2:3 Tetap Anti-Layout Shift --}}
    <div class="relative aspect-[2/3] w-full overflow-hidden bg-gradient-to-br from-[#161C2E] via-[#0C101B] to-[#06080F]">
        @if ($film->posterUrl())
            <img src="{{ $film->posterUrl() }}"
                 alt="Poster {{ $film->title }}"
                 loading="lazy"
                 width="500"
                 height="750"
                 class="size-full object-cover transition-transform duration-500 ease-out group-hover:scale-108">
        @else
            {{-- Placeholder Sinematik Mewah Bila Belum Ada Gambar --}}
            <div class="relative flex size-full flex-col justify-between p-4 text-left select-none overflow-hidden">
                {{-- Ambient Light Accent --}}
                <div class="pointer-events-none absolute -right-6 -top-6 size-28 rounded-full bg-amber-500/15 blur-xl"></div>
                <div class="pointer-events-none absolute -left-6 -bottom-6 size-28 rounded-full bg-blue-500/10 blur-xl"></div>

                {{-- Watermark Pattern Sinema --}}
                <div class="pointer-events-none absolute inset-0 opacity-5 flex items-center justify-center">
                    <svg class="size-36 text-amber-300" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2zm0 3v2h2V7H4zm0 4v2h2v-2H4zm0 4v2h2v-2H4zm16-8h-2v2h2V7zm0 4h-2v2h2v-2zm0 4h-2v2h2v-2zm-12-8h8v10H8V7z"/>
                    </svg>
                </div>

                {{-- Logo / Studio Identifier --}}
                <div class="relative z-10 flex items-center gap-1.5 opacity-80">
                    <span class="flex size-5 items-center justify-center rounded-md bg-amber-500/20 text-amber-400">
                        <svg class="size-3" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                    </span>
                    <span class="text-[9px] font-black uppercase tracking-widest text-amber-300/90">SynemaX</span>
                </div>

                {{-- Judul Tengah Poster --}}
                <div class="relative z-10 my-auto py-2">
                    <h4 class="font-black text-sm sm:text-base text-white tracking-tight leading-snug line-clamp-3 drop-shadow-md">
                        {{ $film->title }}
                    </h4>
                    <span class="mt-1.5 inline-block text-[10px] font-bold text-amber-400 uppercase tracking-wider">
                        {{ $film->genre }}
                    </span>
                </div>

                {{-- Bottom Subtle Info --}}
                <div class="relative z-10 flex items-center justify-between text-[10px] font-tabular text-slate-400 border-t border-white/10 pt-2">
                    <span>Dolby Audio</span>
                    <span>{{ $film->duration_minutes }}m</span>
                </div>
            </div>
        @endif

        {{-- Badge Nomor Ranking CGV (Top Left) --}}
        @if ($rank)
            <div class="absolute left-0 top-0 z-10 flex size-9 sm:size-10 items-center justify-center rounded-br-2xl bg-gradient-to-br from-amber-400 via-amber-500 to-amber-700 text-[#07090E] font-black text-base sm:text-lg shadow-[0_4px_12px_rgba(0,0,0,0.6)]">
                {{ $rank }}
            </div>
        @endif

        {{-- Badge Label Segera Hadir / Tanggal Rilis (Coming Soon) --}}
        @if ($isComingSoon)
            <div class="absolute left-2.5 top-2.5 z-10">
                <span class="inline-flex items-center gap-1 rounded-lg bg-amber-500 text-black px-2.5 py-1 text-[10px] font-black uppercase tracking-wider shadow-md">
                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>
                        @if ($film->release_date)
                            {{ format_tanggal_pendek_id($film->release_date) }}
                        @else
                            Segera
                        @endif
                    </span>
                </span>
            </div>
        @endif

        {{-- Rating Usia Badge (Top Right) --}}
        <span class="absolute right-2.5 top-2.5 z-10 rounded-lg bg-black/85 backdrop-blur-md px-2 py-0.5 font-tabular text-[10px] font-black uppercase text-amber-400 border border-amber-400/30 shadow-md">
            {{ $film->age_rating }}
        </span>

        {{-- Durasi Badge (Bottom Left) --}}
        <span class="absolute left-2.5 bottom-2.5 z-10 rounded-md bg-black/80 backdrop-blur-md px-2 py-0.5 font-tabular text-[10px] font-bold text-slate-200">
            {{ $film->duration_minutes }}m
        </span>

        {{-- Hover/Tap Overlay --}}
        <div class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-2 bg-black/80 p-3 opacity-0 backdrop-blur-xs transition duration-200 group-hover:opacity-100 focus-within:opacity-100">
            @if ($isComingSoon)
                <button type="button"
                        @click="openTrailer(@js($film))"
                        class="inline-flex min-h-10 w-full items-center justify-center gap-1.5 rounded-xl bg-white/20 px-3 text-xs font-bold text-white hover:bg-white/30 transition shadow-sm">
                    <svg class="size-4 text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M8 5v14l11-7z" />
                    </svg>
                    <span>Tonton Trailer</span>
                </button>
                <a href="{{ route('schedule.index') }}"
                   class="btn-gold-cinema inline-flex min-h-10 w-full items-center justify-center rounded-xl px-3 text-xs font-black shadow-md">
                    Lihat Jadwal
                </a>
            @else
                <button type="button"
                        @click="openTrailer(@js($film))"
                        class="inline-flex min-h-10 w-full items-center justify-center gap-1.5 rounded-xl bg-white/20 px-3 text-xs font-bold text-white hover:bg-white/30 transition shadow-sm">
                    <svg class="size-4 text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M8 5v14l11-7z" />
                    </svg>
                    <span>Trailer</span>
                </button>
                <a href="{{ route('schedule.index') }}"
                   class="btn-gold-cinema inline-flex min-h-10 w-full items-center justify-center rounded-xl px-3 text-xs font-black shadow-md">
                    Pesan tiket
                </a>
            @endif
        </div>
    </div>

    {{-- Metadata Judul & Sub Judul di Bawah Poster (Ala CGV) --}}
    <div class="p-3">
        <h3 class="text-xs sm:text-sm font-black text-text leading-snug line-clamp-1 group-hover:text-amber-500 transition">
            <a href="{{ route('schedule.index') }}">
                {{ $film->title }}
            </a>
        </h3>
        <p class="mt-1 text-[11px] text-text-muted truncate">
            {{ $film->genre }}
        </p>
    </div>
</article>
