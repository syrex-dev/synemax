@extends('layouts.admin')

@section('title', 'Siapkan pesanan')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-3 print:hidden">
        <div><h1 class="text-3xl font-black">Siapkan pesanan</h1><p class="mt-2 text-sm text-text-muted">{{ $session->startsAt()->locale('id')->translatedFormat('l, d F Y') }} · {{ substr((string) $session->start_time, 0, 5) }} WIB · {{ $session->film?->title ?? 'Film' }}</p></div>
        <button type="button" onclick="window.print()" class="min-h-11 rounded-lg border border-border px-4 text-sm font-semibold hover:bg-surface-2">Cetak daftar</button>
    </div>
    <section class="mt-6 rounded-lg border border-border bg-surface p-5 print:break-inside-avoid">
        <h2 class="font-bold">Jumlah yang perlu disiapkan</h2>
        <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($aggregates as $name => $aggregate)
                <p class="rounded-lg border border-border bg-surface-2 p-4 text-base font-bold print:bg-surface print:text-text"><span class="mr-2 inline-flex min-w-10 items-center justify-center rounded-md bg-surface px-2 py-1 font-mono">{{ $aggregate['qty'] }}×</span>{{ $name }}</p>
            @empty
                <p class="text-sm text-text-muted">Belum ada jajan yang perlu disiapkan.</p>
            @endforelse
        </div>
    </section>
    <form method="POST" action="{{ route('admin.snacks.preparation.status', $session) }}" class="mt-5 flex flex-wrap items-end gap-3 rounded-lg border border-border bg-surface p-4 print:hidden">@csrf @method('PATCH')
        <div class="flex-1 min-w-[220px]">
            <x-ui.select
                id="session-status"
                name="status"
                label="Ubah status semua item aktif di sesi ini"
                value="baru"
                :options="[
                    'baru' => 'Baru',
                    'disiapkan' => 'Disiapkan',
                    'siap' => 'Siap',
                    'selesai' => 'Selesai',
                ]"
            />
        </div>
        <button class="min-h-11 rounded-lg bg-accent px-4 text-sm font-bold text-accent-contrast">Terapkan semua</button>
    </form>
    <section class="mt-7 space-y-4">
        <h2 class="font-bold">Rincian per booking</h2>
        @forelse ($session->bookings as $booking)
            @if ($booking->items->isNotEmpty())
                <article class="overflow-hidden rounded-lg border border-border bg-surface print:break-inside-avoid print:bg-surface print:text-text">
                    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-4 py-4">
                        <div class="min-w-0">
                            <h3 class="font-bold">{{ $booking->customer_name }}</h3>
                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                <code class="font-mono text-sm text-accent">{{ $booking->code }}</code>
                                <span x-data="{ copied: false, copyError: false }" class="print:hidden">
                                    <button type="button" @click="navigator.clipboard.writeText(@js($booking->code)).then(() => { copied = true; copyError = false; setTimeout(() => copied = false, 1800) }).catch(() => copyError = true)" aria-label="Salin kode booking {{ $booking->code }}" class="inline-flex min-h-9 items-center gap-1 rounded-md border border-border px-2 text-xs font-semibold text-text hover:bg-surface-2">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" class="size-4"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/></svg>
                                        <span x-text="copied ? 'Tersalin' : 'Salin kode'">Salin kode</span>
                                    </button>
                                    <span x-show="copyError" x-cloak role="status" class="ml-1 text-xs text-danger">Salin manual, ya.</span>
                                </span>
                            </div>
                        </div>
                        <a href="{{ route('admin.bookings.show', $booking) }}" class="inline-flex min-h-10 items-center gap-2 rounded-md px-2 text-sm font-semibold text-accent hover:bg-accent/10">Detail booking <span aria-hidden="true">→</span></a>
                    </header>
                    <div class="hidden xl:block">
                        <x-admin.table class="rounded-none">
                            <x-slot:columns>
                                <col style="width: 12%">
                                <col style="width: 42%">
                                <col style="width: 21%">
                                <col style="width: 25%">
                            </x-slot:columns>
                            <x-slot:head>
                                <th scope="col" class="px-4 py-3 font-semibold">Jumlah</th>
                                <th scope="col" class="px-4 py-3 font-semibold">Pesanan</th>
                                <th scope="col" class="px-4 py-3 font-semibold">Status pesanan</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold">Aksi</th>
                            </x-slot:head>
                                @foreach ($booking->items as $item)
                                    <tr x-data="{ initial: '{{ $item->status }}', status: '{{ $item->status }}', saving: false, get dirty() { return this.status !== this.initial }, labels: { baru: 'Baru', disiapkan: 'Disiapkan', siap: 'Siap', selesai: 'Selesai' }, tones: { baru: 'bg-warning-surface text-warning', disiapkan: 'bg-info-surface text-info', siap: 'bg-success-surface text-success', selesai: 'bg-surface-2 text-text-muted' } }" class="transition-colors hover:bg-surface-2/60">
                                        <td class="px-4 py-3"><span class="inline-flex min-w-12 items-center justify-center rounded-md bg-surface-2 px-2 py-1 font-mono font-bold">{{ $item->qty }}×</span></td>
                                        <td class="px-4 py-3"><p class="font-semibold text-text">{{ $item->name_snapshot }}</p>@if ($item->note)<p class="mt-1 text-sm text-text-muted">Catatan: {{ $item->note }}</p>@endif</td>
                                        <td class="px-4 py-3"><span class="inline-flex min-h-7 items-center rounded-full px-3 text-xs font-semibold" :class="tones[status]" x-text="labels[status]">{{ ucfirst($item->status) }}</span></td>
                                        <td class="px-4 py-3">
                                            <form method="POST" action="{{ route('admin.snacks.items.status', $item) }}" @submit="saving = true" @change="if ($event.target.name === 'status') status = $event.target.value">@csrf @method('PATCH')
                                                <div class="min-w-[130px]">
                                                    <x-ui.select
                                                        id="item-status-desktop-{{ $item->id }}"
                                                        name="status"
                                                        :value="$item->status"
                                                        :options="[
                                                            'baru' => 'Baru',
                                                            'disiapkan' => 'Disiapkan',
                                                            'siap' => 'Siap',
                                                            'selesai' => 'Selesai',
                                                        ]"
                                                    />
                                                </div>
                                                <span x-show="dirty" x-cloak class="mt-1 block text-right text-xs font-semibold text-warning">Belum disimpan</span>
                                                <button :disabled="!dirty || saving" class="mt-2 min-h-10 w-full rounded-lg bg-accent px-3 text-sm font-bold text-accent-contrast disabled:cursor-not-allowed disabled:opacity-45"><span x-show="!saving">Simpan</span><span x-show="saving" x-cloak>Menyimpan…</span></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                        </x-admin.table>
                    </div>
                    <div class="space-y-3 p-3 xl:hidden">
                        @foreach ($booking->items as $item)
                            <div x-data="{ initial: '{{ $item->status }}', status: '{{ $item->status }}', saving: false, get dirty() { return this.status !== this.initial }, labels: { baru: 'Baru', disiapkan: 'Disiapkan', siap: 'Siap', selesai: 'Selesai' }, tones: { baru: 'bg-warning-surface text-warning', disiapkan: 'bg-info-surface text-info', siap: 'bg-success-surface text-success', selesai: 'bg-surface-2 text-text-muted' } }" class="rounded-lg border border-border bg-bg p-4">
                                <div class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-3 gap-y-2">
                                    <span class="text-sm text-text-muted">Jumlah</span><span><span class="inline-flex min-w-12 items-center justify-center rounded-md bg-surface-2 px-2 py-1 font-mono font-bold">{{ $item->qty }}×</span></span>
                                    <span class="text-sm text-text-muted">Pesanan</span><span class="font-semibold text-text">{{ $item->name_snapshot }}</span>
                                    @if ($item->note)<span class="text-sm text-text-muted">Catatan</span><span class="text-sm text-text-muted">{{ $item->note }}</span>@endif
                                    <span class="text-sm text-text-muted">Status sekarang</span><span><span class="inline-flex min-h-7 items-center rounded-full px-3 text-xs font-semibold" :class="tones[status]" x-text="labels[status]">{{ ucfirst($item->status) }}</span></span>
                                </div>
                                <form method="POST" action="{{ route('admin.snacks.items.status', $item) }}" @submit="saving = true" class="mt-4 border-t border-border pt-3 print:hidden">@csrf @method('PATCH')
                                    <label for="item-status-mobile-{{ $item->id }}" class="mb-1 block text-sm font-semibold">Status pesanan</label>
                                    <div class="flex items-end gap-2" @change="if ($event.target.name === 'status') status = $event.target.value">
                                        <div class="flex-1 min-w-0">
                                            <x-ui.select
                                                id="item-status-mobile-{{ $item->id }}"
                                                name="status"
                                                :value="$item->status"
                                                :options="[
                                                    'baru' => 'Baru',
                                                    'disiapkan' => 'Disiapkan',
                                                    'siap' => 'Siap',
                                                    'selesai' => 'Selesai',
                                                ]"
                                            />
                                        </div>
                                        <button :disabled="!dirty || saving" class="min-h-11 rounded-lg bg-accent px-4 text-sm font-bold text-accent-contrast disabled:cursor-not-allowed disabled:opacity-45"><span x-show="!saving">Simpan</span><span x-show="saving" x-cloak>Menyimpan…</span></button>
                                    </div>
                                    <p x-show="dirty" x-cloak class="mt-2 text-xs font-semibold text-warning">Belum disimpan</p>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </article>
            @endif
        @empty
            <p class="rounded-xl border border-border bg-surface p-5 text-sm text-text-muted">Belum ada booking untuk sesi ini.</p>
        @endforelse
    </section>
@endsection
