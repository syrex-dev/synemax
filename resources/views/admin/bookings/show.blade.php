@extends('layouts.admin')

@section('title', 'Detail booking')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-accent">Pelanggan</p><h1 class="mt-2 text-3xl font-black">Detail booking</h1><p class="mt-1 font-mono text-sm text-accent">{{ $booking->code }}</p></div>
        <a href="{{ route('admin.bookings.index') }}" class="text-sm text-accent hover:underline">← Kembali ke booking</a>
    </div>
    <article class="mt-6 rounded-2xl border border-border bg-surface p-5">
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
        @endphp
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border pb-4">
            <div class="flex items-center gap-2">
                <span class="text-xs text-text-muted">Status:</span>
                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold {{ $statusStyles[$booking->status] ?? 'bg-surface-2 text-text-muted' }}">
                    {{ $statusLabels[$booking->status] ?? $booking->status }}
                </span>
                @if ($booking->payment_proof_path)
                    <span class="rounded-full bg-success/15 px-2 py-0.5 text-xs font-bold text-success">Bukti diunggah</span>
                @endif
            </div>
            <p class="text-xs text-text-muted">Metode bayar: <strong class="uppercase text-text">{{ ['di_tempat' => 'Bayar di tempat', 'qris' => 'QRIS', 'transfer' => 'Transfer'][$booking->payment_method] ?? $booking->payment_method }}</strong></p>
        </div>

        <div class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
            <p><span class="text-text-muted">Pemesan:</span> {{ $booking->customer_name }} · {{ $booking->whatsapp }}</p>
            <p><span class="text-text-muted">Sesi:</span> {{ \Carbon\Carbon::parse($booking->session->session_date)->locale('id')->translatedFormat('D, d M Y') }} · {{ str_replace(':', '.', substr((string) $booking->session->start_time, 0, 5)) }} WIB</p>
            <p><span class="text-text-muted">Film:</span> {{ $booking->film?->title ?? '—' }}</p>
            <p><span class="text-text-muted">Jumlah:</span> {{ $booking->booking_type === 'private' ? 'Sewa Private (6 kursi)' : $booking->pax.' orang' }}</p>
            <p><span class="text-text-muted">Tiket:</span> Rp {{ number_format($booking->ticket_total, 0, ',', '.') }}</p>
            <p><span class="text-text-muted">Jajan:</span> Rp {{ number_format($booking->snack_total, 0, ',', '.') }}</p>
            <p class="font-black text-accent text-lg sm:col-span-2">Total booking: Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
            @if ($booking->status === 'menunggu_pembayaran' && $booking->payment_expires_at)
                <p class="sm:col-span-2 text-xs font-semibold text-warning">Batas waktu pembayaran: {{ $booking->payment_expires_at->locale('id')->diffForHumans() }} ({{ $booking->payment_expires_at->format('H.i') }} WIB)</p>
            @endif
        </div>

        @if ($booking->proof_rejection_note)
            <div class="mt-4 rounded-xl border border-danger/40 bg-danger/10 p-3.5 text-xs text-danger">
                <p class="font-bold">Bukti transfer sebelumnya ditolak admin:</p>
                <p class="mt-1 font-semibold underline">{{ $booking->proof_rejection_note }}</p>
            </div>
        @endif

        @if ($booking->payment_proof_path)
            <div class="mt-4 border-t border-border pt-4">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Bukti Pembayaran Pelanggan</p>
                <div class="mt-3 flex flex-wrap items-center gap-4">
                    <a href="{{ route('admin.bookings.proof', $booking) }}" target="_blank" rel="noopener noreferrer" class="group relative block overflow-hidden rounded-2xl border border-border bg-surface-2 p-1 transition hover:border-accent">
                        <img src="{{ route('admin.bookings.proof', $booking) }}" alt="Bukti pembayaran {{ $booking->code }}" class="h-28 w-28 rounded-xl object-cover">
                        <div class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition group-hover:opacity-100">
                            <span class="rounded-lg bg-black/75 px-2 py-1 text-xs font-bold text-white">Buka penuh ↗</span>
                        </div>
                    </a>
                    <div class="space-y-1.5 text-xs">
                        <p class="font-bold text-success">File bukti transfer tersimpan</p>
                        @if ($booking->payment_proof_uploaded_at)
                            <p class="text-text-muted">Diunggah: {{ $booking->payment_proof_uploaded_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB ({{ $booking->payment_proof_uploaded_at->locale('id')->diffForHumans() }})</p>
                        @endif
                        <a href="{{ route('admin.bookings.proof', $booking) }}" target="_blank" rel="noopener noreferrer" class="inline-block font-bold text-accent hover:underline">Lihat gambar ukuran penuh ↗</a>
                    </div>
                </div>
            </div>
        @elseif ($booking->isOnlinePayment() && $booking->status === 'menunggu_pembayaran')
            <div class="mt-4 border-t border-border pt-4">
                <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Bukti Pembayaran</p>
                <p class="mt-1 text-xs text-text-muted">Pelanggan belum mengunggah bukti pembayaran.</p>
            </div>
        @endif

        <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-border pt-4">
            @if ($booking->status === 'menunggu_pembayaran')
                <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" data-confirm="Tandai booking ini sudah dibayar?" data-confirm-title="Konfirmasi Pembayaran" data-confirm-action="Tandai Dibayar" novalidate>
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="tandai_dibayar">
                    <button class="inline-flex min-h-11 items-center justify-center rounded-xl bg-accent px-4 text-sm font-bold text-accent-contrast hover:opacity-90">Tandai sudah dibayar</button>
                </form>
            @elseif ($booking->status === 'menunggu_konfirmasi')
                <form method="POST" action="{{ route('admin.bookings.status', $booking) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="konfirmasi">
                    <button class="inline-flex min-h-11 items-center justify-center rounded-xl bg-accent px-4 text-sm font-bold text-accent-contrast hover:opacity-90">Konfirmasi booking & pembayaran</button>
                </form>
                @if ($booking->payment_proof_path)
                    <div x-data="{ openReject: false }">
                        <button type="button" @click="openReject = true" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-warning px-4 text-sm font-semibold text-warning hover:bg-warning/10">Tolak bukti transfer</button>
                        <div x-show="openReject" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-overlay p-4" @click.self="openReject = false">
                            <div class="w-full max-w-md rounded-2xl border border-border bg-surface p-5 shadow-token">
                                <h3 class="text-base font-bold text-text">Tolak Bukti Transfer</h3>
                                <p class="mt-1 text-xs text-text-muted">Masukkan alasan penolakan. Status akan kembali ke menunggu pembayaran agar pelanggan dapat mengunggah bukti yang benar.</p>
                                <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" class="mt-4">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="action" value="tolak_bukti">
                                    <label for="modal_rejection_note" class="text-xs font-semibold text-text">Alasan Penolakan</label>
                                    <textarea id="modal_rejection_note" name="rejection_note" required rows="3" placeholder="Contoh: Nominal transfer kurang / struk tidak terbaca" class="mt-1 w-full rounded-xl border border-border bg-bg p-3 text-sm text-text"></textarea>
                                    <div class="mt-4 flex justify-end gap-2">
                                        <button type="button" @click="openReject = false" class="min-h-10 rounded-lg border border-border px-3 text-xs font-semibold">Batal</button>
                                        <button class="min-h-10 rounded-lg bg-danger px-3 text-xs font-bold text-danger-contrast">Tolak Bukti</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            @elseif ($booking->status === 'terkonfirmasi')
                <form method="POST" action="{{ route('admin.bookings.status', $booking) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="hadir">
                    <button class="inline-flex min-h-11 items-center justify-center rounded-xl bg-success px-4 text-sm font-bold text-success-contrast hover:opacity-90">Tandai hadir</button>
                </form>
                <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" data-confirm="Tandai pemesan tidak hadir? Ini akan menambah catatan no-show pelanggan." data-confirm-title="Tandai No-Show" data-confirm-action="Tandai Tidak Hadir" data-confirm-is-danger="true" novalidate>
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="tidak_hadir">
                    <button class="inline-flex min-h-11 items-center justify-center rounded-xl border border-warning px-4 text-sm font-semibold text-warning hover:bg-warning/10">Tidak hadir (no-show)</button>
                </form>
            @elseif ($booking->status === 'kedaluwarsa')
                <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" data-confirm="Pulihkan booking ini? Kursi akan ditahan kembali jika sesi masih memiliki kapasitas." data-confirm-title="Pulihkan Booking" data-confirm-action="Pulihkan" novalidate>
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="pulihkan">
                    <button class="inline-flex min-h-11 items-center justify-center rounded-xl border border-accent bg-accent/10 px-4 text-sm font-bold text-accent hover:bg-accent/20">Pulihkan booking kedaluwarsa</button>
                </form>
            @endif

            @if ($booking->status !== 'dibatalkan' && $booking->status !== 'kedaluwarsa')
                <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" data-confirm="Batalkan booking ini? Kursi yang dipesan akan dilepas kembali ke sesi." data-confirm-title="Batalkan Booking" data-confirm-action="Batalkan Booking" data-confirm-is-danger="true" novalidate>
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="batal">
                    <button class="inline-flex min-h-11 items-center justify-center rounded-xl border border-danger/40 px-3 text-sm font-semibold text-danger hover:bg-danger/10">Batalkan booking</button>
                </form>
            @endif
        </div>
    </article>

    <section class="mt-7 rounded-2xl border border-border bg-surface p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">Jajan di booking ini</h2>
                <p class="mt-1 text-sm text-text-muted">Total jajan saat ini: Rp {{ number_format($booking->snack_total, 0, ',', '.') }} · Total booking: Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
            </div>
            @isset($snackUpdateWhatsAppUrl)
                @if (filled($snackUpdateWhatsAppUrl))
                    <a href="{{ $snackUpdateWhatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-success px-4 text-sm font-bold text-success-contrast hover:opacity-90 sm:w-auto">Kirim update ke pelanggan via WA</a>
                @endif
            @endisset
        </div>
        @php($snackChangeLogs = collect($booking->getRelations()['snackChangeLogs'] ?? ($snackOrderEdits ?? $booking->getRelations()['snackOrderEdits'] ?? [])))
        @if ($booking->items->isNotEmpty())
            <div class="mt-4 space-y-3">
                @foreach ($booking->items as $item)
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-border pb-3 text-sm last:border-0">
                        <div><p class="font-semibold">{{ $item->name_snapshot }} × {{ $item->qty }} · Rp {{ number_format($item->price_snapshot * $item->qty, 0, ',', '.') }}</p>
                            @if ($item->note)<p class="mt-1 text-xs text-text-muted">Catatan: {{ $item->note }}</p>@endif
                            <p class="mt-1 text-xs text-text-muted">Status: {{ ucfirst($item->status) }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="mt-3 text-sm text-text-muted">Belum ada jajan di booking ini.</p>
        @endif

        <details class="mt-5 rounded-xl border border-border bg-surface-2 p-4">
            <summary class="cursor-pointer font-semibold">Riwayat perubahan jajan <span class="text-sm font-normal text-text-muted">({{ $snackChangeLogs->count() }})</span></summary>
            @if ($snackChangeLogs->isNotEmpty())
                <ol class="mt-4 space-y-3">
                    @foreach ($snackChangeLogs as $change)
                        @php($beforeDetails = collect(data_get($change, 'before_details', data_get($change, 'before', []))))
                        @php($afterDetails = collect(data_get($change, 'after_details', data_get($change, 'after', []))))
                        <li class="border-l-2 border-accent pl-3 text-sm">
                            <p class="font-semibold">{{ data_get($change, 'summary') ?? data_get($change, 'description') ?? data_get($change, 'action') ?? 'Perubahan jajan' }}</p>
                            <p class="mt-1 text-xs text-text-muted">
                                @if (data_get($change, 'created_at'))
                                    {{ \Illuminate\Support\Carbon::parse(data_get($change, 'created_at'))->locale('id')->translatedFormat('d M Y, H:i') }}
                                @endif
                                @if (data_get($change, 'actor.name') ?? data_get($change, 'actor_name') ?? data_get($change, 'user_name'))
                                    · {{ data_get($change, 'actor.name') ?? data_get($change, 'actor_name') ?? data_get($change, 'user_name') }}
                                @endif
                            </p>
                            @if ($beforeDetails->isNotEmpty() || $afterDetails->isNotEmpty())
                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <h3 class="text-xs font-semibold text-text-muted">Sebelum</h3>
                                        @forelse ($beforeDetails as $detail)
                                            <p class="mt-1 text-xs">{{ data_get($detail, 'name_snapshot', data_get($detail, 'name', 'Menu')) }} × {{ data_get($detail, 'qty', 0) }}@if (data_get($detail, 'note')) · {{ data_get($detail, 'note') }}@endif</p>
                                        @empty
                                            <p class="mt-1 text-xs text-text-muted">Tidak ada item jajan.</p>
                                        @endforelse
                                    </div>
                                    <div>
                                        <h3 class="text-xs font-semibold text-text-muted">Sesudah</h3>
                                        @forelse ($afterDetails as $detail)
                                            <p class="mt-1 text-xs">{{ data_get($detail, 'name_snapshot', data_get($detail, 'name', 'Menu')) }} × {{ data_get($detail, 'qty', 0) }}@if (data_get($detail, 'note')) · {{ data_get($detail, 'note') }}@endif</p>
                                        @empty
                                            <p class="mt-1 text-xs text-text-muted">Tidak ada item jajan.</p>
                                        @endforelse
                                    </div>
                                </div>
                            @elseif (data_get($change, 'old_total') !== null || data_get($change, 'new_total') !== null)
                                <p class="mt-1 text-xs text-text-muted">Total jajan:
                                    @if (data_get($change, 'old_total') !== null) Rp {{ number_format((int) data_get($change, 'old_total'), 0, ',', '.') }} @endif
                                    @if (data_get($change, 'new_total') !== null) → Rp {{ number_format((int) data_get($change, 'new_total'), 0, ',', '.') }} @endif
                                </p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @else
                <p class="mt-3 text-sm text-text-muted">Riwayat perubahan belum tersedia untuk booking ini.</p>
            @endif
        </details>

        @if ($booking->status !== 'dibatalkan')
            @if ($booking->items->where('status', '!=', 'dibatalkan')->isNotEmpty())
                <form method="POST" action="{{ route('admin.bookings.snacks.status', $booking) }}" class="mt-5 flex flex-wrap items-center gap-3 border-t border-border pt-5">
                    @csrf @method('PATCH')
                    <label for="booking-snack-status" class="text-sm font-semibold">Status semua jajan booking ini</label>
                    <div class="w-48">
                        <x-ui.select
                            id="booking-snack-status"
                            name="status"
                            value="baru"
                            :options="[
                                'baru' => 'Baru',
                                'disiapkan' => 'Disiapkan',
                                'siap' => 'Siap',
                                'selesai' => 'Selesai',
                            ]"
                        />
                    </div>
                    <button class="min-h-11 rounded-xl btn-secondary text-sm font-semibold">Terapkan status</button>
                </form>
            @endif
            <form method="POST" action="{{ route('admin.bookings.snacks.update', $booking) }}" class="mt-5 border-t border-border pt-5">
                @csrf @method('PATCH')
                <p class="text-sm font-semibold">Ubah menu dan jumlah</p>
                <p class="mt-1 text-xs text-text-muted">Harga item lama tetap memakai snapshot saat dipesan. Item baru memakai harga menu saat ini.</p>
                <div class="mt-4 space-y-5">
                    @foreach ($categories as $category)
                        <details class="rounded-xl border border-border p-4" @if ($loop->first) open @endif>
                            <summary class="cursor-pointer font-bold text-accent">{{ $category->name }}{{ $category->is_active ? '' : ' · kategori nonaktif' }}</summary>
                            <div class="mt-2 space-y-2">
                                @foreach ($category->items as $menuItem)
                                    @php($current = $booking->items->first(fn ($line) => $line->menu_item_id === $menuItem->id && $line->status !== 'dibatalkan'))
                                    <div class="grid gap-2 rounded-xl border border-border p-3 sm:grid-cols-[1fr_7rem_1fr] sm:items-center">
                                        <label for="qty-{{ $menuItem->id }}" class="text-sm">{{ $menuItem->name }} · Rp {{ number_format($menuItem->price, 0, ',', '.') }}{{ $menuItem->is_active ? '' : ' · nonaktif' }}</label>
                                        <input id="qty-{{ $menuItem->id }}" name="snacks[{{ $menuItem->id }}][qty]" type="number" min="0" max="{{ $snackMaxQty }}" value="{{ old('snacks.'.$menuItem->id.'.qty', $current?->qty ?? 0) }}" class="min-h-11 rounded-lg border border-border bg-bg px-3 text-sm text-text">
                                        <label for="note-{{ $menuItem->id }}" class="sr-only">Catatan {{ $menuItem->name }}</label>
                                        <input id="note-{{ $menuItem->id }}" name="snacks[{{ $menuItem->id }}][note]" value="{{ old('snacks.'.$menuItem->id.'.note', $current?->note) }}" maxlength="250" placeholder="Catatan opsional" class="min-h-11 rounded-lg border border-border bg-bg px-3 text-sm text-text placeholder:text-text-muted sm:col-start-3">
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>
                <div class="mt-5 rounded-xl border border-border bg-surface-2 p-4">
                    <p class="text-sm text-text-muted">Total saat ini termasuk Rp {{ number_format($booking->snack_total, 0, ',', '.') }} jajan.</p>
                    <p class="mt-1 text-lg font-bold text-accent">Total booking saat ini: Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
                </div>
                <button class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-accent px-5 font-bold text-accent-contrast hover:opacity-90 sm:w-auto">Simpan perubahan jajan</button>
            </form>
        @endif
    </section>
@endsection
