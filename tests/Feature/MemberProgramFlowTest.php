<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Film;
use App\Models\Member;
use App\Models\MemberVisit;
use App\Models\Session;
use App\Models\Setting;
use App\Models\User;
use App\Services\CinemaSettings;
use App\Services\LoyaltyRules;
use App\Services\MemberCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MemberProgramFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Film $film;

    protected Session $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->film = Film::query()->create([
            'title' => 'Film Member QA',
            'slug' => 'film-member-qa-'.uniqid(),
            'duration_minutes' => 100,
            'genre' => 'Drama',
            'synopsis' => 'Sinopsis film member QA untuk pengujian program loyalitas.',
            'age_rating' => '13+',
            'is_active' => true,
        ]);

        $this->session = Session::query()->create([
            'film_id' => $this->film->id,
            'session_date' => now()->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'status' => 'tersedia',
            'confirmed_at' => now(),
        ]);

        Setting::updateOrCreate(['key' => 'member_program_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'member_discount_visit'], ['value' => '5']);
        Setting::updateOrCreate(['key' => 'member_discount_amount'], ['value' => '2000']);
        Setting::updateOrCreate(['key' => 'member_free_visit'], ['value' => '10']);
        Setting::updateOrCreate(['key' => 'member_max_visits_per_day'], ['value' => '2']);
        Setting::updateOrCreate(['key' => 'check_in_open_minutes_before'], ['value' => '120']);
        Setting::updateOrCreate(['key' => 'check_in_close_after'], ['value' => 'session_end']);
        app(CinemaSettings::class)->clearCache();
    }

    /**
     * 1. Test Member Code Generator format, check digit, normalization, and strict privacy.
     */
    public function test_member_code_generation_checksum_and_schema_privacy(): void
    {
        $code = MemberCodeGenerator::generate();

        // Format check: MBR-XXXX-XXXX-C
        $this->assertMatchesRegularExpression('/^MBR-[0-9A-Z]{4}-[0-9A-Z]{4}-[0-9A-Z]$/', $code);
        $this->assertTrue(MemberCodeGenerator::isValid($code));

        // Corrupted check digit fails
        $corrupted = substr($code, 0, -1).(substr($code, -1) === 'A' ? 'B' : 'A');
        $this->assertFalse(MemberCodeGenerator::isValid($corrupted));

        // Normalization handles lowercases and prefix
        $normalized = MemberCodeGenerator::normalize('synemax-mbr:'.strtolower($code));
        $this->assertEquals($code, $normalized);

        // Strict schema privacy constraint: NO phone, email, WhatsApp, or address columns exist!
        $this->assertFalse(Schema::hasColumn('members', 'phone'));
        $this->assertFalse(Schema::hasColumn('members', 'email'));
        $this->assertFalse(Schema::hasColumn('members', 'whatsapp'));
        $this->assertFalse(Schema::hasColumn('members', 'address'));
    }

    /**
     * 2. Test 30 consecutive visits: 10-visit repeating cycle, rewards at 5 (discount) and 10 (free), reset to 1.
     */
    public function test_thirty_consecutive_visits_and_repeating_ten_visit_cycle(): void
    {
        $member = Member::create([
            'code' => MemberCodeGenerator::generate(),
            'name' => 'Budi Santoso',
            'status' => 'aktif',
            'opening_visits' => 0,
        ]);

        $loyaltyRules = app(LoyaltyRules::class);

        try {
            // Record 30 consecutive visits across 30 distinct sessions (1 visit per day)
            for ($i = 1; $i <= 30; $i++) {
                $simulatedDate = Carbon::today()->addDays($i);
                Carbon::setTestNow($simulatedDate->copy()->setTime(13, 15, 0));

                $sess = Session::query()->create([
                    'film_id' => $this->film->id,
                    'session_date' => $simulatedDate->toDateString(),
                    'start_time' => '13:00:00',
                    'end_time' => '15:00:00',
                    'status' => 'tersedia',
                    'confirmed_at' => now(),
                ]);

                $res = $loyaltyRules->recordVisit(
                    member: $member,
                    session: $sess,
                    userId: $this->admin->id,
                    method: 'qr'
                );

                $this->assertTrue($res['success'], "Visit {$i} failed to record: ".($res['message'] ?? ''));

                $expectedPos = (($i - 1) % 10) + 1;
                $expectedCycle = intdiv($i - 1, 10) + 1;

                $this->assertEquals($expectedPos, $res['visit']->position, "Position mismatch at visit {$i}");
                $this->assertEquals($expectedCycle, $res['visit']->cycle_number, "Cycle mismatch at visit {$i}");

                if ($expectedPos === 5) {
                    $this->assertEquals(LoyaltyRules::REWARD_DISCOUNT, $res['visit']->reward_type);
                    $this->assertEquals(2000, $res['visit']->reward_amount);
                    $this->assertNotNull($res['reward']);
                    $this->assertEquals('discount', $res['reward']->type);
                } elseif ($expectedPos === 10) {
                    $this->assertEquals(LoyaltyRules::REWARD_FREE, $res['visit']->reward_type);
                    $this->assertGreaterThan(0, $res['visit']->reward_amount);
                    $this->assertNotNull($res['reward']);
                    $this->assertEquals('free', $res['reward']->type);
                } else {
                    $this->assertEquals(LoyaltyRules::REWARD_NONE, $res['visit']->reward_type);
                    $this->assertNull($res['reward']);
                }
            }

            $this->assertEquals(30, $member->totalValidVisits());
            $this->assertEquals(4, $member->currentCycle());
            $this->assertEquals(1, $member->nextVisitPosition());
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * 3. Test opening visits balance (e.g. migrating from paper stamp card).
     */
    public function test_opening_visits_progression(): void
    {
        $loyaltyRules = app(LoyaltyRules::class);

        // Member A with opening_visits = 3
        $memberA = Member::create([
            'code' => MemberCodeGenerator::generate(),
            'name' => 'Member Pindahan 3',
            'status' => 'aktif',
            'opening_visits' => 3,
        ]);

        $this->assertEquals(4, $memberA->nextVisitPosition()); // (3 + 0) % 10 + 1 = 4

        // 1st visit -> position 4
        $sess1 = Session::query()->create([
            'film_id' => $this->film->id,
            'session_date' => now()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'tersedia',
            'confirmed_at' => now(),
        ]);
        $res1 = $loyaltyRules->recordVisit($memberA, $sess1, null, $this->admin->id);
        $this->assertEquals(4, $res1['visit']->position);
        $this->assertEquals('none', $res1['visit']->reward_type);

        // 2nd visit -> position 5 -> DISCOUNT!
        $sess2 = Session::query()->create([
            'film_id' => $this->film->id,
            'session_date' => now()->toDateString(),
            'start_time' => '16:00:00',
            'end_time' => '18:00:00',
            'status' => 'tersedia',
            'confirmed_at' => now(),
        ]);
        $res2 = $loyaltyRules->recordVisit($memberA, $sess2, null, $this->admin->id);
        $this->assertEquals(5, $res2['visit']->position);
        $this->assertEquals('discount', $res2['visit']->reward_type);

        // Member B with opening_visits = 9
        $memberB = Member::create([
            'code' => MemberCodeGenerator::generate(),
            'name' => 'Member Pindahan 9',
            'status' => 'aktif',
            'opening_visits' => 9,
        ]);

        // 1st visit -> position 10 -> FREE TICKET!
        $resB = $loyaltyRules->recordVisit($memberB, $sess1, null, $this->admin->id);
        $this->assertEquals(10, $resB['visit']->position);
        $this->assertEquals('free', $resB['visit']->reward_type);
    }

    /**
     * 4. Test duplicate visit constraint for the same session.
     */
    public function test_single_member_cannot_check_in_twice_for_same_session(): void
    {
        $member = Member::create([
            'code' => MemberCodeGenerator::generate(),
            'name' => 'Agus Priyono',
            'status' => 'aktif',
        ]);

        $loyaltyRules = app(LoyaltyRules::class);

        // First check-in succeeds
        $res1 = $loyaltyRules->recordVisit($member, $this->session, null, $this->admin->id);
        $this->assertTrue($res1['success']);

        // Second check-in for the same session is rejected as already recorded
        $res2 = $loyaltyRules->recordVisit($member, $this->session, null, $this->admin->id);
        $this->assertFalse($res2['success']);
        $this->assertEquals('sudah_tercatat_sesi_ini', $res2['verdict']);
    }

    /**
     * 5. Test daily limit enforcement (batas_harian).
     */
    public function test_daily_visit_limit_enforced(): void
    {
        $member = Member::create([
            'code' => MemberCodeGenerator::generate(),
            'name' => 'Dewi Lestari',
            'status' => 'aktif',
        ]);

        $loyaltyRules = app(LoyaltyRules::class);

        $sess1 = Session::query()->create([
            'film_id' => $this->film->id,
            'session_date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'tersedia',
            'confirmed_at' => now(),
        ]);
        $sess2 = Session::query()->create([
            'film_id' => $this->film->id,
            'session_date' => now()->toDateString(),
            'start_time' => '10:30:00',
            'end_time' => '12:30:00',
            'status' => 'tersedia',
            'confirmed_at' => now(),
        ]);
        $sess3 = Session::query()->create([
            'film_id' => $this->film->id,
            'session_date' => now()->toDateString(),
            'start_time' => '16:00:00',
            'end_time' => '18:00:00',
            'status' => 'tersedia',
            'confirmed_at' => now(),
        ]);

        // Visit 1 today -> OK
        $res1 = $loyaltyRules->recordVisit($member, $sess1, null, $this->admin->id);
        $this->assertTrue($res1['success']);

        // Visit 2 today -> OK (max is 2)
        $res2 = $loyaltyRules->recordVisit($member, $sess2, null, $this->admin->id);
        $this->assertTrue($res2['success']);

        // Visit 3 today -> REJECTED (batas_harian)
        $res3 = $loyaltyRules->recordVisit($member, $sess3, null, $this->admin->id);
        $this->assertFalse($res3['success']);
        $this->assertEquals('batas_harian', $res3['verdict']);

        // When member_max_visits_per_day is 0 (tidak ada batas), visit 3 is allowed
        Setting::updateOrCreate(['key' => 'member_max_visits_per_day'], ['value' => '0']);
        app(CinemaSettings::class)->clearCache();
        $res3Unlimited = $loyaltyRules->recordVisit($member, $sess3, null, $this->admin->id);
        $this->assertTrue($res3Unlimited['success']);
    }

    /**
     * 6. Test member status: blocked or inactive members cannot check in.
     */
    public function test_inactive_and_blocked_members_rejected(): void
    {
        $loyaltyRules = app(LoyaltyRules::class);

        $memberInactive = Member::create([
            'code' => MemberCodeGenerator::generate(),
            'name' => 'Member Nonaktif',
            'status' => 'nonaktif',
        ]);

        $eval1 = $loyaltyRules->evaluateCheckIn($memberInactive, $this->session);
        $this->assertFalse($eval1['can_record']);
        $this->assertEquals('member_nonaktif', $eval1['verdict']);

        $memberBlocked = Member::create([
            'code' => MemberCodeGenerator::generate(),
            'name' => 'Member Diblokir',
            'status' => 'diblokir',
        ]);

        $eval2 = $loyaltyRules->evaluateCheckIn($memberBlocked, $this->session);
        $this->assertFalse($eval2['can_record']);
        $this->assertEquals('member_diblokir', $eval2['verdict']);
    }

    /**
     * 7. Test reward settlement: deducted_bill vs refund_cash vs counter_discount.
     */
    public function test_reward_settlement_types(): void
    {
        $loyaltyRules = app(LoyaltyRules::class);

        // Member hitting visit 5 (discount) with unpaid booking -> deducted_bill
        $memberA = Member::create([
            'code' => MemberCodeGenerator::generate(),
            'name' => 'Member Bayar Ditempat',
            'status' => 'aktif',
            'opening_visits' => 4,
        ]);

        $bookingUnpaid = Booking::query()->create([
            'code' => 'SYX-TEST01',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Member Bayar Ditempat',
            'whatsapp' => '08123456789',
            'total_price' => 20000,
            'ticket_total' => 20000,
            'payment_method' => 'di_tempat',
            'status' => 'menunggu_pembayaran',
            'pax' => 1,
            'people_count' => 1,
        ]);

        $resA = $loyaltyRules->recordVisit($memberA, $this->session, $bookingUnpaid, $this->admin->id);
        $this->assertTrue($resA['success']);
        $this->assertEquals('deducted_bill', $resA['reward']->settlement);

        // Member hitting visit 5 with online paid booking -> refund_cash
        $memberB = Member::create([
            'code' => MemberCodeGenerator::generate(),
            'name' => 'Member Lunas Online',
            'status' => 'aktif',
            'opening_visits' => 4,
        ]);

        $sessB = Session::query()->create([
            'film_id' => $this->film->id,
            'session_date' => now()->toDateString(),
            'start_time' => '16:00:00',
            'end_time' => '18:00:00',
            'status' => 'tersedia',
            'confirmed_at' => now(),
        ]);

        $bookingPaid = Booking::query()->create([
            'code' => 'SYX-TEST02',
            'session_id' => $sessB->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Member Lunas Online',
            'whatsapp' => '08123456788',
            'total_price' => 20000,
            'ticket_total' => 20000,
            'payment_method' => 'qris',
            'status' => 'terkonfirmasi',
            'pax' => 1,
            'people_count' => 1,
        ]);

        $resB = $loyaltyRules->recordVisit($memberB, $sessB, $bookingPaid, $this->admin->id);
        $this->assertTrue($resB['success']);
        $this->assertEquals('refund_cash', $resB['reward']->settlement);

        // Member without booking -> counter_discount
        $memberC = Member::create([
            'code' => MemberCodeGenerator::generate(),
            'name' => 'Member Datang Langsung',
            'status' => 'aktif',
            'opening_visits' => 4,
        ]);

        $sessC = Session::query()->create([
            'film_id' => $this->film->id,
            'session_date' => now()->toDateString(),
            'start_time' => '19:00:00',
            'end_time' => '21:00:00',
            'status' => 'tersedia',
            'confirmed_at' => now(),
        ]);

        $resC = $loyaltyRules->recordVisit($memberC, $sessC, null, $this->admin->id);
        $this->assertTrue($resC['success']);
        $this->assertEquals('counter_discount', $resC['reward']->settlement);
    }

    /**
     * 8. Test voiding visit: only the last valid visit can be voided.
     */
    public function test_voiding_only_last_valid_visit(): void
    {
        $member = Member::create([
            'code' => MemberCodeGenerator::generate(),
            'name' => 'Member Void Test',
            'status' => 'aktif',
        ]);

        $loyaltyRules = app(LoyaltyRules::class);

        $sess1 = Session::query()->create([
            'film_id' => $this->film->id,
            'session_date' => now()->toDateString(),
            'start_time' => '16:00:00',
            'end_time' => '18:00:00',
            'status' => 'tersedia',
            'confirmed_at' => now(),
        ]);
        $sess2 = Session::query()->create([
            'film_id' => $this->film->id,
            'session_date' => now()->toDateString(),
            'start_time' => '19:00:00',
            'end_time' => '21:00:00',
            'status' => 'tersedia',
            'confirmed_at' => now(),
        ]);

        $res1 = $loyaltyRules->recordVisit($member, $sess1, null, $this->admin->id);
        $res2 = $loyaltyRules->recordVisit($member, $sess2, null, $this->admin->id);

        $this->assertEquals(2, $member->totalValidVisits());

        // Attempting to void the 1st visit while 2nd visit exists is rejected
        $voidAttempt = $loyaltyRules->voidVisit($member, $res1['visit'], $this->admin->id, 'Salah input');
        $this->assertFalse($voidAttempt['success']);
        $this->assertStringContainsString('Hanya kunjungan sah terakhir', $voidAttempt['message']);

        // Voiding the 2nd visit (last valid visit) succeeds
        $voidSuccess = $loyaltyRules->voidVisit($member, $res2['visit'], $this->admin->id, 'Member batal menonton');
        $this->assertTrue($voidSuccess['success']);
        $this->assertEquals(1, $member->totalValidVisits());

        // Now visit 1 is the last valid visit, and can be voided
        $void1Success = $loyaltyRules->voidVisit($member, $res1['visit'], $this->admin->id, 'Salah scan barcode');
        $this->assertTrue($void1Success['success']);
        $this->assertEquals(0, $member->totalValidVisits());
    }

    /**
     * 9. Test public signed digital member card security.
     */
    public function test_public_signed_card_security_and_versioning(): void
    {
        $member = Member::create([
            'code' => MemberCodeGenerator::generate(),
            'name' => 'Siti Nurhaliza',
            'status' => 'aktif',
            'link_version' => 1,
        ]);

        $signedUrl = $member->digitalCardSignedUrl();

        // 1. Valid signed URL returns 200 with noindex and no-store headers
        $response = $this->get($signedUrl);
        $response->assertOk();
        $response->assertSee('Siti Nurhaliza');
        $response->assertSee($member->code);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        // 2. Tampered signature returns 403
        $tamperedUrl = $signedUrl.'tamper';
        $this->get($tamperedUrl)->assertForbidden();

        // 3. Incrementing link_version invalidates previous signed URL (returns 403)
        $member->increment('link_version'); // link_version becomes 2
        $this->get($signedUrl)->assertForbidden();

        // 4. New signed URL works
        $newSignedUrl = $member->digitalCardSignedUrl();
        $this->get($newSignedUrl)->assertOk();
    }

    /**
     * 10. Test scanner lookup distinguishes between Member QR and Booking QR.
     */
    public function test_scanner_lookup_distinguishes_member_and_booking(): void
    {
        $member = Member::create([
            'code' => MemberCodeGenerator::generate(),
            'name' => 'Surya Saputra',
            'status' => 'aktif',
        ]);

        $booking = Booking::query()->create([
            'code' => 'SYX-TEST99',
            'session_id' => $this->session->id,
            'film_id' => $this->film->id,
            'customer_name' => 'Surya Saputra',
            'whatsapp' => '08129999999',
            'total_price' => 15000,
            'ticket_total' => 15000,
            'payment_method' => 'di_tempat',
            'status' => 'terkonfirmasi',
            'pax' => 1,
            'people_count' => 1,
        ]);

        $this->actingAs($this->admin);

        // Lookup member code returns type: member
        $resMember = $this->postJson(route('admin.scan.lookup'), [
            'code' => $member->code,
            'session_id' => $this->session->id, // integer session_id must be accepted
        ]);
        $resMember->assertOk();
        $resMember->assertJson([
            'type' => 'member',
            'status' => 'found',
            'member' => [
                'name' => 'Surya Saputra',
                'code' => $member->code,
            ],
        ]);

        // Lookup member code with string or none session_id
        $resMemberString = $this->postJson(route('admin.scan.lookup'), [
            'code' => $member->code,
            'session_id' => (string) $this->session->id,
        ]);
        $resMemberString->assertOk();

        $resMemberNone = $this->postJson(route('admin.scan.lookup'), [
            'code' => $member->code,
            'session_id' => 'none',
        ]);
        $resMemberNone->assertOk();

        // Lookup booking code returns type: booking
        $resBooking = $this->postJson(route('admin.scan.lookup'), [
            'code' => $booking->code,
            'session_id' => $this->session->id,
        ]);
        $resBooking->assertOk();
        $resBooking->assertJson([
            'type' => 'booking',
        ]);
    }

    /**
     * 11. Test concurrency / duplicate check-in handling: exactly 1 succeeds, second is rejected gracefully without 500.
     */
    public function test_concurrency_double_checkin_lock_handling(): void
    {
        $member = Member::create([
            'code' => MemberCodeGenerator::generate(),
            'name' => 'Concurrency Tester',
            'status' => 'aktif',
        ]);

        $this->actingAs($this->admin);

        // 1st check-in succeeds (200 OK, success: true)
        $res1 = $this->postJson(route('admin.scan.member_checkin'), [
            'member_id' => $member->id,
            'session_id' => $this->session->id,
            'method' => 'qr',
        ]);
        $res1->assertOk();
        $this->assertTrue($res1->json('success'));
        $this->assertEquals(1, MemberVisit::where('member_id', $member->id)->where('session_id', $this->session->id)->count());

        // 2nd check-in for the same session is rejected gracefully without 500 error or duplicate DB record
        $res2 = $this->postJson(route('admin.scan.member_checkin'), [
            'member_id' => $member->id,
            'session_id' => $this->session->id,
            'method' => 'qr',
        ]);
        $res2->assertOk();
        $this->assertFalse($res2->json('success'));
        $this->assertEquals('sudah_tercatat_sesi_ini', $res2->json('verdict'));
        $this->assertEquals(1, MemberVisit::where('member_id', $member->id)->where('session_id', $this->session->id)->count());
    }

    /**
     * 12. Test member store form handles opening visits numeric selection and creation.
     */
    public function test_member_store_form_accepts_opening_visits(): void
    {
        $this->actingAs($this->admin);

        // Verify GET create form outputs proper numeric option values in select
        $createView = $this->get(route('admin.members.create'));
        $createView->assertOk();
        $this->assertStringContainsString('value\u0022:\u00221\u0022,\u0022label\u0022:\u00221 kunjungan\u0022', $createView->getContent());

        // Test with numeric opening_visits
        $response = $this->post(route('admin.members.store'), [
            'name' => 'Member Zia',
            'nickname' => 'Zia',
            'opening_visits' => 1,
            'status' => 'aktif',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('members', [
            'name' => 'Member Zia',
            'opening_visits' => 1,
            'status' => 'aktif',
        ]);

        // Test with string with prefix/suffix (e.g. "1 kunjungan")
        $response2 = $this->post(route('admin.members.store'), [
            'name' => 'Member Rian',
            'nickname' => 'Rian',
            'opening_visits' => '2 kunjungan',
            'status' => 'aktif',
        ]);

        $response2->assertSessionHasNoErrors();
        $this->assertDatabaseHas('members', [
            'name' => 'Member Rian',
            'opening_visits' => 2,
            'status' => 'aktif',
        ]);
    }

    /**
     * 13. Test member edit form displays and updates total visits.
     */
    public function test_member_edit_form_and_update_adjusts_total_visits(): void
    {
        $this->actingAs($this->admin);

        $member = Member::create([
            'code' => 'MBR-EDIT-VIS1-A',
            'member_code' => 'MBR-EDIT-VIS1-A',
            'name' => 'Member Adit',
            'nickname' => 'Adit',
            'opening_visits' => 0,
            'status' => 'aktif',
            'is_active' => true,
        ]);

        // 1. Check edit view contains input for total_visits with current value 0
        $editView = $this->get(route('admin.members.edit', $member));
        $editView->assertOk();
        $editView->assertSee('name="total_visits"', false);
        $editView->assertSee('value="0"', false);

        // 2. Update member total_visits to 5
        $res = $this->put(route('admin.members.update', $member), [
            'name' => 'Member Adit',
            'nickname' => 'Adit',
            'total_visits' => 5,
            'status' => 'aktif',
        ]);

        $res->assertSessionHasNoErrors();
        $res->assertRedirect(route('admin.members.show', $member));

        $member->refresh();
        $this->assertSame(5, $member->totalVisits());
        $this->assertSame(5, $member->opening_visits);

        // 3. Verify event audit log has before and after total_visits
        $event = $member->events()->latest('id')->first();
        $this->assertNotNull($event);
        $this->assertSame(0, $event->meta['before']['total_visits']);
        $this->assertSame(5, $event->meta['after']['total_visits']);
    }
}
