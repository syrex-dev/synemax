@extends('layouts.admin')

@section('title', 'Detail Booking - ' . $booking->customer_name)

@section('content')
<div x-data="{
    editorOpen: false,
    historyOpen: false,
    rejectProofOpen: false,
    copyCode(code) {
        if (window.copyToClipboard) {
            window.copyToClipboard(code, 'Kode booking tersalin');
        } else {
            navigator.clipboard.writeText(code);
        }
    }
}" class="space-y-6">

    {{-- Breadcrumb & Back --}}
    <div class="flex items-center justify-between text-xs sm:text-sm">
        <a href="{{ route('admin.bookings.index') }}" class="inline-flex items-center gap-1.5 font-bold text-amber-600 dark:text-amber-400 hover:underline">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            <span>Kembali ke Kelola Booking</span>
        </a>

        <span class="text-text-muted">ID Booking #{{ $booking->id }}</span>
    </div>

    {{-- ============================================================== --}}
    {{-- HEADER: H1 = NAMA PELANGGAN, SUBTITLE = KODE + STATUS CHIP      --}}
    {{-- ============================================================== --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl border border-slate-300 dark:border-white/10 bg-surface p-5 sm:p-6 shadow-sm">
        <div class="space-y-1.5 min-w-0">
            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Detail Reservasi Pelanggan</p>
            <h1 class="text-2xl sm:text-3xl font-black text-text tracking-tight truncate">
                {{ $booking->customer_name }}
            </h1>
            <div class="flex flex-wrap items-center gap-2 pt-1 text-xs">
                {{-- Booking Code with 1-click copy --}}
                <button
                    type="button"
                    @click="copyCode('{{ $booking->code }}')"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 dark:border-white/15 bg-surface-2 px-2.5 py-1 font-mono font-bold text-text hover:border-amber-400 transition"
                    title="Salin kode booking"
                >
                    <span>{{ $booking->code }}</span>
                    <svg class="size-3.5 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </button>

                <x-ui.status :status="$booking->status" />

                @if ($booking->payment_proof_path)
                    <span class="badge-success-soft">Bukti Terunggah</span>
                @elseif ($booking->isOnlinePayment() && $booking->status === 'menunggu_pembayaran')
                    <span class="badge-warning-soft">Belum Ada Bukti</span>
                @endif

                @if ($booking->customerProfile?->no_show_count >= 2)
                    <span class="badge-danger-soft">Peringatan Tidak Datang {{ $booking->customerProfile->no_show_count }}×</span>
                @endif
            </div>
        </div>

        {{-- Action Bar: Single Contextual Primary Action + Menu Lainnya --}}
        <div class="flex items-center gap-2.5 shrink-0" x-data="{ menuOpen: false }">
            {{-- Primary Contextual Action --}}
            @if ($booking->status === 'menunggu_pembayaran')
                <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" data-confirm="Konfirmasi pembayaran tunai untuk booking ini?" data-confirm-title="Konfirmasi Pembayaran" data-confirm-action="Tandai Sudah Dibayar" novalidate>
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="tandai_dibayar">
                    <button class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-amber-500 px-5 text-xs sm:text-sm font-bold text-slate-950 hover:bg-amber-400 transition shadow-sm">
                        Konfirmasi Pembayaran
                    </button>
                </form>
            @elseif ($booking->status === 'menunggu_konfirmasi')
                <form method="POST" action="{{ route('admin.bookings.status', $booking) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="konfirmasi">
                    <button class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-amber-500 px-5 text-xs sm:text-sm font-bold text-slate-950 hover:bg-amber-400 transition shadow-sm">
                        Konfirmasi Booking
                    </button>
                </form>
            @elseif ($booking->status === 'terkonfirmasi')
                <form method="POST" action="{{ route('admin.bookings.status', $booking) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="hadir">
                    <button class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-emerald-600 px-5 text-xs sm:text-sm font-bold text-white hover:bg-emerald-500 transition shadow-sm">
                        Tandai Hadir
                    </button>
                </form>
            @elseif ($booking->status === 'kedaluwarsa')
                <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" data-confirm="Pulihkan booking ini? Kursi akan ditahan kembali jika sesi masih memiliki kapasitas." data-confirm-title="Pulihkan Booking" data-confirm-action="Pulihkan" novalidate>
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="pulihkan">
                    <button class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-amber-500 px-5 text-xs sm:text-sm font-bold text-slate-950 hover:bg-amber-400 transition shadow-sm">
                        Pulihkan Booking
                    </button>
                </form>
            @endif

            {{-- Secondary Actions: Menu "Lainnya" --}}
            <div class="relative" @click.outside="menuOpen = false">
                <button
                    type="button"
                    @click="menuOpen = !menuOpen"
                    class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-slate-300 dark:border-white/15 bg-surface px-3.5 text-xs sm:text-sm font-semibold text-text hover:bg-surface-2 transition shadow-none"
                    aria-label="Menu tindakan lainnya"
                >
                    <span>Lainnya</span>
                    <svg class="size-4 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                <div
                    x-show="menuOpen"
                    x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 top-full mt-2 w-56 rounded-2xl border border-slate-300 dark:border-white/15 bg-surface p-1.5 shadow-xl z-50 text-xs"
                >
                    <a
                        href="{{ route('admin.bookings.ticket', $booking) }}"
                        class="flex w-full min-h-[38px] items-center gap-2 rounded-xl px-3 py-2 font-semibold text-text hover:bg-surface-2 transition"
                    >
                        <svg class="size-4 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" />
                        </svg>
                        <span>Pratinjau tiket</span>
                    </a>

                    <a
                        href="https://wa.me/{{ $booking->whatsapp }}?text={{ rawurlencode('Halo '.$booking->customer_name.', admin SynemaX menghubungi terkait booking '.$booking->code.'.') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex w-full min-h-[38px] items-center gap-2 rounded-xl px-3 py-2 font-semibold text-text hover:bg-surface-2 transition"
                    >
                        <svg class="size-4 text-emerald-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.698c.969.585 1.961.947 3.226.947 3.181 0 5.768-2.587 5.768-5.768.001-3.18-2.585-5.766-5.767-5.766zm9.969 5.768c0 5.503-4.467 9.97-9.97 9.97-1.745 0-3.385-.45-4.819-1.238l-4.711 1.235 1.258-4.599c-.879-1.499-1.398-3.238-1.398-5.098 0-5.503 4.467-9.97 9.97-9.97 5.503 0 9.97 4.467 9.97 9.97z"/>
                        </svg>
                        <span>Hubungi WhatsApp</span>
                    </a>

                    @if ($booking->status === 'menunggu_konfirmasi' && $booking->payment_proof_path)
                        <button
                            type="button"
                            @click="menuOpen = false; rejectProofOpen = true"
                            class="flex w-full min-h-[38px] items-center gap-2 rounded-xl px-3 py-2 font-semibold text-amber-600 dark:text-amber-400 hover:bg-surface-2 transition text-left"
                        >
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            <span>Tolak Bukti Transfer</span>
                        </button>
                    @endif

                    @if ($booking->status === 'terkonfirmasi')
                        <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" data-confirm="Tandai pemesan tidak hadir? Ini akan menambah catatan tidak datang pada pelanggan." data-confirm-title="Tandai Tidak Datang" data-confirm-action="Tandai Tidak Datang" data-confirm-is-danger="true" novalidate>
                            @csrf @method('PATCH')
                            <input type="hidden" name="action" value="tidak_hadir">
                            <button class="flex w-full min-h-[38px] items-center gap-2 rounded-xl px-3 py-2 font-semibold text-text-muted hover:bg-surface-2 transition text-left">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                                <span>Tandai Tidak Datang</span>
                            </button>
                        </form>
                    @endif

                    @if ($booking->status !== 'dibatalkan')
                        <div class="border-t border-slate-200 dark:border-white/10 my-1"></div>
                        <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" data-confirm="Batalkan booking ini? Kursi yang dipesan akan dilepas kembali ke sesi." data-confirm-title="Batalkan Booking" data-confirm-action="Batalkan Booking" data-confirm-is-danger="true" novalidate>
                            @csrf @method('PATCH')
                            <input type="hidden" name="action" value="batal">
                            <button class="flex w-full min-h-[38px] items-center gap-2 rounded-xl px-3 py-2 font-semibold text-danger hover:bg-danger-surface transition text-left">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span>Batalkan Booking</span>
                            </button>
                        </form>
                    @endif

                    @if ($booking->customerProfile)
                        <div class="border-t border-slate-200 dark:border-white/10 my-1"></div>
                        <form method="POST" action="{{ route('admin.customers.toggle-block', $booking->customerProfile) }}" data-confirm="{{ $booking->customerProfile->is_blocked ? 'Buka blokir nomor WhatsApp ini?' : 'Blokir nomor WhatsApp ini dari booking baru?' }}" data-confirm-title="{{ $booking->customerProfile->is_blocked ? 'Buka Blokir' : 'Blokir Nomor' }}" data-confirm-action="{{ $booking->customerProfile->is_blocked ? 'Buka Blokir' : 'Blokir' }}" data-confirm-is-danger="{{ $booking->customerProfile->is_blocked ? 'false' : 'true' }}" novalidate>
                            @csrf @method('PATCH')
                            <button class="flex w-full min-h-[38px] items-center gap-2 rounded-xl px-3 py-2 font-semibold {{ $booking->customerProfile->is_blocked ? 'text-emerald-500 hover:bg-emerald-500/10' : 'text-danger hover:bg-danger-surface' }} transition text-left">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.002A11.959 11.959 0 0112 2.714z" />
                                </svg>
                                <span>{{ $booking->customerProfile->is_blocked ? 'Buka Blokir Nomor' : 'Blokir Nomor' }}</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Rejection Warning Note if previously rejected --}}
    @if ($booking->proof_rejection_note)
        <div role="alert" class="rounded-2xl border border-red-500/30 bg-red-500/10 p-4 text-xs text-red-200 flex items-start gap-3">
            <svg class="size-5 shrink-0 text-red-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <div>
                <p class="font-bold text-red-400">Bukti transfer sebelumnya ditolak:</p>
                <p class="mt-0.5">{{ $booking->proof_rejection_note }}</p>
            </div>
        </div>
    @endif

    {{-- ============================================================== --}}
    {{-- 2-COLUMN LAYOUT (DESKTOP: KIRI 8 KOLOM, KANAN 4 KOLOM STICKY)   --}}
    {{-- ============================================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- KOLOM KIRI (8 Kolom): Sesi/Film, Pesanan Jajan, Riwayat Perubahan --}}
        <div class="lg:col-span-8 space-y-6">

            {{-- 1. Kartu Informasi Sesi & Film --}}
            <div class="rounded-2xl border border-slate-300 dark:border-white/10 bg-surface p-5 sm:p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-border">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-text-muted">Informasi Film & Jadwal Tayang</h2>
                    <span class="text-xs font-semibold text-text-muted">Studio 1</span>
                </div>

                <div class="flex flex-col sm:flex-row gap-4">
                    @if ($booking->session?->film?->poster_path)
                        <img
                            src="{{ Storage::disk('public')->url($booking->session->film->poster_path) }}"
                            alt="{{ $booking->session->film->title }}"
                            class="h-28 w-20 rounded-xl object-cover shrink-0 border border-slate-200 dark:border-white/10"
                        >
                    @endif

                    <div class="space-y-2 min-w-0 flex-1">
                        <h3 class="text-lg font-bold text-text truncate">
                            {{ $booking->session?->film?->title ?? $booking->film?->title ?? 'Judul Film' }}
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-text-muted">Jadwal Tayang:</span>
                                <p class="font-bold text-text mt-0.5">
                                    {{ \Carbon\Carbon::parse($booking->session->session_date)->locale('id')->translatedFormat('l, d F Y') }}
                                </p>
                            </div>
                            <div>
                                <span class="text-text-muted">Jam Sesi:</span>
                                <p class="font-bold text-text mt-0.5">
                                    {{ str_replace(':', '.', substr((string) $booking->session->start_time, 0, 5)) }} WIB
                                </p>
                            </div>
                            <div>
                                <span class="text-text-muted">Tipe Paket:</span>
                                <p class="font-bold text-text mt-0.5">
                                    {{ $booking->booking_type === 'private' ? 'Sewa Private (6 kursi)' : 'Reguler (' . $booking->pax . ' orang)' }}
                                </p>
                            </div>
                            <div>
                                <span class="text-text-muted">Nomor Kursi:</span>
                                <p class="font-bold text-amber-600 dark:text-amber-400 mt-0.5">
                                    {{ $booking->seats_reserved > 0 ? $booking->seats_reserved . ' Kursi' : '6 Kursi' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. Kartu Pesanan Jajanan & Makanan Ringan --}}
            <div class="rounded-2xl border border-slate-300 dark:border-white/10 bg-surface p-5 sm:p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-border">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-text-muted">Pesanan Jajanan</h2>
                        <p class="text-xs text-text-muted mt-0.5">Total jajan saat ini: Rp {{ number_format($booking->snack_total, 0, ',', '.') }} · Total booking: Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @php($effectiveSnackWaUrl = $snackUpdateWhatsAppUrl ?? session('snackUpdateWhatsAppUrl'))
                        @if (!empty($effectiveSnackWaUrl))
                            <a href="{{ $effectiveSnackWaUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-[36px] items-center gap-1.5 rounded-xl bg-emerald-700 hover:bg-emerald-600 text-white px-3 text-xs font-bold transition">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a.75.75 0 01-.974-.954 5.94 5.94 0 00.906-2.909A7.95 7.95 0 013 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                                </svg>
                                <span>Kirim update ke pelanggan via WA</span>
                            </a>
                        @endif

                        @if ($booking->status !== 'dibatalkan')
                            <button
                                type="button"
                                @click="editorOpen = true"
                                class="inline-flex min-h-[36px] items-center gap-1.5 rounded-xl border border-amber-500/40 bg-amber-500/10 px-3 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 transition"
                            >
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                                <span>Ubah Jajan</span>
                            </button>
                        @endif
                    </div>
                </div>

                @if ($booking->items->isNotEmpty())
                    <div class="divide-y divide-border">
                        @foreach ($booking->items as $item)
                            <div class="py-3 flex items-center justify-between gap-4 text-xs sm:text-sm">
                                <div class="space-y-0.5 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-text">{{ $item->name_snapshot }} × {{ $item->qty }}</span>
                                        <span class="text-[10px] uppercase font-bold px-1.5 py-0.2 rounded {{ match($item->status) { 'selesai' => 'bg-emerald-500/15 text-emerald-400', 'siap' => 'bg-sky-500/15 text-sky-400', 'disiapkan' => 'bg-amber-500/15 text-amber-400', default => 'bg-surface-2 text-text-muted' } }}">
                                            {{ $item->status }}
                                        </span>
                                    </div>
                                    @if ($item->note)
                                        <p class="text-[11px] text-text-muted italic">Catatan: {{ $item->note }}</p>
                                    @endif
                                </div>
                                <div class="font-bold text-text shrink-0">
                                    Rp {{ number_format($item->price_snapshot * $item->qty, 0, ',', '.') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-6 text-center text-xs text-text-muted space-y-2">
                        <p>Belum ada jajan yang dipesan untuk booking ini.</p>
                        @if ($booking->status !== 'dibatalkan')
                            <button
                                type="button"
                                @click="editorOpen = true"
                                class="inline-flex min-h-[36px] items-center gap-1.5 rounded-xl border border-slate-300 dark:border-white/15 bg-surface px-3 font-semibold text-text hover:bg-surface-2 transition"
                            >
                                + Tambahkan Jajanan
                            </button>
                        @endif
                    </div>
                @endif
            </div>

            {{-- 3. Riwayat Perubahan Jajanan (SVG Chevron Disclosure) --}}
            @php($snackChangeLogs = collect($booking->getRelations()['snackChangeLogs'] ?? ($snackOrderEdits ?? $booking->getRelations()['snackOrderEdits'] ?? [])))
            <div class="rounded-2xl border border-slate-300 dark:border-white/10 bg-surface shadow-sm overflow-hidden">
                <button
                    type="button"
                    @click="historyOpen = !historyOpen"
                    class="flex w-full min-h-[48px] items-center justify-between p-4 text-xs sm:text-sm font-bold text-text hover:bg-surface-2 transition text-left"
                >
                    <span class="flex items-center gap-2">
                        <span>Riwayat Perubahan Jajan</span>
                        <span class="rounded-full bg-surface-2 px-2 py-0.5 text-[11px] font-semibold text-text-muted">
                            {{ $snackChangeLogs->count() }}
                        </span>
                    </span>
                    <svg
                        class="size-4 text-text-muted transition-transform duration-200"
                        :class="historyOpen ? 'rotate-180' : ''"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                <div x-show="historyOpen" x-cloak class="p-4 pt-0 border-t border-border space-y-3">
                    @if ($snackChangeLogs->isNotEmpty())
                        <ol class="mt-3 space-y-3">
                            @foreach ($snackChangeLogs as $change)
                                @php($beforeDetails = collect(data_get($change, 'before_details', data_get($change, 'before', []))))
                                @php($afterDetails = collect(data_get($change, 'after_details', data_get($change, 'after', []))))
                                <li class="border-l-2 border-amber-500 pl-3 text-xs space-y-1">
                                    <div class="flex items-center justify-between font-bold text-text">
                                        <span>{{ data_get($change, 'summary') ?? data_get($change, 'description') ?? 'Pembaruan jajanan' }}</span>
                                        <span class="text-[11px] font-normal text-text-muted">
                                            @if (data_get($change, 'created_at'))
                                                {{ \Illuminate\Support\Carbon::parse(data_get($change, 'created_at'))->locale('id')->translatedFormat('d M, H:i') }}
                                            @endif
                                        </span>
                                    </div>
                                    @if ($beforeDetails->isNotEmpty() || $afterDetails->isNotEmpty())
                                        <div class="grid gap-2 sm:grid-cols-2 text-[11px] text-text-muted pt-1">
                                            <div>
                                                <span class="font-semibold text-text">Sebelum:</span>
                                                @forelse ($beforeDetails as $detail)
                                                    <p class="truncate">{{ data_get($detail, 'name_snapshot', 'Menu') }} × {{ data_get($detail, 'qty', 0) }}@if (data_get($detail, 'note')) · {{ data_get($detail, 'note') }}@endif</p>
                                                @empty
                                                    <p class="italic">Tidak ada</p>
                                                @endforelse
                                            </div>
                                            <div>
                                                <span class="font-semibold text-text">Sesudah:</span>
                                                @forelse ($afterDetails as $detail)
                                                    <p class="truncate">{{ data_get($detail, 'name_snapshot', 'Menu') }} × {{ data_get($detail, 'qty', 0) }}@if (data_get($detail, 'note')) · {{ data_get($detail, 'note') }}@endif</p>
                                                @empty
                                                    <p class="italic">Tidak ada</p>
                                                @endforelse
                                            </div>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <p class="pt-3 text-xs text-text-muted">Belum ada riwayat perubahan tercatat.</p>
                    @endif
                </div>
            </div>

        </div>

        {{-- KOLOM KANAN (4 Kolom Sticky): Ringkasan Pembayaran Snapshot --}}
        <div class="lg:col-span-4 lg:sticky lg:top-24 space-y-4">

            {{-- Kartu Ringkasan Pembayaran Snapshot --}}
            <div class="rounded-2xl border border-slate-300 dark:border-white/10 bg-surface p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-border">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-text-muted">Ringkasan Pembayaran</h2>
                    <span class="badge-accent-soft uppercase font-bold text-[10px]">
                        {{ ['di_tempat' => 'Di Tempat', 'qris' => 'QRIS', 'transfer' => 'Transfer'][$booking->payment_method] ?? $booking->payment_method }}
                    </span>
                </div>

                {{-- Snapshot Breakdown --}}
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between text-text-muted">
                        <span>Tiket ({{ $booking->booking_type === 'private' ? 'Privat 6 kursi' : $booking->pax . ' kursi' }})</span>
                        <span class="font-bold text-text">Rp {{ number_format($booking->ticket_total, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-text-muted">
                        <span>Jajanan & Camilan ({{ $booking->items->sum('qty') }} item)</span>
                        <span class="font-bold text-text">Rp {{ number_format($booking->snack_total, 0, ',', '.') }}</span>
                    </div>
                    <div class="pt-2 border-t border-border flex justify-between items-baseline">
                        <span class="text-xs font-bold uppercase tracking-wider text-text">Total Tagihan</span>
                        <span class="text-xl font-black text-amber-600 dark:text-amber-400">
                            Rp {{ number_format($booking->total_price, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                {{-- Batas Waktu Bayar jika status menunggu --}}
                @if ($booking->status === 'menunggu_pembayaran' && $booking->payment_expires_at)
                    <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-2.5 text-xs text-amber-300 space-y-0.5">
                        <span class="font-bold text-[11px] uppercase tracking-wider">Batas Waktu Bayar:</span>
                        <p class="font-semibold">{{ $booking->payment_expires_at->locale('id')->diffForHumans() }} ({{ $booking->payment_expires_at->format('H.i') }} WIB)</p>
                    </div>
                @endif

                {{-- Bukti Transfer Preview --}}
                @if ($booking->payment_proof_path)
                    <div class="pt-2 border-t border-border space-y-2">
                        <span class="block text-xs font-bold uppercase tracking-wider text-text-muted">Bukti Transfer</span>
                        <a
                            href="{{ route('admin.bookings.proof', $booking) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="group relative block overflow-hidden rounded-xl border border-slate-300 dark:border-white/10 bg-surface-2 p-1 hover:border-amber-400 transition"
                        >
                            <img
                                src="{{ route('admin.bookings.proof', $booking) }}"
                                alt="Bukti transfer {{ $booking->code }}"
                                class="h-36 w-full rounded-lg object-cover"
                            >
                            <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition group-hover:opacity-100">
                                <span class="inline-flex items-center gap-1 rounded-lg bg-black/80 px-2.5 py-1 text-xs font-bold text-white">
                                    <span>Lihat Ukuran Penuh</span>
                                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                </span>
                            </div>
                        </a>
                    </div>
                @endif

                {{-- Kontak Pelanggan --}}
                <div class="pt-3 border-t border-border space-y-2 text-xs">
                    <span class="block font-bold uppercase tracking-wider text-text-muted">Kontak Pemesan</span>
                    <p class="font-semibold text-text">{{ $booking->customer_name }}</p>
                    <p class="font-mono text-text-muted">{{ format_phone_id($booking->whatsapp) }}</p>

                    <a
                        href="https://wa.me/{{ $booking->whatsapp }}?text={{ rawurlencode('Halo '.$booking->customer_name.', admin SynemaX menghubungi terkait booking '.$booking->code.'.') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-2 inline-flex min-h-[40px] w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 text-xs font-bold text-white hover:bg-emerald-500 transition shadow-none"
                    >
                        <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.698c.969.585 1.961.947 3.226.947 3.181 0 5.768-2.587 5.768-5.768.001-3.18-2.585-5.766-5.767-5.766zm9.969 5.768c0 5.503-4.467 9.97-9.97 9.97-1.745 0-3.385-.45-4.819-1.238l-4.711 1.235 1.258-4.599c-.879-1.499-1.398-3.238-1.398-5.098 0-5.503 4.467-9.97 9.97-9.97 5.503 0 9.97 4.467 9.97 9.97z"/>
                        </svg>
                        <span>Chat WhatsApp</span>
                    </a>
                </div>
            </div>

        </div>
    </div>

    {{-- ============================================================== --}}
    {{-- MODAL / DRAWER: CONCESSION EDITOR (BOTTOM SHEET / SIDE PANEL)   --}}
    {{-- ============================================================== --}}
    <div
        x-show="editorOpen"
        x-cloak
        class="fixed inset-0 z-[10000] flex justify-end"
        role="dialog"
        aria-modal="true"
        aria-label="Ubah menu jajanan"
    >
        {{-- Backdrop --}}
        <div
            x-show="editorOpen"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="editorOpen = false"
            class="fixed inset-0 bg-black/60"
        ></div>

        {{-- Panel (Side panel on desktop, bottom sheet on mobile) --}}
        <div
            x-show="editorOpen"
            x-transition:enter="ease-out duration-200 transform"
            x-transition:enter-start="translate-y-full sm:translate-y-0 sm:translate-x-full"
            x-transition:enter-end="translate-y-0 sm:translate-x-0"
            x-transition:leave="ease-in duration-150 transform"
            x-transition:leave-start="translate-y-0 sm:translate-x-0"
            x-transition:leave-end="translate-y-full sm:translate-y-0 sm:translate-x-full"
            class="relative z-10 flex h-[90dvh] sm:h-[100dvh] w-full sm:w-[480px] flex-col rounded-t-2xl sm:rounded-none border-l border-slate-300 dark:border-white/10 bg-surface shadow-2xl mt-auto sm:mt-0"
        >
            {{-- Panel Header --}}
            <div class="flex items-center justify-between border-b border-border p-4 sm:p-5 shrink-0">
                <div>
                    <h3 class="text-base font-bold text-text">Ubah Menu Jajanan</h3>
                    <p class="text-xs text-text-muted">Atur jumlah dan catatan untuk tiap jajanan.</p>
                </div>
                <button
                    type="button"
                    @click="editorOpen = false"
                    class="rounded-xl p-2 text-text-muted hover:text-text hover:bg-surface-2 transition"
                    aria-label="Tutup panel"
                >
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Panel Form Body --}}
            <form method="POST" action="{{ route('admin.bookings.snacks.update', $booking) }}" class="flex flex-1 flex-col overflow-hidden">
                @csrf @method('PATCH')

                <div class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-6">
                    @foreach ($categories as $category)
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                                {{ $category->name }}
                            </h4>

                            <div class="space-y-2">
                                @foreach ($category->items as $menuItem)
                                    @php($current = $booking->items->first(fn ($line) => $line->menu_item_id === $menuItem->id && $line->status !== 'dibatalkan'))
                                    <div
                                        class="rounded-xl border border-slate-300 dark:border-white/10 bg-surface-2/40 p-3 space-y-2"
                                        x-data="{
                                            qty: {{ (int) old('snacks.'.$menuItem->id.'.qty', $current?->qty ?? 0) }},
                                            price: {{ (int) $menuItem->price }},
                                            max: {{ (int) $snackMaxQty }}
                                        }"
                                    >
                                        <div class="flex items-center justify-between gap-2">
                                            <div>
                                                <p class="text-xs font-bold text-text">{{ $menuItem->name }}</p>
                                                <p class="text-[11px] text-text-muted">Rp {{ number_format($menuItem->price, 0, ',', '.') }}</p>
                                            </div>

                                            {{-- Stepper Control --}}
                                            <div class="flex items-center gap-1 rounded-xl border border-slate-300 dark:border-white/15 bg-surface p-1">
                                                <button
                                                    type="button"
                                                    @click="if (qty > 0) qty--"
                                                    class="inline-flex size-8 items-center justify-center rounded-lg text-text hover:bg-surface-2 transition font-bold"
                                                    aria-label="Kurangi jumlah"
                                                >
                                                    -
                                                </button>
                                                <input
                                                    type="number"
                                                    name="snacks[{{ $menuItem->id }}][qty]"
                                                    x-model.number="qty"
                                                    min="0"
                                                    :max="max"
                                                    class="h-8 w-10 text-center font-bold text-xs bg-transparent border-0 focus:outline-none p-0 text-text"
                                                >
                                                <button
                                                    type="button"
                                                    @click="if (qty < max) qty++"
                                                    class="inline-flex size-8 items-center justify-center rounded-lg text-text hover:bg-surface-2 transition font-bold"
                                                    aria-label="Tambah jumlah"
                                                >
                                                    +
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Note Field --}}
                                        <div x-show="qty > 0" x-cloak>
                                            <label for="note-{{ $menuItem->id }}" class="sr-only">Catatan {{ $menuItem->name }}</label>
                                            <input
                                                id="note-{{ $menuItem->id }}"
                                                name="snacks[{{ $menuItem->id }}][note]"
                                                value="{{ old('snacks.'.$menuItem->id.'.note', $current?->note) }}"
                                                maxlength="250"
                                                placeholder="Catatan (misal: tanpa es / pisah bumbu)"
                                                class="min-h-[34px] w-full rounded-lg border border-slate-300 dark:border-white/10 bg-surface px-2.5 text-[11px] text-text placeholder:text-text-muted focus:border-amber-400 focus:outline-none"
                                            >
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Sticky Footer Bar --}}
                <div class="border-t border-border bg-surface p-4 shrink-0 flex items-center justify-between gap-3">
                    <button
                        type="button"
                        @click="editorOpen = false"
                        class="min-h-[44px] rounded-xl border border-slate-300 dark:border-white/15 px-4 text-xs font-semibold text-text hover:bg-surface-2 transition"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="min-h-[44px] flex-1 rounded-xl bg-amber-500 px-4 text-xs sm:text-sm font-bold text-slate-950 hover:bg-amber-400 transition shadow-sm"
                    >
                        Simpan Perubahan Jajan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================== --}}
    {{-- MODAL: TOLAK BUKTI TRANSFER                                     --}}
    {{-- ============================================================== --}}
    <div
        x-show="rejectProofOpen"
        x-cloak
        class="fixed inset-0 z-[10000] flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-label="Tolak bukti transfer"
    >
        <div
            x-show="rejectProofOpen"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="rejectProofOpen = false"
            class="fixed inset-0 bg-black/60"
        ></div>

        <div
            x-show="rejectProofOpen"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative z-10 w-full max-w-md rounded-2xl border border-border bg-surface p-5 shadow-2xl space-y-4"
        >
            <h3 class="text-base font-bold text-text">Tolak Bukti Transfer</h3>
            <p class="text-xs text-text-muted">Masukkan alasan penolakan agar pelanggan dapat membaca penjelasan dan mengunggah ulang bukti transfer yang benar.</p>

            <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" class="space-y-4">
                @csrf @method('PATCH')
                <input type="hidden" name="action" value="tolak_bukti">

                <div>
                    <label for="rejection_note" class="block text-xs font-bold uppercase tracking-wider text-text">Alasan Penolakan</label>
                    <textarea
                        id="rejection_note"
                        name="rejection_note"
                        required
                        rows="3"
                        placeholder="Contoh: Nominal kurang Rp 5.000 / Foto struk buram dan tidak terbaca."
                        class="mt-1.5 w-full rounded-xl border border-border-input bg-surface-2 p-3 text-xs text-text focus:border-amber-400 focus:outline-none"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="rejectProofOpen = false"
                        class="min-h-[40px] rounded-xl border border-border px-4 text-xs font-semibold text-text hover:bg-surface-2"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="min-h-[40px] rounded-xl bg-danger px-4 text-xs font-bold text-white hover:opacity-90"
                    >
                        Tolak Bukti Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
