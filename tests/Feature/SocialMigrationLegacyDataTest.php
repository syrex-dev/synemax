<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\CinemaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialMigrationLegacyDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_processes_legacy_social_data_correctly(): void
    {
        // 1. Siapkan database dengan data lama persis seperti kondisi sebelum migrasi:
        // - Alamat asli
        // - Instagram: instagram.com/synemax_/ (tanpa skema http/https)
        // - TikTok: https://www.tiktok.com/@synemax_?_r=1&_t=ZS-9Aw (dengan parameter pelacak)
        // - YouTube: kosong
        $originalAddress = 'Jl. Desa Panyingkiran Blok Mawar, Panyingkiran, Kec. Panyingkiran, Kabupaten Majalengka, Jawa Barat';

        Setting::query()->delete();
        Setting::create(['key' => 'cinema_address', 'value' => $originalAddress, 'group' => 'kontak']);
        Setting::create(['key' => 'social_instagram', 'value' => 'instagram.com/synemax_/', 'group' => 'kontak']);
        Setting::create(['key' => 'social_tiktok', 'value' => 'https://www.tiktok.com/@synemax_?_r=1&_t=ZS-9Aw', 'group' => 'kontak']);
        Setting::create(['key' => 'social_youtube', 'value' => '', 'group' => 'kontak']);

        // 2. Jalankan logika migrasi
        $migration = require database_path('migrations/2026_10_28_000006_migrate_social_settings_to_two_columns.php');
        $migration->up();

        // 3. Verifikasi hasil migrasi
        // - URL Instagram tersalin bersih
        $instagramUrl = Setting::where('key', 'instagram_url')->value('value');
        $this->assertSame('https://instagram.com/synemax_/', $instagramUrl);

        // - Nama tampil Instagram adalah @synemax_
        $instagramLabel = Setting::where('key', 'instagram_label')->value('value');
        $this->assertSame('@synemax_', $instagramLabel);

        // - URL TikTok bersih tanpa parameter pelacak (_r, _t)
        $tiktokUrl = Setting::where('key', 'tiktok_url')->value('value');
        $this->assertSame('https://www.tiktok.com/@synemax_', $tiktokUrl);
        $this->assertStringNotContainsString('_r=', $tiktokUrl);
        $this->assertStringNotContainsString('_t=', $tiktokUrl);

        // - Nama tampil TikTok adalah @synemax_ (tanpa tanda @@ ganda)
        $tiktokLabel = Setting::where('key', 'tiktok_label')->value('value');
        $this->assertSame('@synemax_', $tiktokLabel);

        // - YouTube kosong (tidak dibuat key / kosong)
        $youtubeUrl = Setting::where('key', 'youtube_url')->value('value');
        $this->assertTrue(empty($youtubeUrl));

        // - Alamat tersinkronisasi utuh ke contact_address
        $contactAddress = Setting::where('key', 'contact_address')->value('value');
        $this->assertSame($originalAddress, $contactAddress);

        // 4. Verifikasi bahwa CinemaSettings dan tampilan footer tidak memunculkan placeholder
        app(CinemaSettings::class)->clearCache();
        $settings = app(CinemaSettings::class);

        $this->assertSame($originalAddress, $settings->contactAddress());
        $this->assertSame('@synemax_', $settings->instagramLabel());
        $this->assertSame('@synemax_', $settings->tiktokLabel());
        $this->assertSame('', $settings->youtubeUrl());
        $this->assertSame('', $settings->youtubeLabel());

        // Footer publik tidak memuat teks placeholder atau YouTube
        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee($originalAddress);
        $response->assertSee('@synemax_');
        $response->assertDontSee('Jl. Bioskop Desa No. 1, Desa Kreatif');
        $response->assertDontSee('kontak@synemax.test');
        $response->assertDontSee('@synemax.cinema');
    }
}
