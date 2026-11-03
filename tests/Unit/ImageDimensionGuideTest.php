<?php

namespace Tests\Unit;

use App\Support\ImageDimensionGuide;
use Tests\TestCase;

class ImageDimensionGuideTest extends TestCase
{
    public function test_all_contains_required_ten_keys(): void
    {
        $all = ImageDimensionGuide::all();

        $expectedKeys = [
            'hero_desktop',
            'hero_mobile',
            'film_poster',
            'promo_banner',
            'nobar_poster',
            'news_cover',
            'qris_image',
            'site_logo',
            'site_og_image',
            'site_favicon',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $all, "Key {$key} must be defined in ImageDimensionGuide::all()");
            $spec = $all[$key];
            $this->assertSame($key, $spec['key']);
            $this->assertNotEmpty($spec['label']);
            $this->assertGreaterThan(0, $spec['width']);
            $this->assertGreaterThan(0, $spec['height']);
            $this->assertNotEmpty($spec['ratio']);
            $this->assertGreaterThan(0, $spec['max_mb']);
            $this->assertNotEmpty($spec['hint']);
            $this->assertNotEmpty($spec['allowed_mimes']);
        }
    }

    public function test_get_returns_correct_spec_or_null(): void
    {
        $hero = ImageDimensionGuide::get('hero_desktop');
        $this->assertNotNull($hero);
        $this->assertSame(1600, $hero['width']);
        $this->assertSame(680, $hero['height']);
        $this->assertSame(3, $hero['max_mb']);

        $poster = ImageDimensionGuide::get('film_poster');
        $this->assertNotNull($poster);
        $this->assertSame(600, $poster['width']);
        $this->assertSame(900, $poster['height']);
        $this->assertSame(5, $poster['max_mb']);

        $nonExistent = ImageDimensionGuide::get('invalid_key');
        $this->assertNull($nonExistent);
    }
}
