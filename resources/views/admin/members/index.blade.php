@extends('layouts.admin')

@section('title', 'Program Member - Panel Admin SynemaX')

@section('content')
<div class="space-y-6" x-data="{
    selectedIds: [],
    selectAll: false,
    deleteModalOpen: false,
    deleteMemberId: null,
    deleteMemberName: '',
    toggleAll() {
        if (this.selectAll) {
            this.selectedIds = Array.from(document.querySelectorAll('.member-checkbox')).map(el => el.value);
        } else {
            this.selectedIds = [];
        }
    },
    openDelete(id, name) {
        this.deleteMemberId = id;
        this.deleteMemberName = name;
        this.deleteModalOpen = true;
    },
    printSelected() {
        if (this.selectedIds.length === 0) return;
        window.location.href = '{{ route('admin.members.print_batch') }}?ids=' + this.selectedIds.join(',');
    }
}">
    {{-- Header & Aksi Utama --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-text sm:text-2xl">Program Member</h1>
            <p class="text-xs text-text-muted mt-1">Kelola data member setia, cetak kartu fisik, dan pantau progres siklus kunjungan.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a
                href="{{ route('admin.members.export') }}"
                class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>Ekspor CSV</span>
            </a>
            <button
                type="button"
                @click="printSelected()"
                :disabled="selectedIds.length === 0"
                class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-semibold text-text disabled:opacity-50 disabled:cursor-not-allowed hover:bg-surface-2 transition"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak Kartu (<span x-text="selectedIds.length"></span>)</span>
            </button>
            <a
                href="{{ route('admin.members.create') }}"
                class="inline-flex min-h-[44px] items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-black shadow-sm hover:bg-amber-400 transition"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Tambah Member</span>
            </a>
        </div>
    </div>

    {{-- Feedback Alerts --}}
    @if (session('status'))
        <div role="alert" class="flex items-center gap-3 rounded-2xl border border-emerald-500/40 bg-emerald-500/10 p-4 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
            <svg class="size-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    {{-- Statistik Ringkas --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-border bg-surface p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-text-muted">Total Member</p>
            <p class="text-xl font-extrabold text-text mt-1">{{ number_format($stats['total'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-surface p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-text-muted">Member Aktif</p>
            <p class="text-xl font-extrabold text-emerald-500 mt-1">{{ number_format($stats['aktif'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-surface p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-text-muted">Nonaktif / Diblokir</p>
            <p class="text-xl font-extrabold text-text-muted mt-1">{{ number_format($stats['nonaktif'] + $stats['diblokir'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-surface p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-text-muted">Reward Pending</p>
            <p class="text-xl font-extrabold text-amber-500 mt-1">{{ number_format($stats['pending_rewards'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Filter & Pencarian --}}
    <div class="rounded-2xl border border-border bg-surface p-4 space-y-4">
        <form method="GET" action="{{ route('admin.members.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <input
                    type="text"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Cari nama member atau ID (misal: 4 karakter terakhir)..."
                    class="w-full min-h-[44px] rounded-xl border border-border bg-surface-2 pl-10 pr-4 text-xs text-text placeholder:text-text-muted focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30"
                >
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-text-muted pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </div>

            <div class="w-full sm:w-48">
                <x-ui.select name="status" class="w-full min-h-[44px] text-xs">
                    <option value="">Semua Status</option>
                    <option value="aktif" {{ $selectedStatus === 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="nonaktif" {{ $selectedStatus === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                    <option value="diblokir" {{ $selectedStatus === 'diblokir' ? 'selected' : '' }}>Diblokir</option>
                </x-ui.select>
            </div>

            <button
                type="submit"
                class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-amber-500 px-5 text-xs font-bold text-black hover:bg-amber-400 transition"
            >
                Cari
            </button>

            @if ($search !== '' || $selectedStatus)
                <a
                    href="{{ route('admin.members.index') }}"
                    class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-border bg-surface px-4 text-xs font-semibold text-text hover:bg-surface-2 transition"
                >
                    Reset
                </a>
            @endif
        </form>
    </div>

    {{-- Tabel Member --}}
    <div class="rounded-2xl border border-border bg-surface shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-border bg-surface-2 text-text-muted uppercase tracking-wider text-[11px] font-bold">
                    <tr>
                        <th class="w-10 px-4 py-3.5 text-center">
                            <input
                                type="checkbox"
                                x-model="selectAll"
                                @change="toggleAll()"
                                class="size-4 rounded border-border text-amber-500 focus:ring-amber-500/30"
                                aria-label="Pilih semua baris"
                            >
                        </th>
                        <th class="px-4 py-3.5">Member</th>
                        <th class="px-4 py-3.5">ID Member</th>
                        <th class="px-4 py-3.5">Progres Siklus</th>
                        <th class="px-4 py-3.5 text-center">Total Kunjungan</th>
                        <th class="px-4 py-3.5">Kunjungan Terakhir</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($members as $member)
                        @php
                            $cycleMax = $settings->memberFreeVisit();
                            $nextPos = $member->nextVisitPosition($cycleMax);
                            $inCycle = $nextPos - 1;
                            $lastVisit = $member->lastValidVisit();
                            $codeParts = explode('-', $member->formattedCode());
                            $codePrefix = count($codeParts) >= 4 ? implode('-', array_slice($codeParts, 0, -1)) . '-' : '';
                            $codeSuffix = count($codeParts) >= 4 ? end($codeParts) : $member->formattedCode();
                        @endphp
                        <tr class="hover:bg-surface-2/60 transition">
                            <td class="px-4 py-3 text-center">
                                <input
                                    type="checkbox"
                                    value="{{ $member->id }}"
                                    x-model="selectedIds"
                                    class="member-checkbox size-4 rounded border-border text-amber-500 focus:ring-amber-500/30"
                                    aria-label="Pilih {{ $member->name }}"
                                >
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="size-9 shrink-0 rounded-full border border-border bg-surface-2 overflow-hidden flex items-center justify-center font-bold text-text-muted">
                                        @if ($member->photo_path)
                                            <img src="{{ $member->photoUrl() }}" alt="{{ $member->name }}" class="size-full object-cover">
                                        @else
                                            <span>{{ strtoupper(substr($member->name, 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.members.show', $member) }}" class="font-bold text-text hover:text-amber-500 transition">
                                            {{ $member->name }}
                                        </a>
                                        @if ($member->nickname)
                                            <p class="text-[11px] text-text-muted">({{ $member->nickname }})</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">
                                <span class="text-text-muted">{{ $codePrefix }}</span><span class="font-bold text-text">{{ $codeSuffix }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="space-y-1 min-w-[130px]">
                                    <div class="flex justify-between text-[11px] font-semibold">
                                        <span class="text-text">{{ $inCycle }}/{{ $cycleMax }}</span>
                                        <span class="text-text-muted">Siklus {{ $member->currentCycle($cycleMax) }}</span>
                                    </div>
                                    <div class="relative h-1.5 w-full rounded-full bg-border overflow-hidden">
                                        <div
                                            class="h-full rounded-full bg-amber-500 transition-all duration-300"
                                            style="width: {{ ($inCycle / $cycleMax) * 100 }}%"
                                        ></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-text">
                                {{ $member->valid_visits_count ?? $member->totalValidVisits() }}
                            </td>
                            <td class="px-4 py-3 text-text-muted">
                                @if ($lastVisit && $lastVisit->created_at)
                                    <div>{{ $lastVisit->created_at->format('d/m/Y') }}</div>
                                    <div class="text-[10px]">{{ $lastVisit->created_at->format('H.i') }} WIB</div>
                                @else
                                    <span class="text-text-muted">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.status
                                    :status="match($member->status) {
                                        'aktif' => 'terkonfirmasi',
                                        'diblokir' => 'dibatalkan',
                                        default => 'menunggu_pembayaran',
                                    }"
                                    :label="match($member->status) {
                                        'aktif' => 'Aktif',
                                        'diblokir' => 'Diblokir',
                                        default => 'Nonaktif',
                                    }"
                                />
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a
                                        href="{{ route('admin.members.show', $member) }}"
                                        class="inline-flex min-h-[32px] items-center rounded-lg border border-border bg-surface px-2.5 py-1 text-[11px] font-semibold text-text hover:bg-surface-2 transition"
                                    >
                                        Detail
                                    </a>
                                    <a
                                        href="{{ route('admin.members.print_single', $member) }}"
                                        target="_blank"
                                        class="inline-flex min-h-[32px] items-center rounded-lg border border-border bg-surface px-2.5 py-1 text-[11px] font-semibold text-text hover:bg-surface-2 transition"
                                        title="Cetak Kartu"
                                    >
                                        Cetak
                                    </a>
                                    <a
                                        href="{{ route('admin.members.edit', $member) }}"
                                        class="inline-flex min-h-[32px] items-center rounded-lg border border-border bg-surface px-2.5 py-1 text-[11px] font-semibold text-text hover:bg-surface-2 transition"
                                    >
                                        Ubah
                                    </a>
                                    <button
                                        type="button"
                                        @click="openDelete({{ $member->id }}, '{{ addslashes($member->name) }}')"
                                        class="inline-flex min-h-[32px] items-center rounded-lg border border-red-500/30 bg-red-500/5 px-2.5 py-1 text-[11px] font-semibold text-red-500 hover:bg-red-500/10 transition"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-text-muted">
                                <p class="text-sm font-semibold">Belum ada data member yang sesuai.</p>
                                <p class="text-xs mt-1">Daftarkan member baru untuk memulai program loyalitas kunjungan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($members->hasPages())
            <div class="border-t border-border p-4">
                {{ $members->links() }}
            </div>
        @endif
    </div>

    {{-- Modal In-App Hapus Member (Soft Delete dengan Alasan Wajib) --}}
    <div
        x-show="deleteModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
        style="display: none;"
    >
        <div
            @click.away="deleteModalOpen = false"
            class="w-full max-w-md rounded-2xl border border-border bg-surface p-6 shadow-2xl space-y-4"
        >
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-base font-bold text-text">Hapus Member</h3>
                    <p class="text-xs text-text-muted mt-1">
                        Member <strong class="text-text" x-text="deleteMemberName"></strong> akan dihapus secara soft-delete.
                    </p>
                </div>
                <button
                    type="button"
                    @click="deleteModalOpen = false"
                    class="rounded-lg p-1 text-text-muted hover:text-text hover:bg-surface-2"
                >
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form :action="'{{ url('/admin/members') }}/' + deleteMemberId" method="POST" class="space-y-4">
                @csrf
                @method('DELETE')

                <div>
                    <label for="delete_reason" class="block text-xs font-semibold text-text-muted mb-1.5">
                        Alasan Penghapusan <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="delete_reason"
                        name="reason"
                        rows="3"
                        required
                        placeholder="Tulis alasan penghapusan member untuk jejak audit..."
                        class="w-full rounded-xl border border-border bg-surface-2 p-3 text-xs text-text focus:border-red-500 focus:ring-1 focus:ring-red-500/30"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="deleteModalOpen = false"
                        class="min-h-[40px] rounded-xl border border-border bg-surface px-4 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="min-h-[40px] rounded-xl bg-red-600 px-4 py-2 text-xs font-bold text-white hover:bg-red-500 transition"
                    >
                        Ya, Hapus Member
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

