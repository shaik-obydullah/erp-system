<?php

namespace Tests\Feature\Accounting;

use App\Accounting\AccountingPoster;
use App\Models\Admin;
use App\Models\Expense;
use App\Models\Income;
use App\Models\JournalEntry;
use App\Models\Need;
use App\Models\Permission;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SaleReturn;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\Transaction;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VatPostingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_income_with_vat_credits_output_vat(): void
    {
        $income = $this->makeIncome(120.00, 100.00, 20.00, 'Consulting');

        app(AccountingPoster::class)->income($income);

        $lines = $this->lines("income:{$income->id}:income");

        $this->assertSame(120.00, $lines['cash']['debit']);
        $this->assertSame(100.00, $lines['general_income']['credit']);
        $this->assertSame(20.00, $lines['sales_tax_payable']['credit']);
    }

    public function test_expense_with_vat_debits_input_vat(): void
    {
        $expense = $this->makeExpense(60.00, 50.00, 10.00, 'Utilities');

        app(AccountingPoster::class)->expense($expense);

        $lines = $this->lines("expense:{$expense->id}:expense");

        $this->assertSame(50.00, $lines['general_expense']['debit']);
        $this->assertSame(10.00, $lines['input_vat']['debit']);
        $this->assertSame(60.00, $lines['cash']['credit']);
    }

    public function test_sale_return_with_vat_reverses_output_vat(): void
    {
        $sale = $this->makeSale();
        $stock = $this->makeStock();

        $return = SaleReturn::create([
            'fk_sale_id' => $sale->id,
            'fk_stock_id' => $stock->id,
            'quantity' => 1,
            'refund_amount' => 120.00,
            'vat_code' => 'standard',
            'vat_rate' => 20.00,
            'net_amount' => 100.00,
            'vat_amount' => 20.00,
            'reason' => 'customer_request',
            'status' => 'approved',
        ]);

        app(AccountingPoster::class)->saleReturn($return, $sale);

        $lines = $this->lines("sale_return:{$return->id}:refund");

        $this->assertSame(100.00, $lines['sales_returns']['debit']);
        $this->assertSame(20.00, $lines['sales_tax_payable']['debit']);
        $this->assertSame(120.00, $lines['cash']['credit']);
    }

    public function test_purchase_order_recovers_input_vat_and_splits_payment(): void
    {
        $supplier = Supplier::create([
            'name' => 'VAT Supplier',
            'email' => 'vat-supplier@example.com',
            'password' => 'password',
            'balance' => 0,
            'status' => 'active',
        ]);

        $need = Need::create([
            'description' => 'Stock replenishment',
            'date_identified' => now()->toDateString(),
        ]);

        $order = PurchaseOrder::create([
            'fk_need_id' => $need->id,
            'fk_supplier_id' => $supplier->id,
            'order_number' => 'PO-VAT-1',
            'total_amount' => 240.00,
            'net_amount' => 200.00,
            'vat_amount' => 40.00,
            'vat_code' => 'standard',
            'vat_rate' => 20.00,
            'due_amount' => 100.00,
        ]);

        app(AccountingPoster::class)->purchaseOrder($order);

        $lines = $this->lines("purchase_order:{$order->id}:purchase");

        $this->assertSame(200.00, $lines['inventory']['debit']);
        $this->assertSame(40.00, $lines['input_vat']['debit']);
        $this->assertSame(140.00, $lines['cash']['credit']);
        $this->assertSame(100.00, $lines['accounts_payable']['credit']);
        $this->assertSame($this->entry("purchase_order:{$order->id}:purchase")->id, $order->fresh()->journal_entry_id);
    }

    public function test_vat_return_reports_nine_boxes_and_reconciles_to_the_ledger(): void
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'vat-accountant']);
        $admin->roles()->attach($role);
        $role->permissions()->attach(Permission::create(['name' => 'dashboard.view']));

        $sale = $this->makeSale();
        $sale->update([
            'net_price' => 100.00,
            'vat_amount' => 20.00,
            'grand_total' => 120.00,
            'paid_amount' => 120.00,
            'sale_due' => 0,
        ]);

        $stock = $this->makeStock();

        SaleDetail::create([
            'fk_sale_id' => $sale->id,
            'fk_stock_id' => $stock->id,
            'stock_name' => 'VAT Product',
            'sale_stock' => 1,
            'subtotal' => 120.00,
            'vat_code' => 'standard',
            'vat_rate' => 20.00,
            'net_amount' => 100.00,
            'vat_amount' => 20.00,
            'gross_amount' => 120.00,
        ]);

        Transaction::create([
            'date' => now()->toDateString(),
            'type' => Transaction::TYPE_SALE_INCOME,
            'fk_reference_id' => $sale->id,
            'amount' => 120.00,
            'paid_amount' => 120.00,
            'due_amount' => 0,
        ]);

        app(AccountingPoster::class)->sale($sale, 120.00, 0, 0);

        $expense = $this->makeExpense(60.00, 50.00, 10.00, 'VAT Expense');
        app(AccountingPoster::class)->expense($expense);

        $response = $this->actingAs($admin, 'admin')
            ->getJson(route('reports.vat-return', [
                'start_date' => now()->startOfQuarter()->toDateString(),
                'end_date' => now()->endOfQuarter()->toDateString(),
            ]));

        $response->assertOk();

        $boxes = $response->json('boxes');

        $this->assertEquals(20.00, $boxes[1]['value']);
        $this->assertEquals(0.00, $boxes[2]['value']);
        $this->assertEquals(20.00, $boxes[3]['value']);
        $this->assertEquals(10.00, $boxes[4]['value']);
        $this->assertEquals(10.00, $boxes[5]['value']);
        $this->assertEquals(100.00, $boxes[6]['value']);
        $this->assertEquals(50.00, $boxes[7]['value']);
        $this->assertEquals(0.00, $boxes[8]['value']);
        $this->assertEquals(0.00, $boxes[9]['value']);

        $this->assertEquals(20.00, $response->json('reconciliation.output.report'));
        $this->assertEquals(20.00, $response->json('reconciliation.output.ledger'));
        $this->assertEquals(0.00, $response->json('reconciliation.output.difference'));
        $this->assertEquals(10.00, $response->json('reconciliation.input.report'));
        $this->assertEquals(10.00, $response->json('reconciliation.input.ledger'));
        $this->assertEquals(0.00, $response->json('reconciliation.input.difference'));
    }

    public function test_vat_return_nets_approved_returns_and_excludes_their_refund_mirror(): void
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'vat-accountant']);
        $admin->roles()->attach($role);
        $role->permissions()->attach(Permission::create(['name' => 'dashboard.view']));

        $sale = $this->makeSale();
        $sale->update([
            'net_price' => 110.00,
            'vat_amount' => 22.00,
            'grand_total' => 132.00,
            'paid_amount' => 132.00,
            'sale_due' => 0,
            'shipping_cost' => 12.00,
            'vat_breakdown' => [
                ['code' => 'standard', 'label' => 'Standard rate (20%)', 'rate' => 20, 'net' => 110.00, 'vat' => 22.00, 'gross' => 132.00],
            ],
        ]);

        $stock = $this->makeStock();

        SaleDetail::create([
            'fk_sale_id' => $sale->id,
            'fk_stock_id' => $stock->id,
            'stock_name' => 'VAT Product',
            'sale_stock' => 1,
            'subtotal' => 120.00,
            'vat_code' => 'standard',
            'vat_rate' => 20.00,
            'net_amount' => 100.00,
            'vat_amount' => 20.00,
            'gross_amount' => 120.00,
        ]);

        Transaction::create([
            'date' => now()->toDateString(),
            'type' => Transaction::TYPE_SALE_INCOME,
            'fk_reference_id' => $sale->id,
            'amount' => 132.00,
            'paid_amount' => 132.00,
            'due_amount' => 0,
        ]);

        app(AccountingPoster::class)->sale($sale, 132.00, 0, 0);

        $otherIncome = $this->makeIncome(120.00, 100.00, 20.00, 'Other output');
        app(AccountingPoster::class)->income($otherIncome);

        $return = SaleReturn::create([
            'fk_sale_id' => $sale->id,
            'fk_stock_id' => $stock->id,
            'quantity' => 1,
            'refund_amount' => 60.00,
            'vat_code' => 'standard',
            'vat_rate' => 20.00,
            'net_amount' => 50.00,
            'vat_amount' => 10.00,
            'reason' => 'customer_request',
            'status' => 'approved',
        ]);

        app(AccountingPoster::class)->saleReturn($return, $sale);

        $response = $this->actingAs($admin, 'admin')
            ->getJson(route('reports.vat-return', [
                'start_date' => now()->startOfQuarter()->toDateString(),
                'end_date' => now()->endOfQuarter()->toDateString(),
            ]));

        $response->assertOk();

        $boxes = $response->json('boxes');

        $this->assertEquals(32.00, $boxes[1]['value']);
        $this->assertEquals(32.00, $boxes[3]['value']);
        // The refund mirror is not an input, so it must not be reclaimed.
        $this->assertEquals(0.00, $boxes[4]['value']);
        $this->assertEquals(32.00, $boxes[5]['value']);
        $this->assertEquals(160.00, $boxes[6]['value']);
        $this->assertEquals(0.00, $boxes[7]['value']);

        $this->assertEquals(32.00, $response->json('reconciliation.output.report'));
        $this->assertEquals(32.00, $response->json('reconciliation.output.ledger'));
        $this->assertEquals(0.00, $response->json('reconciliation.output.difference'));
        $this->assertEquals(0.00, $response->json('reconciliation.input.report'));
        $this->assertEquals(0.00, $response->json('reconciliation.input.ledger'));
        $this->assertEquals(0.00, $response->json('reconciliation.input.difference'));

        $breakdown = $response->json('rate_breakdown');
        $this->assertCount(1, $breakdown);
        $this->assertEquals(160.00, $breakdown[0]['net']);
        $this->assertEquals(32.00, $breakdown[0]['vat']);
        $this->assertEquals(192.00, $breakdown[0]['gross']);
    }

    private function makeSale(): Sale
    {
        return Sale::create([
            'invoice_id' => 'INV-'.uniqid(),
            'type' => 'POS',
            'net_price' => 900.00,
            'vat_amount' => 100.00,
            'tax_amount' => 0.00,
            'shipping_cost' => 0.00,
            'discount_amount' => 0.00,
            'grand_total' => 1000.00,
            'paid_amount' => 1000.00,
            'sale_due' => 0.00,
            'buy_price' => 0.00,
            'status' => 'completed',
        ]);
    }

    private function makeStock(): Stock
    {
        return Stock::create([
            'quantity' => 10,
            'buy_price' => 0,
            'sale_price' => 120,
            'status' => 'active',
        ]);
    }

    private function makeIncome(float $gross, float $net, float $vat, string $description): Income
    {
        $transaction = Transaction::create([
            'date' => now()->toDateString(),
            'type' => Transaction::TYPE_INCOME,
            'fk_reference_id' => 0,
            'amount' => $gross,
            'paid_amount' => $gross,
            'due_amount' => 0,
        ]);

        $income = Income::create([
            'table_name' => 'incomes',
            'fk_transaction_id' => $transaction->id,
            'description' => $description,
            'amount' => $gross,
            'vat_code' => 'standard',
            'vat_rate' => 20.00,
            'net_amount' => $net,
            'vat_amount' => $vat,
        ]);

        $transaction->update(['fk_reference_id' => $income->id]);

        return $income;
    }

    private function makeExpense(float $gross, float $net, float $vat, string $description): Expense
    {
        $transaction = Transaction::create([
            'date' => now()->toDateString(),
            'type' => Transaction::TYPE_EXPENSE,
            'fk_reference_id' => 0,
            'amount' => $gross,
            'paid_amount' => $gross,
            'due_amount' => 0,
        ]);

        $expense = Expense::create([
            'table_name' => 'expenses',
            'fk_transaction_id' => $transaction->id,
            'description' => $description,
            'amount' => $gross,
            'vat_code' => 'standard',
            'vat_rate' => 20.00,
            'net_amount' => $net,
            'vat_amount' => $vat,
        ]);

        $transaction->update(['fk_reference_id' => $expense->id]);

        return $expense;
    }

    private function entry(string $sourceKey): JournalEntry
    {
        return JournalEntry::with('lines.account')->where('source_key', $sourceKey)->firstOrFail();
    }

    /**
     * @return array<string, array{debit: float, credit: float}>
     */
    private function lines(string $sourceKey): array
    {
        return $this->entry($sourceKey)->lines->mapWithKeys(function ($line) {
            return [$line->account->system_key => [
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
            ]];
        })->all();
    }
}
