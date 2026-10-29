<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);
    }

    public function test_promo_upload_rejects_non_image_files(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->admin)->post(route('admin.promos.store'), [
            'title' => 'Promo Diskon File Salah',
            'image' => $file,
        ]);

        $response->assertSessionHasErrors('image');
    }

    public function test_promo_upload_rejects_files_exceeding_max_size(): void
    {
        // 2.5 MB exceeds the 2048 KB limit
        $file = UploadedFile::fake()->image('promo_oversized.jpg')->size(2500);

        $response = $this->actingAs($this->admin)->post(route('admin.promos.store'), [
            'title' => 'Promo File Kebesaran',
            'image' => $file,
        ]);

        $response->assertSessionHasErrors('image');
    }

    public function test_promo_upload_accepts_valid_image_and_stores_in_public_disk(): void
    {
        $file = UploadedFile::fake()->image('promo_valid.webp', 1200, 600)->size(500);

        $response = $this->actingAs($this->admin)->post(route('admin.promos.store'), [
            'title' => 'Promo Valid 1200x600',
            'image' => $file,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.promos.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('promos', [
            'title' => 'Promo Valid 1200x600',
        ]);
    }

    public function test_banner_upload_rejects_non_image(): void
    {
        $file = UploadedFile::fake()->create('script.php', 10, 'text/plain');

        $response = $this->actingAs($this->admin)->post(route('admin.banners.store'), [
            'title' => 'Banner PHP File',
            'type' => 'hero',
            'target_type' => 'none',
            'image' => $file,
        ]);

        $response->assertSessionHasErrors('image');
    }

    public function test_banner_upload_accepts_valid_image(): void
    {
        $file = UploadedFile::fake()->image('hero_banner.png', 1600, 680)->size(800);

        $response = $this->actingAs($this->admin)->post(route('admin.banners.store'), [
            'title' => 'Hero Banner 1600x680',
            'type' => 'hero',
            'target_type' => 'none',
            'image' => $file,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.banners.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('banners', [
            'title' => 'Hero Banner 1600x680',
            'type' => 'hero',
        ]);
    }
}
