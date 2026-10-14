@extends('layouts.public')

@section('title', 'Jadwal '. $date->locale('id')->translatedFormat('l, d F'))
@section('meta')
    <meta name="description" content="Pilih jadwal nonton Home Cinema. Kapasitas tiap sesi maksimal enam kursi.">
@endsection

@section('content')
    <section class="mx-auto max-w-5xl px-4 pb-28 pt-7 sm:px-6 sm:pb-28 sm:pt-10">
        <a href="{{ route('home') }}#jadwal" class="inline-flex min-h-11 items-center text-sm font-semibold text-accent hover:underline">← Kembali ke beranda</a>
        <ol aria-label="Langkah booking" class="mt-5 grid grid-cols-5 gap-2 text-center text-xs">
            @foreach (['Jadwal', 'Film', 'Penonton', 'Jajan', 'Ringkasan'] as $stepLabel)
                <li class="rounded-xl border px-1 py-2 font-semibold {{ $loop->first ? 'border-accent bg-accent text-accent-contrast' : 'border-border bg-surface text-text-muted' }}" @if ($loop->first) aria-current="step" @endif>
                    <span class="block font-bold">{{ $loop->iteration }}</span><span class="hidden min-[380px]:inline">{{ $stepLabel }}</span><span class="min-[380px]:hidden">{{ mb_substr($stepLabel, 0, 1) }}</span>
                </li>
            @endforeach
        </ol>
        <div class="mt-7">
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-accent">Langkah 1 dari 5</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-text sm:text-4xl">Pilih hari dan jam nonton</h1>
            <p class="mt-2 text-base text-text-muted">Tiap sesi muat maksimal 6 kursi. Status kursi bisa berubah saat ada booking baru.</p>
        </div>

        <nav class="mt-6 flex snap-x gap-2 overflow-x-auto pb-3" aria-label="Pilih tanggal">
            @foreach ($dates as $availableDate)
                @php
                    $dateKey = $availableDate->toDateString();
                    $isSelected = $dateKey === $date->toDateString();
                @endphp
                <a href="{{ route('schedule.date', ['date' => $dateKey]) }}" @if ($isSelected) aria-current="date" @endif
                   class="min-w-24 snap-start rounded-xl border px-3 py-3 text-center transition {{ $isSelected ? 'border-accent bg-accent text-accent-contrast' : 'border-border bg-surface text-text hover:border-accent' }}">
                    <span class="block text-xs font-bold">{{ $dateKey === $today ? 'Hari ini' : $availableDate->locale('id')->translatedFormat('D') }}</span>
                    <span class="mt-1 block text-base font-black">{{ $availableDate->format('d M') }}</span>
                    @if ($holidayDates->has($dateKey))
                        <span class="mt-1 block text-[11px] font-semibold {{ $isSelected ? 'text-accent-contrast' : 'text-warning' }}">Libur</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div id="sessions" class="scroll-mt-24 mt-5 flex items-end justify-between gap-3">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.12em] text-accent">Jadwal sesi</p>
                <h2 class="mt-1 text-2xl font-bold text-text">{{ $date->locale('id')->translatedFormat('l, d F Y') }}</h2>
            </div>
            @if ($holidayDates->has($date->toDateString()))
                <span class="rounded-full bg-warning/15 px-3 py-2 text-sm font-bold text-warning">Hari libur</span>
            @endif
        </div>

        @if ($closedReason)
            <div role="status" class="mt-5 rounded-2xl border border-danger/30 bg-danger/10 p-5">
                <p class="font-bold text-danger">Studio tutup hari ini</p>
                <p class="mt-1 text-base text-text-muted">{{ $closedReason }}</p>
            </div>
        @elseif ($sessions->isEmpty())
            <div class="mt-5 rounded-2xl border border-border bg-surface p-5 text-base text-text-muted">Belum ada sesi untuk tanggal ini. Coba pilih hari lain, ya.</div>
        @else
            <div class="mt-5 space-y-3 scroll-mt-24 pb-24">
                @foreach ($sessions as $session)
                    @php
                        $label = $session->is_private ? 'Sewa Private' : $session->availabilityLabel($bookingCutoffMinutes);
                        $canBook = ! $session->is_private
                            && ! in_array($label, ['Tutup', 'Sudah lewat', 'Penuh'], true);
                        $occupied = $session->is_private
                            ? $session->seatCapacity()
                            : $session->seatCapacity() - $session->remainingSeats();
                        $stateDot = match (true) {
                            $session->is_private => 'bg-text-muted',
                            $label === 'Tersedia' => 'bg-success',
                            str_starts_with($label, 'Sisa') => 'bg-accent',
                            $label === 'Penuh' => 'bg-danger',
                            default => 'bg-text-muted',
                        };
                    @endphp
                    <article class="rounded-2xl border border-border bg-surface p-4 shadow-token sm:p-5">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 gap-4">
                                <div class="w-24 shrink-0 rounded-xl bg-surface-2 px-2 py-3 text-center">
                                    <p class="text-2xl font-black text-accent">{{ substr((string) $session->start_time, 0, 5) }}</p>
                                    <p class="mt-1 text-xs text-text-muted">{{ substr((string) $session->end_time, 0, 5) }} selesai</p>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-text">{{ $session->film?->title ?? 'Film bebas dipilih' }}</p>
                                    <p class="mt-1 text-sm text-text-muted">
                                        @if ($session->film)
                                            {{ $session->film->genre }} · {{ $session->film->duration_minutes }} menit
                                        @else
                                            Pemesan pertama memilih film
                                        @endif
                                    </p>
                                    <p class="mt-2 inline-flex items-center gap-2 text-sm font-bold {{ $canBook ? 'text-success' : 'text-text-muted' }}">
                                        <span class="size-2 rounded-full {{ $stateDot }}" aria-hidden="true"></span>
                                        <span>{{ $label }}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-4 sm:justify-end">
                                <div>
                                    <div class="flex gap-1.5" aria-label="{{ $occupied }} kursi terisi dari {{ $session->seatCapacity() }}">
                                        @for ($seat = 1; $seat <= $session->seatCapacity(); $seat++)
                                            <span class="inline-flex size-7 items-center justify-center rounded-md {{ $seat <= $occupied ? 'bg-danger/15 text-danger' : 'bg-surface-2 text-text-muted' }}" role="img" aria-label="{{ $seat <= $occupied ? 'Terisi' : 'Kosong' }}">▰</span>
                                        @endfor
                                    </div>
                                    <p class="mt-1 text-xs text-text-muted">{{ $occupied }} dari {{ $session->seatCapacity() }} kursi terisi</p>
                                </div>
                                @if ($canBook)
                                    <a href="{{ route('booking.create', ['session' => $session->id]) }}"
                                       @click="sessionStorage.setItem('synemax:booking-wizard', JSON.stringify({ sessionId: {{ $session->id }}, date: @js($date->toDateString()), step: 2 }))"
                                       class="inline-flex min-h-12 min-w-0 items-center justify-center rounded-xl bg-accent px-2 text-sm font-bold text-accent-contrast hover:opacity-90 sm:min-w-28 sm:px-4">Pilih sesi</a>
                                @else
                                    <span class="inline-flex min-h-12 min-w-28 items-center justify-center rounded-xl border border-border px-3 text-center text-sm font-semibold text-text-muted">{{ $session->is_private ? 'Sudah disewa' : $label }}</span>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-border bg-surface/95 px-4 pt-3 shadow-token backdrop-blur lg:hidden" style="padding-bottom: max(env(safe-area-inset-bottom), .75rem)">
        <a href="#sessions" class="mx-auto flex min-h-12 max-w-5xl items-center justify-between gap-4 rounded-xl bg-accent px-4 text-accent-contrast">
            <span><span class="block text-xs font-semibold opacity-90">Tiket mulai</span><span class="block text-base font-black">Rp {{ number_format($sessionPrice, 0, ',', '.') }} / orang</span></span>
            <span class="text-sm font-bold">Pilih sesi ↑</span>
        </a>
    </div>
@endsection
