<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta name="color-scheme" content="light dark">
        <meta name="theme-color" content="#07090E">
        <title>Masuk admin · {{ config('app.name') }}</title>
        @php
            $settingsService = app(\App\Services\CinemaSettings::class);
            $faviconUrl = $settingsService->siteFaviconUrl();
            $faviconMime = $settingsService->siteFaviconMime();
        @endphp
        <link rel="icon" type="{{ $faviconMime }}" href="{{ $faviconUrl }}">
        <link rel="shortcut icon" href="{{ $faviconUrl }}">
        <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
        <script>
            (() => {
                const storageKeys = ['synemax-theme', 'synemax_theme_preference'];
                const modes = ['light', 'dark', 'system'];
                const getMode = () => {
                    try {
                        for (const k of storageKeys) {
                            const mode = localStorage.getItem(k);
                            if (modes.includes(mode)) return mode;
                        }
                    } catch (error) {}
                    return 'system';
                };
                const applyMode = (mode) => {
                    const dark = mode === 'dark' || (mode === 'system' && matchMedia('(prefers-color-scheme: dark)').matches);
                    const effective = dark ? 'dark' : 'light';
                    document.documentElement.classList.toggle('dark', dark);
                    document.documentElement.dataset.themeMode = mode;
                    const schemeMeta = document.querySelector('meta[name="color-scheme"]');
                    if (schemeMeta) {
                        schemeMeta.content = effective;
                    }
                    const themeMeta = document.querySelector('meta[name="theme-color"]');
                    if (themeMeta) {
                        themeMeta.content = dark ? '#07090E' : '#FFFFFF';
                    }
                };
                window.synemaXTheme = {
                    getMode,
                    applyMode,
                    setMode(mode) {
                        if (!modes.includes(mode)) return;
                        try {
                            for (const k of storageKeys) {
                                localStorage.setItem(k, mode);
                            }
                        } catch (error) {}
                        applyMode(mode);
                        window.dispatchEvent(new CustomEvent('synemax-theme-changed', { detail: { mode } }));
                    },
                };
                if (window.matchMedia) {
                    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                        if (getMode() === 'system') applyMode('system');
                    });
                }
                applyMode(getMode());
            })();
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-[100dvh] bg-bg font-sans text-text antialiased">
        <div class="fixed right-3 top-3 z-10" style="padding-top: env(safe-area-inset-top); padding-right: env(safe-area-inset-right);">
            <button type="button"
                    onclick="window.synemaXTheme.setMode(document.documentElement.classList.contains('dark') ? 'light' : 'dark')"
                    aria-label="Ganti mode gelap atau terang"
                    class="flex size-11 items-center justify-center rounded-xl border border-border bg-surface text-text hover:border-amber-400 focus-visible:outline-2 focus-visible:outline-focus">
                <svg class="size-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <svg class="size-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </button>
        </div>

        <div class="flex min-h-[100dvh] flex-col items-center justify-center px-4 py-4" style="padding-top: max(env(safe-area-inset-top), 1rem); padding-bottom: max(env(safe-area-inset-bottom), 1rem);">
            <a href="{{ route('home') }}" class="mb-4 inline-flex items-center focus:outline-none">
                <x-brand.logo size="md" />
            </a>
            <div class="w-full max-w-[400px] rounded-xl border border-border bg-surface p-5 sm:p-6 shadow-none">
                {{ $slot }}
            </div>
            <a href="{{ route('home') }}" class="mt-3.5 text-xs font-semibold text-text-muted hover:text-text">← Kembali ke halaman pelanggan</a>
        </div>
    </body>
</html>
