@extends('layouts.public')

@section('title', 'Jadwal ' . $date->locale('id')->translatedFormat('l, d F'))
@section('meta')
    <meta name="description" content="Pilih jadwal nonton di studio mini SynemaX. Kapasitas tiap sesi maksimal enam kursi.">
@endsection

@section('content')
    <main class="mx-auto max-w-4xl px-4 py-6 sm:px-6 sm:py-10">
        {{-- Navigasi Kembali --}}
        <div>
            <a href="{{ route('home') }}#jadwal" class="inline-flex min-h-11 items-center gap-2 text-xs font-bold text-text-muted hover:text-amber-400 transition">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                <span>Kembali ke beranda</span>
            </a>
        </div>

        {{-- Indikator Langkah Riil --}}
        <div class="mt-4 border-b border-border dark:border-white/10 pb-4">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-amber-400 animate-pulse"></span>
                <p class="text-xs font-bold uppercase tracking-wider text-amber-500 dark:text-amber-400">Langkah 1 dari 5: Jadwal</p>
            </div>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-text sm:text-3xl">Pilih hari dan jam nonton</h1>
            <p class="mt-1 text-xs text-text-muted">Kapasitas maksimal 6 kursi per sesi. Status kursi diperbarui otomatis saat ada pemesanan baru.</p>
        </div>

        {{-- Pemilih Tanggal (Horizontal Scrollable Pills ala XXI / CGV) --}}
        <nav class="mt-6 flex snap-x gap-2.5 overflow-x-auto pb-3 scrollbar-none" aria-label="Pilih tanggal nonton">
            @foreach ($dates as $availableDate)
                @php
                    $dateKey = $availableDate->toDateString();
                    $isSelected = $dateKey === $date->toDateString();
                @endphp
                <a href="{{ route('schedule.date', ['date' => $dateKey]) }}"
                   @if ($isSelected) aria-current="date" @endif
                   class="min-w-28 shrink-0 snap-start rounded-2xl border p-3.5 text-center transition-all duration-200 {{ $isSelected ? 'border-amber-400 bg-gradient-to-b from-amber-500/25 via-surface to-surface text-amber-600 dark:text-amber-300 shadow-[0_0_20px_rgba(212,175,55,0.25)]' : 'border-border dark:border-white/10 bg-surface-2 dark:bg-surface text-text-muted hover:border-amber-400/50 hover:text-text' }}">
                    <span class="block text-[11px] font-bold uppercase tracking-wider {{ $isSelected ? 'text-amber-600 dark:text-amber-400' : 'text-text-muted' }}">
                        {{ $dateKey === $today ? 'Hari ini' : $availableDate->locale('id')->translatedFormat('l') }}
                    </span>
                    <span class="mt-1 block font-tabular text-lg font-black {{ $isSelected ? 'text-amber-950 dark:text-white' : 'text-text' }}">
                        {{ $availableDate->format('d M') }}
                    </span>
                    @if ($holidayDates->has($dateKey))
                        <span class="mt-1.5 inline-block rounded-md bg-amber-400/20 px-2 py-0.5 text-[10px] font-black uppercase text-amber-600 dark:text-amber-300 border border-amber-400/30">
                            Libur
                        </span>
                    @endif
                </a>
            @endforeach
        </nav>

        {{-- Judul Tanggal Terpilih --}}
        <div id="sessions" class="scroll-mt-8 mt-6 flex items-baseline justify-between gap-3 border-b border-border dark:border-white/10 pb-2">
            <div>
                <h2 class="text-lg font-black text-text">{{ $date->locale('id')->translatedFormat('l, d F Y') }}</h2>
            </div>
            @if ($holidayDates->has($date->toDateString()))
                <span class="rounded-lg bg-amber-400/20 border border-amber-400/40 px-2.5 py-1 text-xs font-black text-amber-600 dark:text-amber-300">
                    Hari libur
                </span>
            @endif
        </div>

        {{-- Daftar Sesi Tayang --}}
        @if ($closedReason)
            <div role="status" class="mt-6 rounded-2xl border border-danger/40 bg-danger-surface p-5 text-sm text-danger-text">
                <p class="font-bold">Studio tutup pada tanggal ini</p>
                <p class="mt-1 text-xs text-danger-text/90">{{ $closedReason }}</p>
            </div>
        @elseif ($sessions->isEmpty())
            <div class="mt-6 rounded-2xl border border-border dark:border-white/10 bg-surface-2 dark:bg-surface p-6 text-center text-sm text-text-muted">
                Belum ada sesi penayangan untuk tanggal ini. Silakan pilih tanggal lain di atas.
            </div>
        @else
            <div class="mt-6 space-y-3.5 pb-24">
                @foreach ($sessions as $session)
                    @php
                        $label = $session->is_private ? 'Sewa Private' : $session->availabilityLabel($bookingCutoffMinutes);
                        $canBook = ! $session->is_private
                            && ! in_array($label, ['Tutup', 'Sudah lewat', 'Penuh'], true);
                        $occupied = $session->is_private
                            ? $session->seatCapacity()
                            : $session->seatCapacity() - $session->remainingSeats();
                        $startTime = substr((string) $session->start_time, 0, 5);
                        $endTime = substr((string) $session->end_time, 0, 5);
                    @endphp
                    <article class="rounded-2xl border border-border dark:border-white/10 bg-surface dark:bg-gradient-to-r dark:from-surface dark:via-surface dark:to-surface-2 p-4 sm:p-5 transition hover:border-amber-400/40 shadow-sm">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-start gap-4">
                                {{-- Kotak Waktu Sesi ala XXI Clock --}}
                                <div class="w-24 shrink-0 rounded-xl border border-border dark:border-white/10 bg-surface-2 dark:bg-black/40 px-2 py-3 text-center shadow-inner">
                                    <p class="font-tabular text-xl font-black text-amber-600 dark:text-amber-400">{{ $startTime }}</p>
                                    <p class="mt-0.5 font-tabular text-[11px] font-medium text-text-muted">{{ $endTime }} WIB</p>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-base font-bold text-text truncate sm:whitespace-normal">
                                        {{ $session->film?->title ?? 'Film bebas dipilih oleh pemesan pertama' }}
                                    </h3>
                                    <p class="mt-0.5 text-xs text-text-muted">
                                        @if ($session->film)
                                            {{ $session->film->genre }}, {{ $session->film->duration_minutes }} menit, batas usia {{ $session->film->age_rating }}
                                        @else
                                            Pemesan pertama bebas menentukan film yang akan diputar
                                        @endif
                                    </p>
                                    <div class="mt-2 flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-bold border {{ $canBook ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-600 dark:text-emerald-400' : 'bg-surface-2 dark:bg-white/5 border-border dark:border-white/10 text-text-muted' }}">
                                            <span class="size-1.5 rounded-full {{ $canBook ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500' }}"></span>
                                            <span>{{ $session->is_private ? 'Sudah disewa private' : $label }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-4 sm:justify-end">
                                <div class="text-right">
                                    <p class="font-tabular text-xs font-bold text-text">
                                        {{ $occupied }} dari {{ $session->seatCapacity() }} kursi terisi
                                    </p>
                                </div>
                                @if ($canBook)
                                    <a href="{{ route('booking.create', ['session' => $session->id]) }}"
                                       @click="sessionStorage.setItem('synemax:booking-wizard', JSON.stringify({ sessionId: {{ $session->id }}, date: @js($date->toDateString()), step: 2 }))"
                                       class="btn-gold-cinema inline-flex min-h-11 min-w-28 items-center justify-center rounded-xl px-4 text-xs font-bold shadow-md">
                                        Pesan tiket
                                    </a>
                                @else
                                    <span class="inline-flex min-h-11 min-w-28 items-center justify-center rounded-xl border border-white/10 bg-white/5 px-3 text-center text-xs font-semibold text-text-muted">
                                        {{ $session->is_private ? 'Sudah disewa' : $label }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </main>

    {{-- Bottom Bar Mobile untuk Aksi Cepat --}}
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-white/10 bg-surface/95 px-4 pt-3 backdrop-blur-md lg:hidden" style="padding-bottom: max(env(safe-area-inset-bottom), .75rem)">
        <a href="#sessions" class="btn-gold-cinema mx-auto flex min-h-12 max-w-4xl items-center justify-between gap-4 rounded-xl px-4 text-black shadow-lg">
            <div>
                <span class="block text-[11px] font-semibold opacity-90">Tiket studio mulai</span>
                <span class="block font-tabular text-sm font-extrabold">Rp {{ number_format($sessionPrice, 0, ',', '.') }} per orang</span>
            </div>
            <span class="text-xs font-black">Pilih jam tayang</span>
        </a>
    </div>
@endsection
