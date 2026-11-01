<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use App\Services\MediaUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(Request $request): View
    {
        $kategori = $request->query('kategori');
        $status = $request->query('status');
        $showTrash = $request->boolean('trash') || $status === 'trash';

        $query = NewsArticle::query();

        if ($showTrash) {
            $query->onlyTrashed()->where('deleted_at', '>=', now()->subDays(30))->latest('deleted_at');
        } else {
            if (in_array($kategori, ['Berita', 'Pengumuman'], true)) {
                $query->where('kategori', $kategori);
            }

            if ($status === 'draft') {
                $query->where('status_publikasi', 'draft');
            } elseif ($status === 'terbit') {
                $query->where('status_publikasi', 'terbit');
            }

            $query->orderByDesc('is_pinned')
                ->orderByRaw('COALESCE(tanggal_terbit, created_at) DESC');
        }

        $articles = $query->paginate(15)->withQueryString();
        $trashedCount = NewsArticle::onlyTrashed()->where('deleted_at', '>=', now()->subDays(30))->count();

        return view('admin.articles.index', [
            'articles' => $articles,
            'currentCategory' => $kategori,
            'currentStatus' => $status,
            'showTrash' => $showTrash,
            'trashedCount' => $trashedCount,
        ]);
    }

    public function create(): View
    {
        return view('admin.articles.form', [
            'article' => new NewsArticle([
                'kategori' => 'Berita',
                'status_publikasi' => 'terbit',
                'tanggal_terbit' => now(),
            ]),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request, MediaUploadService $media): RedirectResponse
    {
        $data = $this->validateArticle($request);

        if ($request->hasFile('cover')) {
            $data['cover_path'] = $media->uploadImage($request->file('cover'), 'news');
        }

        $article = NewsArticle::create($data);

        return redirect()->route('admin.articles.index')
            ->with('status', 'Artikel "'.$article->judul.'" berhasil disimpan.');
    }

    public function edit(NewsArticle $article): View
    {
        return view('admin.articles.form', [
            'article' => $article,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, NewsArticle $article, MediaUploadService $media): RedirectResponse
    {
        $data = $this->validateArticle($request, $article->id);

        if ($request->boolean('delete_cover') && $article->cover_path) {
            $media->deleteImage($article->cover_path);
            $data['cover_path'] = null;
        } elseif ($request->hasFile('cover')) {
            $media->deleteImage($article->cover_path);
            $data['cover_path'] = $media->uploadImage($request->file('cover'), 'news');
        }

        $article->update($data);

        return redirect()->route('admin.articles.index')
            ->with('status', 'Artikel "'.$article->judul.'" berhasil diperbarui.');
    }

    public function destroy(NewsArticle $article): RedirectResponse
    {
        $articleId = $article->id;
        $title = $article->judul;
        $article->delete();

        return redirect()->route('admin.articles.index')
            ->with('status', 'Artikel "'.$title.'" berhasil dihapus.')
            ->with('undo_route', route('admin.articles.restore', $articleId));
    }

    public function restore(int $id): RedirectResponse
    {
        $article = NewsArticle::withTrashed()->findOrFail($id);
        $article->restore();

        return redirect()->route('admin.articles.index')
            ->with('status', 'Artikel "'.$article->judul.'" berhasil dipulihkan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateArticle(Request $request, ?int $id = null): array
    {
        $slug = trim((string) $request->input('slug'));
        if ($slug === '') {
            $slug = Str::slug((string) $request->input('judul'));
        } else {
            $slug = Str::slug($slug);
        }

        $uniqueSlug = $slug;
        $counter = 1;
        while (NewsArticle::where('slug', $uniqueSlug)->when($id, fn ($q) => $q->where('id', '!=', $id))->exists()) {
            $uniqueSlug = $slug.'-'.$counter;
            $counter++;
        }

        $request->merge(['slug' => $uniqueSlug]);

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'in:Berita,Pengumuman'],
            'ringkasan' => ['required', 'string', 'max:500'],
            'konten' => ['required', 'string', 'max:50000'],
            'cover' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:3072'],
            'status_publikasi' => ['required', 'in:draft,terbit'],
            'tanggal_terbit' => ['nullable', 'date'],
            'is_pinned' => ['required', 'boolean'],
        ], [
            'judul.required' => 'Judul artikel wajib diisi.',
            'kategori.required' => 'Pilih kategori artikel.',
            'ringkasan.required' => 'Ringkasan singkat artikel wajib diisi.',
            'konten.required' => 'Isi konten artikel wajib diisi.',
            'cover.max' => 'Ukuran file gambar cover maksimal 3 MB.',
        ]);

        unset($data['cover']);

        return $data;
    }
}
