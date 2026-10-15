<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaUploadService
{
    public function uploadImage(UploadedFile $file, string $folder = 'nobar', int $maxDimension = 1400): string
    {
        $canWebp = function_exists('imagewebp');
        $extension = $canWebp ? 'webp' : match ($file->getMimeType()) {
            'image/png' => 'png',
            default => 'jpg',
        };

        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $filename = ($filename !== '' ? substr($filename, 0, 30).'_' : '').Str::random(16).'.'.$extension;
        $targetPath = trim($folder, '/').'/'.$filename;

        [$width, $height] = @getimagesize($file->getPathname()) ?: [0, 0];

        $image = match ($file->getMimeType()) {
            'image/png' => @imagecreatefrompng($file->getPathname()),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file->getPathname()) : false,
            default => @imagecreatefromjpeg($file->getPathname()),
        };

        if ($image === false || $width === 0 || $height === 0) {
            // If GD fails to parse or image function unavailable, fallback to direct store
            return $file->storeAs(trim($folder, '/'), $filename, 'local');
        }

        if ($file->getMimeType() === 'image/png' && ! $canWebp) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        if ($width > $maxDimension || $height > $maxDimension) {
            $ratio = min($maxDimension / $width, $maxDimension / $height);
            $newWidth = (int) round($width * $ratio);
            $newHeight = (int) round($height * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            if ($extension === 'png' || $extension === 'webp') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'media_');
        if ($canWebp) {
            imagewebp($image, $tempPath, 85);
        } elseif ($extension === 'png') {
            imagepng($image, $tempPath, 8);
        } else {
            imagejpeg($image, $tempPath, 85);
        }
        imagedestroy($image);

        Storage::disk('local')->put($targetPath, file_get_contents($tempPath));
        @unlink($tempPath);

        return $targetPath;
    }

    public function deleteImage(?string $path): void
    {
        if (filled($path) && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    public function supportsWebp(): bool
    {
        return function_exists('imagewebp');
    }
}
