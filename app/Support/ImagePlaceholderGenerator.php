<?php

namespace App\Support;

class ImagePlaceholderGenerator
{
    /**
     * Generate a modern, visual placeholder image using TrueType font and geometric accents.
     *
     * @param  string  $relativePath  Relative to storage/app/public/ (e.g. 'banners/hero_1.png')
     * @param  int  $width  Image width in pixels
     * @param  int  $height  Image height in pixels
     * @param  string  $title  Primary title text
     * @param  string  $category  Category/badge label (e.g. 'PROMO SPESIAL', 'SYNEMAX PREMIERE')
     * @param  string  $colorScheme  'amber', 'blue', or 'purple'
     * @return string Relative storage path
     */
    public static function generate(
        string $relativePath,
        int $width,
        int $height,
        string $title,
        string $category = 'SYNEMAX',
        string $colorScheme = 'amber'
    ): string {
        $fullPath = storage_path('app/public/'.$relativePath);
        $directory = dirname($fullPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        // Solid neutral background (Zinc 900 / Slate 900 neutral dark tile)
        $bgColor = imagecolorallocate($image, 24, 24, 27); // #18181b
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

        // Clean 1px subtle neutral border
        $borderColor = imagecolorallocate($image, 39, 39, 42); // #27272a
        imagerectangle($image, 0, 0, $width - 1, $height - 1, $borderColor);

        // Typography Setup
        $fontPath = resource_path('fonts/Inter.ttf');
        $hasTtf = file_exists($fontPath) && function_exists('imagettftext');

        $cleanTitle = trim(strip_tags($title));
        $cleanCategory = strtoupper(trim(strip_tags($category)));

        if ($hasTtf) {
            // Category Badge
            $catFontSize = max(11, (int) round($height * 0.028));
            $catBox = imagettfbbox($catFontSize, 0, $fontPath, $catCategoryText = $cleanCategory ?: 'SYNEMAX');
            $catWidth = abs($catBox[4] - $catBox[0]);
            $catHeight = abs($catBox[5] - $catBox[1]);

            // Badge Background Pill
            $pillPadX = 16;
            $pillPadY = 8;
            $pillW = $catWidth + ($pillPadX * 2);
            $pillH = $catHeight + ($pillPadY * 2);
            $pillX = (int) (($width - $pillW) / 2);
            $pillY = (int) ($height * 0.28);

            $badgeBg = imagecolorallocate($image, 39, 39, 42); // Zinc 800
            $badgeBorder = imagecolorallocate($image, 63, 63, 70); // Zinc 700
            imagefilledrectangle($image, $pillX, $pillY, $pillX + $pillW, $pillY + $pillH, $badgeBg);
            imagerectangle($image, $pillX, $pillY, $pillX + $pillW, $pillY + $pillH, $badgeBorder);

            // Badge Text (Neutral Silver Zinc 300)
            $catColor = imagecolorallocate($image, 212, 212, 216);
            $catTextX = $pillX + $pillPadX;
            $catTextY = $pillY + $pillPadY + $catHeight;
            imagettftext($image, $catFontSize, 0, $catTextX, $catTextY, $catColor, $fontPath, $catCategoryText);

            // Large Title Text (6-8% of image height)
            $titleFontSize = max(16, (int) round($height * 0.065));
            $whiteColor = imagecolorallocate($image, 255, 255, 255);
            $shadowColor = imagecolorallocatealpha($image, 0, 0, 0, 60);

            // Wrap title into up to 3 lines
            $lines = self::wrapText($cleanTitle, $titleFontSize, $fontPath, (int) ($width * 0.85));
            if (count($lines) > 3) {
                $lines = array_slice($lines, 0, 3);
                $lines[2] = rtrim($lines[2], '.').'...';
            }

            $lineHeight = (int) round($titleFontSize * 1.35);
            $totalTextHeight = count($lines) * $lineHeight;
            $startY = (int) ($height * 0.44) + (int) (($height * 0.45 - $totalTextHeight) / 2) + $titleFontSize;

            foreach ($lines as $i => $line) {
                $box = imagettfbbox($titleFontSize, 0, $fontPath, $line);
                $lineWidth = abs($box[4] - $box[0]);
                $lineX = (int) (($width - $lineWidth) / 2);
                $lineY = $startY + ($i * $lineHeight);

                // Subtle text shadow
                imagettftext($image, $titleFontSize, 0, $lineX + 2, $lineY + 2, $shadowColor, $fontPath, $line);
                // Main white text
                imagettftext($image, $titleFontSize, 0, $lineX, $lineY, $whiteColor, $fontPath, $line);
            }
        } else {
            // GD built-in font fallback
            $catColor = imagecolorallocate($image, 252, 211, 77);
            $font = 4;
            $tagX = max(20, (int) (($width - strlen($cleanCategory) * imagefontwidth($font)) / 2));
            $tagY = max(20, (int) ($height * 0.35));
            imagestring($image, $font, $tagX, $tagY, $cleanCategory, $catColor);

            $whiteColor = imagecolorallocate($image, 255, 255, 255);
            $fontTitle = 5;
            $titleClean = StrLimit($cleanTitle, (int) ($width / 14));
            $titleX = max(20, (int) (($width - strlen($titleClean) * imagefontwidth($fontTitle)) / 2));
            $titleY = (int) ($height * 0.50);
            imagestring($image, $fontTitle, $titleX, $titleY, $titleClean, $whiteColor);
        }

        imagepng($image, $fullPath);
        imagedestroy($image);

        return $relativePath;
    }

    /**
     * Wrap text into lines that fit within max pixel width using TrueType font metrics.
     *
     * @return array<int, string>
     */
    protected static function wrapText(string $text, int $fontSize, string $fontPath, int $maxWidth): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine === '' ? $word : $currentLine.' '.$word;
            $box = imagettfbbox($fontSize, 0, $fontPath, $testLine);
            $width = abs($box[4] - $box[0]);

            if ($width > $maxWidth && $currentLine !== '') {
                $lines[] = $currentLine;
                $currentLine = $word;
            } else {
                $currentLine = $testLine;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines;
    }
}

function StrLimit(string $value, int $limit = 100): string
{
    if (mb_strwidth($value, 'UTF-8') <= $limit) {
        return $value;
    }

    return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')).'...';
}
