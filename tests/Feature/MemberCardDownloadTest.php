<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MemberCardDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Member $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->member = Member::create([
            'code' => 'TEST1234',
            'member_code' => 'TEST1234',
            'name' => 'Budi Santoso',
            'nickname' => 'Budi',
            'opening_visits' => 2,
            'status' => 'aktif',
            'is_active' => true,
            'link_version' => 1,
        ]);
    }

    public function test_public_member_card_view_contains_download_and_print_buttons(): void
    {
        $signedUrl = $this->member->digitalCardSignedUrl();

        $response = $this->get($signedUrl);

        $response->assertOk();
        $response->assertSee('Download Kartu (PNG)');
        $response->assertSee('Cetak / Simpan PDF');
        $response->assertSee('downloadMemberCardPng');
        $response->assertSee('Budi Santoso (Budi)');
        $response->assertSee('member-mascot.jpg');
        $response->assertSee($this->member->formattedCode());
    }

    public function test_public_member_card_svg_download(): void
    {
        $svgUrl = URL::signedRoute('public.member.card', [
            'code' => $this->member->code,
            'v' => $this->member->link_version,
            'format' => 'svg',
        ]);

        $response = $this->get($svgUrl);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml; charset=utf-8');
        $this->assertStringContainsString('attachment; filename="kartu-member-synemax-', (string) $response->headers->get('Content-Disposition'));

        $content = $response->getContent();
        $this->assertStringContainsString('<svg', $content);
        $this->assertStringContainsString('Budi Santoso (Budi)', $content);
        $this->assertStringContainsString($this->member->formattedCode(), $content);
        $this->assertStringContainsString('KARTU MEMBER', $content);
    }

    public function test_admin_member_print_single_view_contains_download_button(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.members.print_single', $this->member));

        $response->assertOk();
        $response->assertSee('Download Gambar (PNG)');
        $response->assertSee('Cetak Kartu');
        $response->assertSee('downloadMemberCardPng');
        $response->assertSee('Budi Santoso (Budi)');
        $response->assertSee('member-mascot.jpg');
    }

    public function test_admin_member_print_single_svg_download(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.members.print_single', [
            'member' => $this->member,
            'format' => 'svg',
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml; charset=utf-8');
        $this->assertStringContainsString('attachment; filename="kartu-member-synemax-', (string) $response->headers->get('Content-Disposition'));

        $content = $response->getContent();
        $this->assertStringContainsString('<svg', $content);
        $this->assertStringContainsString('Budi Santoso (Budi)', $content);
    }

    public function test_admin_member_show_contains_download_button(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.members.show', $this->member));

        $response->assertOk();
        $response->assertSee('Download Kartu (PNG)');
        $response->assertSee('downloadMemberCardPng');
    }

    public function test_admin_print_batch_prints_all_selected_members_across_sheets(): void
    {
        $this->actingAs($this->admin);

        // Create 25 active members
        $createdMembers = [];
        for ($i = 1; $i <= 25; $i++) {
            $createdMembers[] = Member::create([
                'code' => sprintf('MBR%05d', $i),
                'member_code' => sprintf('MBR%05d', $i),
                'name' => 'Member Nomor '.$i,
                'nickname' => 'M'.$i,
                'status' => 'aktif',
                'is_active' => true,
                'link_version' => 1,
            ]);
        }

        $ids = array_map(fn ($m) => $m->id, $createdMembers);
        $idsString = implode(',', $ids);

        $response = $this->get(route('admin.members.print_batch', ['ids' => $idsString]));

        $response->assertOk();
        $response->assertSee('Cetak 25 Kartu (4 Lembar A4)');
        $response->assertSee('Member Nomor 1 (M1)');
        $response->assertSee('Member Nomor 25 (M25)');
        $this->assertEquals(4, substr_count($response->getContent(), 'class="a4-sheet"'));
    }
}
