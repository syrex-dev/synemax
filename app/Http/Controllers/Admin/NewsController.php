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

        $query = NewsArticle::query();

        if (in_array($kategori, ['Berita', 'Promo', 'Pengumuman'], true)) {
            $query->where('kategori', $kategori);
        }

        if ($status === 'draft') {
            $query->where('status_publikasi', 'draft');
        } elseif ($status === 'terbit') {
            $query->where('status_publikasi', 'terbit');
        }

        $articles = $query->orderByDesc('is_pinned')
            ->orderByRaw('COALESCE(tanggal_terbit, created_at) DESC')
            ->paginate(15)
            ->withQueryString();

        return view('admin.articles.index', [
            'articles' => $articles,
            'currentCategory' => $kategori,
            'currentStatus' => $status,
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

    public function destroy(NewsArticle $article, MediaUploadService $media): RedirectResponse
    {
        $media->deleteImage($article->cover_path);
        $article->delete();

        return redirect()->route('admin.articles.index')
            ->with('status', 'Artikel berhasil dihapus.');
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
            'kategori' => ['required', 'in:Berita,Promo,Pengumuman'],
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
