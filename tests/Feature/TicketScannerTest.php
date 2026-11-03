<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Session;
use App\Models\Setting;
use App\Models\User;
use App\Services\CinemaSettings;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class TicketScannerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $regularUser;

    protected Film $film;

    protected Session $todaySession;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('scan_failed:1');
        RateLimiter::clear('scan_failed:2');

        Setting::updateOrCreate(['key' => 'seats_capacity'], ['value' => '6']);
        Setting::updateOrCreate(['key' => 'quorum_min_seats'], ['value' => '3']);
        Setting::updateOrCreate(['key' => 'minimum_public_viewers'], ['value' => '3']);
        Setting::updateOrCreate(['key' => 'ticket_unit_price'], ['value' => '25000']);
        Setting::updateOrCreate(['key' => 'couple_package_price'], ['value' => '65000']);
        Setting::updateOrCreate(['key' => 'private_package_price_weekday'], ['value' => '150000']);
        Setting::updateOrCreate(['key' => 'private_package_price_weekend'], ['value' => '175000']);
        Setting::updateOrCreate(['key' => 'check_in_open_minutes_before'], ['value' => '60']);
        Setting::updateOrCreate(['key' => 'check_in_close_after'], ['value' => 'session_end']);
        Setting::updateOrCreate(['key' => 'check_in_sound_enabled'], ['value' => '1']);
        app(CinemaSettings::class)->clearCache();

        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->regularUser = User::factory()->create([
            'is_admin' => false,
        ]);

        $this->film = Film::query()->create([
            'title' => 'Film Pemindai Tiket QA',
            'slug' => 'film-pemindai-tiket-qa-'.uniqid(),
            'duration_minutes' => 90,
            'genre' => 'Aksi',
            'age_rating' => '13+',
            'synopsis' => 'Film untuk pengujian pemindai QR dan check-in',
            'is_active' => true,
        ]);

        // Today's active session: starts 30 mins ago, ends 60 mins from now
        $now = CarbonImmutable::now();
        $this->todaySession = Session::query()->create([
            'film_id' => $this->film->id,
            'session_date' => $now->toDateString(),
            'start_time' => $now->subMinutes(30)->format('H:i:s'),
            'end_time' => $now->addMinutes(60)->format('H:i:s'),
            'status' => 'tersedia',
            'confirmed_at' => $now,
        ]);
    }

    public function test_guest_is_redirected_from_scanner_page(): void
    {
        $response = $this->get(route('admin.scan.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_is_forbidden_from_scanner_page(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.scan.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_access_scanner_page_with_no_cache_headers(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.scan.index'));

        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $response->assertSee('Pemindai Tiket');
        $response->assertSee('Kamera');
        $response->assertSee('Kode');
        $response->assertSee('Daftar');
    }

    public function test_public_scan_alias_redirects_to_admin_scan(): void
    {
        $response = $this->actingAs($this->admin)->get('/scan');
        $response->assertRedirect(route('admin.scan.index'));
    }

    public function test_lookup_normalizes_url_and_finds_booking(): void
    {
        $booking = Booking::query()->create([
            'session_id' => $this->todaySession->id,
            'film_id' => $this->film->id,
            'code' => 'SNX-20261103-URL1',
            'customer_name' => 'Budi URL Scanner',
            'whatsapp' => '6281234567890',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'seats_reserved' => 2,
            'payment_method' => 'qris',
            'status' => 'terkonfirmasi',
            'total_price' => 50000,
            'ticket_total' => 50000,
        ]);

        $rawUrl = 'https://synemax.test/tiket/SNX-20261103-URL1?ref=qr#focus';

        $response = $this->actingAs($this->admin)->postJson(route('admin.scan.lookup'), [
            'code' => $rawUrl,
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'found',
            'verdict' => 'siap_hadir',
        ]);
        $response->assertJsonPath('preview.code', 'SNX-20261103-URL1');
        $response->assertJsonPath('preview.customer_name', 'Budi URL Scanner');
    }

    public function test_lookup_normalizes_lowercase_and_extra_spaces(): void
    {
        $booking = Booking::query()->create([
            'session_id' => $this->todaySession->id,
            'film_id' => $this->film->id,
            'code' => 'SNX-20261103-SPAC',
            'customer_name' => 'Siti Spasi',
            'whatsapp' => '6281234567891',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'seats_reserved' => 1,
            'payment_method' => 'cash',
            'status' => 'terkonfirmasi',
            'total_price' => 25000,
            'ticket_total' => 25000,
        ]);

        $rawInput = '  snx - 20261103 - spac  ';

        $response = $this->actingAs($this->admin)->postJson(route('admin.scan.lookup'), [
            'code' => $rawInput,
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'found',
            'verdict' => 'siap_hadir',
        ]);
        $response->assertJsonPath('preview.code', 'SNX-20261103-SPAC');
    }

    public function test_lookup_suffix_matching_single_and_ambiguous(): void
    {
        $booking1 = Booking::query()->create([
            'session_id' => $this->todaySession->id,
            'film_id' => $this->film->id,
            'code' => 'SNX-20261103-UNIQ',
            'customer_name' => 'Akun Unik Suffix',
            'whatsapp' => '6281234567892',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'seats_reserved' => 1,
            'payment_method' => 'cash',
            'status' => 'terkonfirmasi',
            'total_price' => 25000,
            'ticket_total' => 25000,
        ]);

        // Suffix 4 karakter cocok tunggal
        $resSingle = $this->actingAs($this->admin)->postJson(route('admin.scan.lookup'), [
            'code' => 'UNIQ',
            'session_id' => (string) $this->todaySession->id,
        ]);

        $resSingle->assertOk();
        $resSingle->assertJson([
            'status' => 'found',
            'verdict' => 'siap_hadir',
        ]);
        $resSingle->assertJsonPath('preview.code', 'SNX-20261103-UNIQ');

        // Buat 2 booking dengan akhiran sama DUPL
        Booking::query()->create([
            'session_id' => $this->todaySession->id,
            'film_id' => $this->film->id,
            'code' => 'SNX-20261103-1DUPL',
            'customer_name' => 'Duplikat 1',
            'whatsapp' => '6281234567893',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'seats_reserved' => 1,
            'payment_method' => 'cash',
            'status' => 'terkonfirmasi',
            'total_price' => 25000,
            'ticket_total' => 25000,
        ]);

        Booking::query()->create([
            'session_id' => $this->todaySession->id,
            'film_id' => $this->film->id,
            'code' => 'SNX-20261103-2DUPL',
            'customer_name' => 'Duplikat 2',
            'whatsapp' => '6281234567894',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'seats_reserved' => 1,
            'payment_method' => 'cash',
            'status' => 'terkonfirmasi',
            'total_price' => 25000,
            'ticket_total' => 25000,
        ]);

        $resAmbiguous = $this->actingAs($this->admin)->postJson(route('admin.scan.lookup'), [
            'code' => 'DUPL',
            'session_id' => (string) $this->todaySession->id,
        ]);

        $resAmbiguous->assertOk();
        $resAmbiguous->assertJson([
            'status' => 'ambiguous',
            'verdict' => 'ambigu',
        ]);
        $this->assertCount(2, $resAmbiguous->json('matches'));
    }

    public function test_lookup_verdicts(): void
    {
        // 1. Belum Bayar
        $unpaid = Booking::query()->create([
            'session_id' => $this->todaySession->id,
            'film_id' => $this->film->id,
            'code' => 'SNX-VERDICT-UNPAID',
            'customer_name' => 'Belum Lunas',
            'whatsapp' => '6281234567895',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'seats_reserved' => 1,
            'payment_method' => 'transfer',
            'status' => 'menunggu_pembayaran',
            'total_price' => 25000,
            'ticket_total' => 25000,
            'payment_expires_at' => now()->addMinutes(20),
        ]);

        $resUnpaid = $this->actingAs($this->admin)->postJson(route('admin.scan.lookup'), ['code' => $unpaid->code]);
        $resUnpaid->assertJsonPath('verdict', 'belum_bayar');

        // 2. Diproses
        $processing = Booking::query()->create([
            'session_id' => $this->todaySession->id,
            'film_id' => $this->film->id,
            'code' => 'SNX-VERDICT-PROC',
            'customer_name' => 'Sedang Diproses',
            'whatsapp' => '6281234567896',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'seats_reserved' => 1,
            'payment_method' => 'transfer',
            'payment_proof_path' => 'proofs/sample.jpg',
            'status' => 'menunggu_konfirmasi',
            'total_price' => 25000,
            'ticket_total' => 25000,
            'payment_expires_at' => now()->addMinutes(20),
        ]);

        $resProc = $this->actingAs($this->admin)->postJson(route('admin.scan.lookup'), ['code' => $processing->code]);
        $resProc->assertJsonPath('verdict', 'diproses');

        // 3. Sudah Hadir
        $attended = Booking::query()->create([
            'session_id' => $this->todaySession->id,
            'film_id' => $this->film->id,
            'code' => 'SNX-VERDICT-HADIR',
            'customer_name' => 'Sudah Masuk',
            'whatsapp' => '6281234567897',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'seats_reserved' => 1,
            'payment_method' => 'cash',
            'status' => 'hadir',
            'checked_in_at' => now()->subMinutes(10),
            'checked_in_by' => $this->admin->id,
            'check_in_method' => 'qr',
            'total_price' => 25000,
            'ticket_total' => 25000,
        ]);

        $resHadir = $this->actingAs($this->admin)->postJson(route('admin.scan.lookup'), ['code' => $attended->code]);
        $resHadir->assertJsonPath('verdict', 'sudah_hadir');

        // 4. Dibatalkan
        $cancelled = Booking::query()->create([
            'session_id' => $this->todaySession->id,
            'film_id' => $this->film->id,
            'code' => 'SNX-VERDICT-BATAL',
            'customer_name' => 'Pesanan Batal',
            'whatsapp' => '6281234567898',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'seats_reserved' => 1,
            'payment_method' => 'cash',
            'status' => 'dibatalkan',
            'total_price' => 25000,
            'ticket_total' => 25000,
        ]);

        $resBatal = $this->actingAs($this->admin)->postJson(route('admin.scan.lookup'), ['code' => $cancelled->code]);
        $resBatal->assertJsonPath('verdict', 'dibatalkan');

        // 5. Kedaluwarsa
        $expired = Booking::query()->create([
            'session_id' => $this->todaySession->id,
            'film_id' => $this->film->id,
            'code' => 'SNX-VERDICT-EXPD',
            'customer_name' => 'Tiket Expired',
            'whatsapp' => '6281234567899',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'seats_reserved' => 1,
            'payment_method' => 'transfer',
            'status' => 'kedaluwarsa',
            'total_price' => 25000,
            'ticket_total' => 25000,
            'payment_expires_at' => now()->subMinutes(10),
        ]);

        $resExpd = $this->actingAs($this->admin)->postJson(route('admin.scan.lookup'), ['code' => $expired->code]);
        $resExpd->assertJsonPath('verdict', 'kedaluwarsa');

        // 6. Tidak Datang
        $noShow = Booking::query()->create([
            'session_id' => $this->todaySession->id,
            'film_id' => $this->film->id,
            'code' => 'SNX-VERDICT-NOSHOW',
            'customer_name' => 'No Show',
            'whatsapp' => '6281234567800',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'seats_reserved' => 1,
            'payment_method' => 'cash',
            'status' => 'tidak_datang',
            'total_price' => 25000,
            'ticket_total' => 25000,
        ]);

        $resNoShow = $this->actingAs($this->admin)->postJson(route('admin.scan.lookup'), ['code' => $noShow->code]);
        $resNoShow->assertJsonPath('verdict', 'tidak_datang');

        // 7. Sesi Lain (Besok)
        $tomorrowSession = Session::query()->create([
            'film_id' => $this->film->id,
            'session_date' => now()->addDay()->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'status' => 'tersedia',
            'confirmed_at' => now(),
        ]);

        $diffSession = Booking::query()->create([
            'session_id' => $tomorrowSession->id,
            'film_id' => $this->film->id,
            'code' => 'SNX-VERDICT-DIFF',
            'customer_name' => 'Sesi Besok',
            'whatsapp' => '6281234567801',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'seats_reserved' => 1,
            'payment_method' => 'cash',
            'status' => 'terkonfirmasi',
            'total_price' => 25000,
            'ticket_total' => 25000,
        ]);

        $resDiff = $this->actingAs($this->admin)->postJson(route('admin.scan.lookup'), ['code' => $diffSession->code]);
        $resDiff->assertJsonPath('verdict', 'sesi_lain');
        $this->assertTrue($resDiff->json('preview.can_override'));

        // 8. Tidak Ditemukan
        $resNotFound = $this->actingAs($this->admin)->postJson(route('admin.scan.lookup'), ['code' => 'SNX-TIDAK-ADA']);
        $resNotFound->assertJsonPath('verdict', 'tidak_ditemukan');
    }

    public function test_successful_check_in_sets_status_timestamp_user_and_creates_audit_record(): void
    {
        $booking = Booking::query()->create([
            'session_id' => $this->todaySession->id,
            'film_id' => $this->film->id,
            'code' => 'SNX-20261103-ATT1',
            'customer_name' => 'Peserta Checkin Sukses',
            'whatsapp' => '6281234567802',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'seats_reserved' => 2,
            'payment_method' => 'cash',
            'status' => 'terkonfirmasi',
            'total_price' => 50000,
            'ticket_total' => 50000,
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('admin.scan.checkin'), [
            'booking_id' => $booking->id,
            'method' => 'qr',
            'session_id' => (string) $this->todaySession->id,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'verdict' => 'siap_hadir',
            'result' => 'ok',
        ]);

        $booking->refresh();
        $this->assertSame('hadir', $booking->status);
        $this->assertNotNull($booking->checked_in_at);
        $this->assertSame($this->admin->id, $booking->checked_in_by);
        $this->assertSame('qr', $booking->check_in_method);

        $this->assertDatabaseHas('booking_checkins', [
            'booking_id' => $booking->id,
            'user_id' => $this->admin->id,
            'method' => 'qr',
            'result' => 'ok',
        ]);
    }

    public function test_concurrency_and_idempotency_second_check_in_returns_sudah_hadir(): void
    {
        $booking = Booking::query()->create([
            'session_id' => $this->todaySession->id,
            'film_id' => $this->film->id,
            'code' => 'SNX-20261103-IDEM',
            'customer_name' => 'Peserta Idempoten',
            'whatsapp' => '6281234567803',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'seats_reserved' => 1,
            'payment_method' => 'cash',
            'status' => 'terkonfirmasi',
            'total_price' => 25000,
            'ticket_total' => 25000,
        ]);

        // Scan pertama sukses
        $res1 = $this->actingAs($this->admin)->postJson(route('admin.scan.checkin'), [
            'booking_id' => $booking->id,
            'method' => 'qr',
        ]);
        $res1->assertJsonPath('success', true);

        // Scan kedua idempoten: ditolak dengan verdict sudah_hadir dan result duplicate
        $res2 = $this->actingAs($this->admin)->postJson(route('admin.scan.checkin'), [
            'booking_id' => $booking->id,
            'method' => 'qr',
        ]);

        $res2->assertOk();
        $res2->assertJsonPath('success', false);
        $res2->assertJsonPath('verdict', 'sudah_hadir');
        $res2->assertJsonPath('result', 'duplicate');

        // Audit log mencatat rekaman kedua bertipe duplicate tanpa merusak data booking
        $this->assertDatabaseHas('booking_checkins', [
            'booking_id' => $booking->id,
            'result' => 'duplicate',
        ]);
    }

    public function test_override_check_in_for_other_session_succeeds_with_audit(): void
    {
        $tomorrowSession = Session::query()->create([
            'film_id' => $this->film->id,
            'session_date' => now()->addDay()->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'status' => 'tersedia',
            'confirmed_at' => now(),
        ]);

        $booking = Booking::query()->create([
            'session_id' => $tomorrowSession->id,
            'film_id' => $this->film->id,
            'code' => 'SNX-20261103-OVRD',
            'customer_name' => 'Peserta Izin Maju Hari',
            'whatsapp' => '6281234567804',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'seats_reserved' => 1,
            'payment_method' => 'cash',
            'status' => 'terkonfirmasi',
            'total_price' => 25000,
            'ticket_total' => 25000,
        ]);

        // Check-in biasa ditolak
        $resRejected = $this->actingAs($this->admin)->postJson(route('admin.scan.checkin'), [
            'booking_id' => $booking->id,
            'method' => 'qr',
            'allow_override' => false,
        ]);
        $resRejected->assertJsonPath('success', false);
        $resRejected->assertJsonPath('verdict', 'sesi_lain');

        // Check-in dengan override dan alasan
        $resOverride = $this->actingAs($this->admin)->postJson(route('admin.scan.checkin'), [
            'booking_id' => $booking->id,
            'method' => 'qr',
            'allow_override' => true,
            'reason' => 'Izin pengelola bioskop karena bangku kosong',
        ]);

        $resOverride->assertOk();
        $resOverride->assertJsonPath('success', true);
        $resOverride->assertJsonPath('result', 'override');

        $booking->refresh();
        $this->assertSame('hadir', $booking->status);

        $this->assertDatabaseHas('booking_checkins', [
            'booking_id' => $booking->id,
            'result' => 'override',
            'reason' => 'Izin pengelola bioskop karena bangku kosong',
        ]);
    }

    public function test_undo_check_in_reverts_status_and_logs_audit(): void
    {
        $booking = Booking::query()->create([
            'session_id' => $this->todaySession->id,
            'film_id' => $this->film->id,
            'code' => 'SNX-20261103-UNDO',
            'customer_name' => 'Batal Hadir',
            'whatsapp' => '6281234567805',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'seats_reserved' => 1,
            'payment_method' => 'cash',
            'status' => 'hadir',
            'checked_in_at' => now(),
            'checked_in_by' => $this->admin->id,
            'check_in_method' => 'qr',
            'total_price' => 25000,
            'ticket_total' => 25000,
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('admin.scan.undo'), [
            'booking_id' => $booking->id,
            'reason' => 'Salah pindai tiket',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        $booking->refresh();
        $this->assertNull($booking->checked_in_at);
        $this->assertNull($booking->checked_in_by);
        $this->assertNotSame('hadir', $booking->status);

        $this->assertDatabaseHas('booking_checkins', [
            'booking_id' => $booking->id,
            'user_id' => $this->admin->id,
            'result' => 'undo',
            'reason' => 'Salah pindai tiket',
        ]);
    }

    public function test_session_bookings_list_api_returns_grouped_data(): void
    {
        // 1 Booking Hadir
        Booking::query()->create([
            'session_id' => $this->todaySession->id,
            'film_id' => $this->film->id,
            'code' => 'SNX-LIST-1',
            'customer_name' => 'Tamu Hadir',
            'whatsapp' => '6281234567806',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 2,
            'seats_reserved' => 2,
            'payment_method' => 'cash',
            'status' => 'hadir',
            'checked_in_at' => now(),
            'total_price' => 50000,
            'ticket_total' => 50000,
        ]);

        // 1 Booking Siap
        Booking::query()->create([
            'session_id' => $this->todaySession->id,
            'film_id' => $this->film->id,
            'code' => 'SNX-LIST-2',
            'customer_name' => 'Tamu Siap Masuk',
            'whatsapp' => '6281234567807',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'seats_reserved' => 1,
            'payment_method' => 'cash',
            'status' => 'terkonfirmasi',
            'total_price' => 25000,
            'ticket_total' => 25000,
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('admin.scan.session_bookings', [
            'session_id' => $this->todaySession->id,
        ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'summary' => ['hadir', 'belum', 'perlu_perhatian', 'text'],
            'items' => [
                '*' => ['id', 'code', 'customer_name', 'people_count', 'session_time', 'chip', 'is_hadir'],
            ],
        ]);

        $this->assertSame(1, $response->json('summary.hadir'));
        $this->assertSame(1, $response->json('summary.belum'));
        $this->assertCount(2, $response->json('items'));
    }

    public function test_booking_detail_tucks_manual_action_in_disclosure(): void
    {
        $booking = Booking::query()->create([
            'session_id' => $this->todaySession->id,
            'film_id' => $this->film->id,
            'code' => 'SNX-DISCLOSURE-1',
            'customer_name' => 'Tamu Detail',
            'whatsapp' => '6281234567808',
            'package' => 'reguler',
            'booking_type' => 'per_orang',
            'pax' => 1,
            'seats_reserved' => 1,
            'payment_method' => 'cash',
            'status' => 'terkonfirmasi',
            'total_price' => 25000,
            'ticket_total' => 25000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking));

        $response->assertOk();
        // Memastikan tombol submit langsung tidak terpampang telanjang, melainkan di dalam disclosure
        $response->assertSee('Tampilkan aksi manual');
        $response->assertSee('Tandai hadir (manual)');
        $response->assertSee('Konfirmasi Hadir Manual');
    }

    public function test_dashboard_renders_attendance_and_scan_shortcut(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Scan tiket');
        $response->assertSee(route('admin.scan.index'));
    }

    public function test_reports_page_renders_check_in_statistics(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertSee('Tingkat Kehadiran & Cara Check-In', false);
        $response->assertSee('Scan QR');
        $response->assertSee('Input Kode');
    }
}
