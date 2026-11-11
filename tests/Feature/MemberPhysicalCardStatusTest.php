<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberEvent;
use App\Models\User;
use App\Services\MemberCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberPhysicalCardStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);
    }

    private function createMember(string $name, bool $hasPhysicalCard = false): Member
    {
        $code = MemberCodeGenerator::generate();

        return Member::create([
            'code' => $code,
            'member_code' => $code,
            'name' => $name,
            'status' => 'aktif',
            'is_active' => true,
            'has_physical_card' => $hasPhysicalCard,
            'physical_card_printed_at' => $hasPhysicalCard ? now() : null,
            'opening_visits' => 0,
            'link_version' => 1,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_store_sets_physical_card_status_and_defaults_to_false(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.members.store'), [
                'name' => 'Member Tanpa Kartu',
                'status' => 'aktif',
                'opening_visits' => 0,
                'ignore_duplicate_name' => true,
            ])
            ->assertRedirect();

        $withoutCard = Member::where('name', 'Member Tanpa Kartu')->firstOrFail();
        $this->assertFalse($withoutCard->hasPhysicalCard());
        $this->assertNull($withoutCard->physical_card_printed_at);

        $this->actingAs($this->admin)
            ->post(route('admin.members.store'), [
                'name' => 'Member Sudah Kartu',
                'status' => 'aktif',
                'opening_visits' => 0,
                'has_physical_card' => '1',
                'ignore_duplicate_name' => true,
            ])
            ->assertRedirect();

        $withCard = Member::where('name', 'Member Sudah Kartu')->firstOrFail();
        $this->assertTrue($withCard->hasPhysicalCard());
        $this->assertNotNull($withCard->physical_card_printed_at);
    }

    public function test_index_displays_badges_stats_and_filters_by_card_status(): void
    {
        $issued = $this->createMember('Rina Sudah Kartu', true);
        $missing = $this->createMember('Budi Belum Kartu', false);

        $allResponse = $this->actingAs($this->admin)
            ->get(route('admin.members.index'))
            ->assertOk();

        $allResponse->assertViewHas('stats', function (array $stats): bool {
            return $stats['card_issued'] === 1 && $stats['card_missing'] === 1;
        });
        $allResponse->assertSee('Sudah Ada');
        $allResponse->assertSee('Belum Ada');

        $this->actingAs($this->admin)
            ->get(route('admin.members.index', ['card_status' => 'belum']))
            ->assertOk()
            ->assertSee($missing->name)
            ->assertDontSee($issued->name);

        $this->actingAs($this->admin)
            ->get(route('admin.members.index', ['card_status' => 'sudah']))
            ->assertOk()
            ->assertSee($issued->name)
            ->assertDontSee($missing->name);
    }

    public function test_single_physical_card_toggle_endpoint_supports_json_and_redirect_and_logs_event(): void
    {
        $member = $this->createMember('Sari Toggle', false);

        $jsonResponse = $this->actingAs($this->admin)
            ->patchJson(route('admin.members.toggle_physical_card', $member), [
                'has_physical_card' => true,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('has_physical_card', true)
            ->assertJsonPath('stats.card_issued', 1)
            ->assertJsonPath('stats.card_missing', 0);

        $this->assertNotNull($jsonResponse->json('printed_at'));

        $member->refresh();
        $this->assertTrue($member->hasPhysicalCard());
        $this->assertNotNull($member->physical_card_printed_at);

        $this->assertDatabaseHas('member_events', [
            'member_id' => $member->id,
            'event' => 'physical_card_updated',
        ]);

        // Form PATCH back to false from show page
        $this->actingAs($this->admin)
            ->from(route('admin.members.show', $member))
            ->patch(route('admin.members.toggle_physical_card', $member), [
                'has_physical_card' => '0',
            ])
            ->assertRedirect(route('admin.members.show', $member));

        $member->refresh();
        $this->assertFalse($member->hasPhysicalCard());
        $this->assertNull($member->physical_card_printed_at);
    }

    public function test_bulk_physical_card_update_marks_selected_members_and_logs_audit(): void
    {
        $m1 = $this->createMember('Bulk Satu', false);
        $m2 = $this->createMember('Bulk Dua', false);
        $m3 = $this->createMember('Bulk Tiga', false);

        $this->actingAs($this->admin)
            ->postJson(route('admin.members.bulk_physical_card'), [
                'ids' => [$m1->id, $m2->id],
                'has_physical_card' => true,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('updated_count', 2)
            ->assertJsonPath('stats.card_issued', 2)
            ->assertJsonPath('stats.card_missing', 1);

        $this->assertTrue($m1->fresh()->hasPhysicalCard());
        $this->assertTrue($m2->fresh()->hasPhysicalCard());
        $this->assertFalse($m3->fresh()->hasPhysicalCard());

        $event = MemberEvent::query()->where('member_id', $m1->id)->where('event', 'physical_card_updated')->firstOrFail();
        $this->assertTrue((bool) ($event->meta['bulk'] ?? false));

        // Reset back to false
        $this->actingAs($this->admin)
            ->postJson(route('admin.members.bulk_physical_card'), [
                'ids' => [$m1->id],
                'has_physical_card' => false,
            ])
            ->assertOk()
            ->assertJsonPath('updated_count', 1)
            ->assertJsonPath('stats.card_issued', 1)
            ->assertJsonPath('stats.card_missing', 2);

        $this->assertFalse($m1->fresh()->hasPhysicalCard());
    }

    public function test_update_handles_checkbox_and_preserves_status_when_omitted(): void
    {
        $member = $this->createMember('Andi Update', true);

        // Omitting has_physical_card keeps true (backwards compatibility)
        $this->actingAs($this->admin)
            ->put(route('admin.members.update', $member), [
                'name' => 'Andi Update',
                'status' => 'aktif',
                'total_visits' => 2,
            ])
            ->assertRedirect(route('admin.members.show', $member));

        $this->assertTrue($member->fresh()->hasPhysicalCard());

        // Sending explicit '0' clears has_physical_card
        $this->actingAs($this->admin)
            ->put(route('admin.members.update', $member), [
                'name' => 'Andi Update',
                'status' => 'aktif',
                'total_visits' => 2,
                'has_physical_card' => '0',
            ])
            ->assertRedirect(route('admin.members.show', $member));

        $this->assertFalse($member->fresh()->hasPhysicalCard());
        $this->assertNull($member->fresh()->physical_card_printed_at);
    }

    public function test_csv_export_and_print_views_include_physical_card_status(): void
    {
        $issued = $this->createMember('Dewi Cetak', true);
        $missing = $this->createMember('Eko Belum', false);

        $csvResponse = $this->actingAs($this->admin)
            ->get(route('admin.members.export'))
            ->assertOk();

        $csvContent = $csvResponse->streamedContent();
        $this->assertStringContainsString('Kartu Fisik', $csvContent);
        $this->assertStringContainsString('Sudah Ada', $csvContent);
        $this->assertStringContainsString('Belum Dibuatkan', $csvContent);

        $this->actingAs($this->admin)
            ->get(route('admin.members.print_single', $missing))
            ->assertOk()
            ->assertSee('Tandai Sudah Dicetak');

        $this->actingAs($this->admin)
            ->get(route('admin.members.print_single', $issued))
            ->assertOk()
            ->assertSee('Sudah Ada Kartu Fisik');

        $this->actingAs($this->admin)
            ->get(route('admin.members.print_batch', ['ids' => $missing->id]))
            ->assertOk()
            ->assertSee('Tandai 1 Member Sudah Dicetak');
    }
}
