<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CinemaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    private CinemaSettings $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settings = app(CinemaSettings::class);
        $this->settings->clearCache();
    }

    public function test_public_routes_accessible_when_maintenance_is_off(): void
    {
        $this->settings->set('maintenance_mode', '0', 'operasional');

        $response = $this->get('/');
        $response->assertOk();
    }

    public function test_public_routes_return_503_when_maintenance_is_on(): void
    {
        $this->settings->set('maintenance_mode', '1', 'operasional');
        $this->settings->set('maintenance_message', 'Situs sedang dalam pemeliharaan khusus.', 'operasional');

        $response = $this->get('/');
        $response->assertStatus(503);
    }

    public function test_admin_routes_and_login_remain_accessible_during_maintenance(): void
    {
        $this->settings->set('maintenance_mode', '1', 'operasional');

        // Login route
        $loginResponse = $this->get('/login');
        $loginResponse->assertOk();

        // Admin panel
        $admin = User::factory()->create(['is_admin' => true]);
        $adminResponse = $this->actingAs($admin)->get('/admin');
        $adminResponse->assertOk();
    }

    public function test_admin_can_preview_public_site_with_preview_param_during_maintenance(): void
    {
        $this->settings->set('maintenance_mode', '1', 'operasional');

        $admin = User::factory()->create(['is_admin' => true]);
        $response = $this->actingAs($admin)->get('/?preview=1');
        $response->assertOk();
    }

    public function test_json_requests_receive_503_json_during_maintenance(): void
    {
        $this->settings->set('maintenance_mode', '1', 'operasional');
        $this->settings->set('maintenance_message', 'API sedang dalam pemeliharaan.', 'operasional');

        $response = $this->getJson('/film');
        $response->assertStatus(503);
        $response->assertJson([
            'status' => 503,
            'message' => 'API sedang dalam pemeliharaan.',
        ]);
    }
}
