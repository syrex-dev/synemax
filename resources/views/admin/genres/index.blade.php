@extends('layouts.admin')

@section('title', 'Kelola Genre Film')

@section('content')
    <div
        x-data="{
            addModalOpen: false,
            editModalOpen: false,
            mergeModalOpen: false,
            deleteModalOpen: false,
            currentGenre: {
                id: null,
                name: '',
                sort_order: 0,
                is_active: true,
                films_count: 0
            },
            mergeTargetId: '',
            deleteAction: 'merge',
            deleteTargetId: '',

            openEdit(genre) {
                this.currentGenre = {
                    id: genre.id,
                    name: genre.name,
                    sort_order: genre.sort_order,
                    is_active: Boolean(genre.is_active),
                    films_count: genre.films_count
                };
                this.editModalOpen = true;
            },

            openMerge(genre) {
                this.currentGenre = {
                    id: genre.id,
                    name: genre.name,
                    films_count: genre.films_count
                };
                this.mergeTargetId = '';
                this.mergeModalOpen = true;
            },

            openDelete(genre) {
                this.currentGenre = {
                    id: genre.id,
                    name: genre.name,
                    films_count: genre.films_count
                };
                this.deleteAction = genre.films_count > 0 ? 'merge' : 'delete';
                this.deleteTargetId = '';
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
                    <span class="text-text">Genre</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-text tracking-tight">Kelola Genre Film</h1>
                <p class="text-xs text-text-muted mt-1">
                    Satu sumber kebenaran untuk seluruh katalog, filter beranda, form film, dan impor CSV.
                    Total {{ $genres->count() }} genre ({{ $activeCount }} aktif).
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('admin.films.index') }}"
                    class="inline-flex min-h-[42px] items-center gap-1.5 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors shadow-xs"
                >
                    &larr; Kembali ke Film
                </a>
                <button
                    type="button"
                    @click="addModalOpen = true"
                    class="inline-flex min-h-[42px] items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition-colors"
                >
                    <span class="text-base leading-none">+</span>
                    <span>Tambah Genre Baru</span>
                </button>
            </div>
        </div>

        {{-- Genre Table --}}
        <div class="overflow-hidden rounded-2xl border border-border bg-surface shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-border bg-surface-2 text-text-muted uppercase tracking-wider font-bold">
                            <th class="py-3 px-4 w-16 text-center">Urutan</th>
                            <th class="py-3 px-4">Nama Genre</th>
                            <th class="py-3 px-4">Kunci Normalisasi</th>
                            <th class="py-3 px-4 text-center">Jumlah Film</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($genres as $index => $genre)
                            <tr class="hover:bg-surface-2/50 transition-colors">
                                {{-- Urutan Reorder --}}
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <form action="{{ route('admin.genres.reorder') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $genre->id }}">
                                            <input type="hidden" name="direction" value="up">
                                            <button
                                                type="submit"
                                                @disabled($index === 0)
                                                class="rounded p-1 text-text-muted hover:text-amber-500 disabled:opacity-30 disabled:hover:text-text-muted transition"
                                                title="Pindah ke atas"
                                                aria-label="Geser ke atas"
                                            >
                                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                                </svg>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.genres.reorder') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $genre->id }}">
                                            <input type="hidden" name="direction" value="down">
                                            <button
                                                type="submit"
                                                @disabled($index === $genres->count() - 1)
                                                class="rounded p-1 text-text-muted hover:text-amber-500 disabled:opacity-30 disabled:hover:text-text-muted transition"
                                                title="Pindah ke bawah"
                                                aria-label="Geser ke bawah"
                                            >
                                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>

                                {{-- Nama Genre --}}
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center rounded-lg bg-surface-2 px-2.5 py-1 font-bold text-text text-xs border border-border">
                                            {{ $genre->name }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Normalized Name --}}
                                <td class="py-3 px-4 font-mono text-[11px] text-text-muted">
                                    {{ $genre->normalized_name }}
                                </td>

                                {{-- Film Count --}}
                                <td class="py-3 px-4 text-center">
                                    <a
                                        href="{{ route('admin.films.index', ['genre' => $genre->name]) }}"
                                        class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $genre->films_count > 0 ? 'bg-amber-500/10 text-amber-700 dark:text-amber-400 hover:bg-amber-500/20' : 'bg-surface-2 text-text-muted' }} transition"
                                    >
                                        {{ $genre->films_count }} film
                                    </a>
                                </td>

                                {{-- Status Active / Inactive Switch --}}
                                <td class="py-3 px-4 text-center">
                                    <form action="{{ route('admin.genres.toggle', $genre) }}" method="POST" class="inline-block">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="submit"
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-bold transition {{ $genre->is_active ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-500/25' : 'bg-surface-2 text-text-muted hover:bg-surface-2/80' }}"
                                            title="Klik untuk mengubah status aktif/nonaktif"
                                        >
                                            <span class="size-1.5 rounded-full {{ $genre->is_active ? 'bg-emerald-500' : 'bg-text-muted' }}"></span>
                                            <span>{{ $genre->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                        </button>
                                    </form>
                                </td>

                                {{-- Actions --}}
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEdit(@js($genre))"
                                            class="rounded-lg border border-border bg-surface px-2.5 py-1 text-xs font-semibold text-text hover:border-amber-500 hover:text-amber-500 transition shadow-xs"
                                        >
                                            Ubah
                                        </button>
                                        <button
                                            type="button"
                                            @click="openMerge(@js($genre))"
                                            class="rounded-lg border border-border bg-surface px-2.5 py-1 text-xs font-semibold text-text hover:border-amber-500 hover:text-amber-500 transition shadow-xs"
                                            title="Gabungkan seluruh film genre ini ke genre lain"
                                        >
                                            Gabungkan
                                        </button>
                                        <button
                                            type="button"
                                            @click="openDelete(@js($genre))"
                                            class="rounded-lg border border-rose-500/30 bg-rose-500/10 px-2.5 py-1 text-xs font-semibold text-rose-700 dark:text-rose-400 hover:bg-rose-500/20 transition shadow-xs"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-text-muted">
                                    Belum ada genre yang terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- MODAL TAMBAH GENRE BARU                                                   --}}
        {{-- ========================================================================= --}}
        <div
            x-show="addModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
        >
            <div
                @click.away="addModalOpen = false"
                class="w-full max-w-md rounded-2xl border border-border bg-surface p-6 shadow-2xl space-y-4"
            >
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <h3 class="text-base font-bold text-text">Tambah Genre Baru</h3>
                    <button type="button" @click="addModalOpen = false" class="text-text-muted hover:text-text text-lg leading-none" aria-label="Tutup modal">&times;</button>
                </div>

                <form action="{{ route('admin.genres.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="add-name" class="block text-xs font-bold text-text mb-1">Nama Genre <span class="text-rose-500">*</span></label>
                        <input
                            type="text"
                            id="add-name"
                            name="name"
                            required
                            maxlength="50"
                            placeholder="Contoh: Musikal, Biografi, Fantasi"
                            class="min-h-11 w-full rounded-xl border border-border-input bg-surface px-3 py-2 text-xs text-text placeholder:text-text-muted focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400"
                        >
                        <p class="text-[11px] text-text-muted mt-1">Nama akan otomatis disesuaikan ke huruf besar di awal tiap kata (Title Case).</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="add-is-active" name="is_active" value="1" checked class="size-4 rounded border-border-input text-amber-500 focus:ring-amber-400">
                        <label for="add-is-active" class="text-xs font-semibold text-text">Aktifkan langsung (tampil di pilihan filter dan form film)</label>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-border pt-3">
                        <button
                            type="button"
                            @click="addModalOpen = false"
                            class="min-h-10 rounded-xl border border-border px-4 py-2 text-xs font-bold text-text hover:bg-surface-2 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="min-h-10 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition"
                        >
                            Simpan Genre
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- MODAL UBAH GENRE                                                          --}}
        {{-- ========================================================================= --}}
        <div
            x-show="editModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
        >
            <div
                @click.away="editModalOpen = false"
                class="w-full max-w-md rounded-2xl border border-border bg-surface p-6 shadow-2xl space-y-4"
            >
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <h3 class="text-base font-bold text-text">Ubah Nama Genre</h3>
                    <button type="button" @click="editModalOpen = false" class="text-text-muted hover:text-text text-lg leading-none" aria-label="Tutup modal">&times;</button>
                </div>

                <form :action="'{{ url('admin/genres') }}/' + currentGenre.id" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="edit-name" class="block text-xs font-bold text-text mb-1">Nama Genre <span class="text-rose-500">*</span></label>
                        <input
                            type="text"
                            id="edit-name"
                            name="name"
                            x-model="currentGenre.name"
                            required
                            maxlength="50"
                            class="min-h-11 w-full rounded-xl border border-border-input bg-surface px-3 py-2 text-xs text-text focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400"
                        >
                        <p class="text-[11px] text-text-muted mt-1" x-show="currentGenre.films_count > 0">
                            Mengubah nama genre ini akan secara otomatis memperbarui <span class="font-bold text-amber-500" x-text="currentGenre.films_count"></span> film yang menggunakannya dalam satu transaksi.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="edit-is-active" name="is_active" value="1" x-model="currentGenre.is_active" class="size-4 rounded border-border-input text-amber-500 focus:ring-amber-400">
                        <label for="edit-is-active" class="text-xs font-semibold text-text">Genre Aktif</label>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-border pt-3">
                        <button
                            type="button"
                            @click="editModalOpen = false"
                            class="min-h-10 rounded-xl border border-border px-4 py-2 text-xs font-bold text-text hover:bg-surface-2 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="min-h-10 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- MODAL GABUNGKAN GENRE                                                     --}}
        {{-- ========================================================================= --}}
        <div
            x-show="mergeModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
        >
            <div
                @click.away="mergeModalOpen = false"
                class="w-full max-w-md rounded-2xl border border-border bg-surface p-6 shadow-2xl space-y-4"
            >
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <h3 class="text-base font-bold text-text">Gabungkan Genre</h3>
                    <button type="button" @click="mergeModalOpen = false" class="text-text-muted hover:text-text text-lg leading-none" aria-label="Tutup modal">&times;</button>
                </div>

                <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-text">
                    <p>
                        Genre sumber: <strong class="text-amber-600 dark:text-amber-400" x-text="currentGenre.name"></strong>
                        (<span x-text="currentGenre.films_count"></span> film terdampak).
                    </p>
                    <p class="mt-1 text-text-muted">
                        Seluruh film yang memiliki genre sumber akan diperbarui menjadi genre tujuan. Genre sumber akan dihapus setelah penggabungan selesai.
                    </p>
                </div>

                <form :action="'{{ url('admin/genres') }}/' + currentGenre.id + '/merge'" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="merge-target-id" class="block text-xs font-bold text-text mb-1">Gabungkan ke Genre Tujuan <span class="text-rose-500">*</span></label>
                        <x-ui.select
                            name="target_id"
                            id="merge-target-id"
                            x-model="mergeTargetId"
                            required
                        >
                            <option value="">-- Pilih Genre Tujuan --</option>
                            @foreach ($genres as $opt)
                                <option
                                    value="{{ $opt->id }}"
                                    x-show="currentGenre.id !== {{ $opt->id }}"
                                >
                                    {{ $opt->name }} ({{ $opt->films_count }} film)
                                </option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-border pt-3">
                        <button
                            type="button"
                            @click="mergeModalOpen = false"
                            class="min-h-10 rounded-xl border border-border px-4 py-2 text-xs font-bold text-text hover:bg-surface-2 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="!mergeTargetId"
                            class="min-h-10 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 disabled:opacity-50 transition"
                        >
                            Proses Penggabungan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- MODAL HAPUS GENRE                                                         --}}
        {{-- ========================================================================= --}}
        <div
            x-show="deleteModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
        >
            <div
                @click.away="deleteModalOpen = false"
                class="w-full max-w-md rounded-2xl border border-border bg-surface p-6 shadow-2xl space-y-4"
            >
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <h3 class="text-base font-bold text-rose-600 dark:text-rose-400">Hapus Genre</h3>
                    <button type="button" @click="deleteModalOpen = false" class="text-text-muted hover:text-text text-lg leading-none" aria-label="Tutup modal">&times;</button>
                </div>

                <div x-show="currentGenre.films_count === 0" class="space-y-4">
                    <p class="text-xs text-text">
                        Yakin ingin menghapus genre <strong class="text-amber-500" x-text="currentGenre.name"></strong>?
                        Genre ini tidak digunakan oleh film mana pun.
                    </p>

                    <form :action="'{{ url('admin/genres') }}/' + currentGenre.id" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="flex items-center justify-end gap-2 border-t border-border pt-3">
                            <button
                                type="button"
                                @click="deleteModalOpen = false"
                                class="min-h-10 rounded-xl border border-border px-4 py-2 text-xs font-bold text-text hover:bg-surface-2 transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                class="min-h-10 rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white hover:bg-rose-500 transition"
                            >
                                Hapus Genre Sekarang
                            </button>
                        </div>
                    </form>
                </div>

                <div x-show="currentGenre.films_count > 0" class="space-y-4">
                    <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-text">
                        Peringatan: Genre <strong class="text-amber-600 dark:text-amber-400" x-text="currentGenre.name"></strong> sedang digunakan oleh <span class="font-bold text-amber-500" x-text="currentGenre.films_count"></span> film.
                        Pilih opsi berikut untuk melanjutkan:
                    </div>

                    <form :action="'{{ url('admin/genres') }}/' + currentGenre.id" method="POST" class="space-y-4">
                        @csrf
                        @method('DELETE')

                        <div class="space-y-2">
                            <label class="flex items-start gap-2 p-2.5 rounded-xl border border-border cursor-pointer hover:bg-surface-2">
                                <input type="radio" name="action" value="merge" x-model="deleteAction" class="mt-0.5 text-amber-500 focus:ring-amber-400">
                                <div>
                                    <span class="text-xs font-bold text-text block">Gabungkan film ke genre lain, lalu hapus</span>
                                    <span class="text-[11px] text-text-muted">Film tidak kehilangan kategori genre.</span>
                                </div>
                            </label>

                            <label class="flex items-start gap-2 p-2.5 rounded-xl border border-border cursor-pointer hover:bg-surface-2">
                                <input type="radio" name="action" value="deactivate" x-model="deleteAction" class="mt-0.5 text-amber-500 focus:ring-amber-400">
                                <div>
                                    <span class="text-xs font-bold text-text block">Jangan hapus, nonaktifkan saja</span>
                                    <span class="text-[11px] text-text-muted">Film lama tetap aman, tapi tidak muncul di form baru atau filter publik.</span>
                                </div>
                            </label>
                        </div>

                        <div x-show="deleteAction === 'merge'" class="pt-2">
                            <label for="delete-target-id" class="block text-xs font-bold text-text mb-1">Pilih Genre Pengganti <span class="text-rose-500">*</span></label>
                            <x-ui.select
                                name="target_id"
                                id="delete-target-id"
                                x-model="deleteTargetId"
                            >
                                <option value="">-- Pilih Genre Tujuan --</option>
                                @foreach ($genres as $opt)
                                    <option
                                        value="{{ $opt->id }}"
                                        x-show="currentGenre.id !== {{ $opt->id }}"
                                    >
                                        {{ $opt->name }} ({{ $opt->films_count }} film)
                                    </option>
                                @endforeach
                            </x-ui.select>
                        </div>

                        <div class="flex items-center justify-end gap-2 border-t border-border pt-3">
                            <button
                                type="button"
                                @click="deleteModalOpen = false"
                                class="min-h-10 rounded-xl border border-border px-4 py-2 text-xs font-bold text-text hover:bg-surface-2 transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="deleteAction === 'merge' && !deleteTargetId"
                                class="min-h-10 rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white hover:bg-rose-500 disabled:opacity-50 transition"
                            >
                                Proses & Selesaikan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
