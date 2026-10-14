<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class WebpImageProcessor
{
    public function store(UploadedFile $upload, string $directory, int $maxWidth = 500): string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            throw new RuntimeException('Ekstensi GD dengan dukungan WebP wajib aktif untuk memproses gambar.');
        }

        $contents = file_get_contents($upload->getRealPath());
        $source = $contents === false ? false : imagecreatefromstring($contents);

        if ($source === false) {
            throw new RuntimeException('Gambar tidak bisa dibaca sebagai gambar yang valid.');
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $targetWidth = min($maxWidth, $sourceWidth);
        $targetHeight = max(1, (int) round($sourceHeight * ($targetWidth / $sourceWidth)));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight,
        );

        ob_start();
        $encoded = imagewebp($target, null, 78);
        $webp = ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);

        if (! $encoded || $webp === false) {
            throw new RuntimeException('Gambar gagal dikompres ke WebP.');
        }

        $path = trim($directory, '/').'/'.Str::uuid().'.webp';

        if (! Storage::disk('public')->put($path, $webp)) {
            throw new RuntimeException('Gambar WebP gagal disimpan.');
        }

        return $path;
    }
}
