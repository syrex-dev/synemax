<nav aria-label="Navigasi admin" class="space-y-6">
    {{-- GRUP 1: OPERASIONAL --}}
    <div>
        <p class="px-3 text-[11px] font-bold uppercase tracking-[0.15em] text-text-muted mb-2">Operasional</p>
        <div class="space-y-1">
            <a
                href="{{ route('admin.dashboard') }}"
                @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif
                class="flex min-h-[48px] items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-[#FEF3C7] text-[#78350F] font-bold border-l-4 border-[#F59E0B] rounded-r-xl rounded-l-none pl-2.5 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30 dark:rounded-xl dark:pl-3' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}"
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span>Hari Ini</span>
            </a>

            <a
                href="{{ route('admin.bookings.index') }}"
                @if (request()->routeIs('admin.bookings.*')) aria-current="page" @endif
                class="flex min-h-[48px] items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-colors {{ request()->routeIs('admin.bookings.*') ? 'bg-[#FEF3C7] text-[#78350F] font-bold border-l-4 border-[#F59E0B] rounded-r-xl rounded-l-none pl-2.5 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30 dark:rounded-xl dark:pl-3' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}"
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                </svg>
                <span>Booking</span>
            </a>

            @php
                $agreementBadgeCount = \App\Models\Session::query()
                    ->where('session_date', '>=', now()->toDateString())
                    ->where('status', 'tersedia')
                    ->whereNull('confirmed_at')
                    ->get()
                    ->filter(fn ($s) => $s->isInAgreementWindow())
                    ->count();
            @endphp
            <a
                href="{{ route('admin.schedule.index') }}"
                @if (request()->routeIs('admin.schedule.*')) aria-current="page" @endif
                class="flex min-h-[48px] items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-colors {{ request()->routeIs('admin.schedule.*') ? 'bg-[#FEF3C7] text-[#78350F] font-bold border-l-4 border-[#F59E0B] rounded-r-xl rounded-l-none pl-2.5 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30 dark:rounded-xl dark:pl-3' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}"
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Jadwal</span>
                @if ($agreementBadgeCount > 0)
                    <span class="ml-auto inline-flex items-center justify-center rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-black text-black animate-pulse" title="{{ $agreementBadgeCount }} sesi butuh kesepakatan">
                        {{ $agreementBadgeCount }}
                    </span>
                @endif
            </a>

            <a
                href="{{ route('admin.nobar.index') }}"
                @if (request()->routeIs('admin.nobar.*')) aria-current="page" @endif
                class="flex min-h-[48px] items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-colors {{ request()->routeIs('admin.nobar.*') ? 'bg-[#FEF3C7] text-[#78350F] font-bold border-l-4 border-[#F59E0B] rounded-r-xl rounded-l-none pl-2.5 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30 dark:rounded-xl dark:pl-3' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}"
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Nobar</span>
            </a>
        </div>
    </div>

    {{-- GRUP 2: KONTEN --}}
    <div>
        <p class="px-3 text-[11px] font-bold uppercase tracking-[0.15em] text-text-muted mb-2">Konten</p>
        <div class="space-y-1">
            <a
                href="{{ route('admin.films.index') }}"
                @if (request()->routeIs('admin.films.*')) aria-current="page" @endif
                class="flex min-h-[48px] items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-colors {{ request()->routeIs('admin.films.*') ? 'bg-[#FEF3C7] text-[#78350F] font-bold border-l-4 border-[#F59E0B] rounded-r-xl rounded-l-none pl-2.5 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30 dark:rounded-xl dark:pl-3' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}"
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                </svg>
                <span>Film</span>
            </a>

            <a
                href="{{ route('admin.banners.index') }}"
                @if (request()->routeIs('admin.banners.*')) aria-current="page" @endif
                class="flex min-h-[48px] items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-colors {{ request()->routeIs('admin.banners.*') ? 'bg-[#FEF3C7] text-[#78350F] font-bold border-l-4 border-[#F59E0B] rounded-r-xl rounded-l-none pl-2.5 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30 dark:rounded-xl dark:pl-3' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}"
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Banner Hero</span>
            </a>

            <a
                href="{{ route('admin.promos.index') }}"
                @if (request()->routeIs('admin.promos.*')) aria-current="page" @endif
                class="flex min-h-[48px] items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-colors {{ request()->routeIs('admin.promos.*') ? 'bg-[#FEF3C7] text-[#78350F] font-bold border-l-4 border-[#F59E0B] rounded-r-xl rounded-l-none pl-2.5 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30 dark:rounded-xl dark:pl-3' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}"
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                <span>Promo</span>
            </a>

            <a
                href="{{ route('admin.articles.index') }}"
                @if (request()->routeIs('admin.articles.*')) aria-current="page" @endif
                class="flex min-h-[48px] items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-colors {{ request()->routeIs('admin.articles.*') ? 'bg-[#FEF3C7] text-[#78350F] font-bold border-l-4 border-[#F59E0B] rounded-r-xl rounded-l-none pl-2.5 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30 dark:rounded-xl dark:pl-3' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}"
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                </svg>
                <span>Kabar</span>
            </a>

            <a
                href="{{ route('admin.snacks.index') }}"
                @if (request()->routeIs('admin.snacks.*') && ! request()->routeIs('admin.snacks.preparation.*')) aria-current="page" @endif
                class="flex min-h-[48px] items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-colors {{ request()->routeIs('admin.snacks.*') && ! request()->routeIs('admin.snacks.preparation.*') ? 'bg-[#FEF3C7] text-[#78350F] font-bold border-l-4 border-[#F59E0B] rounded-r-xl rounded-l-none pl-2.5 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30 dark:rounded-xl dark:pl-3' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}"
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                </svg>
                <span>Jajanan</span>
            </a>
        </div>
    </div>

    {{-- GRUP 3: LAINNYA --}}
    <div>
        <p class="px-3 text-[11px] font-bold uppercase tracking-[0.15em] text-text-muted mb-2">Lainnya</p>
        <div class="space-y-1">
            <a
                href="{{ route('admin.snacks.preparation.index') }}"
                @if (request()->routeIs('admin.snacks.preparation.*')) aria-current="page" @endif
                class="flex min-h-[48px] items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-colors {{ request()->routeIs('admin.snacks.preparation.*') ? 'bg-[#FEF3C7] text-[#78350F] font-bold border-l-4 border-[#F59E0B] rounded-r-xl rounded-l-none pl-2.5 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30 dark:rounded-xl dark:pl-3' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}"
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span>Daftar siapkan</span>
            </a>

            <a
                href="{{ route('admin.reports.index') }}"
                @if (request()->routeIs('admin.reports.*')) aria-current="page" @endif
                class="flex min-h-[48px] items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-colors {{ request()->routeIs('admin.reports.*') ? 'bg-[#FEF3C7] text-[#78350F] font-bold border-l-4 border-[#F59E0B] rounded-r-xl rounded-l-none pl-2.5 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30 dark:rounded-xl dark:pl-3' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}"
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span>Laporan</span>
            </a>

            <a
                href="{{ route('admin.settings.edit') }}"
                @if (request()->routeIs('admin.settings.*')) aria-current="page" @endif
                class="flex min-h-[48px] items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-colors {{ request()->routeIs('admin.settings.*') ? 'bg-[#FEF3C7] text-[#78350F] font-bold border-l-4 border-[#F59E0B] rounded-r-xl rounded-l-none pl-2.5 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30 dark:rounded-xl dark:pl-3' : 'text-text-muted hover:bg-surface-2 hover:text-text' }}"
            >
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Pengaturan</span>
            </a>
        </div>
    </div>
</nav>
