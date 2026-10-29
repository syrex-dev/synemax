@extends('layouts.public')

@section('title', 'Cek booking: SynemaX')
@section('meta')
    <meta name="description" content="Cek status tiket dan pembayaran SynemaX dengan kode booking dan nomor WhatsApp.">
@endsection

@section('content')
    <section class="mx-auto max-w-xl px-4 py-6 sm:px-6 sm:py-10"
             x-data="{
                 code: @js(old('code', request('code', ''))),
                 savedCodes: [],
                 init() {
                     try {
                         const oldKey = JSON.parse(localStorage.getItem('synemax:booking-codes') || '[]');
                         const newKey = JSON.parse(localStorage.getItem('synema_booking_history') || '[]');
                         const merged = [...new Set([...(Array.isArray(newKey) ? newKey : []), ...(Array.isArray(oldKey) ? oldKey : [])])];
                         this.savedCodes = merged.filter(c => typeof c === 'string' && c.length > 0).slice(0, 8);
                     } catch (e) {
                         this.savedCodes = [];
                     }
                 },
                 selectCode(c) {
                     this.code = c;
                     this.$refs.codeInput.focus();
                 }
             }">

        {{-- Header Halaman ala XXI Ticket Finder Booth --}}
        <header class="mb-6 text-center sm:text-left">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-400/40 bg-amber-500/15 px-3 py-1 text-xs font-black uppercase tracking-wider text-amber-300">
                <svg class="size-3.5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                </svg>
                Pencarian tiket
            </span>
            <h1 class="mt-2 text-2xl font-black text-text sm:text-4xl tracking-tight">Cek booking</h1>
            <p class="mt-2 text-xs sm:text-sm text-text-muted leading-relaxed">
                Masukkan kode booking dan nomor WhatsApp yang digunakan saat memesan untuk membuka tiket digital atau menyelesaikan pembayaran.
            </p>
        </header>

        {{-- Flash Messages --}}
        @if (session('status'))
            <div role="status" class="mb-5 rounded-2xl border border-emerald-500/40 bg-emerald-500/10 p-4 text-xs font-bold text-emerald-400 shadow-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="mb-5 rounded-2xl border border-danger/40 bg-danger-surface p-4 text-xs font-semibold text-danger-text space-y-1.5 shadow-sm">
                @foreach ($errors->all() as $error)
                    <p class="font-bold">{{ $error }}</p>
                @endforeach
                @if ($errors->has('code'))
                    <p class="font-normal opacity-90 leading-relaxed pt-1">
                        Pastikan huruf kode booking sesuai (contoh: HC-261014-A7K2) dan nomor WhatsApp sama dengan yang dimasukkan saat pemesanan. Bila Anda pernah memesan di perangkat ini, pilih kode dari daftar riwayat di bawah.
                    </p>
                @endif
            </div>
        @endif

        {{-- Form Pencarian Tiket Mewah --}}
        <form method="POST" action="{{ route('booking.lookup') }}" class="space-y-5 rounded-3xl border border-border dark:border-white/10 bg-surface dark:bg-gradient-to-b dark:from-surface dark:via-[#0C111D] dark:to-[#07090E] p-6 sm:p-8 shadow-2xl">
            @csrf

            <div>
                <label for="code" class="block text-xs font-black uppercase tracking-wider text-amber-500">
                    Kode booking
                </label>
                <div class="relative mt-2">
                    <input id="code"
                           name="code"
                           x-ref="codeInput"
                           x-model="code"
                           required
                           maxlength="20"
                           autocomplete="off"
                           aria-describedby="code-help @if ($errors->has('code')) code-error @endif"
                           placeholder="Contoh: HC-261014-A7K2"
                           class="min-h-12 w-full rounded-xl border border-border-input dark:border-white/15 bg-surface-2 dark:bg-black/40 px-4 font-tabular text-sm font-black uppercase tracking-wider text-text dark:text-white placeholder:normal-case placeholder:font-normal placeholder:tracking-normal placeholder:text-text-muted focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400">
                </div>
                <p id="code-help" class="mt-1.5 text-[11px] text-text-muted">Kode unik yang tertera saat pemesanan dibuat.</p>
                @if ($errors->has('code'))
                    <p id="code-error" class="mt-1 text-xs font-semibold text-danger-text">{{ $errors->first('code') }}</p>
                @endif
            </div>

            <div>
                <label for="whatsapp" class="block text-xs font-black uppercase tracking-wider text-amber-500">
                    Nomor WhatsApp
                </label>
                <div class="relative mt-2">
                    <input id="whatsapp"
                           name="whatsapp"
                           value="{{ old('whatsapp') }}"
                           required
                           type="tel"
                           inputmode="numeric"
                           autocomplete="tel"
                           aria-describedby="whatsapp-help @if ($errors->has('whatsapp')) whatsapp-error @endif"
                           placeholder="Contoh: 081234567890"
                           class="min-h-12 w-full rounded-xl border border-border-input dark:border-white/15 bg-surface-2 dark:bg-black/40 px-4 font-tabular text-sm font-bold text-text dark:text-white placeholder:text-text-muted focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400">
                </div>
                <p id="whatsapp-help" class="mt-1.5 text-[11px] text-text-muted">Nomor WhatsApp aktif pemesan untuk verifikasi keamanan.</p>
                @if ($errors->has('whatsapp'))
                    <p id="whatsapp-error" class="mt-1 text-xs font-semibold text-danger-text">{{ $errors->first('whatsapp') }}</p>
                @endif
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="btn-gold-cinema inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl px-5 text-sm font-black shadow-lg">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span>Cek booking</span>
                </button>
            </div>
        </form>

        {{-- Riwayat Booking di Perangkat Ini (Booking Saya) --}}
        <section x-show="savedCodes.length > 0" x-cloak class="mt-6 rounded-3xl border border-border dark:border-white/10 bg-surface-2/60 dark:bg-surface/60 p-5 sm:p-6 shadow-md backdrop-blur-sm">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-amber-400 animate-pulse"></span>
                <h2 class="text-xs font-black uppercase tracking-wider text-amber-500 dark:text-amber-400">Booking saya di perangkat ini</h2>
            </div>
            <p class="mt-1 text-xs text-text-muted">Sentuh salah satu kode untuk mengisi form di atas:</p>
            <div class="mt-3.5 flex flex-wrap gap-2">
                <template x-for="c in savedCodes" :key="c">
                    <button type="button"
                            @click="selectCode(c)"
                            class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-border dark:border-white/10 bg-surface dark:bg-black/40 px-3.5 py-1.5 font-tabular text-xs font-black tracking-wider text-amber-600 dark:text-amber-300 hover:border-amber-400 hover:bg-amber-500/10 transition shadow-sm">
                        <svg class="size-3 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                        <span x-text="c"></span>
                    </button>
                </template>
            </div>
        </section>

        <div class="mt-8 text-center text-xs text-text-muted">
            <p>Ingin melihat sesi film lain? <a href="{{ route('home') }}" class="font-bold text-accent-link hover:underline">Kembali ke beranda</a></p>
        </div>
    </section>
@endsection
