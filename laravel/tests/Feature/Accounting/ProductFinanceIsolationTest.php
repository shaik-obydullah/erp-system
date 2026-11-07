<?php

namespace Tests\Feature\Accounting;

use App\Models\Admin;
use App\Models\Cashbook;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductFinanceIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_module_has_no_finance_columns_or_side_effects(): void
    {
        $financeColumns = [
            'vat_code', 'vat_rate',
            'tax_code', 'tax_rate',
            'sale_price', 'buy_price', 'cost_price',
            'journal_entry_id', 'accounting_version',
        ];

        foreach ($financeColumns as $column) {
            $this->assertFalse(
                Schema::hasColumn('products', $column),
                "Products table must not contain finance column [{$column}]."
            );
        }

        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')->post(route('products.store'), [
            'name' => 'Isolated Product',
            'status' => 'active',
            // Finance-shaped input must be ignored by the product module.
            'vat_code' => 'standard',
            'vat_rate' => 20,
            'sale_price' => 99.99,
            'buy_price' => 50,
        ])->assertRedirect(route('products.index'));

        $product = Product::firstOrFail();

        $this->assertSame(0, $product->stocks()->count());
        $this->assertSame(0, JournalEntry::count());
        $this->assertSame(0, Transaction::count());
        $this->assertSame(0, Cashbook::count());

        $this->actingAs($admin, 'admin')->put(route('products.update', $product), [
            'name' => 'Isolated Product Updated',
            'status' => 'active',
            'vat_code' => 'zero',
            'vat_rate' => 0,
            'sale_price' => 1,
            'buy_price' => 1,
        ])->assertRedirect(route('products.index'));

        $product->refresh();

        $this->assertSame('Isolated Product Updated', $product->name);
        $this->assertSame(0, $product->stocks()->count());
        $this->assertSame(0, JournalEntry::count());
        $this->assertSame(0, Transaction::count());
        $this->assertSame(0, Cashbook::count());
    }
}
