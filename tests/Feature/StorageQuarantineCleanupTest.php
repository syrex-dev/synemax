<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Film;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\NewsArticle;
use App\Models\NobarEvent;
use App\Models\Promo;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageQuarantineCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_all_referenced_images_are_exempt_from_cleanup(): void
    {
        // 1. Film poster & banner
        Storage::disk('public')->put('posters/film_poster.webp', 'data');
        Storage::disk('public')->put('banners/film_banner.webp', 'data');
        Film::query()->create([
            'title' => 'Film Referensi',
            'duration_minutes' => 90,
            'genre' => 'Drama',
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis referensi.',
            'poster_path' => 'posters/film_poster.webp',
            'banner_path' => 'banners/film_banner.webp',
            'is_active' => true,
        ]);

        // 2. Banner desktop & mobile
        Storage::disk('public')->put('banners/hero_desktop.webp', 'data');
        Storage::disk('public')->put('banners/hero_mobile.webp', 'data');
        Banner::query()->create([
            'title' => 'Banner Uji',
            'type' => 'hero',
            'image_path' => 'banners/hero_desktop.webp',
            'mobile_image_path' => 'banners/hero_mobile.webp',
            'is_active' => true,
        ]);

        // 3. Promo banner
        Storage::disk('public')->put('promos/promo_banner.webp', 'data');
        Promo::query()->create([
            'title' => 'Promo Uji',
            'slug' => 'promo-uji',
            'image_path' => 'promos/promo_banner.webp',
            'description' => 'Deskripsi promo uji.',
            'is_active' => true,
        ]);

        // 4. Nobar poster
        Storage::disk('public')->put('nobar/nobar_poster.webp', 'data');
        NobarEvent::query()->create([
            'judul' => 'Nobar Final',
            'slug' => 'nobar-final',
            'kompetisi' => 'Liga Champions',
            'tim_a' => 'Tim A',
            'tim_b' => 'Tim B',
            'kickoff_at' => now()->addDays(5),
            'durasi_menit' => 90,
            'lokasi_nama' => 'Studio Utama SynemaX',
            'alamat_patokan' => 'Jl. Mawar No. 10',
            'maps_url' => 'https://maps.google.com',
            'is_free' => true,
            'poster_path' => 'nobar/nobar_poster.webp',
            'status_publikasi' => 'terbit',
        ]);

        // 5. News cover
        Storage::disk('public')->put('news/news_cover.webp', 'data');
        NewsArticle::query()->create([
            'judul' => 'Kabar Bioskop',
            'slug' => 'kabar-bioskop',
            'kategori' => 'berita',
            'cover_path' => 'news/news_cover.webp',
            'ringkasan' => 'Ringkasan kabar.',
            'konten' => 'Konten kabar.',
            'status_publikasi' => 'terbit',
        ]);

        // 6. Snack photo
        Storage::disk('public')->put('snacks/popcorn.webp', 'data');
        $category = MenuCategory::query()->create([
            'name' => 'Camilan',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Popcorn Manis',
            'price' => 15000,
            'photo_path' => 'snacks/popcorn.webp',
            'is_available' => true,
        ]);

        // 7. Setting logos & QRIS
        Storage::disk('public')->put('settings/site_logo.png', 'data');
        Storage::disk('public')->put('settings/qris.png', 'data');
        Setting::query()->create(['key' => 'site_logo', 'value' => 'settings/site_logo.png']);
        Setting::query()->create(['key' => 'qris_image', 'value' => 'settings/qris.png']);

        // 8. Actual orphan file
        Storage::disk('public')->put('posters/orphan_file.webp', 'orphan_data');

        // Step 1: Dry-run preview
        $response = $this->postJson(route('admin.films.posters.cleanup_storage'), [
            'dry_run' => true,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'dry_run' => true,
            'total_orphan_count' => 1,
        ]);

        $orphans = collect($response->json('orphan_files'));
        $orphanPaths = $orphans->pluck('path')->all();

        // Check that NONE of the referenced files are in the orphan list
        $this->assertNotContains('posters/film_poster.webp', $orphanPaths);
        $this->assertNotContains('banners/film_banner.webp', $orphanPaths);
        $this->assertNotContains('banners/hero_desktop.webp', $orphanPaths);
        $this->assertNotContains('banners/hero_mobile.webp', $orphanPaths);
        $this->assertNotContains('promos/promo_banner.webp', $orphanPaths);
        $this->assertNotContains('nobar/nobar_poster.webp', $orphanPaths);
        $this->assertNotContains('news/news_cover.webp', $orphanPaths);
        $this->assertNotContains('snacks/popcorn.webp', $orphanPaths);
        $this->assertNotContains('settings/site_logo.png', $orphanPaths);
        $this->assertNotContains('settings/qris.png', $orphanPaths);

        // The only orphan must be orphan_file.webp
        $this->assertContains('posters/orphan_file.webp', $orphanPaths);

        // Step 2: Quarantine execution
        $quarantineResponse = $this->postJson(route('admin.films.posters.cleanup_storage'));
        $quarantineResponse->assertOk();
        $quarantineResponse->assertJson([
            'success' => true,
            'dry_run' => false,
            'quarantined_files' => 1,
        ]);

        // All 10 referenced files remain in public storage
        Storage::disk('public')->assertExists('posters/film_poster.webp');
        Storage::disk('public')->assertExists('banners/film_banner.webp');
        Storage::disk('public')->assertExists('banners/hero_desktop.webp');
        Storage::disk('public')->assertExists('banners/hero_mobile.webp');
        Storage::disk('public')->assertExists('promos/promo_banner.webp');
        Storage::disk('public')->assertExists('nobar/nobar_poster.webp');
        Storage::disk('public')->assertExists('news/news_cover.webp');
        Storage::disk('public')->assertExists('snacks/popcorn.webp');
        Storage::disk('public')->assertExists('settings/site_logo.png');
        Storage::disk('public')->assertExists('settings/qris.png');
    }

    public function test_empty_quarantine_permanently_clears_quarantined_files(): void
    {
        $today = Carbon::now()->format('Y-m-d');
        $quarantineTodayDir = storage_path("app/karantina/{$today}");
        if (! is_dir($quarantineTodayDir)) {
            @mkdir($quarantineTodayDir, 0755, true);
        }
        file_put_contents($quarantineTodayDir.'/sample_orphan.webp', 'sample');

        $response = $this->postJson(route('admin.films.posters.empty_quarantine'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertFileDoesNotExist($quarantineTodayDir.'/sample_orphan.webp');
    }
}
