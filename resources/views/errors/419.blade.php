@extends('layouts.public')

@section('title', 'Sesi kedaluwarsa - SynemaX')

@section('content')
    <main class="mx-auto max-w-lg px-4 py-16 text-center sm:py-24">
        <p class="font-tabular text-5xl sm:text-6xl font-black text-text-muted">419</p>
        <h1 class="mt-4 text-2xl sm:text-3xl font-extrabold text-text">Sesi halaman sudah kedaluwarsa</h1>
        <p class="mt-2.5 text-xs sm:text-sm text-text-muted leading-relaxed max-w-md mx-auto">
            Halaman ini sudah terbuka terlalu lama demi keamanan transaksi Anda. Silakan muat ulang halaman lalu kirimkan kembali data yang diperlukan.
        </p>
        <div class="mt-6 flex flex-wrap justify-center gap-2.5">
            <button type="button" onclick="window.location.reload()" class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] bg-primary px-5 text-xs font-bold text-white shadow-xs transition hover:opacity-95">
                Muat ulang halaman
            </button>
            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] border-[1.5px] border-border-input bg-surface px-4 text-xs font-bold text-text hover:border-primary">
                Kembali ke beranda
            </a>
        </div>
    </main>
@endsection
