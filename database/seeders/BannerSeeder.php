<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Film;
use App\Models\NobarEvent;
use App\Models\Promo;
use App\Support\ImagePlaceholderGenerator;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        // Generate crisp 1600x680 hero banner images
        $hero1Image = ImagePlaceholderGenerator::generate(
            'banners/hero_film_malam.png',
            1600,
            680,
            'Malam di Bukit Bintang',
            'FILM UNGGULAN PILIHAN',
            'amber',
        );

        $hero2Image = ImagePlaceholderGenerator::generate(
            'banners/hero_promo_combo.png',
            1600,
            680,
            'Paket Nonton Berdua Hemat',
            'PROMO SPESIAL STUDIO',
            'blue',
        );

        $hero3Image = ImagePlaceholderGenerator::generate(
            'banners/hero_nobar_garuda.png',
            1600,
            680,
            'Nobar Akbar Garuda Muda',
            'ACARA KOMUNITAS',
            'purple',
        );

        // Generate crisp 1200x600 snack banners
        ImagePlaceholderGenerator::generate(
            'banners/snack_combo_banner.png',
            1200,
            600,
            'Paket Combo Popcorn & Minuman',
            'MAKANAN & MINUMAN',
            'amber',
        );

        ImagePlaceholderGenerator::generate(
            'banners/snack_menu_banner.png',
            1200,
            600,
            'Menu Jajan & Camilan Sinema',
            'KANTIN BIOSKOP',
            'blue',
        );

        // Remove legacy banners without images
        Banner::where('type', 'hero')->whereNull('image_path')->delete();

        // Find reference models to link targets
        $sampleFilm = Film::query()->first();
        $samplePromo = Promo::query()->where('is_active', true)->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString())->first();
        $sampleNobar = NobarEvent::query()->first();

        $heroBanners = [
            [
                'title' => '[Contoh] Tayang Spesial: Malam di Bukit Bintang',
                'image_path' => $hero1Image,
                'target_type' => $sampleFilm ? 'film' : 'none',
                'target_id' => $sampleFilm ? (string) $sampleFilm->id : null,
                'cta_url' => $sampleFilm ? route('films.show', $sampleFilm->slug ?: $sampleFilm->id, false) : '/film',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => '[Contoh] Paket Combo Nonton Berdua Hemat',
                'image_path' => $hero2Image,
                'target_type' => $samplePromo ? 'promo' : 'none',
                'target_id' => $samplePromo ? (string) $samplePromo->id : null,
                'cta_url' => $samplePromo ? route('promos.show', $samplePromo->slug ?: $samplePromo->id, false) : '/promo',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => '[Contoh] Siaran Langsung Nobar: Garuda Muda vs Bintang Timur',
                'image_path' => $hero3Image,
                'target_type' => $sampleNobar ? 'nobar' : 'none',
                'target_id' => $sampleNobar ? (string) $sampleNobar->id : null,
                'cta_url' => $sampleNobar ? route('nobar.show', $sampleNobar->slug ?: $sampleNobar->id, false) : '/nobar',
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($heroBanners as $banner) {
            Banner::updateOrCreate(
                ['type' => 'hero', 'title' => $banner['title']],
                [
                    'type' => 'hero',
                    'title' => $banner['title'],
                    'image_path' => $banner['image_path'],
                    'target_type' => $banner['target_type'],
                    'target_id' => $banner['target_id'],
                    'cta_url' => $banner['cta_url'],
                    'sort_order' => $banner['sort_order'],
                    'is_active' => $banner['is_active'],
                ],
            );
        }
    }
}
