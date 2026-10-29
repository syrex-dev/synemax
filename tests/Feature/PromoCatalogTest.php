<?php

namespace Tests\Feature;

use App\Models\Promo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_promo_generates_slug_automatically_on_creation(): void
    {
        $promo = Promo::create([
            'title' => 'Diskon Pelajar Akhir Pekan',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'is_active' => true,
        ]);

        $this->assertSame('diskon-pelajar-akhir-pekan', $promo->slug);

        // Test duplicate title gets unique slug suffix
        $promo2 = Promo::create([
            'title' => 'Diskon Pelajar Akhir Pekan',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $this->assertNotSame($promo->slug, $promo2->slug);
        $this->assertStringStartsWith('diskon-pelajar-akhir-pekan-', $promo2->slug);
    }

    public function test_promo_catalog_displays_active_promos_and_dims_expired_ones(): void
    {
        $activePromo = Promo::create([
            'title' => 'Promo Aktif Berjalan',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'is_active' => true,
            'description' => 'Deskripsi promo aktif.',
        ]);

        $expiredPromo = Promo::create([
            'title' => 'Promo Telah Berakhir',
            'start_date' => now()->subDays(20)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'is_active' => true,
            'description' => 'Deskripsi promo kedaluwarsa.',
        ]);

        $response = $this->get(route('promos.index'));

        $response->assertOk();
        $response->assertSee('Promo Berlangsung');
        $response->assertSee('Promo Aktif Berjalan');
        $response->assertSee('Promo Telah Berakhir');
        $response->assertSee('Berakhir');
    }

    public function test_expired_promo_does_not_appear_on_homepage(): void
    {
        $activePromo = Promo::create([
            'title' => 'Promo Spesial Beranda',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'is_active' => true,
        ]);

        $expiredPromo = Promo::create([
            'title' => 'Promo Musim Lalu Yang Berakhir',
            'start_date' => now()->subDays(20)->toDateString(),
            'end_date' => now()->subDays(2)->toDateString(),
            'is_active' => true,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Promo Spesial Beranda');
        $response->assertDontSee('Promo Musim Lalu Yang Berakhir');
    }

    public function test_promo_detail_page_renders_cgv_layout_with_terms_and_booking_cta(): void
    {
        $promo = Promo::create([
            'title' => 'Paket Combo Nonton Berdua',
            'start_date' => now()->subDays(1)->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'description' => 'Nikmati paket hemat berdua bersama sahabat.',
            'terms_conditions' => "1. Berlaku untuk 2 tiket.\n2. Tidak dapat ditukar uang.\n3. Harap datang 15 menit sebelum sesi.",
            'is_active' => true,
        ]);

        $response = $this->get(route('promos.show', $promo->slug));

        $response->assertOk();
        $response->assertSee('Paket Combo Nonton Berdua');
        $response->assertSee('Syarat & Ketentuan', false);
        $response->assertSee('Berlaku untuk 2 tiket.');
        $response->assertSee('Tidak dapat ditukar uang.');
        $response->assertSee('Pesan Tiket Sekarang');
    }
}
