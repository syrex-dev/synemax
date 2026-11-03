@extends('layouts.public')

@section('title', 'Tiket Saya: ' . setting('site_name', 'SynemaX'))
@section('meta')
    <meta name="description" content="Akses tiket bioskop digital, cek status pemesanan, dan selesaikan pembayaran booking di SynemaX.">
    <meta name="robots" content="noindex, follow">
@endsection

@section('content')
    @php
        $counts = [
            'belum_bayar' => $categorizedBookings['belum_bayar']->count(),
            'diproses' => $categorizedBookings['diproses']->count(),
            'aktif' => $categorizedBookings['aktif']->count(),
            'riwayat' => $categorizedBookings['riwayat']->count(),
        ];

        $initialTab = request('tab');
        if (! in_array($initialTab, ['belum_bayar', 'diproses', 'aktif', 'riwayat'], true)) {
            $initialTab = $counts['belum_bayar'] > 0
                ? 'belum_bayar'
                : ($counts['diproses'] > 0
                    ? 'diproses'
                    : ($counts['aktif'] > 0
                        ? 'aktif'
                        : ($counts['riwayat'] > 0 ? 'riwayat' : 'aktif')));
        }
    @endphp

    <div class="mx-auto max-w-[520px] w-full px-4 py-5 sm:py-8"
         x-data="{
             activeTab: '{{ $initialTab }}',
             cancelModalOpen: false,
             cancelCode: '',
             openCancelModal(code) {
                 this.cancelCode = code;
                 this.cancelModalOpen = true;
             }
         }">

        {{-- Navigasi Kembali ke Beranda --}}
        <div class="mb-4">
            <a href="{{ route('home') }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-text-muted hover:text-text transition no-underline">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Beranda</span>
            </a>
        </div>

        {{-- Header Halaman Tiket --}}
        <header class="mb-6">
            <h1 class="text-2xl font-black text-text sm:text-3xl tracking-tight">Tiket Saya</h1>
            <p class="mt-1.5 text-xs sm:text-sm text-text-muted leading-relaxed">
                Kelola pesanan, selesaikan pembayaran yang tertunda, atau buka tiket digital yang tersimpan pada perangkat ini.
            </p>
        </header>

        {{-- Flash Session Message --}}
        @if (session('status'))
            <div role="status" class="mb-5 rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-4 text-xs font-bold text-emerald-400">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="mb-5 rounded-xl border border-danger/40 bg-danger-surface p-4 text-xs font-semibold text-danger-text space-y-1">
                @foreach ($errors->all() as $err)
                    <p class="font-bold">{{ $err }}</p>
                @endforeach
            </div>
        @endif

        {{-- Navigasi 4 Tab --}}
        <div class="border-b border-border">
            <nav class="flex space-x-1 sm:space-x-2 overflow-x-auto pb-px scrollbar-none" aria-label="Kategori Tiket">
                <button
                    type="button"
                    @click="activeTab = 'belum_bayar'"
                    :class="activeTab === 'belum_bayar'
                        ? 'border-amber-500 text-amber-600 dark:text-amber-400 font-black border-b-2'
                        : 'border-transparent text-text-muted hover:text-text'"
                    class="whitespace-nowrap px-3 py-2.5 text-xs sm:text-sm font-bold transition-colors"
                >
                    Belum bayar ({{ $counts['belum_bayar'] }})
                </button>

                <button
                    type="button"
                    @click="activeTab = 'diproses'"
                    :class="activeTab === 'diproses'
                        ? 'border-amber-500 text-amber-600 dark:text-amber-400 font-black border-b-2'
                        : 'border-transparent text-text-muted hover:text-text'"
                    class="whitespace-nowrap px-3 py-2.5 text-xs sm:text-sm font-bold transition-colors"
                >
                    Diproses ({{ $counts['diproses'] }})
                </button>

                <button
                    type="button"
                    @click="activeTab = 'aktif'"
                    :class="activeTab === 'aktif'
                        ? 'border-amber-500 text-amber-600 dark:text-amber-400 font-black border-b-2'
                        : 'border-transparent text-text-muted hover:text-text'"
                    class="whitespace-nowrap px-3 py-2.5 text-xs sm:text-sm font-bold transition-colors"
                >
                    Tiket aktif ({{ $counts['aktif'] }})
                </button>

                <button
                    type="button"
                    @click="activeTab = 'riwayat'"
                    :class="activeTab === 'riwayat'
                        ? 'border-amber-500 text-amber-600 dark:text-amber-400 font-black border-b-2'
                        : 'border-transparent text-text-muted hover:text-text'"
                    class="whitespace-nowrap px-3 py-2.5 text-xs sm:text-sm font-bold transition-colors"
                >
                    Riwayat ({{ $counts['riwayat'] }})
                </button>
            </nav>
        </div>

        {{-- ==================== TAB 1: BELUM BAYAR ==================== --}}
        <div x-show="activeTab === 'belum_bayar'" x-cloak class="mt-5 space-y-4">
            @forelse ($categorizedBookings['belum_bayar'] as $b)
                <div class="rounded-2xl border border-amber-500/40 bg-surface dark:bg-[#0E131F] p-4 sm:p-5 shadow-xs"
                     x-data="{
                         expiresAt: @js($b->payment_expires_at?->toIso8601String()),
                         timeLeft: 0,
                         isOverdue: false,
                         init() {
                             if (!this.expiresAt) return;
                             const target = new Date(this.expiresAt).getTime();
                             const update = () => {
                                 const diff = Math.max(0, Math.floor((target - Date.now()) / 1000));
                                 this.timeLeft = diff;
                                 if (diff <= 0) this.isOverdue = true;
                             };
                             update();
                             setInterval(update, 1000);
                         },
                         formatTime(sec) {
                             const m = Math.floor(sec / 60);
                             const s = sec % 60;
                             return `${m}.${s < 10 ? '0' : ''}${s}`;
                         }
                     }">
                    <div class="flex items-start gap-3.5">
                        <div class="size-16 sm:size-20 rounded-xl overflow-hidden bg-surface-2 shrink-0 border border-border">
                            @if ($b->film && $b->film->poster_url)
                                <img src="{{ $b->film->poster_url }}" alt="{{ $b->film->title }}" class="size-full object-cover">
                            @else
                                <div class="size-full flex items-center justify-center text-text-muted text-[10px]">Poster</div>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-tabular text-xs font-black uppercase tracking-wider text-amber-500">
                                    {{ $b->code }}
                                </span>
                                <x-ui.status type="warning" label="Menunggu Pembayaran" />
                            </div>

                            <h2 class="mt-1 text-sm sm:text-base font-black text-text truncate">
                                {{ $b->film?->title ?? 'Film Sesi' }}
                            </h2>

                            <p class="mt-0.5 text-xs text-text-muted">
                                {{ format_tanggal_id($b->session?->session_date) }} &bull; {{ format_jam_id($b->session?->start_time) }}
                            </p>

                            <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                                <span class="font-semibold text-text">
                                    Jumlah: {{ $b->people_count ?? $b->seats_reserved ?? $b->pax }} orang
                                </span>
                                <span class="text-text-muted">&bull;</span>
                                <span class="font-black text-text">
                                    Rp {{ number_format($b->total_price, 0, ',', '.') }}
                                </span>
                            </div>

                            <p class="mt-2 text-xs font-bold text-amber-800 dark:text-amber-300">
                                Sisa waktu bayar: <span class="font-tabular font-black" x-text="formatTime(timeLeft)">--.--</span>
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-border flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            @click="openCancelModal('{{ $b->code }}')"
                            class="inline-flex min-h-10 items-center justify-center rounded-xl border border-border px-3.5 text-xs font-bold text-text hover:text-danger hover:border-danger transition"
                        >
                            Batalkan
                        </button>
                        <a
                            href="{{ route('booking.payment', ['code' => $b->code]) }}"
                            class="btn-gold-cinema inline-flex min-h-10 items-center justify-center rounded-xl px-4 text-xs font-black shadow-xs"
                        >
                            Lanjutkan pembayaran
                        </a>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-border bg-surface-2 p-8 text-center">
                    <p class="text-sm font-bold text-text">Tidak ada booking yang menunggu pembayaran.</p>
                    <p class="mt-1 text-xs text-text-muted">Semua pesananmu sudah diselesaikan atau sudah kedaluwarsa.</p>
                    <div class="mt-4">
                        <a href="{{ route('schedule.index') }}" class="btn-gold-cinema inline-flex min-h-10 items-center justify-center rounded-xl px-4 text-xs font-bold">
                            Lihat jadwal
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- ==================== TAB 2: DIPROSES ==================== --}}
        <div x-show="activeTab === 'diproses'" x-cloak class="mt-5 space-y-4">
            @forelse ($categorizedBookings['diproses'] as $b)
                <div class="rounded-2xl border border-border bg-surface dark:bg-[#0E131F] p-4 sm:p-5 shadow-xs">
                    <div class="flex items-start gap-3.5">
                        <div class="size-16 sm:size-20 rounded-xl overflow-hidden bg-surface-2 shrink-0 border border-border">
                            @if ($b->film && $b->film->poster_url)
                                <img src="{{ $b->film->poster_url }}" alt="{{ $b->film->title }}" class="size-full object-cover">
                            @else
                                <div class="size-full flex items-center justify-center text-text-muted text-[10px]">Poster</div>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-tabular text-xs font-black uppercase tracking-wider text-text-muted">
                                    {{ $b->code }}
                                </span>
                                @if ($b->status === 'menunggu_konfirmasi')
                                    <x-ui.status type="info" label="Menunggu Konfirmasi Admin" />
                                @else
                                    <x-ui.status type="neutral" label="Menunggu Penonton" />
                                @endif
                            </div>

                            <h2 class="mt-1 text-sm sm:text-base font-black text-text truncate">
                                {{ $b->film?->title ?? 'Film Sesi' }}
                            </h2>

                            <p class="mt-0.5 text-xs text-text-muted">
                                {{ format_tanggal_id($b->session?->session_date) }} &bull; {{ format_jam_id($b->session?->start_time) }}
                            </p>

                            <p class="mt-1.5 text-xs font-semibold text-text">
                                Jumlah: {{ $b->people_count ?? $b->seats_reserved ?? $b->pax }} orang
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-border flex items-center justify-end">
                        <a
                            href="{{ route('booking.ticket', ['code' => $b->code]) }}"
                            class="inline-flex min-h-10 items-center justify-center rounded-xl border border-border px-4 text-xs font-bold text-text hover:border-amber-500 transition"
                        >
                            Lihat detail
                        </a>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-border bg-surface-2 p-8 text-center">
                    <p class="text-sm font-bold text-text">Tidak ada booking yang sedang diproses.</p>
                    <p class="mt-1 text-xs text-text-muted">Bukti bayar yang kamu kirimkan akan diverifikasi oleh admin di sini.</p>
                    <div class="mt-4">
                        <a href="{{ route('schedule.index') }}" class="btn-gold-cinema inline-flex min-h-10 items-center justify-center rounded-xl px-4 text-xs font-bold">
                            Lihat jadwal
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- ==================== TAB 3: TIKET AKTIF ==================== --}}
        <div x-show="activeTab === 'aktif'" x-cloak class="mt-5 space-y-4">
            @forelse ($categorizedBookings['aktif'] as $b)
                <div class="rounded-2xl border border-emerald-500/30 bg-surface dark:bg-[#0E131F] p-4 sm:p-5 shadow-xs">
                    <div class="flex items-start gap-3.5">
                        <div class="size-16 sm:size-20 rounded-xl overflow-hidden bg-surface-2 shrink-0 border border-border">
                            @if ($b->film && $b->film->poster_url)
                                <img src="{{ $b->film->poster_url }}" alt="{{ $b->film->title }}" class="size-full object-cover">
                            @else
                                <div class="size-full flex items-center justify-center text-text-muted text-[10px]">Poster</div>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-tabular text-xs font-black uppercase tracking-wider text-emerald-500">
                                    {{ $b->code }}
                                </span>
                                <x-ui.status type="success" label="Terkonfirmasi" />
                            </div>

                            <h2 class="mt-1 text-sm sm:text-base font-black text-text truncate">
                                {{ $b->film?->title ?? 'Film Sesi' }}
                            </h2>

                            <p class="mt-0.5 text-xs text-text-muted">
                                {{ format_tanggal_id($b->session?->session_date) }} &bull; {{ format_jam_id($b->session?->start_time) }}
                            </p>

                            <p class="mt-1.5 text-xs font-semibold text-text">
                                Jumlah: {{ $b->people_count ?? $b->seats_reserved ?? $b->pax }} orang
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-border flex items-center justify-end">
                        <a
                            href="{{ route('booking.ticket', ['code' => $b->code]) }}"
                            class="btn-gold-cinema inline-flex min-h-10 items-center justify-center rounded-xl px-4 text-xs font-black shadow-xs"
                        >
                            Lihat tiket
                        </a>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-border bg-surface-2 p-8 text-center">
                    <p class="text-sm font-bold text-text">Belum ada tiket aktif.</p>
                    <p class="mt-1 text-xs text-text-muted">Tiket yang sudah terkonfirmasi untuk sesi mendatang akan muncul di sini.</p>
                    <div class="mt-4">
                        <a href="{{ route('schedule.index') }}" class="btn-gold-cinema inline-flex min-h-10 items-center justify-center rounded-xl px-4 text-xs font-bold">
                            Lihat jadwal
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- ==================== TAB 4: RIWAYAT ==================== --}}
        <div x-show="activeTab === 'riwayat'" x-cloak class="mt-5 space-y-4">
            @forelse ($categorizedBookings['riwayat'] as $b)
                <div class="rounded-2xl border border-border bg-surface/70 dark:bg-[#0E131F]/70 p-4 sm:p-5 opacity-90">
                    <div class="flex items-start gap-3.5">
                        <div class="size-16 sm:size-20 rounded-xl overflow-hidden bg-surface-2 shrink-0 border border-border opacity-70">
                            @if ($b->film && $b->film->poster_url)
                                <img src="{{ $b->film->poster_url }}" alt="{{ $b->film->title }}" class="size-full object-cover">
                            @else
                                <div class="size-full flex items-center justify-center text-text-muted text-[10px]">Poster</div>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-tabular text-xs font-bold text-text-muted uppercase">
                                    {{ $b->code }}
                                </span>
                                @php
                                    $statusType = match ($b->status) {
                                        'hadir' => 'success',
                                        'dibatalkan' => 'danger',
                                        'kedaluwarsa' => 'neutral',
                                        'tidak_hadir' => 'danger',
                                        default => 'neutral',
                                    };
                                    $statusLabel = match ($b->status) {
                                        'hadir' => 'Hadir',
                                        'dibatalkan' => 'Dibatalkan',
                                        'kedaluwarsa' => 'Kedaluwarsa',
                                        'tidak_hadir' => 'Tidak Hadir',
                                        default => 'Selesai',
                                    };
                                @endphp
                                <x-ui.status :type="$statusType" :label="$statusLabel" />
                            </div>

                            <h2 class="mt-1 text-sm sm:text-base font-bold text-text truncate">
                                {{ $b->film?->title ?? 'Film Sesi' }}
                            </h2>

                            <p class="mt-0.5 text-xs text-text-muted">
                                {{ format_tanggal_id($b->session?->session_date) }} &bull; {{ format_jam_id($b->session?->start_time) }}
                            </p>

                            <p class="mt-1 text-xs text-text-muted">
                                Jumlah: {{ $b->people_count ?? $b->seats_reserved ?? $b->pax }} orang
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-border flex items-center justify-end">
                        <a
                            href="{{ route('booking.ticket', ['code' => $b->code]) }}"
                            class="inline-flex min-h-10 items-center justify-center rounded-xl border border-border px-3.5 text-xs font-semibold text-text-muted hover:text-text transition"
                        >
                            Lihat arsip tiket
                        </a>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-border bg-surface-2 p-8 text-center">
                    <p class="text-sm font-bold text-text">Belum ada riwayat pesanan.</p>
                    <p class="mt-1 text-xs text-text-muted">Tiket yang telah selesai ditonton atau dibatalkan akan tersimpan di sini.</p>
                    <div class="mt-4">
                        <a href="{{ route('schedule.index') }}" class="btn-gold-cinema inline-flex min-h-10 items-center justify-center rounded-xl px-4 text-xs font-bold">
                            Lihat jadwal
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- ==================== FORM TAMBAH TIKET MANUAL ==================== --}}
        <section class="mt-10 rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-xs">
            <h2 class="text-sm font-bold text-text">Tiket tidak muncul?</h2>
            <p class="mt-1 text-xs text-text-muted leading-relaxed">
                Tambahkan booking ke perangkat ini dengan memasukkan kode booking dan nomor WhatsApp yang digunakan saat memesan.
            </p>

            <form method="POST" action="{{ route('booking.ticket.add') }}" class="mt-4 space-y-4">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="code" class="block text-[11px] font-bold uppercase tracking-wider text-text-muted">Kode booking</label>
                        <input id="code"
                               name="code"
                               type="text"
                               required
                               placeholder="Contoh: HC-261103-A7K2"
                               value="{{ old('code', request('code')) }}"
                               class="mt-1.5 min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 font-tabular text-xs font-bold uppercase text-text focus:border-amber-400 focus:outline-none">
                    </div>

                    <div>
                        <label for="whatsapp" class="block text-[11px] font-bold uppercase tracking-wider text-text-muted">Nomor WhatsApp</label>
                        <input id="whatsapp"
                               name="whatsapp"
                               type="tel"
                               inputmode="numeric"
                               required
                               placeholder="Contoh: 081234567890"
                               value="{{ old('whatsapp') }}"
                               class="mt-1.5 min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 font-tabular text-xs font-bold text-text focus:border-amber-400 focus:outline-none">
                    </div>
                </div>

                <div class="flex justify-end pt-1">
                    <button type="submit"
                            class="btn-gold-cinema inline-flex min-h-11 items-center justify-center rounded-xl px-4 text-xs font-black shadow-xs">
                        Cari & tambahkan tiket
                    </button>
                </div>
            </form>
        </section>

        {{-- In-App Confirmation Modal: Batal Booking --}}
        <div
            x-show="cancelModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-xs"
            role="dialog"
            aria-modal="true"
            aria-label="Konfirmasi Batal Booking"
            @keydown.escape.window="cancelModalOpen = false"
        >
            <div
                @click.outside="cancelModalOpen = false"
                class="w-full max-w-sm rounded-2xl border border-border bg-surface dark:bg-[#0E131F] p-6 shadow-2xl"
            >
                <h3 class="text-base font-black text-text dark:text-white">Batalkan booking ini?</h3>
                <p class="mt-2 text-xs leading-relaxed text-text-muted">
                    Apakah kamu yakin ingin membatalkan booking <strong x-text="cancelCode"></strong>? Tempat yang telah kamu pesan akan segera dilepaskan kembali agar bisa dipesan penonton lain.
                </p>

                <form method="POST" :action="'/tiket/' + cancelCode + '/batal'" class="mt-5">
                    @csrf
                    <div class="flex justify-end gap-2.5">
                        <button
                            type="button"
                            @click="cancelModalOpen = false"
                            class="inline-flex min-h-10 items-center justify-center rounded-xl border border-border px-3.5 text-xs font-bold text-text hover:bg-surface-2 transition"
                        >
                            Jangan batalkan
                        </button>
                        <button
                            type="submit"
                            class="inline-flex min-h-10 items-center justify-center rounded-xl bg-danger px-4 text-xs font-bold text-white hover:opacity-90 transition"
                        >
                            Ya, batalkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
