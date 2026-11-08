<?php

namespace Tests\Feature\Accounting;

use App\Accounting\AccountingPoster;
use App\Accounting\LedgerService;
use App\Accounting\Reconciliation;
use App\Accounting\UnbalancedEntryException;
use App\Models\Admin;
use App\Models\Cashbook;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\FiscalPeriod;
use App\Models\Income;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Need;
use App\Models\Permission;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\Transaction;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingPostingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_sale_posts_balanced_entry_and_splits_tax(): void
    {
        $sale = $this->makeSale(['vat_amount' => 100, 'tax_amount' => 0]);

        app(AccountingPoster::class)->sale($sale, 600, 400, 350);

        $entry = $this->entry("sale:{$sale->id}:income");
        $lines = $this->lines($entry);

        $this->assertSame(1000.0, $this->total($lines, 'debit'));
        $this->assertSame(1000.0, $this->total($lines, 'credit'));
        $this->assertSame(600.0, $lines['cash']['debit']);
        $this->assertSame(400.0, $lines['accounts_receivable']['debit']);
        $this->assertSame(900.0, $lines['sales_revenue']['credit']);
        $this->assertSame(100.0, $lines['sales_tax_payable']['credit']);

        $cogs = $this->entry("sale:{$sale->id}:cogs");
        $cogsLines = $this->lines($cogs);

        $this->assertSame(350.0, $cogsLines['cost_of_goods_sold']['debit']);
        $this->assertSame(350.0, $cogsLines['inventory']['credit']);
    }

    public function test_sale_posting_is_idempotent(): void
    {
        $sale = $this->makeSale();

        $poster = app(AccountingPoster::class);

        $poster->sale($sale, 500, 500, 300);
        $poster->sale($sale, 500, 500, 300);

        $this->assertSame(2, JournalEntry::where('source_id', $sale->id)->where('source_type', 'sale')->count());
    }

    public function test_journal_entry_numbers_are_short_unique_and_sequential(): void
    {
        $sale = $this->makeSale();

        app(AccountingPoster::class)->sale($sale, 600, 400, 350);

        $income = $this->entry("sale:{$sale->id}:income");
        $cogs = $this->entry("sale:{$sale->id}:cogs");

        foreach ([$income, $cogs] as $entry) {
            $this->assertLessThanOrEqual(30, strlen($entry->entry_no));
            $this->assertMatchesRegularExpression('/^JE-\d{4}-\d{6}$/', $entry->entry_no);
        }

        $this->assertNotSame($income->entry_no, $cogs->entry_no);
    }

    public function test_sale_can_post_without_tax(): void
    {
        $sale = $this->makeSale(['vat_amount' => 0, 'tax_amount' => 0]);

        app(AccountingPoster::class)->sale($sale, 800, 0, 0);

        $lines = $this->lines($this->entry("sale:{$sale->id}:income"));

        $this->assertArrayNotHasKey('sales_tax_payable', $lines);
        $this->assertSame(800.0, $lines['sales_revenue']['credit']);
    }

    public function test_income_and_expense_post_balanced_entries(): void
    {
        $poster = app(AccountingPoster::class);

        $income = $this->makeIncome(250, 'Consulting');

        $poster->income($income);

        $incomeLines = $this->lines($this->entry("income:{$income->id}:income"));

        $this->assertSame(250.0, $incomeLines['cash']['debit']);
        $this->assertSame(250.0, $incomeLines['general_income']['credit']);
        $this->assertSame($this->entry("income:{$income->id}:income")->id, $income->fresh()->journal_entry_id);

        $expense = $this->makeExpense(80, 'Utilities');

        $poster->expense($expense);

        $expenseLines = $this->lines($this->entry("expense:{$expense->id}:expense"));

        $this->assertSame(80.0, $expenseLines['general_expense']['debit']);
        $this->assertSame(80.0, $expenseLines['cash']['credit']);
        $this->assertSame($this->entry("expense:{$expense->id}:expense")->id, $expense->fresh()->journal_entry_id);
    }

    public function test_sale_due_collection_reduces_receivable(): void
    {
        $sale = $this->makeSale(['paid_amount' => 400, 'sale_due' => 600]);

        $transaction = Transaction::create([
            'date' => now()->toDateString(),
            'type' => Transaction::TYPE_SALE_DUE,
            'fk_reference_id' => $sale->id,
            'amount' => 150,
            'paid_amount' => 150,
            'due_amount' => 450,
        ]);

        app(AccountingPoster::class)->saleDue($sale, 150, $transaction->id);

        $lines = $this->lines($this->entry("sale:{$sale->id}:due:{$transaction->id}"));

        $this->assertSame(150.0, $lines['cash']['debit']);
        $this->assertSame(150.0, $lines['accounts_receivable']['credit']);
        $this->assertSame($this->entry("sale:{$sale->id}:due:{$transaction->id}")->id, $transaction->fresh()->journal_entry_id);
    }

    public function test_sale_return_reverses_revenue_and_restocks_inventory(): void
    {
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

        $refund = $this->lines($this->entry("sale_return:{$return->id}:refund"));
        $this->assertSame(180.0, $refund['sales_returns']['debit']);
        $this->assertSame(180.0, $refund['cash']['credit']);

        $restock = $this->lines($this->entry("sale_return:{$return->id}:restock"));
        $this->assertSame(50.0, $restock['inventory']['debit']);
        $this->assertSame(50.0, $restock['cost_of_goods_sold']['credit']);
    }

    public function test_customer_and_supplier_funds_post_balanced_entries(): void
    {
        $poster = app(AccountingPoster::class);

        $customer = Customer::create([
            'name' => 'Acme',
            'email' => 'acme@example.com',
            'password' => 'password',
            'balance' => 0,
            'status' => 'active',
        ]);

        $poster->customerFund($customer, 300, 11);

        $customerLines = $this->lines($this->entry('customer_fund:11'));
        $this->assertSame(300.0, $customerLines['cash']['debit']);
        $this->assertSame(300.0, $customerLines['customer_advances']['credit']);

        $supplier = Supplier::create([
            'name' => 'Supplier Co',
            'email' => 'supplier@example.com',
            'password' => 'password',
            'balance' => 0,
            'status' => 'active',
        ]);

        $poster->supplierFund($supplier, 200, 12);

        $supplierLines = $this->lines($this->entry('supplier_fund:12'));
        $this->assertSame(200.0, $supplierLines['supplier_advances']['debit']);
        $this->assertSame(200.0, $supplierLines['cash']['credit']);

        $poster->supplierPayment($supplier, 120, 13);

        $paymentLines = $this->lines($this->entry('supplier_payment:13'));
        $this->assertSame(120.0, $paymentLines['accounts_payable']['debit']);
        $this->assertSame(120.0, $paymentLines['cash']['credit']);
    }

    public function test_trial_balance_is_zero_after_a_mixed_sequence(): void
    {
        $poster = app(AccountingPoster::class);

        $sale = $this->makeSale(['vat_amount' => 30]);
        $poster->sale($sale, 300, 0, 100);

        $income = $this->makeIncome(100, 'Interest');
        $poster->income($income);

        $expense = $this->makeExpense(40, 'Rent');
        $poster->expense($expense);

        $totalDebit = (float) JournalLine::sum('debit');
        $totalCredit = (float) JournalLine::sum('credit');

        $this->assertSame(round($totalDebit, 2), round($totalCredit, 2));

        $this->artisan('accounting:verify')->assertSuccessful();
    }

    public function test_backfill_is_idempotent(): void
    {
        $sale = $this->makeSale(['paid_amount' => 600, 'sale_due' => 400]);

        Transaction::create([
            'date' => now()->toDateString(),
            'type' => Transaction::TYPE_SALE_INCOME,
            'fk_reference_id' => $sale->id,
            'amount' => 1000,
            'paid_amount' => 600,
            'due_amount' => 400,
        ]);

        $this->artisan('accounting:backfill', ['--only' => ['sales']])->assertSuccessful();
        $firstCount = JournalEntry::count();

        $this->artisan('accounting:backfill', ['--only' => ['sales']])->assertSuccessful();

        $this->assertSame($firstCount, JournalEntry::count());
        $this->assertSame(2, $firstCount);
    }

    public function test_posting_is_blocked_for_a_closed_fiscal_period(): void
    {
        $this->expectException(UnbalancedEntryException::class);

        FiscalPeriod::create([
            'name' => now()->format('F Y'),
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'closed',
        ]);

        app(LedgerService::class)->post(
            'manual',
            'manual',
            null,
            'manual:closed-period',
            now(),
            'Should be blocked',
            [
                ['account' => 'cash', 'debit' => 100],
                ['account' => 'general_income', 'credit' => 100],
            ]
        );
    }

    public function test_reversal_is_idempotent(): void
    {
        $sale = $this->makeSale();
        app(AccountingPoster::class)->sale($sale, 600, 400, 350);

        $entry = $this->entry("sale:{$sale->id}:income");
        $ledger = app(LedgerService::class);

        $ledger->reverse($entry, 'First reversal');
        $ledger->reverse($entry, 'Second reversal');

        $this->assertSame(1, JournalEntry::where('source_key', "sale:{$sale->id}:income:reversal")->count());
        $this->assertSame(3, JournalEntry::count());
    }

    public function test_income_posting_links_back_to_cashbook_and_transaction(): void
    {
        $income = $this->makeIncome(125, 'Consulting');

        $cashbook = Cashbook::create([
            'table_name' => 'incomes',
            'fk_reference_id' => $income->fk_transaction_id,
            'description' => 'Consulting',
            'in_amount' => 125,
            'out_amount' => 0,
        ]);

        app(AccountingPoster::class)->income($income);

        $entry = $this->entry("income:{$income->id}:income");

        $this->assertSame($entry->id, $income->fresh()->journal_entry_id);
        $this->assertSame($entry->id, $cashbook->fresh()->journal_entry_id);
        $this->assertSame($entry->id, $income->transaction->fresh()->journal_entry_id);
    }

    public function test_ledger_rejects_unbalanced_entries(): void
    {
        $this->expectException(UnbalancedEntryException::class);

        app(LedgerService::class)->post(
            'manual',
            'manual',
            null,
            'manual:unbalanced',
            now(),
            'Broken entry',
            [
                ['account' => 'cash', 'debit' => 100],
                ['account' => 'general_income', 'credit' => 90],
            ]
        );
    }

    public function test_ledger_rejects_a_line_with_both_debit_and_credit(): void
    {
        $this->expectException(UnbalancedEntryException::class);

        app(LedgerService::class)->post(
            'manual',
            'manual',
            null,
            'manual:both',
            now(),
            'Broken line',
            [
                ['account' => 'cash', 'debit' => 100, 'credit' => 100],
                ['account' => 'general_income', 'credit' => 100],
            ]
        );
    }

    public function test_accounting_pages_render_for_an_authorised_admin(): void
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'accountant']);
        $admin->roles()->attach($role);

        foreach (['accounting.view', 'accounting.advanced', 'accounting.manage'] as $permission) {
            $role->permissions()->attach(Permission::create(['name' => $permission]));
        }

        $routes = [
            'accounting.index',
            'accounting.journal',
            'accounting.ledger',
            'accounting.trial-balance',
            'accounting.profit-loss',
            'accounting.balance-sheet',
            'accounting.cash-flow',
            'accounting.chart-of-accounts',
            'accounting.reconciliation',
        ];

        foreach ($routes as $route) {
            $this->actingAs($admin, 'admin')->get(route($route))->assertOk();
        }
    }

    public function test_reconciliation_matches_document_dues_and_flags_unassigned_receivables(): void
    {
        $customer = Customer::create([
            'name' => 'Ledger Customer',
            'email' => 'ledger-customer@example.com',
            'password' => 'password',
            // Advances live in the balance column and must not be treated as dues.
            'balance' => 500,
            'status' => 'active',
        ]);

        $sale = $this->makeSale([
            'fk_user_id' => $customer->id,
            'paid_amount' => 750,
            'sale_due' => 250,
        ]);

        app(AccountingPoster::class)->sale($sale, 750, 250, 0);

        $summary = app(Reconciliation::class)->summary();

        $this->assertSame(250.0, $summary['customer_due']);
        $this->assertSame(250.0, $summary['customer_receivable']);
        $this->assertTrue($summary['customer_matched']);
        $this->assertSame(0.0, $summary['unassigned_receivable']);

        $anonymous = $this->makeSale([
            'paid_amount' => 0,
            'sale_due' => 500,
            'grand_total' => 500,
            'vat_amount' => 0,
        ]);

        app(AccountingPoster::class)->sale($anonymous, 0, 500, 0);

        $summary = app(Reconciliation::class)->summary();

        $this->assertSame(250.0, $summary['customer_receivable']);
        $this->assertTrue($summary['customer_matched']);
        $this->assertSame(500.0, $summary['unassigned_receivable']);

        $supplier = Supplier::create([
            'name' => 'Ledger Supplier',
            'email' => 'ledger-supplier@example.com',
            'password' => 'password',
            // An advance must not be mistaken for a payable.
            'balance' => 400,
            'status' => 'active',
        ]);

        $need = Need::create([
            'description' => 'Test purchase need',
            'date_identified' => now()->toDateString(),
        ]);

        $order = PurchaseOrder::create([
            'fk_need_id' => $need->id,
            'fk_supplier_id' => $supplier->id,
            'order_number' => 'PO-'.uniqid(),
            'total_amount' => 300.00,
            'net_amount' => 300.00,
            'vat_amount' => 0.00,
            'due_amount' => 300.00,
        ]);

        app(AccountingPoster::class)->purchaseOrder($order);

        $summary = app(Reconciliation::class)->summary();

        $this->assertSame(300.0, $summary['supplier_due']);
        $this->assertSame(300.0, $summary['supplier_payable']);
        $this->assertTrue($summary['supplier_matched']);
        $this->assertSame(0.0, $summary['unassigned_payable']);
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

    private function makeIncome(float $amount, string $description): Income
    {
        $transaction = Transaction::create([
            'date' => now()->toDateString(),
            'type' => Transaction::TYPE_INCOME,
            'fk_reference_id' => 0,
            'amount' => $amount,
            'paid_amount' => $amount,
            'due_amount' => 0,
        ]);

        $income = Income::create([
            'table_name' => 'incomes',
            'fk_transaction_id' => $transaction->id,
            'description' => $description,
            'amount' => $amount,
        ]);

        $transaction->update(['fk_reference_id' => $income->id]);

        return $income;
    }

    private function makeExpense(float $amount, string $description): Expense
    {
        $transaction = Transaction::create([
            'date' => now()->toDateString(),
            'type' => Transaction::TYPE_EXPENSE,
            'fk_reference_id' => 0,
            'amount' => $amount,
            'paid_amount' => $amount,
            'due_amount' => 0,
        ]);

        $expense = Expense::create([
            'table_name' => 'expenses',
            'fk_transaction_id' => $transaction->id,
            'description' => $description,
            'amount' => $amount,
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
    private function lines(JournalEntry $entry): array
    {
        return $entry->lines->mapWithKeys(function ($line) {
            return [$line->account->system_key => [
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
            ]];
        })->all();
    }

    private function total(array $lines, string $column): float
    {
        return round(array_sum(array_column($lines, $column)), 2);
    }
}
