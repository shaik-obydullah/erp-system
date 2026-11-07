<?php

namespace Tests\Feature\Accounting;

use App\Accounting\AccountingPoster;
use App\Accounting\LedgerService;
use App\Accounting\UnbalancedEntryException;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Income;
use App\Models\JournalEntry;
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

        $totalDebit = (float) \App\Models\JournalLine::sum('debit');
        $totalCredit = (float) \App\Models\JournalLine::sum('credit');

        $this->assertSame(round($totalDebit, 2), round($totalCredit, 2));

        $this->artisan('accounting:verify')->assertSuccessful();
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
