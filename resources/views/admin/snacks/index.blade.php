@extends('layouts.admin')

@section('title', 'Menu jajanan')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><h1 class="text-3xl font-black">Menu jajanan</h1><p class="mt-2 text-sm text-text-muted">Atur kategori, harga, dan ketersediaan menu warung.</p></div>
        <a href="{{ route('admin.snacks.create') }}" class="inline-flex min-h-11 items-center rounded-lg bg-accent px-4 text-sm font-bold text-accent-contrast">Tambah menu</a>
    </div>
    @if ($errors->any())
        <div role="alert" class="mt-5 rounded-xl border border-danger/50 bg-danger/10 p-4 text-sm text-danger">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif

    <section class="mt-6 rounded-lg border border-border bg-surface p-4 sm:p-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div><h2 class="text-lg font-bold">Kategori</h2><p class="mt-1 text-sm text-text-muted">Urutan kecil muncul lebih dulu di daftar menu.</p></div>
            <span class="text-sm text-text-muted">{{ $categories->count() }} kategori</span>
        </div>
        <form method="POST" action="{{ route('admin.snacks.categories.store') }}" class="mt-5 grid gap-3 rounded-lg border border-border bg-bg p-4 md:grid-cols-[minmax(15rem,1fr)_12rem_auto] md:items-end">
            @csrf
            <div><label for="new-category-name" class="mb-1 block text-sm font-semibold">Nama kategori</label><input id="new-category-name" name="name" required maxlength="100" value="{{ old('name') }}" placeholder="Contoh: Minuman dingin" class="min-h-11 w-full rounded-lg border border-border bg-surface px-3 text-base text-text"></div>
            <div><label for="new-category-order" class="mb-1 block text-sm font-semibold">Urutan tampil</label><input id="new-category-order" type="number" name="sort_order" min="0" max="65535" value="{{ old('sort_order', 0) }}" required class="min-h-11 w-full rounded-lg border border-border bg-surface px-3 text-base text-text"><p class="mt-1 text-xs text-text-muted">Angka kecil tampil lebih dulu.</p></div>
            <button class="min-h-11 rounded-lg bg-accent px-4 text-sm font-bold text-accent-contrast hover:bg-accent-hover">Tambah kategori</button>
        </form>
        <div class="mt-5 hidden xl:block">
            <x-admin.table class="rounded-lg border border-border">
                <x-slot:columns>
                    <col style="width: 34%">
                    <col style="width: 20%">
                    <col style="width: 18%">
                    <col style="width: 14%">
                    <col style="width: 14%">
                </x-slot:columns>
                <x-slot:head>
                    <th scope="col" class="px-4 py-3 font-semibold">Nama kategori</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Urutan tampil</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Status</th>
                    <th scope="col" class="px-2 py-3 text-right font-semibold">Simpan</th>
                    <th scope="col" class="px-2 py-3 text-right font-semibold">Hapus</th>
                </x-slot:head>
                    @forelse ($categories as $category)
                        <tr x-data="{ initial: [@js($category->name), {{ (int) $category->sort_order }}, {{ $category->is_active ? 'true' : 'false' }}], dirty: false, saving: false, activeLabel: '{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}', sync() { this.dirty = this.$refs.name.value !== this.initial[0] || Number(this.$refs.order.value) !== this.initial[1] || this.$refs.active.checked !== this.initial[2]; this.activeLabel = this.$refs.active.checked ? 'Aktif' : 'Nonaktif' } }" @input="sync()" @change="sync()" class="transition-colors hover:bg-surface-2/60">
                            <td class="px-4 py-3">
                                <form id="category-update-desktop-{{ $category->id }}" method="POST" action="{{ route('admin.snacks.categories.update', $category) }}" @submit="saving = true">@csrf @method('PATCH')</form>
                                <label for="category-name-desktop-{{ $category->id }}" class="sr-only">Nama kategori</label><input id="category-name-desktop-{{ $category->id }}" x-ref="name" form="category-update-desktop-{{ $category->id }}" name="name" value="{{ $category->name }}" required maxlength="100" class="min-h-10 w-full rounded-lg border border-border bg-bg px-3 text-base text-text">
                            </td>
                            <td class="px-4 py-3"><label for="category-order-desktop-{{ $category->id }}" class="sr-only">Urutan tampil</label><input id="category-order-desktop-{{ $category->id }}" x-ref="order" form="category-update-desktop-{{ $category->id }}" type="number" name="sort_order" value="{{ $category->sort_order }}" min="0" max="65535" required class="min-h-10 w-full rounded-lg border border-border bg-bg px-3 text-base text-text"></td>
                            <td class="px-4 py-3">
                                <input type="hidden" form="category-update-desktop-{{ $category->id }}" name="is_active" value="0">
                                <label class="flex min-h-10 cursor-pointer items-center gap-2">
                                    <input x-ref="active" form="category-update-desktop-{{ $category->id }}" type="checkbox" name="is_active" value="1" @checked($category->is_active) class="peer sr-only">
                                    <span aria-hidden="true" class="relative h-6 w-10 rounded-full bg-border transition-colors peer-checked:bg-accent after:absolute after:left-1 after:top-1 after:size-4 after:rounded-full after:bg-surface after:transition-transform peer-checked:after:translate-x-4"></span>
                                    <span class="text-sm text-text" x-text="activeLabel">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </label>
                            </td>
                            <td class="px-2 py-3 text-right">
                                <span x-show="dirty" x-cloak class="mb-1 block text-xs font-semibold text-warning">Belum disimpan</span>
                                <button form="category-update-desktop-{{ $category->id }}" :disabled="!dirty || saving" class="min-h-10 min-w-20 rounded-lg bg-accent px-3 text-sm font-bold text-accent-contrast disabled:cursor-not-allowed disabled:opacity-45"><span x-show="!saving">Simpan</span><span x-show="saving" x-cloak>Menyimpan…</span></button>
                            </td>
                            <td class="px-4 py-3 text-right"><form method="POST" action="{{ route('admin.snacks.categories.destroy', $category) }}" data-confirm="Yakin hapus kategori &quot;{{ $category->name }}&quot;? Menu dalam kategori ini mungkin terdampak." data-confirm-title="Hapus Kategori" data-confirm-action="Hapus" data-confirm-is-danger="true" novalidate>@csrf @method('DELETE')<button class="min-h-10 rounded-lg border border-danger/40 px-3 text-sm font-semibold text-danger hover:bg-danger-surface">Hapus</button></form></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-text-muted">Kategori belum ada. Tambahkan kategori pertama lewat formulir di atas.</td></tr>
                    @endforelse
            </x-admin.table>
        </div>
        <div class="mt-4 space-y-3 xl:hidden">
            @forelse ($categories as $category)
                <div x-data="{ initial: [@js($category->name), {{ (int) $category->sort_order }}, {{ $category->is_active ? 'true' : 'false' }}], dirty: false, saving: false, activeLabel: '{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}', sync() { this.dirty = this.$refs.name.value !== this.initial[0] || Number(this.$refs.order.value) !== this.initial[1] || this.$refs.active.checked !== this.initial[2]; this.activeLabel = this.$refs.active.checked ? 'Aktif' : 'Nonaktif' } }" @input="sync()" @change="sync()" class="rounded-lg border border-border bg-bg p-4">
                    <form id="category-update-mobile-{{ $category->id }}" method="POST" action="{{ route('admin.snacks.categories.update', $category) }}" @submit="saving = true">@csrf @method('PATCH')</form>
                    <div class="grid gap-3">
                        <div><label for="category-name-mobile-{{ $category->id }}" class="mb-1 block text-sm font-semibold">Nama kategori</label><input id="category-name-mobile-{{ $category->id }}" x-ref="name" form="category-update-mobile-{{ $category->id }}" name="name" value="{{ $category->name }}" required maxlength="100" class="min-h-11 w-full rounded-lg border border-border bg-surface px-3 text-base text-text"></div>
                        <div><label for="category-order-mobile-{{ $category->id }}" class="mb-1 block text-sm font-semibold">Urutan tampil</label><input id="category-order-mobile-{{ $category->id }}" x-ref="order" form="category-update-mobile-{{ $category->id }}" type="number" name="sort_order" value="{{ $category->sort_order }}" min="0" max="65535" required class="min-h-11 w-full rounded-lg border border-border bg-surface px-3 text-base text-text"><p class="mt-1 text-xs text-text-muted">Angka kecil tampil lebih dulu.</p></div>
                        <div>
                            <input type="hidden" form="category-update-mobile-{{ $category->id }}" name="is_active" value="0">
                            <label class="flex min-h-11 cursor-pointer items-center gap-2">
                                <input x-ref="active" form="category-update-mobile-{{ $category->id }}" type="checkbox" name="is_active" value="1" @checked($category->is_active) class="peer sr-only">
                                <span aria-hidden="true" class="relative h-6 w-10 rounded-full bg-border transition-colors peer-checked:bg-accent after:absolute after:left-1 after:top-1 after:size-4 after:rounded-full after:bg-surface after:transition-transform peer-checked:after:translate-x-4"></span>
                                <span class="text-sm font-medium text-text" x-text="activeLabel">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-3 border-t border-border pt-3">
                        <span x-show="dirty" x-cloak class="text-xs font-semibold text-warning">Belum disimpan</span><span x-show="!dirty" class="text-xs text-text-muted">Tersimpan</span>
                        <div class="flex gap-2">
                            <button form="category-update-mobile-{{ $category->id }}" :disabled="!dirty || saving" class="min-h-11 rounded-lg bg-accent px-4 text-sm font-bold text-accent-contrast disabled:cursor-not-allowed disabled:opacity-45"><span x-show="!saving">Simpan</span><span x-show="saving" x-cloak>Menyimpan…</span></button>
                            <form method="POST" action="{{ route('admin.snacks.categories.destroy', $category) }}" data-confirm="Yakin hapus kategori &quot;{{ $category->name }}&quot;? Menu dalam kategori ini mungkin terdampak." data-confirm-title="Hapus Kategori" data-confirm-action="Hapus" data-confirm-is-danger="true" novalidate>@csrf @method('DELETE')<button aria-label="Hapus kategori {{ $category->name }}" class="min-h-11 rounded-lg border border-danger/40 px-3 text-sm font-semibold text-danger hover:bg-danger-surface">Hapus</button></form>
                        </div>
                    </div>
                </div>
            @empty
                <p class="rounded-lg border border-border bg-bg p-5 text-center text-sm text-text-muted">Kategori belum ada. Tambahkan kategori pertama lewat formulir di atas.</p>
            @endforelse
        </div>
    </section>

    <section class="mt-8 space-y-3">
        <h2 class="font-bold">Daftar menu</h2>
        @forelse ($items as $item)
            <article class="rounded-2xl border border-border bg-surface p-4 sm:p-5">
                <div class="flex gap-4">
                    @if ($item->photo_path)
                        <img src="{{ asset('storage/'.$item->photo_path) }}" alt="" loading="lazy" width="72" height="72" class="size-[72px] rounded-xl object-cover">
                    @else
                        <div class="grid size-[72px] shrink-0 place-items-center rounded-xl bg-surface-2 text-xs text-text-muted">Tanpa foto</div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div><h3 class="font-bold">{{ $item->name }} @unless ($item->is_active)<span class="text-xs text-text-muted">· Nonaktif</span>@endunless</h3><p class="mt-1 text-xs text-text-muted">{{ $item->category->name }} · persiapan {{ $item->prep_minutes }} menit</p></div>
                            <p class="font-bold text-accent">Rp {{ number_format($item->price, 0, ',', '.') }}</p>
                        </div>
                        <p class="mt-2 text-xs {{ $item->stock_mode === 'angka' && $item->stock_qty <= $item->stock_alert_threshold ? 'text-danger' : 'text-text-muted' }}">
                            @if ($item->stock_mode === 'angka')
                                Stok {{ $item->stock_qty }}{{ $item->stock_qty <= $item->stock_alert_threshold ? ' · Stok menipis' : '' }}
                            @else
                                {{ $item->is_available ? 'Tersedia' : 'Habis' }} · mode toggle
                            @endif
                        </p>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2 border-t border-border pt-3">
                    <a href="{{ route('admin.snacks.edit', $item) }}" class="inline-flex min-h-10 items-center rounded-lg border border-border px-3 text-xs font-semibold">Ubah</a>
                    <form method="POST" action="{{ route('admin.snacks.availability', $item) }}">@csrf @method('PATCH')
                        <button class="min-h-10 rounded-lg border border-accent/30 px-3 text-xs font-semibold text-accent">{{ $item->is_available ? 'Tandai Habis' : 'Tandai Tersedia' }}</button>
                    </form>
                    @if ($item->stock_mode === 'angka')
                        <form method="POST" action="{{ route('admin.snacks.stock', $item) }}" class="flex items-center gap-2">@csrf @method('PATCH')
                            <label class="sr-only" for="adjust-{{ $item->id }}">Koreksi stok (+/-)</label><input id="adjust-{{ $item->id }}" name="adjustment" type="number" required value="0" class="min-h-10 w-24 rounded-lg border border-border bg-bg px-2 text-xs text-text">
                            <button class="min-h-10 rounded-lg border border-border px-3 text-xs font-semibold">Koreksi stok</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.snacks.destroy', $item) }}" data-confirm="Hapus menu &quot;{{ $item->name }}&quot;? Riwayat pesanan tetap tersimpan." data-confirm-title="Hapus Menu" data-confirm-action="Hapus" data-confirm-is-danger="true" novalidate>@csrf @method('DELETE')
                        <button class="min-h-10 rounded-lg border border-danger/30 px-3 text-xs font-semibold text-danger">Hapus</button>
                    </form>
                </div>
            </article>
        @empty
            <p class="rounded-xl border border-border bg-surface p-5 text-sm text-text-muted">Menu belum ditambahkan.</p>
        @endforelse
        <div>{{ $items->links() }}</div>
    </section>
@endsection
