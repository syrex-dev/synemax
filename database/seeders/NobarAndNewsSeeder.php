<?php

namespace Database\Seeders;

use App\Models\NewsArticle;
use App\Models\NobarEvent;
use App\Models\Setting;
use App\Support\ImagePlaceholderGenerator;
use Illuminate\Database\Seeder;

class NobarAndNewsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Enable Info & Nobar feature setting
        Setting::firstOrCreate(
            ['key' => 'info_nobar_enabled'],
            ['value' => '1'],
        );

        // 2. Seed generic soccer nobar events without trademarked clubs or logos
        $events = [
            [
                'judul' => '[Contoh] Nobar Seru Akhir Pekan: Tim Garuda Muda vs Tim Bintang Timur',
                'slug' => 'contoh-nobar-tim-garuda-muda-vs-tim-bintang-timur',
                'kompetisi' => 'Turnamen Persahabatan Komunitas',
                'tim_a' => 'Tim Garuda Muda',
                'tim_b' => 'Tim Bintang Timur',
                'kickoff_at' => now()->addDays(2)->setTime(19, 30),
                'durasi_menit' => 150,
                'lokasi_nama' => 'SynemaX Studio & Lounge',
                'alamat_patokan' => 'Jl. Melati No. 12, Area Nobar Komunitas (Sebelah Kedai Kopi)',
                'maps_url' => 'https://maps.google.com/?q=-6.200000,106.816666',
                'is_free' => true,
                'harga_info' => null,
                'catatan' => "Siaran langsung layar lebar resolusi tinggi didukung tata suara surround. Tersedia camilan hangat dan minuman segar di lokasi.\n\n- Dilarang membawa makanan dan minuman dari luar\n- Pintu studio dibuka 30 menit sebelum kickoff\n- Kontak RSVP: 0812-3456-7890",
                'status_acara' => 'aktif',
                'status_publikasi' => 'terbit',
                'is_pinned' => true,
            ],
            [
                'judul' => '[Contoh] Nobar Perebutan Puncak Klasemen: Tim Singa Perkasa vs Tim Rajawali',
                'slug' => 'contoh-nobar-tim-singa-perkasa-vs-tim-rajawali',
                'kompetisi' => 'Liga Akhir Pekan Antar Kota',
                'tim_a' => 'Tim Singa Perkasa',
                'tim_b' => 'Tim Rajawali',
                'kickoff_at' => now()->addDays(5)->setTime(21, 00),
                'durasi_menit' => 150,
                'lokasi_nama' => 'SynemaX Studio & Lounge',
                'alamat_patokan' => 'Jl. Melati No. 12, Area Nobar Komunitas (Sebelah Kedai Kopi)',
                'maps_url' => 'https://maps.google.com/?q=-6.200000,106.816666',
                'is_free' => false,
                'harga_info' => 'Rp 15.000 (termasuk voucher minuman dingin)',
                'catatan' => "Tiket masuk sudah termasuk voucher minuman dingin dan snack ringan.\n\nHarap konfirmasi kehadiran lebih awal untuk mengamankan kursi sofa terbaik.\n\nKontak RSVP: 0812-3456-7890",
                'status_acara' => 'aktif',
                'status_publikasi' => 'terbit',
                'is_pinned' => false,
            ],
            [
                'judul' => '[Contoh] Nobar Derby Komunitas: Tim Biru Langit vs Tim Merah Menyala',
                'slug' => 'contoh-nobar-tim-biru-langit-vs-tim-merah-menyala',
                'kompetisi' => 'Piala Musim Dingin Antar Komunitas',
                'tim_a' => 'Tim Biru Langit',
                'tim_b' => 'Tim Merah Menyala',
                'kickoff_at' => now()->addDays(7)->setTime(16, 00),
                'durasi_menit' => 150,
                'lokasi_nama' => 'SynemaX Studio & Lounge',
                'alamat_patokan' => 'Jl. Melati No. 12, Area Nobar Komunitas (Sebelah Kedai Kopi)',
                'maps_url' => 'https://maps.google.com/?q=-6.200000,106.816666',
                'is_free' => true,
                'harga_info' => null,
                'catatan' => "Keseruan derby komunitas dengan atmosfer tribun hangat dan ramah keluarga.\n\nKapasitas ruang terbatas, disarankan datang lebih awal.",
                'status_acara' => 'aktif',
                'status_publikasi' => 'terbit',
                'is_pinned' => false,
            ],
        ];

        foreach ($events as $event) {
            $posterPath = ImagePlaceholderGenerator::generate(
                'posters/'.$event['slug'].'.png',
                1200,
                600,
                $event['tim_a'].' vs '.$event['tim_b'],
                $event['kompetisi'],
                'blue'
            );
            $event['poster_path'] = $posterPath;

            // Also mirror to local disk nobar/ for seamless access
            $localNobarPath = storage_path('app/nobar/'.basename($posterPath));
            if (! file_exists($localNobarPath)) {
                @mkdir(dirname($localNobarPath), 0755, true);
                @copy(storage_path('app/public/'.$posterPath), $localNobarPath);
            }

            NobarEvent::updateOrCreate(
                ['slug' => $event['slug']],
                $event,
            );
        }

        // 3. Seed generic news and promo articles
        $articles = [
            [
                'judul' => '[Contoh] Suasana Baru Nobar dan Nonton Film Lebih Nyaman di SynemaX',
                'slug' => 'contoh-suasana-baru-nobar-dan-nonton-film-lebih-nyaman',
                'kategori' => 'Berita',
                'ringkasan' => 'SynemaX terus berbenah menghadirkan pengalaman audio visual yang lebih jernih serta ruang tunggu santai untuk para penikmat film dan komunitas sepak bola.',
                'konten' => "Kami selalu berupaya menghadirkan pengalaman menonton terbaik untuk Anda, baik untuk sesi bioskop privat bersama keluarga maupun keseruan nonton bareng pertandingan komunitas.\n\n## Peningkatan Fasilitas\n\nBeberapa pembaruan yang kini hadir di studio:\n\n- **Kualitas Gambar & Tata Suara:** Kalibrasi proyektor beresolusi tinggi dengan tata suara surround yang lebih seimbang dan memanjakan telinga.\n- **Sofa Ergonomis:** Kursi yang lebih empuk untuk kenyamanan durasi menonton hingga 3 jam.\n- **Pilihan Menu Hangat:** Menu mi rebus racikan spesial, aneka sosis panggang, dan seduhan kopi lokal siap menemani tontonan Anda.\n\n> Datang dan rasakan kenyamanan menonton dekat layaknya di ruang keluarga sendiri!",
                'status_publikasi' => 'terbit',
                'tanggal_terbit' => now()->subDays(3),
                'is_pinned' => true,
            ],
            [
                'judul' => '[Contoh] Paket Combo Cemilan Hangat Spesial Akhir Pekan',
                'slug' => 'contoh-paket-combo-cemilan-hangat-spesial-akhir-pekan',
                'kategori' => 'Pengumuman',
                'ringkasan' => 'Nikmati bundling camilan goreng renyah dan minuman segar dengan harga hemat khusus pemesanan sesi akhir pekan.',
                'konten' => "Menonton film favorit atau mendukung tim jagoan tentu makin asyik ditemani camilan gurih yang hangat.\n\n## Menu Paket Hemat\n\n1. **Paket Santai:** Tahu krispi + Teh manis dingin hanya Rp 7.000.\n2. **Paket Kenyang:** Mi rebus telur + Sosis goreng + Air mineral dingin hanya Rp 14.000.\n\nCamilan bisa langsung Anda pesan bersamaan saat melakukan reservasi jadwal secara online tanpa perlu repot antre di lokasi.",
                'status_publikasi' => 'terbit',
                'tanggal_terbit' => now()->subDays(1),
                'is_pinned' => false,
            ],
            [
                'judul' => '[Contoh] Panduan Reservasi Studio dan Protokol Kenyamanan Bersama',
                'slug' => 'contoh-panduan-reservasi-studio-dan-protokol-kenyamanan',
                'kategori' => 'Pengumuman',
                'ringkasan' => 'Simak tips dan aturan reservasi sesi bioskop agar pengalaman menonton Anda dan rombongan berjalan lancar dan berkesan.',
                'konten' => "Demi kenyamanan dan ketertiban seluruh penonton, berikut beberapa hal yang perlu diperhatikan:\n\n- **Konfirmasi Pembayaran:** Pastikan menyelesaikan konfirmasi pembayaran sesuai instruksi batas waktu pada tiket.\n- **Waktu Kedatangan:** Sangat disarankan tiba di lokasi 10-15 menit sebelum waktu tayang dimulai.\n- **Privasi & Kebersihan:** Harap menjaga kebersihan area sofa dan membuang bungkus makanan ke tempat sampah yang disediakan.\n\nTerima kasih atas kerja samanya dalam menjaga ruang bersama kita tetap bersih dan nyaman!",
                'status_publikasi' => 'terbit',
                'tanggal_terbit' => now()->subHours(6),
                'is_pinned' => false,
            ],
        ];

        foreach ($articles as $article) {
            $coverPath = ImagePlaceholderGenerator::generate(
                'articles/'.$article['slug'].'.png',
                1200,
                600,
                $article['judul'],
                $article['kategori'].' • SynemaX',
                'amber'
            );
            $article['cover_path'] = $coverPath;

            // Also mirror to local disk news/ for seamless access
            $localNewsPath = storage_path('app/news/'.basename($coverPath));
            if (! file_exists($localNewsPath)) {
                @mkdir(dirname($localNewsPath), 0755, true);
                @copy(storage_path('app/public/'.$coverPath), $localNewsPath);
            }

            NewsArticle::updateOrCreate(
                ['slug' => $article['slug']],
                $article,
            );
        }
    }
}
