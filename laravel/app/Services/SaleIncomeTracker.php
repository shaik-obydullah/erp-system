<?php

namespace App\Services;

use App\Models\Income;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Transaction;

class SaleIncomeTracker
{
    /**
     * Recalculate the income mirror for a sale net of approved/completed returns.
     *
     * The original saleIncome transaction and journal entry stay immutable so the
     * ledger keeps the sale-time posting. Only the incomes mirror row is updated,
     * which keeps revenue reports net of refunds without double counting VAT:
     * VatReturnController already excludes table_name = 'sales' incomes and
     * subtracts sale_returns separately.
     *
     * Recalculation is idempotent because the original split is always read from
     * the immutable transaction and the sale snapshot, never from the income row.
     */
    public function sync(Sale $sale, ?int $adminId = null): ?Income
    {
        $transaction = Transaction::query()
            ->where('type', Transaction::TYPE_SALE_INCOME)
            ->where('fk_reference_id', $sale->id)
            ->first();

        if (! $transaction) {
            return null;
        }

        $income = Income::query()
            ->where('table_name', 'sales')
            ->where('fk_transaction_id', $transaction->id)
            ->first();

        if (! $income) {
            return null;
        }

        $gross = round((float) $transaction->amount, 2);

        if ($gross <= 0) {
            return $income;
        }

        $net = round((float) $sale->net_price, 2);
        $vat = round((float) $sale->vat_amount + (float) $sale->tax_amount, 2);

        // The sale snapshot normally carries the original split. If it is
        // incomplete, rebuild it without reading an already-adjusted mirror.
        if (round($net + $vat, 2) !== $gross) {
            if (round((float) $income->amount, 2) === $gross) {
                $vat = round(min(max(0, (float) $income->vat_amount), $gross), 2);
            } elseif ((float) $income->vat_rate > 0) {
                $vat = round($gross * (float) $income->vat_rate / (100 + (float) $income->vat_rate), 2);
            } else {
                $vat = 0.0;
            }

            $net = round($gross - $vat, 2);
        }

        $returns = SaleReturn::query()
            ->where('fk_sale_id', $sale->id)
            ->whereIn('status', ['approved', 'completed'])
            ->selectRaw('COALESCE(SUM(refund_amount), 0) as refund, COALESCE(SUM(net_amount), 0) as net, COALESCE(SUM(vat_amount), 0) as vat')
            ->first();

        $newGross = round(max(0, $gross - (float) ($returns->refund ?? 0)), 2);
        $newNet = round(max(0, $net - (float) ($returns->net ?? 0)), 2);
        $newVat = round(max(0, $vat - (float) ($returns->vat ?? 0)), 2);

        $changed = round((float) $income->amount, 2) !== $newGross
            || round((float) $income->net_amount, 2) !== $newNet
            || round((float) $income->vat_amount, 2) !== $newVat;

        if (! $changed) {
            return $income;
        }

        $oldData = $income->toArray();

        $income->update([
            'amount' => $newGross,
            'net_amount' => $newNet,
            'vat_amount' => $newVat,
            'updated_by' => $adminId,
        ]);

        ActivityLogger::updated('Sale Income', $income, $income->getChanges(), $oldData);

        return $income;
    }
}
