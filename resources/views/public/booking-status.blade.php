@extends('layouts.public')

@section('title', 'Cek Booking · SynemaX')
@section('meta')
    <meta name="description" content="Cek status tiket dan pembayaran SynemaX Home Cinema dengan kode booking dan nomor WhatsApp.">
@endsection

@section('content')
    <section class="mx-auto max-w-lg px-4 py-8 sm:px-6 sm:py-12"
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

        <div class="text-center">
            <span class="text-xs font-bold uppercase tracking-[0.2em] text-accent">Pencarian Tiket</span>
            <h1 class="mt-2 text-3xl font-black text-text sm:text-4xl">Cek Booking</h1>
            <p class="mt-2 text-sm text-text-muted">Masukkan kode booking dan nomor WhatsApp yang digunakan saat memesan.</p>
        </div>

        @if (session('status'))
            <div role="status" class="mt-6 rounded-2xl border border-success/30 bg-success/10 p-4 text-xs font-semibold text-success">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="mt-6 rounded-2xl border border-danger/30 bg-danger/10 p-4 text-xs font-semibold text-danger">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('booking.lookup') }}" class="mt-6 space-y-4 rounded-3xl border border-border bg-surface p-6 shadow-token sm:p-7">
            @csrf

            <div>
                <label for="code" class="block text-xs font-bold uppercase tracking-wider text-text">Kode Booking</label>
                <input id="code"
                       name="code"
                       x-ref="codeInput"
                       x-model="code"
                       required
                       maxlength="20"
                       autocomplete="off"
                       placeholder="Contoh: HC-261014-A7K2"
                       class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-4 font-mono text-base font-bold uppercase tracking-wider text-text focus:border-accent focus:ring-accent">
            </div>

            <div>
                <label for="whatsapp" class="block text-xs font-bold uppercase tracking-wider text-text">Nomor WhatsApp</label>
                <input id="whatsapp"
                       name="whatsapp"
                       value="{{ old('whatsapp') }}"
                       required
                       type="tel"
                       inputmode="numeric"
                       autocomplete="tel"
                       placeholder="Contoh: 081234567890"
                       class="mt-2 min-h-12 w-full rounded-xl border border-border bg-bg px-4 text-sm text-text focus:border-accent focus:ring-accent">
                <p class="mt-1.5 text-[11px] text-text-muted">Hanya pemesan terverifikasi yang dapat mengakses rincian tiket.</p>
            </div>

            <button type="submit"
                    class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-accent px-5 text-sm font-bold text-accent-contrast shadow-sm transition hover:opacity-90">
                Buka Tiket / Pembayaran
            </button>
        </form>

        {{-- Riwayat Booking di Perangkat Ini --}}
        <div x-show="savedCodes.length > 0" x-cloak class="mt-6 rounded-2xl border border-border bg-surface-2/60 p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-text-muted">Booking Terakhir di Perangkat Ini:</p>
            <div class="mt-2.5 flex flex-wrap gap-2">
                <template x-for="c in savedCodes" :key="c">
                    <button type="button"
                            @click="selectCode(c)"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-surface px-3 py-1.5 font-mono text-xs font-bold text-accent hover:border-accent hover:bg-accent/5">
                        <span x-text="c"></span>
                    </button>
                </template>
            </div>
            <p class="mt-2 text-[11px] text-text-muted">Klik salah satu kode di atas untuk mengisi otomatis.</p>
        </div>

        <div class="mt-8 text-center text-xs text-text-muted">
            <p>Butuh bantuan? <a href="{{ route('home') }}" class="font-bold text-accent hover:underline">Kembali ke Beranda</a></p>
        </div>
    </section>
@endsection
