<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that essential defense-in-depth security headers are present on web responses.
     */
    public function test_security_headers_are_present_on_web_responses(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    /**
     * Test that HSTS header is attached when request is secure.
     */
    public function test_strict_transport_security_header_on_https(): void
    {
        $response = $this->get('https://synemax.test/');

        $response->assertStatus(200);
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    /**
     * Test that the public film search endpoint is rate limited against DoS scraping.
     */
    public function test_film_catalog_endpoint_has_rate_limiting(): void
    {
        $response = $this->get('/film');

        $response->assertStatus(200);
        $this->assertNotNull($response->headers->get('X-RateLimit-Limit'));
    }

    /**
     * Test that the login endpoint is rate limited.
     */
    public function test_login_post_endpoint_has_rate_limiting(): void
    {
        $response = $this->post('/login', [
            'email' => 'attacker@malicious.com',
            'password' => 'wrong-password',
        ]);

        $this->assertNotNull($response->headers->get('X-RateLimit-Limit'));
    }
}
