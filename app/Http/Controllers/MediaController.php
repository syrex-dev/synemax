<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MediaController extends Controller
{
    public function nobarPoster(string $filename): Response
    {
        if (! preg_match('/^[a-zA-Z0-9_\-]+\.(webp|png|jpg|jpeg)$/i', $filename)) {
            abort(404, 'Nama file poster tidak valid.');
        }

        $safeName = basename($filename);
        $candidates = [
            ['disk' => 'local', 'path' => 'nobar/'.$safeName],
            ['disk' => 'public', 'path' => 'posters/'.$safeName],
            ['disk' => 'public', 'path' => 'nobar/'.$safeName],
            ['disk' => 'local', 'path' => 'posters/'.$safeName],
        ];

        $matchedDisk = null;
        $matchedPath = null;
        foreach ($candidates as $cand) {
            if (Storage::disk($cand['disk'])->exists($cand['path'])) {
                $matchedDisk = Storage::disk($cand['disk']);
                $matchedPath = $cand['path'];
                break;
            }
        }

        if (! $matchedDisk || ! $matchedPath) {
            abort(404, 'Poster acara tidak ditemukan.');
        }

        $extension = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'webp' => 'image/webp',
            'png' => 'image/png',
            default => 'image/jpeg',
        };

        $content = $matchedDisk->get($matchedPath);

        return response($content, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function articleCover(string $filename): Response
    {
        if (! preg_match('/^[a-zA-Z0-9_\-]+\.(webp|png|jpg|jpeg)$/i', $filename)) {
            abort(404, 'Nama file sampul tidak valid.');
        }

        $safeName = basename($filename);
        $candidates = [
            ['disk' => 'local', 'path' => 'news/'.$safeName],
            ['disk' => 'public', 'path' => 'articles/'.$safeName],
            ['disk' => 'public', 'path' => 'news/'.$safeName],
            ['disk' => 'local', 'path' => 'articles/'.$safeName],
        ];

        $matchedDisk = null;
        $matchedPath = null;
        foreach ($candidates as $cand) {
            if (Storage::disk($cand['disk'])->exists($cand['path'])) {
                $matchedDisk = Storage::disk($cand['disk']);
                $matchedPath = $cand['path'];
                break;
            }
        }

        if (! $matchedDisk || ! $matchedPath) {
            abort(404, 'Gambar sampul artikel tidak ditemukan.');
        }

        $extension = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'webp' => 'image/webp',
            'png' => 'image/png',
            default => 'image/jpeg',
        };

        $content = $matchedDisk->get($matchedPath);

        return response($content, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
