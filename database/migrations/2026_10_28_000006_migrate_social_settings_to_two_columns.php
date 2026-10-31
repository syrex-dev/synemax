<?php

use App\Models\Setting;
use App\Services\CinemaSettings;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Migrasi data sosial media lama ke format baru: label + URL tanpa menimpa yang sudah ada
        $instagram = Setting::where('key', 'social_instagram')->value('value') ?: Setting::where('key', 'instagram_url')->value('value');
        $tiktok = Setting::where('key', 'social_tiktok')->value('value') ?: Setting::where('key', 'tiktok_url')->value('value');
        $youtube = Setting::where('key', 'social_youtube')->value('value') ?: Setting::where('key', 'youtube_url')->value('value');
        $maps = Setting::where('key', 'contact_maps_url')->value('value') ?: Setting::where('key', 'google_maps_url')->value('value') ?: Setting::where('key', 'maps_url')->value('value');

        // Instagram
        if ($instagram && trim($instagram) !== '') {
            $rawIg = trim($instagram);
            if (! str_starts_with($rawIg, 'http://') && ! str_starts_with($rawIg, 'https://')) {
                if (str_contains($rawIg, 'instagram.com')) {
                    $rawIg = 'https://'.ltrim($rawIg, '/');
                } else {
                    $rawIg = 'https://instagram.com/'.ltrim($rawIg, '@');
                }
            }
            $cleanUrl = CinemaSettings::cleanSocialUrl($rawIg);
            $handle = CinemaSettings::suggestSocialHandle('instagram', $cleanUrl);

            $currentUrl = Setting::where('key', 'instagram_url')->value('value');
            if (! $currentUrl || $currentUrl === 'https://instagram.com/synemax.cinema') {
                Setting::updateOrCreate(['key' => 'instagram_url'], ['value' => $cleanUrl, 'group' => 'kontak']);
            }
            $currentLabel = Setting::where('key', 'instagram_label')->value('value');
            if (! $currentLabel || $currentLabel === 'Instagram' || $currentLabel === '@synemax.cinema') {
                Setting::updateOrCreate(['key' => 'instagram_label'], ['value' => mb_substr($handle, 0, 30), 'group' => 'kontak']);
            }
        }

        // TikTok
        if ($tiktok && trim($tiktok) !== '') {
            $rawTiktok = trim($tiktok);
            if (! str_starts_with($rawTiktok, 'http://') && ! str_starts_with($rawTiktok, 'https://')) {
                if (str_contains($rawTiktok, 'tiktok.com')) {
                    $rawTiktok = 'https://'.ltrim($rawTiktok, '/');
                } else {
                    $rawTiktok = 'https://tiktok.com/@'.ltrim($rawTiktok, '@');
                }
            }
            $cleanUrl = CinemaSettings::cleanSocialUrl($rawTiktok);
            $handle = CinemaSettings::suggestSocialHandle('tiktok', $cleanUrl);

            $currentUrl = Setting::where('key', 'tiktok_url')->value('value');
            if (! $currentUrl || $currentUrl === 'https://tiktok.com/@synemax.cinema') {
                Setting::updateOrCreate(['key' => 'tiktok_url'], ['value' => $cleanUrl, 'group' => 'kontak']);
            }
            $currentLabel = Setting::where('key', 'tiktok_label')->value('value');
            if (! $currentLabel || $currentLabel === 'TikTok' || $currentLabel === '@synemax.cinema') {
                Setting::updateOrCreate(['key' => 'tiktok_label'], ['value' => mb_substr($handle, 0, 30), 'group' => 'kontak']);
            }
        }

        // YouTube: only migrate if filled and not empty
        if ($youtube && trim($youtube) !== '') {
            $rawYt = trim($youtube);
            if (! str_starts_with($rawYt, 'http://') && ! str_starts_with($rawYt, 'https://')) {
                if (str_contains($rawYt, 'youtube.com') || str_contains($rawYt, 'youtu.be')) {
                    $rawYt = 'https://'.ltrim($rawYt, '/');
                } else {
                    $rawYt = 'https://youtube.com/@'.ltrim($rawYt, '@');
                }
            }
            $cleanUrl = CinemaSettings::cleanSocialUrl($rawYt);
            $handle = CinemaSettings::suggestSocialHandle('youtube', $cleanUrl);

            $currentUrl = Setting::where('key', 'youtube_url')->value('value');
            if (! $currentUrl || $currentUrl === 'https://youtube.com/@synemax.cinema') {
                Setting::updateOrCreate(['key' => 'youtube_url'], ['value' => $cleanUrl, 'group' => 'kontak']);
            }
            $currentLabel = Setting::where('key', 'youtube_label')->value('value');
            if (! $currentLabel) {
                Setting::updateOrCreate(['key' => 'youtube_label'], ['value' => mb_substr($handle, 0, 30), 'group' => 'kontak']);
            }
        }

        // Google Maps
        if ($maps && trim($maps) !== '') {
            $cleanMaps = CinemaSettings::cleanSocialUrl(trim($maps));
            Setting::updateOrCreate(['key' => 'maps_url'], ['value' => $cleanMaps, 'group' => 'kontak']);
            Setting::updateOrCreate(['key' => 'maps_label'], ['value' => 'Lihat di Google Maps', 'group' => 'kontak']);
        }

        // 2. Sinkronisasi Alamat & Email Asli
        $cinemaAddress = Setting::where('key', 'cinema_address')->value('value');
        $contactAddress = Setting::where('key', 'contact_address')->value('value');
        if (! $contactAddress && $cinemaAddress) {
            Setting::updateOrCreate(['key' => 'contact_address'], ['value' => $cinemaAddress, 'group' => 'kontak']);
        } elseif ($contactAddress && ! $cinemaAddress) {
            Setting::updateOrCreate(['key' => 'cinema_address'], ['value' => $contactAddress, 'group' => 'kontak']);
        }

        $cinemaEmail = Setting::where('key', 'cinema_email')->value('value');
        $contactEmail = Setting::where('key', 'contact_email')->value('value');
        if (! $contactEmail && $cinemaEmail) {
            Setting::updateOrCreate(['key' => 'contact_email'], ['value' => $cinemaEmail, 'group' => 'kontak']);
        } elseif ($contactEmail && ! $cinemaEmail) {
            Setting::updateOrCreate(['key' => 'cinema_email'], ['value' => $contactEmail, 'group' => 'kontak']);
        }

        // 3. Footer text token
        $footer = Setting::where('key', 'footer_text')->value('value');
        if (! $footer || str_contains((string) $footer, 'Home Cinema')) {
            Setting::updateOrCreate(
                ['key' => 'footer_text'],
                ['value' => '© {tahun} SynemaX Cinema. Hak cipta dilindungi.', 'group' => 'kontak']
            );
        }
    }

    public function down(): void
    {
        // Additive migration, tidak menghapus data
    }
};
