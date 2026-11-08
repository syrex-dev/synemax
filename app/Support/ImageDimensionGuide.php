<?php

namespace App\Support;

class ImageDimensionGuide
{
    /**
     * Sumber tunggal kanonik panduan dan validator dimensi gambar SynemaX.
     *
     * @return array<string, array{
     *     key: string,
     *     label: string,
     *     width: int,
     *     height: int,
     *     ratio: string,
     *     max_mb: int,
     *     hint: string,
     *     allowed_mimes: list<string>
     * }>
     */
    public static function all(): array
    {
        return [
            'hero_desktop' => [
                'key' => 'hero_desktop',
                'label' => 'Banner Hero Desktop',
                'width' => 1600,
                'height' => 680,
                'ratio' => '2.35:1',
                'max_mb' => 3,
                'hint' => 'Rasio 2,35:1 (1600 × 680 px), JPG/PNG/WebP, maks 3 MB. Visual murni tanpa teks bertumpuk.',
                'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            ],
            'hero_mobile' => [
                'key' => 'hero_mobile',
                'label' => 'Banner Hero Mobile',
                'width' => 1200,
                'height' => 900,
                'ratio' => '4:3',
                'max_mb' => 3,
                'hint' => 'Rasio 4:3 (1200 × 900 px), JPG/PNG/WebP, maks 3 MB. Vertikal layar ponsel.',
                'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            ],
            'film_poster' => [
                'key' => 'film_poster',
                'label' => 'Poster Film Resmi',
                'width' => 600,
                'height' => 900,
                'ratio' => '2:3',
                'max_mb' => 5,
                'hint' => 'Rasio portrait 2:3 (600 × 900 px), JPG/PNG/WebP, maks 5 MB.',
                'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            ],
            'promo_banner' => [
                'key' => 'promo_banner',
                'label' => 'Banner Promo & Penawaran',
                'width' => 1200,
                'height' => 600,
                'ratio' => '2:1',
                'max_mb' => 2,
                'hint' => 'Rasio 2:1 (1200 × 600 px), JPG/PNG/WebP, maks 2 MB. Gambar murni tanpa teks bertumpuk.',
                'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            ],
            'nobar_poster' => [
                'key' => 'nobar_poster',
                'label' => 'Poster Acara Nobar',
                'width' => 1200,
                'height' => 600,
                'ratio' => '2:1 / Fleksibel',
                'max_mb' => 3,
                'hint' => 'Disarankan 1200 × 600 px (landscape) atau 600 × 900 px (portrait), min 600 px tiap sisi, maks 3 MB.',
                'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            ],
            'news_cover' => [
                'key' => 'news_cover',
                'label' => 'Sampul Kabar / Berita',
                'width' => 1200,
                'height' => 675,
                'ratio' => '16:9',
                'max_mb' => 3,
                'hint' => 'Rasio 16:9 (1200 × 675 px), JPG/PNG/WebP, maks 3 MB.',
                'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            ],
            'qris_image' => [
                'key' => 'qris_image',
                'label' => 'Gambar QRIS Bioskop',
                'width' => 500,
                'height' => 500,
                'ratio' => '1:1',
                'max_mb' => 2,
                'hint' => 'Persegi 1:1 (min 300 × 300 px, disarankan 500 × 500 px), PNG/JPG/WebP, maks 2 MB.',
                'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            ],
            'site_logo' => [
                'key' => 'site_logo',
                'label' => 'Logo Navbar (Mode Terang/Gelap)',
                'width' => 400,
                'height' => 80,
                'ratio' => '5:1 / Fleksibel',
                'max_mb' => 1,
                'hint' => 'Tinggi standar 80 px, lebar maks 400 px, PNG/SVG/WebP transparan, maks 1 MB.',
                'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'],
            ],
            'site_og_image' => [
                'key' => 'site_og_image',
                'label' => 'OG Image (Media Sosial / WA)',
                'width' => 1200,
                'height' => 630,
                'ratio' => '1.91:1',
                'max_mb' => 2,
                'hint' => 'Preview share link (1200 × 630 px), JPG/PNG/WebP, maks 2 MB.',
                'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            ],
            'site_favicon' => [
                'key' => 'site_favicon',
                'label' => 'Favicon Tab Browser',
                'width' => 512,
                'height' => 512,
                'ratio' => '1:1',
                'max_mb' => 1,
                'hint' => 'Persegi 512 × 512 px, PNG/SVG/ICO, maks 1 MB.',
                'allowed_mimes' => ['image/png', 'image/x-icon', 'image/svg+xml'],
            ],
            'member_photo' => [
                'key' => 'member_photo',
                'label' => 'Foto Kartu Member',
                'width' => 400,
                'height' => 400,
                'ratio' => '1:1',
                'max_mb' => 1,
                'hint' => 'Persegi 1:1 (400 × 400 px), WebP/JPG/PNG, disarankan <= 60 KB.',
                'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            ],
            'snack_photo' => [
                'key' => 'snack_photo',
                'label' => 'Foto Menu Jajan',
                'width' => 600,
                'height' => 600,
                'ratio' => '1:1',
                'max_mb' => 2,
                'hint' => 'Persegi 1:1 (600 × 600 px), WebP/JPG/PNG, maks 2 MB.',
                'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            ],
        ];
    }

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     width: int,
     *     height: int,
     *     ratio: string,
     *     max_mb: int,
     *     hint: string,
     *     allowed_mimes: list<string>
     * }|null
     */
    public static function get(string $key): ?array
    {
        return static::all()[$key] ?? null;
    }

    /**
     * Kembalikan teks petunjuk unggah untuk suatu komponen gambar.
     */
    public static function hint(string $key): string
    {
        return static::get($key)['hint'] ?? '';
    }

    /**
     * Kembalikan aturan validasi Laravel terpadu untuk komponen gambar.
     *
     * @return list<string>
     */
    public static function validationRule(string $key, bool $required = false): array
    {
        $spec = static::get($key);
        if (! $spec) {
            return [$required ? 'required' : 'nullable', 'image'];
        }

        $extensions = array_map(function (string $mime): string {
            return match ($mime) {
                'image/jpeg' => 'jpeg,jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/svg+xml' => 'svg',
                'image/x-icon' => 'ico',
                default => 'png',
            };
        }, $spec['allowed_mimes']);

        $mimesList = implode(',', $extensions);
        $maxKb = $spec['max_mb'] * 1024;

        return [
            $required ? 'required' : 'nullable',
            'image',
            "mimes:{$mimesList}",
            "max:{$maxKb}",
        ];
    }
}
