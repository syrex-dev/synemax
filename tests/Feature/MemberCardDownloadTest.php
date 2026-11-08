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
}
