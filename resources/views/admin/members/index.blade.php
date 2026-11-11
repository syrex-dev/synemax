@extends('layouts.admin')

@section('title', 'Program Member - Panel Admin SynemaX')

@section('content')
<div class="space-y-6" x-data="{
    selectedIds: [],
    selectAll: false,
    deleteModalOpen: false,
    deleteMemberId: null,
    deleteMemberName: '',
    searchQuery: {{ Js::from($search) }},
    selectedStatus: {{ Js::from($selectedStatus ?? '') }},
    selectedCardStatus: {{ Js::from($selectedCardStatus ?? '') }},
    cardIssuedCount: {{ (int) ($stats['card_issued'] ?? 0) }},
    cardMissingCount: {{ (int) ($stats['card_missing'] ?? 0) }},
    bulkUpdatingCard: false,
    isSearching: false,
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
    },
    setCardFilter(val, toggle = false) {
        if (toggle && this.selectedCardStatus === val) {
            this.selectedCardStatus = '';
        } else {
            this.selectedCardStatus = val;
        }
        this.performSearch();
    },
    notifyToast(type, message) {
        if (typeof window.showToast === 'function') {
            window.showToast({ type, message });
        }
    },
    setCardCounts(issued, missing) {
        if (typeof issued === 'number') {
            this.cardIssuedCount = issued;
        }
        if (typeof missing === 'number') {
            this.cardMissingCount = missing;
        }
    },
    async bulkSetPhysicalCard(issued) {
        if (this.selectedIds.length === 0 || this.bulkUpdatingCard) return;
        this.bulkUpdatingCard = true;
        const csrfToken = document.querySelector('meta[name=csrf-token]') ? document.querySelector('meta[name=csrf-token]').content : '';
        try {
            const response = await fetch('{{ route('admin.members.bulk_physical_card') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    ids: this.selectedIds.map(Number),
                    has_physical_card: issued,
                }),
            });
            const data = await response.json();
            if (response.ok && data.status === 'ok') {
                if (data.stats) {
                    this.cardIssuedCount = data.stats.card_issued;
                    this.cardMissingCount = data.stats.card_missing;
                }
                this.notifyToast('success', data.message || 'Status kartu fisik berhasil diperbarui.');
                await this.performSearch();
            } else {
                this.notifyToast('error', data.message || 'Gagal memperbarui status kartu fisik.');
            }
        } catch (err) {
            this.notifyToast('error', 'Koneksi terputus saat memperbarui status kartu.');
        } finally {
            this.bulkUpdatingCard = false;
        }
    },
    async performSearch() {
        this.isSearching = true;
        const url = new URL('{{ route('admin.members.index') }}', window.location.origin);
        const trimmed = (this.searchQuery || '').trim();
        if (trimmed.length > 0) {
            url.searchParams.set('q', trimmed);
        }
        if (this.selectedStatus) {
            url.searchParams.set('status', this.selectedStatus);
        }
        if (this.selectedCardStatus) {
            url.searchParams.set('card_status', this.selectedCardStatus);
        }
        url.searchParams.delete('page');

        window.history.replaceState({}, '', url.toString());

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const newContainer = doc.getElementById('members-table-container');
            const currentContainer = document.getElementById('members-table-container');

            if (newContainer && currentContainer) {
                currentContainer.innerHTML = newContainer.innerHTML;
                this.selectedIds = [];
                this.selectAll = false;

                if (window.Alpine && window.Alpine.initTree) {
                    window.Alpine.initTree(currentContainer);
                }
            }
        } catch (err) {
            console.error('Member search error:', err);
        } finally {
            this.isSearching = false;
        }
    },
    clearSearch() {
        this.searchQuery = '';
        this.performSearch();
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
                href="{{ route('admin.members.scan') }}"
                class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-amber-500/50 bg-amber-500/10 px-4 py-2 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 transition shadow-2xs"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
                <span>Buka Scan Member</span>
            </a>
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

    {{-- Statistik Ringkas & Filter Cepat Kartu Fisik --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="rounded-2xl border border-border bg-surface p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-text-muted">Total Member</p>
            <p class="text-xl font-extrabold text-text mt-1">{{ number_format($stats['total'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-surface p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-text-muted">Member Aktif</p>
            <p class="text-xl font-extrabold text-emerald-500 mt-1">{{ number_format($stats['aktif'], 0, ',', '.') }}</p>
        </div>
        <button
            type="button"
            @click="setCardFilter('sudah', true)"
            class="text-left rounded-2xl border p-4 transition"
            :class="selectedCardStatus === 'sudah' ? 'border-emerald-500 bg-emerald-500/10 ring-1 ring-emerald-500/30' : 'border-border bg-surface hover:bg-surface-2/70'"
            title="Klik untuk memfilter member yang sudah punya kartu fisik"
        >
            <p class="text-[11px] font-semibold uppercase tracking-wider text-text-muted">Punya Kartu Fisik</p>
            <p class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1" x-text="Number(cardIssuedCount).toLocaleString('id-ID')">{{ number_format($stats['card_issued'] ?? 0, 0, ',', '.') }}</p>
        </button>
        <button
            type="button"
            @click="setCardFilter('belum', true)"
            class="text-left rounded-2xl border p-4 transition"
            :class="selectedCardStatus === 'belum' ? 'border-amber-500 bg-amber-500/10 ring-1 ring-amber-500/30' : 'border-border bg-surface hover:bg-surface-2/70'"
            title="Klik untuk memfilter member yang belum dibuatkan kartu fisik"
        >
            <p class="text-[11px] font-semibold uppercase tracking-wider text-text-muted">Belum Ada Kartu</p>
            <p class="text-xl font-extrabold text-amber-600 dark:text-amber-400 mt-1" x-text="Number(cardMissingCount).toLocaleString('id-ID')">{{ number_format($stats['card_missing'] ?? 0, 0, ',', '.') }}</p>
        </button>
        <div class="rounded-2xl border border-border bg-surface p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-text-muted">Nonaktif / Diblokir</p>
            <p class="text-xl font-extrabold text-text-muted mt-1">{{ number_format($stats['nonaktif'] + $stats['diblokir'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-surface p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-text-muted">Reward Pending</p>
            <p class="text-xl font-extrabold text-amber-500 mt-1">{{ number_format($stats['pending_rewards'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Filter & Pencarian Live Auto-Search --}}
    <div class="rounded-2xl border border-border bg-surface p-4 space-y-4">
        <form
            method="GET"
            action="{{ route('admin.members.index') }}"
            @submit.prevent="performSearch()"
            class="flex flex-col sm:flex-row gap-3"
        >
            <div class="relative flex-1">
                <input
                    type="text"
                    name="q"
                    x-model="searchQuery"
                    @input.debounce.300ms="performSearch()"
                    placeholder="Cari nama member atau ID (misal: 4 karakter terakhir)..."
                    autocomplete="off"
                    class="w-full min-h-[44px] rounded-xl border border-border bg-surface-2 pl-10 pr-9 text-xs text-text placeholder:text-text-muted focus:border-amber-500 focus:ring-1 focus:ring-amber-500/30 transition-colors"
                >
                {{-- Icon Search / Spinner Loading --}}
                <div class="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none text-text-muted">
                    <svg x-show="!isSearching" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <svg x-show="isSearching" x-cloak class="size-4 animate-spin text-amber-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                {{-- Tombol Clear X --}}
                <button
                    type="button"
                    x-show="searchQuery && searchQuery.length > 0"
                    x-cloak
                    @click="clearSearch()"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted hover:text-text p-1 rounded-full hover:bg-surface transition"
                    title="Hapus pencarian"
                >
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="w-full sm:w-48">
                <x-ui.select
                    name="status"
                    :value="$selectedStatus"
                    @synemax-select-changed="selectedStatus = $event.detail.value; performSearch()"
                    class="w-full min-h-[44px] text-xs"
                >
                    <option value="">Semua Status</option>
                    <option value="aktif" {{ $selectedStatus === 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="nonaktif" {{ $selectedStatus === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                    <option value="diblokir" {{ $selectedStatus === 'diblokir' ? 'selected' : '' }}>Diblokir</option>
                </x-ui.select>
            </div>

            <input type="hidden" name="card_status" :value="selectedCardStatus">

            <button
                type="submit"
                class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-amber-500 px-5 text-xs font-bold text-black hover:bg-amber-400 transition"
            >
                Cari
            </button>

            <a
                href="{{ route('admin.members.index') }}"
                x-show="(searchQuery && searchQuery.length > 0) || (selectedStatus && selectedStatus.length > 0) || (selectedCardStatus && selectedCardStatus.length > 0)"
                x-cloak
                class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-border bg-surface px-4 text-xs font-semibold text-text hover:bg-surface-2 transition"
            >
                Reset
            </a>
        </form>

        {{-- Filter Cepat Status Kartu Fisik --}}
        <div class="flex flex-wrap items-center justify-between gap-3 pt-1 border-t border-border/60">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[11px] font-semibold text-text-muted mr-1">Filter Kartu Fisik:</span>
                <button
                    type="button"
                    @click="setCardFilter('')"
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-semibold border transition"
                    :class="!selectedCardStatus ? 'border-amber-500 bg-amber-500 text-black' : 'border-border bg-surface-2 text-text-muted hover:text-text'"
                >
                    <span>Semua</span>
                </button>
                <button
                    type="button"
                    @click="setCardFilter('belum')"
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-semibold border transition"
                    :class="selectedCardStatus === 'belum' ? 'border-amber-500 bg-amber-500/20 text-amber-700 dark:text-amber-300 ring-1 ring-amber-500/40' : 'border-border bg-surface-2 text-text-muted hover:text-text'"
                >
                    <span class="size-2 rounded-full bg-amber-500"></span>
                    <span>Belum Dibuatkan</span>
                    <span class="font-bold" x-text="'(' + cardMissingCount + ')'">({{ $stats['card_missing'] ?? 0 }})</span>
                </button>
                <button
                    type="button"
                    @click="setCardFilter('sudah')"
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-semibold border transition"
                    :class="selectedCardStatus === 'sudah' ? 'border-emerald-500 bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-500/40' : 'border-border bg-surface-2 text-text-muted hover:text-text'"
                >
                    <span class="size-2 rounded-full bg-emerald-500"></span>
                    <span>Sudah Punya Kartu</span>
                    <span class="font-bold" x-text="'(' + cardIssuedCount + ')'">({{ $stats['card_issued'] ?? 0 }})</span>
                </button>
            </div>
            <p class="text-[11px] text-text-muted">Klik lencana pada kolom <strong>Kartu Fisik</strong> untuk mengubah status dengan cepat.</p>
        </div>
    </div>

    {{-- Bar Aksi Massal Saat Baris Dipilih --}}
    <div
        x-show="selectedIds.length > 0"
        x-cloak
        class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-xs"
    >
        <div class="flex items-center gap-2 font-semibold text-text">
            <span class="inline-flex items-center justify-center rounded-full bg-amber-500 px-2.5 py-0.5 text-[11px] font-bold text-black" x-text="selectedIds.length"></span>
            <span>member dipilih</span>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                @click="bulkSetPhysicalCard(true)"
                :disabled="bulkUpdatingCard"
                class="inline-flex min-h-[36px] items-center gap-1.5 rounded-xl border border-emerald-500/40 bg-emerald-500/15 px-3.5 py-1.5 text-xs font-bold text-emerald-700 dark:text-emerald-300 hover:bg-emerald-500/25 disabled:opacity-50 transition"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                <span>Tandai Sudah Punya Kartu</span>
            </button>
            <button
                type="button"
                @click="bulkSetPhysicalCard(false)"
                :disabled="bulkUpdatingCard"
                class="inline-flex min-h-[36px] items-center gap-1.5 rounded-xl border border-border bg-surface px-3.5 py-1.5 text-xs font-semibold text-text hover:bg-surface-2 disabled:opacity-50 transition"
            >
                <span>Tandai Belum Ada Kartu</span>
            </button>
            <button
                type="button"
                @click="printSelected()"
                class="inline-flex min-h-[36px] items-center gap-1.5 rounded-xl bg-amber-500 px-3.5 py-1.5 text-xs font-bold text-black hover:bg-amber-400 transition"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak Kartu Terpilih</span>
            </button>
        </div>
    </div>

    {{-- Tabel Member --}}
    <div
        id="members-table-container"
        class="rounded-2xl border border-border bg-surface shadow-sm overflow-hidden transition-opacity duration-150"
        :class="{ 'opacity-60 pointer-events-none': isSearching }"
    >
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
                        <th class="px-4 py-3.5">Kartu Fisik</th>
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
                            <td
                                class="px-4 py-3"
                                x-data="{
                                    hasCard: {{ $member->hasPhysicalCard() ? 'true' : 'false' }},
                                    printedAt: {{ Js::from($member->physical_card_printed_at?->format('d/m/Y')) }},
                                    toggling: false,
                                    async toggle() {
                                        if (this.toggling) return;
                                        this.toggling = true;
                                        const csrfToken = document.querySelector('meta[name=csrf-token]') ? document.querySelector('meta[name=csrf-token]').content : '';
                                        try {
                                            const res = await fetch('{{ route('admin.members.toggle_physical_card', $member) }}', {
                                                method: 'PATCH',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'Accept': 'application/json',
                                                    'X-CSRF-TOKEN': csrfToken,
                                                    'X-Requested-With': 'XMLHttpRequest',
                                                },
                                                body: JSON.stringify({ has_physical_card: !this.hasCard }),
                                            });
                                            const data = await res.json();
                                            if (res.ok && data.status === 'ok') {
                                                this.hasCard = Boolean(data.has_physical_card);
                                                this.printedAt = data.printed_at ? String(data.printed_at).split(' ')[0] : null;
                                                if (data.stats) {
                                                    setCardCounts(data.stats.card_issued, data.stats.card_missing);
                                                }
                                                notifyToast('success', data.message);
                                            } else {
                                                notifyToast('error', data.message || 'Gagal memperbarui status kartu.');
                                            }
                                        } catch (err) {
                                            notifyToast('error', 'Koneksi terputus saat mengubah status kartu.');
                                        } finally {
                                            this.toggling = false;
                                        }
                                    }
                                }"
                            >
                                <button
                                    type="button"
                                    @click="toggle()"
                                    :disabled="toggling"
                                    :title="hasCard ? 'Kartu fisik sudah dibuat. Klik untuk ubah ke Belum Dibuatkan.' : 'Kartu fisik belum dibuatkan. Klik untuk tandai Sudah Ada.'"
                                    class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-bold transition disabled:opacity-60"
                                    :class="hasCard
                                        ? 'border-emerald-500/35 bg-emerald-500/12 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-500/20'
                                        : 'border-amber-500/45 bg-amber-500/12 text-amber-700 dark:text-amber-300 hover:bg-amber-500/20'"
                                >
                                    <svg x-show="hasCard" class="size-3.5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    <svg x-show="!hasCard" x-cloak class="size-3.5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                    </svg>
                                    <span x-text="hasCard ? 'Sudah Ada' : 'Belum Ada'">{{ $member->hasPhysicalCard() ? 'Sudah Ada' : 'Belum Ada' }}</span>
                                </button>
                                <div x-show="hasCard && printedAt" x-cloak class="mt-0.5 pl-1 text-[10px] text-text-muted" x-text="'Tercatat ' + printedAt">{{ $member->physical_card_printed_at ? 'Tercatat '.$member->physical_card_printed_at->format('d/m/Y') : '' }}</div>
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
                                {{ $member->totalVisits() }}
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
                                >{{ match($member->status) {
                                    'aktif' => 'Aktif',
                                    'diblokir' => 'Diblokir',
                                    default => 'Nonaktif',
                                } }}</x-ui.status>
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
                            <td colspan="9" class="px-6 py-12 text-center text-text-muted">
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

