<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_for_admin_routes(): void
    {
        $this->get(route('admin.settings.edit'))->assertRedirect(route('login'));
        $this->get(route('admin.banners.index'))->assertRedirect(route('login'));
        $this->get(route('admin.promos.index'))->assertRedirect(route('login'));
        $this->get(route('admin.films.index'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_non_admin_user_is_forbidden_from_admin_panel(): void
    {
        $normalUser = User::factory()->create([
            'is_admin' => false,
        ]);

        $this->actingAs($normalUser)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($normalUser)->get(route('admin.banners.index'))->assertForbidden();
        $this->actingAs($normalUser)->get(route('admin.promos.index'))->assertForbidden();
        $this->actingAs($normalUser)->get(route('admin.films.index'))->assertForbidden();
        $this->actingAs($normalUser)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_user_can_access_all_admin_routes(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk();
        $this->actingAs($admin)->get(route('admin.banners.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.promos.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.films.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }
}
