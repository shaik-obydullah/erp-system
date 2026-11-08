<?php

namespace Tests\Feature\Accounting;

use App\Models\Admin;
use App\Models\Income;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Stock;
use App\Models\Transaction;
use App\Services\SaleIncomeTracker;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleIncomeTrackerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_approving_a_return_reduces_the_sale_income_net_of_returns(): void
    {
        $admin = $this->adminWith(['sales.save', 'sales.edit']);
        [$sale, $transaction, $income] = $this->makeSaleWithIncome();
        $stock = $this->makeStock();

        $this->actingAs($admin, 'admin')->post(route('sale-returns.store'), [
            'fk_sale_id' => $sale->id,
            'fk_stock_id' => $stock->id,
            'quantity' => 1,
            'refund_amount' => 240,
            'reason' => 'customer_request',
        ])->assertRedirect(route('sale-returns.index'));

        $return = SaleReturn::firstOrFail();

        $this->actingAs($admin, 'admin')->post(route('sale-returns.approve', $return))
            ->assertRedirect(route('sale-returns.index'));

        $income->refresh();

        $this->assertSame(760.0, (float) $income->amount);
        $this->assertSame(700.0, (float) $income->net_amount);
        $this->assertSame(60.0, (float) $income->vat_amount);
        $this->assertSame($admin->id, (int) $income->updated_by);

        // The sale-time transaction stays immutable for the ledger audit trail.
        $this->assertSame(1000.0, (float) $transaction->fresh()->amount);
        $this->assertDatabaseHas('activities', [
            'name' => 'Updated Sale Income',
            'subject_type' => Income::class,
            'subject_id' => $income->id,
        ]);
    }

    public function test_sync_is_idempotent_and_accumulates_multiple_returns(): void
    {
        $sale = $this->makeSaleWithIncome()[0];
        $tracker = app(SaleIncomeTracker::class);

        SaleReturn::create([
            'fk_sale_id' => $sale->id,
            'fk_stock_id' => $this->makeStock()->id,
            'quantity' => 1,
            'refund_amount' => 240,
            'vat_code' => 'standard',
            'vat_rate' => 20,
            'net_amount' => 200,
            'vat_amount' => 40,
            'status' => 'approved',
        ]);

        SaleReturn::create([
            'fk_sale_id' => $sale->id,
            'fk_stock_id' => $this->makeStock()->id,
            'quantity' => 1,
            'refund_amount' => 120,
            'vat_code' => 'standard',
            'vat_rate' => 20,
            'net_amount' => 100,
            'vat_amount' => 20,
            'status' => 'approved',
        ]);

        // A pending return must not affect the income row.
        SaleReturn::create([
            'fk_sale_id' => $sale->id,
            'fk_stock_id' => $this->makeStock()->id,
            'quantity' => 1,
            'refund_amount' => 60,
            'vat_code' => 'standard',
            'vat_rate' => 20,
            'net_amount' => 50,
            'vat_amount' => 10,
            'status' => 'pending',
        ]);

        $income = $tracker->sync($sale);

        $this->assertSame(640.0, (float) $income->amount);
        $this->assertSame(600.0, (float) $income->net_amount);
        $this->assertSame(40.0, (float) $income->vat_amount);

        // Re-running recalculates from the immutable originals, so nothing drifts.
        $tracker->sync($sale);

        $income->refresh();
        $this->assertSame(640.0, (float) $income->amount);
        $this->assertSame(600.0, (float) $income->net_amount);
        $this->assertSame(40.0, (float) $income->vat_amount);
    }

    /**
     * @return array{0: Sale, 1: Transaction, 2: Income}
     */
    private function makeSaleWithIncome(): array
    {
        $sale = Sale::create([
            'invoice_id' => 'INV-'.uniqid(),
            'type' => 'POS',
            'net_price' => 900,
            'vat_amount' => 100,
            'tax_amount' => 0,
            'shipping_cost' => 0,
            'discount_amount' => 0,
            'grand_total' => 1000,
            'paid_amount' => 1000,
            'sale_due' => 0,
            'buy_price' => 350,
            'status' => 'completed',
        ]);

        $transaction = Transaction::create([
            'date' => '2026-11-01',
            'type' => Transaction::TYPE_SALE_INCOME,
            'fk_reference_id' => $sale->id,
            'amount' => 1000,
            'paid_amount' => 1000,
            'due_amount' => 0,
        ]);

        $income = Income::create([
            'table_name' => 'sales',
            'fk_transaction_id' => $transaction->id,
            'description' => "Sale {$sale->invoice_id}",
            'amount' => 1000,
            'net_amount' => 900,
            'vat_amount' => 100,
        ]);

        return [$sale, $transaction, $income];
    }

    private function makeStock(): Stock
    {
        $product = Product::create([
            'name' => 'Widget '.uniqid(),
            'status' => 'active',
        ]);

        return Stock::create([
            'fk_product_id' => $product->id,
            'quantity' => 5,
            'buy_price' => 20,
            'sale_price' => 40,
            'vat_code' => 'standard',
            'vat_rate' => 20,
            'status' => 'active',
        ]);
    }

    private function adminWith(array $permissions = []): Admin
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'income-tracker-'.uniqid()]);

        foreach ($permissions as $permission) {
            $role->permissions()->attach(Permission::create(['name' => $permission]));
        }

        $admin->roles()->attach($role);

        return $admin;
    }
}
