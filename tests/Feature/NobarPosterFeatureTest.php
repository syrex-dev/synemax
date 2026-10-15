<?php

namespace Tests\Feature;

use App\Models\NobarEvent;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NobarPosterFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['key' => 'info_nobar_enabled'], ['value' => '1']);
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'is_admin' => true,
        ]);
    }

    public function test_null_poster_width_and_height_renders_safely_without_error(): void
    {
        $event = NobarEvent::create([
            'judul' => 'Nobar Poster Dimensi Null',
            'slug' => 'nobar-poster-dimensi-null',
            'tim_a' => 'Tim A',
            'tim_b' => 'Tim B',
            'kickoff_at' => now()->addDays(2),
            'durasi_menit' => 120,
            'lokasi_nama' => 'Studio 1',
            'alamat_patokan' => 'Jalan Utama',
            'maps_url' => 'https://maps.google.com/?q=-6.2,106.8',
            'is_free' => true,
            'status_publikasi' => 'terbit',
            'status_acara' => 'aktif',
            'poster_path' => 'nobar/poster-legacy-tanpa-ukuran.png',
            'poster_width' => null,
            'poster_height' => null,
        ]);

        // Model helper should return graceful 4/5 aspect ratio fallback
        $this->assertSame('aspect-ratio: 4 / 5;', $event->aspectRatioStyle());
        $this->assertSame('poster-nobar-poster-dimensi-null.png', $event->posterDownloadFilename());

        // Detail page should load 200 OK and not crash
        $response = $this->get(route('nobar.show', ['slug' => $event->slug]));
        $response->assertOk();
        $response->assertSee('aspect-ratio: 4 / 5;');
        $response->assertSee('Nobar Poster Dimensi Null');
    }

    public function test_event_without_poster_renders_clean_layout_without_empty_left_gap(): void
    {
        $event = NobarEvent::create([
            'judul' => 'Nobar Tanpa Poster Sama Sekali',
            'slug' => 'nobar-tanpa-poster-sama-sekali',
            'tim_a' => 'Tim X',
            'tim_b' => 'Tim Y',
            'kickoff_at' => now()->addDays(3),
            'durasi_menit' => 120,
            'lokasi_nama' => 'Studio 2',
            'alamat_patokan' => 'Jalan Samping',
            'maps_url' => 'https://maps.google.com/?q=-6.2,106.8',
            'is_free' => true,
            'status_publikasi' => 'terbit',
            'status_acara' => 'aktif',
            'poster_path' => null,
        ]);

        $this->assertFalse($event->hasPoster());

        $response = $this->get(route('nobar.show', ['slug' => $event->slug]));
        $response->assertOk();
        // Should use single column max-w-4xl and not the 2-column sticky sidebar
        $response->assertSee('max-w-4xl mx-auto overflow-hidden rounded-3xl border border-border');
        $response->assertDontSee('lg:grid-cols-[380px,1fr]');
    }

    public function test_media_route_strictly_rejects_path_traversal_and_invalid_extensions(): void
    {
        // 1. Path traversal attempts
        $this->get('/media/nobar/..%2F..%2F.env')->assertNotFound();
        $this->get('/media/nobar/../nobar/secret.png')->assertNotFound();
        $this->get('/media/nobar/....//passwd.jpg')->assertNotFound();

        // 2. Dangerous or invalid extensions
        $this->get('/media/nobar/exploit.php')->assertNotFound();
        $this->get('/media/nobar/shell.sh')->assertNotFound();
        $this->get('/media/nobar/vector.svg')->assertNotFound();
        $this->get('/media/nobar/info.txt')->assertNotFound();

        // 3. Characters outside safe whitelist
        $this->get('/media/nobar/bad*file.png')->assertNotFound();
        $this->get('/media/nobar/bad$name.webp')->assertNotFound();
    }

    public function test_duplicate_event_creates_independent_poster_and_og_files(): void
    {
        $admin = $this->createAdmin();
        $disk = Storage::disk('local');

        // Create dummy physical files for the original event
        $originalPosterFilename = 'nobar/original_poster_test_'.uniqid().'.png';
        $originalOgFilename = 'nobar/original_og_test_'.uniqid().'.png';

        $disk->put($originalPosterFilename, 'ORIGINAL_POSTER_CONTENT');
        $disk->put($originalOgFilename, 'ORIGINAL_OG_CONTENT');

        $originalEvent = NobarEvent::create([
            'judul' => 'Acara Nobar Asal',
            'slug' => 'acara-nobar-asal',
            'kompetisi' => 'Liga A',
            'tim_a' => 'Tim Asal A',
            'tim_b' => 'Tim Asal B',
            'kickoff_at' => now()->addDays(5),
            'durasi_menit' => 150,
            'lokasi_nama' => 'Studio Utama',
            'alamat_patokan' => 'Jl. Asal No. 1',
            'maps_url' => 'https://maps.google.com/?q=-6.2,106.8',
            'is_free' => true,
            'status_publikasi' => 'terbit',
            'status_acara' => 'aktif',
            'poster_path' => $originalPosterFilename,
            'poster_og_path' => $originalOgFilename,
            'poster_width' => 1080,
            'poster_height' => 1350,
            'poster_alt' => 'Poster Asli',
        ]);

        // Duplicate the event
        $response = $this->actingAs($admin)
            ->post(route('admin.nobar.duplicate', ['nobar' => $originalEvent->id]));

        $response->assertRedirect();

        // Retrieve duplicated event
        $duplicate = NobarEvent::where('slug', 'acara-nobar-asal-salinan')->firstOrFail();
        $this->assertSame('draft', $duplicate->status_publikasi);
        $this->assertNotSame($originalEvent->id, $duplicate->id);

        // Poster and OG filenames must be completely independent
        $this->assertNotSame($originalEvent->poster_path, $duplicate->poster_path);
        $this->assertNotSame($originalEvent->poster_og_path, $duplicate->poster_og_path);

        // Both duplicate physical files should exist
        $this->assertTrue($disk->exists($duplicate->poster_path));
        $this->assertTrue($disk->exists($duplicate->poster_og_path));

        // Now delete the duplicated event
        $this->actingAs($admin)
            ->delete(route('admin.nobar.destroy', ['nobar' => $duplicate->id]))
            ->assertRedirect(route('admin.nobar.index'));

        // Duplicated event is soft-deleted and its physical files are removed
        $this->assertSoftDeleted('nobar_events', ['id' => $duplicate->id]);
        $this->assertFalse($disk->exists($duplicate->poster_path));
        $this->assertFalse($disk->exists($duplicate->poster_og_path));

        // Original event and ITS physical files MUST STILL EXIST untouched!
        $this->assertDatabaseHas('nobar_events', ['id' => $originalEvent->id, 'deleted_at' => null]);
        $this->assertTrue($disk->exists($originalPosterFilename));
        $this->assertTrue($disk->exists($originalOgFilename));

        // Cleanup
        $disk->delete([$originalPosterFilename, $originalOgFilename]);
    }

    public function test_poster_upload_rejects_shortest_side_below_600px(): void
    {
        $admin = $this->createAdmin();

        // 500x700: shortest side is 500 (< 600px)
        $smallImage = UploadedFile::fake()->image('small_poster.jpg', 500, 700);

        $response = $this->actingAs($admin)
            ->post(route('admin.nobar.store'), [
                'judul' => 'Nobar Poster Terlalu Kecil',
                'tim_a' => 'Tim A',
                'kickoff_at' => '2026-11-20T19:30',
                'durasi_menit' => 120,
                'lokasi_nama' => 'Studio',
                'alamat_patokan' => 'Jalan',
                'maps_url' => 'https://maps.google.com/?q=-6.2,106.8',
                'is_free' => 1,
                'status_publikasi' => 'draft',
                'is_pinned' => 0,
                'status_acara' => 'aktif',
                'poster' => $smallImage,
            ]);

        $response->assertSessionHasErrors(['poster']);
        $errorMsg = session('errors')->first('poster');
        $this->assertStringContainsString('Sisi terpendek minimal 600 piksel', $errorMsg);
    }

    public function test_poster_upload_rejects_svg_files(): void
    {
        $admin = $this->createAdmin();

        $svgFile = UploadedFile::fake()->createWithContent('malicious.svg', '<svg xmlns="http://www.w3.org/2000/svg"><circle r="50"/></svg>');

        $response = $this->actingAs($admin)
            ->post(route('admin.nobar.store'), [
                'judul' => 'Nobar Poster SVG',
                'tim_a' => 'Tim A',
                'kickoff_at' => '2026-11-20T19:30',
                'durasi_menit' => 120,
                'lokasi_nama' => 'Studio',
                'alamat_patokan' => 'Jalan',
                'maps_url' => 'https://maps.google.com/?q=-6.2,106.8',
                'is_free' => 1,
                'status_publikasi' => 'draft',
                'is_pinned' => 0,
                'status_acara' => 'aktif',
                'poster' => $svgFile,
            ]);

        $response->assertSessionHasErrors(['poster']);
        $errorMsg = session('errors')->first('poster');
        $this->assertStringContainsString('bukan SVG', $errorMsg);
    }

    public function test_open_graph_image_is_absolute_url_and_falls_back_to_social_banner(): void
    {
        // 1. Event without poster
        $noPosterEvent = NobarEvent::create([
            'judul' => 'Nobar Tanpa Gambar',
            'slug' => 'nobar-tanpa-gambar',
            'tim_a' => 'Tim A',
            'kickoff_at' => now()->addDays(1),
            'durasi_menit' => 120,
            'lokasi_nama' => 'Studio',
            'alamat_patokan' => 'Jalan',
            'maps_url' => 'https://maps.google.com/?q=-6.2,106.8',
            'is_free' => true,
            'status_publikasi' => 'terbit',
            'status_acara' => 'aktif',
        ]);

        $this->assertStringStartsWith('http', $noPosterEvent->posterOgUrl());
        $this->assertStringContainsString('social-banner.png', $noPosterEvent->posterOgUrl());

        // 2. Event with OG banner
        $withOgEvent = NobarEvent::create([
            'judul' => 'Nobar Bergambar',
            'slug' => 'nobar-bergambar',
            'tim_a' => 'Tim A',
            'kickoff_at' => now()->addDays(1),
            'durasi_menit' => 120,
            'lokasi_nama' => 'Studio',
            'alamat_patokan' => 'Jalan',
            'maps_url' => 'https://maps.google.com/?q=-6.2,106.8',
            'is_free' => true,
            'status_publikasi' => 'terbit',
            'status_acara' => 'aktif',
            'poster_path' => 'nobar/poster-sample.webp',
            'poster_og_path' => 'nobar/og-sample.webp',
        ]);

        $this->assertStringStartsWith('http', $withOgEvent->posterOgUrl());
        $this->assertStringContainsString('/media/nobar/og-sample.webp', $withOgEvent->posterOgUrl());
    }
}
