<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Film;
use App\Models\NobarEvent;
use App\Models\Promo;
use App\Services\MediaUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function index(): View
    {
        $banners = Banner::query()
            ->ofType('hero')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('admin.banners.index', [
            'banners' => $banners,
        ]);
    }

    public function create(): View
    {
        return view('admin.banners.form', [
            'banner' => new Banner([
                'type' => 'hero',
                'is_active' => true,
                'target_type' => 'none',
                'sort_order' => Banner::max('sort_order') + 1,
            ]),
            'isEdit' => false,
            'films' => Film::query()->where('is_active', true)->orderBy('title')->get(['id', 'title', 'slug']),
            'promos' => Promo::query()->where('is_active', true)->orderBy('title')->get(['id', 'title', 'slug']),
            'nobars' => NobarEvent::query()->where('status_publikasi', 'terbit')->orderBy('judul')->get(['id', 'judul', 'slug']),
        ]);
    }

    public function store(Request $request, MediaUploadService $media): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'type' => 'required|string|in:hero,promo,snack',
            'image' => 'required|image|mimes:jpeg,png,webp|max:3072',
            'mobile_image' => 'nullable|image|mimes:jpeg,png,webp|max:3072',
            'target_type' => 'required|string|in:none,film,promo,nobar,custom',
            'target_id' => 'nullable|string|max:100',
            'cta_url' => 'nullable|url|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $banner = new Banner;
        $banner->title = $validated['title'];
        $banner->type = $validated['type'];
        $banner->target_type = $validated['target_type'];
        $banner->target_id = $validated['target_id'] ?? null;
        $banner->cta_url = $validated['cta_url'] ?? null;
        $banner->is_active = $request->boolean('is_active', true);
        $banner->sort_order = (int) ($validated['sort_order'] ?? 0);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('banners', 'public');
            $banner->image_path = $path;
        }

        if ($request->hasFile('mobile_image')) {
            $mobilePath = $request->file('mobile_image')->store('banners', 'public');
            $banner->mobile_image_path = $mobilePath;
        }

        $banner->save();

        return redirect()->route('admin.banners.index')->with('status', 'Banner hero berhasil ditambahkan.');
    }

    public function edit(Banner $banner): View
    {
        return view('admin.banners.form', [
            'banner' => $banner,
            'isEdit' => true,
            'films' => Film::query()->where('is_active', true)->orderBy('title')->get(['id', 'title', 'slug']),
            'promos' => Promo::query()->where('is_active', true)->orderBy('title')->get(['id', 'title', 'slug']),
            'nobars' => NobarEvent::query()->where('status_publikasi', 'terbit')->orderBy('judul')->get(['id', 'judul', 'slug']),
        ]);
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'type' => 'required|string|in:hero,promo,snack',
            'image' => 'nullable|image|mimes:jpeg,png,webp|max:3072',
            'mobile_image' => 'nullable|image|mimes:jpeg,png,webp|max:3072',
            'target_type' => 'required|string|in:none,film,promo,nobar,custom',
            'target_id' => 'nullable|string|max:100',
            'cta_url' => 'nullable|url|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $banner->title = $validated['title'];
        $banner->type = $validated['type'];
        $banner->target_type = $validated['target_type'];
        $banner->target_id = $validated['target_id'] ?? null;
        $banner->cta_url = $validated['cta_url'] ?? null;
        $banner->is_active = $request->boolean('is_active', true);
        $banner->sort_order = (int) ($validated['sort_order'] ?? 0);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('banners', 'public');
            $banner->image_path = $path;
        }

        if ($request->hasFile('mobile_image')) {
            $mobilePath = $request->file('mobile_image')->store('banners', 'public');
            $banner->mobile_image_path = $mobilePath;
        }

        $banner->save();

        return redirect()->route('admin.banners.index')->with('status', 'Banner hero berhasil diperbarui.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $banner->delete();

        return redirect()->route('admin.banners.index')->with('status', 'Banner hero berhasil dihapus.');
    }

    public function toggleStatus(Banner $banner): RedirectResponse
    {
        $banner->is_active = ! $banner->is_active;
        $banner->save();

        return back()->with('status', 'Status banner berhasil diubah.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $order = $request->input('order', []);
        foreach ($order as $index => $id) {
            Banner::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return back()->with('status', 'Urutan banner berhasil disimpan.');
    }
}
