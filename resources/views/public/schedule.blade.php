@extends('layouts.public')

@section('title', 'Jadwal ' . $date->locale('id')->translatedFormat('l, d F'))
@section('meta')
    <meta name="description" content="Pilih jadwal nonton di studio mini SynemaX. Kapasitas tiap sesi maksimal enam tempat.">
@endsection

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 sm:py-10">
        {{-- Indikator Langkah Riil --}}
        <div class="mt-4 border-b border-border dark:border-white/10 pb-4">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-amber-400 animate-pulse"></span>
                <p class="text-xs font-bold uppercase tracking-wider text-amber-500 dark:text-amber-400">Langkah 1 dari 5: Jadwal</p>
            </div>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-text sm:text-3xl">Pilih hari dan jam nonton</h1>
            <p class="mt-1 text-xs text-text-muted">Kapasitas maksimal 6 penonton per sesi. Ketersediaan tempat diperbarui otomatis saat ada pemesanan baru.</p>
        </div>

        {{-- Film Pilihan Banner (Jika pengguna memilih film terlebih dahulu) --}}
        @if ($selectedFilm)
            <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3.5 rounded-2xl border border-amber-500/30 bg-amber-500/10 p-3.5 sm:p-4">
                <div class="flex items-center gap-3.5 min-w-0">
                    <div class="w-11 h-16 shrink-0 rounded-lg overflow-hidden bg-slate-800 border border-white/10 relative">
                        @if ($selectedFilm->posterUrl())
                            <img src="{{ $selectedFilm->posterUrl() }}" alt="{{ $selectedFilm->title }}" class="size-full object-cover" onerror="this.style.display='none'">
                        @else
                            <div class="flex size-full items-center justify-center text-[10px] text-amber-200/80 font-bold p-1 text-center">
                                {{ Str::limit($selectedFilm->title, 12) }}
                            </div>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">Film yang kamu pilih:</p>
                        <h2 class="text-sm sm:text-base font-black text-text truncate">{{ $selectedFilm->title }}</h2>
                        <p class="text-xs text-text-muted truncate">
                            {{ $selectedFilm->formattedGenresText(2) }} · {{ format_durasi_id($selectedFilm->duration_minutes) }} @if($selectedFilm->age_rating)· {{ $selectedFilm->age_rating }}@endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('films.index') }}"
                       class="inline-flex min-h-9 items-center justify-center rounded-xl border border-border bg-surface px-3 py-1.5 text-xs font-bold text-text hover:bg-surface-2 transition">
                        Ganti film
                    </a>
                </div>
            </div>
        @endif

        {{-- Pemilih Tanggal (Horizontal Scrollable Pills dengan Fade Tepi) --}}
        <div class="relative -mx-4 px-4 sm:mx-0 sm:px-0">
            <nav class="mt-6 flex snap-x gap-2 overflow-x-auto pb-3 scrollbar-none scroll-smooth" style="touch-action: pan-x pan-y pinch-zoom; -webkit-overflow-scrolling: touch;" aria-label="Pilih tanggal nonton">
                @foreach ($dates as $availableDate)
                    @php
                        $dateKey = $availableDate->toDateString();
                        $isSelected = $dateKey === $date->toDateString();
                    @endphp
                    <a href="{{ route('schedule.date', array_filter(['date' => $dateKey, 'film' => $selectedFilm?->id])) }}"
                       @if ($isSelected) aria-current="date" @endif
                       class="min-w-24 shrink-0 snap-start rounded-xl border p-2.5 text-center transition-colors {{ $isSelected ? 'border-amber-500 bg-amber-500/15 text-text font-bold' : 'border-border bg-surface text-text-muted hover:border-amber-400/50 hover:text-text' }}">
                        <span class="block text-[11px] font-bold uppercase tracking-wider {{ $isSelected ? 'text-amber-700 dark:text-amber-400' : 'text-text-muted' }}">
                            {{ $dateKey === $today ? 'Hari ini' : $availableDate->locale('id')->translatedFormat('l') }}
                        </span>
                        <span class="mt-0.5 block font-tabular text-base font-black {{ $isSelected ? 'text-text' : 'text-text' }}">
                            {{ format_tanggal_pendek_id($availableDate) }}
                        </span>
                        @if ($holidayDates->has($dateKey))
                            <span class="mt-1 inline-block rounded bg-amber-400/20 px-1.5 py-0.5 text-[9px] font-black uppercase text-amber-700 dark:text-amber-300 border border-amber-400/30">
                                Libur
                            </span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>

        {{-- Judul Tanggal Terpilih --}}
        <div id="sessions" class="scroll-mt-8 mt-6 flex items-baseline justify-between gap-3 border-b border-border pb-2">
            <div>
                <h2 class="text-lg font-black text-text">{{ format_hari_tanggal_id($date) }}</h2>
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
            {{-- Empty State Ramah: Jadwal Sedang Disiapkan --}}
            <div class="mt-6 rounded-2xl border border-border bg-surface p-8 text-center space-y-4">
                <div class="max-w-md mx-auto space-y-1.5">
                    <p class="text-base font-bold text-text">Jadwal sedang disiapkan</p>
                    <p class="text-xs text-text-muted leading-relaxed">
                        Sesi penayangan untuk tanggal ini belum dibuka atau sedang dalam penyesuaian jadwal operator. Anda dapat menanyakan ketersediaan studio langsung ke admin atau memeriksa booking Anda.
                    </p>
                </div>
                <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                    @if (setting('whatsapp_number'))
                        <a
                            href="https://wa.me/{{ preg_replace('/[^0-9]/', '', setting('whatsapp_number')) }}?text={{ urlencode('Halo ' . setting('site_name', 'SynemaX') . ', saya ingin bertanya mengenai ketersediaan jadwal penayangan pada ' . format_hari_tanggal_id($date) . '.') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-emerald-700 hover:bg-emerald-600 text-white px-4 py-2 text-xs font-bold shadow-xs"
                        >
                            <span>Tanya Admin via WA</span>
                        </a>
                    @endif
                    <a
                        href="{{ route('booking.ticket.index') }}"
                        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-bold text-text hover:bg-surface-2"
                    >
                        <span>Cek Booking Saya</span>
                    </a>
                </div>
            </div>
        @else
            @php
                $upcomingSessions = $sessions->filter(fn($s) => ! $s->statusWaktu($bookingCutoffMinutes)['is_past']);
                $pastSessions = $sessions->filter(fn($s) => $s->statusWaktu($bookingCutoffMinutes)['is_past']);
            @endphp

            <div class="mt-6 space-y-3.5 pb-24">
                @if ($upcomingSessions->isEmpty())
                    <div class="rounded-2xl border border-dashed border-border bg-surface-2 p-5 text-center text-xs text-text-muted">
                        Semua sesi penayangan untuk hari ini telah selesai atau sedang berlangsung. Silakan cek bagian sesi yang sudah lewat di bawah atau pilih tanggal lain di atas.
                    </div>
                @else
                    @foreach ($upcomingSessions as $session)
                        @php
                            $sw = $session->statusWaktu($bookingCutoffMinutes);
                            $canBook = $sw['can_book'];
                            $occupied = $session->is_private
                                ? $session->seatCapacity()
                                : $session->seatCapacity() - $session->remainingSeats();
                            $startTime = format_jam_id($session->start_time, false);
                            $endTime = format_jam_id($session->end_time, true);
                        @endphp
                        @php
                            $sessionFilmId = $session->film_id ?: $session->activeFilm()?->id;
                            $isLockedOtherFilm = $selectedFilm && ($session->isConfirmed() || $session->status === 'terkonfirmasi') && $sessionFilmId && (int) $sessionFilmId !== (int) $selectedFilm->id;
                            $canBookThisSession = $canBook && ! $isLockedOtherFilm;
                        @endphp
                        <article class="rounded-2xl border border-border bg-surface p-4 sm:p-5 transition hover:border-amber-500 shadow-2xs {{ $isLockedOtherFilm ? 'opacity-60 bg-surface/50 border-dashed hover:border-border' : '' }}">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex min-w-0 items-start gap-4">
                                    {{-- Kotak Waktu Sesi ala XXI Clock --}}
                                    <div class="w-24 shrink-0 rounded-xl border border-border dark:border-white/10 bg-surface-2 dark:bg-black/40 px-2 py-3 text-center shadow-inner">
                                        <p class="font-tabular text-xl font-black text-amber-600 dark:text-amber-400">{{ $startTime }}</p>
                                        <p class="mt-0.5 font-tabular text-[11px] font-medium text-text-muted">{{ $endTime }}</p>
                                    </div>
                                    <div class="min-w-0">
                                        @php
                                            $confirmed = $session->isConfirmed();
                                            $activeFilm = $session->activeFilm();
                                            $leadingTally = $session->leadingFilmTally();
                                            $quorum = $session->quorum();
                                        @endphp

                                        <h3 class="text-base font-bold text-text truncate sm:whitespace-normal">
                                            @if ($session->film)
                                                {{ $session->film->title }}
                                            @elseif ($leadingTally && $leadingTally['film'])
                                                {{ $leadingTally['title'] }} <span class="text-xs font-normal text-text-muted">(Calon terdepan)</span>
                                            @else
                                                <span class="italic text-text-muted font-medium">Belum ada film, kamu yang menentukan</span>
                                            @endif
                                        </h3>

                                        <p class="mt-0.5 text-xs text-text-muted">
                                            @if ($activeFilm)
                                                {{ $activeFilm->genre }}, {{ $activeFilm->duration_minutes }} menit, batas usia {{ $activeFilm->age_rating }}
                                            @else
                                                Pilih paket dan tentukan film pertama di sesi ini
                                            @endif
                                        </p>

                                        <div class="mt-2.5 flex flex-wrap items-center gap-2">
                                            @if ($confirmed)
                                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-bold border border-emerald-500/40 bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
                                                    <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    <span>Pasti tayang: {{ $session->film?->title }}</span>
                                                </span>
                                            @elseif ($leadingTally)
                                                <div class="inline-flex items-center gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-2.5 py-1 text-[11px] font-bold text-amber-700 dark:text-amber-400">
                                                    <span>{{ $leadingTally['title'] }} {{ $leadingTally['seats'] }}/{{ $quorum }} · butuh {{ $leadingTally['needed_seats'] }} lagi</span>
                                                    <div class="w-12 h-1.5 bg-amber-200 dark:bg-white/20 rounded-full overflow-hidden">
                                                        <div class="h-full bg-amber-500 rounded-full" style="width: {{ min(100, round(($leadingTally['seats'] / $quorum) * 100)) }}%"></div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] border {{ $sw['badge_class'] }}">
                                                    <span class="size-1.5 rounded-full {{ $canBook ? 'bg-emerald-400 animate-pulse' : 'bg-slate-400' }}"></span>
                                                    <span>{{ $sw['label'] }}</span>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between gap-4 sm:justify-end">
                                    <div class="text-right">
                                        <p class="font-tabular text-xs font-bold text-text">
                                            {{ $session->remainingSeats() }} slot tersisa
                                        </p>
                                        <p class="text-[11px] text-text-muted">
                                            ({{ $occupied }} dari {{ $session->seatCapacity() }} terisi)
                                        </p>
                                    </div>

                                    @if ($canBookThisSession)
                                        @php
                                            $btnLabel = match (true) {
                                                $selectedFilm !== null => 'Pilih jam ini',
                                                $confirmed => 'Pesan tiket',
                                                $leadingTally !== null => 'Gabung film ini',
                                                default => 'Pilih paket',
                                            };
                                        @endphp
                                        <a href="{{ route('booking.create', array_filter(['session' => $session->id, 'film' => $selectedFilm?->id ?: request('film')])) }}"
                                           @click="sessionStorage.setItem('synemax:booking-wizard', JSON.stringify({ sessionId: {{ $session->id }}, date: @js($date->toDateString()), step: 1 }))"
                                           class="btn-gold-cinema inline-flex min-h-11 min-w-28 items-center justify-center rounded-xl px-4 text-xs font-bold shadow-md">
                                            {{ $btnLabel }}
                                        </a>
                                    @else
                                        <span class="inline-flex min-h-11 min-w-36 items-center justify-center rounded-xl border border-border dark:border-white/10 bg-surface-2 dark:bg-white/5 px-3 text-center text-xs font-semibold text-text-muted">
                                            {{ $isLockedOtherFilm ? 'Sesi ini sudah memilih film lain' : $sw['label'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                @endif

                {{-- Sesi yang Sudah Lewat (Diciutkan di Bawah, Tanpa Tombol Pesan) --}}
                @if ($pastSessions->isNotEmpty())
                    <details class="group mt-8 rounded-2xl border border-border dark:border-white/10 bg-surface-2/40 dark:bg-white/[0.02] p-4 transition shadow-xs">
                        <summary class="flex cursor-pointer items-center justify-between font-bold text-sm text-text-muted hover:text-text select-none">
                            <div class="flex items-center gap-2">
                                <span class="size-2 rounded-full bg-slate-400"></span>
                                <span>Sesi yang sudah lewat ({{ $pastSessions->count() }})</span>
                            </div>
                            <svg class="size-4 text-text-muted transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>

                        <div class="mt-4 space-y-3">
                            @foreach ($pastSessions as $session)
                                @php
                                    $sw = $session->statusWaktu($bookingCutoffMinutes);
                                    $occupied = $session->is_private
                                        ? $session->seatCapacity()
                                        : $session->seatCapacity() - $session->remainingSeats();
                                    $startTime = format_jam_id($session->start_time, false);
                                    $endTime = format_jam_id($session->end_time, true);
                                    $activeFilm = $session->activeFilm();
                                @endphp
                                <article class="rounded-xl border border-border/80 dark:border-white/5 bg-surface/60 dark:bg-black/20 p-3.5 opacity-75">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <div class="w-20 shrink-0 rounded-lg border border-border/60 dark:border-white/5 bg-surface-2/80 dark:bg-black/40 px-2 py-1.5 text-center">
                                                <p class="font-tabular text-sm font-bold text-text-muted">{{ $startTime }}</p>
                                                <p class="font-tabular text-[10px] text-text-muted">{{ $endTime }}</p>
                                            </div>
                                            <div class="min-w-0">
                                                <h4 class="text-sm font-semibold text-text truncate">
                                                    {{ $activeFilm ? $activeFilm->title : 'Sesi Film' }}
                                                </h4>
                                                <div class="mt-1 flex items-center gap-2">
                                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[10px] border {{ $sw['badge_class'] }}">
                                                        <span>{{ $sw['label'] }}</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between sm:justify-end gap-3 text-xs text-text-muted">
                                            <span class="text-[11px]">
                                                {{ $occupied }}/{{ $session->seatCapacity() }} terisi
                                            </span>
                                            <span class="inline-flex min-h-8 items-center justify-center rounded-lg border border-border/60 dark:border-white/10 bg-surface-2/60 dark:bg-white/5 px-2.5 text-[11px] font-semibold text-text-muted">
                                                {{ $sw['label'] }}
                                            </span>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </details>
                @endif
            </div>
        @endif
    </div>
@endsection
