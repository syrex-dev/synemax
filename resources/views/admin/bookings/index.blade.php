@extends('layouts.admin')

@section('title', 'Kelola booking')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-accent">Pelanggan</p><h1 class="mt-2 text-3xl font-black">Kelola booking</h1></div>
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-accent hover:underline">← Kembali ke ringkasan</a>
    </div>
    <form method="GET" action="{{ route('admin.bookings.index') }}" class="mt-6 grid gap-3 rounded-2xl border border-border bg-surface p-4 sm:grid-cols-[1fr_1.5fr_auto_auto] sm:items-end">
        @if (request()->filled('session_id'))
            <input type="hidden" name="session_id" value="{{ request('session_id') }}">
        @endif
        <div><label for="status" class="text-sm font-semibold text-text-muted">Filter status</label><select id="status" name="status" class="mt-2 min-h-12 w-full rounded-lg border border-border bg-bg px-3 text-sm text-text"><option value="">Semua status</option>@foreach (['menunggu_konfirmasi' => 'Menunggu konfirmasi', 'terkonfirmasi' => 'Terkonfirmasi', 'hadir' => 'Hadir', 'tidak_hadir' => 'Tidak hadir', 'dibatalkan' => 'Dibatalkan'] as $key => $label)<option value="{{ $key }}" @selected($selectedStatus === $key)>{{ $label }}</option>@endforeach</select></div>
        <div><label for="q" class="text-sm font-semibold text-text-muted">Cari nama, kode, atau WhatsApp</label><input id="q" name="q" type="search" value="{{ $search }}" class="mt-2 min-h-12 w-full rounded-lg border border-border bg-bg px-3 text-sm text-text"></div>
        <button class="min-h-12 rounded-lg bg-accent px-5 text-sm font-bold text-accent-contrast hover:opacity-90">Cari booking</button>
        @if ($search || $selectedStatus)
            <a href="{{ route('admin.bookings.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-lg border border-border px-4 text-sm font-semibold hover:bg-surface-2">Hapus filter</a>
        @endif
    </form>

    <div class="mt-5 space-y-3">
        @forelse ($bookings as $booking)
            @php
                $statusLabels = [
                    'menunggu_konfirmasi' => 'Menunggu konfirmasi',
                    'terkonfirmasi' => 'Terkonfirmasi',
                    'hadir' => 'Hadir',
                    'tidak_hadir' => 'Tidak hadir',
                    'dibatalkan' => 'Dibatalkan',
                ];
                $statusStyles = [
                    'menunggu_konfirmasi' => 'bg-warning/10 text-warning',
                    'terkonfirmasi' => 'bg-accent/10 text-accent',
                    'hadir' => 'bg-success/10 text-success',
                    'tidak_hadir' => 'bg-surface-2 text-text-muted',
                    'dibatalkan' => 'bg-danger/10 text-danger',
                ];
                $whatsAppMessage = 'Halo '.$booking->customer_name.', admin Home Cinema menghubungi untuk booking '.$booking->code.'.';
            @endphp
            <article class="rounded-2xl border border-border bg-surface p-4 sm:p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2"><h2 class="font-bold">{{ $booking->customer_name }}</h2><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $statusStyles[$booking->status] ?? 'bg-surface-2 text-text-muted' }}">{{ $statusLabels[$booking->status] ?? $booking->status }}</span>@if ($booking->customerProfile?->no_show_count >= 2)<span class="rounded-full bg-danger/10 px-2 py-1 text-xs font-semibold text-danger">Peringatan no-show {{ $booking->customerProfile->no_show_count }}×</span>@endif</div>
                        <p class="mt-1 font-mono text-sm text-accent">{{ $booking->code }}</p>
                    </div>
                    <div class="text-left sm:text-right"><p class="text-lg font-bold">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p><p class="mt-1 text-xs text-text-muted">{{ $booking->booking_type === 'private' ? 'Sewa Private (6 kursi)' : $booking->pax.' orang' }}</p><p class="mt-1 text-xs text-text-muted">Tiket Rp {{ number_format($booking->ticket_total, 0, ',', '.') }} · Jajan Rp {{ number_format($booking->snack_total, 0, ',', '.') }}</p></div>
                </div>
                <div class="mt-4 grid gap-2 border-t border-border pt-4 text-sm sm:grid-cols-2">
                    <p><span class="text-text-muted">WhatsApp:</span> {{ $booking->whatsapp }}</p>
                    <p><span class="text-text-muted">Film:</span> {{ $booking->film?->title ?? '—' }}</p>
                    <p><span class="text-text-muted">Jadwal:</span> {{ $booking->session->session_date }} · {{ substr((string) $booking->session->start_time, 0, 5) }} WIB</p>
                    <p><span class="text-text-muted">Bayar:</span> {{ ['di_tempat' => 'Di tempat', 'qris' => 'QRIS', 'transfer' => 'Transfer'][$booking->payment_method] ?? $booking->payment_method }}</p>
                </div>
                <div class="mt-4 grid gap-2 sm:flex sm:flex-wrap">
                    <a href="{{ route('admin.bookings.show', $booking) }}" class="inline-flex min-h-12 items-center justify-center rounded-lg border border-accent px-4 text-sm font-semibold text-accent hover:bg-surface-2">Detail & jajan</a>
                    <a href="https://wa.me/{{ $booking->whatsapp }}?text={{ rawurlencode($whatsAppMessage) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-12 items-center justify-center rounded-lg bg-success px-4 text-sm font-bold text-success-contrast hover:opacity-90">Chat WhatsApp</a>
                    @if ($booking->payment_proof_path)
                        <a href="{{ route('admin.bookings.proof', $booking) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-12 items-center justify-center rounded-lg border border-border px-4 text-sm font-semibold hover:bg-surface-2">Lihat bukti bayar</a>
                    @endif
                    @if ($booking->customerProfile)
                        <form method="POST" action="{{ route('admin.customers.toggle-block', $booking->customerProfile) }}" onsubmit="return confirm('{{ $booking->customerProfile->is_blocked ? 'Buka blokir nomor WhatsApp ini?' : 'Blokir nomor WhatsApp ini dari booking baru?' }}')">@csrf @method('PATCH')
                            <button class="min-h-12 w-full rounded-lg border border-border px-4 text-sm font-semibold {{ $booking->customerProfile->is_blocked ? 'text-success' : 'text-danger' }}">{{ $booking->customerProfile->is_blocked ? 'Buka blokir nomor' : 'Blokir nomor' }}</button>
                        </form>
                    @endif
                </div>
                @if (in_array($booking->status, ['menunggu_konfirmasi', 'terkonfirmasi'], true))
                    <div class="mt-4 grid gap-2 border-t border-border pt-4 sm:flex sm:flex-wrap">
                        @if ($booking->status === 'menunggu_konfirmasi')
                            <form method="POST" action="{{ route('admin.bookings.status', $booking) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="konfirmasi"><button class="min-h-12 w-full rounded-lg bg-accent px-4 text-sm font-bold text-accent-contrast sm:w-auto">Konfirmasi</button></form>
                        @else
                            <form method="POST" action="{{ route('admin.bookings.status', $booking) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="hadir"><button class="min-h-12 w-full rounded-lg bg-success px-4 text-sm font-bold text-success-contrast sm:w-auto">Tandai hadir</button></form>
                            <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" onsubmit="return confirm('Tandai pemesan tidak hadir? Ini akan menambah catatan no-show pelanggan.')">@csrf @method('PATCH')<input type="hidden" name="action" value="tidak_hadir"><button class="min-h-12 w-full rounded-lg border border-warning px-4 text-sm font-semibold text-warning sm:w-auto">Tidak hadir (no-show)</button></form>
                        @endif
                        <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" onsubmit="return confirm('Batalkan booking ini? Tindakan ini tidak dapat dibatalkan.')">@csrf @method('PATCH')<input type="hidden" name="action" value="batal"><button class="min-h-12 w-full rounded-lg border border-danger px-4 text-sm font-semibold text-danger sm:w-auto">Batalkan booking</button></form>
                    </div>
                @endif
            </article>
        @empty
            <p class="rounded-xl border border-border bg-surface p-5 text-sm text-text-muted">Belum ada booking yang cocok dengan filter ini.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $bookings->links() }}</div>
@endsection
