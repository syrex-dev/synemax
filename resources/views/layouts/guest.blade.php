<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta name="theme-color" content="#fbf6ea">
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
    </head>
    <body class="min-h-screen bg-bg font-sans text-text antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
            <div class="fixed right-4 top-4 z-10">
                <button type="button" data-theme-toggle role="switch" aria-label="Mode gelap" aria-checked="false" class="group relative inline-flex min-h-11 min-w-[76px] items-center justify-between rounded-full border border-border bg-surface px-2 text-text transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus" title="Ganti tema terang atau gelap">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="size-4"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="size-4"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5 8.5 8.5 0 1 0 20.5 14.2Z"/></svg>
                    <span data-theme-thumb class="pointer-events-none absolute left-1 top-1 size-9 rounded-full bg-accent shadow-sm transition-transform duration-150"></span>
                </button>
            </div>
            <a href="{{ route('home') }}" class="mb-6 inline-flex items-center">
                <x-brand.logo size="lg" />
            </a>
            <div class="w-full max-w-md rounded-3xl border border-border bg-surface p-6 shadow-token sm:p-8">
                {{ $slot }}
            </div>
            <a href="{{ route('home') }}" class="mt-5 text-sm text-text-muted hover:text-text">← Kembali ke halaman pelanggan</a>
        </div>
    </body>
</html>
