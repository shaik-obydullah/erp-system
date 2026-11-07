<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_used_in_stock_cannot_be_deleted(): void
    {
        $admin = $this->adminWith('products.delete');
        $product = $this->product();

        Stock::create([
            'fk_product_id' => $product->id,
            'quantity' => 10,
            'buy_price' => 5,
            'sale_price' => 10,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->deleteJson(route('products.destroy', $product));

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'This product cannot be deleted because it is used in 1 stock record. Mark the product as inactive instead.',
            ]);

        $this->assertNotSoftDeleted($product);
        $this->assertDatabaseHas('stocks', ['fk_product_id' => $product->id]);
    }

    public function test_product_without_stock_can_be_deleted(): void
    {
        $admin = $this->adminWith('products.delete');
        $product = $this->product();

        $this->actingAs($admin, 'admin')
            ->deleteJson(route('products.destroy', $product))
            ->assertOk()
            ->assertJsonFragment(['message' => 'Product deleted successfully.']);

        $this->assertSoftDeleted($product);
        $this->assertSame($admin->id, $product->fresh()->deleted_by);
    }

    public function test_api_delete_product_is_blocked_by_the_same_guard(): void
    {
        $admin = $this->adminWith('products.delete');
        $product = $this->product();

        Stock::create([
            'fk_product_id' => $product->id,
            'quantity' => 3,
            'buy_price' => 5,
            'sale_price' => 10,
            'status' => 'active',
        ]);

        Sanctum::actingAs($admin);

        $this->deleteJson('/api/v1/delete-product/'.$product->id)
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'This product cannot be deleted because it is used in 1 stock record. Mark the product as inactive instead.',
            ]);

        $this->assertNotSoftDeleted($product);
    }

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Delete Guard Product '.uniqid(),
            'status' => 'active',
        ], $overrides));
    }

    private function adminWith(string $permission): Admin
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'product-delete-'.uniqid()]);
        $role->permissions()->attach(Permission::create(['name' => $permission]));
        $admin->roles()->attach($role);

        return $admin;
    }
}
