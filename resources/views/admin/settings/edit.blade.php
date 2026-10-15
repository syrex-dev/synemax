@extends('layouts.admin')

@section('title', 'Pengaturan')

@section('content')
    <div x-data="{
        previewOpen: false,
        qrisRatioWarningClient: false,
        checkQrisRatio(event) {
            const file = event.target.files && event.target.files[0];
            if (!file) {
                this.qrisRatioWarningClient = false;
                return;
            }
            const img = new Image();
            img.onload = () => {
                if (img.height > 0) {
                    const ratio = img.width / img.height;
                    this.qrisRatioWarningClient = (ratio < 0.8 || ratio > 1.25);
                }
            };
            img.src = URL.createObjectURL(file);
        },
        accounts: @js(old('transfer_accounts', !empty($settings['transfer_accounts']) ? $settings['transfer_accounts'] : [['bank_name' => '', 'account_number' => '', 'account_holder' => '']])),
        addAccount() {
            this.accounts.push({ bank_name: '', account_number: '', account_holder: '' });
        },
        removeAccount(index) {
            if (this.accounts.length > 1) {
                this.accounts.splice(index, 1);
            } else {
                this.accounts = [{ bank_name: '', account_number: '', account_holder: '' }];
            }
        }
    }">
        <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-accent">Konfigurasi</p><h1 class="mt-2 text-3xl font-black">Pengaturan Home Cinema</h1></div>
        
        @if ($errors->any())
            <div role="alert" class="mt-5 rounded-xl border border-danger/50 bg-danger/10 p-4 text-sm text-danger">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif

        @if ($qrisWarning)
            <div role="alert" class="mt-5 flex items-start gap-3 rounded-2xl border border-warning/40 bg-warning/10 p-4 text-warning">
                <svg class="mt-0.5 size-5 shrink-0 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="flex-1 text-sm text-text">
                    <p class="font-bold text-warning">Peringatan: Gambar QRIS belum diunggah atau tidak ditemukan</p>
                    <p class="mt-0.5 text-xs text-text-muted">Metode QRIS saat ini terdaftar aktif, tetapi pelanggan tidak dapat memilihnya sampai gambar QRIS yang valid diunggah di bawah.</p>
                </div>
            </div>
        @endif

        @if ($transferWarning)
            <div role="alert" class="mt-5 flex items-start gap-3 rounded-2xl border border-warning/40 bg-warning/10 p-4 text-warning">
                <svg class="mt-0.5 size-5 shrink-0 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="flex-1 text-sm text-text">
                    <p class="font-bold text-warning">Peringatan: Rekening transfer belum dikonfigurasi</p>
                    <p class="mt-0.5 text-xs text-text-muted">Metode Transfer saat ini terdaftar aktif, tetapi pelanggan tidak dapat memilihnya sampai minimal satu rekening bank/e-wallet ditambahkan di bawah.</p>
                </div>
            </div>
        @endif

        <form id="delete-qris-form" method="POST" action="{{ route('admin.settings.qris.destroy') }}" class="hidden" onsubmit="return confirm('Hapus gambar QRIS saat ini? Metode QRIS akan otomatis dinonaktifkan.');">
            @csrf
            @method('DELETE')
        </form>

        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">@csrf @method('PUT')
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
                <summary class="cursor-pointer text-lg font-bold">Metode Pembayaran</summary>
                <fieldset class="mt-4">
                    <legend class="text-sm font-semibold">Metode yang aktif</legend>
                    <div class="mt-3 flex flex-wrap gap-3">
                        @foreach (['di_tempat' => 'Bayar di tempat', 'qris' => 'QRIS', 'transfer' => 'Transfer'] as $method => $label)
                            <label class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-border px-3 text-sm">
                                <input type="checkbox" name="payment_methods[]" value="{{ $method }}" class="rounded border-border bg-bg text-accent" @checked(in_array($method, old('payment_methods', $enabledMethods), true))>
                                {{ $label }}
                                @if ($method === 'qris' && ! $settings['has_qris_image'])
                                    <span class="text-xs text-warning">(Wajib upload gambar)</span>
                                @elseif ($method === 'transfer' && ! $settings['has_transfer_accounts'])
                                    <span class="text-xs text-warning">(Wajib tambah rekening)</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div><label for="admin_whatsapp" class="text-sm font-semibold">WhatsApp admin (format 08xx atau +62)</label><input id="admin_whatsapp" name="admin_whatsapp" value="{{ old('admin_whatsapp', $settings['admin_whatsapp']) }}" inputmode="tel" required class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-sm text-text"></div>
                    <div><label for="electricity_cost_per_session" class="text-sm font-semibold">Biaya listrik per sesi (Rp)</label><input id="electricity_cost_per_session" name="electricity_cost_per_session" type="number" min="0" step="500" required value="{{ old('electricity_cost_per_session', $settings['electricity_cost_per_session']) }}" class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-sm text-text"></div>
                </div>
                <div class="mt-4"><label for="payment_instructions" class="text-sm font-semibold">Petunjuk pembayaran umum (opsional)</label><textarea id="payment_instructions" name="payment_instructions" rows="2" maxlength="2000" class="mt-2 w-full rounded-xl border border-border bg-bg px-3 py-3 text-sm text-text">{{ old('payment_instructions', $settings['payment_instructions']) }}</textarea></div>

                {{-- QRIS Section --}}
                <div id="qris-section" class="mt-6 border-t border-border pt-6">
                    <h3 class="text-base font-bold text-text">Pembayaran QRIS</h3>
                    <p class="mt-1 text-xs text-text-muted">Pelanggan yang memilih QRIS akan melihat kode QR ini di halaman pembayaran & tiket digital.</p>

                    <div class="mt-4 space-y-4">
                        @if ($settings['has_qris_image'])
                            <div class="flex flex-wrap items-center gap-4 rounded-xl border border-border bg-surface-2 p-4">
                                <div class="shrink-0 rounded-xl bg-white p-2 shadow-xs dark:bg-white">
                                    <img src="{{ route('payment.qris', ['v' => $settings['qris_image_version']]) }}" alt="QRIS SynemaX" class="size-28 object-contain">
                                </div>
                                <div class="flex-1 space-y-2">
                                    <p class="text-sm font-bold text-success">Gambar QRIS aktif tersimpan</p>
                                    <p class="text-xs text-text-muted">Disimpan privat dan disajikan aman ke pelanggan tanpa kompresi pecah.</p>
                                    <div class="flex flex-wrap gap-2 pt-1">
                                        <button type="button" @click="previewOpen = true" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-accent bg-accent/10 px-3 text-xs font-bold text-accent hover:bg-accent/20">Lihat tampilan pelanggan</button>
                                        <button type="submit" form="delete-qris-form" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-danger/40 px-3 text-xs font-bold text-danger hover:bg-danger/10">Hapus gambar</button>
                                    </div>
                                </div>
                            </div>

                            @if (!empty($settings['qris_ratio_warning']))
                                <div class="flex items-start gap-3 rounded-xl border border-warning/40 bg-warning/10 p-3.5 text-xs text-warning">
                                    <svg class="mt-0.5 size-4 shrink-0 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <div>
                                        <p class="font-bold">Perhatian Rasio Gambar QRIS</p>
                                        <p class="mt-0.5 text-text">QRIS sebaiknya persegi dan rapi; pangkas bagian lain.</p>
                                    </div>
                                </div>
                            @endif

                            <div>
                                <label for="qris_image" class="block text-sm font-semibold">Ganti gambar QRIS (PNG, JPG, WEBP maks. 2 MB, min. 300 × 300 px)</label>
                                <input id="qris_image" name="qris_image" type="file" accept="image/png,image/jpeg,image/webp" @change="checkQrisRatio($event)" class="mt-2 block w-full text-sm text-text file:mr-3 file:min-h-11 file:rounded-lg file:border-0 file:bg-surface-2 file:px-4 file:font-semibold file:text-text">
                                <div x-show="qrisRatioWarningClient" x-cloak class="mt-2 flex items-start gap-3 rounded-xl border border-warning/40 bg-warning/10 p-3 text-xs text-warning">
                                    <svg class="mt-0.5 size-4 shrink-0 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <div>
                                        <p class="font-bold">Perhatian Rasio Gambar</p>
                                        <p class="mt-0.5 text-text">QRIS sebaiknya persegi dan rapi; pangkas bagian lain.</p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="rounded-xl border border-dashed border-border bg-surface-2 p-5 text-sm">
                                <p class="font-bold text-text">Belum ada gambar QRIS</p>
                                <p class="mt-1 text-xs text-text-muted">Unggah kode QRIS studio Anda (PNG/JPG/WEBP, min. 300 × 300 px, maks. 2 MB). Metode QRIS tidak dapat diaktifkan jika belum ada gambar.</p>
                                <div class="mt-3">
                                    <label for="qris_image" class="block text-sm font-semibold">Unggah gambar QRIS</label>
                                    <input id="qris_image" name="qris_image" type="file" accept="image/png,image/jpeg,image/webp" @change="checkQrisRatio($event)" class="mt-2 block w-full text-sm text-text file:mr-3 file:min-h-11 file:rounded-lg file:border-0 file:bg-surface file:px-4 file:font-semibold file:text-text">
                                    <div x-show="qrisRatioWarningClient" x-cloak class="mt-2 flex items-start gap-3 rounded-xl border border-warning/40 bg-warning/10 p-3 text-xs text-warning">
                                        <svg class="mt-0.5 size-4 shrink-0 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <div>
                                            <p class="font-bold">Perhatian Rasio Gambar</p>
                                            <p class="mt-0.5 text-text">QRIS sebaiknya persegi dan rapi; pangkas bagian lain.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="qris_merchant_name" class="text-sm font-semibold">Nama merchant / atas nama (tampil ke pelanggan)</label>
                                <input id="qris_merchant_name" name="qris_merchant_name" value="{{ old('qris_merchant_name', $settings['qris_merchant_name']) }}" placeholder="Contoh: SynemaX Home Cinema" maxlength="120" class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-sm text-text">
                            </div>
                            <div>
                                <label for="qris_payment_notes" class="text-sm font-semibold">Catatan pembayaran QRIS (opsional)</label>
                                <textarea id="qris_payment_notes" name="qris_payment_notes" rows="2" maxlength="2000" placeholder="Contoh: Bayar sesuai total nominal, lalu unggah bukti transfer di bawah." class="mt-2 w-full rounded-xl border border-border bg-bg px-3 py-2 text-sm text-text">{{ old('qris_payment_notes', $settings['qris_payment_notes']) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Transfer Section --}}
                <div id="transfer-section" class="mt-6 border-t border-border pt-6">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-bold text-text">Pembayaran Transfer Bank & E-Wallet</h3>
                            <p class="mt-1 text-xs text-text-muted">Kelola daftar rekening bank atau akun e-wallet tujuan transfer pelanggan.</p>
                        </div>
                        <button type="button" @click="addAccount()" class="inline-flex min-h-10 items-center gap-1.5 rounded-lg border border-accent bg-accent/10 px-3 text-xs font-bold text-accent hover:bg-accent/20">
                            + Tambah Rekening
                        </button>
                    </div>

                    <div class="mt-4 space-y-3">
                        <template x-for="(acc, index) in accounts" :key="index">
                            <div class="flex flex-wrap items-start gap-3 rounded-xl border border-border bg-surface-2 p-4">
                                <div class="min-w-36 flex-1">
                                    <label :for="'acc_bank_' + index" class="block text-xs font-semibold text-text">Nama Bank / E-Wallet</label>
                                    <input :id="'acc_bank_' + index" :name="'transfer_accounts[' + index + '][bank_name]'" x-model="acc.bank_name" placeholder="BCA / Mandiri / GoPay" maxlength="50" class="mt-1.5 min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-sm text-text">
                                </div>
                                <div class="min-w-44 flex-1">
                                    <label :for="'acc_num_' + index" class="block text-xs font-semibold text-text">Nomor Rekening / Akun</label>
                                    <input :id="'acc_num_' + index" :name="'transfer_accounts[' + index + '][account_number]'" x-model="acc.account_number" placeholder="1234567890" maxlength="50" class="mt-1.5 min-h-11 w-full rounded-lg border border-border bg-bg px-3 font-mono text-sm text-text">
                                </div>
                                <div class="min-w-44 flex-1">
                                    <label :for="'acc_holder_' + index" class="block text-xs font-semibold text-text">Atas Nama (Pemilik Rekening)</label>
                                    <input :id="'acc_holder_' + index" :name="'transfer_accounts[' + index + '][account_holder]'" x-model="acc.account_holder" placeholder="PT / Nama Pemilik" maxlength="100" class="mt-1.5 min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-sm text-text">
                                </div>
                                <div class="pt-6">
                                    <button type="button" @click="removeAccount(index)" aria-label="Hapus rekening" class="grid size-11 place-items-center rounded-lg border border-danger/40 text-danger hover:bg-danger/10">✕</button>
                                </div>
                            </div>
                        </template>

                        <div class="mt-4">
                            <label for="transfer_payment_notes" class="text-sm font-semibold">Catatan pembayaran Transfer (opsional)</label>
                            <textarea id="transfer_payment_notes" name="transfer_payment_notes" rows="2" maxlength="2000" placeholder="Contoh: Beri berita transfer sesuai kode booking Anda." class="mt-2 w-full rounded-xl border border-border bg-bg px-3 py-2 text-sm text-text">{{ old('transfer_payment_notes', $settings['transfer_payment_notes']) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Online Payment Rules Section --}}
                <div id="payment-rules-section" class="mt-6 border-t border-border pt-6">
                    <h3 class="text-base font-bold text-text">Aturan Pembayaran Online (QRIS & Transfer)</h3>
                    <p class="mt-1 text-xs text-text-muted">Pengaturan batas waktu transfer, cutoff sesi, dan batas ukuran file bukti transfer.</p>

                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="payment_time_limit_minutes" class="text-sm font-semibold">Batas waktu bayar (menit)</label>
                            <input id="payment_time_limit_minutes" name="payment_time_limit_minutes" type="number" min="5" max="180" value="{{ old('payment_time_limit_minutes', $settings['payment_time_limit_minutes']) }}" required class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-sm text-text">
                            <p class="mt-1 text-[11px] text-text-muted">Kursi ditahan selama waktu ini sebelum kedaluwarsa.</p>
                        </div>
                        <div>
                            <label for="online_payment_cutoff_minutes" class="text-sm font-semibold">Cutoff online sebelum sesi (menit)</label>
                            <input id="online_payment_cutoff_minutes" name="online_payment_cutoff_minutes" type="number" min="0" max="180" value="{{ old('online_payment_cutoff_minutes', $settings['online_payment_cutoff_minutes']) }}" required class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-sm text-text">
                            <p class="mt-1 text-[11px] text-text-muted">Jika sesi mulai dalam waktu ini, hanya bayar di tempat yang aktif.</p>
                        </div>
                        <div>
                            <label for="max_payment_proof_kb" class="text-sm font-semibold">Batas upload bukti (KB)</label>
                            <input id="max_payment_proof_kb" name="max_payment_proof_kb" type="number" min="512" max="10240" step="512" value="{{ old('max_payment_proof_kb', $settings['max_payment_proof_kb']) }}" required class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-3 text-sm text-text">
                            <p class="mt-1 text-[11px] text-text-muted">Contoh: 4096 = 4 MB, 2048 = 2 MB.</p>
                        </div>
                    </div>
                </div>
            </details>

            <details open class="rounded-2xl border border-border bg-surface p-5 sm:p-6">
                <summary class="cursor-pointer text-lg font-bold">Fitur Info & Nobar</summary>
                <p class="mt-1 text-xs text-text-muted">Aktifkan atau nonaktifkan bagian Nobar Komunitas dan Kabar Berita pada situs publik.</p>
                <div class="mt-4">
                    <label class="inline-flex w-full items-center gap-3 rounded-xl border border-border bg-surface-2 p-4 text-sm font-semibold cursor-pointer hover:border-accent">
                        <input type="checkbox" name="info_nobar_enabled" value="1" class="size-5 rounded border-border bg-bg text-accent" @checked(old('info_nobar_enabled', $settings['info_nobar_enabled']))>
                        <div>
                            <p class="font-bold text-text">Tampilkan Info & Nobar di Situs Publik</p>
                            <p class="mt-0.5 text-xs font-normal text-text-muted">Jika dicentang, jadwal nobar sepak bola dan kabar/promo akan muncul di beranda dan navigasi situs.</p>
                        </div>
                    </label>
                </div>
            </details>

            <button class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-accent px-5 font-bold text-accent-contrast hover:opacity-90 sm:w-auto">Simpan pengaturan</button>
        </form>

        {{-- Modal Pratinjau Tampilan Pelanggan --}}
        @if ($settings['has_qris_image'])
            <div x-show="previewOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-overlay p-4" @click.self="previewOpen = false" @keydown.escape.window="previewOpen = false">
                <section role="dialog" aria-modal="true" class="w-full max-w-md overflow-hidden rounded-3xl border border-border bg-surface p-5 shadow-token sm:p-6">
                    <div class="flex items-center justify-between border-b border-border pb-3">
                        <h3 class="font-bold text-text">Pratinjau Tampilan Pelanggan</h3>
                        <button type="button" @click="previewOpen = false" class="grid size-9 place-items-center rounded-full text-text-muted hover:bg-surface-2 hover:text-text">✕</button>
                    </div>
                    
                    <div class="mt-4 rounded-2xl border border-border bg-bg p-4">
                        <div class="text-center">
                            <p class="text-xs font-bold uppercase tracking-wider text-accent">Metode Pembayaran QRIS</p>
                            <p class="mt-1 text-sm font-semibold text-text">{{ $settings['qris_merchant_name'] ?: 'SynemaX Home Cinema' }}</p>
                        </div>

                        <div class="mx-auto mt-4 max-w-xs rounded-2xl bg-white p-4 shadow-sm dark:bg-white">
                            <img src="{{ route('payment.qris', ['v' => $settings['qris_image_version']]) }}" alt="QRIS SynemaX" class="max-h-64 w-auto max-w-full mx-auto object-contain">
                            <p class="mt-2 text-center font-mono text-[11px] font-bold text-gray-700">NMID / QRIS STANDAR</p>
                        </div>

                        <div class="mt-4 rounded-xl bg-surface p-3 text-center">
                            <span class="text-xs text-text-muted">Total Pembayaran</span>
                            <p class="text-xl font-black text-accent">Rp 45.000</p>
                            <span class="mt-1 inline-flex items-center gap-1 text-xs text-text-muted">Tombol "Salin nominal"</span>
                        </div>

                        @if ($settings['qris_payment_notes'])
                            <div class="mt-3 rounded-xl border border-accent/20 bg-accent/5 p-3 text-xs leading-relaxed text-text">
                                <p class="font-semibold text-accent">Catatan:</p>
                                <p class="mt-0.5 whitespace-pre-line">{{ $settings['qris_payment_notes'] }}</p>
                            </div>
                        @endif

                        <p class="mt-3 text-center text-xs text-text-muted">Bisa dipindai dengan BCA, Mandiri, GoPay, OVO, Dana, ShopeePay, dan mobile banking lainnya.</p>
                    </div>

                    <button type="button" @click="previewOpen = false" class="mt-4 min-h-11 w-full rounded-xl bg-accent text-sm font-bold text-accent-contrast">Tutup pratinjau</button>
                </section>
            </div>
        @endif
    </div>
@endsection
