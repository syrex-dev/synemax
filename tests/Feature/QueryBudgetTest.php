<?php

namespace Tests\Feature;

use App\Models\Film;
use App\Models\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create sample records
        $film = Film::query()->create([
            'title' => 'Sample Sci-Fi Film',
            'duration_minutes' => 120,
            'genre' => 'Sci-Fi',
            'age_rating' => '13+',
            'synopsis' => 'Test synopsis for query budget.',
            'is_active' => true,
        ]);

        Session::query()->create([
            'session_date' => now()->addDay()->toDateString(),
            'start_time' => '14:00',
            'end_time' => '16:30',
            'film_id' => $film->id,
            'capacity' => 6,
            'status' => 'tersedia',
        ]);
    }

    public function test_home_page_query_budget(): void
    {
        DB::enableQueryLog();
        DB::flushQueryLog();

        $response = $this->get(route('home'));
        $response->assertOk();

        $queryCount = count(DB::getQueryLog());
        $this->assertLessThanOrEqual(15, $queryCount, "Home page exceeded query budget: {$queryCount} queries executed.");
    }

    public function test_film_catalog_query_budget(): void
    {
        DB::enableQueryLog();
        DB::flushQueryLog();

        $response = $this->get(route('films.index'));
        $response->assertOk();

        $queryCount = count(DB::getQueryLog());
        $this->assertLessThanOrEqual(10, $queryCount, "Film catalog exceeded query budget: {$queryCount} queries executed.");
    }

    public function test_schedule_page_query_budget(): void
    {
        DB::enableQueryLog();
        DB::flushQueryLog();

        $response = $this->get(route('schedule.index'));
        $response->assertOk();

        $queryCount = count(DB::getQueryLog());
        $this->assertLessThanOrEqual(40, $queryCount, "Schedule page exceeded query budget: {$queryCount} queries executed.");
    }

    public function test_film_detail_page_query_budget(): void
    {
        $film = Film::query()->first();

        DB::enableQueryLog();
        DB::flushQueryLog();

        $response = $this->get(route('films.show', $film->slug ?: $film->id));
        $response->assertOk();

        $queryCount = count(DB::getQueryLog());
        $this->assertLessThanOrEqual(10, $queryCount, "Film detail exceeded query budget: {$queryCount} queries executed.");
    }

    public function test_ticket_index_query_budget(): void
    {
        DB::enableQueryLog();
        DB::flushQueryLog();

        $response = $this->get(route('booking.ticket.index'));
        $response->assertOk();

        $queryCount = count(DB::getQueryLog());
        $this->assertLessThanOrEqual(10, $queryCount, "Ticket index exceeded query budget: {$queryCount} queries executed.");
    }

    public function test_booking_wizard_page_query_budget(): void
    {
        $session = Session::query()->first();

        DB::enableQueryLog();
        DB::flushQueryLog();

        $response = $this->get(route('booking.create', $session));
        $response->assertOk();

        $queryCount = count(DB::getQueryLog());
        $this->assertLessThanOrEqual(35, $queryCount, "Booking wizard exceeded query budget: {$queryCount} queries executed.");
    }
}
