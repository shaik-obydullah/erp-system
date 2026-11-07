<?php

namespace Tests\Feature\Accounting;

use App\Accounting\AccountingPoster;
use App\Models\Admin;
use App\Models\BillOfMaterial;
use App\Models\Campaign;
use App\Models\Cashbook;
use App\Models\Employee;
use App\Models\FixedAsset;
use App\Models\JournalEntry;
use App\Models\Need;
use App\Models\Payroll;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Production;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\ShipmentReturn;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Services\LedgerProjector;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalPostingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_fixed_asset_create_update_and_delete_post_to_accounting(): void
    {
        $admin = $this->adminWith();

        $this->actingAs($admin, 'admin')->post(route('fixed-assets.store'), [
            'name' => 'Delivery van',
            'price' => 1200,
            'description' => 'Used for deliveries',
        ])->assertRedirect(route('fixed-assets.index'));

        $asset = FixedAsset::firstOrFail();
        $created = $this->entry("fixed_asset:{$asset->id}:v1");
        $lines = $this->lines($created);

        $this->assertSame(1200.0, $lines['fixed_assets']['debit']);
        $this->assertSame(1200.0, $lines['cash']['credit']);
        $this->assertSame($created->id, $asset->journal_entry_id);
        $this->assertDatabaseHas('transactions', [
            'type' => Transaction::TYPE_FIXED_ASSET,
            'fk_reference_id' => $asset->id,
            'journal_entry_id' => $created->id,
        ]);
        $this->assertDatabaseHas('cashbook', [
            'table_name' => 'fixed_assets',
            'fk_reference_id' => $asset->id,
            'journal_entry_id' => $created->id,
        ]);

        $this->actingAs($admin, 'admin')->put(route('fixed-assets.update', $asset), [
            'name' => 'Delivery van',
            'price' => 1500,
            'description' => 'Used for deliveries',
        ])->assertRedirect(route('fixed-assets.index'));

        $asset->refresh();

        $this->assertSame(2, $asset->accounting_version);
        $this->assertNotNull($this->entry("fixed_asset:{$asset->id}:v1:reversal"));
        $this->assertSame(1500.0, $this->lines($this->entry("fixed_asset:{$asset->id}:v2"))['fixed_assets']['debit']);
        $this->assertSame(1500.0, $this->lines($this->entry("fixed_asset:{$asset->id}:v2"))['cash']['credit']);
        $this->assertSoftDeleted('transactions', ['journal_entry_id' => $created->id]);
        $this->assertSoftDeleted('cashbook', ['journal_entry_id' => $created->id]);

        $this->actingAs($admin, 'admin')->delete(route('fixed-assets.destroy', $asset))
            ->assertRedirect(route('fixed-assets.index'));

        $this->assertSoftDeleted('fixed_assets', ['id' => $asset->id]);
        $this->assertNotNull($this->entry("fixed_asset:{$asset->id}:v2:reversal"));
    }

    public function test_payroll_create_update_and_delete_post_salary_to_accounting(): void
    {
        $admin = $this->adminWith();
        $employee = $this->makeEmployee();

        $this->actingAs($admin, 'admin')->post(route('payrolls.store'), [
            'fk_employee_id' => $employee->id,
            'basic_salary' => 1000,
            'allowances' => 200,
            'deductions' => 50,
            'pay_date' => '2026-11-01',
        ])->assertRedirect(route('payrolls.index'));

        $payroll = Payroll::firstOrFail();
        $created = $this->entry("payroll:{$payroll->id}:v1");
        $lines = $this->lines($created);

        $this->assertSame(1150.0, $lines['salaries_expense']['debit']);
        $this->assertSame(1150.0, $lines['cash']['credit']);
        $this->assertDatabaseHas('transactions', [
            'type' => Transaction::TYPE_SALARY_PAYMENT,
            'fk_reference_id' => $payroll->id,
            'journal_entry_id' => $created->id,
        ]);
        $this->assertDatabaseHas('cashbook', [
            'table_name' => 'payrolls',
            'fk_reference_id' => $payroll->id,
            'journal_entry_id' => $created->id,
        ]);

        $this->actingAs($admin, 'admin')->put(route('payrolls.update', $payroll), [
            'fk_employee_id' => $employee->id,
            'basic_salary' => 1000,
            'allowances' => 250,
            'deductions' => 50,
            'pay_date' => '2026-11-01',
        ])->assertRedirect(route('payrolls.index'));

        $payroll->refresh();

        $this->assertSame(2, $payroll->accounting_version);
        $this->assertSame(1200.0, $this->lines($this->entry("payroll:{$payroll->id}:v2"))['salaries_expense']['debit']);
        $this->assertSoftDeleted('transactions', ['journal_entry_id' => $created->id]);

        $this->actingAs($admin, 'admin')->delete(route('payrolls.destroy', $payroll))
            ->assertRedirect(route('payrolls.index'));

        $this->assertSoftDeleted('payrolls', ['id' => $payroll->id]);
        $this->assertNotNull($this->entry("payroll:{$payroll->id}:v2:reversal"));
    }

    public function test_campaign_budget_is_a_memo_but_actual_spend_posts(): void
    {
        $admin = $this->adminWith();

        $this->actingAs($admin, 'admin')->post(route('campaigns.store'), [
            'name' => 'Autumn launch',
            'description' => 'Launch campaign',
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-31',
            'status' => 'planned',
            'budget' => 5000,
            'spent_amount' => 0,
        ])->assertRedirect(route('campaigns.index'));

        $campaign = Campaign::firstOrFail();

        $this->assertSame(0, JournalEntry::where('source_type', 'campaign')->count());

        $this->actingAs($admin, 'admin')->put(route('campaigns.update', $campaign), [
            'name' => 'Autumn launch',
            'description' => 'Launch campaign',
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-31',
            'status' => 'active',
            'budget' => 5000,
            'spent_amount' => 800,
        ])->assertRedirect(route('campaigns.index'));

        $campaign->refresh();
        $spend = $this->entry("campaign:{$campaign->id}:v2");

        $this->assertSame(2, $campaign->accounting_version);
        $this->assertSame(800.0, $this->lines($spend)['marketing_expense']['debit']);
        $this->assertSame(800.0, $this->lines($spend)['cash']['credit']);
        $this->assertDatabaseHas('transactions', [
            'type' => Transaction::TYPE_CAMPAIGN_EXPENSE,
            'fk_reference_id' => $campaign->id,
            'journal_entry_id' => $spend->id,
        ]);

        $this->actingAs($admin, 'admin')->delete(route('campaigns.destroy', $campaign))
            ->assertRedirect(route('campaigns.index'));

        $this->assertSoftDeleted('campaigns', ['id' => $campaign->id]);
        $this->assertNotNull($this->entry("campaign:{$campaign->id}:v2:reversal"));
    }

    public function test_production_capitalises_cost_into_inventory(): void
    {
        $admin = $this->adminWith();
        $product = $this->makeProduct();
        $bom = BillOfMaterial::create(['fk_product_id' => $product->id, 'name' => 'Widget BOM']);

        $this->actingAs($admin, 'admin')->post(route('productions.store'), [
            'fk_bill_of_material_id' => $bom->id,
            'production_cost' => 300,
            'other_cost' => 50,
            'expected_profit' => 100,
            'quantity' => 10,
        ])->assertRedirect(route('productions.index'));

        $production = Production::firstOrFail();
        $created = $this->entry("production:{$production->id}:v1");

        $this->assertSame(350.0, $this->lines($created)['inventory']['debit']);
        $this->assertSame(350.0, $this->lines($created)['cash']['credit']);
        $this->assertDatabaseHas('transactions', [
            'type' => Transaction::TYPE_BOM_EXPENSE,
            'fk_reference_id' => $production->id,
            'journal_entry_id' => $created->id,
        ]);

        $this->actingAs($admin, 'admin')->put(route('productions.update', $production), [
            'fk_bill_of_material_id' => $bom->id,
            'production_cost' => 400,
            'other_cost' => 50,
            'expected_profit' => 100,
            'quantity' => 10,
        ])->assertRedirect(route('productions.index'));

        $production->refresh();

        $this->assertSame(2, $production->accounting_version);
        $this->assertSame(450.0, $this->lines($this->entry("production:{$production->id}:v2"))['inventory']['debit']);
        $this->assertSoftDeleted('transactions', ['journal_entry_id' => $created->id]);

        $this->actingAs($admin, 'admin')->delete(route('productions.destroy', $production))
            ->assertRedirect(route('productions.index'));

        $this->assertSoftDeleted('productions', ['id' => $production->id]);
        $this->assertNotNull($this->entry("production:{$production->id}:v2:reversal"));
    }

    public function test_stock_creation_posts_opening_stock_without_moving_cash(): void
    {
        $admin = $this->adminWith(['stocks.save', 'stocks.edit', 'stocks.delete']);
        $product = $this->makeProduct();

        $this->actingAs($admin, 'admin')->post(route('stocks.store'), [
            'fk_product_id' => $product->id,
            'quantity' => 10,
            'buy_price' => 20,
            'sale_price' => 40,
            'vat_code' => 'standard',
            'vat_rate' => 20,
            'status' => 'active',
        ])->assertRedirect(route('stocks.index'));

        $stock = Stock::firstOrFail();
        $created = $this->entry("stock:{$stock->id}:v1");

        $this->assertSame('standard', $stock->vat_code);
        $this->assertSame(20.00, (float) $stock->vat_rate);

        $this->assertSame(200.0, $this->lines($created)['inventory']['debit']);
        $this->assertSame(200.0, $this->lines($created)['opening_balance_equity']['credit']);
        $this->assertSame(0, Transaction::where('type', Transaction::TYPE_STOCK_IN)->count());
        $this->assertSame(0, Cashbook::where('table_name', 'stocks')->count());

        $this->actingAs($admin, 'admin')->put(route('stocks.update', $stock), [
            'fk_product_id' => $product->id,
            'quantity' => 12,
            'buy_price' => 20,
            'sale_price' => 40,
            'vat_code' => 'zero',
            'vat_rate' => 0,
            'status' => 'active',
        ])->assertRedirect(route('stocks.index'));

        $stock->refresh();

        $this->assertSame('zero', $stock->vat_code);
        $this->assertSame(0.00, (float) $stock->vat_rate);
        $this->assertSame(2, $stock->accounting_version);
        $this->assertSame(240.0, $this->lines($this->entry("stock:{$stock->id}:v2"))['inventory']['debit']);

        $this->actingAs($admin, 'admin')->delete(route('stocks.destroy', $stock))
            ->assertRedirect(route('stocks.index'));

        $this->assertSoftDeleted('stocks', ['id' => $stock->id]);
        $this->assertNotNull($this->entry("stock:{$stock->id}:v2:reversal"));
    }

    public function test_supplier_opening_balance_posts_advance_or_payable(): void
    {
        $admin = $this->adminWith(['suppliers.save', 'suppliers.edit', 'suppliers.delete']);

        $this->actingAs($admin, 'admin')->post(route('suppliers.store'), [
            'name' => 'Advance Supplier',
            'email' => 'advance@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'balance' => 500,
            'status' => 'active',
        ])->assertRedirect(route('suppliers.index'));

        $advance = Supplier::where('email', 'advance@example.com')->firstOrFail();
        $advanceEntry = $this->entry("supplier_opening:{$advance->id}:v1");

        $this->assertSame(500.0, $this->lines($advanceEntry)['supplier_advances']['debit']);
        $this->assertSame(500.0, $this->lines($advanceEntry)['opening_balance_equity']['credit']);

        $this->actingAs($admin, 'admin')->post(route('suppliers.store'), [
            'name' => 'Payable Supplier',
            'email' => 'payable@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'balance' => -300,
            'status' => 'active',
        ])->assertRedirect(route('suppliers.index'));

        $payable = Supplier::where('email', 'payable@example.com')->firstOrFail();
        $payableEntry = $this->entry("supplier_opening:{$payable->id}:v1");

        $this->assertSame(300.0, $this->lines($payableEntry)['opening_balance_equity']['debit']);
        $this->assertSame(300.0, $this->lines($payableEntry)['accounts_payable']['credit']);

        $this->actingAs($admin, 'admin')->put(route('suppliers.update', $advance), [
            'name' => 'Advance Supplier',
            'email' => 'advance@example.com',
            'balance' => -200,
            'status' => 'active',
        ])->assertRedirect(route('suppliers.index'));

        $advance->refresh();

        $this->assertSame(2, $advance->accounting_version);
        $this->assertSame(200.0, $this->lines($this->entry("supplier_opening:{$advance->id}:v2"))['accounts_payable']['credit']);
        $this->assertNotNull($this->entry("supplier_opening:{$advance->id}:v1:reversal"));

        $this->actingAs($admin, 'admin')->delete(route('suppliers.destroy', $payable))
            ->assertRedirect(route('suppliers.index'));

        $this->assertSoftDeleted('suppliers', ['id' => $payable->id]);
        $this->assertNotNull($this->entry("supplier_opening:{$payable->id}:v1:reversal"));
    }

    public function test_shipment_return_posts_payable_advance_and_inventory_when_processed(): void
    {
        $admin = $this->adminWith();
        $supplier = $this->makeSupplier();
        $order = $this->makePurchaseOrder($supplier, dueAmount: 600);

        $this->actingAs($admin, 'admin')->post(route('shipment-returns.store'), [
            'fk_po_id' => $order->id,
            'invoice_amount' => 400,
            'return_reason' => 'damaged',
            'status' => 'pending',
            'remark' => 'Waiting for approval',
        ])->assertRedirect(route('shipment-returns.index'));

        $return = ShipmentReturn::firstOrFail();

        $this->assertSame(0, JournalEntry::where('source_type', 'shipment_return')->count());

        $this->actingAs($admin, 'admin')->put(route('shipment-returns.update', $return), [
            'fk_po_id' => $order->id,
            'invoice_amount' => 400,
            'return_reason' => 'damaged',
            'status' => 'processed',
            'remark' => 'Approved',
        ])->assertRedirect(route('shipment-returns.index'));

        $return->refresh();
        $entry = $this->entry("shipment_return:{$return->id}:v2");
        $lines = $this->lines($entry);

        $this->assertSame(2, $return->accounting_version);
        $this->assertSame(400.0, $lines['accounts_payable']['debit']);
        $this->assertSame(400.0, $lines['inventory']['credit']);

        $this->actingAs($admin, 'admin')->put(route('shipment-returns.update', $return), [
            'fk_po_id' => $order->id,
            'invoice_amount' => 700,
            'return_reason' => 'damaged',
            'status' => 'completed',
            'remark' => 'Approved',
        ])->assertRedirect(route('shipment-returns.index'));

        $return->refresh();
        $updated = $this->lines($this->entry("shipment_return:{$return->id}:v3"));

        $this->assertSame(600.0, $updated['accounts_payable']['debit']);
        $this->assertSame(100.0, $updated['supplier_advances']['debit']);
        $this->assertSame(700.0, $updated['inventory']['credit']);

        $this->actingAs($admin, 'admin')->delete(route('shipment-returns.destroy', $return))
            ->assertRedirect(route('shipment-returns.index'));

        $this->assertSoftDeleted('shipment_returns', ['id' => $return->id]);
        $this->assertNotNull($this->entry("shipment_return:{$return->id}:v3:reversal"));
    }

    public function test_shipment_return_uses_supplier_advance_when_purchase_has_no_due(): void
    {
        $admin = $this->adminWith();
        $supplier = $this->makeSupplier();
        $order = $this->makePurchaseOrder($supplier, dueAmount: 0);

        $this->actingAs($admin, 'admin')->post(route('shipment-returns.store'), [
            'fk_po_id' => $order->id,
            'invoice_amount' => 250,
            'return_reason' => 'excess_quantity',
            'status' => 'completed',
            'remark' => null,
        ])->assertRedirect(route('shipment-returns.index'));

        $return = ShipmentReturn::firstOrFail();
        $lines = $this->lines($this->entry("shipment_return:{$return->id}:v1"));

        $this->assertSame(250.0, $lines['supplier_advances']['debit']);
        $this->assertSame(250.0, $lines['inventory']['credit']);
        $this->assertArrayNotHasKey('accounts_payable', $lines);
    }

    public function test_deleting_a_sale_return_reverses_its_refund_and_restock_entries(): void
    {
        $admin = $this->adminWith(['sales.delete']);
        $sale = $this->makeSale();
        $stock = Stock::create([
            'quantity' => 10,
            'buy_price' => 25,
            'sale_price' => 50,
            'status' => 'active',
        ]);

        $return = SaleReturn::create([
            'fk_sale_id' => $sale->id,
            'fk_stock_id' => $stock->id,
            'quantity' => 2,
            'refund_amount' => 180,
            'reason' => 'damaged',
            'status' => 'approved',
        ]);

        app(AccountingPoster::class)->saleReturn($return, $sale);

        $this->assertNotNull($this->entry("sale_return:{$return->id}:refund"));
        $this->assertNotNull($this->entry("sale_return:{$return->id}:restock"));

        $this->actingAs($admin, 'admin')->delete(route('sale-returns.destroy', $return))
            ->assertRedirect(route('sale-returns.index'));

        $this->assertSoftDeleted('sale_returns', ['id' => $return->id]);
        $this->assertNotNull($this->entry("sale_return:{$return->id}:refund:reversal"));
        $this->assertNotNull($this->entry("sale_return:{$return->id}:restock:reversal"));
    }

    public function test_legacy_operational_rows_can_be_backfilled_idempotently(): void
    {
        $asset = FixedAsset::create([
            'name' => 'Legacy laptop',
            'price' => 900,
        ]);

        $stock = Stock::create([
            'quantity' => 5,
            'buy_price' => 10,
            'sale_price' => 20,
            'status' => 'active',
        ]);

        $projector = app(LedgerProjector::class);
        $projector->run(['fixed_assets', 'opening_stock']);

        $this->assertSame(900.0, $this->lines($this->entry("fixed_asset:{$asset->id}:v1"))['fixed_assets']['debit']);
        $this->assertSame(50.0, $this->lines($this->entry("stock:{$stock->id}:v1"))['inventory']['debit']);

        $count = JournalEntry::count();
        $projector->run(['fixed_assets', 'opening_stock']);

        $this->assertSame($count, JournalEntry::count());
    }

    private function adminWith(array $permissions = []): Admin
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'operational-'.uniqid()]);

        foreach ($permissions as $permission) {
            $role->permissions()->attach(Permission::create(['name' => $permission]));
        }

        $admin->roles()->attach($role);

        return $admin;
    }

    private function makeEmployee(): Employee
    {
        return Employee::create([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada-'.uniqid().'@example.com',
            'job_title' => 'Engineer',
            'salary' => 1000,
            'hire_date' => '2026-01-01',
            'status' => 'active',
        ]);
    }

    private function makeProduct(): Product
    {
        return Product::create([
            'name' => 'Widget '.uniqid(),
            'status' => 'active',
        ]);
    }

    private function makeSupplier(): Supplier
    {
        return Supplier::create([
            'name' => 'Supplier '.uniqid(),
            'email' => uniqid().'@example.com',
            'password' => 'password',
            'balance' => 0,
            'status' => 'active',
        ]);
    }

    private function makePurchaseOrder(Supplier $supplier, float $dueAmount): PurchaseOrder
    {
        $need = Need::create([
            'description' => 'Stock replenishment',
            'date_identified' => '2026-11-01',
        ]);

        return PurchaseOrder::create([
            'fk_need_id' => $need->id,
            'fk_supplier_id' => $supplier->id,
            'order_number' => 'PO-'.uniqid(),
            'total_amount' => 1000,
            'due_amount' => $dueAmount,
        ]);
    }

    private function makeSale(): Sale
    {
        return Sale::create([
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
        ]);
    }

    private function entry(string $sourceKey): JournalEntry
    {
        return JournalEntry::with('lines.account')->where('source_key', $sourceKey)->firstOrFail();
    }

    /**
     * @return array<string, array{debit: float, credit: float}>
     */
    private function lines(JournalEntry $entry): array
    {
        return $entry->lines->mapWithKeys(function ($line) {
            return [$line->account->system_key => [
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
            ]];
        })->all();
    }
}
