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
    public function index(): View
    {
        return view('admin.films.index', [
            'films' => Film::query()->orderByDesc('is_active')->orderBy('title')->paginate(15),
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
        DB::transaction(function () use ($film): void {
            $this->closeUpcomingSessions($film);
            $film->delete();
        });

        return redirect()->route('admin.films.index')->with('status', 'Film dinonaktifkan dari katalog publik.');
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
