<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cashbook;
use App\Models\Customer;
use App\Models\Income;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SyncController extends Controller
{
    private const PULL_TABLES = [
        'categories',
        'products',
        'stocks',
        'customers',
        'sales',
        'sale_details',
        'payments',
        'stock_adjustments',
        'incomes',
        'expenses',
    ];

    public function pull(Request $request)
    {
        $sinceRaw = $request->query('since');
        try {
            $since = $sinceRaw ? (new \DateTimeImmutable($sinceRaw))->format('Y-m-d H:i:s') : null;
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid since parameter'], 422);
        }
        $tables = array_filter(explode(',', (string) $request->query('tables', '')));
        $tables = $tables === [] ? self::PULL_TABLES : array_values(array_intersect($tables, self::PULL_TABLES));

        $data = [];
        foreach ($tables as $table) {
            $q = DB::table($table);
            if ($table !== 'payments') {
                $q->where(function ($w) use ($since) {
                    if ($since) {
                        $w->where('updated_at', '>', $since)
                            ->orWhere('deleted_at', '>', $since);
                    }
                });
                if (! $since) {
                    // full pull: everything including tombstones
                }
            } elseif ($since) {
                $q->where('created_at', '>', $since);
            }
            $data[$table] = $q->orderBy('id')
                ->limit(5000)
                ->get()
                ->all();
        }

        return response()->json([
            'data' => $data,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function push(Request $request)
    {
        $adminId = auth('admin')->id();
        $body = $request->json()->all();
        $mappings = [
            'customers' => [],
            'sales' => [],
            'stock_adjustments' => [],
            'incomes' => [],
            'expenses' => [],
        ];
        $errors = [];

        // Each row runs inside its own nested transaction (savepoint): a
        // failing row rolls back its partial writes without poisoning the rest.
        $run = function (string $table, array $row, callable $handler) use (&$mappings, &$errors) {
            $uuid = $row['uuid'] ?? null;
            try {
                $mappings[$table][] = DB::transaction(fn () => $handler($row));
            } catch (\Throwable $e) {
                $errors[] = ['table' => $table, 'uuid' => $uuid, 'message' => $e->getMessage()];
            }
        };

        foreach ((array) ($body['customers'] ?? []) as $row) {
            $run('customers', $row, fn (array $r) => $this->syncCustomer($r));
        }

        foreach ((array) ($body['sales'] ?? []) as $row) {
            $run('sales', $row, fn (array $r) => $this->syncSale($r, $adminId));
        }

        foreach ((array) ($body['stock_adjustments'] ?? []) as $row) {
            $run('stock_adjustments', $row, fn (array $r) => $this->syncStockAdjustment($r, $adminId));
        }

        foreach ((array) ($body['incomes'] ?? []) as $row) {
            $run('incomes', $row, fn (array $r) => $this->syncIncomeOrExpense($r, $adminId, true));
        }

        foreach ((array) ($body['expenses'] ?? []) as $row) {
            $run('expenses', $row, fn (array $r) => $this->syncIncomeOrExpense($r, $adminId, false));
        }

        return response()->json([
            'mappings' => $mappings,
            'errors' => $errors,
            'synced_at' => now()->toIso8601String(),
        ]);
    }

    private function syncCustomer(array $row): array
    {
        $uuid = $row['uuid'] ?? (string) Str::uuid();
        /** @var Customer|null $customer */
        $customer = Customer::withTrashed()->where('uuid', $uuid)->first();

        if ($customer) {
            foreach (['name', 'phone', 'address', 'status'] as $field) {
                if (array_key_exists($field, $row)) {
                    $customer->{$field} = $row[$field];
                }
            }
            $customer->updated_at = now();
            $customer->save();

            return ['uuid' => $uuid, 'id' => $customer->id, 'duplicate' => true];
        }

        $email = trim((string) ($row['email'] ?? ''));
        if ($email === '') {
            $email = $uuid.'@pos.local';
        } elseif (Customer::withTrashed()->where('email', $email)->exists()) {
            $email = $uuid.'@pos.local';
        }

        $customer = new Customer();
        $customer->uuid = $uuid;
        $customer->name = $row['name'] ?? 'Walk-in';
        $customer->email = $email;
        $customer->password = Hash::make(Str::random(16));
        $customer->phone = $row['phone'] ?? '';
        $customer->address = $row['address'] ?? '';
        $customer->status = $row['status'] ?? 'active';
        $customer->balance = 0;
        $customer->created_at = now();
        $customer->updated_at = now();
        $customer->save();

        return ['uuid' => $uuid, 'id' => $customer->id];
    }

    private function syncSale(array $row, ?int $adminId): array
    {
        $uuid = $row['uuid'] ?? (string) Str::uuid();
        $detailsIn = (array) ($row['sale_details'] ?? []);
        $paymentsIn = (array) ($row['payments'] ?? []);

        /** @var Sale|null $sale */
        $sale = Sale::withTrashed()->where('uuid', $uuid)->first();

        if ($sale) {
            $children = $this->syncSaleChildren($sale, $detailsIn, $paymentsIn);

            return ['uuid' => $uuid, 'id' => $sale->id, 'duplicate' => true, 'children' => $children];
        }

        $invoiceId = substr((string) ($row['invoice_id'] ?? ''), 0, 30);
        if ($invoiceId === '' || Sale::withTrashed()->where('invoice_id', $invoiceId)->exists()) {
            $invoiceId = substr('INV-'.strtoupper(Str::random(8)).'-'.time().'-'.substr($uuid, 0, 4), 0, 30);
        }

        $createdAt = isset($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : now();

        $sale = new Sale();
        $sale->uuid = $uuid;
        $sale->fk_user_id = $row['fk_user_id'] ?? null;
        $sale->invoice_id = $invoiceId;
        $sale->type = 'POS';
        $sale->net_price = $row['net_price'] ?? 0;
        $sale->vat_amount = $row['vat_amount'] ?? 0;
        $sale->tax_amount = $row['tax_amount'] ?? 0;
        $sale->shipping_cost = $row['shipping_cost'] ?? 0;
        $sale->discount_amount = $row['discount_amount'] ?? 0;
        $sale->grand_total = $row['grand_total'] ?? 0;
        $sale->paid_amount = $row['paid_amount'] ?? ($row['grand_total'] ?? 0);
        $sale->sale_due = 0;
        $sale->status = 'completed';
        $sale->note = $row['note'] ?? '';
        $sale->created_by = $adminId;
        $sale->created_at = $createdAt;
        $sale->updated_at = now();
        $sale->save();

        $detailMappings = [];
        foreach ($detailsIn as $d) {
            $detailUuid = $d['uuid'] ?? (string) Str::uuid();
            $existingDetail = SaleDetail::withTrashed()->where('uuid', $detailUuid)->first();
            if ($existingDetail) {
                $detailMappings[] = ['uuid' => $detailUuid, 'id' => $existingDetail->id, 'duplicate' => true];
                continue;
            }

            $stock = Stock::find($d['fk_stock_id']);
            if (! $stock) {
                throw new \InvalidArgumentException("Unknown fk_stock_id {$d['fk_stock_id']}");
            }
            $totalStock = $stock->quantity;

            $detail = new SaleDetail();
            $detail->uuid = $detailUuid;
            $detail->fk_sale_id = $sale->id;
            $detail->fk_stock_id = $stock->id;
            $detail->stock_name = $stock->product->name ?? '';
            $detail->total_stock = $totalStock;
            $detail->sale_stock = $d['sale_stock'];
            $detail->subtotal = $d['subtotal'];
            $detail->created_at = $createdAt;
            $detail->updated_at = now();
            $detail->save();

            Stock::where('id', $stock->id)->decrement('quantity', $d['sale_stock']);
            $detailMappings[] = ['uuid' => $detailUuid, 'id' => $detail->id];
        }

        $paymentMappings = [];
        foreach ($paymentsIn as $p) {
            $paymentUuid = $p['uuid'] ?? (string) Str::uuid();
            $existingPayment = Payment::where('uuid', $paymentUuid)->first();
            if ($existingPayment) {
                $paymentMappings[] = ['uuid' => $paymentUuid, 'id' => $existingPayment->id, 'duplicate' => true];
                continue;
            }
            $payment = new Payment();
            $payment->uuid = $paymentUuid;
            $payment->fk_sale_id = $sale->id;
            $payment->method = $p['method'] ?? 'cash';
            $payment->amount = $p['amount'] ?? 0;
            $payment->change_amount = $p['change_amount'] ?? 0;
            $payment->transaction_ref = $p['transaction_ref'] ?? null;
            $payment->created_at = $createdAt;
            $payment->save();
            $paymentMappings[] = ['uuid' => $paymentUuid, 'id' => $payment->id];
        }

        $total = (float) $sale->grand_total;
        $transaction = Transaction::create([
            'date' => $createdAt->format('Y-m-d'),
            'type' => Transaction::TYPE_SALE_INCOME,
            'fk_reference_id' => $sale->id,
            'amount' => $total,
            'paid_amount' => $total,
            'due_amount' => 0,
            'created_by' => $adminId,
        ]);

        Income::create([
            'table_name' => 'sales',
            'fk_transaction_id' => $transaction->id,
            'description' => "Sale #{$sale->invoice_id}",
            'amount' => $total,
            'created_by' => $adminId,
            'created_at' => $createdAt,
            'updated_at' => now(),
        ]);

        Cashbook::create([
            'table_name' => 'sales',
            'fk_reference_id' => $sale->id,
            'description' => "Sale #{$sale->invoice_id}",
            'in_amount' => $total,
            'out_amount' => 0,
            'amount_payable' => 0,
            'amount_receivable' => 0,
            'created_by' => $adminId,
        ]);

        return [
            'uuid' => $uuid,
            'id' => $sale->id,
            'invoice_id' => $sale->invoice_id,
            'children' => ['sale_details' => $detailMappings, 'payments' => $paymentMappings],
        ];
    }

    private function syncSaleChildren(Sale $sale, array $detailsIn, array $paymentsIn): array
    {
        $detailMappings = [];
        foreach ($detailsIn as $d) {
            if (empty($d['uuid'])) {
                continue;
            }
            $existing = SaleDetail::withTrashed()->where('uuid', $d['uuid'])->first();
            $detailMappings[] = $existing
                ? ['uuid' => $d['uuid'], 'id' => $existing->id, 'duplicate' => true]
                : ['uuid' => $d['uuid'], 'id' => null];
        }
        $paymentMappings = [];
        foreach ($paymentsIn as $p) {
            if (empty($p['uuid'])) {
                continue;
            }
            $existing = Payment::where('uuid', $p['uuid'])->first();
            $paymentMappings[] = $existing
                ? ['uuid' => $p['uuid'], 'id' => $existing->id, 'duplicate' => true]
                : ['uuid' => $p['uuid'], 'id' => null];
        }

        return ['sale_details' => $detailMappings, 'payments' => $paymentMappings];
    }

    private function syncStockAdjustment(array $row, ?int $adminId): array
    {
        $uuid = $row['uuid'] ?? (string) Str::uuid();
        $existing = StockAdjustment::withTrashed()->where('uuid', $uuid)->first();
        if ($existing) {
            return ['uuid' => $uuid, 'id' => $existing->id, 'duplicate' => true];
        }

        $stock = Stock::find($row['fk_stock_id'] ?? null);
        if (! $stock) {
            throw new \InvalidArgumentException('Unknown fk_stock_id');
        }
        $quantity = (int) ($row['quantity'] ?? 0);
        if ($quantity < 1) {
            throw new \InvalidArgumentException('quantity must be >= 1');
        }
        $reason = $row['reason'] ?? 'correction';
        $newQty = $reason === 'return'
            ? $stock->quantity + $quantity
            : max(0, $stock->quantity - $quantity);

        $adjustment = new StockAdjustment();
        $adjustment->uuid = $uuid;
        $adjustment->fk_stock_id = $stock->id;
        $adjustment->fk_warehouse_id = $row['fk_warehouse_id'] ?? null;
        $adjustment->batch = $row['batch'] ?? null;
        $adjustment->lot = $row['lot'] ?? null;
        $adjustment->quantity = $quantity;
        $adjustment->reason = $reason;
        $adjustment->created_by = $adminId;
        $adjustment->created_at = isset($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : now();
        $adjustment->updated_at = now();
        $adjustment->save();

        $stock->quantity = $newQty;
        $stock->updated_at = now();
        $stock->save();

        return ['uuid' => $uuid, 'id' => $adjustment->id];
    }

    private function syncIncomeOrExpense(array $row, ?int $adminId, bool $isIncome): array
    {
        $uuid = $row['uuid'] ?? (string) Str::uuid();
        if ($isIncome && Income::withTrashed()->where('uuid', $uuid)->exists()) {
            return ['uuid' => $uuid, 'duplicate' => true];
        }
        if (! $isIncome && Expense::withTrashed()->where('uuid', $uuid)->exists()) {
            return ['uuid' => $uuid, 'duplicate' => true];
        }

        $amount = (float) ($row['amount'] ?? 0);
        $description = (string) ($row['description'] ?? 'POS entry');
        $createdAt = isset($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : now();

        $transaction = Transaction::create([
            'date' => $createdAt->format('Y-m-d'),
            'type' => $isIncome ? Transaction::TYPE_INCOME : Transaction::TYPE_EXPENSE,
            'fk_reference_id' => 0,
            'amount' => $amount,
            'paid_amount' => $amount,
            'due_amount' => 0,
            'created_by' => $adminId,
        ]);

        if ($isIncome) {
            $entry = new Income();
            $entry->uuid = $uuid;
            $entry->table_name = 'incomes';
            $entry->fk_transaction_id = $transaction->id;
            $entry->description = $description;
            $entry->amount = $amount;
            $entry->created_by = $adminId;
            $entry->created_at = $createdAt;
            $entry->updated_at = now();
            $entry->save();
            $transaction->update(['fk_reference_id' => $entry->id]);

            Cashbook::create([
                'table_name' => 'incomes',
                'fk_reference_id' => $entry->id,
                'description' => $description,
                'in_amount' => $amount,
                'out_amount' => 0,
                'amount_payable' => 0,
                'amount_receivable' => 0,
                'created_by' => $adminId,
            ]);

            return ['uuid' => $uuid, 'id' => $entry->id];
        }

        $entry = new Expense();
        $entry->uuid = $uuid;
        $entry->table_name = 'expenses';
        $entry->fk_transaction_id = $transaction->id;
        $entry->description = $description;
        $entry->amount = $amount;
        $entry->created_by = $adminId;
        $entry->created_at = $createdAt;
        $entry->updated_at = now();
        $entry->save();
        $transaction->update(['fk_reference_id' => $entry->id]);

        Cashbook::create([
            'table_name' => 'expenses',
            'fk_reference_id' => $entry->id,
            'description' => $description,
            'in_amount' => 0,
            'out_amount' => $amount,
            'amount_payable' => 0,
            'amount_receivable' => 0,
            'created_by' => $adminId,
        ]);

        return ['uuid' => $uuid, 'id' => $entry->id];
    }
}
