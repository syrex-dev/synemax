<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#fbf6ea">
        <meta name="description" content="Nonton bareng di Home Cinema. Pilih film dan jadwal, booking gampang lewat WhatsApp.">
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
                    document.querySelector('meta[name="theme-color"]').content = dark ? '#0b1220' : '#fbf6ea';
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
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
    </head>
    <body class="min-h-screen bg-bg font-sans text-text antialiased">
        <header class="border-b border-border bg-surface">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-3 gap-y-2 px-4 py-3 sm:px-6 sm:py-4 md:flex-nowrap md:gap-6">
                <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3" aria-label="Home Cinema, beranda">
                    <span class="grid size-10 place-items-center rounded-xl bg-accent text-lg font-black text-accent-contrast">S</span>
                    <span>
                        <span class="block text-sm font-bold tracking-wide">SynemaX</span>
                        <span class="block text-xs text-text-muted">Nonton dekat, seru bareng</span>
                    </span>
                </a>
                <button type="button" data-theme-toggle role="switch" aria-label="Mode gelap" aria-checked="false" class="group relative order-2 inline-flex min-h-11 min-w-[76px] shrink-0 items-center justify-between rounded-full border border-border bg-surface px-2 text-text transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus md:order-last" title="Ganti tema terang atau gelap">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="size-4"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="size-4"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5 8.5 8.5 0 1 0 20.5 14.2Z"/></svg>
                    <span data-theme-thumb class="pointer-events-none absolute left-1 top-1 size-9 rounded-full bg-accent shadow-sm transition-transform duration-150"></span>
                </button>
                <nav class="order-3 flex w-full items-center gap-1 overflow-x-auto pb-1 text-sm sm:gap-2 md:order-none md:w-auto md:overflow-visible md:pb-0">
                    <a href="{{ route('home') }}#jadwal" class="shrink-0 whitespace-nowrap rounded-lg px-3 py-2 text-text-muted hover:bg-surface-2 hover:text-text">Jadwal</a>
                    <a href="{{ route('home') }}#booking-saya" class="shrink-0 whitespace-nowrap rounded-lg px-3 py-2 text-text-muted hover:bg-surface-2 hover:text-text">Booking Saya</a>
                    <a href="{{ route('booking.lookup.form') }}" class="shrink-0 whitespace-nowrap rounded-lg border border-gold px-3 py-2 font-semibold text-gold hover:bg-gold-surface">Cek booking</a>
                </nav>
            </div>
        </header>

        @if (session('status') || session('error'))
            <div class="mx-auto max-w-6xl px-4 pt-4 sm:px-6">
                <p role="status" class="rounded-xl border border-warning bg-warning-surface px-4 py-3 text-sm text-warning">
                    {{ session('status') ?? session('error') }}
                </p>
            </div>
        @endif

        <main>
            @yield('content')
        </main>

        <footer class="border-t border-border px-4 py-8 text-center text-xs text-text-muted">
            <p>Home Cinema · Bioskop kecil, cerita besar.</p>
        </footer>
    </body>
</html>
