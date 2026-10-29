@extends('layouts.admin')

@section('title', 'Ringkasan Hari Ini')

@push('styles')
<style>
    @media print {
        header, aside, nav, .no-print, button, a[href*="admin"] {
            display: none !important;
        }
        body, html, main {
            background-color: #ffffff !important;
            color: #000000 !important;
            padding: 0.5cm !important;
            margin: 0 !important;
        }
        .print-container {
            display: block !important;
        }
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    {{-- ============================================================== --}}
    {{-- 1. PAGE HEADER                                                 --}}
    {{-- ============================================================== --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Operasional Bioskop</p>
            </div>
            <h1 class="mt-1 text-2xl sm:text-3xl font-black text-text tracking-tight">Ringkasan hari ini</h1>
            <p class="mt-0.5 text-xs sm:text-sm text-text-muted">
                {{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}
            </p>
        </div>

        <div class="flex items-center gap-2.5 no-print" x-data="{ moreOpen: false }">
            <a
                href="{{ route('admin.schedule.index') }}"
                class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 text-xs sm:text-sm font-bold text-slate-950 hover:bg-amber-400 transition shadow-sm"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Kelola Sesi</span>
            </a>

            {{-- Titik Tiga Menu --}}
            <div class="relative" @click.outside="moreOpen = false">
                <button
                    type="button"
                    @click="moreOpen = !moreOpen"
                    class="inline-flex size-11 items-center justify-center rounded-xl border border-slate-300 dark:border-white/15 bg-surface text-text hover:bg-surface-2 transition shadow-xs focus-visible:outline-2 focus-visible:outline-amber-500"
                    aria-label="Menu lainnya"
                >
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                    </svg>
                </button>

                <div
                    x-show="moreOpen"
                    x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 top-full mt-2 w-48 rounded-2xl border border-border bg-surface p-1.5 shadow-xl z-50 text-xs"
                >
                    <button
                        type="button"
                        onclick="window.print()"
                        @click="moreOpen = false"
                        class="flex w-full min-h-[40px] items-center gap-2 rounded-xl px-3 py-2 font-semibold text-text hover:bg-surface-2 transition text-left"
                    >
                        <svg class="size-4 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Cetak Laporan</span>
                    </button>
                    <a
                        href="{{ route('admin.reports.index') }}"
                        class="flex w-full min-h-[40px] items-center gap-2 rounded-xl px-3 py-2 font-semibold text-text hover:bg-surface-2 transition"
                    >
                        <svg class="size-4 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span>Laporan Lengkap</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================== --}}
    {{-- 2. STATISTIK UTAMA (4 Kartu Kompak)                           --}}
    {{-- ============================================================== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        {{-- Card 1: Sesi Hari Ini --}}
        <a
            href="{{ route('admin.schedule.index') }}"
            class="group flex flex-col justify-between rounded-2xl border border-slate-300 dark:border-white/10 bg-surface p-4 shadow-sm hover:border-amber-500 hover:shadow-md transition min-h-[92px]"
        >
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-text-muted">Sesi Hari Ini</span>
                <svg class="size-4 text-text-muted group-hover:text-amber-500 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-black text-text group-hover:text-amber-500 transition">{{ $todaySessions->count() }}</span>
                <span class="text-[11px] font-semibold text-text-muted">{{ $todayCapacity }} total kursi</span>
            </div>
        </a>

        {{-- Card 2: Kursi Terisi Hari Ini --}}
        <a
            href="{{ route('admin.schedule.index') }}"
            class="group flex flex-col justify-between rounded-2xl border border-slate-300 dark:border-white/10 bg-surface p-4 shadow-sm hover:border-amber-500 hover:shadow-md transition min-h-[92px]"
        >
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-text-muted">Kursi Terisi</span>
                <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400">{{ $todayOccupancyPercent }}%</span>
            </div>
            <div class="mt-2">
                <div class="flex items-baseline justify-between">
                    <span class="text-2xl sm:text-3xl font-black text-text group-hover:text-amber-500 transition">{{ $todayViewers }}</span>
                    <span class="text-[11px] font-semibold text-text-muted">/ {{ $todayCapacity }} kursi</span>
                </div>
                <div class="mt-2 h-1.5 w-full rounded-full bg-surface-2 overflow-hidden">
                    <div class="h-full rounded-full bg-amber-500 transition-all duration-500" style="width: {{ min(100, $todayOccupancyPercent) }}%"></div>
                </div>
            </div>
        </a>

        {{-- Card 3: Booking Menunggu --}}
        <a
            href="{{ route('admin.bookings.index', ['status' => 'menunggu_konfirmasi']) }}"
            class="group flex flex-col justify-between rounded-2xl border border-slate-300 dark:border-white/10 bg-surface p-4 shadow-sm hover:border-amber-500 hover:shadow-md transition min-h-[92px]"
        >
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-text-muted">Booking Menunggu</span>
                @if ($waitingBookings > 0)
                    <span class="flex size-2 rounded-full bg-amber-500 animate-ping"></span>
                @endif
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-black {{ $waitingBookings > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-text' }}">{{ $waitingBookings }}</span>
                <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400 group-hover:underline">Konfirmasi →</span>
            </div>
        </a>

        {{-- Card 4: Perlu Tindakan --}}
        <div
            class="flex flex-col justify-between rounded-2xl border border-slate-300 dark:border-white/10 bg-surface p-4 shadow-sm min-h-[92px]"
        >
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-text-muted">Perlu Tindakan</span>
                @if ($totalActionsCount > 0)
                    <span class="inline-flex items-center justify-center rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-400">
                        {{ $totalActionsCount }} Hal
                    </span>
                @else
                    <span class="text-emerald-500 text-xs font-bold">✓ Aman</span>
                @endif
            </div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-black {{ $totalActionsCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-text' }}">
                    {{ $totalActionsCount }}
                </span>
                <span class="text-[11px] text-text-muted">{{ $totalActionsCount > 0 ? 'Perlu dicek' : 'Semua beres' }}</span>
            </div>
        </div>
    </div>

    {{-- ============================================================== --}}
    {{-- 3. LAYOUT UTAMA (2 Kolom Desktop, 1 Kolom Mobile)              --}}
    {{-- ============================================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        {{-- KOLOM KIRI (Desktop ~2/3 = 8 Kolom): Sesi Perlu Penonton & Jadwal Hari Ini --}}
        <div class="lg:col-span-8 space-y-6">

            {{-- ============================================================== --}}
            {{-- A. KARTU: SESI PERLU PENONTON                                  --}}
            {{-- ============================================================== --}}
            @php
                // Default tab: first tab that contains items
                $defaultTab = $lowToday->isNotEmpty() ? 'hari_ini' : ($lowTomorrow->isNotEmpty() ? 'besok' : 'mendatang');
            @endphp
            <div
                class="rounded-2xl border border-slate-300 dark:border-white/10 bg-surface shadow-sm overflow-hidden"
                x-data="{
                    activeTab: '{{ $defaultTab }}'
                }"
            >
                {{-- Card Header --}}
                <div class="p-4 sm:p-5 border-b border-border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base sm:text-lg font-bold text-text">Sesi Menunggu Kuorum</h2>
                            <span class="inline-flex items-center rounded-full bg-amber-500/15 px-2 py-0.5 text-[11px] font-black text-amber-600 dark:text-amber-400">
                                {{ $lowToday->count() + $lowTomorrow->count() + $lowUpcoming->count() }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-xs text-text-muted">
                            Minimal {{ $minimumViewers }} penonton per sesi
                        </p>
                    </div>

                    {{-- Hubungi Pemesan Button (ONLY shown if any session has bookings) --}}
                    @if ($hasAnyBookedLowAttendance)
                        <a
                            href="{{ route('admin.bookings.index') }}"
                            class="inline-flex min-h-[38px] items-center justify-center gap-1.5 rounded-xl border border-amber-500/40 bg-amber-500/10 px-3 py-1.5 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-500 hover:text-slate-950 transition shrink-0"
                        >
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <span>Hubungi Pemesan</span>
                        </a>
                    @endif
                </div>

                {{-- Horizontal Tabs Bar --}}
                <div class="flex items-center border-b border-border bg-surface-2/40 px-3 overflow-x-auto scrollbar-none">
                    <button
                        type="button"
                        @click="activeTab = 'hari_ini'"
                        class="min-h-[44px] px-3.5 text-xs font-bold border-b-2 transition-colors whitespace-nowrap"
                        :class="activeTab === 'hari_ini' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-text-muted hover:text-text'"
                    >
                        Hari Ini ({{ $lowToday->count() }})
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'besok'"
                        class="min-h-[44px] px-3.5 text-xs font-bold border-b-2 transition-colors whitespace-nowrap"
                        :class="activeTab === 'besok' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-text-muted hover:text-text'"
                    >
                        Besok ({{ $lowTomorrow->count() }})
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'mendatang'"
                        class="min-h-[44px] px-3.5 text-xs font-bold border-b-2 transition-colors whitespace-nowrap"
                        :class="activeTab === 'mendatang' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-text-muted hover:text-text'"
                    >
                        Mendatang ({{ $lowUpcoming->count() }})
                    </button>
                </div>

                {{-- TAB 1: HARI INI --}}
                <div x-show="activeTab === 'hari_ini'">
                    @if ($lowToday->isEmpty())
                        <div class="p-8 text-center text-text-muted">
                            <span class="inline-flex size-10 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-500 mb-2">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <p class="text-sm font-bold text-text">Semua sesi hari ini aman</p>
                            <p class="text-xs text-text-muted mt-0.5">Seluruh sesi tayang hari ini telah mencapai kuorum atau pasti tayang.</p>
                        </div>
                    @else
                        <div class="divide-y divide-border">
                            @foreach ($lowToday->take(5) as $session)
                                @php
                                    $deadline = $session->confirmationDeadline();
                                    $minsToDeadline = now()->diffInMinutes($deadline, false);
                                    $isUrgent = $session->isInAgreementWindow() || ($minsToDeadline >= 0 && $minsToDeadline <= 360);
                                    $tallies = $session->candidateFilmTallies();
                                    if ($tallies->isNotEmpty()) {
                                        $parts = $tallies->map(fn($t) => "{$t['title']} {$t['seats']}")->implode(' · ');
                                        $leading = $tallies->first()['seats'] ?? 0;
                                        $shortage = max(0, $minimumViewers - $leading);
                                        $summary = "{$parts} · butuh {$shortage} lagi";
                                    } else {
                                        $leading = 0;
                                        $summary = "Belum ada peminat · butuh {$minimumViewers} lagi";
                                    }
                                @endphp
                                <a
                                    href="{{ $session->occupiedBookings->isNotEmpty() ? route('admin.bookings.index', ['session_id' => $session->id]) : route('admin.schedule.index') }}"
                                    class="group flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-3 sm:px-4 sm:py-3.5 min-h-[52px] hover:bg-surface-2 transition text-xs"
                                >
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="shrink-0 px-2 py-0.5 rounded-md font-black text-[10px] uppercase {{ $isUrgent ? 'bg-danger text-white' : 'bg-amber-500/20 text-amber-700 dark:text-amber-400' }}">
                                            {{ $isUrgent ? 'Mendesak' : 'Hari Ini' }}
                                        </span>
                                        <span class="font-bold text-text font-tabular shrink-0">
                                            {{ str_replace(':', '.', substr((string) $session->start_time, 0, 5)) }} WIB
                                        </span>
                                        <span class="font-medium text-text truncate group-hover:text-amber-500 transition">
                                            {{ $summary }}
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0">
                                        <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg bg-surface-2 border border-border">
                                            <div class="w-12 h-1.5 bg-border rounded-full overflow-hidden">
                                                <div class="h-full bg-amber-500 rounded-full" style="width: {{ min(100, ($leading / $minimumViewers) * 100) }}%"></div>
                                            </div>
                                            <span class="font-bold text-[11px] text-text font-tabular">{{ $leading }}/{{ $minimumViewers }}</span>
                                        </div>
                                        <svg class="size-4 text-text-muted group-hover:text-amber-500 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        @if ($lowToday->count() > 5)
                            <div class="p-3 border-t border-border text-center bg-surface-2/30">
                                <a href="{{ route('admin.schedule.index') }}" class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline">
                                    Lihat semua {{ $lowToday->count() }} sesi hari ini →
                                </a>
                            </div>
                        @endif
                    @endif
                </div>

                {{-- TAB 2: BESOK --}}
                <div x-show="activeTab === 'besok'">
                    @if ($lowTomorrow->isEmpty())
                        <div class="p-8 text-center text-text-muted">
                            <span class="inline-flex size-10 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-500 mb-2">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <p class="text-sm font-bold text-text">Semua sesi besok aman</p>
                            <p class="text-xs text-text-muted mt-0.5">Seluruh sesi tayang besok telah mencapai kuorum atau pasti tayang.</p>
                        </div>
                    @else
                        <div class="divide-y divide-border">
                            @foreach ($lowTomorrow->take(5) as $session)
                                @php
                                    $deadline = $session->confirmationDeadline();
                                    $minsToDeadline = now()->diffInMinutes($deadline, false);
                                    $isUrgent = $session->isInAgreementWindow() || ($minsToDeadline >= 0 && $minsToDeadline <= 360);
                                    $tallies = $session->candidateFilmTallies();
                                    if ($tallies->isNotEmpty()) {
                                        $parts = $tallies->map(fn($t) => "{$t['title']} {$t['seats']}")->implode(' · ');
                                        $leading = $tallies->first()['seats'] ?? 0;
                                        $shortage = max(0, $minimumViewers - $leading);
                                        $summary = "{$parts} · butuh {$shortage} lagi";
                                    } else {
                                        $leading = 0;
                                        $summary = "Belum ada peminat · butuh {$minimumViewers} lagi";
                                    }
                                @endphp
                                <a
                                    href="{{ $session->occupiedBookings->isNotEmpty() ? route('admin.bookings.index', ['session_id' => $session->id]) : route('admin.schedule.index') }}"
                                    class="group flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-3 sm:px-4 sm:py-3.5 min-h-[52px] hover:bg-surface-2 transition text-xs"
                                >
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="shrink-0 px-2 py-0.5 rounded-md font-bold text-[10px] uppercase {{ $isUrgent ? 'bg-danger text-white' : 'bg-surface-2 text-text-muted border border-border' }}">
                                            {{ $isUrgent ? 'Mendesak' : 'Besok' }}
                                        </span>
                                        <span class="font-bold text-text font-tabular shrink-0">
                                            {{ str_replace(':', '.', substr((string) $session->start_time, 0, 5)) }} WIB
                                        </span>
                                        <span class="font-medium text-text truncate group-hover:text-amber-500 transition">
                                            {{ $summary }}
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0">
                                        <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg bg-surface-2 border border-border">
                                            <div class="w-12 h-1.5 bg-border rounded-full overflow-hidden">
                                                <div class="h-full bg-amber-500 rounded-full" style="width: {{ min(100, ($leading / $minimumViewers) * 100) }}%"></div>
                                            </div>
                                            <span class="font-bold text-[11px] text-text font-tabular">{{ $leading }}/{{ $minimumViewers }}</span>
                                        </div>
                                        <svg class="size-4 text-text-muted group-hover:text-amber-500 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        @if ($lowTomorrow->count() > 5)
                            <div class="p-3 border-t border-border text-center bg-surface-2/30">
                                <a href="{{ route('admin.schedule.index') }}" class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline">
                                    Lihat semua {{ $lowTomorrow->count() }} sesi besok →
                                </a>
                            </div>
                        @endif
                    @endif
                </div>

                {{-- TAB 3: MENDATANG --}}
                <div x-show="activeTab === 'mendatang'">
                    @if ($lowUpcoming->isEmpty())
                        <div class="p-8 text-center text-text-muted">
                            <span class="inline-flex size-10 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-500 mb-2">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <p class="text-sm font-bold text-text">Tidak ada sesi mendesak</p>
                            <p class="text-xs text-text-muted mt-0.5">Semua sesi dalam jendela pemantauan telah mencapai kuorum atau pasti tayang.</p>
                        </div>
                    @else
                        <div class="divide-y divide-border">
                            @foreach ($lowUpcoming->take(5) as $session)
                                @php
                                    $deadline = $session->confirmationDeadline();
                                    $minsToDeadline = now()->diffInMinutes($deadline, false);
                                    $isUrgent = $session->isInAgreementWindow() || ($minsToDeadline >= 0 && $minsToDeadline <= 360);
                                    $tallies = $session->candidateFilmTallies();
                                    if ($tallies->isNotEmpty()) {
                                        $parts = $tallies->map(fn($t) => "{$t['title']} {$t['seats']}")->implode(' · ');
                                        $leading = $tallies->first()['seats'] ?? 0;
                                        $shortage = max(0, $minimumViewers - $leading);
                                        $summary = "{$parts} · butuh {$shortage} lagi";
                                    } else {
                                        $leading = 0;
                                        $summary = "Belum ada peminat · butuh {$minimumViewers} lagi";
                                    }
                                @endphp
                                <a
                                    href="{{ $session->occupiedBookings->isNotEmpty() ? route('admin.bookings.index', ['session_id' => $session->id]) : route('admin.schedule.index') }}"
                                    class="group flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-3 sm:px-4 sm:py-3.5 min-h-[52px] hover:bg-surface-2 transition text-xs"
                                >
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="shrink-0 px-2 py-0.5 rounded-md font-bold text-[10px] uppercase {{ $isUrgent ? 'bg-danger text-white' : 'bg-surface-2 text-text-muted border border-border' }}">
                                            {{ $isUrgent ? 'Mendesak' : strtoupper(\Carbon\Carbon::parse($session->session_date)->locale('id')->isoFormat('ddd, D MMM')) }}
                                        </span>
                                        <span class="font-bold text-text font-tabular shrink-0">
                                            {{ str_replace(':', '.', substr((string) $session->start_time, 0, 5)) }} WIB
                                        </span>
                                        <span class="font-medium text-text truncate group-hover:text-amber-500 transition">
                                            {{ $summary }}
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0">
                                        <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg bg-surface-2 border border-border">
                                            <div class="w-12 h-1.5 bg-border rounded-full overflow-hidden">
                                                <div class="h-full bg-amber-500 rounded-full" style="width: {{ min(100, ($leading / $minimumViewers) * 100) }}%"></div>
                                            </div>
                                            <span class="font-bold text-[11px] text-text font-tabular">{{ $leading }}/{{ $minimumViewers }}</span>
                                        </div>
                                        <svg class="size-4 text-text-muted group-hover:text-amber-500 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        @if ($lowUpcoming->count() > 5)
                            <div class="p-3 border-t border-border text-center bg-surface-2/30">
                                <a href="{{ route('admin.schedule.index') }}" class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline">
                                    Lihat semua {{ $lowUpcoming->count() }} sesi mendatang →
                                </a>
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Summary row for distant sessions beyond warning window --}}
                @if ($distantCount > 0)
                    <div class="px-4 py-2.5 bg-surface-2/60 border-t border-border flex items-center justify-between text-xs text-text-muted">
                        <span>{{ $distantCount }} sesi di luar jendela {{ $warningWindowHours }} jam belum mencapai kuota.</span>
                        <a href="{{ route('admin.schedule.index') }}" class="font-bold text-text hover:text-amber-500">Buka Jadwal →</a>
                    </div>
                @endif
            </div>

            {{-- ============================================================== --}}
            {{-- B. KARTU: JADWAL HARI INI (Timeline Ringkas)                  --}}
            {{-- ============================================================== --}}
            <div class="rounded-2xl border border-slate-300 dark:border-white/10 bg-surface shadow-sm p-4 sm:p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-text">Sesi hari ini</h2>
                        <p class="text-xs text-text-muted mt-0.5">Timeline pemutaran film di studio SynemaX</p>
                    </div>
                    <a href="{{ route('admin.schedule.index') }}" class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline">
                        Semua Sesi →
                    </a>
                </div>

                @if ($todaySessions->isEmpty())
                    <div class="rounded-xl border border-dashed border-border p-8 text-center text-xs text-text-muted">
                        Belum ada jadwal sesi yang aktif hari ini. Tekan tombol <a href="{{ route('admin.schedule.index') }}" class="text-amber-500 font-bold underline">Kelola Sesi</a> untuk membuat jadwal.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($todaySessions as $session)
                            @php
                                $cap = $session->seatCapacity();
                                $occ = (int) $session->viewer_count;
                                $pct = $cap > 0 ? round(($occ / $cap) * 100) : 0;
                            @endphp
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-xl border border-border bg-surface-2/40 hover:bg-surface-2 transition">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex flex-col items-center justify-center size-12 rounded-xl bg-surface border border-border shrink-0">
                                        <span class="text-xs font-black text-amber-600 dark:text-amber-400 font-tabular">{{ str_replace(':', '.', substr((string) $session->start_time, 0, 5)) }}</span>
                                        <span class="text-[9px] text-text-muted font-bold">WIB</span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <h3 class="font-bold text-sm text-text truncate">{{ $session->activeFilm()?->title ?? 'Belum ada film' }}</h3>
                                            @if ($session->activeFilm()?->age_rating)
                                                <span class="rounded px-1.5 py-0.2 text-[9px] font-black bg-black/60 text-amber-400 border border-white/10">{{ $session->activeFilm()->age_rating }}</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-text-muted mt-0.5">
                                            {{ $session->is_private ? 'Sewa Private' : 'Publik' }} · {{ (int) $session->viewer_count }} kursi terpesan · {{ $session->occupied_bookings_count }} booking
                                            @if ($session->pending_booking_count > 0)
                                                · <span class="text-warning font-bold">{{ $session->pending_booking_count }} booking menunggu</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0">
                                    <div class="text-right">
                                        <span class="block text-xs font-bold text-text font-tabular">{{ $occ }}/{{ $cap }} Kursi</span>
                                        <div class="w-16 h-1.5 bg-border rounded-full overflow-hidden mt-1">
                                            <div class="h-full bg-amber-500 rounded-full" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>
                                    <span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase {{ $session->status === 'tersedia' ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-surface-2 text-text-muted' }}">
                                        {{ $session->status }}
                                    </span>
                                </div>
                            </div>

                            {{-- Persiapan Jajan per Sesi (Bila Ada) --}}
                            @php($snackBookings = $session->occupiedBookings->filter(fn ($booking) => $booking->items->isNotEmpty()))
                            @if ($snackBookings->isNotEmpty())
                                <div class="px-3.5 pb-3.5 -mt-2 rounded-b-xl border-x border-b border-border bg-surface-2/20 text-xs">
                                    <p class="text-[11px] font-bold text-text-muted uppercase tracking-wider mb-2">Persiapan jajan ({{ $snackBookings->count() }} booking)</p>
                                    <div class="space-y-1.5">
                                        @foreach ($snackBookings as $b)
                                            <div class="p-2.5 rounded-lg bg-surface border border-border/80">
                                                <div class="flex items-center justify-between">
                                                    <span class="font-bold text-text">{{ $b->customer_name }}</span>
                                                    <span class="font-mono text-amber-500 font-bold">{{ $b->code }}</span>
                                                </div>
                                                <ul class="mt-1 space-y-0.5 text-text-muted">
                                                    @foreach ($b->items as $item)
                                                        <li>{{ $item->qty }}× {{ $item->name_snapshot }}@if ($item->note) · <span class="text-text font-medium">{{ $item->note }}</span>@endif</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- KOLOM KANAN (Desktop ~1/3 = 4 Kolom): Perlu Tindakan & Pintasan Cepat --}}
        <div class="lg:col-span-4 space-y-6">

            {{-- ============================================================== --}}
            {{-- C. KARTU: PERLU TINDAKAN (Gabungan Peringatan)                 --}}
            {{-- ============================================================== --}}
            <div class="rounded-2xl border border-slate-300 dark:border-white/10 bg-surface shadow-sm p-4 sm:p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base sm:text-lg font-bold text-text">Perlu Tindakan</h2>
                    @if ($totalActionsCount > 0)
                        <span class="px-2 py-0.5 rounded-full text-xs font-black bg-amber-500/15 text-amber-600 dark:text-amber-400">
                            {{ $totalActionsCount }}
                        </span>
                    @endif
                </div>

                @if ($actionItems->isEmpty())
                    <div class="py-6 text-center text-text-muted">
                        <span class="inline-flex size-10 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-500 mb-2">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <p class="text-sm font-bold text-text">Tidak ada yang perlu ditindak</p>
                        <p class="text-xs text-text-muted mt-0.5">Seluruh operasional, pembayaran, dan konten berjalan lancar.</p>
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach ($actionItems->take(4) as $item)
                            <a
                                href="{{ $item['url'] }}"
                                class="group flex items-center justify-between gap-3 p-3 rounded-xl border border-border hover:border-amber-500 hover:bg-surface-2 transition text-xs"
                            >
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg {{ $item['level'] === 'critical' ? 'bg-danger/15 text-danger' : ($item['level'] === 'warning' ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400' : 'bg-surface-2 text-text-muted') }}">
                                        @if ($item['type'] === 'booking')
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                                        @elseif ($item['type'] === 'qris' || $item['type'] === 'transfer')
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        @elseif ($item['type'] === 'promo')
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                        @else
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @endif
                                    </div>
                                    <span class="font-semibold text-text group-hover:text-amber-500 transition truncate">
                                        {{ $item['title'] }}
                                    </span>
                                </div>
                                <svg class="size-4 text-text-muted group-hover:text-amber-500 transition shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        @endforeach
                    </div>
                    @if ($actionItems->count() > 4)
                        <div class="pt-2 text-center border-t border-border">
                            <a href="{{ route('admin.bookings.index') }}" class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline">
                                Lihat semua ({{ $actionItems->count() }}) →
                            </a>
                        </div>
                    @endif
                @endif
            </div>

            {{-- ============================================================== --}}
            {{-- D. PINTASAN CEPAT                                              --}}
            {{-- ============================================================== --}}
            <div class="rounded-2xl border border-slate-300 dark:border-white/10 bg-surface shadow-sm p-4 sm:p-5 space-y-3" x-data="{ quickSheetOpen: false }">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold text-text">Pintasan Cepat</h2>
                </div>

                {{-- Desktop: Deretan Chip Rapi --}}
                <div class="hidden sm:grid sm:grid-cols-2 gap-2 text-xs">
                    <a href="{{ route('admin.films.create') }}" class="flex min-h-[44px] items-center gap-2 rounded-xl border border-border p-2.5 font-bold text-text hover:border-amber-500 hover:text-amber-500 hover:bg-surface-2 transition">
                        <span class="flex size-6 items-center justify-center rounded-lg bg-amber-500/15 text-amber-500 font-black">+</span>
                        <span class="truncate">Tambah Film</span>
                    </a>
                    <a href="{{ route('admin.promos.create') }}" class="flex min-h-[44px] items-center gap-2 rounded-xl border border-border p-2.5 font-bold text-text hover:border-amber-500 hover:text-amber-500 hover:bg-surface-2 transition">
                        <span class="flex size-6 items-center justify-center rounded-lg bg-amber-500/15 text-amber-500 font-black">+</span>
                        <span class="truncate">Tambah Promo</span>
                    </a>
                    <a href="{{ route('admin.nobar.create') }}" class="flex min-h-[44px] items-center gap-2 rounded-xl border border-border p-2.5 font-bold text-text hover:border-amber-500 hover:text-amber-500 hover:bg-surface-2 transition">
                        <span class="flex size-6 items-center justify-center rounded-lg bg-amber-500/15 text-amber-500 font-black">+</span>
                        <span class="truncate">Tambah Nobar</span>
                    </a>
                    <a href="{{ route('admin.banners.create') }}" class="flex min-h-[44px] items-center gap-2 rounded-xl border border-border p-2.5 font-bold text-text hover:border-amber-500 hover:text-amber-500 hover:bg-surface-2 transition">
                        <span class="flex size-6 items-center justify-center rounded-lg bg-amber-500/15 text-amber-500 font-black">+</span>
                        <span class="truncate">Slide Hero</span>
                    </a>
                </div>

                {{-- Mobile: Single "+ Tambah" Button opening bottom sheet --}}
                <div class="sm:hidden">
                    <button
                        type="button"
                        @click="quickSheetOpen = true"
                        class="flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl border border-amber-500 bg-amber-500/10 px-4 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-500 hover:text-slate-950 transition"
                    >
                        <span class="font-black text-sm">+</span>
                        <span>Tambah Konten Cepat</span>
                    </button>

                    {{-- Bottom Sheet Modal for Mobile --}}
                    <div
                        x-show="quickSheetOpen"
                        x-cloak
                        class="fixed inset-0 z-50 sm:hidden"
                    >
                        <div
                            x-show="quickSheetOpen"
                            x-transition:enter="transition-opacity ease-out duration-200"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition-opacity ease-in duration-150"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            @click="quickSheetOpen = false"
                            class="fixed inset-0 bg-black/60 backdrop-blur-xs"
                        ></div>

                        <div
                            x-show="quickSheetOpen"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="translate-y-full"
                            x-transition:enter-end="translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="translate-y-0"
                            x-transition:leave-end="translate-y-full"
                            class="fixed inset-x-0 bottom-0 rounded-t-3xl border-t border-border bg-surface p-4 shadow-2xl z-50 space-y-3"
                        >
                            <div class="flex items-center justify-between pb-3 border-b border-border">
                                <span class="text-xs font-bold uppercase tracking-wider text-text">Pintasan Tambah Konten</span>
                                <button type="button" @click="quickSheetOpen = false" class="text-text-muted hover:text-text p-1">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="space-y-2 text-xs">
                                <a href="{{ route('admin.films.create') }}" class="flex min-h-[48px] items-center gap-3 rounded-xl p-3 font-bold text-text bg-surface-2 hover:bg-amber-500 hover:text-slate-950 transition">
                                    <span class="flex size-7 items-center justify-center rounded-lg bg-amber-500/20 text-amber-500 font-black">+</span>
                                    <span>Tambah Film Baru</span>
                                </a>
                                <a href="{{ route('admin.promos.create') }}" class="flex min-h-[48px] items-center gap-3 rounded-xl p-3 font-bold text-text bg-surface-2 hover:bg-amber-500 hover:text-slate-950 transition">
                                    <span class="flex size-7 items-center justify-center rounded-lg bg-amber-500/20 text-amber-500 font-black">+</span>
                                    <span>Tambah Promo Baru</span>
                                </a>
                                <a href="{{ route('admin.nobar.create') }}" class="flex min-h-[48px] items-center gap-3 rounded-xl p-3 font-bold text-text bg-surface-2 hover:bg-amber-500 hover:text-slate-950 transition">
                                    <span class="flex size-7 items-center justify-center rounded-lg bg-amber-500/20 text-amber-500 font-black">+</span>
                                    <span>Tambah Nobar Baru</span>
                                </a>
                                <a href="{{ route('admin.banners.create') }}" class="flex min-h-[48px] items-center gap-3 rounded-xl p-3 font-bold text-text bg-surface-2 hover:bg-amber-500 hover:text-slate-950 transition">
                                    <span class="flex size-7 items-center justify-center rounded-lg bg-amber-500/20 text-amber-500 font-black">+</span>
                                    <span>Tambah Slide Hero Baru</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
