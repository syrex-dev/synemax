@extends('layouts.public')

@section('title', 'Cek booking — SynemaX')
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

        <header class="mb-6">
            <span class="text-xs font-bold text-text-muted">Pencarian tiket</span>
            <h1 class="mt-1 text-2xl font-black text-text sm:text-3xl">Cek booking</h1>
            <p class="mt-2 text-xs sm:text-sm text-text-muted leading-relaxed">
                Masukkan kode booking dan nomor WhatsApp yang digunakan saat memesan untuk membuka tiket digital atau menyelesaikan pembayaran.
            </p>
        </header>

        @if (session('status'))
            <div role="status" class="mb-5 rounded-[var(--radius-control)] border border-success-border bg-success-surface p-3.5 text-xs font-bold text-success-text">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="mb-5 rounded-[var(--radius-control)] border border-danger-border bg-danger-surface p-4 text-xs font-semibold text-danger-text space-y-1.5">
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

        <form method="POST" action="{{ route('booking.lookup') }}" class="space-y-4 rounded-[var(--radius-control)] border border-border bg-surface p-5 shadow-xs sm:p-6">
            @csrf

            <div>
                <label for="code" class="block text-xs font-bold text-text">Kode booking</label>
                <input id="code"
                       name="code"
                       x-ref="codeInput"
                       x-model="code"
                       required
                       maxlength="20"
                       autocomplete="off"
                       aria-describedby="code-help @if ($errors->has('code')) code-error @endif"
                       placeholder="Contoh: HC-261014-A7K2"
                       class="mt-1.5 min-h-11 w-full rounded-[var(--radius-control)] border border-border-input bg-bg px-3.5 font-tabular text-sm font-bold text-text focus:border-primary focus:outline-none">
                <p id="code-help" class="mt-1 text-[11px] text-text-muted">Kode unik yang tertera saat pemesanan dibuat.</p>
                @if ($errors->has('code'))
                    <p id="code-error" class="mt-1 text-xs font-semibold text-danger-text">{{ $errors->first('code') }}</p>
                @endif
            </div>

            <div>
                <label for="whatsapp" class="block text-xs font-bold text-text">Nomor WhatsApp</label>
                <input id="whatsapp"
                       name="whatsapp"
                       value="{{ old('whatsapp') }}"
                       required
                       type="tel"
                       inputmode="numeric"
                       autocomplete="tel"
                       aria-describedby="whatsapp-help @if ($errors->has('whatsapp')) whatsapp-error @endif"
                       placeholder="Contoh: 081234567890"
                       class="mt-1.5 min-h-11 w-full rounded-[var(--radius-control)] border border-border-input bg-bg px-3.5 text-sm text-text focus:border-primary focus:outline-none">
                <p id="whatsapp-help" class="mt-1 text-[11px] text-text-muted">Nomor WhatsApp aktif pemesan untuk verifikasi keamanan.</p>
                @if ($errors->has('whatsapp'))
                    <p id="whatsapp-error" class="mt-1 text-xs font-semibold text-danger-text">{{ $errors->first('whatsapp') }}</p>
                @endif
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="inline-flex min-h-11 w-full items-center justify-center rounded-[var(--radius-control)] bg-primary px-5 text-xs font-bold text-white shadow-xs transition hover:opacity-95">
                    Cek booking
                </button>
            </div>
        </form>

        {{-- Riwayat Booking di Perangkat Ini (Booking Saya) --}}
        <section x-show="savedCodes.length > 0" x-cloak class="mt-6 rounded-[var(--radius-control)] border border-border bg-surface-2 p-4 sm:p-5">
            <h2 class="text-xs font-bold text-text-muted">Booking saya di perangkat ini</h2>
            <p class="mt-1 text-xs text-text-muted">Sentuh salah satu kode untuk mengisi form di atas:</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <template x-for="c in savedCodes" :key="c">
                    <button type="button"
                            @click="selectCode(c)"
                            class="inline-flex min-h-9 items-center gap-1.5 rounded-[var(--radius-control)] border border-border-input bg-surface px-3 py-1 font-tabular text-xs font-bold text-text hover:border-primary hover:text-primary">
                        <span x-text="c"></span>
                    </button>
                </template>
            </div>
        </section>

        <footer class="mt-8 text-center text-xs text-text-muted">
            <p>Ingin melihat sesi film lain? <a href="{{ route('home') }}" class="font-bold text-link hover:underline">Kembali ke beranda</a></p>
        </footer>
    </section>
@endsection
