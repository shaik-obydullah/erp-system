<?php

namespace Tests\Feature\Accounting;

use App\Models\Admin;
use App\Models\Income;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Stock;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiVatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_save_sale_recalculates_inclusive_vat_and_posts_it(): void
    {
        $admin = Admin::factory()->create();
        Sanctum::actingAs($admin);

        [$standardProduct, $standardStock] = $this->makeProduct('API Standard', 120.00, 'standard', 20.00);
        [$zeroProduct, $zeroStock] = $this->makeProduct('API Zero', 50.00, 'zero', 0.00);

        $response = $this->postJson('/api/v1/save-sale', [
            'items' => [
                ['fk_stock_id' => $standardStock->id, 'sale_stock' => 2, 'subtotal' => 999.00],
                ['fk_stock_id' => $zeroStock->id, 'sale_stock' => 1, 'subtotal' => 999.00],
            ],
            // Client-supplied totals must be ignored: the server rebuilds them.
            'vat_amount' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total' => 0,
        ]);

        $response->assertOk();
        $response->assertJson([
            'net_amount' => 250.00,
            'vat_amount' => 40.00,
            'total' => 290.00,
        ]);

        $sale = Sale::latest('id')->firstOrFail();

        $this->assertSame(250.00, (float) $sale->net_price);
        $this->assertSame(40.00, (float) $sale->vat_amount);
        $this->assertSame(290.00, (float) $sale->grand_total);

        $standardLine = SaleDetail::where('fk_sale_id', $sale->id)
            ->where('fk_stock_id', $standardStock->id)
            ->firstOrFail();
        $zeroLine = SaleDetail::where('fk_sale_id', $sale->id)
            ->where('fk_stock_id', $zeroStock->id)
            ->firstOrFail();

        $this->assertSame(240.00, (float) $standardLine->gross_amount);
        $this->assertSame(200.00, (float) $standardLine->net_amount);
        $this->assertSame(40.00, (float) $standardLine->vat_amount);
        $this->assertSame(0.00, (float) $zeroLine->vat_amount);

        $income = Income::where('fk_transaction_id', $sale->transaction->id)->firstOrFail();
        $this->assertSame(250.00, (float) $income->net_amount);
        $this->assertSame(40.00, (float) $income->vat_amount);

        $lines = $this->entryLines("sale:{$sale->id}:income");

        $this->assertSame(290.00, $lines['cash']['debit']);
        $this->assertSame(250.00, $lines['sales_revenue']['credit']);
        $this->assertSame(40.00, $lines['sales_tax_payable']['credit']);
    }

    /**
     * @return array{0: Product, 1: Stock}
     */
    private function makeProduct(string $name, float $price, string $vatCode, float $vatRate): array
    {
        $product = Product::create([
            'name' => $name,
            'url_slug' => Str::slug($name).'-'.Str::random(6),
            'status' => 'active',
        ]);

        $stock = Stock::create([
            'fk_product_id' => $product->id,
            'quantity' => 10,
            'buy_price' => 0,
            'sale_price' => $price,
            'status' => 'active',
            'vat_code' => $vatCode,
            'vat_rate' => $vatRate,
        ]);

        return [$product, $stock];
    }

    /**
     * @return array<string, array{debit: float, credit: float}>
     */
    private function entryLines(string $sourceKey): array
    {
        $entry = JournalEntry::with('lines.account')
            ->where('source_key', $sourceKey)
            ->firstOrFail();

        return $entry->lines->mapWithKeys(fn ($line) => [
            $line->account->system_key => [
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
            ],
        ])->all();
    }
}
