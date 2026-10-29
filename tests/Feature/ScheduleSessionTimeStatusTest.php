<?php

namespace Tests\Feature;

use App\Models\Film;
use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleSessionTimeStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_time_status_progression(): void
    {
        $film = Film::create([
            'title' => 'Film Uji',
            'slug' => 'film-uji',
            'synopsis' => 'Sinopsis film uji',
            'genre' => 'Aksi',
            'duration_minutes' => 120,
            'age_rating' => '13+',
        ]);

        $session = Session::create([
            'session_date' => '2026-10-28',
            'start_time' => '19:30:00',
            'end_time' => '21:45:00',
            'film_id' => $film->id,
            'capacity' => 6,
            'is_private' => false,
            'status' => 'tersedia',
        ]);

        // Case 1: Pukul 18:00 (sebelum mulai dan belum lewat batas cutoff 30 menit)
        Carbon::setTestNow(Carbon::parse('2026-10-28 18:00:00', config('app.timezone')));
        $status1800 = $session->statusWaktu(30);
        $this->assertTrue($status1800['can_book']);
        $this->assertFalse($status1800['is_past']);
        $this->assertSame('tersedia', $status1800['key']);
        $this->assertStringContainsString('Tersedia', $status1800['label']);

        // Case 2: Pukul 19:10 (lewat batas cutoff 30 menit sebelum 19:30, tapi belum mulai)
        Carbon::setTestNow(Carbon::parse('2026-10-28 19:10:00', config('app.timezone')));
        $status1910 = $session->statusWaktu(30);
        $this->assertFalse($status1910['can_book']);
        $this->assertSame('pemesanan_ditutup', $status1910['key']);
        $this->assertSame('Pemesanan ditutup', $status1910['label']);

        // Case 3: Pukul 20:20 (sesi sedang tayang antara 19:30 dan 21:45)
        // CRITICAL REQUIREMENT: Sesi 19:30 yang selesai 21:45 TIDAK boleh berlabel "Selesai" pada pukul 20:20.
        Carbon::setTestNow(Carbon::parse('2026-10-28 20:20:00', config('app.timezone')));
        $status2020 = $session->statusWaktu(30);
        $this->assertFalse($status2020['can_book']);
        $this->assertTrue($status2020['is_past']);
        $this->assertSame('berlangsung', $status2020['key']);
        $this->assertSame('Berlangsung', $status2020['label']);
        $this->assertTrue($session->isOngoing());
        $this->assertFalse($session->isCompleted());

        // Case 4: Pukul 22:00 (setelah jam selesai 21:45)
        Carbon::setTestNow(Carbon::parse('2026-10-28 22:00:00', config('app.timezone')));
        $status2200 = $session->statusWaktu(30);
        $this->assertFalse($status2200['can_book']);
        $this->assertTrue($status2200['is_past']);
        $this->assertSame('selesai', $status2200['key']);
        $this->assertSame('Selesai', $status2200['label']);
        $this->assertFalse($session->isOngoing());
        $this->assertTrue($session->isCompleted());

        Carbon::setTestNow(); // Reset test clock
    }

    public function test_public_schedule_past_sessions_have_no_book_button(): void
    {
        $film = Film::create([
            'title' => 'Film Selesai',
            'slug' => 'film-selesai',
            'synopsis' => 'Sinopsis film selesai',
            'genre' => 'Drama',
            'duration_minutes' => 120,
            'age_rating' => 'SU',
        ]);

        $session = Session::create([
            'session_date' => '2026-10-28',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'film_id' => $film->id,
            'capacity' => 6,
            'is_private' => false,
            'status' => 'tersedia',
        ]);

        // Test at 14:00 (session at 10:00 is completed)
        Carbon::setTestNow(Carbon::parse('2026-10-28 14:00:00', config('app.timezone')));

        $this->assertSame(1, Session::where('session_date', '2026-10-28')->count());
        $response = $this->get('/jadwal/2026-10-28');
        $response->assertStatus(200);
        $response->assertSee('Sesi yang sudah lewat');
        // Past sessions must NEVER show booking link / button
        $response->assertDontSee('route(\'booking.create\', [\'session\' => '.$session->id, false);
        $this->assertStringNotContainsString('/pesan?session='.$session->id, $response->getContent());
        $this->assertStringNotContainsString('>Pesan tiket</a>', $response->getContent());

        Carbon::setTestNow();
    }
}
