<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PromoController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $query = Promo::query();

        if ($status === 'aktif') {
            $query->current();
        } elseif ($status === 'berakhir') {
            $query->whereNotNull('end_date')->where('end_date', '<', now()->toDateString());
        } elseif ($status === 'draft') {
            $query->where('is_active', false);
        }

        $promos = $query->ordered()->paginate(15)->withQueryString();

        return view('admin.promos.index', [
            'promos' => $promos,
            'currentStatus' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.promos.form', [
            'promo' => new Promo([
                'is_active' => true,
                'sort_order' => Promo::max('sort_order') + 1,
            ]),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'slug' => 'nullable|string|max:160|unique:promos,slug',
            'image' => 'required|image|mimes:jpeg,png,webp|max:2048',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string|max:5000',
            'terms_conditions' => 'nullable|string|max:5000',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $promo = new Promo;
        $promo->title = $validated['title'];
        $promo->slug = filled($validated['slug'] ?? null) ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        $promo->start_date = $validated['start_date'] ?? null;
        $promo->end_date = $validated['end_date'] ?? null;
        $promo->description = $validated['description'] ?? null;
        $promo->terms_conditions = $validated['terms_conditions'] ?? null;
        $promo->is_active = $request->boolean('is_active', true);
        $promo->sort_order = (int) ($validated['sort_order'] ?? 0);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('promos', 'public');
            $promo->image_path = $path;
        }

        $promo->save();

        return redirect()->route('admin.promos.index')->with('status', 'Promo baru berhasil ditambahkan.');
    }

    public function edit(Promo $promo): View
    {
        return view('admin.promos.form', [
            'promo' => $promo,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, Promo $promo): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'slug' => 'nullable|string|max:160|unique:promos,slug,'.$promo->id,
            'image' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string|max:5000',
            'terms_conditions' => 'nullable|string|max:5000',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $promo->title = $validated['title'];
        if (filled($validated['slug'] ?? null)) {
            $promo->slug = Str::slug($validated['slug']);
        }
        $promo->start_date = $validated['start_date'] ?? null;
        $promo->end_date = $validated['end_date'] ?? null;
        $promo->description = $validated['description'] ?? null;
        $promo->terms_conditions = $validated['terms_conditions'] ?? null;
        $promo->is_active = $request->boolean('is_active', true);
        $promo->sort_order = (int) ($validated['sort_order'] ?? 0);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('promos', 'public');
            $promo->image_path = $path;
        }

        $promo->save();

        return redirect()->route('admin.promos.index')->with('status', 'Promo berhasil diperbarui.');
    }

    public function destroy(Promo $promo): RedirectResponse
    {
        $promo->delete();

        return redirect()->route('admin.promos.index')->with('status', 'Promo berhasil dihapus.');
    }

    public function toggleStatus(Promo $promo): RedirectResponse
    {
        $promo->is_active = ! $promo->is_active;
        $promo->save();

        return back()->with('status', 'Status promo berhasil diubah.');
    }

    public function duplicate(Promo $promo): RedirectResponse
    {
        $replica = $promo->replicate();
        $replica->title = $promo->title.' (Duplikat)';
        $replica->slug = Str::slug($replica->title).'-'.time();
        $replica->is_active = false;
        $replica->save();

        return redirect()->route('admin.promos.edit', $replica->id)->with('status', 'Promo berhasil diduplikat sebagai draft baru.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $order = $request->input('order', []);
        foreach ($order as $index => $id) {
            Promo::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return back()->with('status', 'Urutan promo berhasil disimpan.');
    }
}
