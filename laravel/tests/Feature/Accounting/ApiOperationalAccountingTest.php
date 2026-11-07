<?php

namespace Tests\Feature\Accounting;

use App\Models\Admin;
use App\Models\Cashbook;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Transaction;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiOperationalAccountingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_save_stock_posts_opening_inventory_and_stores_vat(): void
    {
        $admin = Admin::factory()->create();
        Sanctum::actingAs($admin);

        [$product] = $this->makeProduct('API Stock Product');

        $response = $this->postJson('/api/v1/save-stock', [
            'fk_product_id' => $product->id,
            'quantity' => 10,
            'buy_price' => 20,
            'sale_price' => 40,
            'vat_code' => 'standard',
            'vat_rate' => 20,
            'status' => 'Active',
        ]);

        $response->assertOk();

        $stock = Stock::latest('id')->firstOrFail();

        $this->assertSame('standard', $stock->vat_code);
        $this->assertSame(20.00, (float) $stock->vat_rate);
        $this->assertSame('active', $stock->status);
        $this->assertSame($admin->id, $stock->created_by);

        $lines = $this->entryLines("stock:{$stock->id}:v1");

        $this->assertSame(200.00, $lines['inventory']['debit']);
        $this->assertSame(200.00, $lines['opening_balance_equity']['credit']);
        $this->assertSame(0, Transaction::where('type', Transaction::TYPE_STOCK_IN)->count());
        $this->assertSame(0, Cashbook::where('table_name', 'stocks')->count());
    }

    public function test_save_sale_with_partial_payment_posts_cash_receivable_and_ledger(): void
    {
        $admin = Admin::factory()->create();
        Sanctum::actingAs($admin);

        [$product, $stock] = $this->makeProduct('API Part Payment', 120.00, 50.00, 'standard', 20.00, 10);

        $response = $this->postJson('/api/v1/save-sale', [
            'items' => [
                ['fk_stock_id' => $stock->id, 'sale_stock' => 1],
            ],
            'discount_amount' => 0,
            'paid_amount' => 60,
        ]);

        $response->assertOk();
        $response->assertJson([
            'paid_amount' => 60.00,
            'due_amount' => 60.00,
            'total' => 120.00,
        ]);

        $sale = Sale::latest('id')->firstOrFail();

        $this->assertSame('sales', $sale->table_name);
        $this->assertSame(60.00, (float) $sale->paid_amount);
        $this->assertSame(60.00, (float) $sale->sale_due);
        $this->assertSame($admin->id, $sale->created_by);

        $transaction = Transaction::where('fk_reference_id', $sale->id)
            ->where('type', Transaction::TYPE_SALE_INCOME)
            ->firstOrFail();

        $this->assertSame(60.00, (float) $transaction->paid_amount);
        $this->assertSame(60.00, (float) $transaction->due_amount);
        $this->assertSame($admin->id, $transaction->created_by);

        $payment = Cashbook::where('table_name', 'sales')
            ->where('fk_reference_id', $sale->id)
            ->where('in_amount', '>', 0)
            ->firstOrFail();

        $this->assertSame(60.00, (float) $payment->in_amount);
        $this->assertSame($admin->id, $payment->created_by);

        $receivable = Cashbook::where('table_name', 'sales')
            ->where('fk_reference_id', $sale->id)
            ->where('amount_receivable', '>', 0)
            ->firstOrFail();

        $this->assertSame(60.00, (float) $receivable->amount_receivable);
        $this->assertSame($admin->id, $receivable->created_by);

        $lines = $this->entryLines("sale:{$sale->id}:income");

        $this->assertSame(60.00, $lines['cash']['debit']);
        $this->assertSame(60.00, $lines['accounts_receivable']['debit']);
        $this->assertSame(100.00, $lines['sales_revenue']['credit']);
        $this->assertSame(20.00, $lines['sales_tax_payable']['credit']);

        $this->assertSame(9, (int) $stock->fresh()->quantity);
    }

    public function test_save_sale_rejects_insufficient_stock_without_touching_inventory(): void
    {
        $admin = Admin::factory()->create();
        Sanctum::actingAs($admin);

        [$product, $stock] = $this->makeProduct('API Short Stock', 120.00, 50.00, 'standard', 20.00, 2);

        $response = $this->postJson('/api/v1/save-sale', [
            'items' => [
                ['fk_stock_id' => $stock->id, 'sale_stock' => 3],
            ],
            'paid_amount' => 360,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Insufficient stock for API Short Stock.');

        $this->assertSame(0, Sale::count());
        $this->assertSame(2, (int) $stock->fresh()->quantity);
        $this->assertSame(0, Transaction::count());
        $this->assertSame(0, Cashbook::count());
    }

    /**
     * @return array{0: Product, 1: Stock}
     */
    private function makeProduct(
        string $name,
        float $price = 40.00,
        float $buyPrice = 0.00,
        string $vatCode = 'standard',
        float $vatRate = 20.00,
        int $quantity = 10
    ): array {
        $product = Product::create([
            'name' => $name,
            'url_slug' => Str::slug($name).'-'.Str::random(6),
            'status' => 'active',
        ]);

        $stock = Stock::create([
            'fk_product_id' => $product->id,
            'quantity' => $quantity,
            'buy_price' => $buyPrice,
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
