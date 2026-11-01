<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Unifikasi Alamat Bioskop (Canonical: contact_address)
        $contactAddress = trim((string) Setting::where('key', 'contact_address')->value('value'));
        $cinemaAddress = trim((string) Setting::where('key', 'cinema_address')->value('value'));

        if ($contactAddress === '' && $cinemaAddress !== '') {
            $contactAddress = $cinemaAddress;
            Setting::updateOrCreate(['key' => 'contact_address'], ['value' => $contactAddress, 'group' => 'kontak']);
        } elseif ($contactAddress !== '' && $cinemaAddress !== $contactAddress) {
            Setting::updateOrCreate(['key' => 'cinema_address'], ['value' => $contactAddress, 'group' => 'kontak']);
        }

        // 2. Unifikasi WhatsApp / Telepon Bioskop (Canonical: admin_whatsapp)
        $adminWhatsapp = trim((string) Setting::where('key', 'admin_whatsapp')->value('value'));
        $whatsappNumber = trim((string) Setting::where('key', 'whatsapp_number')->value('value'));
        $cinemaPhone = trim((string) Setting::where('key', 'cinema_phone')->value('value'));

        $canonicalPhone = $adminWhatsapp ?: ($whatsappNumber ?: $cinemaPhone);
        if ($canonicalPhone !== '') {
            Setting::updateOrCreate(['key' => 'admin_whatsapp'], ['value' => $canonicalPhone, 'group' => 'kontak']);
            Setting::updateOrCreate(['key' => 'whatsapp_number'], ['value' => $canonicalPhone, 'group' => 'kontak']);
            Setting::updateOrCreate(['key' => 'cinema_phone'], ['value' => $canonicalPhone, 'group' => 'kontak']);
        }

        // 3. Unifikasi Email Bioskop (Canonical: contact_email)
        $contactEmail = trim((string) Setting::where('key', 'contact_email')->value('value'));
        $cinemaEmail = trim((string) Setting::where('key', 'cinema_email')->value('value'));

        $canonicalEmail = $contactEmail ?: $cinemaEmail;
        if ($canonicalEmail !== '') {
            Setting::updateOrCreate(['key' => 'contact_email'], ['value' => $canonicalEmail, 'group' => 'kontak']);
            Setting::updateOrCreate(['key' => 'cinema_email'], ['value' => $canonicalEmail, 'group' => 'kontak']);
        }

        // 4. Bersihkan YouTube jika hanya berisi data seed demo palsu
        $youtubeUrl = trim((string) Setting::where('key', 'youtube_url')->value('value'));
        $socialYoutube = trim((string) Setting::where('key', 'social_youtube')->value('value'));
        if (
            ($youtubeUrl === 'https://youtube.com/@synemax.cinema' || $youtubeUrl === 'https://www.youtube.com/@synemax.cinema')
            && ($socialYoutube === '' || $socialYoutube === 'https://youtube.com/@synemax.cinema')
        ) {
            Setting::updateOrCreate(['key' => 'youtube_url'], ['value' => '', 'group' => 'kontak']);
            Setting::updateOrCreate(['key' => 'youtube_label'], ['value' => '', 'group' => 'kontak']);
            Setting::updateOrCreate(['key' => 'social_youtube'], ['value' => '', 'group' => 'kontak']);
        }

        // 5. Bersihkan Google Maps jika hanya koordinat demo default Jakarta
        $mapsUrl = trim((string) Setting::where('key', 'maps_url')->value('value'));
        if ($mapsUrl === 'https://maps.google.com/?q=-6.200000,106.816666') {
            Setting::updateOrCreate(['key' => 'maps_url'], ['value' => '', 'group' => 'kontak']);
            Setting::updateOrCreate(['key' => 'contact_maps_url'], ['value' => '', 'group' => 'kontak']);
            Setting::updateOrCreate(['key' => 'google_maps_url'], ['value' => '', 'group' => 'kontak']);
        }
    }

    public function down(): void
    {
        // Additive migration, tidak menghapus data
    }
};
