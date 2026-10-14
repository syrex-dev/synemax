<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Session;
use App\Models\User;
use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private Film $firstFilm;

    private Film $secondFilm;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-13 10:00:00', 'Asia/Jakarta'));
        $this->firstFilm = $this->createFilm('Malam di Bukit Bintang');
        $this->secondFilm = $this->createFilm('Misi Terakhir Pak Pos');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_booking_cannot_oversell_capacity_or_change_the_locked_film(): void
    {
        $session = $this->createSession();

        $this->post(route('booking.store', $session), $this->bookingData($this->firstFilm, pax: 5))
            ->assertRedirect();

        $this->from(route('booking.create', $session))
            ->post(route('booking.store', $session), $this->bookingData($this->firstFilm, pax: 2))
            ->assertSessionHasErrors('pax');

        $this->from(route('booking.create', $session))
            ->post(route('booking.store', $session), $this->bookingData($this->secondFilm, pax: 1))
            ->assertSessionHasErrors('film_id');

        $this->assertDatabaseCount('bookings', 1);
        $this->assertSame($this->firstFilm->id, $session->fresh()->film_id);
    }

    public function test_competing_requests_for_the_last_seat_never_exceed_six_reserved_seats(): void
    {
        $session = $this->createSession($this->firstFilm);
        $this->createBooking($session, $this->firstFilm, pax: 5);

        $firstAttempt = $this->post(
            route('booking.store', $session),
            $this->bookingData($this->firstFilm, pax: 1, whatsapp: '081111111111'),
        );
        $secondAttempt = $this->from(route('booking.create', $session))
            ->post(
                route('booking.store', $session),
                $this->bookingData($this->firstFilm, pax: 1, whatsapp: '082222222222'),
            );

        $firstAttempt->assertRedirect();
        $secondAttempt->assertSessionHasErrors('pax');
        $this->assertSame(
            6,
            (int) Booking::query()->where('session_id', $session->id)->sum('seats_reserved'),
        );
    }

    public function test_server_never_uses_a_capacity_above_six(): void
    {
        $session = $this->createSession($this->firstFilm);
        $session->update(['capacity' => 8]);
        $this->createBooking($session, $this->firstFilm, pax: 5);

        $this->from(route('booking.create', $session))
            ->post(route('booking.store', $session), $this->bookingData($this->firstFilm, pax: 2))
            ->assertSessionHasErrors('pax');

        $this->assertSame(1, $session->fresh()->remainingSeats());
        $this->assertSame(
            5,
            (int) Booking::query()->where('session_id', $session->id)->sum('seats_reserved'),
        );
    }

    public function test_private_booking_charges_a_six_seat_package_and_closes_the_session(): void
    {
        $session = $this->createSession();
        $data = $this->bookingData($this->firstFilm, type: 'private');
        unset($data['pax']);

        $response = $this->post(route('booking.store', $session), $data);

        $response->assertRedirect();
        $booking = Booking::query()->firstOrFail();

        $this->assertSame(6, $booking->pax);
        $this->assertSame(6, $booking->seats_reserved);
        $this->assertSame(36000, $booking->total_price);
        $this->assertTrue($session->fresh()->is_private);

        $this->from(route('booking.create', $session))
            ->post(route('booking.store', $session), $this->bookingData($this->firstFilm, pax: 1))
            ->assertSessionHasErrors('booking_type');
    }

    public function test_private_booking_ignores_a_forged_pax_value(): void
    {
        $session = $this->createSession();
        $data = $this->bookingData($this->firstFilm, type: 'private');
        $data['pax'] = 2;

        $this->post(route('booking.store', $session), $data)->assertRedirect();

        $booking = Booking::query()->firstOrFail();
        $this->assertSame(6, $booking->pax);
        $this->assertSame(6, $booking->seats_reserved);
        $this->assertSame(36000, $booking->total_price);
    }

    public function test_per_person_booking_requires_pax_and_calculates_ticket_total_on_the_server(): void
    {
        $session = $this->createSession();
        $data = $this->bookingData($this->firstFilm, pax: 3);
        unset($data['pax']);

        $this->from(route('booking.create', $session))
            ->post(route('booking.store', $session), $data)
            ->assertSessionHasErrors('pax');

        $this->assertDatabaseCount('bookings', 0);

        $data['pax'] = 3;
        $this->post(route('booking.store', $session), $data)->assertRedirect();

        $booking = Booking::query()->firstOrFail();
        $this->assertSame(3, $booking->pax);
        $this->assertSame(3, $booking->seats_reserved);
        $this->assertSame(15000, $booking->total_price);
    }

    public function test_private_and_per_person_bookings_render_across_public_and_admin_pages(): void
    {
        $privateSession = $this->createSession();
        $this->post(
            route('booking.store', $privateSession),
            $this->bookingData($this->firstFilm, pax: 2, type: 'private'),
        )->assertRedirect();
        $privateBooking = Booking::query()->firstOrFail();

        $this->get(route('booking.success', $privateBooking->code))
            ->assertOk()
            ->assertSee('Sewa Private (6 kursi)');
        $this->post(route('booking.lookup'), [
            'code' => $privateBooking->code,
            'whatsapp' => '081234567890',
        ])->assertOk()->assertSee('Sewa Private (6 kursi)');

        $perPersonSession = $this->createSession($this->firstFilm, '19:30');
        $this->post(
            route('booking.store', $perPersonSession),
            $this->bookingData($this->firstFilm, pax: 2, type: 'per_orang', whatsapp: '082222222222'),
        )->assertRedirect();
        $perPersonBooking = Booking::query()->where('session_id', $perPersonSession->id)->firstOrFail();
        $privateBooking->update(['status' => 'terkonfirmasi']);
        $perPersonBooking->update(['status' => 'terkonfirmasi']);

        $this->get(route('booking.success', $perPersonBooking->code))
            ->assertOk()
            ->assertSee('2 orang');
        $this->post(route('booking.lookup'), [
            'code' => $perPersonBooking->code,
            'whatsapp' => '082222222222',
        ])->assertOk()->assertSee('2 orang');

        $this->actingAs(User::factory()->create());
        foreach ([$privateBooking, $perPersonBooking] as $booking) {
            $this->get(route('admin.bookings.show', $booking))
                ->assertOk()
                ->assertSee($booking->booking_type === 'private' ? 'Sewa Private (6 kursi)' : '2 orang');
        }
        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.bookings.index'))->assertOk();
        $this->get(route('admin.reports.index'))->assertOk()->assertSee('Kursi terpesan');
    }

    public function test_private_booking_is_rejected_if_the_session_already_has_a_public_booking(): void
    {
        $session = $this->createSession($this->firstFilm);
        $this->createBooking($session, $this->firstFilm, pax: 1);

        $this->from(route('booking.create', $session))
            ->post(route('booking.store', $session), $this->bookingData($this->firstFilm, type: 'private'))
            ->assertSessionHasErrors('booking_type');

        $this->assertFalse($session->fresh()->is_private);
    }

    public function test_customer_can_cancel_before_the_deadline_and_release_reserved_seats(): void
    {
        $session = $this->createSession($this->firstFilm);
        $booking = $this->createBooking($session, $this->firstFilm, pax: 2);

        $response = $this->post(route('booking.cancel', $booking->code), [
            'whatsapp' => '081234567890',
        ]);

        $response->assertRedirect(route('booking.lookup.form'));
        $this->assertSame('dibatalkan', $booking->fresh()->status);
        $this->assertSame(0, (int) $session->fresh()->occupiedBookings()->sum('seats_reserved'));
    }

    public function test_customer_cannot_cancel_after_the_configured_deadline(): void
    {
        $session = $this->createSession($this->firstFilm);
        $booking = $this->createBooking($session, $this->firstFilm, pax: 2);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-14 11:00:00', 'Asia/Jakarta'));

        $this->from(route('booking.lookup.form'))
            ->post(route('booking.cancel', $booking->code), ['whatsapp' => '081234567890'])
            ->assertSessionHasErrors('code');

        $this->assertSame('menunggu_konfirmasi', $booking->fresh()->status);
    }

    public function test_phone_number_is_normalized_to_the_indonesian_country_code(): void
    {
        $session = $this->createSession();

        $this->post(route('booking.store', $session), $this->bookingData($this->firstFilm))
            ->assertRedirect();

        $this->assertDatabaseHas('bookings', ['whatsapp' => '6281234567890']);
    }

    public function test_mysql_booking_transactions_execute_a_row_lock(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Row-lock assertion requires the MySQL test connection.');
        }

        $session = $this->createSession();
        $queries = [];

        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        app(BookingService::class)->create(
            $session,
            [
                ...$this->bookingData($this->firstFilm),
                'whatsapp' => '6281234567890',
            ],
        );

        $this->assertTrue(
            collect($queries)->contains(fn (string $query): bool => str_contains($query, 'for update')),
            'The booking transaction must lock the screening session row on MySQL.',
        );
    }

    public function test_per_person_booking_exceeding_remaining_seats_is_rejected(): void
    {
        $session = $this->createSession($this->firstFilm);
        $this->createBooking($session, $this->firstFilm, pax: 4);

        $response = $this->from(route('booking.create', $session))
            ->post(route('booking.store', $session), $this->bookingData($this->firstFilm, pax: 3));

        $response->assertSessionHasErrors('pax');
        $this->assertSame(2, $session->fresh()->remainingSeats());
    }

    public function test_wizard_page_renders_with_unselected_booking_type_and_all_steps(): void
    {
        $session = $this->createSession();

        $response = $this->get(route('booking.create', $session));

        $response->assertOk()
            ->assertSee('Langkah')
            ->assertSee('dari 5')
            ->assertSee('Jadwal')
            ->assertSee('Film')
            ->assertSee('Tipe')
            ->assertSee('Jajan')
            ->assertSee('Ringkasan')
            ->assertSee('Per orang')
            ->assertSee('Sewa Private')
            ->assertSee('Pilih film')
            ->assertSee('Film yang cocok belum ada');
    }

    private function createFilm(string $title): Film
    {
        return Film::query()->create([
            'title' => $title,
            'duration_minutes' => 100,
            'genre' => 'Drama',
            'age_rating' => 'SU',
            'synopsis' => 'Cerita demo untuk menguji katalog Home Cinema.',
            'is_active' => true,
        ]);
    }

    private function createSession(?Film $film = null, string $startTime = '16:00'): Session
    {
        return Session::query()->create([
            'session_date' => '2026-10-14',
            'start_time' => $startTime,
            'end_time' => $startTime === '16:00' ? '18:30' : '22:00',
            'film_id' => $film?->id,
            'capacity' => 6,
            'status' => 'tersedia',
        ]);
    }

    private function createBooking(Session $session, Film $film, int $pax): Booking
    {
        return Booking::query()->create([
            'code' => 'HC-261014-'.strtoupper(fake()->unique()->bothify('??????')),
            'session_id' => $session->id,
            'film_id' => $film->id,
            'customer_name' => 'Pemesan Awal',
            'whatsapp' => '6281234567890',
            'pax' => $pax,
            'seats_reserved' => $pax,
            'booking_type' => 'per_orang',
            'total_price' => 5000 * $pax,
            'payment_method' => 'di_tempat',
            'status' => 'menunggu_konfirmasi',
        ]);
    }

    private function bookingData(
        Film $film,
        int $pax = 1,
        string $type = 'per_orang',
        string $whatsapp = '081234567890',
    ): array {
        return [
            'customer_name' => 'Pemesan Baru',
            'whatsapp' => $whatsapp,
            'pax' => $pax,
            'booking_type' => $type,
            'film_id' => $film->id,
            'payment_method' => 'di_tempat',
        ];
    }
}
