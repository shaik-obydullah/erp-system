<?php

namespace App\Services;

use App\Accounting\AccountingPoster;
use App\Accounting\LedgerService;
use App\Models\Cashbook;
use App\Models\Shipment;
use App\Models\ShipmentCost;
use App\Models\Supplier;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Records landed costs for an inbound shipment in both layers:
 *  - simple finance: transactions + cashbook, so the money is visible where
 *    users already look;
 *  - double-entry: inventory is debited and cash/payables credited.
 */
class ShipmentCostService
{
    public function __construct(
        private readonly AccountingPoster $poster,
        private readonly LedgerService $ledger,
    ) {
    }

    public function create(Shipment $shipment, array $data, ?int $adminId = null): ShipmentCost
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'cost.amount' => 'Enter a shipment cost greater than zero.',
            ]);
        }

        $paymentStatus = ($data['payment_status'] ?? ShipmentCost::STATUS_PAID) === ShipmentCost::STATUS_UNPAID
            ? ShipmentCost::STATUS_UNPAID
            : ShipmentCost::STATUS_PAID;

        $supplier = null;

        if (! empty($data['fk_supplier_id'])) {
            $supplier = Supplier::find($data['fk_supplier_id']);

            if (! $supplier) {
                throw ValidationException::withMessages([
                    'cost.fk_supplier_id' => 'The selected supplier could not be found.',
                ]);
            }
        }

        if ($paymentStatus === ShipmentCost::STATUS_UNPAID && ! $supplier) {
            throw ValidationException::withMessages([
                'cost.fk_supplier_id' => 'Choose a supplier when the shipment cost is on credit.',
            ]);
        }

        $date = $this->dateString($data['cost_date'] ?? null);

        return DB::transaction(function () use ($shipment, $data, $amount, $paymentStatus, $supplier, $date, $adminId) {
            $cost = ShipmentCost::create([
                'fk_shipment_id' => $shipment->id,
                'fk_supplier_id' => $supplier?->id,
                'cost_type' => $data['cost_type'] ?? ShipmentCost::TYPE_FREIGHT,
                'amount' => $amount,
                'cost_date' => $date,
                'payment_status' => $paymentStatus,
                'paid_date' => $paymentStatus === ShipmentCost::STATUS_PAID ? $date : null,
                'invoice_number' => $data['invoice_number'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'description' => $data['description'] ?? null,
                'created_by' => $adminId,
            ]);

            $transaction = Transaction::create([
                'date' => $date,
                'type' => Transaction::TYPE_SHIPMENT_COST,
                'fk_reference_id' => $shipment->id,
                'amount' => $amount,
                'paid_amount' => $paymentStatus === ShipmentCost::STATUS_PAID ? $amount : 0,
                'due_amount' => $paymentStatus === ShipmentCost::STATUS_UNPAID ? $amount : 0,
                'created_by' => $adminId,
            ]);

            $cashbook = null;

            if ($paymentStatus === ShipmentCost::STATUS_PAID) {
                $cashbook = Cashbook::create([
                    'table_name' => 'shipments',
                    'fk_reference_id' => $shipment->id,
                    'description' => $this->description($shipment, $cost),
                    'in_amount' => 0,
                    'out_amount' => $amount,
                    'amount_payable' => 0,
                    'amount_receivable' => 0,
                    'created_by' => $adminId,
                ]);
            } elseif ($supplier) {
                $supplier->decrement('balance', $amount);
                $supplier->update(['updated_by' => $adminId]);
            }

            $cost->update([
                'fk_transaction_id' => $transaction->id,
                'fk_cashbook_id' => $cashbook?->id,
            ]);

            $this->poster->shipmentCost($cost->fresh(), $adminId);

            return $cost->fresh();
        });
    }

    public function markPaid(ShipmentCost $cost, array $data = [], ?int $adminId = null): ShipmentCost
    {
        if ($cost->isPaid()) {
            return $cost;
        }

        $supplier = $cost->fk_supplier_id
            ? Supplier::withTrashed()->find($cost->fk_supplier_id)
            : null;

        if (! $supplier) {
            throw ValidationException::withMessages([
                'cost' => 'A supplier is required before this cost can be paid.',
            ]);
        }

        $date = $this->dateString($data['paid_date'] ?? null);
        $amount = round((float) $cost->amount, 2);

        return DB::transaction(function () use ($cost, $supplier, $amount, $date, $adminId) {
            $transaction = Transaction::create([
                'date' => $date,
                'type' => Transaction::TYPE_SUPPLIER_PAYMENT,
                'fk_reference_id' => $supplier->id,
                'amount' => $amount,
                'paid_amount' => $amount,
                'due_amount' => 0,
                'created_by' => $adminId,
            ]);

            $cashbook = Cashbook::create([
                'table_name' => 'suppliers',
                'fk_reference_id' => $supplier->id,
                'description' => $this->description($cost->shipment, $cost, 'Payment'),
                'in_amount' => 0,
                'out_amount' => $amount,
                'amount_payable' => 0,
                'amount_receivable' => 0,
                'created_by' => $adminId,
            ]);

            $supplier->increment('balance', $amount);
            $supplier->update(['updated_by' => $adminId]);

            $this->poster->supplierPayment(
                $supplier,
                $amount,
                $transaction->id,
                $this->description($cost->shipment, $cost, 'Payment'),
                $adminId,
                $cashbook->id
            );

            $cost->update([
                'payment_status' => ShipmentCost::STATUS_PAID,
                'paid_date' => $date,
                'fk_payment_transaction_id' => $transaction->id,
                'fk_payment_cashbook_id' => $cashbook->id,
                'updated_by' => $adminId,
            ]);

            // The original cost transaction now shows as fully paid too.
            Transaction::where('id', $cost->fk_transaction_id)->update([
                'paid_amount' => $amount,
                'due_amount' => 0,
                'updated_by' => $adminId,
            ]);

            return $cost->fresh();
        });
    }

    public function delete(ShipmentCost $cost, ?int $adminId = null): void
    {
        DB::transaction(function () use ($cost, $adminId) {
            $cost->loadMissing(['journalEntry', 'transaction', 'cashbook', 'paymentTransaction', 'paymentCashbook']);

            // An unpaid cost reduced the supplier balance; put it back.
            if (! $cost->isPaid() && $cost->fk_supplier_id) {
                Supplier::withTrashed()->find($cost->fk_supplier_id)?->increment('balance', (float) $cost->amount);
            }

            if ($cost->paymentTransaction) {
                if ($cost->paymentTransaction->journal_entry_id) {
                    $entry = $cost->paymentTransaction->journalEntry;

                    if ($entry) {
                        $this->ledger->reverse($entry, "Reversal of deleted shipment cost payment #{$cost->id}", $adminId);
                    }
                }

                $this->softDeleteTransaction($cost->paymentTransaction, $adminId);
            }

            if ($cost->paymentCashbook) {
                $this->softDeleteCashbook($cost->paymentCashbook, $adminId);
            }

            if ($cost->journalEntry) {
                $this->ledger->reverse($cost->journalEntry, "Reversal of deleted shipment cost #{$cost->id}", $adminId);
            }

            if ($cost->transaction) {
                $this->softDeleteTransaction($cost->transaction, $adminId);
            }

            if ($cost->cashbook) {
                $this->softDeleteCashbook($cost->cashbook, $adminId);
            }

            $cost->update(['deleted_by' => $adminId]);
            $cost->delete();
        });
    }

    private function softDeleteTransaction(Transaction $transaction, ?int $adminId): void
    {
        $transaction->update(['deleted_by' => $adminId]);
        $transaction->delete();
    }

    private function softDeleteCashbook(Cashbook $cashbook, ?int $adminId): void
    {
        $cashbook->update(['deleted_by' => $adminId]);
        $cashbook->delete();
    }

    private function dateString(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return $value ?: now()->toDateString();
    }

    private function description(Shipment $shipment, ShipmentCost $cost, string $prefix = ''): string
    {
        $type = ucfirst($cost->cost_type);
        $tracking = $shipment->tracking_number ?? ('#'.$shipment->id);

        return trim($prefix.' '.$type.' for shipment '.$tracking);
    }
}
