<?php

namespace Tests\Feature\Accounting;

use App\Accounting\AccountingPoster;
use App\Models\Admin;
use App\Models\Cashbook;
use App\Models\Need;
use App\Models\Permission;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Supplier;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_with_outstanding_payable_cannot_be_deleted(): void
    {
        $admin = $this->adminWith('suppliers.delete');
        $supplier = $this->supplier(['balance' => -500]);

        $response = $this->actingAs($admin, 'admin')
            ->deleteJson(route('suppliers.destroy', $supplier));

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'This supplier cannot be deleted because it still has an outstanding payable of 500.00. Mark the supplier as inactive instead.']);

        $this->assertNotSoftDeleted($supplier);
    }

    public function test_supplier_with_ledger_activity_cannot_be_deleted(): void
    {
        $this->seed(ChartOfAccountsSeeder::class);

        $admin = $this->adminWith('suppliers.delete');
        $supplier = $this->supplier(['balance' => -750]);

        app(AccountingPoster::class)->supplierOpening($supplier);

        $response = $this->actingAs($admin, 'admin')
            ->deleteJson(route('suppliers.destroy', $supplier));

        $response->assertStatus(422);

        $this->assertTrue(collect($response->json('blockers'))->contains(
            fn (string $blocker) => str_contains($blocker, 'outstanding payable')
        ));
        $this->assertNotSoftDeleted($supplier);
    }

    public function test_supplier_with_cashbook_activity_cannot_be_deleted(): void
    {
        $admin = $this->adminWith('suppliers.delete');
        $supplier = $this->supplier();

        Cashbook::create([
            'table_name' => 'suppliers',
            'fk_reference_id' => $supplier->id,
            'description' => 'Advance payment',
            'in_amount' => 0,
            'out_amount' => 100,
            'amount_payable' => 0,
            'amount_receivable' => 100,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->deleteJson(route('suppliers.destroy', $supplier));

        $response->assertStatus(422);

        $this->assertTrue(collect($response->json('blockers'))->contains(
            fn (string $blocker) => str_contains($blocker, 'cashbook transaction')
        ));
        $this->assertNotSoftDeleted($supplier);
    }

    public function test_supplier_with_purchase_orders_cannot_be_deleted(): void
    {
        $admin = $this->adminWith('suppliers.delete');
        $supplier = $this->supplier();

        $need = Need::create([
            'description' => 'Stock replenishment',
            'date_identified' => now()->toDateString(),
        ]);

        PurchaseOrder::create([
            'fk_need_id' => $need->id,
            'fk_supplier_id' => $supplier->id,
            'order_number' => 'PO-DELETE-1',
            'total_amount' => 250,
            'due_amount' => 250,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->deleteJson(route('suppliers.destroy', $supplier));

        $response->assertStatus(422);

        $this->assertTrue(collect($response->json('blockers'))->contains(
            fn (string $blocker) => str_contains($blocker, 'purchase order')
        ));
        $this->assertNotSoftDeleted($supplier);
    }

    public function test_supplier_without_financial_activity_can_be_deleted(): void
    {
        $admin = $this->adminWith('suppliers.delete');
        $supplier = $this->supplier();

        $this->actingAs($admin, 'admin')
            ->deleteJson(route('suppliers.destroy', $supplier))
            ->assertOk()
            ->assertJsonFragment(['message' => 'Supplier deleted successfully.']);

        $this->assertSoftDeleted($supplier);
        $this->assertSame($admin->id, $supplier->fresh()->deleted_by);
    }

    private function supplier(array $overrides = []): Supplier
    {
        return Supplier::create(array_merge([
            'name' => 'Delete Guard Supplier',
            'email' => 'delete-guard-'.uniqid().'@example.com',
            'password' => 'password',
            'balance' => 0,
            'status' => 'active',
        ], $overrides));
    }

    private function adminWith(string $permission): Admin
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'supplier-delete-'.uniqid()]);
        $role->permissions()->attach(Permission::create(['name' => $permission]));
        $admin->roles()->attach($role);

        return $admin;
    }
}
