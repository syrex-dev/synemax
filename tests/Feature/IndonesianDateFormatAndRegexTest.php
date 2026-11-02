<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Session;
use App\Models\Setting;
use App\Services\CinemaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndonesianDateFormatAndRegexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['key' => 'seats_capacity'], ['value' => '6']);
        Setting::updateOrCreate(['key' => 'minimum_public_viewers'], ['value' => '4']);
        Setting::updateOrCreate(['key' => 'weekday_price'], ['value' => '20000']);
        Setting::updateOrCreate(['key' => 'weekend_price'], ['value' => '24000']);
        Setting::updateOrCreate(['key' => 'payment_methods'], ['value' => 'di_tempat,transfer,qris']);
        app(CinemaSettings::class)->clearCache();
    }

    /**
     * Test helper functions for Indonesian date, time, and range formats.
     */
    public function test_helpers_produce_strict_indonesian_date_and_time(): void
    {
        // Time format: must use dot, not colon, with WIB
        $this->assertSame('13.00 WIB', format_jam_id('13:00:00'));
        $this->assertSame('13.00', format_jam_id('13:00:00', false));
        $this->assertSame('09.15 WIB', format_jam_id('09:15:00'));
        $this->assertSame('21.45 WIB', format_jam_id('21:45:00'));

        // Single digit date: strictly no leading zero (3 Nov, not 03 Nov)
        $this->assertSame('Sel, 3 Nov 2026', format_tanggal_id('2026-11-03'));
        $this->assertSame('3 Nov', format_tanggal_pendek_id('2026-11-03'));
        $this->assertSame('Selasa, 3 November 2026', format_hari_tanggal_id('2026-11-03'));

        // Two digit date
        $this->assertSame('Rab, 28 Okt 2026', format_tanggal_id('2026-10-28'));
        $this->assertSame('28 Okt', format_tanggal_pendek_id('2026-10-28'));
        $this->assertSame('Rabu, 28 Oktober 2026', format_hari_tanggal_id('2026-10-28'));

        // Time range
        $this->assertSame('13.00 - 15.00 WIB', format_rentang_jam_id('13:00:00', '15:00:00'));
        $this->assertSame('13.00 - 15.00', format_rentang_jam_id('13:00:00', '15:00:00', false));
    }

    /**
     * Test that rendered public pages comply with the dot separator and no-leading-zero rules.
     */
    public function test_rendered_pages_comply_with_indonesian_date_time_rules(): void
    {
        $film = Film::query()->create([
            'title' => 'Film QA Formatting',
            'slug' => 'film-qa-formatting-'.uniqid(),
            'duration_minutes' => 120,
            'genre' => 'Petualangan',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis film untuk uji format tanggal dan jam.',
            'is_active' => true,
        ]);

        // Pick single-digit day: 3rd day of next month
        $singleDigitDate = now()->addMonth()->startOfMonth()->addDays(2)->toDateString(); // Day 3

        $availableSession = Session::query()->create([
            'film_id' => $film->id,
            'session_date' => $singleDigitDate,
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'status' => 'tersedia',
        ]);

        $bookingSession = Session::query()->create([
            'film_id' => $film->id,
            'session_date' => $singleDigitDate,
            'start_time' => '16:00:00',
            'end_time' => '18:00:00',
            'status' => 'pasti_tayang',
        ]);

        $unpaidBooking = Booking::query()->create([
            'session_id' => $bookingSession->id,
            'code' => 'DT'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'QA Date Tester',
            'whatsapp' => '6281234567893',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'payment_method' => 'qris',
            'status' => 'menunggu_pembayaran',
            'total_price' => 40000,
            'payment_expires_at' => now()->addMinutes(30),
        ]);

        $confirmedBooking = Booking::query()->create([
            'session_id' => $bookingSession->id,
            'code' => 'DC'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'QA Date Confirmed',
            'whatsapp' => '6281234567894',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'payment_method' => 'qris',
            'status' => 'terkonfirmasi',
            'total_price' => 40000,
        ]);

        $colonTimeWibPattern = '/\b\d{1,2}:\d{2}\s*WIB/i';
        $leadingZeroDatePattern = '/\b0[1-9]\s+(Jan|Feb|Mar|Apr|Mei|Jun|Jul|Agu|Agt|Sep|Okt|Nov|Des)/i';

        // 1. Home / Schedule
        $resp = $this->get(route('home'));
        $resp->assertOk();
        $this->assertDoesNotMatch($colonTimeWibPattern, $resp->getContent(), 'Home contains colon in time with WIB');
        $this->assertDoesNotMatch($leadingZeroDatePattern, $resp->getContent(), 'Home contains leading zero date');

        // 2. Booking create form
        $resp = $this->get(route('booking.create', ['session' => $availableSession->id]));
        $resp->assertOk();
        $this->assertDoesNotMatch($colonTimeWibPattern, $resp->getContent(), 'Booking form contains colon in time with WIB');
        $this->assertDoesNotMatch($leadingZeroDatePattern, $resp->getContent(), 'Booking form contains leading zero date');

        // 3. Payment page
        $resp = $this->withSession(['verified_booking_'.$unpaidBooking->code => true])
            ->get(route('booking.payment', ['code' => $unpaidBooking->code]));
        $resp->assertOk();
        $this->assertDoesNotMatch($colonTimeWibPattern, $resp->getContent(), 'Payment view contains colon in time with WIB');
        $this->assertDoesNotMatch($leadingZeroDatePattern, $resp->getContent(), 'Payment view contains leading zero date');

        // 4. Ticket page
        $resp = $this->withSession(['verified_booking_'.$confirmedBooking->code => true])
            ->get(route('booking.ticket', ['code' => $confirmedBooking->code]));
        $resp->assertOk();
        $this->assertDoesNotMatch($colonTimeWibPattern, $resp->getContent(), 'Ticket view contains colon in time with WIB');
        $this->assertDoesNotMatch($leadingZeroDatePattern, $resp->getContent(), 'Ticket view contains leading zero date');

        // 5. /tiket index
        $resp = $this->withSession(['verified_booking_'.$confirmedBooking->code => true])
            ->get(route('booking.ticket.index'));
        $resp->assertOk();
        $this->assertDoesNotMatch($colonTimeWibPattern, $resp->getContent(), 'Ticket index contains colon in time with WIB');
        $this->assertDoesNotMatch($leadingZeroDatePattern, $resp->getContent(), 'Ticket index contains leading zero date');
    }

    /**
     * Unit assertion to ensure our regex strictly catches colon times and leading zeros.
     */
    public function test_regex_detector_catches_invalid_formats(): void
    {
        $colonTimeWibPattern = '/\b\d{1,2}:\d{2}\s*WIB/i';
        $leadingZeroDatePattern = '/\b0[1-9]\s+(Jan|Feb|Mar|Apr|Mei|Jun|Jul|Agu|Agt|Sep|Okt|Nov|Des)/i';

        $invalidTimes = ['13:00 WIB', '09:30 WIB', '19:45 WIB'];
        foreach ($invalidTimes as $time) {
            $this->assertMatchesRegularExpression($colonTimeWibPattern, $time);
        }

        $validTimes = ['13.00 WIB', '09.30 WIB', '19.45 WIB'];
        foreach ($validTimes as $time) {
            $this->assertDoesNotMatch($colonTimeWibPattern, $time);
        }

        $invalidDates = ['03 Nov 2026', '01 Jan 2026', '09 Des 2026'];
        foreach ($invalidDates as $date) {
            $this->assertMatchesRegularExpression($leadingZeroDatePattern, $date);
        }

        $validDates = ['3 Nov 2026', '1 Jan 2026', '9 Des 2026', '28 Okt 2026'];
        foreach ($validDates as $date) {
            $this->assertDoesNotMatch($leadingZeroDatePattern, $date);
        }
    }

    protected function assertDoesNotMatch(string $pattern, string $string, string $message = ''): void
    {
        preg_match_all($pattern, $string, $matches);
        $this->assertEmpty($matches[0], $message.' Matches: '.json_encode($matches[0]));
    }
}
