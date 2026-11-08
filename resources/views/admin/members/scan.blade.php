<!DOCTYPE html>
<html lang="id" class="h-full antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Scan Member - SynemaX Admin</title>

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
    x-data="memberScannerApp({
        initialSessionId: @js($selectedSessionId),
        soundEnabledInitial: @js($soundEnabled),
        routes: {
            lookup: @js(route('admin.scan.lookup')),
            memberCheckIn: @js(route('admin.scan.member_checkin')),
            memberUndo: @js(route('admin.scan.member_undo')),
            memberRewardSettle: @js(route('admin.scan.member_reward_settle')),
            scanTicket: @js(route('admin.scan.index')),
        }
    })"
    x-init="initApp()"
    class="h-full flex flex-col bg-bg font-sans text-text select-none overscroll-none"
>

    {{-- HEADER UTAMA --}}
    <header class="shrink-0 h-14 border-b border-border bg-surface px-3 sm:px-6 flex items-center justify-between gap-3 z-20 pt-[env(safe-area-inset-top)]">
        <div class="flex items-center gap-2.5 min-w-0">
            <a
                href="{{ route('admin.members.index') }}"
                class="inline-flex size-9 sm:size-10 items-center justify-center rounded-xl border border-border bg-surface text-text hover:bg-surface-2 transition shadow-xs"
                aria-label="Kembali ke Daftar Member"
                title="Kembali ke Daftar Member"
            >
                <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </a>
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <h1 class="text-sm font-black text-text tracking-tight truncate">Scan Member</h1>
                    <span class="inline-flex items-center gap-1 rounded-md bg-amber-500/15 px-1.5 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-400 border border-amber-500/30">
                        <span class="size-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        <span>Stempel Loyalitas</span>
                    </span>
                </div>
            </div>
        </div>

        {{-- Kanan: Navigasi cepat ke Scan Tiket & Toggle Suara --}}
        <div class="flex items-center gap-2">
            <a
                href="{{ route('admin.scan.index') }}"
                class="hidden sm:inline-flex min-h-[36px] items-center gap-1.5 rounded-xl border border-border bg-surface-2 px-3 text-xs font-semibold text-text-muted hover:text-text hover:bg-surface transition"
            >
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
                <span>Beralih ke Scan Tiket</span>
            </a>

            <button
                type="button"
                @click="soundEnabled = !soundEnabled"
                class="inline-flex size-9 sm:size-10 items-center justify-center rounded-xl border border-border bg-surface text-text hover:bg-surface-2 transition shadow-xs"
                :title="soundEnabled ? 'Suara aktif (klik untuk membisukan)' : 'Suara bisu (klik untuk aktifkan)'"
            >
                <svg x-show="soundEnabled" class="size-4.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.414 0-.75-.336-.75-.75V8.25c0-.414.336-.75.75-.75h2.24z" />
                </svg>
                <svg x-show="!soundEnabled" x-cloak class="size-4.5 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 9.75L19.5 12m0 0l2.25 2.25M19.5 12l2.25-2.25M19.5 12l-2.25 2.25m-10.5-7.5l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.414 0-.75-.336-.75-.75V8.25c0-.414.336-.75.75-.75h2.24z" />
                </svg>
            </button>
        </div>
    </header>

    {{-- KONTEN UTAMA DUA KOLOM (RESPONSIF) --}}
    <main class="flex-1 min-h-0 flex flex-col lg:flex-row overflow-hidden">

        {{-- KOLOM KIRI: KAMERA SCANNER & INPUT MANUAL --}}
        <section class="flex-1 flex flex-col min-h-0 bg-surface border-b lg:border-b-0 lg:border-r border-border p-3 sm:p-5 overflow-y-auto">
            
            {{-- Pilihan Sesi Penayangan Hari Ini --}}
            <div class="mb-4">
                <label for="member_session_select" class="block text-xs font-semibold text-text-muted mb-1.5">
                    Sesi Penayangan yang Dihadiri
                </label>
                <div class="flex items-center gap-2">
                    <select
                        id="member_session_select"
                        x-model="selectedSessionId"
                        @change="onSessionChanged()"
                        class="flex-1 min-h-[44px] rounded-xl border border-border bg-surface-2 px-3 text-xs font-bold text-text focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30 transition"
                    >
                        @forelse ($todaySessions as $session)
                            <option value="{{ $session->id }}" {{ $selectedSession?->id === $session->id ? 'selected' : '' }}>
                                {{ format_cinema_time($session->start_time) }} · {{ $session->film?->title ?? 'Sesi Bebas' }} ({{ $session->is_private ? 'Privat' : 'Publik' }})
                            </option>
                        @empty
                            <option value="none">Tidak ada sesi penayangan hari ini</option>
                        @endforelse
                    </select>
                </div>
            </div>

            {{-- Viewport Kamera --}}
            <div class="relative w-full max-w-lg mx-auto aspect-square rounded-2xl bg-black overflow-hidden border border-border shadow-md flex items-center justify-center">
                <video
                    x-ref="videoElement"
                    playsinline
                    muted
                    class="absolute inset-0 size-full object-cover"
                ></video>
                <canvas x-ref="canvasElement" class="hidden"></canvas>

                {{-- Overlay Bingkai Scan --}}
                <div class="relative z-10 size-64 sm:size-72 border-2 border-amber-500/60 rounded-2xl pointer-events-none flex flex-col justify-between p-2">
                    <div class="flex justify-between">
                        <div class="size-6 border-t-4 border-l-4 border-amber-500 rounded-tl-lg"></div>
                        <div class="size-6 border-t-4 border-r-4 border-amber-500 rounded-tr-lg"></div>
                    </div>
                    <div class="flex justify-between">
                        <div class="size-6 border-b-4 border-l-4 border-amber-500 rounded-bl-lg"></div>
                        <div class="size-6 border-b-4 border-r-4 border-amber-500 rounded-br-lg"></div>
                    </div>
                    <div class="scan-laser-beam absolute inset-x-2 h-0.5 bg-gradient-to-r from-transparent via-amber-400 to-transparent shadow-[0_0_8px_rgba(245,158,11,0.8)]"></div>
                </div>

                {{-- Status Kamera & Tombol Switch --}}
                <div class="absolute bottom-3 inset-x-3 z-10 flex items-center justify-between pointer-events-auto">
                    <button
                        type="button"
                        @click="switchCamera()"
                        class="min-h-[38px] inline-flex items-center gap-1.5 rounded-xl bg-black/60 backdrop-blur-md px-3 py-1.5 text-[11px] font-bold text-white border border-white/20 hover:bg-black/80 transition"
                    >
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span>Putar Kamera</span>
                    </button>

                    <button
                        type="button"
                        x-show="hasTorch"
                        @click="toggleTorch()"
                        class="min-h-[38px] inline-flex items-center gap-1.5 rounded-xl bg-black/60 backdrop-blur-md px-3 py-1.5 text-[11px] font-bold text-white border border-white/20 hover:bg-black/80 transition"
                    >
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.516 0c.85.493 1.508 1.333 1.508 2.316V18" />
                        </svg>
                        <span x-text="torchOn ? 'Matikan Senter' : 'Nyalakan Senter'"></span>
                    </button>
                </div>
            </div>

            {{-- Input Manual Kode / Pencarian --}}
            <div class="mt-4 max-w-lg mx-auto w-full">
                <form @submit.prevent="lookupCode(manualInput)" class="flex gap-2">
                    <div class="relative flex-1">
                        <input
                            type="text"
                            x-model="manualInput"
                            placeholder="Ketik kode MBR-XXXX-XXXX-C..."
                            class="w-full min-h-[44px] rounded-xl border border-border bg-surface-2 px-3.5 text-xs text-text uppercase placeholder:normal-case placeholder:text-text-muted focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30 transition"
                        >
                    </div>
                    <button
                        type="submit"
                        :disabled="!manualInput.trim() || isLookingUp"
                        class="min-h-[44px] inline-flex items-center justify-center rounded-xl bg-amber-500 px-4 text-xs font-bold text-black hover:bg-amber-400 disabled:opacity-40 transition shadow-xs shrink-0"
                    >
                        <span x-show="!isLookingUp">Cari</span>
                        <span x-show="isLookingUp" x-cloak>Mencari…</span>
                    </button>
                </form>
                <p class="text-[11px] text-text-muted mt-1.5 text-center">
                    Arahkan kamera ke QR kartu fisik / digital member, atau masukkan kode 9 karakter.
                </p>
            </div>
        </section>

        {{-- KOLOM KANAN: PANEL HASIL PEMINDAIAN & REWARD --}}
        <section class="flex-1 flex flex-col min-h-0 bg-surface-2/40 p-4 sm:p-6 overflow-y-auto">
            
            {{-- Keadaan Kosong (Belum ada member yang discan) --}}
            <div x-show="!activeMember" class="my-auto text-center py-12 px-4 max-w-md mx-auto">
                <div class="size-16 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-500 flex items-center justify-center mx-auto mb-3 shadow-inner">
                    <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.364a4.125 4.125 0 00-6.338 0" />
                    </svg>
                </div>
                <h2 class="text-base font-bold text-text">Belum Ada Member yang Dipindai</h2>
                <p class="text-xs text-text-muted mt-1.5 leading-relaxed">
                    Pindai kartu QR member atau ketikkan kodenya di sebelah kiri untuk melihat status stempel, siklus kunjungan, dan klaim reward.
                </p>
            </div>

            {{-- Kartu Profil Member Aktif --}}
            <div x-show="activeMember" x-cloak class="space-y-4 max-w-xl mx-auto w-full">
                
                {{-- Kartu Utama --}}
                <div class="rounded-2xl border border-border bg-surface p-4 sm:p-5 shadow-sm space-y-4">
                    
                    {{-- Header Member --}}
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <template x-if="activeMember?.photo_url">
                                <img :src="activeMember.photo_url" class="size-14 rounded-xl object-cover border border-border shadow-xs" alt="Foto Member">
                            </template>
                            <template x-if="!activeMember?.photo_url">
                                <div class="size-14 rounded-xl bg-amber-500/20 border border-amber-500/30 text-amber-600 dark:text-amber-400 font-black text-xl flex items-center justify-center">
                                    <span x-text="activeMember?.name?.substring(0, 2).toUpperCase() || 'MB'"></span>
                                </div>
                            </template>
                            <div>
                                <h2 class="text-base font-black text-text" x-text="activeMember?.name"></h2>
                                <p class="text-xs font-mono font-bold text-amber-600 dark:text-amber-400" x-text="activeMember?.code"></p>
                                <template x-if="activeMember?.nickname">
                                    <p class="text-[11px] text-text-muted" x-text="'Panggilan: ' + activeMember.nickname"></p>
                                </template>
                            </div>
                        </div>

                        {{-- Status Chip --}}
                        <div>
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold border"
                                :class="{
                                    'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30': activeMember?.status === 'aktif',
                                    'bg-slate-500/15 text-text-muted border-border': activeMember?.status === 'nonaktif',
                                    'bg-danger/15 text-danger border-danger/30': activeMember?.status === 'diblokir'
                                }"
                                x-text="activeMember?.status ? (activeMember.status.charAt(0).toUpperCase() + activeMember.status.slice(1)) : ''"
                            ></span>
                        </div>
                    </div>

                    {{-- Progres 10 Stempel Kunjungan --}}
                    <div class="rounded-xl border border-border bg-surface-2 p-3 sm:p-4 space-y-3">
                        <div class="flex items-center justify-between text-xs font-bold">
                            <span class="text-text" x-text="'Siklus Kunjungan Ke-' + (activeMember?.cycle_number || 1)"></span>
                            <span class="text-amber-600 dark:text-amber-400 font-mono" x-text="(activeMember?.current_cycle_visit_count || 0) + ' / 10'"></span>
                        </div>

                        {{-- 10 Lingkaran Stempel --}}
                        <div class="grid grid-cols-5 sm:grid-cols-10 gap-2">
                            <template x-for="i in 10" :key="i">
                                <div
                                    class="relative aspect-square rounded-xl flex flex-col items-center justify-center text-xs font-black border transition"
                                    :class="{
                                        'bg-emerald-500 text-slate-950 border-emerald-400 shadow-xs': i <= (activeMember?.current_cycle_visit_count || 0),
                                        'bg-amber-500/20 text-amber-500 border-amber-500/40 ring-2 ring-amber-500/40 animate-pulse': i === (activeMember?.next_position),
                                        'bg-surface border-border text-text-muted': i > (activeMember?.current_cycle_visit_count || 0) && i !== (activeMember?.next_position)
                                    }"
                                >
                                    <span x-text="i"></span>

                                    {{-- Penanda Reward Khusus (Posisi 5 & 10) --}}
                                    <template x-if="i === 5">
                                        <span class="absolute -top-1.5 -right-1 text-[8px] bg-amber-500 text-black px-1 rounded-full font-black">2k</span>
                                    </template>
                                    <template x-if="i === 10">
                                        <span class="absolute -top-1.5 -right-1 text-[8px] bg-emerald-500 text-black px-1 rounded-full font-black">Gratis</span>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <p class="text-[11px] text-text-muted leading-relaxed" x-text="activeMember?.progress_description"></p>
                    </div>

                    {{-- Banner Pemberitahuan Reward Aktif --}}
                    <div x-show="activeMember?.potential_reward && activeMember.potential_reward !== 'none'" x-cloak class="rounded-xl border border-amber-500/40 bg-amber-500/10 p-3.5 space-y-2">
                        <div class="flex items-center gap-2">
                            <svg class="size-5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h4 class="text-xs font-black text-amber-600 dark:text-amber-400">
                                <span x-text="activeMember?.potential_reward === 'discount' ? 'Kunjungan Ke-5: Berhak Potongan Rp 2.000!' : 'Kunjungan Ke-10: Berhak 1 Tiket Nonton Gratis!'"></span>
                            </h4>
                        </div>
                        <p class="text-[11px] text-text-muted">
                            Reward langsung diselesaikan di loket/pintu saat kunjungan ini dicatat.
                        </p>
                    </div>

                    {{-- Pilihan Booking Terkait Jika Ada --}}
                    <template x-if="activeSessionBookings && activeSessionBookings.length > 0">
                        <div class="rounded-xl border border-border bg-surface-2 p-3 space-y-1.5">
                            <label class="block text-xs font-semibold text-text-muted">
                                Booking Terkait Sesi Ini (Opsional):
                            </label>
                            <select
                                x-model="selectedBookingId"
                                class="w-full min-h-[38px] rounded-lg border border-border bg-surface px-2.5 text-xs font-semibold text-text"
                            >
                                <option value="">Tanpa Booking (Datang Langsung / Diskon Kasir)</option>
                                <template x-for="b in activeSessionBookings" :key="b.id">
                                    <option :value="b.id" x-text="b.code + ' · ' + b.customer_name + ' (' + (b.is_paid ? 'Lunas Online' : 'Bayar Ditempat') + ')'"></option>
                                </template>
                            </select>
                        </div>
                    </template>

                    {{-- Tombol Aksi Utama --}}
                    <div class="flex flex-col sm:flex-row gap-2 pt-2">
                        <button
                            type="button"
                            @click="recordVisit()"
                            :disabled="!activeMember?.can_record || isRecording"
                            class="flex-1 min-h-[46px] inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-5 text-sm font-black text-black hover:bg-amber-400 disabled:opacity-40 transition shadow-sm"
                        >
                            <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span x-show="!isRecording">Catat Kunjungan (+1 Stempel)</span>
                            <span x-show="isRecording" x-cloak>Menyimpan…</span>
                        </button>

                        <button
                            type="button"
                            @click="resetActiveMember()"
                            class="min-h-[46px] inline-flex items-center justify-center rounded-xl border border-border bg-surface px-4 text-xs font-bold text-text hover:bg-surface-2 transition"
                        >
                            Tutup / Pindai Baru
                        </button>
                    </div>

                    {{-- Tombol Batal Hadir (Undo) Jika Sesi Ini Sudah Dicatat --}}
                    <div x-show="activeMember?.verdict === 'sudah_tercatat_sesi_ini' && activeMember?.last_valid_visit_id" class="pt-2">
                        <button
                            type="button"
                            @click="openUndoModal = true"
                            class="w-full min-h-[38px] inline-flex items-center justify-center gap-1.5 rounded-xl border border-danger/40 bg-danger/10 text-xs font-bold text-danger hover:bg-danger hover:text-white transition"
                        >
                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                            </svg>
                            <span>Batalkan Catatan Kunjungan Ini (Undo)</span>
                        </button>
                    </div>

                </div>

            </div>
        </section>
    </main>

    {{-- MODAL IN-APP: BATALKAN KUNJUNGAN (UNDO) --}}
    <div
        x-show="openUndoModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4"
    >
        <div class="w-full max-w-md rounded-2xl border border-border bg-surface p-5 space-y-4 shadow-2xl">
            <h3 class="text-base font-black text-danger">Batalkan Kunjungan Member</h3>
            <p class="text-xs text-text-muted leading-relaxed">
                Hanya kunjungan sah terakhir yang dapat dibatalkan. Stempel kunjungan akan dikurangi 1 dan reward terkait akan disesuaikan.
            </p>
            <div>
                <label for="undo_reason_input" class="block text-xs font-semibold text-text mb-1.5">Alasan Pembatalan (Wajib):</label>
                <input
                    type="text"
                    id="undo_reason_input"
                    x-model="undoReason"
                    placeholder="Contoh: Salah scan kartu, member batal nonton"
                    class="w-full min-h-[42px] rounded-xl border border-border bg-surface-2 px-3 text-xs text-text focus:border-danger focus:ring-1 focus:ring-danger/30 transition"
                >
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button
                    type="button"
                    @click="openUndoModal = false; undoReason = ''"
                    class="min-h-[40px] rounded-xl border border-border px-4 text-xs font-semibold text-text hover:bg-surface-2"
                >
                    Kembali
                </button>
                <button
                    type="button"
                    @click="submitUndo()"
                    :disabled="!undoReason.trim() || isUndoing"
                    class="min-h-[40px] rounded-xl bg-danger px-4 text-xs font-bold text-white hover:bg-danger/90 disabled:opacity-40"
                >
                    <span x-show="!isUndoing">Ya, Batalkan Kunjungan</span>
                    <span x-show="isUndoing" x-cloak>Membatalkan…</span>
                </button>
            </div>
        </div>
    </div>

    {{-- APLIKASI ALPINES JS KHUSUS SCAN MEMBER --}}
    <script>
        function memberScannerApp(config) {
            return {
                selectedSessionId: config.initialSessionId || 'none',
                soundEnabled: Boolean(config.soundEnabledInitial),
                hasTorch: false,
                torchOn: false,
                cameraStatus: 'idle',
                videoStream: null,
                facingMode: 'environment',
                manualInput: '',
                isLookingUp: false,
                isRecording: false,
                isUndoing: false,
                activeMember: null,
                activeSessionBookings: [],
                selectedBookingId: '',
                openUndoModal: false,
                undoReason: '',
                audioCtx: null,

                initApp() {
                    this.initCamera();
                },

                playBeep(success = true) {
                    if (!this.soundEnabled) return;
                    try {
                        const AudioContext = window.AudioContext || window.webkitAudioContext;
                        if (!AudioContext) return;
                        if (!this.audioCtx) this.audioCtx = new AudioContext();
                        if (this.audioCtx.state === 'suspended') this.audioCtx.resume();

                        const osc = this.audioCtx.createOscillator();
                        const gain = this.audioCtx.createGain();
                        osc.connect(gain);
                        gain.connect(this.audioCtx.destination);

                        if (success) {
                            osc.frequency.setValueAtTime(880, this.audioCtx.currentTime); // A5
                            osc.frequency.setValueAtTime(1320, this.audioCtx.currentTime + 0.08); // E6
                            gain.gain.setValueAtTime(0.15, this.audioCtx.currentTime);
                            gain.gain.exponentialRampToValueAtTime(0.01, this.audioCtx.currentTime + 0.22);
                            osc.start();
                            osc.stop(this.audioCtx.currentTime + 0.22);
                        } else {
                            osc.frequency.setValueAtTime(260, this.audioCtx.currentTime);
                            gain.gain.setValueAtTime(0.2, this.audioCtx.currentTime);
                            gain.gain.exponentialRampToValueAtTime(0.01, this.audioCtx.currentTime + 0.3);
                            osc.start();
                            osc.stop(this.audioCtx.currentTime + 0.3);
                        }
                    } catch (e) {}
                },

                async initCamera() {
                    const video = this.$refs.videoElement;
                    if (!video || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                        this.cameraStatus = 'unsupported';
                        return;
                    }

                    try {
                        if (this.videoStream) {
                            this.videoStream.getTracks().forEach(t => t.stop());
                        }

                        const stream = await navigator.mediaDevices.getUserMedia({
                            video: {
                                facingMode: { ideal: this.facingMode },
                                width: { ideal: 1280 },
                                height: { ideal: 720 }
                            },
                            audio: false
                        });

                        this.videoStream = stream;
                        video.srcObject = stream;
                        await video.play();
                        this.cameraStatus = 'active';

                        const track = stream.getVideoTracks()[0];
                        if (track && track.getCapabilities && track.getCapabilities().torch) {
                            this.hasTorch = true;
                        }

                        this.scanLoop();
                    } catch (err) {
                        this.cameraStatus = 'permission_denied';
                    }
                },

                switchCamera() {
                    this.facingMode = this.facingMode === 'environment' ? 'user' : 'environment';
                    this.initCamera();
                },

                async toggleTorch() {
                    if (!this.videoStream) return;
                    const track = this.videoStream.getVideoTracks()[0];
                    if (track && this.hasTorch) {
                        this.torchOn = !this.torchOn;
                        await track.applyConstraints({ advanced: [{ torch: this.torchOn }] }).catch(() => {});
                    }
                },

                scanLoop() {
                    const video = this.$refs.videoElement;
                    const canvas = this.$refs.canvasElement;
                    if (!video || !canvas || this.cameraStatus !== 'active') return;

                    if (video.readyState === video.HAVE_ENOUGH_DATA && !this.isLookingUp && !this.activeMember) {
                        canvas.width = video.videoWidth;
                        canvas.height = video.videoHeight;
                        const ctx = canvas.getContext('2d', { willReadFrequently: true });
                        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                        const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);

                        if (window.jsQR) {
                            const code = window.jsQR(imgData.data, imgData.width, imgData.height, {
                                inversionAttempts: 'dontInvert'
                            });

                            if (code && code.data && code.data.trim()) {
                                this.lookupCode(code.data.trim());
                            }
                        }
                    }

                    requestAnimationFrame(() => this.scanLoop());
                },

                async lookupCode(rawCode) {
                    if (!rawCode || this.isLookingUp) return;
                    this.isLookingUp = true;

                    try {
                        const res = await fetch(config.routes.lookup, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                code: rawCode,
                                session_id: this.selectedSessionId !== 'none' ? this.selectedSessionId : null
                            })
                        });

                        const data = await res.json();

                        if (data.type === 'booking') {
                            this.playBeep(false);
                            window.showToast({
                                type: 'warning',
                                message: 'Kode yang dipindai adalah Tiket Booking (' + rawCode + '), bukan Kartu Member.'
                            });
                            this.manualInput = '';
                            return;
                        }

                        if (data.status === 'found' && data.type === 'member') {
                            this.playBeep(true);
                            this.activeMember = {
                                ...data.member,
                                ...data.evaluation,
                                can_record: data.evaluation?.can_record ?? true,
                                verdict: data.evaluation?.verdict,
                                progress_description: data.evaluation?.message,
                                potential_reward: data.evaluation?.potential_reward,
                                last_valid_visit_id: data.evaluation?.last_valid_visit_id
                            };
                            this.activeSessionBookings = data.session_bookings || [];
                            if (this.activeSessionBookings.length > 0) {
                                this.selectedBookingId = this.activeSessionBookings[0].id;
                            }
                            window.showToast({
                                type: 'success',
                                message: 'Kartu member ' + data.member.name + ' ditemukan.'
                            });
                            this.manualInput = '';
                        } else {
                            this.playBeep(false);
                            window.showToast({
                                type: 'error',
                                message: data.message || 'Kartu member tidak ditemukan.'
                            });
                            this.manualInput = '';
                        }
                    } catch (e) {
                        this.playBeep(false);
                        window.showToast({
                            type: 'error',
                            message: 'Koneksi terputus saat mencari kartu member.'
                        });
                    } finally {
                        this.isLookingUp = false;
                    }
                },

                onSessionChanged() {
                    if (this.activeMember?.code) {
                        this.lookupCode(this.activeMember.code);
                    }
                },

                async recordVisit() {
                    if (!this.activeMember || this.isRecording) return;
                    this.isRecording = true;

                    try {
                        const res = await fetch(config.routes.memberCheckIn, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                member_id: this.activeMember.id,
                                session_id: this.selectedSessionId,
                                booking_id: this.selectedBookingId || null,
                                method: 'qr'
                            })
                        });

                        const data = await res.json();

                        if (data.success) {
                            this.playBeep(true);
                            window.showToast({
                                type: 'success',
                                message: data.message || 'Kunjungan member berhasil dicatat!'
                            });
                            this.lookupCode(this.activeMember.code);
                        } else {
                            this.playBeep(false);
                            window.showToast({
                                type: 'error',
                                message: data.message || 'Gagal mencatat kunjungan.'
                            });
                        }
                    } catch (e) {
                        this.playBeep(false);
                        window.showToast({
                            type: 'error',
                            message: 'Koneksi terputus saat mencatat kunjungan.'
                        });
                    } finally {
                        this.isRecording = false;
                    }
                },

                async submitUndo() {
                    if (!this.activeMember || !this.undoReason.trim() || this.isUndoing) return;
                    this.isUndoing = true;

                    try {
                        const res = await fetch(config.routes.memberUndo, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                member_id: this.activeMember.id,
                                visit_id: this.activeMember.last_valid_visit_id,
                                reason: this.undoReason.trim()
                            })
                        });

                        const data = await res.json();

                        if (data.success) {
                            this.playBeep(true);
                            this.openUndoModal = false;
                            this.undoReason = '';
                            window.showToast({
                                type: 'success',
                                message: data.message || 'Kunjungan berhasil dibatalkan.'
                            });
                            this.lookupCode(this.activeMember.code);
                        } else {
                            this.playBeep(false);
                            window.showToast({
                                type: 'error',
                                message: data.message || 'Gagal membatalkan kunjungan.'
                            });
                        }
                    } catch (e) {
                        this.playBeep(false);
                        window.showToast({
                            type: 'error',
                            message: 'Koneksi terputus saat membatalkan kunjungan.'
                        });
                    } finally {
                        this.isUndoing = false;
                    }
                },

                resetActiveMember() {
                    this.activeMember = null;
                    this.activeSessionBookings = [];
                    this.selectedBookingId = '';
                    this.manualInput = '';
                }
            };
        }
    </script>

    {{-- In-App Dialogs (Toasts, Offline Banner, Confirmations) --}}
    <x-in-app-dialogs />
</body>
</html>
