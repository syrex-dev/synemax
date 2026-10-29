<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CinemaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);
    }

    public function test_setting_helper_returns_default_when_empty(): void
    {
        $this->assertSame('Default Bioskop', setting('custom_unknown_key', 'Default Bioskop'));
    }

    public function test_settings_page_can_be_viewed_by_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.settings.edit'));

        $response->assertOk();
        $response->assertSee('Pengaturan');
        $response->assertSee('Identitas Situs');
        $response->assertSee('Navbar & Menu');
        $response->assertSee('Tampilan');
        $response->assertSee('Operasional');
        $response->assertSee('Harga & FAQ');
        $response->assertSee('Kontak & Sosial');
        $response->assertSee('SEO & Lanjutan');
    }

    public function test_settings_can_be_updated_and_cached(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'active_tab' => 'identitas',
            'site_name' => 'SynemaX Updated',
            'site_tagline' => 'Bioskop Mini Desa Tercinta',
            'navbar_brand_display' => 'logo_text',
        ]);

        $response->assertRedirect(route('admin.settings.edit', ['tab' => 'identitas']));
        $response->assertSessionHas('status');

        $this->assertSame('SynemaX Updated', setting('site_name'));
        $this->assertSame('Bioskop Mini Desa Tercinta', setting('site_tagline'));
        $this->assertSame('logo_text', setting('navbar_brand_display'));

        // Verify cache gets populated after setting() access
        $this->assertTrue(Cache::has(CinemaSettings::CACHE_KEY));
    }

    public function test_settings_cache_can_be_cleared(): void
    {
        app(CinemaSettings::class)->all();
        $this->assertTrue(Cache::has(CinemaSettings::CACHE_KEY));

        $response = $this->actingAs($this->admin)->post(route('admin.settings.cache.clear'));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Cache sistem dan pengaturan berhasil dibersihkan.');
        $this->assertFalse(Cache::has(CinemaSettings::CACHE_KEY));
    }

    public function test_settings_can_be_reset_to_defaults(): void
    {
        $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'active_tab' => 'identitas',
            'site_name' => 'Custom Brand Name',
            'navbar_brand_display' => 'logo_text',
        ]);

        $this->assertSame('Custom Brand Name', setting('site_name'));

        $response = $this->actingAs($this->admin)->post(route('admin.settings.reset'));

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertSame('SynemaX', setting('site_name'));
    }

    public function test_dynamic_favicon_renders_in_public_and_admin_layouts(): void
    {
        $settingsService = app(CinemaSettings::class);
        $settingsService->set('site_favicon', 'branding/custom_favicon.svg', 'identitas');

        // Test public page contains custom favicon url
        $publicRes = $this->get('/');
        $publicRes->assertOk();
        $publicRes->assertSee('custom_favicon.svg');
        $publicRes->assertSee('rel="icon"', false);

        // Test admin page contains custom favicon url
        $adminRes = $this->actingAs($this->admin)->get(route('admin.settings.edit'));
        $adminRes->assertOk();
        $adminRes->assertSee('custom_favicon.svg');
        $adminRes->assertSee('rel="icon"', false);
    }
}
