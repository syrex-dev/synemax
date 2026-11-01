<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Booking;
use App\Models\Film;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Session;
use Illuminate\Contracts\Console\Kernel;

$film = Film::query()->firstOrCreate(
    ['slug' => 'film-audit-pdf'],
    [
        'title' => 'Film Uji Audit PDF',
        'duration_minutes' => 115,
        'age_rating' => '13+',
        'genre' => 'Petualangan',
        'synopsis' => 'Sinopsis film untuk pengujian audit tiket PDF 1 halaman.',
        'is_active' => true,
    ]
);

$session = Session::query()->firstOrCreate(
    ['session_date' => '2026-11-15', 'start_time' => '19:00:00'],
    [
        'film_id' => $film->id,
        'end_time' => '21:00:00',
        'capacity' => 6,
        'status' => 'tersedia',
        'confirmed_at' => now(),
        'confirmation_method' => 'admin',
    ]
);

$category = MenuCategory::query()->where('name', 'Menu Audit PDF')->orWhere('slug', 'menu-audit-pdf')->first()
    ?: MenuCategory::create([
        'name' => 'Menu Audit PDF',
        'slug' => 'menu-audit-pdf',
        'is_active' => true,
        'sort_order' => 1,
    ]);

// Ensure 12 menu items
$menuItems = [];
for ($i = 1; $i <= 12; $i++) {
    $menuItems[] = MenuItem::query()->where('slug', "menu-audit-pdf-{$i}")->first()
        ?: MenuItem::create([
            'category_id' => $category->id,
            'name' => "Snack Spesial {$i}",
            'slug' => "menu-audit-pdf-{$i}",
            'price' => 15000,
            'cost_price' => 7000,
            'stock_mode' => 'unlimited',
            'is_active' => true,
            'sort_order' => $i,
        ]);
}

$scenarios = [
    ['code' => 'TKT-AUDIT-0SNACK', 'snack_count' => 0],
    ['code' => 'TKT-AUDIT-3SNACK', 'snack_count' => 3],
    ['code' => 'TKT-AUDIT-6SNACK', 'snack_count' => 6],
    ['code' => 'TKT-AUDIT-12SNACK', 'snack_count' => 12],
];

$results = [];

foreach ($scenarios as $s) {
    $booking = Booking::query()->updateOrCreate(
        ['code' => $s['code']],
        [
            'session_id' => $session->id,
            'film_id' => $film->id,
            'customer_name' => 'Peserta Uji '.$s['snack_count'].' Jajan',
            'whatsapp' => '6281234567899',
            'seats_reserved' => 2,
            'pax' => 2,
            'ticket_total' => 50000,
            'snack_total' => $s['snack_count'] * 15000,
            'total_price' => 50000 + ($s['snack_count'] * 15000),
            'status' => 'terkonfirmasi',
            'payment_method' => 'di_tempat',
            'notes' => 'Catatan uji booking '.$s['snack_count'].' item',
        ]
    );

    // Sync items
    $booking->items()->delete();
    for ($j = 0; $j < $s['snack_count']; $j++) {
        $mi = $menuItems[$j];
        $booking->items()->create([
            'menu_item_id' => $mi->id,
            'name_snapshot' => $mi->name,
            'price_snapshot' => $mi->price,
            'cost_snapshot' => $mi->cost_price,
            'qty' => 1,
            'note' => 'Catatan item '.($j + 1),
            'status' => 'baru',
        ]);
    }

    $results[] = [
        'id' => $booking->id,
        'code' => $booking->code,
        'snack_count' => $s['snack_count'],
        'ticket_url' => "http://synemax.test/admin/booking/{$booking->id}/tiket",
    ];
}

// Also seed a pending COD booking to test "Bayar di tempat" live
$pendingBooking = Booking::query()->updateOrCreate(
    ['code' => 'TKT-AUDIT-PENDING-COD'],
    [
        'session_id' => $session->id,
        'film_id' => $film->id,
        'customer_name' => 'Peserta Uji COD Menunggu',
        'whatsapp' => '6281234567898',
        'seats_reserved' => 2,
        'pax' => 2,
        'ticket_total' => 50000,
        'snack_total' => 0,
        'total_price' => 50000,
        'status' => 'menunggu_konfirmasi',
        'payment_method' => 'di_tempat',
        'notes' => 'Catatan uji bayar di tempat',
    ]
);

$fixturesData = [
    'scenarios' => $results,
    'pending_cod' => [
        'id' => $pendingBooking->id,
        'code' => $pendingBooking->code,
        'ticket_url' => "http://synemax.test/admin/booking/{$pendingBooking->id}/tiket",
    ],
];

file_put_contents(__DIR__.'/ticket_audit_fixtures.json', json_encode($fixturesData, JSON_PRETTY_PRINT));
echo json_encode($fixturesData, JSON_PRETTY_PRINT);
