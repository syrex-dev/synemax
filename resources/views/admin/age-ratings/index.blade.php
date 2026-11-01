@extends('layouts.admin')

@section('title', 'Klasifikasi Usia')

@section('content')
    <div
        x-data="{
            addModalOpen: false,
            editModalOpen: false,
            deleteModalOpen: false,
            editRating: {
                id: null,
                code: '',
                label: '',
                min_age: 0,
                description: '',
                is_active: true,
                films_count: 0
            },
            deleteRating: {
                id: null,
                code: '',
                label: '',
                films_count: 0
            },
            deleteAction: 'reassign',
            deleteTargetCode: '',
            openEdit(rating) {
                this.editRating = {
                    id: rating.id,
                    code: rating.code,
                    label: rating.label,
                    min_age: rating.min_age,
                    description: rating.description || '',
                    is_active: Boolean(rating.is_active),
                    films_count: rating.films_count
                };
                this.editModalOpen = true;
            },
            openDelete(rating) {
                this.deleteRating = {
                    id: rating.id,
                    code: rating.code,
                    label: rating.label,
                    films_count: rating.films_count
                };
                this.deleteAction = rating.films_count > 0 ? 'reassign' : 'delete';
                this.deleteTargetCode = '';
                this.deleteModalOpen = true;
            }
        }"
        class="space-y-6"
    >
        {{-- Flash Status & Errors --}}
        @if (session('status'))
            <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-4 text-xs font-semibold text-rose-700 dark:text-rose-400">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-4 text-xs font-semibold text-rose-700 dark:text-rose-400">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Page Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-text-muted mb-1">
                    <a href="{{ route('admin.films.index') }}" class="hover:text-amber-500 transition-colors">Katalog Film</a>
                    <span>/</span>
                    <span class="text-text">Klasifikasi Usia</span>
                </div>
                <h1 class="text-2xl font-black tracking-tight text-text">Klasifikasi Usia</h1>
                <p class="text-xs text-text-muted mt-1">
                    Kelola batas umur penonton (SU, 13+, 17+, dll.). Data digunakan pada katalog, jadwal, form film, dan tiket.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a
                    href="{{ route('admin.films.index') }}"
                    class="inline-flex min-h-[42px] items-center gap-1.5 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors shadow-xs"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Kembali ke Film</span>
                </a>
                <button
                    type="button"
                    @click="addModalOpen = true"
                    class="inline-flex min-h-[42px] items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition-colors shadow-xs"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Klasifikasi</span>
                </button>
            </div>
        </div>

        {{-- Content Table & Cards --}}
        <div class="rounded-2xl border border-border bg-surface overflow-hidden shadow-xs">
            {{-- Desktop Table View --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-border bg-surface-2 text-text-muted uppercase tracking-wider text-[11px] font-bold">
                            <th class="py-3 px-4 w-16 text-center">Urutan</th>
                            <th class="py-3 px-4 w-28">Kode</th>
                            <th class="py-3 px-4">Label</th>
                            <th class="py-3 px-4 w-32">Usia Minimal</th>
                            <th class="py-3 px-4 w-28 text-center">Jumlah Film</th>
                            <th class="py-3 px-4 w-28 text-center">Status</th>
                            <th class="py-3 px-4 w-36 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($ageRatings as $index => $rating)
                            <tr class="hover:bg-surface-2/60 transition-colors">
                                {{-- Sort Order & Reorder Buttons --}}
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <form action="{{ route('admin.age_ratings.reorder') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $rating->id }}">
                                            <input type="hidden" name="direction" value="up">
                                            <button
                                                type="submit"
                                                @if ($index === 0) disabled @endif
                                                class="rounded p-1 text-text-muted hover:text-amber-500 disabled:opacity-20 disabled:cursor-not-allowed"
                                                title="Geser Naik"
                                            >
                                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                                            </button>
                                        </form>
                                        <span class="font-mono text-text-muted text-[11px] w-4 text-center">{{ $rating->sort_order }}</span>
                                        <form action="{{ route('admin.age_ratings.reorder') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $rating->id }}">
                                            <input type="hidden" name="direction" value="down">
                                            <button
                                                type="submit"
                                                @if ($index === count($ageRatings) - 1) disabled @endif
                                                class="rounded p-1 text-text-muted hover:text-amber-500 disabled:opacity-20 disabled:cursor-not-allowed"
                                                title="Geser Turun"
                                            >
                                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>

                                {{-- Code --}}
                                <td class="py-3 px-4 font-black">
                                    <span class="inline-flex items-center rounded-md border border-border bg-surface-2 px-2.5 py-1 text-xs font-black tracking-wide text-amber-500 font-mono">
                                        {{ $rating->code }}
                                    </span>
                                </td>

                                {{-- Label & Description --}}
                                <td class="py-3 px-4">
                                    <div class="font-bold text-text">{{ $rating->label }}</div>
                                    @if ($rating->description)
                                        <div class="text-[11px] text-text-muted line-clamp-1 mt-0.5">{{ $rating->description }}</div>
                                    @endif
                                </td>

                                {{-- Min Age --}}
                                <td class="py-3 px-4">
                                    <span class="font-semibold text-text">
                                        @if ($rating->min_age === 0)
                                            Semua usia
                                        @else
                                            ≥ {{ $rating->min_age }} tahun
                                        @endif
                                    </span>
                                </td>

                                {{-- Film Count --}}
                                <td class="py-3 px-4 text-center">
                                    @if ($rating->films_count > 0)
                                        <span class="inline-flex items-center rounded-full bg-surface-2 border border-border px-2.5 py-0.5 text-[11px] font-bold text-text">
                                            {{ $rating->films_count }} film
                                        </span>
                                    @else
                                        <span class="text-text-muted text-[11px]">0 film</span>
                                    @endif
                                </td>

                                {{-- Status (Switch / Toggle) --}}
                                <td class="py-3 px-4 text-center">
                                    <form action="{{ route('admin.age_ratings.toggle', $rating) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="submit"
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold transition-colors {{ $rating->is_active ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/20' : 'bg-surface-2 text-text-muted border border-border hover:text-text' }}"
                                            title="Klik untuk ubah status aktif"
                                        >
                                            <span class="size-1.5 rounded-full {{ $rating->is_active ? 'bg-emerald-500' : 'bg-text-muted' }}"></span>
                                            <span>{{ $rating->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                        </button>
                                    </form>
                                </td>

                                {{-- Actions --}}
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEdit(@js($rating))"
                                            class="rounded-lg border border-border bg-surface-2 px-2.5 py-1 text-xs font-semibold text-text hover:border-amber-500 hover:text-amber-500 transition-colors"
                                        >
                                            Ubah
                                        </button>
                                        <button
                                            type="button"
                                            @click="openDelete(@js($rating))"
                                            class="rounded-lg border border-rose-500/30 bg-rose-500/10 px-2.5 py-1 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-500/20 transition-colors"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-text-muted">
                                    Belum ada data klasifikasi usia.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile Two-Tier Row View --}}
            <div class="md:hidden divide-y divide-border">
                @forelse ($ageRatings as $index => $rating)
                    <div class="p-4 space-y-3">
                        {{-- Tier 1: Code, Label, Status, Film Count --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center rounded-md border border-border bg-surface-2 px-2.5 py-1 text-xs font-black text-amber-500 font-mono">
                                    {{ $rating->code }}
                                </span>
                                <div>
                                    <div class="font-bold text-text text-sm">{{ $rating->label }}</div>
                                    <div class="text-xs text-text-muted mt-0.5">
                                        {{ $rating->min_age === 0 ? 'Semua usia' : '≥ ' . $rating->min_age . ' tahun' }}
                                        · {{ $rating->films_count }} film
                                    </div>
                                </div>
                            </div>
                            <form action="{{ route('admin.age_ratings.toggle', $rating) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold {{ $rating->is_active ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30' : 'bg-surface-2 text-text-muted border border-border' }}"
                                >
                                    <span class="size-1.5 rounded-full {{ $rating->is_active ? 'bg-emerald-500' : 'bg-text-muted' }}"></span>
                                    <span>{{ $rating->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </button>
                            </form>
                        </div>

                        {{-- Tier 2: Description (if any) & Action Toolbar --}}
                        @if ($rating->description)
                            <p class="text-xs text-text-muted">{{ $rating->description }}</p>
                        @endif

                        <div class="flex items-center justify-between pt-1 border-t border-border/60">
                            {{-- Order controls --}}
                            <div class="flex items-center gap-1">
                                <form action="{{ route('admin.age_ratings.reorder') }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $rating->id }}">
                                    <input type="hidden" name="direction" value="up">
                                    <button
                                        type="submit"
                                        @if ($index === 0) disabled @endif
                                        class="rounded-lg border border-border p-1.5 text-text-muted hover:text-amber-500 disabled:opacity-20"
                                        aria-label="Naik"
                                    >
                                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                                    </button>
                                </form>
                                <span class="font-mono text-xs text-text-muted px-1">{{ $rating->sort_order }}</span>
                                <form action="{{ route('admin.age_ratings.reorder') }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $rating->id }}">
                                    <input type="hidden" name="direction" value="down">
                                    <button
                                        type="submit"
                                        @if ($index === count($ageRatings) - 1) disabled @endif
                                        class="rounded-lg border border-border p-1.5 text-text-muted hover:text-amber-500 disabled:opacity-20"
                                        aria-label="Turun"
                                    >
                                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                </form>
                            </div>

                            {{-- Edit & Delete Buttons --}}
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="openEdit(@js($rating))"
                                    class="rounded-lg border border-border bg-surface-2 px-3 py-1.5 text-xs font-semibold text-text hover:border-amber-500 hover:text-amber-500"
                                >
                                    Ubah
                                </button>
                                <button
                                    type="button"
                                    @click="openDelete(@js($rating))"
                                    class="rounded-lg border border-rose-500/30 bg-rose-500/10 px-3 py-1.5 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-500/20"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-text-muted text-xs">
                        Belum ada data klasifikasi usia.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- In-App Modal: Tambah Klasifikasi --}}
        <div
            x-show="addModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
        >
            <div
                @click.outside="addModalOpen = false"
                class="w-full max-w-lg rounded-2xl border border-border bg-surface p-6 shadow-2xl space-y-4"
            >
                <div>
                    <h3 class="text-base font-bold text-text">Tambah Klasifikasi Usia Baru</h3>
                    <p class="text-xs text-text-muted mt-0.5">
                        Masukkan kode, label, dan usia minimal. Kode akan disimpan pada data film bioskop.
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.age_ratings.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="new_code" class="block text-xs font-bold uppercase tracking-wider text-text">
                                Kode Klasifikasi <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="code"
                                id="new_code"
                                required
                                maxlength="8"
                                placeholder="Contoh: 21+, BO, SU"
                                class="mt-1.5 min-h-[42px] w-full rounded-xl border border-border bg-surface text-text px-3 text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                            >
                            <p class="mt-1 text-[11px] text-text-muted">Maksimal 8 karakter (huruf, angka, + atau -).</p>
                        </div>

                        <div>
                            <label for="new_min_age" class="block text-xs font-bold uppercase tracking-wider text-text">
                                Usia Minimal (Tahun) <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="number"
                                name="min_age"
                                id="new_min_age"
                                required
                                min="0"
                                max="99"
                                value="0"
                                class="mt-1.5 min-h-[42px] w-full rounded-xl border border-border bg-surface text-text px-3 text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                            >
                            <p class="mt-1 text-[11px] text-text-muted">Isi 0 untuk semua umur / tanpa batas.</p>
                        </div>
                    </div>

                    <div>
                        <label for="new_label" class="block text-xs font-bold uppercase tracking-wider text-text">
                            Label Tampilan <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="label"
                            id="new_label"
                            required
                            maxlength="100"
                            placeholder="Contoh: Dewasa 21 Tahun ke Atas"
                            class="mt-1.5 min-h-[42px] w-full rounded-xl border border-border bg-surface text-text px-3 text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                        >
                    </div>

                    <div>
                        <label for="new_description" class="block text-xs font-bold uppercase tracking-wider text-text">
                            Deskripsi (Opsional)
                        </label>
                        <textarea
                            name="description"
                            id="new_description"
                            rows="2"
                            maxlength="500"
                            placeholder="Penjelasan panduan untuk penonton..."
                            class="mt-1.5 w-full rounded-xl border border-border bg-surface text-text p-3 text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                        ></textarea>
                    </div>

                    <div class="pt-2 flex items-center justify-between border-t border-border">
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-text select-none">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded border-border text-amber-500 focus:ring-amber-500">
                            <span>Langsung Aktifkan untuk Form Film Baru</span>
                        </label>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="addModalOpen = false"
                                class="rounded-xl px-4 py-2 text-xs font-semibold text-text-muted hover:text-text"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                class="rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition-colors shadow-xs"
                            >
                                Simpan Klasifikasi
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- In-App Modal: Ubah Klasifikasi --}}
        <div
            x-show="editModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
        >
            <div
                @click.outside="editModalOpen = false"
                class="w-full max-w-lg rounded-2xl border border-border bg-surface p-6 shadow-2xl space-y-4"
            >
                <div>
                    <h3 class="text-base font-bold text-text">Ubah Klasifikasi Usia</h3>
                    <p class="text-xs text-text-muted mt-0.5">
                        Perbarui detail klasifikasi usia.
                        <template x-if="editRating.films_count > 0">
                            <span class="text-amber-500 font-semibold">(Kode terkunci karena dipakai <span x-text="editRating.films_count"></span> film)</span>
                        </template>
                    </p>
                </div>

                <form
                    method="POST"
                    :action="'{{ url('admin/age-ratings') }}/' + editRating.id"
                    class="space-y-4"
                >
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="edit_code" class="block text-xs font-bold uppercase tracking-wider text-text">
                                Kode Klasifikasi
                            </label>
                            <input
                                type="text"
                                name="code"
                                id="edit_code"
                                x-model="editRating.code"
                                :disabled="editRating.films_count > 0"
                                required
                                maxlength="8"
                                class="mt-1.5 min-h-[42px] w-full rounded-xl border border-border bg-surface text-text px-3 text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                            <template x-if="editRating.films_count > 0">
                                <p class="mt-1 text-[11px] text-text-muted">Kode tidak dapat diubah karena sedang dipakai oleh film.</p>
                            </template>
                        </div>

                        <div>
                            <label for="edit_min_age" class="block text-xs font-bold uppercase tracking-wider text-text">
                                Usia Minimal (Tahun) <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="number"
                                name="min_age"
                                id="edit_min_age"
                                x-model="editRating.min_age"
                                required
                                min="0"
                                max="99"
                                class="mt-1.5 min-h-[42px] w-full rounded-xl border border-border bg-surface text-text px-3 text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                            >
                        </div>
                    </div>

                    <div>
                        <label for="edit_label" class="block text-xs font-bold uppercase tracking-wider text-text">
                            Label Tampilan <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="label"
                            id="edit_label"
                            x-model="editRating.label"
                            required
                            maxlength="100"
                            class="mt-1.5 min-h-[42px] w-full rounded-xl border border-border bg-surface text-text px-3 text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                        >
                    </div>

                    <div>
                        <label for="edit_description" class="block text-xs font-bold uppercase tracking-wider text-text">
                            Deskripsi (Opsional)
                        </label>
                        <textarea
                            name="description"
                            id="edit_description"
                            x-model="editRating.description"
                            rows="2"
                            maxlength="500"
                            class="mt-1.5 w-full rounded-xl border border-border bg-surface text-text p-3 text-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                        ></textarea>
                    </div>

                    <div class="pt-2 flex items-center justify-between border-t border-border">
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-text select-none">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                x-model="editRating.is_active"
                                class="rounded border-border text-amber-500 focus:ring-amber-500"
                            >
                            <span>Aktif untuk Form Film Baru</span>
                        </label>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="editModalOpen = false"
                                class="rounded-xl px-4 py-2 text-xs font-semibold text-text-muted hover:text-text"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                class="rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition-colors shadow-xs"
                            >
                                Perbarui
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- In-App Modal: Hapus / Alihkan Klasifikasi --}}
        <div
            x-show="deleteModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
        >
            <div
                @click.outside="deleteModalOpen = false"
                class="w-full max-w-md rounded-2xl border border-border bg-surface p-6 shadow-2xl space-y-4"
            >
                <div>
                    <h3 class="text-base font-bold text-text">
                        Hapus Klasifikasi <span class="text-amber-500 font-mono" x-text="deleteRating.code"></span>
                    </h3>
                    <p class="text-xs text-text-muted mt-1">
                        <template x-if="deleteRating.films_count > 0">
                            <span>Klasifikasi ini sedang digunakan oleh <strong class="text-text" x-text="deleteRating.films_count"></strong> film. Tentukan tindakan untuk film-film tersebut:</span>
                        </template>
                        <template x-if="deleteRating.films_count === 0">
                            <span>Klasifikasi ini tidak sedang digunakan oleh film apa pun dan aman untuk dihapus.</span>
                        </template>
                    </p>
                </div>

                {{-- Disallow deleting last active notice --}}
                @if ($activeCount <= 1)
                    <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-700 dark:text-rose-400 font-semibold">
                        Peringatan: Ini adalah satu-satunya klasifikasi aktif di sistem. Anda tidak boleh menghapus atau menonaktifkannya sebelum menambahkan klasifikasi aktif lain.
                    </div>
                @endif

                <form
                    method="POST"
                    :action="'{{ url('admin/age-ratings') }}/' + deleteRating.id"
                    class="space-y-4"
                >
                    @csrf
                    @method('DELETE')

                    {{-- If films are using this rating --}}
                    <template x-if="deleteRating.films_count > 0">
                        <div class="space-y-3">
                            <label class="flex items-start gap-3 rounded-xl border border-border p-3 cursor-pointer hover:bg-surface-2 transition-colors">
                                <input
                                    type="radio"
                                    name="action"
                                    value="reassign"
                                    x-model="deleteAction"
                                    class="mt-0.5 text-amber-500 focus:ring-amber-500"
                                >
                                <div class="text-xs">
                                    <span class="font-bold text-text block">Pindahkan film ke klasifikasi lain lalu hapus</span>
                                    <span class="text-text-muted block mt-0.5">
                                        Seluruh <span x-text="deleteRating.films_count"></span> film akan diperbarui ke klasifikasi tujuan.
                                    </span>
                                </div>
                            </label>

                            {{-- Reassign target select --}}
                            <div x-show="deleteAction === 'reassign'" class="pl-7">
                                <label for="target_code" class="block text-xs font-semibold text-text mb-1">
                                    Pilih Klasifikasi Tujuan:
                                </label>
                                <x-ui.select
                                    name="target_code"
                                    id="target_code"
                                    placeholder="-- Pilih Klasifikasi Tujuan --"
                                    :options="$ageRatings->where('is_active', true)->mapWithKeys(fn ($r) => [$r->code => $r->code . ' · ' . $r->label])->all()"
                                />
                            </div>

                            <label class="flex items-start gap-3 rounded-xl border border-border p-3 cursor-pointer hover:bg-surface-2 transition-colors">
                                <input
                                    type="radio"
                                    name="action"
                                    value="deactivate"
                                    x-model="deleteAction"
                                    class="mt-0.5 text-amber-500 focus:ring-amber-500"
                                >
                                <div class="text-xs">
                                    <span class="font-bold text-text block">Nonaktifkan saja</span>
                                    <span class="text-text-muted block mt-0.5">
                                        Hilang dari pilihan form baru, film lama tetap aman dan tampil normal.
                                    </span>
                                </div>
                            </label>
                        </div>
                    </template>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-border">
                        <button
                            type="button"
                            @click="deleteModalOpen = false"
                            class="rounded-xl px-4 py-2 text-xs font-semibold text-text-muted hover:text-text"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            @if ($activeCount <= 1) disabled @endif
                            :disabled="deleteRating.films_count > 0 && deleteAction === 'reassign' && !deleteTargetCode"
                            class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white hover:bg-rose-500 transition-colors shadow-xs disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            <span x-text="deleteRating.films_count > 0 && deleteAction === 'deactivate' ? 'Nonaktifkan' : 'Konfirmasi Hapus'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
