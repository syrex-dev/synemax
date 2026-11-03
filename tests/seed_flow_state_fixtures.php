<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$argvList = $argv ?? ($_SERVER['argv'] ?? []);
if (in_array('--env=qa', $argvList, true) || getenv('APP_ENV') === 'qa') {
    $app->loadEnvironmentFrom('.env.qa');
}

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$dbPath = config('database.connections.sqlite.database');
if (str_ends_with(str_replace('\\', '/', (string) $dbPath), 'database/database.sqlite') || app()->environment('production')) {
    fwrite(STDERR, "SAFETY ERROR: Refusing to seed audit bookings on owner database ($dbPath)!\n");
    exit(1);
}

use App\Models\Booking;
use App\Models\Film;
use App\Models\Session;
use App\Models\Setting;

Setting::updateOrCreate(['key' => 'seats_capacity'], ['value' => '6']);
Setting::updateOrCreate(['key' => 'quorum_min_seats'], ['value' => '3']);
Setting::updateOrCreate(['key' => 'minimum_public_viewers'], ['value' => '3']);
Setting::updateOrCreate(['key' => 'payment_time_limit_minutes'], ['value' => '30']);

$film = Film::query()->firstOrCreate(
    ['slug' => 'film-audit-flow'],
    [
        'title' => 'Detektif Cilik & Misteri Jam Gadang',
        'duration_minutes' => 105,
        'age_rating' => 'SU',
        'genre' => 'Petualangan Keluarga',
        'synopsis' => 'Kisah petualangan anak-anak memecahkan misteri di Bukittinggi.',
        'poster_path' => 'posters/contoh-detektif-cilik-misteri-jam-gadang.png',
        'is_active' => true,
    ]
);

// Sesi 1: Belum kuorum untuk menunggu_penonton (shortage = 2)
$sessionWaiting = Session::query()->updateOrCreate(
    ['session_date' => '2026-11-20', 'start_time' => '13:00:00'],
    [
        'film_id' => $film->id,
        'end_time' => '15:00:00',
        'capacity' => 6,
        'status' => 'tersedia',
        'confirmed_at' => null,
        'confirmation_method' => null,
    ]
);

// Sesi 1B: Sesi terpisah untuk booking lain yang belum lunas
$sessionOther = Session::query()->updateOrCreate(
    ['session_date' => '2026-11-21', 'start_time' => '13:00:00'],
    [
        'film_id' => $film->id,
        'end_time' => '15:00:00',
        'capacity' => 6,
        'status' => 'tersedia',
        'confirmed_at' => null,
        'confirmation_method' => null,
    ]
);

// Sesi 2: Sudah terkonfirmasi / pasti tayang
$sessionConfirmed = Session::query()->updateOrCreate(
    ['session_date' => '2026-11-20', 'start_time' => '16:00:00'],
    [
        'film_id' => $film->id,
        'end_time' => '18:00:00',
        'capacity' => 6,
        'status' => 'tersedia',
        'confirmed_at' => now(),
        'confirmation_method' => 'admin',
    ]
);

// Sesi 3: Sesi lampau untuk selesai/kedaluwarsa
$sessionPast = Session::query()->updateOrCreate(
    ['session_date' => '2026-11-01', 'start_time' => '10:00:00'],
    [
        'film_id' => $film->id,
        'end_time' => '12:00:00',
        'capacity' => 6,
        'status' => 'tersedia',
        'confirmed_at' => now()->subDays(2),
    ]
);

