<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Cashbook;
use App\Models\JournalLine;
use App\Models\Payable;
use App\Models\PurchaseOrder;
use App\Models\ShipmentCost;
use App\Models\Supplier;
use App\Models\Transaction;
use Illuminate\Support\Str;

/**
 * A supplier with any accounting or finance footprint must not be deleted.
 * Deleting it would orphan ledger entries, payments, advances or purchase
 * history and break the party subledger. Deactivate the supplier instead.
 */
class SupplierDeletionGuard
{
    /**
     * Return the reasons why the supplier cannot be deleted.
     *
     * @return list<string>
     */
    public function blockers(Supplier $supplier): array
    {
        $blockers = [];

        $balance = round((float) $supplier->balance, 2);
        $ledger = $this->ledgerSummary($supplier);

        // A negative balance is money we owe the supplier; a positive one is
        // an advance we have paid. The ledger is authoritative, but the legacy
        // balance is checked as well so imported suppliers are covered.
        $payable = max(
            $balance < 0 ? abs($balance) : 0.0,
            $ledger['payable']
        );

        $advance = max(
            $balance > 0 ? $balance : 0.0,
            $ledger['advance']
        );

        if ($payable > 0) {
            $blockers[] = 'an outstanding payable of '.number_format($payable, 2);
        }

        if ($advance > 0) {
            $blockers[] = 'an advance balance of '.number_format($advance, 2);
        }

        $orders = PurchaseOrder::where('fk_supplier_id', $supplier->id)->count();
        if ($orders > 0) {
            $blockers[] = $orders.' '.Str::plural('purchase order', $orders);
        }

        $shipmentCosts = ShipmentCost::where('fk_supplier_id', $supplier->id)->count();
        if ($shipmentCosts > 0) {
            $blockers[] = $shipmentCosts.' '.Str::plural('shipment cost', $shipmentCosts);
        }

        $cashbook = Cashbook::where('table_name', 'suppliers')
            ->where('fk_reference_id', $supplier->id)
            ->count();
        if ($cashbook > 0) {
            $blockers[] = $cashbook.' '.Str::plural('cashbook transaction', $cashbook);
        }

        $funds = Transaction::whereIn('type', [
            Transaction::TYPE_SUPPLIER_DEPOSIT,
            Transaction::TYPE_SUPPLIER_PAYMENT,
        ])
            ->where('fk_reference_id', $supplier->id)
            ->count();
        if ($funds > 0) {
            $blockers[] = $funds.' '.Str::plural('supplier fund transaction', $funds);
        }

        $payables = Payable::where('table_name', 'suppliers')
            ->where('fk_reference_id', $supplier->id)
            ->count();
        if ($payables > 0) {
            $blockers[] = $payables.' '.Str::plural('payable record', $payables);
        }

        // Any remaining ledger activity (for example a fully settled payable)
        // still has to be kept for audit purposes.
        if ($ledger['lines'] > 0 && $payable <= 0 && $advance <= 0) {
            $blockers[] = $ledger['lines'].' '.Str::plural('accounting ledger entry', $ledger['lines']);
        }

        return $blockers;
    }

    /**
     * Build the user-facing message for a set of blockers.
     *
     * @param  list<string>  $blockers
     */
    public function message(array $blockers): string
    {
        return 'This supplier cannot be deleted because it still has '
            .$this->join($blockers)
            .'. Mark the supplier as inactive instead.';
    }

    /**
     * @return array{lines: int, payable: float, advance: float}
     */
    private function ledgerSummary(Supplier $supplier): array
    {
        $lines = JournalLine::query()
            ->where('party_type', 'supplier')
            ->where('party_id', $supplier->id)
            ->whereHas('entry', fn ($query) => $query->posted());

        $payableAccount = Account::where('system_key', 'accounts_payable')->value('id');
        $advanceAccount = Account::where('system_key', 'supplier_advances')->value('id');

        $payable = 0.0;
        if ($payableAccount) {
            $payable = (float) (clone $lines)
                ->where('account_id', $payableAccount)
                ->selectRaw('COALESCE(SUM(credit - debit), 0) as balance')
                ->value('balance');
        }

        $advance = 0.0;
        if ($advanceAccount) {
            $advance = (float) (clone $lines)
                ->where('account_id', $advanceAccount)
                ->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')
                ->value('balance');
        }

        return [
            'lines' => (clone $lines)->count(),
            'payable' => round(max(0, $payable), 2),
            'advance' => round(max(0, $advance), 2),
        ];
    }

    /**
     * @param  list<string>  $blockers
     */
    private function join(array $blockers): string
    {
        if (count($blockers) <= 1) {
            return (string) ($blockers[0] ?? '');
        }

        $last = array_pop($blockers);

        return implode(', ', $blockers).' and '.$last;
    }
}
