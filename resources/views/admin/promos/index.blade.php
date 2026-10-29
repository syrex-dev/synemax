@extends('layouts.admin')

@section('title', 'Kelola Promo')

@section('content')
    <div x-data="{
        items: {{ Js::from($promos->items()) }},
        moveUp(index) {
            if (index <= 0) return;
            const temp = this.items[index];
            this.items[index] = this.items[index - 1];
            this.items[index - 1] = temp;
            this.saveOrder();
        },
        moveDown(index) {
            if (index >= this.items.length - 1) return;
            const temp = this.items[index];
            this.items[index] = this.items[index + 1];
            this.items[index + 1] = temp;
            this.saveOrder();
        },
        saveOrder() {
            const form = document.getElementById('promoReorderForm');
            const orderInput = document.getElementById('promoOrderData');
            orderInput.value = JSON.stringify(this.items.map(i => i.id));
            form.submit();
        }
    }">
        <x-admin.page-header
            eyebrow="Penawaran Khusus"
            title="Promo & Penawaran"
            subtitle="Promo dikelola murni lewat upload banner gambar 1200 × 600 px (tanpa teks bertumpuk). Setiap promo memiliki halaman detail sendiri."
        >
            <a href="{{ route('admin.promos.create') }}" class="inline-flex min-h-[42px] items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition-colors">
                <span class="text-base leading-none">+</span>
                <span>Tambah Promo Baru</span>
            </a>
        </x-admin.page-header>

        {{-- Hidden Form for Reordering --}}
        <form id="promoReorderForm" method="POST" action="{{ route('admin.promos.reorder') }}" class="hidden">
            @csrf
            <input type="hidden" id="promoOrderData" name="order[]">
        </form>

        {{-- Filters: Status --}}
        <div class="mt-6 flex flex-wrap gap-2">
            @php
                $statusTabs = [
                    '' => 'Semua Promo',
                    'aktif' => 'Aktif Berjalan',
                    'berakhir' => 'Sudah Berakhir',
                    'draft' => 'Draft / Nonaktif',
                ];
            @endphp
            @foreach ($statusTabs as $k => $lbl)
                <a
                    href="{{ empty($k) ? route('admin.promos.index') : route('admin.promos.index', ['status' => $k]) }}"
                    class="inline-flex min-h-[38px] items-center rounded-xl px-4 text-xs font-bold transition-colors {{ (empty($k) && empty($currentStatus)) || ($currentStatus === $k) ? 'bg-amber-500 text-slate-950 shadow-xs' : 'border border-slate-300 dark:border-white/10 bg-surface text-text-muted hover:text-text' }}"
                >
                    {{ $lbl }}
                </a>
            @endforeach
        </div>

        @if ($promos->isEmpty())
            <div class="mt-6 rounded-2xl border border-border bg-surface shadow-token">
                <x-admin.empty-state
                    title="Belum ada promo"
                    description="Buat promo menarik dengan mengunggah gambar banner 1200 × 600 px."
                    actionUrl="{{ route('admin.promos.create') }}"
                    actionLabel="Tambah Promo Baru"
                />
            </div>
        @else
            {{-- DESKTOP TABLE VIEW (1024px and up) --}}
            <div class="mt-6 hidden lg:block admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="w-20 text-center">Urutan</th>
                            <th class="col-title-flex">Banner & Judul Promo</th>
                            <th class="col-nowrap">Periode Berlaku</th>
                            <th class="col-nowrap">Status</th>
                            <th class="col-actions-sticky">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($promos as $index => $promo)
                            <tr>
                                <td class="text-center font-bold text-text-muted">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" @click="moveUp({{ $index }})" class="p-1 hover:text-amber-500" title="Geser ke atas">▲</button>
                                        <span class="text-xs">{{ $promo->sort_order ?: ($index + 1) }}</span>
                                        <button type="button" @click="moveDown({{ $index }})" class="p-1 hover:text-amber-500" title="Geser ke bawah">▼</button>
                                    </div>
                                </td>
                                <td class="col-title-flex">
                                    <div class="flex items-center gap-3">
                                        @if ($promo->imageUrl())
                                            <img src="{{ $promo->imageUrl() }}" alt="{{ $promo->title }}" class="h-12 w-24 shrink-0 rounded-lg object-cover border border-border shadow-xs">
                                        @else
                                            <div class="flex h-12 w-24 shrink-0 items-center justify-center rounded-lg border border-border bg-surface-2 text-[10px] font-semibold text-text-muted uppercase text-center p-1 leading-tight">
                                                Tanpa poster
                                            </div>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <a href="{{ route('admin.promos.edit', $promo) }}" class="font-bold text-text hover:text-amber-500 line-clamp-1 leading-snug" title="{{ $promo->title }}">
                                                {{ $promo->title }}
                                            </a>
                                            <p class="mt-0.5 truncate font-mono text-[11px] text-text-muted">/promo/{{ $promo->slug }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="col-nowrap text-xs">
                                    <p class="font-semibold text-text">{{ $promo->periodFormatted() }}</p>
                                    @if ($promo->isExpired())
                                        <span class="text-danger font-bold text-[10px]">Telah berakhir</span>
                                    @endif
                                </td>
                                <td class="col-nowrap">
                                    <form method="POST" action="{{ route('admin.promos.status', $promo) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="submit"
                                            title="Klik untuk ganti status aktif"
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold transition {{ $promo->is_active ? 'bg-success/15 text-success border border-success/30' : 'bg-slate-200 dark:bg-white/10 text-text-muted' }}"
                                        >
                                            <span class="size-1.5 rounded-full {{ $promo->is_active ? 'bg-success' : 'bg-slate-400' }}"></span>
                                            <span>{{ $promo->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                        </button>
                                    </form>
                                </td>
                                <td class="col-actions-sticky">
                                    <x-admin.action-menu
                                        editUrl="{{ route('admin.promos.edit', $promo) }}"
                                        viewUrl="{{ url('/promo/' . $promo->slug) }}"
                                        duplicateUrl="{{ route('admin.promos.duplicate', $promo) }}"
                                        deleteUrl="{{ route('admin.promos.destroy', $promo) }}"
                                        deleteMessage="Hapus promo '{{ addslashes($promo->title) }}'?"
                                        itemName="promo"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- MOBILE STACKED CARDS VIEW (< 1024px) --}}
            <div class="mt-6 grid gap-4 lg:hidden">
                @foreach ($promos as $promo)
                    <div class="rounded-2xl border border-border bg-surface p-4 shadow-token space-y-3">
                        <div class="flex items-start gap-3">
                            @if ($promo->imageUrl())
                                <img src="{{ $promo->imageUrl() }}" alt="{{ $promo->title }}" class="h-16 w-28 shrink-0 rounded-xl object-cover border border-border">
                            @else
                                <div class="flex h-16 w-28 shrink-0 items-center justify-center rounded-xl border border-border bg-surface-2 text-[10px] font-semibold text-text-muted uppercase text-center p-1 leading-tight">
                                    Tanpa poster
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-text leading-snug">{{ $promo->title }}</p>
                                <p class="text-xs text-text-muted mt-0.5">{{ $promo->periodFormatted() }}</p>
                                <div class="mt-2 flex items-center gap-2">
                                    <form method="POST" action="{{ route('admin.promos.status', $promo) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-bold {{ $promo->is_active ? 'bg-success/15 text-success' : 'bg-slate-200 dark:bg-white/10 text-text-muted' }}">
                                            {{ $promo->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </button>
                                    </form>
                                    @if ($promo->isExpired())
                                        <span class="rounded-full bg-danger/15 px-2 py-0.5 text-[10px] font-bold text-danger">Berakhir</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t border-border pt-3 text-xs">
                            <span class="text-text-muted font-mono truncate max-w-[150px]">/promo/{{ $promo->slug }}</span>
                            <x-admin.action-menu
                                editUrl="{{ route('admin.promos.edit', $promo) }}"
                                viewUrl="{{ url('/promo/' . $promo->slug) }}"
                                duplicateUrl="{{ route('admin.promos.duplicate', $promo) }}"
                                deleteUrl="{{ route('admin.promos.destroy', $promo) }}"
                                deleteMessage="Hapus promo '{{ addslashes($promo->title) }}'?"
                                itemName="promo"
                            />
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $promos->links() }}
            </div>
        @endif
    </div>
@endsection
