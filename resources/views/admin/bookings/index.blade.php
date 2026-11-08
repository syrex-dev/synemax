@extends('layouts.admin')

@section('title', 'Kelola booking')

@section('content')
<div x-data="{ cleanModalOpen: false }" class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-accent">Pelanggan</p>
            <h1 class="mt-2 text-3xl font-black">Kelola booking</h1>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.bookings.create') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-accent px-4 py-2 text-sm font-bold text-accent-contrast shadow-sm transition hover:opacity-90 active:scale-95">
                <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>Pesan di Tempat (Loket)</span>
            </a>

            @if ($bookings->total() > 0)
                <button
                    type="button"
                    @click="cleanModalOpen = true"
                    class="inline-flex min-h-11 items-center gap-1.5 rounded-xl border border-rose-500/30 bg-rose-500/10 px-3.5 py-2 text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-500/20 active:scale-95 transition"
                    title="Hapus riwayat booking testing"
                >
                    <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Bersihkan Data Testing</span>
                </button>
            @endif

            <a href="{{ route('admin.dashboard') }}" class="text-sm text-accent hover:underline ml-1">← Ringkasan</a>
        </div>
    </div>

    {{-- Modal Konfirmasi Bersihkan Data Testing --}}
    <div
        x-show="cleanModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div
            @click.outside="cleanModalOpen = false"
            class="w-full max-w-md rounded-2xl border border-border bg-surface p-6 shadow-2xl space-y-4"
        >
            <div class="flex items-start gap-3.5">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-rose-500/15 text-rose-500">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-black text-text">Bersihkan Data Booking Testing?</h3>
                    <p class="mt-1 text-xs text-text-muted leading-relaxed">
                        Tindakan ini akan menghapus seluruh riwayat booking, pesanan jajan, dan bukti bayar testing. Laporan keuangan akan kembali ke Rp 0 dan kapasitas kursi sesi akan di-reset bersih untuk transaksi nyata.
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.bookings.clean_test_data') }}" class="space-y-4 pt-2 border-t border-border/60">
                @csrf
                <label class="flex items-start gap-2.5 cursor-pointer text-xs text-text">
                    <input type="checkbox" name="reset_member_visits" value="1" class="mt-0.5 rounded text-rose-500 focus:ring-rose-500">
                    <span>Reset juga stempel kunjungan member yang dibuat selama uji coba</span>
                </label>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button
                        type="button"
                        @click="cleanModalOpen = false"
                        class="min-h-10 rounded-xl border border-border bg-surface px-4 text-xs font-semibold text-text hover:bg-surface-2 transition"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="min-h-10 rounded-xl bg-rose-600 px-4 text-xs font-bold text-white hover:bg-rose-500 active:scale-95 transition flex items-center gap-1.5"
                    >
                        <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span>Ya, Bersihkan Data Testing</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

    {{-- Live Quick Search --}}
    <div class="mt-6">
        <x-admin.live-booking-search />
    </div>

    <form method="GET" action="{{ route('admin.bookings.index') }}" class="mt-4 grid gap-3 rounded-2xl border border-border bg-surface p-4 sm:grid-cols-[1fr_1fr_1.5fr_auto_auto] sm:items-end">
        @if (request()->filled('session_id'))
            <input type="hidden" name="session_id" value="{{ request('session_id') }}">
        @endif
        <div>
            <x-ui.select
                name="status"
                id="status"
                label="Status"
                placeholder="Semua status"
                :value="$selectedStatus"
                :autoSubmit="true"
                :options="[
                    '' => 'Semua status',
                    'menunggu_pembayaran' => 'Menunggu pembayaran',
                    'menunggu_konfirmasi' => 'Menunggu konfirmasi',
                    'terkonfirmasi' => 'Terkonfirmasi',
                    'kedaluwarsa' => 'Kedaluwarsa',
                    'hadir' => 'Hadir',
                    'tidak_hadir' => 'Tidak hadir',
                    'dibatalkan' => 'Dibatalkan',
                ]"
            />
        </div>
        <div>
            <x-ui.select
                name="payment_method"
                id="payment_method"
                label="Metode bayar"
                placeholder="Semua metode"
                :value="$selectedMethod ?? ''"
                :autoSubmit="true"
                :options="[
                    '' => 'Semua metode',
                    'di_tempat' => 'Bayar di tempat',
                    'qris' => 'QRIS',
                    'transfer' => 'Transfer',
                ]"
            />
        </div>
        <div>
            <label for="q" class="text-sm font-semibold text-text-muted">Cari booking</label>
            <input id="q" name="q" type="search" placeholder="Nama, kode, WhatsApp…" value="{{ $search }}" class="mt-2 min-h-12 w-full rounded-lg border border-border bg-bg px-3 text-sm text-text">
        </div>
        <button class="min-h-12 rounded-lg bg-accent px-5 text-sm font-bold text-accent-contrast hover:opacity-90 active:scale-95 transition">Filter</button>
        @if ($search || $selectedStatus || ($selectedMethod ?? ''))
            <a href="{{ route('admin.bookings.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-lg border border-border px-4 text-sm font-semibold hover:bg-surface-2 active:scale-95 transition">Reset</a>
        @endif
    </form>

    <div class="mt-5 space-y-3">
        @forelse ($bookings as $booking)
            @php
                $statusLabels = [
                    'menunggu_pembayaran' => 'Menunggu pembayaran',
                    'menunggu_konfirmasi' => 'Menunggu konfirmasi',
                    'terkonfirmasi' => 'Terkonfirmasi',
                    'kedaluwarsa' => 'Kedaluwarsa',
                    'hadir' => 'Hadir',
                    'tidak_hadir' => 'Tidak hadir',
                    'dibatalkan' => 'Dibatalkan',
                ];
                $statusStyles = [
                    'menunggu_pembayaran' => 'bg-warning/15 text-warning',
                    'menunggu_konfirmasi' => 'bg-accent/15 text-accent',
                    'terkonfirmasi' => 'bg-success/15 text-success',
                    'kedaluwarsa' => 'bg-danger/10 text-danger',
                    'hadir' => 'bg-success/10 text-success',
                    'tidak_hadir' => 'bg-surface-2 text-text-muted',
                    'dibatalkan' => 'bg-danger/10 text-danger',
                ];
                $whatsAppMessage = 'Halo '.$booking->customer_name.', admin SynemaX menghubungi untuk booking '.$booking->code.'.';
                if ($booking->notes) {
                    $whatsAppMessage .= "\nCatatan pemesan: ".$booking->notes;
                }
                $hasNotes = !empty($booking->notes) || $booking->items->contains(fn ($i) => !empty($i->note));
            @endphp
            <article class="rounded-2xl border border-border bg-surface p-4 sm:p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-bold text-text">{{ $booking->customer_name }}</h2>
                            <x-ui.status :status="$booking->status" />
                            @if ($booking->payment_proof_path)
                                <span class="rounded-full bg-success/15 px-2 py-0.5 text-xs font-bold text-success">Bukti diunggah</span>
                            @elseif ($booking->isOnlinePayment() && $booking->status === 'menunggu_pembayaran')
                                <span class="rounded-full bg-warning/10 px-2 py-0.5 text-xs text-warning">Belum ada bukti</span>
                            @endif
                            @if ($booking->customerProfile?->no_show_count >= 2)
                                <span class="rounded-full bg-danger/10 px-2 py-1 text-xs font-semibold text-danger">Peringatan tidak datang {{ $booking->customerProfile->no_show_count }}×</span>
                            @endif
                            @if ($hasNotes)
                                <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-xs font-bold text-amber-600 dark:text-amber-400">Ada catatan</span>
                            @endif
                        </div>
                        <p class="mt-1 font-mono text-sm font-black text-accent">{{ $booking->code }}</p>
                    </div>
                    <div class="text-left sm:text-right">
                        <p class="text-lg font-black text-accent">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
                        <p class="mt-1 text-xs text-text-muted">{{ $booking->booking_type === 'private' ? 'Sewa Private (6 kursi)' : $booking->pax.' orang' }}</p>
                        <p class="mt-1 text-xs text-text-muted">Tiket Rp {{ number_format($booking->ticket_total, 0, ',', '.') }} · Jajan Rp {{ number_format($booking->snack_total, 0, ',', '.') }}</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-2 border-t border-border pt-4 text-sm sm:grid-cols-2">
                    <p><span class="text-text-muted">WhatsApp:</span> {{ format_phone_id($booking->whatsapp) }}</p>
                    <p><span class="text-text-muted">Jadwal:</span> {{ format_tanggal_id($booking->session->session_date) }} · {{ format_jam_id($booking->session->start_time) }}</p>
                    <p><span class="text-text-muted">Metode bayar:</span> <strong class="uppercase text-text">{{ ['di_tempat' => 'Di tempat', 'qris' => 'QRIS', 'transfer' => 'Transfer'][$booking->payment_method] ?? $booking->payment_method }}</strong></p>
                    @if ($booking->status === 'menunggu_pembayaran' && $booking->payment_expires_at)
                        <p class="sm:col-span-2 text-xs text-warning font-semibold">Batas bayar: {{ $booking->payment_expires_at->locale('id')->diffForHumans() }} ({{ $booking->payment_expires_at->format('H.i') }} WIB)</p>
                    @endif
                    @if ($booking->notes)
                        <div class="sm:col-span-2 rounded-lg border border-amber-500/25 bg-amber-500/10 p-2.5 text-xs text-text">
                            <span class="font-bold text-amber-600 dark:text-amber-400">Catatan Pemesan:</span>
                            <span class="font-medium">{{ $booking->notes }}</span>
                        </div>
                    @endif
                    @php($snackNotes = $booking->items->filter(fn ($i) => !empty($i->note)))
                    @if ($snackNotes->isNotEmpty())
                        <div class="sm:col-span-2 text-xs text-text-muted space-y-0.5">
                            <span class="font-bold text-amber-600 dark:text-amber-400">Catatan Jajan:</span>
                            @foreach ($snackNotes as $sn)
                                <p><span class="font-semibold text-text">{{ $sn->name_snapshot }}:</span> {{ $sn->note }}</p>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="mt-4 grid gap-2 sm:flex sm:flex-wrap">
                    <a href="{{ route('admin.bookings.show', $booking) }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-500 hover:text-slate-950 dark:hover:text-slate-950 active:scale-95 transition shadow-xs">Detail & jajan</a>
                    <x-ui.whatsapp-button variant="admin" :phone="$booking->whatsapp" :message="$whatsAppMessage" label="WhatsApp pemesan" />
                    @if ($booking->payment_proof_path)
                        <a href="{{ route('admin.bookings.proof', $booking) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-lg border border-border px-4 text-sm font-semibold hover:bg-surface-2 active:scale-95 transition">
                            <span>Lihat bukti bayar</span>
                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                        </a>
                    @endif
                    @if ($booking->customerProfile)
                        <form method="POST" action="{{ route('admin.customers.toggle-block', $booking->customerProfile) }}" data-confirm="{{ $booking->customerProfile->is_blocked ? 'Buka blokir nomor WhatsApp ini?' : 'Blokir nomor WhatsApp ini dari booking baru?' }}" data-confirm-title="{{ $booking->customerProfile->is_blocked ? 'Buka Blokir Nomor' : 'Blokir Nomor' }}" data-confirm-action="{{ $booking->customerProfile->is_blocked ? 'Buka Blokir' : 'Blokir' }}" data-confirm-is-danger="{{ $booking->customerProfile->is_blocked ? 'false' : 'true' }}" novalidate>@csrf @method('PATCH')
                            <button class="min-h-11 w-full rounded-lg border border-border px-4 text-sm font-semibold {{ $booking->customerProfile->is_blocked ? 'text-success hover:bg-success/10 hover:border-success/40' : 'text-danger hover:bg-danger/10 hover:border-danger/40' }} active:scale-95 transition">{{ $booking->customerProfile->is_blocked ? 'Buka blokir' : 'Blokir nomor' }}</button>
                        </form>
                    @endif
                </div>

                @if (in_array($booking->status, ['menunggu_pembayaran', 'menunggu_konfirmasi', 'terkonfirmasi', 'kedaluwarsa'], true))
                    <div class="mt-4 grid gap-2 border-t border-border pt-4 sm:flex sm:flex-wrap">
                        @if ($booking->status === 'menunggu_pembayaran')
                            <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" data-confirm="Tandai booking ini sudah dibayar?" data-confirm-title="Konfirmasi Pembayaran" data-confirm-action="Tandai Dibayar" novalidate>
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="tandai_dibayar">
                                <button class="min-h-11 w-full rounded-lg bg-accent px-4 text-sm font-bold text-accent-contrast hover:opacity-90 active:scale-95 transition shadow-xs sm:w-auto">Tandai sudah dibayar</button>
                            </form>
                            <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" data-confirm="Batalkan booking ini?" data-confirm-title="Batalkan Booking" data-confirm-action="Batalkan" data-confirm-is-danger="true" novalidate>
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="batal">
                                <button class="min-h-11 w-full rounded-lg border border-danger/60 px-4 text-sm font-semibold text-danger hover:bg-danger/10 hover:border-danger active:scale-95 transition sm:w-auto">Batalkan</button>
                            </form>
                        @elseif ($booking->status === 'menunggu_konfirmasi')
                            <form method="POST" action="{{ route('admin.bookings.status', $booking) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="konfirmasi">
                                <button class="min-h-11 w-full rounded-lg bg-accent px-4 text-sm font-bold text-accent-contrast hover:opacity-90 active:scale-95 transition shadow-xs sm:w-auto">Konfirmasi booking</button>
                            </form>
                            @if ($booking->payment_proof_path)
                                <div x-data="{ openReject: false }">
                                    <button type="button" @click="openReject = true" class="min-h-11 w-full rounded-lg border border-warning/60 px-4 text-sm font-semibold text-warning hover:bg-warning/10 hover:border-warning active:scale-95 transition sm:w-auto">Tolak bukti</button>
                                    <div x-show="openReject" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-overlay p-4" @click.self="openReject = false">
                                        <div class="w-full max-w-md rounded-2xl border border-border bg-surface p-5 shadow-token">
                                            <h3 class="text-base font-bold text-text">Tolak Bukti Transfer</h3>
                                            <p class="mt-1 text-xs text-text-muted">Masukkan alasan penolakan. Status booking akan dikembalikan ke menunggu pembayaran dan pelanggan dapat mengunggah bukti baru.</p>
                                            <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" class="mt-4">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="action" value="tolak_bukti">
                                                <label for="reject_note_{{ $booking->id }}" class="text-xs font-semibold text-text">Alasan Penolakan</label>
                                                <textarea id="reject_note_{{ $booking->id }}" name="rejection_note" required rows="3" placeholder="Contoh: Nominal kurang / struk tidak terbaca" class="mt-1 w-full rounded-xl border border-border bg-bg p-3 text-sm text-text"></textarea>
                                                <div class="mt-4 flex justify-end gap-2">
                                                    <button type="button" @click="openReject = false" class="min-h-10 rounded-lg border border-border px-3 text-xs font-semibold hover:bg-surface-2 transition">Batal</button>
                                                    <button class="min-h-10 rounded-lg bg-danger px-3 text-xs font-bold text-danger-contrast hover:opacity-90 active:scale-95 transition">Tolak Bukti</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" data-confirm="Batalkan booking ini?" data-confirm-title="Batalkan Booking" data-confirm-action="Batalkan" data-confirm-is-danger="true" novalidate>
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="batal">
                                <button class="min-h-11 w-full rounded-lg border border-danger/60 px-4 text-sm font-semibold text-danger hover:bg-danger/10 hover:border-danger active:scale-95 transition sm:w-auto">Batalkan</button>
                            </form>
                        @elseif ($booking->status === 'terkonfirmasi')
                            <a href="{{ route('admin.scan.index', ['sesi' => $booking->session_id]) }}" class="inline-flex min-h-11 w-full items-center justify-center gap-1.5 rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 text-sm font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 active:scale-95 sm:w-auto transition">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                </svg>
                                <span>Scan tiket</span>
                            </a>
                            <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" data-confirm="Tandai pemesan tidak hadir? Ini akan menambah catatan tidak datang pada profil pelanggan." data-confirm-title="Tandai Tidak Datang" data-confirm-action="Tandai Tidak Datang" data-confirm-is-danger="true" novalidate>
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="tidak_hadir">
                                <button class="min-h-11 w-full rounded-lg border border-warning/60 px-4 text-sm font-semibold text-warning hover:bg-warning/10 hover:border-warning active:scale-95 transition sm:w-auto">Tandai tidak datang</button>
                            </form>
                            <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" data-confirm="Batalkan booking ini?" data-confirm-title="Batalkan Booking" data-confirm-action="Batalkan" data-confirm-is-danger="true" novalidate>
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="batal">
                                <button class="min-h-11 w-full rounded-lg border border-danger/60 px-4 text-sm font-semibold text-danger hover:bg-danger/10 hover:border-danger active:scale-95 transition sm:w-auto">Batalkan</button>
                            </form>
                        @elseif ($booking->status === 'kedaluwarsa')
                            <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" data-confirm="Pulihkan booking kedaluwarsa ini? Kursi akan kembali ditahan dengan batas waktu baru jika kapasitas sesi masih tersedia." data-confirm-title="Pulihkan Booking" data-confirm-action="Pulihkan" novalidate>
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="pulihkan">
                                <button class="min-h-11 w-full rounded-lg border border-accent bg-accent/10 px-4 text-sm font-bold text-accent hover:bg-accent/20 active:scale-95 transition sm:w-auto">Pulihkan booking</button>
                            </form>
                        @endif
                    </div>
                @endif
            </article>
        @empty
            <p class="rounded-xl border border-border bg-surface p-5 text-sm text-text-muted">Belum ada booking yang cocok dengan filter ini.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $bookings->links() }}</div>
@endsection
