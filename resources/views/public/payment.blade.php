@extends('layouts.public')

@section('title', 'Pembayaran Booking ' . $booking->code . ': SynemaX')
@section('meta')
    <meta name="description" content="Selesaikan pembayaran untuk booking {{ $booking->code }} di SynemaX.">
    <meta property="og:title" content="Pembayaran booking {{ $booking->code }}: SynemaX">
    <meta property="og:description" content="Total: Rp {{ number_format($booking->total_price, 0, ',', '.') }}, {{ $booking->film?->title ?? 'Film Sesi' }}">
    @if ($booking->payment_method === 'qris' && $hasQrisImage)
        <link rel="preload" as="image" href="{{ route('payment.qris', ['v' => $qrisVersion]) }}" fetchpriority="high">
    @endif
@endsection

@section('content')
    <div class="mx-auto max-w-xl px-4 py-5 sm:px-6 sm:py-8"
         x-data="{
             code: @js($booking->code),
             expiresAt: @js($booking->payment_expires_at?->toIso8601String()),
             isExpired: @js($booking->status === 'kedaluwarsa' || ($booking->payment_expires_at && now()->greaterThan($booking->payment_expires_at))),
             timeLeft: 0,
             timerInterval: null,
             copiedNominal: false,
             copiedAccount: null,
             enlargeModal: false,
             hasFile: false,
             selectedFileName: '',
             cancelModal: false,
             init() {
                 this.initTimer();
             },
             initTimer() {
                 if (!this.expiresAt) return;
                 const target = new Date(this.expiresAt).getTime();
                 const update = () => {
                     const now = Date.now();
                     const diff = Math.max(0, Math.floor((target - now) / 1000));
                     this.timeLeft = diff;
                     if (diff <= 0) {
                         this.isExpired = true;
                         if (this.timerInterval) clearInterval(this.timerInterval);
                     }
                 };
                 update();
                 if (this.timeLeft > 0 && !this.isExpired) {
                     this.timerInterval = setInterval(update, 1000);
                 }
             },
             formatTime(seconds) {
                 const m = Math.floor(seconds / 60);
                 const s = seconds % 60;
                 return `${m}:${s < 10 ? '0' : ''}${s}`;
             },
             copyNominal(amount) {
                 if (navigator.clipboard && navigator.clipboard.writeText) {
                     navigator.clipboard.writeText(amount.toString()).then(() => {
                         this.copiedNominal = true;
                         setTimeout(() => this.copiedNominal = false, 2500);
                     });
                 }
             },
             copyAccount(accNum) {
                 if (navigator.clipboard && navigator.clipboard.writeText) {
                     navigator.clipboard.writeText(accNum.toString()).then(() => {
                         this.copiedAccount = accNum;
                         setTimeout(() => this.copiedAccount = null, 2500);
                     });
                 }
             },
             handleFileChange(event) {
                 const files = event.target.files;
                 if (files && files.length > 0) {
                     this.hasFile = true;
                     this.selectedFileName = files[0].name;
                 } else {
                     this.hasFile = false;
                     this.selectedFileName = '';
                 }
             }
         }">

        {{-- Flash Pesan / Alert Status --}}
        @if (session('status'))
            <div role="status" class="mb-4 rounded-[var(--radius-control)] border border-success-border bg-success-surface p-3.5 text-xs font-bold text-success-text">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="mb-4 rounded-[var(--radius-control)] border border-danger-border bg-danger-surface p-3.5 text-xs font-semibold text-danger-text space-y-1">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- Hitung Mundur Sisa Waktu Pembayaran --}}
        <div x-show="!isExpired"
             class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-[var(--radius-control)] border border-border-input bg-surface p-4 shadow-xs">
            <div>
                <p class="text-xs font-bold text-text-muted">Sisa waktu pembayaran</p>
                <p class="mt-0.5 font-tabular text-2xl font-black text-primary sm:text-3xl" x-text="formatTime(timeLeft)">--:--</p>
            </div>
            <p class="max-w-xs text-xs text-text-muted leading-relaxed">
                Kirim bukti transfer sebelum batas waktu berakhir agar kursi Anda tidak dilepaskan kembali.
            </p>
        </div>

        {{-- Banner Jika Waktu Pembayaran Habis --}}
        <div x-show="isExpired" x-cloak
             class="mb-6 rounded-[var(--radius-control)] border border-danger-border bg-danger-surface p-5 text-center text-danger-text">
            <h1 class="text-base font-extrabold text-danger-text sm:text-lg">Waktu pembayaran habis</h1>
            <p class="mt-1 text-xs opacity-90 leading-relaxed">
                Batas waktu transfer telah berakhir dan kursi pesanan telah dilepaskan kembali ke publik.
            </p>
            <div class="mt-4 flex flex-wrap justify-center gap-2">
                <a href="{{ route('booking.create', ['session' => $booking->session_id]) }}"
                   class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] bg-primary px-4 text-xs font-bold text-white hover:opacity-95">
                    Buat booking baru
                </a>
                @if ($whatsAppUrl)
                    <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] border border-border-input bg-surface px-4 text-xs font-bold text-text hover:border-primary">
                        Hubungi admin
                    </a>
                @endif
            </div>
        </div>

        {{-- Catatan Penolakan Bukti Sebelumnya (Jika Ada) --}}
        @if ($booking->proof_rejection_note)
            <div class="mb-5 rounded-[var(--radius-control)] border border-danger-border bg-danger-surface p-4 text-xs text-danger-text">
                <p class="font-bold">Bukti pembayaran sebelumnya belum dapat disetujui admin:</p>
                <p class="mt-1 font-semibold">{{ $booking->proof_rejection_note }}</p>
                <p class="mt-1 opacity-90">Silakan unggah ulang bukti transfer yang jelas sebelum waktu pembayaran habis.</p>
            </div>
        @endif

        <div x-show="!isExpired">
            {{-- Kartu Total Pembayaran --}}
            <div class="rounded-[var(--radius-control)] border border-border bg-surface p-4 sm:p-5 shadow-xs">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border pb-4">
                    <div>
                        <span class="text-xs text-text-muted">Total yang harus dibayar</span>
                        <p class="mt-0.5 font-tabular text-2xl font-black text-text sm:text-3xl">
                            Rp {{ number_format($booking->total_price, 0, ',', '.') }}
                        </p>
                    </div>
                    <button type="button"
                            @click="copyNominal({{ $booking->total_price }})"
                            class="inline-flex min-h-10 items-center gap-1.5 rounded-[var(--radius-control)] border border-border-input bg-surface px-3 text-xs font-bold text-text hover:border-primary">
                        <span x-text="copiedNominal ? 'Nominal tersalin' : 'Salin nominal'">Salin nominal</span>
                    </button>
                </div>
                <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2 text-xs text-text-muted">
                    <p>Kode booking: <strong class="font-tabular font-bold text-text">{{ $booking->code }}</strong></p>
                    <p>Transfer tepat sesuai jumlah di atas.</p>
                </div>

                {{-- Opsi 1: QRIS --}}
                @if ($booking->payment_method === 'qris')
                    <div class="mt-5 border-t border-border pt-5">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-sm font-bold text-text">Pindai kode QRIS</h2>
                            <span class="rounded-[var(--radius-control)] bg-surface-2 px-2 py-0.5 text-xs font-bold text-text">
                                {{ $qrisMerchantName ?: 'SynemaX' }}
                            </span>
                        </div>

                        @if ($hasQrisImage)
                            <div class="mt-4 text-center">
                                <div class="mx-auto inline-block max-w-sm rounded-[var(--radius-control)] border border-border bg-white p-4 shadow-xs text-center">
                                    <img src="{{ route('payment.qris', ['v' => $qrisVersion]) }}"
                                         alt="QRIS {{ $qrisMerchantName }}"
                                         width="280"
                                         height="280"
                                         fetchpriority="high"
                                         class="aspect-square max-h-64 sm:max-h-72 w-auto max-w-full mx-auto cursor-zoom-in object-contain rounded-[var(--radius-control)]"
                                         @click="enlargeModal = true"
                                         title="Klik untuk memperbesar">
                                    <p class="mt-2.5 font-tabular text-[10px] font-bold text-neutral-800">QRIS STANDAR PEMBAYARAN</p>
                                    <p class="mt-0.5 text-[10px] text-neutral-500">M-Banking & E-Wallet (BCA, Mandiri, BRI, GoPay, OVO, Dana, ShopeePay)</p>
                                </div>
                                <p class="mt-2 text-xs text-text-muted">Ketuk gambar QRIS untuk memperbesar.</p>

                                <div class="mt-3">
                                    <a href="{{ route('payment.qris', ['download' => 1]) }}"
                                       download="QRIS-SynemaX"
                                       class="inline-flex min-h-10 items-center justify-center rounded-[var(--radius-control)] border border-border-input bg-surface px-3.5 text-xs font-bold text-text hover:border-primary">
                                        Simpan gambar QRIS
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="mt-4 rounded-[var(--radius-control)] border border-border bg-surface-2 p-3 text-xs text-text-muted">
                                Gambar QRIS sedang disiapkan pengelola. Anda dapat menghubungi admin lewat WhatsApp untuk mendapatkan kode pembayaran.
                            </div>
                        @endif

                        @if ($qrisPaymentNotes)
                            <div class="mt-3 rounded-[var(--radius-control)] bg-surface-2 p-3 text-xs text-text-muted leading-relaxed">
                                <p class="font-bold text-text">Catatan:</p>
                                <p class="mt-0.5">{{ $qrisPaymentNotes }}</p>
                            </div>
                        @endif

                        <div class="mt-4 rounded-[var(--radius-control)] border border-border bg-surface-2 p-3.5 text-xs text-text-muted">
                            <h3 class="font-bold text-text">Cara bayar QRIS</h3>
                            <ol class="mt-1.5 list-decimal space-y-1 pl-4">
                                <li>Buka aplikasi m-Banking atau dompet digital Anda.</li>
                                <li>Pilih menu <strong>Scan QRIS</strong>.</li>
                                <li>Arahkan kamera ke gambar QR di atas atau unggah dari galeri.</li>
                                <li>Pastikan nominal bayar tepat <strong class="font-tabular text-text">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</strong>.</li>
                                <li>Simpan bukti transfer dan unggah pada formulir di bawah.</li>
                            </ol>
                        </div>
                    </div>

                {{-- Opsi 2: Transfer Bank / E-Wallet --}}
                @elseif ($booking->payment_method === 'transfer')
                    <div class="mt-5 border-t border-border pt-5">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-sm font-bold text-text">Rekening tujuan transfer</h2>
                            <span class="text-xs text-text-muted">{{ count($transferAccounts) }} rekening</span>
                        </div>

                        <div class="mt-3 space-y-2.5">
                            @forelse ($transferAccounts as $acc)
                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-[var(--radius-control)] border border-border bg-surface p-3.5">
                                    <div>
                                        <span class="rounded-[var(--radius-control)] bg-surface-2 px-2 py-0.5 text-xs font-extrabold text-primary">
                                            {{ $acc['bank_name'] }}
                                        </span>
                                        <p class="mt-1.5 font-tabular text-lg font-black text-text">{{ $acc['account_number'] }}</p>
                                        <p class="mt-0.5 text-xs text-text-muted">a.n. <strong class="text-text">{{ $acc['account_holder'] }}</strong></p>
                                    </div>
                                    <button type="button"
                                            @click="copyAccount('{{ $acc['account_number'] }}')"
                                            class="inline-flex min-h-9 items-center rounded-[var(--radius-control)] border border-border-input bg-surface px-3 text-xs font-bold text-text hover:border-primary">
                                        <span x-text="copiedAccount === '{{ $acc['account_number'] }}' ? 'Tersalin' : 'Salin nomor'">Salin nomor</span>
                                    </button>
                                </div>
                            @empty
                                <div class="rounded-[var(--radius-control)] border border-border bg-surface-2 p-3 text-xs text-text-muted">
                                    Rekening pembayaran sedang disiapkan. Silakan hubungi admin lewat WhatsApp.
                                </div>
                            @endforelse
                        </div>

                        @if ($transferPaymentNotes)
                            <div class="mt-3 rounded-[var(--radius-control)] bg-surface-2 p-3 text-xs text-text-muted leading-relaxed">
                                <p class="font-bold text-text">Catatan:</p>
                                <p class="mt-0.5">{{ $transferPaymentNotes }}</p>
                            </div>
                        @endif

                        <div class="mt-4 rounded-[var(--radius-control)] border border-border bg-surface-2 p-3.5 text-xs text-text-muted">
                            <h3 class="font-bold text-text">Cara bayar transfer</h3>
                            <ol class="mt-1.5 list-decimal space-y-1 pl-4">
                                <li>Transfer ke rekening atau nomor e-wallet di atas.</li>
                                <li>Pastikan nominal transfer tepat <strong class="font-tabular text-text">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</strong>.</li>
                                <li>Simpan bukti tangkapan layar atau struk ATM.</li>
                            </ol>
                        </div>
                    </div>
                @endif

                {{-- Formulir Unggah Bukti Bayar --}}
                <div class="mt-6 border-t border-border pt-6">
                    <h3 class="text-sm font-bold text-text">Kirim bukti bayar</h3>
                    <p class="mt-0.5 text-xs text-text-muted">
                        Unggah foto atau tangkapan layar bukti transfer (JPG, PNG, atau WebP maks. {{ round($maxPaymentProofKb / 1024) }} MB).
                    </p>

                    <form method="POST"
                          action="{{ route('booking.proof.upload', ['code' => $booking->code]) }}"
                          enctype="multipart/form-data"
                          class="mt-3 space-y-3 rounded-[var(--radius-control)] border border-border bg-surface p-4">
                        @csrf
                        <input type="hidden" name="whatsapp" value="{{ $booking->whatsapp }}">

                        <div>
                            <label for="payment_proof" class="block text-xs font-bold text-text">Berkas bukti pembayaran</label>
                            <input id="payment_proof"
                                   name="payment_proof"
                                   type="file"
                                   required
                                   accept="image/jpeg,image/png,image/webp"
                                   @change="handleFileChange($event)"
                                   class="mt-1.5 block w-full text-xs text-text file:mr-3 file:min-h-10 file:rounded-[var(--radius-control)] file:border file:border-border-input file:bg-surface file:px-3 file:text-xs file:font-bold file:text-text hover:file:border-primary">
                            <p x-show="selectedFileName" class="mt-1.5 text-xs font-bold text-primary" x-text="'Berkas dipilih: ' + selectedFileName"></p>
                        </div>

                        {{-- Tombol Utama: Kirim bukti bayar --}}
                        <button type="submit"
                                :disabled="!hasFile"
                                class="inline-flex min-h-11 w-full items-center justify-center rounded-[var(--radius-control)] bg-primary px-5 text-xs font-bold text-white shadow-xs transition hover:opacity-95 disabled:cursor-not-allowed disabled:opacity-45">
                            Kirim bukti bayar
                        </button>
                    </form>
                </div>

                {{-- Tautan Aksi Sekunder --}}
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border pt-3">
                    @if ($whatsAppUrl)
                        <a href="{{ $whatsAppUrl }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="inline-flex min-h-10 items-center justify-center rounded-[var(--radius-control)] border border-border-input bg-surface px-3.5 text-xs font-bold text-text hover:border-primary">
                            Kirim bukti lewat WhatsApp
                        </a>
                    @endif

                    <button type="button"
                            @click="cancelModal = true"
                            class="text-xs text-text-muted hover:text-danger-text hover:underline">
                        Batalkan booking
                    </button>
                </div>
            </div>
        </div>

        {{-- Dialog Modal Konfirmasi Pembatalan --}}
        <div x-show="cancelModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-overlay p-4"
             @click.self="cancelModal = false">
            <div class="w-full max-w-sm rounded-[var(--radius-control)] border border-border bg-surface p-5 shadow-token">
                <h3 class="text-base font-bold text-text">Batalkan booking ini?</h3>
                <p class="mt-2 text-xs leading-relaxed text-text-muted">
                    Apakah Anda yakin ingin membatalkan pemesanan ini? Kursi yang telah dipesan akan dilepaskan kembali.
                </p>
                <form method="POST" action="{{ route('booking.cancel', ['code' => $booking->code]) }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="whatsapp" value="{{ $booking->whatsapp }}">
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="cancelModal = false"
                                class="inline-flex min-h-10 items-center justify-center rounded-[var(--radius-control)] border border-border-input px-3 text-xs font-bold text-text">
                            Jangan batalkan
                        </button>
                        <button type="submit"
                                class="inline-flex min-h-10 items-center justify-center rounded-[var(--radius-control)] bg-danger px-3 text-xs font-bold text-white hover:opacity-95">
                            Ya, batalkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Perbesar Gambar QRIS --}}
        @if ($booking->payment_method === 'qris' && $hasQrisImage)
            <div x-show="enlargeModal" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-overlay p-4"
                 @click.self="enlargeModal = false">
                <div class="relative w-full max-w-lg rounded-[var(--radius-control)] border border-border bg-white p-5 shadow-token">
                    <button type="button" @click="enlargeModal = false"
                            class="absolute right-3 top-3 flex size-9 items-center justify-center rounded-[var(--radius-control)] border border-neutral-300 bg-neutral-100 text-neutral-800 hover:bg-neutral-200"
                            title="Tutup">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <div class="text-center pt-2">
                        <p class="text-xs font-bold text-neutral-500">QRIS SynemaX</p>
                        <p class="mt-0.5 text-base font-black text-neutral-900">{{ $qrisMerchantName ?: 'SynemaX' }}</p>
                        <div class="mx-auto mt-3 max-w-md rounded-[var(--radius-control)] border border-neutral-200 bg-white p-3">
                            <img src="{{ route('payment.qris', ['v' => $qrisVersion]) }}"
                                 alt="QRIS {{ $qrisMerchantName }}"
                                 class="max-h-[65vh] w-auto max-w-full mx-auto object-contain rounded-[var(--radius-control)]">
                            <p class="mt-2.5 font-tabular text-xs font-extrabold text-neutral-800">
                                Total: Rp {{ number_format($booking->total_price, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
