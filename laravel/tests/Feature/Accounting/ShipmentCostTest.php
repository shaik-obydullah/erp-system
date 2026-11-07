<?php

namespace Tests\Feature\Accounting;

use App\Accounting\AccountingPoster;
use App\Models\Admin;
use App\Models\Cashbook;
use App\Models\JournalEntry;
use App\Models\Need;
use App\Models\Permission;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\ShipmentCost;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\Warehouse;
use App\Services\LedgerProjector;
use App\Services\ShipmentCostService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentCostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_paid_shipment_cost_is_capitalised_and_paid_from_cash(): void
    {
        [$shipment, $supplier] = $this->makeShipment();

        $cost = app(ShipmentCostService::class)->create($shipment, [
            'cost_type' => ShipmentCost::TYPE_FREIGHT,
            'amount' => 250,
            'fk_supplier_id' => $supplier->id,
            'payment_status' => ShipmentCost::STATUS_PAID,
            'cost_date' => now()->toDateString(),
            'description' => 'Sea freight',
        ]);

        $lines = $this->lines($this->entry("shipment_cost:{$cost->id}:cost"));

        $this->assertSame(250.0, $lines['inventory']['debit']);
        $this->assertSame(250.0, $lines['cash']['credit']);
        $this->assertArrayNotHasKey('accounts_payable', $lines);

        // The simple finance layer mirrors the same money movement.
        $transaction = $cost->transaction;
        $this->assertSame(Transaction::TYPE_SHIPMENT_COST, $transaction->type);
        $this->assertSame($shipment->id, $transaction->fk_reference_id);
        $this->assertSame(250.0, (float) $transaction->paid_amount);

        $cashbook = $cost->cashbook;
        $this->assertSame('shipments', $cashbook->table_name);
        $this->assertSame($shipment->id, $cashbook->fk_reference_id);
        $this->assertSame(250.0, (float) $cashbook->out_amount);

        $entry = $this->entry("shipment_cost:{$cost->id}:cost");
        $this->assertSame($entry->id, $cost->fresh()->journal_entry_id);
        $this->assertSame($entry->id, $transaction->fresh()->journal_entry_id);
        $this->assertSame($entry->id, $cashbook->fresh()->journal_entry_id);

        // A cost paid immediately does not touch supplier balances.
        $this->assertSame(0.0, (float) $supplier->fresh()->balance);
    }

    public function test_credit_shipment_cost_is_capitalised_to_accounts_payable(): void
    {
        [$shipment, $supplier] = $this->makeShipment();

        $cost = app(ShipmentCostService::class)->create($shipment, [
            'cost_type' => ShipmentCost::TYPE_DUTY,
            'amount' => 400,
            'fk_supplier_id' => $supplier->id,
            'payment_status' => ShipmentCost::STATUS_UNPAID,
            'cost_date' => now()->toDateString(),
        ]);

        $entry = $this->entry("shipment_cost:{$cost->id}:cost");
        $lines = $this->lines($entry);

        $this->assertSame(400.0, $lines['inventory']['debit']);
        $this->assertSame(400.0, $lines['accounts_payable']['credit']);
        $this->assertArrayNotHasKey('cash', $lines);

        $payable = $entry->lines->firstWhere('account.system_key', 'accounts_payable');
        $this->assertSame('supplier', $payable->party_type);
        $this->assertSame($supplier->id, $payable->party_id);

        $this->assertSame(-400.0, (float) $supplier->fresh()->balance);

        $this->assertSame(0.0, (float) $cost->transaction->paid_amount);
        $this->assertSame(400.0, (float) $cost->transaction->due_amount);
        $this->assertNull($cost->cashbook);
    }

    public function test_credit_cost_can_be_paid_later_and_keeps_the_payable_entry(): void
    {
        [$shipment, $supplier] = $this->makeShipment();

        $service = app(ShipmentCostService::class);
        $cost = $service->create($shipment, [
            'cost_type' => ShipmentCost::TYPE_HANDLING,
            'amount' => 150,
            'fk_supplier_id' => $supplier->id,
            'payment_status' => ShipmentCost::STATUS_UNPAID,
            'cost_date' => now()->toDateString(),
        ]);

        $this->assertSame(-150.0, (float) $supplier->fresh()->balance);

        $paid = $service->markPaid($cost, ['paid_date' => now()->toDateString()]);

        $this->assertTrue($paid->isPaid());
        $this->assertSame(0.0, (float) $supplier->fresh()->balance);
        $this->assertNotNull($paid->fk_payment_transaction_id);
        $this->assertNotNull($paid->fk_payment_cashbook_id);

        // The original entry stays as Inventory / AP; the payment clears AP.
        $original = $this->lines($this->entry("shipment_cost:{$paid->id}:cost"));
        $this->assertSame(150.0, $original['accounts_payable']['credit']);
        $this->assertArrayNotHasKey('cash', $original);

        $payment = $this->lines($this->entry("supplier_payment:{$paid->fk_payment_transaction_id}"));
        $this->assertSame(150.0, $payment['accounts_payable']['debit']);
        $this->assertSame(150.0, $payment['cash']['credit']);

        $this->assertSame(150.0, (float) $paid->transaction->fresh()->paid_amount);
        $this->assertSame(0.0, (float) $paid->transaction->fresh()->due_amount);

        $this->assertSame('suppliers', $paid->paymentCashbook->table_name);
        $this->assertSame($supplier->id, $paid->paymentCashbook->fk_reference_id);
    }

    public function test_deleting_a_credit_cost_reverses_the_ledger_and_finance_rows(): void
    {
        [$shipment, $supplier] = $this->makeShipment();

        $service = app(ShipmentCostService::class);
        $cost = $service->create($shipment, [
            'cost_type' => ShipmentCost::TYPE_INSURANCE,
            'amount' => 90,
            'fk_supplier_id' => $supplier->id,
            'payment_status' => ShipmentCost::STATUS_UNPAID,
            'cost_date' => now()->toDateString(),
        ]);

        $this->assertSame(-90.0, (float) $supplier->fresh()->balance);

        $service->delete($cost);

        // Supplier balance was restored because the payable was still open.
        $this->assertSame(0.0, (float) $supplier->fresh()->balance);

        $this->assertNotNull(
            JournalEntry::where('source_key', "shipment_cost:{$cost->id}:cost:reversal")->first()
        );

        $this->assertTrue(Transaction::withTrashed()->find($cost->fk_transaction_id)->trashed());
        $this->assertTrue(ShipmentCost::withTrashed()->find($cost->id)->trashed());

        $totalDebit = (float) \App\Models\JournalLine::sum('debit');
        $totalCredit = (float) \App\Models\JournalLine::sum('credit');
        $this->assertSame(round($totalDebit, 2), round($totalCredit, 2));
    }

    public function test_deleting_a_paid_cost_reverses_cash_and_finance_rows(): void
    {
        [$shipment, $supplier] = $this->makeShipment();

        $service = app(ShipmentCostService::class);
        $cost = $service->create($shipment, [
            'cost_type' => ShipmentCost::TYPE_OTHER,
            'amount' => 60,
            'fk_supplier_id' => $supplier->id,
            'payment_status' => ShipmentCost::STATUS_PAID,
            'cost_date' => now()->toDateString(),
        ]);

        $cashbookId = $cost->fk_cashbook_id;
        $transactionId = $cost->fk_transaction_id;

        $service->delete($cost);

        $this->assertTrue(Transaction::withTrashed()->find($transactionId)->trashed());
        $this->assertTrue(Cashbook::withTrashed()->find($cashbookId)->trashed());
        $this->assertNotNull(
            JournalEntry::where('source_key', "shipment_cost:{$cost->id}:cost:reversal")->first()
        );
    }

    public function test_shipment_cost_posting_and_backfill_are_idempotent(): void
    {
        [$shipment, $supplier] = $this->makeShipment();

        $cost = app(ShipmentCostService::class)->create($shipment, [
            'cost_type' => ShipmentCost::TYPE_FREIGHT,
            'amount' => 120,
            'fk_supplier_id' => $supplier->id,
            'payment_status' => ShipmentCost::STATUS_UNPAID,
            'cost_date' => now()->toDateString(),
        ]);

        app(AccountingPoster::class)->shipmentCost($cost->fresh());
        app(LedgerProjector::class)->shipmentCosts();
        app(LedgerProjector::class)->shipmentCosts();

        $this->assertSame(1, JournalEntry::where('source_key', "shipment_cost:{$cost->id}:cost")->count());
    }

    public function test_shipment_form_records_a_landed_cost_with_the_shipment(): void
    {
        $admin = $this->adminWithPermissions();
        [$shipment, $supplier] = $this->makeShipment();

        $response = $this->actingAs($admin, 'admin')->post(route('shipments.store'), [
            'fk_po_id' => $shipment->fk_po_id,
            'fk_warehouse_id' => $shipment->fk_warehouse_id,
            'tracking_number' => 'TRACK-COST-1',
            'received_date' => now()->toDateString(),
            'status' => 'shipped',
            'remark' => 'With freight',
            'cost' => [
                'cost_type' => 'freight',
                'amount' => 75,
                'fk_supplier_id' => $supplier->id,
                'payment_status' => 'paid',
                'cost_date' => now()->toDateString(),
                'invoice_number' => 'FR-1',
                'description' => 'Freight invoice',
            ],
        ]);

        $response->assertRedirect(route('shipments.index'));

        $created = Shipment::where('tracking_number', 'TRACK-COST-1')->firstOrFail();
        $cost = $created->costs()->firstOrFail();

        $this->assertSame('freight', $cost->cost_type);
        $this->assertSame(75.0, (float) $cost->amount);
        $this->assertSame(75.0, $created->landedCostTotal());

        $this->assertDatabaseHas('transactions', [
            'id' => $cost->fk_transaction_id,
            'type' => 'shipmentCost',
            'fk_reference_id' => $created->id,
        ]);

        $this->assertDatabaseHas('cashbook', [
            'id' => $cost->fk_cashbook_id,
            'table_name' => 'shipments',
            'fk_reference_id' => $created->id,
        ]);

        $this->assertNotNull($cost->journal_entry_id);
    }

    public function test_shipment_pages_render_with_landed_costs(): void
    {
        $admin = $this->adminWithPermissions();
        [$shipment, $supplier] = $this->makeShipment();

        app(ShipmentCostService::class)->create($shipment, [
            'cost_type' => ShipmentCost::TYPE_FREIGHT,
            'amount' => 55,
            'fk_supplier_id' => $supplier->id,
            'payment_status' => ShipmentCost::STATUS_PAID,
            'cost_date' => now()->toDateString(),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('shipments.create'))
            ->assertOk()
            ->assertSee('Landed cost');

        $this->actingAs($admin, 'admin')
            ->get(route('shipments.show', $shipment))
            ->assertOk()
            ->assertSee('Landed Costs')
            ->assertSee('Freight')
            ->assertSee('55.00');
    }

    public function test_deleting_a_shipment_unwinds_its_costs(): void
    {
        $admin = $this->adminWithPermissions();
        [$shipment, $supplier] = $this->makeShipment();

        $cost = app(ShipmentCostService::class)->create($shipment, [
            'cost_type' => ShipmentCost::TYPE_FREIGHT,
            'amount' => 45,
            'fk_supplier_id' => $supplier->id,
            'payment_status' => ShipmentCost::STATUS_PAID,
            'cost_date' => now()->toDateString(),
        ]);

        $transactionId = $cost->fk_transaction_id;
        $cashbookId = $cost->fk_cashbook_id;

        $this->actingAs($admin, 'admin')
            ->delete(route('shipments.destroy', $shipment))
            ->assertRedirect(route('shipments.index'));

        $this->assertTrue(Shipment::withTrashed()->find($shipment->id)->trashed());
        $this->assertTrue(ShipmentCost::withTrashed()->find($cost->id)->trashed());
        $this->assertTrue(Transaction::withTrashed()->find($transactionId)->trashed());
        $this->assertTrue(Cashbook::withTrashed()->find($cashbookId)->trashed());
    }

    /**
     * @return array{0: Shipment, 1: Supplier}
     */
    private function makeShipment(): array
    {
        $supplier = Supplier::create([
            'name' => 'Freight Co',
            'email' => 'freight@example.com',
            'password' => 'password',
            'balance' => 0,
            'status' => 'active',
        ]);

        $need = Need::create([
            'description' => 'Stock replenishment',
            'date_identified' => now()->toDateString(),
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'fk_need_id' => $need->id,
            'fk_supplier_id' => $supplier->id,
            'order_number' => 'PO-'.uniqid(),
            'total_amount' => 1000,
            'due_amount' => 1000,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'capacity' => 1000,
            'location' => 'Dhaka',
            'contact_number' => '000',
            'email' => 'warehouse@example.com',
        ]);

        $shipment = Shipment::create([
            'fk_po_id' => $purchaseOrder->id,
            'fk_warehouse_id' => $warehouse->id,
            'tracking_number' => 'TRACK-'.uniqid(),
            'received_date' => now()->toDateString(),
            'status' => 'shipped',
        ]);

        return [$shipment, $supplier];
    }

    private function adminWithPermissions(): Admin
    {
        $admin = Admin::factory()->create();
        $role = Role::create(['name' => 'logistics-'.uniqid()]);

        foreach (['shipments.view', 'shipments.save', 'shipments.edit', 'shipments.delete'] as $permission) {
            $role->permissions()->attach(Permission::create(['name' => $permission]));
        }

        $admin->roles()->attach($role);

        return $admin;
    }

    private function entry(string $sourceKey): JournalEntry
    {
        return JournalEntry::with('lines.account')->where('source_key', $sourceKey)->firstOrFail();
    }

    /**
     * @return array<string, array{debit: float, credit: float}>
     */
    private function lines(JournalEntry $entry): array
    {
        return $entry->lines->mapWithKeys(function ($line) {
            return [$line->account->system_key => [
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
            ]];
        })->all();
    }
}
