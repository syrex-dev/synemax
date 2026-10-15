<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use App\Models\NobarEvent;
use App\Services\CinemaSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\View\View;

class PublicInfoController extends Controller
{
    private function ensureFeatureEnabled(): void
    {
        if (! app(CinemaSettings::class)->infoNobarEnabled()) {
            abort(404);
        }
    }

    public function nobarIndex(Request $request): View
    {
        $this->ensureFeatureEnabled();

        $filter = $request->query('status', 'semua');

        $query = NobarEvent::query()->published();

        if ($filter === 'mendatang') {
            $query->where('status_acara', 'aktif')
                ->where('kickoff_at', '>=', now()->subHours(4));
        } elseif ($filter === 'selesai') {
            $query->where('kickoff_at', '<', now()->subHours(4));
        }

        $events = $query->orderByDesc('is_pinned')
            ->orderBy('kickoff_at')
            ->paginate(12)
            ->withQueryString();

        return view('public.nobar.index', [
            'events' => $events,
            'currentFilter' => $filter,
        ]);
    }

    public function nobarShow(Request $request, string $slug): View
    {
        $this->ensureFeatureEnabled();

        $isAdmin = (bool) $request->user()?->is_admin;

        $event = NobarEvent::query()
            ->where('slug', $slug)
            ->when(! $isAdmin, fn ($q) => $q->published())
            ->firstOrFail();

        $otherEvents = NobarEvent::query()
            ->published()
            ->where('id', '!=', $event->id)
            ->where('kickoff_at', '>=', now()->subHours(4))
            ->orderBy('kickoff_at')
            ->limit(3)
            ->get();

        return view('public.nobar.show', [
            'event' => $event,
            'otherEvents' => $otherEvents,
            'isPreview' => $event->status_publikasi !== 'terbit',
        ]);
    }

    public function nobarCalendar(string $slug): HttpResponse
    {
        $this->ensureFeatureEnabled();

        $event = NobarEvent::query()
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        $ics = $event->generateIcs();
        $filename = 'nobar-'.$event->slug.'.ics';

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function articleIndex(Request $request): View
    {
        $this->ensureFeatureEnabled();

        $kategori = $request->query('kategori');

        $query = NewsArticle::query()->published();

        if (in_array($kategori, ['Berita', 'Promo', 'Pengumuman'], true)) {
            $query->where('kategori', $kategori);
        }

        $articles = $query->orderByDesc('is_pinned')
            ->orderByRaw('COALESCE(tanggal_terbit, created_at) DESC')
            ->paginate(12)
            ->withQueryString();

        return view('public.articles.index', [
            'articles' => $articles,
            'currentCategory' => $kategori,
        ]);
    }

    public function articleShow(Request $request, string $slug): View
    {
        $this->ensureFeatureEnabled();

        $isAdmin = (bool) $request->user()?->is_admin;

        $article = NewsArticle::query()
            ->where('slug', $slug)
            ->when(! $isAdmin, fn ($q) => $q->published())
            ->firstOrFail();

        $relatedArticles = NewsArticle::query()
            ->published()
            ->where('id', '!=', $article->id)
            ->where('kategori', $article->kategori)
            ->orderByRaw('COALESCE(tanggal_terbit, created_at) DESC')
            ->limit(3)
            ->get();

        return view('public.articles.show', [
            'article' => $article,
            'relatedArticles' => $relatedArticles,
            'isPreview' => ! $article->isLive(),
        ]);
    }
}
