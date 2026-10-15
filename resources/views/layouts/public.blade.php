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
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-4 gap-y-3 px-4 py-3 sm:px-6 sm:py-4">
                <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center gap-2" aria-label="SynemaX, beranda">
                    <x-brand.logo />
                </a>

                <div class="flex items-center gap-2 sm:order-last">
                    <button type="button"
                            onclick="window.synemaXTheme.setMode(document.documentElement.classList.contains('dark') ? 'light' : 'dark')"
                            class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] border border-border-input bg-surface px-3 py-2 text-xs font-bold text-text transition hover:border-primary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                            aria-label="Ganti tema tampilan">
                        <span class="dark:hidden">Tema malam</span>
                        <span class="hidden dark:inline">Tema layar</span>
                    </button>
                </div>

                <nav class="flex w-full items-center gap-1 overflow-x-auto pb-1 text-sm sm:w-auto sm:gap-2 sm:overflow-visible sm:pb-0" aria-label="Navigasi utama">
                    <a href="{{ route('home') }}#jadwal" class="inline-flex min-h-11 shrink-0 items-center rounded-[var(--radius-control)] px-3 py-2 text-text-muted transition hover:bg-surface-2 hover:text-text">Jadwal</a>
                    @if (app(\App\Services\CinemaSettings::class)->infoNobarEnabled())
                        <a href="{{ route('nobar.index') }}" class="inline-flex min-h-11 shrink-0 items-center rounded-[var(--radius-control)] px-3 py-2 transition {{ request()->routeIs('nobar.*') ? 'font-bold text-primary' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}">Nobar</a>
                        <a href="{{ route('articles.index') }}" class="inline-flex min-h-11 shrink-0 items-center rounded-[var(--radius-control)] px-3 py-2 transition {{ request()->routeIs('articles.*') ? 'font-bold text-primary' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}">Kabar</a>
                    @endif
                    <a href="{{ route('booking.lookup.form') }}" class="inline-flex min-h-11 shrink-0 items-center rounded-[var(--radius-control)] border border-border-input px-3.5 py-2 font-bold text-text transition hover:border-primary">Cek Booking</a>
                </nav>
            </div>
        </header>

        @if (session('status') || session('error'))
            <div class="mx-auto max-w-6xl px-4 pt-4 sm:px-6">
                <p role="status" class="rounded-[var(--radius-control)] border border-warning bg-warning-surface px-4 py-3 text-sm text-warning">
                    {{ session('status') ?? session('error') }}
                </p>
            </div>
        @endif

        <main>
            @yield('content')
        </main>

        <footer class="border-t border-border px-4 py-8 text-center text-xs text-text-muted">
            <p>SynemaX Bioskop Mini Desa. Nonton dekat, seru bareng.</p>
        </footer>
    </body>
</html>
