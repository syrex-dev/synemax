<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Film;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\ScheduleTemplate;
use App\Models\Session;
use App\Models\Setting;
use App\Models\SnackOrderEdit;
use App\Models\User;
use Illuminate\Database\Seeder;
use InvalidArgumentException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedScheduleTemplates();
        $this->seedSettings();
        $this->seedFilms();
        $this->seedSnackMenu();
        $this->seedAdmin();
        $this->seedSampleBooking();
        $this->call(NobarAndNewsSeeder::class);
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
        $settings = [
            'weekday_price' => 5000,
            'weekend_price' => 6000,
            'holiday_price' => 6000,
            'private_price' => 36000,
            'minimum_public_viewers' => 3,
            'booking_cutoff_minutes' => 30,
            'cancellation_cutoff_hours' => 6,
            'admin_whatsapp' => config('cinema.admin_whatsapp', ''),
            'payment_instructions' => '',
            'enabled_payment_methods' => json_encode(['di_tempat'], JSON_THROW_ON_ERROR),
            'electricity_cost_per_session' => 0,
            'snacks_enabled' => 1,
            'snack_prep_buffer_minutes' => 15,
            'snack_max_qty_per_item' => 10,
        ];

        foreach ($settings as $key => $value) {
            $attributes = ['value' => (string) $value];

            if (in_array($key, ['snacks_enabled', 'snack_prep_buffer_minutes', 'snack_max_qty_per_item'], true)) {
                Setting::firstOrCreate(['key' => $key], $attributes);
            } else {
                Setting::updateOrCreate(['key' => $key], $attributes);
            }
        }
    }

    private function seedFilms(): void
    {
        $films = [
            [
                'title' => 'Malam di Bukit Bintang',
                'duration_minutes' => 105,
                'genre' => 'Drama keluarga',
                'age_rating' => 'SU',
                'synopsis' => 'Kisah keluarga kecil yang menemukan kembali kebersamaan di sebuah desa pegunungan.',
            ],
            [
                'title' => 'Misi Terakhir Pak Pos',
                'duration_minutes' => 98,
                'genre' => 'Komedi petualangan',
                'age_rating' => '13+',
                'synopsis' => 'Pak pos dan cucunya berkejaran dengan waktu untuk mengantar surat penting.',
            ],
            [
                'title' => 'Rahasia Rumah Tua',
                'duration_minutes' => 112,
                'genre' => 'Misteri',
                'age_rating' => '17+',
                'synopsis' => 'Tiga sahabat menyelidiki cerita lama yang tersimpan di rumah kosong dekat sawah.',
            ],
        ];

        foreach ($films as $film) {
            $model = Film::withTrashed()->updateOrCreate(
                ['title' => $film['title']],
                [
                    ...$film,
                    'license_notes' => 'Judul dan sinopsis ini hanya data demo. Pastikan hak pemutaran film tersedia sebelum digunakan.',
                    'is_active' => true,
                ],
            );

            if ($model->trashed()) {
                $model->restore();
            }
        }
    }

    private function seedAdmin(): void
    {
        $email = (string) env('ADMIN_EMAIL');
        $password = (string) env('ADMIN_PASSWORD');

        if ($email === '' || strlen($password) < 12) {
            throw new InvalidArgumentException(
                'Isi ADMIN_EMAIL dan ADMIN_PASSWORD (minimal 12 karakter) di .env sebelum menjalankan db:seed.',
            );
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin',
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
        $sessionDate = now(config('app.timezone'))->addDay()->toDateString();
        $film = Film::query()->where('title', 'Malam di Bukit Bintang')->firstOrFail();
        $session = Session::query()->updateOrCreate(
            ['session_date' => $sessionDate, 'start_time' => '16:00'],
            [
                'end_time' => '18:30',
                'film_id' => $film->id,
                'capacity' => 6,
                'is_private' => false,
                'status' => 'tersedia',
            ],
        );
        $water = MenuItem::query()->where('name', 'Air mineral')->firstOrFail();
        $noodles = MenuItem::query()->where('name', 'Mie rebus')->firstOrFail();
        $admin = User::query()->where('is_admin', true)->orderBy('id')->firstOrFail();
        $bookingCode = 'HC-'.now(config('app.timezone'))->format('ymd').'-DEMO1';
        $booking = Booking::query()->updateOrCreate(
            ['code' => $bookingCode],
            [
                'session_id' => $session->id,
                'film_id' => $film->id,
                'customer_name' => 'Pelanggan Contoh',
                'whatsapp' => '6281234567890',
                'pax' => 2,
                'seats_reserved' => 2,
                'booking_type' => 'per_orang',
                'total_price' => 24000,
                'ticket_total' => 10000,
                'snack_total' => 14000,
                'payment_method' => 'di_tempat',
                'status' => 'terkonfirmasi',
            ],
        );
        $waterDetails = [
            'menu_item_id' => $water->id,
            'name_snapshot' => 'Air mineral',
            'price_snapshot' => 3000,
            'qty' => 2,
            'note' => 'Tanpa es',
        ];
        $noodlesDetails = [
            'menu_item_id' => $noodles->id,
            'name_snapshot' => 'Mie rebus',
            'price_snapshot' => 8000,
            'qty' => 1,
            'note' => 'Tidak pedas',
        ];

        BookingItem::query()->updateOrCreate(
            ['booking_id' => $booking->id, 'menu_item_id' => $water->id],
            [...$waterDetails, 'cost_snapshot' => null, 'status' => 'baru'],
        );
        BookingItem::query()->updateOrCreate(
            ['booking_id' => $booking->id, 'menu_item_id' => $noodles->id],
            [...$noodlesDetails, 'cost_snapshot' => null, 'status' => 'baru'],
        );

        SnackOrderEdit::query()->updateOrCreate(
            ['booking_id' => $booking->id, 'summary' => 'Pesanan contoh dibuat.'],
            [
                'actor_id' => $admin->id,
                'before_details' => [],
                'after_details' => [$waterDetails],
            ],
        );
        SnackOrderEdit::query()->updateOrCreate(
            ['booking_id' => $booking->id, 'summary' => 'Mie rebus ditambahkan.'],
            [
                'actor_id' => $admin->id,
                'before_details' => [$waterDetails],
                'after_details' => [$waterDetails, $noodlesDetails],
            ],
        );
    }
}
