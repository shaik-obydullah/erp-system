<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorefrontStockAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_lists_active_products_even_without_stock(): void
    {
        $inStock = $this->makeProduct('In Stock Item', 25.00, 5);
        $emptyStock = $this->makeProduct('Empty Stock Item', 15.00, 0);
        $inactiveStock = $this->makeProduct('Inactive Stock Item', 10.00, 9, 'inactive');
        $noStock = $this->makeProduct('No Stock Item', 5.00, null);
        $inactiveProduct = $this->makeProduct('Inactive Product Item', 12.00, 4, 'active', 'inactive');

        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee($inStock->name)
            ->assertSee($emptyStock->name)
            ->assertSee($inactiveStock->name)
            ->assertSee($noStock->name)
            ->assertDontSee($inactiveProduct->name)
            ->assertSee('Out of Stock');
    }

    public function test_product_listing_shows_active_products_regardless_of_stock(): void
    {
        $inStock = $this->makeProduct('Listed Item', 30.00, 3);
        $outOfStock = $this->makeProduct('Unlisted Item', 20.00, 0);
        $inactiveProduct = $this->makeProduct('Hidden Product', 18.00, 2, 'active', 'inactive');

        $this->get(route('store.products'))
            ->assertOk()
            ->assertSee($inStock->name)
            ->assertSee($outOfStock->name)
            ->assertDontSee($inactiveProduct->name)
            ->assertSee('Out of Stock');
    }

    public function test_out_of_stock_product_detail_is_still_viewable(): void
    {
        $outOfStock = $this->makeProduct('Sold Out Item', 40.00, 0);

        $this->get(route('store.product', $outOfStock->url_slug))
            ->assertOk()
            ->assertSee($outOfStock->name)
            ->assertSee('Out of Stock');
    }

    public function test_checkout_rejects_a_product_whose_stock_ran_out(): void
    {
        $product = $this->makeProduct('Checkout Item', 12.00, 1);

        $this->postJson(route('store.checkout.place'), [
            'customer_name' => 'Jane Buyer',
            'customer_email' => 'jane@example.com',
            'address' => '1 Test Street',
            'country' => 'GB',
            'items' => [
                ['id' => $product->id, 'qty' => 2],
            ],
        ])->assertStatus(422);
    }

    private function makeProduct(
        string $name,
        float $price,
        ?int $quantity,
        string $stockStatus = 'active',
        string $productStatus = 'active'
    ): Product {
        $product = Product::create([
            'name' => $name,
            'url_slug' => Str::slug($name).'-'.Str::random(6),
            'status' => $productStatus,
        ]);

        if ($quantity !== null) {
            Stock::create([
                'fk_product_id' => $product->id,
                'quantity' => $quantity,
                'buy_price' => 0,
                'sale_price' => $price,
                'status' => $stockStatus,
            ]);
        }

        return $product;
    }
}
