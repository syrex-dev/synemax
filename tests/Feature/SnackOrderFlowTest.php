<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Film;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Session;
use App\Models\Setting;
use App\Models\SnackOrderEdit;
use App\Models\User;
use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SnackOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private Film $film;

    private MenuItem $water;

    private MenuItem $noodle;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-14 09:00:00', 'Asia/Jakarta'));
        $this->film = Film::query()->create([
            'title' => 'Film jajan uji',
            'duration_minutes' => 100,
            'genre' => 'Komedi',
            'age_rating' => 'SU',
            'synopsis' => 'Film untuk pengujian pesanan jajan.',
            'is_active' => true,
        ]);
        $category = MenuCategory::query()->create([
            'name' => 'Minuman Dingin',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $this->water = $this->createMenuItem($category, 'Air mineral', 3000, 1);
        $this->noodle = $this->createMenuItem($category, 'Mie rebus', 8000, 15);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_booking_total_includes_snack_items_and_keeps_price_snapshots(): void
    {
        $session = $this->createSession();

        $this->post(route('booking.store', $session), [
            ...$this->bookingData(),
            'snacks' => [$this->water->id => ['qty' => 2, 'note' => 'Tanpa es']],
        ])->assertRedirect();

        $booking = Booking::query()->with('items')->firstOrFail();
        $item = $booking->items->firstOrFail();

        $this->assertSame(5000, $booking->ticket_total);
        $this->assertSame(6000, $booking->snack_total);
        $this->assertSame(11000, $booking->total_price);
        $this->assertSame('Air mineral', $item->name_snapshot);
        $this->assertSame(3000, $item->price_snapshot);
        $this->assertSame('Tanpa es', $item->note);

        $this->water->update(['name' => 'Air mineral baru', 'price' => 4000, 'cost_price' => 1000]);

        $this->assertSame('Air mineral', $item->fresh()->name_snapshot);
        $this->assertSame(3000, $item->fresh()->price_snapshot);
        $this->assertSame(11000, $booking->fresh()->total_price);
        $this->followingRedirects()
            ->get(route('booking.success', $booking->code))
            ->assertOk()
            ->assertSee('Air mineral × 2')
            ->assertSee('Rp 11.000');
    }

    public function test_public_snack_update_endpoint_is_removed_without_changing_the_order(): void
    {
        $session = $this->createSession();
        $booking = app(BookingService::class)->create($session, [
            ...$this->bookingData(),
            'whatsapp' => '6281234567890',
        ]);

        $this->post('/cek-booking/'.$booking->code.'/jajan', [
            'whatsapp' => '081234567890',
            'snacks' => [$this->water->id => ['qty' => 2]],
        ])->assertNotFound();

        $this->assertDatabaseCount('booking_items', 0);
        $this->assertSame(0, $booking->fresh()->snack_total);
        $this->assertSame(5000, $booking->fresh()->total_price);
    }

    public function test_order_deadline_uses_the_longest_preparation_time_and_locks_changes_afterward(): void
    {
        Setting::query()->updateOrCreate(['key' => 'booking_cutoff_minutes'], ['value' => '0']);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-14 15:35:00', 'Asia/Jakarta'));
        $session = $this->createSession();

        $this->from(route('booking.create', $session))
            ->post(route('booking.store', $session), [
                ...$this->bookingData(),
                'whatsapp' => '081111111111',
                'snacks' => [$this->noodle->id => ['qty' => 1]],
            ])
            ->assertSessionHasErrors('snacks');

        $this->post(route('booking.store', $session), [
            ...$this->bookingData(),
            'whatsapp' => '082222222222',
            'snacks' => [$this->water->id => ['qty' => 1]],
        ])->assertRedirect();

        $booking = Booking::query()->latest('id')->firstOrFail();
        $this->assertSame(3000, $booking->snack_total);
        $this->assertDatabaseCount('bookings', 1);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-14 15:44:00', 'Asia/Jakarta'));
        $this->from(route('booking.create', $session))
            ->post(route('booking.store', $session), [
                ...$this->bookingData(),
                'whatsapp' => '083333333333',
                'snacks' => [$this->water->id => ['qty' => 1]],
            ])
            ->assertSessionHasErrors('snacks');

        $this->assertDatabaseCount('bookings', 1);
        $this->assertSame(3000, $booking->fresh()->snack_total);
        $this->assertDatabaseCount('snack_order_edits', 0);
    }

    public function test_legacy_delivered_items_are_backfilled_as_completed(): void
    {
        $session = $this->createSession();
        $booking = app(BookingService::class)->create($session, [
            ...$this->bookingData(),
            'whatsapp' => '6281234567890',
        ]);
        $item = $booking->items()->create([
            'menu_item_id' => $this->water->id,
            'name_snapshot' => 'Air mineral',
            'price_snapshot' => 3000,
            'qty' => 1,
            'status' => 'diantar',
        ]);

        $migration = require database_path('migrations/2026_10_14_000002_replace_diantar_snack_status_with_selesai.php');
        $migration->up();

        $this->assertSame('selesai', $item->fresh()->status);
    }

    public function test_admin_snack_edits_update_totals_and_record_meaningful_audit_details(): void
    {
        $session = $this->createSession();
        $booking = app(BookingService::class)->create($session, [
            ...$this->bookingData(),
            'whatsapp' => '6281234567890',
            'snacks' => [$this->water->id => ['qty' => 2, 'note' => 'Tanpa es']],
        ]);
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $payload = [
            'snacks' => [
                $this->water->id => ['qty' => 3, 'note' => 'Sedikit es'],
                $this->noodle->id => ['qty' => 1, 'note' => 'Pedas'],
            ],
        ];
        $editResponse = $this->patch(route('admin.bookings.snacks.update', $booking), $payload)
            ->assertRedirect();
        $editResponse->assertSessionHas('snackUpdateWhatsAppUrl', function (?string $url): bool {
            if ($url === null || parse_url($url, PHP_URL_HOST) !== 'wa.me' || parse_url($url, PHP_URL_PATH) !== '/6281234567890') {
                return false;
            }

            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            return str_contains($query['text'] ?? '', 'Air mineral × 3 (Sedikit es)')
                && str_contains($query['text'] ?? '', 'Total jajan: Rp 17.000');
        });

        $booking->refresh();
        $this->assertSame(5000, $booking->ticket_total);
        $this->assertSame(17000, $booking->snack_total);
        $this->assertSame(22000, $booking->total_price);
        $this->assertDatabaseCount('snack_order_edits', 1);

        $audit = SnackOrderEdit::query()->firstOrFail();
        $this->assertSame($admin->id, $audit->actor_id);
        $this->assertSame('Pesanan jajan diperbarui: Air mineral: 2× → 3×; Mie rebus: 0× → 1×', $audit->summary);
        $this->assertSame([
            [
                'menu_item_id' => $this->water->id,
                'name_snapshot' => 'Air mineral',
                'price_snapshot' => 3000,
                'qty' => 2,
                'note' => 'Tanpa es',
            ],
        ], $audit->before_details);
        $this->assertSame([
            [
                'menu_item_id' => $this->water->id,
                'name_snapshot' => 'Air mineral',
                'price_snapshot' => 3000,
                'qty' => 3,
                'note' => 'Sedikit es',
            ],
            [
                'menu_item_id' => $this->noodle->id,
                'name_snapshot' => 'Mie rebus',
                'price_snapshot' => 8000,
                'qty' => 1,
                'note' => 'Pedas',
            ],
        ], $audit->after_details);
        $this->assertTrue($audit->created_at->equalTo(now()));

        $this->patch(route('admin.bookings.snacks.update', $booking), $payload)->assertRedirect();
        $this->assertDatabaseCount('snack_order_edits', 1);

        $response = $this->get(route('admin.bookings.show', $booking))->assertOk();
        $response->assertViewHas('snackChangeLogs', function ($history) use ($audit, $admin): bool {
            $entry = $history->first();

            return $history->count() === 1
                && $entry->is($audit)
                && $entry->actor->is($admin);
        });
        $response->assertViewHas('booking', function (Booking $showBooking) use ($audit): bool {
            return $showBooking->relationLoaded('snackChangeLogs')
                && $showBooking->snackChangeLogs->first()->is($audit);
        });
        $response->assertViewHas('snackUpdateWhatsAppUrl', function (?string $url): bool {
            if ($url === null || parse_url($url, PHP_URL_HOST) !== 'wa.me' || parse_url($url, PHP_URL_PATH) !== '/6281234567890') {
                return false;
            }

            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            return str_contains($query['text'] ?? '', 'Air mineral × 3 (Sedikit es)')
                && str_contains($query['text'] ?? '', 'Mie rebus × 1 (Pedas)')
                && str_contains($query['text'] ?? '', 'Total jajan: Rp 17.000')
                && str_contains($query['text'] ?? '', 'Total booking: Rp 22.000');
        });
    }

    public function test_admin_can_remove_a_snack_item_and_audit_the_change(): void
    {
        $session = $this->createSession();
        $booking = app(BookingService::class)->create($session, [
            ...$this->bookingData(),
            'whatsapp' => '6281234567890',
            'snacks' => [$this->water->id => ['qty' => 1]],
        ]);
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.bookings.snacks.update', $booking), [])
            ->assertRedirect();

        $this->assertSame(5000, $booking->fresh()->total_price);
        $this->assertSame(0, $booking->fresh()->snack_total);
        $this->assertSame('dibatalkan', $booking->items()->firstOrFail()->status);
        $this->assertDatabaseHas('snack_order_edits', [
            'booking_id' => $booking->id,
            'actor_id' => $admin->id,
            'summary' => 'Pesanan jajan diperbarui: Air mineral: 1× → 0×',
        ]);
    }

    public function test_cancelling_booking_cancels_its_snack_items(): void
    {
        $session = $this->createSession();
        $booking = app(BookingService::class)->create($session, [
            ...$this->bookingData(),
            'whatsapp' => '6281234567890',
            'snacks' => [$this->water->id => ['qty' => 2]],
        ]);

        $this->post(route('booking.cancel', $booking->code), ['whatsapp' => '081234567890'])
            ->assertRedirect(route('booking.lookup.form'));

        $this->assertSame('dibatalkan', $booking->fresh()->status);
        $this->assertSame('dibatalkan', $booking->items()->firstOrFail()->status);
        $this->assertSame(0, (int) $session->fresh()->occupiedBookings()->sum('seats_reserved'));

        $this->followingRedirects()->post(route('booking.lookup'), [
            'code' => $booking->code,
            'whatsapp' => '081234567890',
        ])->assertOk()
            ->assertViewHas('booking', function (Booking $statusBooking): bool {
                return $statusBooking->items->count() === 1
                    && $statusBooking->items->first()->status === 'dibatalkan';
            });
    }

    public function test_public_booking_status_exposes_a_read_only_snack_change_request_url(): void
    {
        Setting::query()->create(['key' => 'admin_whatsapp', 'value' => '+62 812-3456-7890']);
        $session = $this->createSession();
        $booking = app(BookingService::class)->create($session, [
            ...$this->bookingData(),
            'whatsapp' => '6281234567890',
            'snacks' => [$this->water->id => ['qty' => 2, 'note' => 'Tanpa es']],
        ]);
        BookingItem::query()->create([
            'booking_id' => $booking->id,
            'menu_item_id' => $this->noodle->id,
            'name_snapshot' => 'Mie rebus',
            'price_snapshot' => 8000,
            'qty' => 1,
            'status' => 'dibatalkan',
        ]);

        $response = $this->followingRedirects()->post(route('booking.lookup'), [
            'code' => $booking->code,
            'whatsapp' => '081234567890',
        ])->assertOk();

        $response->assertViewHas('snackWhatsAppUrl', function (?string $url) use ($booking): bool {
            if ($url === null || parse_url($url, PHP_URL_HOST) !== 'wa.me' || parse_url($url, PHP_URL_PATH) !== '/6281234567890') {
                return false;
            }

            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $message = $query['text'] ?? '';

            return str_contains($message, 'Jadwal: 2026-10-14 · 16:00 WIB')
                && str_contains($message, 'Saya ingin mengubah pesanan jajan:')
                && str_contains($message, 'Kode booking: '.$booking->code)
                && str_contains($message, 'Air mineral × 2 (Tanpa es)');
        });
        $response->assertViewHas('whatsAppUrl');
        $response->assertViewHas('snackLocked', false);
        $response->assertViewHas('booking', function (Booking $statusBooking): bool {
            return $statusBooking->items->count() === 1
                && $statusBooking->items->first()->status !== 'dibatalkan';
        });
        $this->assertSame(6000, $booking->fresh()->snack_total);
        $this->assertDatabaseCount('snack_order_edits', 0);
    }

    public function test_numeric_stock_is_deducted_on_confirmation_restored_on_cancel_and_not_oversold(): void
    {
        $this->water->update(['stock_mode' => 'angka', 'stock_qty' => 1]);
        $session = $this->createSession();
        $first = $this->createPendingBooking($session, '6281111111111');
        $second = $this->createPendingBooking($session, '6282222222222');
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.bookings.status', $first), ['action' => 'konfirmasi'])
            ->assertRedirect();
        $this->assertSame(0, $this->water->fresh()->stock_qty);

        $this->from(route('admin.bookings.index'))
            ->actingAs($admin)
            ->patch(route('admin.bookings.status', $second), ['action' => 'konfirmasi'])
            ->assertSessionHasErrors('status');
        $this->assertSame('menunggu_konfirmasi', $second->fresh()->status);
        $this->assertSame(0, $this->water->fresh()->stock_qty);

        $this->patch(route('admin.bookings.status', $first), ['action' => 'batal'])->assertRedirect();
        $this->assertSame(1, $this->water->fresh()->stock_qty);

        $this->patch(route('admin.bookings.status', $second), ['action' => 'konfirmasi'])->assertRedirect();
        $this->assertSame(0, $this->water->fresh()->stock_qty);
        $this->assertSame(1, (int) BookingItem::query()
            ->where('booking_id', $second->id)
            ->value('stock_deducted_qty'));
    }

    public function test_public_booking_and_admin_preparation_views_show_order_snapshots(): void
    {
        $session = $this->createSession();
        $this->get(route('booking.create', $session))
            ->assertOk()
            ->assertSee('Mau pesan jajan?')
            ->assertSee('Air mineral');

        $booking = app(BookingService::class)->create($session, [
            ...$this->bookingData(),
            'whatsapp' => '6281234567890',
            'snacks' => [$this->noodle->id => ['qty' => 2, 'note' => 'Tidak pedas']],
        ]);

        $this->followingRedirects()->post(route('booking.lookup'), [
            'code' => $booking->code,
            'whatsapp' => '081234567890',
        ])->assertOk()
            ->assertSee('Mie rebus × 2')
            ->assertSee('Tidak pedas');

        $this->actingAs(User::factory()->create());
        $this->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Mie rebus × 2');
        $this->get(route('admin.snacks.preparation.index'))->assertOk();
        $this->get(route('admin.snacks.preparation.show', $session))
            ->assertOk()
            ->assertSee('2×')
            ->assertSee('Mie rebus')
            ->assertSee('Tidak pedas');

        $item = $booking->items()->firstOrFail();
        $this->patch(route('admin.snacks.items.status', $item), ['status' => 'disiapkan'])
            ->assertRedirect();
        $this->assertSame('disiapkan', $item->fresh()->status);

        $this->patch(route('admin.snacks.preparation.status', $session), ['status' => 'siap'])
            ->assertRedirect();
        $this->assertSame('siap', $item->fresh()->status);

        $this->from(route('admin.bookings.show', $booking))
            ->patch(route('admin.snacks.items.status', $item), ['status' => 'diantar'])
            ->assertSessionHasErrors('status');
        $this->assertSame('siap', $item->fresh()->status);

        $this->patch(route('admin.snacks.items.status', $item), ['status' => 'selesai'])
            ->assertRedirect();
        $this->assertSame('selesai', $item->fresh()->status);

        $this->patch(route('admin.bookings.snacks.status', $booking), ['status' => 'selesai'])
            ->assertRedirect();
        $this->assertSame('selesai', $item->fresh()->status);

        $this->patch(route('admin.snacks.preparation.status', $session), ['status' => 'selesai'])
            ->assertRedirect();
        $this->assertSame('selesai', $item->fresh()->status);
    }

    public function test_admin_can_create_and_toggle_menu_items_and_reports_split_snack_revenue(): void
    {
        $this->actingAs(User::factory()->create());
        $category = $this->water->category;
        $newItem = [
            'category_id' => $category->id,
            'name' => 'Sosis goreng',
            'price' => 6000,
            'cost_price' => 3000,
            'prep_minutes' => 8,
            'stock_mode' => 'angka',
            'stock_qty' => 4,
            'stock_alert_threshold' => 2,
            'is_available' => 1,
            'is_active' => 1,
            'sort_order' => 5,
        ];

        $this->get(route('admin.snacks.index'))->assertOk()->assertSee('Menu jajanan');
        $this->post(route('admin.snacks.store'), $newItem)
            ->assertRedirect(route('admin.snacks.index'));
        $created = MenuItem::query()->where('name', 'Sosis goreng')->firstOrFail();
        $this->assertSame(4, $created->stock_qty);

        $this->patch(route('admin.snacks.availability', $created))->assertRedirect();
        $this->assertFalse($created->fresh()->is_available);
        $this->patch(route('admin.snacks.availability', $created))->assertRedirect();
        $this->assertTrue($created->fresh()->is_available);

        $session = $this->createSession();
        $booking = app(BookingService::class)->create($session, [
            ...$this->bookingData(),
            'whatsapp' => '6281234567890',
            'snacks' => [$created->id => ['qty' => 2]],
        ]);
        $booking->update(['status' => 'terkonfirmasi']);

        $this->get(route('admin.reports.index', ['date' => '2026-10-14']))
            ->assertOk()
            ->assertSee('Pendapatan jajan')
            ->assertSee('Jajan paling laris')
            ->assertSee('Rp 12.000');
        $export = $this->get(route('admin.reports.export', ['date' => '2026-10-14']))->assertOk();
        $this->assertStringContainsString('Pendapatan jajan (Rp)', $export->streamedContent());
        $this->assertStringContainsString('Sosis goreng x2', $export->streamedContent());
    }

    public function test_menu_photo_is_converted_to_a_small_webp_image(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        $this->post(route('admin.snacks.store'), [
            'category_id' => $this->water->category_id,
            'name' => 'Es cekek foto',
            'price' => 3000,
            'prep_minutes' => 3,
            'stock_mode' => 'toggle',
            'stock_alert_threshold' => 3,
            'is_available' => 1,
            'is_active' => 1,
            'sort_order' => 1,
            'photo' => UploadedFile::fake()->image('drink.png', 800, 1200),
        ])->assertRedirect(route('admin.snacks.index'));

        $item = MenuItem::query()->where('name', 'Es cekek foto')->firstOrFail();
        Storage::disk('public')->assertExists($item->photo_path);
        $image = imagecreatefromstring(Storage::disk('public')->get($item->photo_path));

        $this->assertSame('webp', pathinfo($item->photo_path, PATHINFO_EXTENSION));
        $this->assertLessThanOrEqual(500, imagesx($image));
        imagedestroy($image);
    }

    private function createPendingBooking(Session $session, string $whatsapp): Booking
    {
        return app(BookingService::class)->create($session, [
            ...$this->bookingData(),
            'whatsapp' => $whatsapp,
            'snacks' => [$this->water->id => ['qty' => 1]],
        ]);
    }

    private function createMenuItem(MenuCategory $category, string $name, int $price, int $prepMinutes): MenuItem
    {
        return MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => $name,
            'price' => $price,
            'prep_minutes' => $prepMinutes,
            'stock_mode' => 'toggle',
            'is_available' => true,
            'is_active' => true,
        ]);
    }

    private function createSession(): Session
    {
        return Session::query()->create([
            'session_date' => '2026-10-14',
            'start_time' => '16:00',
            'end_time' => '18:30',
            'film_id' => $this->film->id,
            'capacity' => 6,
            'status' => 'tersedia',
        ]);
    }

    private function bookingData(): array
    {
        return [
            'customer_name' => 'Pemesan Jajan',
            'whatsapp' => '081234567890',
            'pax' => 1,
            'booking_type' => 'per_orang',
            'film_id' => $this->film->id,
            'payment_method' => 'di_tempat',
        ];
    }
}
