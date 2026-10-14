@extends('layouts.admin')

@section('title', 'Daftar siapkan')

@section('content')
    <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-accent">Dapur studio</p><h1 class="mt-2 text-3xl font-black">Daftar siapkan</h1><p class="mt-1 text-sm text-text-muted">Pilih sesi untuk lihat jumlah jajan dan catatan tiap pemesan.</p></div>
    <div class="mt-6 space-y-3">
        @forelse ($sessions as $session)
            <a href="{{ route('admin.snacks.preparation.show', $session) }}" class="flex min-h-20 items-center justify-between gap-3 rounded-2xl border border-border bg-surface p-4 hover:border-accent/40">
                <div><p class="font-bold">{{ $session->startsAt()->locale('id')->translatedFormat('D, d M Y') }} · {{ substr((string) $session->start_time, 0, 5) }} WIB</p><p class="mt-1 text-xs text-text-muted">{{ $session->film?->title ?? 'Film belum dipilih' }} · {{ $session->snack_booking_count }} booking jajan</p></div>
                <span class="text-sm font-bold text-accent">Lihat →</span>
            </a>
        @empty
            <p class="rounded-xl border border-border bg-surface p-5 text-sm text-text-muted">Belum ada pesanan jajan untuk sesi mendatang.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $sessions->links() }}</div>
@endsection
