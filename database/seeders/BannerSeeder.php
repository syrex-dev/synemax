<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        // Deactivate old placeholder sample banners
        Banner::where('type', 'hero')->where(function ($q) {
            $q->where('is_sample', true)->orWhere('title', 'like', '%[Contoh]%');
        })->update(['is_active' => false]);

        // Generate high quality photographic banners from studio photo
        $sourcePhoto = storage_path('app/public/branding/gHn5Pu77TcsOV5anoY90VEMnOpwGH7FS9YoTTqVY.jpg');
        $fontPath = resource_path('fonts/Inter.ttf');
        $bannerDir = storage_path('app/public/banners');
        if (! is_dir($bannerDir)) {
            mkdir($bannerDir, 0755, true);
        }

        $heroBanners = [
            [
                'filename' => 'hero_synemax_studio.jpg',
                'title' => 'Sensasi Nonton VIP dengan Tata Suara Dolby Surround',
                'badge' => 'STUDIO BIOSKOP PRIVAT EKSKLUSIF',
                'subtitle' => 'Kapasitas privat 6 kursi sofa empuk, pendingin udara dingin, dan privasi penuh bersama orang tersayang.',
                'cta_url' => '/jadwal',
                'sort_order' => 1,
            ],
            [
                'filename' => 'hero_sewa_privat.jpg',
                'title' => 'Bebas Pilih Film Sendiri dari Katalog Sinema Lengkap',
                'badge' => 'SEWA STUDIO ON-DEMAND',
                'subtitle' => 'Pesan seluruh studio untuk 6 penonton atau gabung sesi publik mulai dari Rp 20.000 / orang.',
                'cta_url' => '/film',
                'sort_order' => 2,
            ],
            [
                'filename' => 'hero_nobar_seru.jpg',
                'title' => 'Nonton Bareng Pertandingan & Film Favorit Lebih Seru',
                'badge' => 'NOBAR & ACARA KOMUNITAS',
                'subtitle' => 'Dilengkapi aneka camilan hangat, popcorn renyah, dan minuman segar untuk menemani tontonan Anda.',
                'cta_url' => '/nobar',
                'sort_order' => 3,
            ],
        ];

        if (! app()->runningUnitTests() && file_exists($sourcePhoto) && function_exists('imagecreatefromjpeg')) {
            @ini_set('memory_limit', '512M');
            $srcImg = @imagecreatefromjpeg($sourcePhoto);
            if ($srcImg) {
                $srcW = imagesx($srcImg);
                $srcH = imagesy($srcImg);
                $bannerW = 1600;
                $bannerH = 680;

                foreach ($heroBanners as $banner) {
                    $destPath = $bannerDir.'/'.$banner['filename'];
                    if (file_exists($destPath)) {
                        continue;
                    }

                    $canvas = imagecreatetruecolor($bannerW, $bannerH);
                    $targetAspect = $bannerW / $bannerH;
                    $srcAspect = $srcW / $srcH;

                    if ($srcAspect > $targetAspect) {
                        $cropH = $srcH;
                        $cropW = (int) round($srcH * $targetAspect);
                        $cropX = (int) round(($srcW - $cropW) / 2);
                        $cropY = 0;
                    } else {
                        $cropW = $srcW;
                        $cropH = (int) round($srcW / $targetAspect);
                        $cropX = 0;
                        $cropY = (int) round(($srcH - $cropH) / 2);
                    }

                    imagecopyresampled($canvas, $srcImg, 0, 0, $cropX, $cropY, $bannerW, $bannerH, $cropW, $cropH);

                    // Left dark gradient
                    for ($x = 0; $x < $bannerW; $x++) {
                        $leftRatio = max(0, 1 - ($x / 1100));
                        $alpha = (int) round($leftRatio * 75);
                        if ($alpha > 0) {
                            $overlayColor = imagecolorallocatealpha($canvas, 15, 23, 42, 127 - $alpha);
                            imageline($canvas, $x, 0, $x, $bannerH, $overlayColor);
                        }
                    }

                    // Bottom dark gradient
                    for ($y = $bannerH - 180; $y < $bannerH; $y++) {
                        $bottomRatio = ($y - ($bannerH - 180)) / 180;
                        $alpha = (int) round($bottomRatio * 60);
                        $bottomColor = imagecolorallocatealpha($canvas, 0, 0, 0, 127 - $alpha);
                        imageline($canvas, 0, $y, $bannerW, $y, $bottomColor);
                    }

                    // Typography
                    if (file_exists($fontPath) && function_exists('imagettftext')) {
                        // Badge
                        $badgeText = $banner['badge'];
                        $badgeFontSize = 13;
                        $badgeBox = imagettfbbox($badgeFontSize, 0, $fontPath, $badgeText);
                        $badgeW = abs($badgeBox[4] - $badgeBox[0]) + 24;
                        $badgeH = abs($badgeBox[5] - $badgeBox[1]) + 14;
                        $badgeX = 80;
                        $badgeY = 140;

                        $badgeBg = imagecolorallocatealpha($canvas, 245, 158, 11, 40);
                        $badgeBorder = imagecolorallocatealpha($canvas, 245, 158, 11, 10);
                        $badgeTextColor = imagecolorallocate($canvas, 254, 243, 199);

                        imagefilledrectangle($canvas, $badgeX, $badgeY, $badgeX + $badgeW, $badgeY + $badgeH, $badgeBg);
                        imagerectangle($canvas, $badgeX, $badgeY, $badgeX + $badgeW, $badgeY + $badgeH, $badgeBorder);
                        imagettftext($canvas, $badgeFontSize, 0, $badgeX + 12, $badgeY + $badgeH - 7, $badgeTextColor, $fontPath, $badgeText);

                        // Title
                        $titleFontSize = 32;
                        $titleColor = imagecolorallocate($canvas, 255, 255, 255);
                        $titleShadow = imagecolorallocatealpha($canvas, 0, 0, 0, 40);

                        $words = explode(' ', $banner['title']);
                        $lines = [];
                        $currentLine = '';
                        foreach ($words as $word) {
                            $testLine = $currentLine === '' ? $word : $currentLine.' '.$word;
                            $box = imagettfbbox($titleFontSize, 0, $fontPath, $testLine);
                            if (abs($box[4] - $box[0]) > 900 && $currentLine !== '') {
                                $lines[] = $currentLine;
                                $currentLine = $word;
                            } else {
                                $currentLine = $testLine;
                            }
                        }
                        if ($currentLine !== '') {
                            $lines[] = $currentLine;
                        }

                        $titleY = $badgeY + $badgeH + 45;
                        foreach ($lines as $i => $line) {
                            $lineY = $titleY + ($i * 44);
                            imagettftext($canvas, $titleFontSize, 0, $badgeX + 2, $lineY + 2, $titleShadow, $fontPath, $line);
                            imagettftext($canvas, $titleFontSize, 0, $badgeX, $lineY, $titleColor, $fontPath, $line);
                        }

                        // Subtitle
                        $subFontSize = 16;
                        $subColor = imagecolorallocate($canvas, 203, 213, 225);
                        $subShadow = imagecolorallocatealpha($canvas, 0, 0, 0, 50);
                        $subY = $titleY + (count($lines) * 44) + 15;

                        $subWords = explode(' ', $banner['subtitle']);
                        $subLines = [];
                        $currentSub = '';
                        foreach ($subWords as $word) {
                            $testSub = $currentSub === '' ? $word : $currentSub.' '.$word;
                            $box = imagettfbbox($subFontSize, 0, $fontPath, $testSub);
                            if (abs($box[4] - $box[0]) > 850 && $currentSub !== '') {
                                $subLines[] = $currentSub;
                                $currentSub = $word;
                            } else {
                                $currentSub = $testSub;
                            }
                        }
                        if ($currentSub !== '') {
                            $subLines[] = $currentSub;
                        }

                        foreach ($subLines as $j => $sline) {
                            $lineY = $subY + ($j * 26);
                            imagettftext($canvas, $subFontSize, 0, $badgeX + 1, $lineY + 1, $subShadow, $fontPath, $sline);
                            imagettftext($canvas, $subFontSize, 0, $badgeX, $lineY, $subColor, $fontPath, $sline);
                        }
                    }

                    imagejpeg($canvas, $destPath, 90);
                    imagedestroy($canvas);
                }
                imagedestroy($srcImg);
            }
        }

        foreach ($heroBanners as $banner) {
            Banner::updateOrCreate(
                ['type' => 'hero', 'title' => $banner['title']],
                [
                    'type' => 'hero',
                    'title' => $banner['title'],
                    'badge' => $banner['badge'],
                    'image_path' => 'banners/'.$banner['filename'],
                    'target_type' => 'none',
                    'target_id' => null,
                    'cta_url' => $banner['cta_url'],
                    'sort_order' => $banner['sort_order'],
                    'is_active' => true,
                    'is_sample' => false,
                ],
            );
        }
    }
}
