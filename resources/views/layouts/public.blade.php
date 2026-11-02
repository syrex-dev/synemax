<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', setting('site_name', 'SynemaX') . ' - ' . setting('site_tagline', 'Studio Bioskop Mini'))</title>
        <meta name="application-name" content="{{ setting('site_name', 'SynemaX') }}">
        <meta name="color-scheme" id="color-scheme-meta" content="light dark">
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
                const storageKeys = ['synemax_theme_preference', 'synemax-theme'];
                const getSavedMode = () => {
                    try {
                        for (const k of storageKeys) {
                            const val = localStorage.getItem(k);
                            if (val === 'dark' || val === 'light' || val === 'system') {
                                return val;
                            }
                        }
                    } catch (e) {}
                    return 'dark';
                };
                const resolveEffective = (mode) => {
                    if (mode === 'system') {
                        return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
                    }
                    return mode === 'light' ? 'light' : 'dark';
                };
                const applyMode = (mode) => {
                    const effective = resolveEffective(mode);
                    const isDark = effective === 'dark';
                    const docEl = document.documentElement;

                    docEl.classList.toggle('dark', isDark);
                    docEl.dataset.theme = effective;
                    docEl.dataset.themeMode = mode;

                    const schemeMeta = document.getElementById('color-scheme-meta');
                    if (schemeMeta) {
                        schemeMeta.setAttribute('content', effective);
                    }

                    const themeMeta = document.getElementById('theme-color-meta');
                    if (themeMeta) {
                        themeMeta.setAttribute('content', isDark ? '#07090E' : '#FFFFFF');
                    }
                };

                window.synemaXTheme = {
                    getMode: getSavedMode,
                    applyMode,
                    setMode(mode) {
                        try {
                            for (const k of storageKeys) {
                                localStorage.setItem(k, mode);
                            }
                        } catch (e) {}
                        applyMode(mode);
                        window.dispatchEvent(new CustomEvent('synemax-theme-changed', { detail: { mode, effective: resolveEffective(mode) } }));
                    },
                };

                if (window.matchMedia) {
                    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                        const cur = getSavedMode();
                        if (cur === 'system') {
                            applyMode('system');
                        }
                    });
                }

                applyMode(getSavedMode());
            })();
        </script>
        <link rel="preload" href="{{ Vite::asset('resources/fonts/bricolage-grotesque-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
        @php
            $isActionPage = request()->routeIs('booking.create') || request()->routeIs('booking.payment');
        @endphp
        <style>
            :root {
                --bottom-bar-h: {{ $isActionPage ? '0px' : 'calc(60px + env(safe-area-inset-bottom, 0px))' }};
            }
            @media (min-width: 768px) {
                :root {
                    --bottom-bar-h: 0px;
                }
            }
        </style>
    </head>
    <body
        x-data="{
            menuSheetOpen: false,
            currentTheme: window.synemaXTheme ? window.synemaXTheme.getMode() : 'dark',
            toggleTheme() {
                const next = this.currentTheme === 'dark' ? 'light' : 'dark';
                if (window.synemaXTheme) {
                    window.synemaXTheme.setMode(next);
                }
                this.currentTheme = next;
            }
        }"
        @synemax-theme-changed.window="currentTheme = $event.detail.mode"
        :class="{ 'overflow-hidden': menuSheetOpen }"
        class="min-h-screen bg-bg font-sans text-text antialiased transition-colors duration-200"
    >
        {{-- Unpaid Booking Global Banner (Section 2) --}}
        <x-public.unpaid-booking-banner />

        {{-- Sticky Cinema Navbar (Translucent Glassmorphic Blur across mobile & desktop) --}}
        <header class="sticky top-0 z-40 border-b border-border/80 dark:border-white/10 bg-surface/80 dark:bg-[#07090E]/80 backdrop-blur-md supports-[backdrop-filter]:bg-surface/75 dark:supports-[backdrop-filter]:bg-[#07090E]/75 transition-colors duration-200 pt-[env(safe-area-inset-top)]">
            <div class="mx-auto flex h-14 md:h-16 max-w-6xl items-center justify-between gap-3 px-4 sm:px-6">
                {{-- Element 1: Logo Brand --}}
                <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center gap-2 group transition-transform hover:scale-102 shrink-0" aria-label="{{ setting('site_name', 'SynemaX') }}, beranda">
                    <x-brand.logo size="default" />
                </a>

                @php
                    $allMenuItems = app(\App\Services\CinemaSettings::class)->navbarMenu();
                    $activeChecker = function ($item) {
                        $id = $item['key'] ?? $item['id'] ?? '';
                        return match($id) {
                            'home' => request()->routeIs('home'),
                            'schedule' => request()->routeIs('schedule.*') || request()->is('jadwal*'),
                            'film' => request()->routeIs('films.*') || request()->is('film*'),
                            'nobar' => request()->routeIs('nobar.*') || request()->is('nobar*'),
                            'promo' => request()->routeIs('promos.*') || request()->is('promo*'),
                            'articles', 'kabar' => request()->routeIs('articles.*') || request()->is('kabar*'),
                            'lookup', 'booking_lookup', 'tickets', 'tiket' => request()->routeIs('booking.lookup*', 'booking.ticket*') || request()->is('cek-booking*', 'tiket*'),
                            default => request()->url() === url($item['url'] ?? ''),
                        };
                    };

                    $visibleItems = array_values(array_filter($allMenuItems, fn ($i) => !empty($i['is_visible'])));
                    $bottomBarCandidates = array_values(array_filter($visibleItems, fn ($i) => ($i['mobile_position'] ?? 'bottom_bar') === 'bottom_bar'));
                    if (count($bottomBarCandidates) === 0) {
                        $bottomBarItems = array_slice($visibleItems, 0, 4);
                    } else {
                        $bottomBarItems = array_slice($bottomBarCandidates, 0, 4);
                    }
                    $bottomBarKeys = array_map(fn ($i) => $i['key'] ?? $i['id'] ?? '', $bottomBarItems);
                    $menuSheetItems = array_values(array_filter($visibleItems, fn ($i) => !in_array($i['key'] ?? $i['id'] ?? '', $bottomBarKeys, true)));
                @endphp

                {{-- Desktop Navigation (>= 768px / md) --}}
                <nav class="hidden md:flex items-center gap-1.5 text-sm" aria-label="Navigasi utama desktop">
                    @foreach ($allMenuItems as $item)
                        @if (!empty($item['is_visible']))
                            @php
                                $isActive = $activeChecker($item);
                                $isBookingLookup = in_array($item['key'] ?? $item['id'] ?? '', ['lookup', 'booking_lookup', 'tickets', 'tiket'], true);
                            @endphp

                            @if ($isBookingLookup)
                                <a
                                    href="{{ url($item['url'] ?? '/tiket') }}"
                                    class="btn-gold-cinema inline-flex min-h-10 shrink-0 items-center rounded-xl px-3.5 py-1.5 text-xs font-black shadow-xs ml-1 {{ $isActive ? 'ring-2 ring-amber-500 ring-offset-2' : '' }}"
                                >
                                    <svg class="size-3.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                    </svg>
                                    <span>{{ in_array($item['label'] ?? '', ['Cek booking', 'Booking'], true) ? 'Tiket' : ($item['label'] ?? 'Tiket') }}</span>
                                </a>
                            @else
                                <a
                                    href="{{ url($item['url'] ?? '/') }}"
                                    class="relative inline-flex min-h-10 shrink-0 items-center px-3 py-1.5 font-bold transition rounded-lg {{ $isActive ? 'text-[#0F172A] font-extrabold after:content-[\'\'] after:absolute after:bottom-0 after:left-2 after:right-2 after:h-[3px] after:bg-[#F59E0B] after:rounded-full dark:after:hidden dark:bg-amber-500/15 dark:text-amber-400 dark:border dark:border-amber-400/40' : 'text-slate-600 hover:bg-[#F1F5F9] hover:text-[#0F172A] dark:text-text-muted dark:hover:bg-surface-2 dark:hover:text-amber-400' }}"
                                >
                                    {{ $item['label'] }}
                                </a>
                            @endif
                        @endif
                    @endforeach
                </nav>

                {{-- Action Area: Desktop Segmented Switcher / Mobile Single Icon (NO hamburger) --}}
                <div class="flex items-center gap-2 shrink-0">
                    {{-- Desktop Segmented Theme Switcher (>= 768px) --}}
                    @if (setting('show_theme_toggle', true))
                        <div class="hidden md:inline-flex items-center rounded-full border border-slate-300 dark:border-white/15 bg-slate-200/80 dark:bg-black/50 p-1 backdrop-blur-md shadow-inner transition-colors duration-200"
                             role="group"
                             aria-label="Pilih mode tampilan">
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

                        {{-- Mobile: Single Icon 44px Theme Toggle Button (< 768px, NO hamburger) --}}
                        <button
                            type="button"
                            @click="toggleTheme()"
                            class="md:hidden inline-flex size-11 items-center justify-center rounded-xl border border-slate-300/80 dark:border-white/15 bg-surface/70 dark:bg-white/5 backdrop-blur-sm text-text hover:bg-surface-2 transition focus-visible:outline-2 focus-visible:outline-amber-500 shadow-xs"
                            aria-label="Ganti mode tampilan terang atau gelap"
                            :title="currentTheme === 'dark' ? 'Ganti ke mode terang' : 'Ganti ke mode gelap'"
                        >
                            {{-- Moon when in light mode --}}
                            <svg x-show="currentTheme === 'light'" class="size-5 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                            {{-- Sun when in dark mode --}}
                            <svg x-show="currentTheme === 'dark'" class="size-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display: none;">
                                <circle cx="12" cy="12" r="4"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
        </header>


        {{-- Mobile Bottom Navigation Bar (CGV Pattern, Fixed bottom-0, Translucent Blur, < 768px) --}}
        @if (! $isActionPage)
            <nav
                id="mobile-bottom-bar"
                aria-label="Navigasi Bawah Mobile"
                class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-surface/85 dark:bg-[#07090E]/85 backdrop-blur-md supports-[backdrop-filter]:bg-surface/80 dark:supports-[backdrop-filter]:bg-[#07090E]/80 border-t border-border/80 dark:border-white/10 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] dark:shadow-[0_-4px_20px_rgba(0,0,0,0.5)] transition-colors duration-200"
                style="height: calc(60px + env(safe-area-inset-bottom, 0px)); padding-bottom: env(safe-area-inset-bottom, 0px);"
            >
                <div class="h-[60px] flex items-stretch justify-around w-full max-w-lg mx-auto">
                    @foreach ($bottomBarItems as $bItem)
                        @php
                            $isActive = $activeChecker($bItem);
                            $bKey = $bItem['key'] ?? $bItem['id'] ?? '';
                            $mobileLabel = !empty($bItem['short_label'])
                                ? $bItem['short_label']
                                : (in_array($bKey, ['lookup', 'booking_lookup', 'tickets', 'tiket'], true) ? 'Tiket' : $bItem['label']);
                        @endphp
                        <a
                            href="{{ url($bItem['url'] ?? '/') }}"
                            aria-current="{{ $isActive ? 'page' : 'false' }}"
                            class="flex-1 min-h-[60px] flex flex-col items-center justify-center relative py-1 px-1 transition-colors {{ $isActive ? 'text-slate-950 dark:text-amber-400 font-extrabold' : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white font-medium' }}"
                        >
                            @if ($isActive)
                                <span class="absolute top-0 inset-x-0 mx-auto h-[3px] w-7 bg-amber-500 rounded-full" aria-hidden="true"></span>
                            @endif

                            @if ($bKey === 'home')
                                <svg class="size-[22px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                </svg>
                            @elseif ($bKey === 'schedule')
                                <svg class="size-[22px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                            @elseif ($bKey === 'film')
                                <svg class="size-[22px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            @elseif ($bKey === 'lookup' || $bKey === 'booking_lookup' || $bKey === 'tickets' || $bKey === 'tiket')
                                <svg class="size-[22px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                                </svg>
                            @else
                                <svg class="size-[22px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                                </svg>
                            @endif

                            <span class="text-[12px] leading-tight mt-0.5 text-center">{{ $mobileLabel }}</span>
                        </a>
                    @endforeach

                    {{-- Item 5: Menu (Opens Bottom Sheet) --}}
                    <button
                        type="button"
                        @click="menuSheetOpen = true"
                        :aria-expanded="menuSheetOpen"
                        aria-controls="mobile-menu-sheet"
                        aria-label="Buka menu lengkap"
                        class="flex-1 min-h-[60px] flex flex-col items-center justify-center relative py-1 px-1 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white font-medium transition-colors"
                    >
                        <svg class="size-[22px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                        <span class="text-[12px] leading-tight mt-0.5">Menu</span>
                    </button>
                </div>
            </nav>
        @endif

        {{-- In-App Mobile Menu Bottom Sheet (Direct Child of <body>, max 80 dvh) --}}
        <div
            x-show="menuSheetOpen"
            x-cloak
            id="mobile-menu-sheet"
            class="md:hidden fixed inset-0 z-50 overflow-hidden"
            role="dialog"
            aria-modal="true"
            aria-label="Menu Lengkap Bioskop"
            @keydown.escape.window="menuSheetOpen = false"
        >
            {{-- Backdrop Overlay (60% solid black) --}}
            <div
                x-show="menuSheetOpen"
                x-transition:enter="transition-opacity ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="menuSheetOpen = false"
                class="fixed inset-0 bg-black/60"
                aria-hidden="true"
            ></div>

            {{-- Bottom Sheet Panel: OPAQUE, MAX 80 DVH --}}
            <div
                x-show="menuSheetOpen"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                class="fixed inset-x-0 bottom-0 z-50 max-h-[80dvh] rounded-t-3xl border-t border-border dark:border-white/10 bg-surface dark:bg-[#07090E] p-5 shadow-2xl flex flex-col overflow-hidden overscroll-contain"
                style="padding-bottom: max(env(safe-area-inset-bottom), 1.25rem);"
            >
                {{-- Handle bar indicator --}}
                <div class="mx-auto w-12 h-1.5 bg-slate-300 dark:bg-white/20 rounded-full mb-3 shrink-0"></div>

                {{-- Sheet Header: Brand Logo + 44px Close Button --}}
                <div class="flex items-center justify-between pb-3.5 border-b border-border dark:border-white/10 shrink-0">
                    <x-brand.logo size="sm" />
                    <button
                        type="button"
                        @click="menuSheetOpen = false"
                        class="inline-flex size-11 items-center justify-center rounded-xl border border-slate-300 dark:border-white/15 bg-surface text-text hover:bg-surface-2 transition focus-visible:outline-2 focus-visible:outline-amber-500"
                        aria-label="Tutup menu navigasi"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Scrollable Content Area --}}
                <div class="overflow-y-auto space-y-6 pt-4 pb-2 overscroll-contain text-sm">
                    {{-- Navigasi Lainnya --}}
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-text-muted mb-2.5">Menu Lainnya</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($menuSheetItems as $sheetItem)
                                <a
                                    href="{{ url($sheetItem['url'] ?? '/') }}"
                                    @click="menuSheetOpen = false"
                                    class="flex min-h-[44px] items-center justify-between rounded-xl border border-border bg-surface-2 px-3.5 py-2 font-bold text-text hover:border-amber-500 transition-colors shadow-xs"
                                >
                                    <span>{{ $sheetItem['label'] }}</span>
                                    <svg class="size-4 text-text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            @endforeach
                            <a
                                href="{{ route('articles.index', ['tab' => 'harga-faq']) }}"
                                @click="menuSheetOpen = false"
                                class="flex min-h-[44px] items-center justify-between rounded-xl border border-border bg-surface-2 px-3.5 py-2 font-bold text-text hover:border-amber-500 transition-colors shadow-xs"
                            >
                                <span>Harga & FAQ</span>
                                <svg class="size-4 text-text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    {{-- Pilihan Tema --}}
                    @if (setting('show_theme_toggle', true))
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-text-muted mb-2.5">Pilihan Tampilan</p>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    type="button"
                                    @click="window.synemaXTheme.setMode('light')"
                                    :class="currentTheme === 'light' ? 'border-amber-500 bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold' : 'border-border bg-surface-2 text-text'"
                                    class="flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold transition shadow-xs"
                                >
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="5" />
                                        <path d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72 1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42m12.72-12.72 1.42-1.42" />
                                    </svg>
                                    <span>Terang</span>
                                </button>
                                <button
                                    type="button"
                                    @click="window.synemaXTheme.setMode('dark')"
                                    :class="currentTheme === 'dark' ? 'border-amber-500 bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold' : 'border-border bg-surface-2 text-text'"
                                    class="flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold transition shadow-xs"
                                >
                                    <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                                    </svg>
                                    <span>Gelap</span>
                                </button>
                                <button
                                    type="button"
                                    @click="window.synemaXTheme.setMode('system')"
                                    :class="currentTheme === 'system' ? 'border-amber-500 bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold' : 'border-border bg-surface-2 text-text'"
                                    class="flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold transition shadow-xs"
                                >
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <rect width="20" height="14" x="2" y="3" rx="2" />
                                        <path d="M8 21h8m-4-4v4" />
                                    </svg>
                                    <span>Perangkat</span>
                                </button>
                            </div>
                        </div>
                    @endif

                    {{-- Kontak & Sosial Media --}}
                    @php
                        $cinSettings = app(\App\Services\CinemaSettings::class);
                        $contactEmail = $cinSettings->contactEmail();
                        $contactPhone = $cinSettings->contactWhatsapp();
                        $address = $cinSettings->contactAddress();
                        $socialSettings = $cinSettings->socialSettings();
                        $hasSocial = count(array_filter($socialSettings, fn ($s) => !empty($s['url']))) > 0;
                        $mapsUrl = $cinSettings->mapsUrl();
                        $mapsLink = $mapsUrl ?: ($address ? 'https://www.google.com/maps/search/?api=1&query='.urlencode($address) : null);
                    @endphp

                    @if ($contactEmail || $contactPhone || $address || $hasSocial)
                        <div class="space-y-3 pt-3 border-t border-border dark:border-white/10">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-text-muted">Kontak & Lokasi</p>

                            @if ($address)
                                <div class="text-xs text-text-muted space-y-1">
                                    <p class="font-bold text-text">{{ setting('site_name', 'SynemaX') }}</p>
                                    <p class="leading-relaxed">{{ $address }}</p>
                                    @if ($mapsLink)
                                        <a
                                            href="{{ $mapsLink }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400 font-bold hover:underline pt-1 text-xs"
                                        >
                                            <span>{{ $cinSettings->mapsLabel() }}</span>
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            @endif

                            {{-- Grid Kontak & Media Sosial Rapi & Terstruktur --}}
                            <div class="space-y-2.5 pt-1">
                                @if ($contactPhone)
                                    @php
                                        $waDigits = preg_replace('/[^\d]/', '', (string) $contactPhone);
                                        if (str_starts_with($waDigits, '0')) {
                                            $waDigits = '62' . substr($waDigits, 1);
                                        } elseif (! str_starts_with($waDigits, '62') && strlen($waDigits) >= 8) {
                                            $waDigits = '62' . $waDigits;
                                        }
                                        $menuWaUrl = 'https://wa.me/' . $waDigits . '?text=' . rawurlencode('Halo admin SynemaX, saya mau bertanya.');
                                    @endphp
                                    <a
                                        href="{{ $menuWaUrl }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex min-h-[44px] w-full items-center justify-between rounded-xl border border-emerald-500/30 bg-emerald-500/10 dark:bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 px-3.5 py-2.5 text-xs font-bold hover:bg-emerald-500/20 transition-colors shadow-xs"
                                    >
                                        <div class="flex items-center gap-2.5">
                                            <span class="flex size-7 items-center justify-center rounded-lg bg-emerald-500 text-white shrink-0 shadow-xs">
                                                <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.303-.058.116-.087.188-.173.289l-.26.302c-.087.087-.179.182-.077.357.101.174.449.741.963 1.199.662.59 1.221.774 1.394.861.173.087.275.072.376-.043.101-.116.433-.506.549-.68.116-.173.231-.144.39-.086.159.058 1.011.477 1.184.564.173.087.289.13.332.202.043.072.043.419-.101.824z"/>
                                                </svg>
                                            </span>
                                            <div>
                                                <p class="leading-tight">WhatsApp Admin</p>
                                                <p class="text-[10px] font-normal text-emerald-700/80 dark:text-emerald-300/80">Tanya jadwal & sewa studio</p>
                                            </div>
                                        </div>
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 dark:text-emerald-300">
                                            Chat
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                                        </span>
                                    </a>
                                @endif

                                @php
                                    $activeSocials = array_filter($socialSettings, fn ($s) => !empty($s['url']));
                                @endphp

                                @if ($contactEmail || count($activeSocials) > 0)
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        @if ($contactEmail)
                                            <a
                                                href="mailto:{{ $contactEmail }}"
                                                class="flex min-h-[44px] items-center gap-2.5 rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs font-semibold text-text hover:border-amber-500 transition-colors shadow-xs"
                                            >
                                                <span class="flex size-7 items-center justify-center rounded-lg bg-amber-500/15 text-amber-500 shrink-0">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                    </svg>
                                                </span>
                                                <span class="truncate">{{ $contactEmail }}</span>
                                            </a>
                                        @endif

                                        @foreach ($socialSettings as $socKey => $socData)
                                            @if (!empty($socData['url']))
                                                <a
                                                    href="{{ $socData['url'] }}"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="flex min-h-[44px] items-center gap-2.5 rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs font-semibold text-text hover:border-amber-500 transition-colors shadow-xs"
                                                >
                                                    @if ($socKey === 'instagram')
                                                        <span class="flex size-7 items-center justify-center rounded-lg bg-pink-500/15 text-pink-500 shrink-0">
                                                            <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                                            </svg>
                                                        </span>
                                                    @elseif ($socKey === 'tiktok')
                                                        <span class="flex size-7 items-center justify-center rounded-lg bg-cyan-500/15 text-text shrink-0">
                                                            <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.24 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                                                            </svg>
                                                        </span>
                                                    @elseif ($socKey === 'youtube')
                                                        <span class="flex size-7 items-center justify-center rounded-lg bg-red-500/15 text-red-500 shrink-0">
                                                            <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                                            </svg>
                                                        </span>
                                                    @else
                                                        <span class="flex size-7 items-center justify-center rounded-lg bg-amber-500/15 text-amber-500 shrink-0 font-bold text-xs">#</span>
                                                    @endif
                                                    <span class="truncate">{{ $socData['label'] ?: ucfirst($socKey) }}</span>
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Hak Cipta --}}
                    <div class="pt-4 border-t border-border dark:border-white/10 text-xs text-text-muted">
                        <p class="leading-relaxed">&copy; {{ date('Y') }} {{ setting('site_name', 'SynemaX') }}. {{ setting('footer_copyright_text', 'Hak cipta dilindungi undang-undang.') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Flash Session Messages --}}
        @if (session('status') || session('error'))
            <div class="mx-auto max-w-6xl px-4 pt-4 sm:px-6">
                <p role="status" class="rounded-xl border border-warning/30 bg-warning-surface/80 backdrop-blur-md px-4 py-3 text-sm text-warning font-medium">
                    {{ session('status') ?? session('error') }}
                </p>
            </div>
        @endif

        <main class="{{ ! $isActionPage ? 'pb-[calc(60px+env(safe-area-inset-bottom,0px)+16px)] md:pb-0' : '' }}">
            @yield('content')
        </main>

        {{-- Floating WhatsApp Action Button (Hanya di Halaman Beranda, bisa ditutup oleh user) --}}
        @if (request()->routeIs('home'))
            <div x-show="!menuSheetOpen" x-cloak>
                <x-ui.whatsapp-button variant="floating" />
            </div>
        @endif

        {{-- Universal Public Footer across all public pages with navbar --}}
        <x-public.footer />

        <x-in-app-dialogs />
    </body>
</html>
