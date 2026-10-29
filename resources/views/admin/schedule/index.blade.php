@extends('layouts.admin')

@section('title', 'Kelola jadwal')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black">Kelola jadwal</h1>
            <p class="mt-2 text-sm text-text-muted">Atur slot sesi, status buka/tutup, hari libur, dan jadwal tayang.</p>
        </div>
        <form method="POST" action="{{ route('admin.schedule.generate') }}" novalidate>
            @csrf
            <button class="inline-flex min-h-11 items-center justify-center rounded-xl bg-accent px-5 text-sm font-bold text-accent-contrast hover:bg-accent-hover transition shadow-sm">
                Generate 14 hari ke depan
            </button>
        </form>
    </div>

    @if ($errors->any())
        <div role="alert" class="mt-5 rounded-xl border border-danger/50 bg-danger/10 p-4 text-sm text-danger">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Sesi 14 Hari ke Depan (Rombak Total: Baris per sesi, tanpa dropdown film/status, switch otomatis) --}}
    <section class="mt-8" x-data="{
        showMoreDays: false,
        openSessionId: null,
        toggleSession(sessionId, event) {
            event.stopPropagation();
            const checkbox = event.target;
            const targetStatus = checkbox.checked ? 'tersedia' : 'ditutup';
            const csrfToken = document.querySelector('meta[name=csrf-token]') ? document.querySelector('meta[name=csrf-token]').content : '';

            fetch(`/admin/jadwal/sesi/${sessionId}/toggle`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: targetStatus })
            })
            .then(res => res.json())
            .then(data => {
                if (data.requires_confirmation) {
                    checkbox.checked = !checkbox.checked; // kembalikan sementara
                    window.openConfirmModal({
                        title: 'Tutup Sesi Ini?',
                        message: data.message,
                        confirmText: 'Ya, Tutup Sesi & Batalkan Booking',
                        isDanger: true,
                        onConfirm: () => {
                            fetch(`/admin/jadwal/sesi/${sessionId}/toggle`, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ status: 'ditutup', confirmed: true })
                            })
                            .then(r => r.json())
                            .then(confirmData => {
                                if (confirmData.status === 'ok') {
                                    checkbox.checked = false;
                                    window.showToast({ type: 'success', message: confirmData.message });
                                    setTimeout(() => window.location.reload(), 500);
                                } else {
                                    window.showToast({ type: 'error', message: confirmData.message || 'Gagal menutup sesi.' });
                                }
                            });
                        }
                    });
                } else if (data.status === 'ok') {
                    window.showToast({ type: 'success', message: data.message });
                } else {
                    checkbox.checked = !checkbox.checked;
                    window.showToast({ type: 'error', message: data.message || 'Gagal mengubah status sesi.' });
                }
            })
            .catch(() => {
                checkbox.checked = !checkbox.checked;
                window.showToast({ type: 'error', message: 'Koneksi terputus saat mengubah status sesi.' });
            });
        }
    }">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold">Sesi 14 hari ke depan</h2>
                <p class="mt-1 text-xs text-text-muted">Film ditentukan penonton saat booking. Klik baris sesi untuk melihat rincian pemesan.</p>
            </div>
        </div>

        @if ($sessionsByDate->isEmpty())
            <div class="mt-4 rounded-2xl border border-border bg-surface p-8 text-center text-sm text-text-muted">
                Belum ada sesi 14 hari ke depan. Tekan tombol <strong>Generate 14 hari ke depan</strong> di atas untuk membuat jadwal otomatis.
            </div>
        @else
            @php
                $dayIndex = 0;
            @endphp
            <div class="mt-6 space-y-6">
                @foreach ($sessionsByDate as $dateString => $daySessions)
                    @php
                        $dayCarbon = \Carbon\Carbon::parse($dateString)->locale('id');
                        $isToday = $dateString === $todayDate;
                        $isTomorrow = $dateString === \Carbon\Carbon::parse($todayDate)->addDay()->toDateString();
                        
                        if ($isToday) {
                            $dayHeading = 'Hari ini (' . $dayCarbon->translatedFormat('D, d M Y') . ')';
                        } elseif ($isTomorrow) {
                            $dayHeading = 'Besok (' . $dayCarbon->translatedFormat('D, d M Y') . ')';
                        } else {
                            $dayHeading = $dayCarbon->translatedFormat('l, d M Y');
                        }
                        
                        $isBeyondSeven = $dayIndex >= 7;
                        $dayIndex++;
                    @endphp

                    <div x-show="{{ $isBeyondSeven ? 'showMoreDays' : 'true' }}" x-cloak class="rounded-2xl border border-border bg-surface overflow-hidden shadow-sm">
                        {{-- Header Tanggal --}}
                        <div class="flex items-center justify-between bg-surface-2/60 px-4 py-3 border-b border-border">
                            <div class="flex items-center gap-2">
                                <span class="size-2 rounded-full {{ $isToday ? 'bg-accent animate-pulse' : 'bg-text-muted/40' }}"></span>
                                <h3 class="text-sm font-bold text-text">{{ $dayHeading }}</h3>
                                <span class="rounded-full bg-surface px-2 py-0.5 text-[11px] font-semibold text-text-muted border border-border/60">
                                    {{ $daySessions->count() }} sesi
                                </span>
                            </div>

                            {{-- Menu Titik Tiga per Hari --}}
                            <div class="relative" x-data="{ openDayMenu: false }">
                                <button type="button" 
                                        @click="openDayMenu = !openDayMenu" 
                                        class="flex size-10 items-center justify-center rounded-lg text-text-muted hover:text-text hover:bg-surface transition-colors"
                                        aria-label="Opsi hari {{ $dayHeading }}">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="1.5" />
                                        <circle cx="12" cy="5" r="1.5" />
                                        <circle cx="12" cy="19" r="1.5" />
                                    </svg>
                                </button>

                                <div x-show="openDayMenu" 
                                     @click.outside="openDayMenu = false" 
                                     x-cloak 
                                     class="absolute right-0 top-11 z-20 w-52 rounded-xl border border-border bg-surface p-1.5 shadow-xl text-xs font-semibold">
                                    <form method="POST" action="{{ route('admin.schedule.day.toggle') }}" 
                                          data-confirm="Buka kembali seluruh sesi pada tanggal {{ $dayCarbon->translatedFormat('d M Y') }}?"
                                          data-confirm-title="Buka Seluruh Hari"
                                          data-confirm-action="Buka Sesi"
                                          novalidate>
                                        @csrf
                                        <input type="hidden" name="date" value="{{ $dateString }}">
                                        <input type="hidden" name="action" value="buka">
                                        <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-text hover:bg-surface-2 transition text-left">
                                            <svg class="size-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                            <span>Buka seluruh hari</span>
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.schedule.day.toggle') }}" 
                                          data-confirm="Tutup seluruh sesi pada tanggal {{ $dayCarbon->translatedFormat('d M Y') }}? Sesi yang memiliki booking aktif akan dibatalkan."
                                          data-confirm-title="Tutup Seluruh Hari"
                                          data-confirm-action="Tutup Seluruh Sesi"
                                          data-confirm-is-danger="true"
                                          novalidate>
                                        @csrf
                                        <input type="hidden" name="date" value="{{ $dateString }}">
                                        <input type="hidden" name="action" value="tutup">
                                        <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-danger hover:bg-danger/10 transition text-left">
                                            <svg class="size-4 text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                            <span>Tutup seluruh hari</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- Daftar Sesi (Satu Baris per Sesi) --}}
                        {{-- Daftar Sesi (Satu Baris per Sesi) --}}
                        <div class="divide-y divide-border/60">
                            @foreach ($daySessions as $session)
                                @php
                                    $film = $session->activeFilm();
                                    $lockedFilm = $session->film_id ? $session->film : null;
                                    $capacity = $session->seatCapacity();
                                    $occupied = (int) ($session->occupied_seats ?? 0);
                                    $isClosed = $session->status === 'ditutup';
                                    $isCompleted = $session->isCompleted();
                                    $isOngoing = $session->isOngoing();
                                    $isPast = $session->isPast();
                                    $startTimeId = format_jam_id($session->start_time);
                                    $quorum = $session->quorum();
                                    $tallies = $session->candidateFilmTallies();
                                    $leadingTally = $tallies->first();
                                    $leadingSeats = $leadingTally['seats'] ?? 0;
                                    $statusWaktu = $session->statusWaktu();
                                    $isConfirmed = $session->isConfirmed();
                                    $isCancelledQuorum = $session->isCancelledByQuorum();
                                @endphp
                                <div class="transition-colors hover:bg-surface-2/40 cursor-pointer {{ $isCompleted ? 'opacity-60 bg-surface/30' : '' }}"
                                     @click="openSessionId = (openSessionId === {{ $session->id }} ? null : {{ $session->id }})">
                                    {{-- Desktop Row --}}
                                    <div class="hidden sm:grid sm:grid-cols-[6.5rem_9.5rem_7.5rem_1fr_auto] items-center gap-3 px-4 py-3 min-h-[52px]">
                                        {{-- Jam --}}
                                        <div class="font-bold text-sm text-text whitespace-nowrap">
                                            {{ $startTimeId }}
                                        </div>

                                        {{-- Chip Tipe & Status Sesi --}}
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            @if ($isConfirmed)
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-black bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                                                    Pasti tayang
                                                </span>
                                            @elseif ($session->isInAgreementWindow())
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-black bg-amber-500/20 text-amber-700 dark:text-amber-400 border border-amber-500/30 animate-pulse">
                                                    Butuh kesepakatan
                                                </span>
                                            @elseif ($isCancelledQuorum)
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold bg-danger/15 text-danger border border-danger/25">
                                                    Dibatalkan kuorum
                                                </span>
                                            @elseif ($isClosed)
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold bg-slate-500/15 text-text-muted border border-border">
                                                    Ditutup admin
                                                </span>
                                            @elseif ($isCompleted)
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold bg-slate-500/15 text-slate-500 dark:text-slate-400 border border-slate-500/20">
                                                    Selesai
                                                </span>
                                            @elseif ($isOngoing)
                                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                                    <span class="size-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                    Berlangsung
                                                </span>
                                            @elseif ($occupied > 0)
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold bg-blue-500/15 text-blue-600 dark:text-blue-300 border border-blue-500/20">
                                                    Menunggu kuorum
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold bg-surface-2 text-text-muted border border-border">
                                                    Kosong
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Progres Kuorum --}}
                                        <div>
                                            <div class="flex items-center justify-between text-xs font-semibold text-text-muted mb-1">
                                                <span>Kuorum</span>
                                                <span class="font-tabular font-bold {{ $isConfirmed ? 'text-emerald-500' : ($leadingSeats > 0 ? 'text-amber-500' : '') }}">
                                                    {{ $leadingSeats }}/{{ $quorum }}
                                                </span>
                                            </div>
                                            <div class="h-1.5 w-full rounded-full bg-border overflow-hidden">
                                                <div class="h-full rounded-full transition-all duration-300 {{ $isConfirmed ? 'bg-emerald-500' : 'bg-amber-500' }}"
                                                     style="width: {{ min(100, round(($leadingSeats / $quorum) * 100)) }}%"></div>
                                            </div>
                                        </div>

                                        {{-- Film Terkunci / Terdepan --}}
                                        <div class="min-w-0 pr-2">
                                            @if ($lockedFilm)
                                                <div class="flex items-center gap-1.5 truncate">
                                                    <span class="truncate font-black text-sm text-emerald-600 dark:text-emerald-400" title="{{ $lockedFilm->title }}">
                                                        {{ $lockedFilm->title }}
                                                    </span>
                                                    <span class="shrink-0 text-[10px] font-black text-emerald-500 px-1.5 py-0.2 rounded bg-emerald-500/10 border border-emerald-500/30">
                                                        Terkunci
                                                    </span>
                                                </div>
                                            @elseif ($leadingTally)
                                                <div class="flex items-center gap-1.5 truncate">
                                                    <span class="truncate font-bold text-sm text-text" title="{{ $leadingTally['title'] }}">
                                                        {{ $leadingTally['title'] }}
                                                    </span>
                                                    <span class="shrink-0 text-[10px] font-bold text-amber-500 px-1.5 py-0.2 rounded bg-amber-500/10 border border-amber-500/30">
                                                        Terdepan ({{ $leadingSeats }})
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-xs text-text-muted italic">
                                                    Belum ada film
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Switch Buka/Ditutup --}}
                                        <div class="flex items-center gap-3 shrink-0" @click.stop>
                                            @if ($isCompleted)
                                                <label class="flex min-h-[44px] min-w-[44px] cursor-not-allowed items-center gap-2 pr-1 opacity-50" title="Sesi telah selesai">
                                                    <input type="checkbox" disabled class="peer sr-only">
                                                    <span aria-hidden="true" class="relative h-6 w-11 shrink-0 rounded-full bg-slate-300 dark:bg-slate-700 after:absolute after:left-1 after:top-1 after:size-4 after:rounded-full after:bg-white shadow-inner"></span>
                                                    <span class="text-xs font-bold text-text-muted w-12 text-left">
                                                        Selesai
                                                    </span>
                                                </label>
                                            @else
                                                <label class="flex min-h-[44px] min-w-[44px] cursor-pointer items-center gap-2 pr-1" title="{{ $isClosed ? 'Status: Ditutup. Klik untuk buka.' : 'Status: Buka. Klik untuk tutup.' }}">
                                                    <input type="checkbox" 
                                                           @checked(!$isClosed)
                                                           @change="toggleSession({{ $session->id }}, $event)"
                                                           class="peer sr-only">
                                                    <span aria-hidden="true" class="relative h-6 w-11 shrink-0 rounded-full bg-border transition-colors peer-checked:bg-success after:absolute after:left-1 after:top-1 after:size-4 after:rounded-full after:bg-white after:transition-transform peer-checked:after:translate-x-5 shadow-inner"></span>
                                                    <span class="text-xs font-bold text-text-muted peer-checked:text-success w-12 text-left">
                                                        {{ $isClosed ? 'Tutup' : 'Buka' }}
                                                    </span>
                                                </label>
                                            @endif

                                            {{-- Indikator Expand Detail --}}
                                            <button type="button" 
                                                    class="flex size-8 items-center justify-center rounded-lg text-text-muted hover:text-text hover:bg-surface-2 transition-transform"
                                                    :class="{ 'rotate-180': openSessionId === {{ $session->id }} }"
                                                    aria-label="Detail pemesan sesi">
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Mobile Row (Dua Tingkat) --}}
                                    <div class="sm:hidden px-4 py-3">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="font-bold text-sm text-text">{{ $startTimeId }}</span>
                                                @if ($isConfirmed)
                                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-black bg-emerald-500/15 text-emerald-400">
                                                        Pasti tayang
                                                    </span>
                                                @elseif ($session->isInAgreementWindow())
                                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-black bg-amber-500/20 text-amber-400 animate-pulse">
                                                        Kesepakatan
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold bg-surface-2 text-text-muted">
                                                        {{ $session->is_private ? 'Privat' : 'Publik' }}
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="flex items-center shrink-0" @click.stop>
                                                @if (!$isCompleted)
                                                    <label class="flex min-h-[44px] min-w-[44px] cursor-pointer items-center gap-2 justify-end">
                                                        <span class="text-xs font-bold text-text-muted peer-checked:text-success">
                                                            {{ $isClosed ? 'Tutup' : 'Buka' }}
                                                        </span>
                                                        <input type="checkbox" 
                                                               @checked(!$isClosed)
                                                               @change="toggleSession({{ $session->id }}, $event)"
                                                               class="peer sr-only">
                                                        <span aria-hidden="true" class="relative h-6 w-11 shrink-0 rounded-full bg-border transition-colors peer-checked:bg-success after:absolute after:left-1 after:top-1 after:size-4 after:rounded-full after:bg-white after:transition-transform peer-checked:after:translate-x-5 shadow-inner"></span>
                                                    </label>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="mt-2 flex items-center justify-between gap-3 text-xs">
                                            <div class="min-w-0 flex-1 truncate">
                                                @if ($lockedFilm)
                                                    <span class="font-black text-emerald-400 truncate block">{{ $lockedFilm->title }} (Terkunci)</span>
                                                @elseif ($leadingTally)
                                                    <span class="font-bold text-text truncate block">{{ $leadingTally['title'] }} ({{ $leadingSeats }}/{{ $quorum }})</span>
                                                @else
                                                    <span class="text-text-muted italic">Belum ada film</span>
                                                @endif
                                            </div>
                                            <div class="shrink-0 font-semibold text-text-muted">
                                                Kursi: {{ $occupied }}/{{ $capacity }}
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Panel Detail Sesi (Peminat per Film, Daftar Booking, Selesaikan Sesi) --}}
                                    <div x-show="openSessionId === {{ $session->id }}" 
                                         x-cloak 
                                         class="border-t border-border bg-surface-2/70 px-4 py-4 text-xs space-y-4 cursor-default"
                                         @click.stop>
                                        <div class="flex items-center justify-between font-bold text-text border-b border-border pb-2">
                                            <span class="text-sm">Detail Sesi: {{ $startTimeId }} · {{ $dayCarbon->translatedFormat('D, d M Y') }}</span>
                                            <span class="text-text-muted font-normal">{{ $session->occupiedBookings->count() }} Booking Aktif</span>
                                        </div>

                                        {{-- 1. PEMINAT PER FILM --}}
                                        <div class="rounded-xl border border-border bg-surface p-3.5 space-y-2">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-500">Peminat per Film Calon (Kuorum: {{ $quorum }} Kursi):</span>
                                            @if ($tallies->isNotEmpty())
                                                <div class="grid gap-1.5 sm:grid-cols-2">
                                                    @foreach ($tallies as $t)
                                                        <div class="flex items-center justify-between rounded-lg border border-border/60 bg-surface-2 px-3 py-2 text-xs">
                                                            <span class="font-bold text-text truncate max-w-[200px]">{{ $t['title'] }}</span>
                                                            <span class="font-tabular font-black text-amber-400 shrink-0">
                                                                {{ $t['seats'] }}/{{ $quorum }} kursi ({{ $t['bookings_count'] ?? $t['count'] ?? 1 }} booking)
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-xs text-text-muted italic">Belum ada film yang dipilih oleh pemesan.</p>
                                            @endif
                                        </div>

                                        {{-- 2. PANEL: SELESAIKAN SESI (INTERVENSI ADMIN) --}}
                                        <div class="rounded-xl border border-amber-500/40 bg-surface p-4 shadow-sm space-y-3"
                                             x-data="{ activeAction: null }">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <h4 class="text-xs font-black uppercase tracking-wider text-amber-500">Panel Selesaikan Sesi</h4>
                                                    <p class="mt-0.5 text-[11px] text-text-muted">Gunakan bila sesi membutuhkan keputusan manual penayangan, pemindahan, atau pembatalan kuorum.</p>
                                                </div>
                                            </div>

                                            <div class="flex flex-wrap gap-2 pt-1">
                                                <button type="button" 
                                                        @click="activeAction = (activeAction === 'tayang' ? null : 'tayang')"
                                                        class="inline-flex min-h-9 items-center gap-1.5 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-3.5 text-xs font-bold text-emerald-500 hover:bg-emerald-500 hover:text-black transition">
                                                    ✓ Tayangkan Tetap
                                                </button>
                                                <button type="button" 
                                                        @click="activeAction = (activeAction === 'pindah' ? null : 'pindah')"
                                                        class="inline-flex min-h-9 items-center gap-1.5 rounded-xl border border-border bg-surface px-3.5 text-xs font-bold text-text hover:border-amber-400 transition">
                                                    ↷ Pindahkan Booking
                                                </button>
                                                <button type="button" 
                                                        @click="activeAction = (activeAction === 'batal' ? null : 'batal')"
                                                        class="inline-flex min-h-9 items-center gap-1.5 rounded-xl border border-danger/40 bg-danger/10 px-3.5 text-xs font-bold text-danger hover:bg-danger hover:text-white transition">
                                                    ✕ Batalkan Sesi
                                                </button>
                                            </div>

                                            {{-- Form Aksi 1: Tayangkan Tetap --}}
                                            <div x-show="activeAction === 'tayang'" x-cloak class="mt-3 rounded-xl border border-emerald-500/30 bg-emerald-500/5 p-3.5 space-y-3">
                                                <h5 class="text-xs font-black text-emerald-400">Konfirmasi Tayangkan Tetap Sesi Ini</h5>
                                                <form method="POST" action="{{ route('admin.schedule.sessions.resolve', ['session' => $session->id]) }}" class="space                                                    @csrf
                                                    <input type="hidden" name="action" value="tayangkan_tetap">
                                                    <div>
                                                        <label class="block text-[11px] font-bold text-text mb-1">Pilih Film yang Ditayangkan:</label>
                                                        <x-ui.select
                                                            name="film_id"
                                                            :value="($session->activeFilm()?->id ?? $films->first()?->id)"
                                                            :options="$films->pluck('title', 'id')"
                                                            placeholder="Pilih Film..."
                                                            required
                                                        />
                                                    </div>
                                                    <div>
                                                        <label class="block text-[11px] font-bold text-text">Alasan Singkat (Wajib):</label>
                                                        <textarea name="reason" rows="2" required placeholder="Contoh: Kesepakatan offline dengan penonton di lokasi" class="mt-1 w-full rounded-lg border border-border bg-surface p-2 text-xs text-text"></textarea>
                                                    </div>
                                                    <div class="flex justify-end gap-2">
                                                        <button type="button" @click="activeAction = null" class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-text">Batal</button>
                                                        <button type="submit" class="btn-gold-cinema rounded-lg px-4 py-1.5 text-xs font-bold">Simpan & Tayangkan</button>
                                                    </div>
                                                </form>
                                            </div>

                                            {{-- Form Aksi 2: Pindahkan Booking --}}
                                            <div x-show="activeAction === 'pindah'" x-cloak class="mt-3 rounded-xl border border-amber-500/30 bg-amber-500/5 p-3.5 space-y-3">
                                                <h5 class="text-xs font-black text-amber-400">Pindahkan Seluruh Booking ke Sesi Lain</h5>
                                                <form method="POST" action="{{ route('admin.schedule.sessions.resolve', ['session' => $session->id]) }}" class="space-y-3">
                                                    @csrf
                                                    <input type="hidden" name="action" value="pindahkan_booking">
                                                    <div>
                                                        <label class="block text-[11px] font-bold text-text mb-1">Pilih Sesi Tujuan:</label>
                                                        @php
                                                            $targetSessionOptions = $sessions
                                                                ->filter(fn ($s) => $s->id !== $session->id && $s->status === 'tersedia')
                                                                ->mapWithKeys(fn ($s) => [
                                                                    $s->id => \Carbon\Carbon::parse($s->session_date)->locale('id')->translatedFormat('D, d M') . ' · ' . substr((string) $s->start_time, 0, 5) . ' WIB (Sisa ' . $s->remainingSeats() . ' kursi)'
                                                                ]);
                                                        @endphp
                                                        <x-ui.select
                                                            name="target_session_id"
                                                            :options="$targetSessionOptions"
                                                            placeholder="Pilih Sesi Tujuan..."
                                                            required
                                                        />
                                                    </div>  </div>
                                                    <div class="flex justify-end gap-2">
                                                        <button type="button" @click="activeAction = null" class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-text">Batal</button>
                                                        <button type="submit" class="btn-gold-cinema rounded-lg px-4 py-1.5 text-xs font-bold">Pindahkan Booking</button>
                                                    </div>
                                                </form>
                                            </div>

                                            {{-- Form Aksi 3: Batalkan Sesi --}}
                                            <div x-show="activeAction === 'batal'" x-cloak class="mt-3 rounded-xl border border-danger/40 bg-danger/5 p-3.5 space-y-3">
                                                <h5 class="text-xs font-black text-danger">Batalkan Sesi & Proses Pembatalan Booking</h5>
                                                <p class="text-[11px] text-danger-text">Dampak: Seluruh booking aktif dibatalkan. Booking yang sudah dibayar otomatis ditandai untuk proses refund.</p>
                                                <form method="POST" action="{{ route('admin.schedule.sessions.resolve', ['session' => $session->id]) }}" class="space-y-3">
                                                    @csrf
                                                    <input type="hidden" name="action" value="batalkan_sesi">
                                                    <div>
                                                        <label class="block text-[11px] font-bold text-text">Alasan Pembatalan (Wajib):</label>
                                                        <textarea name="reason" rows="2" required placeholder="Contoh: Kuorum tidak tercapai sampai batas konfirmasi" class="mt-1 w-full rounded-lg border border-border bg-surface p-2 text-xs text-text"></textarea>
                                                    </div>
                                                    <div class="flex justify-end gap-2">
                                                        <button type="button" @click="activeAction = null" class="rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-text">Batal</button>
                                                        <button type="submit" class="rounded-lg bg-danger px-4 py-1.5 text-xs font-bold text-white hover:opacity-90">Ya, Batalkan Sesi</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        {{-- 3. DAFTAR BOOKING & TEMPLATE WHATSAPP --}}
                                        <div>
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-text-muted mb-2 block">Daftar Pemesan & Hubungi WhatsApp:</span>
                                            @if ($session->bookings->isEmpty())
                                                <p class="text-text-muted italic py-1">Belum ada pemesan pada sesi ini.</p>
                                            @else
                                                <div class="grid gap-2">
                                                    @foreach ($session->bookings as $booking)
                                                        @php
                                                            $myFilmTally = $tallies->firstWhere('film_id', $booking->film_id)['seats'] ?? $booking->seats_reserved;
                                                            $msgAjakan = "Halo {$booking->customer_name}, kami dari SynemaX. Sesi nonton " . ($booking->film->title ?? 'film') . " pada {$dayHeading} {$startTimeId} saat ini sudah ada {$myFilmTally} penonton, kurang " . max(0, 4 - $myFilmTally) . " orang lagi agar pasti tayang! Ajak temanmu bergabung ya: " . route('booking.create', ['session' => $session->id, 'film' => $booking->film_id]);
                                                            $msgKesepakatan = "Halo {$booking->customer_name}, kami dari SynemaX. Sesi nonton pada {$dayHeading} {$startTimeId} akan segera tayang dalam 3 jam. Mau bergabung ke film terdepan agar sesi pasti tayang? Cek pilihanmu di: " . route('booking.ticket', ['code' => $booking->code]);
                                                            $msgBatal = "Halo {$booking->customer_name}, mohon maaf sesi nonton pada {$dayHeading} {$startTimeId} tidak mencapai kuorum sehingga dibatalkan tanpa biaya. " . ($booking->isPaid() ? "Tim kami akan segera memproses pengembalian dana (refund) penuh Anda. " : "") . "Anda dapat memilih jadwal lain di: " . route('schedule.index');
                                                        @endphp
                                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-xl border border-border bg-surface p-3.5">
                                                            <div class="space-y-0.5">
                                                                <div class="flex items-center gap-2">
                                                                    <span class="font-bold text-sm text-text">{{ $booking->customer_name }}</span>
                                                                    <span class="text-text-muted font-tabular">({{ $booking->code }})</span>
                                                                    @if ($booking->refund_needed)
                                                                        <span class="rounded bg-danger/20 text-danger px-1.5 py-0.2 text-[10px] font-bold">Perlu Refund</span>
                                                                    @endif
                                                                </div>
                                                                <div class="text-text-muted text-[11px] flex flex-wrap gap-2 items-center">
                                                                    <span>{{ $booking->seats_reserved }} kursi ({{ $booking->package }})</span>
                                                                    <span>·</span>
                                                                    <span>Film: <strong>{{ $booking->film->title ?? 'Belum ditentukan' }}</strong></span>
                                                                    <span>·</span>
                                                                    <span class="capitalize">{{ str_replace('_', ' ', $booking->status) }}</span>
                                                                </div>
                                                            </div>

                                                            {{-- Hubungi WhatsApp: Tiga Template --}}
                                                            <div class="flex flex-wrap items-center gap-1.5 shrink-0" x-data="{ openWa: false }">
                                                                <div class="relative">
                                                                    <button type="button" @click="openWa = !openWa"
                                                                            class="inline-flex min-h-[34px] items-center gap-1 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-2.5 text-[11px] font-bold text-emerald-500 hover:bg-emerald-500 hover:text-black transition">
                                                                        <span>💬 Hubungi WA</span>
                                                                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                                    </button>

                                                                    <div x-show="openWa" @click.outside="openWa = false" x-cloak
                                                                         class="absolute right-0 top-10 z-30 w-56 rounded-xl border border-border bg-surface p-1.5 shadow-xl space-y-1 text-left">
                                                                        <a href="https://wa.me/{{ $booking->whatsapp }}?text={{ urlencode($msgAjakan) }}" target="_blank" rel="noopener noreferrer"
                                                                           class="block rounded-lg px-2.5 py-1.5 text-[11px] font-semibold text-text hover:bg-surface-2">
                                                                            Template: Ajakan Bergabung
                                                                        </a>
                                                                        <a href="https://wa.me/{{ $booking->whatsapp }}?text={{ urlencode($msgKesepakatan) }}" target="_blank" rel="noopener noreferrer"
                                                                           class="block rounded-lg px-2.5 py-1.5 text-[11px] font-semibold text-text hover:bg-surface-2">
                                                                            Template: Kesepakatan Film
                                                                        </a>
                                                                        <a href="https://wa.me/{{ $booking->whatsapp }}?text={{ urlencode($msgBatal) }}" target="_blank" rel="noopener noreferrer"
                                                                           class="block rounded-lg px-2.5 py-1.5 text-[11px] font-semibold text-danger hover:bg-danger/10">
                                                                            Template: Pembatalan Kuorum
                                                                        </a>
                                                                    </div>
                                                                </div>

                                                                <a href="{{ route('admin.bookings.show', $booking) }}" 
                                                                   class="inline-flex min-h-[34px] items-center justify-center rounded-lg border border-border bg-surface-2 px-2.5 text-[11px] font-bold text-text hover:border-amber-400 transition">
                                                                    Detail ↗
                                                                </a>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>/div>
                    </div>
                @endforeach
            </div>

            {{-- Tombol Tampilkan 7 Hari Berikutnya --}}
            @if (count($sessionsByDate) > 7)
                <div x-show="!showMoreDays" class="mt-6 text-center">
                    <button type="button" 
                            @click="showMoreDays = true" 
                            class="inline-flex min-h-12 items-center justify-center rounded-xl border border-border bg-surface px-6 text-sm font-bold text-text hover:bg-surface-2 transition shadow-sm">
                        Tampilkan 7 hari berikutnya ({{ count($sessionsByDate) - 7 }} hari lagi)
                    </button>
                </div>
            @endif
        @endif
    </section>

    {{-- Template Jam Sesi --}}
    <section class="mt-12">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold">Template jam sesi</h2>
                <p class="mt-1 text-sm text-text-muted">Durasi adalah lama sesi sejak jam mulai. Standar template 150 menit.</p>
            </div>
        </div>
        <div class="mt-4 grid gap-4">
            @foreach ($dayTypes as $type => $label)
                <article class="min-w-0 overflow-hidden rounded-2xl border border-border bg-surface">
                    <div class="flex items-center justify-between gap-3 border-b border-border px-4 py-3 bg-surface-2/40">
                        <h3 class="font-bold text-sm text-text">{{ $label }}</h3>
                        <span class="text-xs text-text-muted">{{ $templates->where('day_type', $type)->count() }} jam</span>
                    </div>
                    <div class="hidden xl:block">
                        <x-admin.table class="rounded-none" table-class="text-sm">
                            <x-slot:columns>
                                <col style="width: 29%">
                                <col style="width: 27%">
                                <col style="width: 19%">
                                <col style="width: 25%">
                            </x-slot:columns>
                            <x-slot:head>
                                <th scope="col" class="px-3 py-2 font-semibold">Jam mulai</th>
                                <th scope="col" class="px-3 py-2 font-semibold">Durasi</th>
                                <th scope="col" class="px-2 py-2 font-semibold">Status</th>
                                <th scope="col" class="px-2 py-2 text-right font-semibold">Aksi</th>
                            </x-slot:head>
                            @forelse ($templates->where('day_type', $type) as $template)
                                <tr x-data="{ initial: ['{{ substr((string) $template->start_time, 0, 5) }}', {{ (int) $template->duration_minutes }}, {{ $template->is_active ? 'true' : 'false' }}], durationValue: {{ (int) $template->duration_minutes }}, dirty: false, saving: false, activeLabel: '{{ $template->is_active ? 'Aktif' : 'Nonaktif' }}', formatDuration(value) { const hours = Math.floor(Number(value) / 60); const remainder = Number(value) % 60; return '= ' + (hours ? hours + ' jam' : '') + (hours && remainder ? ' ' : '') + (remainder ? remainder + ' menit' : '') }, sync() { this.dirty = this.$refs.start.value !== this.initial[0] || Number(this.durationValue) !== this.initial[1] || this.$refs.active.checked !== this.initial[2]; this.activeLabel = this.$refs.active.checked ? 'Aktif' : 'Nonaktif' } }" @input="sync()" @change="sync()" class="transition-colors hover:bg-surface-2/60">
                                    <td class="px-3 py-3">
                                        <form id="template-update-desktop-{{ $template->id }}" method="POST" action="{{ route('admin.schedule.templates.update', $template) }}" @submit="saving = true" novalidate>@csrf @method('PATCH')<input type="hidden" name="day_type" value="{{ $type }}"></form>
                                        <label for="start-desktop-{{ $template->id }}" class="sr-only">Jam mulai</label>
                                        <input id="start-desktop-{{ $template->id }}" x-ref="start" form="template-update-desktop-{{ $template->id }}" name="start_time" type="time" value="{{ substr((string) $template->start_time, 0, 5) }}" required class="min-h-10 min-w-[7.5rem] w-full rounded-lg border border-border bg-bg px-2 text-base text-text">
                                        @error('start_time')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                                    </td>
                                    <td class="px-3 py-3">
                                        <div>
                                            <label for="duration-desktop-{{ $template->id }}" class="sr-only">Durasi</label>
                                            <div class="flex min-h-10 items-center rounded-lg border border-border bg-bg focus-within:ring-2 focus-within:ring-focus">
                                                <input id="duration-desktop-{{ $template->id }}" x-ref="duration" x-model.number="durationValue" form="template-update-desktop-{{ $template->id }}" name="duration_minutes" type="number" min="30" max="600" required class="min-h-9 min-w-0 flex-1 border-0 bg-transparent px-2 text-base text-text focus:ring-0">
                                                <span class="px-2 text-xs text-text-muted">menit</span>
                                            </div>
                                            <p class="mt-1 text-xs text-text-muted" x-text="formatDuration(durationValue)"></p>
                                        </div>
                                    </td>
                                    <td class="px-2 py-3">
                                        <input type="hidden" form="template-update-desktop-{{ $template->id }}" name="is_active" value="0">
                                        <label class="flex min-h-10 cursor-pointer items-center gap-2">
                                            <input x-ref="active" form="template-update-desktop-{{ $template->id }}" name="is_active" type="checkbox" value="1" @checked($template->is_active) class="peer sr-only">
                                            <span aria-hidden="true" class="relative h-6 w-10 shrink-0 rounded-full bg-border transition-colors peer-checked:bg-accent after:absolute after:left-1 after:top-1 after:size-4 after:rounded-full after:bg-surface after:transition-transform peer-checked:after:translate-x-4"></span>
                                            <span class="text-xs text-text" x-text="activeLabel">{{ $template->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                        </label>
                                    </td>
                                    <td class="px-2 py-3">
                                        <span x-show="dirty" x-cloak class="mb-1 block text-right text-xs font-semibold text-warning">Belum disimpan</span>
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            <button form="template-update-desktop-{{ $template->id }}" :disabled="!dirty || saving" class="min-h-10 min-w-20 rounded-lg bg-accent px-2 text-xs font-bold text-accent-contrast disabled:cursor-not-allowed disabled:opacity-45"><span x-show="!saving">Simpan</span><span x-show="saving" x-cloak>…</span></button>
                                            <form method="POST" action="{{ route('admin.schedule.templates.destroy', $template) }}" 
                                                  data-confirm="Yakin hapus jam {{ substr((string) $template->start_time, 0, 5) }}?"
                                                  data-confirm-title="Hapus Jam Template"
                                                  data-confirm-action="Hapus"
                                                  data-confirm-is-danger="true"
                                                  novalidate>
                                                @csrf @method('DELETE')
                                                <button aria-label="Hapus jam {{ substr((string) $template->start_time, 0, 5) }}" title="Hapus jam {{ substr((string) $template->start_time, 0, 5) }}" class="grid size-10 place-items-center rounded-lg border border-danger/40 text-danger hover:bg-danger/10">
                                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="size-4"><path d="M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6m4 4v6m6-6v6"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-7 text-center text-sm text-text-muted">Belum ada jam untuk {{ $label }}.</td></tr>
                            @endforelse
                        </x-admin.table>
                    </div>
                    <div class="space-y-3 p-3 xl:hidden">
                        @forelse ($templates->where('day_type', $type) as $template)
                            <div x-data="{ initial: ['{{ substr((string) $template->start_time, 0, 5) }}', {{ (int) $template->duration_minutes }}, {{ $template->is_active ? 'true' : 'false' }}], durationValue: {{ (int) $template->duration_minutes }}, dirty: false, saving: false, activeLabel: '{{ $template->is_active ? 'Aktif' : 'Nonaktif' }}', formatDuration(value) { const hours = Math.floor(Number(value) / 60); const remainder = Number(value) % 60; return '= ' + (hours ? hours + ' jam' : '') + (hours && remainder ? ' ' : '') + (remainder ? remainder + ' menit' : '') }, sync() { this.dirty = this.$refs.start.value !== this.initial[0] || Number(this.durationValue) !== this.initial[1] || this.$refs.active.checked !== this.initial[2]; this.activeLabel = this.$refs.active.checked ? 'Aktif' : 'Nonaktif' } }" @input="sync()" @change="sync()" class="rounded-lg border border-border bg-bg p-4">
                                <form id="template-update-mobile-{{ $template->id }}" method="POST" action="{{ route('admin.schedule.templates.update', $template) }}" @submit="saving = true" novalidate>@csrf @method('PATCH')<input type="hidden" name="day_type" value="{{ $type }}"></form>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label for="start-mobile-{{ $template->id }}" class="mb-1 block text-sm font-semibold">Jam mulai (24 jam)</label>
                                        <input id="start-mobile-{{ $template->id }}" x-ref="start" form="template-update-mobile-{{ $template->id }}" name="start_time" type="time" value="{{ substr((string) $template->start_time, 0, 5) }}" required class="min-h-11 w-full rounded-lg border border-border bg-surface px-3 text-base text-text">
                                        @error('start_time')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="duration-mobile-{{ $template->id }}" class="mb-1 block text-sm font-semibold">Durasi sesi</label>
                                        <div class="flex min-h-11 items-center rounded-lg border border-border bg-surface focus-within:ring-2 focus-within:ring-focus">
                                            <input id="duration-mobile-{{ $template->id }}" x-ref="duration" x-model.number="durationValue" form="template-update-mobile-{{ $template->id }}" name="duration_minutes" type="number" min="30" max="600" required class="min-h-10 min-w-0 flex-1 border-0 bg-transparent px-3 text-base text-text focus:ring-0">
                                            <span class="px-3 text-sm text-text-muted">menit</span>
                                        </div>
                                        <p class="mt-1 text-xs text-text-muted" x-text="formatDuration(durationValue)"></p>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <input type="hidden" form="template-update-mobile-{{ $template->id }}" name="is_active" value="0">
                                        <label class="flex min-h-11 cursor-pointer items-center gap-2">
                                            <input x-ref="active" form="template-update-mobile-{{ $template->id }}" name="is_active" type="checkbox" value="1" @checked($template->is_active) class="peer sr-only">
                                            <span aria-hidden="true" class="relative h-6 w-10 rounded-full bg-border transition-colors peer-checked:bg-accent after:absolute after:left-1 after:top-1 after:size-4 after:rounded-full after:bg-surface after:transition-transform peer-checked:after:translate-x-4"></span>
                                            <span class="text-sm text-text" x-text="activeLabel">{{ $template->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center justify-between gap-3 border-t border-border pt-3">
                                    <span x-show="dirty" x-cloak class="text-xs font-semibold text-warning">Belum disimpan</span>
                                    <span x-show="!dirty" class="text-xs text-text-muted">Tersimpan</span>
                                    <div class="flex gap-2">
                                        <button form="template-update-mobile-{{ $template->id }}" :disabled="!dirty || saving" class="min-h-11 rounded-lg bg-accent px-4 text-sm font-bold text-accent-contrast disabled:cursor-not-allowed disabled:opacity-45"><span x-show="!saving">Simpan</span><span x-show="saving" x-cloak>Menyimpan…</span></button>
                                        <form method="POST" action="{{ route('admin.schedule.templates.destroy', $template) }}" 
                                              data-confirm="Yakin hapus jam {{ substr((string) $template->start_time, 0, 5) }}?"
                                              data-confirm-title="Hapus Jam Template"
                                              data-confirm-action="Hapus"
                                              data-confirm-is-danger="true"
                                              novalidate>
                                            @csrf @method('DELETE')
                                            <button aria-label="Hapus jam {{ substr((string) $template->start_time, 0, 5) }}" class="grid size-11 place-items-center rounded-lg border border-danger/40 text-danger hover:bg-danger/10">
                                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="size-4"><path d="M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6m4 4v6m6-6v6"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="rounded-lg border border-border bg-bg p-4 text-center text-sm text-text-muted">Belum ada jam untuk {{ $label }}.</p>
                        @endforelse
                    </div>
                    <form method="POST" action="{{ route('admin.schedule.templates.store') }}" class="grid gap-3 border-t border-border bg-surface-2/50 p-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end" novalidate>
                        @csrf
                        <input type="hidden" name="day_type" value="{{ $type }}">
                        <div>
                            <label for="new-time-{{ $type }}" class="mb-1 block text-sm font-semibold">Jam mulai baru</label>
                            <input id="new-time-{{ $type }}" name="start_time" type="time" required class="min-h-11 w-full rounded-lg border border-border bg-surface px-3 text-base text-text">
                            <p class="mt-1 text-xs text-text-muted">Format 24 jam, contoh 19:30.</p>
                        </div>
                        <div x-data="{ minutes: 150, format(value) { const hours = Math.floor(Number(value) / 60); const remainder = Number(value) % 60; return '= ' + (hours ? hours + ' jam' : '') + (hours && remainder ? ' ' : '') + (remainder ? remainder + ' menit' : '') } }">
                            <label for="new-duration-{{ $type }}" class="mb-1 block text-sm font-semibold">Durasi sesi</label>
                            <div class="flex min-h-11 items-center rounded-lg border border-border bg-surface focus-within:ring-2 focus-within:ring-focus">
                                <input id="new-duration-{{ $type }}" x-model.number="minutes" name="duration_minutes" type="number" min="30" max="600" value="150" required class="min-h-10 min-w-0 flex-1 border-0 bg-transparent px-3 text-base text-text focus:ring-0">
                                <span class="px-3 text-sm text-text-muted">menit</span>
                            </div>
                            <p class="mt-1 text-xs text-text-muted" x-text="format(minutes)">= 2 jam 30 menit</p>
                        </div>
                        <button class="min-h-11 rounded-xl border border-border bg-surface px-4 text-sm font-semibold text-text hover:bg-surface-2">Tambah jam baru</button>
                    </form>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Hari Libur & Tanggal Ditutup --}}
    <section class="mt-12 grid gap-6 lg:grid-cols-2">
        {{-- Hari Libur --}}
        <article class="rounded-2xl border border-border bg-surface p-5">
            <h2 class="text-lg font-bold">Hari libur</h2>
            <p class="text-xs text-text-muted mt-1">Hari libur menerapkan template jam khusus 'libur'.</p>
            <form method="POST" action="{{ route('admin.schedule.holidays.store') }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end" novalidate>
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-semibold" for="holiday_date">Tanggal libur</label>
                    <input id="holiday_date" name="holiday_date" type="date" required class="min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-base text-text">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold" for="holiday_name">Nama hari libur</label>
                    <input id="holiday_name" name="name" maxlength="120" required placeholder="Contoh: Hari Desa" class="min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-base text-text placeholder:text-text-muted">
                </div>
                <button class="min-h-11 rounded-xl bg-accent px-4 text-sm font-bold text-accent-contrast">Tambah</button>
            </form>
            <div class="mt-4 space-y-2">
                @forelse ($holidays as $holiday)
                    <div class="flex items-center justify-between gap-2 rounded-xl bg-surface-2 px-3 py-2.5 text-sm">
                        <span>{{ \Carbon\Carbon::parse($holiday->holiday_date)->locale('id')->translatedFormat('d M Y') }} · {{ $holiday->name }}</span>
                        <form method="POST" action="{{ route('admin.schedule.holidays.destroy', $holiday) }}" 
                              data-confirm="Hapus hari libur {{ $holiday->name }} ({{ \Carbon\Carbon::parse($holiday->holiday_date)->locale('id')->translatedFormat('d M Y') }})?"
                              data-confirm-title="Hapus Hari Libur"
                              data-confirm-action="Hapus"
                              data-confirm-is-danger="true"
                              novalidate>
                            @csrf @method('DELETE')
                            <button class="text-xs font-bold text-danger hover:underline">Hapus</button>
                        </form>
                    </div>
                @empty
                    <p class="text-xs text-text-muted">Belum ada hari libur tambahan.</p>
                @endforelse
            </div>
        </article>

        {{-- Tanggal / Sesi Ditutup --}}
        <article class="rounded-2xl border border-border bg-surface p-5">
            <h2 class="text-lg font-bold">Tutup tanggal atau sesi</h2>
            <p class="text-xs text-text-muted mt-1">Blokir tanggal atau jam tertentu dari reservasi.</p>
            <form method="POST" action="{{ route('admin.schedule.blocked.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2" novalidate>
                @csrf
                <div>
                    <label for="blocked_date" class="mb-1 block text-sm font-semibold">Tanggal ditutup</label>
                    <input id="blocked_date" name="blocked_date" type="date" required class="min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-base text-text">
                </div>
                <div>
                    <label for="blocked_time" class="mb-1 block text-sm font-semibold">Jam sesi (opsional)</label>
                    <input id="blocked_time" name="start_time" type="time" class="min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-base text-text">
                    <p class="mt-1 text-xs text-text-muted">Kosongkan untuk menutup seharian.</p>
                </div>
                <div class="sm:col-span-2">
                    <label for="blocked_reason" class="mb-1 block text-sm font-semibold">Alasan penutupan</label>
                    <input id="blocked_reason" name="reason" required maxlength="180" placeholder="Contoh: Maintenance studio" class="min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-base text-text placeholder:text-text-muted">
                </div>
                <button class="min-h-11 rounded-xl border border-danger/40 px-4 text-sm font-bold text-danger hover:bg-danger/10 sm:col-span-2">Tutup tanggal/sesi</button>
            </form>
            <div class="mt-4 space-y-2">
                @forelse ($blockedDates as $blocked)
                    <div class="flex items-center justify-between gap-2 rounded-xl bg-surface-2 px-3 py-2.5 text-sm">
                        <span>{{ \Carbon\Carbon::parse($blocked->blocked_date)->locale('id')->translatedFormat('d M Y') }} · {{ $blocked->scope_key === 'ALL' ? 'Seharian' : $blocked->scope_key }} — {{ $blocked->reason }}</span>
                        <form method="POST" action="{{ route('admin.schedule.blocked.destroy', $blocked) }}" 
                              data-confirm="Buka kembali penutupan pada {{ \Carbon\Carbon::parse($blocked->blocked_date)->locale('id')->translatedFormat('d M Y') }}?"
                              data-confirm-title="Buka Tanggal/Sesi"
                              data-confirm-action="Buka"
                              novalidate>
                            @csrf @method('DELETE')
                            <button class="text-xs font-bold text-danger hover:underline">Buka</button>
                        </form>
                    </div>
                @empty
                    <p class="text-xs text-text-muted">Tidak ada tanggal atau sesi yang sedang ditutup.</p>
                @endforelse
            </div>
        </article>
    </section>
@endsection
