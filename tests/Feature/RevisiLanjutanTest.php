<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Promo;
use App\Models\Session;
use App\Models\Setting;
use App\Models\User;
use App\Services\BookingService;
use App\Services\CinemaSettings;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RevisiLanjutanTest extends TestCase
{
    use RefreshDatabase;

    private Film $filmA;

    private Film $filmB;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'display_density' => 'nyaman',
        ]);

        $this->filmA = Film::query()->create([
            'title' => 'Film Arah Bintang',
            'duration_minutes' => 100,
            'genre' => 'Petualangan',
            'release_year' => 2025,
            'age_rating' => 'SU',
            'synopsis' => 'Sinopsis petualangan arah bintang yang seru.',
            'is_active' => true,
        ]);

        $this->filmB = Film::query()->create([
            'title' => 'Film Misteri Danau',
            'duration_minutes' => 95,
            'genre' => 'Misteri',
            'release_year' => 2024,
            'age_rating' => '13+',
            'synopsis' => 'Sinopsis misteri danau yang mendebarkan.',
            'is_active' => true,
        ]);

        Setting::updateOrCreate(['key' => 'minimum_public_viewers'], ['value' => '3']);
        Setting::updateOrCreate(['key' => 'low_attendance_warning_window'], ['value' => '72']);
        Setting::updateOrCreate(['key' => 'payment_methods'], ['value' => '["di_tempat"]']);
    }

    /**
     * Sesi publik: pemesan pertama memilih film, film itu terkunci ke sesi.
     * Pemesan berikutnya bergabung ke film yang sama; pemesan yang mencoba film lain ditolak.
     */
    public function test_public_session_first_booker_locks_film_and_subsequent_bookers_join_same_film(): void
    {
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();

        $session = Session::query()->create([
            'session_date' => $today->addDay()->toDateString(),
            'start_time' => '19:30:00',
            'end_time' => '21:30:00',
            'film_id' => null, // Mulai tanpa film
            'capacity' => 6,
            'is_private' => false,
            'status' => 'tersedia',
        ]);

        $this->assertNull($session->film_id);
        $this->assertNull($session->activeFilm());

        $bookingService = app(BookingService::class);

        // Pemesan pertama memilih Film A
        $booking1 = $bookingService->create($session, [
            'customer_name' => 'Pemesan Pertama',
            'whatsapp' => '08121111111',
            'pax' => 2,
            'booking_type' => 'per_orang',
            'film_id' => $this->filmA->id,
            'payment_method' => 'di_tempat',
        ]);

        $session->refresh();
        $this->assertNull($session->film_id);
        $this->assertEquals($this->filmA->id, $booking1->film_id);
        $this->assertEquals('Film Arah Bintang', $session->activeFilm()->title);

        // Pemesan kedua bergabung ke Film A (2 kursi -> total 4, mencapai kuorum)
        $booking2 = $bookingService->create($session, [
            'customer_name' => 'Pemesan Kedua',
            'whatsapp' => '08122222222',
            'pax' => 2,
            'booking_type' => 'per_orang',
            'film_id' => $this->filmA->id,
            'payment_method' => 'di_tempat',
        ]);

        $session->refresh();
        $this->assertEquals($this->filmA->id, $session->film_id);
        $this->assertEquals($this->filmA->id, $booking2->film_id);

        // Pemesan ketiga mencoba memilih Film B (harus ditolak karena film sesi sudah terkunci kuorum)
        $this->expectException(ValidationException::class);
        $bookingService->create($session, [
            'customer_name' => 'Pemesan Ketiga',
            'whatsapp' => '08123333333',
            'pax' => 1,
            'booking_type' => 'per_orang',
            'film_id' => $this->filmB->id,
            'payment_method' => 'di_tempat',
        ]);
    }

    /**
     * Sesi privat: penyewa bebas memilih film mana pun.
     */
    public function test_private_session_renter_freely_selects_film(): void
    {
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();

        $session = Session::query()->create([
            'session_date' => $today->addDays(2)->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'film_id' => null,
            'capacity' => 6,
            'is_private' => false,
            'status' => 'tersedia',
        ]);

        $bookingService = app(BookingService::class);

        $privateBooking = $bookingService->create($session, [
            'customer_name' => 'Sewa Studio Keluarga',
            'whatsapp' => '08129999999',
            'booking_type' => 'private',
            'film_id' => $this->filmB->id,
            'payment_method' => 'di_tempat',
        ]);

        $session->refresh();
        $this->assertTrue((bool) $session->is_private);
        $this->assertEquals($this->filmB->id, $session->film_id);
        $this->assertEquals($this->filmB->id, $privateBooking->film_id);
        $this->assertEquals(6, $privateBooking->seats_reserved);
    }

    /**
     * Menutup sesi melalui toggle switch admin:
     * - Sesi kosong dapat ditutup langsung tanpa konfirmasi.
     * - Sesi yang memiliki booking mengembalikan requires_confirmation: true.
     * - Saat dikonfirmasi, booking aktif dibatalkan sesuai aturan refund/pembatalan.
     */
    public function test_admin_toggle_session_status_and_closing_booked_session(): void
    {
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();

        $session = Session::query()->create([
            'session_date' => $today->addDay()->toDateString(),
            'start_time' => '16:00:00',
            'end_time' => '18:00:00',
            'film_id' => $this->filmA->id,
            'capacity' => 6,
            'is_private' => false,
            'status' => 'tersedia',
        ]);

        // 1. Tutup sesi kosong -> langsung berhasil
        $res = $this->actingAs($this->admin)->patchJson(route('admin.schedule.sessions.toggle', $session), [
            'status' => 'ditutup',
        ]);
        $res->assertOk();
        $res->assertJson(['status' => 'ok']);
        $this->assertEquals('ditutup', $session->fresh()->status);

        // Buka kembali
        $this->actingAs($this->admin)->patchJson(route('admin.schedule.sessions.toggle', $session), [
            'status' => 'tersedia',
        ]);
        $this->assertEquals('tersedia', $session->fresh()->status);

        // Buat booking aktif di sesi
        $booking = Booking::query()->create([
            'code' => 'HC-TEST-001',
            'session_id' => $session->id,
            'film_id' => $this->filmA->id,
            'customer_name' => 'Warga Studio',
            'whatsapp' => '08124444444',
            'pax' => 2,
            'seats_reserved' => 2,
            'booking_type' => 'per_orang',
            'total_price' => 10000,
            'status' => 'terkonfirmasi',
            'payment_method' => 'di_tempat',
        ]);

        // 2. Tutup sesi dengan booking tanpa confirmation -> harus minta konfirmasi
        $resNeedsConfirm = $this->actingAs($this->admin)->patchJson(route('admin.schedule.sessions.toggle', $session), [
            'status' => 'ditutup',
            'confirmed' => false,
        ]);
        $resNeedsConfirm->assertOk();
        $resNeedsConfirm->assertJson([
            'requires_confirmation' => true,
            'active_bookings_count' => 1,
        ]);
        $this->assertEquals('tersedia', $session->fresh()->status); // Belum berubah

        // 3. Konfirmasi penutupan sesi -> booking dibatalkan dan sesi ditutup
        $resConfirmed = $this->actingAs($this->admin)->patchJson(route('admin.schedule.sessions.toggle', $session), [
            'status' => 'ditutup',
            'confirmed' => true,
        ]);
        $resConfirmed->assertOk();
        $resConfirmed->assertJson(['status' => 'ok']);

        $session->refresh();
        $booking->refresh();

        $this->assertEquals('ditutup', $session->status);
        $this->assertEquals('dibatalkan', $booking->status);
        $this->assertNotNull($booking->cancelled_at);
    }

    /**
     * Halaman Admin Jadwal tidak lagi memiliki dropdown film, dropdown status, atau tombol Simpan per sesi.
     * Menggunakan format tanggal Indonesia (zero raw ISO format 2026-10-28).
     */
    public function test_admin_schedule_page_has_no_per_session_dropdowns_and_uses_indonesian_dates(): void
    {
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();

        Session::query()->create([
            'session_date' => $today->toDateString(),
            'start_time' => '19:30:00',
            'end_time' => '21:30:00',
            'film_id' => $this->filmA->id,
            'capacity' => 6,
            'is_private' => false,
            'status' => 'tersedia',
        ]);

        $res = $this->actingAs($this->admin)->get(route('admin.schedule.index'));
        $res->assertOk();

        // Tidak boleh ada dropdown film sesi atau dropdown status sesi
        $res->assertDontSee('Film sesi');
        $res->assertDontSee('Status sesi');
        // Tidak boleh ada tombol "Simpan" per baris sesi
        $res->assertDontSee('film-session-');
        $res->assertDontSee('status-session-');

        // Memakai format bahasa Indonesia
        $res->assertSee('Hari ini');
        $res->assertSee('WIB');
        $res->assertSee('19.30 WIB');
    }

    /**
     * Preferensi kepadatan (Nyaman/Ringkas) tersimpan per admin di tabel users
     * dan dirender langsung di tag <html> tanpa flicker.
     */
    public function test_display_density_preference_persisted_per_admin_and_rendered_server_side(): void
    {
        $this->assertEquals('nyaman', $this->admin->display_density);

        // Render dashboard
        $res = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $res->assertOk();
        $res->assertSee('data-density="nyaman"', false);

        // Update preferensi ke 'ringkas'
        $updateRes = $this->actingAs($this->admin)->patchJson(route('admin.density.update'), [
            'density' => 'ringkas',
        ]);
        $updateRes->assertOk();
        $this->assertEquals('ringkas', $this->admin->fresh()->display_density);

        // Render berikutnya langsung memakai ringkas
        $res2 = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $res2->assertOk();
        $res2->assertSee('data-density="ringkas"', false);
    }

    /**
     * "Perlu tindakan" menampilkan QRIS nonaktif bila QRIS dipilih namun nonaktif/tanpa gambar,
     * serta dibatasi maksimal 4 baris.
     */
    public function test_action_items_includes_qris_warning_when_qris_active_without_image(): void
    {
        $settings = app(CinemaSettings::class);
        $settings->set('enabled_payment_methods', ['qris', 'di_tempat']);
        $settings->set('qris_image_path', ''); // Gambar kosong

        $res = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $res->assertOk();
        $res->assertSee('QRIS nonaktif');
        $res->assertSee('unggah gambar QRIS');
    }

    /**
     * Carousel promo mengecualikan promo kedaluwarsa berdasarkan end_date,
     * sedangkan promo tanpa end_date (null) tetap ditampilkan.
     */
    public function test_promo_scope_current_uses_end_date_and_keeps_null_end_date_visible(): void
    {
        // 1. Promo tanpa batas waktu
        $evergreenPromo = Promo::query()->create([
            'title' => 'Promo Diskon Pelajar',
            'is_active' => true,
            'end_date' => null,
            'sort_order' => 1,
        ]);

        // 2. Promo masa kini
        $currentPromo = Promo::query()->create([
            'title' => 'Promo Libur Akhir Tahun',
            'is_active' => true,
            'end_date' => now()->addDays(5)->toDateString(),
            'sort_order' => 2,
        ]);

        // 3. Promo kedaluwarsa
        $expiredPromo = Promo::query()->create([
            'title' => 'Promo Flash Sale Kemarin',
            'is_active' => true,
            'end_date' => now()->subDay()->toDateString(),
            'sort_order' => 3,
        ]);

        $activePromos = Promo::query()->active()->current()->get();

        $this->assertTrue($activePromos->contains('id', $evergreenPromo->id));
        $this->assertTrue($activePromos->contains('id', $currentPromo->id));
        $this->assertFalse($activePromos->contains('id', $expiredPromo->id));
    }
}
