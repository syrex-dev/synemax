@extends('layouts.admin')

@section('title', 'Loket Pemesanan di Tempat (Walk-in)')

@section('content')
@php
    $sessionData = $sessions->map(function ($s) use ($settings) {
        $dayType = app(\App\Services\ScheduleResolver::class)->dayTypeFor($s->startsAt()) ?? 'biasa';
        return [
            'id' => $s->id,
            'date' => is_string($s->session_date) ? $s->session_date : $s->session_date->format('Y-m-d'),
            'date_formatted' => format_hari_tanggal_id($s->session_date),
            'time_formatted' => format_jam_id($s->start_time),
            'film_id' => $s->film_id,
            'film_title' => $s->film?->title,
            'film_poster' => $s->film?->poster_url,
            'is_confirmed' => $s->isConfirmed(),
            'remaining_seats' => $s->remainingSeats(),
            'capacity' => $s->seatCapacity(),
            'is_private_active' => $s->isPrivateActive(),
            'regular_price' => $settings->ticketPriceForDayType($dayType),
            'couple_price' => $settings->couplePrice($dayType),
            'private_price' => $settings->privatePriceForDayType($dayType),
        ];
    });

    $filmsData = $films->map(fn ($f) => [
        'id' => $f->id,
        'title' => $f->title,
        'poster_url' => $f->poster_url,
        'duration_minutes' => $f->duration_minutes,
        'age_rating' => $f->age_rating,
    ]);

    $initialSession = $selectedSession ? $sessionData->firstWhere('id', $selectedSession->id) : $sessionData->first();
@endphp

<div
    x-data="walkInPos({
        sessions: {{ Js::from($sessionData) }},
        films: {{ Js::from($filmsData) }},
        initialSessionId: {{ $initialSession['id'] ?? 'null' }},
        lookupUrl: '{{ route('admin.bookings.member_lookup') }}',
        adminWhatsapp: '{{ $settings->string('admin_whatsapp') ?: '080000000000' }}'
    })"
    class="space-y-6"
