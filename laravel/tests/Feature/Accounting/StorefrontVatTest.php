<?php

namespace Tests\Feature\Accounting;

use App\Models\Income;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Stock;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorefrontVatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_ecommerce_checkout_extracts_vat_from_inclusive_prices_and_posts_it(): void
    {
        [$standardProduct, $standardStock] = $this->makeProduct('Storefront Standard', 120.00, 'standard', 20.00);
        [$zeroProduct, $zeroStock] = $this->makeProduct('Storefront Zero', 50.00, 'zero', 0.00);

        $response = $this->postJson(route('store.checkout.place'), [
            'customer_name' => 'Jane Buyer',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '07000000000',
            'address' => '1 Test Street',
            'city' => 'London',
            'postal_code' => 'SW1A 1AA',
            'country' => 'GB',
            'payment_method' => 'cod',
            'items' => [
                ['id' => $standardProduct->id, 'qty' => 2],
                ['id' => $zeroProduct->id, 'qty' => 1],
            ],
        ]);

        $response->assertOk();

        // 2 x 120 gross = 200 net + 40 VAT; zero-rated 50 is all net.
        $response->assertJson([
            'net_amount' => 250.00,
            'vat_amount' => 40.00,
            'total' => 290.00,
        ]);

        $sale = Sale::latest('id')->firstOrFail();

        $this->assertSame(250.00, (float) $sale->net_price);
        $this->assertSame(40.00, (float) $sale->vat_amount);
        $this->assertSame(290.00, (float) $sale->grand_total);
        $this->assertSame('standard', $sale->vat_breakdown[0]['code']);

        $standardLine = SaleDetail::where('fk_sale_id', $sale->id)
            ->where('fk_stock_id', $standardStock->id)
            ->firstOrFail();
        $zeroLine = SaleDetail::where('fk_sale_id', $sale->id)
            ->where('fk_stock_id', $zeroStock->id)
            ->firstOrFail();

        $this->assertSame(240.00, (float) $standardLine->gross_amount);
        $this->assertSame(200.00, (float) $standardLine->net_amount);
        $this->assertSame(40.00, (float) $standardLine->vat_amount);
        $this->assertSame(50.00, (float) $zeroLine->gross_amount);
        $this->assertSame(0.00, (float) $zeroLine->vat_amount);

        $income = Income::where('fk_transaction_id', $sale->transaction->id)->firstOrFail();
        $this->assertSame(250.00, (float) $income->net_amount);
        $this->assertSame(40.00, (float) $income->vat_amount);

        $entry = JournalEntry::with('lines.account')
            ->where('source_key', "sale:{$sale->id}:income")
            ->firstOrFail();

        $lines = $entry->lines->mapWithKeys(fn ($line) => [
            $line->account->system_key => [
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
            ],
        ])->all();

        $this->assertSame(290.00, $lines['accounts_receivable']['debit']);
        $this->assertSame(250.00, $lines['sales_revenue']['credit']);
        $this->assertSame(40.00, $lines['sales_tax_payable']['credit']);
    }

    public function test_cart_and_checkout_pages_expose_the_default_vat_rate(): void
    {
        $this->get(route('store.cart'))
            ->assertOk()
            ->assertSee('defaultVatRate: 20', false);

        $this->get(route('store.checkout'))
            ->assertOk()
            ->assertSee('defaultVatRate: 20', false);
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
}
