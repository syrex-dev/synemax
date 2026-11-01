<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgeRating;
use App\Models\Film;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgeRatingController extends Controller
{
    /**
     * Display a listing of age ratings.
     */
    public function index(): View
    {
        $ageRatings = AgeRating::query()
            ->withCount('films')
            ->orderBy('sort_order')
            ->orderBy('min_age')
            ->get();

        return view('admin.age-ratings.index', [
            'ageRatings' => $ageRatings,
            'activeCount' => $ageRatings->where('is_active', true)->count(),
        ]);
    }

    /**
     * Store a newly created age rating.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:8', 'regex:/^[A-Za-z0-9\+\-]+$/', 'unique:age_ratings,code'],
            'label' => ['required', 'string', 'max:100'],
            'min_age' => ['required', 'integer', 'between:0,99'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'code.required' => 'Kode klasifikasi wajib diisi.',
            'code.max' => 'Kode klasifikasi maksimal 8 karakter.',
            'code.regex' => 'Kode hanya boleh berupa huruf, angka, tanda tambah (+), atau tanda hubung (-).',
            'code.unique' => 'Kode klasifikasi sudah terdaftar.',
            'label.required' => 'Label klasifikasi wajib diisi.',
            'min_age.required' => 'Usia minimal wajib diisi.',
            'min_age.between' => 'Usia minimal harus antara 0 sampai 99 tahun.',
        ]);

        $maxSort = (int) AgeRating::max('sort_order');

        $rating = AgeRating::create([
            'code' => strtoupper(trim($validated['code'])),
            'label' => trim($validated['label']),
            'min_age' => (int) $validated['min_age'],
            'description' => ! empty($validated['description']) ? trim($validated['description']) : null,
            'sort_order' => $maxSort + 1,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.age_ratings.index')
            ->with('status', "Klasifikasi usia '{$rating->code}' berhasil ditambahkan.");
    }

    /**
     * Update the specified age rating.
     */
    public function update(Request $request, AgeRating $ageRating): RedirectResponse
    {
        $filmCount = $ageRating->films()->count();

        $rules = [
            'label' => ['required', 'string', 'max:100'],
            'min_age' => ['required', 'integer', 'between:0,99'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($filmCount === 0 && $request->filled('code')) {
            $rules['code'] = [
                'required',
                'string',
                'max:8',
                'regex:/^[A-Za-z0-9\+\-]+$/',
                Rule::unique('age_ratings', 'code')->ignore($ageRating->id),
            ];
        }

        $validated = $request->validate($rules, [
            'label.required' => 'Label klasifikasi wajib diisi.',
            'min_age.required' => 'Usia minimal wajib diisi.',
            'code.unique' => 'Kode klasifikasi sudah digunakan.',
        ]);

        // Prevent deactivating if it's the last active rating
        $wantsActive = $request->boolean('is_active');
        if ($ageRating->is_active && ! $wantsActive) {
            $activeCount = AgeRating::where('is_active', true)->count();
            if ($activeCount <= 1) {
                return back()->with('error', 'Tidak dapat menonaktifkan klasifikasi terakhir yang aktif.');
            }
        }

        $updateData = [
            'label' => trim($validated['label']),
            'min_age' => (int) $validated['min_age'],
            'description' => ! empty($validated['description']) ? trim($validated['description']) : null,
            'is_active' => $wantsActive,
        ];

        if (isset($validated['sort_order'])) {
            $updateData['sort_order'] = (int) $validated['sort_order'];
        }

        if ($filmCount === 0 && isset($validated['code'])) {
            $updateData['code'] = strtoupper(trim($validated['code']));
        }

        $ageRating->update($updateData);

        return redirect()->route('admin.age_ratings.index')
            ->with('status', "Klasifikasi usia '{$ageRating->code}' berhasil diperbarui.");
    }

    /**
     * Remove the specified age rating.
     */
    public function destroy(Request $request, AgeRating $ageRating): RedirectResponse
    {
        // Disallow deleting the last active classification
        if ($ageRating->is_active) {
            $activeCount = AgeRating::where('is_active', true)->count();
            if ($activeCount <= 1) {
                return back()->with('error', 'Tidak dapat menghapus klasifikasi terakhir yang aktif.');
            }
        }

        $filmCount = $ageRating->films()->count();

        if ($filmCount > 0) {
            $action = $request->input('action');

            if ($action === 'deactivate') {
                $ageRating->update(['is_active' => false]);

                return redirect()->route('admin.age_ratings.index')
                    ->with('status', "Klasifikasi '{$ageRating->code}' berhasil dinonaktifkan (tetap tersimpan untuk {$filmCount} film lama).");
            }

            if ($action === 'reassign') {
                $targetCode = trim((string) $request->input('target_code'));

                if ($targetCode === '' || $targetCode === $ageRating->code) {
                    return back()->with('error', 'Pilih klasifikasi tujuan yang valid untuk memindahkan film.');
                }

                $targetExists = AgeRating::where('code', $targetCode)->where('is_active', true)->exists();
                if (! $targetExists) {
                    return back()->with('error', 'Klasifikasi tujuan tidak ditemukan atau tidak aktif.');
                }

                DB::transaction(function () use ($ageRating, $targetCode): void {
                    Film::where('age_rating', $ageRating->code)->update(['age_rating' => $targetCode]);
                    $ageRating->delete();
                });

                return redirect()->route('admin.age_ratings.index')
                    ->with('status', "{$filmCount} film berhasil dialihkan ke '{$targetCode}' dan klasifikasi '{$ageRating->code}' telah dihapus.");
            }

            return back()->with('error', "Klasifikasi sedang digunakan oleh {$filmCount} film. Pilih opsi alihkan film atau nonaktifkan saja.");
        }

        $code = $ageRating->code;
        $ageRating->delete();

        return redirect()->route('admin.age_ratings.index')
            ->with('status', "Klasifikasi usia '{$code}' berhasil dihapus.");
    }

    /**
     * Toggle active status.
     */
    public function toggle(AgeRating $ageRating): RedirectResponse
    {
        if ($ageRating->is_active) {
            $activeCount = AgeRating::where('is_active', true)->count();
            if ($activeCount <= 1) {
                return back()->with('error', 'Tidak dapat menonaktifkan klasifikasi terakhir yang aktif.');
            }
            $ageRating->update(['is_active' => false]);
            $msg = "Klasifikasi '{$ageRating->code}' dinonaktifkan.";
        } else {
            $ageRating->update(['is_active' => true]);
            $msg = "Klasifikasi '{$ageRating->code}' diaktifkan.";
        }

        return back()->with('status', $msg);
    }

    /**
     * Reorder ratings up or down.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $id = $request->input('id');
        $direction = $request->input('direction');

        $current = AgeRating::findOrFail($id);
        $all = AgeRating::orderBy('sort_order')->orderBy('min_age')->get();

        $index = $all->search(fn ($item) => $item->id === $current->id);

        if ($direction === 'up' && $index > 0) {
            $swapWith = $all[$index - 1];
            $tempOrder = $current->sort_order;
            $current->update(['sort_order' => $swapWith->sort_order]);
            $swapWith->update(['sort_order' => $tempOrder]);
        } elseif ($direction === 'down' && $index < $all->count() - 1) {
            $swapWith = $all[$index + 1];
            $tempOrder = $current->sort_order;
            $current->update(['sort_order' => $swapWith->sort_order]);
            $swapWith->update(['sort_order' => $tempOrder]);
        }

        // Normalize all sort orders sequentially
        $reloaded = AgeRating::orderBy('sort_order')->orderBy('min_age')->get();
        foreach ($reloaded as $orderIndex => $item) {
            $item->update(['sort_order' => $orderIndex + 1]);
        }

        return back()->with('status', 'Urutan klasifikasi usia berhasil diperbarui.');
    }
}
