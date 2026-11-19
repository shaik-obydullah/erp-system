<?php

namespace Tests\Feature\Accounting;

use App\Models\Admin;
use App\Models\Cashbook;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Transaction;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerFundTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_adding_customer_fund_posts_cash_and_customer_advance_to_accounting(): void
    {
        $admin = $this->adminWith('customers.save');

        $customer = Customer::create([
            'name' => 'Fund Customer',
            'email' => 'fund-customer@example.com',
            'password' => 'password',
            'balance' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin')->post(route('customers.fund.store'), [
            'customer_id' => $customer->id,
            'amount' => 250.00,
            'description' => 'Advance payment',
        ])->assertRedirect(route('customers.fund.index'));

        $this->assertSame(250.0, (float) $customer->fresh()->balance);

        $transaction = Transaction::where('type', Transaction::TYPE_USER_FUND)
            ->where('fk_reference_id', $customer->id)
            ->firstOrFail();

        $cashbook = Cashbook::where('table_name', 'customers')
            ->where('fk_reference_id', $customer->id)
            ->firstOrFail();

        $this->assertSame(250.0, (float) $cashbook->in_amount);
        $this->assertSame(250.0, (float) $cashbook->amount_payable);

        $entry = JournalEntry::with('lines.account')
            ->where('source_key', "customer_fund:{$transaction->id}")
            ->firstOrFail();

        $lines = $entry->lines->mapWithKeys(fn ($line) => [
            $line->account->system_key => [
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
            ],
        ])->all();

        $this->assertSame(250.0, $lines['cash']['debit']);
        $this->assertSame(250.0, $lines['customer_advances']['credit']);

        $this->assertSame($entry->id, $transaction->fresh()->journal_entry_id);
        $this->assertSame($entry->id, $cashbook->fresh()->journal_entry_id);
    }

    private function adminWith(string $permission): Admin
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'customer-'.uniqid()]);
        $role->permissions()->attach(Permission::create(['name' => $permission]));
        $admin->roles()->attach($role);

        return $admin;
    }
}
