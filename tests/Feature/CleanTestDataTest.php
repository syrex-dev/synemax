<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingCheckin;
use App\Models\BookingItem;
use App\Models\Film;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Session;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanTestDataTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-13 10:00:00', 'Asia/Jakarta'));
        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_clean_test_data_command_deletes_all_bookings_and_resets_financial_reports(): void
    {
        $film = Film::query()->create([
            'title' => 'Film Uji',
            'genre' => 'Aksi',
            'duration_minutes' => 90,
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis film.',
            'is_active' => true,
        ]);

        $session = Session::query()->create([
            'session_date' => '2026-10-13',
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'capacity' => 6,
            'status' => 'tersedia',
        ]);

        $category = MenuCategory::query()->create(['name' => 'Snack', 'sort_order' => 1]);
        $menuItem = MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Popcorn Manis',
            'price' => 5000,
            'is_available' => true,
        ]);

        // Create test booking with items and checkin
        $booking = Booking::query()->create([
            'code' => 'HC-TEST-0001',
            'session_id' => $session->id,
            'film_id' => $film->id,
            'customer_name' => 'Tester',
            'whatsapp' => '6281234567890',
            'pax' => 2,
            'seats_reserved' => 2,
            'booking_type' => 'per_orang',
            'package' => 'reguler',
            'people_count' => 2,
            'total_price' => 15000,
            'ticket_total' => 10000,
            'snack_total' => 5000,
            'payment_method' => 'di_tempat',
            'status' => 'terkonfirmasi',
        ]);

        BookingItem::query()->create([
            'booking_id' => $booking->id,
            'menu_item_id' => $menuItem->id,
            'name_snapshot' => 'Popcorn Manis',
            'price_snapshot' => 5000,
            'qty' => 1,
            'status' => 'baru',
        ]);

        BookingCheckin::query()->create([
            'booking_id' => $booking->id,
            'user_id' => $this->admin->id,
            'method' => 'pos',
            'result' => 'success',
            'created_at' => now(),
        ]);

        $this->assertSame(1, Booking::count());
        $this->assertSame(1, BookingItem::count());
        $this->assertSame(1, BookingCheckin::count());

        // Run artisan command
        $this->artisan('bookings:clean-test-data', ['--force' => true])
            ->assertSuccessful();

        $this->assertSame(0, Booking::count());
        $this->assertSame(0, BookingItem::count());
        $this->assertSame(0, BookingCheckin::count());

        // Report should now have 0 revenue
        $reportResponse = $this->get(route('admin.reports.index', ['date' => '2026-10-13']));
        $reportResponse->assertOk();
        $reportResponse->assertSee('Rp 0');
    }

    public function test_clean_test_data_web_route_works(): void
    {
        $session = Session::query()->create([
            'session_date' => '2026-10-13',
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'capacity' => 6,
            'status' => 'tersedia',
        ]);

        Booking::query()->create([
            'code' => 'HC-TEST-0002',
            'session_id' => $session->id,
            'customer_name' => 'Tester 2',
            'whatsapp' => '6281234567890',
            'pax' => 1,
            'total_price' => 5000,
            'ticket_total' => 5000,
            'payment_method' => 'di_tempat',
            'status' => 'terkonfirmasi',
        ]);

        $this->assertSame(1, Booking::count());

        $response = $this->post(route('admin.bookings.clean_test_data'), [
            'reset_member_visits' => 1,
        ]);

        $response->assertRedirect(route('admin.bookings.index'));
        $response->assertSessionHas('status');
        $this->assertSame(0, Booking::count());
    }
}
