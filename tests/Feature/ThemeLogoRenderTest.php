<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ThemeLogoRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_both_light_and_dark_logos(): void
    {
        Setting::updateOrCreate(['key' => 'site_logo'], ['value' => 'branding/test_logo_light.png']);
        Setting::updateOrCreate(['key' => 'site_logo_dark'], ['value' => 'branding/test_logo_dark.png']);
        Setting::updateOrCreate(['key' => 'navbar_brand_display'], ['value' => 'logo_text']);

        $response = $this->get('/');
        $response->assertStatus(200);

        // Assert both <img> tags are rendered
        $response->assertSee('logo-light', false);
        $response->assertSee('logo-dark', false);
        $response->assertSee('test_logo_light.png', false);
        $response->assertSee('test_logo_dark.png', false);
    }

    public function test_single_light_logo_renders_pill_fallback_for_dark_mode(): void
    {
        $rendered = Blade::render('<x-brand.logo logoLight="branding/single_light.png" :logoDark="null" />');

        $this->assertStringContainsString('logo-light', $rendered);
        $this->assertStringContainsString('single_light.png', $rendered);
        // Dark mode should wrap in a pill container
        $this->assertStringContainsString('logo-dark', $rendered);
        $this->assertStringContainsString('bg-white/95', $rendered);
    }

    public function test_single_dark_logo_renders_dark_pill_fallback_for_light_mode(): void
    {
        $rendered = Blade::render('<x-brand.logo :logoLight="null" logoDark="branding/single_dark.png" />');

        $this->assertStringContainsString('logo-dark', $rendered);
        $this->assertStringContainsString('single_dark.png', $rendered);
        // Light mode should wrap in a dark pill container
        $this->assertStringContainsString('logo-light', $rendered);
        $this->assertStringContainsString('bg-slate-900/90', $rendered);
    }
}
