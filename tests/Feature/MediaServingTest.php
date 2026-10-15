<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaServingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_serves_nobar_poster_with_cache_headers(): void
    {
        $dummyImage = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15c4\x00\x00\x00\nIDATx\x9cc\x00\x01\x00\x00\x05\x00\x01\r\n-\xb4\x00\x00\x00\x00IEND\xaeB`\x82";
        Storage::disk('local')->put('nobar/test-poster.png', $dummyImage);

        $response = $this->get(route('media.nobar', ['filename' => 'test-poster.png']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $response->assertHeader('Cache-Control', 'max-age=86400, public');
        $this->assertSame($dummyImage, $response->getContent());
    }

    public function test_serves_article_cover_with_cache_headers(): void
    {
        $dummyImage = "RIFF\x24\x00\x00\x00WEBPVP8 \x18\x00\x00\x00\x30\x01\x00\x9d\x01\x2a\x01\x00\x01\x00\x02\x00\x34\x25\xa4\x00\x03\x70\x00\xfe\xfb\xfd\x50\x00";
        Storage::disk('local')->put('news/test-cover.webp', $dummyImage);

        $response = $this->get(route('media.article', ['filename' => 'test-cover.webp']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/webp');
        $response->assertHeader('Cache-Control', 'max-age=86400, public');
    }

    public function test_media_endpoints_return_404_for_missing_files(): void
    {
        $this->get(route('media.nobar', ['filename' => 'not-found.jpg']))->assertNotFound();
        $this->get(route('media.article', ['filename' => 'not-found.jpg']))->assertNotFound();
    }

    public function test_media_endpoints_reject_path_traversal_and_invalid_extensions(): void
    {
        $this->get('/media/nobar/..%2F..%2F.env')->assertNotFound();
        $this->get('/media/nobar/../nobar/test.png')->assertNotFound();
        $this->get('/media/nobar/malicious.php')->assertNotFound();
        $this->get('/media/nobar/test.svg')->assertNotFound();
        $this->get('/media/nobar/test.exe')->assertNotFound();
        $this->get('/media/nobar/invalid*name.png')->assertNotFound();

        $this->get('/media/berita/..%2F..%2F.env')->assertNotFound();
        $this->get('/media/berita/../news/test.webp')->assertNotFound();
        $this->get('/media/berita/malicious.php')->assertNotFound();
    }
}
