<?php

namespace Tests\Feature\Accounting;

use App\Accounting\AccountingPoster;
use App\Models\Admin;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Services\LedgerProjector;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayableLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_payable_page_uses_the_accounting_ledger(): void
    {
        $admin = $this->adminWith('cashbook.view');

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

        $response = $this->actingAs($admin, 'admin')->get(route('payables.index'));

        $response->assertOk()
            ->assertSee('Payable Supplier')
            ->assertSee('Advance Supplier')
            ->assertSee('Supplier account');

        $this->assertSame(300.0, $response->viewData('payableLedger'));
        $this->assertSame(500.0, $response->viewData('advanceLedger'));
        $this->assertSame(300.0, $response->viewData('openPayables'));
        $this->assertSame(500.0, $response->viewData('openAdvances'));
        $this->assertSame(300.0, $response->viewData('supplierPayables'));
        $this->assertSame(0.0, $response->viewData('unassignedPayables'));
        $this->assertSame(0.0, $response->viewData('difference'));
        $this->assertTrue($response->viewData('reconciled'));
    }

    public function test_supplier_opening_backfill_posts_only_the_unposted_part(): void
    {
        $supplier = $this->makeSupplier([
            'name' => 'Legacy Supplier',
            'email' => 'legacy-supplier@example.com',
            'balance' => 5000,
        ]);

        // A fund deposit was already posted before opening balances were wired
        // into accounting, so the backfill must only add the remaining 4000.
        $transaction = Transaction::create([
            'date' => now()->toDateString(),
            'type' => Transaction::TYPE_SUPPLIER_DEPOSIT,
            'fk_reference_id' => $supplier->id,
            'amount' => 1000,
            'paid_amount' => 1000,
            'due_amount' => 0,
        ]);
        app(AccountingPoster::class)->supplierFund($supplier, 1000, $transaction->id);

        $projector = app(LedgerProjector::class);
        $projector->supplierOpenings();
        $projector->supplierOpenings();

        $entry = $this->entry("supplier_opening:{$supplier->id}:v1");

        $this->assertNotNull($entry);
        $this->assertSame(4000.0, $this->lineBalance($entry->id, 'supplier_advances'));
        $this->assertSame(-4000.0, $this->lineBalance($entry->id, 'opening_balance_equity'));
        $this->assertSame(5000.0, $this->accountBalance('supplier_advances'));
        $this->assertSame(1, JournalEntry::where('source_key', "supplier_opening:{$supplier->id}:v1")->count());
    }

    public function test_updating_a_supplier_keeps_fund_deposits_posted(): void
    {
        $admin = $this->adminWith('suppliers.edit');

        $supplier = $this->makeSupplier([
            'name' => 'Funded Supplier',
            'email' => 'funded-supplier@example.com',
            'balance' => 1000,
        ]);

        $transaction = Transaction::create([
            'date' => now()->toDateString(),
            'type' => Transaction::TYPE_SUPPLIER_DEPOSIT,
            'fk_reference_id' => $supplier->id,
            'amount' => 1000,
            'paid_amount' => 1000,
            'due_amount' => 0,
        ]);
        app(AccountingPoster::class)->supplierFund($supplier, 1000, $transaction->id);

        $this->actingAs($admin, 'admin')->put(route('suppliers.update', $supplier), [
            'name' => 'Funded Supplier',
            'email' => 'funded-supplier@example.com',
            'balance' => 1500,
            'status' => 'active',
        ])->assertRedirect(route('suppliers.index'));

        $fund = JournalEntry::where('source_key', "supplier_fund:{$transaction->id}")->first();

        $this->assertNotNull($fund);
        $this->assertSame(0, JournalEntry::where('source_key', "supplier_fund:{$transaction->id}:reversal")->count());
        $this->assertSame(500.0, $this->lineBalance($this->entry("supplier_opening:{$supplier->id}:v2")->id, 'supplier_advances'));
        $this->assertSame(1500.0, $this->accountBalance('supplier_advances'));
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

    private function entry(string $sourceKey): ?JournalEntry
    {
        return JournalEntry::where('source_key', $sourceKey)->first();
    }

    private function lineBalance(int $entryId, string $systemKey): float
    {
        return round((float) JournalLine::query()
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_lines.journal_entry_id', $entryId)
            ->where('accounts.system_key', $systemKey)
            ->selectRaw('COALESCE(SUM(journal_lines.debit - journal_lines.credit), 0) as balance')
            ->value('balance'), 2);
    }

    private function accountBalance(string $systemKey): float
    {
        return round((float) JournalLine::query()
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('accounts.system_key', $systemKey)
            ->where('journal_entries.status', 'posted')
            ->selectRaw('COALESCE(SUM(journal_lines.debit - journal_lines.credit), 0) as balance')
            ->value('balance'), 2);
    }

    private function adminWith(string $permission): Admin
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'payable-'.uniqid()]);
        $role->permissions()->attach(Permission::create(['name' => $permission]));
        $admin->roles()->attach($role);

        return $admin;
    }
}
