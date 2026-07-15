<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Services\ActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:admin', 'active.admin'])->get('/test-dashboard', function () {
            return response('ok');
        })->name('dashboard');
    }

    public function test_login_creates_activity_record(): void
    {
        $admin = Admin::factory()->create();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('activities', [
            'fk_admin_id' => $admin->id,
            'type' => 'success',
            'name' => 'Login successful',
        ]);
    }

    public function test_logout_creates_activity_record(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')->post('/logout');

        $this->assertDatabaseHas('activities', [
            'fk_admin_id' => $admin->id,
            'type' => 'success',
            'name' => 'Logout successful',
        ]);
    }

    public function test_login_records_ip_address(): void
    {
        $admin = Admin::factory()->create();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $activity = DB::table('activities')
            ->where('fk_admin_id', $admin->id)
            ->where('name', 'Login successful')
            ->first();

        $this->assertNotNull($activity);
        $this->assertNotEmpty($activity->ip_address);
    }

    public function test_activity_service_logs_success(): void
    {
        $admin = Admin::factory()->create();

        ActivityService::success('Test activity', $admin->id);

        $this->assertDatabaseHas('activities', [
            'fk_admin_id' => $admin->id,
            'type' => 'success',
            'name' => 'Test activity',
        ]);
    }

    public function test_activity_service_logs_warning(): void
    {
        $admin = Admin::factory()->create();

        ActivityService::warning('Something risky', $admin->id);

        $this->assertDatabaseHas('activities', [
            'fk_admin_id' => $admin->id,
            'type' => 'warning',
            'name' => 'Something risky',
        ]);
    }

    public function test_activity_service_logs_error(): void
    {
        $admin = Admin::factory()->create();

        ActivityService::error('Something failed', $admin->id);

        $this->assertDatabaseHas('activities', [
            'fk_admin_id' => $admin->id,
            'type' => 'error',
            'name' => 'Something failed',
        ]);
    }

    public function test_failed_login_does_not_log_login_successful(): void
    {
        $admin = Admin::factory()->create();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ]);

        $this->assertDatabaseMissing('activities', [
            'fk_admin_id' => $admin->id,
            'name' => 'Login successful',
        ]);
    }
}
