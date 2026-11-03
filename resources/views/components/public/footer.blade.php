@php
    $settings = app(\App\Services\CinemaSettings::class);
    $email = $settings->contactEmail();
    $instagramUrl = $settings->instagramUrl();
    $instagramLabel = $settings->instagramLabel();
    $tiktokUrl = $settings->tiktokUrl();
    $tiktokLabel = $settings->tiktokLabel();
    $youtubeUrl = $settings->youtubeUrl();
    $youtubeLabel = $settings->youtubeLabel();
    $tagline = $settings->string('site_tagline', 'Studio Bioskop Mini Privat');
    $footerText = $settings->formattedFooterText();
    $siteName = $settings->string('site_name', 'SynemaX');
@endphp

<footer class="hidden md:block border-t border-border bg-[#F8FAFC] dark:bg-[#07090E] transition-colors duration-200">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
        {{-- Baris 1: Logo + Tagline (Kiri), Tautan Inline (Tengah), Kontak (Kanan) --}}
        <div class="flex flex-col lg:flex-row items-center justify-between gap-4">
            {{-- Kiri: Logo kecil + Tagline satu baris --}}
            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ route('home') }}" class="inline-flex items-center" aria-label="Beranda {{ $siteName }}">
                    <x-brand.logo size="sm" />
                </a>
                <span class="text-xs text-text-muted hidden sm:inline" aria-hidden="true">&bull;</span>
                <span class="text-xs text-text-muted font-medium truncate max-w-xs">{{ $tagline }}</span>
            </div>

            {{-- Tengah: Tautan Inline --}}
            <nav class="flex flex-wrap items-center justify-center gap-x-6 gap-y-1.5 text-xs font-bold text-slate-600 dark:text-slate-300" aria-label="Navigasi footer">
                <a href="{{ route('schedule.index') }}" class="hover:text-amber-500 transition-colors">Jadwal</a>
                <a href="{{ route('films.index') }}" class="hover:text-amber-500 transition-colors">Film</a>
                <a href="{{ route('nobar.index') }}" class="hover:text-amber-500 transition-colors">Nobar</a>
                <a href="{{ route('promos.index') }}" class="hover:text-amber-500 transition-colors">Promo</a>
                <a href="{{ route('articles.index') }}" class="hover:text-amber-500 transition-colors">Kabar</a>
                <a href="{{ route('articles.index', ['tab' => 'harga-faq']) }}" class="hover:text-amber-500 transition-colors">Harga & FAQ</a>
                <a href="{{ route('booking.ticket.index') }}" class="hover:text-amber-500 transition-colors">Tiket</a>
                @if ($whatsapp = $settings->contactWhatsapp())
                    <a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Halo admin SynemaX, saya ingin bertanya.') }}" target="_blank" rel="noopener noreferrer" class="hover:text-amber-500 transition-colors">Chat admin</a>
                @endif
            </nav>

            {{-- Kanan: Kontak (label teks + ikon kecil) --}}
            <div class="flex flex-wrap items-center justify-end gap-3 text-xs text-slate-600 dark:text-slate-300 shrink-0">
                @if ($email)
                    <a href="mailto:{{ $email }}" class="inline-flex items-center gap-1.5 hover:text-amber-500 transition-colors">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span>{{ $email }}</span>
                    </a>
                @endif

                @if ($instagramUrl)
                    <a href="{{ $instagramUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 hover:text-amber-500 transition-colors">
                        <svg class="size-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                        <span>{{ $instagramLabel ?: 'Instagram' }}</span>
                    </a>
                @endif

                @if ($tiktokUrl)
                    <a href="{{ $tiktokUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 hover:text-amber-500 transition-colors">
                        <svg class="size-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.24 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                        </svg>
                        <span>{{ $tiktokLabel ?: 'TikTok' }}</span>
                    </a>
                @endif

                @if ($youtubeUrl)
                    <a href="{{ $youtubeUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 hover:text-amber-500 transition-colors">
                        <svg class="size-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                        <span>{{ $youtubeLabel ?: 'YouTube' }}</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- Baris 2: Hak Cipta Satu Baris Saja --}}
        <div class="mt-4 pt-3 border-t border-slate-200/80 dark:border-white/10 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
            <p class="text-[11px] leading-relaxed">
                {!! ! empty(trim((string) $footerText)) ? e($footerText) : '&copy; ' . date('Y') . ' ' . e($siteName) . '. Hak cipta dilindungi.' !!}
            </p>
        </div>
    </div>
</footer>