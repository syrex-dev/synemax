@extends('layouts.admin')

@section('title', 'Kelola film')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-accent">Katalog</p><h1 class="mt-2 text-3xl font-black">Kelola film</h1></div>
        <a href="{{ route('admin.films.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-accent px-4 text-sm font-bold text-accent-contrast hover:opacity-90">Tambah film</a>
    </div>
    <div class="mt-6 space-y-3">
        @forelse ($films as $film)
            <article class="flex flex-col gap-4 rounded-2xl border border-border bg-surface p-4 sm:flex-row sm:items-center">
                @if ($film->poster_path)
                    <img src="{{ Storage::url($film->poster_path) }}" alt="" loading="lazy" decoding="async" class="h-24 w-16 rounded-lg object-cover">
                @else
                    <div class="grid h-24 w-16 place-items-center rounded-lg bg-surface-2 text-[10px] font-bold text-accent">POSTER</div>
                @endif
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2"><h2 class="font-bold">{{ $film->title }}</h2><span class="rounded-full bg-surface-2 px-2 py-1 text-[10px]">{{ $film->age_rating }}</span><span class="rounded-full px-2 py-1 text-[10px] {{ $film->is_active ? 'bg-success/10 text-success' : 'bg-surface-2 text-text-muted' }}">{{ $film->is_active ? 'Aktif' : 'Nonaktif' }}</span></div>
                    <p class="mt-1 text-xs text-text-muted">{{ $film->genre }} · {{ $film->duration_minutes }} menit</p>
                    <p class="mt-2 line-clamp-2 text-xs leading-5 text-text-muted">{{ $film->synopsis }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.films.edit', $film) }}" class="rounded-lg border border-border px-3 py-2 text-xs font-semibold hover:bg-surface-2">Ubah</a>
                    @if ($film->is_active)
                        <form method="POST" action="{{ route('admin.films.destroy', $film) }}" onsubmit="return confirm('Nonaktifkan film ini dari katalog?')">
                            @csrf @method('DELETE')
                            <button class="rounded-lg border border-danger/30 px-3 py-2 text-xs font-semibold text-danger hover:bg-danger/10">Nonaktifkan</button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <p class="rounded-xl border border-border bg-surface p-5 text-sm text-text-muted">Belum ada film di katalog.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $films->links() }}</div>
@endsection
