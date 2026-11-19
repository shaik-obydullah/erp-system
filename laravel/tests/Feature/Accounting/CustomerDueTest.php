<?php

namespace Tests\Feature\Accounting;

use App\Accounting\AccountingPoster;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Transaction;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_customer_due_page_uses_the_accounting_ledger(): void
    {
        $admin = $this->adminWith('customers.view');

        $customer = Customer::create([
            'name' => 'Ledger Customer',
            'email' => 'ledger-customer@example.com',
            'password' => 'password',
            'balance' => 0,
            'status' => 'active',
        ]);

        $attributed = $this->makeSale([
            'fk_user_id' => $customer->id,
            'invoice_id' => 'INV-CUSTOMER',
            'grand_total' => 1000,
            'paid_amount' => 750,
            'sale_due' => 250,
            'vat_amount' => 0,
        ]);
        app(AccountingPoster::class)->sale($attributed, 750, 250, 0);

        $walkIn = $this->makeSale([
            'invoice_id' => 'INV-WALKIN',
            'grand_total' => 500,
            'paid_amount' => 0,
            'sale_due' => 500,
            'vat_amount' => 0,
        ]);
        app(AccountingPoster::class)->sale($walkIn, 0, 500, 0);

        // The page must follow the ledger, not the sale's stored due column.
        $attributed->update(['sale_due' => 999]);

        $response = $this->actingAs($admin, 'admin')->get(route('customers.due.index'));

        $response->assertOk()
            ->assertSee('INV-CUSTOMER')
            ->assertSee('INV-WALKIN')
            ->assertSee('Ledger Customer')
            ->assertSee('Unassigned');

        $this->assertSame(750.0, $response->viewData('ledgerTotal'));
        $this->assertSame(250.0, $response->viewData('customerDue'));
        $this->assertSame(500.0, $response->viewData('unassignedDue'));
        $this->assertSame(750.0, $response->viewData('pageTotal'));
        $this->assertSame(0.0, $response->viewData('otherReceivable'));
        $this->assertTrue($response->viewData('reconciled'));
    }

    public function test_collecting_a_due_reduces_the_ledger_balance_on_the_page(): void
    {
        $admin = $this->adminWith('customers.view');

        $customer = Customer::create([
            'name' => 'Collecting Customer',
            'email' => 'collecting-customer@example.com',
            'password' => 'password',
            'balance' => 0,
            'status' => 'active',
        ]);

        $sale = $this->makeSale([
            'fk_user_id' => $customer->id,
            'grand_total' => 1000,
            'paid_amount' => 500,
            'sale_due' => 500,
            'vat_amount' => 0,
        ]);
        app(AccountingPoster::class)->sale($sale, 500, 500, 0);

        $transaction = Transaction::create([
            'date' => now()->toDateString(),
            'type' => Transaction::TYPE_SALE_DUE,
            'fk_reference_id' => $sale->id,
            'amount' => 200,
            'paid_amount' => 200,
            'due_amount' => 300,
        ]);
        app(AccountingPoster::class)->saleDue($sale, 200, $transaction->id);

        $response = $this->actingAs($admin, 'admin')->get(route('customers.due.index'));

        $response->assertOk();

        $this->assertSame(300.0, $response->viewData('ledgerTotal'));
        $this->assertSame(300.0, $response->viewData('customerDue'));
        $this->assertSame(0.0, $response->viewData('unassignedDue'));
        $this->assertSame(300.0, $response->viewData('pageTotal'));
        $this->assertTrue($response->viewData('reconciled'));
    }

    private function makeSale(array $overrides = []): Sale
    {
        return Sale::create(array_merge([
            'invoice_id' => 'INV-'.uniqid(),
            'type' => 'POS',
            'net_price' => 900,
            'vat_amount' => 100,
            'tax_amount' => 0,
            'shipping_cost' => 0,
            'discount_amount' => 0,
            'grand_total' => 1000,
            'paid_amount' => 600,
            'sale_due' => 400,
            'buy_price' => 350,
            'status' => 'completed',
        ], $overrides));
    }

    private function adminWith(string $permission): Admin
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'customer-due-'.uniqid()]);
        $role->permissions()->attach(Permission::create(['name' => $permission]));
        $admin->roles()->attach($role);

        return $admin;
    }
}
