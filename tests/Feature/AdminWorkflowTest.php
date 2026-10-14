<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\CustomerProfile;
use App\Models\Film;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\ScheduleTemplate;
use App\Models\Session;
use App\Models\SnackOrderEdit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-13 10:00:00', 'Asia/Jakarta'));
        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_admin_dashboard_and_all_admin_sections_are_available(): void
    {
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Ringkasan hari ini');
        $this->get(route('admin.films.index'))->assertOk()->assertSee('Kelola film');
        $this->get(route('admin.schedule.index'))->assertOk()->assertSee('Kelola jadwal');
        $this->get(route('admin.bookings.index'))->assertOk()->assertSee('Kelola booking');
        $this->get(route('admin.snacks.index'))->assertOk()->assertSee('Menu jajanan');
        $this->get(route('admin.snacks.preparation.index'))->assertOk()->assertSee('Daftar siapkan');
        $this->get(route('admin.settings.edit'))->assertOk()->assertSee('Pengaturan Home Cinema');
        $this->get(route('admin.reports.index'))->assertOk()->assertSee('Laporan');
    }

    public function test_dashboard_lists_each_today_session_and_its_active_snack_preparation(): void
    {
        $film = $this->createFilm();
        $session = Session::query()->create([
            'session_date' => '2026-10-13',
            'start_time' => '16:00',
            'end_time' => '18:30',
            'film_id' => $film->id,
            'capacity' => 6,
            'status' => 'tersedia',
        ]);
        $booking = Booking::query()->create([
            'code' => 'HC-261013-ABC123',
            'session_id' => $session->id,
            'film_id' => $film->id,
            'customer_name' => 'Pelanggan Hari Ini',
            'whatsapp' => '6281234567890',
            'pax' => 2,
            'seats_reserved' => 2,
            'total_price' => 10000,
            'ticket_total' => 10000,
            'snack_total' => 0,
            'payment_method' => 'di_tempat',
            'status' => 'menunggu_konfirmasi',
        ]);
        BookingItem::query()->create([
            'booking_id' => $booking->id,
            'name_snapshot' => 'Air mineral',
            'price_snapshot' => 3000,
            'qty' => 2,
            'note' => 'Tanpa es',
            'status' => 'baru',
        ]);
        $cancelledBooking = Booking::query()->create([
            'code' => 'HC-261013-XYZ789',
            'session_id' => $session->id,
            'film_id' => $film->id,
            'customer_name' => 'Pelanggan batal',
            'whatsapp' => '6281234567891',
            'pax' => 4,
            'seats_reserved' => 4,
            'total_price' => 20000,
            'ticket_total' => 20000,
            'snack_total' => 0,
            'payment_method' => 'di_tempat',
            'status' => 'dibatalkan',
        ]);
        BookingItem::query()->create([
            'booking_id' => $cancelledBooking->id,
            'name_snapshot' => 'Pesanan batal',
            'price_snapshot' => 3000,
            'qty' => 1,
            'status' => 'baru',
        ]);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('2 kursi terpesan')
            ->assertSee('1 booking')
            ->assertSee('1 booking menunggu')
            ->assertSee('Persiapan jajan')
            ->assertSee('2× Air mineral')
            ->assertSee('Tanpa es')
            ->assertDontSee('Pelanggan batal')
            ->assertDontSee('Pesanan batal');
    }

    public function test_dashboard_shows_todays_sessions_before_low_attendance_sessions(): void
    {
        $film = $this->createFilm();
        Session::query()->create([
            'session_date' => '2026-10-13',
            'start_time' => '16:00',
            'end_time' => '18:30',
            'film_id' => $film->id,
            'capacity' => 6,
            'status' => 'tersedia',
        ]);
        Session::query()->create([
            'session_date' => '2026-10-14',
            'start_time' => '16:00',
            'end_time' => '18:30',
            'film_id' => $film->id,
            'capacity' => 6,
            'status' => 'tersedia',
        ]);

        $response = $this->get(route('admin.dashboard'))->assertOk();
        $content = $response->getContent();
        $todayPosition = strpos($content, '<h2 class="text-xl font-bold">Sesi hari ini</h2>');
        $lowAttendancePosition = strpos($content, 'Sesi belum mencapai 3 penonton');

        $this->assertNotFalse($todayPosition);
        $this->assertNotFalse($lowAttendancePosition);
        $this->assertLessThan($lowAttendancePosition, $todayPosition);
    }

    public function test_dashboard_and_reports_count_reserved_seats_for_private_bookings(): void
    {
        $film = $this->createFilm();
        $session = Session::query()->create([
            'session_date' => '2026-10-13',
            'start_time' => '16:00',
            'end_time' => '18:30',
            'film_id' => $film->id,
            'capacity' => 6,
            'is_private' => true,
            'status' => 'tersedia',
        ]);
        Booking::query()->create([
            'code' => 'HC-261013-PRIV01',
            'session_id' => $session->id,
            'film_id' => $film->id,
            'customer_name' => 'Pelanggan Private',
            'whatsapp' => '6281234567890',
            'pax' => 2,
            'seats_reserved' => 6,
            'booking_type' => 'private',
            'total_price' => 36000,
            'ticket_total' => 36000,
            'snack_total' => 0,
            'payment_method' => 'di_tempat',
            'status' => 'terkonfirmasi',
        ]);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('6 kursi terpesan');
        $this->get(route('admin.reports.index', ['date' => '2026-10-13']))
            ->assertOk()
            ->assertViewHas('viewers', 6);
    }

    public function test_public_and_admin_get_pages_render_with_booking_items_and_audit_history(): void
    {
        $film = $this->createFilm();
        $category = MenuCategory::query()->create([
            'name' => 'Minuman uji',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $menuItem = MenuItem::query()->create([
            'category_id' => $category->id,
            'name' => 'Air mineral',
            'price' => 3000,
            'prep_minutes' => 1,
            'stock_mode' => 'toggle',
            'is_available' => true,
            'is_active' => true,
        ]);
        $session = Session::query()->create([
            'session_date' => '2026-10-14',
            'start_time' => '16:00',
            'end_time' => '18:30',
            'film_id' => $film->id,
            'capacity' => 6,
            'status' => 'tersedia',
        ]);
        $booking = Booking::query()->create([
            'code' => 'HC-261013-SMOKE1',
            'session_id' => $session->id,
            'film_id' => $film->id,
            'customer_name' => 'Pelanggan Smoke',
            'whatsapp' => '6281234567890',
            'pax' => 1,
            'seats_reserved' => 1,
            'booking_type' => 'per_orang',
            'total_price' => 8000,
            'ticket_total' => 5000,
            'snack_total' => 3000,
            'payment_method' => 'di_tempat',
            'status' => 'menunggu_konfirmasi',
        ]);
        $item = BookingItem::query()->create([
            'booking_id' => $booking->id,
            'menu_item_id' => $menuItem->id,
            'name_snapshot' => 'Air mineral',
            'price_snapshot' => 3000,
            'qty' => 1,
            'status' => 'baru',
        ]);
        SnackOrderEdit::query()->create([
            'booking_id' => $booking->id,
            'actor_id' => auth()->id(),
            'summary' => 'Pesanan jajan diperbarui: Air mineral: 0× → 1×',
            'before_details' => [],
            'after_details' => [[
                'menu_item_id' => $menuItem->id,
                'name_snapshot' => 'Air mineral',
                'price_snapshot' => 3000,
                'qty' => 1,
                'note' => null,
            ]],
        ]);

        foreach ([
            route('home'),
            route('schedule.date', '2026-10-14'),
            route('booking.create', $session),
            route('booking.ticket', $booking->code),
            route('booking.lookup.form'),
            route('admin.dashboard'),
            route('admin.bookings.index'),
            route('admin.bookings.show', $booking),
            route('admin.films.index'),
            route('admin.films.create'),
            route('admin.films.edit', $film),
            route('admin.schedule.index'),
            route('admin.snacks.index'),
            route('admin.snacks.create'),
            route('admin.snacks.edit', $menuItem),
            route('admin.snacks.preparation.index'),
            route('admin.snacks.preparation.show', $session),
            route('admin.reports.index'),
            route('admin.settings.edit'),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get(route('admin.bookings.proof', $booking))->assertNotFound();
        $this->assertModelExists($item);
        $this->assertDatabaseHas('snack_order_edits', ['booking_id' => $booking->id]);
    }

    public function test_booking_detail_shows_snack_update_action_and_audit_snapshots(): void
    {
        $film = $this->createFilm();
        $session = Session::query()->create([
            'session_date' => '2026-10-13',
            'start_time' => '16:00',
            'end_time' => '18:30',
            'film_id' => $film->id,
            'capacity' => 6,
            'status' => 'tersedia',
        ]);
        $booking = Booking::query()->create([
            'code' => 'HC-261013-LOG123',
            'session_id' => $session->id,
            'film_id' => $film->id,
            'customer_name' => 'Pelanggan Audit',
            'whatsapp' => '6281234567890',
            'pax' => 2,
            'seats_reserved' => 2,
            'total_price' => 16000,
            'ticket_total' => 10000,
            'snack_total' => 6000,
            'payment_method' => 'di_tempat',
            'status' => 'menunggu_konfirmasi',
        ]);
        SnackOrderEdit::query()->create([
            'booking_id' => $booking->id,
            'actor_id' => auth()->id(),
            'summary' => 'Air mineral berubah dari 1 menjadi 2.',
            'before_details' => [
                ['name_snapshot' => 'Air mineral', 'price_snapshot' => 3000, 'qty' => 1, 'note' => null],
            ],
            'after_details' => [
                ['name_snapshot' => 'Air mineral', 'price_snapshot' => 3000, 'qty' => 2, 'note' => 'Tanpa es'],
            ],
        ]);

        $this->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Kirim update ke pelanggan via WA')
            ->assertSee('Total jajan saat ini: Rp 6.000')
            ->assertSee('Total booking: Rp 16.000')
            ->assertSee('Air mineral × 1')
            ->assertSee('Air mineral × 2')
            ->assertSee('Tanpa es')
            ->assertSee('Air mineral berubah dari 1 menjadi 2.');
    }

    public function test_non_admin_user_cannot_open_admin_pages(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_can_create_a_film_and_store_a_small_webp_poster(): void
    {
        Storage::fake('public');

        $this->post(route('admin.films.store'), [
            'title' => 'Poster Uji',
            'duration_minutes' => 100,
            'genre' => 'Drama',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis singkat untuk pengujian poster.',
            'license_notes' => 'Catatan lisensi internal.',
            'is_active' => '1',
            'poster' => UploadedFile::fake()->image('poster.png', 800, 1200),
        ])->assertRedirect(route('admin.films.index'));

        $film = Film::query()->where('title', 'Poster Uji')->firstOrFail();
        Storage::disk('public')->assertExists($film->poster_path);
        $image = imagecreatefromstring(Storage::disk('public')->get($film->poster_path));

        $this->assertSame('webp', pathinfo($film->poster_path, PATHINFO_EXTENSION));
        $this->assertLessThanOrEqual(500, imagesx($image));
        $this->assertDatabaseHas('films', ['title' => 'Poster Uji', 'license_notes' => 'Catatan lisensi internal.']);

        imagedestroy($image);
    }

    public function test_removing_a_film_closes_future_sessions_that_use_it(): void
    {
        $film = $this->createFilm();
        $session = Session::query()->create([
            'session_date' => '2026-10-14',
            'start_time' => '16:00',
            'end_time' => '18:30',
            'film_id' => $film->id,
            'capacity' => 6,
            'status' => 'tersedia',
        ]);

        $this->delete(route('admin.films.destroy', $film))
            ->assertRedirect(route('admin.films.index'));

        $this->assertSoftDeleted('films', ['id' => $film->id]);
        $this->assertDatabaseHas('screening_sessions', ['id' => $session->id, 'status' => 'ditutup']);
    }

    public function test_admin_can_generate_sessions_and_update_schedule_exceptions(): void
    {
        $template = ScheduleTemplate::query()->create([
            'day_type' => 'weekday',
            'start_time' => '16:00',
            'duration_minutes' => 150,
            'is_active' => true,
        ]);

        $this->post(route('admin.schedule.generate'))->assertRedirect();
        $this->assertDatabaseHas('screening_sessions', ['session_date' => '2026-10-13', 'start_time' => '16:00']);

        $this->patch(route('admin.schedule.templates.update', $template), [
            'day_type' => 'weekday',
            'start_time' => '16:00',
            'duration_minutes' => 180,
            'is_active' => 1,
        ])->assertRedirect();
        $this->assertDatabaseHas('schedule_templates', [
            'id' => $template->id,
            'duration_minutes' => 180,
            'is_active' => true,
        ]);

        $this->post(route('admin.schedule.holidays.store'), [
            'holiday_date' => '2026-10-20',
            'name' => 'Hari Desa',
        ])->assertRedirect();
        $this->assertDatabaseHas('holidays', ['holiday_date' => '2026-10-20', 'name' => 'Hari Desa']);

        $this->post(route('admin.schedule.blocked.store'), [
            'blocked_date' => '2026-10-14',
            'start_time' => '16:00',
            'reason' => 'Maintenance',
        ])->assertRedirect();
        $this->assertDatabaseHas('blocked_dates', [
            'blocked_date' => '2026-10-14',
            'scope_key' => '16:00',
            'reason' => 'Maintenance',
        ]);
    }

    public function test_admin_cannot_add_overlapping_sessions_for_a_day_type(): void
    {
        $this->post(route('admin.schedule.templates.store'), [
            'day_type' => 'weekday',
            'start_time' => '16:00',
            'duration_minutes' => 150,
        ])->assertRedirect();

        $this->from(route('admin.schedule.index'))
            ->post(route('admin.schedule.templates.store'), [
                'day_type' => 'weekday',
                'start_time' => '18:00',
                'duration_minutes' => 150,
            ])
            ->assertSessionHasErrors('start_time');

        $this->assertDatabaseCount('schedule_templates', 1);
    }

    public function test_admin_can_confirm_mark_no_show_and_block_a_repeated_no_show_number(): void
    {
        $profile = CustomerProfile::query()->create(['whatsapp' => '6281234567890']);
        $film = $this->createFilm();

        foreach (['2026-10-14', '2026-10-15'] as $date) {
            $session = Session::query()->create([
                'session_date' => $date,
                'start_time' => '16:00',
                'end_time' => '18:30',
                'film_id' => $film->id,
                'capacity' => 6,
                'status' => 'tersedia',
            ]);
            $booking = Booking::query()->create([
                'code' => 'HC-'.str_replace('-', '', substr($date, 2)).'-'.strtoupper(fake()->unique()->bothify('??????')),
                'session_id' => $session->id,
                'film_id' => $film->id,
                'customer_profile_id' => $profile->id,
                'customer_name' => 'Pelanggan Uji',
                'whatsapp' => $profile->whatsapp,
                'pax' => 1,
                'seats_reserved' => 1,
                'booking_type' => 'per_orang',
                'total_price' => 5000,
                'payment_method' => 'di_tempat',
                'status' => 'menunggu_konfirmasi',
            ]);

            $this->patch(route('admin.bookings.status', $booking), ['action' => 'konfirmasi'])->assertRedirect();
            $this->patch(route('admin.bookings.status', $booking), ['action' => 'tidak_hadir'])->assertRedirect();
        }

        $this->assertSame(2, $profile->fresh()->no_show_count);
        $this->patch(route('admin.customers.toggle-block', $profile))->assertRedirect();
        $this->assertTrue($profile->fresh()->is_blocked);
    }

    public function test_admin_can_update_settings_and_download_csv_report(): void
    {
        $this->put(route('admin.settings.update'), [
            'weekday_price' => 5500,
            'weekend_price' => 6500,
            'holiday_price' => 7000,
            'private_price' => 39000,
            'minimum_public_viewers' => 3,
            'booking_cutoff_minutes' => 45,
            'cancellation_cutoff_hours' => 8,
            'admin_whatsapp' => '081234567890',
            'electricity_cost_per_session' => 3000,
            'snacks_enabled' => 1,
            'snack_prep_buffer_minutes' => 20,
            'snack_max_qty_per_item' => 8,
            'payment_methods' => ['di_tempat', 'qris'],
            'qris_image' => UploadedFile::fake()->image('qris.png', 400, 400),
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'admin_whatsapp', 'value' => '6281234567890']);
        $this->assertDatabaseHas('settings', ['key' => 'weekday_price', 'value' => '5500']);
        $this->get(route('admin.reports.export', ['period' => 'bulanan', 'date' => '2026-10-14']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    private function createFilm(): Film
    {
        return Film::query()->create([
            'title' => 'Film untuk admin',
            'duration_minutes' => 100,
            'genre' => 'Drama',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis film uji.',
            'is_active' => true,
        ]);
    }
}
