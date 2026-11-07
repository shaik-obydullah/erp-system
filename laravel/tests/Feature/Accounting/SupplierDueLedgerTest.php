<?php

namespace Tests\Feature\Accounting;

use App\Accounting\AccountingPoster;
use App\Accounting\LedgerService;
use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Supplier;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierDueLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_supplier_due_page_uses_the_accounting_ledger(): void
    {
        $admin = $this->adminWith('suppliers.view');

        $payable = $this->makeSupplier([
            'name' => 'Payable Supplier',
            'email' => 'payable-supplier@example.com',
            'balance' => -300,
        ]);
        app(AccountingPoster::class)->supplierOpening($payable);

        $advance = $this->makeSupplier([
            'name' => 'Advance Supplier',
            'email' => 'advance-supplier@example.com',
            'balance' => 500,
        ]);
        app(AccountingPoster::class)->supplierOpening($advance);

        $inactive = $this->makeSupplier([
            'name' => 'Inactive Supplier',
            'email' => 'inactive-supplier@example.com',
            'balance' => -200,
            'status' => 'inactive',
        ]);
        app(AccountingPoster::class)->supplierOpening($inactive);

        $response = $this->actingAs($admin, 'admin')->get(route('suppliers.due.index'));

        $response->assertOk()
            ->assertSee('Payable Supplier')
            ->assertSee('Advance Supplier')
            ->assertSee('Inactive Supplier');

        $this->assertSame(500.0, $response->viewData('ledgerPayable'));
        $this->assertSame(500.0, $response->viewData('ledgerAdvances'));
        $this->assertSame(500.0, $response->viewData('supplierPayable'));
        $this->assertSame(0.0, $response->viewData('unassignedPayable'));
        $this->assertSame(0.0, $response->viewData('ledgerPosition'));
        $this->assertSame(0.0, $response->viewData('operationalPosition'));
        $this->assertSame(0.0, $response->viewData('difference'));
        $this->assertTrue($response->viewData('reconciled'));
        $this->assertSame(3, $response->viewData('suppliers')->total());
    }

    public function test_page_ignores_a_stale_supplier_balance(): void
    {
        $admin = $this->adminWith('suppliers.view');

        $this->makeSupplier([
            'name' => 'Stale Supplier',
            'email' => 'stale-supplier@example.com',
            'balance' => -300,
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('suppliers.due.index'));

        $response->assertOk()
            ->assertDontSee('Stale Supplier')
            ->assertSee('disagree');

        $this->assertSame(0.0, $response->viewData('ledgerPayable'));
        $this->assertSame(0.0, $response->viewData('supplierPayable'));
        $this->assertSame(-300.0, $response->viewData('operationalPosition'));
        $this->assertSame(300.0, $response->viewData('difference'));
        $this->assertFalse($response->viewData('reconciled'));
        $this->assertSame(0, $response->viewData('suppliers')->total());
    }

    public function test_page_includes_deleted_suppliers_from_the_ledger(): void
    {
        $admin = $this->adminWith('suppliers.view');

        $supplier = $this->makeSupplier([
            'name' => 'Deleted Supplier',
            'email' => 'deleted-supplier@example.com',
            'balance' => 500,
        ]);
        app(AccountingPoster::class)->supplierOpening($supplier);

        // Simulate a legacy supplier that was removed without reversing its
        // ledger position, so the outstanding balance must still be listed.
        $supplier->delete();

        $response = $this->actingAs($admin, 'admin')->get(route('suppliers.due.index'));

        $response->assertOk()
            ->assertSee('Deleted Supplier')
            ->assertSee('deleted');

        $this->assertSame(500.0, $response->viewData('ledgerAdvances'));
        $this->assertSame(1, $response->viewData('suppliers')->total());
        $this->assertTrue($response->viewData('reconciled'));
    }

    public function test_page_reports_unassigned_payables(): void
    {
        $admin = $this->adminWith('suppliers.view');

        app(LedgerService::class)->post(
            'purchaseOrder',
            'purchase_order',
            null,
            'test:unassigned-payable',
            now(),
            'Unassigned purchase',
            [
                ['account' => 'inventory', 'debit' => 100],
                ['account' => 'accounts_payable', 'credit' => 100],
            ]
        );

        $response = $this->actingAs($admin, 'admin')->get(route('suppliers.due.index'));

        $response->assertOk()
            ->assertSee('Unassigned payables');

        $this->assertSame(100.0, $response->viewData('ledgerPayable'));
        $this->assertSame(0.0, $response->viewData('supplierPayable'));
        $this->assertSame(100.0, $response->viewData('unassignedPayable'));
        $this->assertSame(0.0, $response->viewData('ledgerPosition'));
        $this->assertTrue($response->viewData('reconciled'));
        $this->assertSame(0, $response->viewData('suppliers')->total());
    }

    private function makeSupplier(array $overrides = []): Supplier
    {
        return Supplier::create(array_merge([
            'name' => 'Supplier',
            'email' => 'supplier-'.uniqid().'@example.com',
            'password' => 'password',
            'balance' => 0,
            'status' => 'active',
        ], $overrides));
    }

    private function adminWith(string $permission): Admin
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'supplier-due-'.uniqid()]);
        $role->permissions()->attach(Permission::create(['name' => $permission]));
        $admin->roles()->attach($role);

        return $admin;
    }
}
