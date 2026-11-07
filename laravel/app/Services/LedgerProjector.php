<?php

namespace App\Services;

use App\Accounting\AccountingPoster;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\ShipmentCost;
use App\Models\Supplier;
use App\Models\Transaction;

/**
 * Replays the existing simple-layer rows (sales, dues, returns, incomes,
 * expenses, funds) into the double-entry ledger. Posting is idempotent: every
 * event uses a stable source_key, so running this repeatedly never
 * double-posts.
 */
class LedgerProjector
{
    public function __construct(private readonly AccountingPoster $poster)
    {
    }

    /**
     * @return array<string, int> number of source rows processed per group
     */
    public function run(?array $only = null): array
    {
        $only = $only ?: ['sales', 'dues', 'returns', 'incomes', 'expenses', 'funds', 'shipment_costs'];
        $counts = [];

        foreach ($only as $group) {
            $counts[$group] = match ($group) {
                'sales' => $this->sales(),
                'dues' => $this->saleDues(),
                'returns' => $this->saleReturns(),
                'incomes' => $this->incomes(),
                'expenses' => $this->expenses(),
                'funds' => $this->funds(),
                'shipment_costs' => $this->shipmentCosts(),
                default => 0,
            };
        }

        return $counts;
    }

    public function sales(): int
    {
        $count = 0;

        Sale::query()->orderBy('id')->chunkById(200, function ($sales) use (&$count) {
            foreach ($sales as $sale) {
                // The original saleIncome transaction holds the amounts as they
                // were at sale time (before dues were collected / returns made).
                $original = Transaction::query()
                    ->where('type', Transaction::TYPE_SALE_INCOME)
                    ->where('fk_reference_id', $sale->id)
                    ->orderBy('id')
                    ->first();

                $paid = (float) ($original->paid_amount ?? $sale->paid_amount);
                $due = (float) ($original->due_amount ?? $sale->sale_due);

                $this->poster->sale($sale, $paid, $due, null, $sale->created_by);
                $count++;
            }
        });

        return $count;
    }

    public function saleDues(): int
    {
        $count = 0;

        Transaction::query()
            ->where('type', Transaction::TYPE_SALE_DUE)
            ->orderBy('id')
            ->chunkById(200, function ($transactions) use (&$count) {
                foreach ($transactions as $transaction) {
                    $sale = Sale::find($transaction->fk_reference_id);

                    if (! $sale) {
                        continue;
                    }

                    $amount = (float) ($transaction->paid_amount ?: $transaction->amount);
                    $this->poster->saleDue($sale, $amount, $transaction->id, $transaction->created_by);
                    $count++;
                }
            });

        return $count;
    }

    public function saleReturns(): int
    {
        $count = 0;

        SaleReturn::query()
            ->whereIn('status', ['approved', 'completed'])
            ->orderBy('id')
            ->chunkById(200, function ($returns) use (&$count) {
                foreach ($returns as $return) {
                    $sale = Sale::find($return->fk_sale_id);

                    if (! $sale) {
                        continue;
                    }

                    $this->poster->saleReturn($return, $sale, $return->created_by);
                    $count++;
                }
            });

        return $count;
    }

    public function incomes(): int
    {
        $count = 0;

        Income::query()
            ->where('table_name', '!=', 'sales')
            ->orderBy('id')
            ->chunkById(200, function ($incomes) use (&$count) {
                foreach ($incomes as $income) {
                    $this->poster->income($income, $income->created_by);
                    $count++;
                }
            });

        return $count;
    }

    public function expenses(): int
    {
        $count = 0;

        Expense::query()->orderBy('id')->chunkById(200, function ($expenses) use (&$count) {
            foreach ($expenses as $expense) {
                $this->poster->expense($expense, $expense->created_by);
                $count++;
            }
        });

        return $count;
    }

    public function funds(): int
    {
        $count = 0;

        $types = [
            Transaction::TYPE_USER_FUND,
            Transaction::TYPE_SUPPLIER_DEPOSIT,
            Transaction::TYPE_SUPPLIER_PAYMENT,
        ];

        Transaction::query()
            ->whereIn('type', $types)
            ->orderBy('id')
            ->chunkById(200, function ($transactions) use (&$count) {
                foreach ($transactions as $transaction) {
                    $amount = (float) ($transaction->amount ?: $transaction->paid_amount);

                    if ($transaction->type === Transaction::TYPE_USER_FUND) {
                        // Include soft-deleted parties: their cashbook rows still
                        // affect the cash reconciliation even after the party is archived.
                        $customer = Customer::withTrashed()->find($transaction->fk_reference_id);
                        if ($customer) {
                            $this->poster->customerFund($customer, $amount, $transaction->id, null, $transaction->created_by);
                            $count++;
                        }
                    } else {
                        $supplier = Supplier::withTrashed()->find($transaction->fk_reference_id);
                        if ($supplier) {
                            if ($transaction->type === Transaction::TYPE_SUPPLIER_PAYMENT) {
                                $this->poster->supplierPayment($supplier, $amount, $transaction->id, null, $transaction->created_by);
                            } else {
                                $this->poster->supplierFund($supplier, $amount, $transaction->id, null, $transaction->created_by);
                            }
                            $count++;
                        }
                    }
                }
            });

        return $count;
    }

    public function shipmentCosts(): int
    {
        $count = 0;

        ShipmentCost::query()->orderBy('id')->chunkById(200, function ($costs) use (&$count) {
            foreach ($costs as $cost) {
                $this->poster->shipmentCost($cost, $cost->created_by);
                $count++;
            }
        });

        return $count;
    }
}
