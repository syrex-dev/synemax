<nav x-data="{ open: false }" class="border-b border-border bg-surface">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
        <a href="{{ route('admin.dashboard') }}" class="font-bold text-accent">← Panel admin</a>
        <div class="hidden items-center gap-4 sm:flex">
            <button type="button" data-theme-toggle role="switch" aria-label="Mode gelap" aria-checked="false" class="group relative inline-flex min-h-11 min-w-[76px] items-center justify-between rounded-full border border-border bg-surface px-2 text-text transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus" title="Ganti tema terang atau gelap">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="size-4"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="size-4"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5 8.5 8.5 0 1 0 20.5 14.2Z"/></svg>
                <span data-theme-thumb class="pointer-events-none absolute left-1 top-1 size-9 rounded-full bg-accent shadow-sm transition-transform duration-150"></span>
            </button>
            <span class="text-base text-text-muted">{{ Auth::user()->name }}</span>
            <a href="{{ route('profile.edit') }}" class="min-h-11 content-center text-base text-text hover:text-accent">Profil admin</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="min-h-11 rounded-lg border border-border px-3 py-2 text-base text-text hover:bg-surface-2">Keluar</button></form>
        </div>
        <button type="button" @click="open = !open" aria-controls="profile-mobile-menu" class="min-h-11 rounded-lg border border-border px-3 text-base text-text sm:hidden" :aria-expanded="open">Menu</button>
    </div>
    <div id="profile-mobile-menu" x-show="open" x-cloak class="space-y-2 border-t border-border px-4 py-4 sm:hidden">
        <p class="text-base text-text-muted">{{ Auth::user()->name }}</p>
        <span id="profile-theme-mode-mobile-label" class="block text-base font-semibold text-text">Tema tampilan</span>
        <button type="button" data-theme-toggle role="switch" aria-label="Mode gelap" aria-checked="false" aria-labelledby="profile-theme-mode-mobile-label" class="group relative inline-flex min-h-11 min-w-[76px] items-center justify-between rounded-full border border-border bg-surface px-2 text-text transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus">
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="size-4"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="size-4"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5 8.5 8.5 0 1 0 20.5 14.2Z"/></svg>
            <span data-theme-thumb class="pointer-events-none absolute left-1 top-1 size-9 rounded-full bg-accent shadow-sm transition-transform duration-150"></span>
        </button>
        <a href="{{ route('profile.edit') }}" class="block min-h-11 rounded-lg px-3 py-2 text-base text-text hover:bg-surface-2">Profil admin</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="min-h-11 w-full rounded-lg border border-border px-3 py-2 text-left text-base text-text">Keluar</button></form>
    </div>
</nav>
