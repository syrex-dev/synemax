@extends('layouts.admin')

@section('title', 'Jadwal Nobar')

@section('content')
    <div x-data="{
        statusModal: false,
        actionUrl: '',
        currentStatus: 'aktif',
        currentReason: '',
        openStatusModal(url, status, reason) {
            this.actionUrl = url;
            this.currentStatus = status;
            this.currentReason = reason || '';
            this.statusModal = true;
            window.dispatchEvent(new CustomEvent('synemax-select-set', { detail: { name: 'status_acara', value: status } }));
        }
    }">
        {{-- Standard Admin Page Header --}}
        <x-admin.page-header
            eyebrow="Komunitas"
            title="Jadwal Nonton Bareng"
            subtitle="Kelola jadwal nobar sepak bola dan siaran langsung untuk komunitas bioskop."
        >
            <a href="{{ route('admin.nobar.create') }}" class="inline-flex min-h-[42px] items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition-colors">
                <span class="text-base leading-none">+</span>
                <span>Tambah Acara Nobar</span>
            </a>
        </x-admin.page-header>

        {{-- Filter Status Tabs --}}
        <div class="mt-6 flex flex-wrap gap-2">
            @php
                $tabs = [
                    '' => 'Semua Acara',
                    'terbit' => 'Terbit',
                    'draft' => 'Draft',
                    'ditunda' => 'Ditunda',
                    'dibatalkan' => 'Dibatalkan',
                ];
            @endphp
            @foreach ($tabs as $key => $label)
                <a
                    href="{{ empty($key) ? route('admin.nobar.index') : route('admin.nobar.index', ['status' => $key]) }}"
                    class="inline-flex min-h-[38px] items-center rounded-xl px-4 text-xs font-bold transition-colors {{ (empty($key) && empty($currentFilter)) || ($currentFilter === $key) ? 'bg-amber-500 text-slate-950 shadow-xs' : 'border border-slate-300 dark:border-white/10 bg-surface text-text-muted hover:text-text' }}"
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>

        @if ($events->isEmpty())
            <div class="mt-6 rounded-2xl border border-border bg-surface shadow-token">
                <x-admin.empty-state
                    title="Belum ada acara nobar"
                    description="Tambahkan jadwal nobar pertandingan sepak bola untuk komunitas Anda."
                    actionUrl="{{ route('admin.nobar.create') }}"
                    actionLabel="Tambah Acara Nobar"
                />
            </div>
        @else
            {{-- DESKTOP TABLE VIEW (1024px and up) --}}
            <div class="mt-6 hidden lg:block admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="col-title-flex">Poster & Judul Acara</th>
                            <th class="col-nowrap">Kickoff (WIB)</th>
                            <th class="col-nowrap">Status Tampilan</th>
                            <th class="col-nowrap">Publikasi</th>
                            <th class="col-nowrap">Tiket</th>
                            <th class="col-actions-sticky">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $item)
                            <tr>
                                <td class="col-title-flex">
                                    <div class="flex items-center gap-3">
                                        @if ($item->hasPoster())
                                            <img src="{{ $item->posterUrl() }}" alt="{{ $item->posterAltText() }}" class="h-12 w-20 shrink-0 rounded-lg object-cover border border-border shadow-xs">
                                        @else
                                            <div class="flex h-12 w-20 shrink-0 items-center justify-center rounded-lg border border-border bg-surface-2 text-[10px] font-semibold text-text-muted uppercase text-center p-1 leading-tight">
                                                Tanpa poster
                                            </div>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <p class="font-bold text-text line-clamp-2 leading-snug" title="{{ $item->judul }}">{{ $item->judul }}</p>
                                            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                                @if ($item->is_pinned)
                                                    <span class="inline-flex items-center rounded-md bg-amber-500/15 px-1.5 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                                        Disematkan
                                                    </span>
                                                @endif
                                                <span class="text-xs text-text-muted truncate">
                                                    {{ $item->vsTitle() }} · <span class="font-semibold text-amber-600 dark:text-amber-400">{{ $item->kompetisi ?: 'Sepak Bola' }}</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="col-nowrap">
                                    <p class="font-bold text-text">{{ format_tanggal_id($item->kickoff_at) }}</p>
                                    <p class="text-xs text-amber-600 dark:text-amber-400 font-semibold">{{ format_jam_id($item->kickoff_at) }} ({{ $item->durasi_menit }} mnt)</p>
                                </td>
                                <td class="col-nowrap">
                                    <button type="button" @click="openStatusModal('{{ route('admin.nobar.status', ['nobar' => $item->id]) }}', '{{ $item->status_acara }}', '{{ addslashes($item->alasan_status ?? '') }}')" class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold transition hover:opacity-80 {{ $item->badgeClasses() }}" title="Klik untuk ubah status acara">
                                        <span>{{ $item->display_status }}</span>
                                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    @if ($item->status_acara !== 'aktif' && $item->alasan_status)
                                        <p class="mt-1 max-w-[180px] text-[11px] text-danger truncate" title="{{ $item->alasan_status }}">{{ $item->alasan_status }}</p>
                                    @endif
                                </td>
                                <td class="col-nowrap">
                                    @if ($item->status_publikasi === 'terbit')
                                        <span class="inline-flex items-center rounded-full bg-success/15 px-2.5 py-0.5 text-xs font-bold text-success border border-success/30">Terbit</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-200 dark:bg-white/10 px-2.5 py-0.5 text-xs font-semibold text-text-muted">Draft</span>
                                    @endif
                                </td>
                                <td class="col-nowrap">
                                    @if ($item->is_free)
                                        <span class="inline-flex items-center rounded-full bg-success/15 px-2.5 py-0.5 text-xs font-bold text-success border border-success/30">Gratis</span>
                                    @else
                                        <div>
                                            <span class="inline-flex items-center rounded-full bg-amber-500/15 px-2 py-0.5 text-xs font-bold text-amber-600 dark:text-amber-400 border border-amber-500/30">Berbayar</span>
                                            @if ($item->harga_info)
                                                <p class="mt-0.5 max-w-[160px] truncate text-[11px] text-text-muted" title="{{ $item->harga_info }}">{{ $item->harga_info }}</p>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="col-actions-sticky">
                                    <x-admin.action-menu
                                        editUrl="{{ route('admin.nobar.edit', $item->id) }}"
                                        viewUrl="{{ route('nobar.show', $item->slug) }}"
                                        duplicateUrl="{{ route('admin.nobar.duplicate', $item->id) }}"
                                        deleteUrl="{{ route('admin.nobar.destroy', $item->id) }}"
                                        deleteMessage="Hapus acara nobar ini? Poster dan data terkait akan dihapus permanen."
                                        itemName="nobar"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- MOBILE STACKED CARDS VIEW (< 1024px) --}}
            <div class="mt-6 grid gap-4 lg:hidden">
                @foreach ($events as $item)
                    <div class="rounded-2xl border border-border bg-surface p-4 shadow-token space-y-3">
                        <div class="flex items-start gap-3">
                            @if ($item->hasPoster())
                                <img src="{{ $item->posterUrl() }}" alt="{{ $item->posterAltText() }}" class="size-14 shrink-0 rounded-xl object-cover border border-border">
                            @else
                                <div class="flex size-14 shrink-0 items-center justify-center rounded-xl border border-border bg-surface-2 text-[10px] font-semibold text-text-muted uppercase text-center p-1 leading-tight">
                                    Tanpa poster
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-text leading-snug">{{ $item->judul }}</p>
                                <p class="text-xs text-text-muted mt-0.5">{{ $item->vsTitle() }} · <span class="font-semibold text-amber-600 dark:text-amber-400">{{ $item->kompetisi ?: 'Sepak Bola' }}</span></p>
                                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                    @if ($item->is_pinned)
                                        <span class="inline-flex items-center rounded-md bg-amber-500/15 px-1.5 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                            Disematkan
                                        </span>
                                    @endif
                                    @if ($item->status_publikasi === 'terbit')
                                        <span class="inline-flex items-center rounded-full bg-success/15 px-2 py-0.5 text-[11px] font-bold text-success border border-success/30">Terbit</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-200 dark:bg-white/10 px-2 py-0.5 text-[11px] font-semibold text-text-muted">Draft</span>
                                    @endif
                                    <button type="button" @click="openStatusModal('{{ route('admin.nobar.status', ['nobar' => $item->id]) }}', '{{ $item->status_acara }}', '{{ addslashes($item->alasan_status ?? '') }}')" class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-bold {{ $item->badgeClasses() }}">
                                        <span>{{ $item->display_status }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t border-border pt-3 text-xs">
                            <div>
                                <span class="font-semibold text-text">{{ format_tanggal_id($item->kickoff_at) }}</span>
                                <span class="text-text-muted">· {{ format_jam_id($item->kickoff_at) }}</span>
                            </div>
                            <x-admin.action-menu
                                editUrl="{{ route('admin.nobar.edit', $item->id) }}"
                                viewUrl="{{ route('nobar.show', $item->slug) }}"
                                duplicateUrl="{{ route('admin.nobar.duplicate', $item->id) }}"
                                deleteUrl="{{ route('admin.nobar.destroy', $item->id) }}"
                                deleteMessage="Hapus acara nobar ini? Poster dan data terkait akan dihapus permanen."
                                itemName="nobar"
                            />
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $events->links() }}
            </div>
        @endif

        {{-- Modal Ubah Status Acara (Tunda / Batalkan) --}}
        <div x-show="statusModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" @click.self="statusModal = false" @keydown.escape.window="statusModal = false">
            <div class="w-full max-w-md rounded-2xl border border-border bg-surface p-6 shadow-token">
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <h3 class="text-base font-bold text-text">Ubah Status Acara Nobar</h3>
                    <button type="button" @click="statusModal = false" class="inline-flex size-8 items-center justify-center rounded-lg text-text-muted hover:bg-surface-2" aria-label="Tutup">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form :action="actionUrl" method="POST" class="mt-4 space-y-4">
                    @csrf
                    @method('PATCH')

                    <div @change="if ($event.target.name === 'status_acara') currentStatus = $event.target.value">
                        <x-ui.select
                            id="modal_status_acara"
                            name="status_acara"
                            label="Status Acara"
                            value="aktif"
                            :options="[
                                'aktif' => 'Aktif (Sesuai Jadwal)',
                                'ditunda' => 'Ditunda',
                                'dibatalkan' => 'Dibatalkan',
                            ]"
                        />
                    </div>

                    <div x-show="currentStatus !== 'aktif'" x-cloak>
                        <label for="modal_alasan_status" class="block text-xs font-semibold text-text">Alasan / Keterangan untuk Publik</label>
                        <textarea id="modal_alasan_status" name="alasan_status" x-model="currentReason" rows="3" maxlength="255" placeholder="Contoh: Jadwal siaran ditunda penyelenggara ke pekan depan." class="mt-1.5 w-full rounded-xl border border-border bg-bg p-3 text-sm text-text"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-border">
                        <button type="button" @click="statusModal = false" class="min-h-10 rounded-xl border border-border px-4 text-xs font-semibold text-text hover:bg-surface-2">
                            Batal
                        </button>
                        <button type="submit" class="min-h-10 rounded-xl bg-amber-500 px-5 text-xs font-bold text-slate-950 hover:bg-amber-400">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
