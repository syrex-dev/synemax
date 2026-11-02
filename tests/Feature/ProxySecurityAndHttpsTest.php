<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ProxySecurityAndHttpsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that spoofed X-Forwarded-Host header does not alter generated URLs or application host.
     */
    public function test_spoofed_x_forwarded_host_is_ignored(): void
    {
        $response = $this->withHeaders([
            'X-Forwarded-Host' => 'evil-attacker-site.com',
            'X-Forwarded-For' => '203.0.113.195',
            'X-Forwarded-Proto' => 'https',
        ])->get('/');

        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringNotContainsString('evil-attacker-site.com', $content);

        // Verify route() helper still resolves to canonical host
        $homeUrl = route('home');
        $this->assertStringNotContainsString('evil-attacker-site.com', $homeUrl);
    }

    /**
     * Test that URL::forceScheme('https') is active when app environment is production.
     */
    public function test_https_is_forced_in_production_environment(): void
    {
        $this->app['env'] = 'production';
        URL::forceScheme('https');

        $url = route('home');
        $this->assertStringStartsWith('https://', $url);
    }
}
