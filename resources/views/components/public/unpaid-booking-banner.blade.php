@php
    $unpaidBookings = \App\Support\DeviceBookingTracker::getUnpaidBookings();
    $unpaidCount = $unpaidBookings->count();
    $singleBooking = $unpaidCount === 1 ? $unpaidBookings->first() : null;
@endphp

@if ($unpaidCount > 0 && ! request()->routeIs('booking.payment'))
    @if ($singleBooking)
        <aside
            id="unpaid-booking-banner"
            aria-label="Pemberitahuan booking belum dibayar"
            x-data="{
                expiresAt: @js($singleBooking->payment_expires_at?->toIso8601String()),
                timeLeft: 0,
                isExpired: false,
                cancelModal: false,
                isMobileCollapsed: false,
                init() {
                    if (!this.expiresAt) return;
                    const target = new Date(this.expiresAt).getTime();
                    const update = () => {
                        const diff = Math.max(0, Math.floor((target - Date.now()) / 1000));
                        this.timeLeft = diff;
                        if (diff <= 0) {
                            this.isExpired = true;
                        }
                    };
                    update();
                    setInterval(update, 1000);
                },
                formatTime(sec) {
                    const m = Math.floor(sec / 60);
                    const s = sec % 60;
                    return `${m}.${s < 10 ? '0' : ''}${s}`;
                }
            }"
            x-show="!isExpired"
            x-cloak
            class="relative z-30 border-b border-amber-500/30 bg-amber-500/10 dark:bg-amber-500/15 text-amber-950 dark:text-amber-200 transition-colors"
        >
            <div class="mx-auto max-w-7xl px-3 py-2 sm:px-6 sm:py-2.5">
                {{-- Desktop View (>= 768px) --}}
                <div class="hidden md:flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2.5 text-xs font-semibold">
                        <span class="flex size-2 rounded-full bg-amber-500 animate-pulse" aria-hidden="true"></span>
                        <p>
                            Booking <strong class="font-tabular font-black">{{ $singleBooking->code }}</strong> belum dibayar.
                            Sisa waktu: <span class="font-tabular font-black text-amber-800 dark:text-amber-300" x-text="formatTime(timeLeft)">--.--</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a
                            href="{{ route('booking.payment', ['code' => $singleBooking->code]) }}"
                            class="inline-flex min-h-9 items-center justify-center rounded-lg bg-amber-500 px-3.5 text-xs font-black text-slate-950 hover:bg-amber-400 transition shadow-xs"
                        >
                            Lanjutkan pembayaran
                        </a>
                        <button
                            type="button"
                            @click="cancelModal = true"
                            class="inline-flex min-h-9 items-center justify-center rounded-lg border border-amber-600/30 dark:border-white/15 bg-surface/50 dark:bg-black/30 px-3 text-xs font-bold text-slate-700 dark:text-slate-300 hover:text-danger hover:border-danger transition"
                        >
                            Batalkan
                        </button>
                    </div>
                </div>

                {{-- Mobile View (< 768px): Ringkas satu baris 44px --}}
                <div class="md:hidden flex items-center justify-between gap-2 min-h-[44px]">
                    <div class="flex items-center gap-1.5 text-xs font-bold truncate">
                        <span class="size-2 rounded-full bg-amber-500 shrink-0" aria-hidden="true"></span>
                        <span class="font-tabular truncate">{{ $singleBooking->code }}</span>
                        <span class="text-text-muted">&bull;</span>
                        <span class="font-tabular font-black text-amber-800 dark:text-amber-300 shrink-0" x-text="formatTime(timeLeft)">--.--</span>
                    </div>

                    <div class="flex items-center gap-1.5 shrink-0">
                        <a
                            href="{{ route('booking.payment', ['code' => $singleBooking->code]) }}"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-amber-500 px-3 text-xs font-black text-slate-950 hover:bg-amber-400 shadow-xs"
                        >
                            Bayar
                        </a>
                        <button
                            type="button"
                            @click="cancelModal = true"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-amber-600/30 dark:border-white/15 bg-surface/40 dark:bg-black/30 px-2.5 text-xs font-bold text-slate-700 dark:text-slate-300 hover:text-danger"
                            title="Batalkan booking"
                        >
                            Batal
                        </button>
                    </div>
                </div>
            </div>

            {{-- In-App Confirmation Modal: Batal Booking --}}
            <div
                x-show="cancelModal"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4 backdrop-blur-xs"
                role="dialog"
                aria-modal="true"
                aria-label="Konfirmasi Pembatalan Booking"
                @keydown.escape.window="cancelModal = false"
            >
                <div
                    @click.outside="cancelModal = false"
                    class="w-full max-w-sm rounded-2xl border border-border bg-surface dark:bg-[#0E131F] p-6 shadow-2xl"
                >
                    <h3 class="text-base font-black text-text dark:text-white">Batalkan booking ini?</h3>
                    <p class="mt-2 text-xs leading-relaxed text-text-muted">
                        Apakah kamu yakin ingin membatalkan booking <strong>{{ $singleBooking->code }}</strong>? Tempat yang telah kamu pilih akan segera dilepaskan kembali agar bisa dipesan penonton lain.
                    </p>

                    <form method="POST" action="{{ route('booking.cancel', ['code' => $singleBooking->code]) }}" class="mt-5">
                        @csrf
                        <div class="flex justify-end gap-2.5">
                            <button
                                type="button"
                                @click="cancelModal = false"
                                class="inline-flex min-h-10 items-center justify-center rounded-xl border border-border px-3.5 text-xs font-bold text-text hover:bg-surface-2 transition"
                            >
                                Jangan batalkan
                            </button>
                            <button
                                type="submit"
                                class="inline-flex min-h-10 items-center justify-center rounded-xl bg-danger px-4 text-xs font-bold text-white hover:opacity-90 transition"
                            >
                                Ya, batalkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </aside>
    @else
        {{-- Banner Jika Lebih Dari 1 Booking Belum Dibayar --}}
        <aside
            aria-label="Pemberitahuan beberapa booking belum dibayar"
            class="relative z-30 border-b border-amber-500/30 bg-amber-500/10 dark:bg-amber-500/15 text-amber-950 dark:text-amber-200"
        >
            <div class="mx-auto max-w-7xl px-4 py-2.5 sm:px-6 flex items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-xs font-bold">
                    <span class="size-2 rounded-full bg-amber-500" aria-hidden="true"></span>
                    <span>{{ $unpaidCount }} booking belum dibayar.</span>
                </div>
                <a
                    href="{{ route('booking.ticket.index', ['tab' => 'belum_bayar']) }}"
                    class="inline-flex min-h-9 items-center justify-center rounded-lg bg-amber-500 px-3.5 text-xs font-black text-slate-950 hover:bg-amber-400 transition"
                >
                    Lihat di Tiket
                </a>
            </div>
        </aside>
    @endif
@endif
