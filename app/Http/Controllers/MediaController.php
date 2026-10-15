<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MediaController extends Controller
{
    public function nobarPoster(string $filename): Response
    {
        // Sanitize filename to prevent directory traversal
        $safeName = basename($filename);
        $path = 'nobar/'.$safeName;
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            abort(404, 'Poster acara tidak ditemukan.');
        }

        $mime = $disk->mimeType($path) ?: 'image/jpeg';
        $content = $disk->get($path);

        return response($content, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function articleCover(string $filename): Response
    {
        $safeName = basename($filename);
        $path = 'news/'.$safeName;
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            abort(404, 'Gambar sampul artikel tidak ditemukan.');
        }

        $mime = $disk->mimeType($path) ?: 'image/jpeg';
        $content = $disk->get($path);

        return response($content, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
