<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ActiveAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:admin', 'active.admin'])->get('/test-active', function () {
            return response('welcome');
        })->name('test.active');
    }

    public function test_active_admin_can_access_protected_route(): void
    {
        $admin = Admin::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin, 'admin')->get('/test-active');

        $response->assertOk();
        $response->assertSee('welcome');
    }

    public function test_inactive_admin_is_logged_out_and_redirected(): void
    {
        $admin = Admin::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($admin, 'admin')->get('/test-active');

        $response->assertRedirect(route('login'));
        $this->assertGuest('admin');
    }

    public function test_inactive_admin_cannot_access_dashboard(): void
    {
        $admin = Admin::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($admin, 'admin')->get('/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_inactive_admin_cannot_access_profile(): void
    {
        $admin = Admin::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($admin, 'admin')->get('/profile');

        $response->assertRedirect(route('login'));
    }
}
