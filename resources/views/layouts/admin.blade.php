@php
    $adminUser = auth()->user();
    $adminDensity = $adminUser?->display_density ?? 'nyaman';
    $waitingCount = \App\Models\Booking::query()->where('status', 'menunggu_konfirmasi')->count();
@endphp
<!doctype html>
<html lang="id" data-density="{{ $adminDensity }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#fbf6ea">
        <title>@yield('title', 'Admin'): SynemaX Admin</title>
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
                    const meta = document.querySelector('meta[name="theme-color"]');
                    if (meta) {
                        meta.content = dark ? '#07090E' : '#F8FAFC';
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
        <style>
            :root {
                --admin-card-p: 1.25rem;
                --admin-row-h: 52px;
            }
            html[data-density="ringkas"] {
                --admin-card-p: 0.875rem;
                --admin-row-h: 44px;
            }
        </style>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body
        x-data="{
            sidebarOpen: false,
            accountOpen: false,
            density: @js($adminDensity),
            lastWaitingCount: {{ $waitingCount }},
            isDark: document.documentElement.classList.contains('dark'),
            init() {
                setInterval(() => {
                    if (document.visibilityState === 'visible') {
                        fetch('{{ route('admin.polling.status') }}')
                            .then(res => res.json())
                            .then(data => {
                                if (data && data.waiting_count > this.lastWaitingCount) {
                                    const diff = data.waiting_count - this.lastWaitingCount;
                                    window.showToast({
                                        type: 'info',
                                        message: `${diff} booking baru masuk menunggu konfirmasi.`
                                    });
                                }
                                this.lastWaitingCount = data.waiting_count;
                            })
                            .catch(() => {});
                    }
                }, 60000);
            },
            toggleTheme() {
                this.isDark = !this.isDark;
                window.synemaXTheme.setMode(this.isDark ? 'dark' : 'light');
            },
            setDensity(mode) {
                this.density = mode;
                document.documentElement.dataset.density = mode;
                fetch('{{ route('admin.density.update') }}', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ density: mode })
                }).catch(() => {});
            }
        }"
        class="min-h-screen bg-bg font-sans text-text antialiased"
    >
        {{-- Sticky Admin Header (56px mobile, 64px desktop, viewport-fit=cover) --}}
        <header class="sticky top-0 z-30 border-b border-border bg-surface/95 backdrop-blur pt-[env(safe-area-inset-top)]">
            <div class="mx-auto flex h-14 lg:h-16 max-w-screen-2xl items-center justify-between gap-2 px-3 sm:px-6">
                {{-- Left: Hamburger + Logo + Breadcrumb --}}
                <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
                    {{-- Mobile Hamburger (44px target) --}}
                    <button
                        type="button"
                        @click="sidebarOpen = !sidebarOpen"
                        class="inline-flex size-11 items-center justify-center rounded-xl border border-slate-300 dark:border-white/15 bg-surface text-text hover:bg-surface-2 focus-visible:outline-2 focus-visible:outline-amber-500 lg:hidden shrink-0"
                        aria-label="Buka menu navigasi"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    {{-- Logo: Icon + Single line SYNEMAX (subtitle hidden < 1024px) --}}
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 min-w-0 shrink">
                        <x-brand.logo size="sm" />
                    </a>

                    {{-- Breadcrumb (Desktop Only) --}}
                    <div class="hidden md:flex items-center text-xs text-text-muted min-w-0 truncate">
                        <span class="mx-1.5 text-border-input font-semibold">/</span>
                        <span class="font-medium">Pengelola</span>
                        <span class="mx-1.5 text-border-input font-semibold">/</span>
                        <span class="font-bold text-text truncate">@yield('title', 'Hari Ini')</span>
                    </div>
                </div>

                {{-- Right: Theme Toggle (Single Icon 44px) + Account Dropdown (44px) --}}
                <div class="flex items-center gap-2 shrink-0">
                    {{-- Theme Toggle (Single 44px Icon Button) --}}
                    <button
                        type="button"
                        @click="toggleTheme()"
                        class="inline-flex size-11 items-center justify-center rounded-xl border border-slate-300 dark:border-white/15 bg-surface text-text hover:bg-surface-2 hover:border-amber-500 transition-colors focus-visible:outline-2 focus-visible:outline-amber-500 shadow-xs"
                        aria-label="Ganti tema terang atau gelap"
                        :title="isDark ? 'Ganti ke tema terang' : 'Ganti ke tema gelap'"
                    >
                        <template x-if="!isDark">
                            {{-- Moon Icon --}}
                            <svg class="size-4.5 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </template>
                        <template x-if="isDark">
                            {{-- Sun Icon --}}
                            <svg class="size-4.5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="4"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/>
                            </svg>
                        </template>
                    </button>

                    {{-- Account Menu Button (44px) --}}
                    <div class="relative" @click.outside="accountOpen = false">
                        <button
                            type="button"
                            @click="accountOpen = !accountOpen"
                            class="inline-flex size-11 sm:w-auto sm:px-3 sm:gap-2 items-center justify-center rounded-xl border border-slate-300 dark:border-white/15 bg-surface text-text hover:border-amber-500 transition shadow-xs focus-visible:outline-2 focus-visible:outline-amber-500"
                            aria-label="Menu Akun Admin"
                            aria-haspopup="true"
                            :aria-expanded="accountOpen"
                        >
                            <span class="flex size-7 items-center justify-center rounded-lg bg-amber-500 text-slate-950 font-black text-xs">
                                {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                            </span>
                            <span class="hidden sm:inline font-bold text-xs text-text max-w-[120px] truncate">
                                {{ auth()->user()->name ?? 'Admin' }}
                            </span>
                            <svg class="hidden sm:inline size-3.5 text-text-muted transition-transform duration-200" :class="accountOpen ? 'rotate-180 text-amber-500' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {{-- Account Dropdown Menu --}}
                        <div
                            x-show="accountOpen"
                            x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 top-full mt-2 w-64 rounded-2xl border border-border bg-surface p-3 shadow-2xl z-50 space-y-2 text-xs"
                        >
                            {{-- User Info --}}
                            <div class="px-2 py-1.5 border-b border-border pb-2">
                                <p class="font-bold text-text truncate">{{ auth()->user()->name ?? 'Administrator' }}</p>
                                <p class="text-[11px] text-text-muted truncate">{{ auth()->user()->email ?? 'admin@synemax.test' }}</p>
                            </div>

                            {{-- Density Switcher --}}
                            <div class="px-2 py-1 space-y-1.5">
                                <span class="block text-[10px] font-bold uppercase tracking-wider text-text-muted">Kepadatan Tampilan</span>
                                <div class="grid grid-cols-2 gap-1.5 p-1 rounded-xl bg-surface-2 border border-border">
                                    <button
                                        type="button"
                                        @click="setDensity('nyaman')"
                                        class="py-1 rounded-lg font-bold text-center transition"
                                        :class="density === 'nyaman' ? 'bg-amber-500 text-slate-950 shadow-xs' : 'text-text-muted hover:text-text'"
                                    >
                                        Nyaman
                                    </button>
                                    <button
                                        type="button"
                                        @click="setDensity('ringkas')"
                                        class="py-1 rounded-lg font-bold text-center transition"
                                        :class="density === 'ringkas' ? 'bg-amber-500 text-slate-950 shadow-xs' : 'text-text-muted hover:text-text'"
                                    >
                                        Ringkas
                                    </button>
                                </div>
                            </div>

                            <div class="border-t border-border pt-1 space-y-1">
                                <a
                                    href="{{ route('home') }}"
                                    target="_blank"
                                    class="flex min-h-[40px] items-center gap-2.5 rounded-xl px-2.5 py-1.5 font-semibold text-text hover:bg-surface-2 transition"
                                >
                                    <svg class="size-4 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    <span>Lihat Situs Publik</span>
                                </a>

                                <a
                                    href="{{ route('admin.settings.edit') }}"
                                    class="flex min-h-[40px] items-center gap-2.5 rounded-xl px-2.5 py-1.5 font-semibold text-text hover:bg-surface-2 transition"
                                >
                                    <svg class="size-4 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span>Pengaturan Bioskop</span>
                                </a>

                                <form method="POST" action="{{ route('logout') }}" class="pt-1 border-t border-border">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="flex min-h-[40px] w-full items-center gap-2.5 rounded-xl px-2.5 py-1.5 font-bold text-danger hover:bg-danger/10 transition"
                                    >
                                        <svg class="size-4 text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        <span>Keluar (Logout)</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        {{-- Mobile Drawer Overlay & Sidebar --}}
        <div
            x-show="sidebarOpen"
            x-cloak
            class="fixed inset-0 z-50 lg:hidden"
            role="dialog"
            aria-modal="true"
        >
            {{-- Dark Overlay --}}
            <div
                x-show="sidebarOpen"
                x-transition:enter="transition-opacity ease-linear duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="sidebarOpen = false"
                class="fixed inset-0 bg-black/60 backdrop-blur-xs"
            ></div>

            {{-- Sliding Drawer Content (Width: min(86vw, 320px)) --}}
            <div
                x-show="sidebarOpen"
                x-transition:enter="transition ease-in-out duration-250 transform"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in-out duration-200 transform"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                @keydown.escape.window="sidebarOpen = false"
                class="relative flex w-[min(86vw,320px)] flex-col h-full bg-surface border-r border-border shadow-2xl z-50"
            >
                {{-- Header Drawer --}}
                <div class="flex items-center justify-between p-4 border-b border-border shrink-0">
                    <x-brand.logo size="sm" />
                    <button
                        type="button"
                        @click="sidebarOpen = false"
                        class="size-11 inline-flex items-center justify-center rounded-xl border border-border text-text hover:bg-surface-2 focus-visible:outline-2 focus-visible:outline-amber-500"
                        aria-label="Tutup menu navigasi"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Scrollable Nav List --}}
                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                    @include('layouts.partials.admin-nav')
                </div>

                {{-- Footer Drawer: User, Lihat situs, Keluar button --}}
                <div class="border-t border-border p-4 bg-surface-2/60 space-y-3 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-500/20 text-amber-500 font-bold text-xs">
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 2)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-text truncate">{{ auth()->user()->name ?? 'Admin Bioskop' }}</p>
                            <p class="text-[11px] text-text-muted truncate">{{ auth()->user()->email ?? 'admin@synemax.test' }}</p>
                        </div>
                    </div>
                    <a
                        href="{{ route('home') }}"
                        target="_blank"
                        class="flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-border bg-surface px-3 text-xs font-bold text-text hover:bg-surface-2 transition"
                    >
                        <svg class="size-4 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        <span>Lihat Situs Publik</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl border border-danger/40 bg-danger/10 px-3 text-xs font-bold text-danger hover:bg-danger/20 transition"
                        >
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @php
            $isFormPage = request()->routeIs([
                'admin.banners.create', 'admin.banners.edit',
                'admin.articles.create', 'admin.articles.edit',
                'admin.films.create', 'admin.films.edit',
                'admin.snacks.create', 'admin.snacks.edit',
                'admin.nobar.create', 'admin.nobar.edit',
                'admin.promos.create', 'admin.promos.edit',
                'admin.settings.edit',
            ]);
        @endphp

        {{-- Mobile Bottom Navigation (< 768px, hidden on form pages) --}}
        @if (! $isFormPage)
            <nav aria-label="Navigasi bawah mobile" class="fixed inset-x-0 bottom-0 z-40 block lg:hidden border-t border-border bg-surface/95 backdrop-blur pb-[env(safe-area-inset-bottom)] shadow-xl">
                <div class="grid grid-cols-5 h-16 items-center px-1">
                    {{-- Hari Ini --}}
                    <a
                        href="{{ route('admin.dashboard') }}"
                        class="flex flex-col items-center justify-center gap-1 text-center py-1.5 min-h-[44px] {{ request()->routeIs('admin.dashboard') ? 'text-[#78350F] dark:text-amber-400 font-bold border-t-2 border-[#F59E0B] dark:border-amber-400 -mt-[1px]' : 'text-text-muted hover:text-text border-t-2 border-transparent' }}"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span class="text-[10px] leading-none">Hari Ini</span>
                    </a>

                    {{-- Booking --}}
                    <a
                        href="{{ route('admin.bookings.index') }}"
                        class="flex flex-col items-center justify-center gap-1 text-center py-1.5 min-h-[44px] {{ request()->routeIs('admin.bookings.*') ? 'text-[#78350F] dark:text-amber-400 font-bold border-t-2 border-[#F59E0B] dark:border-amber-400 -mt-[1px]' : 'text-text-muted hover:text-text border-t-2 border-transparent' }}"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                        <span class="text-[10px] leading-none">Booking</span>
                    </a>

                    {{-- Jadwal --}}
                    <a
                        href="{{ route('admin.schedule.index') }}"
                        class="flex flex-col items-center justify-center gap-1 text-center py-1.5 min-h-[44px] {{ request()->routeIs('admin.schedule.*') ? 'text-[#78350F] dark:text-amber-400 font-bold border-t-2 border-[#F59E0B] dark:border-amber-400 -mt-[1px]' : 'text-text-muted hover:text-text border-t-2 border-transparent' }}"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="text-[10px] leading-none">Jadwal</span>
                    </a>

                    {{-- Nobar --}}
                    <a
                        href="{{ route('admin.nobar.index') }}"
                        class="flex flex-col items-center justify-center gap-1 text-center py-1.5 min-h-[44px] {{ request()->routeIs('admin.nobar.*') ? 'text-[#78350F] dark:text-amber-400 font-bold border-t-2 border-[#F59E0B] dark:border-amber-400 -mt-[1px]' : 'text-text-muted hover:text-text border-t-2 border-transparent' }}"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-[10px] leading-none">Nobar</span>
                    </a>

                    {{-- Menu (Opens Drawer) --}}
                    <button
                        type="button"
                        @click="sidebarOpen = true"
                        class="flex flex-col items-center justify-center gap-1 text-center py-1.5 min-h-[44px] text-text-muted hover:text-text border-t-2 border-transparent"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <span class="text-[10px] leading-none">Menu</span>
                    </button>
                </div>
            </nav>
        @endif

        {{-- Main Layout Grid --}}
        <div class="mx-auto flex max-w-screen-2xl">
            {{-- Desktop Sidebar --}}
            <aside class="sticky top-[3.5rem] lg:top-[4rem] hidden h-[calc(100vh-4rem)] w-64 shrink-0 overflow-y-auto border-r border-border p-4 lg:block">
                @include('layouts.partials.admin-nav')
            </aside>

            {{-- Main Content Column --}}
            <div class="min-w-0 flex-1">
                <main class="mx-auto min-w-0 max-w-screen-xl px-4 py-6 sm:px-6 lg:px-8 {{ ! $isFormPage ? 'pb-24 lg:pb-8' : '' }}">
                    @yield('content')
                </main>
            </div>
        </div>

        {{-- Universal In-App Dialogs (Toasts, Confirmation Modals, 419 Alert) --}}
        <x-in-app-dialogs />
    </body>
</html>
