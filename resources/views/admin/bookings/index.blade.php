@extends('layouts.admin')

@section('title', 'Kelola booking')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-accent">Pelanggan</p><h1 class="mt-2 text-3xl font-black">Kelola booking</h1></div>
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-accent hover:underline">← Kembali ke ringkasan</a>
    </div>
    <form method="GET" action="{{ route('admin.bookings.index') }}" class="mt-6 grid gap-3 rounded-2xl border border-border bg-surface p-4 sm:grid-cols-[1fr_1fr_1.5fr_auto_auto] sm:items-end">
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
        <button class="min-h-12 rounded-lg bg-accent px-5 text-sm font-bold text-accent-contrast hover:opacity-90">Filter</button>
        @if ($search || $selectedStatus || ($selectedMethod ?? ''))
            <a href="{{ route('admin.bookings.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-lg border border-border px-4 text-sm font-semibold hover:bg-surface-2">Reset</a>
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
                $whatsAppMessage = 'Halo '.$booking->customer_name.', admin Home Cinema menghubungi untuk booking '.$booking->code.'.';
            @endphp
            <article class="rounded-2xl border border-border bg-surface p-4 sm:p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-bold text-text">{{ $booking->customer_name }}</h2>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $statusStyles[$booking->status] ?? 'bg-surface-2 text-text-muted' }}">{{ $statusLabels[$booking->status] ?? $booking->status }}</span>
                            @if ($booking->payment_proof_path)
                                <span class="rounded-full bg-success/15 px-2 py-0.5 text-xs font-bold text-success">Bukti diunggah</span>
                            @elseif ($booking->isOnlinePayment() && $booking->status === 'menunggu_pembayaran')
                                <span class="rounded-full bg-warning/10 px-2 py-0.5 text-xs text-warning">Belum ada bukti</span>
                            @endif
                            @if ($booking->customerProfile?->no_show_count >= 2)
                                <span class="rounded-full bg-danger/10 px-2 py-1 text-xs font-semibold text-danger">Peringatan no-show {{ $booking->customerProfile->no_show_count }}×</span>
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
                    <p><span class="text-text-muted">WhatsApp:</span> {{ $booking->whatsapp }}</p>
                    <p><span class="text-text-muted">Jadwal:</span> {{ \Carbon\Carbon::parse($booking->session->session_date)->locale('id')->translatedFormat('D, d M Y') }} · {{ str_replace(':', '.', substr((string) $booking->session->start_time, 0, 5)) }} WIB</p>
                    <p><span class="text-text-muted">Metode bayar:</span> <strong class="uppercase text-text">{{ ['di_tempat' => 'Di tempat', 'qris' => 'QRIS', 'transfer' => 'Transfer'][$booking->payment_method] ?? $booking->payment_method }}</strong></p>
                    @if ($booking->status === 'menunggu_pembayaran' && $booking->payment_expires_at)
                        <p class="sm:col-span-2 text-xs text-warning font-semibold">Batas bayar: {{ $booking->payment_expires_at->locale('id')->diffForHumans() }} ({{ $booking->payment_expires_at->format('H.i') }} WIB)</p>
                    @endif
                </div>

                <div class="mt-4 grid gap-2 sm:flex sm:flex-wrap">
                    <a href="{{ route('admin.bookings.show', $booking) }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-accent px-4 text-sm font-semibold text-accent hover:bg-surface-2">Detail & jajan</a>
                    <a href="https://wa.me/{{ $booking->whatsapp }}?text={{ rawurlencode($whatsAppMessage) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-success px-4 text-sm font-bold text-success-contrast hover:opacity-90">Chat WhatsApp</a>
                    @if ($booking->payment_proof_path)
                        <a href="{{ route('admin.bookings.proof', $booking) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-border px-4 text-sm font-semibold hover:bg-surface-2">Lihat bukti bayar ↗</a>
                    @endif
                    @if ($booking->customerProfile)
                        <form method="POST" action="{{ route('admin.customers.toggle-block', $booking->customerProfile) }}" data-confirm="{{ $booking->customerProfile->is_blocked ? 'Buka blokir nomor WhatsApp ini?' : 'Blokir nomor WhatsApp ini dari booking baru?' }}" data-confirm-title="{{ $booking->customerProfile->is_blocked ? 'Buka Blokir Nomor' : 'Blokir Nomor' }}" data-confirm-action="{{ $booking->customerProfile->is_blocked ? 'Buka Blokir' : 'Blokir' }}" data-confirm-is-danger="{{ $booking->customerProfile->is_blocked ? 'false' : 'true' }}" novalidate>@csrf @method('PATCH')
                            <button class="min-h-11 w-full rounded-lg border border-border px-4 text-sm font-semibold {{ $booking->customerProfile->is_blocked ? 'text-success' : 'text-danger' }}">{{ $booking->customerProfile->is_blocked ? 'Buka blokir' : 'Blokir nomor' }}</button>
                        </form>
                    @endif
                </div>

                @if (in_array($booking->status, ['menunggu_pembayaran', 'menunggu_konfirmasi', 'terkonfirmasi', 'kedaluwarsa'], true))
                    <div class="mt-4 grid gap-2 border-t border-border pt-4 sm:flex sm:flex-wrap">
                        @if ($booking->status === 'menunggu_pembayaran')
                            <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" data-confirm="Tandai booking ini sudah dibayar?" data-confirm-title="Konfirmasi Pembayaran" data-confirm-action="Tandai Dibayar" novalidate>
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="tandai_dibayar">
                                <button class="min-h-11 w-full rounded-lg bg-accent px-4 text-sm font-bold text-accent-contrast sm:w-auto">Tandai sudah dibayar</button>
                            </form>
                            <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" data-confirm="Batalkan booking ini?" data-confirm-title="Batalkan Booking" data-confirm-action="Batalkan" data-confirm-is-danger="true" novalidate>
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="batal">
                                <button class="min-h-11 w-full rounded-lg border border-danger px-4 text-sm font-semibold text-danger sm:w-auto">Batalkan</button>
                            </form>
                        @elseif ($booking->status === 'menunggu_konfirmasi')
                            <form method="POST" action="{{ route('admin.bookings.status', $booking) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="konfirmasi">
                                <button class="min-h-11 w-full rounded-lg bg-accent px-4 text-sm font-bold text-accent-contrast sm:w-auto">Konfirmasi booking</button>
                            </form>
                            @if ($booking->payment_proof_path)
                                <div x-data="{ openReject: false }">
                                    <button type="button" @click="openReject = true" class="min-h-11 w-full rounded-lg border border-warning px-4 text-sm font-semibold text-warning sm:w-auto">Tolak bukti</button>
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
                                                    <button type="button" @click="openReject = false" class="min-h-10 rounded-lg border border-border px-3 text-xs font-semibold">Batal</button>
                                                    <button class="min-h-10 rounded-lg bg-danger px-3 text-xs font-bold text-danger-contrast">Tolak Bukti</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" data-confirm="Batalkan booking ini?" data-confirm-title="Batalkan Booking" data-confirm-action="Batalkan" data-confirm-is-danger="true" novalidate>
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="batal">
                                <button class="min-h-11 w-full rounded-lg border border-danger px-4 text-sm font-semibold text-danger sm:w-auto">Batalkan</button>
                            </form>
                        @elseif ($booking->status === 'terkonfirmasi')
                            <form method="POST" action="{{ route('admin.bookings.status', $booking) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="hadir">
                                <button class="min-h-11 w-full rounded-lg bg-success px-4 text-sm font-bold text-success-contrast sm:w-auto">Tandai hadir</button>
                            </form>
                            <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" data-confirm="Tandai pemesan tidak hadir? Ini akan menambah catatan no-show pelanggan." data-confirm-title="Tandai No-Show" data-confirm-action="Tandai Tidak Hadir" data-confirm-is-danger="true" novalidate>
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="tidak_hadir">
                                <button class="min-h-11 w-full rounded-lg border border-warning px-4 text-sm font-semibold text-warning sm:w-auto">Tidak hadir (no-show)</button>
                            </form>
                            <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" data-confirm="Batalkan booking ini?" data-confirm-title="Batalkan Booking" data-confirm-action="Batalkan" data-confirm-is-danger="true" novalidate>
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="batal">
                                <button class="min-h-11 w-full rounded-lg border border-danger px-4 text-sm font-semibold text-danger sm:w-auto">Batalkan</button>
                            </form>
                        @elseif ($booking->status === 'kedaluwarsa')
                            <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" data-confirm="Pulihkan booking kedaluwarsa ini? Kursi akan kembali ditahan dengan batas waktu baru jika kapasitas sesi masih tersedia." data-confirm-title="Pulihkan Booking" data-confirm-action="Pulihkan" novalidate>
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="pulihkan">
                                <button class="min-h-11 w-full rounded-lg border border-accent bg-accent/10 px-4 text-sm font-bold text-accent sm:w-auto hover:bg-accent/20">Pulihkan booking</button>
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