$fixtures = [
    'menunggu_bayar' => [
        'code' => 'FLOW-MENUNGGU-BAYAR',
        'session_id' => $sessionOther->id,
        'customer_name' => 'Aditya Pratama',
        'whatsapp' => '6281234567001',
        'package' => 'reguler',
        'booking_type' => 'per_orang',
        'pax' => 1,
        'seats_reserved' => 1,
        'payment_method' => 'transfer',
        'status' => 'menunggu_pembayaran',
        'ticket_total' => 25000,
        'snack_total' => 0,
        'total_price' => 25000,
        'payment_expires_at' => now()->addMinutes(25),
        'payment_proof_path' => null,
    ],
    'diproses' => [
        'code' => 'FLOW-DIPROSES',
        'session_id' => $sessionOther->id,
        'customer_name' => 'Citra Lestari',
        'whatsapp' => '6281234567002',
        'package' => 'reguler',
        'booking_type' => 'per_orang',
        'pax' => 1,
        'seats_reserved' => 1,
        'payment_method' => 'transfer',
        'status' => 'menunggu_pembayaran',
        'ticket_total' => 25000,
        'snack_total' => 15000,
        'total_price' => 40000,
        'payment_expires_at' => now()->addMinutes(20),
        'payment_proof_path' => 'proofs/bukti-transfer-test.png',
        'payment_proof_uploaded_at' => now(),
    ],
    'menunggu_penonton' => [
        'code' => 'FLOW-MENUNGGU-PENONTON',
        'session_id' => $sessionWaiting->id,
        'customer_name' => 'Dewi Anggraini',
        'whatsapp' => '6281234567003',
        'package' => 'reguler',
        'booking_type' => 'per_orang',
        'pax' => 1,
        'seats_reserved' => 1,
        'payment_method' => 'qris',
        'status' => 'terkonfirmasi',
        'ticket_total' => 25000,
        'snack_total' => 0,
        'total_price' => 25000,
        'confirmed_at' => now(),
        'payment_expires_at' => null,
    ],
    'aktif' => [
        'code' => 'FLOW-AKTIF',
        'session_id' => $sessionConfirmed->id,
        'customer_name' => 'Fajar Nugraha',
        'whatsapp' => '6281234567004',
        'package' => 'private',
        'booking_type' => 'private',
        'pax' => 6,
        'seats_reserved' => 6,
        'payment_method' => 'qris',
        'status' => 'terkonfirmasi',
        'ticket_total' => 150000,
        'snack_total' => 30000,
        'total_price' => 180000,
        'confirmed_at' => now(),
    ],
    'kedaluwarsa' => [
        'code' => 'FLOW-KEDALUWARSA',
        'session_id' => $sessionPast->id,
        'customer_name' => 'Gita Gutawa',
        'whatsapp' => '6281234567005',
        'package' => 'reguler',
        'booking_type' => 'per_orang',
        'pax' => 1,
        'seats_reserved' => 1,
        'payment_method' => 'transfer',
        'status' => 'kedaluwarsa',
        'ticket_total' => 25000,
        'snack_total' => 0,
        'total_price' => 25000,
        'payment_expires_at' => now()->subHours(2),
    ],
    'dibatalkan' => [
        'code' => 'FLOW-DIBATALKAN',
        'session_id' => $sessionOther->id,
        'customer_name' => 'Hendra Setiawan',
        'whatsapp' => '6281234567006',
        'package' => 'reguler',
        'booking_type' => 'per_orang',
        'pax' => 1,
        'seats_reserved' => 1,
        'payment_method' => 'qris',
        'status' => 'dibatalkan',
        'ticket_total' => 25000,
        'snack_total' => 0,
        'total_price' => 25000,
        'cancelled_at' => now(),
    ],
    'selesai' => [
        'code' => 'FLOW-SELESAI',
        'session_id' => $sessionPast->id,
        'customer_name' => 'Indah Permata',
        'whatsapp' => '6281234567007',
        'package' => 'couple',
        'booking_type' => 'couple',
        'pax' => 2,
        'seats_reserved' => 2,
        'payment_method' => 'qris',
        'status' => 'selesai',
        'ticket_total' => 65000,
        'snack_total' => 20000,
        'total_price' => 85000,
        'attended_at' => now()->subDay(),
    ],
];

$output = [];
foreach ($fixtures as $state => $data) {
    $data['film_id'] = $film->id;
    $b = Booking::query()->updateOrCreate(['code' => $data['code']], $data);
    $output[$state] = [
        'code' => $b->code,
        'flowState' => $b->flowState(),
        'ticket_url' => "/tiket/{$b->code}",
        'payment_url' => "/pembayaran/{$b->code}",
    ];
}

file_put_contents(__DIR__.'/flow_state_fixtures.json', json_encode($output, JSON_PRETTY_PRINT));
echo 'SEEDED '.count($output)." FLOW FIXTURES TO QA DATABASE:\n";
foreach ($output as $state => $info) {
    echo "  - [{$state}] code={$info['code']} resolved_flowState={$info['flowState']}\n";
}
