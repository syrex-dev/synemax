@php
    $email = setting('contact_email');
    $instagram = setting('social_instagram');
    $tiktok = setting('social_tiktok');
    $address = setting('contact_address');
    $mapsUrl = setting('contact_maps_url');

    $capacity = setting('capacity_per_session', 6);
    $startPrice = setting('weekday_price', 25000);
    $tagline = setting('site_tagline', 'Studio Bioskop Mini Eksklusif Berpendingin Udara');
    $footerText = setting('footer_text', 'Hak Cipta Dilindungi.');
    $siteName = setting('site_name', 'SynemaX');
@endphp

<footer class="border-t border-slate-200 dark:border-white/10 bg-[#F8FAFC] dark:bg-[#07090E] transition-colors duration-200">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-12 pb-28 sm:pb-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8 lg:gap-10">
            {{-- Kolom 1: Identitas & Chip (4 cols) --}}
            <div class="lg:col-span-4 space-y-4">
                <a href="{{ route('home') }}" class="inline-flex items-center">
                    <x-brand.logo size="default" />
                </a>

                <p class="text-xs sm:text-sm text-slate-600 dark:text-text-muted leading-relaxed max-w-sm">
                    {{ $tagline }}
                </p>

                {{-- Dua Chip Ringkas --}}
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 dark:border-white/15 bg-white dark:bg-white/5 px-2.5 py-1 text-[11px] font-bold text-slate-800 dark:text-slate-200 shadow-2xs">
                        <svg class="size-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>{{ $capacity }} Kursi VIP</span>
                    </span>

                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 dark:border-white/15 bg-white dark:bg-white/5 px-2.5 py-1 text-[11px] font-bold text-slate-800 dark:text-slate-200 shadow-2xs">
                        <svg class="size-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        <span>Mulai Rp {{ number_format($startPrice, 0, ',', '.') }}</span>
                    </span>
                </div>
            </div>

            {{-- Kolom 2: JELAJAHI (2 cols) --}}
            <div class="lg:col-span-2">
                <h3 class="text-xs font-black uppercase tracking-[0.15em] text-slate-900 dark:text-slate-200 mb-3 sm:mb-4">
                    Jelajahi
                </h3>
                <ul class="space-y-2.5 text-xs font-bold text-slate-600 dark:text-text-muted">
                    <li>
                        <a href="{{ route('schedule.index') }}" class="inline-flex min-h-[36px] sm:min-h-0 items-center hover:text-amber-700 dark:hover:text-amber-400 transition-colors">
                            Jadwal
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('films.index') }}" class="inline-flex min-h-[36px] sm:min-h-0 items-center hover:text-amber-700 dark:hover:text-amber-400 transition-colors">
                            Film
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('nobar.index') }}" class="inline-flex min-h-[36px] sm:min-h-0 items-center hover:text-amber-700 dark:hover:text-amber-400 transition-colors">
                            Nobar
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('promos.index') }}" class="inline-flex min-h-[36px] sm:min-h-0 items-center hover:text-amber-700 dark:hover:text-amber-400 transition-colors">
                            Promo
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('articles.index') }}" class="inline-flex min-h-[36px] sm:min-h-0 items-center hover:text-amber-700 dark:hover:text-amber-400 transition-colors">
                            Kabar
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Kolom 3: BANTUAN (2 cols) --}}
            <div class="lg:col-span-2">
                <h3 class="text-xs font-black uppercase tracking-[0.15em] text-slate-900 dark:text-slate-200 mb-3 sm:mb-4">
                    Bantuan
                </h3>
                <ul class="space-y-2.5 text-xs font-bold text-slate-600 dark:text-text-muted">
                    <li>
                        <a href="{{ route('booking.lookup.form') }}" class="inline-flex min-h-[36px] sm:min-h-0 items-center hover:text-amber-700 dark:hover:text-amber-400 transition-colors">
                            Cek booking
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('articles.index', ['tab' => 'harga-faq']) }}" class="inline-flex min-h-[36px] sm:min-h-0 items-center hover:text-amber-700 dark:hover:text-amber-400 transition-colors">
                            Harga & FAQ
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Kolom 4: HUBUNGI & IKUTI (4 cols) --}}
            <div class="lg:col-span-4 space-y-3">
                <h3 class="text-xs font-black uppercase tracking-[0.15em] text-slate-900 dark:text-slate-200 mb-3 sm:mb-4">
                    Hubungi & Ikuti
                </h3>

                <div class="space-y-2.5 text-xs">
                    @if ($email)
                        <div>
                            <a href="mailto:{{ $email }}" class="inline-flex min-h-[44px] items-center gap-2.5 text-slate-700 dark:text-slate-300 hover:text-amber-700 dark:hover:text-amber-400 font-bold transition-colors">
                                <span class="flex size-7 items-center justify-center rounded-lg bg-slate-200 dark:bg-white/10 shrink-0">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </span>
                                <span class="break-all">{{ $email }}</span>
                            </a>
                        </div>
                    @endif

                    @if ($instagram)
                        <div>
                            <a href="https://instagram.com/{{ ltrim($instagram, '@') }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-[44px] items-center gap-2.5 text-slate-700 dark:text-slate-300 hover:text-amber-700 dark:hover:text-amber-400 font-bold transition-colors">
                                <span class="flex size-7 items-center justify-center rounded-lg bg-slate-200 dark:bg-white/10 shrink-0">
                                    <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                </span>
                                <span>{{ str_starts_with($instagram, '@') ? $instagram : '@' . $instagram }}</span>
                            </a>
                        </div>
                    @endif

                    @if ($tiktok)
                        <div>
                            <a href="https://tiktok.com/@{{ ltrim($tiktok, '@') }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-[44px] items-center gap-2.5 text-slate-700 dark:text-slate-300 hover:text-amber-700 dark:hover:text-amber-400 font-bold transition-colors">
                                <span class="flex size-7 items-center justify-center rounded-lg bg-slate-200 dark:bg-white/10 shrink-0">
                                    <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.24 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                                    </svg>
                                </span>
                                <span>{{ str_starts_with($tiktok, '@') ? $tiktok : '@' . $tiktok }}</span>
                            </a>
                        </div>
                    @endif

                    @if ($address)
                        <div class="pt-2 text-slate-600 dark:text-text-muted leading-relaxed">
                            <p>{{ $address }}</p>
                            @if ($mapsUrl)
                                <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-[36px] items-center gap-1 text-accent-link text-xs font-bold hover:underline mt-0.5">
                                    Buka di Google Maps ↗
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Baris Bawah: Hak Cipta Saja (Tanpa Link Dev / Design System) --}}
        <div class="mt-10 pt-6 border-t border-slate-200 dark:border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 dark:text-text-subtle">
            <p>&copy; {{ date('Y') }} {{ $siteName }}. {{ $footerText }}</p>
            <p class="text-[11px] text-slate-400 dark:text-slate-600">Premiere Mini Cinema Experience</p>
        </div>
    </div>
</footer>
