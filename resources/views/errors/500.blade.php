@extends('layouts.public')

@section('title', 'Ada gangguan')

@section('content')
    <main class="mx-auto max-w-xl px-4 py-20 text-center sm:py-28">
        <p class="font-mono text-6xl font-black text-danger">500</p>
        <h1 class="mt-4 text-3xl font-black text-text">Ada sedikit gangguan</h1>
        <p class="mt-3 text-base text-text-muted">Coba lagi sebentar lagi atau hubungi admin studio lewat WhatsApp.</p>
        <a href="{{ route('home') }}" class="mt-7 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-accent px-5 font-bold text-accent-contrast sm:w-auto">Kembali ke beranda</a>
    </main>
@endsection
