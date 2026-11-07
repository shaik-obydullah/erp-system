<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Cashbook;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashbookTest extends TestCase
{
    use RefreshDatabase;

    private function adminWithCashbookAccess(bool $accountingAdvanced = false): Admin
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'administrator']);

        $role->permissions()->attach(Permission::create(['name' => 'cashbook.view']));

        if ($accountingAdvanced) {
            $role->permissions()->attach(Permission::create(['name' => 'accounting.advanced']));
        }

        $admin->roles()->attach($role);

        return $admin;
    }

    public function test_cashbook_page_shows_cash_totals_separately_from_money_owed(): void
    {
        $admin = $this->adminWithCashbookAccess();

        Cashbook::create([
            'table_name' => 'sales',
            'fk_reference_id' => 1,
            'description' => 'Payment for INV-001',
            'in_amount' => 100,
            'out_amount' => 0,
            'amount_payable' => 0,
            'amount_receivable' => 25,
        ]);

        Cashbook::create([
            'table_name' => 'expenses',
            'fk_reference_id' => 1,
            'description' => 'Office rent',
            'in_amount' => 0,
            'out_amount' => 40,
            'amount_payable' => 10,
            'amount_receivable' => 0,
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('cashbook.index'));

        $response->assertOk();
        $response->assertSee('Cashbook');
        $response->assertSee('Cash in');
        $response->assertSee('Cash out');
        $response->assertSee('Net cash movement');
        $response->assertSee('Money owed — not cash');
        $response->assertSee('Owed to you');
        $response->assertSee('You owe');
        $response->assertSee('$100.00');
        $response->assertSee('$40.00');
        $response->assertSee('$60.00');
        $response->assertDontSee('View Balance Sheet');
    }

    public function test_cashbook_page_can_search_by_description(): void
    {
        $admin = $this->adminWithCashbookAccess();

        Cashbook::create([
            'table_name' => 'sales',
            'fk_reference_id' => 1,
            'description' => 'Payment for INV-001',
            'in_amount' => 100,
            'out_amount' => 0,
        ]);

        Cashbook::create([
            'table_name' => 'expenses',
            'fk_reference_id' => 2,
            'description' => 'Office rent',
            'in_amount' => 0,
            'out_amount' => 40,
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('cashbook.index', ['search' => 'rent']));

        $response->assertOk();
        $response->assertSee('Office rent');
        $response->assertDontSee('Payment for INV-001');
    }

    public function test_cashbook_page_links_to_balance_sheet_only_when_user_can_view_it(): void
    {
        $admin = $this->adminWithCashbookAccess(accountingAdvanced: true);

        $response = $this->actingAs($admin, 'admin')->get(route('cashbook.index'));

        $response->assertOk();
        $response->assertSee('View Balance Sheet');
    }
}
