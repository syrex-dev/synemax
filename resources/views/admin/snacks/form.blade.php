@extends('layouts.admin')

@section('title', $menuItem->exists ? 'Ubah menu' : 'Tambah menu')

@section('content')
    <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-accent">Warung studio</p><h1 class="mt-2 text-3xl font-black">{{ $menuItem->exists ? 'Ubah menu' : 'Tambah menu' }}</h1></div>
    @if ($errors->any())
        <div role="alert" class="mt-5 rounded-xl border border-danger/50 bg-danger/10 p-4 text-sm text-danger">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
    <form method="POST" action="{{ $menuItem->exists ? route('admin.snacks.update', $menuItem) : route('admin.snacks.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5 rounded-2xl border border-border bg-surface p-5 sm:p-6">
        @csrf @if ($menuItem->exists) @method('PUT') @endif
        <details open class="rounded-xl border border-border p-4">
            <summary class="cursor-pointer font-bold">Informasi menu & harga</summary>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><label for="name" class="text-sm font-semibold">Nama menu</label><input id="name" name="name" value="{{ old('name', $menuItem->name) }}" required maxlength="120" class="mt-2 min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-sm text-text"></div>
                <div><label for="category_id" class="text-sm font-semibold">Kategori</label><select id="category_id" name="category_id" required class="mt-2 min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-sm text-text">@foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $menuItem->category_id) == $category->id)>{{ $category->name }}{{ $category->is_active ? '' : ' · Nonaktif' }}</option>@endforeach</select></div>
                <div><label for="price" class="text-sm font-semibold">Harga jual (Rp)</label><input id="price" name="price" type="number" min="500" max="1000000" step="500" value="{{ old('price', $menuItem->price) }}" required class="mt-2 min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-sm text-text"></div>
                <div><label for="cost_price" class="text-sm font-semibold">Harga modal (opsional)</label><input id="cost_price" name="cost_price" type="number" min="0" max="1000000" step="500" value="{{ old('cost_price', $menuItem->cost_price) }}" class="mt-2 min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-sm text-text"></div>
            </div>
        </details>
        <details open class="rounded-xl border border-border p-4">
            <summary class="cursor-pointer font-bold">Persiapan & persediaan</summary>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><label for="prep_minutes" class="text-sm font-semibold">Waktu persiapan (menit)</label><input id="prep_minutes" name="prep_minutes" type="number" min="0" max="600" value="{{ old('prep_minutes', $menuItem->prep_minutes ?? 0) }}" required class="mt-2 min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-sm text-text"></div>
                <div><label for="sort_order" class="text-sm font-semibold">Urutan tampil</label><input id="sort_order" name="sort_order" type="number" min="0" max="65535" value="{{ old('sort_order', $menuItem->sort_order ?? 0) }}" required class="mt-2 min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-sm text-text"></div>
                <div><label for="stock_mode" class="text-sm font-semibold">Mode stok</label><select id="stock_mode" name="stock_mode" required class="mt-2 min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-sm text-text"><option value="toggle" @selected(old('stock_mode', $menuItem->stock_mode ?? 'toggle') === 'toggle')>Toggle tersedia / habis</option><option value="angka" @selected(old('stock_mode', $menuItem->stock_mode) === 'angka')>Stok angka</option></select></div>
                <div><label for="stock_qty" class="text-sm font-semibold">Jumlah stok (mode angka)</label><input id="stock_qty" name="stock_qty" type="number" min="0" max="1000000" value="{{ old('stock_qty', $menuItem->stock_qty) }}" class="mt-2 min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-sm text-text"></div>
                <div><label for="stock_alert_threshold" class="text-sm font-semibold">Peringatan stok menipis di angka</label><input id="stock_alert_threshold" name="stock_alert_threshold" type="number" min="0" max="100000" value="{{ old('stock_alert_threshold', $menuItem->stock_alert_threshold ?? 3) }}" required class="mt-2 min-h-11 w-full rounded-lg border border-border bg-bg px-3 text-sm text-text"></div>
            </div>
        </details>
        <details open class="rounded-xl border border-border p-4">
            <summary class="cursor-pointer font-bold">Foto & ketersediaan</summary>
            @if ($menuItem->photo_path)<img src="{{ asset('storage/'.$menuItem->photo_path) }}" alt="Foto {{ $menuItem->name }}" loading="lazy" width="160" height="120" class="mt-4 max-h-32 rounded-xl object-cover">@endif
            <div class="mt-4"><label for="photo" class="text-sm font-semibold">Foto (opsional, otomatis WebP)</label><input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-sm text-text-muted file:mr-3 file:min-h-10 file:rounded-lg file:border-0 file:bg-surface-2 file:px-4 file:font-semibold file:text-text"></div>
            <div class="mt-4 flex flex-wrap gap-5 text-sm">
                <label class="inline-flex items-center gap-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $menuItem->is_active ?? true)) class="rounded border-border bg-bg text-accent">Menu aktif</label>
                <label class="inline-flex items-center gap-2"><input type="hidden" name="is_available" value="0"><input type="checkbox" name="is_available" value="1" @checked(old('is_available', $menuItem->is_available ?? true)) class="rounded border-border bg-bg text-accent">Tersedia</label>
            </div>
        </details>
        <div class="flex flex-wrap gap-3"><button class="min-h-12 w-full rounded-xl bg-accent px-5 font-bold text-accent-contrast hover:opacity-90 sm:w-auto">Simpan menu</button><a href="{{ route('admin.snacks.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-border px-5 text-sm font-semibold">Batal</a></div>
    </form>
@endsection
