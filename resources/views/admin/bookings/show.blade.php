@extends('layouts.admin')

@section('title', 'Detail booking')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-accent">Pelanggan</p><h1 class="mt-2 text-3xl font-black">Detail booking</h1><p class="mt-1 font-mono text-sm text-accent">{{ $booking->code }}</p></div>
        <a href="{{ route('admin.bookings.index') }}" class="text-sm text-accent hover:underline">← Kembali ke booking</a>
    </div>
    <article class="mt-6 rounded-2xl border border-border bg-surface p-5">
        <div class="grid gap-3 text-sm sm:grid-cols-2">
            <p><span class="text-text-muted">Pemesan:</span> {{ $booking->customer_name }} · {{ $booking->whatsapp }}</p>
            <p><span class="text-text-muted">Sesi:</span> {{ $booking->session->session_date }} · {{ substr((string) $booking->session->start_time, 0, 5) }} WIB</p>
            <p><span class="text-text-muted">Film:</span> {{ $booking->film?->title ?? '—' }}</p>
            <p><span class="text-text-muted">Jumlah:</span> {{ $booking->booking_type === 'private' ? 'Sewa Private (6 kursi)' : $booking->pax.' orang' }}</p>
            <p><span class="text-text-muted">Tiket:</span> Rp {{ number_format($booking->ticket_total, 0, ',', '.') }}</p>
            <p><span class="text-text-muted">Jajan:</span> Rp {{ number_format($booking->snack_total, 0, ',', '.') }}</p>
            <p class="font-bold text-accent sm:col-span-2">Total booking saat ini: Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
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
                    <select id="booking-snack-status" name="status" class="min-h-10 rounded-lg border border-border bg-bg px-3 text-sm text-text">@foreach (['baru' => 'Baru', 'disiapkan' => 'Disiapkan', 'siap' => 'Siap', 'selesai' => 'Selesai'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
                    <button class="min-h-12 rounded-lg border border-border px-4 text-sm font-semibold">Terapkan status</button>
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
