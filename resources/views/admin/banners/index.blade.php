@extends('layouts.admin')

@section('title', 'Banner Hero')

@section('content')
    <div x-data="{
        reorderOpen: false,
        items: {{ Js::from($banners->items()) }},
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
            const form = document.getElementById('reorderForm');
            const orderInput = document.getElementById('orderData');
            orderInput.value = JSON.stringify(this.items.map(i => i.id));
            form.submit();
        }
    }">
        <x-admin.page-header
            eyebrow="Konten Visual"
            title="Slide Banner Hero"
            subtitle="Banner visual layar lebar beranda (1600 × 680 px) tanpa teks overlay dengan transisi geser halus."
        >
            <a href="{{ route('admin.banners.create') }}" class="inline-flex min-h-[42px] items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 shadow-sm hover:bg-amber-400 transition-colors">
                <span class="text-base leading-none">+</span>
                <span>Tambah Slide Hero</span>
            </a>
        </x-admin.page-header>

        {{-- Hidden Form for Reordering --}}
        <form id="reorderForm" method="POST" action="{{ route('admin.banners.reorder') }}" class="hidden">
            @csrf
            <input type="hidden" id="orderData" name="order[]">
        </form>

        @if ($banners->isEmpty())
            <div class="mt-6 rounded-2xl border border-border bg-surface shadow-token">
                <x-admin.empty-state
                    title="Belum ada slide banner hero"
                    description="Unggah banner visual berkualitas tinggi (1600 × 680 px) untuk memukau pengunjung di beranda."
                    actionUrl="{{ route('admin.banners.create') }}"
                    actionLabel="Tambah Slide Hero"
                />
            </div>
        @else
            {{-- DESKTOP TABLE VIEW (1024px and up) --}}
            <div class="mt-6 hidden lg:block admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="w-20 text-center">Urutan</th>
                            <th class="col-title-flex">Gambar & Judul Internal</th>
                            <th class="col-nowrap">Tujuan Tautan</th>
                            <th class="col-nowrap">Status Tampil</th>
                            <th class="col-actions-sticky">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($banners as $index => $banner)
                            <tr>
                                <td class="text-center font-bold text-text-muted">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" @click="moveUp({{ $index }})" class="p-1 hover:text-amber-500" title="Geser ke atas">▲</button>
                                        <span class="text-xs">{{ $banner->sort_order ?: ($index + 1) }}</span>
                                        <button type="button" @click="moveDown({{ $index }})" class="p-1 hover:text-amber-500" title="Geser ke bawah">▼</button>
                                    </div>
                                </td>
                                <td class="col-title-flex">
                                    <div class="flex items-center gap-3">
                                        @if ($banner->imageUrl())
                                            <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->title }}" class="h-12 w-28 shrink-0 rounded-lg object-cover border border-border shadow-xs">
                                        @else
                                            <div class="flex h-12 w-28 shrink-0 items-center justify-center rounded-lg border border-border bg-surface-2 text-[10px] font-semibold text-text-muted uppercase text-center p-1 leading-tight">
                                                Tanpa poster
                                            </div>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <p class="font-bold text-text line-clamp-1 leading-snug" title="{{ $banner->title }}">
                                                {{ $banner->title }}
                                            </p>
                                            <p class="text-[11px] text-text-muted">Alt text: {{ $banner->title }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="col-nowrap">
                                    @if ($banner->target_type === 'film')
                                        <span class="inline-flex items-center rounded-md bg-blue-500/15 px-2 py-0.5 text-xs font-semibold text-blue-500">Film: /film/{{ $banner->target_id }}</span>
                                    @elseif ($banner->target_type === 'promo')
                                        <span class="inline-flex items-center rounded-md bg-amber-500/15 px-2 py-0.5 text-xs font-semibold text-amber-500">Promo: /promo/{{ $banner->target_id }}</span>
                                    @elseif ($banner->target_type === 'nobar')
                                        <span class="inline-flex items-center rounded-md bg-emerald-500/15 px-2 py-0.5 text-xs font-semibold text-emerald-500">Nobar: /nobar/{{ $banner->target_id }}</span>
                                    @elseif ($banner->target_type === 'custom')
                                        <span class="inline-flex items-center rounded-md bg-purple-500/15 px-2 py-0.5 text-xs font-semibold text-purple-500 truncate max-w-[200px]" title="{{ $banner->cta_url }}">{{ $banner->cta_url }}</span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-slate-200 dark:bg-white/10 px-2 py-0.5 text-xs font-semibold text-text-muted">Tanpa Tautan</span>
                                    @endif
                                </td>
                                <td class="col-nowrap">
                                    <form method="POST" action="{{ route('admin.banners.status', $banner) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="submit"
                                            title="Klik untuk ganti status aktif"
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold transition {{ $banner->is_active ? 'bg-success/15 text-success border border-success/30' : 'bg-slate-200 dark:bg-white/10 text-text-muted' }}"
                                        >
                                            <span class="size-1.5 rounded-full {{ $banner->is_active ? 'bg-success' : 'bg-slate-400' }}"></span>
                                            <span>{{ $banner->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                        </button>
                                    </form>
                                </td>
                                <td class="col-actions-sticky">
                                    <x-admin.action-menu
                                        editUrl="{{ route('admin.banners.edit', $banner) }}"
                                        viewUrl="{{ $banner->resolvedUrl() }}"
                                        deleteUrl="{{ route('admin.banners.destroy', $banner) }}"
                                        deleteMessage="Hapus slide banner hero '{{ addslashes($banner->title) }}'?"
                                        itemName="slide banner"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- MOBILE STACKED CARDS VIEW (< 1024px) --}}
            <div class="mt-6 grid gap-4 lg:hidden">
                @foreach ($banners as $banner)
                    <div class="rounded-2xl border border-border bg-surface p-4 shadow-token space-y-3">
                        <div class="flex items-start gap-3">
                            @if ($banner->imageUrl())
                                <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->title }}" class="h-16 w-28 shrink-0 rounded-xl object-cover border border-border">
                            @else
                                <div class="flex h-16 w-28 shrink-0 items-center justify-center rounded-xl border border-border bg-surface-2 text-[10px] font-semibold text-text-muted uppercase text-center p-1 leading-tight">
                                    Tanpa poster
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-text leading-snug">{{ $banner->title }}</p>
                                <p class="text-xs text-text-muted mt-0.5">Tujuan: {{ ucfirst($banner->target_type) }}</p>
                                <div class="mt-2 flex items-center gap-2">
                                    <form method="POST" action="{{ route('admin.banners.status', $banner) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-bold {{ $banner->is_active ? 'bg-success/15 text-success' : 'bg-slate-200 dark:bg-white/10 text-text-muted' }}">
                                            {{ $banner->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t border-border pt-3 text-xs">
                            <span class="text-text-muted font-bold">Urutan #{{ $banner->sort_order }}</span>
                            <x-admin.action-menu
                                editUrl="{{ route('admin.banners.edit', $banner) }}"
                                viewUrl="{{ $banner->resolvedUrl() }}"
                                deleteUrl="{{ route('admin.banners.destroy', $banner) }}"
                                deleteMessage="Hapus slide banner hero '{{ addslashes($banner->title) }}'?"
                                itemName="slide banner"
                            />
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $banners->links() }}
            </div>
        @endif
    </div>
@endsection
