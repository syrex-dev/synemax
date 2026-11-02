<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Session;
use App\Models\Setting;
use App\Services\CinemaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class DoubleArrowDetectorTest extends TestCase
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
     * Architectural scan: ensure no public views contain raw "←" or double arrow entities.
     */
    public function test_public_views_contain_no_raw_left_arrow_or_double_arrows(): void
    {
        $directories = [
            resource_path('views/public'),
            resource_path('views/components/public'),
        ];

        $violatingFiles = [];
        $pattern = '/(?:←←|<<|&larr;&larr;|&laquo;|←)/u';

        foreach ($directories as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($files as $file) {
                if ($file->isDir() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                    continue;
                }

                $content = file_get_contents($file->getPathname());
                // Strip blade comments
                $clean = preg_replace('/{{--.*?--}}/s', '', $content);

                if (preg_match_all($pattern, $clean, $matches)) {
                    $violatingFiles[$file->getPathname()] = array_unique($matches[0]);
                }
            }
        }

        $this->assertEmpty(
            $violatingFiles,
            'Public views must not contain raw left arrow "←" or double arrow glyphs: '.json_encode($violatingFiles)
        );
    }

    /**
     * Runtime HTTP test: verify rendered public pages have no double arrows or SVG adjacent to "←".
     */
    public function test_rendered_public_pages_have_no_double_arrows(): void
    {
        $film = Film::query()->create([
            'title' => 'Film QA Double Arrow',
            'slug' => 'film-qa-double-arrow-'.uniqid(),
            'duration_minutes' => 90,
            'genre' => 'Drama',
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis',
            'is_active' => true,
        ]);

        $session = Session::query()->create([
            'film_id' => $film->id,
            'session_date' => now()->addDays(2)->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'status' => 'pasti_tayang',
        ]);

        $booking = Booking::query()->create([
            'session_id' => $session->id,
            'code' => 'DA'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'QA Arrow Tester',
            'whatsapp' => '6281234567891',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'payment_method' => 'qris',
            'status' => 'menunggu_pembayaran',
            'total_price' => 40000,
            'payment_expires_at' => now()->addMinutes(30),
        ]);

        $confirmedBooking = Booking::query()->create([
            'session_id' => $session->id,
            'code' => 'CF'.strtoupper(substr(uniqid(), -6)),
            'customer_name' => 'QA Confirmed Tester',
            'whatsapp' => '6281234567892',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'status' => 'terkonfirmasi',
            'total_price' => 40000,
        ]);

        $doubleArrowPattern = '/(?:←←|<<|&larr;&larr;|&laquo;|<svg[^>]*>.*?<\/svg>\s*←|←\s*<svg[^>]*>.*?<\/svg>)/si';

        // 1. /tiket (index)
        $resp = $this->get(route('booking.ticket.index'));
        $resp->assertOk();
        $this->assertDoesNotMatch($doubleArrowPattern, $resp->getContent(), 'Page /tiket contains double arrow');

        // 2. /pembayaran/{code}
        $resp = $this->withSession(['verified_booking_'.$booking->code => true])
            ->get(route('booking.payment', ['code' => $booking->code]));
        $resp->assertOk();
        $this->assertDoesNotMatch($doubleArrowPattern, $resp->getContent(), 'Page /pembayaran contains double arrow');

        // 3. /tiket/{code}
        $resp = $this->withSession(['verified_booking_'.$confirmedBooking->code => true])
            ->get(route('booking.ticket', ['code' => $confirmedBooking->code]));
        $resp->assertOk();
        $this->assertDoesNotMatch($doubleArrowPattern, $resp->getContent(), 'Page /tiket/{code} contains double arrow');

        // Test booking-status view directly
        $renderedStatus = (string) $this->view('public.booking-status', [
            'booking' => null,
            'searchAttempted' => false,
        ]);
        $this->assertDoesNotMatch($doubleArrowPattern, $renderedStatus, 'booking-status view contains double arrow or SVG adjacent to ←');
    }

    /**
     * Unit assertion to ensure our detector pattern accurately catches double arrow violations.
     */
    public function test_detector_catches_synthetic_double_arrow(): void
    {
        $doubleArrowPattern = '/(?:←←|<<|&larr;&larr;|&laquo;|<svg[^>]*>.*?<\/svg>\s*←|←\s*<svg[^>]*>.*?<\/svg>)/si';

        $invalidSnippets = [
            '<a href="/"><svg class="size-4"></svg> ← Beranda</a>',
            '<a href="/">← <svg class="size-4"></svg> Kembali</a>',
            '<span>←← Kembali</span>',
            '<span>&laquo; Kembali</span>',
            '<span><< Kembali</span>',
        ];

        foreach ($invalidSnippets as $snippet) {
            $this->assertMatchesRegularExpression($doubleArrowPattern, $snippet, "Expected detector to flag: {$snippet}");
        }

        $validSnippets = [
            '<a href="/"><svg class="size-4"></svg> Kembali ke Beranda</a>',
            '<a href="/">Kembali</a>',
        ];

        foreach ($validSnippets as $snippet) {
            $this->assertDoesNotMatch($doubleArrowPattern, $snippet, "Expected clean snippet to pass: {$snippet}");
        }
    }

    protected function assertDoesNotMatch(string $pattern, string $string, string $message = ''): void
    {
        $this->assertSame(0, preg_match($pattern, $string), $message);
    }
}
