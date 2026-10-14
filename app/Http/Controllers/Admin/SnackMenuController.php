<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Services\WebpImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SnackMenuController extends Controller
{
    public function index(): View
    {
        return view('admin.snacks.index', [
            'categories' => MenuCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
            'items' => MenuItem::query()
                ->with('category')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(30),
        ]);
    }

    public function create(): View
    {
        return $this->form(new MenuItem);
    }

    public function store(Request $request, WebpImageProcessor $images): RedirectResponse
    {
        $data = $this->validatedItem($request);
        $photo = $request->file('photo');
        unset($data['photo']);

        if ($photo !== null) {
            $data['photo_path'] = $images->store($photo, 'snacks');
        }

        MenuItem::query()->create($data);

        return redirect()->route('admin.snacks.index')->with('status', 'Menu jajan berhasil ditambahkan.');
    }

    public function edit(MenuItem $menuItem): View
    {
        return $this->form($menuItem);
    }

    public function update(Request $request, MenuItem $menuItem, WebpImageProcessor $images): RedirectResponse
    {
        $data = $this->validatedItem($request);
        $photo = $request->file('photo');
        $oldPhoto = null;
        unset($data['photo']);

        if ($photo !== null) {
            $oldPhoto = $menuItem->photo_path;
            $data['photo_path'] = $images->store($photo, 'snacks');
        }

        DB::transaction(function () use ($menuItem, &$data): void {
            $locked = MenuItem::query()->lockForUpdate()->findOrFail($menuItem->id);

            if ($data['stock_mode'] === 'toggle') {
                $data['stock_qty'] = $locked->stock_qty;
            } elseif ($locked->stock_mode !== 'angka' && $data['stock_qty'] === null) {
                $data['stock_qty'] = 0;
            }

            $locked->update($data);
        }, attempts: 3);

        if ($oldPhoto !== null) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return redirect()->route('admin.snacks.index')->with('status', 'Perubahan menu berhasil disimpan.');
    }

    public function destroy(MenuItem $menuItem): RedirectResponse
    {
        $result = DB::transaction(function () use ($menuItem): array {
            $locked = MenuItem::query()->lockForUpdate()->findOrFail($menuItem->id);

            if ($locked->bookingItems()->exists()) {
                $locked->update(['is_active' => false]);

                return ['deleted' => false, 'photo' => null];
            }

            $photo = $locked->photo_path;
            $locked->delete();

            return ['deleted' => true, 'photo' => $photo];
        }, attempts: 3);

        if (! $result['deleted']) {
            return back()->with('status', 'Menu sudah dipakai dalam riwayat booking, jadi dinonaktifkan agar snapshot dan stok tetap aman.');
        }

        if ($result['photo'] !== null) {
            Storage::disk('public')->delete($result['photo']);
        }

        return back()->with('status', 'Menu jajan berhasil dihapus.');
    }

    public function toggleAvailability(MenuItem $menuItem): RedirectResponse
    {
        $menuItem->update(['is_available' => ! $menuItem->is_available]);

        return back()->with('status', $menuItem->is_available ? 'Menu ditandai tersedia.' : 'Menu ditandai habis.');
    }

    public function adjustStock(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $data = $request->validate([
            'adjustment' => ['required', 'integer', 'between:-100000,100000'],
        ]);

        DB::transaction(function () use ($menuItem, $data): void {
            $locked = MenuItem::query()->lockForUpdate()->findOrFail($menuItem->id);

            if ($locked->stock_mode !== 'angka') {
                abort(422, 'Koreksi stok hanya tersedia untuk menu dengan mode stok angka.');
            }

            $quantity = $locked->stock_qty + (int) $data['adjustment'];

            if ($quantity < 0) {
                abort(422, 'Jumlah stok tidak boleh kurang dari nol.');
            }

            $locked->update(['stock_qty' => $quantity]);
        });

        return back()->with('status', 'Stok menu berhasil dikoreksi.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:menu_categories,name'],
            'sort_order' => ['required', 'integer', 'between:0,65535'],
        ]);

        MenuCategory::query()->create([...$data, 'is_active' => true]);

        return back()->with('status', 'Kategori berhasil ditambahkan.');
    }

    public function updateCategory(Request $request, MenuCategory $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('menu_categories', 'name')->ignore($category->id)],
            'sort_order' => ['required', 'integer', 'between:0,65535'],
            'is_active' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($category, $data): void {
            MenuCategory::query()->lockForUpdate()->findOrFail($category->id)->update($data);
        }, attempts: 3);

        return back()->with('status', 'Kategori berhasil diperbarui.');
    }

    public function destroyCategory(MenuCategory $category): RedirectResponse
    {
        $deleted = DB::transaction(function () use ($category): bool {
            $locked = MenuCategory::query()->lockForUpdate()->findOrFail($category->id);

            if ($locked->items()->exists()) {
                return false;
            }

            $locked->delete();

            return true;
        }, attempts: 3);

        if (! $deleted) {
            return back()->with('error', 'Pindahkan atau hapus menu di kategori ini sebelum menghapusnya.');
        }

        return back()->with('status', 'Kategori berhasil dihapus.');
    }

    private function form(MenuItem $menuItem): View
    {
        return view('admin.snacks.form', [
            'menuItem' => $menuItem,
            'categories' => MenuCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    private function validatedItem(Request $request): array
    {
        return $request->validate([
            'category_id' => ['required', 'integer', 'exists:menu_categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'price' => ['required', 'integer', 'between:500,1000000'],
            'cost_price' => ['nullable', 'integer', 'between:0,1000000'],
            'prep_minutes' => ['required', 'integer', 'between:0,600'],
            'stock_mode' => ['required', 'in:toggle,angka'],
            'stock_qty' => ['nullable', 'required_if:stock_mode,angka', 'integer', 'between:0,1000000'],
            'stock_alert_threshold' => ['required', 'integer', 'between:0,100000'],
            'is_available' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'between:0,65535'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=3000,max_height=4500'],
        ], [
            'price.between' => 'Harga jual harus antara Rp500 dan Rp1.000.000.',
            'prep_minutes.between' => 'Waktu persiapan maksimal 600 menit.',
            'stock_qty.required_if' => 'Isi stok awal untuk mode stok angka.',
            'photo.max' => 'Ukuran foto menu maksimal 4 MB.',
            'photo.dimensions' => 'Ukuran foto maksimal 3000 × 4500 piksel.',
        ]);
    }
}
