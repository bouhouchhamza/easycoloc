<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_admin_can_access_admin_dashboard(): void
    {
        Role::findOrCreate(User::ROLE_GLOBAL_ADMIN, 'web');
        Role::findOrCreate(User::ROLE_USER, 'web');

        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_GLOBAL_ADMIN);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
    }

    public function test_non_admin_cannot_access_admin_dashboard(): void
    {
        Role::findOrCreate(User::ROLE_GLOBAL_ADMIN, 'web');
        Role::findOrCreate(User::ROLE_USER, 'web');

        $user = User::factory()->create();
        $user->assignRole(User::ROLE_USER);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }
}
