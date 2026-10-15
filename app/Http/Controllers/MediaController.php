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
        $path = 'nobar/'.$safeName;
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            abort(404, 'Poster acara tidak ditemukan.');
        }

        $extension = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'webp' => 'image/webp',
            'png' => 'image/png',
            default => 'image/jpeg',
        };

        $content = $disk->get($path);

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
        $path = 'news/'.$safeName;
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            abort(404, 'Gambar sampul artikel tidak ditemukan.');
        }

        $extension = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'webp' => 'image/webp',
            'png' => 'image/png',
            default => 'image/jpeg',
        };

        $content = $disk->get($path);

        return response($content, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
