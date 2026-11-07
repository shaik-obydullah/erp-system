<?php

namespace App\Accounting;

use App\Models\Cashbook;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class AccountingPoster
{
    public function __construct(private readonly LedgerService $ledger)
    {
    }

    public function sale(Sale $sale, float $paid, float $due, ?float $cogs = null, ?int $adminId = null): void
    {
        $this->safe(function () use ($sale, $paid, $due, $cogs, $adminId) {
            $paid = round(max(0, $paid), 2);
            $due = round(max(0, $due), 2);
            $total = round($paid + $due, 2);

            if ($total <= 0) {
                return;
            }

            $tax = round(max(0, (float) $sale->vat_amount + (float) $sale->tax_amount), 2);
            $tax = min($tax, $total);
            $revenue = round($total - $tax, 2);

            $lines = [];

            if ($paid > 0) {
                $lines[] = ['account' => 'cash', 'debit' => $paid, 'description' => "Cash received for {$sale->invoice_id}"];
            }

            if ($due > 0) {
                $lines[] = [
                    'account' => 'accounts_receivable',
                    'debit' => $due,
                    'party_type' => 'customer',
                    'party_id' => $sale->fk_user_id,
                    'description' => "Due for {$sale->invoice_id}",
                ];
            }

            if ($revenue > 0) {
                $lines[] = ['account' => 'sales_revenue', 'credit' => $revenue, 'description' => "Sale {$sale->invoice_id}"];
            }

            if ($tax > 0) {
                $lines[] = ['account' => 'sales_tax_payable', 'credit' => $tax, 'description' => "Tax on {$sale->invoice_id}"];
            }

            $entry = $this->ledger->post(
                'saleIncome',
                'sale',
                $sale->id,
                "sale:{$sale->id}:income",
                $sale->created_at ?? now(),
                "Sale #{$sale->invoice_id}",
                $lines,
                $adminId
            );

            if ($entry) {
                // Only fill rows that are not linked yet, so a later replay never
                // overwrites the link from a due-collection or return entry.
                Cashbook::where('table_name', 'sales')
                    ->where('fk_reference_id', $sale->id)
                    ->whereNull('journal_entry_id')
                    ->update(['journal_entry_id' => $entry->id]);

                Transaction::where('fk_reference_id', $sale->id)
                    ->where('type', Transaction::TYPE_SALE_INCOME)
                    ->whereNull('journal_entry_id')
                    ->update(['journal_entry_id' => $entry->id]);
            }

            $cogs = $cogs ?? $this->saleCost($sale);

            if ($cogs > 0) {
                $this->ledger->post(
                    'cogs',
                    'sale',
                    $sale->id,
                    "sale:{$sale->id}:cogs",
                    $sale->created_at ?? now(),
                    "Cost of goods sold for #{$sale->invoice_id}",
                    [
                        ['account' => 'cost_of_goods_sold', 'debit' => round($cogs, 2)],
                        ['account' => 'inventory', 'credit' => round($cogs, 2)],
                    ],
                    $adminId
                );
            }
        });
    }

    public function saleDue(Sale $sale, float $amount, int $transactionId, ?int $adminId = null): void
    {
        $this->safe(function () use ($sale, $amount, $transactionId, $adminId) {
            $amount = round(max(0, $amount), 2);

            if ($amount <= 0) {
                return;
            }

            $date = Transaction::where('id', $transactionId)->value('date') ?? now();

            $entry = $this->ledger->post(
                'saleDue',
                'sale',
                $sale->id,
                "sale:{$sale->id}:due:{$transactionId}",
                $date,
                "Due payment for {$sale->invoice_id}",
                [
                    ['account' => 'cash', 'debit' => $amount, 'description' => 'Due collected'],
                    [
                        'account' => 'accounts_receivable',
                        'credit' => $amount,
                        'party_type' => 'customer',
                        'party_id' => $sale->fk_user_id,
                    ],
                ],
                $adminId
            );

            if ($entry) {
                Transaction::where('id', $transactionId)->update(['journal_entry_id' => $entry->id]);

                Cashbook::where('table_name', 'sales')
                    ->where('fk_reference_id', $sale->id)
                    ->whereNull('journal_entry_id')
                    ->where('in_amount', $amount)
                    ->where('description', 'like', 'Due payment%')
                    ->latest('id')
                    ->first()
                    ?->update(['journal_entry_id' => $entry->id]);
            }
        });
    }

    public function income(Income $income, ?int $adminId = null): void
    {
        $this->safe(function () use ($income, $adminId) {
            $amount = round((float) $income->amount, 2);

            if ($amount <= 0) {
                return;
            }

            $entry = $this->ledger->post(
                'income',
                'income',
                $income->id,
                "income:{$income->id}:income",
                $income->transaction?->date ?? now(),
                $income->description,
                [
                    ['account' => 'cash', 'debit' => $amount, 'description' => $income->description],
                    ['account' => 'general_income', 'credit' => $amount, 'description' => $income->description],
                ],
                $adminId
            );

            if ($entry) {
                Income::where('id', $income->id)->update(['journal_entry_id' => $entry->id]);
                if ($income->fk_transaction_id) {
                    Transaction::where('id', $income->fk_transaction_id)->update(['journal_entry_id' => $entry->id]);
                }

                Cashbook::where('table_name', 'incomes')
                    ->whereIn('fk_reference_id', array_filter([$income->id, $income->fk_transaction_id]))
                    ->whereNull('journal_entry_id')
                    ->update(['journal_entry_id' => $entry->id]);
            }
        });
    }

    public function expense(Expense $expense, ?int $adminId = null): void
    {
        $this->safe(function () use ($expense, $adminId) {
            $amount = round((float) $expense->amount, 2);

            if ($amount <= 0) {
                return;
            }

            $entry = $this->ledger->post(
                'expense',
                'expense',
                $expense->id,
                "expense:{$expense->id}:expense",
                $expense->transaction?->date ?? now(),
                $expense->description,
                [
                    ['account' => 'general_expense', 'debit' => $amount, 'description' => $expense->description],
                    ['account' => 'cash', 'credit' => $amount, 'description' => $expense->description],
                ],
                $adminId
            );

            if ($entry) {
                Expense::where('id', $expense->id)->update(['journal_entry_id' => $entry->id]);
                if ($expense->fk_transaction_id) {
                    Transaction::where('id', $expense->fk_transaction_id)->update(['journal_entry_id' => $entry->id]);
                }

                Cashbook::where('table_name', 'expenses')
                    ->whereIn('fk_reference_id', array_filter([$expense->id, $expense->fk_transaction_id]))
                    ->whereNull('journal_entry_id')
                    ->update(['journal_entry_id' => $entry->id]);
            }
        });
    }

    public function saleReturn(SaleReturn $return, Sale $sale, ?int $adminId = null): void
    {
        $this->safe(function () use ($return, $sale, $adminId) {
            $refund = round((float) $return->refund_amount, 2);
            $date = $return->created_at ?? now();

            if ($refund > 0) {
                $entry = $this->ledger->post(
                    'saleReturn',
                    'sale_return',
                    $return->id,
                    "sale_return:{$return->id}:refund",
                    $date,
                    "Refund for return #{$return->id} (Sale {$sale->invoice_id})",
                    [
                        ['account' => 'sales_returns', 'debit' => $refund],
                        ['account' => 'cash', 'credit' => $refund],
                    ],
                    $adminId
                );

                if ($entry) {
                    Cashbook::where('table_name', 'sale_returns')
                        ->where('fk_reference_id', $return->id)
                        ->whereNull('journal_entry_id')
                        ->update(['journal_entry_id' => $entry->id]);

                    Transaction::where('type', Transaction::TYPE_MISC_EXPENSE)
                        ->where('fk_reference_id', $return->id)
                        ->whereNull('journal_entry_id')
                        ->update(['journal_entry_id' => $entry->id]);
                }
            }

            $stock = Stock::find($return->fk_stock_id);
            $cost = round((int) $return->quantity * (float) ($stock->buy_price ?? 0), 2);

            if ($cost > 0) {
                $this->ledger->post(
                    'saleReturnRestock',
                    'sale_return',
                    $return->id,
                    "sale_return:{$return->id}:restock",
                    $date,
                    "Stock returned for return #{$return->id}",
                    [
                        ['account' => 'inventory', 'debit' => $cost],
                        ['account' => 'cost_of_goods_sold', 'credit' => $cost],
                    ],
                    $adminId
                );
            }
        });
    }

    public function customerFund(Customer $customer, float $amount, int $transactionId, ?string $description = null, ?int $adminId = null): void
    {
        $this->safe(function () use ($customer, $amount, $transactionId, $description, $adminId) {
            $amount = round(max(0, $amount), 2);

            if ($amount <= 0) {
                return;
            }

            $entry = $this->ledger->post(
                'userFund',
                'customer',
                $customer->id,
                "customer_fund:{$transactionId}",
                Transaction::where('id', $transactionId)->value('date') ?? now(),
                $description ?? "Fund added to {$customer->name}",
                [
                    ['account' => 'cash', 'debit' => $amount],
                    ['account' => 'customer_advances', 'credit' => $amount, 'party_type' => 'customer', 'party_id' => $customer->id],
                ],
                $adminId
            );

            if ($entry) {
                Transaction::where('id', $transactionId)->whereNull('journal_entry_id')->update(['journal_entry_id' => $entry->id]);

                Cashbook::where('table_name', 'customers')
                    ->where('fk_reference_id', $customer->id)
                    ->whereNull('journal_entry_id')
                    ->where('in_amount', $amount)
                    ->latest('id')
                    ->first()
                    ?->update(['journal_entry_id' => $entry->id]);
            }
        });
    }

    public function supplierFund(Supplier $supplier, float $amount, int $transactionId, ?string $description = null, ?int $adminId = null): void
    {
        $this->safe(function () use ($supplier, $amount, $transactionId, $description, $adminId) {
            $amount = round(max(0, $amount), 2);

            if ($amount <= 0) {
                return;
            }

            $entry = $this->ledger->post(
                'supplierDeposit',
                'supplier',
                $supplier->id,
                "supplier_fund:{$transactionId}",
                Transaction::where('id', $transactionId)->value('date') ?? now(),
                $description ?? "Fund added to {$supplier->name}",
                [
                    ['account' => 'supplier_advances', 'debit' => $amount, 'party_type' => 'supplier', 'party_id' => $supplier->id],
                    ['account' => 'cash', 'credit' => $amount],
                ],
                $adminId
            );

            if ($entry) {
                Transaction::where('id', $transactionId)->whereNull('journal_entry_id')->update(['journal_entry_id' => $entry->id]);

                Cashbook::where('table_name', 'suppliers')
                    ->where('fk_reference_id', $supplier->id)
                    ->whereNull('journal_entry_id')
                    ->where('out_amount', $amount)
                    ->latest('id')
                    ->first()
                    ?->update(['journal_entry_id' => $entry->id]);
            }
        });
    }

    public function supplierPayment(Supplier $supplier, float $amount, int $transactionId, ?string $description = null, ?int $adminId = null): void
    {
        $this->safe(function () use ($supplier, $amount, $transactionId, $description, $adminId) {
            $amount = round(max(0, $amount), 2);

            if ($amount <= 0) {
                return;
            }

            $entry = $this->ledger->post(
                'supplierPayment',
                'supplier',
                $supplier->id,
                "supplier_payment:{$transactionId}",
                Transaction::where('id', $transactionId)->value('date') ?? now(),
                $description ?? "Payment to {$supplier->name}",
                [
                    ['account' => 'accounts_payable', 'debit' => $amount, 'party_type' => 'supplier', 'party_id' => $supplier->id],
                    ['account' => 'cash', 'credit' => $amount],
                ],
                $adminId
            );

            if ($entry) {
                Transaction::where('id', $transactionId)->whereNull('journal_entry_id')->update(['journal_entry_id' => $entry->id]);

                Cashbook::where('table_name', 'suppliers')
                    ->where('fk_reference_id', $supplier->id)
                    ->whereNull('journal_entry_id')
                    ->where('out_amount', $amount)
                    ->latest('id')
                    ->first()
                    ?->update(['journal_entry_id' => $entry->id]);
            }
        });
    }

    /**
     * Rebuild a customer subledger row imported from CSV as an opening/import entry.
     */
    public function importCustomer(Customer $customer, array $row, string $rowKey, ?int $adminId = null, ?int $cashbookId = null): void
    {
        $this->safe(function () use ($customer, $row, $rowKey, $adminId, $cashbookId) {
            $lines = [];

            $netCash = round((float) ($row['in'] ?? 0) - (float) ($row['out'] ?? 0), 2);
            $receivable = round((float) ($row['receivable'] ?? 0), 2);
            $payable = round((float) ($row['payable'] ?? 0), 2);

            if ($netCash > 0) {
                $lines[] = ['account' => 'cash', 'debit' => $netCash];
            } elseif ($netCash < 0) {
                $lines[] = ['account' => 'cash', 'credit' => abs($netCash)];
            }

            if ($receivable > 0) {
                $lines[] = ['account' => 'accounts_receivable', 'debit' => $receivable, 'party_type' => 'customer', 'party_id' => $customer->id];
            }

            if ($payable > 0) {
                $lines[] = ['account' => 'customer_advances', 'credit' => $payable, 'party_type' => 'customer', 'party_id' => $customer->id];
            }

            if (count($lines) < 2) {
                return;
            }

            $diff = round(array_sum(array_column($lines, 'debit')) - array_sum(array_column($lines, 'credit')), 2);

            if ($diff > 0) {
                $lines[] = ['account' => 'opening_balance_equity', 'credit' => $diff];
            } elseif ($diff < 0) {
                $lines[] = ['account' => 'opening_balance_equity', 'debit' => abs($diff)];
            }

            $entry = $this->ledger->post(
                'import',
                'customer',
                $customer->id,
                "import:customer:{$customer->id}:{$rowKey}",
                $row['date'] ?? now(),
                $row['description'] ?? 'Imported customer transaction',
                $lines,
                $adminId
            );

            if ($entry && $cashbookId) {
                Cashbook::where('id', $cashbookId)->whereNull('journal_entry_id')->update(['journal_entry_id' => $entry->id]);
            }
        });
    }

    /**
     * Rebuild a supplier subledger row imported from CSV as an opening/import entry.
     */
    public function importSupplier(Supplier $supplier, array $row, string $rowKey, ?int $adminId = null, ?int $cashbookId = null): void
    {
        $this->safe(function () use ($supplier, $row, $rowKey, $adminId, $cashbookId) {
            $lines = [];

            $netCash = round((float) ($row['in'] ?? 0) - (float) ($row['out'] ?? 0), 2);
            $payable = round((float) ($row['payable'] ?? 0), 2);
            $receivable = round((float) ($row['receivable'] ?? 0), 2);

            if ($netCash > 0) {
                $lines[] = ['account' => 'cash', 'debit' => $netCash];
            } elseif ($netCash < 0) {
                $lines[] = ['account' => 'cash', 'credit' => abs($netCash)];
            }

            if ($payable > 0) {
                $lines[] = ['account' => 'accounts_payable', 'credit' => $payable, 'party_type' => 'supplier', 'party_id' => $supplier->id];
            }

            if ($receivable > 0) {
                $lines[] = ['account' => 'supplier_advances', 'debit' => $receivable, 'party_type' => 'supplier', 'party_id' => $supplier->id];
            }

            if (count($lines) < 2) {
                return;
            }

            $diff = round(array_sum(array_column($lines, 'debit')) - array_sum(array_column($lines, 'credit')), 2);

            if ($diff > 0) {
                $lines[] = ['account' => 'opening_balance_equity', 'credit' => $diff];
            } elseif ($diff < 0) {
                $lines[] = ['account' => 'opening_balance_equity', 'debit' => abs($diff)];
            }

            $entry = $this->ledger->post(
                'import',
                'supplier',
                $supplier->id,
                "import:supplier:{$supplier->id}:{$rowKey}",
                $row['date'] ?? now(),
                $row['description'] ?? 'Imported supplier transaction',
                $lines,
                $adminId
            );

            if ($entry && $cashbookId) {
                Cashbook::where('id', $cashbookId)->whereNull('journal_entry_id')->update(['journal_entry_id' => $entry->id]);
            }
        });
    }

    private function saleCost(Sale $sale): float
    {
        $stored = (float) $sale->buy_price;

        if ($stored > 0) {
            return round($stored, 2);
        }

        $sale->loadMissing('details');

        // Prefer the cost snapshot taken at sale time.
        $snapshot = round($sale->details->sum(
            fn ($detail) => (float) $detail->sale_stock * (float) ($detail->cost_price ?? 0)
        ), 2);

        if ($snapshot > 0) {
            return $snapshot;
        }

        $prices = Stock::whereIn('id', $sale->details->pluck('fk_stock_id')->filter()->all())
            ->pluck('buy_price', 'id');

        return round($sale->details->sum(
            fn ($detail) => (float) $detail->sale_stock * (float) ($prices[$detail->fk_stock_id] ?? 0)
        ), 2);
    }

    private function safe(callable $callback): void
    {
        if (! config('accounting.enabled')) {
            return;
        }

        try {
            $callback();
        } catch (\Throwable $e) {
            Log::error('Accounting posting failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            if (config('accounting.enforced')) {
                throw $e;
            }
        }
    }
}
