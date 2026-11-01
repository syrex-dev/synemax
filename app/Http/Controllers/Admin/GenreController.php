<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class GenreController extends Controller
{
    /**
     * Display a listing of genres.
     */
    public function index(): View
    {
        if (! Schema::hasTable('genres')) {
            return view('admin.genres.index', [
                'genres' => collect(),
                'activeCount' => 0,
            ])->with('error', 'Tabel genre belum dimigrasi di database ini. Jalankan php artisan migrate untuk mengaktifkan.');
        }

        $genres = Genre::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Calculate film counts
        $genresWithCount = $genres->map(function (Genre $genre) {
            $genre->films_count = $genre->countFilms();

            return $genre;
        });

        return view('admin.genres.index', [
            'genres' => $genresWithCount,
            'activeCount' => $genres->where('is_active', true)->count(),
        ]);
    }

    /**
     * Store a newly created genre.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama genre wajib diisi.',
            'name.max' => 'Nama genre maksimal 50 karakter.',
        ]);

        $formatted = Genre::formatDisplayName($validated['name']);
        if (! $formatted) {
            return back()->with('error', 'Nama genre tidak boleh kosong atau hanya berupa simbol.')->withInput();
        }

        $normalized = Genre::normalizeName($formatted);
        $exists = Genre::where('normalized_name', $normalized)->exists();
        if ($exists) {
            return back()->with('error', "Genre '{$formatted}' sudah terdaftar.")->withInput();
        }

        $maxOrder = (int) Genre::max('sort_order');

        Genre::create([
            'name' => $formatted,
            'normalized_name' => $normalized,
            'sort_order' => $maxOrder + 1,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.genres.index')
            ->with('status', "Genre '{$formatted}' berhasil ditambahkan.");
    }

    /**
     * Update the specified genre.
     */
    public function update(Request $request, Genre $genre): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama genre wajib diisi.',
            'name.max' => 'Nama genre maksimal 50 karakter.',
        ]);

        $formatted = Genre::formatDisplayName($validated['name']);
        if (! $formatted) {
            return back()->with('error', 'Nama genre tidak boleh kosong atau hanya berupa simbol.');
        }

        $wantsActive = $request->boolean('is_active');
        if ($genre->is_active && ! $wantsActive) {
            $activeCount = Genre::where('is_active', true)->count();
            if ($activeCount <= 1) {
                return back()->with('error', 'Tidak dapat menonaktifkan genre terakhir yang aktif.');
            }
        }

        try {
            if ($genre->name !== $formatted) {
                $genre->renameWithFilms($formatted);
            }

            $updateData = ['is_active' => $wantsActive];
            if (isset($validated['sort_order'])) {
                $updateData['sort_order'] = (int) $validated['sort_order'];
            }
            $genre->update($updateData);

            return redirect()->route('admin.genres.index')
                ->with('status', "Genre '{$formatted}' berhasil diperbarui.");
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Merge genre into another genre.
     */
    public function merge(Request $request, Genre $genre): RedirectResponse
    {
        $validated = $request->validate([
            'target_id' => ['required', 'exists:genres,id'],
        ], [
            'target_id.required' => 'Pilih genre tujuan penggabungan.',
            'target_id.exists' => 'Genre tujuan tidak ditemukan.',
        ]);

        $targetId = (int) $validated['target_id'];
        if ($targetId === $genre->id) {
            return back()->with('error', 'Genre tujuan tidak boleh sama dengan genre yang akan digabungkan.');
        }

        $target = Genre::findOrFail($targetId);
        $oldName = $genre->name;
        $targetName = $target->name;
        $affectedCount = $genre->countFilms();

        $genre->mergeInto($target);

        return redirect()->route('admin.genres.index')
            ->with('status', "Genre '{$oldName}' ({$affectedCount} film) berhasil digabungkan ke '{$targetName}'.");
    }

    /**
     * Remove the specified genre.
     */
    public function destroy(Request $request, Genre $genre): RedirectResponse
    {
        $filmCount = $genre->countFilms();

        if ($filmCount > 0) {
            $action = $request->input('action');

            if ($action === 'merge') {
                $targetId = (int) $request->input('target_id');
                if (! $targetId || $targetId === $genre->id) {
                    return back()->with('error', 'Pilih genre tujuan penggabungan yang valid.');
                }

                $target = Genre::find($targetId);
                if (! $target) {
                    return back()->with('error', 'Genre tujuan tidak ditemukan.');
                }

                $oldName = $genre->name;
                $targetName = $target->name;
                $genre->mergeInto($target);

                return redirect()->route('admin.genres.index')
                    ->with('status', "{$filmCount} film dialihkan ke '{$targetName}' dan genre '{$oldName}' berhasil dihapus.");
            }

            if ($action === 'deactivate') {
                $genre->update(['is_active' => false]);

                return redirect()->route('admin.genres.index')
                    ->with('status', "Genre '{$genre->name}' dinonaktifkan (tetap tersimpan pada {$filmCount} film lama).");
            }

            return back()->with('error', "Genre sedang digunakan oleh {$filmCount} film. Gabungkan ke genre lain atau nonaktifkan.");
        }

        $name = $genre->name;
        $genre->delete();

        return redirect()->route('admin.genres.index')
            ->with('status', "Genre '{$name}' berhasil dihapus.");
    }

    /**
     * Toggle active status.
     */
    public function toggle(Genre $genre): RedirectResponse
    {
        if ($genre->is_active) {
            $activeCount = Genre::where('is_active', true)->count();
            if ($activeCount <= 1) {
                return back()->with('error', 'Tidak dapat menonaktifkan genre terakhir yang aktif.');
            }
            $genre->update(['is_active' => false]);
            $msg = "Genre '{$genre->name}' dinonaktifkan.";
        } else {
            $genre->update(['is_active' => true]);
            $msg = "Genre '{$genre->name}' diaktifkan.";
        }

        return back()->with('status', $msg);
    }

    /**
     * Reorder genres.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $id = (int) $request->input('id');
        $direction = $request->input('direction');

        $current = Genre::findOrFail($id);
        $all = Genre::orderBy('sort_order')->orderBy('name')->get();

        $index = $all->search(fn ($item) => $item->id === $current->id);

        if ($direction === 'up' && $index > 0) {
            $swapWith = $all[$index - 1];
            $currentOrder = $current->sort_order;
            $current->update(['sort_order' => $swapWith->sort_order]);
            $swapWith->update(['sort_order' => $currentOrder]);
        } elseif ($direction === 'down' && $index !== false && $index < $all->count() - 1) {
            $swapWith = $all[$index + 1];
            $currentOrder = $current->sort_order;
            $current->update(['sort_order' => $swapWith->sort_order]);
            $swapWith->update(['sort_order' => $currentOrder]);
        }

        return back()->with('status', "Urutan genre '{$current->name}' diperbarui.");
    }
}
