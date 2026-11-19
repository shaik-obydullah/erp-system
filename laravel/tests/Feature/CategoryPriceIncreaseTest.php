<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryPriceIncreaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_increase_form_is_available_to_admins_with_stock_edit_permission(): void
    {
        $admin = $this->adminWith('stocks.edit');
        Category::create(['name' => 'Electronics', 'status' => 'active']);

        $this->actingAs($admin, 'admin')
            ->get(route('stocks.price-increase.create'))
            ->assertOk()
            ->assertSee('Electronics');
    }

    public function test_increase_updates_every_stock_sale_price_in_the_category(): void
    {
        $admin = $this->adminWith('stocks.edit');
        $category = $this->category('Electronics');
        $otherCategory = $this->category('Furniture');

        $first = $this->stock($category, 100, 'active');
        $second = $this->stock($category, 50.50, 'inactive');
        $unrelated = $this->stock($otherCategory, 200, 'active');

        $this->actingAs($admin, 'admin')->post(route('stocks.price-increase.store'), [
            'fk_category_id' => $category->id,
            'percentage' => 10,
        ])->assertRedirect(route('stocks.index'));

        $this->assertSame(110.0, (float) $first->fresh()->sale_price);
        $this->assertSame(55.55, (float) $second->fresh()->sale_price);
        $this->assertSame(200.0, (float) $unrelated->fresh()->sale_price);
        $this->assertSame($admin->id, (int) $first->fresh()->updated_by);
    }

    public function test_increase_also_updates_products_in_child_categories(): void
    {
        $admin = $this->adminWith('stocks.edit');
        $parent = $this->category('Electronics');
        $child = Category::create(['name' => 'Mobile', 'fk_category_id' => $parent->id, 'status' => 'active']);
        $grandchild = Category::create(['name' => 'Accessories', 'fk_category_id' => $child->id, 'status' => 'active']);

        $parentStock = $this->stock($parent, 100, 'active');
        $childStock = $this->stock($child, 200, 'active');
        $grandchildStock = $this->stock($grandchild, 50, 'active');
        $unrelated = $this->stock($this->category('Furniture'), 300, 'active');

        $this->actingAs($admin, 'admin')->postJson(route('stocks.price-increase.store'), [
            'fk_category_id' => $parent->id,
            'percentage' => 10,
        ])->assertOk()->assertJsonFragment([
            'message' => 'Sale prices in Electronics increased by 10% across 3 stock record(s).',
        ]);

        $this->assertSame(110.0, (float) $parentStock->fresh()->sale_price);
        $this->assertSame(220.0, (float) $childStock->fresh()->sale_price);
        $this->assertSame(55.0, (float) $grandchildStock->fresh()->sale_price);
        $this->assertSame(300.0, (float) $unrelated->fresh()->sale_price);
    }

    public function test_negative_percentage_decreases_prices_without_going_below_zero(): void
    {
        $admin = $this->adminWith('stocks.edit');
        $category = $this->category('Electronics');

        $stock = $this->stock($category, 80, 'active');
        $cheap = $this->stock($category, 50, 'active');

        $this->actingAs($admin, 'admin')->postJson(route('stocks.price-increase.store'), [
            'fk_category_id' => $category->id,
            'percentage' => -75,
        ])->assertOk()->assertJsonFragment(['message' => 'Sale prices in Electronics decreased by 75% across 2 stock record(s).']);

        $this->assertSame(20.0, (float) $stock->fresh()->sale_price);
        $this->assertSame(12.5, (float) $cheap->fresh()->sale_price);

        $this->actingAs($admin, 'admin')->postJson(route('stocks.price-increase.store'), [
            'fk_category_id' => $category->id,
            'percentage' => -100,
        ])->assertOk();

        $this->assertSame(0.0, (float) $stock->fresh()->sale_price);
    }

    public function test_category_without_stock_returns_an_error(): void
    {
        $admin = $this->adminWith('stocks.edit');
        $category = $this->category('Empty Category');

        $this->actingAs($admin, 'admin')->postJson(route('stocks.price-increase.store'), [
            'fk_category_id' => $category->id,
            'percentage' => 10,
        ])->assertStatus(422)->assertJsonFragment([
            'message' => 'No stock records found for category Empty Category.',
        ]);

        $this->assertSame(0, Stock::count());
    }

    public function test_percentage_is_limited_to_the_supported_range(): void
    {
        $admin = $this->adminWith('stocks.edit');
        $category = $this->category('Electronics');
        $stock = $this->stock($category, 100, 'active');

        $this->actingAs($admin, 'admin')->postJson(route('stocks.price-increase.store'), [
            'fk_category_id' => $category->id,
            'percentage' => 101,
        ])->assertStatus(422)->assertJsonValidationErrors('percentage');

        $this->assertSame(100.0, (float) $stock->fresh()->sale_price);
    }

    public function test_admin_without_stock_edit_permission_cannot_apply_changes(): void
    {
        $admin = $this->adminWith('stocks.view');
        $category = $this->category('Electronics');
        $stock = $this->stock($category, 100, 'active');

        $this->actingAs($admin, 'admin')->post(route('stocks.price-increase.store'), [
            'fk_category_id' => $category->id,
            'percentage' => 10,
        ])->assertForbidden();

        $this->assertSame(100.0, (float) $stock->fresh()->sale_price);
    }

    private function category(string $name): Category
    {
        return Category::create(['name' => $name, 'status' => 'active']);
    }

    private function stock(Category $category, float $salePrice, string $status): Stock
    {
        $product = Product::create([
            'name' => 'Price Test Product '.uniqid(),
            'url_slug' => 'price-test-'.uniqid(),
            'fk_category_id' => $category->id,
            'status' => 'active',
        ]);

        return Stock::create([
            'fk_product_id' => $product->id,
            'quantity' => 10,
            'buy_price' => 40,
            'sale_price' => $salePrice,
            'status' => $status,
        ]);
    }

    private function adminWith(string $permission): Admin
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'price-test-'.uniqid()]);
        $role->permissions()->attach(Permission::create(['name' => $permission]));
        $admin->roles()->attach($role);

        return $admin;
    }
}
