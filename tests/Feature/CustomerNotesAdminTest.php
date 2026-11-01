<?php

namespace Tests\Feature;

use App\Models\Film;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Session;
use App\Models\Setting;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerNotesAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Session $session;

    private Film $film;

    private MenuItem $menuItem;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['key' => 'admin_whatsapp'], ['value' => '628123456789']);

        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->film = Film::create([
            'title' => 'Film Uji Catatan',
            'slug' => 'film-uji-catatan',
            'duration_minutes' => 110,
            'age_rating' => '13+',
            'genre' => 'Drama',
            'synopsis' => 'Sinopsis film uji',
            'is_active' => true,
        ]);

        $category = MenuCategory::create([
            'name' => 'Snacks',
            'slug' => 'snacks',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->menuItem = MenuItem::create([
            'category_id' => $category->id,
            'name' => 'Popcorn Caramel',
            'slug' => 'popcorn-caramel',
            'price' => 25000,
            'cost_price' => 10000,
            'stock_mode' => 'unlimited',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->session = Session::create([
            'film_id' => $this->film->id,
            'session_date' => now()->addDay()->toDateString(),
            'start_time' => '19:00:00',
            'end_time' => '21:00:00',
            'capacity' => 6,
            'status' => 'tersedia',
        ]);
    }

    public function test_booking_service_stores_customer_notes_and_snack_notes(): void
    {
        $bookingService = app(BookingService::class);

        $customerNote = 'Tolong sediakan kursi paling tengah ya, terima kasih!';
        $snackNote = 'Ekstra manis, jangan terlalu asin.';

        $booking = $bookingService->create(
            requestedSession: $this->session,
            data: [
                'customer_name' => 'Budi Santoso',
                'whatsapp' => '081234567890',
                'pax' => 2,
                'booking_type' => 'per_orang',
                'package' => 'reguler',
                'film_id' => $this->film->id,
                'payment_method' => 'di_tempat',
                'notes' => $customerNote,
                'snacks' => [
                    $this->menuItem->id => [
                        'qty' => 1,
                        'note' => $snackNote,
                    ],
                ],
            ]
        );

        $this->assertNotNull($booking->id);
        $this->assertSame($customerNote, $booking->notes);

        $freshItem = $booking->items()->first();
        $this->assertNotNull($freshItem);
        $this->assertSame($snackNote, $freshItem->note);
    }

    public function test_booking_request_validates_notes_max_300_chars(): void
    {
        $tooLongNote = str_repeat('a', 301);

        $response = $this->post(route('booking.store', $this->session), [
            'customer_name' => 'Budi Santoso',
            'whatsapp' => '081234567890',
            'pax' => 2,
            'package' => 'reguler',
            'film_id' => $this->film->id,
            'payment_method' => 'di_tempat',
            'notes' => $tooLongNote,
        ]);

        $response->assertSessionHasErrors('notes');
    }

    public function test_admin_booking_show_displays_notes_and_escapes_html(): void
    {
        $maliciousNote = '<b>Tolong AC dingin</b> & <script>alert("hack")</script>';
        $snackNote = 'Tanpa garam <script>bad()</script>';

        $booking = app(BookingService::class)->create(
            requestedSession: $this->session,
            data: [
                'customer_name' => 'Siti Nurhaliza',
                'whatsapp' => '081234567891',
                'pax' => 2,
                'booking_type' => 'per_orang',
                'package' => 'reguler',
                'film_id' => $this->film->id,
                'payment_method' => 'di_tempat',
                'notes' => $maliciousNote,
                'snacks' => [
                    $this->menuItem->id => [
                        'qty' => 2,
                        'note' => $snackNote,
                    ],
                ],
            ]
        );

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking));
        $response->assertOk();

        // Must show Ada Catatan badge
        $response->assertSee('Ada Catatan');
        $response->assertSee('Catatan Pemesan:');

        // Escaped output: raw HTML tags must not execute as HTML elements
        $response->assertSee(e($maliciousNote), false);
        $response->assertDontSee('<script>alert("hack")</script>', false);

        // Snack note must be visible and escaped
        $response->assertSee(e($snackNote), false);
        $response->assertDontSee('<script>bad()</script>', false);
    }

    public function test_admin_bookings_index_displays_notes_and_appends_to_wa_url(): void
    {
        $customerNote = 'Tolong sediakan bantal tambahan.';

        $booking = app(BookingService::class)->create(
            requestedSession: $this->session,
            data: [
                'customer_name' => 'Joko Anwar',
                'whatsapp' => '081234567892',
                'pax' => 1,
                'booking_type' => 'per_orang',
                'package' => 'reguler',
                'film_id' => $this->film->id,
                'payment_method' => 'di_tempat',
                'notes' => $customerNote,
            ]
        );

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.index'));
        $response->assertOk();

        $response->assertSee('Ada catatan');
        $response->assertSee('Catatan Pemesan:');
        $response->assertSee($customerNote);
        $response->assertSee(rawurlencode("\nCatatan pemesan: ".$customerNote), false);
    }

    public function test_admin_snacks_preparation_displays_booking_and_item_notes(): void
    {
        $customerNote = 'Penting: ulang tahun teman saya.';
        $snackNote = 'Tulisi kartu: Selamat Ultah!';

        $booking = app(BookingService::class)->create(
            requestedSession: $this->session,
            data: [
                'customer_name' => 'Rian DMasiv',
                'whatsapp' => '081234567893',
                'pax' => 2,
                'booking_type' => 'per_orang',
                'package' => 'reguler',
                'film_id' => $this->film->id,
                'payment_method' => 'di_tempat',
                'notes' => $customerNote,
                'snacks' => [
                    $this->menuItem->id => [
                        'qty' => 1,
                        'note' => $snackNote,
                    ],
                ],
            ]
        );

        $response = $this->actingAs($this->admin)->get(route('admin.snacks.preparation.show', $this->session));
        $response->assertOk();

        $response->assertSee('Ada catatan');
        $response->assertSee('Catatan Pemesan:');
        $response->assertSee($customerNote);
        $response->assertSee($snackNote);
    }

    public function test_admin_dashboard_displays_today_session_snack_notes_and_customer_notes(): void
    {
        $todaySession = Session::create([
            'film_id' => $this->film->id,
            'session_date' => now()->toDateString(),
            'start_time' => '23:30:00',
            'end_time' => '23:59:00',
            'capacity' => 6,
            'status' => 'tersedia',
        ]);

        $customerNote = 'Catatan di dashboard hari ini';
        $snackNote = 'Snack note di dashboard';

        $booking = app(BookingService::class)->create(
            requestedSession: $todaySession,
            data: [
                'customer_name' => 'Dewi Persik',
                'whatsapp' => '081234567894',
                'pax' => 2,
                'booking_type' => 'per_orang',
                'package' => 'reguler',
                'film_id' => $this->film->id,
                'payment_method' => 'di_tempat',
                'notes' => $customerNote,
                'snacks' => [
                    $this->menuItem->id => [
                        'qty' => 2,
                        'note' => $snackNote,
                    ],
                ],
            ]
        );

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertOk();

        $response->assertSee('Ada catatan');
        $response->assertSee('Catatan pemesan: '.$customerNote);
        $response->assertSee($snackNote);
    }
}
