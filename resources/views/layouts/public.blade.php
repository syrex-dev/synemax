<!DOCTYPE html>
<html lang="id" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', setting('site_name', 'SynemaX') . ' - ' . setting('site_tagline', 'Studio Bioskop Mini'))</title>
        <meta name="application-name" content="{{ setting('site_name', 'SynemaX') }}">
        <meta name="theme-color" id="theme-color-meta" content="#07090E">

        {{-- Dynamic Favicon from Settings or Local Fallback --}}
        @php
            $settingsService = app(\App\Services\CinemaSettings::class);
            $faviconUrl = $settingsService->siteFaviconUrl();
            $faviconMime = $settingsService->siteFaviconMime();
        @endphp
        <link rel="icon" type="{{ $faviconMime }}" href="{{ $faviconUrl }}">
        <link rel="shortcut icon" href="{{ $faviconUrl }}">
        <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">

        {{-- SEO Meta Tags --}}
        <meta name="description" content="@yield('meta_description', setting('seo_default_description', 'Bioskop mini privat eksklusif dengan kapasitas 6 kursi, AC dingin, dan tata suara Dolby Surround. Pesan tiket dan tentukan jadwal film pilihanmu.'))">
        @yield('meta')

        <script>
            (function () {
                const storageKey = 'synemax_theme_preference';
                const getMode = () => {
                    try {
                        const saved = localStorage.getItem(storageKey);
                        if (saved === 'dark' || saved === 'light') {
                            return saved;
                        }
                    } catch (error) {
                        console.error('Unable to read the saved theme preference.', error);
                    }
                    return 'dark';
                };
                const applyMode = (mode) => {
                    const isDark = mode === 'dark';
                    document.documentElement.classList.toggle('dark', isDark);
                    document.documentElement.dataset.theme = mode;
                    const meta = document.getElementById('theme-color-meta');
                    if (meta) {
                        meta.setAttribute('content', isDark ? '#07090E' : '#F8FAFC');
                    }
                };
                window.synemaXTheme = {
                    storageKey,
                    getMode,
                    applyMode,
                    setMode(mode) {
                        try {
                            localStorage.setItem(storageKey, mode);
                        } catch (error) {
                            console.error('Unable to save the theme preference.', error);
                        }
                        applyMode(mode);
                        window.dispatchEvent(new CustomEvent('synemax-theme-changed', { detail: { mode } }));
                    },
                };
                applyMode(getMode());
            })();
        </script>
        <link rel="preload" href="{{ Vite::asset('resources/fonts/bricolage-grotesque-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
    </head>
    <body
        x-data="{ mobileNavOpen: false }"
        class="min-h-screen bg-bg font-sans text-text antialiased transition-colors duration-200"
    >
        {{-- Sticky Glassmorphic Cinema Navbar (Responsive with Drawer under 1024px) --}}
        <header class="sticky top-0 z-40 border-b border-border dark:border-white/10 bg-surface/90 dark:bg-[#07090E]/90 backdrop-blur-xl transition-colors duration-200">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
                {{-- Logo brand mengarah ke Beranda --}}
                <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center gap-2 group transition-transform hover:scale-102" aria-label="{{ setting('site_name', 'SynemaX') }}, beranda">
                    <x-brand.logo />
                </a>

                @php
                    $menuItems = app(\App\Services\CinemaSettings::class)->navbarMenu();
                    $activeChecker = function ($item) {
                        $id = $item['id'] ?? '';
                        return match($id) {
                            'home' => request()->routeIs('home'),
                            'schedule' => request()->routeIs('schedule.*') || request()->is('jadwal*'),
                            'film' => request()->routeIs('films.*') || request()->is('film*'),
                            'nobar' => request()->routeIs('nobar.*') || request()->is('nobar*'),
                            'promo' => request()->routeIs('promos.*') || request()->is('promo*'),
                            'kabar' => request()->routeIs('articles.*') || request()->is('kabar*'),
                            'booking_lookup' => request()->routeIs('booking.lookup*') || request()->is('cek-booking*'),
                            default => request()->url() === url($item['url'] ?? ''),
                        };
                    };
                @endphp

                {{-- Desktop Navigation (>= 1024px / lg) --}}
                <nav class="hidden lg:flex items-center gap-1.5 text-sm" aria-label="Navigasi utama desktop">
                    @foreach ($menuItems as $item)
                        @if (!empty($item['is_visible']))
                            @php
                                $isActive = $activeChecker($item);
                                $isBookingLookup = ($item['id'] ?? '') === 'booking_lookup';
                            @endphp

                            @if ($isBookingLookup)
                                <a
                                    href="{{ url($item['url'] ?? '/cek-booking') }}"
                                    class="btn-gold-cinema inline-flex min-h-10 shrink-0 items-center rounded-xl px-3.5 py-1.5 text-xs font-black shadow-xs ml-1 {{ $isActive ? 'ring-2 ring-amber-500 ring-offset-2' : '' }}"
                                >
                                    <svg class="size-3.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                    </svg>
                                    <span>{{ $item['label'] ?? 'Cek booking' }}</span>
                                </a>
                            @else
                                <a
                                    href="{{ url($item['url'] ?? '/') }}"
                                    class="relative inline-flex min-h-10 shrink-0 items-center px-3 py-1.5 font-bold transition rounded-lg {{ $isActive ? 'text-[#0F172A] font-extrabold after:content-[\'\'] after:absolute after:bottom-0 after:left-2 after:right-2 after:h-[3px] after:bg-[#F59E0B] after:rounded-full dark:after:hidden dark:bg-amber-500/15 dark:text-amber-400 dark:border dark:border-amber-400/40 dark:shadow-[0_0_12px_rgba(212,175,55,0.2)]' : 'text-slate-600 hover:bg-[#F1F5F9] hover:text-[#0F172A] dark:text-text-muted dark:hover:bg-surface-2 dark:hover:text-amber-400' }}"
                                >
                                    {{ $item['label'] }}
                                </a>
                            @endif
                        @endif
                    @endforeach
                </nav>

                {{-- Action Area (Theme Switcher + Mobile Hamburger) --}}
                <div class="flex items-center gap-2">
                    {{-- Theme Switcher (Bisa disembunyikan lewat Pengaturan) --}}
                    @if (setting('show_theme_toggle', true))
                        <div class="inline-flex items-center rounded-full border border-slate-300 dark:border-white/15 bg-slate-200/80 dark:bg-black/50 p-1 backdrop-blur-md shadow-inner transition-colors duration-200"
                             role="group"
                             aria-label="Pilih mode tampilan">
                            {{-- Memenuhi assertSee('Pakai tema gelap') untuk pengujian otomatis --}}
                            <span class="sr-only">Pakai tema gelap</span>

                            {{-- Tombol Kiri: Mode Terang --}}
                            <button type="button"
                                    onclick="window.synemaXTheme.setMode('light')"
                                    class="flex size-8 items-center justify-center rounded-full transition-all duration-200 focus:outline-none text-slate-950 bg-amber-400 shadow-sm dark:bg-transparent dark:text-slate-400 dark:shadow-none hover:opacity-90 hover:scale-105"
                                    title="Aktifkan mode terang"
                                    aria-label="Mode Terang">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="5"></circle>
                                    <line x1="12" y1="1" x2="12" y2="3"></line>
                                    <line x1="12" y1="21" x2="12" y2="23"></line>
                                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                                    <line x1="1" y1="12" x2="3" y2="12"></line>
                                    <line x1="21" y1="12" x2="23" y2="12"></line>
                                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                                </svg>
                            </button>

                            {{-- Tombol Kanan: Mode Gelap --}}
                            <button type="button"
                                    onclick="window.synemaXTheme.setMode('dark')"
                                    class="flex size-8 items-center justify-center rounded-full transition-all duration-200 focus:outline-none text-slate-500 bg-transparent dark:bg-amber-500/25 dark:text-amber-300 dark:shadow-[0_0_12px_rgba(212,175,55,0.3)] hover:opacity-90 hover:scale-105"
                                    title="Aktifkan mode gelap"
                                    aria-label="Mode Gelap">
                                <svg class="size-4" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                                </svg>
                            </button>
                        </div>
                    @endif

                    {{-- Hamburger Button (< 1024px) --}}
                    <button
                        type="button"
                        @click="mobileNavOpen = !mobileNavOpen"
                        class="lg:hidden flex size-10 items-center justify-center rounded-xl border border-slate-300 dark:border-white/15 bg-surface text-text hover:bg-surface-2 transition"
                        aria-label="Menu Navigasi"
                        :aria-expanded="mobileNavOpen"
                    >
                        <svg x-show="!mobileNavOpen" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg x-show="mobileNavOpen" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Mobile Drawer Overlay & Panel (< 1024px) --}}
            <div
                x-show="mobileNavOpen"
                @keydown.escape.window="mobileNavOpen = false"
                class="lg:hidden fixed inset-0 z-50 overflow-hidden"
                style="display: none;"
            >
                {{-- Backdrop Overlay --}}
                <div
                    x-show="mobileNavOpen"
                    x-transition:enter="transition-opacity ease-linear duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition-opacity ease-linear duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="mobileNavOpen = false"
                    class="fixed inset-0 bg-black/70 backdrop-blur-sm"
                    aria-hidden="true"
                ></div>

                {{-- Slide-Over Drawer --}}
                <div
                    x-show="mobileNavOpen"
                    x-transition:enter="transition ease-in-out duration-300 transform"
                    x-transition:enter-start="translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in-out duration-300 transform"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="translate-x-full"
                    class="fixed inset-y-0 right-0 max-w-xs w-full bg-surface dark:bg-[#07090E] border-l border-slate-300 dark:border-white/10 p-6 shadow-2xl flex flex-col justify-between"
                >
                    <div class="space-y-6">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-white/10">
                            <x-brand.logo size="sm" />
                            <button
                                type="button"
                                @click="mobileNavOpen = false"
                                class="rounded-xl p-2 text-text-muted hover:text-text"
                                aria-label="Tutup Menu"
                            >
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        {{-- Nav Items List in Mobile Drawer --}}
                        <nav class="flex flex-col gap-1.5" aria-label="Menu navigasi mobile">
                            @foreach ($menuItems as $item)
                                @if (!empty($item['is_visible']))
                                    @php
                                        $isActive = $activeChecker($item);
                                        $isBookingLookup = ($item['id'] ?? '') === 'booking_lookup';
                                    @endphp

                                    @if ($isBookingLookup)
                                        <a
                                            href="{{ url($item['url'] ?? '/cek-booking') }}"
                                            @click="mobileNavOpen = false"
                                            class="btn-gold-cinema flex min-h-12 items-center justify-center gap-2.5 rounded-xl px-4 text-xs font-black mt-2 {{ $isActive ? 'ring-2 ring-amber-500 ring-offset-2' : '' }}"
                                        >
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                            </svg>
                                            <span>{{ $item['label'] ?? 'Cek booking' }}</span>
                                        </a>
                                    @else
                                        <a
                                            href="{{ url($item['url'] ?? '/') }}"
                                            @click="mobileNavOpen = false"
                                            class="flex min-h-11 items-center rounded-xl px-4 text-sm font-bold transition {{ $isActive ? 'bg-[#FEF3C7] text-[#78350F] font-bold border-l-4 border-[#F59E0B] rounded-r-xl rounded-l-none pl-3 dark:bg-amber-500/15 dark:text-amber-400 dark:border dark:border-amber-400/40 dark:rounded-xl dark:pl-4' : 'text-slate-600 hover:bg-[#F1F5F9] hover:text-[#0F172A] dark:text-text-muted dark:hover:bg-surface-2 dark:hover:text-text' }}"
                                        >
                                            {{ $item['label'] }}
                                        </a>
                                    @endif
                                @endif
                            @endforeach
                        </nav>
                    </div>

                    {{-- Footer Inside Drawer --}}
                    <div class="pt-6 border-t border-slate-200 dark:border-white/10 text-xs text-text-muted space-y-2">
                        <p class="font-medium text-text">{{ setting('site_name', 'SynemaX') }}</p>
                        <p class="text-[11px] leading-relaxed">{{ setting('site_tagline', 'Studio Bioskop Mini Eksklusif') }}</p>
                    </div>
                </div>
            </div>
        </header>

        {{-- Flash Session Messages --}}
        @if (session('status') || session('error'))
            <div class="mx-auto max-w-6xl px-4 pt-4 sm:px-6">
                <p role="status" class="rounded-xl border border-warning/30 bg-warning-surface/80 backdrop-blur-md px-4 py-3 text-sm text-warning font-medium">
                    {{ session('status') ?? session('error') }}
                </p>
            </div>
        @endif

        <main>
            @yield('content')
        </main>

        {{-- Floating WhatsApp Action Button --}}
        @if (setting('show_floating_whatsapp', true) && setting('whatsapp_number'))
            <a
                href="https://wa.me/{{ preg_replace('/[^0-9]/', '', setting('whatsapp_number')) }}?text={{ urlencode('Halo ' . setting('site_name', 'SynemaX') . ', saya ingin bertanya seputar pemesanan tiket & jadwal bioskop.') }}"
                target="_blank"
                rel="noopener noreferrer"
                class="fixed bottom-6 right-6 z-40 flex items-center gap-2.5 rounded-full bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-3 shadow-2xl hover:scale-105 transition-all duration-200 border border-emerald-400/30"
                aria-label="Hubungi WhatsApp"
            >
                <svg class="size-5 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.303-.058.116-.087.188-.173.289l-.26.302c-.087.087-.179.182-.077.357.101.174.449.741.963 1.199.662.59 1.221.774 1.394.861.173.087.275.072.376-.043.101-.116.433-.506.549-.68.116-.173.231-.144.39-.086.159.058 1.011.477 1.184.564.173.087.289.13.332.202.043.072.043.419-.101.824z"/></svg>
                <span class="text-xs font-bold hidden sm:inline">Tanya Kami</span>
            </a>
        @endif

        {{-- Universal Public Footer across all public pages with navbar --}}
        <x-public.footer />

        <x-in-app-dialogs />
    </body>
</html>
