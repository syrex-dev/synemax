<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Film;
use App\Models\Session;
use App\Services\PosterProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FilmController extends Controller
{
    public function index(Request $request): View
    {
        $showTrash = $request->boolean('trash');
        $filter = $request->query('filter', '');
        $search = trim((string) $request->query('q', ''));
        $batchId = $request->query('batch_id');

        $query = Film::query();

        if ($showTrash) {
            $query->onlyTrashed()->where('deleted_at', '>=', now()->subDays(30))->latest('deleted_at');
        } else {
            if ($filter === 'belum_tampil') {
                $query->where('is_active', false);
            } elseif ($filter === 'tanpa_poster') {
                $query->where(fn ($q) => $q->whereNull('poster_path')->orWhere('poster_path', ''));
            } elseif ($filter === 'perlu_dilengkapi') {
                $query->where(fn ($q) => $q->whereNull('synopsis')->orWhere('synopsis', '')->orWhere('synopsis', 'like', 'Sinopsis belum tersedia%'));
            }

            if (filled($batchId)) {
                $query->where('import_batch_id', $batchId);
            }

            $query->orderByDesc('is_active')->orderBy('title');
        }

        if (filled($search)) {
            $query->where('title', 'like', '%'.$search.'%');
        }

        $allCount = Film::count();
        $inactiveCount = Film::where('is_active', false)->count();
        $noPosterCount = Film::where(fn ($q) => $q->whereNull('poster_path')->orWhere('poster_path', ''))->count();
        $needsCompletionCount = Film::where(fn ($q) => $q->whereNull('synopsis')->orWhere('synopsis', '')->orWhere('synopsis', 'like', 'Sinopsis belum tersedia%'))->count();
        $trashedCount = Film::onlyTrashed()->where('deleted_at', '>=', now()->subDays(30))->count();

        // Undo batch info if set in session
        $undoBatchId = session('undo_batch_id');
        $undoBatchCount = 0;
        if ($undoBatchId) {
            $undoBatchCount = Film::where('import_batch_id', $undoBatchId)->count();
        }

        return view('admin.films.index', [
            'films' => $query->paginate(15)->withQueryString(),
            'showTrash' => $showTrash,
            'filter' => $filter,
            'search' => $search,
            'batchId' => $batchId,
            'allCount' => $allCount,
            'inactiveCount' => $inactiveCount,
            'noPosterCount' => $noPosterCount,
            'needsCompletionCount' => $needsCompletionCount,
            'trashedCount' => $trashedCount,
            'undoBatchId' => $undoBatchId,
            'undoBatchCount' => $undoBatchCount,
            'canonicalGenres' => Film::CANONICAL_GENRES,
        ]);
    }

    public function create(): View
    {
        return view('admin.films.form', ['film' => new Film]);
    }

    public function store(Request $request, PosterProcessor $posterProcessor): RedirectResponse
    {
        $data = $this->validatedData($request);
        $poster = $request->file('poster');
        unset($data['poster']);

        if ($poster !== null) {
            $data['poster_path'] = $posterProcessor->storeAsWebp($poster);
        }

        Film::query()->create($data);

        return redirect()->route('admin.films.index')->with('status', 'Film berhasil ditambahkan.');
    }

    public function edit(Film $film): View
    {
        return view('admin.films.form', compact('film'));
    }

    public function update(Request $request, Film $film, PosterProcessor $posterProcessor): RedirectResponse
    {
        $data = $this->validatedData($request);
        $poster = $request->file('poster');
        $oldPoster = null;
        unset($data['poster']);

        if ($poster !== null) {
            $oldPoster = $film->poster_path;
            $data['poster_path'] = $posterProcessor->storeAsWebp($poster);
        }

        if (! (bool) $data['is_active']) {
            DB::transaction(function () use ($film, $data): void {
                $this->closeUpcomingSessions($film);
                $film->update($data);
            });
        } else {
            $film->update($data);
        }

        if ($oldPoster !== null) {
            Storage::disk('public')->delete($oldPoster);
        }

        return redirect()->route('admin.films.index')->with('status', 'Perubahan film berhasil disimpan.');
    }

    public function destroy(Film $film): RedirectResponse
    {
        $filmId = $film->id;
        $filmTitle = $film->title;

        DB::transaction(function () use ($film): void {
            $this->closeUpcomingSessions($film);
            $film->delete();
        });

        return redirect()->route('admin.films.index')
            ->with('status', 'Film "'.$filmTitle.'" berhasil dihapus.')
            ->with('undo_route', route('admin.films.restore', $filmId));
    }

    public function restore(int $id): RedirectResponse
    {
        $film = Film::withTrashed()->findOrFail($id);
        $film->restore();

        return redirect()->route('admin.films.index')
            ->with('status', 'Film "'.$film->title.'" berhasil dipulihkan.');
    }

    public function toggleStatus(Film $film): RedirectResponse
    {
        $newStatus = ! $film->is_active;
        if (! $newStatus) {
            DB::transaction(function () use ($film): void {
                $this->closeUpcomingSessions($film);
                $film->update(['is_active' => false]);
            });
        } else {
            $film->update(['is_active' => true]);
        }

        return back()->with('status', 'Status film berhasil diubah.');
    }

    public function duplicate(Film $film): RedirectResponse
    {
        $replica = $film->replicate();
        $replica->title = $film->title.' (Duplikat)';
        $replica->slug = Str::slug($replica->title).'-'.time();
        $replica->is_active = false;
        $replica->save();

        return redirect()->route('admin.films.edit', $replica->id)->with('status', 'Film berhasil diduplikat sebagai draft baru.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:activate,deactivate,delete,restore,set_rating,add_genre,replace_genre'],
            'film_ids' => ['nullable', 'array'],
            'film_ids.*' => ['integer'],
            'select_all' => ['nullable', 'boolean'],
            'filter' => ['nullable', 'string'],
            'q' => ['nullable', 'string'],
            'batch_id' => ['nullable', 'string'],
            'age_rating' => ['nullable', 'string', 'in:SU,13+,17+'],
            'genre' => ['nullable', 'string'],
        ], [
            'action.in' => 'Aksi yang dipilih tidak valid.',
        ]);

        $action = $validated['action'];
        $selectAll = $request->boolean('select_all');
        $count = 0;

        DB::transaction(function () use ($request, $action, $selectAll, $validated, &$count): void {
            if ($selectAll) {
                $query = Film::query();
                if ($request->boolean('trash')) {
                    $query->onlyTrashed()->where('deleted_at', '>=', now()->subDays(30));
                } else {
                    $filter = $request->input('filter');
                    if ($filter === 'belum_tampil') {
                        $query->where('is_active', false);
                    } elseif ($filter === 'tanpa_poster') {
                        $query->where(fn ($q) => $q->whereNull('poster_path')->orWhere('poster_path', ''));
                    } elseif ($filter === 'perlu_dilengkapi') {
                        $query->where(fn ($q) => $q->whereNull('synopsis')->orWhere('synopsis', '')->orWhere('synopsis', 'like', 'Sinopsis belum tersedia%'));
                    }
                    if ($request->filled('batch_id')) {
                        $query->where('import_batch_id', $request->input('batch_id'));
                    }
                }
                if ($request->filled('q')) {
                    $query->where('title', 'like', '%'.$request->input('q').'%');
                }
                $films = $query->get();
            } else {
                $ids = (array) ($validated['film_ids'] ?? []);
                if (empty($ids)) {
                    return;
                }
                $films = Film::withTrashed()->whereIn('id', $ids)->get();
            }

            foreach ($films as $film) {
                if ($action === 'restore' && $film->trashed()) {
                    $film->restore();
                    $count++;
                } elseif ($action === 'delete') {
                    $this->closeUpcomingSessions($film);
                    $film->delete();
                    $count++;
                } elseif ($action === 'activate') {
                    $film->update(['is_active' => true]);
                    $count++;
                } elseif ($action === 'deactivate') {
                    $this->closeUpcomingSessions($film);
                    $film->update(['is_active' => false]);
                    $count++;
                } elseif ($action === 'set_rating' && ! empty($validated['age_rating'])) {
                    $film->update(['age_rating' => $validated['age_rating']]);
                    $count++;
                } elseif ($action === 'replace_genre' && ! empty($validated['genre'])) {
                    $film->update(['genre' => trim($validated['genre'])]);
                    $count++;
                } elseif ($action === 'add_genre' && ! empty($validated['genre'])) {
                    $newGenre = trim($validated['genre']);
                    $existingGenres = array_filter(array_map('trim', explode(',', (string) $film->genre)));
                    if (! in_array(strtolower($newGenre), array_map('strtolower', $existingGenres), true)) {
                        $existingGenres[] = $newGenre;
                        $film->update(['genre' => implode(', ', $existingGenres)]);
                    }
                    $count++;
                }
            }
        });

        $messages = [
            'activate' => "{$count} film berhasil ditampilkan.",
            'deactivate' => "{$count} film berhasil disembunyikan.",
            'delete' => "{$count} film berhasil dipindahkan ke sampah.",
            'restore' => "{$count} film berhasil dipulihkan.",
            'set_rating' => "Klasifikasi usia {$count} film berhasil diatur ke '{$request->input('age_rating')}'.",
            'add_genre' => "Genre berhasil ditambahkan ke {$count} film.",
            'replace_genre' => "Genre {$count} film berhasil diperbarui.",
        ];

        return back()->with('status', $messages[$action] ?? "Aksi massal berhasil diterapkan pada {$count} film.");
    }

    private function closeUpcomingSessions(Film $film): void
    {
        Session::query()
            ->where('film_id', $film->id)
            ->where('session_date', '>=', now(config('app.timezone'))->toDateString())
            ->where('status', 'tersedia')
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->each(fn (Session $session) => $session->update(['status' => 'ditutup']));
    }

    private function validatedData(Request $request): array
    {
        // Support both genres array (multi-select) and direct string input
        if ($request->has('genres') && is_array($request->input('genres'))) {
            $selected = array_filter(array_map('trim', (array) $request->input('genres')));
            if (! empty($request->input('custom_genre'))) {
                $custom = array_filter(array_map('trim', explode(',', (string) $request->input('custom_genre'))));
                $selected = array_unique(array_merge($selected, $custom));
            }
            $request->merge(['genre' => implode(', ', $selected)]);
        }

        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:180'],
            'duration_minutes' => ['required', 'integer', 'between:1,600'],
            'release_year' => ['nullable', 'integer', 'between:1900,2100'],
            'genre' => ['required', 'string', 'max:150'],
            'age_rating' => ['required', 'in:SU,13+,17+'],
            'synopsis' => ['required', 'string', 'max:3000'],
            'trailer_url' => ['nullable', 'url', 'max:255'],
            'license_notes' => ['nullable', 'string', 'max:3000'],
            'is_active' => ['required', 'boolean'],
            'poster' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
                'dimensions:max_width=3000,max_height=4500',
            ],
        ], [
            'title.required' => 'Judul film wajib diisi.',
            'duration_minutes.between' => 'Durasi film harus antara 1 sampai 600 menit.',
            'age_rating.in' => 'Pilih rating SU, 13+, atau 17+.',
            'trailer_url.url' => 'Masukkan tautan trailer yang valid.',
            'poster.image' => 'Poster harus berupa file gambar.',
            'poster.mimes' => 'Poster harus JPG, PNG, atau WebP.',
            'poster.max' => 'Ukuran poster maksimal 4 MB.',
            'poster.dimensions' => 'Ukuran gambar poster maksimal 3000 × 4500 piksel.',
        ]);
    }
}
