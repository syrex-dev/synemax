<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\CinemaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederPreservesSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_does_not_overwrite_existing_settings(): void
    {
        $settingsService = app(CinemaSettings::class);

        // Pre-fill custom admin settings
        $settingsService->set('admin_whatsapp', '6285169730960', 'operasional');
        $settingsService->set('whatsapp_number', '6285169730960', 'kontak');
        $settingsService->set('cinema_email', 'admin-custom@synemax.test', 'kontak');
        $settingsService->set('site_name', 'Bioskop Custom Admin', 'identitas');

        // Run the seeder
        $this->seed();

        // Refresh settings cache
        $settingsService->clearCache();

        // Assert that custom values were NOT overwritten by default seed values
        $this->assertSame('6285169730960', $settingsService->string('admin_whatsapp'));
        $this->assertSame('6285169730960', $settingsService->string('whatsapp_number'));
        $this->assertSame('admin-custom@synemax.test', $settingsService->string('cinema_email'));
        $this->assertSame('Bioskop Custom Admin', $settingsService->string('site_name'));

        // Assert that new keys that did not exist yet ARE populated by the seeder
        $this->assertTrue(Setting::where('key', 'default_theme')->exists());
        $this->assertTrue(Setting::where('key', 'opening_hours_text')->exists());
    }
}
