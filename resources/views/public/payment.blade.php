@extends('layouts.public')

@section('title', 'Pembayaran Booking ' . $booking->code . ': SynemaX Premiere')
@section('meta')
    <meta name="description" content="Selesaikan pembayaran untuk booking {{ $booking->code }} di SynemaX.">
    <meta property="og:title" content="Pembayaran booking {{ $booking->code }}: SynemaX">
    <meta property="og:description" content="Total: Rp {{ number_format($booking->total_price, 0, ',', '.') }}, {{ $booking->film?->title ?? 'Film Sesi' }}">
    @if ($booking->payment_method === 'qris' && $hasQrisImage)
        <link rel="preload" as="image" href="{{ route('payment.qris', ['v' => $qrisVersion]) }}" fetchpriority="high">
    @endif
@endsection

@section('content')
    <div class="mx-auto max-w-[520px] w-full px-4 py-5 sm:py-8"
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
             totalSeconds: @js(($paymentTimeLimitMinutes ?? 30) * 60),
             get progressPct() {
                 return Math.min(100, Math.max(0, (this.timeLeft / (this.totalSeconds || 1800)) * 100));
             },
             get isUrgent() {
                 return this.timeLeft > 0 && this.timeLeft <= 180;
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
             fileError: '',
             isCompressing: false,
             compressStatus: '',
             async handleFileChange(event) {
                 const inputEl = event.target;
                 const files = inputEl.files;
                 if (!files || files.length === 0) {
                     this.fileError = '';
                     this.hasFile = false;
                     this.selectedFileName = '';
                     this.compressStatus = '';
                     return;
                 }
                 const file = files[0];
                 if (!file.type || !file.type.startsWith('image/')) {
                     this.fileError = 'Hanya file gambar (JPG, PNG, WebP) yang diperbolehkan.';
                     this.hasFile = false;
                     this.selectedFileName = '';
                     inputEl.value = '';
                     return;
                 }

                 this.fileError = '';
                 this.isCompressing = true;
                 this.compressStatus = 'Mengoptimalkan ukuran gambar agar cepat terkirim...';

                 try {
                     const compressedFile = await this.compressImage(file);
                     try {
                         const dt = new DataTransfer();
                         dt.items.add(compressedFile);
                         inputEl.files = dt.files;
                     } catch (e) {
                         // DataTransfer fallback
                     }

                     const newSizeKb = Math.round(compressedFile.size / 1024);
                     this.hasFile = true;
                     this.selectedFileName = `${compressedFile.name} (${newSizeKb} KB)`;
                     this.compressStatus = '';
                 } catch (err) {
                     console.error('Compression failed:', err);
                     const maxKb = @js($maxPaymentProofKb ?? 4096);
                     if (file.size > maxKb * 1024) {
                         this.fileError = `Ukuran file (${(file.size / (1024 * 1024)).toFixed(1)} MB) terlalu besar. Silakan pilih foto dengan resolusi lebih kecil.`;
                         this.hasFile = false;
                         this.selectedFileName = '';
                         inputEl.value = '';
                     } else {
                         this.hasFile = true;
                         this.selectedFileName = file.name;
                     }
                     this.compressStatus = '';
                 } finally {
                     this.isCompressing = false;
                 }
             },
             compressImage(file) {
                 return new Promise((resolve, reject) => {
                     const reader = new FileReader();
                     reader.onload = (e) => {
                         const img = new Image();
                         img.onload = () => {
                             const maxDimension = 1600;
                             let width = img.width;
                             let height = img.height;

                             if (width > maxDimension || height > maxDimension) {
                                 if (width > height) {
                                     height = Math.round((height * maxDimension) / width);
                                     width = maxDimension;
                                 } else {
                                     width = Math.round((width * maxDimension) / height);
                                     height = maxDimension;
                                 }
                             }

                             const canvas = document.createElement('canvas');
                             canvas.width = width;
                             canvas.height = height;
                             const ctx = canvas.getContext('2d');
                             ctx.drawImage(img, 0, 0, width, height);

                             canvas.toBlob((blob) => {
                                 if (!blob) {
                                     resolve(file);
                                     return;
                                 }
                                 const cleanName = file.name.replace(/\.[^/.]+$/, '') + '.jpg';
                                 const newFile = new File([blob], cleanName, {
                                     type: 'image/jpeg',
                                     lastModified: Date.now()
                                 });
                                 resolve(newFile);
                             }, 'image/jpeg', 0.82);
                         };
                         img.onerror = () => reject(new Error('Gagal memuat gambar'));
                         img.src = e.target.result;
                     };
                     reader.onerror = () => reject(new Error('Gagal membaca file'));
                     reader.readAsDataURL(file);
                 });
             }
         }">

        {{-- Stepper 3 Tahap Alur Booking Online --}}
        <nav aria-label="Tahapan Booking" class="mb-5 no-print">
            <ol class="grid grid-cols-3 gap-2 text-center text-xs">
                {{-- Langkah 1: Booking (Selesai) --}}
                <li class="flex items-center justify-center gap-1.5 rounded-xl border border-emerald-600/30 bg-emerald-500/10 p-2 font-medium">
                    <span class="grid size-5 place-items-center rounded-full bg-emerald-700 text-white" aria-hidden="true">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </span>
                    <span class="font-bold text-slate-800 dark:text-slate-100">Booking</span>
                </li>

                {{-- Langkah 2: Bayar (Aktif) --}}
                <li class="flex items-center justify-center gap-1.5 rounded-xl border border-amber-500/50 bg-amber-500/10 p-2 font-bold">
                    <span class="grid size-5 place-items-center rounded-full bg-amber-400 text-slate-950 text-[11px] font-black" aria-hidden="true">2</span>
                    <span class="text-amber-900 dark:text-amber-200">Bayar</span>
                </li>

                {{-- Langkah 3: Tiket (Belum) --}}
                <li class="flex items-center justify-center gap-1.5 rounded-xl border border-border bg-surface p-2 font-medium text-text-muted">
                    <span class="grid size-5 place-items-center rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-[11px] font-bold" aria-hidden="true">3</span>
                    <span>Tiket</span>
                </li>
            </ol>
        </nav>

        {{-- Flash Pesan / Alert Status --}}
        @if (session('status'))
            <div role="status" class="mb-4 rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-4 text-xs font-bold text-emerald-400">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div role="alert" class="mb-4 rounded-xl border border-danger/40 bg-danger-surface p-4 text-xs font-bold text-danger-text">
                {{ session('error') }}
            </div>
        @endif

        @if (session('warning'))
            <div role="status" class="mb-4 rounded-xl border border-warning/40 bg-warning-surface p-4 text-xs font-bold text-warning">
                {{ session('warning') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="mb-4 rounded-xl border border-danger/40 bg-danger-surface p-4 text-xs font-semibold text-danger-text space-y-1">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- ==================== COUNTDOWN TIMER PEMBAYARAN & PROGRESS BAR ==================== --}}
        <div x-show="!isExpired"
             class="mb-6 rounded-xl border p-5 shadow-none transition-all"
             :class="isUrgent ? 'badge-danger-soft' : 'badge-accent-soft'">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="size-2 rounded-full"
                              :class="isUrgent ? 'bg-danger' : 'bg-amber-600 dark:bg-amber-400'"></span>
                        <p class="text-xs font-bold uppercase tracking-wider text-text-muted">
                            Sisa waktu pembayaran
                        </p>
                    </div>
                    <div class="mt-1 flex items-baseline gap-2">
                        <p class="font-tabular text-3xl font-black sm:text-4xl"
                           :class="isUrgent ? 'text-danger-text' : 'text-amber-800 dark:text-amber-300'"
                           x-text="formatTime(timeLeft)">--:--</p>
                        <span class="text-xs text-text-muted">Menit : Detik</span>
                    </div>
                </div>
                <div class="text-right">
                    <span class="rounded-lg bg-surface-2 px-2.5 py-1 text-[11px] font-bold text-text-muted">
                        Batas: {{ $paymentTimeLimitMinutes ?? 30 }} Menit
                    </span>
                    <p class="mt-1 text-[11px] text-text-muted">Tempatmu ditahan sementara</p>
                </div>
            </div>

            {{-- Visual Progress Bar --}}
            <div class="mt-4 w-full overflow-hidden rounded-full progress-track h-2">
                <div class="h-full rounded-full transition-all duration-1000 ease-linear shadow-none"
                     :class="isUrgent ? 'bg-danger' : 'progress-fill'"
                     :style="`width: ${progressPct}%`"></div>
            </div>
            <p class="mt-2 text-[11px] text-text-muted text-center sm:text-left">
                Kirim bukti transfer sebelum penghitung waktu habis agar tempat Anda tidak dibatalkan otomatis.
            </p>
        </div>

        {{-- Banner Jika Waktu Pembayaran Habis --}}
        <div x-show="isExpired" x-cloak
             class="mb-6 rounded-xl border border-danger/40 bg-danger-surface p-6 text-center text-danger-text">
            <h1 class="text-lg font-black text-danger-text sm:text-xl">Waktu Pembayaran Habis</h1>
            <p class="mt-1.5 text-xs text-danger-text/90 leading-relaxed">
                Batas waktu transfer telah berakhir dan tempat pesanan telah dilepaskan kembali ke publik.
            </p>
            <div class="mt-5 flex flex-wrap justify-center gap-3">
                <a href="{{ route('booking.create', ['session' => $booking->session_id]) }}"
                   class="btn-gold-cinema inline-flex min-h-11 items-center justify-center rounded-xl px-5 text-xs font-bold">
                    Buat Booking Baru
                </a>
            </div>
        </div>

        {{-- Catatan Penolakan Bukti Sebelumnya (Jika Ada) --}}
        @if ($booking->proof_rejection_note)
            <div class="mb-5 rounded-xl border border-danger/40 bg-danger-surface p-4 text-xs text-danger-text">
                <p class="font-bold">Bukti pembayaran sebelumnya belum dapat disetujui admin:</p>
                <p class="mt-1 font-semibold">{{ $booking->proof_rejection_note }}</p>
                <p class="mt-1 opacity-90">Silakan unggah ulang bukti transfer yang jelas sebelum waktu pembayaran habis.</p>
            </div>
        @endif

        <div x-show="!isExpired">
            {{-- Kartu Total Pembayaran --}}
            <div class="rounded-xl border border-border bg-surface-2 dark:bg-[#0E131F] p-5 sm:p-6 shadow-none">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border pb-4">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-text">Total Yang Harus Ditransfer</span>
                        <p class="mt-0.5 font-tabular text-3xl font-black text-text sm:text-4xl">
                            Rp {{ number_format($booking->total_price, 0, ',', '.') }}
                        </p>
                    </div>
                    <button type="button"
                            @click="copyNominal({{ $booking->total_price }})"
                            class="btn-gold-cinema inline-flex min-h-10 items-center gap-1.5 rounded-xl px-4 text-xs font-bold">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <span x-text="copiedNominal ? 'Nominal tersalin' : 'Salin nominal'">Salin nominal</span>
                    </button>
                </div>

                {{-- Rincian Transparansi Biaya (Tiket + Jajan) --}}
                <div class="mt-3.5 pt-3 border-t border-border space-y-2 text-xs">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-text-muted">Rincian Pembayaran</p>
                    <div class="flex justify-between items-center text-text-muted">
                        <span>Tiket ({{ $booking->isPrivate() ? ('Sewa Private (' . ($booking->seats_reserved ?: 6) . ' orang)') : ($booking->isCouple() ? 'Paket Couple' : ($booking->seat_price ? $booking->pax . ' × Rp ' . number_format($booking->seat_price, 0, ',', '.') : $booking->pax . ' orang')) }})</span>
                        <span class="font-tabular font-bold text-text">Rp {{ number_format($booking->ticket_total, 0, ',', '.') }}</span>
                    </div>

                    @php
                        $paymentSnackItems = $booking->items->where('status', '!=', 'dibatalkan');
                        if ($paymentSnackItems->isEmpty() && $booking->items->isNotEmpty() && $booking->snack_total > 0) {
                            $paymentSnackItems = $booking->items;
                        }
                    @endphp

                    @if ($paymentSnackItems->isNotEmpty())
                        @foreach ($paymentSnackItems as $snackItem)
                            <div class="flex justify-between items-center text-text-muted">
                                <span class="truncate pr-2">Jajan ({{ $snackItem->name_snapshot }} × {{ $snackItem->qty }}{{ $snackItem->note ? ' · ' . $snackItem->note : '' }})</span>
                                <span class="font-tabular font-semibold text-text shrink-0">Rp {{ number_format($snackItem->subtotal, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    @elseif ($booking->snack_total > 0)
                        <div class="flex justify-between items-center text-text-muted">
                            <span>Pesanan Jajan</span>
                            <span class="font-tabular font-semibold text-text">Rp {{ number_format($booking->snack_total, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-text-muted">
                    <p>Kode Booking: <strong class="font-tabular font-bold text-amber-700 dark:text-amber-400">{{ $booking->code }}</strong></p>
                    <p>Transfer tepat sesuai jumlah di atas.</p>
                </div>

                {{-- ==================== METODE 1: QRIS INSTAN ==================== --}}
                @if ($booking->payment_method === 'qris')
                    <div class="mt-6 border-t border-border dark:border-white/10 pt-6">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-sm font-black text-text">Pindai kode QRIS</h2>
                            <span class="badge-accent-soft rounded-lg px-2.5 py-0.5 text-xs font-bold">
                                {{ $qrisMerchantName ?: 'SynemaX Cinema' }}
                            </span>
                        </div>

                        @if ($hasQrisImage)
                            <div class="mt-5 text-center">
                                <div class="relative mx-auto inline-block max-w-sm rounded-2xl border-2 border-amber-400/50 bg-white p-5 shadow-2xl text-center">
                                    {{-- Scanner Corner Brackets --}}
                                    <div class="absolute top-2 left-2 size-4 border-t-2 border-l-2 border-black"></div>
                                    <div class="absolute top-2 right-2 size-4 border-t-2 border-r-2 border-black"></div>
                                    <div class="absolute bottom-2 left-2 size-4 border-b-2 border-l-2 border-black"></div>
                                    <div class="absolute bottom-2 right-2 size-4 border-b-2 border-r-2 border-black"></div>

                                    <img src="{{ route('payment.qris', ['v' => $qrisVersion]) }}"
                                         alt="QRIS {{ $qrisMerchantName }}"
                                         width="280"
                                         height="280"
                                         fetchpriority="high"
                                         class="aspect-square max-h-64 sm:max-h-72 w-auto max-w-full mx-auto cursor-zoom-in object-contain rounded-xl"
                                         @click="enlargeModal = true"
                                         title="Klik untuk memperbesar">
                                    <p class="mt-3 font-tabular text-[11px] font-black tracking-wider text-neutral-900 uppercase">QRIS STANDAR PEMBAYARAN NASIONAL</p>
                                    <p class="mt-0.5 text-[10px] text-neutral-500">M-Banking & Dompet Digital (BCA, Mandiri, BRI, BNI, GoPay, OVO, Dana)</p>
                                </div>
                                <p class="mt-2.5 text-xs text-text-muted">Ketuk gambar QRIS untuk memperbesar.</p>

                                <div class="mt-3">
                                    <a href="{{ route('payment.qris', ['download' => 1]) }}"
                                       download="QRIS-SynemaX"
                                       class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-xl border border-border dark:border-white/20 bg-surface dark:bg-white/5 px-4 text-xs font-bold text-text dark:text-white hover:border-amber-400 transition">
                                        <svg class="size-4 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        <span>Simpan gambar QRIS</span>
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="mt-4 rounded-xl border border-border bg-surface-2 p-4 text-xs text-text-muted">
                                Gambar QRIS sedang disiapkan pengelola. Anda dapat menghubungi admin lewat WhatsApp untuk mendapatkan kode pembayaran.
                            </div>
                        @endif

                        @if ($qrisPaymentNotes)
                            <div class="mt-4 rounded-xl bg-surface-2 p-3.5 text-xs text-text-muted leading-relaxed">
                                <p class="font-bold text-text">Catatan Pengelola:</p>
                                <p class="mt-0.5">{{ $qrisPaymentNotes }}</p>
                            </div>
                        @endif

                        <div class="mt-5 rounded-2xl border border-border dark:border-white/10 bg-surface dark:bg-white/5 p-4 text-xs text-text-muted shadow-xs">
                            <h3 class="font-bold text-text dark:text-white flex items-center gap-1.5">
                                <span class="size-1.5 rounded-full bg-amber-500"></span>
                                <span>Petunjuk Bayar via QRIS</span>
                            </h3>
                            <ol class="mt-2 list-decimal space-y-1.5 pl-4 text-text-muted">
                                <li>Buka aplikasi m-Banking atau dompet digital favorit Anda.</li>
                                <li>Pilih menu <strong class="text-text font-bold">Scan QRIS</strong>.</li>
                                <li>Arahkan kamera ke gambar QR di atas atau unggah dari galeri.</li>
                                <li>Pastikan nominal bayar tepat <strong class="font-tabular font-bold text-amber-700 dark:text-amber-400">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</strong>.</li>
                                <li>Simpan bukti transfer dan unggah pada formulir di bawah.</li>
                            </ol>
                        </div>
                    </div>

                {{-- ==================== METODE 2: TRANSFER BANK ==================== --}}
                @elseif ($booking->payment_method === 'transfer')
                    <div class="mt-6 border-t border-border dark:border-white/10 pt-6">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-sm font-black text-text dark:text-white">Rekening Tujuan Transfer</h2>
                            <span class="text-xs text-text-muted">{{ count($transferAccounts) }} Rekening Tersedia</span>
                        </div>

                        <div class="mt-3.5 space-y-3">
                            @forelse ($transferAccounts as $acc)
                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border dark:border-white/10 bg-surface dark:bg-white/5 p-4 shadow-xs">
                                    <div>
                                        <span class="rounded-md bg-amber-500/15 dark:bg-amber-500/20 px-2 py-0.5 text-xs font-black text-amber-700 dark:text-amber-400 uppercase tracking-wide">
                                            {{ $acc['bank_name'] }}
                                        </span>
                                        <p class="mt-2 font-tabular text-xl sm:text-2xl font-black text-text dark:text-white tracking-wider">{{ $acc['account_number'] }}</p>
                                        <p class="mt-0.5 text-xs text-text-muted">Atas Nama: <strong class="text-text dark:text-slate-200 font-bold">{{ $acc['account_holder'] }}</strong></p>
                                    </div>
                                    <button type="button"
                                            @click="copyAccount('{{ $acc['account_number'] }}')"
                                            class="btn-gold-cinema inline-flex min-h-9 items-center rounded-lg px-3.5 text-xs font-bold shrink-0">
                                        <span x-text="copiedAccount === '{{ $acc['account_number'] }}' ? 'Tersalin!' : 'Salin Nomor'">Salin Nomor</span>
                                    </button>
                                </div>
                            @empty
                                <div class="rounded-xl border border-border bg-surface-2 p-4 text-xs text-text-muted">
                                    Rekening pembayaran sedang disiapkan. Silakan hubungi admin lewat WhatsApp.
                                </div>
                            @endforelse
                        </div>

                        @if ($transferPaymentNotes)
                            <div class="mt-4 rounded-xl bg-surface-2 p-3.5 text-xs text-text-muted leading-relaxed">
                                <p class="font-bold text-text">Catatan:</p>
                                <p class="mt-0.5">{{ $transferPaymentNotes }}</p>
                            </div>
                        @endif

                        <div class="mt-5 rounded-2xl border border-border dark:border-white/10 bg-surface dark:bg-white/5 p-4 text-xs text-text-muted shadow-xs">
                            <h3 class="font-bold text-text dark:text-white flex items-center gap-1.5">
                                <span class="size-1.5 rounded-full bg-amber-500"></span>
                                <span>Petunjuk Bayar via Transfer</span>
                            </h3>
                            <ol class="mt-2 list-decimal space-y-1.5 pl-4 text-text-muted">
                                <li>Transfer ke rekening atau nomor e-wallet di atas.</li>
                                <li>Pastikan nominal transfer tepat <strong class="font-tabular font-bold text-amber-700 dark:text-amber-400">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</strong>.</li>
                                <li>Simpan bukti tangkapan layar atau struk ATM untuk diunggah.</li>
                            </ol>
                        </div>
                    </div>
                @endif

                {{-- ==================== FORMULIR UNGGAH BUKTI BAYAR ==================== --}}
                <div class="mt-8 border-t border-border dark:border-white/10 pt-6">
                    <h3 class="text-sm font-black text-text dark:text-white">Kirim Bukti Pembayaran</h3>
                    <p class="mt-0.5 text-xs text-text-muted">
                        Unggah foto atau tangkapan layar bukti transfer (JPG, PNG, atau WebP maks. {{ round($maxPaymentProofKb / 1024) }} MB).
                    </p>

                    <form method="POST"
                          action="{{ route('booking.proof.upload', ['code' => $booking->code]) }}"
                          enctype="multipart/form-data"
                          class="mt-4 space-y-4 rounded-2xl border border-border dark:border-white/10 bg-surface dark:bg-white/5 p-5 shadow-xs">
                        @csrf
                        <input type="hidden" name="whatsapp" value="{{ $booking->whatsapp }}">

                        <div>
                            <label for="payment_proof" class="block text-xs font-bold uppercase tracking-wider text-text-muted">Pilih Berkas Bukti Transfer</label>
                            <input id="payment_proof"
                                   name="payment_proof"
                                   type="file"
                                   required
                                   accept="image/jpeg,image/png,image/webp"
                                   @change="handleFileChange($event)"
                                   class="mt-2 block w-full text-xs text-text file:mr-3 file:min-h-11 file:rounded-xl file:border file:border-amber-500/40 file:bg-amber-500/10 file:px-4 file:text-xs file:font-bold file:text-amber-700 dark:file:text-amber-400 hover:file:bg-amber-500/20 cursor-pointer">
                            <p x-show="compressStatus" class="mt-2 text-xs font-medium text-amber-700 dark:text-amber-400 flex items-center gap-1.5" x-text="compressStatus"></p>
                            <p x-show="selectedFileName && !compressStatus" class="mt-2 text-xs font-bold text-emerald-600 dark:text-emerald-400" x-text="'Berkas siap dikirim: ' + selectedFileName"></p>
                            <p x-show="fileError" class="mt-2 text-xs font-bold text-danger-text" x-text="fileError"></p>
                        </div>

                        {{-- Tombol Kirim Bukti Bayar --}}
                        <button type="submit"
                                :disabled="!hasFile || isCompressing"
                                class="btn-gold-cinema inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl px-5 text-sm font-black shadow-lg transition disabled:cursor-not-allowed disabled:opacity-40">
                            <span x-show="!isCompressing">Kirim bukti bayar</span>
                            <span x-show="isCompressing" x-cloak>Menyiapkan gambar...</span>
                        </button>
                    </form>
                </div>

                {{-- Aksi Pembatalan --}}
                <div class="mt-6 pt-5 border-t border-border dark:border-white/10 text-center max-w-[400px] mx-auto w-full">
                    <button type="button"
                            @click="cancelModal = true"
                            class="text-xs text-text-muted hover:text-red-500 dark:hover:text-red-400 hover:underline transition-colors">
                        Batalkan booking ini
                    </button>
                </div>
            </div>
        </div>

        {{-- Dialog Modal Pembatalan --}}
        <div x-show="cancelModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
             @click.self="cancelModal = false">
            <div class="w-full max-w-sm rounded-2xl border border-border dark:border-white/10 bg-surface dark:bg-[#0E131F] p-5 shadow-2xl">
                <h3 class="text-base font-black text-text dark:text-white">Batalkan booking ini?</h3>
                <p class="mt-2 text-xs leading-relaxed text-text-muted">
                    Apakah Anda yakin ingin membatalkan pemesanan ini? Tempat yang telah dipesan akan dilepaskan kembali.
                </p>
                <form method="POST" action="{{ route('booking.cancel', ['code' => $booking->code]) }}" class="mt-5">
                    @csrf
                    <input type="hidden" name="whatsapp" value="{{ $booking->whatsapp }}">
                    <div class="flex justify-end gap-2.5">
                        <button type="button" @click="cancelModal = false"
                                class="inline-flex min-h-10 items-center justify-center rounded-xl border border-border bg-surface-2 dark:bg-transparent dark:border-white/20 px-3.5 text-xs font-bold text-text hover:bg-surface transition">
                            Jangan batalkan
                        </button>
                        <button type="submit"
                                class="inline-flex min-h-10 items-center justify-center rounded-xl bg-danger px-4 text-xs font-bold text-white hover:opacity-90 transition">
                            Ya, batalkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Perbesar Gambar QRIS --}}
        @if ($booking->payment_method === 'qris' && $hasQrisImage)
            <div x-show="enlargeModal" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/85 p-4 backdrop-blur-md"
                 @click.self="enlargeModal = false">
                <div class="relative w-full max-w-lg rounded-3xl border border-white/20 bg-white p-6 shadow-2xl text-center">
                    <button type="button" @click="enlargeModal = false"
                            class="absolute right-4 top-4 flex size-9 items-center justify-center rounded-xl bg-neutral-100 text-neutral-800 hover:bg-neutral-200"
                            title="Tutup">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">QRIS Standar SynemaX</p>
                    <p class="mt-1 text-lg font-black text-neutral-900">{{ $qrisMerchantName ?: 'SynemaX Cinema' }}</p>
                    <div class="mx-auto mt-4 max-w-sm rounded-2xl border border-neutral-200 p-3">
                        <img src="{{ route('payment.qris', ['v' => $qrisVersion]) }}"
                             alt="QRIS {{ $qrisMerchantName }}"
                             class="max-h-[60vh] w-auto max-w-full mx-auto object-contain rounded-xl">
                        <p class="mt-3 font-tabular text-sm font-black text-neutral-900">
                            Total: Rp {{ number_format($booking->total_price, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
