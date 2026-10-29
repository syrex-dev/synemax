<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Film;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\ScheduleTemplate;
use App\Models\Session;
use App\Models\Setting;
use App\Models\User;
use App\Services\CinemaSettings;
use App\Support\ImagePlaceholderGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedScheduleTemplates();
        $this->seedSettings();
        $this->seedFilms();
        $this->seedSessions();
        $this->seedSnackMenu();
        $this->seedAdmin();
        $this->seedSampleBooking();

        $this->call(NobarAndNewsSeeder::class);
        $this->call(PromoSeeder::class);
        $this->call(BannerSeeder::class);
    }

    private function seedScheduleTemplates(): void
    {
        $schedules = [
            'weekday' => ['16:00', '19:30'],
            'jumat' => ['13:00', '16:00', '19:30'],
            'weekend' => ['09:00', '13:00', '16:00', '19:30'],
            'libur' => ['09:00', '13:00', '16:00', '19:30'],
        ];

        foreach ($schedules as $dayType => $startTimes) {
            foreach ($startTimes as $startTime) {
                ScheduleTemplate::updateOrCreate(
                    ['day_type' => $dayType, 'start_time' => $startTime],
                    ['duration_minutes' => 150, 'is_active' => true],
                );
            }
        }
    }

    private function seedSettings(): void
    {
        // Generate site brand images
        $logoLight = ImagePlaceholderGenerator::generate('site/logo_light.png', 400, 80, 'SYNEMAX', 'STUDIO CINEMA', 'amber');
        $favicon = ImagePlaceholderGenerator::generate('site/favicon.png', 512, 512, 'SX', 'LOGO', 'amber');
        $ogImage = ImagePlaceholderGenerator::generate('site/og_share.png', 1200, 630, 'SynemaX Cinema', 'STUDIO MINI PRIVAT', 'amber');

        $settings = [
            // Tab 1: Identitas Situs
            'site_name' => 'SynemaX',
            'site_tagline' => 'Studio Bioskop Mini Eksklusif Berpendingin Udara',
            'brand_display' => 'both',
            'logo_light' => $logoLight,
            'favicon' => $favicon,
            'og_image' => $ogImage,

            // Tab 2: Navbar & Menu
            'navbar_menu' => json_encode([
                ['id' => 'home', 'label' => 'Beranda', 'url' => '/', 'is_visible' => true, 'order' => 1],
                ['id' => 'schedule', 'label' => 'Jadwal', 'url' => '/jadwal', 'is_visible' => true, 'order' => 2],
                ['id' => 'film', 'label' => 'Film', 'url' => '/film', 'is_visible' => true, 'order' => 3],
                ['id' => 'nobar', 'label' => 'Nobar', 'url' => '/nobar', 'is_visible' => true, 'order' => 4],
                ['id' => 'promo', 'label' => 'Promo', 'url' => '/promo', 'is_visible' => true, 'order' => 5],
                ['id' => 'kabar', 'label' => 'Kabar', 'url' => '/kabar', 'is_visible' => true, 'order' => 6],
                ['id' => 'booking_lookup', 'label' => 'Cek booking', 'url' => '/cek-booking', 'is_visible' => true, 'order' => 7],
            ], JSON_THROW_ON_ERROR),

            // Tab 3: Tampilan
            'default_theme' => 'system',
            'show_theme_toggle' => '1',
            'homepage_info_chips' => json_encode([
                ['id' => 'chip_1', 'text' => '6 kursi privat per sesi', 'icon' => 'chair', 'is_visible' => true],
                ['id' => 'chip_2', 'text' => 'Mulai Rp 5.000', 'icon' => 'tag', 'is_visible' => true],
                ['id' => 'chip_3', 'text' => 'Studio AC & Audio Dolby', 'icon' => 'sparkles', 'is_visible' => true],
            ], JSON_THROW_ON_ERROR),
            'homepage_films_count' => '8',
            'hero_autoplay_interval' => '5',
            'hero_autoplay_enabled' => '1',

            // Tab 4: Operasional
            'opening_hours_text' => 'Setiap Hari: 09.00 - 22.00 WIB',
            'seats_capacity' => '6',
            'booking_cutoff_minutes' => '30',
            'max_seats_per_booking' => '6',
            'minimum_public_viewers' => 4,
            'agreement_window_minutes' => 180,
            'confirmation_deadline_minutes' => 60,
            'cancellation_cutoff_hours' => 6,
            'enabled_payment_methods' => json_encode(['di_tempat', 'qris', 'transfer'], JSON_THROW_ON_ERROR),
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'SynemaX Studio',
            'payment_instructions' => 'Mohon selesaikan konfirmasi pembayaran sesuai instruksi pada tiket pemesanan Anda.',
            'maintenance_mode' => '0',
            'maintenance_message' => 'Situs SynemaX sedang dalam peningkatan sistem berkala. Silakan kembali beberapa saat lagi.',

            // Tab 5: Harga & FAQ
            'weekday_price' => 20000,
            'weekend_price' => 24000,
            'holiday_price' => 24000,
            'private_price' => 180000,
            'couple_weekday_price' => 0,
            'couple_weekend_price' => 0,
            'private_weekday_price' => 0,
            'private_weekend_price' => 0,
            'faq_items' => json_encode([
                [
                    'question' => 'Berapa kapasitas kursi dalam satu sesi?',
                    'answer' => 'Kapasitas maksimal satu sesi adalah 6 kursi privat dengan fasilitas sofa ergonomis ber-AC dan audio Dolby surround.',
                    'order' => 1,
                ],
                [
                    'question' => 'Apakah penonton bisa memilih film sendiri?',
                    'answer' => 'Bisa! SynemaX beroperasi dengan konsep katalog film on-demand. Anda dapat memilih judul apa pun yang tersedia di katalog atau memesan seluruh 6 kursi untuk sesi privat khusus.',
                    'order' => 2,
                ],
                [
                    'question' => 'Bagaimana metode pembayaran yang tersedia?',
                    'answer' => 'Pembayaran dapat dilakukan langsung di tempat saat kedatangan, melalui scan QRIS, atau Transfer Bank. Tiket langsung terverifikasi secara sistem.',
                    'order' => 3,
                ],
                [
                    'question' => 'Apakah boleh membawa makanan dari luar studio?',
                    'answer' => 'Untuk menjaga kebersihan dan kenyamanan sofa studio, dilarang membawa makanan berbau menyengat. Kami menyediakan aneka cemilan hangat dan minuman segar dengan harga terjangkau.',
                    'order' => 4,
                ],
            ], JSON_THROW_ON_ERROR),

            // Tab 6: Kontak & Sosial
            'cinema_phone' => '6285169730960',
            'whatsapp_number' => '6285169730960',
            'admin_whatsapp' => '6285169730960',
            'cinema_email' => 'halo@synemax.test',
            'cinema_address' => 'Jl. Melati No. 12, Jawa Tengah',
            'google_maps_url' => 'https://maps.google.com/?q=-6.200000,106.816666',
            'instagram_url' => 'https://instagram.com/synemax.cinema',
            'youtube_url' => 'https://youtube.com/@synemax.cinema',
            'tiktok_url' => 'https://tiktok.com/@synemax.cinema',
            'footer_text' => '© '.date('Y').' SynemaX Cinema. All rights reserved.',
            'show_floating_whatsapp' => '1',

            // Tab 7: SEO
            'seo_title_template' => '{title} - SynemaX Bioskop Mini',
            'seo_default_description' => 'Studio bioskop mini eksklusif 6 kursi privat ber-AC di Jawa Tengah dengan sistem audio Dolby surround. Pesan tiket dan tonton film favoritmu.',

            // Legacy & snack configs
            'info_nobar_enabled' => '1',
            'electricity_cost_per_session' => 0,
            'snacks_enabled' => 1,
            'snack_prep_buffer_minutes' => 15,
            'snack_max_qty_per_item' => 10,
        ];

        foreach ($settings as $key => $value) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => (string) $value]
            );
        }

        // Clear settings cache
        app(CinemaSettings::class)->clearCache();
    }

    private function seedFilms(): void
    {
        $films = [
            [
                'title' => '[Contoh] Malam di Bukit Bintang',
                'slug' => 'contoh-malam-di-bukit-bintang',
                'duration_minutes' => 105,
                'genre' => 'Drama, Keluarga',
                'release_year' => 2024,
                'age_rating' => 'SU',
                'synopsis' => 'Kisah keluarga kecil yang menemukan kembali kehangatan dan kebersamaan di sebuah desa pegunungan yang tenang di bawah taburan bintang.',
                'color' => 'amber',
            ],
            [
                'title' => '[Contoh] Misi Terakhir Pak Pos',
                'slug' => 'contoh-misi-terakhir-pak-pos',
                'duration_minutes' => 98,
                'genre' => 'Komedi, Petualangan',
                'release_year' => 2023,
                'age_rating' => '13+',
                'synopsis' => 'Pak pos senior dan cucunya berkejaran dengan waktu serta kejadian-kejadian lucu tak terduga demi mengantarkan sebuah surat penting sebelum pensiun.',
                'color' => 'blue',
            ],
            [
                'title' => '[Contoh] Rahasia Rumah Tua',
                'slug' => 'contoh-rahasia-rumah-tua',
                'duration_minutes' => 112,
                'genre' => 'Horor, Misteri',
                'release_year' => 2025,
                'age_rating' => '17+',
                'synopsis' => 'Tiga sahabat pecinta misteri menelusuri kisah lama yang terkunci di dalam sebuah rumah kosong pinggir sawah berpuluh tahun silam.',
                'color' => 'purple',
            ],
            [
                'title' => '[Contoh] Laskar Bahari Nusantara',
                'slug' => 'contoh-laskar-bahari-nusantara',
                'duration_minutes' => 110,
                'genre' => 'Petualangan',
                'release_year' => 2024,
                'age_rating' => 'SU',
                'synopsis' => 'Petualangan inspiratif sekelompok pemuda pesisir menjaga ekosistem terumbu karang dan warisan luhur bahari dari perusakan kapal liar.',
                'color' => 'blue',
            ],
            [
                'title' => '[Contoh] Detektif Cilik & Misteri Jam Gadang',
                'slug' => 'contoh-detektif-cilik-misteri-jam-gadang',
                'duration_minutes' => 95,
                'genre' => 'Animasi, Keluarga',
                'release_year' => 2025,
                'age_rating' => 'SU',
                'synopsis' => 'Keseruan anak-anak cerdik memecahkan teka-teki sandi kuno di lorong bawah tanah kota tua saat libur sekolah.',
                'color' => 'amber',
            ],
            [
                'title' => '[Contoh] Pusaka Rimba Terlarang',
                'slug' => 'contoh-pusaka-rimba-terlarang',
                'duration_minutes' => 108,
                'genre' => 'Aksi, Fantasi',
                'release_year' => 2024,
                'age_rating' => '13+',
                'synopsis' => 'Perjalanan menegangkan menelusuri belantara rimba purba untuk mencari pusaka legendaris pelindung perbatasan desa.',
                'color' => 'purple',
            ],
            [
                'title' => '[Contoh] Sang Penjaga Cakrawala',
                'slug' => 'contoh-sang-penjaga-cakrawala',
                'duration_minutes' => 125,
                'genre' => 'Sci-Fi, Aksi',
                'release_year' => 2026,
                'age_rating' => '13+',
                'synopsis' => 'Stasiun pengamat luar angkasa pertama nusantara mendeteksi sinyal anomali misterius dari nebula terjauh galaksi.',
                'color' => 'blue',
            ],
            [
                'title' => '[Contoh] Jejak Langkah Guru Desa',
                'slug' => 'contoh-jejak-langkah-guru-desa',
                'duration_minutes' => 102,
                'genre' => 'Drama',
                'release_year' => 2022,
                'age_rating' => 'SU',
                'synopsis' => 'Perjuangan tulus seorang guru honorer muda yang mendedikasikan hidupnya untuk mendidik anak-anak di pulau terpencil tanpa listrik.',
                'color' => 'amber',
            ],
            [
                'title' => '[Contoh] Melodi Senja di Tepi Sawah',
                'slug' => 'contoh-melodi-senja-di-tepi-sawah',
                'duration_minutes' => 94,
                'genre' => 'Romantis, Drama',
                'release_year' => 2023,
                'age_rating' => '13+',
                'synopsis' => 'Pertemuan tak sengaja antara seorang musisi jalanan ibu kota dengan perajin batik lokal di tepi persawahan hijau nan damai.',
                'color' => 'amber',
            ],
            [
                'title' => '[Contoh] Operasi Sandi Merah Putih',
                'slug' => 'contoh-operasi-sandi-merah-putih',
                'duration_minutes' => 115,
                'genre' => 'Aksi, Thriller',
                'release_year' => 2025,
                'age_rating' => '17+',
                'synopsis' => 'Satuan tim taktis rahasia berusaha membongkar jaringan penyelundup barang terlarang lintas pulau di perairan selat.',
                'color' => 'purple',
            ],
            [
                'title' => '[Contoh] Tawaria di Pasar Kaget',
                'slug' => 'contoh-tawaria-di-pasar-kaget',
                'duration_minutes' => 88,
                'genre' => 'Komedi',
                'release_year' => 2024,
                'age_rating' => 'SU',
                'synopsis' => 'Kekacauan kocak saat pedagang pasar tumpah tradisional berlomba memenangkan undian berhadiah gerobak listrik impian.',
                'color' => 'amber',
            ],
            [
                'title' => '[Contoh] Legenda Kerajaan Laut Selatan',
                'slug' => 'contoh-legenda-kerajaan-laut-selatan',
                'duration_minutes' => 120,
                'genre' => 'Fantasi, Petualangan',
                'release_year' => 2026,
                'age_rating' => '13+',
                'synopsis' => 'Cerita mitologi megah tentang putri samudra yang menjaga keharmonisan antara manusia daratan dan penghuni kedalaman samudra.',
                'color' => 'blue',
            ],
        ];

        foreach ($films as $filmData) {
            $posterPath = ImagePlaceholderGenerator::generate(
                'posters/'.$filmData['slug'].'.png',
                600,
                900,
                $filmData['title'],
                $filmData['genre'].' • '.$filmData['release_year'],
                $filmData['color'] ?? 'amber',
            );

            $model = Film::withTrashed()->updateOrCreate(
                ['slug' => $filmData['slug']],
                [
                    'title' => $filmData['title'],
                    'slug' => $filmData['slug'],
                    'duration_minutes' => $filmData['duration_minutes'],
                    'genre' => $filmData['genre'],
                    'release_year' => $filmData['release_year'],
                    'age_rating' => $filmData['age_rating'],
                    'synopsis' => $filmData['synopsis'],
                    'poster_path' => $posterPath,
                    'is_active' => true,
                    'is_coming_soon' => false,
                    'license_notes' => 'Judul dan sinopsis ini adalah data demo SynemaX.',
                ],
            );

            if ($model->trashed()) {
                $model->restore();
            }
        }

        // Ensure any legacy film without poster has a generated poster
        foreach (Film::whereNull('poster_path')->get() as $legacyFilm) {
            $poster = ImagePlaceholderGenerator::generate(
                'posters/'.($legacyFilm->slug ?: 'film-'.$legacyFilm->id).'.png',
                600,
                900,
                $legacyFilm->title,
                ($legacyFilm->genre ?: 'Film').' • '.($legacyFilm->release_year ?: 2024),
                'amber'
            );
            $legacyFilm->update(['poster_path' => $poster]);
        }
    }

    private function seedSessions(): void
    {
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $sampleFilms = Film::query()->where('is_active', true)->orderBy('id')->get();

        if ($sampleFilms->isEmpty()) {
            return;
        }

        // Schedule screening sessions for 7 days (28 upcoming sessions)
        $times = [
            ['start' => '10:00', 'end' => '12:15'],
            ['start' => '13:00', 'end' => '15:15'],
            ['start' => '16:00', 'end' => '18:15'],
            ['start' => '19:30', 'end' => '21:45'],
        ];

        foreach (range(0, 6) as $dayOffset) {
            $date = $today->addDays($dayOffset)->toDateString();

            foreach ($times as $idx => $slot) {
                Session::query()->updateOrCreate(
                    [
                        'session_date' => $date,
                        'start_time' => $slot['start'],
                    ],
                    [
                        'end_time' => $slot['end'],
                        'film_id' => null,
                        'capacity' => 6,
                        'is_private' => false,
                        'status' => 'tersedia',
                    ]
                );
            }
        }
    }

    private function seedAdmin(): void
    {
        $email = (string) env('ADMIN_EMAIL', 'admin@synemax.com');
        $password = (string) env('ADMIN_PASSWORD', 'PasswordAdmin123');

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin SynemaX',
                'password' => $password,
                'is_admin' => true,
            ],
        );
    }

    private function seedSnackMenu(): void
    {
        $menu = [
            'Minuman Dingin' => [
                ['Air mineral', 3000, 1],
                ['Es cekek', 3000, 5],
            ],
            'Minuman Seduh' => [
                ['Kopi/teh/minuman sachet', 3000, 5],
            ],
            'Es Krim' => [
                ['Ice cream', 5000, 2],
            ],
            'Snack Kemasan' => [
                ['Chiki', 5000, 1],
            ],
            'Makanan Hangat' => [
                ['Mie rebus', 8000, 15],
            ],
            'Camilan Goreng' => [
                ['Tahu kecil', 5000, 10],
                ['Sosis', 5000, 10],
            ],
        ];

        foreach ($menu as $categoryOrder => $items) {
            $categoryName = (string) $categoryOrder;
            $category = MenuCategory::query()->firstOrCreate(
                ['name' => $categoryName],
                ['sort_order' => array_search($categoryName, array_keys($menu), true) * 10, 'is_active' => true],
            );

            foreach ($items as $itemOrder => [$name, $price, $prepMinutes]) {
                MenuItem::query()->firstOrCreate(
                    ['category_id' => $category->id, 'name' => $name],
                    [
                        'price' => $price,
                        'prep_minutes' => $prepMinutes,
                        'stock_mode' => 'toggle',
                        'stock_qty' => null,
                        'is_available' => true,
                        'is_active' => true,
                        'sort_order' => $itemOrder * 10,
                    ],
                );
            }
        }
    }

    private function seedSampleBooking(): void
    {
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $sampleFilms = Film::query()->where('is_active', true)->orderBy('id')->get();
        if ($sampleFilms->count() < 4) {
            return;
        }

        $filmA = $sampleFilms[0];
        $filmB = $sampleFilms[1];
        $filmC = $sampleFilms[2];
        $filmD = $sampleFilms[3];

        $water = MenuItem::query()->where('name', 'Air mineral')->first();
        $noodles = MenuItem::query()->where('name', 'Mie rebus')->first();
        $admin = User::query()->where('is_admin', true)->orderBy('id')->first();

        // 1. Sesi Batal karena Kuorum (Kemarin 19:30)
        $yesterdayDate = $today->subDay()->toDateString();
        $cancelledSession = Session::query()->updateOrCreate(
            ['session_date' => $yesterdayDate, 'start_time' => '19:30'],
            [
                'end_time' => '21:45',
                'film_id' => null,
                'capacity' => 6,
                'is_private' => false,
                'status' => 'ditutup',
                'resolution_notes' => 'Dibatalkan otomatis: kuorum tidak terpenuhi hingga batas konfirmasi.',
            ]
        );
        Booking::query()->updateOrCreate(
            ['code' => 'HC-BATAL-KUORUM'],
            [
                'session_id' => $cancelledSession->id,
                'film_id' => $filmA->id,
                'package' => 'reguler',
                'customer_name' => 'Dimas Anggara',
                'whatsapp' => '6281234567890',
                'pax' => 2,
                'people_count' => 2,
                'seats_reserved' => 2,
                'booking_type' => 'per_orang',
                'total_price' => 40000,
                'ticket_total' => 40000,
                'snack_total' => 0,
                'payment_method' => 'qris',
                'status' => 'dibatalkan',
                'refund_needed' => true,
                'notes' => 'Dibatalkan: kuorum tidak terpenuhi',
                'cancelled_at' => now()->subDay(),
            ]
        );

        // 2. Hari Ini 13:00: Menunggu Kuorum (A: 1 kursi)
        $sToday13 = Session::query()->where('session_date', $today->toDateString())->where('start_time', '13:00')->first();
        if ($sToday13) {
            $sToday13->update(['film_id' => null, 'confirmed_at' => null, 'confirmation_method' => null]);
            Booking::query()->updateOrCreate(
                ['code' => 'HC-TODAY-A1'],
                [
                    'session_id' => $sToday13->id,
                    'film_id' => $filmA->id,
                    'package' => 'reguler',
                    'customer_name' => 'Budi Pratama',
                    'whatsapp' => '6281234567891',
                    'pax' => 1,
                    'people_count' => 1,
                    'seats_reserved' => 1,
                    'booking_type' => 'per_orang',
                    'total_price' => 20000,
                    'ticket_total' => 20000,
                    'snack_total' => 0,
                    'payment_method' => 'di_tempat',
                    'status' => 'terkonfirmasi',
                ]
            );
        }

        // 3. Hari Ini 16:00: Menunggu Kuorum (A: 2 kursi, B: 1 kursi -> leading A:2, butuh 2 lagi)
        $sToday16 = Session::query()->where('session_date', $today->toDateString())->where('start_time', '16:00')->first();
        if ($sToday16) {
            $sToday16->update(['film_id' => null, 'confirmed_at' => null, 'confirmation_method' => null]);
            Booking::query()->updateOrCreate(
                ['code' => 'HC-TODAY-A2'],
                [
                    'session_id' => $sToday16->id,
                    'film_id' => $filmA->id,
                    'package' => 'reguler',
                    'customer_name' => 'Citra Dewi',
                    'whatsapp' => '6281234567892',
                    'pax' => 2,
                    'people_count' => 2,
                    'seats_reserved' => 2,
                    'booking_type' => 'per_orang',
                    'total_price' => 40000,
                    'ticket_total' => 40000,
                    'snack_total' => 0,
                    'payment_method' => 'di_tempat',
                    'status' => 'terkonfirmasi',
                ]
            );
            Booking::query()->updateOrCreate(
                ['code' => 'HC-TODAY-B1'],
                [
                    'session_id' => $sToday16->id,
                    'film_id' => $filmB->id,
                    'package' => 'reguler',
                    'customer_name' => 'Doni Darmawan',
                    'whatsapp' => '6281234567893',
                    'pax' => 1,
                    'people_count' => 1,
                    'seats_reserved' => 1,
                    'booking_type' => 'per_orang',
                    'total_price' => 20000,
                    'ticket_total' => 20000,
                    'snack_total' => 0,
                    'payment_method' => 'di_tempat',
                    'status' => 'terkonfirmasi',
                ]
            );
        }

        // 4. Hari Ini 19:30: Butuh Kesepakatan (A: 2 kursi, B: 2 kursi -> imbang)
        $sToday19 = Session::query()->where('session_date', $today->toDateString())->where('start_time', '19:30')->first();
        if ($sToday19) {
            $sToday19->update(['film_id' => null, 'confirmed_at' => null, 'confirmation_method' => null]);
            Booking::query()->updateOrCreate(
                ['code' => 'HC-TIE-A2'],
                [
                    'session_id' => $sToday19->id,
                    'film_id' => $filmA->id,
                    'package' => 'reguler',
                    'customer_name' => 'Eka Saputra',
                    'whatsapp' => '6281234567894',
                    'pax' => 2,
                    'people_count' => 2,
                    'seats_reserved' => 2,
                    'booking_type' => 'per_orang',
                    'total_price' => 40000,
                    'ticket_total' => 40000,
                    'snack_total' => 0,
                    'payment_method' => 'di_tempat',
                    'status' => 'terkonfirmasi',
                ]
            );
            Booking::query()->updateOrCreate(
                ['code' => 'HC-TIE-B2'],
                [
                    'session_id' => $sToday19->id,
                    'film_id' => $filmB->id,
                    'package' => 'reguler',
                    'customer_name' => 'Fitri Handayani',
                    'whatsapp' => '6281234567895',
                    'pax' => 2,
                    'people_count' => 2,
                    'seats_reserved' => 2,
                    'booking_type' => 'per_orang',
                    'total_price' => 40000,
                    'ticket_total' => 40000,
                    'snack_total' => 0,
                    'payment_method' => 'di_tempat',
                    'status' => 'terkonfirmasi',
                ]
            );
        }

        // 5. Besok 10:00: Kosong (tidak ada booking)
        $sTomorrow10 = Session::query()->where('session_date', $today->addDay()->toDateString())->where('start_time', '10:00')->first();
        if ($sTomorrow10) {
            $sTomorrow10->update(['film_id' => null, 'confirmed_at' => null, 'confirmation_method' => null]);
        }

        // 6. Besok 13:00: Pasti Tayang via Kuorum (A: 4 kursi terkonfirmasi, B: 1 kursi perlu pilih ulang)
        $sTomorrow13 = Session::query()->where('session_date', $today->addDay()->toDateString())->where('start_time', '13:00')->first();
        if ($sTomorrow13) {
            $sTomorrow13->update([
                'film_id' => $filmA->id,
                'confirmed_at' => now(),
                'confirmation_method' => 'quorum',
                'resolution_notes' => 'Kuorum tercapai: 4 penonton.',
            ]);
            Booking::query()->updateOrCreate(
                ['code' => 'HC-QUORUM-A1'],
                [
                    'session_id' => $sTomorrow13->id,
                    'film_id' => $filmA->id,
                    'package' => 'reguler',
                    'customer_name' => 'Gilang Ramadhan',
                    'whatsapp' => '6281234567896',
                    'pax' => 2,
                    'people_count' => 2,
                    'seats_reserved' => 2,
                    'booking_type' => 'per_orang',
                    'total_price' => 40000,
                    'ticket_total' => 40000,
                    'snack_total' => 0,
                    'payment_method' => 'di_tempat',
                    'status' => 'terkonfirmasi',
                ]
            );
            Booking::query()->updateOrCreate(
                ['code' => 'HC-QUORUM-A2'],
                [
                    'session_id' => $sTomorrow13->id,
                    'film_id' => $filmA->id,
                    'package' => 'reguler',
                    'customer_name' => 'Hany Purnomo',
                    'whatsapp' => '6281234567897',
                    'pax' => 2,
                    'people_count' => 2,
                    'seats_reserved' => 2,
                    'booking_type' => 'per_orang',
                    'total_price' => 40000,
                    'ticket_total' => 40000,
                    'snack_total' => 0,
                    'payment_method' => 'di_tempat',
                    'status' => 'terkonfirmasi',
                ]
            );
            Booking::query()->updateOrCreate(
                ['code' => 'HC-RESELECT-B1'],
                [
                    'session_id' => $sTomorrow13->id,
                    'film_id' => $filmB->id,
                    'package' => 'reguler',
                    'customer_name' => 'Irfan Maulana',
                    'whatsapp' => '6281234567898',
                    'pax' => 1,
                    'people_count' => 1,
                    'seats_reserved' => 1,
                    'booking_type' => 'per_orang',
                    'total_price' => 20000,
                    'ticket_total' => 20000,
                    'snack_total' => 0,
                    'payment_method' => 'di_tempat',
                    'status' => 'terkonfirmasi',
                    'reselect_needed' => true,
                ]
            );
        }

        // 7. Besok 16:00: Paket Couple (2 orang, Pasti Tayang)
        $sTomorrow16 = Session::query()->where('session_date', $today->addDay()->toDateString())->where('start_time', '16:00')->first();
        if ($sTomorrow16) {
            $sTomorrow16->update([
                'film_id' => $filmC->id,
                'confirmed_at' => now(),
                'confirmation_method' => 'couple',
                'resolution_notes' => 'Pasti tayang: Paket Couple.',
            ]);
            Booking::query()->updateOrCreate(
                ['code' => 'HC-COUPLE-C1'],
                [
                    'session_id' => $sTomorrow16->id,
                    'film_id' => $filmC->id,
                    'package' => 'couple',
                    'customer_name' => 'Joko & Pasangan',
                    'whatsapp' => '6281234567899',
                    'pax' => 2,
                    'people_count' => 2,
                    'seats_reserved' => 2,
                    'booking_type' => 'per_orang',
                    'total_price' => 80000,
                    'ticket_total' => 80000,
                    'snack_total' => 0,
                    'payment_method' => 'qris',
                    'status' => 'terkonfirmasi',
                ]
            );
        }

        // 8. Besok 19:30: Paket Private (1-6 orang, seluruh studio, Pasti Tayang)
        $sTomorrow19 = Session::query()->where('session_date', $today->addDay()->toDateString())->where('start_time', '19:30')->first();
        if ($sTomorrow19) {
            $sTomorrow19->update([
                'film_id' => $filmD->id,
                'confirmed_at' => now(),
                'confirmation_method' => 'private',
                'is_private' => true,
                'resolution_notes' => 'Pasti tayang: Sewa Private Studio.',
            ]);
            Booking::query()->updateOrCreate(
                ['code' => 'HC-PRIVATE-D1'],
                [
                    'session_id' => $sTomorrow19->id,
                    'film_id' => $filmD->id,
                    'package' => 'private',
                    'customer_name' => 'Keluarga Kusuma',
                    'whatsapp' => '6281234567800',
                    'pax' => 5,
                    'people_count' => 5,
                    'seats_reserved' => 6,
                    'booking_type' => 'private',
                    'total_price' => 120000,
                    'ticket_total' => 120000,
                    'snack_total' => 0,
                    'payment_method' => 'transfer',
                    'status' => 'terkonfirmasi',
                ]
            );
        }
    }
}
