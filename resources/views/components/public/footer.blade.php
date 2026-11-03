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
    $whatsapp = $settings->contactWhatsapp();
@endphp

<footer class="hidden md:block mt-auto border-t border-border bg-[#F8FAFC] dark:bg-[#07090E] transition-colors duration-200">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-8">
        {{-- Grid 3 Kolom Proporsional --}}
        <div class="grid grid-cols-12 gap-8 items-start">
            {{-- Kolom 1: Brand & Tagline (5 kolom) --}}
            <div class="col-span-12 lg:col-span-5 space-y-3">
                <a href="{{ route('home') }}" class="inline-flex items-center" aria-label="Beranda {{ $siteName }}">
                    <x-brand.logo size="default" />
                </a>
                <p class="text-xs font-semibold text-text">
                    {{ $tagline }}
                </p>
                <p class="text-xs text-text-muted leading-relaxed max-w-sm">
                    Studio bioskop mini eksklusif berpendingin udara dengan proyektor laser tajam dan tata suara jernih untuk pengalaman nonton privat terbaik.
                </p>
            </div>

            {{-- Kolom 2: Navigasi Cepat (4 kolom) --}}
            <div class="col-span-12 sm:col-span-6 lg:col-span-4 space-y-3">
                <h4 class="text-xs font-black uppercase tracking-wider text-text">Jelajahi</h4>
                <nav class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs font-medium text-text-muted" aria-label="Navigasi footer">
                    <a href="{{ route('schedule.index') }}" class="hover:text-amber-500 transition-colors">Jadwal Tayang</a>
                    <a href="{{ route('films.index') }}" class="hover:text-amber-500 transition-colors">Katalog Film</a>
                    <a href="{{ route('nobar.index') }}" class="hover:text-amber-500 transition-colors">Nonton Bareng</a>
                    <a href="{{ route('promos.index') }}" class="hover:text-amber-500 transition-colors">Promo Spesial</a>
                    <a href="{{ route('articles.index') }}" class="hover:text-amber-500 transition-colors">Kabar & Berita</a>
                    <a href="{{ route('articles.index', ['tab' => 'harga-faq']) }}" class="hover:text-amber-500 transition-colors">Harga & FAQ</a>
                    <a href="{{ route('booking.ticket.index') }}" class="hover:text-amber-500 transition-colors">Cek E-Tiket</a>
                    @if ($whatsapp)
                        <a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Halo admin SynemaX, saya ingin bertanya.') }}" target="_blank" rel="noopener noreferrer" class="hover:text-amber-500 transition-colors">Chat Admin</a>
                    @endif
                </nav>
            </div>

            {{-- Kolom 3: Layanan & Media Sosial (3 kolom) --}}
            <div class="col-span-12 sm:col-span-6 lg:col-span-3 space-y-3">
                <h4 class="text-xs font-black uppercase tracking-wider text-text">Hubungi Kami</h4>
                <div class="space-y-2 text-xs text-text-muted">
                    @if ($email)
                        <a href="mailto:{{ $email }}" class="flex items-center gap-2 hover:text-amber-500 transition-colors">
                            <svg class="size-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span class="truncate">{{ $email }}</span>
                        </a>
                    @endif

                    @if ($whatsapp)
                        <a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Halo admin SynemaX, saya ingin bertanya.') }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 hover:text-amber-500 transition-colors">
                            <svg class="size-4 shrink-0 text-emerald-500" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/>
                            </svg>
                            <span>WhatsApp CS</span>
                        </a>
                    @endif
                </div>

                {{-- Ikon Sosial Media --}}
                <div class="pt-1 flex items-center gap-3 text-text-muted">
                    @if ($instagramUrl)
                        <a href="{{ $instagramUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex size-8 items-center justify-center rounded-lg border border-border bg-surface hover:border-amber-400 hover:text-amber-500 transition-colors" title="{{ $instagramLabel ?: 'Instagram' }}">
                            <svg class="size-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                        </a>
                    @endif

                    @if ($tiktokUrl)
                        <a href="{{ $tiktokUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex size-8 items-center justify-center rounded-lg border border-border bg-surface hover:border-amber-400 hover:text-amber-500 transition-colors" title="{{ $tiktokLabel ?: 'TikTok' }}">
                            <svg class="size-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.24 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                            </svg>
                        </a>
                    @endif

                    @if ($youtubeUrl)
                        <a href="{{ $youtubeUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex size-8 items-center justify-center rounded-lg border border-border bg-surface hover:border-amber-400 hover:text-amber-500 transition-colors" title="{{ $youtubeLabel ?: 'YouTube' }}">
                            <svg class="size-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Baris Bawah: Hak Cipta --}}
        <div class="mt-8 pt-5 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-text-muted">
            <p class="text-[11px] leading-relaxed">
                {!! ! empty(trim((string) $footerText)) ? e($footerText) : '&copy; ' . date('Y') . ' ' . e($siteName) . '. Hak cipta dilindungi.' !!}
            </p>
            <p class="text-[11px] text-text-muted/70">
                Studio Bioskop Mini Eksklusif Berpendingin Udara
            </p>
        </div>
    </div>
</footer>