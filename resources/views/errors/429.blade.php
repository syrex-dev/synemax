@extends('layouts.public')

@section('title', 'Terlalu Banyak Permintaan: SynemaX')

@section('content')
    <div class="mx-auto max-w-lg px-4 py-16 text-center sm:py-24">
        <p class="font-tabular text-5xl sm:text-6xl font-black text-text-muted">429</p>
        <h1 class="mt-4 text-2xl sm:text-3xl font-extrabold text-text">Terlalu banyak permintaan</h1>
        <p class="mt-2.5 text-xs sm:text-sm text-text-muted leading-relaxed max-w-md mx-auto">
            Sistem mendeteksi terlalu banyak permintaan dari perangkat Anda dalam waktu singkat. Mohon tunggu beberapa detik sebelum mencoba kembali.
        </p>
        <div class="mt-6 flex flex-wrap justify-center gap-2.5">
            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] bg-primary px-5 text-xs font-bold text-white shadow-xs transition hover:opacity-95">
                Ke beranda
            </a>
        </div>
    </div>
@endsection
