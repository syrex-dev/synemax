<?php

namespace Tests\Feature;

use Tests\TestCase;

class MediaRouteTest extends TestCase
{
    public function test_nobar_poster_media_route_serves_image(): void
    {
        $response = $this->get('/media/nobar/contoh-nobar-tim-garuda-muda-vs-tim-bintang-timur.png');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_article_cover_media_route_serves_image(): void
    {
        $response = $this->get('/media/berita/contoh-suasana-baru-nobar-dan-nonton-film-lebih-nyaman.png');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
    }
}
