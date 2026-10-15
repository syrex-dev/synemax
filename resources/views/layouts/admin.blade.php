<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#fbf6ea">
        <title>@yield('title', 'Admin') - SynemaX Admin</title>
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
                    document.querySelector('meta[name="theme-color"]').content = dark ? '#0F2A2F' : '#F2F5F3';
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
        <header class="sticky top-0 z-30 border-b border-border bg-surface/95 backdrop-blur">
            <div class="mx-auto flex max-w-screen-2xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
                <a href="{{ route('admin.dashboard') }}" class="flex min-h-11 items-center gap-2 font-bold">
                    <x-brand.logo size="sm" />
                    <span class="text-sm font-normal text-text-muted">/ Pengelola</span>
                </a>
                <div class="flex items-center gap-2">
                    <button type="button" data-theme-toggle role="switch" aria-label="Mode gelap" aria-checked="false" class="group relative inline-flex min-h-11 min-w-[76px] items-center justify-between rounded-full border border-border bg-surface px-2 text-text transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus" title="Ganti tema terang atau gelap">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="size-4"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="size-4"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5 8.5 8.5 0 1 0 20.5 14.2Z"/></svg>
                        <span data-theme-thumb class="pointer-events-none absolute left-1 top-1 size-9 rounded-full bg-accent shadow-sm transition-transform duration-150"></span>
                    </button>
                    <a href="{{ route('home') }}" class="hidden min-h-11 items-center rounded-lg px-3 text-sm text-text-muted hover:bg-surface-2 hover:text-text sm:inline-flex">Lihat situs</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="min-h-11 rounded-lg border border-border px-3 text-sm font-semibold text-text hover:bg-surface-2">Keluar</button>
                    </form>
                </div>
            </div>
        </header>

        <div class="mx-auto max-w-[1200px] lg:grid lg:grid-cols-[14rem_minmax(0,1fr)]">
            <aside class="sticky top-[4.2rem] hidden h-[calc(100dvh-4.2rem)] overflow-y-auto border-r border-border px-3 py-5 lg:block">
                <nav aria-label="Navigasi admin" class="space-y-1">
                    <a href="{{ route('admin.dashboard') }}" @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif class="flex min-h-12 items-center gap-3 rounded-xl px-3 text-base {{ request()->routeIs('admin.dashboard') ? 'bg-accent/10 font-bold text-accent' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}"><span aria-hidden="true">⌂</span>Hari Ini</a>
                    <a href="{{ route('admin.bookings.index') }}" @if (request()->routeIs('admin.bookings.*')) aria-current="page" @endif class="flex min-h-12 items-center gap-3 rounded-xl px-3 text-base {{ request()->routeIs('admin.bookings.*') ? 'bg-accent/10 font-bold text-accent' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}"><span aria-hidden="true">▤</span>Booking</a>
                    <a href="{{ route('admin.schedule.index') }}" @if (request()->routeIs('admin.schedule.*')) aria-current="page" @endif class="flex min-h-12 items-center gap-3 rounded-xl px-3 text-base {{ request()->routeIs('admin.schedule.*') ? 'bg-accent/10 font-bold text-accent' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}"><span aria-hidden="true">◷</span>Jadwal</a>
                    <a href="{{ route('admin.films.index') }}" @if (request()->routeIs('admin.films.*')) aria-current="page" @endif class="flex min-h-12 items-center gap-3 rounded-xl px-3 text-base {{ request()->routeIs('admin.films.*') ? 'bg-accent/10 font-bold text-accent' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}"><span aria-hidden="true">▣</span>Film</a>
                    <a href="{{ route('admin.snacks.index') }}" @if (request()->routeIs('admin.snacks.*') && ! request()->routeIs('admin.snacks.preparation.*')) aria-current="page" @endif class="flex min-h-12 items-center gap-3 rounded-xl px-3 text-base {{ request()->routeIs('admin.snacks.*') && ! request()->routeIs('admin.snacks.preparation.*') ? 'bg-accent/10 font-bold text-accent' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}"><span aria-hidden="true">◈</span>Jajanan</a>
                    <a href="{{ route('admin.nobar.index') }}" @if (request()->routeIs('admin.nobar.*')) aria-current="page" @endif class="flex min-h-12 items-center gap-3 rounded-xl px-3 text-base {{ request()->routeIs('admin.nobar.*') ? 'bg-accent/10 font-bold text-accent' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}"><span aria-hidden="true">◎</span>Nobar</a>
                    <a href="{{ route('admin.articles.index') }}" @if (request()->routeIs('admin.articles.*')) aria-current="page" @endif class="flex min-h-12 items-center gap-3 rounded-xl px-3 text-base {{ request()->routeIs('admin.articles.*') ? 'bg-accent/10 font-bold text-accent' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}"><span aria-hidden="true">▤</span>Kabar / Promo</a>
                    <div class="my-4 border-t border-border"></div>
                    <p class="px-3 text-xs font-bold uppercase tracking-[0.12em] text-text-muted">Lainnya</p>
                    <a href="{{ route('admin.snacks.preparation.index') }}" @if (request()->routeIs('admin.snacks.preparation.*')) aria-current="page" @endif class="flex min-h-12 items-center gap-3 rounded-xl px-3 text-base {{ request()->routeIs('admin.snacks.preparation.*') ? 'bg-accent/10 font-bold text-accent' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}">Daftar siapkan</a>
                    <a href="{{ route('admin.reports.index') }}" @if (request()->routeIs('admin.reports.*')) aria-current="page" @endif class="flex min-h-12 items-center gap-3 rounded-xl px-3 text-base {{ request()->routeIs('admin.reports.*') ? 'bg-accent/10 font-bold text-accent' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}">Laporan</a>
                    <a href="{{ route('admin.settings.edit') }}" @if (request()->routeIs('admin.settings.*')) aria-current="page" @endif class="flex min-h-12 items-center gap-3 rounded-xl px-3 text-base {{ request()->routeIs('admin.settings.*') ? 'bg-accent/10 font-bold text-accent' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}">Pengaturan</a>
                </nav>
            </aside>

            <div class="min-w-0 lg:col-start-2">
                @if (session('status') || session('error') || session('warning'))
                    <div x-data="{ visible: true }" x-show="visible" x-init="setTimeout(() => visible = false, 6000)" role="{{ session()->has('error') ? 'alert' : (session()->has('warning') ? 'alert' : 'status') }}" aria-live="{{ session()->has('error') ? 'assertive' : 'polite' }}" class="fixed right-4 top-20 z-50 flex w-[min(26rem,calc(100vw-2rem))] items-start justify-between gap-4 rounded-lg border {{ session()->has('error') ? 'border-danger/30 bg-danger-surface text-danger' : (session()->has('warning') ? 'border-warning/40 bg-warning/10 text-warning' : 'border-success/30 bg-success-surface text-success') }} px-4 py-3 text-sm shadow-token">
                        <span>{{ session('status') ?? session('warning') ?? session('error') }}</span>
                        <button type="button" @click="visible = false" aria-label="Tutup pemberitahuan" class="grid size-8 shrink-0 place-items-center rounded-md text-text-muted hover:bg-surface-2">×</button>
                    </div>
                @endif
                <main class="mx-auto min-w-0 max-w-[1200px] px-4 pb-28 pt-7 sm:px-6 sm:pb-28 lg:px-8 lg:py-10">
                    @yield('content')
                </main>
            </div>
        </div>

        <nav aria-label="Navigasi utama admin" class="fixed inset-x-0 bottom-0 z-40 border-t border-border bg-surface/95 px-2 pt-2 shadow-token backdrop-blur lg:hidden" style="padding-bottom: max(env(safe-area-inset-bottom), .5rem)">
            <div class="mx-auto grid max-w-screen-2xl grid-cols-6 gap-1">
                <a href="{{ route('admin.dashboard') }}" @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif class="flex min-h-12 flex-col items-center justify-center gap-0.5 rounded-lg text-[11px] font-semibold {{ request()->routeIs('admin.dashboard') ? 'text-accent' : 'text-text-muted' }}"><span aria-hidden="true" class="text-lg leading-5">⌂</span>Hari Ini</a>
                <a href="{{ route('admin.bookings.index') }}" @if (request()->routeIs('admin.bookings.*')) aria-current="page" @endif class="flex min-h-12 flex-col items-center justify-center gap-0.5 rounded-lg text-[11px] font-semibold {{ request()->routeIs('admin.bookings.*') ? 'text-accent' : 'text-text-muted' }}"><span aria-hidden="true" class="text-lg leading-5">▤</span>Booking</a>
                <a href="{{ route('admin.schedule.index') }}" @if (request()->routeIs('admin.schedule.*')) aria-current="page" @endif class="flex min-h-12 flex-col items-center justify-center gap-0.5 rounded-lg text-[11px] font-semibold {{ request()->routeIs('admin.schedule.*') ? 'text-accent' : 'text-text-muted' }}"><span aria-hidden="true" class="text-lg leading-5">◷</span>Jadwal</a>
                <a href="{{ route('admin.films.index') }}" @if (request()->routeIs('admin.films.*')) aria-current="page" @endif class="flex min-h-12 flex-col items-center justify-center gap-0.5 rounded-lg text-[11px] font-semibold {{ request()->routeIs('admin.films.*') ? 'text-accent' : 'text-text-muted' }}"><span aria-hidden="true" class="text-lg leading-5">▣</span>Film</a>
                <a href="{{ route('admin.snacks.index') }}" @if (request()->routeIs('admin.snacks.*') && ! request()->routeIs('admin.snacks.preparation.*')) aria-current="page" @endif class="flex min-h-12 flex-col items-center justify-center gap-0.5 rounded-lg text-[11px] font-semibold {{ request()->routeIs('admin.snacks.*') && ! request()->routeIs('admin.snacks.preparation.*') ? 'text-accent' : 'text-text-muted' }}"><span aria-hidden="true" class="text-lg leading-5">◈</span>Jajanan</a>
                <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                    <div x-show="open" x-cloak class="absolute bottom-full right-0 mb-2 w-52 rounded-2xl border border-border bg-surface p-2 shadow-token">
                        <a href="{{ route('admin.nobar.index') }}" class="flex min-h-12 items-center rounded-xl px-3 text-base text-text hover:bg-surface-2">Nobar</a>
                        <a href="{{ route('admin.articles.index') }}" class="flex min-h-12 items-center rounded-xl px-3 text-base text-text hover:bg-surface-2">Kabar / Promo</a>
                        <a href="{{ route('admin.snacks.preparation.index') }}" class="flex min-h-12 items-center rounded-xl px-3 text-base text-text hover:bg-surface-2">Daftar siapkan</a>
                        <a href="{{ route('admin.reports.index') }}" class="flex min-h-12 items-center rounded-xl px-3 text-base text-text hover:bg-surface-2">Laporan</a>
                        <a href="{{ route('admin.settings.edit') }}" class="flex min-h-12 items-center rounded-xl px-3 text-base text-text hover:bg-surface-2">Pengaturan</a>
                        <a href="{{ route('profile.edit') }}" class="flex min-h-12 items-center rounded-xl px-3 text-base text-text hover:bg-surface-2">Profil admin</a>
                    </div>
                    <button type="button" @click="open = !open" :aria-expanded="open" class="flex min-h-12 w-full flex-col items-center justify-center gap-0.5 rounded-lg text-[11px] font-semibold text-text-muted"><span aria-hidden="true" class="text-lg leading-5">•••</span>Lainnya</button>
                </div>
            </div>
        </nav>
    </body>
</html>
