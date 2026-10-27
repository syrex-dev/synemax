<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#F2F5F3">
        <meta name="description" content="Nonton dekat, seru bareng di studio mini SynemaX. Pilih film dan jadwal tanpa ribet bikin akun.">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icon-192.png') }}">
        <title>@yield('title', config('app.name'))</title>
        @yield('meta')
        <script>
            (() => {
                const storageKey = 'synemax-theme';
                const modes = ['light', 'dark', 'system'];
                const getMode = () => {
                    try {
                        const mode = localStorage.getItem(storageKey);
                        return modes.includes(mode) ? mode : 'system';
                    } catch (error) {
                        return 'system';
                    }
                };
                const applyMode = (mode) => {
                    const dark = mode === 'dark' || (mode === 'system' && matchMedia('(prefers-color-scheme: dark)').matches);
                    document.documentElement.classList.toggle('dark', dark);
                    document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
                    document.documentElement.dataset.themeMode = mode;
                    const metaTheme = document.querySelector('meta[name="theme-color"]');
                    if (metaTheme) {
                        metaTheme.content = dark ? '#0F2A2F' : '#F2F5F3';
                    }
                    const themeBtn = document.getElementById('theme-toggle-btn');
                    if (themeBtn) {
                        themeBtn.setAttribute('aria-label', dark ? 'Pakai tema terang' : 'Pakai tema gelap');
                    }
                };
                window.synemaXTheme = {
                    storageKey,
                    getMode,
                    applyMode,
                    setMode(mode) {
                        if (!modes.includes(mode)) {
                            return;
                        }
                        try {
                            localStorage.setItem(storageKey, mode);
                        } catch (error) {
                            console.error('Unable to save the theme preference.', error);
                        }
                        applyMode(mode);
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
        {{-- Sticky Glassmorphic Cinema Navbar --}}
        <header class="sticky top-0 z-40 border-b border-border bg-surface/90 backdrop-blur-xl transition-all">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-4 gap-y-3 px-4 py-3 sm:px-6 sm:py-3.5">
                <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center gap-2 group transition-transform hover:scale-102" aria-label="SynemaX, beranda">
                    <x-brand.logo />
                </a>

                <div class="flex items-center gap-2 sm:order-last">
                    <button type="button"
                            id="theme-toggle-btn"
                            onclick="window.synemaXTheme.setMode(document.documentElement.classList.contains('dark') ? 'light' : 'dark')"
                            class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-xl border border-border-input bg-surface-2/60 px-3 py-1.5 text-xs font-bold text-text transition hover:border-primary hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                            aria-label="Pakai tema gelap">
                        <span class="dark:hidden">Pakai tema gelap</span>
                        <span class="hidden dark:inline">Pakai tema terang</span>
                    </button>
                </div>

                <nav class="flex w-full items-center gap-1.5 overflow-x-auto pb-1 text-sm sm:w-auto sm:gap-2 sm:overflow-visible sm:pb-0" aria-label="Navigasi utama">
                    <a href="{{ route('home') }}#jadwal"
                       class="inline-flex min-h-10 shrink-0 items-center rounded-xl px-3.5 py-1.5 font-medium text-text-muted transition hover:bg-surface-2 hover:text-text">
                        Jadwal
                    </a>
                    @if (app(\App\Services\CinemaSettings::class)->infoNobarEnabled())
                        <a href="{{ route('nobar.index') }}"
                           class="inline-flex min-h-10 shrink-0 items-center rounded-xl px-3.5 py-1.5 font-medium transition {{ request()->routeIs('nobar.*') ? 'bg-primary/10 font-bold text-primary border border-primary/20' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}">
                            Nobar
                        </a>
                        <a href="{{ route('articles.index') }}"
                           class="inline-flex min-h-10 shrink-0 items-center rounded-xl px-3.5 py-1.5 font-medium transition {{ request()->routeIs('articles.*') ? 'bg-primary/10 font-bold text-primary border border-primary/20' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}">
                            Kabar
                        </a>
                    @endif
                    <a href="{{ route('booking.lookup.form') }}"
                       class="inline-flex min-h-10 shrink-0 items-center rounded-xl border border-primary/40 bg-primary/10 px-4 py-1.5 text-xs font-bold text-primary transition hover:bg-primary hover:text-primary-text shadow-[0_0_15px_rgba(212,175,55,0.15)]">
                        Cek booking
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

        <footer class="border-t border-border bg-surface/50 backdrop-blur-md px-4 py-10 text-center text-xs text-text-muted">
            <div class="mx-auto max-w-4xl space-y-3">
                <div class="flex items-center justify-center gap-2">
                    <x-brand.logo size="sm" />
                </div>
                <p class="font-medium text-text">Studio Bioskop Mini Eksklusif Berpendingin Udara</p>
                <p class="text-text-muted">Jl. Melati No. 12, Jawa Tengah | Pengalaman Nonton Sinematik Layar Lebar</p>
                <div class="pt-2 text-[11px] text-text-subtle">
                    &copy; {{ date('Y') }} SynemaX Cinema. All rights reserved.
                </div>
            </div>
        </footer>
    </body>
</html>
