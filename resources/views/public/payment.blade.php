@extends('layouts.public')

@section('title', 'Pembayaran booking '.$booking->code)
@section('meta')
    <meta name="description" content="Selesaikan pembayaran untuk booking {{ $booking->code }} di SynemaX Home Cinema.">
    <meta property="og:title" content="Pembayaran booking {{ $booking->code }} · SynemaX">
    <meta property="og:description" content="Total: Rp {{ number_format($booking->total_price, 0, ',', '.') }} · {{ $booking->film?->title ?? 'Home Cinema' }}">
@endsection

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-6 sm:px-6 sm:py-10"
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


        {{-- Flash status or alert --}}
        @if (session('status'))
            <div role="status" class="mb-5 rounded-2xl border border-success/30 bg-success/10 p-4 text-sm font-semibold text-success">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="mb-5 rounded-2xl border border-danger/30 bg-danger/10 p-4 text-sm font-semibold text-danger">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- Hitung Mundur Batas Waktu Bayar --}}
        <div x-show="!isExpired" class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-warning/40 bg-warning/10 p-4">
            <div class="flex items-center gap-3">
                <div class="grid size-10 place-items-center rounded-xl bg-warning/20 text-warning" aria-hidden="true">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-warning">Sisa Waktu Pembayaran</p>
                    <p class="font-mono text-2xl font-black text-text sm:text-3xl" x-text="formatTime(timeLeft)">--:--</p>
                </div>
            </div>
            <p class="max-w-xs text-xs text-text-muted">Selesaikan pembayaran dan kirim bukti sebelum waktu habis agar kursi tidak dilepas ke orang lain.</p>
        </div>

        {{-- Kotak Peringatan Waktu Habis --}}
        <div x-show="isExpired" x-cloak class="mb-6 rounded-2xl border border-danger/40 bg-danger/10 p-5 text-center">
            <div class="mx-auto grid size-12 place-items-center rounded-full bg-danger/20 text-danger" aria-hidden="true">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h1 class="mt-3 text-lg font-black text-danger">Waktu Pembayaran Habis</h1>
            <p class="mt-1 text-sm text-text-muted">Batas waktu transfer telah berakhir dan kursi booking telah dilepaskan kembali.</p>
            <div class="mt-4 flex flex-wrap justify-center gap-3">
                <a href="{{ route('booking.create', ['session' => $booking->session_id]) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-accent px-4 text-xs font-bold text-accent-contrast hover:opacity-90">
                    Buat booking baru
                </a>
                @if ($whatsAppUrl)
                    <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-border bg-surface px-4 text-xs font-bold text-text hover:bg-surface-2">
                        <span>Hubungi admin</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- Catatan Penolakan Bukti Sebelumnya (jika ada) --}}
        @if ($booking->proof_rejection_note)
            <div class="mb-5 rounded-2xl border border-danger/40 bg-danger/10 p-4 text-sm text-danger">
                <div class="flex items-start gap-2.5">
                    <svg class="size-5 shrink-0 text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    <div>
                        <p class="font-bold">Bukti pembayaran sebelumnya ditolak admin</p>
                        <p class="mt-1">Alasan: <span class="font-bold underline">{{ $booking->proof_rejection_note }}</span></p>
                        <p class="mt-1 text-xs text-text-muted">Silakan transfer dengan benar dan kirim bukti baru sebelum batas waktu pembayaran.</p>
                    </div>
                </div>
            </div>
        @endif

        <div x-show="!isExpired">
            {{-- Kotak Total Pembayaran --}}
            <div class="rounded-3xl border border-accent/30 bg-surface p-5 shadow-token sm:p-7">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border pb-5">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-text-muted">Total Pembayaran</span>
                        <p class="mt-1 text-3xl font-black text-accent sm:text-4xl">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
                    </div>
                    <button type="button"
                            @click="copyNominal({{ $booking->total_price }})"
                            class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-accent px-4 text-xs font-bold text-accent-contrast shadow-xs hover:opacity-90">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                        </svg>
                        <span x-text="copiedNominal ? 'Nominal disalin' : 'Salin nominal'">Salin nominal</span>
                    </button>
                </div>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-text-muted">
                    <p>Kode booking: <strong class="font-mono font-bold text-text">{{ $booking->code }}</strong></p>
                    <p>Pastikan nominal yang dibayar sesuai persis.</p>
                </div>

                {{-- Instruksi Metode Pembayaran --}}
                @if ($booking->payment_method === 'qris')
                    <div class="mt-6 border-t border-border pt-6">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-accent">Pembayaran QRIS</span>
                                <h2 class="mt-0.5 text-lg font-black text-text">Pindai Kode QRIS</h2>
                            </div>
                            <span class="rounded-full bg-accent/10 px-3 py-1 text-xs font-bold text-accent">{{ $qrisMerchantName ?: 'SynemaX' }}</span>
                        </div>

                        @if ($hasQrisImage)
                            <div class="mt-4 text-center">
                                {{-- Quiet zone putih bersih: wadah fleksibel mengikuti rasio QRIS tanpa terpotong --}}
                                <div class="mx-auto inline-block max-w-sm rounded-2xl bg-white p-4 sm:p-5 shadow-sm text-center">
                                    <img src="{{ route('payment.qris', ['v' => $qrisVersion]) }}"
                                         alt="QRIS {{ $qrisMerchantName }}"
                                         class="max-h-72 sm:max-h-80 w-auto max-w-full mx-auto cursor-zoom-in object-contain rounded-lg"
                                         @click="enlargeModal = true"
                                         title="Ketuk untuk memperbesar">
                                    <p class="mt-3 font-mono text-[10px] font-bold tracking-wide text-neutral-800">NMID / QRIS STANDAR PEMBAYARAN</p>
                                    <p class="mt-1 text-[10px] font-medium text-neutral-500">BCA · Mandiri · BRI · BNI · GoPay · OVO · Dana · ShopeePay</p>
                                </div>
                                <p class="mt-2 text-xs text-text-muted">Ketuk gambar QRIS untuk memperbesar tampilan.</p>

                                <div class="mt-3">
                                    <a href="{{ route('payment.qris', ['download' => 1]) }}" download="QRIS-SynemaX" class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-xl border border-border bg-surface px-4 text-xs font-bold text-text hover:border-accent">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        <span>Simpan gambar QRIS</span>
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="mt-4 rounded-xl border border-warning/30 bg-warning/10 p-4 text-sm text-text">
                                <p class="font-bold text-warning">Gambar QRIS sedang diperbarui admin</p>
                                <p class="mt-1 text-xs text-text-muted">Silakan hubungi admin via WhatsApp untuk mendapatkan kode QRIS atau rekening pembayaran.</p>
                            </div>
                        @endif

                        @if ($qrisPaymentNotes)
                            <div class="mt-4 rounded-xl border border-accent/20 bg-accent/5 p-3 text-xs leading-relaxed text-text">
                                <p class="font-bold text-accent">Catatan Pembayaran:</p>
                                <p class="mt-0.5 text-text-muted">{{ $qrisPaymentNotes }}</p>
                            </div>
                        @endif

                        <div class="mt-4 rounded-2xl border border-border bg-surface-2 p-4">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-text">Langkah Pembayaran QRIS</h3>
                            <ol class="mt-2 list-decimal space-y-1 pl-4 text-xs text-text-muted">
                                <li>Buka aplikasi m-Banking atau e-Wallet yang Anda gunakan.</li>
                                <li>Pilih menu <strong>Scan / Bayar QRIS</strong>.</li>
                                <li>Arahkan kamera ke kode QR di atas (atau pilih dari galeri foto).</li>
                                <li>Pastikan nominal bayar tepat <strong>Rp {{ number_format($booking->total_price, 0, ',', '.') }}</strong>.</li>
                                <li>Selesaikan transaksi dan simpan bukti tangkapan layarnya.</li>
                            </ol>
                        </div>
                    </div>
                @elseif ($booking->payment_method === 'transfer')
                    <div class="mt-6 border-t border-border pt-6">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-accent">Transfer Bank / E-Wallet</span>
                                <h2 class="mt-0.5 text-lg font-black text-text">Rekening Tujuan</h2>
                            </div>
                            <span class="rounded-full bg-accent/10 px-3 py-1 text-xs font-bold text-accent">{{ count($transferAccounts) }} Rekening Tersedia</span>
                        </div>

                        <div class="mt-4 space-y-3">
                            @forelse ($transferAccounts as $acc)
                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border bg-surface-2 p-4 transition hover:border-accent">
                                    <div>
                                        <span class="rounded-lg bg-surface px-2.5 py-1 text-xs font-black uppercase text-accent">{{ $acc['bank_name'] }}</span>
                                        <p class="mt-2 font-mono text-xl font-black tracking-wide text-text">{{ $acc['account_number'] }}</p>
                                        <p class="mt-0.5 text-xs text-text-muted">a.n. <strong class="text-text">{{ $acc['account_holder'] }}</strong></p>
                                    </div>
                                    <button type="button"
                                            @click="copyAccount('{{ $acc['account_number'] }}')"
                                            class="inline-flex min-h-10 items-center gap-1.5 rounded-xl border border-border bg-surface px-3 text-xs font-bold text-text hover:border-accent">
                                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                                        </svg>
                                        <span x-text="copiedAccount === '{{ $acc['account_number'] }}' ? 'Tersalin' : 'Salin'">Salin</span>
                                    </button>
                                </div>
                            @empty
                                <div class="rounded-xl border border-warning/30 bg-warning/10 p-4 text-xs text-text">
                                    <p class="font-bold text-warning">Rekening transfer sedang diperbarui admin</p>
                                    <p class="mt-1 text-text-muted">Silakan hubungi admin studio via WhatsApp untuk nomor rekening pembayaran.</p>
                                </div>
                            @endforelse
                        </div>

                        @if ($transferPaymentNotes)
                            <div class="mt-4 rounded-xl border border-accent/20 bg-accent/5 p-3 text-xs leading-relaxed text-text">
                                <p class="font-bold text-accent">Catatan Pembayaran:</p>
                                <p class="mt-0.5 text-text-muted">{{ $transferPaymentNotes }}</p>
                            </div>
                        @endif

                        <div class="mt-4 rounded-2xl border border-border bg-surface-2 p-4">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-text">Langkah Pembayaran Transfer</h3>
                            <ol class="mt-2 list-decimal space-y-1 pl-4 text-xs text-text-muted">
                                <li>Transfer ke salah satu rekening atau nomor e-wallet di atas.</li>
                                <li>Pastikan nominal transfer tepat <strong>Rp {{ number_format($booking->total_price, 0, ',', '.') }}</strong>.</li>
                                <li>Simpan bukti transfer (struk ATM / tangkapan layar m-Banking).</li>
                            </ol>
                        </div>
                    </div>
                @endif

                {{-- Formulir Unggah Bukti Bayar --}}
                <div class="mt-6 border-t border-border pt-6">
                    <h3 class="text-base font-bold text-text">Kirim Bukti Pembayaran</h3>
                    <p class="mt-1 text-xs text-text-muted">Sudah transfer? Silakan unggah foto atau tangkapan layar bukti bayar (JPG, PNG, atau WEBP maks. {{ round($maxPaymentProofKb / 1024) }} MB).</p>

                    <form method="POST" action="{{ route('booking.proof.upload', ['code' => $booking->code]) }}" enctype="multipart/form-data" class="mt-4 space-y-3 rounded-2xl border border-border bg-surface-2 p-4">
                        @csrf
                        <input type="hidden" name="whatsapp" value="{{ $booking->whatsapp }}">

                        <div>
                            <label for="payment_proof" class="block text-xs font-semibold text-text">File Bukti Bayar</label>
                            <input id="payment_proof"
                                   name="payment_proof"
                                   type="file"
                                   required
                                   accept="image/jpeg,image/png,image/webp"
                                   @change="handleFileChange($event)"
                                   class="mt-2 block w-full text-xs text-text file:mr-3 file:min-h-10 file:rounded-xl file:border-0 file:bg-surface file:px-4 file:text-xs file:font-bold file:text-text hover:file:opacity-90">
                            <p x-show="selectedFileName" class="mt-2 text-xs font-medium text-accent" x-text="'File dipilih: ' + selectedFileName"></p>
                        </div>

                        {{-- SATU Tombol Utama: Kirim bukti bayar (dinonaktifkan jika belum ada file) --}}
                        <button type="submit"
                                :disabled="!hasFile"
                                class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-accent px-5 text-sm font-bold text-accent-contrast shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40">
                            Kirim bukti bayar
                        </button>
                    </form>
                </div>

                {{-- Tombol Sekunder & Aksi Tambahan --}}
                <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-border pt-4">
                    @if ($whatsAppUrl)
                        <a href="{{ $whatsAppUrl }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-border bg-surface px-4 text-xs font-semibold text-text hover:bg-surface-2">
                            <svg class="size-4 text-success" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2zm.01 1.67c2.2 0 4.26.86 5.82 2.42a8.17 8.17 0 012.41 5.82c0 4.54-3.7 8.24-8.24 8.24-1.45 0-2.88-.38-4.13-1.12l-.3-.18-3.08.81.82-3-.19-.31a8.16 8.16 0 01-1.25-4.44c0-4.54 3.7-8.24 8.24-8.24zm4.52 11.66c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.98-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.25-1.5-1.4-1.75-.15-.25-.02-.39.11-.51.11-.11.25-.29.38-.44.12-.14.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.35-.77-1.85-.2-.49-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1s.9 2.43 1.03 2.6c.13.17 1.77 2.7 4.28 3.79.6.26 1.07.41 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.15-1.18-.06-.1-.23-.17-.48-.29z"/>
                            </svg>
                            <span>Kirim bukti lewat WhatsApp</span>
                        </a>
                    @endif

                    <button type="button"
                            @click="cancelModal = true"
                            class="text-xs text-text-muted hover:text-danger hover:underline">
                        Batalkan booking
                    </button>
                </div>
            </div>
        </div>

        {{-- Dialog Modal Konfirmasi Pembatalan --}}
        <div x-show="cancelModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" @click.self="cancelModal = false">
            <div class="w-full max-w-sm rounded-2xl border border-border bg-surface p-5 shadow-token">
                <h3 class="text-base font-bold text-text">Batalkan Booking?</h3>
                <p class="mt-2 text-xs leading-relaxed text-text-muted">Apakah Anda yakin ingin membatalkan booking ini? Kursi yang telah dipesan akan dilepaskan kembali.</p>
                <form method="POST" action="{{ route('booking.cancel', ['code' => $booking->code]) }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="whatsapp" value="{{ $booking->whatsapp }}">
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="cancelModal = false" class="min-h-10 rounded-xl border border-border px-3 text-xs font-semibold text-text">
                            Jangan batalkan
                        </button>
                        <button type="submit" class="min-h-10 rounded-xl bg-danger px-3 text-xs font-bold text-danger-contrast hover:opacity-90">
                            Ya, batalkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Perbesar QRIS --}}
        @if ($booking->payment_method === 'qris' && $hasQrisImage)
            <div x-show="enlargeModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4" @click.self="enlargeModal = false">
                <div class="relative w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl">
                    <button type="button" @click="enlargeModal = false" class="absolute right-4 top-4 grid size-8 place-items-center rounded-full bg-neutral-100 text-neutral-700 hover:bg-neutral-200" title="Tutup">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <div class="text-center">
                        <p class="text-xs font-bold uppercase tracking-wider text-neutral-500">QRIS SynemaX</p>
                        <p class="mt-0.5 text-base font-black text-neutral-900">{{ $qrisMerchantName ?: 'SynemaX' }}</p>
                        <div class="mx-auto mt-4 max-w-md rounded-2xl border border-neutral-200 bg-white p-4">
                            <img src="{{ route('payment.qris', ['v' => $qrisVersion]) }}"
                                 alt="QRIS {{ $qrisMerchantName }}"
                                 class="max-h-[70vh] w-auto max-w-full mx-auto object-contain rounded-lg">
                            <p class="mt-3 font-mono text-xs font-bold text-neutral-800">Total: Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </section>
@endsection
