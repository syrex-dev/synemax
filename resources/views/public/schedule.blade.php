@extends('layouts.public')

@section('title', 'Jadwal ' . $date->locale('id')->translatedFormat('l, d F'))
@section('meta')
    <meta name="description" content="Pilih jadwal nonton di studio mini SynemaX. Kapasitas tiap sesi maksimal enam kursi.">
@endsection

@section('content')
    <main class="mx-auto max-w-4xl px-4 py-6 sm:px-6 sm:py-10">
        {{-- Navigasi Kembali --}}
        <div>
            <a href="{{ route('home') }}#jadwal" class="inline-flex min-h-11 items-center text-xs font-bold text-text-muted hover:text-primary">
                Kembali ke beranda
            </a>
        </div>

        {{-- Indikator Langkah Riil --}}
        <div class="mt-4 border-b border-border pb-4">
            <p class="text-xs font-bold text-primary">Langkah 1 dari 5: Jadwal</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-text sm:text-3xl">Pilih hari dan jam nonton</h1>
            <p class="mt-1 text-xs text-text-muted">Kapasitas maksimal 6 kursi per sesi. Status kursi diperbarui otomatis saat ada pemesanan baru.</p>
        </div>

        {{-- Pemilih Tanggal --}}
        <nav class="mt-6 flex snap-x gap-2 overflow-x-auto pb-3" aria-label="Pilih tanggal nonton">
            @foreach ($dates as $availableDate)
                @php
                    $dateKey = $availableDate->toDateString();
                    $isSelected = $dateKey === $date->toDateString();
                @endphp
                <a href="{{ route('schedule.date', ['date' => $dateKey]) }}"
                   @if ($isSelected) aria-current="date" @endif
                   class="min-w-28 snap-start rounded-[var(--radius-control)] border p-3 text-center transition {{ $isSelected ? 'border-2 border-primary bg-surface shadow-xs font-bold text-text' : 'border-border-input bg-surface text-text-muted hover:border-primary hover:text-text' }}">
                    <span class="block text-xs font-bold">{{ $dateKey === $today ? 'Hari ini' : $availableDate->locale('id')->translatedFormat('l') }}</span>
                    <span class="mt-1 block font-tabular text-base font-extrabold text-text">{{ $availableDate->format('d M') }}</span>
                    @if ($holidayDates->has($dateKey))
                        <span class="mt-1 inline-block rounded-[var(--radius-control)] bg-[#FFC62E] px-1.5 py-0.2 text-[10px] font-bold text-[#16201F]">Libur</span>
                    @endif
                </a>
            @endforeach
        </nav>

        {{-- Judul Tanggal Terpilih --}}
        <div id="sessions" class="scroll-mt-8 mt-6 flex items-baseline justify-between gap-3 border-b border-border pb-2">
            <div>
                <h2 class="text-lg font-bold text-text">{{ $date->locale('id')->translatedFormat('l, d F Y') }}</h2>
            </div>
            @if ($holidayDates->has($date->toDateString()))
                <span class="rounded-[var(--radius-control)] bg-[#FFC62E] px-2 py-0.5 text-xs font-bold text-[#16201F]">Hari libur</span>
            @endif
        </div>

        {{-- Daftar Sesi Tayang --}}
        @if ($closedReason)
            <div role="status" class="mt-6 rounded-[var(--radius-control)] border border-danger-border bg-danger-surface p-5 text-sm text-danger-text">
                <p class="font-bold">Studio tutup pada tanggal ini</p>
                <p class="mt-1 text-xs text-danger-text/90">{{ $closedReason }}</p>
            </div>
        @elseif ($sessions->isEmpty())
            <div class="mt-6 rounded-[var(--radius-control)] border border-border bg-surface p-5 text-sm text-text-muted">
                Belum ada sesi penayangan untuk tanggal ini. Silakan pilih tanggal lain di atas.
            </div>
        @else
            <div class="mt-6 space-y-3 pb-24">
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
                    <article class="rounded-[var(--radius-control)] border border-border bg-surface p-4 sm:p-5">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-start gap-4">
                                {{-- Kotak Waktu Sesi --}}
                                <div class="w-24 shrink-0 rounded-[var(--radius-control)] border border-border-input bg-surface-2 px-2 py-2.5 text-center">
                                    <p class="font-tabular text-xl font-extrabold text-text">{{ $startTime }}</p>
                                    <p class="mt-0.5 font-tabular text-[11px] text-text-muted">{{ $endTime }} WIB</p>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-base font-bold text-text">{{ $session->film?->title ?? 'Film bebas dipilih oleh pemesan pertama' }}</h3>
                                    <p class="mt-0.5 text-xs text-text-muted">
                                        @if ($session->film)
                                            {{ $session->film->genre }}, {{ $session->film->duration_minutes }} menit, batas usia {{ $session->film->age_rating }}
                                        @else
                                            Pemesan pertama bebas menentukan film yang akan diputar
                                        @endif
                                    </p>
                                    <p class="mt-2 text-xs font-bold {{ $canBook ? 'text-success-text' : 'text-text-muted' }}">
                                        {{ $session->is_private ? 'Sudah disewa private' : $label }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-4 sm:justify-end">
                                <div class="text-right">
                                    <p class="font-tabular text-xs font-bold text-text">{{ $occupied }} dari {{ $session->seatCapacity() }} kursi terisi</p>
                                </div>
                                @if ($canBook)
                                    <a href="{{ route('booking.create', ['session' => $session->id]) }}"
                                       @click="sessionStorage.setItem('synemax:booking-wizard', JSON.stringify({ sessionId: {{ $session->id }}, date: @js($date->toDateString()), step: 2 }))"
                                       class="inline-flex min-h-11 min-w-28 items-center justify-center rounded-[var(--radius-control)] bg-primary px-4 text-xs font-bold text-white shadow-xs transition hover:opacity-95 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                                        Pesan tiket
                                    </a>
                                @else
                                    <span class="inline-flex min-h-11 min-w-28 items-center justify-center rounded-[var(--radius-control)] border border-border px-3 text-center text-xs font-semibold text-text-muted">
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
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-border bg-surface/95 px-4 pt-3 backdrop-blur lg:hidden" style="padding-bottom: max(env(safe-area-inset-bottom), .75rem)">
        <a href="#sessions" class="mx-auto flex min-h-12 max-w-4xl items-center justify-between gap-4 rounded-[var(--radius-control)] bg-primary px-4 text-white shadow-xs">
            <div>
                <span class="block text-[11px] font-semibold opacity-90">Tiket studio mulai</span>
                <span class="block font-tabular text-sm font-extrabold">Rp {{ number_format($sessionPrice, 0, ',', '.') }} per orang</span>
            </div>
            <span class="text-xs font-bold">Pilih jam tayang</span>
        </a>
    </div>
@endsection
