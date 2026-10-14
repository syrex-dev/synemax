@extends('layouts.admin')

@section('title', 'Pengaturan')

@section('content')
    <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-accent">Konfigurasi</p><h1 class="mt-2 text-3xl font-black">Pengaturan Home Cinema</h1></div>
    @if ($errors->any())
        <div role="alert" class="mt-5 rounded-xl border border-danger/50 bg-danger/10 p-4 text-sm text-danger">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif

    <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-6 space-y-6">@csrf @method('PUT')
        <details open class="rounded-2xl border border-border bg-surface p-5 sm:p-6">
            <summary class="cursor-pointer text-lg font-bold">Harga tiket</summary>
            <p class="mt-1 text-xs text-text-muted">Harga disimpan pada setiap booking, jadi laporan lama tidak berubah.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach (['weekday_price' => 'Hari biasa/Jumat', 'weekend_price' => 'Sabtu–Minggu', 'holiday_price' => 'Hari libur', 'private_price' => 'Paket Sewa Private'] as $key => $label)
                    <div><label for="{{ $key }}" class="text-sm font-semibold">{{ $label }} (Rp)</label><input id="{{ $key }}" name="{{ $key }}" type="number" min="500" step="500" required value="{{ old($key, $settings[$key]) }}" class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-sm text-text focus:border-accent focus:ring-accent"></div>
                @endforeach
            </div>
        </details>

        <details open class="rounded-2xl border border-border bg-surface p-5 sm:p-6">
            <summary class="cursor-pointer text-lg font-bold">Pesanan jajan</summary>
            <p class="mt-1 text-xs leading-5 text-text-muted">Batas pesan dihitung dari jam mulai sesi dikurangi waktu persiapan jajan terlama dan buffer ini.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div><label for="snack_prep_buffer_minutes" class="text-sm font-semibold">Buffer waktu pesan (menit)</label><input id="snack_prep_buffer_minutes" name="snack_prep_buffer_minutes" type="number" min="0" max="360" value="{{ old('snack_prep_buffer_minutes', $settings['snack_prep_buffer_minutes']) }}" required class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-sm text-text"></div>
                <div><label for="snack_max_qty_per_item" class="text-sm font-semibold">Maksimal jumlah per menu / booking</label><input id="snack_max_qty_per_item" name="snack_max_qty_per_item" type="number" min="1" max="100" value="{{ old('snack_max_qty_per_item', $settings['snack_max_qty_per_item']) }}" required class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-sm text-text"></div>
                <label class="flex min-h-12 items-center gap-3 rounded-xl border border-border px-3 text-sm"><input type="hidden" name="snacks_enabled" value="0"><input type="checkbox" name="snacks_enabled" value="1" @checked(old('snacks_enabled', $settings['snacks_enabled'])) class="rounded border-border bg-bg text-accent">Aktifkan pesanan jajan</label>
            </div>
        </details>

        <details open class="rounded-2xl border border-border bg-surface p-5 sm:p-6">
            <summary class="cursor-pointer text-lg font-bold">Aturan booking</summary>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div><label for="minimum_public_viewers" class="text-sm font-semibold">Minimal penonton sesi publik</label><input id="minimum_public_viewers" name="minimum_public_viewers" type="number" min="1" max="6" required value="{{ old('minimum_public_viewers', $settings['minimum_public_viewers']) }}" class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-sm text-text"></div>
                <div><label for="booking_cutoff_minutes" class="text-sm font-semibold">Booking ditutup (menit sebelum sesi)</label><input id="booking_cutoff_minutes" name="booking_cutoff_minutes" type="number" min="0" max="720" required value="{{ old('booking_cutoff_minutes', $settings['booking_cutoff_minutes']) }}" class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-sm text-text"></div>
                <div><label for="cancellation_cutoff_hours" class="text-sm font-semibold">Batas pembatalan mandiri (jam)</label><input id="cancellation_cutoff_hours" name="cancellation_cutoff_hours" type="number" min="0" max="168" required value="{{ old('cancellation_cutoff_hours', $settings['cancellation_cutoff_hours']) }}" class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-sm text-text"></div>
            </div>
        </details>

        <details open class="rounded-2xl border border-border bg-surface p-5 sm:p-6">
            <summary class="cursor-pointer text-lg font-bold">Pembayaran & biaya</summary>
            <fieldset class="mt-4">
                <legend class="text-sm font-semibold">Metode yang tersedia</legend>
                <div class="mt-3 flex flex-wrap gap-3">
                    @foreach (['di_tempat' => 'Bayar di tempat', 'qris' => 'QRIS', 'transfer' => 'Transfer'] as $method => $label)
                        <label class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-border px-3 text-sm"><input type="checkbox" name="payment_methods[]" value="{{ $method }}" class="rounded border-border bg-bg text-accent" @checked(in_array($method, old('payment_methods', $enabledMethods), true))>{{ $label }}</label>
                    @endforeach
                </div>
            </fieldset>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><label for="admin_whatsapp" class="text-sm font-semibold">WhatsApp admin (format 08xx atau +62)</label><input id="admin_whatsapp" name="admin_whatsapp" value="{{ old('admin_whatsapp', $settings['admin_whatsapp']) }}" inputmode="tel" required class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-sm text-text"></div>
                <div><label for="electricity_cost_per_session" class="text-sm font-semibold">Biaya listrik per sesi (Rp)</label><input id="electricity_cost_per_session" name="electricity_cost_per_session" type="number" min="0" step="500" required value="{{ old('electricity_cost_per_session', $settings['electricity_cost_per_session']) }}" class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-sm text-text"></div>
            </div>
            <div class="mt-4"><label for="payment_instructions" class="text-sm font-semibold">Petunjuk QRIS/transfer</label><textarea id="payment_instructions" name="payment_instructions" rows="3" maxlength="2000" class="mt-2 w-full rounded-xl border border-border bg-bg px-3 py-3 text-sm text-text">{{ old('payment_instructions', $settings['payment_instructions']) }}</textarea></div>
            <p class="mt-3 text-xs leading-5 text-text-muted">Aktifkan QRIS/transfer hanya jika instruksi pembayarannya sudah diinformasikan ke pelanggan. Bukti bayar bersifat opsional dan disimpan privat.</p>
        </details>

        <button class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-accent px-5 font-bold text-accent-contrast hover:opacity-90 sm:w-auto">Simpan pengaturan</button>
    </form>
@endsection
