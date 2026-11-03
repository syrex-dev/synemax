<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\NewsArticle;
use App\Models\NobarEvent;
use App\Models\Promo;
use App\Models\Session;
use App\Support\DeviceBookingTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoHomeLinksInPublicContentTest extends TestCase
{
    use RefreshDatabase;

    private function extractMainContent(string $html): string
    {
        if (preg_match('/<main[^>]*>(.*?)<\/main>/is', $html, $matches)) {
            return $matches[1];
        }

        return $html;
    }

    public function test_no_home_links_in_content_on_public_pages(): void
    {
        $film = Film::query()->create([
            'title' => 'Film Uji Tanpa Beranda',
            'slug' => 'film-uji-tanpa-beranda',
            'duration_minutes' => 95,
            'genre' => 'Drama',
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis film uji.',
            'is_active' => true,
        ]);

        $session = Session::query()->create([
            'film_id' => $film->id,
            'session_date' => now()->addDays(2)->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'status' => 'pasti_tayang',
        ]);

        $promo = Promo::query()->create([
            'title' => 'Promo Diskon Uji',
            'slug' => 'promo-diskon-uji',
            'description' => 'Deskripsi promo uji',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'is_active' => true,
        ]);

        $nobar = NobarEvent::query()->create([
            'judul' => 'Nobar Final Sepakbola Uji',
            'slug' => 'nobar-final-sepakbola-uji',
            'tim_a' => 'Tim A',
            'tim_b' => 'Tim B',
            'kickoff_at' => now()->addDays(3)->setTime(20, 0),
            'lokasi_nama' => 'Studio Mini SynemaX',
            'alamat_patokan' => 'Patokan Lokasi Uji',
            'maps_url' => 'https://maps.google.com',
            'status_publikasi' => 'terbit',
            'status_acara' => 'aktif',
        ]);

        $article = NewsArticle::query()->create([
            'judul' => 'Kabar Studio Terkini Uji',
            'slug' => 'kabar-studio-terkini-uji',
            'kategori' => 'Berita',
            'ringkasan' => 'Ringkasan kabar',
            'konten' => 'Konten kabar studio',
            'status_publikasi' => 'terbit',
            'published_at' => now()->subHours(2),
        ]);

        $bookingUnpaid = Booking::query()->create([
            'session_id' => $session->id,
            'code' => 'HC-TEST-UNPAID',
            'customer_name' => 'User Unpaid',
            'whatsapp' => '6281299990001',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'payment_method' => 'qris',
            'status' => 'menunggu_pembayaran',
            'total_price' => 40000,
            'payment_expires_at' => now()->addMinutes(20),
        ]);

        $bookingConfirmed = Booking::query()->create([
            'session_id' => $session->id,
            'code' => 'HC-TEST-CONFIRMED',
            'customer_name' => 'User Confirmed',
            'whatsapp' => '6281299990002',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'payment_method' => 'qris',
            'status' => 'terkonfirmasi',
            'total_price' => 40000,
        ]);

        $routesToTest = [
            route('films.index'),
            route('films.show', ['slug' => $film->slug]),
            route('promos.index'),
            route('promos.show', ['slug' => $promo->slug]),
            route('nobar.index'),
            route('nobar.show', ['slug' => $nobar->slug]),
            route('articles.index'),
            route('articles.show', ['slug' => $article->slug]),
            route('schedule.index'),
            route('booking.ticket.index'),
            route('booking.payment', ['code' => $bookingUnpaid->code]),
            route('booking.ticket', ['code' => $bookingConfirmed->code]),
        ];

        $homeUrl = route('home');
        $forbiddenTexts = [
            'Kembali ke Beranda',
            'Kembali ke beranda',
            '← Beranda',
            '← Kembali ke Beranda',
        ];

        $cookiePayload = json_encode([
            ['code' => $bookingUnpaid->code, 'token' => 'tok-unpaid', 'created_at' => time()],
            ['code' => $bookingConfirmed->code, 'token' => 'tok-confirmed', 'created_at' => time()],
        ]);

        foreach ($routesToTest as $url) {
            $response = $this->withCookie(DeviceBookingTracker::COOKIE_NAME, $cookiePayload)
                ->withSession([
                    'verified_booking_'.$bookingUnpaid->code => true,
                    'verified_booking_'.$bookingConfirmed->code => true,
                ])
                ->get($url);

            $response->assertOk();
            $content = $this->extractMainContent($response->getContent());

            foreach ($forbiddenTexts as $forbidden) {
                $this->assertStringNotContainsString(
                    $forbidden,
                    $content,
                    "Forbidden text '{$forbidden}' found in <main> content of {$url}"
                );
            }

            // Ensure no breadcrumb linking to home inside <main>
            $this->assertDoesNotMatchRegularExpression(
                '/<a[^>]+href=["\'](?:'.preg_quote($homeUrl, '/').'|\/)["\'][^>]*>.*?(?:Beranda|Home).*?<\/a>/is',
                $content,
                "Found link to home inside <main> content of {$url}"
            );
        }
    }
}
