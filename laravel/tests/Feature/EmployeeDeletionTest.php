<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_with_payroll_cannot_be_deleted(): void
    {
        $admin = $this->adminWith('employees.delete');
        $employee = $this->employee();

        $this->payroll($employee);

        $response = $this->actingAs($admin, 'admin')
            ->deleteJson(route('employees.destroy', $employee));

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'This employee cannot be deleted because it has 1 payroll record. Mark the employee as inactive instead.',
            ]);

        $this->assertNotSoftDeleted($employee);
        $this->assertSame(1, Payroll::where('fk_employee_id', $employee->id)->count());
    }

    public function test_employee_with_payroll_redirects_with_error_for_normal_requests(): void
    {
        $admin = $this->adminWith('employees.delete');
        $employee = $this->employee();

        $this->payroll($employee);

        $this->actingAs($admin, 'admin')
            ->delete(route('employees.destroy', $employee))
            ->assertRedirect(route('employees.index'))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($employee);
    }

    public function test_employee_without_payroll_can_be_deleted(): void
    {
        $admin = $this->adminWith('employees.delete');
        $employee = $this->employee();

        $this->actingAs($admin, 'admin')
            ->deleteJson(route('employees.destroy', $employee))
            ->assertOk()
            ->assertJsonFragment(['message' => 'Employee deleted successfully.']);

        $this->assertSoftDeleted($employee);
        $this->assertSame($admin->id, $employee->fresh()->deleted_by);
    }

    public function test_soft_deleted_payroll_does_not_block_deletion(): void
    {
        $admin = $this->adminWith('employees.delete');
        $employee = $this->employee();

        $this->payroll($employee)->delete();

        $this->actingAs($admin, 'admin')
            ->deleteJson(route('employees.destroy', $employee))
            ->assertOk();

        $this->assertSoftDeleted($employee);
    }

    private function employee(array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'first_name' => 'Delete',
            'last_name' => 'Guard',
            'email' => 'delete-guard-'.uniqid().'@example.com',
            'job_title' => 'Engineer',
            'salary' => 1000,
            'hire_date' => '2026-01-01',
            'status' => 'active',
        ], $overrides));
    }

    private function payroll(Employee $employee, array $overrides = []): Payroll
    {
        return Payroll::create(array_merge([
            'fk_employee_id' => $employee->id,
            'basic_salary' => 1000,
            'allowances' => 0,
            'deductions' => 0,
            'net_salary' => 1000,
            'pay_date' => '2026-01-31',
        ], $overrides));
    }

    private function adminWith(string $permission): Admin
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'employee-delete-'.uniqid()]);
        $role->permissions()->attach(Permission::create(['name' => $permission]));
        $admin->roles()->attach($role);

        return $admin;
    }
}
