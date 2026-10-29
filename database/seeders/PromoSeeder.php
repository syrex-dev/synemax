<?php

namespace Database\Seeders;

use App\Models\Promo;
use App\Support\ImagePlaceholderGenerator;
use Illuminate\Database\Seeder;

class PromoSeeder extends Seeder
{
    public function run(): void
    {
        $promos = [
            [
                'title' => '[Contoh] Paket Combo Nonton Berdua Hemat',
                'slug' => 'contoh-paket-combo-nonton-berdua-hemat',
                'image_filename' => 'promos/promo_combo_berdua.png',
                'start_date' => now()->subDays(7)->toDateString(),
                'end_date' => now()->addDays(14)->toDateString(),
                'description' => "Nonton film favorit lebih seru dan hemat berdua bersama sahabat atau pasangan! Dapatkan 2 tiket nonton studio privat lengkap dengan cemilan hangat gurih dan 2 minuman segar favoritmu.\n\nSuasana studio sejuk berpendingin udara dengan sofa empuk siap menyambutmu.",
                'terms_conditions' => "1. Berlaku untuk pemesanan minimal 2 kursi pada sesi penayangan yang sama.\n2. Berlaku untuk seluruh judul film yang tersedia di katalog SynemaX.\n3. Paket camilan disajikan langsung di tempat dan tidak dapat ditukar uang tunai.\n4. Konfirmasi pembayaran selambat-lambatnya 30 menit sebelum sesi dimulai.\n5. Tidak dapat digabungkan dengan program promo lainnya.",
                'is_active' => true,
                'sort_order' => 1,
                'color_scheme' => 'amber',
            ],
            [
                'title' => '[Contoh] Sewa Studio Privat 6 Kursi Bebas Pilih Film',
                'slug' => 'contoh-sewa-studio-privat-6-kursi',
                'image_filename' => 'promos/promo_sewa_privat.png',
                'start_date' => now()->subDays(10)->toDateString(),
                'end_date' => now()->addDays(30)->toDateString(),
                'description' => "Ingin nonton bareng keluarga besar atau sahabat terdekat tanpa ada penonton asing? Kunci seluruh 6 kursi privat di studio SynemaX hanya dengan Rp 36.000 saja.\n\nBebas request film favorit dari katalog, atur volume audio Dolby sesuai kenyamanan, dan nikmati waktu berkualitas bersama orang tersayang.",
                'terms_conditions' => "1. Paket mencakup seluruh kapasitas studio privat (maksimal 6 orang dewasa).\n2. Pilihan film bebas dipilih dari katalog SynemaX atau request khusus.\n3. Dilarang membawa makanan berat berbau menyengat dari luar studio.\n4. Pembatalan atau penyesuaian jadwal dapat dilakukan maksimal 6 jam sebelum jam sesi.\n5. Harap hadir 15 menit sebelum waktu pemutaran film dimulai.",
                'is_active' => true,
                'sort_order' => 2,
                'color_scheme' => 'blue',
            ],
            [
                'title' => '[Contoh] Diskon Pelajar & Mahasiswa Rabu Seru',
                'slug' => 'contoh-diskon-pelajar-mahasiswa-rabu-seru',
                'image_filename' => 'promos/promo_diskon_pelajar.png',
                'start_date' => now()->subDays(3)->toDateString(),
                'end_date' => now()->addDays(21)->toDateString(),
                'description' => 'Hari Rabu waktunya rileks bareng kawan kampus atau sekolah! Dapatkan potongan harga spesial tiket nonton di semua sesi hari Rabu cukup dengan menunjukkan kartu identitas pelajar atau mahasiswa aktif saat tiba di kasir.',
                'terms_conditions' => "1. Berlaku eksklusif untuk penayangan di setiap hari Rabu.\n2. Wajib menunjukkan kartu pelajar atau kartu tanda mahasiswa aktif di kasir.\n3. Berlaku maksimal untuk 2 tiket per kartu identitas yang valid.\n4. Pemesanan tetap wajib dilakukan melalui website untuk reservasi kursi.\n5. Promo ini berlaku selama kuota kursi hari Rabu masih tersedia.",
                'is_active' => true,
                'sort_order' => 3,
                'color_scheme' => 'purple',
            ],
            [
                'title' => '[Contoh] Promo Pembukaan Awal Musim Liburan',
                'slug' => 'contoh-promo-pembukaan-awal-musim-liburan',
                'image_filename' => 'promos/promo_liburan_selesai.png',
                'start_date' => now()->subDays(30)->toDateString(),
                'end_date' => now()->subDays(5)->toDateString(), // EXPIRED!
                'description' => 'Promo bundling liburan sekolah dengan hadiah voucher camilan mie rebus dan es cekek gratis untuk setiap pemesanan rombongan lebih dari 4 orang.',
                'terms_conditions' => "1. Periode promo telah resmi berakhir.\n2. Simak terus daftar promo aktif kami untuk penawaran musim berikutnya.",
                'is_active' => true,
                'sort_order' => 4,
                'color_scheme' => 'amber',
            ],
        ];

        foreach ($promos as $item) {
            $imagePath = ImagePlaceholderGenerator::generate(
                $item['image_filename'],
                1200,
                600,
                $item['title'],
                'PROMO SPESIAL',
                $item['color_scheme'] ?? 'amber',
            );

            Promo::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'title' => $item['title'],
                    'image_path' => $imagePath,
                    'start_date' => $item['start_date'],
                    'end_date' => $item['end_date'],
                    'description' => $item['description'],
                    'terms_conditions' => $item['terms_conditions'],
                    'is_active' => $item['is_active'],
                    'sort_order' => $item['sort_order'],
                ],
            );
        }
    }
}
