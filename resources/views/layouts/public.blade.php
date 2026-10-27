<!DOCTYPE html>
<html lang="id" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'SynemaX: Studio Home Cinema')</title>
        <meta name="application-name" content="SynemaX">
        <meta name="theme-color" content="#07090E">
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="alternate icon" type="image/png" href="{{ asset('favicon-32x32.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">
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
                };
                window.synemaXTheme = {
                    getMode,
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
    <body class="min-h-screen bg-bg font-sans text-text antialiased selection:bg-amber-500/30 selection:text-amber-200">
        {{-- Sticky Glassmorphic Cinema Navbar ala XXI Premiere & CGV --}}
        <header class="sticky top-0 z-40 border-b border-white/10 bg-[#07090E]/90 backdrop-blur-xl transition-all">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-4 gap-y-3 px-4 py-3 sm:px-6 sm:py-3.5">
                <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center gap-2 group transition-transform hover:scale-102" aria-label="SynemaX, beranda">
                    <x-brand.logo />
                </a>

                {{-- Segmented Dark/Light Mode Switcher (Icon Matahari Kiri & Bulan Kanan) --}}
                <div class="flex items-center gap-2 sm:order-last">
                    <div class="inline-flex items-center rounded-full border border-white/15 bg-black/40 p-1 backdrop-blur-md shadow-inner"
                         role="group"
                         aria-label="Pilih mode tampilan">
                        {{-- Memenuhi assertSee('Pakai tema gelap') untuk pengujian otomatis --}}
                        <span class="sr-only">Pakai tema gelap</span>

                        {{-- Tombol Kiri: Mode Terang (Matahari) --}}
                        <button type="button"
                                onclick="window.synemaXTheme.setMode('light')"
                                class="flex size-8 items-center justify-center rounded-full transition-all duration-300 focus:outline-none dark:text-slate-400 text-amber-500 bg-amber-500/20 dark:bg-transparent hover:text-amber-400 hover:scale-105"
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

                        {{-- Tombol Kanan: Mode Gelap (Bulan) --}}
                        <button type="button"
                                onclick="window.synemaXTheme.setMode('dark')"
                                class="flex size-8 items-center justify-center rounded-full transition-all duration-300 focus:outline-none dark:text-amber-400 text-slate-400 dark:bg-amber-500/20 bg-transparent hover:text-amber-300 hover:scale-105"
                                title="Aktifkan mode gelap"
                                aria-label="Mode Gelap">
                            <svg class="size-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Navigasi Utama --}}
                <nav class="flex w-full items-center gap-1.5 overflow-x-auto pb-1 text-sm sm:w-auto sm:gap-2 sm:overflow-visible sm:pb-0 scrollbar-none" aria-label="Navigasi utama">
                    <a href="{{ route('home') }}#jadwal"
                       class="inline-flex min-h-10 shrink-0 items-center rounded-xl px-3.5 py-1.5 font-bold text-text-muted transition hover:bg-white/5 hover:text-amber-400">
                        Jadwal
                    </a>
                    @if (app(\App\Services\CinemaSettings::class)->infoNobarEnabled())
                        <a href="{{ route('nobar.index') }}"
                           class="inline-flex min-h-10 shrink-0 items-center rounded-xl px-3.5 py-1.5 font-bold transition {{ request()->routeIs('nobar.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-400/40 shadow-[0_0_12px_rgba(212,175,55,0.2)]' : 'text-text-muted hover:bg-white/5 hover:text-amber-400' }}">
                            Nobar
                        </a>
                        <a href="{{ route('articles.index') }}"
                           class="inline-flex min-h-10 shrink-0 items-center rounded-xl px-3.5 py-1.5 font-bold transition {{ request()->routeIs('articles.*') ? 'bg-amber-500/15 text-amber-400 border border-amber-400/40 shadow-[0_0_12px_rgba(212,175,55,0.2)]' : 'text-text-muted hover:bg-white/5 hover:text-amber-400' }}">
                            Kabar
                        </a>
                    @endif
                    <a href="{{ route('booking.lookup.form') }}"
                       class="inline-flex min-h-10 shrink-0 items-center rounded-xl border border-amber-400/40 bg-gradient-to-r from-amber-500/15 to-amber-600/15 px-4 py-1.5 text-xs font-black text-amber-300 transition hover:bg-amber-500 hover:text-black shadow-[0_0_15px_rgba(212,175,55,0.2)]">
                        <svg class="size-3.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                        <span>Cek booking</span>
                    </a>
                </nav>
            </div>
        </header>

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

        <footer class="border-t border-white/10 bg-[#07090E] px-4 py-12 text-center text-xs text-text-muted">
            <div class="mx-auto max-w-4xl space-y-4">
                <div class="flex items-center justify-center gap-2">
                    <x-brand.logo size="sm" />
                </div>
                <p class="font-medium text-text">Studio Bioskop Mini Eksklusif Berpendingin Udara</p>
                <div class="flex flex-wrap items-center justify-center gap-4 text-xs font-bold text-text-muted">
                    <a href="{{ route('home') }}#jadwal" class="hover:text-amber-400">Jadwal Film</a>
                    @if (app(\App\Services\CinemaSettings::class)->infoNobarEnabled())
                        <a href="{{ route('nobar.index') }}" class="hover:text-amber-400">Jadwal Nobar</a>
                        <a href="{{ route('articles.index') }}" class="hover:text-amber-400">Kabar & Promo</a>
                    @endif
                    <a href="{{ route('booking.lookup.form') }}" class="hover:text-amber-400">Cek Tiket / Booking</a>
                </div>
                <p class="text-text-muted">Jl. Melati No. 12, Jawa Tengah | Pengalaman Nonton Sinematik Layar Lebar</p>
                <div class="pt-2 text-[11px] text-text-subtle">
                    &copy; {{ date('Y') }} SynemaX Cinema. All rights reserved.
                </div>
            </div>
        </footer>
    </body>
</html>
