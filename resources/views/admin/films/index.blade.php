@extends('layouts.admin')

@section('title', 'Katalog Film')

@section('content')
    <div>
        <x-admin.page-header
            eyebrow="Katalog"
            title="Katalog Film"
            subtitle="Kelola film bioskop mini, info rating usia, durasi, dan poster."
        >
            <a href="{{ route('admin.films.create') }}" class="inline-flex min-h-[42px] items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition-colors">
                <span class="text-base leading-none">+</span>
                <span>Tambah Film Baru</span>
            </a>
        </x-admin.page-header>

        @if ($films->isEmpty())
            <div class="mt-6 rounded-2xl border border-border bg-surface shadow-token">
                <x-admin.empty-state
                    title="Belum ada film di katalog"
                    description="Tambahkan film baru untuk mulai menjadwalkan sesi tontonan."
                    actionUrl="{{ route('admin.films.create') }}"
                    actionLabel="Tambah Film Baru"
                />
            </div>
        @else
            {{-- DESKTOP TABLE VIEW (1024px and up) --}}
            <div class="mt-6 hidden lg:block admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="col-title-flex">Poster & Judul Film</th>
                            <th class="col-nowrap">Genre & Tahun</th>
                            <th class="col-nowrap">Durasi</th>
                            <th class="col-nowrap">Klasifikasi Usia</th>
                            <th class="col-nowrap">Status</th>
                            <th class="col-actions-sticky">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($films as $film)
                            <tr>
                                <td class="col-title-flex">
                                    <div class="flex items-center gap-3">
                                        @if ($film->poster_path)
                                            <img src="{{ $film->posterUrl() }}" alt="{{ $film->title }}" class="h-16 w-11 shrink-0 rounded-lg object-cover border border-border">
                                        @else
                                            <div class="flex h-16 w-11 shrink-0 items-center justify-center rounded-lg border border-border bg-surface-2 text-[9px] font-semibold text-text-muted uppercase text-center p-1 leading-tight">
                                                Tanpa poster
                                            </div>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <a href="{{ route('admin.films.edit', $film) }}" class="font-bold text-text hover:text-amber-500 line-clamp-2 leading-snug" title="{{ $film->title }}">
                                                {{ $film->title }}
                                            </a>
                                            <p class="mt-0.5 line-clamp-1 text-xs text-text-muted">{{ $film->synopsis }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="col-nowrap">
                                    <p class="font-semibold text-text">{{ $film->genre }}</p>
                                    <p class="text-xs text-text-muted">{{ $film->release_year ?: ($film->release_date ? $film->release_date->format('Y') : '-') }}</p>
                                </td>
                                <td class="col-nowrap text-sm font-semibold text-text">
                                    {{ $film->duration_minutes }} menit
                                </td>
                                <td class="col-nowrap">
                                    <span class="inline-flex items-center rounded-md bg-slate-200 dark:bg-white/10 px-2 py-0.5 text-xs font-bold text-text">
                                        {{ $film->age_rating }}
                                    </span>
                                </td>
                                <td class="col-nowrap">
                                    <form method="POST" action="{{ route('admin.films.status', $film) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="submit"
                                            title="Klik untuk ganti status aktif"
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold transition {{ $film->is_active ? 'bg-success/15 text-success border border-success/30' : 'bg-slate-200 dark:bg-white/10 text-text-muted' }}"
                                        >
                                            <span class="size-1.5 rounded-full {{ $film->is_active ? 'bg-success' : 'bg-slate-400' }}"></span>
                                            <span>{{ $film->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                        </button>
                                    </form>
                                </td>
                                <td class="col-actions-sticky">
                                    <x-admin.action-menu
                                        editUrl="{{ route('admin.films.edit', $film) }}"
                                        viewUrl="{{ $film->slug ? url('/film/' . $film->slug) : null }}"
                                        duplicateUrl="{{ route('admin.films.duplicate', $film) }}"
                                        deleteUrl="{{ route('admin.films.destroy', $film) }}"
                                        deleteMessage="Nonaktifkan film '{{ addslashes($film->title) }}' dari katalog publik?"
                                        itemName="film"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- MOBILE STACKED CARDS VIEW (< 1024px) --}}
            <div class="mt-6 grid gap-4 lg:hidden">
                @foreach ($films as $film)
                    <div class="rounded-2xl border border-border bg-surface p-4 shadow-token space-y-3">
                        <div class="flex items-start gap-3">
                            @if ($film->poster_path)
                                <img src="{{ $film->posterUrl() }}" alt="{{ $film->title }}" class="h-20 w-14 shrink-0 rounded-xl object-cover border border-border">
                            @else
                                <div class="flex h-20 w-14 shrink-0 items-center justify-center rounded-xl border border-border bg-surface-2 text-[9px] font-semibold text-text-muted uppercase text-center p-1 leading-tight">
                                    Tanpa poster
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('admin.films.edit', $film) }}" class="font-bold text-text hover:text-amber-500 leading-snug">
                                    {{ $film->title }}
                                </a>
                                <p class="text-xs text-text-muted mt-0.5">{{ $film->genre }} · {{ $film->duration_minutes }} mnt</p>
                                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center rounded-md bg-slate-200 dark:bg-white/10 px-2 py-0.5 text-[11px] font-bold text-text">
                                        {{ $film->age_rating }}
                                    </span>
                                    @if ($film->release_year)
                                        <span class="inline-flex items-center rounded-md bg-surface-2 px-2 py-0.5 text-[11px] font-semibold text-text-muted">
                                            {{ $film->release_year }}
                                        </span>
                                    @endif
                                    <form method="POST" action="{{ route('admin.films.status', $film) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-bold {{ $film->is_active ? 'bg-success/15 text-success' : 'bg-slate-200 dark:bg-white/10 text-text-muted' }}">
                                            {{ $film->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t border-border pt-3 text-xs">
                            <span class="text-text-muted truncate max-w-[150px]">{{ $film->synopsis }}</span>
                            <x-admin.action-menu
                                editUrl="{{ route('admin.films.edit', $film) }}"
                                viewUrl="{{ $film->slug ? url('/film/' . $film->slug) : null }}"
                                duplicateUrl="{{ route('admin.films.duplicate', $film) }}"
                                deleteUrl="{{ route('admin.films.destroy', $film) }}"
                                deleteMessage="Nonaktifkan film '{{ addslashes($film->title) }}' dari katalog publik?"
                                itemName="film"
                            />
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $films->links() }}
            </div>
        @endif
    </div>
@endsection
