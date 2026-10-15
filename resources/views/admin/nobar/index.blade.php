@extends('layouts.admin')

@section('title', 'Kelola Nobar Sepak Bola')

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
        }
    }">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-accent">Komunitas</p>
                <h1 class="mt-1 text-3xl font-black text-text">Jadwal Nonton Bareng</h1>
            </div>
            <a href="{{ route('admin.nobar.create') }}" class="inline-flex min-h-12 items-center gap-2 rounded-xl bg-accent px-5 font-bold text-accent-contrast shadow-sm hover:opacity-90">
                <span class="text-lg">+</span>
                <span>Tambah Acara Nobar</span>
            </a>
        </div>

        {{-- Filter Status --}}
        <div class="mt-6 flex flex-wrap gap-2">
            <a href="{{ route('admin.nobar.index') }}" class="inline-flex min-h-10 items-center rounded-xl px-4 text-xs font-bold transition {{ empty($currentFilter) ? 'bg-accent text-accent-contrast shadow-xs' : 'border border-border bg-surface text-text-muted hover:text-text' }}">
                Semua Acara
            </a>
            <a href="{{ route('admin.nobar.index', ['status' => 'terbit']) }}" class="inline-flex min-h-10 items-center rounded-xl px-4 text-xs font-bold transition {{ $currentFilter === 'terbit' ? 'bg-accent text-accent-contrast shadow-xs' : 'border border-border bg-surface text-text-muted hover:text-text' }}">
                Terbit
            </a>
            <a href="{{ route('admin.nobar.index', ['status' => 'draft']) }}" class="inline-flex min-h-10 items-center rounded-xl px-4 text-xs font-bold transition {{ $currentFilter === 'draft' ? 'bg-accent text-accent-contrast shadow-xs' : 'border border-border bg-surface text-text-muted hover:text-text' }}">
                Draft
            </a>
            <a href="{{ route('admin.nobar.index', ['status' => 'ditunda']) }}" class="inline-flex min-h-10 items-center rounded-xl px-4 text-xs font-bold transition {{ $currentFilter === 'ditunda' ? 'bg-accent text-accent-contrast shadow-xs' : 'border border-border bg-surface text-text-muted hover:text-text' }}">
                Ditunda
            </a>
            <a href="{{ route('admin.nobar.index', ['status' => 'dibatalkan']) }}" class="inline-flex min-h-10 items-center rounded-xl px-4 text-xs font-bold transition {{ $currentFilter === 'dibatalkan' ? 'bg-accent text-accent-contrast shadow-xs' : 'border border-border bg-surface text-text-muted hover:text-text' }}">
                Dibatalkan
            </a>
        </div>

        {{-- Table Card --}}
        <div class="mt-6 overflow-hidden rounded-2xl border border-border bg-surface shadow-token">
            @if ($events->isEmpty())
                <div class="p-12 text-center text-text-muted">
                    <svg class="mx-auto size-12 text-text-muted/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <p class="mt-3 text-base font-bold text-text">Belum ada acara nobar</p>
                    <p class="mt-1 text-xs">Tambahkan jadwal nobar pertandingan sepak bola untuk komunitas Anda.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-border bg-surface-2 text-xs uppercase tracking-wider text-text-muted">
                            <tr>
                                <th class="px-4 py-3.5">Poster & Judul Acara</th>
                                <th class="px-4 py-3.5">Kickoff (WIB)</th>
                                <th class="px-4 py-3.5">Status Tampilan</th>
                                <th class="px-4 py-3.5">Publikasi</th>
                                <th class="px-4 py-3.5">Tiket</th>
                                <th class="px-4 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($events as $item)
                                <tr class="transition hover:bg-surface-2/50">
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-3">
                                            @if ($item->hasPoster())
                                                <img src="{{ $item->posterUrl() }}" alt="{{ $item->posterAltText() }}" class="size-12 shrink-0 rounded-lg object-cover border border-border">
                                            @else
                                                <div class="flex size-12 shrink-0 items-center justify-center rounded-lg border border-border bg-surface-2 text-[10px] font-semibold text-text-muted uppercase text-center p-1 leading-tight">
                                                    Tanpa Poster
                                                </div>
                                            @endif
                                            <div>
                                                <div class="flex items-center gap-1.5">
                                                    <span class="font-bold text-text">{{ $item->judul }}</span>
                                                    @if ($item->is_pinned)
                                                        <span class="inline-flex items-center gap-1 rounded bg-accent/10 px-1.5 py-0.5 text-[10px] font-semibold text-accent" title="Disematkan di atas">
                                                            Semat
                                                        </span>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-text-muted">
                                                    {{ $item->vsTitle() }} · <span class="text-accent">{{ $item->kompetisi ?: 'Sepak Bola' }}</span>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <p class="font-bold text-text">{{ $item->kickoff_at->translatedFormat('d M Y') }}</p>
                                        <p class="text-xs text-accent font-semibold">{{ $item->kickoff_at->format('H:i') }} WIB ({{ $item->durasi_menit }} mnt)</p>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <button type="button" @click="openStatusModal('{{ route('admin.nobar.status', ['nobar' => $item->id]) }}', '{{ $item->status_acara }}', '{{ addslashes($item->alasan_status ?? '') }}')" class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold transition hover:opacity-80 {{ $item->badgeClasses() }}" title="Klik untuk ubah status acara (Tunda/Batalkan)">
                                            <span>{{ $item->display_status }}</span>
                                            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        @if ($item->status_acara !== 'aktif' && $item->alasan_status)
                                            <p class="mt-1 max-w-xs text-[11px] text-danger truncate" title="{{ $item->alasan_status }}">{{ $item->alasan_status }}</p>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        @if ($item->status_publikasi === 'terbit')
                                            <span class="rounded-full bg-success/10 px-2.5 py-1 text-xs font-bold text-success">Terbit</span>
                                        @else
                                            <span class="rounded-full bg-surface-2 px-2.5 py-1 text-xs font-bold text-text-muted">Draft</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        @if ($item->is_free)
                                            <span class="text-xs font-bold text-success">Gratis</span>
                                        @else
                                            <span class="text-xs font-medium text-text">{{ $item->harga_info ?: 'Berbayar' }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ route('nobar.show', ['slug' => $item->slug]) }}" target="_blank" class="rounded-lg border border-border px-2.5 py-1.5 text-xs font-semibold text-text hover:border-accent" title="Lihat Tampilan Publik">
                                                Lihat
                                            </a>
                                            <form method="POST" action="{{ route('admin.nobar.duplicate', ['nobar' => $item->id]) }}" onsubmit="return confirm('Duplikat acara nobar ini sebagai draft baru?');" class="inline">
                                                @csrf
                                                <button type="submit" class="rounded-lg border border-border px-2.5 py-1.5 text-xs font-semibold text-text hover:border-accent" title="Duplikat Acara">
                                                    Duplikat
                                                </button>
                                            </form>
                                            <a href="{{ route('admin.nobar.edit', ['nobar' => $item->id]) }}" class="rounded-lg border border-border px-2.5 py-1.5 text-xs font-semibold text-text hover:border-accent" title="Edit Acara">
                                                Edit
                                            </a>
                                            <form method="POST" action="{{ route('admin.nobar.destroy', ['nobar' => $item->id]) }}" onsubmit="return confirm('Hapus acara nobar ini? Poster dan data terkait akan dihapus permanen.');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg border border-danger/40 px-2.5 py-1.5 text-xs font-semibold text-danger hover:bg-danger/10" title="Hapus">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-border">
                    {{ $events->links() }}
                </div>
            @endif
        </div>

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

                    <div>
                        <label for="modal_status_acara" class="block text-xs font-semibold text-text">Status Acara</label>
                        <select id="modal_status_acara" name="status_acara" x-model="currentStatus" class="mt-1.5 min-h-11 w-full rounded-xl border border-border bg-bg px-3 text-sm text-text">
                            <option value="aktif">Aktif (Sesuai Jadwal)</option>
                            <option value="ditunda">Ditunda</option>
                            <option value="dibatalkan">Dibatalkan</option>
                        </select>
                    </div>

                    <div x-show="currentStatus !== 'aktif'" x-cloak>
                        <label for="modal_alasan_status" class="block text-xs font-semibold text-text">Alasan / Keterangan untuk Publik</label>
                        <textarea id="modal_alasan_status" name="alasan_status" x-model="currentReason" rows="3" maxlength="255" placeholder="Contoh: Jadwal siaran ditunda penyelenggara ke pekan depan." class="mt-1.5 w-full rounded-xl border border-border bg-bg p-3 text-sm text-text"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-border">
                        <button type="button" @click="statusModal = false" class="min-h-10 rounded-xl border border-border px-4 text-xs font-semibold text-text hover:bg-surface-2">
                            Batal
                        </button>
                        <button type="submit" class="min-h-10 rounded-xl bg-accent px-5 text-xs font-bold text-accent-contrast hover:opacity-90">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
