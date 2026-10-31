@extends('layouts.admin')

@section('title', 'Katalog Film')

@section('content')
    <div
        x-data="{
            selected: [],
            allChecked: false,
            selectAllFiltered: false,
            ratingModalOpen: false,
            genreModalOpen: false,
            showUndoToast: {{ $undoBatchId ? 'true' : 'false' }},
            undoCountdown: 8,
            undoTimer: null,
            init() {
                if (this.showUndoToast) {
                    this.undoTimer = setInterval(() => {
                        if (this.undoCountdown > 1) {
                            this.undoCountdown--;
                        } else {
                            this.showUndoToast = false;
                            clearInterval(this.undoTimer);
                        }
                    }, 1000);
                }
            },
            toggleAll() {
                if (this.allChecked) {
                    this.selected = [];
                    this.allChecked = false;
                    this.selectAllFiltered = false;
                } else {
                    this.selected = {{ Js::from($films->pluck('id')->values()->all()) }};
                    this.allChecked = true;
                }
            },
            updateCheckAll() {
                this.allChecked = this.selected.length > 0 && this.selected.length === {{ $films->count() }};
                if (!this.allChecked) {
                    this.selectAllFiltered = false;
                }
            }
        }"
    >
        {{-- Toast Urungkan Batch Impor (8 Detik) --}}
        @if ($undoBatchId)
            <div
                x-show="showUndoToast"
                x-cloak
                x-transition
                class="fixed top-6 right-6 z-50 rounded-2xl border border-amber-500/50 bg-surface/95 backdrop-blur-md p-4 shadow-2xl flex items-center gap-3 text-xs"
            >
                <div class="flex items-center gap-2">
                    <span class="inline-flex size-2 rounded-full bg-amber-500 animate-ping"></span>
                    <span class="font-bold text-text">Batch impor baru saja diterapkan.</span>
                    <span class="text-text-muted font-mono" x-text="'(' + undoCountdown + 's)'"></span>
                </div>
                <form
                    action="{{ route('admin.films.import.rollback', $undoBatchId) }}"
                    method="POST"
                    data-confirm="Batalkan impor ini? Seluruh {{ $undoBatchCount }} film pada batch ini akan dihapus lunak dari katalog."
                    data-confirm-title="Batalkan Impor Ini"
                    data-confirm-action="Hapus Seluruh Film Batch Ini"
                    data-confirm-is-danger="true"
                    novalidate
                    class="inline"
                >
                    @csrf
                    <button type="submit" class="rounded-lg bg-amber-500 px-3 py-1 font-bold text-slate-950 hover:bg-amber-400 transition">
                        Batalkan Impor Ini ({{ $undoBatchCount }} Film)
                    </button>
                </form>
                <button type="button" @click="showUndoToast = false" class="text-text-muted hover:text-text p-1 inline-flex items-center" aria-label="Tutup notifikasi">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        <x-admin.page-header
            eyebrow="Katalog"
            title="Katalog Film"
            subtitle="Kelola film bioskop mini, info rating usia, durasi, poster, dan impor massal."
        >
            <div class="flex items-center flex-wrap gap-2">
                <a
                    href="{{ route('admin.films.export') }}"
                    class="inline-flex min-h-[42px] items-center gap-1.5 rounded-xl border border-slate-300 dark:border-white/20 bg-surface px-3.5 py-2 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors shadow-xs"
                    title="Unduh seluruh data film dalam format CSV untuk diedit di spreadsheet"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Ekspor CSV</span>
                </a>
                <a
                    href="{{ route('admin.films.posters.index') }}"
                    class="inline-flex min-h-[42px] items-center gap-1.5 rounded-xl border border-slate-300 dark:border-white/20 bg-surface px-3.5 py-2 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors shadow-xs"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Poster Massal</span>
                </a>
                <a
                    href="{{ route('admin.films.import.create') }}"
                    class="inline-flex min-h-[42px] items-center gap-1.5 rounded-xl border border-slate-300 dark:border-white/20 bg-surface px-3.5 py-2 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors shadow-xs"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12" />
                    </svg>
                    <span>Impor Film CSV</span>
                </a>
                <a href="{{ route('admin.films.create') }}" class="inline-flex min-h-[42px] items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition-colors">
                    <span class="text-base leading-none">+</span>
                    <span>Tambah Film Baru</span>
                </a>
            </div>
        </x-admin.page-header>

        {{-- Filter Tabs & Pencarian Judul --}}
        <div class="mt-4 space-y-3">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-border pb-3">
                {{-- Filter Tabs --}}
                <div class="flex items-center flex-wrap gap-1.5">
                    <a
                        href="{{ route('admin.films.index') }}"
                        class="rounded-xl px-3 py-1.5 text-xs font-bold transition-colors {{ empty($filter) && !$showTrash ? 'bg-amber-500 text-slate-950' : 'text-text-muted hover:text-text bg-surface-2' }}"
                    >
                        Semua Film ({{ $allCount }})
                    </a>
                    <a
                        href="{{ route('admin.films.index', ['filter' => 'belum_tampil']) }}"
                        class="rounded-xl px-3 py-1.5 text-xs font-bold transition-colors {{ $filter === 'belum_tampil' ? 'bg-amber-500 text-slate-950' : 'text-text-muted hover:text-text bg-surface-2' }}"
                    >
                        Belum Tampil ({{ $inactiveCount }})
                    </a>
                    <a
                        href="{{ route('admin.films.index', ['filter' => 'tanpa_poster']) }}"
                        class="rounded-xl px-3 py-1.5 text-xs font-bold transition-colors {{ $filter === 'tanpa_poster' ? 'bg-amber-500 text-slate-950' : 'text-text-muted hover:text-text bg-surface-2' }}"
                    >
                        Tanpa Poster ({{ $noPosterCount }})
                    </a>
                    <a
                        href="{{ route('admin.films.index', ['filter' => 'perlu_dilengkapi']) }}"
                        class="rounded-xl px-3 py-1.5 text-xs font-bold transition-colors {{ $filter === 'perlu_dilengkapi' ? 'bg-amber-500 text-slate-950' : 'text-text-muted hover:text-text bg-surface-2' }}"
                    >
                        Perlu Dilengkapi ({{ $needsCompletionCount }})
                    </a>
                    @if ($batchId)
                        <a
                            href="{{ route('admin.films.index', ['batch_id' => $batchId]) }}"
                            class="rounded-xl px-3 py-1.5 text-xs font-bold transition-colors bg-amber-500 text-slate-950"
                        >
                            Batch Impor ({{ $films->total() }})
                        </a>
                    @endif
                    @if ($trashedCount > 0 || $showTrash)
                        <a
                            href="{{ route('admin.films.index', ['trash' => 1]) }}"
                            class="rounded-xl px-3 py-1.5 text-xs font-bold transition-colors {{ $showTrash ? 'bg-amber-500 text-slate-950' : 'text-text-muted hover:text-text bg-surface-2' }}"
                        >
                            Sampah ({{ $trashedCount }})
                        </a>
                    @endif
                </div>

                {{-- Kolom Cari Judul --}}
                <form action="{{ route('admin.films.index') }}" method="GET" class="flex items-center gap-2">
                    @if ($filter)
                        <input type="hidden" name="filter" value="{{ $filter }}">
                    @endif
                    @if ($showTrash)
                        <input type="hidden" name="trash" value="1">
                    @endif
                    @if ($batchId)
                        <input type="hidden" name="batch_id" value="{{ $batchId }}">
                    @endif
                    <div class="relative w-full sm:w-64">
                        <input
                            type="text"
                            name="q"
                            value="{{ $search }}"
                            placeholder="Cari judul film..."
                            class="w-full min-h-[38px] rounded-xl border border-border bg-surface pl-9 pr-3 text-xs text-text placeholder:text-text-muted focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                        >
                        <svg class="size-4 text-text-muted absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    @if ($search)
                        <a href="{{ route('admin.films.index', array_filter(['filter' => $filter, 'trash' => $showTrash ? 1 : null, 'batch_id' => $batchId])) }}" class="text-xs text-text-muted hover:text-text">
                            Reset
                        </a>
                    @endif
                </form>
            </div>
        </div>

        {{-- Banner Pilih Semua Sesuai Filter ("Pilih 230 film") --}}
        @if ($films->total() > $films->count())
            <div
                x-show="allChecked && !selectAllFiltered"
                x-cloak
                class="mt-4 rounded-xl border border-amber-500/40 bg-amber-500/10 p-3 text-xs text-text flex items-center justify-between gap-3 shadow-xs"
            >
                <span>Semua <strong>{{ $films->count() }}</strong> film di halaman ini telah dipilih.</span>
                <button
                    type="button"
                    @click="selectAllFiltered = true"
                    class="font-bold text-amber-600 dark:text-amber-400 hover:underline"
                >
                    Pilih semua {{ $films->total() }} film yang sesuai filter ini
                </button>
            </div>

            <div
                x-show="selectAllFiltered"
                x-cloak
                class="mt-4 rounded-xl border border-success/40 bg-success/10 p-3 text-xs text-text flex items-center justify-between gap-3 shadow-xs"
            >
                <span class="font-bold text-success">
                    Seluruh {{ $films->total() }} film sesuai filter telah dipilih untuk aksi massal.
                </span>
                <button
                    type="button"
                    @click="selectAllFiltered = false"
                    class="text-xs text-text-muted hover:underline"
                >
                    Batalkan pilihan massal seluruh filter
                </button>
            </div>
        @endif

        @if ($films->isEmpty())
            <div class="mt-6 rounded-2xl border border-border bg-surface shadow-token">
                <x-admin.empty-state
                    title="{{ $showTrash ? 'Tidak ada film di kotak sampah' : ($search ? 'Tidak ada film yang cocok dengan pencarian' : 'Belum ada film di katalog') }}"
                    description="{{ $showTrash ? 'Film yang dihapus akan muncul di sini selama 30 hari.' : ($search ? 'Coba ubah kata kunci pencarian atau reset filter.' : 'Tambahkan film baru atau gunakan impor massal untuk memulai.') }}"
                    actionUrl="{{ $showTrash ? route('admin.films.index') : route('admin.films.create') }}"
                    actionLabel="{{ $showTrash ? 'Kembali ke Katalog' : 'Tambah Film Baru' }}"
                />
            </div>
        @else
            {{-- DESKTOP TABLE VIEW (1024px and up) --}}
            <div class="mt-6 hidden lg:block admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center">
                                <input
                                    type="checkbox"
                                    x-model="allChecked"
                                    @change="toggleAll()"
                                    aria-label="Pilih semua film di halaman ini"
                                    class="rounded border-border text-amber-500 focus:ring-amber-500"
                                >
                            </th>
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
                                <td class="w-10 text-center">
                                    <input
                                        type="checkbox"
                                        value="{{ $film->id }}"
                                        x-model="selected"
                                        @change="updateCheckAll()"
                                        aria-label="Pilih film {{ $film->title }}"
                                        class="rounded border-border text-amber-500 focus:ring-amber-500"
                                    >
                                </td>
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
                                            <p class="mt-0.5 line-clamp-1 text-xs text-text-muted">
                                                {{ $film->synopsis ?: 'Belum ada sinopsis (perlu dilengkapi).' }}
                                            </p>
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
                                    @if ($film->trashed())
                                        <span class="inline-flex items-center rounded-full bg-danger/15 px-2.5 py-0.5 text-xs font-bold text-danger border border-danger/30">
                                            Di Sampah
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('admin.films.status', $film) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold transition-colors {{ $film->is_active ? 'bg-success/15 text-success border border-success/30 hover:bg-success/25' : 'bg-surface-2 text-text-muted border border-border hover:bg-surface-3' }}"
                                            >
                                                <span class="size-1.5 rounded-full {{ $film->is_active ? 'bg-success' : 'bg-text-muted' }}"></span>
                                                <span>{{ $film->is_active ? 'Tampil' : 'Disembunyikan' }}</span>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                                <td class="col-actions-sticky">
                                    <div class="flex items-center gap-1">
                                        @if ($film->trashed())
                                            <form method="POST" action="{{ route('admin.films.restore', $film->id) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 dark:border-white/20 bg-surface px-2.5 py-1 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors">
                                                    Pulihkan
                                                </button>
                                            </form>
                                        @else
                                            <x-admin.action-menu
                                                editUrl="{{ route('admin.films.edit', $film) }}"
                                                viewUrl="{{ $film->slug ? url('/film/' . $film->slug) : null }}"
                                                duplicateUrl="{{ route('admin.films.duplicate', $film) }}"
                                                deleteUrl="{{ route('admin.films.destroy', $film) }}"
                                                deleteMessage="Pindahkan film '{{ addslashes($film->title) }}' ke kotak sampah?"
                                                itemName="film"
                                            />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- MOBILE CARD VIEW (Under 1024px) --}}
            <div class="mt-6 space-y-3 lg:hidden">
                @foreach ($films as $film)
                    <div class="rounded-2xl border border-border bg-surface p-4 shadow-token space-y-3">
                        <div class="flex items-start gap-3">
                            <input
                                type="checkbox"
                                value="{{ $film->id }}"
                                x-model="selected"
                                @change="updateCheckAll()"
                                aria-label="Pilih film {{ $film->title }}"
                                class="mt-1 rounded border-border text-amber-500 focus:ring-amber-500"
                            >
                            @if ($film->poster_path)
                                <img src="{{ $film->posterUrl() }}" alt="{{ $film->title }}" class="h-20 w-14 shrink-0 rounded-lg object-cover border border-border">
                            @else
                                <div class="flex h-20 w-14 shrink-0 items-center justify-center rounded-lg border border-border bg-surface-2 text-[9px] font-semibold text-text-muted uppercase text-center p-1 leading-tight">
                                    Tanpa poster
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('admin.films.edit', $film) }}" class="font-bold text-text hover:text-amber-500 leading-snug">
                                    {{ $film->title }}
                                </a>
                                <p class="mt-0.5 text-xs text-text-muted">{{ $film->genre }} · {{ $film->release_year ?: '-' }} · {{ $film->duration_minutes }}m</p>
                                <div class="mt-2 flex items-center gap-2">
                                    <span class="inline-flex items-center rounded-md bg-slate-200 dark:bg-white/10 px-2 py-0.5 text-[10px] font-bold text-text">
                                        {{ $film->age_rating }}
                                    </span>
                                    @if ($film->trashed())
                                        <span class="inline-flex items-center rounded-full bg-danger/15 px-2 py-0.5 text-[10px] font-bold text-danger border border-danger/30">
                                            Di Sampah
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('admin.films.status', $film) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $film->is_active ? 'bg-success/15 text-success border border-success/30' : 'bg-surface-2 text-text-muted border border-border' }}"
                                            >
                                                <span>{{ $film->is_active ? 'Tampil' : 'Disembunyikan' }}</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t border-border pt-3 text-xs">
                            <span class="text-text-muted truncate max-w-[150px]">{{ $film->synopsis ?: 'Belum ada sinopsis' }}</span>
                            @if ($film->trashed())
                                <form method="POST" action="{{ route('admin.films.restore', $film->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 dark:border-white/20 bg-surface px-2.5 py-1 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors">
                                        Pulihkan
                                    </button>
                                </form>
                            @else
                                <x-admin.action-menu
                                    editUrl="{{ route('admin.films.edit', $film) }}"
                                    viewUrl="{{ $film->slug ? url('/film/' . $film->slug) : null }}"
                                    duplicateUrl="{{ route('admin.films.duplicate', $film) }}"
                                    deleteUrl="{{ route('admin.films.destroy', $film) }}"
                                    deleteMessage="Pindahkan film '{{ addslashes($film->title) }}' ke kotak sampah?"
                                    itemName="film"
                                />
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $films->links() }}
            </div>
        @endif

        {{-- Floating Sticky Bulk Action Bar --}}
        <div
            x-show="selected.length > 0 || selectAllFiltered"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 w-[95%] max-w-3xl rounded-2xl border border-border bg-surface/95 backdrop-blur-md p-4 shadow-2xl flex flex-wrap items-center justify-between gap-3"
        >
            <div class="flex items-center gap-2">
                <span class="inline-flex size-6 items-center justify-center rounded-full bg-amber-500 text-xs font-black text-slate-950" x-text="selectAllFiltered ? '{{ $films->total() }}' : selected.length"></span>
                <span class="text-xs font-bold text-text">film dipilih</span>
            </div>

            <div class="flex items-center flex-wrap gap-2">
                @if ($showTrash)
                    <form method="POST" action="{{ route('admin.films.bulk_action') }}" class="inline">
                        @csrf
                        <input type="hidden" name="action" value="restore">
                        <input type="hidden" name="trash" value="1">
                        <input type="hidden" name="select_all" :value="selectAllFiltered ? 1 : 0">
                        <template x-for="id in selected" :key="'restore-'+id">
                            <input type="hidden" name="film_ids[]" :value="id">
                        </template>
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-success/40 bg-success/10 px-3 py-1.5 text-xs font-bold text-success hover:bg-success/20 transition-colors">
                            Pulihkan Semua
                        </button>
                    </form>
                @else
                    {{-- Tampilkan --}}
                    <form method="POST" action="{{ route('admin.films.bulk_action') }}" class="inline">
                        @csrf
                        <input type="hidden" name="action" value="activate">
                        <input type="hidden" name="filter" value="{{ $filter }}">
                        <input type="hidden" name="q" value="{{ $search }}">
                        <input type="hidden" name="select_all" :value="selectAllFiltered ? 1 : 0">
                        <template x-for="id in selected" :key="'act-'+id">
                            <input type="hidden" name="film_ids[]" :value="id">
                        </template>
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-success/40 bg-success/10 px-3 py-1.5 text-xs font-bold text-success hover:bg-success/20 transition-colors">
                            Tampilkan
                        </button>
                    </form>

                    {{-- Sembunyikan --}}
                    <form method="POST" action="{{ route('admin.films.bulk_action') }}" class="inline">
                        @csrf
                        <input type="hidden" name="action" value="deactivate">
                        <input type="hidden" name="filter" value="{{ $filter }}">
                        <input type="hidden" name="q" value="{{ $search }}">
                        <input type="hidden" name="select_all" :value="selectAllFiltered ? 1 : 0">
                        <template x-for="id in selected" :key="'deact-'+id">
                            <input type="hidden" name="film_ids[]" :value="id">
                        </template>
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-border bg-surface-2 px-3 py-1.5 text-xs font-bold text-text hover:bg-surface-3 transition-colors">
                            Sembunyikan
                        </button>
                    </form>

                    {{-- Atur Klasifikasi Usia --}}
                    <button
                        type="button"
                        @click="ratingModalOpen = true"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-border bg-surface-2 px-3 py-1.5 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors"
                    >
                        Atur Klasifikasi
                    </button>

                    {{-- Kelola Genre --}}
                    <button
                        type="button"
                        @click="genreModalOpen = true"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-border bg-surface-2 px-3 py-1.5 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors"
                    >
                        Kelola Genre
                    </button>

                    {{-- Hapus (Pindahkan ke Sampah) --}}
                    <form
                        method="POST"
                        action="{{ route('admin.films.bulk_action') }}"
                        data-confirm="Pindahkan seluruh film terpilih ke sampah?"
                        data-confirm-title="Pindahkan ke Sampah"
                        data-confirm-action="Hapus Masal"
                        data-confirm-is-danger="true"
                        novalidate
                        class="inline"
                    >
                        @csrf
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="filter" value="{{ $filter }}">
                        <input type="hidden" name="q" value="{{ $search }}">
                        <input type="hidden" name="select_all" :value="selectAllFiltered ? 1 : 0">
                        <template x-for="id in selected" :key="'del-'+id">
                            <input type="hidden" name="film_ids[]" :value="id">
                        </template>
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-danger/40 bg-danger/10 px-3 py-1.5 text-xs font-bold text-danger hover:bg-danger/20 transition-colors">
                            Hapus
                        </button>
                    </form>
                @endif

                <button
                    type="button"
                    @click="selected = []; allChecked = false; selectAllFiltered = false"
                    class="rounded-xl px-2.5 py-1.5 text-xs font-semibold text-text-muted hover:text-text"
                >
                    Batal
                </button>
            </div>
        </div>

        {{-- Modal In-App: Atur Klasifikasi Usia --}}
        <div
            x-show="ratingModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
        >
            <div
                @click.outside="ratingModalOpen = false"
                class="w-full max-w-md rounded-2xl border border-border bg-surface p-6 shadow-2xl space-y-4"
            >
                <div>
                    <h3 class="text-base font-bold text-text">Atur Klasifikasi Usia Massal</h3>
                    <p class="text-xs text-text-muted mt-0.5">
                        Terapkan klasifikasi usia terpilih untuk <span x-text="selectAllFiltered ? '{{ $films->total() }}' : selected.length"></span> film.
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.films.bulk_action') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="action" value="set_rating">
                    <input type="hidden" name="filter" value="{{ $filter }}">
                    <input type="hidden" name="q" value="{{ $search }}">
                    <input type="hidden" name="select_all" :value="selectAllFiltered ? 1 : 0">
                    <template x-for="id in selected" :key="'rate-'+id">
                        <input type="hidden" name="film_ids[]" :value="id">
                    </template>

                    <div>
                        <x-ui.select
                            name="age_rating"
                            label="Pilih Klasifikasi Baru"
                            :value="'13+'"
                            :options="[
                                'SU' => 'SU (Semua Umur)',
                                '13+' => '13+ (Remaja)',
                                '17+' => '17+ (Dewasa)',
                            ]"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="ratingModalOpen = false"
                            class="px-4 py-2 rounded-xl border border-border text-xs font-semibold text-text-muted hover:text-text"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-5 py-2 rounded-xl bg-amber-500 text-xs font-bold text-slate-950 hover:bg-amber-400 transition"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal In-App: Kelola Genre Massal --}}
        <div
            x-show="genreModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
        >
            <div
                @click.outside="genreModalOpen = false"
                class="w-full max-w-md rounded-2xl border border-border bg-surface p-6 shadow-2xl space-y-4"
            >
                <div>
                    <h3 class="text-base font-bold text-text">Kelola Genre Massal</h3>
                    <p class="text-xs text-text-muted mt-0.5">
                        Tambahkan atau ganti genre pada <span x-text="selectAllFiltered ? '{{ $films->total() }}' : selected.length"></span> film terpilih.
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.films.bulk_action') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="filter" value="{{ $filter }}">
                    <input type="hidden" name="q" value="{{ $search }}">
                    <input type="hidden" name="select_all" :value="selectAllFiltered ? 1 : 0">
                    <template x-for="id in selected" :key="'genre-'+id">
                        <input type="hidden" name="film_ids[]" :value="id">
                    </template>

                    <div>
                        <x-ui.select
                            name="action"
                            label="Jenis Perubahan Genre"
                            :value="'add_genre'"
                            :options="[
                                'add_genre' => 'Tambah genre (pertahankan genre lama film)',
                                'replace_genre' => 'Ganti seluruh genre film',
                            ]"
                        />
                    </div>

                    <div>
                        @php
                            $genreSelectOptions = array_combine($canonicalGenres, $canonicalGenres);
                        @endphp
                        <x-ui.select
                            name="genre"
                            label="Pilih Genre Baku"
                            :value="'Drama'"
                            :options="$genreSelectOptions"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="genreModalOpen = false"
                            class="px-4 py-2 rounded-xl border border-border text-xs font-semibold text-text-muted hover:text-text"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="px-5 py-2 rounded-xl bg-amber-500 text-xs font-bold text-slate-950 hover:bg-amber-400 transition"
                        >
                            Terapkan Genre
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
