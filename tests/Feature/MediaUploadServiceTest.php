<?php

namespace Tests\Feature;

use App\Services\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MediaUploadServiceTest extends TestCase
{
    private MediaUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MediaUploadService::class);
    }

    public function test_upload_nobar_poster_successfully_creates_poster_and_1200x630_og_variant(): void
    {
        $disk = Storage::disk('local');

        // 800 x 1000 (portrait 4:5, shortest side >= 600px)
        $file = UploadedFile::fake()->image('match_poster.png', 800, 1000);

        $result = $this->service->uploadNobarPoster($file);

        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('og_path', $result);
        $this->assertArrayHasKey('width', $result);
        $this->assertArrayHasKey('height', $result);

        $this->assertSame(800, $result['width']);
        $this->assertSame(1000, $result['height']);

        // Assert physical files exist via Storage
        $this->assertTrue($disk->exists($result['path']));
        $this->assertTrue($disk->exists($result['og_path']));

        // Verify OG variant has 1200 x 630 dimensions
        $ogInfo = getimagesize($disk->path($result['og_path']));
        $this->assertSame(1200, $ogInfo[0]);
        $this->assertSame(630, $ogInfo[1]);

        // Clean up
        $this->service->deletePosterAndOg($result['path'], $result['og_path']);
        $this->assertFalse($disk->exists($result['path']));
        $this->assertFalse($disk->exists($result['og_path']));
    }

    public function test_upload_nobar_poster_rejects_shortest_side_below_600px(): void
    {
        $this->expectException(ValidationException::class);

        $file = UploadedFile::fake()->image('narrow_poster.jpg', 550, 900);
        $this->service->uploadNobarPoster($file);
    }

    public function test_upload_nobar_poster_rejects_svg(): void
    {
        $this->expectException(ValidationException::class);

        $file = UploadedFile::fake()->createWithContent('malicious.svg', '<svg></svg>');
        $this->service->uploadNobarPoster($file);
    }

    public function test_copy_poster_creates_independent_physical_files(): void
    {
        $disk = Storage::disk('local');

        $file = UploadedFile::fake()->image('original.png', 700, 900);
        $original = $this->service->uploadNobarPoster($file);

        $copied = $this->service->copyPoster($original['path'], $original['og_path']);

        $this->assertNotSame($original['path'], $copied['poster_path']);
        $this->assertNotSame($original['og_path'], $copied['poster_og_path']);

        $this->assertTrue($disk->exists($copied['poster_path']));
        $this->assertTrue($disk->exists($copied['poster_og_path']));

        // Clean up
        $this->service->deletePosterAndOg($original['path'], $original['og_path']);
        $this->service->deletePosterAndOg($copied['poster_path'], $copied['poster_og_path']);
    }
}
