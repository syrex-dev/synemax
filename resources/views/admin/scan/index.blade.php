<!DOCTYPE html>
<html lang="id" class="h-full antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Scan Tiket - SynemaX Admin</title>

    <script>
        (function() {
            try {
                const stored = localStorage.getItem('synemax_theme_mode');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (stored === 'dark' || (!stored && prefersDark)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="{{ asset('vendor/jsqr/jsQR.js') }}"></script>

    <style>
        @keyframes scanBeam {
            0%, 100% { top: 8%; opacity: 0.95; }
            50% { top: 92%; opacity: 0.95; }
        }
        .scan-laser-beam {
            animation: scanBeam 2.2s ease-in-out infinite;
        }
    </style>
</head>
<body
    x-data="ticketScannerApp({
        initialSessionId: @js($selectedSessionId),
        soundEnabledInitial: @js($soundEnabled),
        routes: {
            lookup: @js(route('admin.scan.lookup')),
            checkIn: @js(route('admin.scan.checkin')),
            undo: @js(route('admin.scan.undo')),
            sessionBookings: @js(route('admin.scan.session_bookings')),
            searchLive: @js(route('admin.bookings.live_search')),
        }
    })"
    x-init="initApp()"
    class="h-full flex flex-col bg-bg font-sans text-text select-none overscroll-none"
>

    {{-- ============================================================== --}}
    {{-- 1. HEADER UTAMA (Ringkas, Ramping, & Responsif)                --}}
    {{-- ============================================================== --}}
    <header class="shrink-0 h-14 border-b border-border bg-surface px-3 sm:px-6 flex items-center justify-between gap-3 z-20 pt-[env(safe-area-inset-top)]">
        <div class="flex items-center gap-2.5 min-w-0">
            <a
                href="{{ route('admin.dashboard') }}"
                class="inline-flex size-9 sm:size-10 items-center justify-center rounded-xl border border-border bg-surface text-text hover:bg-surface-2 transition shadow-xs"
                aria-label="Kembali ke Ringkasan Hari Ini"
                title="Kembali ke Dashboard Hari Ini"
            >
                <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </a>
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <h1 class="text-sm font-black text-text tracking-tight truncate">Scan tiket</h1>
                    <span class="inline-flex items-center gap-1 rounded-md bg-surface-2 px-1.5 py-0.5 text-[10px] font-bold text-text-muted border border-border">
                        <span class="size-1.5 rounded-full" :class="cameraStatus === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500'"></span>
                        <span class="hidden sm:inline">Pintu Studio</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            {{-- Tombol Bunyi Pemindaian --}}
            <button
                type="button"
                @click="toggleSound()"
                :class="soundEnabled ? 'border-amber-500/60 text-amber-600 dark:text-amber-400 bg-amber-500/10' : 'border-border text-text-muted hover:text-text bg-surface'"
                class="inline-flex size-9 sm:size-10 items-center justify-center rounded-xl border transition shadow-xs"
                :title="soundEnabled ? 'Bunyi aktif (klik untuk membisukan)' : 'Bunyi mati (klik untuk mengaktifkan)'"
                :aria-label="soundEnabled ? 'Bunyi aktif' : 'Bunyi mati'"
            >
                <template x-if="soundEnabled">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.757 3.63 8.25 4.51 8.25H6.75z" />
                    </svg>
                </template>
                <template x-if="!soundEnabled">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 9.75L19.5 12m0 0l2.25 2.25M19.5 12l2.25-2.25M19.5 12l-2.25 2.25m-10.5-7.5l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.757 3.63 8.25 4.51 8.25H6.75z" />
                    </svg>
                </template>
            </button>

            {{-- Tombol Ganti Tema --}}
            <button
                type="button"
                @click="toggleTheme()"
                class="inline-flex size-9 sm:size-10 items-center justify-center rounded-xl border border-border bg-surface text-text hover:bg-surface-2 transition shadow-xs"
                aria-label="Ganti mode terang/gelap"
                title="Ganti mode terang/gelap"
            >
                <svg x-show="!isDark" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                </svg>
                <svg x-show="isDark" x-cloak class="size-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                </svg>
            </button>
        </div>
    </header>

    {{-- ============================================================== --}}
    {{-- 2. KONTEKS SESI HARI INI (Pilihan Sesi & Metrik Kehadiran)       --}}
    {{-- ============================================================== --}}
    @php
        $sessionOptions = [
            'all' => 'Semua sesi hari ini',
        ];
        foreach ($todaySessions as $s) {
            $sessionOptions[(string) $s->id] = format_cinema_time($s->start_time) . ' · ' . ($s->film?->title ?? 'Belum ada film');
        }
    @endphp
    <section class="shrink-0 border-b border-border bg-surface-2/30 px-3 py-2 sm:px-6">
        <div class="max-w-4xl mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            {{-- Dropdown Konteks Sesi --}}
            <div class="flex items-center gap-2 min-w-0 flex-1" @change="if ($event.target.name === 'session_id_context') { selectedSessionId = $event.target.value; onSessionChange(); }">
                <label for="session_context_select" class="sr-only">Pilih Sesi</label>
                <div class="w-full sm:max-w-md">
                    <x-ui.select
                        name="session_id_context"
                        id="session_context_select"
                        :value="$selectedSessionId"
                        :options="$sessionOptions"
                        placeholder="Pilih Sesi..."
                        class="w-full"
                        buttonClass="min-h-[40px] text-xs font-semibold"
                    />
                </div>
            </div>

            {{-- Stat Progress Kehadiran & Badge Film --}}
            <div class="flex items-center justify-between sm:justify-end gap-3 text-xs shrink-0">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-text font-tabular tracking-tight" x-text="sessionStatsText">Memuat...</span>
                    {{-- Mini Progress Bar --}}
                    <div class="w-16 h-1.5 rounded-full bg-border overflow-hidden hidden sm:block">
                        <div class="h-full bg-emerald-500 transition-all duration-300" :style="`width: ${sessionProgressPct}%`"></div>
                    </div>
                </div>
                <span class="rounded-md bg-amber-500/15 px-2.5 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-400 border border-amber-500/20 truncate max-w-[140px]" x-text="currentSessionFilmBadge">Film</span>
            </div>
        </div>
    </section>

    {{-- ============================================================== --}}
    {{-- 3. SEGMENTED TAB CONTROL (Modern, Berpusat, Ramah Sentuhan)     --}}
    {{-- ============================================================== --}}
    <nav class="shrink-0 border-b border-border bg-surface py-2 px-3 sm:px-6" aria-label="Mode Pemindai">
        <div class="max-w-md mx-auto grid grid-cols-3 p-1 rounded-xl bg-surface-2 border border-border/80 gap-1 text-center">
            {{-- Tab 1: Kamera --}}
            <button
                type="button"
                data-tab="kamera"
                @click="switchTab('kamera')"
                :class="activeTab === 'kamera' ? 'bg-surface text-amber-600 dark:text-amber-400 font-bold shadow-xs border border-border/60' : 'text-text-muted hover:text-text'"
                class="min-h-[44px] flex items-center justify-center gap-1.5 py-2 px-3 text-xs font-semibold rounded-lg transition"
            >
                <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                </svg>
                <span>Kamera</span>
            </button>

            {{-- Tab 2: Kode Manual --}}
            <button
                type="button"
                data-tab="kode"
                @click="switchTab('kode')"
                :class="activeTab === 'kode' ? 'bg-surface text-amber-600 dark:text-amber-400 font-bold shadow-xs border border-border/60' : 'text-text-muted hover:text-text'"
                class="min-h-[44px] flex items-center justify-center gap-1.5 py-2 px-3 text-xs font-semibold rounded-lg transition"
            >
                <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 13.5h.75m0 0H18m-.75 0V15m0-1.5v.75m-3.75 3.75h.75m0 0H18m-.75 0V19.5m0-1.5v.75m-3.75-3.75h.75m0 0H15m0 3.75h.75" />
                </svg>
                <span>Kode</span>
            </button>

            {{-- Tab 3: Daftar Sesi --}}
            <button
                type="button"
                data-tab="daftar"
                @click="switchTab('daftar')"
                :class="activeTab === 'daftar' ? 'bg-surface text-amber-600 dark:text-amber-400 font-bold shadow-xs border border-border/60' : 'text-text-muted hover:text-text'"
                class="min-h-[44px] flex items-center justify-center gap-1.5 py-2 px-3 text-xs font-semibold rounded-lg transition"
            >
                <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
                <span>Daftar</span>
                <span x-show="sessionListBelumCount > 0" class="rounded-full bg-amber-500/20 px-1.5 py-0.2 text-[10px] font-bold text-amber-600 dark:text-amber-400" x-text="sessionListBelumCount"></span>
            </button>
        </div>
    </nav>

    {{-- ============================================================== --}}
    {{-- 4. KONTEN TAB UTAMA                                            --}}
    {{-- ============================================================== --}}
    <main class="flex-1 relative overflow-hidden bg-bg flex flex-col">

        {{-- ---------------------------------------------------------- --}}
        {{-- TAB 1: KAMERA                                              --}}
        {{-- ---------------------------------------------------------- --}}
        <div
            x-show="activeTab === 'kamera'"
            x-cloak
            class="flex-1 relative overflow-hidden flex flex-col bg-black justify-center items-center"
        >
            {{-- Wrapper Responsif: Pada layar besar berbentuk terminal konsol berbingkai rapi --}}
            <div class="relative w-full h-full md:max-w-xl md:max-h-[500px] md:my-auto md:rounded-3xl md:overflow-hidden md:border md:border-border/80 md:shadow-2xl flex flex-col justify-center items-center bg-black">

                {{-- A. State In-App: Protokol Tidak Aman (Safari/Ponsel Butuh HTTPS) --}}
                <template x-if="cameraStatus === 'unsupported_protocol'">
                    <div class="m-4 max-w-sm p-6 rounded-2xl border border-red-500/40 bg-surface text-center space-y-4 shadow-xl">
                        <div class="size-12 mx-auto rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center justify-center text-red-500">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="font-bold text-sm text-danger">Kamera membutuhkan koneksi HTTPS</h2>
                            <p class="text-xs text-text-muted mt-1 leading-relaxed">
                                Browser seluler (Safari / Chrome) membatasi akses kamera jika tidak memakai HTTPS atau localhost. Silakan gunakan tab Kode atau Daftar untuk input cepat.
                            </p>
                        </div>
                        <div class="flex flex-col gap-2 pt-1">
                            <button
                                type="button"
                                @click="switchTab('kode')"
                                class="w-full min-h-[44px] inline-flex items-center justify-center rounded-xl bg-amber-500 px-4 text-xs font-bold text-slate-950 hover:bg-amber-400 transition"
                            >
                                Gunakan Tab Kode
                            </button>
                            <button
                                type="button"
                                @click="switchTab('daftar')"
                                class="w-full min-h-[40px] inline-flex items-center justify-center rounded-xl border border-border bg-surface px-4 text-xs font-semibold text-text hover:bg-surface-2 transition"
                            >
                                Buka Tab Daftar Sesi
                            </button>
                        </div>
                    </div>
                </template>

                {{-- B. State In-App: Izin Ditolak --}}
                <template x-if="cameraStatus === 'denied'">
                    <div class="m-4 max-w-sm p-6 rounded-2xl border border-amber-500/40 bg-surface text-center space-y-4 shadow-xl">
                        <div class="size-12 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-500">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="font-bold text-sm text-warning">Kamera tidak diizinkan</h2>
                            <p class="text-xs text-text-muted mt-1 leading-relaxed">
                                Akses kamera dinonaktifkan di izin browser. Buka pengaturan situs peramban Anda untuk mengizinkan kamera, atau beralih ke tab Kode.
                            </p>
                        </div>
                        <div class="flex justify-center gap-2 pt-1">
                            <button
                                type="button"
                                @click="startCamera()"
                                class="min-h-[44px] flex-1 rounded-xl border border-border bg-surface px-4 text-xs font-semibold text-text hover:bg-surface-2 transition"
                            >
                                Coba Lagi
                            </button>
                            <button
                                type="button"
                                @click="switchTab('kode')"
                                class="min-h-[44px] flex-1 rounded-xl bg-amber-500 px-4 text-xs font-bold text-slate-950 hover:bg-amber-400 transition"
                            >
                                Buka Tab Kode
                            </button>
                        </div>
                    </div>
                </template>

                {{-- C. State In-App: Kamera Tidak Ditemukan --}}
                <template x-if="cameraStatus === 'not_found'">
                    <div class="m-4 max-w-sm p-6 rounded-2xl border border-red-500/40 bg-surface text-center space-y-4 shadow-xl">
                        <div class="size-12 mx-auto rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center justify-center text-red-500">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="font-bold text-sm text-danger">Kamera tidak ditemukan</h2>
                            <p class="text-xs text-text-muted mt-1 leading-relaxed">
                                Perangkat ini tidak memiliki modul kamera video yang terdeteksi atau dibatasi oleh browser. Silakan gunakan tab Kode atau Daftar.
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="switchTab('kode')"
                            class="w-full min-h-[44px] inline-flex items-center justify-center rounded-xl bg-amber-500 px-4 text-xs font-bold text-slate-950 hover:bg-amber-400 transition"
                        >
                            Gunakan Tab Kode
                        </button>
                    </div>
                </template>

                {{-- D. State In-App: Kamera Sedang Dipakai Aplikasi Lain / Error Lain --}}
                <template x-if="cameraStatus === 'error'">
                    <div class="m-4 max-w-sm p-6 rounded-2xl border border-red-500/40 bg-surface text-center space-y-4 shadow-xl">
                        <div class="size-12 mx-auto rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center justify-center text-red-500">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="font-bold text-sm text-danger">Kamera tidak dapat dibuka</h2>
                            <p class="text-xs text-text-muted mt-1 leading-relaxed" x-text="cameraErrorMessage || 'Kamera sedang dipakai aplikasi lain atau sistem mengalami kendala.'"></p>
                        </div>
                        <div class="flex justify-center gap-2 pt-1">
                            <button
                                type="button"
                                @click="startCamera()"
                                class="min-h-[44px] flex-1 rounded-xl border border-border bg-surface px-4 text-xs font-semibold text-text hover:bg-surface-2 transition"
                            >
                                Muat Ulang Kamera
                            </button>
                            <button
                                type="button"
                                @click="switchTab('kode')"
                                class="min-h-[44px] flex-1 rounded-xl bg-amber-500 px-4 text-xs font-bold text-slate-950 hover:bg-amber-400 transition"
                            >
                                Buka Tab Kode
                            </button>
                        </div>
                    </div>
                </template>

                {{-- E. State In-App: Idle / Menunggu Izin Pengguna --}}
                <template x-if="cameraStatus === 'idle'">
                    <div class="m-auto max-w-sm p-6 sm:p-8 rounded-2xl border border-border bg-surface text-center space-y-4 shadow-2xl">
                        <div class="size-16 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-500">
                            <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-text">Izinkan kamera untuk memindai tiket</h2>
                            <p class="text-xs text-text-muted mt-1 leading-relaxed">
                                Arahkan kamera ke QR Code e-tiket penonton untuk validasi kehadiran langsung di pintu masuk studio.
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="requestCameraAccess()"
                            class="w-full min-h-[48px] inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-5 text-sm font-bold text-slate-950 hover:bg-amber-400 transition shadow-sm"
                        >
                            <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5z" />
                            </svg>
                            <span>Aktifkan kamera</span>
                        </button>
                    </div>
                </template>

                {{-- F. Viewfinder Kamera Aktif & Frame Pemandu --}}
                <div x-show="cameraStatus === 'active'" class="relative w-full h-full flex items-center justify-center overflow-hidden">
                    <video
                        x-ref="videoElement"
                        playsinline
                        muted
                        autoplay
                        class="absolute inset-0 w-full h-full object-cover"
                    ></video>
                    <canvas x-ref="canvasElement" class="hidden"></canvas>

                    {{-- Kotak Bingkai Pemandu QR (Reticle) --}}
                    <div class="relative size-64 sm:size-72 border-2 border-amber-400/80 rounded-2xl pointer-events-none z-10 flex items-center justify-center overflow-hidden shadow-none">
                        {{-- 4 Sudut Tegas (Precision Reticle) --}}
                        <span class="absolute -top-1 -left-1 size-6 border-t-4 border-l-4 border-amber-400 rounded-tl-xl"></span>
                        <span class="absolute -top-1 -right-1 size-6 border-t-4 border-r-4 border-amber-400 rounded-tr-xl"></span>
                        <span class="absolute -bottom-1 -left-1 size-6 border-b-4 border-l-4 border-amber-400 rounded-bl-xl"></span>
                        <span class="absolute -bottom-1 -right-1 size-6 border-b-4 border-r-4 border-amber-400 rounded-br-xl"></span>

                        {{-- Garis Laser Pemindai (Scan Line Animation) --}}
                        <div class="absolute inset-x-3 h-0.5 bg-amber-400/90 scan-laser-beam pointer-events-none"></div>

                        {{-- Label Pemandu --}}
                        <span class="text-[11px] font-bold text-white bg-black/70 px-3 py-1 rounded-full backdrop-blur-sm border border-white/10">
                            Arahkan QR ke kotak ini
                        </span>
                    </div>

                    {{-- Toolbar Kontrol Kamera Mengambang Atas --}}
                    <div class="absolute top-3 inset-x-3 z-10 flex items-center justify-between gap-2">
                        {{-- Dropdown Pilih Lensa Kamera --}}
                        <div x-show="videoDevices.length > 1" class="relative max-w-[170px]" @click.outside="cameraMenuOpen = false">
                            <button
                                type="button"
                                @click="cameraMenuOpen = !cameraMenuOpen"
                                class="min-h-[36px] w-full inline-flex items-center justify-between gap-1.5 rounded-xl border border-white/20 bg-black/70 px-2.5 text-[11px] font-semibold text-white backdrop-blur focus:outline-none"
                            >
                                <span class="truncate" x-text="videoDevices.find(d => d.deviceId === selectedCameraId)?.label || 'Pilih Kamera'"></span>
                                <svg class="size-3 shrink-0 text-white/70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div
                                x-show="cameraMenuOpen"
                                x-cloak
                                class="absolute left-0 top-full mt-1 w-44 rounded-xl border border-white/20 bg-black/90 p-1 shadow-xl backdrop-blur z-20 space-y-1"
                            >
                                <template x-for="device in videoDevices" :key="device.deviceId">
                                    <button
                                        type="button"
                                        @click="selectedCameraId = device.deviceId; changeCameraDevice(); cameraMenuOpen = false"
                                        class="w-full text-left px-2.5 py-1.5 rounded-lg text-[11px] text-white hover:bg-white/20 truncate"
                                        :class="selectedCameraId === device.deviceId ? 'bg-amber-500/30 font-bold text-amber-300' : ''"
                                        x-text="device.label || `Kamera ${device.deviceId.slice(0,4)}`"
                                    ></button>
                                </template>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 ml-auto">
                            {{-- Tombol Balik Kamera (Depan / Belakang) --}}
                            <button
                                x-show="videoDevices.length > 1"
                                type="button"
                                @click="flipCameraFacing()"
                                class="inline-flex size-9 sm:size-10 items-center justify-center rounded-xl border border-white/20 bg-black/70 text-white backdrop-blur transition"
                                title="Balik kamera (Depan/Belakang)"
                                aria-label="Balik kamera"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                            </button>

                            {{-- Tombol Senter (Torch) --}}
                            <button
                                x-show="hasTorch"
                                type="button"
                                @click="toggleTorch()"
                                :class="isTorchOn ? 'bg-amber-400 text-slate-950 font-bold' : 'bg-black/70 text-white'"
                                class="inline-flex size-9 sm:size-10 items-center justify-center rounded-xl border border-white/20 backdrop-blur transition"
                                title="Senter"
                                aria-label="Senter"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.516 0c.85.493 1.508 1.333 1.508 2.316V18" />
                                </svg>
                            </button>

                            {{-- Tombol Jeda / Lanjut Kamera --}}
                            <button
                                type="button"
                                @click="toggleCameraPause()"
                                :class="isCameraPaused ? 'bg-amber-500 text-slate-950 font-bold' : 'bg-black/70 text-white'"
                                class="inline-flex size-9 sm:size-10 items-center justify-center rounded-xl border border-white/20 backdrop-blur transition"
                                :title="isCameraPaused ? 'Lanjutkan pemindaian' : 'Jeda kamera'"
                                :aria-label="isCameraPaused ? 'Lanjutkan pemindaian' : 'Jeda kamera'"
                            >
                                <template x-if="!isCameraPaused">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                                    </svg>
                                </template>
                                <template x-if="isCameraPaused">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                                    </svg>
                                </template>
                            </button>
                        </div>
                    </div>

                    {{-- Chip Status Pemindaian Bawah --}}
                    <div class="absolute bottom-3 inset-x-3 z-10 flex justify-center pointer-events-none">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-black/75 border border-white/10 px-3 py-1 text-[11px] font-semibold text-white/90 backdrop-blur-sm">
                            <span class="size-2 rounded-full bg-amber-400 animate-ping"></span>
                            <span>Mencari QR Code tiket...</span>
                        </span>
                    </div>

                    {{-- Banner Peringatan Pemindaian Tidak Valid --}}
                    <div
                        x-show="clientAlertMessage"
                        x-cloak
                        class="absolute bottom-12 inset-x-4 z-20 p-3 rounded-xl border border-red-500/50 bg-black/85 text-center text-xs font-semibold text-red-300 backdrop-blur shadow-xl"
                    >
                        <span x-text="clientAlertMessage"></span>
                    </div>
                </div>

            </div>
        </div>

        {{-- ---------------------------------------------------------- --}}
        {{-- TAB 2: KODE MANUAL & PENCARIAN LIVE                        --}}
        {{-- ---------------------------------------------------------- --}}
        <div
            x-show="activeTab === 'kode'"
            x-cloak
            class="flex-1 overflow-y-auto p-4 sm:p-6 bg-bg"
        >
            <div class="max-w-xl mx-auto space-y-4">

                {{-- Card 1: Input Kode Booking (56px Tactile) --}}
                <div class="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <label for="manual_code_input" class="block text-xs font-bold uppercase tracking-wider text-text">
                            Masukkan Kode Tiket atau 4-6 Karakter Akhir
                        </label>
                        <span class="text-[11px] text-text-muted font-mono">Kode Suffix / URL</span>
                    </div>

                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <input
                                id="manual_code_input"
                                type="text"
                                x-model="manualCodeInput"
                                @keydown.enter.prevent="submitManualCode()"
                                placeholder="HC-261103-XXXXXX atau XXXXXX"
                                inputmode="text"
                                autocapitalize="characters"
                                autocomplete="off"
                                spellcheck="false"
                                class="h-14 w-full rounded-xl border border-border bg-surface-2 px-4 font-mono text-base sm:text-lg font-bold tracking-wider text-text uppercase placeholder:normal-case placeholder:font-sans placeholder:text-xs placeholder:font-normal placeholder:text-text-muted focus:border-amber-400 focus:outline-none pr-10"
                            >
                            {{-- Tombol Bersihkan Input (X) --}}
                            <button
                                x-show="manualCodeInput.length > 0"
                                type="button"
                                @click="manualCodeInput = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 size-7 rounded-lg inline-flex items-center justify-center text-text-muted hover:text-text hover:bg-surface transition"
                                title="Hapus teks"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        {{-- Tombol Aksi Cari --}}
                        <button
                            type="button"
                            @click="submitManualCode()"
                            :disabled="isSearching || !manualCodeInput.trim()"
                            class="h-14 px-6 rounded-xl bg-amber-500 text-sm font-bold text-slate-950 hover:bg-amber-400 disabled:opacity-40 transition shadow-sm shrink-0 flex items-center justify-center gap-2"
                        >
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                            <span>Cari</span>
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-text-muted pt-1">
                        <span>Bisa memasukkan kode lengkap atau cukup 4-6 digit terakhir kode booking.</span>
                        {{-- Tombol Tempel Clipboard --}}
                        <button
                            type="button"
                            @click="pasteFromClipboard()"
                            class="inline-flex items-center gap-1 font-semibold text-amber-600 dark:text-amber-400 hover:underline"
                        >
                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                            </svg>
                            <span>Tempel</span>
                        </button>
                    </div>
                </div>

                {{-- Hasil Ambigu / Multi-Pilihan Akhiran Suffix --}}
                <template x-if="ambiguousMatches.length > 0">
                    <div class="rounded-2xl border border-amber-500/40 bg-surface p-4 sm:p-5 space-y-3 shadow-xs">
                        <div class="flex items-center gap-2">
                            <span class="size-2 rounded-full bg-amber-500"></span>
                            <p class="text-xs font-bold text-amber-600 dark:text-amber-400">
                                Ditemukan beberapa tiket dengan akhiran tersebut. Pilih untuk verifikasi:
                            </p>
                        </div>
                        <div class="divide-y divide-border rounded-xl border border-border overflow-hidden">
                            <template x-for="item in ambiguousMatches" :key="item.id">
                                <button
                                    type="button"
                                    @click="verifyBookingById(item.id, 'kode')"
                                    class="w-full py-3 px-3.5 flex items-center justify-between text-left hover:bg-surface-2 transition"
                                >
                                    <div>
                                        <p class="text-xs font-bold text-text" x-text="item.customer_name"></p>
                                        <p class="font-mono text-xs text-amber-500 font-bold" x-text="item.code"></p>
                                    </div>
                                    <div class="text-right text-xs text-text-muted">
                                        <span class="font-bold text-text" x-text="`${item.people_count} orang`"></span> · <span x-text="item.start_time"></span>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Card 2: Cari Nama Pemesan atau WhatsApp (Live Search) --}}
                <div class="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-xs space-y-3">
                    <label for="live_search_input" class="block text-xs font-bold uppercase tracking-wider text-text">
                        Cari Nama Pemesan atau WhatsApp
                    </label>
                    <div class="relative">
                        <input
                            id="live_search_input"
                            type="text"
                            x-model="liveSearchInput"
                            @input="onLiveSearchInput()"
                            placeholder="Ketik nama penonton atau nomor telepon..."
                            class="h-12 w-full rounded-xl border border-border bg-surface-2 pl-10 pr-4 text-xs font-semibold text-text placeholder:text-text-muted focus:border-amber-400 focus:outline-none"
                        >
                        <svg class="size-4 text-text-muted absolute left-3.5 top-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </div>

                    {{-- Hasil Pencarian Live Search --}}
                    <div x-show="liveSearchResults.length > 0" class="divide-y divide-border rounded-xl border border-border overflow-hidden pt-1">
                        <template x-for="res in liveSearchResults" :key="res.id">
                            <button
                                type="button"
                                @click="verifyBookingById(res.id, 'kode')"
                                class="w-full py-2.5 px-3 flex items-center justify-between text-left hover:bg-surface-2 transition"
                            >
                                <div>
                                    <p class="text-xs font-bold text-text" x-text="res.customer_name"></p>
                                    <p class="text-[11px] text-text-muted font-mono" x-text="res.code"></p>
                                </div>
                                <span class="rounded bg-amber-500/10 px-2.5 py-1 text-[10px] font-bold text-amber-600 dark:text-amber-400 border border-amber-500/20">Verifikasi</span>
                            </button>
                        </template>
                    </div>
                </div>

            </div>
        </div>

        {{-- ---------------------------------------------------------- --}}
        {{-- TAB 3: DAFTAR SESI                                         --}}
        {{-- ---------------------------------------------------------- --}}
        <div
            x-show="activeTab === 'daftar'"
            x-cloak
            class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4 bg-bg"
        >
            <div class="max-w-2xl mx-auto space-y-3">

                {{-- Header Filter & Ringkasan Kehadiran --}}
                <div class="rounded-2xl border border-border bg-surface p-4 sm:p-5 shadow-xs space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <p class="text-xs font-bold text-text" x-text="sessionListSummaryText">Memuat ringkasan...</p>
                            <p class="text-[10px] text-text-muted mt-0.5">Pembaruan otomatis tiap 20 detik saat tab aktif.</p>
                        </div>
                        <button
                            type="button"
                            @click="loadSessionBookings(true)"
                            class="inline-flex size-9 items-center justify-center rounded-xl border border-border bg-surface text-text hover:bg-surface-2 transition shadow-xs"
                            title="Segarkan daftar"
                            :disabled="isLoadingDaftar"
                        >
                            <svg class="size-4" :class="isLoadingDaftar ? 'animate-spin text-amber-500' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                        </button>
                    </div>

                    {{-- Filter Tabs (Semua, Belum Hadir, Hadir) & Filter Input Cepat --}}
                    <div class="pt-2 border-t border-border flex flex-col sm:flex-row gap-2.5 items-stretch sm:items-center justify-between">
                        <div class="inline-flex p-1 rounded-xl bg-surface-2 border border-border text-xs font-semibold gap-1">
                            <button
                                type="button"
                                @click="daftarFilter = 'all'"
                                :class="daftarFilter === 'all' ? 'bg-surface text-text font-bold shadow-xs' : 'text-text-muted hover:text-text'"
                                class="px-2.5 py-1 rounded-lg transition text-[11px]"
                            >
                                Semua (<span x-text="sessionListItems.length"></span>)
                            </button>
                            <button
                                type="button"
                                @click="daftarFilter = 'belum'"
                                :class="daftarFilter === 'belum' ? 'bg-surface text-amber-600 dark:text-amber-400 font-bold shadow-xs' : 'text-text-muted hover:text-text'"
                                class="px-2.5 py-1 rounded-lg transition text-[11px]"
                            >
                                Belum (<span x-text="sessionListBelumCount"></span>)
                            </button>
                            <button
                                type="button"
                                @click="daftarFilter = 'hadir'"
                                :class="daftarFilter === 'hadir' ? 'bg-surface text-emerald-600 dark:text-emerald-400 font-bold shadow-xs' : 'text-text-muted hover:text-text'"
                                class="px-2.5 py-1 rounded-lg transition text-[11px]"
                            >
                                Hadir (<span x-text="sessionListHadirCount"></span>)
                            </button>
                        </div>

                        {{-- Filter Pencarian Instan di Daftar --}}
                        <div class="relative flex-1 sm:max-w-xs">
                            <input
                                type="text"
                                x-model="daftarSearch"
                                placeholder="Filter nama / kode..."
                                class="h-9 w-full rounded-xl border border-border bg-surface-2 pl-8 pr-3 text-xs text-text placeholder:text-text-muted focus:border-amber-400 focus:outline-none"
                            >
                            <svg class="size-3.5 text-text-muted absolute left-2.5 top-3 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Daftar Kartu Booking Penonton --}}
                <div class="rounded-2xl border border-border bg-surface divide-y divide-border overflow-hidden shadow-xs">
                    <template x-if="filteredSessionListItems.length === 0">
                        <div class="p-8 text-center space-y-2">
                            <svg class="size-8 mx-auto text-text-muted/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                            </svg>
                            <p class="text-xs text-text-muted">Tidak ada booking yang cocok dengan filter yang dipilih.</p>
                        </div>
                    </template>
                    <template x-for="b in filteredSessionListItems" :key="b.id">
                        <div
                            @click="verifyBookingById(b.id, 'daftar')"
                            class="p-3.5 sm:p-4 flex items-center justify-between gap-3 hover:bg-surface-2 transition cursor-pointer"
                            :class="b.is_hadir ? 'bg-surface-2/20' : ''"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="text-xs sm:text-sm font-bold text-text truncate" x-text="b.customer_name"></p>
                                    <span
                                        class="rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider shrink-0"
                                        :class="b.chip.token === 'success' ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : (b.chip.token === 'warning' ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/20' : 'bg-red-500/15 text-red-600 dark:text-red-400 border border-red-500/20')"
                                        x-text="b.chip.label"
                                    ></span>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-2 text-[11px] text-text-muted mt-1">
                                    <span class="font-mono text-text-muted font-semibold">
                                        <span x-text="b.code_prefix"></span><strong class="text-text font-bold" x-text="b.code_suffix"></strong>
                                    </span>
                                    <span>·</span>
                                    <span class="font-bold text-text" x-text="`${b.people_count} orang`"></span>
                                    <span>·</span>
                                    <span x-text="b.session_time"></span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <template x-if="!b.is_hadir">
                                    <span class="hidden sm:inline-flex rounded-lg bg-emerald-600 text-white text-[11px] font-bold px-2.5 py-1 shadow-xs">Check-In</span>
                                </template>
                                <svg class="size-4 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </div>
                        </div>
                    </template>
                </div>

            </div>
        </div>

    </main>

    {{-- ============================================================== --}}
    {{-- 5. BOTTOM SHEET / MODAL PRATINJAU TIKET (In-App Verdict Panel)  --}}
    {{-- ============================================================== --}}
    {{-- Backdrop --}}
    <div
        x-show="sheetOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-40 bg-black/60 backdrop-blur-xs"
        @click="closeSheet()"
    ></div>

    {{-- Panel Pratinjau --}}
    <div
        x-show="sheetOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-y-full sm:translate-y-4 sm:opacity-0"
        x-transition:enter-end="translate-y-0 sm:translate-y-0 sm:opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-y-0 sm:translate-y-0 sm:opacity-100"
        x-transition:leave-end="translate-y-full sm:translate-y-4 sm:opacity-0"
        class="fixed inset-x-0 bottom-0 sm:bottom-8 sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2 z-50 w-full sm:max-w-lg max-h-[90vh] overflow-y-auto rounded-t-3xl sm:rounded-3xl border-t sm:border border-border bg-surface p-5 sm:p-6 shadow-2xl space-y-4 pb-[max(1.25rem,env(safe-area-inset-bottom))]"
    >
        {{-- Strip Penutup Atas --}}
        <div class="flex items-center justify-between pb-3 border-b border-border">
            <div class="flex items-center gap-2">
                <span
                    class="rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wider"
                    :class="verdictToken === 'success' ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/40' : (verdictToken === 'warning' ? 'bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/40' : 'bg-red-500/20 text-red-700 dark:text-red-300 border border-red-500/40')"
                    x-text="currentPreview?.verdict_badge?.label || 'Hasil Validasi'"
                ></span>
                <span class="font-mono text-xs font-bold text-text-muted" x-text="currentPreview?.code"></span>
            </div>
            <button
                type="button"
                @click="closeSheet()"
                class="size-8 inline-flex items-center justify-center rounded-xl border border-border text-text hover:bg-surface-2 transition"
                aria-label="Tutup panel"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Pesan Status / Verdict Banner --}}
        <div
            class="p-3.5 rounded-xl border text-xs font-bold"
            :class="verdictToken === 'success' ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300' : (verdictToken === 'warning' ? 'border-amber-500/40 bg-amber-500/10 text-amber-800 dark:text-amber-300' : 'border-red-500/40 bg-red-500/10 text-red-800 dark:text-red-300')"
        >
            <p x-text="currentPreview?.message"></p>
            <template x-if="currentPreview?.checked_in_info">
                <p class="mt-1 font-semibold text-[11px]" x-text="currentPreview.checked_in_info"></p>
            </template>
        </div>

        {{-- Rincian Tiket Pratinjau (Boarding Pass Cinema Style) --}}
        <template x-if="currentPreview && currentPreview.customer_name">
            <div class="rounded-2xl border border-border bg-surface-2/40 p-4 space-y-3 text-xs">
                <div class="flex gap-3">
                    <template x-if="currentPreview.film_poster_url">
                        <img :src="currentPreview.film_poster_url" alt="Poster Film" class="size-16 rounded-xl object-cover shrink-0 border border-border">
                    </template>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-text" x-text="currentPreview.customer_name"></p>
                        <p class="font-bold text-amber-600 dark:text-amber-400 mt-0.5 truncate" x-text="currentPreview.film_title"></p>
                        <p class="text-[11px] text-text-muted mt-0.5">
                            <span x-text="currentPreview.session_date_formatted"></span> · <span x-text="currentPreview.session_time_formatted"></span>
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-border text-[11px]">
                    <div>
                        <span class="text-text-muted block">Paket & Penonton:</span>
                        <strong class="text-text font-bold" x-text="currentPreview.package_display"></strong>
                    </div>
                    <div>
                        <span class="text-text-muted block">Status Bayar:</span>
                        <strong class="text-text font-bold" x-text="currentPreview.payment_status_label"></strong>
                    </div>
                </div>

                {{-- WhatsApp Tersamar Sebagian --}}
                <div class="pt-2 border-t border-border flex items-center justify-between text-[11px]">
                    <div>
                        <span class="text-text-muted block">WhatsApp:</span>
                        <strong class="text-text font-mono" x-text="showFullPhone ? currentPreview.whatsapp_raw : currentPreview.whatsapp_masked"></strong>
                    </div>
                    <button
                        type="button"
                        @click="showFullPhone = !showFullPhone"
                        class="px-2.5 py-1 rounded-lg border border-border bg-surface text-[10px] font-semibold text-text hover:bg-surface-2 transition"
                        x-text="showFullPhone ? 'Sembunyikan' : 'Lihat nomor'"
                    ></button>
                </div>

                {{-- Jajan Ringkas & Catatan --}}
                <template x-if="currentPreview.snacks_summary && currentPreview.snacks_summary !== 'Tidak ada jajan'">
                    <div class="pt-2 border-t border-border text-[11px]">
                        <span class="text-text-muted block">Pesanan Jajan:</span>
                        <p class="font-semibold text-text" x-text="currentPreview.snacks_summary"></p>
                    </div>
                </template>

                <template x-if="currentPreview.notes">
                    <div class="pt-2 border-t border-border text-[11px] bg-amber-500/10 p-2.5 rounded-xl border border-amber-500/20">
                        <span class="font-bold text-amber-700 dark:text-amber-300 block">Catatan Pemesan:</span>
                        <p class="text-text" x-text="currentPreview.notes"></p>
                    </div>
                </template>
            </div>
        </template>

        {{-- Tombol Aksi Sesuai Verdict --}}
        <div class="space-y-2 pt-1">
            {{-- Tombol 56px Primer untuk Siap Hadir --}}
            <template x-if="currentPreview?.can_check_in">
                <button
                    type="button"
                    @click="executeCheckIn(false)"
                    :disabled="isSubmitting"
                    class="w-full h-14 rounded-xl bg-emerald-600 text-base font-black text-white hover:bg-emerald-500 active:scale-[0.98] transition shadow-md flex items-center justify-center gap-2"
                >
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    <span>Tandai hadir sekarang</span>
                </button>
            </template>

            {{-- Tombol Override untuk Di Luar Jendela atau Sesi Lain --}}
            <template x-if="currentPreview?.can_override && !currentPreview?.can_check_in">
                <button
                    type="button"
                    @click="overrideModalOpen = true"
                    class="w-full h-14 rounded-xl bg-amber-500 text-sm font-bold text-slate-950 hover:bg-amber-400 active:scale-[0.98] transition shadow-sm flex items-center justify-center gap-2"
                >
                    <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <span>Tandai hadir tetap (Override)</span>
                </button>
            </template>

            {{-- Tautan Buka Detail Booking jika Belum Bayar atau Diproses --}}
            <template x-if="currentPreview?.detail_url && (currentPreview?.verdict === 'belum_bayar' || currentPreview?.verdict === 'diproses')">
                <a
                    :href="currentPreview.detail_url"
                    class="w-full min-h-[44px] rounded-xl border border-border bg-surface text-xs font-bold text-text hover:bg-surface-2 transition flex items-center justify-center gap-2"
                >
                    <span>Buka detail booking di admin</span>
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            </template>

            {{-- Tombol Tutup --}}
            <button
                type="button"
                @click="closeSheet()"
                class="w-full min-h-[44px] rounded-xl border border-border bg-surface text-xs font-semibold text-text-muted hover:text-text hover:bg-surface-2 transition"
            >
                Tutup
            </button>
        </div>
    </div>

    {{-- ============================================================== --}}
    {{-- 6. MODAL OVERRIDE IN-APP (Di Luar Jendela / Sesi Lain)           --}}
    {{-- ============================================================== --}}
    <div
        x-show="overrideModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
        @keydown.escape.window="overrideModalOpen = false"
    >
        <div class="w-full max-w-md rounded-2xl border border-border bg-surface p-5 shadow-2xl space-y-4" @click.outside="overrideModalOpen = false">
            <div class="flex items-center justify-between border-b border-border pb-3">
                <h3 class="font-bold text-sm text-text">Konfirmasi Hadir Luar Jadwal</h3>
                <button type="button" @click="overrideModalOpen = false" class="size-8 inline-flex items-center justify-center rounded-lg text-text-muted hover:bg-surface-2">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p class="text-xs text-text-muted leading-relaxed" x-text="currentPreview?.message"></p>
            <p class="text-xs text-amber-600 dark:text-amber-400 font-semibold">
                Tiket ini berada di luar batas waktu resmi. Jika Anda melanjutkan, kehadiran akan dicatat dengan status override dalam jejak audit.
            </p>
            <div>
                <label for="override_reason" class="block text-xs font-semibold text-text mb-1">Alasan override (opsional):</label>
                <input
                    id="override_reason"
                    type="text"
                    x-model="overrideReason"
                    maxlength="120"
                    placeholder="Contoh: Tamu datang lebih awal atas izin pengelola"
                    class="w-full min-h-[40px] rounded-xl border border-border bg-surface-2 px-3 text-xs text-text focus:outline-none focus:ring-2 focus:ring-amber-500"
                >
            </div>
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-border">
                <button type="button" @click="overrideModalOpen = false" class="min-h-[40px] px-4 rounded-xl border border-border bg-surface text-xs font-semibold text-text hover:bg-surface-2">Batal</button>
                <button type="button" @click="executeCheckIn(true)" class="min-h-[40px] px-4 rounded-xl bg-amber-500 text-xs font-bold text-slate-950 hover:bg-amber-400">Ya, Tandai Hadir Tetap</button>
            </div>
        </div>
    </div>

    {{-- ============================================================== --}}
    {{-- 7. SCRIPT LOGIKA PEMINDAI & ALUR                               --}}
    {{-- ============================================================== --}}
    <script>
        function ticketScannerApp(config) {
            return {
                activeTab: 'kamera',
                selectedSessionId: config.initialSessionId || 'all',
                soundEnabled: !!config.soundEnabledInitial,
                isDark: document.documentElement.classList.contains('dark'),

                // Camera state
                cameraStatus: 'idle', // idle, active, denied, not_found, unsupported_protocol, error
                cameraErrorMessage: '',
                videoDevices: [],
                selectedCameraId: '',
                cameraFacingMode: 'environment', // environment, user
                cameraMenuOpen: false,
                hasTorch: false,
                isTorchOn: false,
                isCameraPaused: false,
                mediaStream: null,
                scanIntervalId: null,
                lastScannedCode: '',
                lastScannedTimestamp: 0,
                clientAlertMessage: '',
                clientAlertTimeout: null,

                // Manual code & live search
                manualCodeInput: '',
                liveSearchInput: '',
                liveSearchTimer: null,
                liveSearchResults: [],
                ambiguousMatches: [],
                isSearching: false,

                // Session list state
                sessionListItems: [],
                daftarFilter: 'all', // all, belum, hadir
                daftarSearch: '',
                isLoadingDaftar: false,
                sessionListSummaryText: 'Memuat...',
                sessionStatsText: 'Hadir 0 dari 0 orang',
                sessionProgressPct: 0,
                currentSessionFilmBadge: 'Film',
                sessionPollingIntervalId: null,

                // Sheet & Preview state
                sheetOpen: false,
                verdictToken: 'neutral',
                currentPreview: null,
                showFullPhone: false,
                isSubmitting: false,
                autoCloseTimeout: null,
                overrideModalOpen: false,
                overrideReason: '',

                initApp() {
                    this.updateSessionStatsHeader();
                    this.loadSessionBookings(false);

                    // Check protocol security (MediaDevices requires secure context, localhost, or .test local domains)
                    if (!window.isSecureContext && location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1' && !location.hostname.endsWith('.test')) {
                        this.cameraStatus = 'unsupported_protocol';
                        this.activeTab = 'kode';
                    }

                    // Visibility change listener to pause/resume scan & polling
                    document.addEventListener('visibilitychange', () => {
                        if (document.hidden) {
                            this.pauseScanningLoop();
                        } else {
                            if (this.cameraStatus === 'active' && !this.isCameraPaused) {
                                this.startScanningLoop();
                            }
                            if (this.activeTab === 'daftar') {
                                this.loadSessionBookings(false);
                            }
                        }
                    });

                    // Cleanup camera tracks on page hide
                    window.addEventListener('pagehide', () => this.stopCamera());

                    // Start 20s polling for session bookings list
                    this.sessionPollingIntervalId = setInterval(() => {
                        if (!document.hidden && this.activeTab === 'daftar') {
                            this.loadSessionBookings(false);
                        }
                    }, 20000 + Math.floor(Math.random() * 2000));
                },

                toggleTheme() {
                    this.isDark = !this.isDark;
                    if (this.isDark) {
                        document.documentElement.classList.add('dark');
                        localStorage.setItem('synemax_theme_mode', 'dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                        localStorage.setItem('synemax_theme_mode', 'light');
                    }
                },

                toggleSound() {
                    this.soundEnabled = !this.soundEnabled;
                },

                playBeep() {
                    if (!this.soundEnabled) return;
                    try {
                        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                        const osc = audioCtx.createOscillator();
                        const gain = audioCtx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(880, audioCtx.currentTime);
                        osc.frequency.exponentialRampToValueAtTime(1760, audioCtx.currentTime + 0.12);
                        gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
                        gain.gain.linearRampToValueAtTime(0, audioCtx.currentTime + 0.12);
                        osc.connect(gain);
                        gain.connect(audioCtx.destination);
                        osc.start();
                        osc.stop(audioCtx.currentTime + 0.12);
                    } catch (e) {}
                },

                vibrateDevice() {
                    if (navigator.vibrate) {
                        try { navigator.vibrate([40]); } catch (e) {}
                    }
                },

                switchTab(tab) {
                    this.activeTab = tab;
                    if (tab === 'kamera') {
                        if (this.cameraStatus === 'active') {
                            this.startScanningLoop();
                        }
                    } else {
                        this.pauseScanningLoop();
                        if (tab === 'daftar') {
                            this.loadSessionBookings(true);
                        }
                    }
                },

                onSessionChange() {
                    this.updateSessionStatsHeader();
                    this.loadSessionBookings(true);
                },

                // ---------------- CAMERA LOGIC ----------------
                async requestCameraAccess() {
                    await this.startCamera();
                },

                async startCamera() {
                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                        this.cameraStatus = 'not_found';
                        this.activeTab = 'kode';
                        return;
                    }

                    this.stopCamera();

                    const constraints = {
                        audio: false,
                        video: {
                            facingMode: { ideal: this.cameraFacingMode },
                            width: { ideal: 1280 },
                            height: { ideal: 720 },
                            deviceId: this.selectedCameraId ? { exact: this.selectedCameraId } : undefined,
                        }
                    };

                    try {
                        let stream = null;
                        try {
                            stream = await navigator.mediaDevices.getUserMedia(constraints);
                        } catch (primaryErr) {
                            // Fallback jika constraint ideal/environment gagal di browser desktop/laptop
                            console.warn('Primary constraint failed, falling back to video: true', primaryErr);
                            stream = await navigator.mediaDevices.getUserMedia({ audio: false, video: true });
                        }

                        this.mediaStream = stream;
                        this.cameraStatus = 'active';

                        const video = this.$refs.videoElement;
                        if (video) {
                            video.srcObject = stream;
                            await video.play();
                        }

                        // Inspect track capabilities (torch, device list)
                        const track = stream.getVideoTracks()[0];
                        if (track) {
                            const capabilities = track.getCapabilities ? track.getCapabilities() : {};
                            this.hasTorch = !!capabilities.torch;
                        }

                        await this.loadVideoDevices();
                        this.startScanningLoop();
                    } catch (err) {
                        console.warn('Camera access error:', err);
                        if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                            this.cameraStatus = 'denied';
                            this.activeTab = 'kode';
                        } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                            this.cameraStatus = 'not_found';
                            this.activeTab = 'kode';
                        } else {
                            this.cameraStatus = 'error';
                            this.cameraErrorMessage = err.message || 'Kamera tidak dapat diakses.';
                        }
                    }
                },

                stopCamera() {
                    this.pauseScanningLoop();
                    if (this.mediaStream) {
                        this.mediaStream.getTracks().forEach(t => t.stop());
                        this.mediaStream = null;
                    }
                    if (this.$refs.videoElement) {
                        this.$refs.videoElement.srcObject = null;
                    }
                },

                async loadVideoDevices() {
                    try {
                        const devices = await navigator.mediaDevices.enumerateDevices();
                        this.videoDevices = devices.filter(d => d.kind === 'videoinput');
                        if (!this.selectedCameraId && this.videoDevices.length > 0) {
                            this.selectedCameraId = this.videoDevices[0].deviceId;
                        }
                    } catch (e) {}
                },

                async changeCameraDevice() {
                    if (this.selectedCameraId) {
                        await this.startCamera();
                    }
                },

                async flipCameraFacing() {
                    this.cameraFacingMode = this.cameraFacingMode === 'environment' ? 'user' : 'environment';
                    this.selectedCameraId = '';
                    await this.startCamera();
                },

                async toggleTorch() {
                    if (!this.mediaStream || !this.hasTorch) return;
                    const track = this.mediaStream.getVideoTracks()[0];
                    if (track && track.applyConstraints) {
                        try {
                            this.isTorchOn = !this.isTorchOn;
                            await track.applyConstraints({
                                advanced: [{ torch: this.isTorchOn }]
                            });
                        } catch (e) {
                            this.isTorchOn = false;
                        }
                    }
                },

                toggleCameraPause() {
                    this.isCameraPaused = !this.isCameraPaused;
                    if (this.isCameraPaused) {
                        this.pauseScanningLoop();
                    } else {
                        this.startScanningLoop();
                    }
                },

                startScanningLoop() {
                    this.pauseScanningLoop();
                    // Scan ~9 frames per second (110ms interval) on scaled canvas
                    this.scanIntervalId = setInterval(() => this.captureAndScanFrame(), 110);
                },

                pauseScanningLoop() {
                    if (this.scanIntervalId) {
                        clearInterval(this.scanIntervalId);
                        this.scanIntervalId = null;
                    }
                },

                async captureAndScanFrame() {
                    if (this.sheetOpen || this.isCameraPaused || !this.$refs.videoElement) return;
                    const video = this.$refs.videoElement;
                    if (video.readyState < 2 || video.videoWidth === 0) return;

                    const canvas = this.$refs.canvasElement;
                    if (!canvas) return;

                    // Downscale to 360px width for fast processing and low battery use
                    const targetWidth = 360;
                    const targetHeight = Math.round((video.videoHeight / video.videoWidth) * targetWidth);
                    canvas.width = targetWidth;
                    canvas.height = targetHeight;

                    const ctx = canvas.getContext('2d', { willReadFrequently: true });
                    ctx.drawImage(video, 0, 0, targetWidth, targetHeight);

                    // 1. Try native BarcodeDetector if available
                    if ('BarcodeDetector' in window) {
                        try {
                            const barcodeDetector = new window.BarcodeDetector({ formats: ['qr_code'] });
                            const barcodes = await barcodeDetector.detect(canvas);
                            if (barcodes && barcodes.length > 0) {
                                this.handleScannedRawText(barcodes[0].rawValue);
                                return;
                            }
                        } catch (e) {}
                    }

                    // 2. Fallback to jsQR
                    if (typeof window.jsQR === 'function') {
                        const imgData = ctx.getImageData(0, 0, targetWidth, targetHeight);
                        const code = window.jsQR(imgData.data, imgData.width, imgData.height, {
                            inversionAttempts: 'dontInvert',
                        });
                        if (code && code.data) {
                            this.handleScannedRawText(code.data);
                        }
                    }
                },

                handleScannedRawText(rawText) {
                    if (!rawText) return;
                    const trimmed = rawText.trim();

                    // Client validation: must look like SynemaX booking or /tiket/{code}
                    let parsedCode = '';
                    if (trimmed.includes('/tiket/')) {
                        const parts = trimmed.split('/tiket/');
                        parsedCode = (parts[1] || '').split(/[?#]/)[0];
                    } else if (trimmed.startsWith('HC-') || /^[A-Z0-9\-]{4,24}$/i.test(trimmed)) {
                        parsedCode = trimmed;
                    }

                    if (!parsedCode) {
                        this.showClientAlert('QR ini bukan tiket SynemaX.');
                        return;
                    }

                    // 3-second deduplication lock
                    const now = Date.now();
                    if (parsedCode === this.lastScannedCode && (now - this.lastScannedTimestamp) < 3000) {
                        return;
                    }

                    this.lastScannedCode = parsedCode;
                    this.lastScannedTimestamp = now;

                    this.performLookup(parsedCode, 'qr');
                },

                showClientAlert(msg) {
                    this.clientAlertMessage = msg;
                    if (this.clientAlertTimeout) clearTimeout(this.clientAlertTimeout);
                    this.clientAlertTimeout = setTimeout(() => {
                        this.clientAlertMessage = '';
                    }, 3000);
                },

                // ---------------- LOOKUP & VERIFY ----------------
                async performLookup(codeStr, method) {
                    this.isSearching = true;
                    try {
                        const res = await fetch(config.routes.lookup, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                code: codeStr,
                                session_id: this.selectedSessionId,
                            })
                        });

                        const data = await res.json();
                        this.isSearching = false;

                        if (res.status === 429) {
                            this.showClientAlert(data.message || 'Terlalu banyak permintaan. Tunggu sebentar.');
                            return;
                        }

                        if (data.status === 'ambiguous') {
                            this.ambiguousMatches = data.matches || [];
                            this.switchTab('kode');
                            return;
                        }

                        if (data.status === 'not_found') {
                            this.openSheet({
                                verdict: 'tidak_ditemukan',
                                verdict_badge: { label: 'Tidak Ditemukan', token: 'danger' },
                                message: data.message || 'Kode tidak ditemukan. Cek ejaan atau pakai tab Kode/Daftar.',
                                code: codeStr,
                            });
                            return;
                        }

                        if (data.preview) {
                            data.preview.method = method;
                            this.openSheet(data.preview);
                        }
                    } catch (err) {
                        this.isSearching = false;
                        this.showClientAlert('Gagal menghubungi server. Periksa koneksi internet.');
                    }
                },

                async verifyBookingById(bookingId, method) {
                    const item = this.sessionListItems.find(i => i.id === bookingId);
                    if (item && item.code) {
                        await this.performLookup(item.code, method);
                    }
                },

                submitManualCode() {
                    const code = this.manualCodeInput.trim();
                    if (!code) return;
                    this.ambiguousMatches = [];
                    this.performLookup(code, 'kode');
                },

                async pasteFromClipboard() {
                    if (navigator.clipboard && navigator.clipboard.readText) {
                        try {
                            const text = await navigator.clipboard.readText();
                            if (text) {
                                this.manualCodeInput = text.trim();
                            }
                        } catch (e) {}
                    }
                },

                onLiveSearchInput() {
                    clearTimeout(this.liveSearchTimer);
                    const q = this.liveSearchInput.trim();
                    if (q.length < 2) {
                        this.liveSearchResults = [];
                        return;
                    }
                    this.liveSearchTimer = setTimeout(async () => {
                        try {
                            const url = new URL(config.routes.searchLive, window.location.origin);
                            url.searchParams.set('q', q);
                            const res = await fetch(url.toString(), {
                                headers: { 'Accept': 'application/json' }
                            });
                            const data = await res.json();
                            this.liveSearchResults = Array.isArray(data) ? data : (data.bookings || []);
                        } catch (e) {}
                    }, 250);
                },

                // ---------------- SHEET & CHECK-IN EXECUTION ----------------
                openSheet(preview) {
                    this.currentPreview = preview;
                    this.verdictToken = preview.verdict_badge?.token || (preview.verdict === 'siap_hadir' ? 'success' : 'danger');
                    this.sheetOpen = true;
                    this.showFullPhone = false;

                    this.vibrateDevice();

                    if (preview.verdict === 'siap_hadir' || preview.verdict === 'sudah_hadir') {
                        this.playBeep();
                    }
                },

                closeSheet() {
                    this.sheetOpen = false;
                    this.currentPreview = null;
                    if (this.autoCloseTimeout) {
                        clearTimeout(this.autoCloseTimeout);
                        this.autoCloseTimeout = null;
                    }
                },

                async executeCheckIn(isOverride = false) {
                    if (!this.currentPreview || !this.currentPreview.booking_id) return;
                    this.isSubmitting = true;

                    try {
                        const res = await fetch(config.routes.checkIn, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                booking_id: this.currentPreview.booking_id,
                                method: this.currentPreview.method || 'qr',
                                allow_override: isOverride,
                                reason: isOverride ? this.overrideReason : null,
                                session_id: this.selectedSessionId,
                            })
                        });

                        const data = await res.json();
                        this.isSubmitting = false;
                        this.overrideModalOpen = false;
                        this.overrideReason = '';

                        if (data.preview) {
                            this.openSheet(data.preview);
                        } else {
                            this.currentPreview.message = data.message;
                            this.currentPreview.can_check_in = false;
                        }

                        if (data.success) {
                            this.playBeep();
                            this.vibrateDevice();
                            this.loadSessionBookings(false);

                            // Auto close sheet in 2 seconds on success
                            this.autoCloseTimeout = setTimeout(() => {
                                this.closeSheet();
                            }, 2000);
                        }
                    } catch (err) {
                        this.isSubmitting = false;
                        this.showClientAlert('Gagal memproses check-in. Coba lagi.');
                    }
                },

                // ---------------- SESSION BOOKINGS LIST ----------------
                get sessionListHadirCount() {
                    return this.sessionListItems.filter(i => i.is_hadir).length;
                },

                get sessionListBelumCount() {
                    return this.sessionListItems.filter(i => !i.is_hadir).length;
                },

                get filteredSessionListItems() {
                    let items = this.sessionListItems;
                    if (this.daftarFilter === 'belum') {
                        items = items.filter(i => !i.is_hadir);
                    } else if (this.daftarFilter === 'hadir') {
                        items = items.filter(i => i.is_hadir);
                    }

                    if (this.daftarSearch.trim()) {
                        const q = this.daftarSearch.trim().toLowerCase();
                        items = items.filter(i =>
                            (i.customer_name || '').toLowerCase().includes(q) ||
                            (i.code || '').toLowerCase().includes(q) ||
                            (i.code_suffix || '').toLowerCase().includes(q)
                        );
                    }

                    return items;
                },

                async loadSessionBookings(force = false) {
                    this.isLoadingDaftar = true;
                    try {
                        const url = new URL(config.routes.sessionBookings, window.location.origin);
                        url.searchParams.set('session_id', this.selectedSessionId);

                        const res = await fetch(url.toString(), {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        this.sessionListItems = data.items || [];
                        this.sessionListSummaryText = data.summary?.text || 'Memuat...';

                        // Update header counter
                        const hadir = data.summary?.hadir || 0;
                        const total = (data.summary?.hadir || 0) + (data.summary?.belum || 0);
                        this.sessionStatsText = `Hadir ${hadir} dari ${total} orang`;
                        this.sessionProgressPct = total > 0 ? Math.round((hadir / total) * 100) : 0;
                    } catch (e) {
                    } finally {
                        this.isLoadingDaftar = false;
                    }
                },

                updateSessionStatsHeader() {
                    const sel = document.getElementById('session_context_select');
                    if (sel && sel.selectedIndex >= 0) {
                        const text = sel.options[sel.selectedIndex].text;
                        if (this.selectedSessionId === 'all') {
                            this.currentSessionFilmBadge = 'Semua Sesi';
                        } else {
                            const parts = text.split(' · ');
                            this.currentSessionFilmBadge = parts[1] || 'Sesi';
                        }
                    }
                }
            };
        }
    </script>
</body>
</html>
