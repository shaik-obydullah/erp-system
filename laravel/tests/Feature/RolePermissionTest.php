<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:admin', 'role:super-admin'])->get('/test-admin-only', function () {
            return response('admin area');
        })->name('admin.only');

        Route::middleware(['auth:admin', 'role:super-admin,manager'])->get('/test-admin-or-manager', function () {
            return response('admin or manager area');
        })->name('admin.or.manager');

        Route::middleware(['auth:admin', 'permission:manage-products'])->get('/test-permission-required', function () {
            return response('products area');
        })->name('permission.required');

        Route::middleware(['auth:admin', 'permission:manage-products,sell-products'])->get('/test-any-permission', function () {
            return response('products or sales area');
        })->name('any.permission.required');
    }

    public function test_admin_with_correct_role_can_access(): void
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'super-admin']);
        $admin->roles()->attach($role);

        $response = $this->actingAs($admin, 'admin')->get('/test-admin-only');

        $response->assertOk();
        $response->assertSee('admin area');
    }

    public function test_admin_without_correct_role_gets_403(): void
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'cashier']);
        $admin->roles()->attach($role);

        $response = $this->actingAs($admin, 'admin')->get('/test-admin-only');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_gets_redirect(): void
    {
        $response = $this->get('/test-admin-only');

        $response->assertRedirect();
    }

    public function test_admin_with_one_of_multiple_roles_can_access(): void
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'manager']);
        $admin->roles()->attach($role);

        $response = $this->actingAs($admin, 'admin')->get('/test-admin-or-manager');

        $response->assertOk();
        $response->assertSee('admin or manager area');
    }

    public function test_admin_with_no_matching_role_gets_403_on_multi_role_check(): void
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'cashier']);
        $admin->roles()->attach($role);

        $response = $this->actingAs($admin, 'admin')->get('/test-admin-or-manager');

        $response->assertStatus(403);
    }

    public function test_admin_with_permission_can_access(): void
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'super-admin']);
        $admin->roles()->attach($role);
        Permission::create(['fk_role_id' => $role->id, 'name' => 'manage-products']);

        $response = $this->actingAs($admin, 'admin')->get('/test-permission-required');

        $response->assertOk();
        $response->assertSee('products area');
    }

    public function test_admin_without_permission_gets_403(): void
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'cashier']);
        $admin->roles()->attach($role);

        $response = $this->actingAs($admin, 'admin')->get('/test-permission-required');

        $response->assertStatus(403);
    }

    public function test_admin_with_any_of_multiple_permissions_can_access(): void
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'cashier']);
        $admin->roles()->attach($role);
        Permission::create(['fk_role_id' => $role->id, 'name' => 'sell-products']);

        $response = $this->actingAs($admin, 'admin')->get('/test-any-permission');

        $response->assertOk();
        $response->assertSee('products or sales area');
    }

    public function test_admin_with_no_permissions_gets_403(): void
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'viewer']);
        $admin->roles()->attach($role);

        $response = $this->actingAs($admin, 'admin')->get('/test-any-permission');

        $response->assertStatus(403);
    }
}
