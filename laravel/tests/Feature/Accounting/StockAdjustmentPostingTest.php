<?php

namespace Tests\Feature\Accounting;

use App\Models\Admin;
use App\Models\JournalEntry;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Services\LedgerProjector;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAdjustmentPostingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_damage_reduces_stock_and_posts_inventory_loss(): void
    {
        $admin = $this->adminWith('stocks.save');
        $stock = $this->makeStock(quantity: 10, buyPrice: 25);

        $this->actingAs($admin, 'admin')->postJson(route('stock-adjustments.store'), [
            'fk_stock_id' => $stock->id,
            'quantity' => 3,
            'reason' => 'damage',
        ])->assertOk();

        $this->assertSame(7, $stock->fresh()->quantity);

        $adjustment = StockAdjustment::latest('id')->firstOrFail();
        $lines = $this->lines("stock_adjustment:{$adjustment->id}");

        $this->assertSame(75.0, $lines['inventory_adjustment']['debit']);
        $this->assertSame(75.0, $lines['inventory']['credit']);
        $this->assertSame(
            $this->entry("stock_adjustment:{$adjustment->id}")->id,
            $adjustment->fresh()->journal_entry_id
        );
    }

    public function test_return_adds_stock_and_reverses_the_loss(): void
    {
        $admin = $this->adminWith('stocks.save');
        $stock = $this->makeStock(quantity: 10, buyPrice: 25);

        $this->actingAs($admin, 'admin')->postJson(route('stock-adjustments.store'), [
            'fk_stock_id' => $stock->id,
            'quantity' => 4,
            'reason' => 'return',
        ])->assertOk();

        $this->assertSame(14, $stock->fresh()->quantity);

        $adjustment = StockAdjustment::latest('id')->firstOrFail();
        $lines = $this->lines("stock_adjustment:{$adjustment->id}");

        $this->assertSame(100.0, $lines['inventory']['debit']);
        $this->assertSame(100.0, $lines['inventory_adjustment']['credit']);
    }

    public function test_zero_cost_stock_does_not_post_an_entry(): void
    {
        $admin = $this->adminWith('stocks.save');
        $stock = $this->makeStock(quantity: 10, buyPrice: 0);

        $this->actingAs($admin, 'admin')->postJson(route('stock-adjustments.store'), [
            'fk_stock_id' => $stock->id,
            'quantity' => 2,
            'reason' => 'damage',
        ])->assertOk();

        $this->assertSame(0, JournalEntry::where('source_type', 'stock_adjustment')->count());
    }

    public function test_backfill_posts_existing_stock_adjustments(): void
    {
        $stock = $this->makeStock(quantity: 10, buyPrice: 12);

        $adjustment = StockAdjustment::create([
            'fk_stock_id' => $stock->id,
            'quantity' => 2,
            'reason' => 'damage',
        ]);

        app(LedgerProjector::class)->run(['stock_adjustments']);

        $lines = $this->lines("stock_adjustment:{$adjustment->id}");

        $this->assertSame(24.0, $lines['inventory_adjustment']['debit']);
        $this->assertSame(24.0, $lines['inventory']['credit']);
        $this->assertSame(
            $this->entry("stock_adjustment:{$adjustment->id}")->id,
            $adjustment->fresh()->journal_entry_id
        );
    }

    private function adminWith(string $permission): Admin
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'stock-'.uniqid()]);
        $role->permissions()->attach(Permission::create(['name' => $permission]));
        $admin->roles()->attach($role);

        return $admin;
    }

    private function makeStock(int $quantity, float $buyPrice): Stock
    {
        return Stock::create([
            'quantity' => $quantity,
            'buy_price' => $buyPrice,
            'sale_price' => $buyPrice * 2,
            'status' => 'active',
        ]);
    }

    private function entry(string $sourceKey): JournalEntry
    {
        return JournalEntry::with('lines.account')->where('source_key', $sourceKey)->firstOrFail();
    }

    /**
     * @return array<string, array{debit: float, credit: float}>
     */
    private function lines(string $sourceKey): array
    {
        return $this->entry($sourceKey)->lines->mapWithKeys(function ($line) {
            return [$line->account->system_key => [
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
            ]];
        })->all();
    }
}
