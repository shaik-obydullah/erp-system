<?php

namespace Tests\Feature\Accounting;

use App\Models\Admin;
use App\Models\JournalEntry;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Stock;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosVatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_pos_checkout_calculates_and_persists_vat_per_line(): void
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'cashier']);
        $permission = Permission::create(['name' => 'pos.save']);
        $role->permissions()->attach($permission);
        $admin->roles()->attach($role);

        $standardStock = $this->makeStock('Standard Product', 120.00, 'standard', 20.00);
        $zeroStock = $this->makeStock('Zero Rated Product', 50.00, 'zero', 0.00);

        $response = $this->actingAs($admin, 'admin')->postJson(route('pos.checkout'), [
            'cart' => [
                ['product_id' => $standardStock->id, 'quantity' => 2],
                ['product_id' => $zeroStock->id, 'quantity' => 1],
            ],
            'discount' => 12.00,
            'shipping' => 6.00,
            'paid_amount' => 200.00,
        ]);

        $response->assertOk();

        $sale = Sale::latest('id')->firstOrFail();

        // Gross: 240 + 50 = 290, less 12 discount, plus 6 shipping = 284.
        $this->assertSame(284.00, (float) $sale->grand_total);
        $this->assertSame(244.66, (float) $sale->net_price);
        $this->assertSame(39.34, (float) $sale->vat_amount);
        $this->assertSame(0.00, (float) $sale->tax_amount);

        $standardLine = SaleDetail::where('fk_sale_id', $sale->id)
            ->where('fk_stock_id', $standardStock->id)
            ->firstOrFail();
        $zeroLine = SaleDetail::where('fk_sale_id', $sale->id)
            ->where('fk_stock_id', $zeroStock->id)
            ->firstOrFail();

        $this->assertSame('standard', $standardLine->vat_code);
        $this->assertSame(20.00, (float) $standardLine->vat_rate);
        $this->assertSame(230.07, (float) $standardLine->gross_amount);
        $this->assertSame(191.73, (float) $standardLine->net_amount);
        $this->assertSame(38.34, (float) $standardLine->vat_amount);

        $this->assertSame('zero', $zeroLine->vat_code);
        $this->assertSame(0.00, (float) $zeroLine->vat_rate);
        $this->assertSame(47.93, (float) $zeroLine->gross_amount);
        $this->assertSame(47.93, (float) $zeroLine->net_amount);
        $this->assertSame(0.00, (float) $zeroLine->vat_amount);

        $this->assertSame(8, $standardStock->fresh()->quantity);
        $this->assertSame(9, $zeroStock->fresh()->quantity);

        $entry = JournalEntry::with('lines.account')
            ->where('source_key', "sale:{$sale->id}:income")
            ->firstOrFail();

        $lines = $entry->lines->mapWithKeys(fn ($line) => [
            $line->account->system_key => [
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
            ],
        ])->all();

        $this->assertSame(200.00, $lines['cash']['debit']);
        $this->assertSame(84.00, $lines['accounts_receivable']['debit']);
        $this->assertSame(244.66, $lines['sales_revenue']['credit']);
        $this->assertSame(39.34, $lines['sales_tax_payable']['credit']);
    }

    private function makeStock(string $name, float $price, string $vatCode, float $vatRate): Stock
    {
        $product = Product::create([
            'name' => $name,
            'url_slug' => Str::slug($name).'-'.Str::random(6),
            'status' => 'active',
            'vat_code' => $vatCode,
            'vat_rate' => $vatRate,
        ]);

        return Stock::create([
            'fk_product_id' => $product->id,
            'quantity' => 10,
            'buy_price' => 0,
            'sale_price' => $price,
            'status' => 'active',
        ]);
    }
}