>
    {{-- Header & Breadcrumb --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-medium text-text-muted">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-text transition">Pengelola</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('admin.bookings.index') }}" class="hover:text-text transition">Booking</a>
                <span aria-hidden="true">/</span>
                <span class="font-bold text-text" aria-current="page">Loket Pemesanan di Tempat</span>
            </nav>
            <h1 class="mt-2 text-2xl sm:text-3xl font-black text-text tracking-tight flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-amber-500/15 text-amber-500">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </span>
                <span>Loket Pemesanan di Tempat</span>
            </h1>
            <p class="mt-1 text-xs sm:text-sm text-text-muted">
                Kasir & pemesanan langsung untuk anak-anak dan penonton walk-in tanpa ponsel atau akun web.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.bookings.index') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-border bg-surface px-4 py-2 text-xs sm:text-sm font-semibold text-text hover:bg-surface-2 transition">
                <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                <span>Daftar Booking</span>
            </a>
        </div>
    </div>

    {{-- Banner Notifikasi Transaksi Terakhir Berhasil --}}
    @if ($lastBooking)
        <div class="relative overflow-hidden rounded-2xl border-2 border-emerald-500/30 bg-emerald-500/10 p-5 dark:bg-emerald-950/30">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-sm">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Transaksi Loket Berhasil</span>
                            <span class="rounded-full bg-emerald-500/20 px-2 py-0.5 text-xs font-bold text-emerald-700 dark:text-emerald-300 font-mono">{{ $lastBooking['code'] }}</span>
                            @if ($lastBooking['attended'] ?? false)
                                <span class="rounded-full bg-blue-500/20 px-2 py-0.5 text-xs font-bold text-blue-700 dark:text-blue-300">Langsung Hadir</span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm font-bold text-text">
                            Pemesan: <span class="text-accent">{{ $lastBooking['customer_name'] }}</span>
                            @if (!empty($lastBooking['member_code']))
                                <span class="text-xs text-text-muted">(Member: {{ $lastBooking['member_code'] }})</span>
                            @endif
                        </p>
                        <div class="mt-1 flex flex-wrap items-center gap-4 text-xs font-medium text-text-muted">
                            <span>Total: <strong class="text-text font-bold">{{ $lastBooking['total_price_formatted'] }}</strong></span>
                            @if (!empty($lastBooking['cash_received']) && $lastBooking['cash_received'] > 0)
                                <span>Uang Tunai: <strong class="text-text font-bold">{{ $lastBooking['cash_received_formatted'] }}</strong></span>
                                <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-black text-sm">
                                    Kembalian: {{ $lastBooking['change_amount_formatted'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-end sm:self-center">
                    <a
                        href="{{ $lastBooking['ticket_url'] }}"
                        target="_blank"
                        class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs sm:text-sm font-bold text-white shadow-sm hover:bg-emerald-500 active:scale-95 transition"
                    >
                        <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Cetak Tiket / Struk</span>
                    </a>
                    <a
                        href="{{ $lastBooking['show_url'] }}"
                        class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-border bg-surface px-3 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition"
                    >
                        <span>Detail</span>
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- Error validation alerts --}}
    @if ($errors->any())
        <div class="rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-xs sm:text-sm text-rose-600 dark:text-rose-400">
            <p class="font-bold">Mohon periksa data formulir berikut:</p>
            <ul class="mt-1 list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form Utama Loket Walk-in --}}
    <form method="POST" action="{{ route('admin.bookings.store') }}" @submit="handleSubmit($event)">
        @csrf

        {{-- Hidden bindings --}}
        <input type="hidden" name="session_id" :value="selectedSessionId">
        <input type="hidden" name="package" :value="packageType">
        <input type="hidden" name="pax" :value="pax">
        <input type="hidden" name="film_id" :value="resolvedFilmId">
        <input type="hidden" name="member_code" :value="memberFound ? memberFound.code : ''">
        <input type="hidden" name="apply_member_reward" :value="applyMemberReward ? 1 : 0">
        <input type="hidden" name="cash_received" :value="cashReceivedNumeric">
        <input type="hidden" name="mark_as_attended" :value="markAsAttended ? 1 : 0">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            {{-- KOLOM KIRI: Input Form (8 kolom) --}}
            <div class="lg:col-span-7 xl:col-span-8 space-y-6">

                {{-- LANGKAH 1: PILIH SESI NONTON --}}
                <div class="rounded-2xl border border-border bg-surface p-5 shadow-xs">
                    <div class="flex items-center justify-between pb-3 border-b border-border/60">
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-7 items-center justify-center rounded-lg bg-amber-500 text-slate-950 font-black text-xs">1</span>
                            <h2 class="text-base font-black text-text">Pilih Sesi Tayang</h2>
                        </div>
                        <span class="text-xs text-text-muted">Hari ini & jadwal aktif</span>
                    </div>

                    @if ($sessions->isEmpty())
                        <div class="mt-4 rounded-xl border border-dashed border-border p-6 text-center text-sm text-text-muted">
                            Belum ada jadwal sesi yang aktif untuk hari ini.
                            <div class="mt-2">
                                <a href="{{ route('admin.schedule.index') }}" class="font-bold text-accent hover:underline">+ Buka Kelola Sesi</a>
                            </div>
                        </div>
                    @else
                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                            <template x-for="ses in sessions" :key="ses.id">
                                <button
                                    type="button"
                                    @click="selectSession(ses.id)"
                                    :disabled="ses.is_private_active || ses.remaining_seats <= 0"
                                    :class="{
                                        'border-amber-500 bg-amber-500/10 ring-2 ring-amber-500/30 text-text': selectedSessionId === ses.id,
                                        'border-border bg-bg/50 hover:bg-surface-2 text-text opacity-50 cursor-not-allowed': ses.is_private_active || ses.remaining_seats <= 0,
                                        'border-border bg-surface hover:border-amber-500/50 hover:bg-surface-2 text-text': selectedSessionId !== ses.id && !ses.is_private_active && ses.remaining_seats > 0
                                    }"
                                    class="relative flex flex-col p-3.5 rounded-xl border text-left transition"
                                >
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-base font-black tracking-tight" x-text="ses.time_formatted"></span>
                                        <span
                                            :class="{
                                                'bg-rose-500/15 text-rose-500': ses.remaining_seats <= 0 || ses.is_private_active,
                                                'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400': ses.remaining_seats > 2,
                                                'bg-amber-500/15 text-amber-600 dark:text-amber-400': ses.remaining_seats <= 2 && ses.remaining_seats > 0
                                            }"
                                            class="rounded-md px-1.5 py-0.5 text-[11px] font-bold"
                                        >
                                            <span x-show="ses.is_private_active">Privat</span>
                                            <span x-show="!ses.is_private_active && ses.remaining_seats <= 0">Penuh</span>
                                            <span x-show="!ses.is_private_active && ses.remaining_seats > 0" x-text="'Sisa ' + ses.remaining_seats + ' orang'"></span>
                                        </span>
                                    </div>

                                    <div class="mt-1 text-xs text-text-muted truncate" x-text="ses.date_formatted"></div>

                                    <div class="mt-2 text-xs font-semibold truncate flex items-center gap-1.5">
                                        <span x-show="ses.is_confirmed" class="size-2 rounded-full bg-emerald-500 shrink-0"></span>
                                        <span x-show="!ses.is_confirmed" class="size-2 rounded-full bg-amber-500 shrink-0"></span>
                                        <span class="truncate" x-text="ses.film_title || 'Bebas Pilih Film'"></span>
                                    </div>
                                </button>
                            </template>
                        </div>
                    @endif
                </div>

                {{-- LANGKAH 2: PAKET & JUMLAH PENONTON --}}
                <div class="rounded-2xl border border-border bg-surface p-5 shadow-xs">
                    <div class="flex items-center justify-between pb-3 border-b border-border/60">
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-7 items-center justify-center rounded-lg bg-amber-500 text-slate-950 font-black text-xs">2</span>
                            <h2 class="text-base font-black text-text">Paket & Jumlah Penonton</h2>
                        </div>
                        <span class="text-xs text-text-muted">Kapasitas maksimal 6 orang</span>
                    </div>

                    {{-- Pilihan Paket --}}
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                        {{-- Reguler --}}
                        <label
                            :class="{
                                'border-amber-500 bg-amber-500/10 ring-2 ring-amber-500/30': packageType === 'reguler',
                                'border-border bg-bg/50 hover:bg-surface-2': packageType !== 'reguler'
                            }"
                            class="flex flex-col p-3.5 rounded-xl border cursor-pointer transition"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-text">Reguler</span>
                                <input type="radio" name="package_radio" value="reguler" x-model="packageType" class="text-amber-500 focus:ring-amber-500">
                            </div>
                            <span class="mt-1 text-xs text-text-muted">Per orang (1 - 6 orang)</span>
                            <span class="mt-2 text-xs font-bold text-amber-600 dark:text-amber-400" x-text="formatRupiah(currentSession?.regular_price || 5000) + ' / orang'"></span>
                        </label>

                        {{-- Couple --}}
                        <label
                            :class="{
                                'border-amber-500 bg-amber-500/10 ring-2 ring-amber-500/30': packageType === 'couple',
                                'border-border bg-bg/50 hover:bg-surface-2': packageType !== 'couple',
                                'opacity-50 cursor-not-allowed': currentSession && currentSession.remaining_seats < 2
                            }"
                            class="flex flex-col p-3.5 rounded-xl border cursor-pointer transition"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-text">Paket Couple</span>
                                <input type="radio" name="package_radio" value="couple" x-model="packageType" :disabled="currentSession && currentSession.remaining_seats < 2" class="text-amber-500 focus:ring-amber-500">
                            </div>
                            <span class="mt-1 text-xs text-text-muted">Pas untuk 2 orang</span>
                            <span class="mt-2 text-xs font-bold text-amber-600 dark:text-amber-400" x-text="formatRupiah(currentSession?.couple_price || 10000)"></span>
                        </label>

                        {{-- Privat --}}
                        <label
                            :class="{
                                'border-amber-500 bg-amber-500/10 ring-2 ring-amber-500/30': packageType === 'private',
                                'border-border bg-bg/50 hover:bg-surface-2': packageType !== 'private',
                                'opacity-50 cursor-not-allowed': currentSession && currentSession.remaining_seats < currentSession.capacity
                            }"
                            class="flex flex-col p-3.5 rounded-xl border cursor-pointer transition"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-text">Sewa Privat</span>
                                <input type="radio" name="package_radio" value="private" x-model="packageType" :disabled="currentSession && currentSession.remaining_seats < currentSession.capacity" class="text-amber-500 focus:ring-amber-500">
                            </div>
                            <span class="mt-1 text-xs text-text-muted">1 Studio penuh (maks 6 orang)</span>
                            <span class="mt-2 text-xs font-bold text-amber-600 dark:text-amber-400" x-text="formatRupiah(currentSession?.private_price || 30000)"></span>
                        </label>
                    </div>

                    {{-- Tombol Cepat Jumlah Orang (Khusus Reguler) --}}
                    <div x-show="packageType === 'reguler'" class="mt-4 pt-4 border-t border-border/60">
                        <label class="block text-xs font-bold uppercase tracking-wider text-text-muted mb-2">Jumlah Orang (Anak)</label>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="n in 6" :key="n">
                                <button
                                    type="button"
                                    @click="pax = n"
                                    :disabled="currentSession && n > currentSession.remaining_seats"
                                    :class="{
                                        'bg-amber-500 text-slate-950 font-black shadow-sm ring-2 ring-amber-500/30': pax === n,
                                        'bg-bg border border-border text-text hover:bg-surface-2': pax !== n && (!currentSession || n <= currentSession.remaining_seats),
                                        'opacity-40 cursor-not-allowed bg-bg/40 text-text-muted': currentSession && n > currentSession.remaining_seats
                                    }"
                                    class="size-11 sm:w-16 rounded-xl flex items-center justify-center text-sm font-bold transition"
                                >
                                    <span x-text="n + ' Org'"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- LANGKAH 3: PILIH FILM --}}
                <div class="rounded-2xl border border-border bg-surface p-5 shadow-xs">
                    <div class="flex items-center justify-between pb-3 border-b border-border/60">
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-7 items-center justify-center rounded-lg bg-amber-500 text-slate-950 font-black text-xs">3</span>
                            <h2 class="text-base font-black text-text">Pilihan Film</h2>
                        </div>
                        <span x-show="isSessionFilmLocked" class="rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                            Terkunci oleh Sesi
                        </span>
                        <span x-show="!isSessionFilmLocked" class="text-xs text-text-muted">
                            Pilih judul film yang ingin ditonton
                        </span>
                    </div>

                    {{-- Kasus 1: Sesi sudah terkunci filmnya --}}
                    <div x-show="isSessionFilmLocked" class="mt-4 flex items-center gap-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3.5">
                        <div class="size-14 rounded-lg bg-surface-2 overflow-hidden shrink-0 border border-border">
                            <template x-if="currentSession && currentSession.film_poster">
                                <img :src="currentSession.film_poster" :alt="currentSession.film_title" class="size-full object-cover">
                            </template>
                            <template x-if="!currentSession || !currentSession.film_poster">
                                <div class="size-full flex items-center justify-center text-text-muted text-xs">No Poster</div>
                            </template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Film Terpilih untuk Sesi Ini</p>
                            <h3 class="text-base font-black text-text truncate" x-text="currentSession?.film_title || 'Film'"></h3>
                            <p class="text-xs text-text-muted mt-0.5">Penonton walk-in akan digabungkan pada film yang sudah terkonfirmasi.</p>
                        </div>
                    </div>

                    {{-- Kasus 2: Sesi masih bebas pilih film --}}
                    <div x-show="!isSessionFilmLocked" class="mt-4 space-y-3">
                        <div class="relative">
                            <input
                                type="text"
                                x-model="filmSearch"
                                placeholder="Cari judul film..."
                                class="min-h-11 w-full rounded-xl border border-border bg-bg px-3.5 text-sm text-text placeholder:text-text-muted focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                            >
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 max-h-72 overflow-y-auto pr-1">
                            <template x-for="film in filteredFilms" :key="film.id">
                                <button
                                    type="button"
                                    @click="selectedFilmId = film.id"
                                    :class="{
                                        'border-amber-500 bg-amber-500/10 ring-2 ring-amber-500/30': selectedFilmId === film.id,
                                        'border-border bg-bg/50 hover:bg-surface-2': selectedFilmId !== film.id
                                    }"
                                    class="flex flex-col p-2.5 rounded-xl border text-left transition group"
                                >
                                    <div class="aspect-[2/3] w-full rounded-lg bg-surface-2 overflow-hidden border border-border/60 mb-2">
                                        <template x-if="film.poster_url">
                                            <img :src="film.poster_url" :alt="film.title" class="size-full object-cover group-hover:scale-105 transition duration-200">
                                        </template>
                                        <template x-if="!film.poster_url">
                                            <div class="size-full flex items-center justify-center text-text-muted text-[11px] text-center p-2">Sinema</div>
                                        </template>
                                    </div>
                                    <h4 class="text-xs font-bold text-text truncate" x-text="film.title"></h4>
                                    <span class="text-[10px] text-text-muted" x-text="(film.duration_minutes ? film.duration_minutes + ' mnt' : '') + (film.age_rating ? ' · ' + film.age_rating : '')"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- LANGKAH 4: DATA PEMESAN & KARTU MEMBER (ANAK-ANAK) --}}
                <div class="rounded-2xl border border-border bg-surface p-5 shadow-xs">
                    <div class="flex items-center justify-between pb-3 border-b border-border/60">
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-7 items-center justify-center rounded-lg bg-amber-500 text-slate-950 font-black text-xs">4</span>
                            <h2 class="text-base font-black text-text">Data Penonton & Kartu Member</h2>
                        </div>
                        <span class="text-xs text-text-muted">Nomor HP bersifat opsional</span>
                    </div>

                    {{-- Pindai / Cari Kartu Member --}}
                    <div class="mt-4 rounded-xl border border-amber-500/30 bg-amber-500/5 p-4">
                        <label class="block text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 mb-1.5">
                            Punya Kartu Member? (Scan Barcode / Ketik Kode / Cari Nama)
                        </label>
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <input
                                    type="text"
                                    x-model="memberQuery"
                                    @keydown.enter.prevent="lookupMember()"
                                    placeholder="Contoh: MBR-ZRGH-V4B9-Q atau nama anak..."
                                    class="min-h-11 w-full rounded-xl border border-border bg-surface px-3.5 text-sm text-text placeholder:text-text-muted focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                                >
                            </div>
                            <button
                                type="button"
                                @click="lookupMember()"
                                :disabled="memberSearching || !memberQuery.trim()"
                                class="inline-flex min-h-11 items-center gap-1.5 rounded-xl bg-amber-500 px-4 text-xs font-bold text-slate-950 hover:bg-amber-400 active:scale-95 transition disabled:opacity-50"
                            >
                                <svg x-show="!memberSearching" class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <span x-show="memberSearching">Mencari...</span>
                                <span x-show="!memberSearching">Cari Member</span>
                            </button>
                        </div>

                        {{-- Keterangan Not Found --}}
                        <div x-show="memberNotFound" class="mt-2 text-xs font-semibold text-rose-500">
                            Member tidak ditemukan. Periksa kode atau ketik nama anak secara manual di bawah.
                        </div>

                        {{-- Kartu Member Terdeteksi --}}
                        <div x-show="memberFound" class="mt-3 rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-3.5">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="size-11 rounded-full bg-emerald-500/20 overflow-hidden shrink-0 border border-emerald-500/40 flex items-center justify-center">
                                        <template x-if="memberFound && memberFound.photo_url">
                                            <img :src="memberFound.photo_url" class="size-full object-cover">
                                        </template>
                                        <template x-if="!memberFound || !memberFound.photo_url">
                                            <span class="text-xs font-black text-emerald-600 dark:text-emerald-400" x-text="memberFound ? memberFound.name.substring(0, 2).toUpperCase() : ''"></span>
                                        </template>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-sm font-black text-text" x-text="memberFound?.display_name"></h4>
                                            <span class="rounded bg-emerald-500/20 px-1.5 py-0.5 text-[10px] font-mono font-bold text-emerald-700 dark:text-emerald-300" x-text="memberFound?.code"></span>
                                        </div>
                                        <p class="text-xs text-text-muted mt-0.5">
                                            Stempel Kunjungan: <strong class="text-emerald-600 dark:text-emerald-400 font-bold" x-text="'Ke-' + memberFound?.next_position + ' dari ' + memberFound?.cycle_max"></strong>
                                            <span class="text-[11px]"> (Otomatis ditambah saat disimpan)</span>
                                        </p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="clearMember()"
                                    class="text-xs text-text-muted hover:text-rose-500 transition underline p-1"
                                >
                                    Lepas
                                </button>
                            </div>

                            {{-- Reward Loyalty Banner (Jika Memenuhi Syarat) --}}
                            <template x-if="memberFound && memberFound.potential_reward !== 'none'">
                                <div class="mt-2.5 pt-2.5 border-t border-emerald-500/20 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <span class="flex size-5 items-center justify-center rounded-full bg-amber-500 text-slate-950">
                                            <svg class="size-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                        </span>
                                        <span class="text-xs font-bold text-amber-700 dark:text-amber-400" x-text="'Mendapat Reward: ' + memberFound.potential_reward_label"></span>
                                    </div>
                                    <label class="flex items-center gap-1.5 text-xs font-bold text-text cursor-pointer">
                                        <input type="checkbox" x-model="applyMemberReward" class="rounded text-amber-500 focus:ring-amber-500">
                                        <span>Gunakan Diskon Langsung</span>
                                    </label>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Form Nama & WhatsApp --}}
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="customer_name" class="block text-xs font-bold uppercase tracking-wider text-text-muted mb-1.5">
                                Nama Anak / Penonton <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="customer_name"
                                name="customer_name"
                                x-model="customerName"
                                placeholder="Contoh: Bagas (Bagas)"
                                class="min-h-11 w-full rounded-xl border border-border bg-bg px-3.5 text-sm text-text placeholder:text-text-muted focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                            >
                        </div>
                        <div>
                            <label for="whatsapp" class="block text-xs font-bold uppercase tracking-wider text-text-muted mb-1.5">
                                Nomor WhatsApp <span class="text-text-muted font-normal text-[11px]">(Opsional)</span>
                            </label>
                            <input
                                type="text"
                                id="whatsapp"
                                name="whatsapp"
                                x-model="whatsapp"
                                placeholder="Kosongkan jika anak tidak bawa HP"
                                class="min-h-11 w-full rounded-xl border border-border bg-bg px-3.5 text-sm text-text placeholder:text-text-muted focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                            >
                            <p class="mt-1 text-[11px] text-text-muted">
                                Jika kosong, otomatis dicatat menggunakan nomor loket (walk-in).
                            </p>
                        </div>
                    </div>
                </div>

                {{-- LANGKAH 5: TAMBAH JAJANAN / MINUMAN (SNACK BAR) --}}
                <div class="rounded-2xl border border-border bg-surface p-5 shadow-xs" x-data="{ snackOpen: false }">
                    <div class="flex items-center justify-between pb-3 border-b border-border/60">
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-7 items-center justify-center rounded-lg bg-amber-500 text-slate-950 font-black text-xs">5</span>
                            <div>
                                <h2 class="text-base font-black text-text">Jajanan & Minuman</h2>
                                <p class="text-xs text-text-muted">Popcorn, sosis, minuman botol, dan cemilan</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="snackOpen = !snackOpen"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-border px-3 py-1.5 text-xs font-bold text-text hover:bg-surface-2 transition"
                        >
                            <span x-text="snackOpen ? 'Tutup Pilihan' : '+ Buka Menu Jajan'"></span>
                            <span x-show="totalSnackQty > 0" class="rounded-full bg-amber-500 px-1.5 py-0.2 text-[10px] font-black text-slate-950" x-text="totalSnackQty"></span>
                        </button>
                    </div>

                    {{-- Daftar Jajanan --}}
                    <div x-show="snackOpen" x-transition class="mt-4 space-y-5">
                        @forelse ($categories as $category)
                            @if ($category->items->isNotEmpty())
                                <div>
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-text-muted mb-2.5">{{ $category->name }}</h3>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                        @foreach ($category->items as $item)
                                            <div class="flex items-center justify-between gap-3 p-3 rounded-xl border border-border bg-bg/50">
                                                <div class="min-w-0 flex-1">
                                                    <h4 class="text-sm font-bold text-text truncate">{{ $item->name }}</h4>
                                                    <p class="text-xs font-semibold text-amber-600 dark:text-amber-400">{{ format_rupiah($item->price) }}</p>
                                                </div>

                                                {{-- Counter Qty --}}
                                                <div class="flex items-center gap-1.5 shrink-0">
                                                    <button
                                                        type="button"
                                                        @click="decrementSnack({{ $item->id }})"
                                                        class="size-8 rounded-lg bg-surface border border-border flex items-center justify-center text-text font-bold hover:bg-surface-2 active:scale-95 transition"
                                                    >
                                                        -
                                                    </button>
                                                    <input
                                                        type="number"
                                                        name="snacks[{{ $item->id }}][qty]"
                                                        :value="getSnackQty({{ $item->id }})"
                                                        readonly
                                                        class="w-9 text-center bg-transparent border-0 font-bold text-sm text-text p-0 focus:ring-0"
                                                    >
                                                    <button
                                                        type="button"
                                                        @click="incrementSnack({{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->price }})"
                                                        class="size-8 rounded-lg bg-amber-500 flex items-center justify-center text-slate-950 font-bold hover:bg-amber-400 active:scale-95 transition"
                                                    >
                                                        +
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @empty
                            <p class="text-xs text-text-muted italic">Belum ada menu jajanan yang tersedia.</p>
                        @endforelse
                    </div>

                    {{-- Ringkasan Singkat Jajanan Terpilih --}}
                    <div x-show="totalSnackQty > 0 && !snackOpen" class="mt-3 flex items-center justify-between rounded-xl bg-amber-500/10 border border-amber-500/20 px-3.5 py-2 text-xs font-bold text-amber-700 dark:text-amber-400">
                        <span x-text="totalSnackQty + ' jajan terpilih'"></span>
                        <span x-text="formatRupiah(snackTotal)"></span>
                    </div>
                </div>

            </div>

            {{-- KOLOM KANAN: KASIR & STRUK RINGKASAN (4 kolom sticky) --}}
            <div class="lg:col-span-5 xl:col-span-4 sticky top-6 space-y-5">
                <div class="rounded-2xl border-2 border-border bg-surface p-5 shadow-lg">
                    <div class="flex items-center justify-between pb-3 border-b border-border">
                        <h2 class="text-base font-black text-text">Ringkasan Tagihan</h2>
                        <span class="rounded bg-accent/15 px-2 py-0.5 text-xs font-bold text-accent">Kasir Loket</span>
                    </div>

                    {{-- Detail Rincian Sesi & Film --}}
                    <div class="mt-4 space-y-2 text-xs border-b border-border/60 pb-3">
                        <div class="flex justify-between">
                            <span class="text-text-muted">Sesi:</span>
                            <span class="font-bold text-text text-right" x-text="(currentSession?.time_formatted || '-') + ' · ' + (currentSession?.date_formatted || '-')"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-text-muted">Film:</span>
                            <span class="font-bold text-text text-right truncate max-w-[180px]" x-text="resolvedFilmTitle"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-text-muted">Paket:</span>
                            <span class="font-bold text-text capitalize" x-text="packageType === 'private' ? 'Sewa Privat (6 org)' : (packageType === 'couple' ? 'Couple (2 org)' : 'Reguler (' + pax + ' org)')"></span>
                        </div>
                        <template x-if="memberFound">
                            <div class="flex justify-between text-emerald-600 dark:text-emerald-400 font-semibold">
                                <span>Member:</span>
                                <span class="truncate max-w-[180px]" x-text="memberFound.display_name"></span>
                            </div>
                        </template>
                    </div>

                    {{-- Hitungan Biaya --}}
                    <div class="mt-3 space-y-1.5 text-xs">
                        <div class="flex justify-between">
                            <span class="text-text-muted">Subtotal Tiket:</span>
                            <span class="font-bold text-text" x-text="formatRupiah(ticketSubtotalBeforeDiscount)"></span>
                        </div>
                        <template x-if="memberDiscount > 0">
                            <div class="flex justify-between text-emerald-600 dark:text-emerald-400 font-semibold">
                                <span>Diskon Member:</span>
                                <span x-text="'-' + formatRupiah(memberDiscount)"></span>
                            </div>
                        </template>
                        <div class="flex justify-between">
                            <span class="text-text-muted">Subtotal Jajanan:</span>
                            <span class="font-bold text-text" x-text="formatRupiah(snackTotal)"></span>
                        </div>
                    </div>

                    {{-- GRAND TOTAL TAGIHAN --}}
                    <div class="mt-4 pt-3 border-t-2 border-border flex items-baseline justify-between">
                        <div>
                            <span class="text-xs font-black uppercase tracking-wider text-text-muted">Total Bayar</span>
                            <p class="text-[10px] text-text-muted">Termasuk tiket & jajan</p>
                        </div>
                        <div class="text-2xl font-black text-accent tracking-tight" x-text="formatRupiah(grandTotal)"></div>
                    </div>

                    {{-- Metode Pembayaran --}}
                    <div class="mt-5 pt-4 border-t border-border">
                        <label class="block text-xs font-bold uppercase tracking-wider text-text-muted mb-2">Metode Bayar</label>
                        <div class="grid grid-cols-3 gap-2">
                            <label
                                :class="{
                                    'bg-amber-500 text-slate-950 font-black shadow-sm': paymentMethod === 'di_tempat',
                                    'bg-bg border border-border text-text hover:bg-surface-2': paymentMethod !== 'di_tempat'
                                }"
                                class="flex items-center justify-center p-2 rounded-xl text-xs font-bold cursor-pointer transition text-center"
                            >
                                <input type="radio" name="payment_method" value="di_tempat" x-model="paymentMethod" class="sr-only">
                                <span>Tunai</span>
                            </label>
                            <label
                                :class="{
                                    'bg-amber-500 text-slate-950 font-black shadow-sm': paymentMethod === 'qris',
                                    'bg-bg border border-border text-text hover:bg-surface-2': paymentMethod !== 'qris'
                                }"
                                class="flex items-center justify-center p-2 rounded-xl text-xs font-bold cursor-pointer transition text-center"
                            >
                                <input type="radio" name="payment_method" value="qris" x-model="paymentMethod" class="sr-only">
                                <span>QRIS</span>
                            </label>
                            <label
                                :class="{
                                    'bg-amber-500 text-slate-950 font-black shadow-sm': paymentMethod === 'transfer',
                                    'bg-bg border border-border text-text hover:bg-surface-2': paymentMethod !== 'transfer'
                                }"
                                class="flex items-center justify-center p-2 rounded-xl text-xs font-bold cursor-pointer transition text-center"
                            >
                                <input type="radio" name="payment_method" value="transfer" x-model="paymentMethod" class="sr-only">
                                <span>Transfer</span>
                            </label>
                        </div>
                    </div>

                    {{-- KALKULATOR UANG TUNAI & KEMBALIAN (Khusus Pembayaran Tunai) --}}
                    <div x-show="paymentMethod === 'di_tempat'" class="mt-4 rounded-xl border border-amber-500/30 bg-amber-500/5 p-3.5 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">Uang Tunai Diterima</label>
                            <button
                                type="button"
                                @click="setExactCash()"
                                class="text-[11px] font-bold text-amber-600 dark:text-amber-400 hover:underline"
                            >
                                Uang Pas
                            </button>
                        </div>

                        {{-- Tombol Cepat Pecahan Uang --}}
                        <div class="grid grid-cols-3 gap-1.5">
                            <button type="button" @click="setCash(5000)" class="rounded-lg border border-border bg-surface py-1 text-xs font-bold text-text hover:bg-surface-2 transition">5.000</button>
                            <button type="button" @click="setCash(10000)" class="rounded-lg border border-border bg-surface py-1 text-xs font-bold text-text hover:bg-surface-2 transition">10.000</button>
                            <button type="button" @click="setCash(20000)" class="rounded-lg border border-border bg-surface py-1 text-xs font-bold text-text hover:bg-surface-2 transition">20.000</button>
                            <button type="button" @click="setCash(50000)" class="rounded-lg border border-border bg-surface py-1 text-xs font-bold text-text hover:bg-surface-2 transition">50.000</button>
                            <button type="button" @click="setCash(100000)" class="rounded-lg border border-border bg-surface py-1 text-xs font-bold text-text hover:bg-surface-2 transition">100.000</button>
                            <button type="button" @click="cashReceived = ''" class="rounded-lg border border-border bg-surface py-1 text-xs font-bold text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/20 transition">Reset</button>
                        </div>

                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-bold text-text-muted">Rp</span>
                            <input
                                type="number"
                                x-model="cashReceived"
                                placeholder="0"
                                class="min-h-10 w-full rounded-lg border border-border bg-surface pl-9 pr-3 text-sm font-bold text-text placeholder:text-text-muted focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                            >
                        </div>

                        {{-- Tampilan Kembalian --}}
                        <div class="rounded-lg bg-surface border border-border p-2.5 flex items-center justify-between">
                            <span class="text-xs font-bold text-text-muted">Kembalian:</span>
                            <span
                                :class="{
                                    'text-emerald-600 dark:text-emerald-400 font-black text-lg': changeAmount >= 0,
                                    'text-rose-500 font-bold text-xs': cashReceivedNumeric > 0 && cashReceivedNumeric < grandTotal
                                }"
                            >
                                <span x-show="cashReceivedNumeric >= grandTotal" x-text="formatRupiah(changeAmount)"></span>
                                <span x-show="cashReceivedNumeric > 0 && cashReceivedNumeric < grandTotal" x-text="'Kurang ' + formatRupiah(grandTotal - cashReceivedNumeric)"></span>
                                <span x-show="!cashReceivedNumeric" class="text-text-muted text-xs">-</span>
                            </span>
                        </div>
                    </div>

                    {{-- Status Hadir Langsung --}}
                    <div class="mt-4 pt-4 border-t border-border">
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input
                                type="checkbox"
                                x-model="markAsAttended"
                                class="mt-0.5 rounded text-amber-500 focus:ring-amber-500"
                            >
                            <div>
                                <span class="text-xs font-bold text-text block">Langsung Masuk Studio (Tandai Hadir)</span>
                                <span class="text-[11px] text-text-muted block">Tiket langsung berstatus hadir tanpa perlu scan di pintu lagi.</span>
                            </div>
                        </label>
                    </div>

                    {{-- Catatan Tambahan --}}
                    <div class="mt-4 pt-3 border-t border-border">
                        <label for="notes" class="block text-xs font-bold uppercase tracking-wider text-text-muted mb-1">Catatan Tambahan</label>
                        <input
                            type="text"
                            id="notes"
                            name="notes"
                            x-model="notes"
                            placeholder="Opsional (misal: rombongan ulang tahun)"
                            class="min-h-9 w-full rounded-lg border border-border bg-bg px-2.5 text-xs text-text placeholder:text-text-muted focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                        >
                    </div>

                    {{-- Tombol Submit --}}
                    <div class="mt-5">
                        <button
                            type="submit"
                            :disabled="isSubmitting || !canSubmit"
                            class="w-full min-h-12 rounded-xl bg-amber-500 px-5 text-sm font-black text-slate-950 shadow-md hover:bg-amber-400 active:scale-95 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            <svg x-show="isSubmitting" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <svg x-show="!isSubmitting" class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span x-text="isSubmitting ? 'Memproses Transaksi...' : 'Simpan Transaksi Loket'"></span>
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function walkInPos(config) {
    return {
        sessions: config.sessions || [],
        films: config.films || [],
        selectedSessionId: config.initialSessionId,
        packageType: 'reguler',
        pax: 1,
        selectedFilmId: null,
        filmSearch: '',
        
        // Member
        memberQuery: '',
        memberSearching: false,
        memberFound: null,
        memberNotFound: false,
        applyMemberReward: true,

        // Customer
        customerName: '',
        whatsapp: '',

        // Snacks
        snacks: {},

        // Cashier
        paymentMethod: 'di_tempat',
        cashReceived: '',
        markAsAttended: true,
        notes: '',
        isSubmitting: false,

        init() {
            if (this.currentSession && this.currentSession.film_id) {
                this.selectedFilmId = this.currentSession.film_id;
            } else if (this.films.length > 0) {
                this.selectedFilmId = this.films[0].id;
            }
        },

        get currentSession() {
            return this.sessions.find(s => s.id === this.selectedSessionId) || null;
        },

        get isSessionFilmLocked() {
            return !!(this.currentSession && this.currentSession.is_confirmed && this.currentSession.film_id);
        },

        get resolvedFilmId() {
            if (this.isSessionFilmLocked) {
                return this.currentSession.film_id;
            }
            return this.selectedFilmId;
        },

        get resolvedFilmTitle() {
            if (this.isSessionFilmLocked) {
                return this.currentSession.film_title || 'Film Terkunci';
            }
            const f = this.films.find(x => x.id === this.selectedFilmId);
            return f ? f.title : 'Pilih Film';
        },

        get filteredFilms() {
            if (!this.filmSearch.trim()) return this.films;
            const q = this.filmSearch.toLowerCase();
            return this.films.filter(f => f.title.toLowerCase().includes(q));
        },

        selectSession(id) {
            this.selectedSessionId = id;
            if (this.currentSession && this.currentSession.film_id) {
                this.selectedFilmId = this.currentSession.film_id;
            }
            if (this.currentSession && this.pax > this.currentSession.remaining_seats) {
                this.pax = Math.max(1, this.currentSession.remaining_seats);
            }
        },

        // Pricing calculations
        get ticketSubtotalBeforeDiscount() {
            if (!this.currentSession) return 0;
            if (this.packageType === 'private') {
                return this.currentSession.private_price || 30000;
            }
            if (this.packageType === 'couple') {
                return this.currentSession.couple_price || 10000;
            }
            return (this.currentSession.regular_price || 5000) * this.pax;
        },

        get memberDiscount() {
            if (!this.memberFound || !this.applyMemberReward) return 0;
            if (this.memberFound.potential_reward === 'none') return 0;
            const rewardAmt = this.memberFound.reward_amount || 0;
            return Math.min(rewardAmt, this.ticketSubtotalBeforeDiscount);
        },

        get ticketSubtotal() {
            return Math.max(0, this.ticketSubtotalBeforeDiscount - this.memberDiscount);
        },

        get snackTotal() {
            let sum = 0;
            for (const id in this.snacks) {
                const item = this.snacks[id];
                if (item && item.qty > 0) {
                    sum += item.qty * item.price;
                }
            }
            return sum;
        },

        get totalSnackQty() {
            let count = 0;
            for (const id in this.snacks) {
                const item = this.snacks[id];
                if (item && item.qty > 0) {
                    count += item.qty;
                }
            }
            return count;
        },

        get grandTotal() {
            return this.ticketSubtotal + this.snackTotal;
        },

        get cashReceivedNumeric() {
            const val = parseInt(this.cashReceived, 10);
            return isNaN(val) ? 0 : val;
        },

        get changeAmount() {
            if (this.cashReceivedNumeric <= 0) return 0;
            return Math.max(0, this.cashReceivedNumeric - this.grandTotal);
        },

        setCash(amount) {
            this.cashReceived = amount;
        },

        setExactCash() {
            this.cashReceived = this.grandTotal;
        },

        // Snack Handlers
        getSnackQty(id) {
            return this.snacks[id] ? this.snacks[id].qty : 0;
        },

        incrementSnack(id, name, price) {
            if (!this.snacks[id]) {
                this.snacks[id] = { qty: 0, name: name, price: price };
            }
            this.snacks[id].qty++;
        },

        decrementSnack(id) {
            if (this.snacks[id] && this.snacks[id].qty > 0) {
                this.snacks[id].qty--;
            }
        },

        // Member Lookup
        async lookupMember() {
            const q = this.memberQuery.trim();
            if (!q) return;

            this.memberSearching = true;
            this.memberNotFound = false;

            try {
                const url = `${config.lookupUrl}?q=${encodeURIComponent(q)}&session_id=${this.selectedSessionId || ''}`;
                const res = await fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
                const data = await res.json();

                if (data.found && data.member) {
                    this.memberFound = data.member;
                    this.memberNotFound = false;
                    if (!this.customerName.trim()) {
                        this.customerName = data.member.display_name;
                    }
                } else {
                    this.memberFound = null;
                    this.memberNotFound = true;
                }
            } catch (err) {
                this.memberNotFound = true;
            } finally {
                this.memberSearching = false;
            }
        },

        clearMember() {
            this.memberFound = null;
            this.memberNotFound = false;
            this.memberQuery = '';
        },

        get canSubmit() {
            if (!this.selectedSessionId) return false;
            if (!this.resolvedFilmId) return false;
            if (this.currentSession && this.currentSession.remaining_seats <= 0) return false;
            return true;
        },

        handleSubmit(e) {
            if (!this.canSubmit) {
                e.preventDefault();
                return;
            }
            this.isSubmitting = true;
        },

        formatRupiah(num) {
            if (!num && num !== 0) return 'Rp 0';
            return 'Rp ' + Number(num).toLocaleString('id-ID');
        }
    };
}
</script>
@endpush
@endsection
