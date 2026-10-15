<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MediaUploadService
{
    /**
     * Upload and process a nobar event poster.
     * Enforces:
     * - Real MIME type (JPG, PNG, WebP only, rejects SVG)
     * - Max 40 megapixels total to prevent memory exhaustion
     * - Min dimension on shortest side >= 600px
     * - Downscales only if longest side > 1600px without cropping
     * - Normalizes EXIF orientation on JPEG and strips metadata
     * - Saves high quality WebP (quality >= 85) with fallback to JPEG/PNG if unsupported
     * - Generates 1200x630 Open Graph banner variant with dark blurred backdrop
     * - Random unique filenames on every replacement to prevent stale cache
     *
     * @return array{path: string, width: int, height: int, og_path: ?string}
     */
    public function uploadNobarPoster(UploadedFile $file): array
    {
        $mime = $file->getMimeType();
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw ValidationException::withMessages([
                'poster' => 'Format file poster harus berupa gambar JPG, PNG, atau WebP (bukan SVG).',
            ]);
        }

        $imageInfo = @getimagesize($file->getPathname());
        if (! $imageInfo || $imageInfo[0] === 0 || $imageInfo[1] === 0) {
            throw ValidationException::withMessages([
                'poster' => 'File poster tidak valid atau rusak.',
            ]);
        }

        [$width, $height] = $imageInfo;

        // Safety limit: max 40 megapixels to protect memory
        if (($width * $height) > 40_000_000) {
            throw ValidationException::withMessages([
                'poster' => 'Resolusi gambar terlalu besar (maksimal 40 megapiksel).',
            ]);
        }

        // Minimum dimension: shortest side must be at least 600px
        if (min($width, $height) < 600) {
            throw ValidationException::withMessages([
                'poster' => 'Dimensi poster terlalu kecil. Sisi terpendek minimal 600 piksel (saat ini: '.min($width, $height).' piksel).',
            ]);
        }

        $image = match ($mime) {
            'image/png' => @imagecreatefrompng($file->getPathname()),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file->getPathname()) : false,
            default => @imagecreatefromjpeg($file->getPathname()),
        };

        if ($image === false) {
            throw ValidationException::withMessages([
                'poster' => 'Gagal memproses berkas gambar poster.',
            ]);
        }

        // Normalize EXIF orientation on JPEG
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($file->getPathname());
            if ($exif && ! empty($exif['Orientation'])) {
                $orientation = (int) $exif['Orientation'];
                $rotated = match ($orientation) {
                    3 => imagerotate($image, 180, 0),
                    6 => imagerotate($image, -90, 0),
                    8 => imagerotate($image, 90, 0),
                    default => null,
                };

                if ($rotated !== false && $rotated !== null) {
                    imagedestroy($image);
                    $image = $rotated;
                    if (in_array($orientation, [6, 8], true)) {
                        $temp = $width;
                        $width = $height;
                        $height = $temp;
                    }
                }
            }
        }

        // Downscale ONLY if longest side > 1600px without cropping
        $longest = max($width, $height);
        $finalWidth = $width;
        $finalHeight = $height;

        if ($longest > 1600) {
            $ratio = 1600 / $longest;
            $finalWidth = (int) round($width * $ratio);
            $finalHeight = (int) round($height * $ratio);

            $resized = imagecreatetruecolor($finalWidth, $finalHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $finalWidth, $finalHeight, $transparent);

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $finalWidth, $finalHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        $canWebp = function_exists('imagewebp');
        $extension = $canWebp ? 'webp' : match ($mime) {
            'image/png' => 'png',
            default => 'jpg',
        };

        if (! $canWebp) {
            Log::warning('Fungsi imagewebp tidak tersedia di PHP. Menggunakan format fallback asli untuk poster.');
        }

        // Save main poster image with unique random name
        $posterFilename = 'poster_'.now()->format('YmdHis').'_'.Str::random(24).'.'.$extension;
        $targetPath = 'nobar/'.$posterFilename;

        $tempPath = tempnam(sys_get_temp_dir(), 'poster_');
        if ($canWebp) {
            imagewebp($image, $tempPath, 85);
        } elseif ($extension === 'png') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagepng($image, $tempPath, 8);
        } else {
            imagejpeg($image, $tempPath, 85);
        }

        Storage::disk('local')->put($targetPath, file_get_contents($tempPath));
        @unlink($tempPath);

        // Generate 1200x630 Open Graph banner
        $ogPath = $this->generateOgBannerImage($image, $finalWidth, $finalHeight, $extension, $canWebp);

        imagedestroy($image);

        return [
            'path' => $targetPath,
            'width' => $finalWidth,
            'height' => $finalHeight,
            'og_path' => $ogPath,
        ];
    }

    /**
     * Generates a 1200x630 Open Graph image with the uncropped poster centered
     * over a dark, blurred backdrop of the poster.
     *
     * @param  \GdImage|resource  $sourceImage
     */
    private function generateOgBannerImage($sourceImage, int $width, int $height, string $extension, bool $canWebp): ?string
    {
        try {
            $ogW = 1200;
            $ogH = 630;
            $canvas = imagecreatetruecolor($ogW, $ogH);

            // Base dark tone (#0f172a / rgb(15, 23, 42))
            $darkBg = imagecolorallocate($canvas, 15, 23, 42);
            imagefilledrectangle($canvas, 0, 0, $ogW, $ogH, $darkBg);

            // Resample poster to fill canvas for blurred background
            $bgThumb = imagecreatetruecolor($ogW, $ogH);
            imagecopyresampled($bgThumb, $sourceImage, 0, 0, 0, 0, $ogW, $ogH, $width, $height);

            // Apply smooth Gaussian blur passes
            for ($i = 0; $i < 12; $i++) {
                imagefilter($bgThumb, IMG_FILTER_GAUSSIAN_BLUR);
            }
            // Dim background for strong contrast
            imagefilter($bgThumb, IMG_FILTER_BRIGHTNESS, -50);

            imagecopy($canvas, $bgThumb, 0, 0, 0, 0, $ogW, $ogH);
            imagedestroy($bgThumb);

            // Center the crisp uncropped poster (fit within 1080 x 590)
            $scale = min(590 / $height, 1080 / $width);
            $drawW = (int) round($width * $scale);
            $drawH = (int) round($height * $scale);
            $drawX = (int) round(($ogW - $drawW) / 2);
            $drawY = (int) round(($ogH - $drawH) / 2);

            imagecopyresampled($canvas, $sourceImage, $drawX, $drawY, 0, 0, $drawW, $drawH, $width, $height);

            $ogFilename = 'og_'.now()->format('YmdHis').'_'.Str::random(24).'.'.$extension;
            $ogTargetPath = 'nobar/'.$ogFilename;

            $tempPath = tempnam(sys_get_temp_dir(), 'og_');
            if ($canWebp) {
                imagewebp($canvas, $tempPath, 85);
            } elseif ($extension === 'png') {
                imagepng($canvas, $tempPath, 8);
            } else {
                imagejpeg($canvas, $tempPath, 85);
            }

            imagedestroy($canvas);

            Storage::disk('local')->put($ogTargetPath, file_get_contents($tempPath));
            @unlink($tempPath);

            return $ogTargetPath;
        } catch (\Throwable $e) {
            Log::warning('Gagal membuat varian Open Graph nobar: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Creates an independent physical copy of poster and OG files for event duplication.
     *
     * @return array{poster_path: ?string, poster_og_path: ?string}
     */
    public function copyPoster(?string $posterPath, ?string $ogPath): array
    {
        $disk = Storage::disk('local');
        $newPosterPath = null;
        $newOgPath = null;

        if (filled($posterPath) && $disk->exists($posterPath)) {
            $ext = pathinfo($posterPath, PATHINFO_EXTENSION);
            $newPosterName = 'poster_copy_'.now()->format('YmdHis').'_'.Str::random(24).'.'.$ext;
            $newPosterPath = 'nobar/'.$newPosterName;
            $disk->copy($posterPath, $newPosterPath);
        }

        if (filled($ogPath) && $disk->exists($ogPath)) {
            $ext = pathinfo($ogPath, PATHINFO_EXTENSION);
            $newOgName = 'og_copy_'.now()->format('YmdHis').'_'.Str::random(24).'.'.$ext;
            $newOgPath = 'nobar/'.$newOgName;
            $disk->copy($ogPath, $newOgPath);
        }

        return [
            'poster_path' => $newPosterPath,
            'poster_og_path' => $newOgPath,
        ];
    }

    /**
     * Delete poster and associated OG banner file.
     */
    public function deletePosterAndOg(?string $posterPath, ?string $ogPath = null): void
    {
        $disk = Storage::disk('local');
        if (filled($posterPath) && $disk->exists($posterPath)) {
            $disk->delete($posterPath);
        }
        if (filled($ogPath) && $disk->exists($ogPath)) {
            $disk->delete($ogPath);
        }
    }

    /**
     * Standard upload for general articles / thumbnails.
     */
    public function uploadImage(UploadedFile $file, string $folder = 'news', int $maxDimension = 1400): string
    {
        $canWebp = function_exists('imagewebp');
        $extension = $canWebp ? 'webp' : match ($file->getMimeType()) {
            'image/png' => 'png',
            default => 'jpg',
        };

        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $filename = ($filename !== '' ? substr($filename, 0, 30).'_' : '').now()->format('YmdHis').'_'.Str::random(16).'.'.$extension;
        $targetPath = trim($folder, '/').'/'.$filename;

        [$width, $height] = @getimagesize($file->getPathname()) ?: [0, 0];

        $image = match ($file->getMimeType()) {
            'image/png' => @imagecreatefrompng($file->getPathname()),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file->getPathname()) : false,
            default => @imagecreatefromjpeg($file->getPathname()),
        };

        if ($image === false || $width === 0 || $height === 0) {
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
