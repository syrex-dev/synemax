<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicFooterAndDesignSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_public_pages_have_single_footer_without_design_system_link(): void
    {
        Setting::updateOrCreate(['key' => 'site_name'], ['value' => 'SynemaX']);
        Setting::updateOrCreate(['key' => 'site_tagline'], ['value' => 'Studio Bioskop Mini Eksklusif']);
        Setting::updateOrCreate(['key' => 'contact_email'], ['value' => 'halo@synemax.test']);
        Setting::updateOrCreate(['key' => 'social_instagram'], ['value' => '@synemax.cinema']);
        Setting::updateOrCreate(['key' => 'social_tiktok'], ['value' => '@synemax']);

        $routes = [
            '/',
            '/jadwal',
            '/film',
            '/nobar',
            '/promo',
            '/kabar',
            '/cek-booking',
        ];

        foreach ($routes as $uri) {
            $response = $this->get($uri);
            $response->assertStatus(200);

            // Assert single footer
            $content = $response->getContent();
            $footerCount = substr_count($content, '<footer');
            $this->assertEquals(1, $footerCount, "Page {$uri} should have exactly one footer element, found {$footerCount}");

            // Assert footer structure
            $response->assertSee('Jelajahi', false);
            $response->assertSee('Bantuan', false);
            $response->assertSee('Hubungi & Ikuti', false);

            // Assert Design System is NOT in the public area
            $response->assertDontSee('Design System', false);
        }
    }
}
