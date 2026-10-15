@extends('layouts.public')

@section('title', 'Gangguan server: SynemaX')

@section('content')
    <main class="mx-auto max-w-lg px-4 py-16 text-center sm:py-24">
        <p class="font-tabular text-5xl sm:text-6xl font-black text-text-muted">500</p>
        <h1 class="mt-4 text-2xl sm:text-3xl font-extrabold text-text">Ada gangguan teknis</h1>
        <p class="mt-2.5 text-xs sm:text-sm text-text-muted leading-relaxed max-w-md mx-auto">
            Server kami sedang mengalami kendala sesaat saat memproses permintaan ini. Silakan coba kembali beberapa saat lagi atau kembali ke beranda.
        </p>
        <div class="mt-6 flex flex-wrap justify-center gap-2.5">
            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] bg-primary px-5 text-xs font-bold text-white shadow-xs transition hover:opacity-95">
                Kembali ke beranda
            </a>
            <a href="{{ route('home') }}#jadwal" class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] border-[1.5px] border-border-input bg-surface px-4 text-xs font-bold text-text hover:border-primary">
                Lihat jadwal bioskop
            </a>
        </div>
    </main>
@endsection
