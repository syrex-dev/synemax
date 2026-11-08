<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Member;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Session;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWalkInBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Film $film;

    private Session $session;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-13 10:00:00', 'Asia/Jakarta'));
        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);

        $this->film = Film::query()->create([
            'title' => 'Petualangan Rimba',
            'genre' => 'Animasi',
            'duration_minutes' => 90,
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis petualangan rimba.',
            'is_active' => true,
        ]);

        $this->session = Session::query()->create([
            'session_date' => '2026-10-13',
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'capacity' => 6,
            'status' => 'tersedia',
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_admin_can_view_walk_in_booking_page(): void
    {
        $response = $this->get(route('admin.bookings.create'));

        $response->assertOk();
        $response->assertSee('Loket Pemesanan di Tempat');
        $response->assertSee('Petualangan Rimba');
    }

    public function test_admin_can_perform_member_lookup(): void
    {
        $member = Member::query()->create([
            'code' => 'MBR-ZRGH-V4B9-Q',
            'member_code' => 'MBR-ZRGH-V4B9-Q',
            'name' => 'Bagas Pratama',
            'nickname' => 'Bagas',
            'status' => 'aktif',
            'opening_visits' => 2,
        ]);

        $response = $this->getJson(route('admin.bookings.member_lookup', [
            'q' => 'MBR-ZRGH-V4B9-Q',
            'session_id' => $this->session->id,
        ]));

        $response->assertOk();
        $response->assertJson([
            'found' => true,
            'member' => [
                'code' => 'MBR-ZRGH-V4B9-Q',
                'name' => 'Bagas Pratama',
                'nickname' => 'Bagas',
            ],
        ]);
    }

    public function test_admin_can_create_walk_in_booking_for_child_without_phone(): void
    {
        $payload = [
            'session_id' => $this->session->id,
            'package' => 'reguler',
            'pax' => 2,
            'film_id' => $this->film->id,
            'customer_name' => 'Adit (Walk-in)',
            'whatsapp' => '', // child has no phone
            'payment_method' => 'di_tempat',
            'cash_received' => 20000,
            'mark_as_attended' => 1,
            'notes' => 'Langsung datang di counter',
        ];

        $response = $this->post(route('admin.bookings.store'), $payload);

        $response->assertRedirect(route('admin.bookings.create', ['session_id' => $this->session->id]));
        $response->assertSessionHas('walk_in_success');

        $booking = Booking::query()->latest('id')->firstOrFail();
        $this->assertSame('Adit (Walk-in)', $booking->customer_name);
        $this->assertSame(2, $booking->pax);
        $this->assertSame('hadir', $booking->status);
        $this->assertSame('di_tempat', $booking->payment_method);
        $this->assertNotNull($booking->checked_in_at);
        $this->assertSame($this->admin->id, $booking->checked_in_by);

        // Ticket price = 5000 * 2 = 10000, cash received = 20000, change = 10000
        $this->assertSame(10000, $booking->total_price);
        $this->assertSame(10000, session('walk_in_success')['change_amount']);
    }

    public function test_admin_can_create_walk_in_with_snacks_and_it_records_financial_revenue(): void
    {
        $category = MenuCategory::query()->create(['name' => 'Minuman', 'sort_order' => 1, 'is_active' => true]);
        $snack = MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Teh Tarik Dingin',
            'price' => 4000,
            'cost_price' => 2000,
            'stock_mode' => 'angka',
            'stock_qty' => 10,
            'is_available' => true,
            'is_active' => true,
        ]);

        $payload = [
            'session_id' => $this->session->id,
            'package' => 'reguler',
            'pax' => 1,
            'film_id' => $this->film->id,
            'customer_name' => 'Dimas',
            'whatsapp' => '081234567890',
            'payment_method' => 'di_tempat',
            'cash_received' => 15000,
            'mark_as_attended' => 1,
            'snacks' => [
                $snack->id => ['qty' => 2, 'note' => 'es sedikit'],
            ],
        ];

        $this->post(route('admin.bookings.store'), $payload)->assertRedirect();

        $booking = Booking::query()->latest('id')->firstOrFail();
        $this->assertSame(5000, $booking->ticket_total);
        $this->assertSame(8000, $booking->snack_total);
        $this->assertSame(13000, $booking->total_price);
        $this->assertSame(2000, session('walk_in_success')['change_amount']);

        // Check stock deducted
        $this->assertSame(8, $snack->fresh()->stock_qty);

        // Check report index displays the financial records
        $reportResponse = $this->get(route('admin.reports.index', ['date' => '2026-10-13']));
        $reportResponse->assertOk();
        $reportResponse->assertSee('13.000'); // Total revenue
    }

    public function test_admin_can_create_walk_in_with_member_card_and_stamp_is_recorded(): void
    {
        $member = Member::query()->create([
            'code' => 'MBR-9YQ5-EYV3-M',
            'member_code' => 'MBR-9YQ5-EYV3-M',
            'name' => 'Abi Setya',
            'nickname' => 'Abi',
            'status' => 'aktif',
            'opening_visits' => 1,
        ]);

        $payload = [
            'session_id' => $this->session->id,
            'package' => 'reguler',
            'pax' => 1,
            'film_id' => $this->film->id,
            'customer_name' => '', // auto-fallback to member name
            'whatsapp' => '',
            'member_code' => 'MBR-9YQ5-EYV3-M',
            'payment_method' => 'di_tempat',
            'cash_received' => 5000,
            'mark_as_attended' => 1,
        ];

        $this->post(route('admin.bookings.store'), $payload)->assertRedirect();

        $booking = Booking::query()->latest('id')->firstOrFail();
        $this->assertStringContainsString('Abi', $booking->customer_name);
        $this->assertStringContainsString('Member: Abi Setya', (string) $booking->notes);

        // Check member visit was recorded
        $this->assertDatabaseHas('member_visits', [
            'member_id' => $member->id,
            'session_id' => $this->session->id,
            'booking_id' => $booking->id,
            'method' => 'loket',
        ]);
    }

    public function test_walk_in_couple_booking_locks_session(): void
    {
        $payload = [
            'session_id' => $this->session->id,
            'package' => 'couple',
            'pax' => 2,
            'film_id' => $this->film->id,
            'customer_name' => 'Pasangan Walk-in',
            'payment_method' => 'di_tempat',
            'cash_received' => 20000,
            'mark_as_attended' => 1,
        ];

        $this->post(route('admin.bookings.store'), $payload)->assertRedirect();

        $booking = Booking::query()->latest('id')->firstOrFail();
        $this->assertSame('couple', $booking->package);
        $this->assertSame(2, $booking->seats_reserved);
        $this->assertTrue($this->session->fresh()->isConfirmed());
    }

    public function test_walk_in_cannot_exceed_session_capacity(): void
    {
        // First book 5 seats
        $payload1 = [
            'session_id' => $this->session->id,
            'package' => 'reguler',
            'pax' => 5,
            'film_id' => $this->film->id,
            'customer_name' => 'Kelompok A',
            'payment_method' => 'di_tempat',
        ];
        $this->post(route('admin.bookings.store'), $payload1)->assertRedirect();

        // Second booking requests 3 seats (only 1 remaining)
        $payload2 = [
            'session_id' => $this->session->id,
            'package' => 'reguler',
            'pax' => 3,
            'film_id' => $this->film->id,
            'customer_name' => 'Kelompok B',
            'payment_method' => 'di_tempat',
        ];
        $response = $this->from(route('admin.bookings.create'))
            ->post(route('admin.bookings.store'), $payload2);

        $response->assertSessionHasErrors('pax');
    }
}
